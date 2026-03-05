# Content Plan — mnsk7-tools.pl

*(Выход агента 02_growth_seo)*

Гайды по подбору, FAQ на категориях, структура контента карточки товара. Источники: REQUIREMENTS, DISCOVERY, CLIENT_INTERVIEW_SUMMARY.

---

## 1. Цели контента

- Ответить на **частые вопросы клиентов:** наличие, доставка на следующий день, подходит ли для материала, режимы резки, фактура VAT (DISCOVERY, REQUIREMENTS 2.4).
- Усилить **доверие** и экспертность: гайды, FAQ, структурированные параметры в карточке.
- Поддержать **SEO:** тексты на лендингах категорий, раздел статей под запросы (REQUIREMENTS 3.1).

---

## 2. Статьи и гайды («подбор фрезы»)

**Раздел:** блог / instrukcje / poradniki (отдельная ветка в меню).

| Тема | Формат | Цель |
|------|--------|------|
| Jak dobrać frez do materiału (drewno, MDF, aluminium, stal) | Гайд 800–1500 слов | Запросы «фрезы для дерева/алюминия» и подбор |
| Frezowanie aluminium — parametry i rodzaje frezów | Статья | Трафик + экспертность |
| Frezy do drewna i MDF — przegląd typów | Обзор | Каталог + внутренние ссылки |
| Regimy skrawania (prędkość, posuw) — podstawy | Инструкция | Частый вопрос «какие режимы резки» |
| Frezy z płytkami wymiennymi — kiedy wybrać | Статья | Маржинальная категория (DISCOVERY) |

Кто наполняет контент — в REQUIREMENTS «Требует уточнения»; до уточнения — заложить структуру и шаблоны.

---

## 3. FAQ на категориях

На каждой целевой странице категории (лендинги по материалу/типу) — блок **5–8 вопросов** с ответами. Примеры:

- Jak dobrać średnicę frezu do obrabiarki?
- Jaka różnica między frezem 2 a 4 zęby?
- Czy ta freza nadaje się do aluminium / stali / drewna?
- Jakie chłodzenie przy frezowaniu aluminium?
- Dostawa — następny dzień? (доставка на следующий день)
- Wystawiacie fakturę VAT?

**Технически:** блок в шаблоне категории или через короткоды; вывод в FAQ schema (SEO_PLAN).

---

## 4. Контент карточки товара — структура

Единая структура (для чего, материал, режимы, совместимость), чтобы инженер быстро сканировал (REQUIREMENTS 2.2, 4).

**Блоки:**

1. **Ключевые параметры (наглядно):** Ø, średnica trzpienia, długość robocza, długość całkowita, liczba zębów, materiał/covering. Не только таблица — выделенный блок.
2. **Do czego / Zastosowanie:** подходит для (материал, operacja); при необходимости «nie do» (не для чего).
3. **Regimy skrawania (opcjonalnie):** рекомендуемые диапазоны prędkości/posuw — если есть данные; иначе ссылка на artykuł.
4. **Dostawa i dostępność:** «Dostawa następnego dnia»; «Na magazynie» / «Na zamówienie» (REQUIREMENTS 2.4, UX-06).
5. **Faktura VAT:** krótka informacja (np. w stopce lub na stronie Dostawa i płatność).

Длинное описание — ниже; выше — короткий скан. Видео и схемы параметров — при наличии контента (REQUIREMENTS 2.2).

---

## 5. Страницы «Доставка» и «Оплата / Faktura»

- **Dostawa:** dostawa następnego dnia, InPost/Paczkomaty, koszt, terminy. Częsty pytanie z wywiadu.
- **Płatność:** Przelewy24, Przelewy24 Raty (jeśli używane). Faktura VAT — jak zamówić, dane do faktury.

Контент статичный; linki z głównej i z checkoutu.

---

## 6. Priorytety wdrożenia

| Priorytet | Element | Gdzie |
|-----------|---------|--------|
| 1 | Blok parametrów + „do czego” w karcie produktu | Child theme override (Sprint 02) |
| 2 | FAQ 5–8 na stronach kategorii / lądowaniach | Szablon kategorii lub shortcode |
| 3 | Teksty na lądowaniach (frez do aluminium, MDF, drewna, CNC) | SEO_PLAN §3 |
| 4 | Sekcja artykułów + pierwsze 2–3 artykuły | Po ustaleniu, kto wypełnia treści |
| 5 | Strony Dostawa / Faktura VAT | Strony statyczne |

Zależności: wykonanie Sprint 02 (karta produktu, zaufanie) ułatwia spójność treści w karcie i na stronie.
