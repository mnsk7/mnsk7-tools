# Critic postmortems (pipeline learning log)

Этот документ ведётся после outcome `REJECT`/`ESCALATE` или при **critical/major** проблемах, чтобы не повторять ошибки в пайплайне и верификации.

## Формат записи

### YYYY-MM-DD — <короткий заголовок>

- **Context**: какая задача/изменение
- **Severity**: critical | major | minor
- **What slipped**: что пропустили (конкретно)
- **Why it slipped**: почему это стало возможно (процесс/инструменты/контекст)
- **Evidence**: какие артефакты это доказали (команды, логи, trace/report)
- **Mitigation (now)**: что сделали сразу
- **Prevention (process)**: что изменить в rules/verify/процессе

---

### 2026-03-26 — REJECT: cookie bar может перекрыть sticky CTA на iOS Safari (stale height)

- **Context**: правки темы (cookie bar + sticky CTA корзины; mobile iPhone XR/Safari), деплой через push в `main` → staging.
- **Severity**: major
- **What slipped**:
  - Высота cookie bar учитывалась только на show/hide и `window.resize`, что на iOS Safari может не отражать изменения viewport/переносов → переменная `--mnsk7-cookie-bar-h` могла стать stale, и sticky CTA в корзине/чекауте потенциально перекрывался.
- **Why it slipped**:
  - Полагались на `resize`, но на mobile Safari изменения UI (адресная строка/viewport) и перепаковка текста могут происходить без классического resize события.
- **Evidence**:
  - Critic PHASE=2 указал риск stale `--mnsk7-cookie-bar-h` и возможное перекрытие primary CTA на mobile при первом визите без consent.
- **Mitigation (now)**:
  - Добавили `ResizeObserver` и `visualViewport` listeners, чтобы поддерживать `--mnsk7-cookie-bar-h` актуальной.
  - Sticky блок `wc-proceed-to-checkout` смещён на `bottom: var(--mnsk7-cookie-bar-h, 0px)`.
- **Prevention (process)**:
  - Для любых fixed/sticky overlay на mobile: требовать L2/визуальный чек на iOS Safari (или минимальный evidence: скрин/видео) + верификацию “CTA не перекрыт” как blocking rule.

### 2026-03-25 — REJECT/ESCALATE: a11y (color-contrast) на remote staging без post-deploy evidence

- **Context**: правки UI/UX (CSS tokens/CTA) и e2e; проверки запускались против remote staging (`BASE_URL=https://staging.mnsk7-tools.pl`).
- **Severity**: critical
- **What slipped**:
  - A11y smoke (`axe`) продолжает падать на remote staging по `color-contrast` (например `.mnsk7-header__search-submit`, `.woocommerce-message > .wc-forward.button`) из-за несоответствия версии (деплой/кэш) и/или реального дефекта контраста.
  - Верификация пыталась оценивать “исправление” без post-deploy подтверждения версии (deploy → purge/bust cache → rerun на той же среде).
  - `verify:all` мог быть “зелёным”, когда a11y был SKIP по умолчанию.
- **Why it slipped**:
  - Remote staging отдавал CSS со старым `bgColor #0c7ddb` (деплой/кэш), поэтому локальные изменения не подтверждались на целевой среде.
  - Не было жёсткого контракта “a11y для UI/CTA не может быть SKIP”.
- **Evidence**:
  - `npm run verify:a11y` → FAIL: `axe` `color-contrast`, `contrastRatio 4.22`, `expected 4.5:1`, `bgColor #0c7ddb`.
  - `npm run verify:all` → OK, но `a11y: SKIP`.
- **Mitigation (now)**:
  - Не принимать PHASE=2 как ACCEPT без post-deploy evidence: deploy → purge/bust cache → `VERIFY_A11Y=1 npm run verify:all` или `npm run verify:a11y` PASS на том же `BASE_URL`.
- **Prevention (process)**:
  - **verify**: remote a11y должен быть deploy-aware: в `scripts/verify/preflight.sh`/`scripts/verify/verify-report.mjs` фиксировать маркер версии (URL/хэш/headers) в `artifacts/verify/verify-report.json` и фейлить как `not-deployed`, если версия не подтверждена.
  - **verify**: если затронуты UI/CTA (theme/CSS/header/footer/buttons) — a11y обязателен, SKIP = FAIL без явного allow-флага.
  - **process**: явно отличать “локальный diff готов” от “staging подтверждён” и не смешивать evidence разных версий.

---
### 2026-03-25 — Pipeline discipline slips (no auto-run)

- **Context**: изменения процесса/структуры (миграция legacy слоя, MCP конфиг, требования к постмортемам).
- **Severity**: major
- **What slipped**: несколько раз подряд изменения вносились без немедленного запуска verifier/critic и без фиксации VERIFY_REPORT; это создавало ложную уверенность и увеличивало шанс дрейфа.
- **Why it slipped**: процесс был описан как правило, но не был закреплён “операционным” гейтом (напоминанием/обязательным артефактом в критике), плюс не было единого `verify:all` контракта/отчёта.
- **Evidence**:
  - повторяющиеся замечания Owner “почему не запустил verifier/critic”
  - критик требовал VERIFY_REPORT и не мог дать ACCEPT
- **Mitigation (now)**:
  - закреплён операционный гейт через `.cursor/hooks/after-file-edit-enforce-verify.js` (напоминание/требование verify-дисциплины)
  - закреплено в `.cursor/agents/critic-scorer.md`: без `artifacts/verify/verify-report.json` outcome не ACCEPT
- **Prevention (process)**:
  - **process**: без свежего `artifacts/verify/verify-report.json` (создаётся `npm run verify:all`) не запрашивать PHASE=2/ACCEPT.
  - **hooks**: `.cursor/hooks/after-file-edit-enforce-verify.js` должен явно требовать “run verify + приложи артефакты” при изменениях процесса/verify tooling.
  - **mcp/tools**: использовать DevTools MCP для evidence (perf/a11y/console) при спорных кейсах.
  - **verify**: стандартизировать `VERIFY_REPORT` (структурированный: passed/failed/skipped counts + ссылки на артефакты) перед любым ACCEPT.

---

### 2026-03-25 — REJECT: verify blockers + слабый evidence

- **Context**: повторный гейт verifier → critic (PHASE=2) после правок в verify tooling (L0 разбивка, `blocking.failed_rules`).
- **Severity**: critical
- **What slipped**:
  - `verify:all` был красным по **blocking причинам**: `l0:php-lint` (PHP отсутствует в окружении), `a11y` (color-contrast).
  - `VERIFY_REPORT` хоть и структурирован, но оставался **слабым как evidence**: `base_url=null`, нет сохранённого “сырого” лога, общий шаг `l0 (static/smoke)` мог падать без объяснения причины.
- **Why it slipped**:
  - процесс предполагал наличие PHP локально/на раннере, но это не закреплено как обеспечиваемая зависимость (а значит пайплайн легко “ломается” на окружении).
  - отчёт собирался только из summary-строк без привязки к конкретным артефактам/логам, поэтому критик не мог делать уверенный аудит причин.
- **Evidence**:
  - `artifacts/verify/verify-report.json` содержит `blocking.failed_rules` (не пусто) → outcome=REJECT.
  - вывод `verify:all` указывает `php not found in PATH` и нарушения `axe` `color-contrast` (Woo UI).
- **Mitigation (now)**:
  - сохраняем `verify:all` лог в `artifacts/verify/verify-all.log` и прокидываем `BASE_URL` в отчёт.
  - устраняем “необъяснимый” общий шаг L0 (либо делаем его производным от сабшагов).
  - правим контраст в теме (CSS/tokens) и повторяем гейт.
- **Prevention (process)**:
  - **rules**: уточнить в `.cursor/rules/60-verify-levels.mdc`, что L0 требует PHP и где это должно обеспечиваться (CI / dev machine), либо явно разрешить `REQUIRE_PHP=0` только локально.
  - **verify**: всегда сохранять raw-log + ссылку на него в `VERIFY_REPORT`; при FAIL — обязательно показывать “что упало” (URL/selector/page) на уровне отчёта.
  - **mcp/tools**: при a11y/perf спорных кейсах использовать DevTools MCP для воспроизводимого evidence (computed styles, контраст, console).

---

### 2026-03-25 — REJECT: verify PASS при SKIP (ложноположительный гейт)

- **Context**: текущий рабочий diff включает изменения verify tooling (`scripts/verify/*`, `package.json` verify scripts), e2e и docs; запрошен гейт `verifier → critic (PHASE=2)` на текущем diff.
- **Severity**: critical
- **What slipped**:
  - verify мог завершаться общим PASS при том, что критичные шаги были **SKIP** (L0 php-lint/linkcheck/lighthouse; потенциально L1 Woo flow).
  - отсутствовал “жёсткий контракт” для L1: **skipped tests = fail**, иначе можно получить зелёный статус без реального прогона Woo сценариев.
- **Why it slipped**:
  - локальный `verify:all` по умолчанию допускает SKIP зависимых шагов (нет PHP в PATH, отключены linkcheck/lighthouse/a11y/L2), но итоговый статус интерпретируется как “PASS”.
  - отсутствует явная проверка в агрегаторе: если L1 вернул “skipped” — трактовать как failure без явного allow-flag.
- **Evidence**:
  - Verifier outcome = `REJECT`: нет свежих доказуемых прогонов `verify:l0` и `verify:l1` на текущем diff.
  - `artifacts/verify/verify-all.log` и `artifacts/verify/verify-report.json` указывали на SKIP в L0 и подозрительный статус L1 (PASS при наличии skipped).
  - Critic+Scorer PHASE=2 outcome = `REJECT`: блокер — “PASS при SKIP” + отсутствие строгого verify loop.
- **Mitigation (now)**:
  - ужесточить verify tooling: сделать skipped на критичных шагах фатальным (или требовать явный флаг `ALLOW_SKIP=1`/`ALLOW_SKIPS=1`).
  - перепройти `REQUIRE_PHP=1 npm run verify:l0` и `npm run verify:l1` на актуальном состоянии и сохранить свежие логи/репорты.
- **Prevention (process)**:
  - **rules**: закрепить “skipped на L1 = fail” как blocking rule для любых UI/Woo изменений.
  - **verify**: в `VERIFY_REPORT` всегда сохранять counts (passed/failed/flaky/skipped) + явный summary; отсутствие counts трактовать как FAIL.
  - **verify**: добавить self-test для verify tooling и запускать его при изменениях `scripts/verify/*`.

