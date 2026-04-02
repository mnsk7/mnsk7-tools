# Hooks self-test (Cursor)

Цель: доказать, что Ralph stop hook действительно срабатывает и не “молчит” из-за несовпадения схемы событий.

## Где лежит конфиг

- `.cursor/hooks.json`

## Где смотреть сырые payload’ы

Stop hook пишет сырые события в:

- `.cursor/hooks/state/ralph-events.ndjson` (игнорируется git)

## Как проверить

1) Запусти Ralph:
   `bash scripts/setup-ralph-loop.sh "Test Ralph hook" --completion-promise "DONE" --max-iterations 2`
2) Попробуй завершить шаг/остановиться без `<promise>DONE</promise>`.
3) Убедись, что в `ralph-events.ndjson` появилась запись с `"hook":"stop"`.
4) Убедись, что stop hook вернул `followup_message` и не дал завершить loop раньше времени.
5) Добавь completion promise в transcript и повтори stop: hook должен отпустить сессию.

Если `ralph-events.ndjson` пустой — hooks не загружены/не поддерживаются в текущем окружении Cursor.
