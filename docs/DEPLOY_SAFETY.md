# Безопасность деплоя: stage vs prod

**Цель:** деплой в stage не поднимается в prod; деплой в prod не трогает `staging/` и не перетирает `wp-config.php`.

---

## 1. Пути на сервере (одна правда)

| Окружение | Путь |
|-----------|------|
| **Prod** | `/home/llojjlcemq/domains/mnsk7-tools.pl/public_html` |
| **Stage** | `/home/llojjlcemq/domains/mnsk7-tools.pl/public_html/staging` |

Stage вложен в prod. Поэтому:
- деплой в **prod** не должен рекурсивно трогать `staging/` (ни `rm -rf`, ни `rsync --delete` в корень без исключения);
- деплой в **stage** должен работать только внутри `.../public_html/staging/`, не подниматься уровнем выше.

---

## 2. Что деплоим (и чего никогда)

- **Деплоим:** только `mu-plugins/`, каталог child-темы `wp-content/themes/mnsk7-storefront/` (не весь `themes/`), при наличии `wp-content/plugins/` из репозитория. Родительская тема Storefront на сервере не трогается (см. docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md).
- **Никогда не деплоим:** `wp-config.php`, `.env`, ядро WP, `uploads/`. В репо их нет (в `.gitignore`), скрипты их не копируют.

Проверка: в репо нет `wp-config.php` → rsync не может его перезаписать.

---

## 3. Точные команды (как сейчас)

### Деплой в **staging** (локально)

```bash
# Путь на сервере (должен быть именно staging):
REMOTE_BASE="domains/mnsk7-tools.pl/public_html/staging"

rsync -avz --delete -e "ssh -p $SSH_PORT ..." mu-plugins/           "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/mu-plugins/"
rsync -avz --delete -e "ssh -p $SSH_PORT ..." wp-content/themes/mnsk7-storefront/ "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/themes/mnsk7-storefront/"
rsync -avz --delete -e "ssh -p $SSH_PORT ..." wp-content/plugins/  "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/plugins/"  # если есть
```

Итог: меняется только `~/.../public_html/staging/wp-content/...`. Prod не трогаем.

### Деплой в **prod** (локально, только вручную)

```bash
# Путь на сервере (строго корень prod, без staging):
REMOTE_BASE="domains/mnsk7-tools.pl/public_html"

rsync -avz --delete -e "ssh -p $SSH_PORT ..." mu-plugins/         "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/mu-plugins/"
rsync -avz --delete -e "ssh -p $SSH_PORT ..." wp-content/themes/mnsk7-storefront/ "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/themes/mnsk7-storefront/"
rsync -avz --delete -e "ssh -p $SSH_PORT ..." wp-content/plugins/ "$SSH_USER@$SSH_HOST:~/$REMOTE_BASE/wp-content/plugins/"  # jeśli jest
```

Итог: мы пишем только в `public_html/wp-content/themes/` и `.../mu-plugins/`, **не** в `public_html/` рекурсивно. Каталог `public_html/staging/` не затрагивается, потому что rsync идёт в подкаталоги `wp-content/...`, а не в корень `public_html` с `--delete`.  
Если бы когда-нибудь делали `rsync SOURCE/ public_html/`, тогда обязательно: `--exclude=staging --exclude=wp-config.php`.

### GitHub Actions (только staging)

Workflow `deploy-staging.yml` срабатывает по push в ветку `staging`. В нём:

- `REMOTE_PATH` = секрет `STAGING_REMOTE_PATH` (должен быть `domains/mnsk7-tools.pl/public_html/staging`).
- Копируются только `mu-plugins/` и `wp-content/themes/` в `~/$REMOTE_PATH/wp-content/...`.
- Prod в Actions не деплоится.

---

## 4. Правила (минимальный набор)

1. **Два явных пути:** в скрипте и в голове всегда разделять `PROD_PATH` и `STAGE_PATH` (см. скрипт).
2. **Перед деплоем печатать путь:** скрипт выводит `TARGET` и полный `REMOTE_BASE`.
3. **Для prod:** целевой путь только `.../public_html` (без `staging`); при любом будущем rsync в корень prod — использовать `--exclude=staging`.
4. **wp-config.php:** не лежит в репо и не входит в rsync — не деплоить никогда.
5. **Dry-run перед реальным деплоем:** `DRY_RUN=1 ./scripts/deploy-rsync.sh [staging|prod]` — только показать, что уйдёт на сервер (rsync с `-n`).

---

## 5. Как проверить, что stage и prod независимы

| # | Тест | Ожидание |
|---|------|----------|
| 1 | **Тема** | На stage одна тема, на prod другая. Поменять тему на stage → prod не меняется. |
| 2 | **Плагин** | Выключить на stage (например staging-safety) → prod не меняется. |
| 3 | **Файл темы** | В theme на stage добавить маркер или изменить `style.css` → на prod темы без изменений. |
| 4 | **Uploads** | Загрузить картинку на stage → в медиатеке prod её нет. |

Если все 4 теста проходят — изоляция ок (разные БД уже есть: prod `llojjlcemq_fdvz1`, stage `llojjlcemq_stg`).

---

## 6. Самое опасное

Stage лежит внутри prod: `.../public_html/staging`. Один неверный:

- `rm -rf`
- `find ... -delete`
- `rsync --delete` в `public_html/` без `--exclude=staging`

может задеть оба окружения. Поэтому: деплой только в конкретный путь (`.../staging` или `.../public_html` только в подкаталоги `wp-content/...`), без «деплоя в корень» всего репо в prod.

---

## 7. Переменные окружения (.env / секреты)

- **Локально:** в `.env` можно задать переопределения путей:
  - `STAGING_REMOTE_PATH` — путь до каталога stage (по умолчанию `domains/mnsk7-tools.pl/public_html/staging`).
  - `STAGING_PROD_PATH` — путь до корня prod (по умолчанию `domains/mnsk7-tools.pl/public_html`).
- **GitHub Actions:** только `STAGING_REMOTE_PATH` (для деплоя в stage). Prod из Actions не деплоится.

---

## 8. Кратко: «всё ок с деплоем»

Только если выполнено:

- [ ] Stage и prod на разных БД (у вас: `llojjlcemq_stg` и `llojjlcemq_fdvz1`).
- [ ] Тема/плагины на stage и prod меняются независимо (проверка тестами выше).
- [ ] Деплой идёт в явный path (staging или prod), не «во всё подряд».
- [ ] Для prod при любом rsync в корень — есть `--exclude=staging`.
- [ ] Перед реальным деплоем есть dry-run (`DRY_RUN=1`).
- [ ] `wp-config.php` не в репо и не входит в деплой.
