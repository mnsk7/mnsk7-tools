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

function readJsonFile(p, fallback) {
  try {
    return JSON.parse(fs.readFileSync(p, "utf8"));
  } catch {
    return fallback;
  }
}

function writeJsonFile(p, payload) {
  fs.mkdirSync(path.dirname(p), { recursive: true });
  fs.writeFileSync(p, JSON.stringify(payload, null, 2) + "\n", "utf8");
}

function isInScope(filePath) {
  const p = String(filePath || "");

  // Avoid spam: docs/logs are not runtime/process by default.
  if (/^docs\//.test(p)) return false;
  if (/^tasks\//.test(p)) return false;

  // Runtime/process surfaces where verify discipline matters.
  if (/^scripts\/verify\//.test(p)) return true;
  if (/^e2e\//.test(p)) return true;
  if (/^wp-content\//.test(p)) return true;
  if (/^\.cursor\/(hooks|agents)\//.test(p)) return true;

  // Root contract files.
  if (/^(package\.json|playwright\.config\..*|playwright\.config\.js|AGENTS\.md|OPERATING-MODEL\.md)$/.test(p)) {
    return true;
  }

  return false;
}

function shouldNag({ statePath, filePath, nowMs }) {
  // Cooldown: do not inject the same pipeline reminder on every save.
  const state = readJsonFile(statePath, {});
  const lastMs = typeof state.last_verify_nag_ts === "number" ? state.last_verify_nag_ts : 0;

  const cooldownMs = 10 * 60 * 1000; // 10 minutes
  const withinCooldown = nowMs - lastMs < cooldownMs;

  if (withinCooldown) return { ok: false, state };
  return { ok: true, state };
}

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  fs.mkdirSync(stateDir, { recursive: true });
  const nagStatePath = path.join(stateDir, "nag-state.json");
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

  const nowMs = Date.now();
  const nag = shouldNag({ statePath: nagStatePath, filePath, nowMs });
  if (!nag.ok) {
    writeJson({});
    return;
  }
  writeJsonFile(nagStatePath, {
    ...nag.state,
    last_verify_nag_ts: nowMs,
    last_verify_nag_path: filePath,
  });

  // This doesn't run subagents directly, but it forces a follow-up instruction
  // into the conversation context so the agent cannot "forget" the gate.
  writeJson({
    additional_context:
      "Изменены файлы в зоне runtime/process. Разделяй верификацию: (A) практическая — сделал ли по смыслу то, что просил Owner (всегда); (B) техническая — verify/tests/артефакты (только если требуется по зоне/политике/детекторам). Пайплайн: Critic PHASE=1 → фиксы (Doer) → Verify по зоне (дефолт: `npm run verify:changed` + `npm run verify:l0`; L1 Woo-flow — только если Woo-зона или форс `VERIFY_L1=1`; a11y — по детектору/форсу) → Verifier (технический) → Critic PHASE=2 (включая практическую проверку)."
  });
}

main().catch(() => writeJson({}));

