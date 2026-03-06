# Quality Gates — pipeline jakości, nie tylko aktywności

**Problem:** Pipeline opisuje kogo uruchomić i jakie pliki mają powstać, ale nie co uznajemy za niedopuszczalny wynik ani kto może zablokować release.

**Zasada:** Każdy etap ma gate — wynik musi przejść walidację. Artykuł agenta nie równa się krok zamknięty.

---

## 1. Niedopuszczalny wynik pośredni

- Discovery/Requirements: brak must-have vs nice-to-have; brak acceptance criteria per szablon.
- Architecture: brak podziału theme / Woo overrides / mu-plugin; brak zasady nie edytujemy pluginów third-party.
- UI spec: tylko kierunkowa notatka, bez visual hierarchy, spacing scale, button variants, card anatomy, header/footer exact behavior.
- Implementation: ekrany bez checklisty ready/not ready; w publicznym menu widać account-flow (Edit Profile, Login, Register, Wishlist, Zamówienie).
- QA: brak smoke, visual defects, regression list, conversion blockers.

Działanie: Gate keeper nie zamyka etapu, dopóki wynik nie spełnia DoD.

---

## 2. Kto może zablokować release

- Product owner: brak akceptacji biznesowej.
- Tech lead / 08_qa_security: smoke nie przechodzi; conversion blockers nie puste; brak visual/IA sign-off.
- Release candidate = po przejściu wszystkich gateów. Nikt nie deployuje bez tego.

---

## 3. UI spec OK, staging wizualnie luźny

- Nie zamykać etapu UI tylko dlatego, że dokument powstał.
- Wymagane: SCREEN_REVIEW_PACK (docs/SCREEN_REVIEW_PACK.md) — per ekran: screenshot/mock, main CTA, hierarchy, mobile, critical defects, approved / rejected.
- Jeśli rejected: backlog z defektami; poprawki; powtórny review.
- Dokument created nie równa się ekran dopięty. Priorytet: ekran dopięty.

---

## 4. Gate przed staging sign-off (4 sign-offy)

- Visual: spójność z UI_SPEC_V2; brak dupów; typografia.
- IA: publiczna nawigacja tylko Sklep, O nas, Pomoc, Kontakt. Bez Edit Profile, Login, Register, Wishlist, Zamówienie. Footer bez dupów Kontakt.
- Conversion: brak conversion blockers; główny CTA czytelny; trust przy checkout.
- Smoke: smoke report wykonany; ścieżki home → category → product → cart → checkout OK.

QA_REPORT.md musi zawierać te sekcje oraz potwierdzenie dla każdego sign-offu.

---

## 5. Kolejność gateów (jak doprowadzać)

1. Discovery approved (REQUIREMENTS + acceptance per szablon).
2. Requirements frozen.
3. Architecture frozen (theme / Woo / mu-plugin; overrides inventory).
4. UI approved (UI_SPEC_V2 + SCREEN_REVIEW_PACK).
5. Shell implemented and reviewed (Header, Footer, nav — Screen Review approved).
6. PLP/PDP implemented and reviewed (Screen Review approved).
7. Checkout reviewed (conversion blockers usunięte).
8. QA passed (wszystkie 4 sign-offy).
9. Release candidate.
10. Deploy (według DEPLOY_SAFETY).

---

## 6. Jedna aktualna specyfikacja UI

- Aktualna: docs/UI_SPEC_V2.md.
- UI_SPEC.md — superseded (zastąpiony przez UI_SPEC_V2). Tylko odniesienie historyczne.

---

Powiązane: START_HERE.md, SCREEN_REVIEW_PACK.md, DEFINITION_OF_DONE.md, BACKLOG_BY_TEMPLATES.md, DEPLOY_SAFETY.md.
