# mnsk7-tools.pl — staging / deploy
# Wymaga: .env (cyberfolks_ssh_*), na serwerze wp-cli

.PHONY: staging-refresh deploy-files deploy-mu-plugins staging-fix-db sync-prod-to-staging github-create-repo check-db

# Создать репо на GitHub и запушить (gh auth login нужен один раз): make github-create-repo REPO=mnsk7-tools
github-create-repo:
	./scripts/github-create-repo.sh $(or $(REPO),mnsk7-tools)

# Проверка каталога по БД (атрибуты, SKU, alt): DB_PASS='...' make check-db
check-db:
	./scripts/check-db-catalog.sh

# На сервере: копировать файлы ПРОД → СТЕЙДЖ (wp-config не трогаем)
sync-prod-to-staging:
	./scripts/sync-prod-to-staging.sh

# В базе staging: siteurl/home → staging URL, blog_public=0 (без ручного phpMyAdmin)
staging-fix-db:
	./scripts/staging-fix-db.sh

# One-button: odświeżenie staging (DB z prod + replace + flush)
staging-refresh:
	./scripts/staging-refresh.sh

# Rsync: mu-plugins (i opcjonalnie themes/plugins) na staging
deploy-files:
	./scripts/deploy-rsync.sh staging

# Tylko mu-plugins
deploy-mu-plugins: deploy-files

# Pełny flow: najpierw pliki, potem DB
staging-full: deploy-files staging-refresh

# FB-04: wyłączenie pluginów filtrów dublujących „Filtruj" na stagingu (wymaga .env, SSH, WP-CLI na serwerze)
deactivate-filter-plugins:
	./scripts/staging-deactivate-filter-plugins.sh
