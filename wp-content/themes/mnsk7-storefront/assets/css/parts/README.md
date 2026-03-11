# CSS parts — mnsk7-storefront

Pliki w tym katalogu to **źródło** do budowy. W runtime temat ładuje tylko `../main.css` (zbudowany skryptem `scripts/build-main-css.sh`). Kolejność: 00-fonts-inter … 25-global-layout (zgodna ze skryptem).

Po edycji parta uruchom `bash scripts/build-main-css.sh` z katalogu theme i wrzuć zaktualizowany main.css w deploy.
