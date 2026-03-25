// @ts-check
/**
 * Footer accordion: on mobile viewport, clicking a section title expands the section.
 */
const { test, expect } = require('@playwright/test');

test.describe('Footer accordion (mobile)', () => {
  test.use({ viewport: { width: 375, height: 667 } });

  test('clicking footer title toggles section', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const footer = page.locator('#colophon.mnsk7-footer, .mnsk7-footer').first();
    await expect(footer).toBeVisible();

    const cols = footer.locator('.mnsk7-footer__col');
    const count = await cols.count();
    if (count < 2) {
      test.skip();
      return;
    }

    const col = cols.nth(1);
    const secondTrigger = col.locator('.mnsk7-footer__accordion-trigger').first();

    await secondTrigger.scrollIntoViewIfNeeded();
    await secondTrigger.click({ force: true });

    await expect(col).toHaveClass(/is-open/, { timeout: 3000 });
    const ariaExpanded = await secondTrigger.getAttribute('aria-expanded');
    expect(ariaExpanded).toBe('true');
  });

  test('footer columns have role=button and aria-expanded on mobile', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const footer = page.locator('#colophon.mnsk7-footer, .mnsk7-footer').first();
    const firstTrigger = footer.locator('.mnsk7-footer__accordion-trigger').first();
    await expect(firstTrigger).toBeVisible();
    await expect(firstTrigger).toHaveAttribute('aria-expanded');
    await expect(firstTrigger).toHaveJSProperty('tagName', 'BUTTON');
  });
});
