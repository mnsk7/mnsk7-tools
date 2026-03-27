// @ts-check
/**
 * Woo flow blocking tests (L1):
 * - add_to_cart works
 * - cart contains an item
 * - checkout entry loads and checkout form is visible
 *
 * Важно: мы НЕ оформляем заказ, только проверяем вход на checkout.
 *
 * Запуск:
 *   BASE_URL=https://staging.mnsk7-tools.pl npx playwright test e2e/woo-flow.spec.js --project=chromium
 *
 * Env:
 * - WOO_PRODUCT_PATH (optional): direct product path, e.g. /produkt/some-product/
 * - WOO_PLP_PATH (default: /sklep/) used only when WOO_PRODUCT_PATH not set
 */
const { test, expect } = require('@playwright/test');

const WOO_PRODUCT_PATH = process.env.WOO_PRODUCT_PATH || '';
const WOO_PLP_PATH = process.env.WOO_PLP_PATH || '/sklep/';

function urlJoin(base, path) {
  if (!base.endsWith('/')) base += '/';
  if (path.startsWith('/')) path = path.slice(1);
  return base + path;
}

async function gotoWithRetries(page, url, { waitUntil = 'domcontentloaded', timeout = 60_000, retries = 2 } = {}) {
  let lastErr = null;
  for (let i = 0; i <= retries; i++) {
    try {
      await page.goto(url, { waitUntil, timeout });
      return;
    } catch (e) {
      lastErr = e;
      try {
        if (page.isClosed()) break;
        await page.waitForTimeout(500);
      } catch {
        break;
      }
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
  const pdpButton = page.locator('button[name="add-to-cart"][value], form.cart button[name="add-to-cart"][value]').first();
  if (await pdpButton.count()) {
    const v = await pdpButton.getAttribute('value');
    if (v && /^\d+$/.test(v)) return v;
  }

  const hiddenAddToCart = page.locator('input[name="add-to-cart"][value], input[name="product_id"][value]').first();
  if (await hiddenAddToCart.count()) {
    const v = await hiddenAddToCart.getAttribute('value');
    if (v && /^\d+$/.test(v)) return v;
  }

  const dataIdBtn = page.locator('[data-product_id]').first();
  if (await dataIdBtn.count()) {
    const v = await dataIdBtn.getAttribute('data-product_id');
    if (v && /^\d+$/.test(v)) return v;
  }

  const valueBtn = page.locator('button[value]').first();
  if (await valueBtn.count()) {
    const v = await valueBtn.getAttribute('value');
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

  const evalId = await page.evaluate(() => {
    const input = document.querySelector('input[name="add-to-cart"][value], input[name="product_id"][value]');
    const vInput = input ? input.getAttribute('value') : null;
    if (vInput && /^\d+$/.test(vInput)) return vInput;

    const btn = document.querySelector('button[name="add-to-cart"][value], button[value][name="add-to-cart"]');
    const vBtn = btn ? btn.getAttribute('value') : null;
    return vBtn && /^\d+$/.test(vBtn) ? vBtn : null;
  });
  if (evalId) return evalId;

  return null;
}

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
  // WP core sitemap
  const wpSitemap = await tryFetchText(request, urlJoin(baseURL, '/wp-sitemap.xml'));
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

  // Common SEO sitemap fallback (Yoast/RankMath)
  const seoSitemap = await tryFetchText(request, urlJoin(baseURL, '/sitemap_index.xml'));
  const seoProductSitemapUrl = firstMatchingLoc(seoSitemap, /product/i);
  if (seoProductSitemapUrl) {
    const seoProductSitemap = await tryFetchText(request, seoProductSitemapUrl);
    const productLoc =
      firstMatchingLoc(seoProductSitemap, /\/produkt\//i) ||
      firstMatchingLoc(seoProductSitemap, /\/product\//i);
    if (productLoc) return productLoc;
  }

  const genericSitemap = await tryFetchText(request, urlJoin(baseURL, '/sitemap.xml'));
  const genericProductLoc =
    firstMatchingLoc(genericSitemap, /\/produkt\//i) ||
    firstMatchingLoc(genericSitemap, /\/product\//i);
  if (genericProductLoc) return genericProductLoc;

  return null;
}

async function selectFirstVariationOptions(page) {
  const selects = page.locator('form.variations_form table.variations select');
  const count = await selects.count();
  if (!count) return;
  for (let i = 0; i < count; i += 1) {
    const select = selects.nth(i);
    const chosen = await select.evaluate((el) => {
      const options = Array.from(el.querySelectorAll('option'));
      const candidate = options.find((o) => o.value && !o.disabled);
      if (!candidate) return null;
      el.value = candidate.value;
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return candidate.value;
    });
    if (chosen) {
      await page.waitForTimeout(150);
    }
  }
}

test.describe('WOO_FLOW (blocking)', () => {
  test('add_to_cart (server-side) → cart → checkout entry', async ({ page, baseURL }) => {
    // L1 should be fast and deterministic; avoid multi-minute hangs.
    test.setTimeout(90_000);
    const plpUrl = urlJoin(baseURL, WOO_PLP_PATH);
    const cartUrl = urlJoin(baseURL, '/koszyk/');
    const checkoutUrl = urlJoin(baseURL, '/zamowienie/');

    if (WOO_PRODUCT_PATH) {
      await gotoWithRetries(page, urlJoin(baseURL, WOO_PRODUCT_PATH), { timeout: 45_000, retries: 2 });
    } else {
      // Prefer deterministic sitemap discovery (works even if shop listing is empty or JS-rendered).
      const discovered = await discoverProductUrl({ request: page.request, baseURL });
      if (discovered) {
        await gotoWithRetries(page, discovered, { timeout: 45_000, retries: 2 });
      } else {
      // PLP: open listing (try a few common candidates)
      const candidates = [
        plpUrl,
        urlJoin(baseURL, '/?post_type=product'),
      ];
      let navigated = false;
      for (const u of candidates) {
        try {
          await gotoWithRetries(page, u, { timeout: 45_000, retries: 2 });
          navigated = true;
          break;
        } catch {
          // continue
        }
      }
      if (!navigated) {
        throw new Error(`Could not open any product listing page. Tried: ${candidates.join(', ')}`);
      }

      // We no longer need to click into PDP here.
      // For L1 we only need a deterministic product_id to add to cart (we'll extract it later).
      }
    }

    // Add to cart via server-side URL (more deterministic than clicking/JS ajax).
    let addToCartId = await discoverAddToCartId(page);
    if (!addToCartId) {
      const firstProductUrl = await page.evaluate(() => {
        const anchors = Array.from(document.querySelectorAll('a[href]'));
        for (const a of anchors) {
          const href = a.getAttribute('href') || '';
          // Prefer product detail URLs under /sklep/<slug>/
          if (/\/sklep\/[^/]+\/?$/.test(href) && !href.includes('/sklep/page/')) return href;
        }
        return null;
      });
      if (firstProductUrl) {
        await gotoWithRetries(page, new URL(firstProductUrl, baseURL).toString(), { timeout: 45_000, retries: 2 });
        addToCartId = await discoverAddToCartId(page);
      }
    }
    if (!addToCartId) {
      throw new Error('Could not discover add-to-cart product_id on PDP/listing.');
    }
    await gotoWithRetries(page, urlJoin(baseURL, `/?add-to-cart=${addToCartId}`), { timeout: 45_000, retries: 2 });

    // Cart: must contain at least one cart item row
    await gotoWithRetries(page, cartUrl, { timeout: 45_000, retries: 2 });
    const cartItems = page.locator('.cart_item, tr.woocommerce-cart-form__cart-item, .wc-block-cart-items__row');
    const count = await cartItems.count();
    if (count <= 0) {
      throw new Error(`Cart appears empty or cart markup not detected at ${cartUrl}.`);
    }

    // Checkout entry: page loads and checkout form exists
    await gotoWithRetries(page, checkoutUrl, { timeout: 45_000, retries: 2 });
    const checkoutForm = page.locator('form.woocommerce-checkout, form[name="checkout"]').first();
    await expect(checkoutForm).toBeVisible({ timeout: 15000 });
  });

  test('add_to_cart (PLP, server-side id from listing) → cart contains item', async ({ page, baseURL }) => {
    test.setTimeout(90_000);
    const plpUrl = urlJoin(baseURL, WOO_PLP_PATH);
    const cartUrl = urlJoin(baseURL, '/koszyk/');

    await gotoWithRetries(page, plpUrl, { timeout: 45_000, retries: 2 });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    const addToCartId = await discoverAddToCartId(page);
    if (!addToCartId) {
      throw new Error('Could not discover add-to-cart product_id on PLP.');
    }
    await gotoWithRetries(page, urlJoin(baseURL, `/?add-to-cart=${addToCartId}`), { timeout: 45_000, retries: 2 });

    await gotoWithRetries(page, cartUrl, { timeout: 45_000, retries: 2 });
    const cartItems = page.locator('.cart_item, tr.woocommerce-cart-form__cart-item, .wc-block-cart-items__row');
    const count = await cartItems.count();
    if (count <= 0) {
      throw new Error(`Cart appears empty or cart markup not detected at ${cartUrl}.`);
    }
  });

  test('cart_update: quantity change keeps item present (classic cart)', async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const plpUrl = urlJoin(baseURL, WOO_PLP_PATH);
    const cartUrl = urlJoin(baseURL, '/koszyk/');

    // Ensure deterministic product_id by looking at PLP.
    await gotoWithRetries(page, plpUrl, { timeout: 45_000, retries: 2 });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    const addToCartId = await discoverAddToCartId(page);
    if (!addToCartId) {
      throw new Error('Could not discover add-to-cart product_id for cart_update test (from PLP).');
    }
    await gotoWithRetries(page, urlJoin(baseURL, `/?add-to-cart=${addToCartId}`), { timeout: 45_000, retries: 2 });

    await gotoWithRetries(page, cartUrl, { timeout: 45_000, retries: 2 });

    const qty = page.locator('input.qty').first();
    if (!(await qty.count())) {
      test.skip(true, 'Classic cart qty input not found (block cart or different markup).');
      return;
    }

    await qty.fill('2');
    const updateBtn = page
      .locator('button[name=\"update_cart\"], button:has-text(\"Zaktualizuj koszyk\"), button:has-text(\"Aktualizuj koszyk\")')
      .first();
    if (await updateBtn.count()) {
      await updateBtn.click();
    }
    await expect(qty).toHaveValue('2', { timeout: 15000 });

    const cartItems = page.locator('.cart_item, tr.woocommerce-cart-form__cart-item, .wc-block-cart-items__row');
    const count = await cartItems.count();
    if (count <= 0) {
      throw new Error(`Cart appears empty or cart markup not detected at ${cartUrl}.`);
    }
  });

  test('pdp_mobile_add_to_cart: mobile PDP click path adds item', async ({ page, baseURL }) => {
    test.setTimeout(120_000);
    const cartUrl = urlJoin(baseURL, '/koszyk/');

    await page.setViewportSize({ width: 390, height: 844 });

    const discovered = await discoverProductUrl({ request: page.request, baseURL });
    if (!discovered) {
      test.skip(true, 'No product URL discovered from sitemap.');
      return;
    }

    await gotoWithRetries(page, discovered, { timeout: 45_000, retries: 2 });
    await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => {});

    if (await page.locator('form.variations_form').count()) {
      await selectFirstVariationOptions(page);
      await page.waitForTimeout(250);
    }

    const addButton = page.locator('form.cart .single_add_to_cart_button').first();
    await expect(addButton).toBeVisible({ timeout: 15000 });
    await addButton.click();
    await page.waitForTimeout(800);

    await gotoWithRetries(page, cartUrl, { timeout: 45_000, retries: 2 });
    const cartItems = page.locator('.cart_item, tr.woocommerce-cart-form__cart-item, .wc-block-cart-items__row');
    const count = await cartItems.count();
    if (count <= 0) {
      throw new Error(`Cart appears empty after mobile PDP add-to-cart at ${cartUrl}.`);
    }
  });
});

