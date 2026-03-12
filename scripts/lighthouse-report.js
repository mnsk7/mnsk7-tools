#!/usr/bin/env node
/**
 * Краткая сводка Lighthouse из JSON.
 * Использование: node scripts/lighthouse-report.js [путь/к/report.json]
 * По умолчанию: docs/lighthouse-archive-after.json
 */
const path = require('path');
const fs = require('fs');

const file = process.argv[2] || path.join(__dirname, '../docs/lighthouse-archive-after.json');
if (!fs.existsSync(file)) {
  console.error('Файл не найден:', file);
  process.exit(1);
}

const d = JSON.parse(fs.readFileSync(file, 'utf8'));
const c = d.categories || {};
const a = d.audits || {};

function val(id) {
  const x = a[id];
  return x && x.displayValue ? x.displayValue : (x && x.numericValue != null ? String(x.numericValue) + (x.numericUnit ? ' ' + x.numericUnit : '') : '–');
}

console.log('=== Lighthouse:', d.finalUrl || d.requestedUrl, '===');
console.log('Дата:', d.fetchTime);

console.log('\nОценки (0–1):');
if (c.performance) console.log('  Performance:    ', c.performance.score);
if (c.accessibility) console.log('  Accessibility:  ', c.accessibility.score);
if (c['best-practices']) console.log('  Best Practices:', c['best-practices'].score);
if (c.seo) console.log('  SEO:            ', c.seo.score);

console.log('\nМетрики:');
const metricIds = ['first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift', 'speed-index'];
metricIds.forEach(id => {
  const title = (a[id] && a[id].title) || id;
  console.log('  ', title, ':', val(id));
});

console.log('\nПровалы (score < 1), топ-15:');
const failures = Object.entries(a)
  .filter(([, v]) => v && v.score != null && v.score < 1)
  .sort((a, b) => a[1].score - b[1].score)
  .slice(0, 15);
failures.forEach(([k, v]) => console.log('  ', k, ':', v.score, v.displayValue || ''));
