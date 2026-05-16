# OP Travel Homi Refresh Design

**Goal**

Refit the public OP Travel storefront so it visually tracks the Blocksy `Homi` starter site's layout language and detail treatment while preserving the current custom WooCommerce booking flow, account flow, and CMS-driven route rendering.

**Design Summary**

- Keep `HV-Travel` branding and current information architecture.
- Shift the theme from the current luxury-gradient look to a lighter editorial storefront with neutral warm surfaces, restrained shadows, larger whitespace, and cleaner form controls.
- Use a more premium typography pairing: refined display headings with a clean sans-serif body.
- Rebuild the storefront feel around:
  - transparent overlay header on hero-driven pages
  - compact sticky header after scroll
  - poster-like hero composition
  - cleaner product cards and archive filters
  - brighter single-product, cart, checkout, and account shells
  - simpler footer and page chrome

**Constraints**

- Do not replace the active theme.
- Do not move booking, payment, account, or CMS business logic out of the current PHP flow in this task.
- Only adjust markup where CSS cannot achieve the `Homi`-like layout closely enough.
- Preserve current page accessibility, responsive behavior, and WooCommerce submit flows.

**Implementation Scope**

- Theme asset refresh in:
  - `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`
  - `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`
  - `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- Structural markup refinement in:
  - `header.php`
  - `footer.php`
  - `front-page.php`
  - `page-lien-he.php`
  - `woocommerce/content-product.php`
  - WooCommerce storefront templates that already own custom layout

**Expected Outcome**

- All user-accessible storefront pages share a coherent `Homi`-inspired visual system.
- Home and contact pages read as editorial landing pages with overlay headers.
- Shop, single product, cart, checkout, and account pages feel like one modern storefront rather than separate custom shells.
- Existing purchase and account flows continue to behave as before.
