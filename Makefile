# mnsk7-tools.pl — staging / deploy
# Wymaga: .env (cyberfolks_ssh_*), na serwerze wp-cli

.PHONY: staging-refresh deploy-files deploy-mu-plugins

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
