#!/usr/bin/env python3
"""Mirror WooCommerce catalog to BaseLinker (single source of truth).

BaseLinker (inventory) is authoritative. This tool makes Woo match BL:

1. Categories: ensure every BL category exists in Woo with the correct
   parent/child tree, then DELETE any Woo product_cat term that does not
   correspond to a BL category (exact mirror).
2. Products: any Woo product whose SKU / EAN / BL-id is not present in BL is
   an orphan. Orphans with sales history are set to draft (order-safe);
   orphans with no sales are trashed (reversible, force=false).

Read-only by default (--report). Writes require an explicit apply flag plus
--allow-production-host when WP_BASE_URL is not a staging host.

Never prints secrets.
"""
from __future__ import annotations

import argparse
import html
import json
import sys
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))

from baselinker_sync_products import (  # noqa: E402
    BaseLinkerClient,
    BlCategoryResolver,
    WooClient,
    load_env,
    normalize_key,
    resolve_inventory_id,
)

try:
    from prod_catalog_cleanup import reassign_woo_default_category  # noqa: E402
except Exception:  # pragma: no cover - optional SSH dependency
    reassign_woo_default_category = None


# ---------------------------------------------------------------------------
# env helpers
# ---------------------------------------------------------------------------

def woo_creds(env):
    base = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    key = (
        env.get("WOO_CONSUMER_KEY")
        or env.get("Woo_Klucz_konsumenta")
        or env.get("WC_CONSUMER_KEY")
        or ""
    ).strip()
    secret = (
        env.get("WOO_CONSUMER_SECRET")
        or env.get("Woo_Tajny_konsumenta")
        or env.get("WC_CONSUMER_SECRET")
        or ""
    ).strip()
    return base, key, secret


def bl_token(env):
    return (
        env.get("BASELINKER_API_TOKEN")
        or env.get("BASE_API_TOKEN")
        or env.get("base_api_token")
        or ""
    ).strip()


# ---------------------------------------------------------------------------
# BaseLinker side
# ---------------------------------------------------------------------------

def bl_path_for(cid, by_id):
    parts = []
    seen = set()
    cur = int(cid)
    while cur and cur not in seen:
        seen.add(cur)
        row = by_id.get(cur)
        if not row:
            break
        parts.append(html.unescape(str(row.get("name", "")).strip()))
        cur = int(row.get("parent_id") or 0)
    return " > ".join(reversed(parts))


def detect_cycles(by_id):
    cycles = []
    for cid in by_id:
        seen = set()
        cur = cid
        while cur:
            if cur in seen:
                cycles.append([cid, cur])
                break
            seen.add(cur)
            row = by_id.get(cur)
            if not row:
                break
            cur = int(row.get("parent_id") or 0)
    return cycles


def bl_category_info(bl, inventory_id):
    cats = bl.list_categories(inventory_id)
    by_id = {int(c["category_id"]): c for c in cats if c.get("category_id") is not None}
    paths = {cid: bl_path_for(cid, by_id) for cid in by_id}
    depth = max((p.count(" > ") + 1 for p in paths.values()), default=0)
    norm_paths = {normalize_key(p) for p in paths.values()}
    return {
        "cats": cats,
        "by_id": by_id,
        "paths": paths,
        "norm_paths": norm_paths,
        "count": len(cats),
        "depth": depth,
        "cycles": detect_cycles(by_id),
    }


def bl_product_index(bl, inventory_id):
    """Scan whole inventory once: SKUs, EANs, BL ids, and SKU->category map."""
    skus = set()
    eans = set()
    bl_ids = set()
    sku_to_cat = {}
    product_ids = bl.list_product_ids(inventory_id)
    for i in range(0, len(product_ids), 100):
        batch = product_ids[i : i + 100]
        data = bl.get_products_data(inventory_id, batch)
        for pid, row in data.items():
            bl_ids.add(str(pid))
            sku = str(row.get("sku", "")).strip()
            if sku:
                up = sku.upper()
                skus.add(up)
                cat = row.get("category_id")
                if cat not in (None, "", 0, "0"):
                    sku_to_cat[up] = int(cat)
            ean = str(row.get("ean", "")).strip()
            if ean:
                eans.add(ean)
        time.sleep(0.05)
    return {
        "skus": skus,
        "eans": eans,
        "bl_ids": bl_ids,
        "sku_to_cat": sku_to_cat,
        "total": len(product_ids),
    }


# ---------------------------------------------------------------------------
# Woo side helpers
# ---------------------------------------------------------------------------

def woo_cat_path(cat, by_id):
    parts = []
    seen = set()
    cur = int(cat.get("id", 0))
    while cur and cur not in seen:
        seen.add(cur)
        row = by_id.get(cur)
        if not row:
            break
        parts.append(html.unescape(str(row.get("name", "")).strip()))
        cur = int(row.get("parent", 0) or 0)
    return " > ".join(reversed(parts))


def product_meta(product, key):
    for item in product.get("meta_data") or []:
        if item.get("key") == key:
            return str(item.get("value", "")).strip()
    return ""


def woo_product_in_bl(woo, product, bl_idx):
    """Return True if product (or any of its variations) maps to a BL entry."""
    sku = str(product.get("sku", "")).strip().upper()
    if sku and sku in bl_idx["skus"]:
        return True
    ean = product_meta(product, "_ean")
    if ean and ean in bl_idx["eans"]:
        return True
    bl_id = product_meta(product, "_mnsk7_bl_product_id")
    if bl_id and bl_id in bl_idx["bl_ids"]:
        return True
    if str(product.get("type")) == "variable":
        page = 1
        while True:
            variations = woo.request(
                "GET", f"/products/{product['id']}/variations?per_page=100&page={page}"
            )
            if not isinstance(variations, list) or not variations:
                break
            for var in variations:
                vsku = str(var.get("sku", "")).strip().upper()
                if vsku and vsku in bl_idx["skus"]:
                    return True
            if len(variations) < 100:
                break
            page += 1
    return False


# ---------------------------------------------------------------------------
# Categories
# ---------------------------------------------------------------------------

def mirror_categories(bl, woo, inventory_id, bl_info, bl_idx, apply=False, env=None):
    """Ensure BL tree in Woo, fix parents/names, delete non-BL categories."""
    result = {"created_or_mapped": 0, "renamed": [], "reparented": [], "deleted": [], "kept": []}

    resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=not apply)
    result["created_or_mapped"] = len(resolver.woo_map)

    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}

    # Rename + reparent mapped Woo terms to match BL exactly.
    for bl_id, woo_id in resolver.woo_map.items():
        row = bl_info["by_id"].get(int(bl_id))
        wc = by_id.get(int(woo_id))
        if not row or not wc:
            continue
        bl_name = str(row.get("name", "")).strip()
        bl_parent_bl = int(row.get("parent_id") or 0)
        target_parent_woo = int(resolver.woo_map.get(bl_parent_bl, 0)) if bl_parent_bl else 0
        cur_name = str(wc.get("name", "")).strip()
        cur_parent = int(wc.get("parent", 0) or 0)
        payload = {}
        if cur_name != bl_name and bl_name:
            payload["name"] = bl_name
            result["renamed"].append({"id": int(woo_id), "from": cur_name, "to": bl_name})
        if cur_parent != target_parent_woo:
            payload["parent"] = target_parent_woo
            result["reparented"].append(
                {"id": int(woo_id), "from": cur_parent, "to": target_parent_woo}
            )
        if payload and apply:
            woo.request("PUT", f"/products/categories/{woo_id}", payload)

    # Refresh after possible renames/reparents.
    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}
    canonical = {int(v) for v in resolver.woo_map.values()}

    # Pick a fallback BL category for default_product_cat reassignment.
    fallback_bl_cat = next(iter(canonical), None)

    for cat in woo_cats:
        cid = int(cat["id"])
        path = woo_cat_path(cat, by_id)
        count = int(cat.get("count", 0) or 0)
        if cid in canonical:
            result["kept"].append({"id": cid, "path": path, "count": count})
            continue

        # Non-BL category -> delete. First move its products to BL category.
        if count > 0:
            _reassign_category_products(woo, cid, by_id, resolver, bl_idx, fallback_bl_cat, apply)

        if not apply:
            result["deleted"].append({"id": cid, "path": path, "count": count})
            continue
        try:
            woo.request("DELETE", f"/products/categories/{cid}?force=true")
            result["deleted"].append({"id": cid, "path": path, "count": count})
        except RuntimeError as error:
            if "woocommerce_rest_cannot_delete" not in str(error):
                raise
            # Default category: repoint default_product_cat then retry.
            if fallback_bl_cat and reassign_woo_default_category and env is not None:
                if reassign_woo_default_category(env, fallback_bl_cat, dry_run=False):
                    try:
                        woo.request("DELETE", f"/products/categories/{cid}?force=true")
                        result["deleted"].append({"id": cid, "path": path, "count": count})
                        continue
                    except RuntimeError:
                        pass
            result["kept"].append(
                {"id": cid, "path": path, "count": count, "reason": "default_category"}
            )

    return result, resolver


def _reassign_category_products(woo, cid, by_id, resolver, bl_idx, fallback, apply):
    page = 1
    while True:
        products = woo.request("GET", f"/products?category={cid}&per_page=100&page={page}")
        if not isinstance(products, list) or not products:
            break
        for product in products:
            pid = product["id"]
            sku = str(product.get("sku", "")).strip().upper()
            target = None
            if sku and sku in bl_idx["sku_to_cat"]:
                target = resolver.resolve(bl_idx["sku_to_cat"][sku])
            if not target:
                target = fallback
            if not target:
                continue
            # Keep other BL categories already on the product, drop this one.
            keep = [
                {"id": int(c["id"])}
                for c in (product.get("categories") or [])
                if int(c["id"]) != cid and int(c["id"]) in {int(v) for v in resolver.woo_map.values()}
            ]
            if not any(c["id"] == int(target) for c in keep):
                keep.append({"id": int(target)})
            if apply:
                woo.update_product(pid, {"categories": keep})
        if len(products) < 100:
            break
        page += 1


# ---------------------------------------------------------------------------
# Products
# ---------------------------------------------------------------------------

def mirror_products(woo, bl_idx, apply=False):
    result = {
        "woo_total": 0,
        "matched": 0,
        "orphans": [],
        "deleted_no_orders": [],
        "drafted_has_orders": [],
    }
    products = woo.list_products(status="any")
    result["woo_total"] = len(products)
    for product in products:
        if woo_product_in_bl(woo, product, bl_idx):
            result["matched"] += 1
            continue
        pid = product["id"]
        sku = str(product.get("sku", "")).strip()
        total_sales = int(product.get("total_sales", 0) or 0)
        info = {
            "id": pid,
            "sku": sku or "-",
            "name": str(product.get("name", ""))[:80],
            "status": product.get("status"),
            "total_sales": total_sales,
        }
        result["orphans"].append(info)
        if total_sales > 0:
            if apply and str(product.get("status")) != "draft":
                woo.update_product(pid, {"status": "draft"})
            result["drafted_has_orders"].append(info)
        else:
            if apply:
                # force=false -> trash (reversible, keeps order line refs intact)
                woo.delete_product(pid, force=False)
            result["deleted_no_orders"].append(info)
        time.sleep(0.1)
    return result


# ---------------------------------------------------------------------------
# main
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(description="Mirror Woo catalog to BaseLinker")
    parser.add_argument("--env-file", default=str(ROOT / ".env"))
    parser.add_argument("--report", action="store_true", help="Read-only report (default)")
    parser.add_argument("--apply-categories", action="store_true")
    parser.add_argument("--apply-products", action="store_true")
    parser.add_argument("--allow-production-host", action="store_true")
    parser.add_argument("--out", default="", help="Optional JSON report output path")
    args = parser.parse_args()

    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")

    env = load_env(args.env_file)
    base, key, secret = woo_creds(env)
    token = bl_token(env)
    if not token:
        raise SystemExit("Missing BaseLinker token in .env")
    if not base or not key or not secret:
        raise SystemExit("Missing Woo credentials in .env")

    apply_any = args.apply_categories or args.apply_products
    if apply_any:
        host = base.replace("https://", "").replace("http://", "")
        if "staging" not in host and not args.allow_production_host:
            raise SystemExit("Refusing apply on production without --allow-production-host")

    bl = BaseLinkerClient(token)
    woo = WooClient(base, key, secret)
    inventory_id = resolve_inventory_id(bl, env.get("BASELINKER_INVENTORY_ID", "").strip())

    report = {
        "host": base,
        "inventory_id": inventory_id,
        "apply_categories": args.apply_categories,
        "apply_products": args.apply_products,
    }

    print("Fetching BaseLinker category tree...", flush=True)
    bl_info = bl_category_info(bl, inventory_id)
    report["bl_category_count"] = bl_info["count"]
    report["bl_tree_depth"] = bl_info["depth"]
    report["bl_cycles"] = bl_info["cycles"]
    print(
        f"  BL categories={bl_info['count']} depth={bl_info['depth']} "
        f"cycles={len(bl_info['cycles'])}",
        flush=True,
    )
    if bl_info["cycles"]:
        print(f"  WARNING: BL category cycles present: {bl_info['cycles']}", flush=True)

    print("Scanning BaseLinker products (this can take a few minutes)...", flush=True)
    bl_idx = bl_product_index(bl, inventory_id)
    report["bl_product_total"] = bl_idx["total"]
    report["bl_sku_count"] = len(bl_idx["skus"])
    print(
        f"  BL products={bl_idx['total']} skus={len(bl_idx['skus'])} eans={len(bl_idx['eans'])}",
        flush=True,
    )

    woo_cats_before = woo.list_product_categories()
    report["woo_category_before"] = len(woo_cats_before)
    print(f"  Woo categories before={len(woo_cats_before)}", flush=True)

    # Categories
    print("\n== CATEGORIES ==", flush=True)
    cat_result, _resolver = mirror_categories(
        bl, woo, inventory_id, bl_info, bl_idx, apply=args.apply_categories, env=env
    )
    report["categories"] = {
        "created_or_mapped": cat_result["created_or_mapped"],
        "renamed": cat_result["renamed"],
        "reparented": cat_result["reparented"],
        "deleted": cat_result["deleted"],
        "kept_count": len(cat_result["kept"]),
        "deleted_count": len(cat_result["deleted"]),
    }
    print(
        f"  mapped={cat_result['created_or_mapped']} "
        f"renamed={len(cat_result['renamed'])} reparented={len(cat_result['reparented'])} "
        f"to_delete={len(cat_result['deleted'])} keep={len(cat_result['kept'])}",
        flush=True,
    )
    for d in cat_result["deleted"]:
        tag = "deleted" if args.apply_categories else "would-delete"
        print(f"    {tag}: id={d['id']} count={d.get('count')} path={d['path']}", flush=True)

    woo_cats_after = woo.list_product_categories()
    report["woo_category_after"] = len(woo_cats_after)

    # Products
    print("\n== PRODUCTS ==", flush=True)
    prod_result = mirror_products(woo, bl_idx, apply=args.apply_products)
    report["products"] = {
        "woo_total": prod_result["woo_total"],
        "matched": prod_result["matched"],
        "orphans": len(prod_result["orphans"]),
        "deleted_no_orders": len(prod_result["deleted_no_orders"]),
        "drafted_has_orders": len(prod_result["drafted_has_orders"]),
        "orphan_list": prod_result["orphans"][:200],
    }
    print(
        f"  woo_total={prod_result['woo_total']} matched={prod_result['matched']} "
        f"orphans={len(prod_result['orphans'])} "
        f"{'deleted' if args.apply_products else 'to_delete'}(no orders)="
        f"{len(prod_result['deleted_no_orders'])} "
        f"{'drafted' if args.apply_products else 'to_draft'}(has orders)="
        f"{len(prod_result['drafted_has_orders'])}",
        flush=True,
    )

    print("\n=== REPORT JSON ===", flush=True)
    print(json.dumps(report, ensure_ascii=False, indent=2), flush=True)
    if args.out:
        Path(args.out).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"\nWrote {args.out}", flush=True)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit(130)
