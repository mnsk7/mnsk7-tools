# START HERE — Как запускать пайплайн и doprowadzać do wyniku (mnsk7-tools.pl)

Дата: 2026-03-06

Pipeline to nie tylko „kogo uruchomić i jakie pliki mają powstać” — to **pipeline jakości**. Wynik musi przejść gate’y; „dokument powstał” ≠ „etap zamknięty”. Szczegóły: **docs/QUALITY_GATES.md**.

---

## Kolejność gate’ów (jak doprowadzać do wyniku)

1. **Discovery approved** — REQUIREMENTS z acceptance criteria per szablon.
2. **Requirements frozen**
3. **Architecture frozen** — theme / Woo / mu-plugin; overrides inventory.
4. **UI approved** — UI_SPEC_V2 (jedyna aktualna spec); SCREEN_REVIEW_PACK (docs/SCREEN_REVIEW_PACK.md).
5. **Shell implemented and reviewed** — Screen Review dla Header, Footer approved.
6. **PLP/PDP implemented and reviewed** — Screen Review approved.
7. **Checkout reviewed** — conversion blockers usunięte.
8. **QA passed** — 4 sign-offy: Visual, IA, Conversion, Smoke (docs/QA_REPORT.md).
9. **Release candidate** → dopiero wtedy deploy według DEPLOY_SAFETY.

---

## Перед первым запуском (один раз)

### 1. SSH-ключ (без пароля) — ⚠️ осталась одна команда
Ключ `~/.ssh/id_ed25519_mnsk7` и `~/.ssh/config` — уже созданы.  
Скопировать ключ на сервер (последний раз введёшь пароль):
```bash
ssh-copy-id -p 222 -i ~/.ssh/id_ed25519_mnsk7.pub llojjlcemq@s56.cyber-folks.pl
```
После — проверить:
```bash
ssh mnsk7-staging "echo OK"
```

### 2. Поддомен + БД на сервере (один раз в DirectAdmin) — ⚠️ вручную
- Создать поддомен `staging.mnsk7-tools.pl` (DirectAdmin → Subdomains)
- Создать БД `mnsk7_stg` + пользователь + права (DirectAdmin → MySQL)
- Создать `~/domains/staging.mnsk7-tools.pl/public_html/wp-config.php`:
  ```php
  define('DB_NAME', 'llojjlcemq_mnsk7stg');
  define('DB_USER', 'ПОЛЬЗОВАТЕЛЬ_БД');
  define('DB_PASSWORD', 'ПАРОЛЬ_БД');
  define('DB_HOST', 'localhost');
  define('WP_HOME', 'https://staging.mnsk7-tools.pl');
  define('WP_SITEURL', 'https://staging.mnsk7-tools.pl');
  define('WP_ENVIRONMENT_TYPE', 'staging');
  define('DISALLOW_FILE_EDIT', true);
  ```

### 3. SSL для staging (один раз) — ⚠️ вручную
DirectAdmin → SSL Certificates → Let's Encrypt → staging.mnsk7-tools.pl

### 4. GitHub репо — ⚠️ добавить remote и push
git init и первый коммит уже сделаны (commit `bfea9b3`).  
Осталось: создать репо на GitHub и подключить:
```bash
git remote add origin https://github.com/ТВОЙ_ЛОГИН/mnsk7-tools.pl.git
git branch -M main
git push -u origin main
```

### 5. WP официальные skills (один раз)
```bash
npx skills add https://github.com/WordPress/agent-skills --agent cursor \
  --skill wordpress-router \
  --skill wp-project-triage \
  --skill wp-plugin-development \
  --skill wp-wpcli-and-ops \
  --skill wp-performance \
  --skill wp-phpstan \
  --skill wpds
```

---

## Пайплайн агентов (порядок)

> Все агенты запускаются вручную в Cursor (@ + имя агента).  
> Результат каждого — файлы в `docs/` или `tasks/`. Не автоматически.  
> Файлы-стабы в `docs/` и `tasks/` — **шаблоны**: агент их заполняет. Не удалять до запуска агента.

### Шаг 1 — Дискавери клиента
Агент: `@00_client_discovery`  
Запрос: «Сделай дискавери проекта mnsk7-tools.pl по своему промпту»  
Выход: `docs/DISCOVERY.md`, `docs/REQUIREMENTS.md`

### Шаг 2 — SEO + Контент + GA4
Агент: `@02_growth_seo`  
Запрос: «Прочитай REQUIREMENTS.md и сделай SEO_PLAN, CONTENT_PLAN, TRACKING»  
Выход: `docs/SEO_PLAN.md`, `docs/CONTENT_PLAN.md`, `docs/TRACKING.md`

### Шаг 3 — Архитектура WP
Агент: `@03_wp_architect`  
Запрос: «Прочитай REQUIREMENTS.md и SEO_PLAN.md, сделай ARCHITECTURE.md и BACKLOG.md»  
Выход: `docs/ARCHITECTURE.md`, `docs/BACKLOG.md`

### Шаг 4 — Backlog и спринты
Агент: `@01_product_manager`  
Запрос: «Прочитай REQUIREMENTS.md, SEO_PLAN.md, ARCHITECTURE.md, сделай epics и sprint_01»  
Выход: `tasks/010_epics.md`, `tasks/020_sprint_01.md`, `tasks/030_sprint_02.md`

### Шаг 5 — Разработка (параллельно)
Агенты: `@05_theme_ux_frontend` + `@04_woo_engineer`  
По задачам из `tasks/020_sprint_01.md`. Код → ветка `feature/*` → PR.

### Шаг 6 — QA
Агент: `@08_qa_security`  
Запрос: «Пройди smoke-тест по qa_smoke_woo, обнови docs/QA_REPORT.md»

### Шаг 7 — Деплой на staging
```bash
make staging-full
```
Или по шагам:
```bash
make deploy-files      # rsync mu-plugins + theme
make staging-refresh   # dump prod DB → staging + search-replace + flush
```
После: проверить https://staging.mnsk7-tools.pl

---

## Обновление staging при новых изменениях

```bash
git add .
git commit -m "feat: описание изменений"
git push origin staging        # или feature/...
make deploy-files              # залить файлы на staging
make staging-refresh           # обновить БД (если нужно)
```

---

## Определение готовности задачи

→ `docs/DEFINITION_OF_DONE.md`

---

## Файлы быстрой справки

| Что | Где |
|-----|-----|
| **Quality gates (kto blokuje release, niedopuszczalny wynik)** | **docs/QUALITY_GATES.md** |
| **Screen Review Pack (gate per ekran)** | **docs/SCREEN_REVIEW_PACK.md** |
| Порядок агентов | `.agents/orchestrator.md` |
| Описания агентов | `.agents/agents/*.md` |
| Skills | `.agents/skills/*/SKILL.md` |
| **Jedyna aktualna spec UI** | **docs/UI_SPEC_V2.md** (UI_SPEC.md = superseded) |
| Деплой playbook | `docs/STAGING_PLAYBOOK.md` |
| DoD | `docs/DEFINITION_OF_DONE.md` |
| Аудит команды | `docs/TEAM_AUDIT.md` |
| Статус готовности | `docs/TEAM_READINESS.md` |
