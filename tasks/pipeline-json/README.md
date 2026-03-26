## pipeline-json

Здесь храним **JSON-результаты пайплайна** (Analyzer / Critic PHASE=1 / Doer summary / Verify / Verifier practical+technical / Critic PHASE=2) для аудита “что и почему было принято”.

### Правила

- **Только JSON** (без скриншотов/видео). Артефакты тестов остаются в стандартных путях (`test-results/`, `playwright-report/`, `artifacts/verify/`).
- Один run = одна папка.
- Название папки: `YYYY-MM-DD__<scope>__<short-title>` (пример: `2026-03-26__protocol__verifier-critic-modes`).
- Внутри: фиксированные имена файлов, чтобы их легко парсить.

### Шаблон run-папки

- `orchestrator.json`
- `analyzer.json`
- `critic_phase1.json`
- `final_issue_list.json` (если есть)
- `doer_summary.json`
- `verify_commands.json` (что запускали)
- `verifier_practical.json`
- `verifier_technical.json`
- `critic_phase2.json`

