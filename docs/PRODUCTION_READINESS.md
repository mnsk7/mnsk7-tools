# Production readiness — checklist przed release

Krótki checklist przed wypuszczeniem na prod. Po zmianach (mnsk7-storefront, usunięcie emoji, typografia) — co zweryfikować.

---

## Wykonane (staging / repo)

- **Tema:** child **mnsk7-storefront** (parent Storefront). Wszystkie odwołania tech-storefront → mnsk7-storefront w theme i docs.
- **Emoji:** usunięte z front-page (hero USPs, kategorie fallback, loyalty tier). Zastąpione pustym spanem + opcjonalny marker „·” w CSS.
- **Typografia w footerze:** 300 zl → 300 zł; Dostawa i płatności; Polityka prywatności. URL-e pozostawione jako slugi ASCII (dostawa-i-platnosci, polityka-prywatnosci).
- **Publiczne menu:** filtr w mu-plugins (mnsk7_remove_account_flow_from_primary_menu) usuwa z primary menu: Edit Profile, Login, Register, Wishlist, Zamówienie itd.
- **Dokumentacja:** ARCHITECTURE, DEPLOY_PLAYBOOK, BACKLOG i inne — aktualna tema: Storefront + mnsk7-storefront.

---

## Przed release na prod — do sprawdzenia

1. **Staging:** https://staging.mnsk7-tools.pl — hero bez emoji, footer z poprawnymi znakami (zł, płatności, prywatności), menu bez pozycji account-flow.
2. **Baza:** staging i prod mają **osobne bazy** (wp-config: DB_NAME różne). Zmiana motywu na staging nie zmienia prod.
3. **Deploy:** ścieżki stage vs prod poprawne (DEPLOY_SAFETY.md); dry-run przed pierwszym deployem na prod.
4. **QA:** smoke (główna → kategoria → produkt → koszyk → checkout), 4 sign-offy (Visual, IA, Conversion, Smoke) — docs/QUALITY_GATES.md.
5. **Referencja:** układ kategorii/produktu według UI_SPEC_V2 i ewentualnie REFERENCE_SANDVIK_COROMANT (tabele w kategoriach).

---

## Pliki zmienione w tej kolejności

- `wp-content/themes/mnsk7-storefront/front-page.php` — emoji usunięte, text domain mnsk7-storefront.
- `wp-content/themes/mnsk7-storefront/footer.php` — 300 zł, płatności, prywatności; URL-e ASCII.
- `wp-content/themes/mnsk7-storefront/assets/css/main.css` — marker „·” dla pustego .mnsk7-hero__usp-icon.
- `wp-content/mu-plugins/mnsk7-tools.php` — filtr menu (już był).
- docs: ARCHITECTURE, DEPLOY_PLAYBOOK, BACKLOG, AS_IS_*, FRONTEND_REWORK_*, MARKETING_UX_*, STAGING_AND_GITHUB — tech-storefront → mnsk7-storefront.

