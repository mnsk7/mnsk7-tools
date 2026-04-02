#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_DIR="$ROOT_DIR/.codex/state"
ARTIFACT_DIR="$ROOT_DIR/.codex/artifacts"
STATE_FILE="$STATE_DIR/ralph-loop.local.json"
LOG_FILE="$ARTIFACT_DIR/ralph-loop.log"

usage() {
  cat <<'EOF'
Usage:
  bash scripts/codex-ralph-start.sh "<prompt>" [--completion-promise "<text>"] [--max-iterations <n>]

Examples:
  bash scripts/codex-ralph-start.sh "Fix mobile megamenu scroll" --completion-promise "DONE" --max-iterations 5
  bash scripts/codex-ralph-start.sh "Refactor header CSS safely" --max-iterations 8
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

mkdir -p "$STATE_DIR" "$ARTIFACT_DIR"

node - "$STATE_FILE" "$LOG_FILE" "$PROMPT" "$COMPLETION_PROMISE" "${MAX_ITERATIONS:-}" <<'NODE'
const fs = require("node:fs");

const [, , stateFile, logFile, prompt, completionPromise, maxIterationsRaw] = process.argv;
const maxIterations =
  maxIterationsRaw === "" || maxIterationsRaw === undefined ? null : Number(maxIterationsRaw);

const state = {
  active: true,
  prompt,
  completion_promise: completionPromise || "",
  max_iterations: Number.isFinite(maxIterations) ? maxIterations : null,
  iteration: 0,
  created_at: new Date().toISOString(),
  completed: false,
  completed_at: null,
  stop_reason: null,
  last_prompt_at: null,
  last_prompt_path: null,
  last_transcript_path: null
};

fs.writeFileSync(stateFile, `${JSON.stringify(state, null, 2)}\n`, "utf8");
fs.appendFileSync(
  logFile,
  `${new Date().toISOString()} START max=${state.max_iterations ?? "unlimited"} promise=${state.completion_promise || "(none)"} prompt=${JSON.stringify(prompt)}\n`,
  "utf8"
);
NODE

echo "Codex Ralph loop is active."
echo "State: $STATE_FILE"
echo "Log:   $LOG_FILE"
if [ -n "$COMPLETION_PROMISE" ]; then
  echo "Completion promise: <promise>$COMPLETION_PROMISE</promise>"
fi
if [ -n "$MAX_ITERATIONS" ]; then
  echo "Max iterations: $MAX_ITERATIONS"
fi
echo
echo "Next step:"
echo "  bash scripts/codex-ralph-next.sh"
