# Chrome DevTools MCP Setup

## What this repo already contains

- Cursor config: `.cursor/mcp.json`
- Codex config: `.codex/mcp.json`
- Codex note: `.codex/SETUP_MCP.md`

The configured server is `chrome-devtools` using `chrome-devtools-mcp@latest`.

## Verified local prerequisites on this machine

- Chrome exists at `C:\Program Files\Google\Chrome\Application\chrome.exe`
- `node` is installed
- `npx -y chrome-devtools-mcp@latest --help` runs successfully

## Best activation path for Cursor

1. Keep the project-level config in `.cursor/mcp.json`.
2. Also add the same server in your user-level Cursor MCP settings if you want it available outside this repo.
3. Restart Cursor.
4. Open the MCP/tools panel and confirm `chrome-devtools` is visible.
5. Ask Cursor to use Chrome DevTools MCP on a concrete task, for example:
   - inspect console errors on staging
   - inspect network requests on checkout
   - capture a screenshot of the current PDP state

### Cursor notes

- OpenAI docs explicitly state that Cursor reads MCP configuration from `mcp.json`.
- If the server does not appear after restart, re-open the repo and validate the JSON file.
- For a safer default, use a clean Chrome profile when debugging authenticated pages.

## Best activation path for Codex

1. Keep the project-level reference config in `.codex/mcp.json`.
2. Add the same server to your Codex MCP configuration if your Codex setup expects user-level config.
3. Restart the Codex app or refresh the workspace.
4. Confirm the server is listed in the Codex MCP/server UI.
5. Run a concrete browser-debug task through the agent.

### Codex notes

- OpenAI docs confirm Codex supports MCP servers and documents setup via Codex MCP configuration and `codex mcp` commands.
- This repo keeps a Windows-friendly JSON example so the same server definition is easy to copy into user-level config if needed.

## Recommended runtime mode

For normal work in this repo, prefer launching a fresh browser instance through MCP.

Use special connection flags only when needed:

- `--autoConnect` if you intentionally want to connect to an already running Chrome 144+ session with remote debugging enabled
- `--browserUrl` or `--wsEndpoint` only for advanced/manual debugging flows
- `--isolated` when you want an ephemeral clean browser context
- `--slim` when you only need screenshots, simple navigation, and script execution

## Practical troubleshooting

If the MCP server does not start:

1. Check that `node` and `npx` work in a new terminal.
2. Run `npx -y chrome-devtools-mcp@latest --help` manually.
3. Confirm Chrome is installed.
4. Restart the app after config changes.
5. If needed, switch the server command to an explicit absolute path for `npx.cmd`.

## Safety

Browser MCP can expose page content, cookies, and authenticated session state to the active client.
Use a clean browser context where possible and avoid mixing personal browsing with debug sessions.

## Sources

- OpenAI Docs MCP page: https://developers.openai.com/learn/docs-mcp
- Chrome DevTools MCP repo: https://github.com/ChromeDevTools/chrome-devtools-mcp
