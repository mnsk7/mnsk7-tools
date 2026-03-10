// @ts-check
/**
 * PLP layout: desktop = table only, mobile (user-agent) = grid only.
 * Layout is chosen on the server (mnsk7_is_mobile_request()), so we check DOM content.
 */
const { test, expect } = require('@playwright/test');

const SHOP_URL = '/sklep/';

test.describe('PLP layout (server-side)', () => {
  test('desktop UA: table present, mobile grid absent', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0' });
    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded' });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');

    await expect(tableWrap).toBeVisible();
    await expect(gridMobile).toHaveCount(0);
  });

  test('mobile UA: grid present, table absent', async ({ page }) => {
    await page.setExtraHTTPHeaders({
      'User-Agent': 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 Mobile Chrome/120.0',
    });
    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded' });

    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');
    const tableWrap = page.locator('.mnsk7-product-table-wrap');

    await expect(gridMobile).toBeVisible();
    await expect(tableWrap).toHaveCount(0);
  });
});

test.describe('PLP category layout', () => {
  test('desktop UA on category: table visible', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0' });
    await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');
    await expect(tableWrap).toBeVisible();
    await expect(gridMobile).toHaveCount(0);
  });
});
