#!/usr/bin/env python3
"""Deploy mu-plugins + mnsk7-storefront theme to production via SFTP (Windows-friendly)."""
import os
import posixpath
import shlex
import time
from pathlib import Path

import paramiko

from baselinker_sync_products import load_env


ROOT = Path(__file__).resolve().parents[1]
DEPLOY_DIRS = [
    ("mu-plugins", "wp-content/mu-plugins"),
    ("wp-content/themes/mnsk7-storefront", "wp-content/themes/mnsk7-storefront"),
]
SKIP_PARTS = {".git", "__pycache__", "node_modules", ".DS_Store"}


def sftp_mkdirs(sftp, path):
    current = ""
    for part in path.strip("/").split("/"):
        current = f"{current}/{part}" if current else part
        try:
            sftp.stat(current)
        except OSError:
            sftp.mkdir(current)


def ssh_exec(ssh, command):
    stdin, stdout, stderr = ssh.exec_command(command)
    out = stdout.read().decode("utf-8", "ignore").strip()
    err = stderr.read().decode("utf-8", "ignore").strip()
    code = stdout.channel.recv_exit_status()
    return code, out, err


def iter_files(local_root):
    for path in local_root.rglob("*"):
        if not path.is_file():
            continue
        if any(part in SKIP_PARTS for part in path.parts):
            continue
        yield path


def main():
    env = {}
    env.update(load_env(".env"))
    env.update(os.environ)

    host = (env.get("cyberfolks_ssh_host") or "").strip()
    port = int((env.get("cyberfolks_ssh_port") or "22").strip())
    user = (env.get("cyberfolks_ssh_user") or "").strip()
    password = (env.get("cyberfolks_ssh_password") or "").strip()
    wp_path = (env.get("STAGING_PROD_PATH") or "domains/mnsk7-tools.pl/public_html").strip().strip("/")
    dry_run = bool(env.get("DRY_RUN"))

    if not all([host, user, password]):
        raise SystemExit("Missing SSH credentials in .env")

    files = []
    for local_rel, remote_rel in DEPLOY_DIRS:
        local_root = ROOT / local_rel
        if not local_root.is_dir():
            raise SystemExit(f"Missing local dir: {local_root}")
        for path in iter_files(local_root):
            rel = path.relative_to(local_root).as_posix()
            files.append((path, posixpath.join(remote_rel, rel)))

    print(f"Deploy prod SFTP dry_run={dry_run} files={len(files)} path={wp_path}")
    if dry_run:
        for local, remote in files[:15]:
            print(f"[DRY-RUN] {local} -> {remote}")
        if len(files) > 15:
            print(f"[DRY-RUN] ... and {len(files) - 15} more")
        return

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port=port, username=user, password=password, timeout=30)

    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    uploaded = 0
    for local, remote_rel in files:
        remote = posixpath.join(wp_path, remote_rel)
        sftp_mkdirs(sftp, posixpath.dirname(remote))
        sftp.put(str(local), remote)
        uploaded += 1
        if uploaded % 50 == 0:
            print(f"[OK] uploaded {uploaded}/{len(files)}...")

    sftp.close()
    transport.close()

    for command in (
        f"cd {wp_path} && wp --skip-plugins --skip-themes cache flush",
        f"rm -rf {wp_path}/wp-content/cache/min {wp_path}/wp-content/cache/wp-rocket 2>/dev/null; echo cache cleared",
    ):
        code, out, err = ssh_exec(ssh, command)
        print(f"[{'OK' if code == 0 else 'WARN'}] {command}")
        if out:
            print(out)
        if err:
            print(err)
    ssh.close()
    print(f"Done. Uploaded {uploaded} files.")


if __name__ == "__main__":
    main()
