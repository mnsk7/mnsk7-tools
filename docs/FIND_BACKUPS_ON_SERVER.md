# Поиск и удаление бэкапов на сервере (освобождение места)

## ✅ Найден крупный бэкап (DirectAdmin)

В папке домена **mnsk7-tools.pl**:
- `public_html` — 18.56 GB (текущий сайт)
- **`public_html_20250703_094309`** — **3.96 GB** — бэкап от 03.07.2025 (193 дня назад). Можно удалить.

---

Предупреждение: **Przekroczono dostępną przestrzeń dyskową** — нужно освободить место.

---

## 1. Подключись по SSH

```bash
ssh mnsk7-staging
# или
ssh -p 222 llojjlcemq@s56.cyber-folks.pl
```

---

## 2. Найти, что занимает место

### Объём по папкам в домашней директории
```bash
du -sh ~/* 2>/dev/null | sort -rh | head -20
```

### Типичные папки с бэкапами
```bash
du -sh ~/backups 2>/dev/null
du -sh ~/domains/*/backups 2>/dev/null
du -sh ~/domains/*/public_html/wp-content/backup* 2>/dev/null
du -sh ~/domains/*/public_html/wp-content/upgrade* 2>/dev/null
du -sh ~/domains/*/public_html/wp-content/upgrade-temp-backup 2>/dev/null
du -sh ~/.trash 2>/dev/null
du -sh ~/tmp 2>/dev/null
```

### Крупные файлы (sql, tar.gz, zip, backup)
```bash
find ~ -maxdepth 4 -type f \( -name "*.sql" -o -name "*.tar.gz" -o -name "*.zip" -o -name "*backup*" \) -size +1M 2>/dev/null -exec ls -lh {} \;
```

### Временные и кеш (часто можно чистить)
```bash
du -sh ~/domains/mnsk7-tools.pl/public_html/wp-content/cache 2>/dev/null
du -sh ~/domains/mnsk7-tools.pl/public_html/wp-content/wp-rocket-config 2>/dev/null
du -sh ~/domains/*/public_html/wp-content/upgrade 2>/dev/null
du -sh ~/domains/*/public_html/wp-content/upgrade-temp-backup 2>/dev/null
```

---

## 3. Удалить (осторожно — только бэкапы и мусор)

### Если нашёл папку backups в домене
```bash
# Сначала посмотреть размер и содержимое
ls -la ~/domains/mnsk7-tools.pl/backups
# Удалить (если это старые бэкапы и они не нужны)
rm -rf ~/domains/mnsk7-tools.pl/backups/*
```

### upgrade-temp-backup (временные копии при обновлении WP)
```bash
rm -rf ~/domains/mnsk7-tools.pl/public_html/wp-content/upgrade-temp-backup/*
```

### Старые .sql в /tmp или в домашней папке
```bash
find ~ -maxdepth 3 -name "*.sql" -type f -mtime +7 -ls
# Если список — старые дампы, удалить:
# find ~ -maxdepth 3 -name "*.sql" -type f -mtime +7 -delete
```

### Кеш плагинов (освободит место, кеш набьётся заново)
```bash
# LiteSpeed / общий кеш
rm -rf ~/domains/mnsk7-tools.pl/public_html/wp-content/cache/*
# WP Rocket (если есть)
rm -rf ~/domains/mnsk7-tools.pl/public_html/wp-content/cache/min/*
```

---

## 4. Проверить квоту после очистки

```bash
quota -s
# или
df -h ~
```

---

## 5. Если бэкапы делает панель DirectAdmin

- Зайти в DirectAdmin → **Backups** / **Restore Backups**.
- Там могут храниться автоматические бэкапы — скачать нужные на свой комп и удалить старые с сервера через панель (если есть опция удаления).

---

## Кратко: скопируй и выполни по шагам

```bash
ssh mnsk7-staging
du -sh ~/* 2>/dev/null | sort -rh | head -20
du -sh ~/domains/*/backups ~/domains/*/public_html/wp-content/upgrade* ~/domains/*/public_html/wp-content/cache 2>/dev/null
find ~ -maxdepth 4 -type f \( -name "*.sql" -o -name "*.tar.gz" -o -name "*backup*" \) -size +5M 2>/dev/null -exec ls -lh {} \;
```

По результату — удаляй только то, в чём уверен (бэкапы старше N дней, upgrade-temp-backup, кеш).
