# Server Ops (CyberFolks) — mnsk7-tools.pl

**Agent:** 07_server_ops_cyberfolks  
**Skills:** wp-wpcli-and-ops, cyberfolks_deploy_playbook, wpcli_db_migrations, vps_deploy_rsync, ssl_certbot_playbook, staging_bootstrap, ssl_cyberfolks

Playbook deploy/rollback, wp-config/salts/access, backups, staging-refresh, MU plugin staging-safety, deploy path (rsync/sftp), SSL (shared vs VPS).

**Bezpieczeństwo stage vs prod (ścieżki, wp-config, dry-run):** [DEPLOY_SAFETY.md](DEPLOY_SAFETY.md).

---

## 1. Deploy playbook

### 1.1 Before deploy (checklist)

- [ ] **Backup files** (theme + mu-plugins if changing):
  ```bash
  make backup-files   # or: ./scripts/backup-remote.sh files
  ```
  Or on server:
  ```bash
  cd ~/domains/mnsk7-tools.pl/public_html/staging
  tar -czf ~/backups/staging-theme-$(date +%Y%m%d-%H%M).tar.gz wp-content/themes/ wp-content/mu-plugins/
  ```
- [ ] **Backup DB** (staging or prod, depending on target):
  ```bash
  make backup-db      # or: ./scripts/backup-remote.sh db
  ```
  Or on server (staging):
  ```bash
  cd ~/domains/mnsk7-tools.pl/public_html/staging && wp db export /tmp/backup-staging-$(date +%Y%m%d).sql
  ```
- [ ] **wp-config:** Staging uses its own DB_NAME, WP_HOME, WP_SITEURL; do not overwrite from Git. See [§3](#3-wp-config-salts-access).
- [ ] **MU plugin staging-safety** must be present in repo and deployed (blocks mail/payments on staging). See [§5](#5-mu-plugin-staging-safety).

### 1.2 Deploy (files only)

Deploy **only** theme, mu-plugins, and (optionally) custom plugins. Do **not** deploy uploads or WP core.

| Method | Command | Notes |
|--------|---------|--------|
| **GitHub Actions** | Push to branch `main` | Workflow runs rsync mu-plugins + themes + robots.txt (`.github/workflows/deploy-staging.yml`) |
| **Local** | `make deploy-files` | Uses `.env` (cyberfolks_ssh_*); target staging by default |
| **With theme backup** | `DEPLOY_BACKUP_THEME=1 make deploy-files` | Before rsync, copies current theme to `<THEME>_prev` on server for quick rollback |
| **Prod target** | `./scripts/deploy-rsync.sh prod` | Set `STAGING_PROD_PATH` in `.env` for prod path |

Deploy path on server: `~/domains/mnsk7-tools.pl/public_html/staging` (or `STAGING_REMOTE_PATH`).  
Script: `scripts/deploy-rsync.sh`. Makefile: `deploy-files`, `deploy-mu-plugins`.

### 1.3 After deploy

- [ ] On server (or via SSH script): `wp cache flush || true`, `wp rewrite flush --hard`
- [ ] Smoke test: cart, checkout, product page; confirm no mail sent on staging (staging-safety)
- [ ] Optional post-backup: `make backup-files` to keep a “post-deploy” snapshot

---

## 2. Rollback playbook

### 2.1 Rollback files (theme / mu-plugins)

**Option A — Redeploy previous Git state**

```bash
git checkout <previous-commit>
make deploy-files
# Or push to staging branch to trigger Actions
```

**Option B — Restore from remote backup**

If you ran `backup-remote.sh files` or created a tar on server:

```bash
# On server
cd ~/domains/mnsk7-tools.pl/public_html/staging
tar -xzf ~/backups/staging-theme-YYYYMMDD-HHMM.tar.gz
wp cache flush --path=. && wp rewrite flush --hard --path=.
```

**Option C — Theme _prev copy (if using deploy with backup)**

If deploy was run with theme backup: `DEPLOY_BACKUP_THEME=1 make deploy-files` (copies current theme to `<THEME>_prev` on server before rsync). Rollback on server:

```bash
cd ~/domains/mnsk7-tools.pl/public_html/staging/wp-content/themes
rm -rf tech-storefront && mv tech-storefront_prev tech-storefront
```

(Theme name may be `tech-storefront` or your active theme; see Makefile/deploy script.)

### 2.2 Rollback database

Only if a bad DB change was applied (e.g. after staging-refresh or a failed migration):

```bash
# On server, in staging root
wp db import /tmp/backup-staging-YYYYMMDD.sql
wp cache flush
wp rewrite flush --hard
```

Keep DB backups in a known location (e.g. `/tmp/` or `~/backups/`) and document retention.

---

## 3. wp-config, salts, access

### 3.1 wp-config (staging)

- **Do not** commit `wp-config.php` or put it in Git.
- On staging, `wp-config.php` is **server-local**. After copying from prod (or creating from scratch), set:
  - `DB_NAME`, `DB_USER`, `DB_PASSWORD` → staging DB and user
  - `WP_HOME`, `WP_SITEURL` → `https://staging.mnsk7-tools.pl`
  - `define('WP_ENVIRONMENT_TYPE', 'staging');` (required for staging-safety MU plugin)
  - `define('DISALLOW_FILE_EDIT', true);`
  - `WP_DEBUG` → `false` (or as needed)

See also: `docs/staging-wpconfig-fix-dupes.md` (fix duplicate defines, DB user).

### 3.2 Salts and keys

- Salts/keys are in `wp-config.php` (AUTH_KEY, SECURE_AUTH_KEY, etc.). Do not commit; generate per environment (e.g. [api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/)).
- Staging can use its own set of salts (recommended).

### 3.3 Access (SSH, DirectAdmin, secrets)

- **SSH:** Key-based auth; private key in GitHub Secrets as `STAGING_SSH_KEY` for Actions. Local deploy uses `.env`: `cyberfolks_ssh_host`, `cyberfolks_ssh_port`, `cyberfolks_ssh_user`.
- **DirectAdmin:** Separate login; do not store passwords in repo.
- **Secrets:** All credentials and keys only in `.env` (local) or GitHub Actions secrets; never in Git.

---

## 4. Backups (before / after)

| What | When | Where / command |
|------|------|------------------|
| **DB (prod)** | Before staging-refresh or major prod change | On server: `cd ~/domains/.../public_html && wp db export /tmp/backup-prod-$(date +%Y%m%d).sql` |
| **DB (staging)** | Before experiments or staging-refresh import | On server: `cd ~/.../staging && wp db export /tmp/backup-staging-$(date +%Y%m%d).sql` |
| **Files (theme + mu-plugins)** | Before major deploy | `make backup-files` or server: `tar -czf ~/backups/staging-theme-$(date +%Y%m%d-%H%M).tar.gz wp-content/themes/ wp-content/mu-plugins/` |
| **After deploy** | Optional | Same as above; keep one “post-deploy” snapshot for rollback |

Scripts: `scripts/backup-remote.sh` (optional) for remote backup triggers.  
On shared hosting (CyberFolks): check panel for automated DB/file backups; consider UpdraftPlus or similar if not available (see AS_IS_BACKLOG).

---

## 5. Staging-refresh playbook (dump prod → import staging → replace → flush)

Refreshes staging DB from production: dump prod → import into staging → search-replace URLs → disable indexing → flush.

### 5.1 One-shot

```bash
make staging-refresh
```

Requires: `.env` with `cyberfolks_ssh_host`, `cyberfolks_ssh_port`, `cyberfolks_ssh_user`; on server: WP-CLI in PATH for both prod and staging roots.

### 5.2 What it does (script: `scripts/staging-refresh.sh`)

1. **Dump prod DB:** `wp db export /tmp/prod_mnsk7.sql --add-drop-table` (in prod path)
2. **Import to staging:** `wp db import /tmp/prod_mnsk7.sql` (in staging path)
3. **Replace URLs:** `wp search-replace` prod URL → staging URL (https and http), `--all-tables --precise`
4. **Disable indexing:** `wp option update blog_public 0`
5. **Flush:** `wp rewrite flush --hard`, `wp cache flush`
6. **Cleanup:** `rm -f /tmp/prod_mnsk7.sql`

Override paths/URLs via env: `STAGING_PROD_PATH`, `STAGING_STAGING_PATH`, `STAGING_PROD_URL`, `STAGING_STAGING_URL`.

### 5.3 Full flow (files + DB)

```bash
make staging-full   # deploy-files then staging-refresh
```

---

## 6. MU plugin: staging-safety

**File:** `mu-plugins/staging-safety.php`

**Purpose:** On staging only (`WP_ENVIRONMENT_TYPE === 'staging'`):

- **Mail:** All `wp_mail` redirected to `dev-null@localhost`; subject prefixed with `[STAGING BLOCKED]`.
- **Payments:** WooCommerce payment gateways disabled (`woocommerce_available_payment_gateways` returns empty array).

**Deploy:** Shipped with `mu-plugins/`; deployed via `make deploy-files` or GitHub Actions. Must be present in `wp-content/mu-plugins/` on staging. No activation needed (MU plugins load automatically).

**Verification:** After deploy, place test order on staging → no real email sent; no real payment methods available.

---

## 7. Deploy path (rsync / SFTP), scripts, Makefile

### 7.1 Paths on server

| Environment | Default path (under `~`) |
|-------------|---------------------------|
| **Staging** | `domains/mnsk7-tools.pl/public_html/staging` |
| **Production** | `domains/mnsk7-tools.pl/public_html` |

Override locally with `.env`: `STAGING_REMOTE_PATH`, `STAGING_PROD_PATH`.

### 7.2 Scripts (`scripts/`)

| Script | Usage | Description |
|--------|--------|-------------|
| `deploy-rsync.sh` | `./scripts/deploy-rsync.sh [staging\|prod]` | Rsync mu-plugins, themes, plugins to staging or prod. Uses `.env` for SSH. |
| `staging-refresh.sh` | `./scripts/staging-refresh.sh` or `make staging-refresh` | Prod DB dump → import to staging → search-replace → blog_public 0 → flush. |
| `backup-remote.sh` | `./scripts/backup-remote.sh [files\|db]` | Trigger remote backup of files or DB (optional). |
| `staging-fix-db.sh` | `make staging-fix-db` | Fix siteurl/home and blog_public on staging (if not using full refresh). |
| `sync-prod-to-staging.sh` | `make sync-prod-to-staging` | Copy prod files to staging (wp-config not touched). |

### 7.3 Makefile targets

| Target | Action |
|--------|--------|
| `deploy-files` | Rsync mu-plugins + themes (+ plugins if present) to **staging** |
| `deploy-mu-plugins` | Alias for deploy-files |
| `staging-refresh` | Run `staging-refresh.sh` (DB refresh from prod) |
| `staging-full` | `deploy-files` then `staging-refresh` |
| `staging-fix-db` | Fix staging DB URLs and blog_public |
| `sync-prod-to-staging` | Copy prod files to staging |
| `backup-files` | Remote backup of theme + mu-plugins (if script exists) |
| `backup-db` | Remote backup of staging DB (if script exists) |

SFTP: Use same host/user/port as rsync; path as above. No automated SFTP script; use for ad-hoc uploads if rsync unavailable.

---

## 8. SSL procedure (CyberFolks: shared vs VPS)

### 8.1 Shared hosting (DirectAdmin)

1. Log in to **DirectAdmin**.
2. **SSL/TLS** → **Let's Encrypt**.
3. Select domain/subdomain: `mnsk7-tools.pl`, `staging.mnsk7-tools.pl`.
4. Enable certificate; renewal is automatic.
5. **HSTS:** Enable only after confirming HTTPS works everywhere (links, assets, redirects).
6. **Order:** Configure staging first, then production.

No CLI automation on shared; panel only. See skill `ssl_cyberfolks`.

### 8.2 VPS (root/SSH)

1. Install Certbot (e.g. `apt install certbot python3-certbot-nginx`).
2. Obtain certificate:  
   `certbot --nginx -d staging.mnsk7-tools.pl` (then prod domain).
3. Verify auto-renewal: `systemctl list-timers | grep certbot` or cron.
4. HSTS only after full HTTPS verification.

See skill `ssl_certbot_playbook`.

---

## 9. Document and script paths (report)

| Item | Path |
|------|------|
| This playbook | `docs/SERVER_OPS_CYBERFOLKS.md` |
| Deploy playbook (summary) | `docs/DEPLOY_PLAYBOOK.md` |
| Staging playbook | `docs/STAGING_PLAYBOOK.md` |
| Staging + GitHub (secrets, branches) | `docs/STAGING_AND_GITHUB.md` |
| Staging wp-config fixes | `docs/staging-wpconfig-fix-dupes.md` |
| Deploy (rsync) script | `scripts/deploy-rsync.sh` |
| Staging-refresh script | `scripts/staging-refresh.sh` |
| Backup script (optional) | `scripts/backup-remote.sh` |
| Makefile | `Makefile` |
| MU plugin staging-safety | `mu-plugins/staging-safety.php` |
| Deploy workflow (Actions) | `.github/workflows/deploy-staging.yml` |
| Agent spec | `.agents/agents/07_server_ops_cyberfolks.md` |

---

## 10. Related docs

- [DEPLOY_PLAYBOOK.md](DEPLOY_PLAYBOOK.md) — Pre/post deploy checklist, backups, rollback summary.
- [STAGING_PLAYBOOK.md](STAGING_PLAYBOOK.md) — Staging slot, one-button refresh, SSL.
- [STAGING_AND_GITHUB.md](STAGING_AND_GITHUB.md) — Repo layout, GitHub Secrets, branch flow.
- [QA_REPORT.md](QA_REPORT.md) — Smoke tests and staging safety checks.
- [DEVOPS.md](DEVOPS.md) — Branches, Actions, scripts overview.
