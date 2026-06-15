#!/usr/bin/env python3
"""Sync existing grouped-offer products from BaseLinker params into Woo only.

No Woo products are created here. For the selected offer groups we:
- write exact MNSK7 group/axis fields into BaseLinker params;
- replace Woo attributes with mapped BaseLinker params only;
- keep the product in its assigned MNSK7 variant group.
"""
import argparse
import json
import os
import urllib.parse

from apply_raw_offer_groups_wpcli import parse_groups, update_bl_product
from baselinker_sync_products import (
    BaseLinkerClient,
    WooClient,
    build_wc_attributes,
    chunked,
    extract_features,
    load_env,
    merge_meta_data,
    resolve_inventory_id,
)


def load_config(env_file):
    env = {}
    env.update(load_env(env_file))
    env.update(os.environ)

    token = (
        env.get("BASELINKER_API_TOKEN")
        or env.get("BASE_API_TOKEN")
        or env.get("base_api_token")
        or ""
    ).strip()
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

    if not token:
        raise SystemExit("Missing BASELINKER_API_TOKEN")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL/WOO_BASE_URL and WOO_CONSUMER_KEY + WOO_CONSUMER_SECRET")

    return {
        "token": token,
        "inventory_id": env.get("BASELINKER_INVENTORY_ID", "").strip(),
        "language": env.get("BASELINKER_LANGUAGE", "pl").strip() or "pl",
        "woo_base_url": woo_base_url,
        "woo_key": woo_key,
        "woo_secret": woo_secret,
    }


def find_bl_product_id_by_sku(bl, inventory_id, sku):
    response = bl.call(
        "getInventoryProductsList",
        {
            "inventory_id": inventory_id,
            "filter_sku": sku,
            "page": 1,
        },
    )
    products = response.get("products", {})
    rows = products.values() if isinstance(products, dict) else products
    for row in rows or []:
        if not isinstance(row, dict):
            continue
        row_sku = str(row.get("sku", "")).strip()
        if row_sku.lower() != str(sku).strip().lower():
            continue
        return str(row.get("product_id") or row.get("id") or "").strip()
    return ""


def index_bl_products(bl, inventory_id, wanted_skus):
    product_ids_by_sku = {}
    for index, sku in enumerate(dict.fromkeys(wanted_skus), start=1):
        product_id = find_bl_product_id_by_sku(bl, inventory_id, sku)
        if product_id:
            product_ids_by_sku[str(sku).strip().lower()] = product_id
        if index % 25 == 0:
            print(f"BaseLinker SKU lookup: {index}/{len(set(wanted_skus))}", flush=True)

    found = {}
    product_ids = list(dict.fromkeys(product_ids_by_sku.values()))
    for id_batch in chunked(product_ids, 100):
        products = bl.get_products_data(inventory_id, id_batch)
        for product_id, product in products.items():
            sku = str(product.get("sku", "")).strip()
            if sku:
                found[sku.lower()] = (product_id, product)
    return found


def index_woo_products(woo):
    products = woo.list_products(status="any")
    index = {}
    for product in products:
        sku = str(product.get("sku", "")).strip()
        if sku:
            index[sku.lower()] = product
    return index


def sync_grouped_attrs(args):
    cfg = load_config(args.env_file)
    raw_text = open(args.raw_file, encoding="utf-8").read()
    groups = parse_groups(raw_text, args.group_prefix)
    if args.group_index:
        groups = [group for index, group in enumerate(groups, start=1) if index == args.group_index]
        if not groups:
            raise SystemExit(f"Group index {args.group_index} not found in raw file")
    wanted_skus = [sku for group in groups for sku in group["skus"]]

    bl = BaseLinkerClient(cfg["token"])
    inventory_id = resolve_inventory_id(bl, cfg["inventory_id"])
    woo = WooClient(cfg["woo_base_url"], cfg["woo_key"], cfg["woo_secret"])
    dry_run = not args.apply

    print("Grouped offer BL params -> Woo attributes", flush=True)
    print(f"- groups: {len(groups)}", flush=True)
    print(f"- skus requested: {len(wanted_skus)}", flush=True)
    print(f"- woo_host: {urllib.parse.urlparse(cfg['woo_base_url']).hostname or cfg['woo_base_url']}", flush=True)
    print(f"- dry-run: {dry_run}", flush=True)
    print("", flush=True)

    bl_index = index_bl_products(bl, inventory_id, wanted_skus)
    woo_index = index_woo_products(woo)
    print(f"Woo SKU index: {len(woo_index)}", flush=True)

    updated = 0
    missing_bl = []
    missing_woo = []
    no_features = []
    failures = []

    for group_index, group in enumerate(groups, start=1):
        group_key = group["group"]
        axis_key = group["axis"]
        for sku in group["skus"]:
            sku_key = sku.lower()
            if sku_key not in bl_index:
                missing_bl.append(sku)
                continue

            product_id_bl, bl_product = bl_index[sku_key]
            try:
                woo_product = woo_index.get(str(sku).strip().lower())
                if not woo_product:
                    missing_woo.append(sku)
                    continue

                features, unknown_features = extract_features(bl_product, cfg["language"])
                wc_attributes = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
                if not wc_attributes:
                    no_features.append(sku)

                meta_data = merge_meta_data(
                    woo_product.get("meta_data", []),
                    [
                        {"key": "_mnsk7_bl_features_raw", "value": json.dumps(features, ensure_ascii=False, sort_keys=True)},
                        {"key": "_mnsk7_bl_unknown_features_raw", "value": json.dumps(unknown_features, ensure_ascii=False, sort_keys=True)},
                        {"key": "_mnsk7_bl_variant_group", "value": group_key},
                        {"key": "_mnsk7_bl_variant_axis", "value": axis_key},
                    ],
                )
                payload = {
                    "attributes": wc_attributes,
                    "meta_data": meta_data,
                }

                if not dry_run:
                    update_bl_product(
                        bl,
                        inventory_id,
                        product_id_bl,
                        sku,
                        bl_product,
                        cfg["language"],
                        group_key,
                        axis_key,
                        True,
                    )
                    woo.update_product(woo_product["id"], payload)

                updated += 1
                print(
                    f"[{'DRY' if dry_run else 'OK'}] group={group_index:02d} sku={sku} "
                    f"wc_id={woo_product['id']} attrs={len(wc_attributes)} axis={axis_key}"
                )
            except Exception as exc:
                failures.append((sku, str(exc)))
                print(f"[FAIL] group={group_index:02d} sku={sku}: {exc}")

    print("")
    print(f"Done: updated={updated} missing_bl={len(missing_bl)} missing_woo={len(missing_woo)} no_features={len(no_features)} failures={len(failures)}")
    if missing_bl:
        print("Missing in BaseLinker:", ", ".join(missing_bl[:80]))
    if missing_woo:
        print("Missing in Woo:", ", ".join(missing_woo[:80]))
    if no_features:
        print("No mapped BaseLinker params:", ", ".join(no_features[:80]))
    if failures:
        raise SystemExit(1)


def main():
    parser = argparse.ArgumentParser(description="Sync grouped offer attributes from BaseLinker to existing Woo products.")
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--raw-file", default="scripts/raw_groups_2026-05-28_18_offers.txt")
    parser.add_argument("--group-prefix", default="mnsk7-offer")
    parser.add_argument("--group-index", type=int, default=0, help="Sync only one offer group (1-based index)")
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()
    sync_grouped_attrs(args)


if __name__ == "__main__":
    main()
