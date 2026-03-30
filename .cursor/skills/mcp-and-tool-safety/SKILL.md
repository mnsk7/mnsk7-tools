---
name: mcp-and-tool-safety
description: Правила безопасного использования MCP, web research, browser tooling и shell automation в shared workflow.
---

# MCP and Tool Safety

## Goal

Use powerful tools without turning them into silent risk.

## Apply this skill when

- enabling or editing MCP servers
- using browser/devtools MCP
- using web research for decision-making
- adding automation that can execute commands or inspect remote content

## Core rules

- Prefer official docs and primary sources for unstable tool configuration.
- Treat MCP servers as privileged integrations.
- Do not grant broad automation authority unless the task actually needs it.
- Keep browser and external-tool usage aligned with task scope.

## Browser / DevTools MCP

- Useful for UI debugging, network inspection, console errors, and performance traces.
- Do not treat browser access as safe by default: it can expose page content, tokens, cookies, and user data present in that browser session.
- Use a dedicated profile or clean browser context when possible.

## Web research

- Use web research for current tool versions, setup instructions, and compatibility checks.
- Prefer official documentation, official GitHub repos, and package registries.
- Record the chosen configuration in repo docs when it affects team workflow.

## Shell automation

- Prefer small, explicit commands.
- Avoid automation that silently changes deploy state or writes outside intended repo scope.
- If a command is powerful, document why it is needed.

## Output

When relevant, produce:

- selected tool/server
- why it is needed
- scope of access
- risks
- safe defaults or mitigations
