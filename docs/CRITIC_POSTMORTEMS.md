# Critic postmortems (pipeline learning log)

Этот документ ведётся после outcome `REJECT`/`ESCALATE` или при **critical/major** проблемах, чтобы не повторять ошибки в пайплайне и верификации.

### 2026-03-27 — REJECT (promo banner cycle): postdeploy L1/a11y/L2 failed after main push

- **Context**: коммит `f982f87` (responsive fix промо-баннера) запушен в `main`; deploy на staging прошёл (`23650862750`), после чего выполнен postdeploy `verify:all` с форсом `VERIFY_L1=1 VERIFY_L2=1 VERIFY_A11Y=1`.
- **Severity**: critical
- **What slipped**:
  - `critic-scorer PHASE=2` вернул `REJECT`, `score_0_100=0`, `process_accept=false`, `product_accept=false`.
  - Blocking rules сработали: `l1 (woo flow)=FAIL`, `a11y=FAIL`, `l2 (visual/regression)=FAIL`.
  - L1: `Cart appears empty after mobile PDP add-to-cart` на `/koszyk/` (блокирующий конверсионный флоу).
  - L2: 4 visual snapshot failures в `e2e/header-layout.spec.js` (mobile closed/open, desktop closed, desktop sklep open).
  - Product Verifier блок не выполнен (нет `owner_bug_ledger`, `agent_found_bugs`, `agent_found_bugs_filtered`, `safari_mobile_status`).
- **Why it slipped**:
  - Исправление промо-бара затронуло header-состояния, но визуальный baseline и поведенческие Woo/a11y сценарии не прошли постдеплойный гейт.
  - Пайплайн дошёл до PHASE=2 корректно, но acceptance попытка была раньше закрытия всех обязательных postdeploy доказательств.
- **Evidence**:
  - Deploy run: `https://github.com/mnsk7/mnsk7-tools/actions/runs/23650862750` (success).
  - `artifacts/verify/verify-report.json`: `exit_code=1`, `blocking.failed_rules=["l1 (woo flow)","a11y","l2 (visual/regression)"]`.
  - `artifacts/verify/verify-all.log`: L1 empty cart error, a11y serious/critical failures, L2 header snapshot regressions.
  - `critic-scorer PHASE=2`: `outcome=REJECT`.
- **Mitigation (now)**:
  - Итог зафиксирован как `REJECT`, добавлен постмортем, ложный `ACCEPT` не выставлялся.
- **Prevention (process)**:
  - Для header/promo правок всегда считать L1+a11y+L2 обязательными postdeploy блокерами до PASS.
  - Не переходить к финальному PHASE=2 без Product Verifier JSON-блока (включая `safari_mobile_status`).
  - Следующий цикл начинать с фикса корневой причины empty-cart на mobile PDP и затем повторного полного verify.

### 2026-03-27 — REJECT (postdeploy gate): нет Product Verifier + критичные SKIP в technical verify

- **Context**: выполнен цикл `predeploy verifier -> deploy -> postdeploy technical verify -> critic-scorer PHASE=2` для текущего `main` без локальных code diff.
- **Severity**: major
- **What slipped**:
  - `critic-scorer PHASE=2` вернул `REJECT`: отсутствует post-deploy Product Verifier (`owner_bug_ledger`, `agent_found_bugs`, `agent_found_bugs_filtered`, `safari_mobile_status`).
  - В postdeploy technical verify много SKIP: `l1 (woo flow)`, `l2 (visual/regression)`, `a11y`, `l0:link-check`, `l0:lighthouse-smoke`.
  - Практический verifier в этом прогоне был predeploy-контекста и не закрывал post-deploy product acceptance.
- **Why it slipped**:
  - Процесс выполнил deploy и базовый L0, но acceptance попытались завершить без обязательного product-блока и без форсированного L1/L2 по e-commerce рискам.
  - `verify:all` с policy по умолчанию допускает SKIP, что недостаточно для финального PHASE=2 gate.
- **Evidence**:
  - GitHub Actions deploy run: `23650586684` (success).
  - `artifacts/verify/verify-report.json`: `l0 PASS`, `l1 SKIP`, `l2 SKIP`, `a11y SKIP`, `blocking.failed_rules=[]`.
  - `critic-scorer PHASE=2`: `outcome=REJECT`, `process_accept=false`, `product_accept=false`, `final_accept=false`.
- **Mitigation (now)**:
  - Результат зафиксирован как `REJECT` (без ложного ACCEPT).
  - Добавлен postmortem с конкретными required fixes на следующий цикл.
- **Prevention (process)**:
  - Перед PHASE=2 всегда выполнять post-deploy Product Verifier с обязательными полями (включая `safari_mobile_status`).
  - Для Woo-зоны форсировать `VERIFY_L1=1` (и при визуальном риске `VERIFY_L2=1`, `VERIFY_A11Y=1`) вместо опоры на default SKIP.
  - Считать `verify:all` с критичными SKIP неполным evidence для acceptance gate.

### 2026-03-27 — REJECT (mobile-core hostile cycle): L2 visual gate failed with untrusted baseline

- **Context**: hostile-цикл по mobile core (`header + search + mega-menu + sticky CTA + cart mobile`) после фиксов коммита `333e436`.
- **Severity**: major
- **What slipped**:
  - Post-deploy verify: `L0 PASS_WITH_SKIPS`, `L1 PASS`, но `L2 REJECT` (4 падения snapshot по header).
  - Baseline для visual в этом цикле изначально считался недоверенным; обновление snapshot без product signoff запрещено.
  - В owner ledger остаются `partially_fixed/not_fixed` (в т.ч. sticky PDP CTA honesty).
- **Why it slipped**:
  - Технические фиксы геометрии/брейкпоинтов изменили вид хедера, но продуктовый signoff целевого визуального состояния не был зафиксирован до visual-gate.
  - Цикл discovery/fix прошёл быстрее, чем контур ручной продуктовой валидации для остаточных owner-багов.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/verify_summary.json` (`l2.result = REJECT`).
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/verifier_postdeploy_practical.json` (`outcome = REJECT`).
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/verifier_postdeploy_technical.json` (`outcome = REJECT`).
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/critic_phase2.json` (`outcome = REJECT`).
- **Mitigation (now)**:
  - Зафиксирован hostile re-test с по-сценарным статусом и списком residual risks.
  - Не выполнялся snapshot update, чтобы не легализовать дефект без product signoff.
- **Prevention (process)**:
  - До следующего L2-гейта сначала завершать product-verifier signoff по целевому UI-state.
  - Для owner-критичных багов (`sticky CTA honesty`, `mega-menu reopen/back`) держать отдельные hostile replay checks обязательными до `PRODUCT_ACCEPT=true`.

### 2026-03-27 — REJECT (post-deploy gate): deploy success, but technical evidence insufficient for ACCEPT

- **Context**: после коммитов и push в `main` деплой на staging завершился `success`; выполнен post-deploy technical verifier и `critic-scorer PHASE=2`.
- **Severity**: major
- **What slipped**:
  - `critic PHASE=2` остался в `REJECT` из-за незакрытого L2 (visual/regression) и недостатка финальных артефактов.
  - Технический verifier post-deploy = `REJECT`; отдельный post-deploy practical verifier с `ACCEPT` в текущем пакете evidence отсутствует.
  - Успешный deploy-workflow не закрыл quality gate сам по себе.
- **Why it slipped**:
  - Pipeline-гейт требует полный набор post-deploy evidence (L0/L1/L2 + verifier practical/technical), а не только факт деплоя.
  - L2 прогон нестабилен/не доведён до терминального подтверждения.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__postdeploy-gate-predeploy-then-technical/verifier_postdeploy_technical.json` -> `outcome: REJECT`.
  - `tasks/pipeline-json/2026-03-27__postdeploy-gate-predeploy-then-technical/verify_summary.json` -> L2 `REJECT`.
  - `critic-scorer PHASE=2` (post-deploy review) -> `outcome: REJECT`, score ниже порога.
- **Mitigation (now)**:
  - Результат официально оставлен в `REJECT`, без ложного ACCEPT.
  - Зафиксирован постмортем и конкретные required fixes для следующей итерации.
- **Prevention (process)**:
  - После deploy не переходить к `ACCEPT`, пока L2 не завершён успешно и оба verifier-режима (practical/technical) не закрыты.
  - Привязывать post-deploy evidence к конкретному SHA/run-id, чтобы исключать спор о “какая версия проверялась”.

### 2026-03-27 — REJECT (predeploy verifier gate): не готово к deploy

- **Context**: после обновления owner-pipeline (predeploy verifier без локальных e2e/verify, затем deploy и post-deploy technical verify) выполнен обязательный predeploy gate.
- **Severity**: major
- **What slipped**:
  - Predeploy verifier (practical+technical) вернул `REJECT`, итог: `overall_readiness_for_deploy=NOT_READY`.
  - Нет deployable состояния для валидируемого diff (много незакоммиченных/untracked изменений), следовательно шаг deploy на staging не может быть корректно продолжен.
  - Post-deploy evidence (L0/L1/L2) для текущего diff отсутствует, значит переход к `critic-scorer (PHASE=2)` преждевременен.
- **Why it slipped**:
  - Попытка пройти gate-последовательность до фиксации release-кандидата в source-of-truth (`main`).
  - Процесс и кодовые изменения готовы, но operational readiness (commit/push/deployable SHA) не закрыт.
- **Evidence**:
  - Predeploy verifier report: `practical.outcome=REJECT`, `technical.outcome=REJECT`, `overall_readiness_for_deploy=NOT_READY`.
  - Текущее состояние репозитория: множество modified/untracked файлов, без нового задеплоенного SHA.
- **Mitigation (now)**:
  - Деплой остановлен на predeploy gate (BLOCK_DEPLOY логика соблюдена).
  - Зафиксирован postmortem вместо ложного продолжения пайплайна.
- **Prevention (process)**:
  - Не переходить к deploy/post-deploy verify, пока predeploy verifier не даст `READY`.
  - Для runtime/UI задач сначала формировать deployable candidate (commit + push), затем выполнять post-deploy technical verify и только после этого запускать `critic-scorer (PHASE=2)`.

### 2026-03-27 — REJECT (predeploy gate): diff есть локально, но не deployable в source-of-truth

- **Context**: после новых правок в runtime-зоне хедера запрошен обязательный порядок: predeploy verifier (practical+technical) -> deploy -> post-deploy technical verify -> critic PHASE=2.
- **Severity**: major
- **What slipped**:
  - На момент predeploy-gate изменения остаются только в локальном worktree, без deployable SHA в `origin/main`.
  - Из-за этого deploy на staging через каноничный путь (GitHub Actions от `main`) не может проверить именно текущий diff.
- **Why it slipped**:
  - В процессе смешались две модели: локальная доработка и требование source-of-truth deploy только из `main`.
  - Попытка идти дальше по гейтам без фиксации deployable состояния приводит к ложной готовности.
- **Evidence**:
  - `git status --short --branch`: множество модификаций, ветка `main...origin/main` без ahead-commits для текущего diff.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verifier_practical_predeploy.json` -> `outcome: REJECT`, `deploy_readiness: BLOCK_DEPLOY`.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verifier_technical_predeploy.json` -> `outcome: REJECT`, `deploy_readiness: BLOCK_DEPLOY`.
- **Mitigation (now)**:
  - Формально закрыт predeploy gate с решением `BLOCK_DEPLOY` вместо пропуска шага.
  - Добавлены predeploy verifier-артефакты и обязательный postmortem.
- **Prevention (process)**:
  - Для всех runtime правок, которые должны пройти post-deploy verify, сначала обеспечивать deployable state (`commit + push`), только потом запускать staging deploy и постдеплойные гейты.
  - Не запускать `critic PHASE=2` до появления post-deploy evidence (L0/L1/L2 по зоне) именно для задеплоенного SHA.

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

### 2026-03-27 — REJECT: repeated gate run still blocked (L2 + Safari/product)

- **Context**: повторный обязательный гейт без новых runtime-правок после последнего цикла: predeploy verifier -> postdeploy `L0/L1/L2` -> `critic-scorer PHASE=2`.
- **Severity**: critical
- **What slipped**:
  - predeploy practical verifier дал `REJECT` (`overall_readiness_for_deploy=NOT_READY`) из-за незакрытых продуктовых блокеров.
  - postdeploy `L2` снова `FAIL`: 4 snapshot failure в `e2e/header-layout.spec.js` (mobile closed/open, desktop closed, desktop sklep open).
  - `PRODUCT_ACCEPT` остаётся заблокирован (`safari_mobile_status.json`: `product_accept_allowed=false`, `cta_honesty=FAIL`; `OWNER-003` не закрыт).
  - `L1` снова не полностью чистый (`3 passed, 1 skipped`).
- **Why it slipped**:
  - Корневые продуктовые блокеры не были устранены до повторного прогона.
  - Визуальный header-state продолжает расходиться с baseline без product signoff на обновление snapshot.
- **Evidence**:
  - predeploy verifier: practical=`REJECT`, technical=`ACCEPT`, readiness=`NOT_READY`.
  - `npm run verify:l0` -> PASS_WITH_SKIPS.
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l1` -> `3 passed, 1 skipped`.
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l2` -> `4 failed, 106 passed`.
  - critic phase2: `outcome=REJECT`, `process_accept=false`, `product_accept=false`, `final_accept=false`.
- **Mitigation (now)**:
  - Зафиксирован повторный `REJECT` как отдельный postmortem (без маскировки “почти зелёного” статуса).
- **Prevention (process)**:
  - Не запускать “формальный повтор” без предварительного закрытия конкретных блокеров (`OWNER-003`, Safari `cta_honesty`, L2 header snapshots/signoff).
  - Для следующего цикла требовать `L1` без skip по критичному PDP-mobile сценарию или явное scope-обоснование skip.
  - Обновление snapshot — только после явного owner/product signoff на целевой visual state.

---

### 2026-03-27 — REJECT: mobile header/PDP pass blocked by L2 + product gate

- **Context**: внедрён пакет правок mobile header + mobile PDP sticky CTA + новый mobile PDP add-to-cart e2e. Пройден цепочный гейт: predeploy verifier -> deploy -> postdeploy L0/L1/L2 -> critic PHASE=2.
- **Severity**: critical
- **What slipped**:
  - `L2` снова красный: 4 snapshot failure в `e2e/header-layout.spec.js` (`mobile closed`, `mobile open`, `desktop closed`, `desktop sklep open`).
  - `PRODUCT_ACCEPT` заблокирован: в `safari_mobile_status.json` остаётся `cta_honesty=FAIL`, `product_accept_allowed=false`.
  - `owner_bug_scoreboard.json` содержит `OWNER-003 = not_fixed` (sticky PDP CTA honesty).
  - `L1` не полностью чистый: `3 passed, 1 skipped` (добавленный mobile PDP path-test).
- **Why it slipped**:
  - Визуальный baseline header не синхронизирован с текущим целевым состоянием UI (без явного product signoff обновлять snapshots нельзя).
  - Продуктовый блокер (`cta_honesty`) не закрыт до этого цикла, несмотря на технические улучшения sticky CTA.
- **Evidence**:
  - `npm run verify:l0` -> PASS_WITH_SKIPS.
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l1` -> `3 passed, 1 skipped`.
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l2` -> `4 failed, 106 passed`.
  - `critic-scorer PHASE=2` -> `REJECT`, `process_accept=false`, `product_accept=false`, `final_accept=false`.
- **Mitigation (now)**:
  - Зафиксирован REJECT с явной привязкой к L2 и Safari/product gate.
  - Сохранён postmortem как блокирующий outcome текущего прохода.
- **Prevention (process)**:
  - Закрыть `OWNER-003` и hostile Safari сценарии до статуса без `cta_honesty=FAIL`.
  - Для следующего прохода убрать `skip` из L1 (или жёстко обосновать scope-исключение).
  - По L2: либо вернуть UI в baseline, либо получить явный owner/product signoff на новый header state и только затем обновлять snapshots.

---

### 2026-03-27 — REJECT: post-deploy L2 visual gate + Safari product gate

- **Context**: после правок process/product-verdict артефактов был выполнен обязательный цикл: predeploy verifier (no local e2e gate) -> push/deploy -> post-deploy technical verify (`L0/L1/L2`) -> `critic-scorer PHASE=2`.
- **Severity**: critical
- **What slipped**:
  - post-deploy `L2` упал на 4 snapshot regression в `e2e/header-layout.spec.js` (mobile closed/open, desktop closed, desktop Sklep open).
  - `PRODUCT_ACCEPT` заблокирован по новым правилам: `safari_mobile_status` содержит `cta_honesty=FAIL`, `back=NOT_VERIFIED`, `product_accept_allowed=false`.
  - в owner scoreboard остаются незакрытые статусы (`partial`/`not_fixed`), включая `OWNER-003`.
- **Why it slipped**:
  - визуальное состояние header изменено относительно baseline (ожидаемо после прошлых правок геометрии), но нет product signoff на новый target state.
  - hostile Safari replay не закрыт до полного PASS по сценариям.
- **Evidence**:
  - `npm run verify:l0` -> PASS_WITH_SKIPS (php-lint PASS, link-check SKIP, lighthouse SKIP).
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l1` -> PASS (3/3).
  - `BASE_URL=https://staging.mnsk7-tools.pl npm run verify:l2` -> FAIL (4 failed, 106 passed, ~6.3m).
  - Артефакты: `tasks/pipeline-json/2026-03-27__mobile-core-hostile/l2_failure_breakdown.json`, `owner_bug_scoreboard.json`, `safari_mobile_status.json`.
- **Mitigation (now)**:
  - зафиксирован REJECT verdict (`process_accept=false`, `product_accept=false`, `final_accept=false`) и сохранён postmortem.
  - добавлен явный `safari_mobile_status.json` и очищена фильтрация `agent_found_bugs_filtered.json` (дубли не считаются как net-new).
- **Prevention (process)**:
  - не обновлять baseline snapshots без owner/product signoff на целевой визуальный state.
  - закрывать Safari hostile-сценарии до уровня, где `cta_honesty` не FAIL и ключевые сценарии не `NOT_VERIFIED`.
  - для фаз с визуальным риском включать post-deploy L0 lighthouse/linkcheck (или явно документировать scoped SKIP).

---

### 2026-03-27 — REJECT: header mobile fallback без завершённого verify:changed

- **Context**: Doer-внесён runtime фикс хедера (touch fallback для mobile state) в `mnsk7-storefront` (`header.php`, `04-header.css`, rebuilt `main.css`).
- **Severity**: major
- **What slipped**:
  - `npm run verify:changed` запущен, но не завершился (зависание/флейк Playwright, процесс остановлен вручную), поэтому не было финального exit status и приемлемых артефактов.
  - `critic-scorer (PHASE=2)` получил `VERIFIER_TECHNICAL=REJECT` и закономерно вернул `outcome=REJECT`.
- **Why it slipped**:
  - Для header/UI правок зона риска требует L1-подтверждения, но verify-процесс нестабилен в текущем окружении.
  - Полагались на факт правки в коде и частичный L0, без завершённого changed-scope behavioral evidence.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verifier_technical.json` → `outcome: REJECT`.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/critic_phase2.json` → `outcome: REJECT`.
  - Терминальный лог `verify:changed`: старт, но без финального footer с exit code.
- **Mitigation (now)**:
  - Зафиксированы verifier/critic JSON-артефакты для прозрачного gate-состояния.
  - Подготовлен минимальный safe diff в теме с touch fallback для хедера, без расширения scope.
- **Prevention (process)**:
  - Для runtime header/mobile правок не переходить к ACCEPT без завершённого `verify:changed` (или `VERIFY_L1=1` эквивалента) и артефактов Playwright.
  - При повторяющемся зависании сразу переключать прогон на более узкий L1-набор (spec-by-spec) с явной фиксацией exit code и trace.

### 2026-03-27 — REJECT: после точечного verify выявлены реальные падения header/footer

- **Context**: после подвисания общего changed-прогона был запущен точечный verify по трём сьютам (`footer-accordion`, `header-layout`, `mobile-design`) для закрытия evidence-гейта.
- **Severity**: major
- **What slipped**:
  - `footer-accordion.spec.js` упал по timeout (не найден/не стабилизировался заголовок аккордеона футера).
  - `header-layout.spec.js` упал на desktop regression (`1024x768`): hover по `Sklep` не видит элемент как visible; часть тестов не выполнилась из-за fail-fast.
- **Why it slipped**:
  - Исправление mobile fallback хедера проверялось в первую очередь по mobile-сигналам; desktop/tablet и footer сценарии не были заранее стабилизированы как обязательные соседние гейты.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verify_summary.json`.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verifier_practical.json`.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/verifier_technical.json`.
  - `tasks/pipeline-json/2026-03-27__design__mobile-header-fallback-gate/critic_phase2.json`.
- **Mitigation (now)**:
  - Зафиксирован formal REJECT-гейт с конкретными failing scenarios вместо состояния “нет финального evidence”.
  - Подтверждено, что mobile-design suite зелёный (62 PASS), но этого недостаточно для acceptance.
- **Prevention (process)**:
  - Для правок хедера всегда гонять связку: `header-layout` + `mobile-design` + `footer-accordion` до первого ACCEPT.
  - Не использовать fail-fast как финальное evidence; после фикса обязательно полный прогон задействованного сьюта без ранней остановки.

### 2026-03-27 — REJECT (повторный gate): verify:changed снова завис, verifiers/critic не приняли diff

- **Context**: повторный запуск обязательного gate-цикла после правок: `verify:changed` + `verify:l0` → `verifier` → `critic-scorer PHASE=2`.
- **Severity**: major
- **What slipped**:
  - `verify:changed` снова не завершился финальным статусом (процесс завис и был остановлен вручную).
  - `verifier` practical и technical дали `REJECT`, `critic PHASE=2` также `REJECT`.
- **Why it slipped**:
  - В зоне хедера остаются неснятые регрессии (`header-layout@1024`, `footer-accordion`) и нестабильность verify-runner.
- **Evidence**:
  - Повторный `verify:l0`: PASS (php-lint), optional L0 шаги SKIP.
  - `critic-scorer PHASE=2`: outcome `REJECT` на текущем diff (блокеры: незавершённый verify:changed и failing targeted checks).
- **Mitigation (now)**:
  - Гейт не закрыт; шаг не завершён как ACCEPT.
  - Зафиксирован повторный REJECT в learning-log, чтобы не терять контекст между итерациями.
- **Prevention (process)**:
  - Перед новым PHASE=2 сначала закрывать Doer-блокеры по `header-layout@1024` и `footer-accordion`, затем запускать verify до полного финального exit.
  - Для hung-ранов сразу использовать узкий reproducible запуск с артефактами (`trace/report`) и только после этого возвращаться к `verify:changed`.

### 2026-03-27 — REJECT (ещё один повтор): gate-последовательность соблюдена, но блокеры не сняты

- **Context**: очередной обязательный цикл на текущем diff: `verify:changed` + `verify:l0` → `verifier` (practical/technical) → `critic PHASE=2`.
- **Severity**: major
- **What slipped**:
  - `verify:changed` снова не завершился (hanging, manual stop), следовательно нет полного финального статуса по changed-зоне.
  - `verifier` practical и technical вернули `REJECT`; `critic PHASE=2` также `REJECT`.
  - Сохраняются known failing scenarios: `header-layout@1024` (hover visibility) и `footer-accordion` (timeout).
- **Why it slipped**:
  - Перед повтором гейта не был сделан новый Doer-фикс по двум уже известным блокерам, из-за чего цикл закономерно повторил предыдущий REJECT.
- **Evidence**:
  - Свежий `verify:l0`: PASS (php-lint), optional checks SKIP.
  - Свежий `verify:changed`: стартует и зависает без финального footer/exit.
  - `critic PHASE=2`: `outcome=REJECT`, `score=0`.
- **Mitigation (now)**:
  - Шаг официально не завершён, ACCEPT не выставлен.
  - Зафиксирован повторный postmortem, чтобы не потерять причинно-следственную цепочку в этом же дне.
- **Prevention (process)**:
  - Не перезапускать PHASE=2 “по кругу” без нового Doer-diff по актуальным blocking issues.
  - Для каждого повторного gate сначала делать минимальный фикс конкретного блокера, затем сразу локальный targeted verify этого блокера, и только потом полный gate.

### 2026-03-27 — REJECT: postdeploy technical gate упёрся в L2 (hang/failures)

- **Context**: выполнен запрошенный порядок: predeploy `verifier` (practical+technical, без локальных verify) → решение `ready_to_deploy=true` → postdeploy technical verify на staging (`L0/L1/L2`) → `critic PHASE=2`.
- **Severity**: major
- **What slipped**:
  - Postdeploy `L0` и `L1` прошли, но `L2` не завершился: в прогоне появились `×/T`, затем зависание без финального статуса, процесс остановлен вручную.
  - Из-за незавершённого `L2` postdeploy technical verifier вернул `REJECT`, и `critic PHASE=2` также `REJECT`.
- **Why it slipped**:
  - Нестабильность L2-раннера/сценариев на staging (флейк/таймаут/ресурсная просадка) не была устранена до acceptance-гейта.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__postdeploy-gate-predeploy-then-technical/verify_summary.json`.
  - `tasks/pipeline-json/2026-03-27__postdeploy-gate-predeploy-then-technical/verifier_postdeploy_technical.json`.
  - `tasks/pipeline-json/2026-03-27__postdeploy-gate-predeploy-then-technical/critic_phase2.json`.
- **Mitigation (now)**:
  - Формально зафиксирован `REJECT` вместо условного ACCEPT на частично зелёных сигналах (`L0/L1`).
  - Сохранены json-артефакты текущего postdeploy гейта.
- **Prevention (process)**:
  - Для postdeploy gate считать шаг закрытым только при терминальном исходе `L2` (PASS/документированный waiver).
  - При зависаниях L2 сразу разбирать первые failing кейсы отдельно (spec-by-spec), сохранять `trace/report`, затем повторять полный L2.

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

---

### 2026-03-26 — ESCALATE: не хватило формального evidence (VERIFY_REPORT/L2) для mobile UI

- **Context**: серия правок в теме (header/cookie bar/sticky CTA) + изменения verify tooling; обсуждение “можно ли завершать шаг гейта”.
- **Severity**: major
- **What slipped**:
  - Были запуски отдельных команд (`verify:l0`, `verify:l1`), но не было единого структурированного `VERIFY_REPORT` с артефактами (raw log + summary), и не было L2 evidence для mobile (header/cookie bar overlay).
- **Why it slipped**:
  - Прогоны шли итеративно, часть проверок была SKIP/прервана, а “one source of evidence” (`npm run verify:all` → `artifacts/verify/verify-report.json`) не был перегенерирован на финальном состоянии.
- **Evidence**:
  - Critic PHASE=2 outcome `ESCALATE`: недостаточно формального evidence по mobile overlay/CTA и отсутствует структурированный отчёт.
- **Mitigation (now)**:
  - Прогнать `VERIFY_L1=1 VERIFY_L2=1 npm run verify:all` (и при необходимости `VERIFY_LINKCHECK=1 VERIFY_LIGHTHOUSE=1`) и приложить `artifacts/verify/verify-report.json` + `artifacts/verify/verify-all.log`.
- **Prevention (process)**:
  - Для изменений в header/cookie bar/cart/checkout: не считать шаг закрытым без L2 (или явного обоснования) и без свежего `verify:all` отчёта.

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

### 2026-03-27 — REJECT: правки verdict-артефактов после последнего verifier/critic без нового evidence

- **Context**: после предыдущего post-deploy цикла были внесены новые правки в `tasks/pipeline-json/2026-03-27__mobile-core-hostile/*.json`; запрошен обязательный predeploy gate перед возможным deploy.
- **Severity**: major
- **What slipped**:
  - Изменены продуктовые статусы (`OWNER-003`, `cta_honesty`) в scoreboard/safari файлах без нового hostile replay evidence.
  - На predeploy этапе нет достаточного подтверждения claims ↔ evidence, поэтому practical+technical verifier закономерно дали `REJECT`.
  - Из-за `BLOCK_DEPLOY` нельзя переходить к post-deploy technical verify и `critic-scorer PHASE=2` для этого diff.
- **Why it slipped**:
  - Попытка обновить verdict-артефакты опередила новый валидирующий цикл (Safari/hostile replay).
  - В артефактах произошло “status uplift” без подкрепления новым доказательным пакетом.
- **Evidence**:
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/verifier_practical_predeploy.json` -> `outcome: REJECT`.
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/verifier_technical_predeploy.json` -> `outcome: REJECT`.
  - `tasks/pipeline-json/2026-03-27__mobile-core-hostile/predeploy_gate_decision.json` -> `overall_readiness_for_deploy: NOT_READY`, `decision: BLOCK_DEPLOY`.
- **Mitigation (now)**:
  - Gate остановлен на predeploy шаге, deploy не выполнен.
  - Добавлены формальные predeploy verifier-артефакты и зафиксирован postmortem.
- **Prevention (process)**:
  - Не менять `owner_bug_scoreboard`/`safari_mobile_status` в сторону “лучше”, пока не приложен новый replay evidence.
  - Перед любым новым deploy требовать predeploy verifier `ACCEPT` в обоих режимах.
  - Только после `READY` запускать post-deploy `L0/L1/L2` и затем `critic-scorer PHASE=2`.

