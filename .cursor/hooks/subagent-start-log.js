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

async function main() {
  const raw = await readStdin();
  const input = raw ? JSON.parse(raw) : {};

  const stateDir = path.join(process.cwd(), ".cursor", "hooks", "state");
  fs.mkdirSync(stateDir, { recursive: true });

  fs.appendFileSync(
    path.join(stateDir, "raw-events.ndjson"),
    `${JSON.stringify({ ts: new Date().toISOString(), hook: "subagentStart", input })}\n`,
    "utf8"
  );

  const line = [
    new Date().toISOString(),
    String(input.subagent_type || "unknown"),
    String(input.task || "").replace(/\s+/g, " ").trim()
  ].join(" | ");

  fs.appendFileSync(path.join(stateDir, "subagent-events.log"), `${line}\n`, "utf8");
  writeJson({ continue: true, permission: "allow" });
}

main().catch(() => writeJson({ continue: true, permission: "allow" }));

