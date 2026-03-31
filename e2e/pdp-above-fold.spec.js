// @ts-check
const { test, expect } = require('@playwright/test');

const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

async function openFirstProduct(page) {
  await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

  const firstProduct = page
    .locator(
      '.mnsk7-product-table tbody .mnsk7-table-cell--title a, .mnsk7-plp-grid-mobile .products .product a, .products .product a'
    )
    .first();

  await expect(firstProduct).toBeVisible({ timeout: 10000 });
  const href = await firstProduct.getAttribute('href');
  expect(href).toBeTruthy();

  await page.goto(href, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('body.single-product')).toBeVisible({ timeout: 10000 });
}

async function isInViewport(page, selector) {
  return page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return false;
    const rect = el.getBoundingClientRect();
    return rect.top >= 0 && rect.top < window.innerHeight && rect.bottom <= window.innerHeight + 120;
  }, selector);
}

test.describe('PDP above-the-fold smoke', () => {
  test('375x812: title, price row and cart form are reachable above fold', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await openFirstProduct(page);

    await expect(page.locator('.summary .product_title').first()).toBeVisible();
    await expect(page.locator('.mnsk7-pdp-price-row').first()).toBeVisible();
    await expect(page.locator('.summary form.cart').first()).toBeVisible();

    expect(await isInViewport(page, '.summary .product_title')).toBe(true);
    expect(await isInViewport(page, '.mnsk7-pdp-price-row')).toBe(true);
  });

  test('1280x900: title, price row and cart form are visible without scroll jump', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    await openFirstProduct(page);

    await expect(page.locator('.summary .product_title').first()).toBeVisible();
    await expect(page.locator('.mnsk7-pdp-price-row').first()).toBeVisible();
    await expect(page.locator('.summary form.cart').first()).toBeVisible();
  });
});
