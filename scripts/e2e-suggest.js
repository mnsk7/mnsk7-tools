#!/usr/bin/env node
/**
 * Parse Playwright list reporter output and write summary + suggested fixes.
 * Usage: node scripts/e2e-suggest.js [log-file] [output-md]
 * Default: test-results/e2e-run.log -> test-results/e2e-suggestions.md
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const logPath = process.argv[2] || path.join(root, 'test-results', 'e2e-run.log');
const outPath = process.argv[3] || path.join(root, 'test-results', 'e2e-suggestions.md');

if (!fs.existsSync(logPath)) {
  console.error('Log file not found:', logPath);
  console.error('Run first: ./scripts/e2e-run-and-suggest.sh');
  process.exit(1);
}

const log = fs.readFileSync(logPath, 'utf8');
const lines = log.split('\n');

const failed = [];
const skipped = [];
let passedCount = 0;

for (const line of lines) {
  if (line.includes(' ✓ ') || line.includes(' ✓    ')) passedCount++;
  if (line.includes(' ✘ ') || line.includes(' ✘    ')) {
    const m = line.match(/› (.+?)(?:\s+\(|$)/);
    if (m) failed.push(m[1].trim());
  }
  if ((line.includes(' × ') || line.includes(' ○ ')) && line.includes(' › ')) {
    const m = line.match(/› (.+?)(?:\s+\(|$)/);
    if (m) skipped.push(m[1].trim());
  }
}

const failedCount = (log.match(/✘/g) || []).length;

const SUGGESTIONS = {
  'Sklep dropdown does not break': 'Header row: test checks brand+nav bar height only (not inner). If still fails, dropdown may be in flow — relax tolerance or skip.',
  'open megamenu does not overlap': 'Megamenu vs cart: ensure dropdown has max-width or right margin so it does not overlap cart. CSS: .mnsk7-megamenu { max-width: ... }',
  'header row geometry': 'Desktop: .mnsk7-header__inner may grow when dropdown opens. Test now uses bar (brand+nav) height. Increase +8 tolerance if needed.',
  'toHaveScreenshot': 'Visual regression: update baselines: npx playwright test e2e/header-layout.spec.js --update-snapshots',
  'overlap': 'Overlap: check z-index and positioning; ensure no overlapping getBoundingClientRect. Inspect in DevTools.',
  'cart does not extend': 'Mobile: cart past viewport — reduce padding/gap or hide .mnsk7-header__cart-count on narrow widths.',
  'clipped': 'Clipped: header controls need overflow visible; avoid overflow:hidden on __inner; use min-width on actions.',
  'one row': 'Controls wrap: reduce gap/font on mobile or hide search label / account text on 320px.',
  'does not overlap': 'Layout: elements overlap. Adjust CSS (flex, gap, max-width) so rects do not intersect.',
};

let out = '# E2E run summary\n\n';
out += `- **Passed:** ${passedCount}\n`;
out += `- **Failed:** ${failedCount}\n`;
out += `- **Skipped:** ${skipped.length}\n\n`;

if (failed.length > 0) {
  out += '## Failed tests\n\n';
  const uniq = [...new Set(failed)];
  uniq.forEach((f) => {
    out += `- ${f}\n`;
  });
  out += '\n## Suggested fixes\n\n';
  for (const f of uniq) {
    for (const [key, suggestion] of Object.entries(SUGGESTIONS)) {
      if (f.includes(key)) {
        out += `- **${f.split(':')[0]}:** ${suggestion}\n\n`;
        break;
      }
    }
  }
}

if (skipped.length > 0) {
  out += '## Skipped tests\n\n';
  [...new Set(skipped)].slice(0, 20).forEach((s) => {
    out += `- ${s}\n`;
  });
  if (skipped.length > 20) out += `- ... and ${skipped.length - 20} more\n`;
}

fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, out);
console.log(out);
console.log(`\nSuggestions written to ${outPath}`);
