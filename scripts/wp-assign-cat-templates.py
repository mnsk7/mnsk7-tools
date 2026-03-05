#!/usr/bin/env python3
"""Assign category landing template to pages after deploy."""
import json, base64, urllib.request
env={}
with open('.env') as f:
    for line in f:
        line=line.strip()
        if line and '=' in line and not line.startswith('#'):
            k,v=line.split('=',1); env[k]=v.strip().strip('"').strip("'")
base=env['WP_BASE_URL'].rstrip('/')
auth=base64.b64encode(f"{env['WP_USER']}:{env['WP_APP_PASSWORD']}".encode()).decode()
headers={'Authorization':f'Basic {auth}','Accept':'application/json','Content-Type':'application/json'}
IDS=[35187,35190,35193,35196,35199,35202,35205,35208,35211,35214]
for pid in IDS:
    b=json.dumps({'template':'page-category-landing.php'}).encode()
    req=urllib.request.Request(f'{base}/wp-json/wp/v2/pages/{pid}',data=b,headers=headers,method='POST')
    try:
        d=json.load(urllib.request.urlopen(req))
        print(f'OK id={pid} template={d.get("template")}')
    except Exception as e:
        print(f'FAIL id={pid}',e)
