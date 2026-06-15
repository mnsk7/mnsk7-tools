#!/usr/bin/env python3
"""Sync only selected variant SKUs from BaseLinker to WooCommerce."""
import argparse
import os
import time

from assign_variant_groups import GROUP_DEFINITIONS
from baselinker_sync_products import (
    BaseLinkerClient,
    WooClient,
    build_wc_attributes,
    build_wc_payload,
    chunked,
    load_env,
    merge_meta_data,
    merge_product_attributes,
    resolve_inventory_id,
)


def collect_target_skus():
    skus = set()
    for group in GROUP_DEFINITIONS:
        for sku in group.get("skus", []):
            skus.add(str(sku).strip())
    return skus


def main():
    parser = argparse.ArgumentParser(description="Sync selected grouped SKUs only")
    parser.add_argument("--env-file", default=".env", help="Path to .env")
    parser.add_argument("--language", default="pl", help="BaseLinker language key")
    parser.add_argument("--apply", action="store_true", help="Write changes")
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
    price_group_id = env.get("BASELINKER_PRICE_GROUP_ID", "").strip()
    warehouse_id = env.get("BASELINKER_WAREHOUSE_ID", "").strip()
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
    publish_status = (env.get("BL_SYNC_PRODUCT_STATUS") or "publish").strip()

    if not baselinker_token:
        raise SystemExit("Missing BASELINKER_API_TOKEN")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL/WOO_BASE_URL and Woo credentials")

    dry_run = not args.apply
    target_skus = collect_target_skus()
    print(f"Target SKUs: {len(target_skus)}")

    bl = BaseLinkerClient(baselinker_token)
    inventory_id = resolve_inventory_id(bl, inventory_id)
    woo = WooClient(woo_base_url, woo_key, woo_secret)

    product_ids = bl.list_product_ids(inventory_id)
    matched = {}
    print(f"Fetching BaseLinker products: {len(product_ids)} ids")
    for id_batch in chunked(product_ids, 100):
        products = bl.get_products_data(inventory_id, id_batch)
        for product_id, product in products.items():
            sku = str(product.get("sku", "")).strip()
            if sku and sku in target_skus:
                matched[sku] = (product_id, product)
        time.sleep(0.15)

    print(f"Matched in BaseLinker: {len(matched)}")

    created = 0
    updated = 0
    skipped = 0

    for sku in sorted(target_skus):
        row = matched.get(sku)
        if not row:
            print(f"[SKIP] sku={sku}: not found in BaseLinker")
            skipped += 1
            continue

        product_id, product = row
        payload, features, _, skip_reason = build_wc_payload(
            product=product,
            product_id=product_id,
            language=args.language,
            price_group_id=price_group_id,
            warehouse_id=warehouse_id,
            publish_status=publish_status,
        )
        if skip_reason:
            print(f"[SKIP] {skip_reason}")
            skipped += 1
            continue

        existing = woo.find_product_by_sku(sku)
        wc_attributes = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
        if existing:
            if wc_attributes:
                payload["attributes"] = merge_product_attributes(existing.get("attributes", []), wc_attributes)
            payload["meta_data"] = merge_meta_data(existing.get("meta_data", []), payload.get("meta_data", []))
            if dry_run:
                print(f"[DRY] UPDATE sku={sku} wc_id={existing.get('id')}")
            else:
                woo.update_product(existing.get("id"), payload)
                print(f"[OK] UPDATE sku={sku} wc_id={existing.get('id')}")
            updated += 1
        else:
            if dry_run:
                print(f"[DRY] CREATE sku={sku}")
            else:
                created_obj = woo.create_product(payload)
                print(f"[OK] CREATE sku={sku} wc_id={created_obj.get('id')}")
            created += 1

        time.sleep(0.15)

    print("")
    print("Summary")
    print(f"- apply: {args.apply}")
    print(f"- created: {created}")
    print(f"- updated: {updated}")
    print(f"- skipped: {skipped}")


if __name__ == "__main__":
    main()
