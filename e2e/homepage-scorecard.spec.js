// @ts-check
const { test, expect } = require('@playwright/test');

const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

async function topOf(page, selector) {
  return page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    return el.getBoundingClientRect().top;
  }, selector);
}

test.describe('Homepage scorecard', () => {
  test('375x700: hero has one primary CTA and no hero media block', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await expect(page.locator('.mnsk7-hero__btn--primary')).toHaveCount(1);
    await expect(page.locator('.mnsk7-hero__link')).toHaveCount(1);
    await expect(page.locator('.mnsk7-hero__media')).toHaveCount(0);
  });

  test('375x700: hero secondary CTA points to a real destination', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 700 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const secondaryCta = page.locator('.mnsk7-hero__link').first();
    await expect(secondaryCta).toBeVisible();

    const href = await secondaryCta.getAttribute('href');
    expect(href).toBeTruthy();

    if (href.startsWith('#')) {
      await expect(page.locator(href).first()).toHaveCount(1);
      await expect(secondaryCta).toHaveAttribute('aria-controls', href.slice(1));
    } else {
      expect(href).toMatch(/\/sklep\/?|shop/);
    }
  });

  test('768x900: catalog after bestsellers, loyalty before trust after catalog', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 900 });
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const bestsellersTop = await topOf(page, '.mnsk7-section--bestsellers');
    const loyaltyTop = await topOf(page, '.mnsk7-section--loyalty');
    const trustTop = await topOf(page, '.mnsk7-section--trust');
    const catalogTop = await topOf(page, '.mnsk7-section--catalog');

    expect(bestsellersTop).not.toBeNull();
    expect(loyaltyTop).not.toBeNull();
    expect(trustTop).not.toBeNull();
    expect(catalogTop).not.toBeNull();

    expect(catalogTop).toBeGreaterThanOrEqual(bestsellersTop);
    expect(loyaltyTop).toBeGreaterThanOrEqual(catalogTop);
    expect(trustTop).toBeGreaterThanOrEqual(loyaltyTop);
  });

  test('1280x900: bestsellers block exposes a see-all continuation link', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await expect(page.locator('.mnsk7-bestsellers-more a')).toBeVisible();
  });
});
