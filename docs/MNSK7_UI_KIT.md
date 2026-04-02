# MNSK7 UI Kit

Local UI kit for the Storefront child theme. This is the place to tune visual language before editing one-off component rules.

## Source of Truth

- Tokens: `wp-content/themes/mnsk7-storefront/assets/css/parts/01-tokens.css`
- Primitives: `wp-content/themes/mnsk7-storefront/assets/css/parts/07-mnsk7-blocks.css`
- Homepage section composition: `wp-content/themes/mnsk7-storefront/assets/css/parts/08-home-sections.css`
- Buttons: `wp-content/themes/mnsk7-storefront/assets/css/parts/17-buttons.css`

## First Knobs To Change

- `--ui-surface-card`: base card background
- `--ui-surface-panel`: section panel background
- `--ui-stroke-soft`: soft border color
- `--ui-stroke-strong`: stronger border for kickers/chips
- `--ui-heading`: main dark heading color
- `--ui-copy`: secondary paragraph color
- `--ui-kicker`: eyebrow/kicker text color
- `--ui-section-radius`: large section shell radius
- `--ui-card-radius`: card radius
- `--ui-panel-shadow`: section shell shadow
- `--ui-card-shadow`: card shadow

## Core Primitives

- `.mnsk7-ui-kicker`
  Use for section eyebrows and small category headers.
- `.mnsk7-ui-heading`
  Use for H2/H3 that should stay inside the main visual system.
- `.mnsk7-ui-copy`
  Use for supporting copy under titles, cards, or proof blocks.
- `.mnsk7-ui-card`
  Use for standalone cards, stat tiles, promo cards, and small content blocks.
- `.mnsk7-ui-panel`
  Use for larger section wrappers that need the frosted/light shell look.
- `.mnsk7-ui-chip`
  Use for lightweight tags, filter chips, and secondary CTA-like pills.
- `.mnsk7-ui-grid-2`
  Use for balanced 2-column content blocks that collapse to 1 column on tablet/mobile.

## Rules

- Do not import external UI kits into this theme unless they are plain CSS/HTML and match the existing runtime.
- Prefer changing tokens first, primitives second, page-specific selectors last.
- If a new pattern repeats on 2 or more templates, move it into the local UI kit instead of leaving it as page-only CSS.
- Keep the visual direction industrial, clean, and high-trust. Avoid glossy SaaS styling, purple gradients, or generic startup look.

## Current Visual Direction

- Light surfaces with controlled blue accents
- Dense but readable information hierarchy
- Industrial/product-first presentation
- Rounded cards and section shells, but no toy-like softness
- Strong contrast on headings and CTAs, muted copy for support text
