# Deploy Playbook — mnsk7-tools.pl (Cyber_Folks)

*(Wyjście agentów 06_devops_github, 07_server_ops_cyberfolks; aktualizacja 2026-03-06)*

Procedura wdrożenia, odświeżenia staging, odwołania i backupy. Źródła: cyberfolks_deploy_playbook, staging_bootstrap, ssl_cyberfolks.

**Gałęzie:** push do `main` → GitHub Actions deploy na staging. PR z `feature/*` do `main` — szablon `.github/PULL_REQUEST_TEMPLATE.md`. Theme: **storefront** (parent) + **mnsk7-storefront** (child) w repozytorium.

**Pełny playbook ops (deploy, rollback, wp-config, backupy, SSL):** [SERVER_OPS_CYBERFOLKS.md](SERVER_OPS_CYBERFOLKS.md).  
**Bezpieczeństwo (stage vs prod, ścieżki, dry-run, 4 testy):** [DEPLOY_SAFETY.md](DEPLOY_SAFETY.md).

---

## 1. Co wdrażamy i skąd

| Źródło | Na serwer (staging) |
|--------|----------------------|
| Git: `mu-plugins/` | `~/domains/.../staging/wp-content/mu-plugins/` |
| Git: `wp-content/themes/mnsk7-storefront` (tylko child; parent Storefront na serwerze osobno — deploy nie nadpisuje `themes/` z `--delete`, żeby nie usuwać storefront) | `~/.../staging/wp-content/themes/mnsk7-storefront/` |
| **Nie w Git:** plugins, uploads, wp-config, .env | Na staging już są (kopie z prod lub ręcznie) |

Trigger: **push do gałęzi `main`** → GitHub Actions robi rsync mu-plugins + themes. Lokalnie: `make deploy-files`.

---

## 2. Gałęzie i PR (gitflow_prs)

- **main** — główna; push do main = deploy na staging.
- **staging** — opcjonalna (można łączyć z main).
- **feature/*** — prace nad funkcją; merge przez PR do main.

PR: użyj szablonu `.github/PULL_REQUEST_TEMPLATE.md` (opis, jak testować, ryzyko, checklist). Przed merge: PHP Lint (Actions) i ręczny smoke według [QA_REPORT.md](QA_REPORT.md).

---

## 3. Przed wdrożeniem (checklist)

- [ ] **Backup plików** (jeśli zmieniasz theme/mu-plugins na prod):  
  `tar -czf backup-theme-$(date +%Y%m%d).tar.gz -C wp-content themes/mnsk7-storefront`
- [ ] **Backup BД** (przed staging-refresh lub przed zmianami na prod):  
  Na serwerze: `wp db export /tmp/backup-$(date +%Y%m%d).sql` (w katalogu staging lub prod).
- [ ] **wp-config na staging:** DB_NAME, DB_USER, DB_PASSWORD, WP_HOME, WP_SITEURL — dla staging; nie nadpisuj wp-config z Git.
- [ ] **MU plugin staging-safety** — musi być w repozytorium i wdrożony (blokada maili i płatności na stagingu).
- [ ] **Sekrety GitHub** (dla Actions): STAGING_SSH_KEY, STAGING_SSH_HOST, STAGING_SSH_USER, STAGING_SSH_PORT, STAGING_REMOTE_PATH — ustawione w Settings → Secrets.

---

## 4. Wdrożenie plików

**Przez GitHub (zalecane):**  
Push do `main` → workflow „Deploy to Staging” rsync’uje mu-plugins i themes na staging.

**Lokalnie (bez pusha):**  
```bash
make deploy-files   # mu-plugins + themes z .env (cyberfolks_ssh_*)
# Opcjonalnie: plugins (jeśli są lokalnie) — deploy-rsync.sh kopiuje też plugins, gdy katalog istnieje
```

Ścieżka na serwerze: `~/$STAGING_REMOTE_PATH` (np. `domains/mnsk7-tools.pl/public_html/staging`). Wymaga: `.env` z `cyberfolks_ssh_host`, `cyberfolks_ssh_port`, `cyberfolks_ssh_user`; opcjonalnie `STAGING_REMOTE_PATH`.

---

## 5. Odświeżenie BД staging z prod (staging-refresh)

Skopiowanie bazy produkcyjnej na staging, zamiana URL-i, wyłączenie indeksacji.

```bash
make staging-refresh
```

Lub ręcznie na serwerze (SSH):

```bash
cd ~/domains/mnsk7-tools.pl/public_html && wp db export /tmp/prod_mnsk7.sql --add-drop-table
cd ~/domains/mnsk7-tools.pl/public_html/staging && wp db import /tmp/prod_mnsk7.sql
wp search-replace 'https://mnsk7-tools.pl' 'https://staging.mnsk7-tools.pl' --all-tables --precise
wp option update blog_public 0
wp rewrite flush --hard
wp cache flush
rm -f /tmp/prod_mnsk7.sql
```

Wymaga: wp-cli w PATH na serwerze, poprawne ścieżki w `.env` (lub w skrypcie `scripts/staging-refresh.sh`).

---

## 6. Po wdrożeniu

- [ ] Na serwerze (staging): `wp cache flush` (jeśli cache włączony), `wp rewrite flush --hard`.
- [ ] Smoke: S1–S9 według [QA_REPORT.md](QA_REPORT.md) (dodanie do koszyka, checkout, strona produktu).
- [ ] Weryfikacja UI (QA_REPORT §5): header bez duplikatów, PDP, footer, tap targets 44px, mobile.
- [ ] Na stagingu maile nie wychodzą do klientów (staging-safety); płatności wyłączone.

---

## 7. Odwołanie (rollback)

**Tylko pliki (theme / mu-plugins):**  
- Wrócić do poprzedniego commita w Git i zrobić push (nowy deploy) albo lokalnie `make deploy-files` po `git checkout` poprzedniego stanu.  
- Alternatywa: na serwerze przywrócić kopię z backupu: `tar -xzf ~/backups/staging-theme-....tar.gz` w katalogu staging (albo `make backup-files` przed deployem, potem przywrócić).  
- Jeśli używano `DEPLOY_BACKUP_THEME=1 make deploy-files`, na serwerze: `cd .../themes && rm -rf mnsk7-storefront && mv mnsk7-storefront_prev mnsk7-storefront` (lub storefront w zależności od backupu), potem `wp cache flush` i `wp rewrite flush --hard`.

**Baza danych:**  
Przy krytycznej zmianie BД na staging: przywrócić z backupu, np.  
`wp db import /tmp/backup-YYYYMMDD.sql` (w katalogu staging).

Szczegóły: [SERVER_OPS_CYBERFOLKS.md](SERVER_OPS_CYBERFOLKS.md) §2.

---

## 8. Backupy (przed/po)

| Co | Kiedy | Gdzie |
|----|--------|-------|
| BД prod | Przed staging-refresh lub przed dużą zmianą na prod | `wp db export` → plik na serwerze lub pobrany lokalnie |
| BД staging | Przed eksperymentami na stagingu | Jak wyżej, w katalogu staging |
| Pliki theme/mu-plugins | Przed dużą zmianą (jeśli nie ufasz tylko Git) | `tar` na serwerze lub po rsync lokalna kopia |

Na shared hosting Cyber_Folks: sprawdzić, czy panel oferuje automatyczne backupy BД i plików; jeśli nie — rozważyć UpdraftPlus lub inny plugin (AS_IS_BACKLOG P0-04).

---

## 9. wp-config, salts, dostępy

- **Staging:** osobna BД (np. `llojjlcemq_stg`), WP_HOME/WP_SITEURL = `https://staging.mnsk7-tools.pl`. W wp-config można ustawić `WP_ENVIRONMENT_TYPE => 'staging'` (dla staging-safety i ewentualnych warunków w kodzie).
- **Salty:** nie commitujemy wp-config; na stagingu wp-config jest lokalny na serwerze. Po sklonowaniu z prod zmienić DB_* i URL-e.
- **Dostępy:** SSH (klucz w GitHub Secrets), DirectAdmin — osobne hasła; nie trzymać w Git.

---

## 10. SSL (Cyber_Folks shared)

- **DirectAdmin** → SSL/TLS → Let's Encrypt: włączyć dla `mnsk7-tools.pl` i `staging.mnsk7-tools.pl`.
- Odnówienie zwykle automatyczne. Najpierw sprawdzić staging, potem prod.
- HSTS włączyć dopiero po upewnieniu się, że HTTPS działa wszędzie.
- Na VPS (jeśli kiedyś): certbot — patrz skill `ssl_certbot_playbook`.

---

## 11. Powiązane dokumenty

- [STAGING_AND_GITHUB.md](STAGING_AND_GITHUB.md) — repozytorium, sekrety, co jest w Git, deploy przy pushu.
- [QA_REPORT.md](QA_REPORT.md) — smoke i checklisty bezpieczeństwa/wydajności.
- Skrypty: `scripts/deploy-rsync.sh`, `scripts/staging-refresh.sh`, `scripts/staging-fix-db.sh`, `Makefile`.
- **06_devops_github / 07_server_ops (2026-03-06):** potwierdzono workflow `deploy-staging.yml` (push main → rsync), PR template, gałąź main; zaktualizowano nazwy tematów (storefront + mnsk7-storefront) w playbooku i STAGING_AND_GITHUB.
