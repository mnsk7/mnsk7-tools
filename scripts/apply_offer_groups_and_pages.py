#!/usr/bin/env python3
"""Apply manual offer groups from raw chat text and create/update common offer pages."""
import argparse
import base64
import json
import os
import re
import sys
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


def strip_chat_prefix(line):
    line = line.strip()
    if not line:
        return ""
    line = re.sub(r"^\[\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}\]\s*\d+:\s*", "", line).strip()
    return line


def parse_raw_blocks(text):
    lines = [strip_chat_prefix(line) for line in text.splitlines()]
    lines = [line for line in lines if line]
    blocks = []
    current = []
    for line in lines:
        if line.startswith("[") and "]" in line and current:
            blocks.append(current)
            current = [line]
            continue
        current.append(line)
    if current:
        blocks.append(current)
    return blocks


def is_sku_token(token):
    token = token.strip()
    if not token:
        return False
    # Accept mixed SKU formats used by this catalog.
    return bool(re.match(r"^[A-Za-z0-9._*xX-]{3,}$", token))


def looks_like_axis_block(lines):
    text = " ".join(lines).lower()
    if "хз" in text:
        return True
    keywords = [
        "диаметр",
        "длина",
        "зажим",
        "угол",
        "шарик",
        "тип",
        "по картинке",
        "по размерам",
        "рабоч",
        "общая длина",
        "er",
    ]
    return any(keyword in text for keyword in keywords)


def axis_text_to_axis(axis_text):
    text = normalize_key(axis_text)
    if not text or text == "xz":
        return "model"

    axis = []
    if "srednica-trzpienia" in text or "trzpien" in text or "zazhim" in text or "zazhym" in text:
        axis.append("srednica-trzpienia")
    if "srednica" in text or "diametr" in text or "raboc" in text:
        axis.append("srednica")
    if "kat" in text or "ugol" in text:
        axis.append("kat-skosu")
    if "tip" in text or "typ" in text:
        axis.append("typ")
    if "sharik" in text:
        axis.append("r")
    if "dlina" in text and "roboc" in text:
        axis.append("dlugosc-robocza-h")
    if "obsh" in text or "calkow" in text:
        axis.append("dlugosc-calkowita-l")
    if "er" in text and "er" not in axis:
        axis.append("er")

    if not axis:
        return "model"

    unique = []
    for item in axis:
        if item not in unique:
            unique.append(item)
    return ",".join(unique)


def parse_groups(raw_text):
    groups = []
    lines = [strip_chat_prefix(line) for line in raw_text.splitlines() if strip_chat_prefix(line)]
    pending_skus = []
    for line in lines:
        # separator by explicit axis phrase if pending skus already collected
        if looks_like_axis_block([line]) and pending_skus:
            axis = axis_text_to_axis(line)
            group_id = len(groups) + 1
            groups.append(
                {
                    "group": f"mnsk7-offer-{group_id:02d}",
                    "axis": axis,
                    "skus": pending_skus[:],
                    "axis_note": line,
                }
            )
            pending_skus = []
            continue

        # otherwise treat line as list of possible SKUs (single token line).
        if is_sku_token(line):
            pending_skus.append(line)
            continue

        # free text boundary
        if pending_skus:
            axis = axis_text_to_axis(line)
            group_id = len(groups) + 1
            groups.append(
                {
                    "group": f"mnsk7-offer-{group_id:02d}",
                    "axis": axis,
                    "skus": pending_skus[:],
                    "axis_note": line,
                }
            )
            pending_skus = []

    if pending_skus:
        group_id = len(groups) + 1
        groups.append(
            {
                "group": f"mnsk7-offer-{group_id:02d}",
                "axis": "model",
                "skus": pending_skus[:],
                "axis_note": "fallback-model",
            }
        )

    # remove small accidental groups
    groups = [group for group in groups if len(group["skus"]) >= 2]
    return groups


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
        # Keep only current canonical keys.
        feature_map.pop("MNK7 grupa wariantu", None)
        feature_map.pop("MNK7 os wariantu", None)
        feature_map["MNSK7 grupa wariantu"] = group_key
        feature_map["MNSK7 os wariantu"] = axis_key
        patch_text_fields[key] = feature_map

    if not apply_changes:
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


def wp_api_request(base_url, user, app_password, method, path, payload=None):
    url = base_url.rstrip("/") + "/wp-json/wp/v2/" + path.lstrip("/")
    auth = base64.b64encode(f"{user}:{app_password}".encode("utf-8")).decode("ascii")
    headers = {
        "Authorization": f"Basic {auth}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "mnsk7-offer-sync/1.0",
    }
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    request = Request(url, data=data, method=method.upper())
    for key, value in headers.items():
        request.add_header(key, value)
    with urlopen(request, timeout=60) as response:
        raw = response.read().decode("utf-8")
        return json.loads(raw) if raw else {}


def ensure_offer_pages(base_url, user, app_password, groups_with_products, apply_changes):
    pages = []
    for group in groups_with_products:
        group_slug = group["group"]
        page_slug = f"oferta-{group_slug}"
        title = f"Oferta: {group_slug}"
        product_id = group["wc_product_id"]
        content = f'[product_page id="{product_id}"]'

        existing = wp_api_request(base_url, user, app_password, "GET", f"pages?slug={quote(page_slug)}")
        existing_id = existing[0]["id"] if isinstance(existing, list) and existing else None

        if not apply_changes:
            pages.append({"group": group_slug, "slug": page_slug, "url": f"{base_url.rstrip('/')}/{page_slug}/"})
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
        pages.append({"group": group_slug, "slug": page_slug, "url": f"{base_url.rstrip('/')}/{page_slug}/"})
        time.sleep(0.2)
    return pages


def main():
    parser = argparse.ArgumentParser(description="Apply grouped offers + create common pages")
    parser.add_argument("--raw-file", required=True, help="Path to text file with raw chat list")
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--language", default="pl")
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    raw_text = open(args.raw_file, "r", encoding="utf-8").read()
    groups = parse_groups(raw_text)
    if not groups:
        raise SystemExit("No groups parsed from raw file")

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
        raise SystemExit("Missing BASELINKER token")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing Woo credentials")
    if not wp_user or not wp_app_password:
        raise SystemExit("Missing WP_USER/WP_APP_PASSWORD")

    bl = BaseLinkerClient(baselinker_token)
    inventory_id = resolve_inventory_id(bl, inventory_id)
    woo = WooClient(woo_base_url, woo_key, woo_secret)

    all_ids = bl.list_product_ids(inventory_id)
    products_by_sku = {}
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

    woo_products = woo.list_products(status="any")
    woo_by_sku = {}
    for item in woo_products:
        sku = str(item.get("sku", "")).strip()
        if sku:
            woo_by_sku[sku] = item

    group_products = defaultdict(list)
    missing_bl = []
    missing_woo = []

    for group in groups:
        for sku in group["skus"]:
            product_data = products_by_sku.get(sku)
            if not product_data:
                missing_bl.append((group["group"], sku))
                continue
            try:
                update_bl_product(
                    bl,
                    inventory_id,
                    product_data["_bl_product_id"],
                    sku,
                    product_data,
                    args.language,
                    group["group"],
                    group["axis"],
                    args.apply,
                )
            except Exception as error:
                print(f"[ERR][BL] {sku}: {error}")

            woo_item = woo_by_sku.get(sku)
            if not woo_item:
                missing_woo.append((group["group"], sku))
                continue
            payload = {
                "meta_data": merge_meta_data(
                    woo_item.get("meta_data", []),
                    [
                        {"key": "_mnsk7_bl_variant_group", "value": group["group"]},
                        {"key": "_mnsk7_bl_variant_axis", "value": group["axis"]},
                    ],
                )
            }
            if args.apply:
                try:
                    woo.update_product(woo_item["id"], payload)
                except Exception as error:
                    print(f"[ERR][WOO] {sku}: {error}")
            group_products[group["group"]].append(woo_item)
            time.sleep(0.1)

    page_groups = []
    for group in groups:
        products = group_products.get(group["group"], [])
        if not products:
            continue
        # pick cheapest in-stock, fallback first
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
                "group": group["group"],
                "axis": group["axis"],
                "wc_product_id": representative["id"],
                "wc_product_sku": representative.get("sku", ""),
                "axis_note": group.get("axis_note", ""),
                "count": len(products),
            }
        )

    pages = ensure_offer_pages(woo_base_url, wp_user, wp_app_password, page_groups, args.apply)

    print(json.dumps(
        {
            "apply": args.apply,
            "parsed_groups": len(groups),
            "pages": pages,
            "missing_bl": missing_bl,
            "missing_woo": missing_woo,
        },
        ensure_ascii=False,
        indent=2,
    ))


if __name__ == "__main__":
    main()
