# Operating Model (mnsk7-tools.pl)

## Что это

Этот файл описывает **общую модель работы репозитория** для двух клиентов:

- Cursor
- Codex

Источник истины теперь находится на уровне репозитория, а не внутри конкретного client overlay.

## Core principles

- Один общий pipeline для Cursor и Codex.
- `main` — единственный deploy branch для `staging.mnsk7-tools.pl`.
- Изменения должны быть минимальными, проверяемыми и не расширять scope без причины.
- WP core и сторонние плагины не редактируем.
- Главный guardrail: не ломаем Woo conversion flow (`add_to_cart`, `cart_update`, `checkout_entry`).
- Тяжёлые verify-циклы запускаются **по риску**, а не автоматически для каждой задачи.

## Canonical sources

| Topic | Canonical source |
| --- | --- |
| Shared repo pipeline | `docs/REPO_PIPELINE.md` |
| Client overlays and folder ownership | `docs/CLIENT_OVERLAYS.md` |
| Shared stack map | `docs/STACK_MAP.md` |
| Definition of done | `docs/DEFINITION_OF_DONE.md` |
| Verify risk policy | `docs/QUALITY_GATES.md` |
| Product verification expectations | `docs/BUG_DISCOVERY_ACCEPTANCE.md` |
| Staging/deploy safety | `docs/DEPLOY_SAFETY.md`, `docs/DEPLOY_PLAYBOOK.md`, `.github/workflows/deploy-staging.yml` |
| Repo-wide rules for editable zones | `.cursorrules`, `AGENTS.md` |
| Cursor-specific adapter | `.cursor/` |
| Codex-specific adapter | `.codex/` |

## Shared workflow

1. Task intake.
2. Scope and risk classification.
3. Minimal safe diff in allowed code zones.
4. Pre-push review against scope, conversion guards, and deploy safety.
5. Push to `main` for staging deploy.
6. Post-deploy verification only to the level required by risk.

## Risk model

### Low risk

Examples:
- docs/process updates
- small copy changes
- narrow CSS/template tweaks outside cart/checkout runtime

Expected path:
- concise plan
- minimal diff
- pre-push review
- selective manual or lightweight verification

### High risk

Examples:
- Woo flow
- cart/checkout/product runtime behavior
- JS behavior changes
- deploy scripts
- mu-plugins
- shared process contracts that can change delivery behavior

Expected path:
- concise plan
- minimal diff
- stricter pre-push review
- post-deploy verification on staging
- L1 Woo flow verification when purchase flow is affected

## Roles

Both clients may use the same conceptual roles, but roles are optional tools, not mandatory ritual steps:

- intake/orchestrator
- analyzer
- implementer/doer
- reviewer/verifier
- critic/scorer

The repo contract does **not** require every role on every task. Use only the amount of process that increases confidence for the actual risk.

## Anti-patterns

- treating agent count as proof of quality
- generating reports that do not change ship decisions
- running full verify suites for low-risk work by default
- keeping client session artifacts in git
- accepting change by vibes when Woo flow risk is present
