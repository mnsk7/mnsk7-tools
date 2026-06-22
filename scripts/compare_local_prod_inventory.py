#!/usr/bin/env python3
"""Inventory diff: local worktree vs production SFTP vs git refs.

Usage:
  python scripts/compare_local_prod_inventory.py
  python scripts/compare_local_prod_inventory.py --download output/prod-snapshot

Does not print secrets. Read-only on prod (download optional).
"""
from __future__ import annotations

import argparse
import hashlib
import os
import posixpath
import re
import subprocess
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))

from baselinker_sync_products import load_env  # noqa: E402
from deploy_prod_sftp import resolve_prod_ssh  # noqa: E402

PRIORITY_PATHS = [
    "wp-content/mu-plugins/inc/product-card.php",
    "wp-content/mu-plugins/inc/woo-ux.php",
    "wp-content/themes/mnsk7-storefront/functions.php",
    "wp-content/themes/mnsk7-storefront/assets/css/main.css",
    "wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css",
    "wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css",
    "wp-content/themes/mnsk7-storefront/woocommerce/archive-product.php",
    "wp-content/themes/mnsk7-storefront/page-kontakt.php",
]

SCAN_DIRS = [
    ("mu-plugins", "wp-content/mu-plugins"),
    ("wp-content/themes/mnsk7-storefront", "wp-content/themes/mnsk7-storefront"),
]
SKIP_PARTS = {".git", "__pycache__", "node_modules", ".DS_Store"}


def file_md5(path: Path) -> str | None:
    if not path.is_file():
        return None
    h = hashlib.md5()
    with path.open("rb") as f:
        for chunk in iter(lambda: f.read(65536), b""):
            h.update(chunk)
    return h.hexdigest()


def remote_to_git_rel(remote_rel: str) -> str:
    if remote_rel.startswith("wp-content/mu-plugins/"):
        return "mu-plugins/" + remote_rel[len("wp-content/mu-plugins/") :]
    return remote_rel


def git_blob_md5(remote_rel: str, ref: str = "HEAD") -> str | None:
    rel = remote_to_git_rel(remote_rel)
    try:
        data = subprocess.check_output(
            ["git", "show", f"{ref}:{rel}"],
            cwd=ROOT,
            stderr=subprocess.DEVNULL,
        )
    except subprocess.CalledProcessError:
        return None
    return hashlib.md5(data).hexdigest()


def theme_version_from_bytes(data: bytes) -> str | None:
    text = data.decode("utf-8", "ignore")
    m = re.search(r"MNSK7_THEME_VERSION['\"],\s*['\"]([0-9.]+)['\"]", text)
    return m.group(1) if m else None


def iter_local_files(local_root: Path, remote_prefix: str):
    for path in local_root.rglob("*"):
        if not path.is_file():
            continue
        if any(part in SKIP_PARTS for part in path.parts):
            continue
        rel = path.relative_to(local_root).as_posix()
        yield f"{remote_prefix}/{rel}" if rel else remote_prefix, path


def connect_sftp(cfg):
    transport = paramiko.Transport((cfg["host"], cfg["port"]))
    transport.connect(username=cfg["user"], password=cfg["password"])
    return paramiko.SFTPClient.from_transport(transport), transport


def prod_md5(sftp, wp_path: str, remote_rel: str) -> tuple[str | None, bytes | None]:
    remote = posixpath.join(wp_path, remote_rel)
    try:
        with sftp.open(remote, "rb") as rf:
            data = rf.read()
        return hashlib.md5(data).hexdigest(), data
    except OSError:
        return None, None


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--download", type=Path, help="Save prod copies under this dir")
    parser.add_argument("--limit", type=int, default=0, help="Max files to scan (0=all)")
    args = parser.parse_args()

    env = {}
    env.update(load_env(ROOT / ".env"))
    env.update(os.environ)
    cfg = resolve_prod_ssh(env)

    print(f"prod_host={cfg['host']} prod_path={cfg['wp_path']}")
    print()

    # Build file list
    files: dict[str, Path] = {}
    for local_rel, remote_prefix in SCAN_DIRS:
        local_root = ROOT / local_rel
        if not local_root.is_dir():
            continue
        for remote_rel, local_path in iter_local_files(local_root, remote_prefix):
            files[remote_rel] = local_path

    priority = [p for p in PRIORITY_PATHS if p in files]
    rest = sorted(k for k in files if k not in priority)
    ordered = priority + rest
    if args.limit:
        ordered = ordered[: args.limit]

    sftp, transport = connect_sftp(cfg)
    if args.download:
        args.download.mkdir(parents=True, exist_ok=True)

    diff_count = 0
    diff_git_head = 0
    diff_git_origin = 0
    header = f"{'file':<58} {'L=P':^5} {'L=H':^5} {'P=H':^5} {'H=O':^5} notes"
    print(header)
    print("-" * len(header))

    for remote_rel in ordered:
        local_path = files[remote_rel]
        loc = file_md5(local_path)
        git_h = git_blob_md5(remote_rel, "HEAD")
        git_o = git_blob_md5(remote_rel, "origin/main")
        prod, prod_data = prod_md5(sftp, cfg["wp_path"], remote_rel)

        same_lp = loc == prod if loc and prod else False
        same_lh = loc == git_h if loc and git_h else False
        same_ph = prod == git_h if prod and git_h else False
        same_ho = git_h == git_o if git_h and git_o else False
        marker = " " if same_lp else "*"
        if not same_lp:
            diff_count += 1
        if not same_lh:
            diff_git_head += 1
        if git_o and git_h != git_o:
            diff_git_origin += 1

        notes = ""
        if remote_rel.endswith("functions.php") and prod_data:
            notes = f"prod={theme_version_from_bytes(prod_data) or '?'}"
            loc_ver = theme_version_from_bytes(local_path.read_bytes())
            if loc_ver:
                notes += f" local={loc_ver}"
        elif not same_lp and same_ph and not same_lh:
            notes = "prod=HEAD; local uncommitted"
        elif not same_lp and same_lh:
            notes = "local committed; prod behind"
        elif same_lp and not same_lh:
            notes = "local uncommitted only"

        short = remote_rel if len(remote_rel) <= 58 else "..." + remote_rel[-55:]
        print(
            f"{marker}{short:<57} "
            f"{'Y' if same_lp else 'N':^5} "
            f"{'Y' if same_lh else 'N':^5} "
            f"{'Y' if same_ph else 'N':^5} "
            f"{'Y' if same_ho else 'N':^5} "
            f"{notes}"
        )

        if args.download and prod_data is not None:
            out = args.download / remote_rel
            out.parent.mkdir(parents=True, exist_ok=True)
            out.write_bytes(prod_data)

    sftp.close()
    transport.close()

    print()
    print(f"scanned={len(ordered)} local_vs_prod_diffs={diff_count} local_vs_HEAD_diffs={diff_git_head} HEAD_vs_origin_diffs={diff_git_origin}")
    print("legend: * = local!=prod; L=P local vs prod; L=H local vs HEAD; P=H prod vs HEAD; H=O HEAD vs origin/main")


if __name__ == "__main__":
    main()
