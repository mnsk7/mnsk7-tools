# Staging playbook (mnsk7-tools.pl → Cyber_Folks)

Powtarzalny pipeline: lokalnie → staging, z DB i SSL.

---

## 1. Co automatyzujemy

| Akcja | Automatyzacja |
|--------|----------------|
| Dump/import DB | ✅ `make staging-refresh` (skrypt) |
| Przenoszenie plików (tema/plugins/mu-plugins) | ✅ `make deploy-files` (rsync) |
| Search-replace domeny (prod → staging) | ✅ w ramach staging-refresh |
| Flush permalinks/cache | ✅ w ramach staging-refresh |
| DISALLOW_INDEXING, wyłączenie maili/płatności na staging | ✅ MU plugin + wp option blog_public 0 |
| Ochrona staging (basic auth) | Opcjonalnie (ręcznie w panelu) |
| Deploy przez SSH/rsync | ✅ scripts/deploy-rsync.sh |
| SSL | ⚠️ Shared: 1× w panelu Let's Encrypt. VPS: certbot/acme.sh |

---

## 2. Staging jako „slot”

- **Domena:** staging.mnsk7-tools.pl (lub subdomena w panelu).
- **Baza:** osobna, np. mnsk7_stg.
- **wp-config:** osobny dla staging (DB_NAME, DB_USER, DB_PASSWORD, WP_HOME, WP_SITEURL).

Must-have w wp-config staging:

```php
define('WP_ENVIRONMENT_TYPE', 'staging');
define('DISALLOW_FILE_EDIT', true);
define('WP_DEBUG', false);  // lub osobny toggle
define('WP_DISABLE_FATAL_ERROR_HANDLER', false);
define('WP_CACHE', true);  // lub false — w zależności od potrzeby
```

---

## 3. One-button: `make staging-refresh`

Wykonuje:

1. Dump prod DB (na serwerze).
2. Import do staging DB.
3. Search-replace domeny (https + http).
4. `wp option update blog_public 0`.
5. `wp rewrite flush --hard`, `wp cache flush`.
6. (Opcjonalnie wcześniej: `make deploy-files` — rsync mu-plugins/tema.)

```bash
make staging-refresh
```

Pełny flow (pliki + DB):

```bash
make staging-full
```

---

## 4. Skrypty i layout

- **scripts/staging-refresh.sh** — SSH, dump → import → replace → flush (wymaga wp-cli na serwerze).
- **scripts/deploy-rsync.sh** — rsync mu-plugins (i opcjonalnie themes/plugins) na staging/prod.
- **mu-plugins/staging-safety.php** — MU plugin: blokada maili, opcjonalnie bramek płatności na staging.
- **Makefile** — `staging-refresh`, `deploy-files`, `staging-full`.

W repozytorium: tema + mu-plugins (i ewentualnie własne pluginy). Uploads i core WP nie w Git.

---

## 5. SSL

- **Shared Cyber_Folks:** panel → SSL/TLS → Let's Encrypt → włączyć dla staging.mnsk7-tools.pl. HSTS dopiero po sprawdzeniu HTTPS.
- **VPS/root:** `certbot --nginx -d staging.mnsk7-tools.pl` lub acme.sh.

---

## 6. Komendy (szablon)

**A) Eksport prod DB (na serwerze)**  
`cd ~/domains/mnsk7-tools.pl/public_html && wp db export /tmp/prod.sql --add-drop-table`

**B) Import na staging**  
`cd ~/domains/staging.mnsk7-tools.pl/public_html && wp db import /tmp/prod.sql`

**C) Replace domeny**  
`wp search-replace 'https://mnsk7-tools.pl' 'https://staging.mnsk7-tools.pl' --all-tables --precise`  
`wp search-replace 'http://mnsk7-tools.pl' 'https://staging.mnsk7-tools.pl' --all-tables --precise`

**D) Wyłączenie indeksacji**  
`wp option update blog_public 0`

**E) Flush**  
`wp rewrite flush --hard`  
`wp cache flush || true`

---

## 7. SSH bez hasła (raz)

Żeby `make staging-refresh` i rsync nie pytały o hasło:

```bash
ssh-keygen -t ed25519 -C "deploy"
ssh-copy-id -p 222 llojjlcemq@s56.cyber-folks.pl
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
```

W `~/.ssh/config`: Host mnsk7-staging, HostName s56.cyber-folks.pl, User llojjlcemq, Port 222, IdentityFile ~/.ssh/id_ed25519. Skill: `ssh_key_auth_playbook`.

---

## 8. Skills

- `staging_bootstrap` — podniesienie stagingu, co wyłączyć, wp-cli.
- `db_provision_mysql` — tworzenie BД, import/eksport, kodowanie.
- `ssl_cyberfolks` — SSL w panelu (shared) lub certbot (VPS).
- `vps_deploy_rsync`, `ssl_certbot_playbook`, `ssh_key_auth_playbook`.
