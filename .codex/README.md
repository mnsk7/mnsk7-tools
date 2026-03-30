# Codex Overlay

This folder contains Codex-specific adapter files for this repository.

## Use this overlay for

- Codex-specific task guidance
- Codex-specific agent prompts or templates
- Codex-local artifacts that should stay out of git

## Shared source of truth

Use repo-level docs for shared process rules:

- `docs/REPO_PIPELINE.md`
- `docs/CLIENT_OVERLAYS.md`
- `docs/STACK_MAP.md`
- `docs/DEFINITION_OF_DONE.md`
- `docs/QUALITY_GATES.md`

## Codex working stance

- follow the shared repo pipeline
- keep diffs minimal
- match verification effort to risk
- treat `.codex/` as an adapter layer, not the repo source of truth

## Included templates

- `templates/task-intake.md`
- `templates/change-review.md`
- `templates/staging-verify-handoff.md`
