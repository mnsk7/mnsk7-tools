# Product Verification Expectations

## Goal

Product verification should confirm that the requested outcome is real in the product.

This repository no longer requires generated bug quotas or mandatory discovery lists for every task.

## What product verification means

For changes with visible or behavioral impact, confirm:

- the requested user-facing result is actually present
- the affected page or flow still behaves honestly
- no obvious regression was introduced in the touched area

## Low-risk changes

For low-risk work, product verification can be lightweight:

- targeted page check
- focused smoke check
- brief note in the delivery summary

## High-risk changes

For high-risk work, product verification should be explicit and staging-based when needed:

- confirm the changed user journey on staging
- confirm the main CTA or flow still works
- record unresolved risk honestly if evidence is partial

## What is no longer required by default

- fixed quotas for newly discovered bugs
- mandatory owner bug ledgers for every task
- device-specific acceptance templates when the task does not require them

## Rule of honesty

If verification is partial, say so.
A change is not accepted just because artifacts exist; acceptance depends on evidence that matches the risk.
