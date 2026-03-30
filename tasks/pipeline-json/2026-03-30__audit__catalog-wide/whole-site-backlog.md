# Whole-Site UX/UI Backlog

Date: 2026-03-30
Scope: whole site
Priority model: conversion-first, Apple-weighted
Basis: live staging review, existing `analyzer.json`, repo templates/CSS, current mobile/desktop regressions

## Wave A - Shell And Control Integrity

### WSA-001
- Surface: PDP quantity control
- Device scope: desktop + mobile
- Severity: P0
- Dimensions: technical, heuristic, taste, apple
- Current behavior: numeric quantity stepper mixes pill-like wrapper with square center input and optically right-weighted geometry
- Why it matters: breaks control cohesion in the core buying block and feels unpolished on iOS-like touch UI
- Expected behavior: quantity cluster reads as one composed pill-family control with optically centered value
- Non-hacky solution direction: normalize wrapper, input, and plus/minus buttons into one geometry family in shared PDP control styles
- Acceptance criteria: no square-looking center field, balanced control rhythm, 44px touch targets preserved
- Verification target: PDP mobile + desktop on staging

### WSA-002
- Surface: archive search controls
- Device scope: mobile
- Severity: P0
- Dimensions: technical, taste, apple
- Current behavior: archive search button/input radius can diverge because generic button rules compete with archive-specific styles
- Why it matters: stitched search breaks into separate pills on category/tag mobile
- Expected behavior: archive search is a single stitched control on all archive surfaces
- Non-hacky solution direction: keep radius ownership in archive-search rules, not generic button layer
- Acceptance criteria: shop/category/tag mobile all show flat inner seam and rounded outer corners only
- Verification target: `/sklep`, category, tag mobile

### WSA-003
- Surface: mobile header geometry
- Device scope: mobile
- Severity: P0
- Dimensions: technical, heuristic, apple
- Current behavior: header remains sensitive to iPhone/Telegram/Safari layout differences and duplicated critical/runtime assumptions
- Why it matters: shared navigation shell is conversion-guard sensitive
- Expected behavior: one stable header contract across Safari-like mobile contexts
- Non-hacky solution direction: unify header geometry tokens and remove competing mobile assumptions
- Acceptance criteria: logo, search, account, cart, menu remain aligned and unclipped
- Verification target: iPhone-sized emulation plus real-device Safari/Telegram

### WSA-004
- Surface: shared control language
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: technical, taste, apple
- Current behavior: chips, trust badges, quantity controls, and segmented control patterns still use slightly different shape logic
- Why it matters: UI feels assembled instead of systemized
- Expected behavior: shared controls use intentional families: pill, rounded rectangle, stitched group
- Non-hacky solution direction: lock a small set of component rules and align variants
- Acceptance criteria: no accidental half-rounded/half-square controls on key surfaces
- Verification target: spot-check header, archives, PDP, cart

## Wave B - Catalog UX And Navigation

### WSB-001
- Surface: `/sklep` first screen
- Device scope: mobile
- Severity: P1
- Dimensions: heuristic, taste, apple
- Current behavior: first screen still spends too much attention budget on controls before products
- Why it matters: slows entry into catalog and weakens scan speed
- Expected behavior: search first, then minimal orientation, then products
- Non-hacky solution direction: reduce visible control weight and collapse secondary filter burden
- Acceptance criteria: first product appears faster and hierarchy reads in 2-3 seconds
- Verification target: `/sklep` mobile

### WSB-002
- Surface: category archive role
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: category pages still feel too similar to `/sklep`
- Why it matters: category should behave like a focused buying lane
- Expected behavior: category pages emphasize fit and product type over generic browsing
- Non-hacky solution direction: tune context line, chips priority, and result hierarchy per archive type
- Acceptance criteria: category page reads as narrower and more decisive than `/sklep`
- Verification target: top category archive desktop + mobile

### WSB-003
- Surface: tag archive role
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: tag pages still carry too much generic archive machinery
- Why it matters: tag should act as use-case/material shortcut
- Expected behavior: tag pages foreground use-case framing and fast narrowing
- Non-hacky solution direction: lighter control stack and stronger context framing
- Acceptance criteria: tag page clearly differs from category and `/sklep`
- Verification target: `product_tag` desktop + mobile

### WSB-004
- Surface: PLP row/card purchase clarity
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste, apple
- Current behavior: fixed-qty and regular rows still vary in purchase cluster language
- Why it matters: buyer should understand stock/qty/add-to-cart instantly
- Expected behavior: all purchase rows read as one compact buyable unit
- Non-hacky solution direction: unify row CTA cluster semantics and suppress non-essential noise on mobile
- Acceptance criteria: price, stock, qty, CTA scan in one pass
- Verification target: `/sklep` table rows and mobile grid cards

### WSB-005
- Surface: chips and filters
- Device scope: mobile
- Severity: P1
- Dimensions: heuristic, taste, apple
- Current behavior: chip rows and filter rows still carry more visual weight than their priority justifies
- Why it matters: cognitive load remains high above the fold
- Expected behavior: only the highest-value chips feel primary; the rest feel secondary/progressive
- Non-hacky solution direction: further compress labels, spacing, and visual emphasis
- Acceptance criteria: chips support navigation rather than dominate it
- Verification target: category/tag mobile

### WSB-006
- Surface: empty and filtered states
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic
- Current behavior: recovery UI can still arrive after too much control noise
- Why it matters: empty state should restore momentum immediately
- Expected behavior: reset and recovery actions are the first obvious path
- Non-hacky solution direction: prioritize recovery block above heavy control stacks
- Acceptance criteria: user sees how to recover immediately
- Verification target: filtered empty archive

## Wave C - PDP And Purchase Clarity

### WSC-001
- Surface: PDP buybox hierarchy
- Device scope: desktop + mobile
- Severity: P0
- Dimensions: heuristic, taste, apple
- Current behavior: title, price, stock, params, quantity, CTA, trust still stack with uneven emphasis
- Why it matters: core purchase block should be decisive and calm
- Expected behavior: buybox reads as layered decision flow: title/price -> availability/trust -> qty/CTA
- Non-hacky solution direction: rebalance summary spacing, grouping, and supporting blocks
- Acceptance criteria: buybox can be understood in one screenful
- Verification target: PDP desktop + mobile

### WSC-002
- Surface: PDP key parameters block
- Device scope: mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: key params compete with purchase controls for attention
- Why it matters: mobile buyers need fit-critical data without burying CTA
- Expected behavior: only most decision-critical params stay above the CTA
- Non-hacky solution direction: tighten spacing and reduce visual dominance of selectors/metadata
- Acceptance criteria: params inform without delaying purchase path
- Verification target: variable and simple PDP mobile

### WSC-003
- Surface: PDP trust placement
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: trust badges are useful but still feel appended rather than integrated
- Why it matters: trust should reduce friction at the decision point
- Expected behavior: trust is near the buying action without competing with it
- Non-hacky solution direction: integrate trust into the buybox rhythm
- Acceptance criteria: trust supports CTA instead of reading like a separate module
- Verification target: PDP mobile + desktop

### WSC-004
- Surface: related products and lower PDP blocks
- Device scope: mobile
- Severity: P2
- Dimensions: taste, heuristic
- Current behavior: lower PDP blocks can feel visually detached from the buybox
- Why it matters: continuity matters after primary decision point
- Expected behavior: lower sections inherit the same spacing and card rhythm
- Non-hacky solution direction: align related-products section spacing with buybox/page rhythm
- Acceptance criteria: smoother transition from buybox to recommendations
- Verification target: PDP mobile

## Wave D - Home And Secondary Templates

### WSD-001
- Surface: homepage hero
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: hero carries many equal-weight meanings: materials, USP pills, CTA, welcome state
- Why it matters: first impression is strong but still slightly noisy
- Expected behavior: hero leads to one primary commercial action with cleaner hierarchy
- Non-hacky solution direction: reduce equal-weight claims and tighten vertical rhythm
- Acceptance criteria: hero reads faster and feels more premium
- Verification target: homepage desktop + mobile

### WSD-002
- Surface: homepage trust section
- Device scope: desktop + mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: trust is credible but visually heavy and section-like rather than flow-integrated
- Why it matters: trust should reinforce catalog intent, not pause it
- Expected behavior: trust confirms confidence with cleaner density and stronger hierarchy
- Non-hacky solution direction: rebalance stats, reviews, and CTA weight
- Acceptance criteria: trust reads as reassurance, not as another competing module
- Verification target: homepage desktop + mobile

### WSD-003
- Surface: homepage catalog section
- Device scope: mobile
- Severity: P1
- Dimensions: heuristic, taste
- Current behavior: catalog discovery on home still mixes chips and category cards with moderate visual noise
- Why it matters: home should be an easier entry into browsing than archives
- Expected behavior: one clean discovery path from home into catalog
- Non-hacky solution direction: reduce repeated labels and unify card/chip rhythm
- Acceptance criteria: user can jump to the right catalog lane quickly
- Verification target: homepage mobile

### WSD-004
- Surface: footer
- Device scope: mobile
- Severity: P2
- Dimensions: heuristic, taste, apple
- Current behavior: footer is functional but still dense in accordion form
- Why it matters: lower-page navigation should remain usable without visual fatigue
- Expected behavior: footer groups stay clear, calm, and easy to scan
- Non-hacky solution direction: tighten accordion rhythm and text density
- Acceptance criteria: footer remains useful without feeling overloaded
- Verification target: mobile footer on home/archive/PDP

### WSD-005
- Surface: delivery/contact/account/guide pages
- Device scope: desktop + mobile
- Severity: P2
- Dimensions: technical, heuristic, taste
- Current behavior: secondary templates inherit shell but may diverge in rhythm and hierarchy
- Why it matters: whole-site polish breaks if secondary pages feel like another product
- Expected behavior: secondary pages follow the same shell and spacing system as commerce pages
- Non-hacky solution direction: run consistency pass on page templates and reusable sections
- Acceptance criteria: no secondary page feels visually disconnected from the store
- Verification target: one page from each template family

## Conversion Guard Tickets

### WSG-001
- Surface: add-to-cart integrity
- Device scope: desktop + mobile
- Severity: P0
- Dimensions: technical
- Current behavior: repeated UI work around PLP/PDP controls raises regression risk
- Why it matters: non-negotiable guard
- Expected behavior: PLP and PDP add-to-cart remain intact through all waves
- Non-hacky solution direction: verify after every control/layout wave
- Acceptance criteria: add-to-cart works on PLP and PDP simple + variable contexts
- Verification target: staging L1 smoke

### WSG-002
- Surface: cart visibility/update and checkout entry
- Device scope: desktop + mobile
- Severity: P0
- Dimensions: technical
- Current behavior: shell and control refactors can indirectly regress cart and checkout
- Why it matters: non-negotiable guard
- Expected behavior: cart and checkout remain visibly reachable and functional
- Non-hacky solution direction: keep every wave behind explicit Woo smoke checks
- Acceptance criteria: cart opens/updates; checkout form remains visible
- Verification target: staging L1 smoke
