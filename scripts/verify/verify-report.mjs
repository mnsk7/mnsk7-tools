import fs from "node:fs";
import path from "node:path";

function readStdin() {
  return new Promise((resolve) => {
    let data = "";
    process.stdin.setEncoding("utf8");
    process.stdin.on("data", (c) => (data += c));
    process.stdin.on("end", () => resolve(data));
  });
}

function ensureDir(p) {
  fs.mkdirSync(p, { recursive: true });
}

function parseResultLines(text) {
  // run-all.sh and l0.sh print: "=== RESULT: <name>: PASS|FAIL|SKIP ..."
  const lines = String(text || "").split("\n");
  const steps = [];
  for (const line of lines) {
    const m = line.match(/^=== RESULT:\s*(.+?)\s*:\s*(PASS|FAIL|SKIP)\b/i);
    if (m) steps.push({ name: m[1], status: m[2].toUpperCase() });
  }
  return steps;
}

function readPolicyFromEnv() {
  const requireA11y = process.env.REQUIRE_A11Y === "1";
  const allowSkipA11y = process.env.ALLOW_SKIP_A11Y === "1";
  const verifyA11y = process.env.VERIFY_A11Y === "1";
  const requireL1 = process.env.REQUIRE_L1 === "1";
  const allowSkipL1 = process.env.ALLOW_SKIP_L1 === "1";
  const verifyL1 = process.env.VERIFY_L1 === "1";
  const requirePhp = process.env.REQUIRE_PHP === "1";
  const verifyLinkcheck = process.env.VERIFY_LINKCHECK === "1";
  const verifyLighthouse = process.env.VERIFY_LIGHTHOUSE === "1";

  return {
    require: {
      a11y: requireA11y,
      "l1 (woo flow)": requireL1,
      "l0:php-lint": requirePhp,
      "l0:link-check": verifyLinkcheck,
      "l0:lighthouse-smoke": verifyLighthouse,
    },
    allow_skip: {
      a11y: allowSkipA11y,
      "l1 (woo flow)": allowSkipL1,
    },
    forced: {
      a11y: verifyA11y,
      "l1 (woo flow)": verifyL1,
    },
  };
}

function buildReport({ baseUrl, stdout, exitCode }) {
  const steps = parseResultLines(stdout);
  const failed = steps.filter((s) => s.status === "FAIL").map((s) => s.name);
  const skipped = steps.filter((s) => s.status === "SKIP").map((s) => s.name);
  const passed = steps.filter((s) => s.status === "PASS").map((s) => s.name);

  const policy = readPolicyFromEnv();
  const stepStatus = new Map(steps.map((s) => [s.name, s.status]));

  const requiredButSkipped = [];
  if (policy.require.a11y) {
    const st = stepStatus.get("a11y");
    if (st === "SKIP" && !policy.allow_skip.a11y) requiredButSkipped.push("a11y");
  }
  if (policy.require["l1 (woo flow)"]) {
    const st = stepStatus.get("l1 (woo flow)");
    if (st === "SKIP" && !policy.allow_skip["l1 (woo flow)"]) requiredButSkipped.push("l1 (woo flow)");
  }
  if (policy.require["l0:php-lint"]) {
    const st = stepStatus.get("l0:php-lint");
    if (st === "SKIP") requiredButSkipped.push("l0:php-lint");
  }
  if (policy.require["l0:link-check"]) {
    const st = stepStatus.get("l0:link-check");
    if (st === "SKIP") requiredButSkipped.push("l0:link-check");
  }
  if (policy.require["l0:lighthouse-smoke"]) {
    const st = stepStatus.get("l0:lighthouse-smoke");
    if (st === "SKIP") requiredButSkipped.push("l0:lighthouse-smoke");
  }

  return {
    generated_at: new Date().toISOString(),
    base_url: baseUrl || null,
    exit_code: Number.isFinite(exitCode) ? exitCode : null,
    policy,
    summary: {
      passed_count: passed.length,
      failed_count: failed.length,
      skipped_count: skipped.length,
    },
    steps,
    blocking: {
      failed_rules: failed,
      skipped_rules: requiredButSkipped,
    },
    note:
      "SKIP означает «не запускалось». В blocking.skipped_rules попадают только те SKIP, которые требуются по политике и не разрешены allow-флагами.",
  };
}

const stdout = await readStdin();
const baseUrl = process.env.BASE_URL || null;
const exitCodeRaw = process.env.VERIFY_ALL_EXIT_CODE;
const exitCode = exitCodeRaw != null ? Number(exitCodeRaw) : null;

const outDir = path.join(process.cwd(), "artifacts", "verify");
ensureDir(outDir);

const report = buildReport({ baseUrl, stdout, exitCode });
fs.writeFileSync(path.join(outDir, "verify-report.json"), JSON.stringify(report, null, 2) + "\n", "utf8");
