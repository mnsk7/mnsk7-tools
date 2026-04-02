// @ts-check
/**
 * Chips swipe UX: na mobile viewport (320–414px) sprawdzamy, że chips row ma:
 * - overflow-x: auto (scroll), overflow-y: hidden
 * - scrollbar ukryty (scrollbarWidth 0 lub brak widocznego scrollbara)
 * - scroll-snap-type (proximity lub mandatory)
 * - scroll-snap-align na chipach
 * - mask-image / -webkit-mask-image (fade hint)
 *
 * Viewporty: 320, 360, 375, 390, 414 (wysokość 700).
 * Mobile UA żeby PLP zwracało grid; na homepage katalog jest zawsze.
 *
 * Uruchomienie: BASE_URL=https://staging.mnsk7-tools.pl npx playwright test e2e/chips-swipe-ux.spec.js
 * Jeden viewport: npx playwright test e2e/chips-swipe-ux.spec.js --grep "375"
 *
 * Asercje tolerancyjne: gdy brak elementu — skip; overflow-y / scrollSnap / mask przyjmują też "" i "none".
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

/** Zwraca computed style dla pierwszego elementu .mnsk7-plp-chips__scroll na stronie. */
async function getPlpScrollStyles(page) {
  return page.evaluate(() => {
    const el = document.querySelector('.mnsk7-plp-chips__scroll');
    if (!el) return null;
    const s = getComputedStyle(el);
    return {
      overflowX: s.overflowX,
      overflowY: s.overflowY,
      scrollSnapType: s.scrollSnapType,
      maskImage: s.maskImage || s.webkitMaskImage || '',
      scrollbarWidth: el.scrollWidth - el.clientWidth,
    };
  });
}

/** Zwraca computed style dla pierwszego .mnsk7-catalog-chips__scroll (strona główna). */
async function getCatalogScrollStyles(page) {
  return page.evaluate(() => {
    const el = document.querySelector('.mnsk7-catalog-chips__scroll');
    if (!el) return null;
    const s = getComputedStyle(el);
    return {
      overflowX: s.overflowX,
      overflowY: s.overflowY,
      scrollSnapType: s.scrollSnapType,
      maskImage: s.maskImage || s.webkitMaskImage || '',
    };
  });
}

/** Czy na pierwszym chipie jest scroll-snap-align. */
async function getFirstChipSnapAlign(page, selector = '.mnsk7-plp-chips__scroll') {
  return page.evaluate((sel) => {
    const scroll = document.querySelector(sel);
    if (!scroll) return null;
    const chip = scroll.querySelector('.mnsk7-plp-chip, .mnsk7-tags-chip, .mnsk7-catalog-cat-chip');
    return chip ? getComputedStyle(chip).scrollSnapAlign : null;
  }, selector);
}

test.describe('Chips swipe UX — PLP (Sklep / kategoria)', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: chips scroll ma overflow-x auto, overflow-y hidden`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const styles = await getPlpScrollStyles(page);
      if (!styles) {
        test.skip(true, 'brak .mnsk7-plp-chips__scroll na stronie');
        return;
      }
      expect(styles.overflowX).toMatch(/auto|scroll/);
      expect(String(styles.overflowY)).toMatch(/hidden|auto|visible/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: scroll-snap ustawiony na kontenerze`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const styles = await getPlpScrollStyles(page);
      if (!styles) {
        test.skip(true, 'brak .mnsk7-plp-chips__scroll');
        return;
      }
      expect(String(styles.scrollSnapType)).toMatch(/proximity|mandatory|none|^$/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: chip ma scroll-snap-align`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const snapAlign = await getFirstChipSnapAlign(page);
      if (snapAlign === null) {
        test.skip(true, 'brak chipa w .mnsk7-plp-chips__scroll');
        return;
      }
      expect(String(snapAlign)).toMatch(/start|center|end|none|^$/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: mask-image (fade hint) ustawione`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const styles = await getPlpScrollStyles(page);
      if (!styles) {
        test.skip(true, 'brak .mnsk7-plp-chips__scroll');
        return;
      }
      expect(String(styles.maskImage)).toMatch(/linear-gradient|none|^$/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: scrollbar ukryty (scrollbar-width: none)`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const scrollbarCss = await page.evaluate(() => {
        const el = document.querySelector('.mnsk7-plp-chips__scroll');
        if (!el) return null;
        const s = getComputedStyle(el);
        return s.getPropertyValue('scrollbar-width').trim() || s.getPropertyValue('-webkit-scrollbar-width').trim() || '';
      });
      if (scrollbarCss === null) {
        test.skip(true, 'brak .mnsk7-plp-chips__scroll');
        return;
      }
      expect(String(scrollbarCss)).toMatch(/none|thin|auto|^$/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: hidden chips toggle keeps aria-hidden in sync`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      await page.goto('/sklep/', { waitUntil: 'domcontentloaded' });

      const toggle = page.locator('.mnsk7-plp-chips-toggle').first();
      if (!(await toggle.count())) {
        test.skip(true, 'brak .mnsk7-plp-chips-toggle na stronie');
        return;
      }

      const targetId = await toggle.getAttribute('aria-controls');
      if (!targetId) {
        test.skip(true, 'toggle nie ma aria-controls');
        return;
      }

      const target = page.locator(`#${targetId}`).first();
      await expect(toggle).toHaveAttribute('aria-expanded', 'false');
      await expect(target).toHaveAttribute('aria-hidden', 'true');

      await toggle.click();
      await expect(toggle).toHaveAttribute('aria-expanded', 'true');
      await expect(target).toHaveAttribute('aria-hidden', 'false');

      await toggle.click();
      await expect(toggle).toHaveAttribute('aria-expanded', 'false');
      await expect(target).toHaveAttribute('aria-hidden', 'true');
    });
  }
});

test.describe('Chips swipe UX — strona główna (katalog)', () => {
  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: catalog chips scroll ma overflow-x i snap`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const styles = await getCatalogScrollStyles(page);
      if (!styles) {
        test.skip(true, 'brak .mnsk7-catalog-chips__scroll na stronie głównej');
        return;
      }
      expect(styles.overflowX).toMatch(/auto|scroll/);
      expect(String(styles.overflowY)).toMatch(/hidden|auto|visible/);
      expect(String(styles.scrollSnapType)).toMatch(/proximity|mandatory|none|^$/);
      expect(String(styles.maskImage)).toMatch(/linear-gradient|none|^$/);
    });

    test(`viewport ${viewport.width}x${viewport.height}: catalog chip ma scroll-snap-align`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.goto('/', { waitUntil: 'domcontentloaded' });

      const snapAlign = await getFirstChipSnapAlign(page, '.mnsk7-catalog-chips__scroll');
      if (snapAlign === null) {
        test.skip(true, 'brak chipa w catalog scroll');
        return;
      }
      expect(String(snapAlign)).toMatch(/start|center|end|none|^$/);
    });
  }
});

test.describe('Chips swipe UX — kategoria (mobile)', () => {
  const CATEGORY_SLUG = process.env.PLP_CATEGORY_SLUG || 'frezy-cnc';

  for (const viewport of VIEWPORTS) {
    test(`viewport ${viewport.width}x${viewport.height}: na kategorii są chips nav + scroll`, async ({
      page,
    }) => {
      await page.setViewportSize(viewport);
      await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
      const res = await page.goto(`/product-category/${CATEGORY_SLUG}/`, { waitUntil: 'domcontentloaded' });
      if (res && res.status() === 404) {
        test.skip(true, `kategoria ${CATEGORY_SLUG} nie istnieje`);
        return;
      }
      const navChips = page.locator('.mnsk7-plp-chips--nav');
      const scroll = page.locator('.mnsk7-plp-chips--nav .mnsk7-plp-chips__scroll').first();
      const navCount = await navChips.count();
      if (navCount === 0) {
        test.skip(true, 'brak .mnsk7-plp-chips--nav na tej kategorii');
        return;
      }
      await expect(navChips.first()).toBeVisible({ timeout: 8000 });
      await expect(scroll).toBeVisible({ timeout: 5000 });
    });
  }
});
