// @ts-check
/**
 * PLP + ?filter_*: ten sam header/layout/body_class dla tej samej strony archiwum z filtrem i bez.
 * Kryteria: filtered URLs nie przełączają na inny header/layout; brak „wycieku” tabeli desktop na mobile.
 *
 * Wymaga: przynajmniej jedna kategoria produktów (domyślnie product-category/frezy-cnc).
 * Zmienna PLP_CATEGORY_SLUG nadpisuje slug kategorii.
 */
const { test, expect } = require('@playwright/test');

const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0';
const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 Mobile Chrome/120.0';

const CATEGORY_SLUG = process.env.PLP_CATEGORY_SLUG || 'frezy-cnc';
const CATEGORY_URL = `/product-category/${CATEGORY_SLUG}/`;
const FILTER_PARAM = 'filter_srednica';
const FILTER_VALUE = '8';

function getBodyClasses(page) {
  return page.locator('body').getAttribute('class').then((c) => (c || '').trim().split(/\s+/));
}

async function resolveCategoryUrl(page) {
  if (process.env.PLP_CATEGORY_SLUG) {
    return CATEGORY_URL;
  }

  await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });
  const href = await page.evaluate(() => {
    const links = Array.from(document.querySelectorAll('a[href]'));
    const hit = links.find((a) => {
      const val = a.getAttribute('href') || '';
      return /\/(kategoria-produktu|product-category)\//.test(val);
    });
    return hit ? hit.getAttribute('href') : null;
  });

  if (!href) {
    return CATEGORY_URL;
  }
  return href.startsWith('/') ? href : new URL(href, 'https://staging.mnsk7-tools.pl').pathname;
}

test.describe('PLP filter URL parity — category', () => {
  let categoryUrl = CATEGORY_URL;

  test.beforeEach(async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });
    categoryUrl = await resolveCategoryUrl(page);
  });

  test('category bez filtra vs z ?filter_*: ten sam zestaw krytycznych body classes (desktop UA)', async ({
    page,
  }) => {
    await page.goto(categoryUrl, { waitUntil: 'domcontentloaded' });
    const classesUnfiltered = await getBodyClasses(page);

    await page.goto(categoryUrl + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });
    const classesFiltered = await getBodyClasses(page);

    const critical = ['tax-product_cat', 'post-type-archive-product', 'woocommerce', 'woocommerce-page'];
    for (const cls of critical) {
      expect(classesFiltered, `body class "${cls}" na stronie z ?${FILTER_PARAM} powinien być taki sam jak bez filtra`).toContain(cls);
      expect(classesUnfiltered).toContain(cls);
    }
  });

  test('category bez filtra vs z ?filter_*: ten sam header (masthead) i wrapper (desktop UA)', async ({
    page,
  }) => {
    await page.goto(categoryUrl, { waitUntil: 'domcontentloaded' });
    const mastheadUnfiltered = await page.locator('#masthead.mnsk7-header').count();
    const wrapUnfiltered = await page.locator('.mnsk7-plp-archive-wrap').count();

    await page.goto(categoryUrl + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });
    const mastheadFiltered = await page.locator('#masthead.mnsk7-header').count();
    const wrapFiltered = await page.locator('.mnsk7-plp-archive-wrap').count();

    expect(mastheadFiltered).toBe(1);
    expect(mastheadUnfiltered).toBe(1);
    expect(wrapFiltered).toBe(1);
    expect(wrapUnfiltered).toBe(1);
  });

  test('category z ?filter_* (desktop UA): tabela lub empty state, NIE siatka mobilna', async ({
    page,
  }) => {
    await page.goto(categoryUrl + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });

    const gridMobile = page.locator('.mnsk7-plp-grid-mobile');
    await expect(gridMobile).toHaveCount(0);
  });

  test('category z ?filter_* (mobile UA): siatka lub empty state, NIE tabela', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto(categoryUrl + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    await expect(tableWrap).toHaveCount(0);
  });
});

test.describe('PLP filter URL parity — shop', () => {
  const SHOP_URL = '/sklep/';

  test('sklep bez filtra vs z ?filter_*: ten sam header i wrapper (desktop UA)', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': DESKTOP_UA });

    await page.goto(SHOP_URL, { waitUntil: 'domcontentloaded' });
    const mastheadUnfiltered = await page.locator('#masthead.mnsk7-header').count();
    const wrapUnfiltered = await page.locator('.mnsk7-plp-archive-wrap').count();

    await page.goto(SHOP_URL + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });
    const mastheadFiltered = await page.locator('#masthead.mnsk7-header').count();
    const wrapFiltered = await page.locator('.mnsk7-plp-archive-wrap').count();

    expect(mastheadFiltered).toBe(1);
    expect(mastheadUnfiltered).toBe(1);
    expect(wrapFiltered).toBe(1);
    expect(wrapUnfiltered).toBe(1);
  });

  test('sklep z ?filter_* (mobile UA): NIE tabela', async ({ page }) => {
    await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
    await page.goto(SHOP_URL + `?${FILTER_PARAM}=${FILTER_VALUE}`, { waitUntil: 'domcontentloaded' });

    const tableWrap = page.locator('.mnsk7-product-table-wrap');
    await expect(tableWrap).toHaveCount(0);
  });
});
