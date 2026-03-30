# Shared Repo Pipeline

## Goal

This is the canonical workflow for changes made from either Cursor or Codex.

The pipeline is intentionally lean. It should scale process with risk instead of forcing the same heavy loop for every task.

## Flow

1. **Intake**
   - Understand the task and identify the affected pages, flows, and files.
   - Confirm the editable zone stays inside theme, project mu-plugins, or project-owned plugin code.

2. **Scope and risk classification**
   - `low-risk`: docs-only, process-only, narrow styling or template change with no Woo runtime impact.
   - `high-risk`: Woo flow, JS behavior, cart, checkout, product runtime, deploy scripts, mu-plugins, or changes to shared delivery contracts.

3. **Minimal safe diff**
   - Change only what is needed for the stated problem.
   - Avoid opportunistic cleanup unless it directly reduces delivery risk.

4. **Pre-push review**
   - Confirm scope did not expand.
   - Confirm allowed zones were respected.
   - Confirm no obvious Woo conversion guard was broken.
   - Confirm docs were updated if the contract changed.

5. **Push to `main`**
   - Staging deploy is driven by `main`.
   - The GitHub Actions workflow remains the deploy mechanism.

6. **Selective post-deploy verification**
   - `low-risk`: lightweight verification only.
   - `high-risk`: targeted staging verification based on the affected risk area.

## Verification policy by risk

### Low-risk

Use the lightest proof that still supports the change:

- diff review
- targeted smoke check
- local syntax/static check when applicable
- optional staging spot-check if the change is visible or easy to regress

Do not require full multi-agent or full-suite verify by default.

### High-risk

Use stronger proof because failure is expensive:

- pre-push review with explicit Woo/conversion guard check
- deploy to staging through `main`
- targeted post-deploy verification on staging
- L1 Woo flow verification when purchase flow is touched
- optional L2 visual/perf/a11y only when the changed area justifies it

## Acceptance

A change is acceptable when all of the following are true:

- the requested outcome is implemented
- scope stayed controlled
- the appropriate risk-based verification was completed
- Woo conversion guards still hold
- staging safety is preserved

## Explicitly not required

The shared pipeline does not require, by default:

- a fixed chain of orchestrator -> analyzer -> critic -> doer -> verifier for every task
- generated bug quotas
- mandatory product-discovery reports for low-risk work
- full L0/L1/L2 verification on every change
- client-specific artifacts committed to git
