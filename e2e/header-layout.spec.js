// @ts-check
/**
 * Header layout E2E: строгие layout-проверки и visual regression.
 *
 * Mobile (320–430): одна строка controls, нет overlap/clipping, cart в viewport,
 * при нехватке места cart count может скрываться, но layout не ломается.
 *
 * Desktop (1024–1440): nav, search, account, cart не пересекаются; dropdown Sklep
 * не ломает геометрию; открытое megamenu не конфликтует с правыми actions.
 *
 * Screenshot assertions: mobile closed/open, desktop closed, desktop Sklep open.
 *
 * Запуск: BASE_URL=https://staging.mnsk7-tools.pl npx playwright test e2e/header-layout.spec.js
 * Обновить снапшоты: npx playwright test e2e/header-layout.spec.js --update-snapshots
 * Снапшоты: e2e/header-layout.spec.js-snapshots/ (коммитить в репо для CI).
 */
const { test, expect } = require('@playwright/test');

const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

const MOBILE_VIEWPORTS = [
  { width: 320, height: 700 },
  { width: 342, height: 700 },
  { width: 360, height: 700 },
  { width: 375, height: 700 },
  { width: 390, height: 700 },
  { width: 414, height: 700 },
  { width: 430, height: 700 },
];

const DESKTOP_VIEWPORTS = [
  { width: 1024, height: 768 },
  { width: 1280, height: 800 },
  { width: 1440, height: 900 },
];

const LAYOUT_TOLERANCE = 2;

function rectsOverlap(a, b, tolerance = 0) {
  return (
    a.left < b.right + tolerance &&
    a.right + tolerance > b.left &&
    a.top < b.bottom + tolerance &&
    a.bottom + tolerance > b.top
  );
}

/** Все элементы в одной строке: разброс по вертикали не больше maxVerticalSpread px. */
function sameRow(rects, maxVerticalSpread = 12) {
  if (rects.length < 2) return true;
  const tops = rects.map((r) => r.top);
  const bottoms = rects.map((r) => r.bottom);
  return Math.max(...tops) - Math.min(...tops) <= maxVerticalSpread &&
    Math.max(...bottoms) - Math.min(...bottoms) <= maxVerticalSpread;
}

/**
 * Возвращает rects для переданных селекторов (по порядку). Селектор может быть массивом — берётся первый найденный.
 */
async function getHeaderControlRects(page, selectors) {
  return page.evaluate((sels) => {
    const result = [];
    for (const sel of sels) {
      const q = Array.isArray(sel) ? sel : [sel];
      let el = null;
      for (const s of q) {
        el = document.querySelector(s);
        if (el) break;
      }
      if (!el) {
        result.push(null);
        continue;
      }
      const r = el.getBoundingClientRect();
      result.push({ top: r.top, bottom: r.bottom, left: r.left, right: r.right, width: r.width, height: r.height });
    }
    return result;
  }, selectors);
}

/** Селекторы mobile: brand, burger, search, account, cart (clickable areas). */
const MOBILE_SELECTORS = [
  ['.mnsk7-header__brand'],
  ['.mnsk7-header__menu-toggle', '.mnsk7-header__nav button'],
  ['.mnsk7-header__search-toggle', '.mnsk7-header__search-wrap button'],
  ['.mnsk7-header__link--account', 'a.mnsk7-header__link[href*="myaccount"]'],
  ['.mnsk7-header__cart', '.mnsk7-header__cart-trigger', 'a.cart-contents'],
];

/** Селекторы desktop: nav (Sklep link), search wrap, account, cart. */
const DESKTOP_SELECTORS = [
  ['.mnsk7-header__nav', '.mnsk7-header__menu'],
  ['.mnsk7-header__search-wrap', '#mnsk7-header-search'],
  ['.mnsk7-header__link--account'],
  ['.mnsk7-header__cart', '.mnsk7-header__cart-trigger'],
];

test.describe('Header layout — mobile', () => {
  for (const viewport of MOBILE_VIEWPORTS) {
    test(`${viewport.width}x${viewport.height}: all controls in one row`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 60_000 });

      const rects = await getHeaderControlRects(page, MOBILE_SELECTORS);
      const present = rects.filter(Boolean);
      expect(present.length).toBeGreaterThanOrEqual(3);

      const ok = sameRow(present, 16);
      expect(ok, `controls should be in one row (tops: ${present.map((r) => r.top).join(', ')})`).toBe(true);
    });

    test(`${viewport.width}x${viewport.height}: burger, search, account, cart do not overlap`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 60_000 });

      const rects = await getHeaderControlRects(page, MOBILE_SELECTORS);
      const present = rects.filter(Boolean);
      for (let i = 0; i < present.length; i++) {
        for (let j = i + 1; j < present.length; j++) {
          const overlap = rectsOverlap(present[i], present[j], LAYOUT_TOLERANCE);
          expect(overlap, `elements ${i} and ${j} should not overlap`).toBe(false);
        }
      }
    });

    test(`${viewport.width}x${viewport.height}: cart does not extend past right edge of viewport`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const rects = await getHeaderControlRects(page, MOBILE_SELECTORS);
      const cartRect = rects[4] || rects[rects.length - 1];
      if (!cartRect) {
        test.skip(true, 'cart element not found');
        return;
      }
      expect(cartRect.right).toBeLessThanOrEqual(viewport.width + LAYOUT_TOLERANCE);
    });

    test(`${viewport.width}x${viewport.height}: no control is clipped (all within viewport horizontally)`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const rects = await getHeaderControlRects(page, MOBILE_SELECTORS);
      for (let i = 0; i < rects.length; i++) {
        if (!rects[i]) continue;
        expect(rects[i].left).toBeGreaterThanOrEqual(-LAYOUT_TOLERANCE);
        expect(rects[i].right).toBeLessThanOrEqual(viewport.width + LAYOUT_TOLERANCE);
      }
    });

    test(`${viewport.width}x${viewport.height}: brand and actions fit in one row (inner does not wrap)`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const innerRect = await page.evaluate(() => {
        const el = document.querySelector('.mnsk7-header__inner');
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { top: r.top, bottom: r.bottom, left: r.left, right: r.right, height: r.height };
      });
      expect(innerRect).not.toBeNull();
      const headerRect = await page.evaluate(() => {
        const el = document.querySelector('#masthead.mnsk7-header, .mnsk7-header');
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return r.height;
      });
      expect(innerRect.height).toBeLessThanOrEqual(headerRect + LAYOUT_TOLERANCE);
    });

    test(`${viewport.width}x${viewport.height}: cart trigger keeps accessibility contract`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 60_000 });

      const cartTrigger = page.locator('.mnsk7-header__cart-trigger, a.cart-contents').first();
      await expect(cartTrigger).toBeVisible();
      await expect(cartTrigger).toHaveAttribute('aria-controls', 'mnsk7-header-cart-dropdown');
      await expect(cartTrigger).toHaveAttribute('aria-expanded', 'false');
    });

    test(`${viewport.width}x${viewport.height}: Sklep submenu opens from the first item, not mid-list`, async ({ page }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 60_000 });

      const menuToggle = page.locator('.mnsk7-header__menu-toggle').first();
      const sklepTrigger = page.locator('.mnsk7-menu-item-sklep').first();
      await expect(menuToggle).toBeVisible();
      await menuToggle.click();
      await expect(sklepTrigger).toBeVisible();
      await sklepTrigger.click();

      const metrics = await page.evaluate(() => {
        const menu = document.getElementById('mnsk7-primary-menu');
        const parentLi = menu ? menu.querySelector('li.menu-item-has-children') : null;
        const submenu = parentLi ? parentLi.querySelector(':scope > .sub-menu') : null;
        const firstLink = submenu ? submenu.querySelector('a[href]') : null;
        if (!menu || !parentLi || !submenu || !firstLink) return null;

        const menuRect = menu.getBoundingClientRect();
        const firstRect = firstLink.getBoundingClientRect();
        return {
          menuScrollTop: menu.scrollTop,
          parentOffsetTop: parentLi.offsetTop,
          firstLinkTopWithinMenu: firstRect.top - menuRect.top,
          firstLinkBottomWithinMenu: firstRect.bottom - menuRect.top,
          menuVisibleHeight: menu.clientHeight,
        };
      });

      if (!metrics) {
        test.skip(true, 'mobile Sklep submenu structure not found');
        return;
      }

      expect(metrics.menuScrollTop).toBeGreaterThanOrEqual(0);
      expect(metrics.menuScrollTop).toBeLessThanOrEqual(Math.max(0, metrics.parentOffsetTop + 16));
      expect(metrics.firstLinkTopWithinMenu).toBeGreaterThanOrEqual(0);
      expect(metrics.firstLinkTopWithinMenu).toBeLessThanOrEqual(140);
      expect(metrics.firstLinkBottomWithinMenu).toBeLessThanOrEqual(metrics.menuVisibleHeight);
    });
  }
});

test.describe('Header layout — desktop regression', () => {
  test.skip(({ isMobile }) => isMobile, 'Desktop header regression is not applicable to mobile projects.');
  for (const vp of DESKTOP_VIEWPORTS) {
    test(`${vp.width}x${vp.height}: nav, search, account, cart do not overlap`, async ({ page }) => {
      await page.setViewportSize(vp);
      await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const rects = await getHeaderControlRects(page, DESKTOP_SELECTORS);
      const present = rects.filter(Boolean);
      expect(present.length).toBeGreaterThanOrEqual(3);
      for (let i = 0; i < present.length; i++) {
        for (let j = i + 1; j < present.length; j++) {
          const overlap = rectsOverlap(present[i], present[j], LAYOUT_TOLERANCE);
          expect(overlap, `desktop elements ${i} and ${j} should not overlap`).toBe(false);
        }
      }
    });

    test(`${vp.width}x${vp.height}: Sklep dropdown does not break header row geometry`, async ({ page }) => {
      await page.setViewportSize(vp);
      await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const rowBottomBefore = await page.evaluate(() => {
        const firstLink = document.querySelector('.mnsk7-header__menu > li > a, .mnsk7-menu-item-sklep');
        const brand = document.querySelector('.mnsk7-header__brand');
        if (!firstLink || !brand) return null;
        const r = firstLink.getBoundingClientRect();
        const b = brand.getBoundingClientRect();
        return { rowBottom: Math.max(r.bottom, b.bottom), brandBottom: b.bottom };
      });
      if (!rowBottomBefore) {
        test.skip(true, 'desktop nav link not found');
        return;
      }

      await page.locator('.mnsk7-menu-item-sklep, a[href*="sklep"]').first().hover();
      await page.waitForTimeout(400);

      const rowBottomAfter = await page.evaluate(() => {
        const firstLink = document.querySelector('.mnsk7-header__menu > li > a, .mnsk7-menu-item-sklep');
        const brand = document.querySelector('.mnsk7-header__brand');
        if (!firstLink || !brand) return null;
        const r = firstLink.getBoundingClientRect();
        const b = brand.getBoundingClientRect();
        return Math.max(r.bottom, b.bottom);
      });
      expect(rowBottomAfter).toBeLessThanOrEqual(rowBottomBefore.rowBottom + 10);
    });

    test(`${vp.width}x${vp.height}: open megamenu does not overlap cart`, async ({ page }) => {
      await page.setViewportSize(vp);
      await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      await page.locator('.mnsk7-menu-item-sklep, a[href*="sklep"]').first().hover();
      await page.waitForTimeout(500);

      const { megamenuRect, cartRect } = await page.evaluate(() => {
        const megamenu = document.querySelector('.mnsk7-megamenu, #mnsk7-menu-submenu-sklep');
        const cart = document.querySelector('.mnsk7-header__cart');
        return {
          megamenuRect: megamenu ? megamenu.getBoundingClientRect() : null,
          cartRect: cart ? cart.getBoundingClientRect() : null,
        };
      });
      if (!megamenuRect) {
        test.skip(true, 'megamenu not found');
        return;
      }
      if (cartRect) {
        const m = { left: megamenuRect.left, right: megamenuRect.right, top: megamenuRect.top, bottom: megamenuRect.bottom };
        const c = { left: cartRect.left, right: cartRect.right, top: cartRect.top, bottom: cartRect.bottom };
        expect(rectsOverlap(m, c, LAYOUT_TOLERANCE), 'megamenu should not overlap cart').toBe(false);
      }
    });
  }
});

test.describe('Header — visual regression (screenshots)', () => {
  test('mobile closed: header snapshot', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium', 'Header visual snapshots are stored for chromium project only.');
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const header = page.locator('#masthead.mnsk7-header, .mnsk7-header').first();
    await expect(header).toHaveScreenshot('header-mobile-closed.png', { maxDiffPixels: 100 });
  });

  test('mobile open: header + menu snapshot', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium', 'Header visual snapshots are stored for chromium project only.');
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await page.locator('.mnsk7-header__menu-toggle').first().click();
    await page.waitForTimeout(350);

    const header = page.locator('#masthead.mnsk7-header, .mnsk7-header').first();
    await expect(header).toHaveScreenshot('header-mobile-open.png', { maxDiffPixels: 100 });
  });

  test('desktop closed: header snapshot', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium', 'Header visual snapshots are stored for chromium project only.');
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const header = page.locator('#masthead.mnsk7-header, .mnsk7-header').first();
    await expect(header).toHaveScreenshot('header-desktop-closed.png', { maxDiffPixels: 100 });
  });

  test('desktop Sklep open: header + megamenu snapshot', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium', 'Header visual snapshots are stored for chromium project only.');
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await page.locator('.mnsk7-menu-item-sklep, a[href*="sklep"]').first().hover();
    await page.waitForTimeout(400);

    const header = page.locator('#masthead.mnsk7-header, .mnsk7-header').first();
    await expect(header).toHaveScreenshot('header-desktop-sklep-open.png', { maxDiffPixels: 150 });
  });
});

test.describe('Header search contract', () => {
  test('375x700: search toggle opens mobile search panel', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const toggle = page.locator('.mnsk7-header__search-toggle').first();
    const panel = page.locator('#mnsk7-header-search-panel').first();

    await expect(toggle).toBeVisible();
    await expect(panel).toBeHidden();

    await toggle.click();
    await expect(panel).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
  });

  test('768x700: search toggle opens mobile search panel', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const toggle = page.locator('.mnsk7-header__search-toggle').first();
    const panel = page.locator('#mnsk7-header-search-panel').first();

    await expect(toggle).toBeVisible();
    await expect(panel).toBeHidden();

    await toggle.click();
    await expect(panel).toBeVisible();
  });

  test('1024x768: inline desktop search is visible and toggle is hidden', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await expect(page.locator('.mnsk7-header__search-toggle').first()).toBeHidden();
    await expect(page.locator('#mnsk7-header-search .mnsk7-header__search-form').first()).toBeVisible();
  });
});
