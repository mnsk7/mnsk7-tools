---
name: legacy-agents-migration
description: Миграция со старого legacy-слоя на каноничный .cursor (агенты/skills/rules).
---

# Legacy слой → `.cursor`

## Правило

Legacy-слой считается устаревшим и не должен использоваться как источник истины.

Канонично:
- `.cursor/rules/*`
- `.cursor/agents/*`
- `.cursor/skills/*`
- `OPERATING-MODEL.md`, `AGENTS.md`

## Если в старых документах встречаются legacy-ссылки

1) Считай это legacy-ссылкой.
2) Ищи эквивалент в `.cursor/*` или обновляй документ.
3) Для WP official skills используй `.cursor/skills/install-wp-official-skills`.

