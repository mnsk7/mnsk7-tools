# Staging and GitHub

## What lives in git

Keep in git only what belongs to the repository contract or deployable code:

- `mu-plugins/`
- `wp-content/themes/` project theme code
- shared docs and workflow contracts
- stable client overlay configuration for Cursor and Codex

Do not keep local runtime artifacts, secrets, or client session noise in git.

## Deploy model

- Push to `main` triggers staging deploy.
- GitHub Actions remains the deploy mechanism.
- Local working branches are optional.
- Pull requests are optional workflow tooling, not the mandatory repo contract.

## Required GitHub secrets

The deploy workflow still depends on:

- `STAGING_SSH_KEY`
- `STAGING_SSH_HOST`
- `STAGING_SSH_USER`
- `STAGING_SSH_PORT`
- `STAGING_REMOTE_PATH`

## Safety

- staging must keep noindex and safety protections
- staging mail must not leave the environment
- live payments must remain disabled on staging

## References

- `docs/DEPLOY_PLAYBOOK.md`
- `docs/DEPLOY_SAFETY.md`
- `docs/REPO_PIPELINE.md`
