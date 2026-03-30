---
name: repo-risk-triage
description: Быстрая классификация задачи по риску и выбор verify-depth для shared Cursor/Codex workflow.
---

# Repo Risk Triage

## Goal

Classify the task before implementation so the team uses the right amount of process.

## Output

Return a compact triage result with:

- `risk_level`: `low` or `high`
- `task_scope`: `docs | ui | runtime | deploy | mixed`
- `affected_areas[]`
- `required_verify_levels[]`
- `needs_staging_confirmation`: `true | false`
- `critical_guards[]`

## High-risk by default

Treat the task as `high` if it touches any of these:

- Woo purchase flow
- cart or checkout
- JS runtime behavior
- `mu-plugins/`
- deploy scripts or workflow files
- shared process contracts that change delivery behavior

## Low-risk by default

Treat the task as `low` if it is only:

- docs or workflow wording
- small copy changes
- narrow CSS/template adjustments with no Woo runtime impact

## Required guards

Always state whether the task could affect:

- add to cart from PLP or PDP
- cart visibility/update
- checkout entry and visible form
- staging safety

## Working rule

If evidence is mixed, classify upward to `high`.
