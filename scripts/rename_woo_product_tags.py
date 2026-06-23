#!/usr/bin/env python3
"""One-off: rename Woo product_tag terms to BL canonical Polish title case."""
import argparse
import sys

sys.path.insert(0, "scripts")
from baselinker_sync_products import (  # noqa: E402
    BaseLinkerClient,
    BlTagResolver,
    WooClient,
    format_catalog_tag_name,
    load_env,
    normalize_key,
)


def main():
    parser = argparse.ArgumentParser(description="Rename Woo product tags using BL canonical names.")
    parser.add_argument("--dry-run", action="store_true", help="Print planned renames only.")
    parser.add_argument("--env-file", default=".env", help="Path to .env (default: .env)")
    args = parser.parse_args()

    env = load_env(args.env_file)
    inventory_id = int(env.get("BASELINKER_INVENTORY_ID") or 0)
    if inventory_id <= 0:
        raise SystemExit("Missing BASELINKER_INVENTORY_ID in .env")

    bl_token = env.get("base_api_token") or env.get("BASELINKER_API_TOKEN")
    if not bl_token:
        raise SystemExit("Missing base_api_token / BASELINKER_API_TOKEN in .env")

    woo_base = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    woo_key = env.get("WOO_CONSUMER_KEY") or env.get("Woo_Klucz_konsumenta")
    woo_secret = env.get("WOO_CONSUMER_SECRET") or env.get("Woo_Tajny_konsumenta")
    if not woo_base or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL and Woo REST credentials in .env")

    bl = BaseLinkerClient(bl_token)
    woo = WooClient(woo_base, woo_key, woo_secret)
    resolver = BlTagResolver(bl, inventory_id)

    bl_names = sorted(resolver.allowed_names)
    print(f"BL inventory tags ({len(bl_names)}): {', '.join(bl_names)}")

    woo_tags = woo.list_product_tags()
    print(f"Woo product tags ({len(woo_tags)})")

    updated = 0
    for tag in woo_tags:
        tag_id = tag.get("id")
        current = str(tag.get("name", "")).strip()
        if not tag_id or not current:
            continue

        canonical = resolver.canonical_by_norm.get(normalize_key(current))
        if not canonical:
            canonical = format_catalog_tag_name(current)

        if canonical == current:
            print(f"[OK] {current}")
            continue

        print(f"[RENAME] {current!r} -> {canonical!r}")
        if not args.dry_run:
            woo.request("PUT", f"/products/tags/{int(tag_id)}", {"name": canonical})
        updated += 1

    print(f"updated={updated} dry_run={args.dry_run}")


if __name__ == "__main__":
    main()
