#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STATE_FILE="$ROOT_DIR/.codex/state/ralph-loop.local.json"
ARTIFACT_DIR="$ROOT_DIR/.codex/artifacts"
TMP_DIR="$ROOT_DIR/.codex/tmp"
LOG_FILE="$ARTIFACT_DIR/ralph-loop.log"
PROMPT_FILE="$TMP_DIR/ralph-next-prompt.md"

usage() {
  cat <<'EOF'
Usage:
  bash scripts/codex-ralph-next.sh [--transcript <path>] [--stdout]

Options:
  --transcript <path>  Check this transcript/output file for the completion promise before issuing the next prompt.
  --stdout             Print only the next prompt to stdout instead of the status wrapper.
EOF
}

TRANSCRIPT_PATH=""
STDOUT_ONLY=0

while [ $# -gt 0 ]; do
  case "$1" in
    --transcript)
      TRANSCRIPT_PATH="${2:-}"
      shift 2
      ;;
    --stdout)
      STDOUT_ONLY=1
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [ ! -f "$STATE_FILE" ]; then
  echo "No active Codex Ralph state found at $STATE_FILE" >&2
  exit 1
fi

mkdir -p "$ARTIFACT_DIR" "$TMP_DIR"

NODE_OUTPUT="$(node - "$STATE_FILE" "$LOG_FILE" "$PROMPT_FILE" "$TRANSCRIPT_PATH" <<'NODE'
const fs = require("node:fs");

const [, , stateFile, logFile, promptFile, transcriptPath] = process.argv;

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

const state = JSON.parse(fs.readFileSync(stateFile, "utf8"));
if (!state.active) {
  process.stdout.write(JSON.stringify({ kind: "inactive", state }, null, 2));
  process.exit(0);
}

const transcriptText =
  transcriptPath && fs.existsSync(transcriptPath) ? fs.readFileSync(transcriptPath, "utf8") : "";
const promise = String(state.completion_promise || "").trim();
const hasPromise =
  promise &&
  transcriptText &&
  (new RegExp(`<promise>\\s*${escapeRegExp(promise)}\\s*<\\/promise>`, "i").test(transcriptText) ||
    transcriptText.includes(promise));

if (hasPromise) {
  state.active = false;
  state.completed = true;
  state.completed_at = new Date().toISOString();
  state.stop_reason = "completion_promise_met";
  state.last_transcript_path = transcriptPath || null;
  fs.writeFileSync(stateFile, `${JSON.stringify(state, null, 2)}\n`, "utf8");
  fs.appendFileSync(
    logFile,
    `${new Date().toISOString()} COMPLETE reason=completion_promise_met transcript=${JSON.stringify(transcriptPath || "")}\n`,
    "utf8"
  );
  process.stdout.write(JSON.stringify({ kind: "completed", state }, null, 2));
  process.exit(0);
}

const maxIterations =
  state.max_iterations === null || state.max_iterations === undefined
    ? null
    : Number(state.max_iterations);
const currentIteration = Number(state.iteration || 0);

if (Number.isFinite(maxIterations) && currentIteration >= maxIterations) {
  state.active = false;
  state.completed = false;
  state.completed_at = new Date().toISOString();
  state.stop_reason = "max_iterations_reached";
  state.last_transcript_path = transcriptPath || state.last_transcript_path || null;
  fs.writeFileSync(stateFile, `${JSON.stringify(state, null, 2)}\n`, "utf8");
  fs.appendFileSync(
    logFile,
    `${new Date().toISOString()} STOP reason=max_iterations_reached transcript=${JSON.stringify(transcriptPath || "")}\n`,
    "utf8"
  );
  process.stdout.write(JSON.stringify({ kind: "stopped", state }, null, 2));
  process.exit(0);
}

state.iteration = currentIteration + 1;
state.last_prompt_at = new Date().toISOString();
if (transcriptPath) state.last_transcript_path = transcriptPath;

const prompt = [
  "# Codex Ralph Loop",
  "",
  `Iteration: ${state.iteration}${Number.isFinite(maxIterations) ? ` / ${maxIterations}` : ""}`,
  "",
  "Original task:",
  state.prompt,
  "",
  promise
    ? `Completion rule: output exactly <promise>${promise}</promise> only when the work is actually done.`
    : "Completion rule: no completion promise configured. Use max iterations or manual cancel.",
  "",
  "Instructions:",
  "- Re-read the same task.",
  "- Use the current repo state as feedback from previous iterations.",
  "- Make the next best improvement.",
  "- Do not claim completion early.",
  "- If blocked, document the blocker precisely."
].join("\n");

fs.writeFileSync(promptFile, `${prompt}\n`, "utf8");
state.last_prompt_path = promptFile;
fs.writeFileSync(stateFile, `${JSON.stringify(state, null, 2)}\n`, "utf8");
fs.appendFileSync(
  logFile,
  `${new Date().toISOString()} NEXT iteration=${state.iteration} transcript=${JSON.stringify(transcriptPath || "")}\n`,
  "utf8"
);

process.stdout.write(JSON.stringify({ kind: "next", state, prompt }, null, 2));
NODE
)"

if [ "$STDOUT_ONLY" -eq 1 ]; then
  printf '%s\n' "$NODE_OUTPUT" | node -e 'let d="";process.stdin.on("data",c=>d+=c);process.stdin.on("end",()=>{const o=JSON.parse(d);if(o.prompt)process.stdout.write(o.prompt+"\n");});'
  exit 0
fi

KIND="$(printf '%s\n' "$NODE_OUTPUT" | node -e 'let d="";process.stdin.on("data",c=>d+=c);process.stdin.on("end",()=>process.stdout.write(JSON.parse(d).kind));')"

case "$KIND" in
  completed)
    echo "Codex Ralph loop completed."
    echo "$NODE_OUTPUT" | node -e 'let d="";process.stdin.on("data",c=>d+=c);process.stdin.on("end",()=>{const o=JSON.parse(d);console.log("Reason:",o.state.stop_reason);console.log("Completed at:",o.state.completed_at);});'
    ;;
  stopped)
    echo "Codex Ralph loop stopped: max iterations reached."
    echo "$NODE_OUTPUT" | node -e 'let d="";process.stdin.on("data",c=>d+=c);process.stdin.on("end",()=>{const o=JSON.parse(d);console.log("Iterations:",o.state.iteration);console.log("Reason:",o.state.stop_reason);});'
    ;;
  inactive)
    echo "Codex Ralph loop is inactive."
    ;;
  next)
    echo "Next prompt written to: $PROMPT_FILE"
    echo
    printf '%s\n' "$NODE_OUTPUT" | node -e 'let d="";process.stdin.on("data",c=>d+=c);process.stdin.on("end",()=>{const o=JSON.parse(d);process.stdout.write(o.prompt+"\n");});'
    ;;
  *)
    echo "$NODE_OUTPUT"
    exit 1
    ;;
esac
