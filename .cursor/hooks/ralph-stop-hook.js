const fs = require("node:fs");
const path = require("node:path");

function readStdin() {
  return new Promise((resolve) => {
    let data = "";
    process.stdin.setEncoding("utf8");
    process.stdin.on("data", (chunk) => (data += chunk));
    process.stdin.on("end", () => resolve(data));
  });
}

function writeJson(payload) {
  process.stdout.write(`${JSON.stringify(payload)}\n`);
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, "utf8"));
}

function writeState(filePath, state) {
  fs.writeFileSync(filePath, `${JSON.stringify(state, null, 2)}\n`, "utf8");
}

/**
 * Completion must appear in the last assistant *text* turn only, as
 * <promise>TOKEN</promise>. Whole-file or substring checks false-positive on:
 * - setup-ralph-loop.sh printing <promise> to terminal (captured in transcript)
 * - Shell tool JSON containing --completion-promise "TOKEN"
 * - assistant saying "do not emit TOKEN yet" (still includes substring)
 */
function extractLastAssistantTextFromJsonl(transcriptPath) {
  if (!transcriptPath || !fs.existsSync(transcriptPath)) {
    return "";
  }
  const raw = fs.readFileSync(transcriptPath, "utf8");
  const lines = raw.split("\n").filter((line) => line.trim() !== "");
  for (let i = lines.length - 1; i >= 0; i--) {
    let row;
    try {
      row = JSON.parse(lines[i]);
    } catch {
      continue;
    }
    if (row.role !== "assistant" || !row.message || !Array.isArray(row.message.content)) {
      continue;
    }
    const textParts = row.message.content
      .filter((p) => p && p.type === "text" && typeof p.text === "string")
      .map((p) => p.text);
    const combined = textParts.join("\n").trim();
    if (combined !== "") {
      return combined;
    }
  }
  return "";
}

function hasCompletionPromise(transcriptPath, promise) {
  if (!promise) return false;
  const lastAssistantText = extractLastAssistantTextFromJsonl(transcriptPath);
  if (!lastAssistantText) return false;
  const tagged = new RegExp(`<promise>\\s*${escapeRegExp(promise)}\\s*<\\/promise>`, "i");
  return tagged.test(lastAssistantText);
}

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const rootDir = process.cwd();
  const statePath = path.join(rootDir, ".cursor", "ralph-loop.local.json");
  const debugDir = path.join(rootDir, ".cursor", "hooks", "state");
  const debugLog = path.join(debugDir, "ralph-events.ndjson");

  fs.mkdirSync(debugDir, { recursive: true });
  fs.appendFileSync(
    debugLog,
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "stop", input })}\n`,
    "utf8"
  );

  if (!fs.existsSync(statePath)) {
    writeJson({});
    return;
  }

  let state;
  try {
    state = readJson(statePath);
  } catch {
    writeJson({});
    return;
  }

  if (!state.active) {
    writeJson({});
    return;
  }

  const conversationId = String(input.conversation_id || "");
  if (!state.conversation_id && conversationId) {
    state.conversation_id = conversationId;
  } else if (state.conversation_id && conversationId && state.conversation_id !== conversationId) {
    writeJson({});
    return;
  }

  const transcriptPath = input.transcript_path || state.last_transcript_path || null;
  if (transcriptPath) {
    state.last_transcript_path = transcriptPath;
  }

  const transcriptPathResolved = transcriptPath;
  const promise = String(state.completion_promise || "").trim();
  const promiseMet = hasCompletionPromise(transcriptPathResolved, promise);
  const currentIteration = Number(state.iteration || 0);
  const maxIterations =
    state.max_iterations === null || state.max_iterations === undefined
      ? null
      : Number(state.max_iterations);

  if (promiseMet) {
    state.active = false;
    state.completed = true;
    state.completed_at = new Date().toISOString();
    state.stop_reason = "completion_promise_met";
    writeState(statePath, state);
    writeJson({});
    return;
  }

  if (Number.isFinite(maxIterations) && currentIteration >= maxIterations) {
    state.active = false;
    state.completed = false;
    state.completed_at = new Date().toISOString();
    state.stop_reason = "max_iterations_reached";
    writeState(statePath, state);
    writeJson({
      followup_message: `Ralph loop stopped after reaching max iterations (${maxIterations}). Document what remains blocked, what you tried, and what the operator should do next, then finish without claiming success.`
    });
    return;
  }

  state.iteration = currentIteration + 1;
  state.last_continue_at = new Date().toISOString();
  writeState(statePath, state);

  const promptBlock = String(state.prompt || "").trim();
  const promiseBlock = promise
    ? `Output completion only when the work is actually done by emitting exactly: <promise>${promise}</promise>`
    : "There is no completion promise configured. Stop only when the operator cancels the loop or the max iteration limit is reached.";
  const iterationBlock = Number.isFinite(maxIterations)
    ? `Iteration ${state.iteration} of ${maxIterations}.`
    : `Iteration ${state.iteration}.`;

  writeJson({
    followup_message: `Continue the active Ralph loop. Re-read the same task and keep iterating inside this session. ${iterationBlock}\n\nOriginal task:\n${promptBlock}\n\n${promiseBlock}\nDo not claim completion early. Use the current files and git state as feedback, make the next best improvement, and continue working.`
  });
}

main().catch(() => writeJson({}));
