// @ts-check
/**
 * Mobile design E2E: проверка всего мобильного дизайна — ничего не наезжает,
 * нет пустых мест вместо hero (hero виден, без огромного разрыва сверху), хедер адекватный.
 *
 * Проверки: хедер виден и с лого/контролами; hero виден и с контентом; #content не под хедером;
 * нет горизонтального скролла; PLP и футер видимы; порядок секций и отсутствие наложений.
 *
 * Строгие layout-проверки хедера (одна строка, overlap, clipping, cart в viewport, desktop regression,
 * visual regression): e2e/header-layout.spec.js
 *
 * Viewporty: 320, 360, 375, 390, 414 (height 700).
 * Запуск: BASE_URL=https://staging.mnsk7-tools.pl npx playwright test e2e/mobile-design.spec.js
 */
const { test, expect } = require('@playwright/test');

const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

const VIEWPORTS = [
  { width: 320, height: 700 },
  { width: 360, height: 700 },
  { width: 375, height: 700 },
  { width: 390, height: 700 },
  { width: 414, height: 700 },
];

/** Допуск в px при сравнении границ (субпиксель, округление). */
const OVERLAP_TOLERANCE = 2;

/**
 * Возвращает { top, bottom, left, right, height } для первого подходящего элемента или null.
 */
async function getRect(page, selector) {
  return page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return { top: r.top, bottom: r.bottom, left: r.left, right: r.right, height: r.height, width: r.width };
  }, selector);
}

/**
 * Нижняя граница «шапки»: промо-бар (если есть) или хедер. В viewport coordinates.
 */
async function getHeaderBottom(page) {
  return page.evaluate(() => {
    const promo = document.querySelector('.mnsk7-promo-bar');
    const header = document.querySelector('#masthead.mnsk7-header, .mnsk7-header');
    const promoBottom = promo ? promo.getBoundingClientRect().bottom : 0;
    const headerBottom = header ? header.getBoundingClientRect().bottom : 0;
    return Math.max(promoBottom, headerBottom);
  });
}

/**
 * Проверка: контент (main или hero) не заезжает под шапку.
 * contentTop должно быть >= headerBottom - tolerance.
 */
async function assertNoOverlapWithHeader(page, contentSelector) {
  const headerBottom = await getHeaderBottom(page);
  const contentRect = await getRect(page, contentSelector);
  if (!contentRect) return { ok: false, reason: `element not found: ${contentSelector}` };
  const ok = contentRect.top >= headerBottom - OVERLAP_TOLERANCE;
  return { ok, headerBottom, contentTop: contentRect.top, reason: ok ? null : 'content overlaps header' };
}

test.describe('Mobile design — header', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: header visible, has logo and controls`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const header = page.locator('#masthead.mnsk7-header, .mnsk7-header').first();
      await expect(header).toBeVisible();

      const brand = page.locator('.mnsk7-header__brand').first();
      await expect(brand).toBeVisible();

      const controls = page.locator('.mnsk7-header__controls, .mnsk7-header__nav').first();
      await expect(controls).toBeVisible();

      const rect = await getRect(page, '#masthead.mnsk7-header, .mnsk7-header');
      expect(rect).not.toBeNull();
      expect(rect.height).toBeGreaterThanOrEqual(44);
    });

    test(`viewport ${viewport.width}x${viewport.height}: header does not overlap main content`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const result = await assertNoOverlapWithHeader(page, '#content, .site-content');
      expect(result.ok, result.reason || `headerBottom=${result.headerBottom} contentTop=${result.contentTop}`).toBe(true);
    });
  }
});

test.describe('Mobile design — hero (strona główna)', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: hero visible and has content`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const hero = page.locator('.mnsk7-hero').first();
      await expect(hero).toBeVisible();

      const title = page.locator('.mnsk7-hero__title').first();
      await expect(title).toBeVisible();
      const titleText = await title.textContent();
      expect((titleText || '').trim().length).toBeGreaterThan(0);

      const rect = await getRect(page, '.mnsk7-hero');
      expect(rect).not.toBeNull();
      expect(rect.height).toBeGreaterThanOrEqual(80);
    });

    test(`viewport ${viewport.width}x${viewport.height}: hero does not overlap header`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const result = await assertNoOverlapWithHeader(page, '.mnsk7-hero');
      expect(result.ok, result.reason || `headerBottom=${result.headerBottom} heroTop=${result.contentTop}`).toBe(true);
    });

    test(`viewport ${viewport.width}x${viewport.height}: no huge empty strip above hero`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const headerBottom = await getHeaderBottom(page);
      const heroRect = await getRect(page, '.mnsk7-hero');
      if (!heroRect) {
        test.skip(true, 'brak .mnsk7-hero');
        return;
      }
      const gap = heroRect.top - headerBottom;
      expect(gap).toBeLessThanOrEqual(120);
      expect(gap).toBeGreaterThanOrEqual(-OVERLAP_TOLERANCE);
    });
  }
});

test.describe('Mobile design — no horizontal scroll', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: no horizontal scroll (overflow-x) on homepage`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const overflowX = await page.evaluate(() => {
        const body = document.body;
        const html = document.documentElement;
        const pageEl = document.getElementById('page');
        const bodyStyle = body ? getComputedStyle(body).overflowX : '';
        const htmlStyle = html ? getComputedStyle(html).overflowX : '';
        const pageStyle = pageEl ? getComputedStyle(pageEl).overflowX : '';
        return { body: bodyStyle, html: htmlStyle, page: pageStyle };
      });
      const hidden = (v) => ['hidden', 'clip'].includes(v);
      expect(hidden(overflowX.body) || hidden(overflowX.html) || hidden(overflowX.page)).toBe(true);
    });

    test(`viewport ${viewport.width}x${viewport.height}: no horizontal scroll on PLP`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const overflowX = await page.evaluate(() => {
        const body = document.body;
        const pageEl = document.getElementById('page');
        return { body: body ? getComputedStyle(body).overflowX : '', page: pageEl ? getComputedStyle(pageEl).overflowX : '' };
      });
      const hidden = (v) => ['hidden', 'clip'].includes(v);
      expect(hidden(overflowX.body) || hidden(overflowX.page)).toBe(true);
    });
  }
});

test.describe('Mobile design — PLP', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: PLP content visible, no overlap with header`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const result = await assertNoOverlapWithHeader(page, '#content, .site-content');
      expect(result.ok, result.reason || `headerBottom=${result.headerBottom} contentTop=${result.contentTop}`).toBe(true);

      const main = page.locator('#main, .site-main, .woocommerce').first();
      await expect(main).toBeVisible();
    });

    test(`viewport ${viewport.width}x${viewport.height}: PLP mobile grid or products area visible`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const gridOrProducts = page.locator('.mnsk7-plp-grid-mobile, .woocommerce ul.products, .mnsk7-product-table-wrap').first();
      await expect(gridOrProducts).toBeVisible({ timeout: 8000 });
    });
  }
});

test.describe('Mobile design — footer', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: footer visible on homepage`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const footer = page.locator('#colophon.mnsk7-footer, .mnsk7-footer').first();
      await expect(footer).toBeVisible();
    });

    test(`viewport ${viewport.width}x${viewport.height}: footer has columns and links`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const cols = page.locator('.mnsk7-footer__col, .mnsk7-footer__top');
      const count = await cols.count();
      expect(count).toBeGreaterThan(0);
    });
  }
});

test.describe('Mobile design — sections order (homepage)', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: hero → bestsellers → catalog → loyalty → trust`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const heroRect = await getRect(page, '.mnsk7-hero');
      const bestsellersRect = await getRect(page, '.mnsk7-section--bestsellers');
      const loyaltyRect = await getRect(page, '.mnsk7-section--loyalty');
      const trustRect = await getRect(page, '.mnsk7-section--trust');
      const catalogRect = await getRect(page, '.mnsk7-section--catalog, .mnsk7-catalog-chips');

      if (!heroRect) {
        test.skip(true, 'brak .mnsk7-hero');
        return;
      }
      if (bestsellersRect) expect(bestsellersRect.top).toBeGreaterThanOrEqual(heroRect.bottom - OVERLAP_TOLERANCE);
      if (catalogRect && bestsellersRect) expect(catalogRect.top).toBeGreaterThanOrEqual(bestsellersRect.bottom - OVERLAP_TOLERANCE);
      if (loyaltyRect && catalogRect) expect(loyaltyRect.top).toBeGreaterThanOrEqual(catalogRect.bottom - OVERLAP_TOLERANCE);
      if (trustRect && loyaltyRect) expect(trustRect.top).toBeGreaterThanOrEqual(loyaltyRect.bottom - OVERLAP_TOLERANCE);
    });
  }
});

test.describe('Mobile design — no overlapping blocks', () => {
  test('homepage: header, hero, bestsellers do not overlap', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 700 });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const headerBottom = await getHeaderBottom(page);
    const heroRect = await getRect(page, '.mnsk7-hero');
    const bestsellersRect = await getRect(page, '.mnsk7-section--bestsellers');

    expect(heroRect).not.toBeNull();
    expect(heroRect.top).toBeGreaterThanOrEqual(headerBottom - OVERLAP_TOLERANCE);
    if (bestsellersRect) {
      expect(bestsellersRect.top).toBeGreaterThanOrEqual(heroRect.bottom - OVERLAP_TOLERANCE);
    }
  });

  test('PLP: header and results area do not overlap', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

    const result = await assertNoOverlapWithHeader(page, '#content, .site-content');
    expect(result.ok, result.reason).toBe(true);
  });
});
