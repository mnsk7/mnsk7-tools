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

function isInScope(filePath) {
  const p = String(filePath || "");
  return (
    /^(\.cursor\/|docs\/|tasks\/|scripts\/|e2e\/|wp-content\/)/.test(p) ||
    /^(OPERATING-MODEL\.md|AGENTS\.md|package\.json|playwright\.config\.js)$/.test(p)
  );
}

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  fs.mkdirSync(stateDir, { recursive: true });
  fs.appendFileSync(
    path.join(stateDir, "raw-events.ndjson"),
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "afterFileEdit", input })}\n`,
    "utf8"
  );

  const filePath =
    input.file_path || input.path || input.target_file || input.edited_file || "(unknown)";

  fs.appendFileSync(
    path.join(stateDir, "file-edits.log"),
    `${new Date().toISOString()} ${filePath}\n`,
    "utf8"
  );

  if (!isInScope(filePath)) {
    writeJson({});
    return;
  }

  // This doesn't run subagents directly, but it forces a follow-up instruction
  // into the conversation context so the agent cannot "forget" the gate.
  writeJson({
    additional_context:
      "Изменены файлы в зоне runtime/process. Следуй пайплайну: Critic PHASE=1 → фиксы (Doer) → Verify по зоне (дефолт: `npm run verify:changed` + `npm run verify:l0`; L1 Woo-flow — ТОЛЬКО если изменения реально в Woo/checkout/cart/product зоне или форс `VERIFY_L1=1`) → Verifier → Critic PHASE=2. Gate verifier/PHASE=2 обязателен ПЕРЕД завершением, но НЕ вместо фикса замечаний PHASE=1."
  });
}

main().catch(() => writeJson({}));

