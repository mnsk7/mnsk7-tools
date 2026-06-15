#!/usr/bin/env python3
"""Assign manual variant groups for selected SKUs in BaseLinker and WooCommerce."""
import argparse
import base64
import json
import os
import re
import time
from collections import defaultdict
from urllib.parse import quote
from urllib.request import Request, urlopen

from baselinker_sync_products import (
    BaseLinkerClient,
    WooClient,
    chunked,
    load_env,
    merge_meta_data,
    normalize_key,
    parse_feature_rows,
    resolve_inventory_id,
)


GROUP_DEFINITIONS = [
    {
        "group": "frez-palcowy-stal-4p-hss-hrc65",
        "axis": "srednica-trzpienia,srednica",
        "skus": [
            "2011091144",
            "2011091146",
            "2011091148",
            "4S10x10x22x72HSS",
            "4S12x12x26x83HSS",
            "4S2x6x7x51HSS",
            "4S3x6x8x52HSS",
            "4S4x6x11x55HSS",
            "4S6x6x13x57HSS",
            "4S8x8x19x63HSS",
        ],
    },
    {"group": "frez-palcowy-2p-vhm-hrc45", "axis": "srednica", "skus": ["4661", "4658"]},
    {"group": "grawer-granit-vhm", "axis": "kat-skosu", "skus": ["4244", "4242"]},
    {"group": "diament-prosty-granit-marmur", "axis": "srednica", "skus": ["5873", "5871", "5872"]},
    {"group": "pcd-granit", "axis": "srednica,kat-skosu", "skus": ["3691", "3692", "3693", "3694", "3695"]},
    {
        "group": "gwintownik-maszynowy-hss-ti",
        "axis": "typ",
        "skus": ["404090931", "404090933", "404090934", "404090935", "404090936"],
    },
    {"group": "frez-do-gwintowania-unc", "axis": "typ", "skus": ["G125009", "G125010", "G125011", "G125012"]},
    {"group": "fazownik-90-3p-vhm", "axis": "srednica", "skus": ["H131204050", "H131206050", "H131208060"]},
    {
        "group": "frez-typ-u-drewno-rowki",
        "axis": "srednica-trzpienia,srednica",
        "skus": ["H0502832", "H0502830", "H0502818", "H0502822"],
    },
    {
        "group": "plytki-wieloostrzowe",
        "axis": "model",
        "name_regex_normalized": r"^p\s*ytka\s+wieloostrzowa\b",
        "expected_count": 22,
    },
]


def parse_features_map(raw_value):
    if isinstance(raw_value, dict):
        result = {}
        for key, value in raw_value.items():
            key_text = str(key).strip()
            value_text = str(value).strip()
            if key_text and value_text:
                result[key_text] = value_text
        return result
    if isinstance(raw_value, str):
        result = {}
        for key, value in parse_feature_rows(raw_value):
            key_text = str(key).strip()
            value_text = str(value).strip()
            if key_text and value_text:
                result[key_text] = value_text
        return result
    return {}


def get_product_name(product):
    direct = str(product.get("name", "")).strip()
    if direct:
        return direct
    text_fields = product.get("text_fields", {})
    if not isinstance(text_fields, dict):
        return ""
    for key in ("name|pl", "name"):
        value = str(text_fields.get(key, "")).strip()
        if value:
            return value
    for key, value in text_fields.items():
        if str(key).startswith("name|"):
            value_text = str(value).strip()
            if value_text:
                return value_text
    return ""


def normalize_sku_spaces(sku):
    return re.sub(r"\s+", " ", str(sku).strip())


def build_group_assignments(products_by_sku):
    assignments = {}
    missing = []
    for definition in GROUP_DEFINITIONS:
        group_key = definition["group"]
        axis_key = definition["axis"]
        group_skus = list(definition.get("skus", []))

        if (
            "name_contains" in definition
            or "sku_prefix" in definition
            or "name_regex_normalized" in definition
        ):
            needle = normalize_key(definition.get("name_contains", ""))
            prefix = str(definition.get("sku_prefix", "")).strip()
            name_regex_normalized = str(definition.get("name_regex_normalized", "")).strip()
            regex = re.compile(name_regex_normalized, flags=re.IGNORECASE) if name_regex_normalized else None
            dynamic_skus = []
            for sku, product in products_by_sku.items():
                name = get_product_name(product)
                normalized_name = normalize_key(name)
                by_name = bool(needle and needle in normalized_name)
                by_prefix = bool(prefix and sku.startswith(prefix))
                by_regex = bool(regex and regex.search(normalized_name))
                if by_name or by_prefix or by_regex:
                    dynamic_skus.append(sku)
            dynamic_skus.sort()
            group_skus = dynamic_skus
            expected = int(definition.get("expected_count", 0))
            if expected and len(group_skus) != expected:
                print(
                    f"[WARN] group={group_key} expected_count={expected} actual={len(group_skus)} "
                    "(using all matched SKUs anyway)"
                )

        for sku in group_skus:
            if sku not in products_by_sku:
                missing.append((group_key, sku))
                continue
            assignments[sku] = {"group": group_key, "axis": axis_key}

    return assignments, missing


def update_bl_product(bl, inventory_id, product_id, sku, product_data, language, group_key, axis_key, apply_changes):
    text_fields = product_data.get("text_fields", {})
    lang_key = f"features|{language}"
    keys_to_patch = []
    if isinstance(text_fields, dict):
        if lang_key in text_fields:
            keys_to_patch.append(lang_key)
        if "features" in text_fields:
            keys_to_patch.append("features")
    if not keys_to_patch:
        keys_to_patch = [lang_key]

    patch_text_fields = {}
    for key in keys_to_patch:
        feature_map = parse_features_map(text_fields.get(key))
        feature_map["MNSK7 grupa wariantu"] = group_key
        feature_map["MNSK7 os wariantu"] = axis_key
        patch_text_fields[key] = feature_map

    if not apply_changes:
        print(f"[DRY][BL] sku={sku} product_id={product_id} group={group_key} axis={axis_key}")
        return

    bl.call(
        "addInventoryProduct",
        {
            "inventory_id": inventory_id,
            "product_id": int(product_id),
            "sku": sku,
            "text_fields": patch_text_fields,
        },
    )
    print(f"[OK][BL] sku={sku} product_id={product_id} group={group_key} axis={axis_key}")


def update_woo_product(woo, woo_products_by_sku, sku, group_key, axis_key, apply_changes):
    existing = woo_products_by_sku.get(sku)
    if not existing:
        existing = woo_products_by_sku.get(normalize_sku_spaces(sku))
    if not existing:
        print(f"[MISS][WOO] sku={sku}")
        return False

    meta_updates = [
        {"key": "_mnsk7_bl_variant_group", "value": group_key},
        {"key": "_mnsk7_bl_variant_axis", "value": axis_key},
    ]
    payload = {"meta_data": merge_meta_data(existing.get("meta_data", []), meta_updates)}

    if not apply_changes:
        print(f"[DRY][WOO] sku={sku} wc_id={existing.get('id')} group={group_key} axis={axis_key}")
        return True

    woo.update_product(existing.get("id"), payload)
    print(f"[OK][WOO] sku={sku} wc_id={existing.get('id')} group={group_key} axis={axis_key}")
    return True


def wp_api_request(base_url, user, app_password, method, path, payload=None):
    url = base_url.rstrip("/") + "/wp-json/wp/v2/" + path.lstrip("/")
    auth = base64.b64encode(f"{user}:{app_password}".encode("utf-8")).decode("ascii")
    headers = {
        "Authorization": f"Basic {auth}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "mnsk7-offer-pages/1.0",
    }
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    request = Request(url, data=data, method=method.upper())
    for key, value in headers.items():
        request.add_header(key, value)
    with urlopen(request, timeout=60) as response:
        raw = response.read().decode("utf-8")
        return json.loads(raw) if raw else {}


def ensure_offer_pages(base_url, user, app_password, groups_with_products, page_prefix, apply_changes):
    pages = []
    for group in groups_with_products:
        group_slug = group["group"]
        page_slug = f"{page_prefix}{group_slug}"
        title = f"Oferta: {group_slug}"
        product_id = group["wc_product_id"]
        content = f'[product_page id="{product_id}"]'

        existing = wp_api_request(base_url, user, app_password, "GET", f"pages?slug={quote(page_slug)}")
        existing_id = existing[0]["id"] if isinstance(existing, list) and existing else None
        page_url = f"{base_url.rstrip('/')}/{page_slug}/"

        if not apply_changes:
            pages.append({"group": group_slug, "slug": page_slug, "url": page_url, "product_id": product_id})
            continue

        payload = {
            "title": title,
            "slug": page_slug,
            "status": "publish",
            "content": content,
        }
        if existing_id:
            wp_api_request(base_url, user, app_password, "POST", f"pages/{existing_id}", payload)
        else:
            wp_api_request(base_url, user, app_password, "POST", "pages", payload)
        pages.append({"group": group_slug, "slug": page_slug, "url": page_url, "product_id": product_id})
        time.sleep(0.2)
    return pages


def main():
    parser = argparse.ArgumentParser(description="Assign manual variant groups by SKU")
    parser.add_argument("--env-file", default=".env", help="Path to .env")
    parser.add_argument("--language", default="pl", help="BaseLinker language key for features")
    parser.add_argument("--apply", action="store_true", help="Write changes to BaseLinker/Woo")
    parser.add_argument("--create-pages", action="store_true", help="Create/update common offer pages in WP")
    parser.add_argument("--page-prefix", default="oferta-", help="Slug prefix for offer pages")
    args = parser.parse_args()

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)

    baselinker_token = (
        env.get("BASELINKER_API_TOKEN")
        or env.get("BASE_API_TOKEN")
        or env.get("base_api_token")
        or ""
    ).strip()
    inventory_id = env.get("BASELINKER_INVENTORY_ID", "").strip()
    woo_base_url = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    woo_key = (
        env.get("WOO_CONSUMER_KEY")
        or env.get("Woo_Klucz_konsumenta")
        or env.get("WC_CONSUMER_KEY")
        or ""
    ).strip()
    woo_secret = (
        env.get("WOO_CONSUMER_SECRET")
        or env.get("Woo_Tajny_konsumenta")
        or env.get("WC_CONSUMER_SECRET")
        or ""
    ).strip()
    wp_user = (env.get("WP_USER") or "").strip()
    wp_app_password = (env.get("WP_APP_PASSWORD") or "").strip()

    if not baselinker_token:
        raise SystemExit("Missing BASELINKER_API_TOKEN")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL/WOO_BASE_URL and Woo credentials")

    bl = BaseLinkerClient(baselinker_token)
    inventory_id = resolve_inventory_id(bl, inventory_id)
    woo = WooClient(woo_base_url, woo_key, woo_secret)

    all_ids = bl.list_product_ids(inventory_id)
    if not all_ids:
        raise SystemExit("No products found in BaseLinker inventory")

    products_by_sku = {}
    print(f"Fetching BaseLinker products: {len(all_ids)} ids")
    for ids_batch in chunked(all_ids, 100):
        payload = bl.get_products_data(inventory_id, ids_batch)
        for product_id, product in payload.items():
            sku = str(product.get("sku", "")).strip()
            if not sku:
                continue
            row = dict(product)
            row["_bl_product_id"] = int(product_id)
            products_by_sku[sku] = row
        time.sleep(0.15)

    assignments, missing_skus = build_group_assignments(products_by_sku)
    print(f"Prepared assignments: {len(assignments)} SKUs")
    if missing_skus:
        print("Missing SKUs in BaseLinker:")
        for group_key, sku in missing_skus:
            print(f"  - group={group_key} sku={sku}")

    woo_products_by_sku = {}
    try:
        woo_products = woo.list_products(status="any")
        for item in woo_products:
            sku = str(item.get("sku", "")).strip()
            if sku:
                woo_products_by_sku[sku] = item
                woo_products_by_sku[normalize_sku_spaces(sku)] = item
        print(f"Woo products indexed by SKU: {len(woo_products_by_sku)}")
    except Exception as error:
        raise SystemExit(f"Failed to fetch Woo products: {error}")

    bl_ok = 0
    woo_ok = 0
    woo_missing = 0
    group_products = defaultdict(list)

    for sku in sorted(assignments.keys()):
        assignment = assignments[sku]
        product = products_by_sku[sku]
        product_id = product.get("_bl_product_id")
        if not product_id:
            print(f"[MISS][BL] sku={sku} missing product_id in payload")
            continue

        try:
            update_bl_product(
                bl=bl,
                inventory_id=inventory_id,
                product_id=product_id,
                sku=sku,
                product_data=product,
                language=args.language,
                group_key=assignment["group"],
                axis_key=assignment["axis"],
                apply_changes=args.apply,
            )
            bl_ok += 1
        except Exception as error:
            print(f"[ERR][BL] sku={sku} {error}")

        try:
            if update_woo_product(
                woo=woo,
                woo_products_by_sku=woo_products_by_sku,
                sku=sku,
                group_key=assignment["group"],
                axis_key=assignment["axis"],
                apply_changes=args.apply,
            ):
                woo_ok += 1
                wc_product = woo_products_by_sku.get(sku) or woo_products_by_sku.get(normalize_sku_spaces(sku))
                if wc_product:
                    group_products[assignment["group"]].append(wc_product)
            else:
                woo_missing += 1
        except Exception as error:
            print(f"[ERR][WOO] sku={sku} {error}")
        time.sleep(0.15)

    pages = []
    if args.create_pages:
        if not wp_user or not wp_app_password:
            raise SystemExit("Missing WP_USER/WP_APP_PASSWORD for page creation")

        page_groups = []
        for definition in GROUP_DEFINITIONS:
            group_key = definition["group"]
            products = group_products.get(group_key, [])
            if not products:
                continue
            products_sorted = sorted(
                products,
                key=lambda item: (
                    0 if item.get("stock_status") == "instock" else 1,
                    float(item.get("price") or 999999),
                    item.get("id") or 0,
                ),
            )
            representative = products_sorted[0]
            page_groups.append(
                {
                    "group": group_key,
                    "axis": definition.get("axis", ""),
                    "wc_product_id": representative.get("id"),
                    "wc_product_sku": representative.get("sku", ""),
                    "count": len(products),
                }
            )

        pages = ensure_offer_pages(
            base_url=woo_base_url,
            user=wp_user,
            app_password=wp_app_password,
            groups_with_products=page_groups,
            page_prefix=args.page_prefix,
            apply_changes=args.apply,
        )

    print("")
    print("Summary")
    print(f"- apply: {args.apply}")
    print(f"- assignments: {len(assignments)}")
    print(f"- bl_updated_or_planned: {bl_ok}")
    print(f"- woo_updated_or_planned: {woo_ok}")
    print(f"- woo_missing: {woo_missing}")
    if args.create_pages:
        print(f"- pages_created_or_planned: {len(pages)}")
        print(json.dumps(pages, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
