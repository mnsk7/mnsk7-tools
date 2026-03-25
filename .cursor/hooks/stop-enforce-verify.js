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

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  const editsLog = path.join(stateDir, "file-edits.log");
  const subagentLog = path.join(stateDir, "subagent-events.log");

  fs.mkdirSync(stateDir, { recursive: true });
  fs.appendFileSync(
    path.join(stateDir, "raw-events.ndjson"),
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "stop", input })}\n`,
    "utf8"
  );

  const lastEdit = parseLastTimestampFromLog(editsLog);
  const lastVerifier = parseLastSubagentTime(subagentLog, "verifier");
  const lastCritic = parseLastSubagentTime(subagentLog, "critic-scorer");
  const lastDoer = parseLastSubagentTime(subagentLog, "doer");
  const lastCriticPhase = parseLastCriticPhase(subagentLog);

  // Pipeline discipline:
  // - After Critic PHASE=1 the next step is fixing PHASE=1 feedback (Doer), not forcing Verifier/Critic PHASE=2.
  // - We only enforce the "verifier -> critic (PHASE=2)" gate when the last critic observed was PHASE=2
  //   (or when we have no phase markers at all but we have verifier markers).
  const editAfterCritic = lastEdit != null && lastCritic != null && lastEdit > lastCritic;
  if (editAfterCritic && lastCriticPhase === 1) {
    writeJson({
      followup_message:
        "Были правки после `critic-scorer (PHASE=1)`. По пайплайну сейчас НЕ verifier/PHASE=2: сначала исправь замечания PHASE=1 (Doer/min safe diff), затем запусти Verify (L0/L1 по зоне; Woo-flow гоняй только если изменения реально в Woo-зоне или форсируй `VERIFY_L1=1`), и только потом `verifier` → `critic-scorer (PHASE=2)`."
    });
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

  writeJson({
    followup_message:
      "Похоже, были правки после последнего verifier/critic. Нельзя завершать шаг без гейта: запусти `verifier`, затем `critic-scorer (PHASE=2)` на текущем diff. Если outcome=REJECT/ESCALATE/critical/major — добавь запись в `docs/CRITIC_POSTMORTEMS.md`. После фиксов — повтори verifier+critic ещё раз."
  });
}

main().catch(() => writeJson({}));

