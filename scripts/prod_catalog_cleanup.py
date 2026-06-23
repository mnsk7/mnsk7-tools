#!/usr/bin/env python3
"""Production maintenance: inactive plugin removal, category/attribute cleanup vs BaseLinker."""
from __future__ import annotations

import argparse
import datetime
import json
import os
import posixpath
import re
import shlex
import sys
import time
import urllib.parse
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))

from baselinker_sync_products import (  # noqa: E402
    BlCategoryResolver,
    BlTagResolver,
    BaseLinkerClient,
    WooClient,
    build_wc_payload,
    load_env,
    normalize_key,
    resolve_inventory_id,
)
from deploy_prod_sftp import resolve_prod_ssh, ssh_exec  # noqa: E402

# Inactive duplicates — NEVER include *przelewy* plugins.
PLUGINS_TO_DELETE = [
    "beaver-builder-lite-version",
    "inpost-for-woocommerce",
    "facebook-for-woocommerce",
    "official-facebook-pixel",
    "gtm-ecommerce-woo",
    "woo-product-filter",
    "woof-by-category",
    "filter-everything",
    "woo-smart-wishlist",
    "flexible-wishlist",
    "seraphinite-accelerator",
    "popup-maker",
    "media-cleaner",
    "media-sync",
    "ithemelandco-woo-report",
    "wc-product-table-lite",
    "woo-bought-together",
    "sticky-menu-or-anything-on-scroll",
    "profile-builder",
    "akismet",
    "header-footer",
    "insert-headers-and-footers",
    "woo-ecommerce-tracking-for-google-and-facebook",
    "litespeed-cache",
]

# Legacy flat Woo categories -> BL category name (parent chain created by BlCategoryResolver).
LEGACY_CAT_MAP = {
    25: "Frez kulowy VHM",
    23: "Frez jednopiórowy",
    144: "Frez palcowy do metalu",
}

UNUSED_ATTR_SLUGS = [
    "czolo",
    "dlugosc-calkowita",
    "dlugosc-robocza",
    "kat-skosu",
    "ksztalt",
    "typ-pilnika",
    "wymiary-trzpienia",
]


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


def category_path(category, by_id):
    parts = []
    seen = set()
    cur = int(category.get("id", 0))
    while cur and cur not in seen:
        seen.add(cur)
        row = by_id.get(cur)
        if not row:
            break
        parts.append(str(row.get("name", "")).strip())
        cur = int(row.get("parent", 0) or 0)
    return " > ".join(reversed(parts))


def delete_plugins(env, dry_run=True):
    cfg = resolve_prod_ssh(env)
    wp_path = cfg["wp_path"]
    date_tag = datetime.date.today().strftime("%Y%m%d")
    results = {"deleted": [], "skipped": [], "missing": [], "errors": []}

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(
        cfg["host"],
        port=cfg["port"],
        username=cfg["user"],
        password=cfg["password"],
        timeout=30,
    )
    try:
        for plugin in PLUGINS_TO_DELETE:
            if "przelewy" in plugin.lower():
                results["skipped"].append(plugin)
                continue
            plugin_dir = posixpath.join(wp_path, "wp-content/plugins", plugin)
            code, out, err = ssh_exec(ssh, f"test -d {posixpath.join(plugin_dir)} && echo exists")
            if "exists" not in out:
                results["missing"].append(plugin)
                continue
            backup_dir = "~/plugin-backups"
            backup = f"{backup_dir}/plugin-backup-{date_tag}-{plugin}.tar.gz"
            if dry_run:
                print(f"[dry-run] backup+delete {plugin}")
                results["deleted"].append(plugin)
                continue
            plugins_dir = posixpath.join(wp_path, "wp-content/plugins")
            tar_cmd = (
                f"mkdir -p {backup_dir} && "
                f"cd {plugins_dir} && "
                f"tar czf {backup} {plugin} && rm -rf {plugin}"
            )
            code, out, err = ssh_exec(ssh, tar_cmd)
            if code != 0:
                results["errors"].append({"plugin": plugin, "error": err or out})
            else:
                print(f"deleted {plugin} (backup {backup})")
                results["deleted"].append(plugin)
    finally:
        ssh.close()
    return results


def count_categories(woo):
    cats = woo.list_product_categories()
    return len(cats), cats


def build_bl_paths(bl, inventory_id):
    categories = bl.list_categories(inventory_id)
    by_id = {int(r["category_id"]): r for r in categories if r.get("category_id") is not None}

    def path_for(cid):
        parts = []
        seen = set()
        cur = int(cid)
        while cur and cur not in seen:
            seen.add(cur)
            row = by_id.get(cur)
            if not row:
                break
            parts.append(str(row.get("name", "")).strip())
            cur = int(row.get("parent_id") or 0)
        return " > ".join(reversed(parts))

    paths = {path_for(int(r["category_id"])): int(r["category_id"]) for r in categories}
    return paths, categories


def attr_slug_matches(slug, *candidates):
    slug = str(slug or "").strip().lstrip("pa_")
    return slug in {str(c).strip().lstrip("pa_") for c in candidates}


def dedupe_bl_canonical_collisions(woo, bl, inventory_id, dry_run=True):
    """When multiple Woo terms share the same BL path, keep BL-mapped canonical and drop empty dupes."""
    resolver = sync_bl_categories_to_woo(bl, woo, inventory_id, dry_run=dry_run)
    bl_paths, _ = build_bl_paths(bl, inventory_id)
    bl_norm_paths = {normalize_key(p): p for p in bl_paths}
    canonical_ids = {int(v) for v in resolver.woo_map.values()}

    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}
    by_norm = {}
    for cat in woo_cats:
        path = category_path(cat, by_id)
        norm = normalize_key(path)
        by_norm.setdefault(norm, []).append(cat)

    deleted = []
    for norm, group in by_norm.items():
        if norm not in bl_norm_paths or len(group) < 2:
            continue
        keep = next((c for c in group if int(c["id"]) in canonical_ids), None)
        if not keep:
            keep = max(group, key=lambda c: int(c.get("count", 0) or 0))
        for cat in group:
            cid = int(cat["id"])
            if cid == int(keep["id"]):
                continue
            count = int(cat.get("count", 0) or 0)
            if count > 0:
                continue
            if dry_run:
                print(f"[dry-run] delete BL-path duplicate {cid} {category_path(cat, by_id)}")
            else:
                woo.request("DELETE", f"/products/categories/{cid}?force=true")
                print(f"deleted BL-path duplicate {cid} {category_path(cat, by_id)}")
            deleted.append({"id": cid, "path": category_path(cat, by_id), "canonical_id": int(keep["id"])})
    return deleted


def reassign_legacy_categories(woo, bl, inventory_id, dry_run=True):
    resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=dry_run)
    woo_cats = woo.list_product_categories()
    by_name_parent = {}
    for cat in woo_cats:
        by_name_parent[(normalize_key(cat.get("name", "")), int(cat.get("parent", 0) or 0))] = cat

    # Map BL category names to woo IDs via resolver
    bl_paths, bl_cats = build_bl_paths(bl, inventory_id)
    name_to_bl_woo = {}
    for bl_id, woo_id in resolver.woo_map.items():
        row = next((r for r in bl_cats if int(r["category_id"]) == bl_id), None)
        if row:
            name_to_bl_woo[normalize_key(row.get("name", ""))] = woo_id

    moved = []
    for legacy_id, target_name in LEGACY_CAT_MAP.items():
        target_woo_id = name_to_bl_woo.get(normalize_key(target_name))
        if not target_woo_id:
            # fallback: find by name anywhere
            for cat in woo_cats:
                if normalize_key(cat.get("name", "")) == normalize_key(target_name):
                    target_woo_id = cat["id"]
                    break
        if not target_woo_id:
            print(f"WARN: no target category for legacy {legacy_id} -> {target_name}")
            continue

        page = 1
        while True:
            path = f"/products?category={legacy_id}&per_page=100&page={page}"
            products = woo.request("GET", path)
            if not isinstance(products, list) or not products:
                break
            for product in products:
                pid = product["id"]
                cats = [{"id": int(target_woo_id)}]
                if dry_run:
                    print(f"[dry-run] product {pid} cat {legacy_id} -> {target_woo_id} ({target_name})")
                else:
                    woo.update_product(pid, {"categories": cats})
                    print(f"product {pid}: {legacy_id} -> {target_woo_id} ({target_name})")
                moved.append(pid)
            if len(products) < 100:
                break
            page += 1
    return moved


def reassign_woo_default_category(env, new_term_id, dry_run=True):
    """Point Woo default_product_cat away from a legacy term so it can be deleted."""
    if dry_run:
        print(f"[dry-run] default_product_cat -> {new_term_id}")
        return True

    cfg = resolve_prod_ssh(env)
    wp_path = cfg["wp_path"]
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(
        cfg["host"],
        port=cfg["port"],
        username=cfg["user"],
        password=cfg["password"],
        timeout=30,
    )
    try:
        cmd = (
            f"cd {shlex.quote(wp_path)} && "
            f"wp option update default_product_cat {int(new_term_id)} --quiet"
        )
        code, out, err = ssh_exec(ssh, cmd)
        if code == 0:
            print(f"default_product_cat -> {new_term_id} (wp-cli)")
            return True

        db_name = (env.get("new_db_name") or "").strip()
        db_user = (env.get("new_db_user") or "").strip()
        db_pass = (env.get("new_db_password") or "").strip()
        db_host = (env.get("new_db_host") or "localhost").strip()
        if not db_name or not db_user:
            print(f"WARN: default_product_cat update failed: {err or out}")
            return False

        prefix_cmd = f"cd {shlex.quote(wp_path)} && wp config get table_prefix --quiet 2>/dev/null || grep table_prefix wp-config.php | head -1"
        _, prefix_out, _ = ssh_exec(ssh, prefix_cmd)
        table_prefix = "wp_"
        if prefix_out:
            m = re.search(r"['\"](\w+_)['\"]", prefix_out)
            if m:
                table_prefix = m.group(1)

        esc_pass = db_pass.replace("'", "'\\''")
        sql = (
            f"UPDATE {table_prefix}options SET option_value='{int(new_term_id)}' "
            f"WHERE option_name='default_product_cat' LIMIT 1;"
        )
        mysql_cmd = (
            f"mysql -h {shlex.quote(db_host)} -u {shlex.quote(db_user)} "
            f"-p'{esc_pass}' {shlex.quote(db_name)} -e {shlex.quote(sql)}"
        )
        code2, out2, err2 = ssh_exec(ssh, mysql_cmd)
        if code2 != 0:
            print(f"WARN: default_product_cat mysql update failed: {err2 or out2}")
            return False
        print(f"default_product_cat -> {new_term_id} (mysql)")
        return True
    finally:
        ssh.close()


def sync_bl_categories_to_woo(bl, woo, inventory_id, dry_run=True):
    """Ensure all BL categories exist in Woo (BL -> Woo only)."""
    resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=dry_run)
    print(f"BL categories synced to Woo: {len(resolver.woo_map)} mapped")
    return resolver


def rename_woo_categories_to_bl(bl, woo, inventory_id, dry_run=True):
    """Rename mapped Woo product_cat terms so display names match BaseLinker exactly."""
    resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=dry_run)
    bl_cats = bl.list_categories(inventory_id)
    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}
    renamed = []

    for row in bl_cats:
        bl_id = int(row.get("category_id"))
        bl_name = str(row.get("name", "")).strip()
        woo_id = resolver.woo_map.get(bl_id)
        if not woo_id or not bl_name:
            continue
        wc = by_id.get(int(woo_id))
        if not wc:
            continue
        woo_name = str(wc.get("name", "")).strip()
        if woo_name == bl_name:
            continue
        parent_id = int(wc.get("parent", 0) or 0)
        payload = {"name": bl_name}
        if parent_id:
            payload["parent"] = parent_id
        if dry_run:
            print(f"[dry-run] rename category {woo_id} {woo_name!r} -> {bl_name!r}")
        else:
            woo.request("PUT", f"/products/categories/{woo_id}", payload)
            print(f"renamed category {woo_id} {woo_name!r} -> {bl_name!r}")
        renamed.append(
            {
                "id": int(woo_id),
                "from": woo_name,
                "to": bl_name,
                "path": category_path(wc, by_id),
            }
        )
    return renamed


def sync_product_tags_from_bl(woo, bl, inventory_id, tag_resolver, dry_run=True):
    """Replace Woo product tags with BaseLinker assignments (BL -> Woo only)."""
    bl_norm = {normalize_key(name) for name in tag_resolver.allowed_names}
    woo_tags = woo.list_product_tags()
    orphan_ids = {
        int(tag["id"])
        for tag in woo_tags
        if normalize_key(str(tag.get("name", ""))) not in bl_norm
    }

    product_ids = set()
    for tag_id in orphan_ids:
        page = 1
        while True:
            rows = woo.request("GET", f"/products?tag={tag_id}&per_page=100&page={page}")
            if not isinstance(rows, list) or not rows:
                break
            for row in rows:
                product_ids.add(int(row["id"]))
            if len(rows) < 100:
                break
            page += 1

    bl_by_sku = {}
    product_ids_bl = bl.list_product_ids(inventory_id)
    for i in range(0, len(product_ids_bl), 100):
        batch = product_ids_bl[i : i + 100]
        data = bl.get_products_data(inventory_id, batch)
        for pid, row in data.items():
            sku = str(row.get("sku", "")).strip().upper()
            if sku:
                bl_by_sku[sku] = row

    updated = []
    skipped = []
    language = "pl"
    price_group_id = ""
    warehouse_id = ""
    publish_status = "publish"

    for wc_id in sorted(product_ids):
        product = woo.request("GET", f"/products/{wc_id}")
        sku = str(product.get("sku", "")).strip()
        bl_product = bl_by_sku.get(sku.upper()) if sku else None
        if not bl_product and str(product.get("type")) == "variable":
            variation_tags = []
            seen_names = set()
            page = 1
            while True:
                variations = woo.request(
                    "GET",
                    f"/products/{wc_id}/variations?per_page=100&page={page}",
                )
                if not isinstance(variations, list) or not variations:
                    break
                for variation in variations:
                    var_sku = str(variation.get("sku", "")).strip().upper()
                    var_bl = bl_by_sku.get(var_sku)
                    if not var_bl:
                        continue
                    for tag in tag_resolver.resolve_product_tags(var_bl):
                        norm = normalize_key(tag["name"])
                        if norm in seen_names:
                            continue
                        seen_names.add(norm)
                        variation_tags.append(tag)
                if len(variations) < 100:
                    break
                page += 1
            tags = variation_tags
        elif bl_product:
            tags = tag_resolver.resolve_product_tags(bl_product)
        else:
            skipped.append({"id": wc_id, "sku": sku or "-", "reason": "bl_missing"})
            continue
        if dry_run:
            print(f"[dry-run] product {wc_id} sku={sku} tags -> {[t['name'] for t in tags]}")
        else:
            woo.update_product(wc_id, {"tags": tags})
            print(f"product {wc_id} sku={sku} tags -> {[t['name'] for t in tags]}")
        updated.append({"id": wc_id, "sku": sku, "tags": [t["name"] for t in tags]})
        time.sleep(0.15)

    return {"updated": updated, "skipped": skipped, "orphan_tag_ids": sorted(orphan_ids)}


def dedupe_woo_category_duplicates(woo, bl, inventory_id, env=None, dry_run=True):
    """Remove empty legacy Woo duplicates when a BL-mapped category with same leaf exists."""
    resolver = sync_bl_categories_to_woo(bl, woo, inventory_id, dry_run=dry_run)
    bl_paths, bl_cats = build_bl_paths(bl, inventory_id)
    bl_norm_paths = {normalize_key(p) for p in bl_paths}

    bl_id_to_path = {int(bl_id): path for path, bl_id in bl_paths.items()}

    canonical_woo_by_norm = {}
    for bl_id, woo_id in resolver.woo_map.items():
        path = bl_id_to_path.get(bl_id)
        if path:
            canonical_woo_by_norm[normalize_key(path)] = int(woo_id)

    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}
    deleted = []
    moved = []

    for cat in woo_cats:
        cid = int(cat["id"])
        path = category_path(cat, by_id)
        norm = normalize_key(path)
        if norm in bl_norm_paths:
            continue
        count = int(cat.get("count", 0) or 0)
        leaf = normalize_key(str(cat.get("name", "")))
        canonical_id = None
        for bl_norm, woo_id in canonical_woo_by_norm.items():
            if normalize_key(bl_norm.split(" > ")[-1]) == leaf:
                canonical_id = woo_id
                break
        if not canonical_id and norm not in bl_norm_paths:
            # Legacy flat category not in BL at all (e.g. default Frez typ V).
            if count > 0:
                continue
            if dry_run:
                print(f"[dry-run] delete legacy category {cid} {path}")
            else:
                try:
                    woo.request("DELETE", f"/products/categories/{cid}?force=true")
                    print(f"deleted legacy category {cid} {path}")
                except RuntimeError as error:
                    if "woocommerce_rest_cannot_delete" not in str(error) or not env:
                        raise
                    fallback = next(iter(canonical_woo_by_norm.values()), None)
                    if fallback and reassign_woo_default_category(env, fallback, dry_run=False):
                        woo.request("DELETE", f"/products/categories/{cid}?force=true")
                        print(f"deleted legacy default category {cid} {path}")
                    else:
                        print(f"WARN: cannot delete default legacy {cid} {path}")
                        continue
            deleted.append({"id": cid, "path": path})
            continue
        if canonical_id == cid:
            continue
        if count > 0:
            page = 1
            while True:
                products = woo.request("GET", f"/products?category={cid}&per_page=100&page={page}")
                if not isinstance(products, list) or not products:
                    break
                for product in products:
                    pid = product["id"]
                    if dry_run:
                        print(f"[dry-run] product {pid} dup cat {cid} -> {canonical_id}")
                    else:
                        woo.update_product(pid, {"categories": [{"id": canonical_id}]})
                        print(f"product {pid}: dup cat {cid} -> {canonical_id}")
                    moved.append(pid)
                if len(products) < 100:
                    break
                page += 1
        if dry_run:
            print(f"[dry-run] delete duplicate category {cid} {path} (canonical {canonical_id})")
        else:
            woo.request("DELETE", f"/products/categories/{cid}?force=true")
            print(f"deleted duplicate category {cid} {path}")
        deleted.append({"id": cid, "path": path, "canonical_id": canonical_id})
    return deleted, moved


def delete_woo_only_categories(woo, bl, inventory_id, env=None, dry_run=True):
    bl_paths, _ = build_bl_paths(bl, inventory_id)
    bl_norm_paths = {normalize_key(p.replace(" > ", " ")) for p in bl_paths}

    woo_cats = woo.list_product_categories()
    by_id = {int(c["id"]): c for c in woo_cats}
    deleted = []
    kept = []
    fallback_bl_cat = next(
        (
            int(c["id"])
            for c in woo_cats
            if normalize_key(category_path(c, by_id).replace(" > ", " ")) in bl_norm_paths
        ),
        None,
    )

    for cat in woo_cats:
        cid = int(cat["id"])
        path = category_path(cat, by_id)
        norm = normalize_key(path.replace(" > ", " "))
        count = int(cat.get("count", 0) or 0)
        if norm in bl_norm_paths:
            kept.append({"id": cid, "path": path, "count": count})
            continue
        if count > 0:
            kept.append({"id": cid, "path": path, "count": count, "reason": "has_products"})
            continue
        if dry_run:
            print(f"[dry-run] delete category {cid} {path}")
            deleted.append({"id": cid, "path": path})
            continue
        try:
            woo.request("DELETE", f"/products/categories/{cid}?force=true")
            print(f"deleted category {cid} {path}")
            deleted.append({"id": cid, "path": path})
        except RuntimeError as error:
            if "woocommerce_rest_cannot_delete" not in str(error):
                raise
            if not fallback_bl_cat or not env:
                kept.append({"id": cid, "path": path, "count": count, "reason": "default_category"})
                print(f"WARN: cannot delete default category {cid} {path}")
                continue
            print(f"default category {cid} — reassigning to BL cat {fallback_bl_cat}")
            if reassign_woo_default_category(env, fallback_bl_cat, dry_run=False):
                woo.request("DELETE", f"/products/categories/{cid}?force=true")
                print(f"deleted category {cid} {path}")
                deleted.append({"id": cid, "path": path})
            else:
                kept.append({"id": cid, "path": path, "count": count, "reason": "default_category"})
    return deleted, kept


def migrate_pa_fi_products(woo, dry_run=True):
    attrs = woo.list_attributes()
    fi_attr = next((a for a in attrs if attr_slug_matches(a.get("slug"), "fi")), None)
    st_attr = next((a for a in attrs if attr_slug_matches(a.get("slug"), "srednica-trzpienia")), None)
    if not fi_attr or not st_attr:
        print("WARN: pa_fi or pa_srednica-trzpienia not found")
        return []

    fi_id = int(fi_attr["id"])
    st_id = int(st_attr["id"])
    fi_terms = {normalize_key(t["name"]): t for t in woo.list_attribute_terms(fi_id)}
    st_terms = {normalize_key(t["name"]): t for t in woo.list_attribute_terms(st_id)}

    migrated = []
    products = woo.list_products(status="any")
    for product in products:
        attrs_on_product = product.get("attributes") or []
        fi_values = []
        other_attrs = []
        for attr in attrs_on_product:
            if int(attr.get("id", 0) or 0) == fi_id or attr_slug_matches(attr.get("slug"), "fi"):
                fi_values = list(attr.get("options") or [])
            else:
                other_attrs.append(attr)
        if not fi_values:
            continue

        new_options = []
        for val in fi_values:
            key = normalize_key(val)
            if key not in st_terms and not dry_run:
                woo.get_or_create_term(st_id, val, dry_run=False)
                st_terms[key] = {"name": val}
            new_options.append(val)

        st_entry = next(
            (a for a in other_attrs if int(a.get("id", 0) or 0) == st_id or attr_slug_matches(a.get("slug"), "srednica-trzpienia")),
            None,
        )
        if st_entry:
            merged = list(dict.fromkeys(list(st_entry.get("options") or []) + new_options))
            st_entry["options"] = merged
            st_entry["visible"] = True
            st_entry["variation"] = st_entry.get("variation", False)
        else:
            other_attrs.append(
                {
                    "id": st_id,
                    "name": st_attr.get("name"),
                    "position": 0,
                    "visible": True,
                    "variation": False,
                    "options": new_options,
                }
            )

        payload = {"attributes": other_attrs}
        if dry_run:
            print(f"[dry-run] migrate pa_fi -> pa_srednica-trzpienia product {product['id']} sku={product.get('sku')}")
        else:
            woo.update_product(product["id"], payload)
            print(f"migrated product {product['id']} sku={product.get('sku')}")
        migrated.append(product["id"])
    return migrated


def delete_unused_attributes(woo, dry_run=True):
    attrs = woo.list_attributes()
    deleted = []
    for attr in attrs:
        slug = str(attr.get("slug", "")).strip().lstrip("pa_")
        if slug == "fi":
            # delete legacy duplicate after migration
            usage = int(attr.get("count", 0) or 0)
            if usage > 0:
                print(f"skip pa_fi: still has usage {usage}")
                continue
        elif slug not in UNUSED_ATTR_SLUGS:
            continue
        else:
            # verify zero usage via product scan is expensive; trust audit + slug list
            pass

        aid = int(attr["id"])
        if dry_run:
            print(f"[dry-run] delete attribute {aid} pa_{slug}")
        else:
            woo.request("DELETE", f"/products/attributes/{aid}?force=true")
            print(f"deleted attribute {aid} pa_{slug}")
        deleted.append(slug)
    return deleted


def count_product_tags(woo):
    tags = woo.list_product_tags()
    return len(tags), tags


def cleanup_woo_orphan_tags(woo, bl, inventory_id, dry_run=True):
    """Delete Woo product_tag terms that are not in BaseLinker inventory (BL -> Woo only)."""
    bl_names = bl.list_inventory_tags(inventory_id)
    bl_norm = {normalize_key(name) for name in bl_names}
    woo_tags = woo.list_product_tags()
    deleted = []
    kept = []
    blocked = []

    for tag in woo_tags:
        name = str(tag.get("name", "")).strip()
        tid = int(tag["id"])
        count = int(tag.get("count", 0) or 0)
        if normalize_key(name) in bl_norm:
            kept.append({"id": tid, "name": name, "count": count})
            continue
        if count > 0:
            blocked.append({"id": tid, "name": name, "count": count})
            if dry_run:
                print(f"[dry-run] orphan tag still in use {tid} {name} count={count}")
            else:
                print(f"WARN: orphan tag {tid} {name} still has count={count}; run product sync first")
            continue
        if dry_run:
            print(f"[dry-run] delete orphan tag {tid} {name}")
        else:
            woo.request("DELETE", f"/products/tags/{tid}?force=true")
            print(f"deleted orphan tag {tid} {name}")
        deleted.append({"id": tid, "name": name})
    return {
        "bl_tags": bl_names,
        "bl_count": len(bl_names),
        "woo_before": len(woo_tags),
        "kept": kept,
        "deleted": deleted,
        "blocked": blocked,
        "woo_after": len(kept) + len(blocked),
    }


def main():
    parser = argparse.ArgumentParser(description="Prod catalog/plugin cleanup")
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--plugins", action="store_true")
    parser.add_argument("--categories", action="store_true")
    parser.add_argument("--attributes", action="store_true")
    parser.add_argument("--tags", action="store_true")
    parser.add_argument("--sync-tags", action="store_true", help="Replace product tags from BL before orphan cleanup")
    parser.add_argument("--rename-categories", action="store_true", help="Rename Woo categories to BL display names")
    parser.add_argument("--all", action="store_true")
    parser.add_argument(
        "--allow-production-host",
        action="store_true",
        help="Allow write operations when WP_BASE_URL is not staging.",
    )
    args = parser.parse_args()

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)
    dry_run = not args.apply

    if args.apply and not dry_run:
        host = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").replace("https://", "").replace("http://", "")
        if "staging" not in host and not args.allow_production_host:
            raise SystemExit("Refusing apply on production without --allow-production-host")

    if args.all:
        args.plugins = args.categories = args.attributes = args.tags = True
        args.sync_tags = True
        args.rename_categories = True

    report = {"dry_run": dry_run}

    if args.plugins:
        report["plugins"] = delete_plugins(env, dry_run=dry_run)

    base, key, secret = woo_creds(env)
    token = bl_token(env)
    inventory_id = env.get("BASELINKER_INVENTORY_ID", "").strip()
    woo = WooClient(base, key, secret)
    bl = BaseLinkerClient(token)
    inventory_id = resolve_inventory_id(bl, inventory_id)

    before_count, _ = count_categories(woo)
    report["categories_before"] = before_count

    if args.categories:
        sync_bl_categories_to_woo(bl, woo, inventory_id, dry_run=dry_run)
        if args.rename_categories:
            report["categories_renamed"] = rename_woo_categories_to_bl(
                bl, woo, inventory_id, dry_run=dry_run
            )
        report["legacy_moved"] = reassign_legacy_categories(woo, bl, inventory_id, dry_run=dry_run)
        report["dedupe_deleted"], report["dedupe_moved"] = dedupe_woo_category_duplicates(
            woo, bl, inventory_id, env=env, dry_run=dry_run
        )
        report["collision_deleted"] = dedupe_bl_canonical_collisions(
            woo, bl, inventory_id, dry_run=dry_run
        )
        report["categories_deleted"], report["categories_kept"] = delete_woo_only_categories(
            woo, bl, inventory_id, env=env, dry_run=dry_run
        )

    if args.attributes:
        report["fi_migrated"] = migrate_pa_fi_products(woo, dry_run=dry_run)
        report["attrs_deleted"] = delete_unused_attributes(woo, dry_run=dry_run)

    tags_before, _ = count_product_tags(woo)
    report["tags_before"] = tags_before
    tag_resolver = BlTagResolver(bl, inventory_id)
    if args.sync_tags:
        report["tags_synced"] = sync_product_tags_from_bl(
            woo, bl, inventory_id, tag_resolver, dry_run=dry_run
        )
    if args.tags:
        report["tags"] = cleanup_woo_orphan_tags(woo, bl, inventory_id, dry_run=dry_run)
    tags_after, _ = count_product_tags(woo)
    report["tags_after"] = tags_after
    report["bl_tag_count"] = len(bl.list_inventory_tags(inventory_id))

    after_count, _ = count_categories(woo)
    report["categories_after"] = after_count
    report["bl_category_count"] = len(bl.list_categories(inventory_id))

    print("\n=== REPORT ===")
    print(json.dumps(report, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
