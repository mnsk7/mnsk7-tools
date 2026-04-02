// @ts-check
/**
 * PLP layout: desktop = table only, mobile (user-agent) = grid only.
 * Layout is chosen on the server (mnsk7_is_mobile_request()), so we check DOM content.
 */
const { test, expect } = require('@playwright/test');

const SHOP_URL = '/sklep/';

test.describe('PLP layout (server-side) — desktop UA', () => {
  test.use({
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    viewport: { width: 1280, height: 800 },
  });

  test.skip(({ isMobile }) => isMobile, 'Desktop-UA assertions are not applicable to mobile projects.');

  test('table present, mobile grid absent', async ({ page }) => {
    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded', timeout: 60_000 });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');

    await expect(tableWrap).toBeVisible();
    await expect(gridMobile).toHaveCount(0);
  });

  test('variable products in table route to PDP instead of fake add-to-cart', async ({ page }) => {
    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded', timeout: 60_000 });

    const variableRow = page.locator('.mnsk7-product-table tbody tr.product-type-variable').first();
    if (!(await variableRow.count())) {
      test.skip(true, 'No variable product row found in current PLP dataset.');
      return;
    }

    const qtyCell = variableRow.locator('.mnsk7-table-cell--qty');
    const actionLink = variableRow.locator('.mnsk7-table-cell--action a.mnsk7-table-addcart-btn').first();
    const actionForm = variableRow.locator('.mnsk7-table-cell--action form.mnsk7-table-addcart-form');

    await expect(actionLink).toBeVisible();
    await expect(actionLink).toHaveText(/Wybierz opcje/i);
    await expect(actionForm).toHaveCount(0);
    await expect(qtyCell).toContainText('—');
  });
});

test.describe('PLP layout (server-side) — mobile UA', () => {
  test.use({
    userAgent:
      'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    viewport: { width: 375, height: 700 },
    isMobile: true,
    hasTouch: true,
  });

  test('grid present, table absent', async ({ page }) => {
    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded', timeout: 60_000 });

    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');
    const tableWrap = page.locator('.mnsk7-product-table-wrap');

    await expect(gridMobile).toBeVisible();
    await expect(tableWrap).toHaveCount(0);
  });
});

test.describe('PLP category layout', () => {
  test.use({
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
  });

  test.skip(({ isMobile }) => isMobile, 'Desktop-UA assertions are not applicable to mobile projects.');

  test('desktop UA on category: table visible', async ({ page }) => {
    await page.goto('/sklep/', { waitUntil: 'domcontentloaded', timeout: 60_000 });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');
    await expect(tableWrap).toBeVisible();
    await expect(gridMobile).toHaveCount(0);
  });
});
