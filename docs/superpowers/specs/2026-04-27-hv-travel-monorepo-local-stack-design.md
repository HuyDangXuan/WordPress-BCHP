# HV-Travel Monorepo Local Stack Design

**Date:** 2026-04-27

**Status:** Approved for planning

**Goal**

Scaffold a local-first HV-Travel monorepo that keeps the project aligned with the existing phase documents: WordPress and WooCommerce remain the storefront and order core on MySQL, while MongoDB and a separate booking-payment service handle business-side extensions without crossing architectural boundaries.

## Context

The current repository contains the HV-Travel phase documents and project-specific skills, but does not yet contain the working WordPress source tree, Docker assets, or the business service runtime. The first implementation cycle therefore needs to create the runtime scaffold without overcommitting to unfinished business features.

This design is based on:

- `Phases/01-phase-1-tong-quan-de-tai-va-kien-truc.md`
- `Phases/02-phase-2-cau-hinh-moi-truong-va-he-thong.md`
- `Phases/08-phase-8-docker-va-deploy-render.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`
- `Skills/hv-travel-project-context/SKILL.md`

## Design Principles

- Keep `WordPress + WooCommerce + MySQL` as the only storefront and order system of record.
- Keep `MongoDB` behind a separate service; do not use it as a replacement for the WordPress database.
- Keep visual and WooCommerce template responsibilities in `op-travel-shop`.
- Keep tour metadata, setup hooks, booking hooks, and WordPress REST integration in `op-travel-core`.
- Keep payment webhook handling, business-side sync, and reporting endpoints in the separate service.
- Prefer a runnable scaffold over incomplete feature depth in the first cycle.
- Match phase vocabulary and environment variable names exactly so later phases do not need contract rewrites.

## Target Repository Structure

The repository should become a documentation-and-source monorepo:

```text
WordPress/
|-- Phases/
|-- Skills/
|-- docs/
|   `-- superpowers/
|       |-- specs/
|       `-- plans/
|-- docker/
|   |-- compose.local.yml
|   |-- wordpress/
|   |   `-- Dockerfile
|   `-- service/
|       `-- Dockerfile
|-- env/
|   |-- wordpress.env.example
|   `-- service.env.example
|-- services/
|   `-- booking-payment-service/
|       |-- package.json
|       |-- src/
|       |   |-- app.js
|       |   |-- routes/
|       |   |-- services/
|       |   `-- config/
|       `-- README.md
`-- wordpress/
    |-- wp-config-docker.php
    `-- wp-content/
        |-- themes/
        |   `-- op-travel-shop/
        `-- plugins/
            `-- op-travel-core/
```

## Architecture Boundaries

### WordPress Layer

`wordpress/` owns:

- WordPress runtime files needed for local execution
- WooCommerce integration
- storefront templates and theme behavior
- admin-managed content
- WooCommerce cart, checkout, and order lifecycle
- WordPress-side payment confirmation endpoint

It does not own:

- direct MongoDB access
- external webhook processing
- payment provider signature validation logic beyond its own callback trust boundary

### Theme Layer

`wordpress/wp-content/themes/op-travel-shop/` owns:

- storefront presentation
- tour browsing experience
- WooCommerce template overrides
- workflow-specific layout helpers for the booking journey

It must not own:

- booking persistence logic
- webhook handling
- business-side reporting logic

### Plugin Layer

`wordpress/wp-content/plugins/op-travel-core/` owns:

- tour-related setup and seed helpers
- CMS and page mapping bootstrap
- tour metadata and booking-related hooks
- WordPress REST bridge endpoint `POST /wp-json/op-travel/v1/payment-confirm`

It must not own:

- theme presentation concerns outside required rendering hooks
- direct access to MongoDB
- standalone payment service responsibilities

### Business Service Layer

`services/booking-payment-service/` owns:

- `POST /api/bookings`
- `POST /api/payments/payos/webhook`
- `GET /api/reports/revenue`
- MongoDB reads and writes
- callback to WordPress using `PAYMENT_SYNC_SECRET` and `WORDPRESS_CONFIRM_ENDPOINT`
- request logging and dependency-aware health reporting

It must not own:

- WordPress page rendering
- WooCommerce checkout rendering
- WordPress core data as an alternate source of truth

## Local Runtime Topology

The local stack must start with four services, matching the project phases:

- `wordpress`
- `mysql`
- `mongodb`
- `booking-payment-service`

### Expected Service Relationships

- `wordpress` connects only to `mysql` for core CMS and WooCommerce data.
- `booking-payment-service` connects to `mongodb` for business-side data.
- `booking-payment-service` calls back into `wordpress` through the internal payment confirm endpoint.
- `wordpress` does not connect directly to `mongodb`.

### Compose Goals

`docker/compose.local.yml` should:

- boot the full four-service stack with one command
- expose WordPress locally for browser setup
- keep MySQL and MongoDB on named volumes
- mount theme and plugin source for iterative development
- pass configuration through env files rather than hard-coded secrets

## Environment Contracts

The scaffold must preserve the exact variable names already defined by the phase documents.

### WordPress Environment

- `WORDPRESS_DB_HOST`
- `WORDPRESS_DB_NAME`
- `WORDPRESS_DB_USER`
- `WORDPRESS_DB_PASSWORD`
- `WP_DEBUG`
- `PAYMENT_SYNC_SECRET`

### Business Service Environment

- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

### Email Environment

- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`

The first scaffold should provide `.env.example` files only. Real secrets remain out of source control.

## WordPress Scaffold Scope

The first cycle should scaffold enough WordPress structure to make later theme and plugin phases additive rather than corrective.

### Theme Scaffold

`op-travel-shop` should start with:

- `style.css`
- `functions.php`
- `index.php`
- `inc/setup.php`
- `inc/workflow.php`
- `inc/woocommerce.php`
- a `woocommerce/` directory reserved for future template overrides

The theme only needs a minimal visual shell in this cycle. Its purpose is to prove activation and provide the correct ownership boundary.

### Plugin Scaffold

`op-travel-core` should start with:

- main plugin bootstrap file
- `includes/CmsSetup.php`
- `includes/BookingHooks.php`
- `includes/Rest/PaymentConfirmController.php`

The plugin should register:

- activation-time setup hooks needed for basic page and option bootstrap
- the WordPress REST route for internal payment confirmation
- placeholder booking hooks with clear extension points for later phases

## Business Service Scaffold Scope

The first cycle should implement a lightweight Node.js service with simple structure, not a heavyweight framework.

The service should include:

- `GET /health`
- `POST /api/bookings`
- `POST /api/payments/payos/webhook`
- `GET /api/reports/revenue`
- a startup configuration validator
- a thin WordPress callback client
- MongoDB connectivity plumbing

The service should return clear placeholder responses where business rules are not implemented yet, but the route names, payload boundaries, and dependency wiring must already match the project contracts.

## Data Flow

The intended first-cycle flow is:

1. WordPress and WooCommerce run on top of MySQL.
2. Theme and plugin activate inside WordPress.
3. The business service starts independently and validates its environment.
4. Booking and payment routes exist on the service side, even if the business logic is still minimal.
5. Payment-related events can be translated into a callback to `POST /wp-json/op-travel/v1/payment-confirm`.

This keeps the architecture demonstrable without falsely claiming completed payOS integration.

## Error Handling Strategy

The first scaffold must be explicit about failure modes.

### Startup Errors

- Missing required environment variables should fail fast with the variable name in the error output.
- Service startup should not silently continue with invalid configuration.

### Dependency Errors

- If MySQL is not ready, WordPress should rely on container restart and normal application retry behavior.
- If MongoDB is unavailable, the business service should stay observable and return a degraded health status rather than crash without explanation.

### Callback Errors

- Failed callbacks from the business service to WordPress should be logged with endpoint and status information.
- Callback failure must not terminate the Node.js process.

### Scope Guardrails

- The scaffold must not claim real payOS readiness.
- The scaffold must not implement direct WordPress-to-Mongo coupling.
- The scaffold must not collapse all responsibilities into one container or one plugin file.

## Verification Criteria

The first implementation cycle is successful when all of the following are true:

- `docker compose -f docker/compose.local.yml up -d --build` boots the four-service stack.
- WordPress is reachable locally and can proceed through initial setup.
- MySQL and MongoDB persist on separate named volumes.
- The custom theme and plugin are mounted into WordPress and can be activated.
- The WordPress REST route for payment confirmation is registered.
- The business service exposes `GET /health`.
- The business service exposes the contract routes from phase 10.
- The repository contains a local run guide and env examples.

## Out of Scope for This First Cycle

The first scaffold will not include:

- completed payOS integration
- full booking workflow logic
- full tour UI implementation
- production-grade Render deployment assets beyond the local topology foundation
- SMTP delivery setup with live credentials
- real reporting logic over MongoDB

These remain follow-up tasks owned by later phases.

## Traceability to Phase Documents

- `Phase 1`: enforces the system boundary between WordPress/MySQL and MongoDB/service logic
- `Phase 2`: drives the local stack, WordPress setup, plugin/theme ownership, env naming, and smoke expectations
- `Phase 8`: drives the four-service topology and service separation
- `Phase 10`: defines the route names, business statuses, and environment contract names that must be preserved

## Implementation Intent

The implementation plan following this document should focus on:

1. creating the monorepo runtime structure
2. scaffolding Docker assets and env templates
3. scaffolding WordPress theme and plugin ownership boundaries
4. scaffolding the Node.js business service with contract routes
5. documenting local bootstrap and smoke verification
