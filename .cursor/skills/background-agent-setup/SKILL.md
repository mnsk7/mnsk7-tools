---
name: background-agent-setup
description: Когда и как использовать background agents в Cursor безопасно и без конфликта с shared workflow.
---

# Background Agent Setup

## Goal

Use background agents only where they create real leverage without taking ownership away from the shared repo workflow.

## Good use cases

- long-running audits
- broad read-only research
- environment setup checks
- repetitive analysis that does not need immediate human steering

## Avoid by default

Do not use background agents as the default for:

- every code change
- deploy decisions
- broad autonomous repo mutation
- tasks with unclear scope or weak guardrails

## Required setup

Before using a background agent:

- classify task risk
- define scope and stop conditions
- decide whether the agent is read-only or allowed to edit
- define which output artifact it must produce

## Safe defaults

- prefer isolated worktree or isolated branch
- prefer read-only first
- keep deploy authority with the main shared workflow
- require a short human review before shipping background-agent output

## Output template

State:

- task
- scope
- permissions
- expected artifact
- stop conditions
- who reviews the result
