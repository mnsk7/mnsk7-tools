# Stack Map — mnsk7-storefront (staging.mnsk7-tools.pl)

## Stack

- CMS: WordPress
- E-commerce: WooCommerce
- Parent theme: Storefront
- Child theme: `wp-content/themes/mnsk7-storefront`
- Shared custom runtime: `mu-plugins/`
- Verification stack: Playwright, Lighthouse CI, axe, linkinator
- Deploy path: GitHub Actions on push to `main`

## Editable zones

- `wp-content/themes/mnsk7-storefront`
- `wp-content/themes/storefront` when present in the repo
- `mu-plugins/`
- `wp-content/plugins/<custom>` when project-owned
- shared docs and workflow files

## High-risk areas

- Woo templates and hooks
- cart and checkout flows
- JS that changes header, navigation, search, or cart behavior
- mu-plugin runtime logic
- deploy scripts and workflow contracts

## Core entry points

- Header: `wp-content/themes/mnsk7-storefront/header.php`
- Footer: `wp-content/themes/mnsk7-storefront/footer.php`
- Theme styles: `wp-content/themes/mnsk7-storefront/assets/css/main.css`
- Theme behavior: `wp-content/themes/mnsk7-storefront/functions.php`
- Woo overrides: `wp-content/themes/mnsk7-storefront/woocommerce/`
- Deploy workflow: `.github/workflows/deploy-staging.yml`
- E2E config: `playwright.config.js`

## Woo conversion guards

Any change must preserve:

- add to cart from PLP or PDP
- cart visibility and cart update
- checkout entry with visible form

## Overlay model

- shared contracts live in `docs/` and root files
- Cursor-specific adapter lives in `.cursor/`
- Codex-specific adapter lives in `.codex/`
