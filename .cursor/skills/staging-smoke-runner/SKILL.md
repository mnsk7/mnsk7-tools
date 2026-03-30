---
name: staging-smoke-runner
description: Компактный smoke-runner для staging под lean workflow: только нужные Woo checks и короткий PASS/FAIL handoff.
---

# Staging Smoke Runner

## Goal

Run the smallest useful staging verification set for the current task and summarize the result honestly.

## Default smoke set

For Woo-related tasks verify at minimum:

- add to cart from PLP or PDP when affected
- cart opens and shows the item when affected
- checkout entry opens and the form is visible when affected

## Output

Return a short handoff with:

- `environment`
- `checks_run[]`
- `checks_skipped[]`
- `result`: `PASS | FAIL | PARTIAL`
- `blockers[]`
- `notes[]`

## Rules

- Do not run the full suite by default.
- Match checks to actual changed area.
- If a check is skipped, say why.
- If evidence is partial, result cannot be `PASS` without that limitation being explicit.

## Suggested commands

Prefer the smallest fitting commands already present in the repo, such as:

- `npm run verify:l0`
- `npm run verify:l1`
- `npm run verify:a11y`

Use only the subset required by task risk.
