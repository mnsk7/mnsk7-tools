#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_FILE="$ROOT_DIR/.cursor/ralph-loop.local.json"

if [ ! -f "$STATE_FILE" ]; then
  echo "No active Ralph loop state found at $STATE_FILE"
  exit 0
fi

rm -f "$STATE_FILE"
echo "Ralph loop cancelled."
