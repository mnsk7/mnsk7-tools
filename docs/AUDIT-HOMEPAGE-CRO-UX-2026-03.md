# Аудит главной страницы (Homepage) — CRO + UX + WooCommerce

**URL:** https://staging.mnsk7-tools.pl/  
**Роль:** senior CRO + UX + WooCommerce reviewer  
**Цель:** понять, помогает ли главная продавать, вести в каталог и вызывать доверие, или это просто набор блоков.

---

## Краткое резюме (формат ответа)

**1. Что критично ломает главную**  
Перегруженный hero (5 USP + CTA + «Witaj»), промо-бар над шапкой оттягивает внимание, блок trust расположен слишком низко, разные стили кнопок без единой системы, нет одного явного «второго шага» после hero.

**2. Что на главной лишнее**  
Пятый USP в hero (цифру лучше в trust), дублирование ссылки Allegro из shortcode, лишнего крупного блока нет — Instagram уместен внизу.

**3. Что на главной слабое**  
CTA в hero не доминирует визуально, нет ссылки «Zobacz wszystkie bestsellery», слабый CTA программы рабатов для гостя, быстрые ссылки «Przeglądaj» зависят от слагов (могут не показаться), риск обрезки длинных названий товаров на мобиле.

**4. Рекомендуемый порядок блоков (без полного редизайна)**  
Hero (упрощённый) → Bestsellery (4–6 + «Zobacz wszystkie») → Trust → Kategorie → Program rabatowy → Instagram → Footer.

**5. Задачи для Cursor**  
См. раздел «Gotowe zadania dla Cursor» в конце документа — списки по Hero, Promo bar, Kategorie, Bestsellery, Trust, Loyalty, wizualna spójność, ogólne.

---

## 1. Первый экран (hero)

| Критерий | Оценка | Комментарий |
|----------|--------|-------------|
| **Специализация за 3 сек** | ✅ Хорошо | H1 «Frezy CNC i narzędzia skrawające» + подзаголовок «Drewno · MDF · Aluminium · Stal · Tworzywa sztuczne» сразу объясняют нишу. |
| **Что продаётся** | ✅ Видно | Текст явно про фрезы и материалы — не «инструменты вообще». |
| **Главный CTA** | ⚠️ Есть, но не доминирует | Одна кнопка «Przejdź do sklepu» — логично, но визуально теряется среди 5 USP-чипов. |
| **Перегруз hero** | ⚠️ Перегружен | В одном экране: H1, подзаголовок, 5 USP-пилюль, опционально «Witaj, …», CTA. На мобиле (2rem padding, font 1.5rem) всё съезжает, чипы переносятся в 2–3 ряда — hero растягивается. |
| **Visual hierarchy** | ⚠️ Слабая | Все чипы одинаковые по весу; CTA не выделен как единственное действие. Нет одного явного «куда нажать». |
| **Читаемость на мобиле** | ⚠️ Норм, но тесно | Размер заголовка уменьшен (1.5rem), отступы 2rem 1rem — текст читаем, но плотно. |
| **Общие обещания** | ⚠️ Частично | «100% pozytywnych opinii», «3 500+ zamówień» — цифры хороши; «Darmowa dostawa od 300 zł», «Faktura VAT» — типовые, без дифференциации. |

**Дополнительно:** над hero висит промо-бар «Warunki dostawy →». Он забирает внимание и место на мобиле; закрывается, но при первом заходе конкурирует с hero.

**Итог по hero:** Специализация и оффер ясны, но первый экран перегружен элементами одного уровня, CTA не является визуальным приоритетом №1.

---

## 2. Путь пользователя

| Вопрос | Ответ |
|--------|--------|
| **Из hero понятно куда идти?** | Да — «Przejdź do sklepu». Альтернативы (категории) в hero нет, только ниже. |
| **Как попасть в категории?** | Через блок «Kategorie» под hero: быстрые чипы «Przeglądaj» (если слаги совпадают с БД) + сетка категорий с картинками и «Wszystkie produkty →». В хедере есть выпадающее меню «Sklep» с категориями — дублирование. |
| **Как попасть в топ товары?** | Блок «Bestsellery i polecane» — 8 товаров с кнопкой «Dodaj do koszyka». Прямо под категориями. |
| **Почему покупать именно тут?** | В hero — USPs (dostawa, faktura, opinie, zamówienia). Отдельный блок «Dlaczego kupujący nam ufają» — цифры (100%, 383, 3 500+, 425), Allegro Super Sprzedawca, отзывы, CTA на Allegro. Программа рабатов — ниже. Логика есть, но trust размазан: часть в hero, часть в отдельной секции. |

**Проблема пути:** Сразу после hero идут категории, потом бестселлеры. Для «хочу купить фрез» путь логичен; для «хочу сравнить магазин» trust идёт после каталога/товаров — часть пользователей может не доскроллить.

---

## 3. Блоки homepage — по каждому

### 3.1 Категории (Kategorie)

| Критерий | Оценка |
|----------|--------|
| Нужен ли | ✅ Да, обязателен для e-commerce. |
| На своём месте | ✅ Сразу после hero — ок. |
| Дублирует ли | ⚠️ Дублирует меню «Sklep» в хедере (те же категории). Не критично: на главной — акцент на выборе категории. |
| Конверсия | ✅ Ведёт в каталог, «Wszystkie produkty →» — явная ссылка. |
| Мобиль | ⚠️ 2 колонки, gap 0.75rem — нормально; быстрые чипы «Przeglądaj» зависят от слагов (`frez-spiralny`, `frezy-do-drewna-mdf` и т.д.) — если слагов нет в БД, строка не показывается. |
| Длина | ✅ Норм — до 12 категорий + ссылка «Wszystkie produkty». |
| CTA | ✅ «Wszystkie produkty →» — достаточно. |

**Задачи:** Проверить, что quick_slugs соответствуют реальным категориям; иначе показывать fallback (например, первые 3–5 из списка категорий) или убрать подпись «Przeglądaj:».

---

### 3.2 Bestsellery / Polecane

| Критерий | Оценка |
|----------|--------|
| Нужен ли | ✅ Да — показывает товар и даёт быстрый «Dodaj do koszyka». |
| На своём месте | ⚠️ Сейчас: hero → Kategorie → Bestsellery. Для конверсии часто выгоднее: hero → Bestsellery → Kategorie (сразу товар, потом углубление). |
| Дублирует ли | Нет. |
| Конверсия | ✅ Прямые действия: карточка + кнопка в корзину. |
| Мобиль | ✅ 2 колонки до 500px, потом 1 — по коду ок; проверить обрезку длинных названий. |
| Длина | ⚠️ 8 товаров — много для первого экрана; 4–6 достаточно. |
| CTA | ✅ «Dodaj do koszyka» на каждой карточке; отдельного блочного CTA «Zobacz wszystkie» нет — можно добавить. |

**Задачи:** Рассмотреть сдвиг блока выше категорий или сразу под hero; ограничить до 4–6 товаров + ссылка «Zobacz wszystkie bestsellery».

---

### 3.3 Trust / Reviews (Dlaczego kupujący nam ufają)

| Критерий | Оценка |
|----------|--------|
| Нужен ли | ✅ Да — снимает сомнения. |
| На своём месте | ⚠️ Сейчас после категорий и бестселлеров. Для CRO лучше ближе к верху: после hero или после одного блока (bestsellery/категории). |
| Дублирует ли | Частично: 100%, 3 500+ уже в hero как USP. В блоке — развёрнуто (4 цифры, Allegro, отзывы). Дублирование цифр допустимо для усиления. |
| Конверсия | ✅ Цифры + отзывы + одна CTA «Zobacz profil i opinie na Allegro». |
| Мобиль | ✅ Сетка отзывов в 1 колонку на ≤900px. |
| Длина | ✅ Норм — 4 стата, подпись, 3 отзыва, одна кнопка. |
| CTA | ✅ Одна явная кнопка Allegro (дубли из shortcode скрыты в CSS). |

**Задачи:** Переместить блок trust выше (см. рекомендуемый порядок); не дублировать лишний раз те же формулировки, что в hero.

---

### 3.4 Allegro trust proof

| Критерий | Оценка |
|----------|--------|
| Встроен в блок «Dlaczego kupujący nam ufają»: текст «Super Sprzedawca Allegro», отзывы shortcode, одна CTA. Отдельного блока нет — ок. | ✅ |

**Задачи:** Нет отдельного блока — оставить как есть.

---

### 3.5 Loyalty / Program rabatowy

| Критерий | Оценка |
|----------|--------|
| Нужен ли | ✅ Да — мотивирует повторные покупки. |
| На своём месте | ⚠️ После trust и перед Instagram — логично, но для первого заказа менее важно, чем trust и товар. |
| Дублирует ли | Нет. |
| Конверсия | ⚠️ CTA «Sprawdź swój poziom rabatu w Moje konto» — для гостя слабый (нужно konto). Лучше добавить контекст: «Zaloguj się lub załóż konto, aby zobaczyć swój rabat». |
| Мобиль | ✅ Тирры в ряд с flex-wrap. |
| Длина | ✅ Норм — 4 тира, короткий текст. |
| CTA | ⚠️ Один текст-ссылка; для гостя неочевидна выгода. |

**Задачи:** Усилить CTA для niezalogowanych (zachęta do rejestracji/konto); визуально выделить один CTA-призыв.

---

### 3.6 Instagram

| Критерий | Оценка |
|----------|--------|
| Нужен ли | ⚠️ Желательно, но не критично для конверсии в покупку. |
| На своём месте | ✅ В конце контента, перед футером — ок. |
| Дублирует ли | В футере есть @mnsk7tools — дублирование ссылки, приемлемо. |
| Конверсия | ❌ Слабо ведёт в магазин; в основном social proof / życie marki. |
| Мобиль | ✅ Сетка/карусель адаптируются. |
| Длина | ✅ 6 постов — норм. |
| CTA | ✅ «Zobacz profil» / link do profilu. |

**Задачи:** Оставить; при реорганизации не поднимать выше trust/bestsellery.

---

### 3.7 Newsletter

| Критерий | Оценка |
|----------|--------|
| В футере, не на главной как отдельная секция. Форма: e-mail + «Zapisz się». Описание: «Otrzymuj informacje o promocjach, nowościach i poradach.» | ✅ |
| Нужен ли | ✅ Да — lead capture. |
| Место | ✅ Footer — стандарт. |
| CTA | ✅ «Zapisz się» — понятно. |

**Задачи:** Без изменений на главной; при необходимости можно добавить один компактный блок newsletter над футером (опционально).

---

### 3.8 Contact / Info

| Критерий | Оценка |
|----------|--------|
| W footerze: adres, KRS/NIP/REGON, formularz kontaktowy, telefon, godziny, Instagram. | ✅ |
| На главной отдельного блока «Kontakt» нет — контакт только в footer. Для главной достаточно. | ✅ |

**Задачи:** Нет.

---

## 4. Визуальная система

| Элемент | Оценка | Комментарий |
|---------|--------|-------------|
| **Отступы секций** | ✅ | `.mnsk7-section { padding: 3rem 0 }`, `.mnsk7-section--light` — единообразно. |
| **Кнопки** | ⚠️ | Hero: `.mnsk7-hero__btn--primary` (biały). Trust: `.mnsk7-trust-cta__btn` (primary). Loyalty: tekst link. Różne style przycisków — nie jedna systemowa „primary”. |
| **Заголовки секций** | ✅ | `.mnsk7-section__title` — center, fs-2xl, font-weight 800. Spójne. |
| **Разнобой блоков** | ⚠️ | Hero — gradient, białe przyciski. Sekcje — białe/surface tło. Trust — liczby + karty opinii. Loyalty — karty z procentami. Instagram — grid/karuzela. Wrażenie „zbierane z różnych szablonów” przez różnorodność kart i CTA. |
| **Одинаковые ли блоки** | ⚠️ | Nie: hero bez karty, kategorie — karty z obrazkiem, bestsellery — grid produktów WC, trust — staty + karty cytatów, loyalty — karty z %, Instagram — inny styl. Spójność średnia. |

**Рекомендации:** Wprowadzić jeden zestaw klas dla przycisków (np. .btn .btn--primary) w sekcjach głównej; ograniczyć warianty kart (np. ten sam border-radius, cień).

---

## 5. Conversion logic — kolejność i priorytety

- **Drugi blok po hero:** Lepiej **Bestsellery** — od razu pokazać produkt i „Dodaj do koszyka”. Kategorie jako trzeci blok — „jeśli nie wiesz, czego szukasz, wybierz kategorię”.
- **Trust:** Lepiej **wyżej** — np. po bestsellerach lub nawet po hero (drugi blok). Obecna pozycja (po kategoriach i bestsellerach) sprawia, że część użytkowników nie dojdzie do argumentów zaufania.
- **Kategorie:** Na 2. lub 3. miejscu — oba warianty dopuszczalne; przy „najpierw bestsellery” kategorie jako 3. blok.
- **Bestsellery:** Powinny być w górnej połowie strony (blok 2 lub 3).
- **Co мешает:** (1) Promo bar nad hero — odciąga uwagę. (2) Zbyt wiele USP w hero — rozprasza. (3) Brak jednego wyraźnego CTA w hero (przycisk ginie wśród chipów).
- **Co uprościć:** Hero — zmniejszyć do 3 USPs lub przenieść część do stopki/trust; jeden główny przycisk w hero.

---

# Podsumowanie

## 1. Co krytycznie psuje główną

1. **Hero przeładowany** — 5 USP + CTA + opcjonalnie „Witaj”; na mobile hero się rozciąga, brak jednej wyraźnej hierarchii i jednego dominującego CTA.
2. **Promo bar „Warunki dostawy”** nad headerem — zabiera uwagę i miejsce; na pierwszym wejściu konkuruje z hero.
3. **Trust za nisko** — użytkownik najpierw widzi kategorie i bestsellery, a „Dlaczego nam ufają” dopiero po przewinięciu; część odchodzi bez wzmocnienia zaufania.
4. **Niespójne przyciski** — hero biały, trust primary, loyalty tekst; brak jednego systemu CTA.
5. **Brak wyraźnego „drugiego kroku”** — po hero nie ma jednej sugerowanej ścieżki (np. „Zobacz bestsellery” vs „Wybierz kategorię”); oba są na równych prawach niżej.

## 2. Co na głównej jest zbędne

1. **Piąty USP w hero** — „3 500+ zamówień rocznie” można przenieść do bloku trust; w hero zostaw 3–4 najważniejsze.
2. **Duplikacja linku do Allegro** — w shortcode są linki, potem ukrywane CSS; lepiej w shortcode nie renderować drugiego linku na stronie głównej.
3. **Instagram** — nie zbędny, ale nie powinien być wyżej niż trust/bestsellery; obecna pozycja ok.

## 3. Co na głównej jest słabe

1. **CTA w hero** — przycisk „Przejdź do sklepu” nie jest wizualnie dominujący; chipy przyciągają równą uwagę.
2. **Brak linku „Zobacz wszystkie bestsellery”** w sekcji bestsellerów.
3. **Program rabatowy** — CTA słabe dla gościa („Sprawdź w Moje konto” bez zachęty do rejestracji).
4. **Quick links „Przeglądaj”** w Kategorie — zależą od slugów; jeśli brak dopasowań, blok może być pusty lub mylący.
5. **Długie nazwy produktów** w bestsellerach — ryzyko obcięcia na małych ekranach (sprawdzić w praktyce).

## 4. Zalecany nowy porządek bloków (bez pełnego redizajnu)

1. **Hero** (zredukowany: 3–4 USPs, jeden wyraźny CTA).
2. **Bestsellery i polecane** (4–6 produktów + link „Zobacz wszystkie”).
3. **Dlaczego kupujący nam ufają** (trust + Allegro + opinie).
4. **Kategorie** (z quick chips tylko jeśli slugi istnieją + grid + „Wszystkie produkty”).
5. **Program rabatowy** (z mocniejszym CTA dla gości).
6. **Instagram.**
7. **Footer** (Klient, Kategorie, Kontakt, Newsletter — bez zmian).

Ewentualna wariant: **Hero → Trust → Bestsellery → Kategorie → Rabat → Instagram**, jeśli priorytetem jest najpierw zaufanie, potem produkt.

## 5. Gotowe zadania dla Cursor (po blokach)

### Hero
- [ ] Zmniejszyć liczbę USP w hero do 3–4 (np. dostawa, faktura, opinie); „3 500+ zamówień” przenieść do bloku trust.
- [ ] W CSS/HTML zwiększyć prominence przycisku „Przejdź do sklepu” (np. większy rozmiar, mocniejszy cień), żeby był jedynym dominującym CTA w hero.
- [ ] Na mobile: rozważyć mniejszą liczbę chipów w jednym rzędzie lub mniejszy font, żeby hero nie rozciągał się za bardzo.

### Promo bar
- [ ] Rozważyć wyłączenie promo baru na stronie głównej (filtr `mnsk7_header_promo_text` zwracać '' gdy `is_front_page()`) lub skrócenie tekstu na mobile.

### Kategorie
- [ ] W `front-page.php`: sprawdzić, czy termy o slugach z `quick_slugs` istnieją; jeśli nie — pokazać fallback (np. pierwsze 3–5 kategorii z listy) lub ukryć wiersz „Przeglądaj:”.
- [ ] Zachować obecną strukturę gridu i link „Wszystkie produkty →”.

### Bestsellery
- [ ] W shortcode/wywołaniu ograniczyć do 4–6 produktów na głównej (np. `limit="6"`).
- [ ] Dodać pod gridem link „Zobacz wszystkie bestsellery” (np. do `/sklep/?orderby=popularity` lub dedykowanej strony).
- [ ] W `front-page.php`: przenieść sekcję bestsellerów nad sekcję Kategorie (zmiana kolejności sekcji).

### Trust
- [ ] Przenieść sekcję „Dlaczego kupujący nam ufają” wyżej: bezpośrednio po bestsellerach (w nowym porządku: po hero → bestsellery → trust → kategorie).
- [ ] Upewnić się, że w shortcode `[mnsk7_allegro_reviews]` na front-page nie renderuje się drugi link do Allegro (albo zostawić ukrywanie przez CSS).

### Loyalty
- [ ] Dodać dla niezalogowanych tekst typu: „Zaloguj się lub załóż konto, aby zobaczyć swój poziom rabatu” i wyraźny przycisk/link do rejestracji lub „Moje konto”.
- [ ] Jednolity styl przycisku (np. ten sam co w trust) zamiast samego linku tekstowego.

### Wizualna spójność
- [ ] Wprowadzić wspólne klasy dla głównych CTA (np. `.btn .btn--primary`) i użyć ich w hero, trust i loyalty, żeby jeden system przycisków.
- [ ] Sprawdzić na mobile: bestsellery (obcięcie nazw), kategorie (2 kolumny), trust (1 kolumna) — poprawić ewentualne obcięcia.

### Ogólne
- [ ] Zastosować nowy porządek bloków w `front-page.php`: Hero → Bestsellery → Trust → Kategorie → Program rabatowy → Instagram.
- [ ] Po zmianach: przetestować na viewport 360px i 1920px (hero, kolejność, klikalność CTA, brak zepsutych layoutów).
