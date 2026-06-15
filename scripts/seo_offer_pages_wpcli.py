#!/usr/bin/env python3
"""Rename oferta-mnsk7-offer-* pages to SEO slugs/titles based on linked product."""
import json
import os
import tempfile
from pathlib import Path

import paramiko

from baselinker_sync_products import load_env


PHP_SCRIPT = r"""<?php
$pages = get_posts(array(
  'post_type' => 'page',
  'post_status' => 'publish',
  'posts_per_page' => 400,
  'fields' => 'ids',
));

$updated = array();
foreach ($pages as $page_id) {
  $page = get_post($page_id);
  if (!$page || strpos($page->post_name, 'oferta-mnsk7-offer-') !== 0) {
    continue;
  }

  $content = (string) $page->post_content;
  if (!preg_match('/\[product_page\s+id="(\d+)"\]/', $content, $m)) {
    continue;
  }

  $product_id = (int) $m[1];
  $product = get_post($product_id);
  if (!$product) {
    continue;
  }

  $product_slug = sanitize_title($product->post_name ? $product->post_name : $product->post_title);
  if (!$product_slug) {
    continue;
  }

  $base_slug = 'oferta-' . $product_slug . '-warianty';
  $new_slug = wp_unique_post_slug($base_slug, $page_id, 'publish', 'page', 0);
  $new_title = 'Oferta: ' . wp_strip_all_tags($product->post_title) . ' | warianty';

  wp_update_post(array(
    'ID' => $page_id,
    'post_name' => $new_slug,
    'post_title' => $new_title,
  ));

  update_post_meta($page_id, '_yoast_wpseo_title', $new_title);
  update_post_meta($page_id, '_yoast_wpseo_metadesc', 'Warianty produktu: ' . wp_strip_all_tags($product->post_title) . '. Sprawdź dostępne rozmiary i parametry.');
  update_post_meta($page_id, 'rank_math_title', $new_title);
  update_post_meta($page_id, 'rank_math_description', 'Warianty produktu: ' . wp_strip_all_tags($product->post_title) . '. Sprawdź dostępne rozmiary i parametry.');

  $updated[] = array(
    'page_id' => $page_id,
    'product_id' => $product_id,
    'old_slug' => $page->post_name,
    'new_slug' => $new_slug,
    'url' => get_permalink($page_id),
  );
}

echo wp_json_encode(array('updated' => $updated), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
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
        raise SystemExit("Missing SSH credentials in .env")

    with tempfile.NamedTemporaryFile("w", suffix=".php", encoding="utf-8", delete=False) as tmp:
        tmp.write(PHP_SCRIPT)
        local_tmp = tmp.name

    remote_tmp = "/tmp/mnsk7_offer_slug_seo.php"
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
    out = stdout.read().decode("utf-8", "ignore")
    err = stderr.read().decode("utf-8", "ignore")
    code = stdout.channel.recv_exit_status()

    ssh.exec_command(f"rm -f {remote_tmp}")[1].channel.recv_exit_status()
    ssh.close()
    transport.close()

    print(out)
    if err.strip():
        print(err)
    if code != 0:
        raise SystemExit(code)


if __name__ == "__main__":
    main()
