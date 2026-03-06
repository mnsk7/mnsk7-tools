# START HERE — How to run the pipeline (mnsk7-tools.pl)

**Date:** 2026-03-05

---

## One-time setup

### 1. SSH key (key-based auth)

Create key and copy to server (one-time):

```bash
# If not done yet:
ssh-copy-id -p 222 -i ~/.ssh/id_ed25519_mnsk7.pub llojjlcemq@s56.cyber-folks.pl
```

Verify:

```bash
ssh mnsk7-staging "echo OK"
```

Reference: `.agents/skills/ssh_key_auth_playbook` (and 07_server_ops_cyberfolks for server access).

### 2. Staging subdomain and DB (DirectAdmin)

- Create subdomain `staging.mnsk7-tools.pl` (DirectAdmin → Subdomains).
- Create MySQL DB and user for staging (e.g. `mnsk7_stg`).
- Create `wp-config.php` for staging with:
  - `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`
  - `WP_HOME`, `WP_SITEURL` = `https://staging.mnsk7-tools.pl`
  - `WP_ENVIRONMENT_TYPE`, `DISALLOW_FILE_EDIT`, etc. (see `docs/STAGING_PLAYBOOK.md`).

### 3. SSL for staging

- **Shared:** DirectAdmin → SSL → Let’s Encrypt for staging.mnsk7-tools.pl.
- **VPS:** Use `ssl_certbot_playbook` (or certbot/acme.sh).

### 4. GitHub

```bash
git remote add origin https://github.com/YOUR_USER/mnsk7-tools.pl.git
git branch -M main
git push -u origin main
```

### 5. WP official skills (once per environment)

```bash
npx skills add https://github.com/WordPress/agent-skills --agent cursor \
  --skill wordpress-router --skill wp-project-triage --skill wp-plugin-development \
  --skill wp-wpcli-and-ops --skill wp-performance --skill wp-phpstan --skill wpds
```

---

## Rules and policy

- **Code and deploy:** `.cursorrules` — no core/plugin edits; theme and mu-plugins only; staging must use staging-safety (block mail/payments, noindex).
- **Staging safety and deploy:** `docs/STAGING_PLAYBOOK.md`, `docs/DEPLOY_PLAYBOOK.md` (and rollback if documented there).
- **Definition of Done:** `docs/DEFINITION_OF_DONE.md`.
- **Staging vs prod:** `docs/USER_JOURNEY_STAGING_VS_PROD.md` — różnice UX i mapowanie plików. **Zanim zmienisz motyw/opcje na staging:** upewnij się, że staging ma **osobną bazę** (DB_NAME ≠ prod). Bez tego zmiana motywu na staging zmienia prod.

---

## Agent pipeline (order)

Run agents **manually** in Cursor (e.g. @agent_name). Each agent writes to `docs/` or `tasks/`. Stub files in `docs/` and `tasks/` are templates; agents fill them. Do not delete stubs before the agent run.

| Step | Agent | Input | Output |
|------|--------|--------|--------|
| 0 | _ceo_team_audit | — | TEAM_AUDIT, TEAM_FIX_PLAN, TEAM_READINESS, START_HERE (on demand) |
| 1 | 00_as_is_audit | Site/docs | AS_IS_AUDIT.md, AS_IS_BACKLOG.md, AS_IS_RISKS.md |
| 2 | 00_client_discovery | CLIENT_INTERVIEW_SUMMARY.md | DISCOVERY_GAP_ANALYSIS.md, DISCOVERY.md, REQUIREMENTS.md |
| 3 | 02_growth_seo | REQUIREMENTS.md | SEO_PLAN.md, CONTENT_PLAN.md, TRACKING.md |
| 4 | 03_wp_architect | REQUIREMENTS.md, SEO_PLAN.md | ARCHITECTURE.md, BACKLOG.md |
| 5 | 01_product_manager | REQUIREMENTS, SEO_PLAN, ARCHITECTURE | tasks/010_epics.md, 020_sprint_01.md, 030_sprint_02.md |
| 5b | 09_ui_designer | REQUIREMENTS, CONTACT_DELIVERY_LOYALTY, client notes | UI_SPEC (or UI_SPEC_V2 — see TEAM_FIX_PLAN for canonical choice) |
| 6 | 05_theme_ux_frontend + 04_woo_engineer | ARCHITECTURE, UI_SPEC, sprint tasks | Code (theme/mu-plugin), PRs |
| 7 | 08_qa_security | — | QA_REPORT.md, smoke/regression checklist, items in 000_inbox.md |
| 8 | 06_devops_github + 07_server_ops_cyberfolks | — | Branches, PR template, Actions; deploy playbook, staging refresh |

**Important:** Run 09_ui_designer (5b) before or in parallel with step 5 so UI_SPEC exists before 05/04 implement (see `docs/TEAM_FIX_PLAN.md` F3).

---

## Deploy to staging (after code changes)

```bash
make deploy-files      # rsync theme + mu-plugins
make staging-refresh   # dump prod DB → import staging → search-replace → flush
```

Then check https://staging.mnsk7-tools.pl.

Full flow: `make staging-full` (if defined in Makefile).

---

## Quick reference

| What | Where |
|------|--------|
| Agent order | `.agents/orchestrator.md` |
| Agent definitions | `.agents/agents/*.md` |
| Skills | `.agents/skills/*/SKILL.md` |
| Deploy / staging | `docs/STAGING_PLAYBOOK.md`, `docs/DEPLOY_PLAYBOOK.md` |
| Definition of Done | `docs/DEFINITION_OF_DONE.md` |
| Team audit | `docs/TEAM_AUDIT.md` |
| Fix plan | `docs/TEAM_FIX_PLAN.md` |
| Readiness | `docs/TEAM_READINESS.md` |
