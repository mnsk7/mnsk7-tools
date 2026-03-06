# Protokół deployu na staging

Źródło prawdy: origin/main. Staging = to, co w main po deployzie (rsync).

## 1. Przed pushem (lokalnie)

  bash scripts/validate-theme-php.sh
  git push origin main

Jeśli skrypt się wywali — nie puszuj.

## 2. W CI

Workflow uruchamia php -l na wszystkich .php motywu PRZED rsync. Błąd składni = job fail, deploy się nie wykona.

## 3. Na staging po deployzie (weryfikacja)

  wc -l wp-content/themes/mnsk7-storefront/header.php
  php -l wp-content/themes/mnsk7-storefront/header.php
  nl -ba wp-content/themes/mnsk7-storefront/header.php | tail -n 30

## 4. Czy plik na stagingu = repo (md5)

Lokalnie: md5sum wp-content/themes/mnsk7-storefront/header.php
Na serwerze: md5sum /sciezka/staging/wp-content/themes/mnsk7-storefront/header.php

Równe hashe = ten sam plik.

## 5. Gdy staging jest w git i ma być wyrównany do main

  cd /sciezka/do/staging
  git fetch origin
  git reset --hard origin/main
  php -l wp-content/themes/mnsk7-storefront/header.php

Uwaga: jeśli deploy idzie przez rsync (jak tu), pliki na stagingu są nadpisywane z repo; powyższy reset ma sens tylko gdy staging jest klonem gita i deploy robisz inaczej.
