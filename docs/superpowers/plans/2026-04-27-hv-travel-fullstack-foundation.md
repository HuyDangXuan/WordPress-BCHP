# HV-Travel Fullstack Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first runnable HV-Travel fullstack monorepo slice with a four-service local stack, a premium WordPress storefront theme, a business plugin, and a Mongo-backed booking-payment service that all follow the project phase documents.

**Architecture:** Use Docker Compose to run `wordpress`, `mysql`, `mongodb`, and `booking-payment-service`. Keep storefront and WooCommerce ownership in the WordPress theme, business logic and WordPress-side REST handling in the plugin, and MongoDB plus webhook/report concerns in the Node.js service.

**Tech Stack:** Docker Compose, WordPress official image, WooCommerce-compatible theme/plugin PHP, plain Node.js HTTP server, MongoDB, MySQL, Markdown docs

---

## File Structure Lock

**Create:**
- `docker/compose.local.yml`
- `docker/wordpress/Dockerfile`
- `docker/service/Dockerfile`
- `env/wordpress.env.example`
- `env/service.env.example`
- `services/booking-payment-service/package.json`
- `services/booking-payment-service/README.md`
- `services/booking-payment-service/src/app.js`
- `services/booking-payment-service/src/config/env.js`
- `services/booking-payment-service/src/lib/http.js`
- `services/booking-payment-service/src/lib/json.js`
- `services/booking-payment-service/src/lib/response.js`
- `services/booking-payment-service/src/routes/bookings.js`
- `services/booking-payment-service/src/routes/health.js`
- `services/booking-payment-service/src/routes/payments.js`
- `services/booking-payment-service/src/routes/reports.js`
- `services/booking-payment-service/src/services/callback-wordpress.js`
- `services/booking-payment-service/src/services/mongo.js`
- `services/booking-payment-service/test/env.test.js`
- `services/booking-payment-service/test/routes.test.js`
- `wordpress/wp-content/themes/op-travel-shop/style.css`
- `wordpress/wp-content/themes/op-travel-shop/functions.php`
- `wordpress/wp-content/themes/op-travel-shop/index.php`
- `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`
- `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`
- `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- `wordpress/wp-content/themes/op-travel-shop/inc/workflow.php`
- `wordpress/wp-content/themes/op-travel-shop/inc/woocommerce.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php`
- `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`
- `wordpress/wp-content/plugins/op-travel-core/op-travel-core.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/ProductMeta.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/Rest/PaymentConfirmController.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/Support/Env.php`
- `wordpress/wp-content/plugins/op-travel-core/includes/Support/OrderMeta.php`
- `README.md`

**Modify:**
- `docs/superpowers/specs/2026-04-27-hv-travel-monorepo-local-stack-design.md` only if implementation reveals a spec mismatch

## Visual Direction Lock

- Visual thesis: premium coastal expedition with warm sand paper tones, dark ink typography, gold accents, and restrained cinematic motion.
- Content plan: poster hero, curated journeys, taxonomy-led discovery, booking reassurance, final conversion CTA.
- Interaction thesis: staggered hero reveal, gentle parallax drift on featured imagery, and hover-lift on shortlisted tours.

### Task 1: Repository Foundation and Env Contracts

**Files:**
- Create: `env/wordpress.env.example`
- Create: `env/service.env.example`
- Create: `README.md`

- [ ] **Step 1: Write the failing env contract test for the service**

```js
import test from 'node:test';
import assert from 'node:assert/strict';
import { loadEnv } from '../src/config/env.js';

test('loadEnv requires all documented service variables', () => {
  assert.throws(() => loadEnv({}), /MONGO_URI/);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test services/booking-payment-service/test/env.test.js`
Expected: FAIL because `loadEnv` does not exist yet

- [ ] **Step 3: Create env example files and root README skeleton**

Create examples for:
- WordPress: `WORDPRESS_DB_HOST`, `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`, `WP_DEBUG`, `PAYMENT_SYNC_SECRET`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`
- Service: `MONGO_URI`, `PAYOS_CLIENT_ID`, `PAYOS_API_KEY`, `PAYOS_CHECKSUM_KEY`, `PAYMENT_SYNC_SECRET`, `WORDPRESS_CONFIRM_ENDPOINT`

Create `README.md` sections:
- overview
- structure
- local setup
- smoke checklist

- [ ] **Step 4: Implement `loadEnv` minimally**

```js
export function loadEnv(source = process.env) {
  const required = [
    'MONGO_URI',
    'PAYOS_CLIENT_ID',
    'PAYOS_API_KEY',
    'PAYOS_CHECKSUM_KEY',
    'PAYMENT_SYNC_SECRET',
    'WORDPRESS_CONFIRM_ENDPOINT',
  ];
  for (const key of required) {
    if (!source[key]) throw new Error(`Missing required environment variable: ${key}`);
  }
  return Object.fromEntries(required.map((key) => [key, source[key]]));
}
```

- [ ] **Step 5: Run the env test to verify it passes**

Run: `node --test services/booking-payment-service/test/env.test.js`
Expected: PASS

### Task 2: Docker Local Stack

**Files:**
- Create: `docker/compose.local.yml`
- Create: `docker/wordpress/Dockerfile`
- Create: `docker/service/Dockerfile`

- [ ] **Step 1: Write a failing compose structure check**

Add an assertion in `services/booking-payment-service/test/routes.test.js` or a dedicated test to verify the documented service names appear in the compose file content.

```js
assert.match(composeText, /booking-payment-service:/);
assert.match(composeText, /mongodb:/);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test services/booking-payment-service/test/routes.test.js`
Expected: FAIL because compose file does not exist yet

- [ ] **Step 3: Create Docker assets**

Compose must define:
- `wordpress`
- `mysql`
- `mongodb`
- `booking-payment-service`

Compose must include:
- named volumes for MySQL and MongoDB
- bind mounts for `wordpress/wp-content/themes/op-travel-shop` and `wordpress/wp-content/plugins/op-travel-core`
- env files from `env/*.example` as documented placeholders

- [ ] **Step 4: Add minimal Dockerfiles**

- `docker/wordpress/Dockerfile`: extend official WordPress image and copy a custom `php.ini` only if needed
- `docker/service/Dockerfile`: use `node:20-alpine`, copy the service, run `node src/app.js`

- [ ] **Step 5: Verify compose definition**

Run: `docker compose -f docker/compose.local.yml config`
Expected: valid rendered configuration

### Task 3: Booking-Payment Service

**Files:**
- Create: `services/booking-payment-service/package.json`
- Create: `services/booking-payment-service/src/app.js`
- Create: `services/booking-payment-service/src/config/env.js`
- Create: `services/booking-payment-service/src/lib/http.js`
- Create: `services/booking-payment-service/src/lib/json.js`
- Create: `services/booking-payment-service/src/lib/response.js`
- Create: `services/booking-payment-service/src/routes/health.js`
- Create: `services/booking-payment-service/src/routes/bookings.js`
- Create: `services/booking-payment-service/src/routes/payments.js`
- Create: `services/booking-payment-service/src/routes/reports.js`
- Create: `services/booking-payment-service/src/services/callback-wordpress.js`
- Create: `services/booking-payment-service/src/services/mongo.js`
- Test: `services/booking-payment-service/test/env.test.js`
- Test: `services/booking-payment-service/test/routes.test.js`

- [ ] **Step 1: Write failing route tests**

```js
test('GET /health returns ok payload', async () => {
  const response = await request(server, { method: 'GET', path: '/health' });
  assert.equal(response.statusCode, 200);
  assert.equal(response.body.status, 'ok');
});

test('POST /api/bookings returns pending booking snapshot response', async () => {
  const response = await request(server, {
    method: 'POST',
    path: '/api/bookings',
    body: { wordpress_order_id: 1024, payment_status: 'pending' },
  });
  assert.equal(response.statusCode, 202);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `node --test services/booking-payment-service/test/*.test.js`
Expected: FAIL because the app/router does not exist yet

- [ ] **Step 3: Implement the minimal HTTP app**

Requirements:
- no external runtime dependencies
- route by method and pathname
- parse JSON bodies
- return JSON for all application endpoints
- expose `GET /health`
- expose `POST /api/bookings`
- expose `POST /api/payments/payos/webhook`
- expose `GET /api/reports/revenue`

- [ ] **Step 4: Implement service behaviors**

Minimal behavior:
- `/health` returns service metadata and dependency placeholders
- `/api/bookings` echoes a booking snapshot with `payment_status: pending`
- `/api/payments/payos/webhook` records a placeholder event result and prepares a callback payload
- `/api/reports/revenue` returns a stub summary with `from` and `to`

- [ ] **Step 5: Re-run the service tests**

Run: `node --test services/booking-payment-service/test/*.test.js`
Expected: PASS

### Task 4: OP Travel Core Plugin

**Files:**
- Create: `wordpress/wp-content/plugins/op-travel-core/op-travel-core.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/ProductMeta.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/Rest/PaymentConfirmController.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/Support/Env.php`
- Create: `wordpress/wp-content/plugins/op-travel-core/includes/Support/OrderMeta.php`

- [ ] **Step 1: Write a failing source verification check**

Use a source-level assertion in Node or PowerShell to verify the plugin defines:
- `destination`
- `tour_style`
- `/wp-json/op-travel/v1/payment-confirm`

- [ ] **Step 2: Run the verification to confirm failure**

Run: `Get-Content wordpress/wp-content/plugins/op-travel-core/op-travel-core.php`
Expected: missing file or missing markers

- [ ] **Step 3: Implement plugin bootstrap and CMS setup**

Requirements:
- register activation hook
- seed/match page slugs `trang-chu`, `tours`, `gio-hang`, `thanh-toan`, `tai-khoan`, `lien-he`, `blog`
- register taxonomies `destination` and `tour_style`
- register CPTs `promotion` and `testimonial`
- set product permalink base to `/tours/`

- [ ] **Step 4: Implement booking and payment modules**

Requirements:
- render booking fields on single product
- validate booking fields before add to cart
- persist booking fields into cart item data and order item meta
- register `POST /wp-json/op-travel/v1/payment-confirm`
- protect payment confirm route with `PAYMENT_SYNC_SECRET`
- store business payment state in order meta for theme rendering

- [ ] **Step 5: Verify PHP syntax**

Run: `Get-ChildItem -Recurse wordpress/wp-content/plugins/op-travel-core -Filter *.php | ForEach-Object { php -l $_.FullName }`
Expected: no syntax errors

### Task 5: OP Travel Shop Theme

**Files:**
- Create: `wordpress/wp-content/themes/op-travel-shop/style.css`
- Create: `wordpress/wp-content/themes/op-travel-shop/functions.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/index.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/front-page.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css`
- Create: `wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js`
- Create: `wordpress/wp-content/themes/op-travel-shop/inc/setup.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/inc/workflow.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/inc/woocommerce.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php`
- Create: `wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`

- [ ] **Step 1: Write a failing source verification check for the theme**

Verify the theme source references:
- `Cormorant Garamond`
- `Manrope`
- CSS variables `--op-sand`, `--op-cream`, `--op-ink`, `--op-gold`, `--op-sea`
- the four journey steps

- [ ] **Step 2: Run the verification to confirm failure**

Run: `Get-ChildItem -Recurse wordpress/wp-content/themes/op-travel-shop`
Expected: files do not exist yet

- [ ] **Step 3: Implement theme setup and visual shell**

Requirements:
- register theme supports and menus
- enqueue `theme.css`, `theme.js`, and the required Google Fonts
- implement reusable workflow helper data
- disable WooCommerce default styles

- [ ] **Step 4: Implement storefront templates**

Requirements:
- `front-page.php`: full-bleed premium hero, featured journeys, workflow section, taxonomy-led discovery CTA, final CTA
- `archive-product.php`: travel-first shortlist with destination and style filters
- `single-product.php`: booking-focused tour detail page
- `cart.php`: “Xác nhận giữ chỗ” framing with booking metadata
- `form-checkout.php`: workflow shell for payment
- `thankyou.php`: states for `pending`, `paid`, `failed`, `expired`, `cancelled`

- [ ] **Step 5: Verify theme PHP syntax and source markers**

Run: `Get-ChildItem -Recurse wordpress/wp-content/themes/op-travel-shop -Filter *.php | ForEach-Object { php -l $_.FullName }`
Expected: no syntax errors

### Task 6: Documentation and Smoke Verification

**Files:**
- Modify: `README.md`
- Create: `services/booking-payment-service/README.md`

- [ ] **Step 1: Document local bootstrap commands**

Add:
- branch name and repo expectation
- env copy instructions
- `docker compose -f docker/compose.local.yml up -d --build`
- WordPress admin bootstrap notes
- theme/plugin activation notes

- [ ] **Step 2: Document the smoke checklist**

Checklist must include:
- WordPress comes up
- service routes respond
- theme/plugin mount correctly
- `/tours/` target is documented
- payment confirm contract path is documented

- [ ] **Step 3: Run all available verifications**

Run:
- `node --test services/booking-payment-service/test/*.test.js`
- `docker compose -f docker/compose.local.yml config`
- PHP syntax checks for theme and plugin if PHP exists locally

Expected:
- Node tests pass
- Compose config renders
- PHP files are syntactically valid, or report if PHP is unavailable

- [ ] **Step 4: Commit the implementation slice**

```bash
git add README.md docker env services wordpress
git commit -m "feat: scaffold hv-travel fullstack foundation"
```
