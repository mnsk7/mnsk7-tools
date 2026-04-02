# Client Overlays

## Purpose

This repository supports two client overlays on top of one shared repo process:

- `.cursor/` for Cursor-specific rules, hooks, agents, and reusable skills
- `.codex/` for Codex-specific guidance, agents, and templates

The overlays may use different file layouts, but they must not define conflicting delivery rules.

## Ownership model

### Shared repo layer

Keep in repo-level docs and root contracts:

- pipeline and acceptance rules
- deploy contract
- stack map
- quality gates
- definition of done
- editable-zone rules

### Cursor overlay

Keep in `.cursor/` only:

- Cursor rules and hooks
- Cursor stop-loop implementation (`Ralph`)
- Cursor-specific agents
- Cursor-specific MCP config
- reusable Cursor skills that are still worth tracking

Do not treat `.cursor/` as the source of truth for the whole repo.

### Codex overlay

Keep in `.codex/` only:

- Codex-specific workflow notes
- Codex-specific agent prompts/templates
- Codex scratch or session layout definitions

Do not copy the whole Cursor layer into `.codex/`.

## Tracked vs ignored

Track in git:

- stable shared contracts
- stable overlay configuration
- stable agent definitions and templates that the team intentionally maintains

Ignore from git:

- generated reports
- session state
- local scratch notes
- local logs and run artifacts
- temporary plans created during one-off work sessions

## Working rule

If a process rule matters for both clients, it belongs in repo docs.
If a rule exists only to adapt the experience of one client, it belongs in that client's overlay.
