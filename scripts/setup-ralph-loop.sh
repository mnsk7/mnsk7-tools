#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_FILE="$ROOT_DIR/.cursor/ralph-loop.local.json"

usage() {
  cat <<'EOF'
Usage:
  bash scripts/setup-ralph-loop.sh "<prompt>" [--completion-promise "<text>"] [--max-iterations <n>]

Examples:
  bash scripts/setup-ralph-loop.sh "Fix checkout regressions" --completion-promise "DONE" --max-iterations 20
  bash scripts/setup-ralph-loop.sh "Refactor header CSS safely" --max-iterations 10
EOF
}

if [ "${1:-}" = "" ] || [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
  usage
  exit 0
fi

PROMPT="$1"
shift

COMPLETION_PROMISE=""
MAX_ITERATIONS=""

while [ $# -gt 0 ]; do
  case "$1" in
    --completion-promise)
      COMPLETION_PROMISE="${2:-}"
      shift 2
      ;;
    --max-iterations)
      MAX_ITERATIONS="${2:-}"
      shift 2
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [ -n "$MAX_ITERATIONS" ] && ! [[ "$MAX_ITERATIONS" =~ ^[0-9]+$ ]]; then
  echo "--max-iterations must be a non-negative integer" >&2
  exit 1
fi

mkdir -p "$ROOT_DIR/.cursor"

node - "$STATE_FILE" "$PROMPT" "$COMPLETION_PROMISE" "${MAX_ITERATIONS:-}" <<'NODE'
const fs = require("node:fs");

const [, , stateFile, prompt, completionPromise, maxIterationsRaw] = process.argv;
const maxIterations =
  maxIterationsRaw === "" || maxIterationsRaw === undefined ? null : Number(maxIterationsRaw);

const state = {
  active: true,
  prompt,
  completion_promise: completionPromise || "",
  max_iterations: Number.isFinite(maxIterations) ? maxIterations : null,
  iteration: 0,
  created_at: new Date().toISOString(),
  last_continue_at: null,
  completed: false,
  completed_at: null,
  stop_reason: null,
  conversation_id: null,
  last_transcript_path: null
};

fs.writeFileSync(stateFile, `${JSON.stringify(state, null, 2)}\n`, "utf8");
NODE

echo "Ralph loop is active."
echo "State: $STATE_FILE"
if [ -n "$COMPLETION_PROMISE" ]; then
  echo "Completion promise: <promise>$COMPLETION_PROMISE</promise>"
fi
if [ -n "$MAX_ITERATIONS" ]; then
  echo "Max iterations: $MAX_ITERATIONS"
fi
