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

function parseLastTimestampFromLog(filePath) {
  if (!fs.existsSync(filePath)) return null;
  const content = fs.readFileSync(filePath, "utf8").trim();
  if (!content) return null;
  const lines = content.split("\n");
  const last = lines[lines.length - 1];
  const iso = last.split(" ")[0];
  const t = Date.parse(iso);
  return Number.isFinite(t) ? t : null;
}

function isProcessScope(filePath) {
  const p = String(filePath || "");

  // Avoid spam: docs/tasks are not runtime/process by default.
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

function parseLastProcessEditFromLog(filePath) {
  if (!fs.existsSync(filePath)) return null;
  const content = fs.readFileSync(filePath, "utf8").trim();
  if (!content) return null;
  const lines = content.split("\n");

  for (let i = lines.length - 1; i >= 0; i--) {
    const line = lines[i];
    // format: ISO <path>
    const firstSpace = line.indexOf(" ");
    if (firstSpace === -1) continue;
    const iso = line.slice(0, firstSpace).trim();
    const rest = line.slice(firstSpace + 1).trim();
    if (!rest) continue;

    // file-edits.log stores absolute paths; normalize to repo-relative when possible.
    const cwd = process.cwd();
    const rel = rest.startsWith(cwd + path.sep) ? rest.slice(cwd.length + 1) : rest;
    if (!isProcessScope(rel)) continue;

    const t = Date.parse(iso);
    if (Number.isFinite(t)) return t;
  }

  return null;
}

function parseLastSubagentTime(logPath, subagentName) {
  if (!fs.existsSync(logPath)) return null;
  const content = fs.readFileSync(logPath, "utf8").trim();
  if (!content) return null;
  const lines = content.split("\n");
  for (let i = lines.length - 1; i >= 0; i--) {
    const line = lines[i];
    const parts = line.split("|").map((s) => s.trim());
    // format: ISO | subagent_type | task
    const iso = parts[0];
    const name = parts[1];
    if (name === subagentName) {
      const t = Date.parse(iso);
      return Number.isFinite(t) ? t : null;
    }
  }
  return null;
}

function parseLastCriticPhase(logPath) {
  if (!fs.existsSync(logPath)) return null;
  const content = fs.readFileSync(logPath, "utf8").trim();
  if (!content) return null;
  const lines = content.split("\n");
  for (let i = lines.length - 1; i >= 0; i--) {
    const line = lines[i];
    const parts = line.split("|").map((s) => s.trim());
    // format: ISO | subagent_type | task
    const subagentType = parts[1];
    const task = parts.slice(2).join(" | ");
    if (subagentType !== "critic-scorer") continue;
    if (/PHASE\s*=\s*1\b/i.test(task)) return 1;
    if (/PHASE\s*=\s*2\b/i.test(task)) return 2;
    // Unknown critic invocation (no explicit phase)
    return null;
  }
  return null;
}

function getMtimeMs(filePath) {
  try {
    return fs.statSync(filePath).mtimeMs;
  } catch {
    return null;
  }
}

function maxFinite(...vals) {
  const nums = vals.filter((v) => Number.isFinite(v));
  return nums.length ? Math.max(...nums) : null;
}

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  const editsLog = path.join(stateDir, "file-edits.log");
  const subagentLog = path.join(stateDir, "subagent-events.log");
  const nagStatePath = path.join(stateDir, "nag-state.json");

  // Evidence files written by verify tooling (when present).
  const verifyAllLog = path.join(process.cwd(), "artifacts", "verify", "verify-all.log");
  const verifyReport = path.join(process.cwd(), "artifacts", "verify", "verify-report.json");

  fs.mkdirSync(stateDir, { recursive: true });
  fs.appendFileSync(
    path.join(stateDir, "raw-events.ndjson"),
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "stop", input })}\n`,
    "utf8"
  );

  const lastEdit = parseLastProcessEditFromLog(editsLog) ?? parseLastTimestampFromLog(editsLog);
  const lastVerifier = parseLastSubagentTime(subagentLog, "verifier");
  const lastCritic = parseLastSubagentTime(subagentLog, "critic-scorer");
  const lastDoer = parseLastSubagentTime(subagentLog, "doer");
  const lastCriticPhase = parseLastCriticPhase(subagentLog);

  const verifyEvidenceTime = maxFinite(getMtimeMs(verifyAllLog), getMtimeMs(verifyReport));

  // De-spam: remember when we last emitted each nag.
  let nagState = {};
  try {
    if (fs.existsSync(nagStatePath)) {
      nagState = JSON.parse(fs.readFileSync(nagStatePath, "utf8") || "{}");
    }
  } catch {
    nagState = {};
  }

  function alreadyNaggedSinceEdit(key) {
    const lastNag = Number(nagState[key] || 0);
    return Number.isFinite(lastEdit) && Number.isFinite(lastNag) && lastNag >= lastEdit;
  }

  function markNag(key) {
    nagState[key] = Date.now();
    try {
      fs.writeFileSync(nagStatePath, JSON.stringify(nagState, null, 2) + "\n", "utf8");
    } catch {
      // ignore
    }
  }

  // Pipeline discipline:
  // - After Critic PHASE=1 the next step is fixing PHASE=1 feedback (Doer), not forcing Verifier/Critic PHASE=2.
  // - We only enforce the "verifier -> critic (PHASE=2)" gate when the last critic observed was PHASE=2
  //   (or when we have no phase markers at all but we have verifier markers).
  const editAfterCritic = lastEdit != null && lastCritic != null && lastEdit > lastCritic;
  // Only nag about "PHASE=1 -> Doer -> Verify" if we DO NOT already have fresh verify evidence after critic.
  const hasVerifyAfterCritic =
    verifyEvidenceTime != null && lastCritic != null && verifyEvidenceTime > lastCritic;
  if (editAfterCritic && lastCriticPhase === 1 && !hasVerifyAfterCritic) {
    if (alreadyNaggedSinceEdit("phase1_after_critic")) {
      writeJson({});
      return;
    }
    writeJson({
      followup_message:
        "Были правки после `critic-scorer (PHASE=1)`. По пайплайну сейчас НЕ verifier/PHASE=2: сначала исправь замечания PHASE=1 (Doer/min safe diff). Дальше разделяй верификацию: (A) практическая — сделано ли по смыслу то, что просил Owner (всегда); (B) техническая — запускать verify/tests только если требуется по зоне/политике/детекторам (дефолт: `npm run verify:changed` + `npm run verify:l0`; L1 Woo-flow — только если Woo-зона или форс `VERIFY_L1=1`). И только потом `verifier` (технический) → `critic-scorer (PHASE=2)` (включая практическую проверку)."
    });
    markNag("phase1_after_critic");
    return;
  }

  // We enforce verifier/PHASE=2 gate only after Doer produced changes.
  // Otherwise (no Doer yet), we're still in analysis/planning and should not block on verifier.
  const editAfterDoer = lastEdit != null && lastDoer != null && lastEdit > lastDoer;
  if (!editAfterDoer) {
    writeJson({});
    return;
  }

  // If there was an edit after last verifier/critic(PHASE=2), force the gate.
  const missingMarkers = lastVerifier == null || lastCritic == null;
  const enforcePhase2Gate =
    lastEdit != null &&
    (missingMarkers ||
      (lastVerifier != null && lastEdit > lastVerifier) ||
      (lastCritic != null && lastEdit > lastCritic)) &&
    // If we can detect critic phase, only enforce for phase 2.
    (lastCriticPhase == null || lastCriticPhase === 2);

  if (!enforcePhase2Gate) {
    writeJson({});
    return;
  }

  if (alreadyNaggedSinceEdit("phase2_gate")) {
    writeJson({});
    return;
  }
  writeJson({
    followup_message:
      "Похоже, были правки после последнего verifier/critic. Нельзя завершать шаг без гейта. Разделяй верификацию: (A) практическая — соответствие запросу Owner (всегда); (B) техническая — verify/tests только если требуется по зоне/политике/детекторам (дефолт: `npm run verify:changed` + `npm run verify:l0`; L1 — только если Woo-зона или форс `VERIFY_L1=1`). Затем запусти `verifier` (технический) и `critic-scorer (PHASE=2)` (включая практическую проверку) на текущем diff. Если outcome=REJECT/ESCALATE/critical/major — добавь запись в `docs/CRITIC_POSTMORTEMS.md`. После фиксов — повтори verifier+critic ещё раз."
  });
  markNag("phase2_gate");
}

main().catch(() => writeJson({}));

