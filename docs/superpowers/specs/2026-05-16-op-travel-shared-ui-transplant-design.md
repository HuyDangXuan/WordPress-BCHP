# OP Travel Shared UI Transplant Design

**Goal**

Refresh the public shared shell of `op-travel-shop` so it visually borrows the bright travel-editorial language from the `travel-agency-modern` theme inside `test`, while keeping all current WooCommerce, booking, account, and CMS route logic intact.

**Design Summary**

- Keep the active `op-travel-shop` theme and its current route structure.
- Treat the work as a shared presentation transplant, not a theme replacement.
- Move the storefront from the current warm-luxury shell toward a lighter travel interface with:
  - brighter blue-leaning surfaces
  - softer borders and shadow depth
  - rounded content cards
  - clearer section headings
  - simpler travel-site header and footer chrome
- Preserve the current taxonomy, WooCommerce products, cart, checkout, account, and CMS-rendered routes.

**Constraints**

- Do not import `tour` custom post type logic, backend API logic, or page-template data dependencies from `test`.
- Do not change booking, payment, cart, checkout, account, or storefront CMS business behavior.
- Keep current JS selectors alive where possible so cart/account/mobile interactions continue to work.
- Prefer additive CSS overrides and minimal markup reshaping over deeper refactors of the current theme files.

**Implementation Scope**

- Shared visual system refresh in:
  - `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`
  - `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- Shared shell markup refinement in:
  - `wordpress/wp-content/themes/op-travel-shop/header.php`
  - `wordpress/wp-content/themes/op-travel-shop/footer.php`
  - `wordpress/wp-content/themes/op-travel-shop/front-page.php`
  - `wordpress/wp-content/themes/op-travel-shop/page.php`
- Minimal selector-safe interaction updates in:
  - `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`
- Minimal source smoke coverage in:
  - `scripts/op-travel-shared-ui-smoke.php`

**Expected Outcome**

- The site header, footer, homepage, and default content pages visually align with the lighter travel-agency-modern direction.
- WooCommerce archive, single product, cart, checkout, and account flows continue to use the same current logic and templates.
- Shared cards, buttons, shells, and section rhythm feel like one coherent storefront rather than a mix of separate styles.
- Theme verification can confirm the new shared UI markers exist before browser QA.
