# AGENTS.md (mnsk7-tools.pl — WordPress + WooCommerce)

## Project scope

The repository contains code and operating documents for **staging.mnsk7-tools.pl**.

## Repo-wide rules

1. Do not edit WordPress core or third-party plugin files.
2. Allowed code zones:
   - `wp-content/themes/mnsk7-storefront`
   - `wp-content/themes/storefront` when present in the repo
   - `mu-plugins/`
   - `wp-content/plugins/<custom>` when it is a project-owned plugin
3. Any WooCommerce customization should live in theme overrides, hooks/filters, or project-owned plugin code.
4. Staging safety remains mandatory: no public indexing, no outbound customer mail, no live payments.
5. Prefer small auditable diffs over large rewrites.
6. Any runtime, deploy, or workflow contract change must update the relevant repo-level docs.

## Shared workflow expectations

This repository supports both Cursor and Codex.

- The shared process contract lives in repo-level docs.
- `.cursor/` is a Cursor adapter layer.
- `.codex/` is a Codex adapter layer.
- Client overlays may differ in structure, but they must follow the same shared pipeline and acceptance criteria.

## Delivery model

- `main` is the deploy branch for staging.
- Push to `main` triggers `.github/workflows/deploy-staging.yml`.
- Pre-push review is always required.
- Post-deploy verification is risk-based:
  - low-risk changes use lightweight verification
  - Woo/runtime/deploy changes require stronger staging verification

## Non-negotiable guards

Never accept a change if it breaks any of the following:

- add to cart from PLP or PDP
- cart update / cart visibility
- checkout entry and visible checkout form
- staging safety guarantees

## Where to look first

- Shared pipeline: `docs/REPO_PIPELINE.md`
- Overlay structure: `docs/CLIENT_OVERLAYS.md`
- Definition of done: `docs/DEFINITION_OF_DONE.md`
- Verify policy: `docs/QUALITY_GATES.md`
- Deploy safety: `docs/DEPLOY_SAFETY.md`
