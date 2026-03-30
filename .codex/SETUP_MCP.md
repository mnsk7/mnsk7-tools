# Codex MCP Setup

## Included server

This repository includes a project-level Chrome DevTools MCP configuration in `.codex/mcp.json`.

Server:
- `chrome-devtools`
- package: `chrome-devtools-mcp@latest`

## Why this config looks this way on Windows

The current setup uses a Windows-friendly wrapper:

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

## Activation

See `docs/CHROME_DEVTOOLS_MCP_SETUP.md` for the step-by-step activation flow for Cursor and Codex.

## Safety

Use a clean browser context where possible. Browser MCP can expose page content and session data to the client.
