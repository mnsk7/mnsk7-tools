# Расследование: «слетает» parent theme после деплоя, визуал плывёт после reinstall

**Дата:** 2026-03-09  
**Суть:** Деплой идёт по push в main (GitHub Actions). Раньше в CI выполнялся `rsync --delete wp-content/themes/` — в репо есть только best-shop, mnsk7-storefront, tech-storefront, **storefront в репо нет**. На сервере после такого rsync всё, чего нет в источнике, удалялось → папка **storefront на сервере удалялась** при каждом деплое. Исправление: деплоим только `mnsk7-storefront/`, не весь каталог themes/ — тогда storefront на сервере не трогаем.

---

## A. Findings

### 1. Child theme metadata (проверено)

| Поле | Значение | Статус |
|------|----------|--------|
| **Template** | `storefront` | ✅ Совпадает с slug директории родителя |
| **Name** | MNK7 Storefront | ✅ |
| **Version** | 1.0.0 | ✅ |
| **Text Domain** | mnsk7-storefront | ✅ |

Файл: `wp-content/themes/mnsk7-storefront/style.css`. Template slug корректен.

### 2. Что в репозитории и что на сервере при деплое

- **В Git отслеживаются только темы:** `best-shop`, `mnsk7-storefront`, `tech-storefront`.  
- **Storefront (parent) в репозитории нет** — ни в `git ls-files`, ни в структуре `wp-content/themes/` в репо.

При деплое (локально или CI):

- Источник: `$ROOT/wp-content/themes/` — в CI это только то, что есть в репо после `git checkout`: **best-shop, mnsk7-storefront, tech-storefront**.
- Команда: `rsync -avz --delete ... wp-content/themes/ ... wp-content/themes/`.
- Флаг **`--delete`** удаляет на сервере всё, чего нет в источнике.
- **Итог:** на сервере после каждого деплоя из репо (особенно из CI) папка **`storefront` удаляется**, так как её нет в источнике.

### 3. Deploy process (факты)

| Где | Скрипт/шаг | Команда themes |
|-----|-------------|----------------|
| **Локально** | `scripts/deploy-rsync.sh` | `rsync -avz --delete ... ROOT/wp-content/themes/ ... REMOTE/wp-content/themes/` |
| **CI** | `.github/workflows/deploy-staging.yml` | `rsync -avz --delete ... wp-content/themes/ ... REMOTE_PATH/wp-content/themes/` |

В обоих случаях синхронизируется **целиком** каталог `themes/` с `--delete`. В репо нет `storefront` → на сервере после деплоя папки `storefront` нет.

### 4. WP options и поведение при отсутствии parent

- В `wp_options` остаются: `template` = `storefront`, `stylesheet` = `mnsk7-storefront` (WordPress не сбрасывает их при физическом удалении папки темы).
- `get_template_directory()` продолжает указывать на путь к несуществующей папке `storefront`.
- В child уже есть проверка: `mnsk7_parent_storefront_available()` — `get_template() === 'storefront'` и `is_readable(get_template_directory() . '/style.css')`. При удалённом parent `is_readable` даёт false → функция возвращает false.
- При `mnsk7_parent_storefront_available() === false`:
  - Parent style **не** подключается (`storefront-style` не enqueue).
  - Подключается только child: `mnsk7-storefront-style` без зависимости от parent.
  - Сайт рендерится с одним лишь child (собственный header.php, свои части CSS). Parent физически «слетел» — его папки нет.

Кастомных фильтров `template`/`stylesheet` в коде не найдено.

### 5. Enqueue order (когда parent есть)

- Сначала: `storefront-style` (parent style.css).
- Затем: `mnsk7-storefront-style` с зависимостью `array('storefront-style')`.
- Затем: части CSS (00-fonts-inter … 24-plp-table) цепочкой от `mnsk7-storefront-style`.

Порядок и зависимости корректны. Проблема не в порядке enqueue, а в том, что после деплоя parent удалён и не подключается.

### 6. Почему при «слетевшем» parent сайт местами выглядит лучше

- Подключается **только** child: свои токены (шрифты, цвета), свои overrides (03-storefront-overrides.css), свой header/footer.
- Storefront style.css **не** грузится — нет его базовых правил (другие шрифты/цвета, свои селекторы).
- Нет конфликта каскада: визуал полностью задаётся child. Поэтому «во многом выглядит лучше» — это режим «только child», без вмешательства родительского CSS.

### 7. Почему после reinstall parent часть элементов становится хуже

- Ставят **текущую** версию Storefront с wordpress.org, а не ту, под которую делали child.
- Новая версия Storefront может иметь:
  - другие селекторы/специфичность;
  - другие CSS-переменные или значения по умолчанию;
  - другую вёрстку/классы (header, footer, WooCommerce).
- Child overrides (03-storefront-overrides и др.) заточены под старую разметку/классы — часть правил перестаёт попадать или перебивается.
- В Customizer у Storefront свои `theme_mods_storefront`. После чистой установки parent они сбрасываются в дефолты — цвета/шрифты из прошлой настройки теряются, визуал «уезжает».

### 8. Theme mods / Customizer

- В коде child прямого использования `theme_mods_*` не найдено.
- Storefront как родитель опирается на свои theme_mods (цвета, типографика). После reinstall они сбрасываются — это усиливает визуальные отличия.

### 9. Кэширование

- После деплоя в workflow выполняется очистка WP Rocket (`cache/min`, `cache/wp-rocket`). Object cache / opcache в скриптах не чищаются.
- Главная причина нестабильного вида — не кэш, а удаление parent при деплое и смена версии/настроек при reinstall.

### 10. Fallback в child

- При отсутствии parent child не переключает тему в админке; активна по-прежнему child (stylesheet = mnsk7-storefront), template в options = storefront.
- Child осознанно не подключает parent style и использует свой header — это не «fallback на другую тему», а работа child без родительского CSS при удалённой папке parent.

---

## B. Root cause

**Главная причина:**  
Деплой делает **rsync с `--delete` всего каталога `wp-content/themes/`** из источника, в котором **нет родительской темы Storefront**. Поэтому при каждом таком деплое папка **`storefront` на сервере удаляется** и parent «слетает».

**Сопутствующие причины:**

1. **Версия parent не зафиксирована** — при ручной переустановке ставят актуальный Storefront; child и overrides рассчитаны на другую версию → расхождение по CSS и разметке.
2. **Родитель не в репозитории** — в .cursorrules указано держать Storefront в репо и деплоить вместе с child, но фактически в репо только child и другие темы; деплой «всех тем» с `--delete` при этом убивает parent на сервере.
3. **Theme mods Storefront** после чистой установки parent сбрасываются в дефолты — ещё один источник визуальных отличий после reinstall.

---

## C. Files / configs to change

| Что | Файл/место |
|-----|------------|
| Деплой тем: не трогать весь `themes/`, синкать только child | `scripts/deploy-rsync.sh` |
| То же для CI | `.github/workflows/deploy-staging.yml` |
| Документация деплоя | `docs/DEPLOY_SAFETY.md`, `docs/DEPLOY_PLAYBOOK.md` |
| Зафиксировать требуемую версию Storefront | `docs/DEPLOY_PLAYBOOK.md` или README темы; опционально комментарий в `style.css` child |

---

## D. Fix plan

### Шаг 1 (минимально необходимое): деплой не должен удалять parent

- Перестать делать rsync всего `wp-content/themes/` с `--delete`.
- Деплоить **только** каталог child: `wp-content/themes/mnsk7-storefront/` в `.../wp-content/themes/mnsk7-storefront/`, с `--delete` **внутри** только этого каталога (чтобы удалять устаревшие файлы внутри child, но не трогать другие темы).
- Аналогично в GitHub Actions: rsync только `wp-content/themes/mnsk7-storefront/`.

Итог: при любом деплое папка `storefront` на сервере остаётся нетронутой, parent не «слетает».

### Шаг 2: зафиксировать версию Storefront

- В документации (например DEPLOY_PLAYBOOK или README) указать: с какой версией Storefront совместим child (например 4.x или конкретный номер).
- При необходимости добавить в `style.css` child комментарий вида: `Requires Storefront: 4.x` (по соглашению с командой).
- На сервере устанавливать/обновлять Storefront только до этой версии (вручную или скриптом), чтобы после «reinstall» визуал не менялся из-за другой версии.

### Шаг 3 (hardening): опционально добавить Storefront в репо

- По .cursorrules: «Родительскую тему Storefront тоже держать в репозитории и деплоить вместе с дочерней».
- Если решите держать parent в репо: добавить в репо выбранную версию Storefront в `wp-content/themes/storefront/` и в деплое снова синкать `themes/` (тогда в источнике будет и storefront, и mnsk7-storefront, и `--delete` не удалит storefront). Альтернатива — по-прежнему деплоить только `mnsk7-storefront` и отдельно при необходимости деплоить `storefront` из репо (без `--delete` для всего `themes/`).

На первом этапе достаточно шагов 1 и 2.

---

## E. Code changes

### E.1. scripts/deploy-rsync.sh

**Было:** синхронизация всего `wp-content/themes/` с `--delete`.  
**Стало:** синхронизация только `mnsk7-storefront` с `--delete` только внутри этой папки.

```bash
# Было:
if [[ -d "$ROOT/wp-content/themes" ]]; then
  ...
  rsync -avz --delete $RSYNC_EXTRA -e "$RSYNC_SSH" "$ROOT/wp-content/themes/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/themes/"
fi

# Стало:
if [[ -d "$ROOT/wp-content/themes/mnsk7-storefront" ]]; then
  # Deploy only child theme — do not sync entire themes/ with --delete (would remove storefront on server)
  if [[ -z "${DRY_RUN:-}" ]] && [[ -n "${DEPLOY_BACKUP_THEME:-}" ]]; then
    echo "Backup current theme on server (for rollback)..."
    ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" \
      "cd ~/${REMOTE_BASE}/wp-content/themes && [ -d mnsk7-storefront ] && [ ! -d mnsk7-storefront_prev ] && cp -a mnsk7-storefront mnsk7-storefront_prev" 2>/dev/null || true
  fi
  echo "Rsync theme mnsk7-storefront -> $TARGET..."
  rsync -avz --delete $RSYNC_EXTRA -e "$RSYNC_SSH" "$ROOT/wp-content/themes/mnsk7-storefront/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/themes/mnsk7-storefront/"
fi
```

### E.2. .github/workflows/deploy-staging.yml

**Было:**  
`rsync -avz --delete ... wp-content/themes/ ... wp-content/themes/`

**Стало:**  
Деплой только child theme:

```yaml
- name: Deploy mu-plugins and themes
  env:
    ...
  run: |
    RSYNC_SSH="ssh -i ~/.ssh/deploy_key -p $SSH_PORT -o StrictHostKeyChecking=no"
    rsync -avz --delete -e "$RSYNC_SSH" mu-plugins/ "$SSH_USER@$SSH_HOST:~/$REMOTE_PATH/wp-content/mu-plugins/"
    rsync -avz --delete -e "$RSYNC_SSH" wp-content/themes/mnsk7-storefront/ "$SSH_USER@$SSH_HOST:~/$REMOTE_PATH/wp-content/themes/mnsk7-storefront/"
```

### E.3. Child style.css (опционально)

Добавить в заголовок явное указание на версию parent:

```
Template:     storefront
Template Version: 4.5
```

(Подставить реальную совместимую версию Storefront.)

### E.4. docs/DEPLOY_PLAYBOOK.md (или DEPLOY_SAFETY)

- Уточнить: деплоится только `mnsk7-storefront`, не весь `themes/`; parent Storefront на сервере не перезаписывается и не удаляется.
- Указать: для стабильного вида после reinstall использовать одну и ту же версию Storefront (например 4.5.x), указанную в документации или в style.css child.

---

## F. Acceptance criteria

Система считается исправленной, если:

1. После обычного деплоя child theme (push в main / запуск deploy-rsync) родительская тема Storefront на сервере **не удаляется** и остаётся доступной.
2. В `wp_options` остаются корректные `template` = storefront, `stylesheet` = mnsk7-storefront; WordPress остаётся на связке child + parent.
3. Визуал не меняется случайно после деплоя (цвета, header, footer, PDP, cart, checkout стабильны).
4. После переустановки **той же** версии Storefront, с которой совместим child, визуал не деградирует (при условии сохранения или переноса theme_mods при необходимости).
5. Источник истины понятен: что в child (код, overrides), что в parent (установленная версия), что в БД (theme_mods_storefront, theme_mods_mnsk7-storefront).

---

## Ответы на три вопроса

**1. Почему при слетевшем parent сайт местами выглядит лучше?**  
Потому что в этот момент подключается только child: его CSS (токены, overrides, header/footer). Storefront style.css не грузится — нет ни его базовых стилей, ни конфликта каскада с родителем. Визуал на 100% задаётся child, поэтому он выглядит «чище» и местами лучше, чем при подключённом parent другой версии или сброшенных theme_mods.

**2. Почему после reinstall parent часть элементов становится хуже?**  
Потому что (a) ставится новая версия Storefront с другими селекторами/CSS/разметкой, и overrides в child перестают точно попадать или перебиваются; (b) theme_mods_storefront после чистой установки сбрасываются в дефолты — цвета и типографика меняются. Ребёнок рассчитан на старую версию и старые настройки.

**3. Как сделать так, чтобы разработка child вообще не зависела от ручной переустановки parent?**  
(1) Деплоить только папку child (`mnsk7-storefront`), а не весь `themes/` с `--delete` — тогда parent на сервере не удаляется и переустанавливать его не нужно. (2) Зафиксировать в документации и при установке одну совместимую версию Storefront и не обновлять parent без проверки совместимости с child. (3) При желании — добавить Storefront в репозиторий и деплоить его вместе с child, тогда и обновления хостинга не затрут parent, и версия будет под контролем.
