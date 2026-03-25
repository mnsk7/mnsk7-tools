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

### 2026-03-25 — ESCALATE: a11y fix not deploy-verified

- **Context**: UI/UX fixes + пайплайн дисциплина. Локально изменены CSS tokens (контраст primary), и обновлены e2e проверки (`woo-flow`, `a11y-smoke`) для корректного покрытия checkout.
- **Severity**: critical
- **What slipped**:
  - A11y smoke (`axe`) продолжает падать на **remote staging** по `color-contrast` (например `.mnsk7-header__search-submit`), потому что staging ещё **не содержит** наши локальные CSS изменения.
  - Верификация пыталась оценивать “исправление” без деплоя/кэш-баста, то есть без проверки на целевой среде.
- **Why it slipped**:
  - Проверки запускаются против `BASE_URL=https://staging.mnsk7-tools.pl`, но изменения ещё не задеплоены туда; результат теста отражает старую версию.
  - Не было явного шага “deploy → purge cache → rerun a11y” как обязательного для a11y-багфикса.
- **Evidence**:
  - `npx playwright test e2e/woo-flow.spec.js --project=chromium` → **3 passed** (функциональная часть ок).
  - `npx playwright test e2e/a11y-smoke.spec.js --project=chromium` → **FAIL**: `axe` `color-contrast`, `bgColor #0c7ddb` (remote staging).
  - Critic+Scorer PHASE=2 outcome = **ESCALATE**: требование деплоя и повторного прогона a11y на той же среде.
- **Mitigation (now)**:
  - Зафиксировано как ESCALATE до получения post-deploy evidence.
  - Уточнены локальные e2e шаги для checkout (предусловие add_to_cart).
- **Prevention (process)**:
  - **verify**: для a11y-багфикса обязательная последовательность: deploy → cache bust/purge → a11y smoke (PASS) → только после этого ACCEPT.
  - **process**: явно отличать “локальный diff готов” от “staging подтверждён” и не смешивать evidence разных версий.
# Critic postmortems (pipeline learning log)

Этот документ ведётся **Critic+Scorer** после выявления существенных проблем в пайплайне или результате.

## Когда писать постмортем

Пишем запись, если:
- verdict = `REJECT` или `ESCALATE`
- либо обнаружены **critical/major** проблемы (в т.ч. “fake completion”, обход verify, сломанный Woo flow)
- либо пришлось делать существенный refайн процесса (новые rules/skills/MCP), чтобы не повторять ошибку

## Формат записи (шаблон)

### YYYY-MM-DD — <короткий заголовок>

- **Context**: какая задача/изменение
- **Severity**: critical | major | minor
- **What slipped**: что пропустили (конкретно)
- **Why it slipped**: почему это стало возможно (процесс/инструменты/контекст/человеческий фактор)
- **Evidence**: какие артефакты это доказали (Playwright trace/report, Lighthouse, console, diff)
- **Mitigation (now)**: что сделали сразу
- **Prevention (process)**:
  - **rules**: что добавить/уточнить в `.cursor/rules/*`
  - **skills**: что добавить/усилить в `.cursor/skills/*`
  - **mcp/tools**: какой MCP/инструмент подключить/использовать (например Chrome DevTools MCP)
  - **verify**: какие проверки L0/L1/L2 добавить/ужесточить

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
  - добавлено alwaysApply правило автозапуска пайплайна (`.cursor/rules/10-autostart-pipeline.mdc`)
  - добавлен `docs/CRITIC_POSTMORTEMS.md` + обязательство критика писать постмортем при REJECT/ESCALATE
  - прогон `npm run verify:all` показал FAIL по a11y (реальная проблема, не поломка пайплайна)
- **Prevention (process)**:
  - **rules**: держать `.cursor/rules/85-verify-critic-loop.mdc` как обязательный гейт; не принимать изменения процесса без верификации
  - **skills**: фиксировать “как запускать verify” и “как читать PASS/FAIL/SKIP” в `START_HERE`
  - **mcp/tools**: использовать DevTools MCP для evidence (perf/a11y/console) при спорных кейсах
  - **verify**: стандартизировать VERIFY_REPORT (структурированный) перед любым ACCEPT

---

### 2026-03-25 — Large migration without structured VERIFY_REPORT

- **Context**: массовая миграция процесса/структуры (удаление `.agents`, добавление `.cursor/hooks`, MCP, новые skills).
- **Severity**: major
- **What slipped**: изменения такого масштаба были сделаны без структурированного `VERIFY_REPORT` и без доказуемого “green run” verify-уровней.
- **Why it slipped**: не было автоматической генерации `VERIFY_REPORT.json` в `verify:all`, а критик не имел жёсткого правила “без VERIFY_REPORT — не ACCEPT”.
- **Evidence**: Critic+Scorer PHASE=2 требовал VERIFY_REPORT и указал на непроверенный риск слома workflows/CI.
- **Mitigation (now)**:
  - в `.cursor/agents/critic-scorer.md` закреплено: без `VERIFY_REPORT` outcome не ACCEPT
  - добавляем генерацию `VERIFY_REPORT.json` как часть `verify:all`
- **Prevention (process)**:
  - **rules**: требовать `VERIFY_REPORT.json` для любых process/runtime изменений
  - **verify**: `verify:all` должен генерировать PASS/FAIL/SKIP отчёт + пути артефактов

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

