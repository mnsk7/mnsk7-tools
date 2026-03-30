# START HERE — Shared workflow for Cursor and Codex

## First read

Start with these files:

- `docs/REPO_PIPELINE.md`
- `docs/CLIENT_OVERLAYS.md`
- `docs/STACK_MAP.md`
- `docs/DEFINITION_OF_DONE.md`
- `docs/QUALITY_GATES.md`
- `docs/DEPLOY_PLAYBOOK.md`

## Mental model

This repository uses one shared process for both Cursor and Codex.

- shared rules live in repo docs
- `.cursor/` adapts the workflow for Cursor
- `.codex/` adapts the workflow for Codex
- `main` is the staging deploy branch
- verification depth depends on risk

## Default path

1. understand the task
2. classify risk
3. make the minimal safe diff
4. review before push
5. push to `main`
6. verify on staging only as much as the risk requires

## Never skip these guards

- do not edit WP core or third-party plugins
- do not break `add_to_cart`, `cart`, or `checkout_entry`
- do not compromise staging safety
- do not commit client session artifacts to git
