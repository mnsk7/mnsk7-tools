// @ts-check
/**
 * A11y smoke (axe) for key pages.
 * L0/L1 depending on scope, but используется как ранний блокер для критичных страниц.
 *
 * Запуск:
 *   BASE_URL=https://staging.mnsk7-tools.pl npx playwright test e2e/a11y-smoke.spec.js --project=chromium
 */
const { test, expect } = require('@playwright/test');
const { AxeBuilder } = require('@axe-core/playwright');

// By default we check only checkout, because it's the highest-risk conversion surface.
// You can expand coverage by setting A11Y_PAGES=home,shop,cart,checkout
const ALL_PAGES = {
  home: { name: 'home', path: '/' },
  shop: { name: 'shop', path: '/sklep/' },
  cart: { name: 'cart', path: '/koszyk/' },
  checkout: { name: 'checkout', path: '/zamowienie/' },
};

const selectedKeys = (process.env.A11Y_PAGES || 'checkout')
  .split(',')
  .map((s) => s.trim())
  .filter(Boolean);

const PAGES = selectedKeys
  .map((k) => ALL_PAGES[k])
  .filter(Boolean);

async function tryFetchText(request, url) {
  try {
    const res = await request.get(url, { timeout: 15000 });
    if (!res.ok()) return null;
    return await res.text();
  } catch {
    return null;
  }
}

function firstMatchingLoc(xmlText, re) {
  if (!xmlText) return null;
  const locRe = /<loc>\s*([^<]+)\s*<\/loc>/gi;
  let m;
  while ((m = locRe.exec(xmlText))) {
    const loc = String(m[1]).trim();
    if (re.test(loc)) return loc;
  }
  return null;
}

async function discoverProductUrl({ request, baseURL }) {
  const wpSitemap = await tryFetchText(request, new URL('/wp-sitemap.xml', baseURL).toString());
  const productSitemapUrl =
    firstMatchingLoc(wpSitemap, /wp-sitemap-posts-product-\d+\.xml/i) ||
    firstMatchingLoc(wpSitemap, /product/i);

  if (productSitemapUrl) {
    const productSitemap = await tryFetchText(request, productSitemapUrl);
    const productLoc =
      firstMatchingLoc(productSitemap, /\/produkt\//i) ||
      firstMatchingLoc(productSitemap, /\/product\//i);
    if (productLoc) return productLoc;
  }

  const seoSitemap = await tryFetchText(request, new URL('/sitemap_index.xml', baseURL).toString());
  const seoProductSitemapUrl = firstMatchingLoc(seoSitemap, /product/i);
  if (seoProductSitemapUrl) {
    const seoProductSitemap = await tryFetchText(request, seoProductSitemapUrl);
    const productLoc =
      firstMatchingLoc(seoProductSitemap, /\/produkt\//i) ||
      firstMatchingLoc(seoProductSitemap, /\/product\//i);
    if (productLoc) return productLoc;
  }

  const genericSitemap = await tryFetchText(request, new URL('/sitemap.xml', baseURL).toString());
  const genericProductLoc =
    firstMatchingLoc(genericSitemap, /\/produkt\//i) ||
    firstMatchingLoc(genericSitemap, /\/product\//i);
  if (genericProductLoc) return genericProductLoc;

  return null;
}

async function gotoWithRetries(page, url, { waitUntil = 'domcontentloaded', timeout = 60_000, retries = 2 } = {}) {
  let lastErr = null;
  for (let i = 0; i <= retries; i++) {
    try {
      await page.goto(url, { waitUntil, timeout });
      return;
    } catch (e) {
      lastErr = e;
      await page.waitForTimeout(500);
    }
  }
  throw lastErr;
}

function extractAddToCartIdFromUrl(href) {
  try {
    const u = new URL(href, 'https://example.invalid');
    const id = u.searchParams.get('add-to-cart');
    return id && /^\d+$/.test(id) ? id : null;
  } catch {
    return null;
  }
}

async function discoverAddToCartId(page) {
  const input = page.locator('input[name="add-to-cart"][value], input[name="product_id"][value]').first();
  if (await input.count()) {
    const v = await input.getAttribute('value');
    if (v && /^\d+$/.test(v)) return v;
  }

  const dataIdBtn = page.locator('[data-product_id]').first();
  if (await dataIdBtn.count()) {
    const v = await dataIdBtn.getAttribute('data-product_id');
    if (v && /^\d+$/.test(v)) return v;
  }

  const addHref = await page.evaluate(() => {
    const a = document.querySelector('a[href*="add-to-cart="]');
    return a ? a.getAttribute('href') : null;
  });
  if (addHref) {
    const id = extractAddToCartIdFromUrl(addHref);
    if (id) return id;
  }

  return null;
}

test.describe('A11y smoke (axe)', () => {
  for (const p of PAGES) {
    test(`${p.name}: no serious/critical violations`, async ({ page, baseURL }) => {
      test.setTimeout(120_000);
      // Checkout requires a non-empty cart on staging; otherwise Woo redirects /zamowienie/ → /koszyk/.
      if (p.name === 'checkout') {
        const discovered = await discoverProductUrl({ request: page.request, baseURL });
        if (discovered) {
          await gotoWithRetries(page, discovered, { timeout: 60_000, retries: 2 });
        } else {
          await gotoWithRetries(page, new URL('/sklep/', baseURL).toString(), { timeout: 60_000, retries: 2 });
        }

        // Prefer deterministic server-side add-to-cart by extracting product_id from the page markup.
        const addToCartId = await discoverAddToCartId(page);
        if (!addToCartId) {
          throw new Error('Could not discover add-to-cart product_id for a11y checkout pre-step.');
        }
        await gotoWithRetries(page, new URL(`/?add-to-cart=${addToCartId}`, baseURL).toString(), { timeout: 60_000, retries: 2 });

        await gotoWithRetries(page, new URL(p.path, baseURL).toString(), { timeout: 60_000, retries: 2 });
        const checkoutForm = page.locator('form.woocommerce-checkout, form[name="checkout"]').first();
        await expect(checkoutForm).toBeVisible({ timeout: 15000 });
      } else {
        await gotoWithRetries(page, new URL(p.path, baseURL).toString(), { timeout: 60_000, retries: 2 });
      }

      const results = await new AxeBuilder({ page }).analyze();
      const serious = results.violations.filter((v) => v.impact === 'serious' || v.impact === 'critical');
      expect(serious, JSON.stringify(serious.slice(0, 3), null, 2)).toEqual([]);
    });
  }
});

