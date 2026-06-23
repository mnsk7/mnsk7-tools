#!/usr/bin/env python3
"""Sync selected SKUs BL -> Woo (categories, descriptions, group meta). No BL writes."""
import argparse
import sys
import time

sys.path.insert(0, "scripts")
from baselinker_sync_products import (
    BaseLinkerClient,
    BlCategoryResolver,
    BlTagResolver,
    WooClient,
    build_wc_attributes,
    build_wc_payload,
    load_env,
    merge_meta_data,
    replace_mapped_product_attributes,
    resolve_inventory_id,
    upsert_wc_product_from_bl,
)
from product_param_parse import OFFER13_SKU_ALIASES, OFFER13_SKUS, normalize_offer_sku


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--allow-production-host", action="store_true")
    parser.add_argument("--sku", action="append", default=[])
    args = parser.parse_args()

    env = load_env(args.env_file)
    skus = [normalize_offer_sku(s) for s in (args.sku or OFFER13_SKUS) if s.strip()]
    skus = [s for s in skus if s]
    bl_sku_keys = set()
    for sku in skus:
        bl_sku_keys.add(sku.upper())
        for alias, canonical in OFFER13_SKU_ALIASES.items():
            if canonical.upper() == sku.upper():
                bl_sku_keys.add(alias.upper())

    bl = BaseLinkerClient(env.get("base_api_token") or env.get("BASELINKER_API_TOKEN"))
    inventory_id = resolve_inventory_id(bl, env.get("BASELINKER_INVENTORY_ID", "").strip())
    woo = WooClient(
        env.get("WP_BASE_URL") or env.get("WOO_BASE_URL"),
        env.get("WOO_CONSUMER_KEY") or env.get("Woo_Klucz_konsumenta"),
        env.get("WOO_CONSUMER_SECRET") or env.get("Woo_Tajny_konsumenta"),
    )
    language = (env.get("BASELINKER_LANGUAGE") or "pl").strip() or "pl"
    dry_run = not args.apply
    host = (env.get("WP_BASE_URL") or "").replace("https://", "").replace("http://", "")
    if not dry_run and "staging" not in host and not args.allow_production_host:
        raise SystemExit("Refusing apply on production without --allow-production-host")

    print(f"inventory_id={inventory_id} skus={skus} dry_run={dry_run}", flush=True)
    category_resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=dry_run)
    tag_resolver = BlTagResolver(bl, inventory_id)
    print(f"categories_mapped={len(category_resolver.woo_map)} bl_tags={len(tag_resolver.allowed_names)}", flush=True)

    # Index BL products by SKU (scan inventory).
    wanted = bl_sku_keys
    found = {}
    product_ids = bl.list_product_ids(inventory_id)
    for i in range(0, len(product_ids), 100):
        batch = product_ids[i : i + 100]
        data = bl.get_products_data(inventory_id, batch)
        for pid, row in data.items():
            sku = str(row.get("sku", "")).strip().upper()
            if sku in wanted:
                found[sku] = (pid, row)
        if len(found) == len(wanted):
            break

    updated = failed = missing = 0
    for sku in skus:
        key = sku.upper()
        if key not in found:
            print(f"[MISS] BL sku={sku}", flush=True)
            missing += 1
            continue
        pid_bl, product = found[key]
        payload, features, unknown, skip = build_wc_payload(
            product,
            pid_bl,
            language,
            env.get("BASELINKER_PRICE_GROUP_ID", "").strip(),
            env.get("BASELINKER_WAREHOUSE_ID", "").strip(),
            env.get("BL_SYNC_PRODUCT_STATUS") or "publish",
            category_resolver=category_resolver,
            tag_resolver=tag_resolver,
        )
        if skip:
            print(f"[SKIP] {skip}", flush=True)
            continue
        existing = woo.find_product_by_sku(sku)
        if not existing:
            print(f"[MISS] Woo sku={sku}", flush=True)
            missing += 1
            continue
        wc_attrs = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
        if wc_attrs:
            payload["attributes"] = replace_mapped_product_attributes(existing.get("attributes", []), wc_attrs)
        payload["meta_data"] = merge_meta_data(existing.get("meta_data", []), payload.get("meta_data", []))
        try:
            if dry_run:
                print(
                    f"[DRY] UPDATE {sku} wc={existing['id']} type={existing.get('type')} "
                    f"cats={len(payload.get('categories', []))} short={bool(payload.get('short_description'))}",
                    flush=True,
                )
            else:
                upsert_wc_product_from_bl(woo, existing, payload)
                print(
                    f"[OK] UPDATE {sku} wc={existing['id']} type={existing.get('type')} "
                    f"group={next((m.get('value') for m in payload.get('meta_data', []) if m.get('key') == '_mnsk7_bl_variant_group'), '-')}",
                    flush=True,
                )
            updated += 1
        except Exception as error:
            print(f"[ERR] {sku}: {error}", flush=True)
            failed += 1
        time.sleep(0.2)

    print(f"summary updated={updated} missing={missing} failed={failed}", flush=True)


if __name__ == "__main__":
    main()
