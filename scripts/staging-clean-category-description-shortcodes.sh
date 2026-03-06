#!/usr/bin/env bash
# Удаляет из описаний категорий товаров (product_cat) артефакты шорткодов [wpf-filters id=7] и т.п.
# После деактивации плагина фильтров шорткод выводится как текст.
# Запуск: ./scripts/staging-clean-category-description-shortcodes.sh [--dry-run]

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"
[[ -f .env ]] || { echo "Brak .env"; exit 1; }

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
SSH_PASS=$(grep '^cyberfolks_ssh_password=' .env | cut -d= -f2-)
WP_PATH="domains/mnsk7-tools.pl/public_html/staging"
WP="cd $WP_PATH && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp"

DRY_RUN="0"
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN="1"

run_ssh() { sshpass -p "$SSH_PASS" ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$1"; }

# Usuń shortcode [wpf-filters ...] z opisu kategorii (wp eval w kontekście WP)
EVAL='\$dry='"$DRY_RUN"'; \$terms=get_terms(array("taxonomy"=>"product_cat","hide_empty"=>false)); foreach(\$terms as \$t){ \$d=get_term_field("description",\$t->term_id,"product_cat"); if(empty(\$d)) continue; \$n=preg_replace("/\[wpf-filters[^\]]*\]/","",\$d); \$n=preg_replace("/\[wpf_filters[^\]]*\]/","",\$n); \$n=trim(preg_replace("/\n\s*\n/","\n",\$n)); if(\$n!==\$d){ echo \$t->term_id." ".\$t->slug."\n"; if(!\$dry) wp_update_term(\$t->term_id,"product_cat",array("description"=>\$n)); } }'

if [[ "$DRY_RUN" == "1" ]]; then
  echo ">>> [DRY-RUN] Kategorie z shortcode [wpf-filters] w opisie <<<"
else
  echo ">>> Usuwam [wpf-filters id=7] z opisów kategorii <<<"
fi
run_ssh "$WP eval '$EVAL'" 2>/dev/null || true
echo "Gotowe. Odśwież stronę kategorii."