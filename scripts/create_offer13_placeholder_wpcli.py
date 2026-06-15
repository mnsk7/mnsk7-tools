#!/usr/bin/env python3
"""Create/update placeholder page for offer-13 when SKUs are missing in BaseLinker."""
import os
import tempfile

import paramiko

from baselinker_sync_products import load_env


PHP_SCRIPT = r"""<?php
$slug = 'oferta-h030901-h080901-warianty';
$title = 'Oferta: warianty H030901-H080901';
$content = '<p>Ta oferta jest w przygotowaniu. Warianty zostaną podłączone po synchronizacji SKU w BaseLinker.</p><p><a href="/kontakt/">Skontaktuj się z nami</a>, jeśli chcesz rezerwację.</p>';

$existing = get_posts(array(
  'post_type' => 'page',
  'name' => $slug,
  'post_status' => 'any',
  'posts_per_page' => 1,
  'fields' => 'ids',
));

if (!empty($existing)) {
  $id = (int) $existing[0];
  wp_update_post(array(
    'ID' => $id,
    'post_title' => $title,
    'post_name' => $slug,
    'post_content' => $content,
    'post_status' => 'publish',
  ));
  echo wp_json_encode(array('status' => 'updated', 'page_id' => $id, 'url' => get_permalink($id)));
  return;
}

$id = wp_insert_post(array(
  'post_type' => 'page',
  'post_title' => $title,
  'post_name' => $slug,
  'post_content' => $content,
  'post_status' => 'publish',
));

if (is_wp_error($id)) {
  echo wp_json_encode(array('status' => 'error', 'message' => $id->get_error_message()));
  return;
}

echo wp_json_encode(array('status' => 'created', 'page_id' => (int)$id, 'url' => get_permalink($id)));
"""


def main():
    env = {}
    env.update(load_env(".env"))
    env.update(os.environ)

    host = (env.get("cyberfolks_ssh_host") or "").strip()
    port = int((env.get("cyberfolks_ssh_port") or "22").strip())
    user = (env.get("cyberfolks_ssh_user") or "").strip()
    password = (env.get("cyberfolks_ssh_password") or "").strip()
    wp_path = (env.get("STAGING_PROD_PATH") or "domains/mnsk7-tools.pl/public_html").strip().strip("/")
    if not all([host, user, password]):
        raise SystemExit("Missing SSH credentials")

    with tempfile.NamedTemporaryFile("w", suffix=".php", encoding="utf-8", delete=False) as tmp:
        tmp.write(PHP_SCRIPT)
        local_tmp = tmp.name

    remote_tmp = "/tmp/mnsk7_offer13_placeholder.php"
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    sftp.put(local_tmp, remote_tmp)
    sftp.close()

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port=port, username=user, password=password, timeout=25)
    command = f"cd {wp_path} && wp --skip-plugins --skip-themes eval-file {remote_tmp}"
    stdin, stdout, stderr = ssh.exec_command(command)
    out = stdout.read().decode("utf-8", "ignore").strip()
    err = stderr.read().decode("utf-8", "ignore").strip()
    code = stdout.channel.recv_exit_status()
    ssh.exec_command(f"rm -f {remote_tmp}")[1].channel.recv_exit_status()
    ssh.close()
    transport.close()

    print(out)
    if err:
        print(err)
    if code != 0:
        raise SystemExit(code)


if __name__ == "__main__":
    main()
