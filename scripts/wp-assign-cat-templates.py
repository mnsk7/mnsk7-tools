#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Assign category landing template to pages. Run from project root: python3 scripts/wp-assign-cat-templates.py"""
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
            if line and '=' in line and not line.startswith('#'):
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env

env = load_env()
base = env.get('WP_BASE_URL', '').rstrip('/')
auth = base64.b64encode(f"{env.get('WP_USER', '')}:{env.get('WP_APP_PASSWORD', '')}".encode()).decode()
headers = {'Authorization': f'Basic {auth}', 'Accept': 'application/json', 'Content-Type': 'application/json'}

# ID stron kategorii (dostosuj do stagingu po utworzeniu stron)
IDS = [35187, 35190, 35193, 35196, 35199, 35202, 35205, 35208, 35211, 35214]

for pid in IDS:
    b = json.dumps({'template': 'page-category-landing.php'}).encode()
    req = urllib.request.Request(f'{base}/wp-json/wp/v2/pages/{pid}', data=b, headers=headers, method='POST')
    try:
        d = json.load(urllib.request.urlopen(req))
        print(f'OK id={pid} template={d.get("template")}')
    except urllib.error.HTTPError as e:
        if e.code == 404:
            print(f'Skip id={pid} (not found on this site — update IDS for this env)')
        else:
            print(f'FAIL id={pid}', e.code, e.read().decode()[:150])
    except Exception as e:
        print(f'FAIL id={pid}', e)
