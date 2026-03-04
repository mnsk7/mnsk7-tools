# Definition of Done (DoD)

Задача считается **выполненной**, если выполнены все пункты своей категории.

---

## Любая задача (минимум)

- [ ] Код/файлы закоммичены в ветку `feature/*` или `staging`.
- [ ] Описание PR: что сделано, как протестировать, риск.
- [ ] Нет PHP-ошибок в debug.log.
- [ ] Smoke-тест пройден: корзина, чекаут (даже беглая проверка).

---

## Задача на тему / frontend

- [ ] Страница категории открывается без ошибок.
- [ ] Карточка товара открывается без ошибок.
- [ ] add_to_cart работает из категории и из карточки.
- [ ] Изменения только в теме / mu-plugins / кастом-плагине (не в ядре/плагинах).
- [ ] Проверено на мобильном (≤375px).
- [ ] LCP/CLS не ухудшились (PageSpeed или визуально — хотя бы не хуже чем было).
- [ ] Woo overrides через `/woocommerce/` в теме, не в шаблонах плагина.

---

## Задача на Woo (каталог / чекаут / лояльность)

- [ ] add_to_cart работает из категории и из карточки.
- [ ] Чекаут проходит end-to-end: add_to_cart → checkout → заказ создан.
- [ ] Статус заказа корректный после оформления (pending / processing).
- [ ] Письмо заказа на staging перехвачено staging-safety (не ушло наружу).
- [ ] Acceptance criteria из задачи выполнены.

---

## Задача на SEO / контент

- [ ] Категории: Title, H1, Description прописаны по шаблону (см. seo_woocommerce).
- [ ] Мусорные URL фильтров: noindex или canonical (не создают тонкого контента).
- [ ] schema.org не ломает валидацию (Rich Results Test или schema.org/validator).
- [ ] Не появились лишние noindex (проверить в Search Console после деплоя).
- [ ] Внутренние ссылки добавлены (или явно не нужны — зафиксировано в задаче).

---

## Деплой на staging

- [ ] Backup БД: `wp db export /tmp/backup-$(date +%Y%m%d).sql`
- [ ] Backup темы: `cp -a themes/<THEME> themes/<THEME>_prev` (на сервере перед rsync).
- [ ] `make deploy-files` выполнен.
- [ ] `make staging-refresh` выполнен (если нужно обновить БД).
- [ ] blog_public = 0 (noindex на staging).
- [ ] staging-safety.php активен (wp-content/mu-plugins/).
- [ ] post-deploy: `wp cache flush`, `wp rewrite flush --hard`.
- [ ] Smoke-тест: добавить в корзину → чекаут → проверить что заказ создан, письмо не ушло.

---

## Деплой на prod

- [ ] Staging протестирован и одобрен.
- [ ] Backup сделан (DB + тема в _prev).
- [ ] Rollback шаг проверен (знаем как откатить).
- [ ] Деплой в рабочие часы (не в пятницу вечером).
- [ ] post-deploy: `wp cache flush`, `wp rewrite flush --hard`.
- [ ] blog_public = 1 на проде.
- [ ] Smoke-тест после деплоя на проде.
