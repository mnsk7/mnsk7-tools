#!/usr/bin/env python3
"""Create/update common offer pages on production via SSH + WP-CLI."""
import argparse
import json
import os
import shlex
from pathlib import Path

import paramiko

from assign_variant_groups import GROUP_DEFINITIONS
from baselinker_sync_products import WooClient, load_env


def load_runtime_env(env_file):
    env = {}
    env.update(load_env(env_file))
    env.update(os.environ)
    return env


def get_meta_value(meta_data, key):
    if not isinstance(meta_data, list):
        return ""
    for row in meta_data:
        if str(row.get("key", "")).strip() == key:
            return str(row.get("value", "")).strip()
    return ""


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


def build_groups_from_woo(woo_products):
    grouped = {}
    for product in woo_products:
        group_key = get_meta_value(product.get("meta_data", []), "_mnsk7_bl_variant_group")
        if not group_key:
            continue
        grouped.setdefault(group_key, []).append(product)
    return grouped


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


def main():
    parser = argparse.ArgumentParser(description="Create/update offer pages via WP-CLI over SSH")
    parser.add_argument("--env-file", default=".env")
    parser.add_argument("--page-prefix", default="oferta-")
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    env = load_runtime_env(args.env_file)
    wp_base_url = (env.get("WP_BASE_URL") or "").strip().rstrip("/")
    woo_base_url = (env.get("WOO_BASE_URL") or wp_base_url).strip()
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
    ssh_host = (env.get("cyberfolks_ssh_host") or "").strip()
    ssh_port = int((env.get("cyberfolks_ssh_port") or "22").strip())
    ssh_user = (env.get("cyberfolks_ssh_user") or "").strip()
    ssh_password = (env.get("cyberfolks_ssh_password") or "").strip()
    wp_path = (env.get("STAGING_PROD_PATH") or "domains/mnsk7-tools.pl/public_html").strip().strip("/")

    if not all([woo_base_url, woo_key, woo_secret]):
        raise SystemExit("Missing Woo credentials / base URL")
    if args.apply and not all([ssh_host, ssh_user, ssh_password]):
        raise SystemExit("Missing SSH credentials")

    woo = WooClient(woo_base_url, woo_key, woo_secret)
    products = woo.list_products(status="any")
    grouped = build_groups_from_woo(products)

    groups_result = []
    for definition in GROUP_DEFINITIONS:
        group_key = definition["group"]
        members = grouped.get(group_key, [])
        if not members:
            continue
        representative = pick_representative(members)
        if not representative:
            continue
        slug = f"{args.page_prefix}{group_key}"
        title = f"Oferta: {group_key}"
        product_id = int(representative.get("id"))
        groups_result.append(
            {
                "group": group_key,
                "slug": slug,
                "title": title,
                "product_id": product_id,
                "sku": str(representative.get("sku", "")).strip(),
                "count": len(members),
                "url": f"{wp_base_url}/{slug}/",
            }
        )

    actions = []
    if args.apply:
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(ssh_host, port=ssh_port, username=ssh_user, password=ssh_password, timeout=25)
        try:
            for row in groups_result:
                page_id, status = upsert_page(
                    ssh=ssh,
                    wp_path=wp_path,
                    slug=row["slug"],
                    title=row["title"],
                    content=f'[product_page id="{row["product_id"]}"]',
                )
                actions.append(
                    {
                        "group": row["group"],
                        "slug": row["slug"],
                        "page_id": page_id,
                        "status": status,
                        "url": row["url"],
                    }
                )
        finally:
            ssh.close()

    print(
        json.dumps(
            {
                "apply": args.apply,
                "groups_count": len(groups_result),
                "groups": groups_result,
                "actions": actions,
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
