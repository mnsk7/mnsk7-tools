#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Create pages via WP REST API. Run from project root (where .env is)."""
import os
import json
import base64
import urllib.request
import urllib.error

def load_env():
    env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
    if not os.path.isfile(env_path):
        raise SystemExit('No .env in project root')
    env = {}
    with open(env_path) as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if '=' in line:
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env

def main():
    env = load_env()
    base = env.get('WP_BASE_URL', '').rstrip('/')
    user = env.get('WP_USER', '')
    password = env.get('WP_APP_PASSWORD', '')
    if not all([base, user, password]):
        raise SystemExit('Set WP_BASE_URL, WP_USER, WP_APP_PASSWORD in .env')
    auth = base64.b64encode(f'{user}:{password}'.encode()).decode()
    headers = {
        'Authorization': f'Basic {auth}',
        'Accept': 'application/json',
        'Content-Type': 'application/json; charset=utf-8',
    }

    def get_page_id(slug):
        req = urllib.request.Request(
            f'{base}/wp-json/wp/v2/pages?slug={slug}&_fields=id',
            headers={'Authorization': f'Basic {auth}', 'Accept': 'application/json'}
        )
        with urllib.request.urlopen(req) as r:
            data = json.load(r)
        return data[0]['id'] if data else None

    def create_page(slug, title, template):
        existing = get_page_id(slug)
        if existing:
            print(f'Page exists: {title} (id={existing}), setting template.')
            body = json.dumps({'template': template}).encode('utf-8')
            req = urllib.request.Request(
                f'{base}/wp-json/wp/v2/pages/{existing}',
                data=body, headers=headers, method='POST'
            )
            urllib.request.urlopen(req)
            print(f'  OK template={template}')
            return
        body = json.dumps({
            'title': title,
            'slug': slug,
            'status': 'publish',
            'template': template,
            'content': '',
        }).encode('utf-8')
        req = urllib.request.Request(
            f'{base}/wp-json/wp/v2/pages',
            data=body, headers=headers, method='POST'
        )
        try:
            with urllib.request.urlopen(req) as r:
                data = json.load(r)
            print(f'Created: {title} id={data["id"]} {data.get("link", "")}')
        except urllib.error.HTTPError as e:
            print(f'Error {title}: {e.code}', e.read().decode()[:200])

    pages = [
        ('dostawa-i-platnosci', 'Dostawa i płatności', 'page-dostawa.php'),
        ('kontakt', 'Kontakt', 'page-kontakt.php'),
        ('frezy-do-aluminium', 'Frezy do aluminium CNC', 'page-frezy-aluminium.php'),
        ('frezy-mdf', 'Frezy do drewna i MDF', 'page-frezy-mdf.php'),
        ('frezy-do-stali', 'Frezy do stali i metalu', 'page-frezy-stali.php'),
        ('frezy-cnc', 'Frezy CNC', 'page-cnc-frezy.php'),
        # SEO / treść
        ('przewodnik', 'Baza wiedzy', 'page-seo.php'),
        ('regulamin', 'Regulamin', 'page-seo.php'),
        ('polityka-prywatnosci', 'Polityka prywatności', 'page-seo.php'),
    ]
    for slug, title, template in pages:
        create_page(slug, title, template)
    print('Done.')

if __name__ == '__main__':
    main()
