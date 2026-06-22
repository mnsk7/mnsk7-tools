#!/usr/bin/env python3
"""Parse product titles and push missing params into BaseLinker features, then optional Woo sync."""
import argparse
import json
import os
import sys
import time
import urllib.parse

from baselinker_sync_products import (
    BaseLinkerClient,
    normalize_key,
    WooClient,
    build_wc_attributes,
    chunked,
    extract_features,
    load_env,
    merge_meta_data,
    merge_title_features,
    replace_mapped_product_attributes,
    resolve_inventory_id,
)
from product_param_parse import (
    OFFER13_AXIS,
    OFFER13_GROUP,
    OFFER13_SKU_ALIASES,
    OFFER13_SKUS_BL,
    SLUG_TO_BL_LABEL,
    normalize_offer_sku,
    parse_product_params,
    parsed_to_bl_features,
)


def merge_bl_feature_maps(existing, parsed_labels):
    merged = canonicalize_bl_features(existing or {})
    for label, value in parsed_labels.items():
        canonical_label = resolve_canonical_label(label)
        incoming_value = str(value).strip()
        if not incoming_value:
            continue
        # Parsed canonical value must win; BL is source of truth for Woo sync.
        merged[canonical_label] = incoming_value
    return merged


def parse_features_map(raw_value):
    if isinstance(raw_value, dict):
        return {str(k).strip(): str(v).strip() for k, v in raw_value.items() if str(k).strip() and str(v).strip()}
    return {}


CANONICAL_LABELS = {normalize_key(label): label for label in SLUG_TO_BL_LABEL.values()}

# Real BL data sometimes contains legacy/garbled aliases; fold them into canonical labels.
EXTRA_LABEL_ALIASES = {
    normalize_key("Materiał wykonania"): "Materiał",
    normalize_key("Material wykonania"): "Materiał",
    normalize_key("Materia wykonania"): "Materiał",
    normalize_key("Średnica"): "Średnica robocza",
    normalize_key("Srednica"): "Średnica robocza",
}


def resolve_canonical_label(label):
    text = str(label or "").strip()
    key = normalize_key(text)
    if key in CANONICAL_LABELS:
        return CANONICAL_LABELS[key]
    if key in EXTRA_LABEL_ALIASES:
        return EXTRA_LABEL_ALIASES[key]
    return text


def canonicalize_bl_features(features_map):
    canonical = {}
    for raw_label, raw_value in (features_map or {}).items():
        label = resolve_canonical_label(raw_label)
        value = str(raw_value or "").strip()
        if not label or not value:
            continue
        # Deterministic canonicalization only; no heuristic conflicts.
        canonical[label] = value
    return canonical


def main():
    parser = argparse.ArgumentParser(description="Push parsed title params to BaseLinker (+ optional Woo attribute refresh)")
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--limit", type=int, default=0)
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--sync-woo", action="store_true", help="Also refresh Woo attributes from merged BL features")
    parser.add_argument("--sku", action="append", default=[], help="Limit to SKU(s)")
    args = parser.parse_args()

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)

    token = (
        env.get("BASELINKER_API_TOKEN")
        or env.get("BASE_API_TOKEN")
        or env.get("base_api_token")
        or ""
    ).strip()
    inventory_id = env.get("BASELINKER_INVENTORY_ID", "").strip()
    language = env.get("BASELINKER_LANGUAGE", "pl").strip() or "pl"
    woo_base_url = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    woo_key = (env.get("WOO_CONSUMER_KEY") or env.get("Woo_Klucz_konsumenta") or "").strip()
    woo_secret = (env.get("WOO_CONSUMER_SECRET") or env.get("Woo_Tajny_konsumenta") or "").strip()
    dry_run = not args.apply

    if not token:
        raise SystemExit("Missing BASELINKER_API_TOKEN")

    bl = BaseLinkerClient(token)
    inventory_id = resolve_inventory_id(bl, inventory_id)
    woo = None
    if args.sync_woo:
        if not woo_base_url or not woo_key or not woo_secret:
            raise SystemExit("Missing Woo credentials for --sync-woo")
        woo = WooClient(woo_base_url, woo_key, woo_secret)

    product_ids = bl.list_product_ids(inventory_id, limit=args.limit or None)
    wanted = {s.strip().upper() for s in args.sku if s.strip()}
    for sku in list(wanted):
        canonical = normalize_offer_sku(sku)
        if canonical:
            wanted.add(canonical.upper())
        for alias, canon in OFFER13_SKU_ALIASES.items():
            if sku == alias.upper() or sku == canon.upper():
                wanted.add(alias.upper())
                wanted.add(canon.upper())

    updated_bl = 0
    updated_woo = 0
    skipped = 0

    print(f"Push title params -> BaseLinker (dry-run={dry_run})")
    print(f"- inventory_id: {inventory_id}")
    print(f"- products: {len(product_ids)}")
    print("")

    for id_batch in chunked(product_ids, 100):
        products = bl.get_products_data(inventory_id, id_batch)
        for product_id, product in products.items():
            sku = str(product.get("sku", "")).strip()
            if not sku:
                skipped += 1
                continue
            if wanted and sku.upper() not in wanted:
                continue

            text_fields = product.get("text_fields", {})
            name = ""
            short_desc = ""
            long_desc = ""
            if isinstance(text_fields, dict):
                name = text_fields.get(f"name|{language}") or text_fields.get("name") or ""
                short_desc = (
                    text_fields.get(f"description|{language}")
                    or text_fields.get("description")
                    or text_fields.get(f"short_description|{language}")
                    or text_fields.get("short_description")
                    or ""
                )
                long_desc = text_fields.get(f"description_extra|{language}") or text_fields.get("description_extra") or ""
            if not name:
                name = str(product.get("name") or "")

            parsed = parse_product_params(name, short_desc, long_desc)
            if not parsed:
                skipped += 1
                continue

            lang_key = f"features|{language}"
            current_map = parse_features_map(text_fields.get(lang_key) if isinstance(text_fields, dict) else {})
            if not current_map and isinstance(text_fields, dict):
                current_map = parse_features_map(text_fields.get("features"))

            patch = merge_bl_feature_maps(current_map, parsed_to_bl_features(parsed))

            if sku.upper() in OFFER13_SKU_ALIASES or sku.upper() in OFFER13_SKUS_BL:
                patch["Model"] = "FrezWiertlo_H"
                patch["MNSK7 grupa wariantu"] = OFFER13_GROUP
                patch["MNSK7 os wariantu"] = OFFER13_AXIS

            if patch == current_map:
                skipped += 1
                continue

            if not dry_run:
                bl.update_product_features(inventory_id, product_id, sku, patch, language=language)

            updated_bl += 1
            print(f"[{'DRY' if dry_run else 'OK'}] BL sku={sku} features={len(patch)} parsed={len(parsed)}")

            if woo and args.sync_woo:
                features, _unknown = extract_features({"text_fields": {lang_key: patch}}, language)
                features = merge_title_features(name, features)
                wc_attributes = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
                existing = woo.find_product_by_sku(sku)
                if existing and wc_attributes:
                    payload = {
                        "attributes": replace_mapped_product_attributes(existing.get("attributes", []), wc_attributes),
                        "meta_data": merge_meta_data(
                            existing.get("meta_data", []),
                            [
                                {"key": "_mnsk7_bl_features_raw", "value": json.dumps(features, ensure_ascii=False)},
                            ]
                            + (
                                [
                                    {"key": "_mnsk7_bl_variant_group", "value": OFFER13_GROUP},
                                    {"key": "_mnsk7_bl_variant_axis", "value": OFFER13_AXIS},
                                ]
                                if sku.upper() in OFFER13_SKU_ALIASES or sku.upper() in OFFER13_SKUS_BL
                                else []
                            ),
                        ),
                    }
                    if not dry_run:
                        woo.update_product(existing["id"], payload)
                    updated_woo += 1

            time.sleep(0.12)

    print("")
    print("Summary")
    print(f"- bl_updated: {updated_bl}")
    print(f"- woo_updated: {updated_woo}")
    print(f"- skipped: {skipped}")


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit(130)
