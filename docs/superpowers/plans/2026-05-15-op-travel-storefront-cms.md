# OP Travel Storefront CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first working `op-travel-storefront-cms` plugin so `wp-admin` can manage most storefront composition through route-bound flexible sections while WooCommerce `product` data remains the source of truth for tour and booking data.

**Architecture:** Add a private `storefront_document` CPT in a new plugin, store route binding and ordered sections in post meta, expose a theme-facing route renderer with preview support, and refactor the current storefront templates to render CMS documents first and fall back to legacy hard-coded templates when no published document exists.

**Tech Stack:** WordPress plugin PHP, WooCommerce theme templates, admin meta boxes, lightweight admin JavaScript, Markdown docs, PowerShell, PHP CLI syntax and smoke checks

---

## File Structure Lock

**Create:**
- `docs/superpowers/plans/2026-05-15-op-travel-storefront-cms.md`
- `scripts/storefront-cms-domain-smoke.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/op-travel-storefront-cms.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Bootstrap.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/StorefrontDocumentPostType.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/RouteKey.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/SectionSanitizer.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Documents/DocumentRepository.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/AdminAssets.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentMetaBoxes.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentSave.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Sections/SectionRegistry.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/PreviewResolver.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/RouteRenderer.php`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/assets/admin/storefront-cms-admin.js`
- `wordpress/wp-content/plugins/op-travel-storefront-cms/assets/admin/storefront-cms-admin.css`
- `wordpress/wp-content/themes/op-travel-shop/inc/storefront-cms.php`

**Modify:**
- `wordpress/wp-content/themes/op-travel-shop/functions.php`
- `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- `wordpress/wp-content/themes/op-travel-shop/page.php`
- `wordpress/wp-content/themes/op-travel-shop/page-lien-he.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`

## Implementation Notes

- Keep render markup in the theme, not in the plugin.
- Keep `product` title, excerpt, gallery, metadata, price, and booking flow in WooCommerce and `op-travel-core`.
- Store sections as sanitized structured arrays in post meta; do not store executable markup or PHP.
- Ship a constrained section library first: `hero`, `rich_text`, `cta_band`, `featured_tours`, `taxonomy_grid`, `testimonial_list`, `promotion_list`, `tour_highlights`, `tour_itinerary`, `tour_includes_excludes`, `tour_booking_panel`.
- Roll out route integration with safe fallback in every template.

### Task 1: Scaffold the Plugin and Domain Helpers

**Files:**
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/op-travel-storefront-cms.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Bootstrap.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/StorefrontDocumentPostType.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/RouteKey.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/SectionSanitizer.php`
- Create: `scripts/storefront-cms-domain-smoke.php`

- [ ] **Step 1: Write the domain smoke script first**

Create `scripts/storefront-cms-domain-smoke.php` with assertions for:
- `RouteKey::fromParts('home') === 'home'`
- `RouteKey::fromParts('page', 15) === 'page:15'`
- invalid page IDs throw
- `SectionSanitizer::sanitizeMany()` returns normalized arrays with `id`, `type`, `enabled`, `settings`, `content`, `bindings`

- [ ] **Step 2: Run the smoke script to verify it fails**

Run: `php scripts/storefront-cms-domain-smoke.php`
Expected: FAIL because the plugin classes do not exist yet

- [ ] **Step 3: Create the plugin bootstrap and domain classes**

Implementation requirements:
- main plugin file loads all includes and calls `Bootstrap::boot()`
- `StorefrontDocumentPostType` registers private admin CPT `storefront_document`
- `RouteKey` builds and validates `home`, `shop_archive`, `product_single_default`, and `page:{page_id}`
- `SectionSanitizer` normalizes repeater payloads into safe arrays

- [ ] **Step 4: Run the smoke script again**

Run: `php scripts/storefront-cms-domain-smoke.php`
Expected: PASS

- [ ] **Step 5: Run PHP syntax checks on the new plugin scaffold**

Run:
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/op-travel-storefront-cms.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Bootstrap.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/StorefrontDocumentPostType.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/RouteKey.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Domain/SectionSanitizer.php`

Expected: no syntax errors

### Task 2: Build the Admin Document Editor

**Files:**
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Documents/DocumentRepository.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/AdminAssets.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentMetaBoxes.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentSave.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Sections/SectionRegistry.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/assets/admin/storefront-cms-admin.js`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/assets/admin/storefront-cms-admin.css`
- Test: `scripts/storefront-cms-domain-smoke.php`

- [ ] **Step 1: Extend the smoke script with route uniqueness and section registry assertions**

Add assertions for:
- registry returns supported section types
- unsupported section types are filtered or normalized safely
- duplicate route publish checks can be queried through repository helpers

- [ ] **Step 2: Run the smoke script to verify it fails**

Run: `php scripts/storefront-cms-domain-smoke.php`
Expected: FAIL because repository and registry helpers do not exist yet

- [ ] **Step 3: Implement the admin editor**

Implementation requirements:
- route binding meta box with route type and page target
- sections meta box with repeatable section cards
- reusable section type list from `SectionRegistry`
- lightweight JS for add, remove, and reorder actions
- CSS for readable admin layout

- [ ] **Step 4: Implement save flow and publish guard**

Implementation requirements:
- save route meta and sanitized sections
- normalize route key into one canonical meta value
- if a publish action conflicts with another published document on the same route, revert to draft and show an admin notice

- [ ] **Step 5: Re-run smoke and syntax checks**

Run:
- `php scripts/storefront-cms-domain-smoke.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Documents/DocumentRepository.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentMetaBoxes.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Admin/DocumentSave.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Sections/SectionRegistry.php`

Expected: PASS and no syntax errors

### Task 3: Add Frontend Route Resolution and Preview

**Files:**
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/PreviewResolver.php`
- Create: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/RouteRenderer.php`
- Modify: `wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Bootstrap.php`
- Modify: `wordpress/wp-content/plugins/op-travel-storefront-cms/op-travel-storefront-cms.php`

- [ ] **Step 1: Extend the smoke script with current-route and preview assertions that do not require WordPress bootstrap**

Add assertions for:
- preview query parsing returns null for invalid values
- route renderer helper rejects unsupported explicit route keys cleanly

- [ ] **Step 2: Run the smoke script to verify it fails**

Run: `php scripts/storefront-cms-domain-smoke.php`
Expected: FAIL because preview and rendering helpers do not exist yet

- [ ] **Step 3: Implement document lookup and preview resolution**

Implementation requirements:
- fetch published document by route key
- allow privileged preview via signed query string
- expose small public wrapper functions so theme templates can ask the plugin to render a specific route

- [ ] **Step 4: Implement render contract**

Implementation requirements:
- plugin resolves the document and preview state
- plugin calls a theme function to render sections
- plugin returns `true` only when a matching document rendered successfully

- [ ] **Step 5: Re-run smoke and syntax checks**

Run:
- `php scripts/storefront-cms-domain-smoke.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/PreviewResolver.php`
- `php -l wordpress/wp-content/plugins/op-travel-storefront-cms/includes/Front/RouteRenderer.php`

Expected: PASS and no syntax errors

### Task 4: Refactor Theme Rendering with Safe Fallbacks

**Files:**
- Create: `wordpress/wp-content/themes/op-travel-shop/inc/storefront-cms.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/functions.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/page.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/page-lien-he.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- Modify: `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`

- [ ] **Step 1: Add theme render helpers for the initial section library**

Implementation requirements:
- keep markup in theme functions
- reuse existing classes like `op-section`, `op-shell`, `op-section-heading`, `op-tour-grid`, `op-summary-panel`, `op-discovery-grid`
- support both manual copy and dynamic bindings for product, promotions, testimonials, and taxonomies

- [ ] **Step 2: Wire route rendering into each CMS-managed template**

Implementation requirements:
- `front-page.php` tries `home`
- `page.php` and `page-lien-he.php` try `page:{ID}`
- `archive-product.php` tries `shop_archive`
- `single-product.php` tries `product_single_default`
- each template returns to legacy markup if the plugin does not render a document

- [ ] **Step 3: Preserve current business features**

Check that:
- archive filtering and pagination still rely on WooCommerce query state
- single-product booking panel still calls `woocommerce_template_single_add_to_cart()`
- product metadata still comes from WooCommerce + `op-travel-core`

- [ ] **Step 4: Run PHP syntax checks across all modified theme files**

Run:
- `php -l wordpress/wp-content/themes/op-travel-shop/functions.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/inc/storefront-cms.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/front-page.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/page.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/page-lien-he.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- `php -l wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`

Expected: no syntax errors

- [ ] **Step 5: Commit the route integration slice**

```bash
git add wordpress/wp-content/plugins/op-travel-storefront-cms wordpress/wp-content/themes/op-travel-shop docs/superpowers/plans/2026-05-15-op-travel-storefront-cms.md scripts/storefront-cms-domain-smoke.php
git commit -m "feat: add storefront cms route rendering"
```

### Task 5: Final Verification and Admin Smoke Checklist

**Files:**
- Modify only if verification reveals issues

- [ ] **Step 1: Run the domain smoke script**

Run: `php scripts/storefront-cms-domain-smoke.php`
Expected: PASS

- [ ] **Step 2: Run PHP syntax checks for every touched PHP file**

Run `php -l` on all changed PHP files in:
- `wordpress/wp-content/plugins/op-travel-storefront-cms/`
- `wordpress/wp-content/themes/op-travel-shop/`
- `scripts/storefront-cms-domain-smoke.php`

Expected: no syntax errors

- [ ] **Step 3: Review git diff for boundary regressions**

Check that:
- no booking logic moved out of `op-travel-core`
- no large storefront HTML was added inside the plugin
- fallback branches still exist in every route template

- [ ] **Step 4: Capture manual admin verification notes**

Verify in `wp-admin`:
- create a `storefront_document`
- bind it to `home`
- add at least `hero` and `featured_tours`
- save draft, preview, then publish
- confirm front page renders CMS output
- confirm deleting or unpublishing the document restores legacy theme fallback

- [ ] **Step 5: Commit the final implementation**

```bash
git add wordpress/wp-content/plugins/op-travel-storefront-cms wordpress/wp-content/themes/op-travel-shop scripts/storefront-cms-domain-smoke.php docs/superpowers/plans/2026-05-15-op-travel-storefront-cms.md
git commit -m "feat: add admin managed storefront cms"
```
