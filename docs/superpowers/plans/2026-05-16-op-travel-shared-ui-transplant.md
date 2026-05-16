# OP Travel Shared UI Transplant Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the shared public-facing visual language of the `travel-agency-modern` reference theme to `op-travel-shop` without changing current WooCommerce and CMS behavior.

**Architecture:** Keep current theme logic in place and refresh only the shared presentation layer. Use one narrow smoke script for source-level verification, then reshape the shared PHP templates and append CSS overrides that restyle the storefront shell while preserving existing JS selectors and flow wiring.

**Tech Stack:** WordPress theme PHP, WooCommerce theme overrides, vanilla CSS, vanilla JavaScript, PHP CLI smoke verification

---

### Task 1: Lock the design docs and verification target

**Files:**
- Modify: `docs/superpowers/specs/2026-05-16-op-travel-shared-ui-transplant-design.md`
- Modify: `docs/superpowers/plans/2026-05-16-op-travel-shared-ui-transplant.md`
- Create: `scripts/op-travel-shared-ui-smoke.php`

- [ ] Write the shared UI transplant spec and implementation plan.
- [ ] Write a smoke script that checks for the new shared-shell markers in the theme source.
- [ ] Run the smoke script and confirm it fails before theme changes land.

### Task 2: Refresh global visual primitives

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Update font loading to support the lighter travel-editorial direction.
- [ ] Add the transplanted palette, spacing, shell, button, card, and heading overrides.
- [ ] Add shared responsive rules for the new shell primitives.

### Task 3: Refresh the shared shell templates

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/header.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/footer.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`

- [ ] Rework header markup to feel closer to the reference theme while preserving account, cart, and mobile-menu behavior.
- [ ] Rework footer markup into a brighter three-column travel footer.
- [ ] Keep or minimally adapt JS selectors so existing interactions continue to work.

### Task 4: Refresh homepage and default page shells

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Recompose the homepage hero and supporting sections using the current WooCommerce and taxonomy data.
- [ ] Add a page intro and content-card shell for regular pages while preserving cart/checkout/account exceptions.
- [ ] Restyle current product cards and discovery panels through shared CSS rather than deeper WooCommerce template changes.

### Task 5: Verify and inspect

**Files:**
- Modify: `scripts/op-travel-shared-ui-smoke.php` only if fixes are needed

- [ ] Run `php scripts/op-travel-shared-ui-smoke.php` and confirm it passes after implementation.
- [ ] Run PHP syntax checks on edited theme PHP files.
- [ ] Review git diff to ensure the scope stayed inside shared presentation changes.
- [ ] Summarize any remaining browser QA that still needs a live local stack.
