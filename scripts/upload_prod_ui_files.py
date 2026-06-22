#!/usr/bin/env python3
"""Upload selected PDP runtime files to production over SFTP."""
import os
import posixpath
import shlex
import time

import paramiko

from baselinker_sync_products import load_env
from deploy_prod_sftp import resolve_prod_ssh, require_manual_prod_deploy


FILES = [
    ("mu-plugins/inc/product-card.php", "wp-content/mu-plugins/inc/product-card.php"),
    ("wp-content/themes/mnsk7-storefront/functions.php", "wp-content/themes/mnsk7-storefront/functions.php"),
    (
        "wp-content/themes/mnsk7-storefront/woocommerce/archive-product.php",
        "wp-content/themes/mnsk7-storefront/woocommerce/archive-product.php",
    ),
    ("wp-content/themes/mnsk7-storefront/page-kontakt.php", "wp-content/themes/mnsk7-storefront/page-kontakt.php"),
    ("wp-content/themes/mnsk7-storefront/assets/css/main.css", "wp-content/themes/mnsk7-storefront/assets/css/main.css"),
    (
        "wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css",
        "wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css",
    ),
    (
        "wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css",
        "wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css",
    ),
]


def sftp_mkdirs(sftp, path):
    current = ""
    for part in path.strip("/").split("/"):
        current = f"{current}/{part}" if current else part
        try:
            sftp.stat(current)
        except IOError:
            sftp.mkdir(current)


def ssh_exec(ssh, command):
    stdin, stdout, stderr = ssh.exec_command(command)
    out = stdout.read().decode("utf-8", "ignore").strip()
    err = stderr.read().decode("utf-8", "ignore").strip()
    code = stdout.channel.recv_exit_status()
    return code, out, err


def main():
    env = {}
    env.update(load_env(".env"))
    env.update(os.environ)

    cfg = resolve_prod_ssh(env)
    host = cfg["host"]
    port = cfg["port"]
    user = cfg["user"]
    password = cfg["password"]
    wp_path = cfg["wp_path"]
    dry_run = bool(env.get("DRY_RUN"))
    require_manual_prod_deploy(dry_run=dry_run)
    if not all([host, user, password]):
        raise SystemExit("Missing SSH credentials")

    if dry_run:
        print(f"[DRY-RUN] target=prod path={wp_path}")
        for local, remote_rel in FILES:
            remote = posixpath.join(wp_path, remote_rel)
            size = os.path.getsize(local)
            print(f"[DRY-RUN] upload {local} ({size} bytes) -> {remote}")
        return

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port=port, username=user, password=password, timeout=25)

    backup_root = posixpath.join(f"codex-prod-backup-{time.strftime('%Y%m%d-%H%M%S')}", "mnsk7-tools.pl")
    print(f"[OK] backup root {backup_root}")
    for _local, remote_rel in FILES:
        remote = posixpath.join(wp_path, remote_rel)
        backup = posixpath.join(backup_root, remote_rel)
        command = (
            f"mkdir -p {shlex.quote(posixpath.dirname(backup))} && "
            f"if [ -f {shlex.quote(remote)} ]; then cp {shlex.quote(remote)} {shlex.quote(backup)}; fi"
        )
        code, out, err = ssh_exec(ssh, command)
        if code != 0:
            raise SystemExit(f"Backup failed for {remote}: {err or out}")

    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    for local, remote_rel in FILES:
        remote = posixpath.join(wp_path, remote_rel)
        sftp_mkdirs(sftp, posixpath.dirname(remote))
        sftp.put(local, remote)
        print(f"[OK] upload {local} -> {remote}")

    sftp.close()

    commands = [
        f"cd {wp_path} && wp --skip-plugins --skip-themes cache flush",
        f"cd {wp_path} && rm -rf wp-content/cache/min wp-content/cache/wp-rocket/min wp-content/cache/wp-rocket/mnsk7-tools.pl",
    ]
    for command in commands:
        code, out, err = ssh_exec(ssh, command)
        print(f"[{'OK' if code == 0 else 'WARN'}] {command}")
        if out:
            print(out)
        if err:
            print(err)
    ssh.close()
    transport.close()


if __name__ == "__main__":
    main()
