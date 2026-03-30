# Agent / Automation Research Notes (2026-03)

## Why this note exists

This repository now has a shared Cursor/Codex pipeline. The next improvements should follow current agent-building practice instead of reviving the old heavy workflow.

## Key takeaways from current docs

### 1. Start with the simplest workable orchestration

OpenAI's practical guide recommends maximizing a single agent before adding multi-agent complexity, and scaling to multiple agents only when instructions or tool choice become unreliable.

Implication for this repo:
- keep the shared pipeline lean
- use multi-agent patterns only for real separation of concerns
- prefer prompt templates and structured checklists over forced agent chains

### 2. Use specialized agents with isolated context only when they save context or reduce risk

Claude Code's subagent docs emphasize that specialized subagents run in their own context, with custom prompts, tool access, and permissions. That makes them useful for read-only research, code review, and scoped execution.

Implication for this repo:
- keep a small set of role-specific agents
- make research agents read-only
- keep implementation agents separate from review agents
- use background or parallel agents only when the work is truly independent

### 3. Background agents are powerful but high-risk

Cursor's MCP and agent docs show how easily local and external tools can be connected and auto-run. That is useful for long-running audits or setup, but it increases prompt-injection and exfiltration risk if used carelessly.

Implication for this repo:
- use background or long-running agents for research, audits, or setup, not as the default path for every task
- keep repo write and deploy authority anchored in the shared workflow, not in unbounded autonomy

### 4. Guardrails and structured outputs matter more than agent count

OpenAI's current agent safety guidance stresses structured outputs, tool approvals, clear instructions, evals, and guardrails instead of trusting raw autonomy.

Implication for this repo:
- prefer structured templates for intake, review, and staging verify handoff
- keep external tool use explicit
- add lightweight eval-style checks for recurring delivery failures

## Recommended next additions

### A. Add a `repo-risk-triage` skill

Purpose:
- classify a task as `low-risk` or `high-risk`
- output required verify depth
- decide whether staging confirmation is required

Why:
- this turns the shared workflow into a reusable front door
- it removes ambiguity before any coding starts

### B. Add a `staging-smoke-runner` skill

Purpose:
- run and summarize only the smallest Woo smoke set needed for staging
- produce a short PASS/FAIL handoff

Why:
- the repo already has smoke knowledge, but not a very compact reusable skill for the new lean workflow
- this is the fastest way to make verification repeatable without reviving full heavy loops

### C. Add a `mcp-and-tool-safety` skill

Purpose:
- define how external MCPs, web research, browser tooling, and shell access should be used safely
- list when approvals or manual review are required

Why:
- current official guidance puts real emphasis on tool safety and prompt-injection risk
- this repo uses deploy, browser, and external-tool workflows where that matters

### D. Add a `background-agent-setup` note or skill for Cursor only

Purpose:
- document when to use Cursor background agents
- define allowed scenarios, branch expectations, package bootstrap, and stop conditions

Why:
- long-running background execution is useful, but too dangerous as an unbounded default

### E. Add a `delivery-retrospective` template

Purpose:
- capture repeated failure patterns in a short structured format
- record what to change in skills, templates, or verify rules

Why:
- this is a lighter replacement for the old heavy postmortem culture
- it keeps the workflow improving without forcing giant reports

## Recommended active agent set

A practical set for this repo is:
- `orchestrator` or intake agent
- `implementer`
- `reviewer/verifier`
- optional read-only `research` agent
- optional `staging-check` handoff agent

Anything beyond that should be justified by real complexity, not habit.

## Sources

- OpenAI practical guide to building agents: https://cdn.openai.com/business-guides-and-resources/a-practical-guide-to-building-agents.pdf
- OpenAI agent safety guidance: https://developers.openai.com/api/docs/guides/agent-builder-safety
- OpenAI code-generation guide: https://developers.openai.com/api/docs/guides/code-generation
- Cursor MCP docs: https://docs.cursor.com/en/context/mcp
- Chrome DevTools MCP: https://github.com/ChromeDevTools/chrome-devtools-mcp
- Claude Code subagents docs: https://code.claude.com/docs/en/sub-agents
