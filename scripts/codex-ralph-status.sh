#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_FILE="$ROOT_DIR/.codex/state/ralph-loop.local.json"

if [ ! -f "$STATE_FILE" ]; then
  echo "No Codex Ralph state found."
  exit 0
fi

cat "$STATE_FILE"
