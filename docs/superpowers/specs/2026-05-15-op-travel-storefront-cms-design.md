# OP Travel Storefront CMS Design

**Date:** 2026-05-15

**Status:** Approved for planning

**Goal**

Add a new WordPress plugin, `op-travel-storefront-cms`, that lets administrators manage most storefront content from `wp-admin` through a flexible section-based CMS, while preserving the current architecture boundaries: `op-travel-shop` stays the presentation layer, `op-travel-core` stays the business layer, and WooCommerce `product` data remains the source of truth for tour and booking data.

## Context

The current storefront is mostly hard-coded in theme templates:

- `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- `wordpress/wp-content/themes/op-travel-shop/page.php`
- `wordpress/wp-content/themes/op-travel-shop/page-lien-he.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`

The existing plugin layer already owns business-side CMS setup and tour metadata:

- `wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/ProductMeta.php`

The next iteration should move storefront content management into `wp-admin` without turning the theme into a page builder and without relocating booking or payment logic into the CMS plugin.

This design is based on:

- `Phases/01-phase-1-tong-quan-de-tai-va-kien-truc.md`
- `Phases/04-phase-4-tuy-bien-skin-theme-op-travel-shop.md`
- `Phases/05-phase-5-plugin-nghiep-vu-op-travel-core.md`
- `Skills/hv-travel-project-context/SKILL.md`
- `Skills/op-travel-core-plugin-extension/SKILL.md`

## Design Principles

- Keep `WordPress + WooCommerce + MySQL` as the storefront and admin source of truth.
- Keep business logic in `op-travel-core`; the new CMS plugin must not own booking, payment, or order state.
- Keep `product` as the source of truth for tour title, price, taxonomy, media, metadata, and booking flow.
- Manage storefront composition with a separate CMS entity rather than overloading WordPress `page` or WooCommerce `product`.
- Restrict the first version to fixed route keys already present in the storefront.
- Prefer structured section types over unrestricted page-builder freedom.
- Always preserve a safe fallback to the current hard-coded theme templates during rollout.

## Scope

The first version should support CMS-managed composition for these existing storefront routes:

- home page
- standard content pages already mapped in WordPress
- shop archive
- product single

The first version should not support:

- cart, checkout, or my account route composition
- arbitrary new route creation
- frontend inline editing
- arbitrary block nesting or custom PHP snippets in admin
- moving tour business data out of WooCommerce products

## Architecture Boundaries

### New Plugin Layer

`wordpress/wp-content/plugins/op-travel-storefront-cms/` owns:

- storefront CMS document model
- admin document management UI
- section schema and validation
- route-to-document resolution
- theme-facing rendering helpers
- preview and publish-state rules for storefront documents

It must not own:

- WooCommerce booking validation
- order persistence
- payment workflow
- taxonomies and tour metadata that already belong to `op-travel-core`

### Theme Layer

`wordpress/wp-content/themes/op-travel-shop/` continues to own:

- layout shell
- CSS and JavaScript
- section markup partials
- WooCommerce template overrides
- render-time page context assembly

It should stop owning:

- hard-coded copy for most storefront sections
- hard-coded section ordering for CMS-managed routes

### Business Plugin Layer

`wordpress/wp-content/plugins/op-travel-core/` continues to own:

- tour metadata
- taxonomy registration
- page setup helpers
- booking flow
- REST/payment bridge responsibilities

The new CMS plugin may read business-layer data through public WordPress and WooCommerce APIs, but must not duplicate or replace those responsibilities.

## Plugin Model

The new plugin should register a private admin-focused custom post type:

- `storefront_document`

This CPT exists to reuse WordPress capabilities:

- revision history
- draft/publish status
- autosave
- capability mapping
- admin list table behavior

It should not be publicly queryable on the frontend.

Each document represents one CMS-managed storefront route and stores:

- route binding
- ordered section list
- document-level settings
- preview metadata

## Route Model

Each document binds to exactly one supported route key.

Initial route keys:

- `home`
- `shop_archive`
- `product_single_default`
- `page:{page_id}`

Rules:

- only one published document may own a route key at a time
- duplicate route claims are allowed in draft, but publish must be blocked
- `page:{page_id}` only applies to existing WordPress pages
- unsupported routes fall back to current theme behavior

## Section Model

Each document stores an ordered array of section objects in post meta JSON.

Each section should contain:

- `id`: stable UUID-like string for admin updates
- `type`: registered section type slug
- `label`: editor-facing name
- `enabled`: boolean
- `settings`: visual and layout configuration
- `content`: manual content fields
- `bindings`: dynamic data bindings

### Section Type Registry

Section types should be registered in code through a registry so both admin and theme render from the same definition.

Each section type definition should declare:

- type slug
- display label
- supported routes
- field schema
- default values
- render callback or template identifier
- validation rules

### Initial Section Types

The first version should focus on a constrained, reusable library:

- `hero`
- `rich_text`
- `cta_band`
- `stats`
- `featured_tours`
- `taxonomy_grid`
- `testimonial_list`
- `promotion_list`
- `media_text`
- `faq`
- `tour_highlights`
- `tour_itinerary`
- `tour_includes_excludes`
- `tour_booking_panel`

This list is intentionally constrained. The goal is a structured CMS, not a generic page builder.

## Dynamic Data Strategy

The CMS must support both manual content and dynamic content bindings.

### Manual Content

Manual content is stored directly in the section:

- headline
- body copy
- image IDs
- button labels and links
- badges
- supporting text

### Dynamic Bindings

Dynamic bindings allow a section to pull data from the current route context instead of duplicating it.

Initial binding sources:

- current `product`
- WooCommerce product queries
- `destination` taxonomy
- `tour_style` taxonomy
- `promotion` posts
- `testimonial` posts
- current WordPress page data

Examples:

- a `featured_tours` section queries products by configured count or taxonomy
- a `tour_highlights` section reads `_tour_highlights` from the current product
- a `hero` section on `product_single_default` can use the current product title and short description
- a `taxonomy_grid` section can display `destination` or `tour_style`

Bindings should support manual override where useful, but the default approach should avoid data duplication.

## Admin Experience

The plugin should expose a top-level `Storefront CMS` menu.

### Document List Screen

The list screen should show:

- title
- route key
- status
- last modified time
- preview action
- duplicate action

The editor should guide admins toward route-driven thinking rather than generic posts.

### Document Editor

The editor should provide:

- route selector with guarded route types
- document status and validation notices
- section list ordered top to bottom
- add-section control filtered by route support
- per-section settings panels
- publish validation before save

The editor does not need a drag-heavy visual builder in version one. Reorder controls and structured forms are sufficient.

### Preview Model

Preview should render the real theme route with the draft document injected for authorized admins.

Requirements:

- preview URLs must be capability-checked
- preview should not affect anonymous users
- published pages must continue serving the current live document

## Theme Integration

The theme should be refactored from hard-coded section composition to route-aware document rendering.

### Route Resolution

Each CMS-managed template should:

1. resolve the current route key
2. ask the plugin for the published document for that route
3. if a document exists, render its ordered sections
4. if no document exists, render the legacy template fallback

### Theme Responsibilities

The theme should keep:

- common shells
- breadcrumb and step helpers
- section partial markup
- visual classes and responsive behavior

The plugin should not emit large inline HTML strings for the storefront. Rendering should remain theme-oriented.

## Data Flow

### Home and Page Routes

1. Admin edits a `storefront_document` in `wp-admin`
2. Plugin validates route ownership and section payload
3. Document is saved as draft or published
4. Theme resolves the route and fetches the active document
5. Theme renders each section through the registry

### Shop Archive Route

1. Theme keeps WooCommerce query and filter mechanics
2. CMS controls compositional sections around the archive
3. Dynamic sections may query taxonomy summaries or featured products
4. Product grid and pagination remain powered by WooCommerce query state

### Product Single Route

1. WooCommerce resolves the current product
2. Theme passes product context into CMS rendering
3. Dynamic sections pull title, excerpt, gallery, and tour metadata from the product
4. Booking panel remains owned by WooCommerce and `op-travel-core`

## Validation and Error Handling

The plugin should reject invalid states early in admin.

Validation rules:

- a document must bind to one supported route
- only one published document may exist per route
- a section type must be valid for the selected route
- required fields per section type must be present
- invalid JSON or unknown section types should fail safely

Frontend failure rules:

- if document lookup fails, fall back to the legacy template
- if one section payload is invalid, skip that section and continue rendering the page
- never break cart, checkout, account, or booking functionality because a CMS document is malformed

Admin notices should be explicit about what is wrong and what route or section caused it.

## Rollout Strategy

The rollout should be incremental and reversible.

### Phase 1

- scaffold plugin
- register `storefront_document`
- implement route model
- implement section registry
- implement basic admin editor for a small section set
- integrate `home` route first

### Phase 2

- refactor page routes
- add `shop_archive`
- add `product_single_default`
- expand dynamic bindings for product and taxonomy data

### Phase 3

- improve admin ergonomics
- add preview polish
- add migration helpers for current hard-coded copy

At every phase, legacy theme fallback must remain available until the new route is verified.

## Testing Strategy

Testing should cover both plugin correctness and route safety.

### PHP Tests

Add focused tests for:

- route key normalization
- published route uniqueness
- section schema validation
- document lookup rules
- preview authorization

### Markup/Integration Tests

Add or extend theme/plugin integration tests for:

- home route renders CMS document when present
- home route falls back when no document exists
- shop archive preserves WooCommerce loop behavior
- product single preserves booking panel rendering
- invalid section payload does not white-screen the route

### Manual Verification

Verify in WordPress admin:

- create draft document
- publish a document per route
- reorder sections
- switch a page route to CMS-backed rendering
- preview unpublished changes
- confirm product metadata still renders from WooCommerce product data

## Risks and Tradeoffs

- A custom section editor is more work than plain meta boxes, but it matches the required flexibility.
- A private CMS entity adds complexity, but it cleanly separates storefront composition from WordPress pages and WooCommerce products.
- Keeping render markup in the theme requires coordination between plugin and theme, but it preserves the correct ownership boundary.
- Dynamic bindings reduce duplication, but require careful schema design so the admin UI stays understandable.

## Recommended Implementation Direction

Build `op-travel-storefront-cms` as a separate plugin with:

- a private `storefront_document` CPT
- route-key-based document ownership
- a code-defined section registry
- structured admin forms for section composition
- theme-side rendering with fallback to existing templates

This approach fits the current repository architecture, keeps business logic out of the theme, keeps tour data in WooCommerce where it already belongs, and gives `wp-admin` a realistic CMS workflow for most storefront content without introducing a heavyweight frontend editor.
