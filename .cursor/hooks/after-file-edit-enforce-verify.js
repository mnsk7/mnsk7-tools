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
    /^(\.cursor\/|\.codex\/|docs\/|tasks\/|scripts\/|e2e\/|wp-content\/|mu-plugins\/)/.test(p) ||
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

  writeJson({
    additional_context:
      "Изменены repo/process/runtime файлы. Следуй shared pipeline: оцени риск, сделай минимальный diff, выполни pre-push review и выбери verify depth по риску. Для Woo/deploy/runtime изменений после push в main нужна staging-проверка достаточной глубины."
  });
}

main().catch(() => writeJson({}));
