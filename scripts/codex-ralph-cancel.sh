#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_FILE="$ROOT_DIR/.codex/state/ralph-loop.local.json"
LOG_FILE="$ROOT_DIR/.codex/artifacts/ralph-loop.log"

if [ ! -f "$STATE_FILE" ]; then
  echo "No active Codex Ralph state found."
  exit 0
fi

rm -f "$STATE_FILE"
mkdir -p "$(dirname "$LOG_FILE")"
printf '%s CANCEL\n' "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" >> "$LOG_FILE"
echo "Codex Ralph loop cancelled."
