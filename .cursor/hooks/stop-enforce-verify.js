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

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  const editsLog = path.join(stateDir, "file-edits.log");
  const nagStatePath = path.join(stateDir, "nag-state.json");

  fs.mkdirSync(stateDir, { recursive: true });
  fs.appendFileSync(
    path.join(stateDir, "raw-events.ndjson"),
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "stop", input })}\n`,
    "utf8"
  );

  const lastEdit = parseLastTimestampFromLog(editsLog);

  let nagState = {};
  try {
    if (fs.existsSync(nagStatePath)) {
      nagState = JSON.parse(fs.readFileSync(nagStatePath, "utf8") || "{}");
    }
  } catch {
    nagState = {};
  }

  const lastNag = Number(nagState.shared_pipeline || 0);
  if (Number.isFinite(lastEdit) && Number.isFinite(lastNag) && lastNag >= lastEdit) {
    writeJson({});
    return;
  }

  nagState.shared_pipeline = Date.now();
  try {
    fs.writeFileSync(nagStatePath, JSON.stringify(nagState, null, 2) + "\n", "utf8");
  } catch {
    // ignore
  }

  writeJson({
    followup_message:
      "Перед завершением проверь shared workflow: scope под контролем, diff минимальный, verify depth соответствует риску. Для low-risk задач достаточно лёгкой проверки; для Woo/deploy/runtime изменений нужен staging-based verify после push в main."
  });
}

main().catch(() => writeJson({}));
