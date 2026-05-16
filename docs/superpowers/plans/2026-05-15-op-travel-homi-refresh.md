# OP Travel Homi Refresh Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Re-skin the current OP Travel storefront to closely track the Blocksy `Homi` visual style without replacing the active custom theme or changing booking logic.

**Architecture:** Keep all business logic and WooCommerce route overrides in place, then layer the refresh through a new visual system, selective markup cleanup, and small interaction updates for header behavior and responsive navigation. Prefer CSS overrides and minimal PHP template reshaping over deeper functional refactors.

**Tech Stack:** WordPress theme PHP, WooCommerce template overrides, vanilla CSS, vanilla JavaScript

---

### Task 1: Document and prepare the refresh

**Files:**
- Modify: `docs/superpowers/specs/2026-05-15-op-travel-homi-refresh-design.md`
- Modify: `docs/superpowers/plans/2026-05-15-op-travel-homi-refresh.md`

- [ ] Confirm the approved Homi-inspired direction and scope.
- [ ] Record the design and implementation approach in the spec and plan docs.

### Task 2: Rebuild the global visual system

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Update font loading for a display/body pairing that supports the editorial direction.
- [ ] Replace the current palette, spacing, buttons, chrome, and surface treatment with a neutral warm system.
- [ ] Add responsive overrides for the new spacing and component sizing.

### Task 3: Refresh shared shell elements

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/header.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/footer.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Refine header markup for navigation, cart/account tools, and overlay behavior.
- [ ] Add scrolled-state and mobile navigation behavior in JavaScript.
- [ ] Restyle the footer to match the new storefront shell.

### Task 4: Refresh landing and browse pages

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/page-lien-he.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Recompose the hero and section rhythm on the home page.
- [ ] Align contact and archive layouts with the new editorial system.
- [ ] Simplify product cards and filter shells to better match the Homi reference.

### Task 5: Refresh transactional WooCommerce pages

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Rebalance single-product gallery, booking panel, tabs, and summary blocks.
- [ ] Restyle cart, checkout, and thank-you shells with cleaner surfaces and hierarchy.
- [ ] Preserve current booking and payment interactions.

### Task 6: Refresh account and static content pages

**Files:**
- Modify: `wordpress/wp-content/themes/op-travel-shop/page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/*.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`

- [ ] Align account, auth, and default page shells with the same visual system.
- [ ] Keep current account navigation and custom OTP/auth flows intact.

### Task 7: Verify the refresh

**Files:**
- Modify: none unless fixes are needed

- [ ] Run PHP syntax checks on edited templates and setup files.
- [ ] Review git diff for accidental logic changes outside the visual scope.
- [ ] Summarize any remaining gaps that need browser-based QA.
