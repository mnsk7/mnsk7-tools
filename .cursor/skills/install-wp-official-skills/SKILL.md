---
name: install-wp-official-skills
description: Установка официальных WordPress agent-skills (wp-cli, triage, performance, phpstan и т.д.) в Cursor окружение.
---

# Установка официальных WP skills

В этом репозитории **каноничный путь** — хранить активные skills в `.cursor/skills/`.

Официальные WP skills лучше **устанавливать из источника**, чем копировать в репозиторий вручную.

Запуск из корня репо:

```bash
npx skills add https://github.com/WordPress/agent-skills --agent cursor -y \
  --skill wordpress-router --skill wp-project-triage --skill wp-plugin-development \
  --skill wp-wpcli-and-ops --skill wp-performance --skill wp-phpstan --skill wpds
```

Если команда недоступна в среде — фиксируем это как блокер и используем локальные скрипты/verify, не “выдумывая” результаты.

