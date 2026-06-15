#!/usr/bin/env python3
"""Parse raw chat SKU blocks, assign MNSK7 variant groups, and create offer pages via WP-CLI."""
import argparse
import json
import os
import re
import shlex
import time
from collections import defaultdict
from pathlib import Path

import paramiko

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

RAW_OFFER_AXIS_OVERRIDES = {
    1: "model",
    2: "model",
    3: "srednica,dlugosc-robocza-h,dlugosc-calkowita-l",
    4: "srednica,dlugosc-robocza-h",
    5: "srednica-trzpienia,r",
    6: "srednica-trzpienia,srednica",
    7: "srednica-trzpienia,srednica",
    8: "srednica-trzpienia,srednica,kat-skosu",
    9: "er,srednica-trzpienia",
    10: "srednica-trzpienia,srednica",
    11: "srednica,srednica-trzpienia",
    12: "srednica,srednica-trzpienia",
    13: "model",
    14: "srednica-trzpienia,srednica",
    15: "model",
    16: "srednica,typ",
    17: "srednica,r",
    18: "srednica-trzpienia,r,dlugosc-calkowita-l",
}


def strip_chat_prefix(line):
    line = line.strip()
    if not line:
        return ""
    return re.sub(r"^\[\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}\]\s*\d+:\s*", "", line).strip()


def is_sku_token(token):
    token = token.strip()
    if not token:
        return False
    return bool(re.match(r"^[A-Za-z0-9._*xX°-]{3,}$", token))


def looks_like_axis_line(line):
    text = line.strip().lower()
    if not text:
        return False
    if text in {"хз", "xz"}:
        return True
    keywords = (
        "диаметр",
        "длина",
        "зажим",
        "угол",
        "шарик",
        "тип",
        "рабоч",
        "общая",
        "trzpien",
        "srednica",
        "kat",
        "typ",
        "по картинке",
        "по размерам",
        "er",
    )
    return any(keyword in text for keyword in keywords)


def axis_text_to_axis(axis_text):
    raw = axis_text.strip().lower()
    text = normalize_key(axis_text)
    text_mix = " ".join(part for part in (text, raw) if part).strip()
    if not text_mix or raw in {"хз", "xz"}:
        return "model"

    axis = []
    if any(item in text_mix for item in ("srednica-trzpienia", "trzpien", "zazhim", "zazhym", "зажим")):
        axis.append("srednica-trzpienia")
    if any(item in text_mix for item in ("srednica", "diametr", "raboc", "диаметр", "рабоч")):
        axis.append("srednica")
    if any(item in text_mix for item in ("kat", "ugol", "угол")):
        axis.append("kat-skosu")
    if any(item in text_mix for item in ("tip", "typ", "тип")):
        axis.append("typ")
    if any(item in text_mix for item in ("sharik", "шарик")):
        axis.append("r")
    if ("dlina" in text_mix and "roboc" in text_mix) or ("длина" in text_mix and "рабоч" in text_mix):
        axis.append("dlugosc-robocza-h")
    if any(item in text_mix for item in ("obsh", "calkow", "общая")):
        axis.append("dlugosc-calkowita-l")
    if "er" in text_mix:
        axis.append("er")
    if not axis:
        return "model"
    unique = []
    for item in axis:
        if item not in unique:
            unique.append(item)
    return ",".join(unique)


def parse_groups(raw_text, prefix):
    lines = [strip_chat_prefix(line) for line in raw_text.splitlines()]
    lines = [line for line in lines if line]

    groups = []
    pending_skus = []
    pending_axis_lines = []

    def flush_group(default_axis_note="model"):
        nonlocal pending_skus, pending_axis_lines, groups
        if len(pending_skus) < 2:
            pending_skus = []
            pending_axis_lines = []
            return
        axis_note = " ".join(pending_axis_lines).strip() if pending_axis_lines else default_axis_note
        axis = axis_text_to_axis(axis_note)
        groups.append(
            {
                "group": f"{prefix}-{len(groups) + 1:02d}",
                "axis": axis,
                "axis_note": axis_note,
                "skus": pending_skus[:],
            }
        )
        pending_skus = []
        pending_axis_lines = []

    for line in lines:
        if is_sku_token(line):
            pending_skus.append(line)
            continue

        if looks_like_axis_line(line):
            pending_axis_lines.append(line)
            # In this chat format axis usually closes previous sku block.
            if pending_skus:
                flush_group()
            continue

        # Any other text boundary: close current block if present.
        if pending_skus:
            flush_group(default_axis_note=line)

    if pending_skus:
        flush_group()

    for index, group in enumerate(groups, start=1):
        override = RAW_OFFER_AXIS_OVERRIDES.get(index)
        if override:
            group["axis"] = override
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


def ssh_exec(ssh, command):
    stdin, stdout, stderr = ssh.exec_command(command)
    out = stdout.read().decode("utf-8", "ignore").strip()
    err = stderr.read().decode("utf-8", "ignore").strip()
    code = stdout.channel.recv_exit_status()
    if code != 0:
        raise RuntimeError(f"Remote command failed ({code}): {command}\n{out}\n{err}")
    return out


def upsert_page(ssh, wp_path, slug, title, content):
    slug_q = shlex.quote(slug)
    title_q = shlex.quote(title)
    content_q = shlex.quote(content)

    get_id_cmd = (
        f"cd {shlex.quote(wp_path)} && "
        f"wp --skip-plugins --skip-themes post list --post_type=page --name={slug_q} --field=ID --format=ids"
    )
    existing_id = ssh_exec(ssh, get_id_cmd).strip()
    if existing_id:
        update_cmd = (
            f"cd {shlex.quote(wp_path)} && "
            f"wp --skip-plugins --skip-themes post update {shlex.quote(existing_id)} "
            f"--post_title={title_q} --post_name={slug_q} --post_content={content_q} --post_status=publish"
        )
        ssh_exec(ssh, update_cmd)
        return int(existing_id), "updated"

    create_cmd = (
        f"cd {shlex.quote(wp_path)} && "
        f"wp --skip-plugins --skip-themes post create --post_type=page --post_status=publish "
        f"--post_title={title_q} --post_name={slug_q} --post_content={content_q} --porcelain"
    )
    new_id = ssh_exec(ssh, create_cmd).strip()
    return int(new_id), "created"


def pick_representative(products):
    if not products:
        return None
    ranked = sorted(
        products,
        key=lambda item: (
            0 if item.get("stock_status") == "instock" else 1,
            float(item.get("price") or 999999),
            int(item.get("id") or 0),
        ),
    )
    return ranked[0]


def build_seo_offer_slug(page_prefix, representative, fallback_group):
    product_slug = str((representative or {}).get("slug", "")).strip()
    if product_slug:
        return f"oferta-{product_slug}-warianty"
    return f"{page_prefix}{fallback_group}"


def main():
    parser = argparse.ArgumentParser(description="Assign groups from raw chat and create offer pages")
    parser.add_argument("--raw-file", required=True)
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--language", default="pl")
    parser.add_argument("--group-prefix", default="mnsk7-offer")
    parser.add_argument("--page-prefix", default="oferta-")
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--create-pages", action="store_true")
    args = parser.parse_args()

    raw_text = Path(args.raw_file).read_text(encoding="utf-8")
    groups = parse_groups(raw_text, args.group_prefix)
    if not groups:
        raise SystemExit("No groups parsed from raw file")

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)

    baselinker_token = (env.get("BASELINKER_API_TOKEN") or env.get("BASE_API_TOKEN") or env.get("base_api_token") or "").strip()
    inventory_id = env.get("BASELINKER_INVENTORY_ID", "").strip()
    woo_base_url = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    woo_key = (env.get("WOO_CONSUMER_KEY") or env.get("Woo_Klucz_konsumenta") or env.get("WC_CONSUMER_KEY") or "").strip()
    woo_secret = (env.get("WOO_CONSUMER_SECRET") or env.get("Woo_Tajny_konsumenta") or env.get("WC_CONSUMER_SECRET") or "").strip()
    ssh_host = (env.get("cyberfolks_ssh_host") or "").strip()
    ssh_port = int((env.get("cyberfolks_ssh_port") or "22").strip())
    ssh_user = (env.get("cyberfolks_ssh_user") or "").strip()
    ssh_password = (env.get("cyberfolks_ssh_password") or "").strip()
    wp_path = (env.get("STAGING_PROD_PATH") or "domains/mnsk7-tools.pl/public_html").strip().strip("/")

    if not baselinker_token:
        raise SystemExit("Missing BaseLinker token")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing Woo credentials")

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

    missing_bl = []
    missing_woo = []
    group_products = defaultdict(list)

    for group in groups:
        for sku in group["skus"]:
            bl_product = products_by_sku.get(sku)
            if not bl_product:
                missing_bl.append({"group": group["group"], "sku": sku})
                continue

            update_bl_product(
                bl=bl,
                inventory_id=inventory_id,
                product_id=bl_product["_bl_product_id"],
                sku=sku,
                product_data=bl_product,
                language=args.language,
                group_key=group["group"],
                axis_key=group["axis"],
                apply_changes=args.apply,
            )

            woo_item = woo_by_sku.get(sku)
            if not woo_item:
                missing_woo.append({"group": group["group"], "sku": sku})
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
                woo.update_product(woo_item["id"], payload)
            group_products[group["group"]].append(woo_item)
            time.sleep(0.08)

    page_actions = []
    pages = []
    if args.create_pages:
        if not all([ssh_host, ssh_user, ssh_password]):
            raise SystemExit("Missing SSH credentials for page creation")
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(ssh_host, port=ssh_port, username=ssh_user, password=ssh_password, timeout=25)
        try:
            for group in groups:
                products = group_products.get(group["group"], [])
                representative = pick_representative(products)
                if not representative:
                    continue
                slug = build_seo_offer_slug(args.page_prefix, representative, group["group"])
                title = f"Oferta: {group['group']}"
                url = f"{woo_base_url.rstrip('/')}/{slug}/"
                pages.append(
                    {
                        "group": group["group"],
                        "axis": group["axis"],
                        "count": len(products),
                        "product_id": int(representative["id"]),
                        "sku": str(representative.get("sku", "")).strip(),
                        "slug": slug,
                        "url": url,
                    }
                )
                if args.apply:
                    page_id, status = upsert_page(
                        ssh=ssh,
                        wp_path=wp_path,
                        slug=slug,
                        title=title,
                        content=f'[product_page id="{int(representative["id"])}"]',
                    )
                    page_actions.append({"group": group["group"], "page_id": page_id, "status": status, "url": url})
        finally:
            ssh.close()
    else:
        for group in groups:
            products = group_products.get(group["group"], [])
            representative = pick_representative(products)
            if not representative:
                continue
            slug = build_seo_offer_slug(args.page_prefix, representative, group["group"])
            pages.append(
                {
                    "group": group["group"],
                    "axis": group["axis"],
                    "count": len(products),
                    "product_id": int(representative["id"]),
                    "sku": str(representative.get("sku", "")).strip(),
                    "slug": slug,
                    "url": f"{woo_base_url.rstrip('/')}/{slug}/",
                }
            )

    print(
        json.dumps(
            {
                "apply": args.apply,
                "parsed_groups": len(groups),
                "groups": groups,
                "pages": pages,
                "page_actions": page_actions,
                "missing_bl": missing_bl,
                "missing_woo": missing_woo,
            },
            ensure_ascii=True,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
