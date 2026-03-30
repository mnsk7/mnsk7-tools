# Deploy Playbook — mnsk7-tools.pl (Cyber_Folks)

This playbook describes how staging deploy works after the shared pipeline cleanup.

## Branch model

- `main` is the staging deploy branch.
- Push to `main` triggers `.github/workflows/deploy-staging.yml`.
- Local working branches are optional, not required by the repo contract.
- A PR workflow may still be used by choice, but it is no longer the mandatory delivery path.

## What is deployed

| Source | Staging target |
| --- | --- |
| `mu-plugins/` | `wp-content/mu-plugins/` |
| `wp-content/themes/mnsk7-storefront/` | `wp-content/themes/mnsk7-storefront/` |

The deploy workflow remains rsync-based and staging-only.

## Pre-push expectations

Before pushing to `main`:

- keep the diff minimal
- stay inside allowed code zones
- review Woo conversion risk
- update docs when contracts change

## After push

- GitHub Actions deploys to staging
- verification depth depends on change risk
- Woo/runtime/deploy changes require stronger staging confirmation

## Safety rules

- staging target must remain staging-only
- staging safety protections must remain active
- rollback should use git history or known backup procedure

## References

- `docs/REPO_PIPELINE.md`
- `docs/QUALITY_GATES.md`
- `docs/DEPLOY_SAFETY.md`
- `.github/workflows/deploy-staging.yml`
