# Codex MCP Setup

## Included server

This repository includes a project-level Chrome DevTools MCP configuration in `.codex/mcp.json`.

Server:
- `chrome-devtools`
- package: `chrome-devtools-mcp@latest`

## Why this config looks this way on Windows

The official Chrome DevTools MCP docs recommend a Windows-friendly wrapper using:

- `cmd /c npx -y chrome-devtools-mcp@latest`
- `SystemRoot`
- `PROGRAMFILES`
- `startup_timeout_ms = 20000`

This improves startup reliability on Windows when launching Chrome through the MCP server.

## Usage

Use Chrome DevTools MCP for:

- console and runtime errors
- network inspection
- layout/debug traces
- performance traces
- DOM inspection on staging

## Safety

Use a clean browser context where possible. Browser MCP can expose page content and session data to the client.
