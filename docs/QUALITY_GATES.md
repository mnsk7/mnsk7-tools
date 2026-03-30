# Quality Gates

## Goal

Quality gates exist to make ship decisions clearer, not heavier.

The repository uses **risk-based gates**. Stronger checks are required only when the risk justifies them.

## Always-on gates

These checks apply to every change:

- editable zones respected
- scope controlled
- no obvious breakage in the touched area
- Woo conversion guards not violated
- deploy contract preserved

## Low-risk gate

Use a lightweight gate when the change is docs-only, process-only, or a narrow non-runtime change.

Recommended proof:

- diff review
- targeted manual check
- syntax/static check when applicable

Low-risk work should not require a mandatory full verify loop.

## High-risk gate

Use a stronger gate when the change affects Woo runtime, cart, checkout, JS behavior, mu-plugins, or deploy mechanics.

Required proof:

- explicit pre-push review
- push to `main`
- targeted post-deploy verification on staging

### Verification levels

- `L0`: baseline syntax and smoke checks
- `L1`: Woo functional flow checks
- `L2`: visual, perf, a11y, or deeper regression checks when the changed area warrants them

### Minimum expectations

- Woo flow changes require `L1`
- deploy script changes require staging confirmation
- visible UI changes may require selected `L2` checks when the area is regression-prone

## Blocking conditions

Reject or rework the change if any of these happen:

- add to cart is broken
- cart is not usable after the change
- checkout entry is broken or the form is missing
- staging safety is compromised
- evidence is weaker than the actual risk of the change
