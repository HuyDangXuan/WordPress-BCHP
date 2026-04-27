# HV-Travel Fullstack Monorepo

HV-Travel is a WordPress and WooCommerce tour-booking stack organized as a monorepo. The repository keeps the original phase documents and skills alongside the runnable local stack for the storefront, business plugin, payment service, and Docker topology.

## Structure

- `Phases/`: project phase documents
- `Skills/`: HV-Travel project skills
- `docker/`: local stack Docker assets
- `env/`: environment templates
- `services/booking-payment-service/`: Mongo-backed business service
- `wordpress/`: theme and plugin source mounted into WordPress

## Local Setup

The current implementation branch is `codex/hv-travel-fullstack`.

The first runnable slice targets a four-service local stack:

- `wordpress`
- `mysql`
- `mongodb`
- `booking-payment-service`

1. Review the env templates in `env/wordpress.env.example` and `env/service.env.example`.
2. Adjust placeholder values if needed for your local or demo setup.
3. Start the stack:

```powershell
docker compose -f docker/compose.local.yml up -d --build
```

4. Open `http://localhost:8080` and complete the normal WordPress install flow.
5. Install and activate `WooCommerce`.
6. Activate the theme `OP Travel Shop`.
7. Activate the plugin `OP Travel Core`.
8. Confirm the shop, cart, checkout, and account page mapping created by the plugin.

## Smoke Checklist

- `docker compose -f docker/compose.local.yml config` renders without errors
- WordPress boots through Docker Compose on `http://localhost:8080`
- The booking-payment service responds on `http://localhost:8787/health`
- The `op-travel-shop` theme mounts into WordPress and can be activated
- The `op-travel-core` plugin mounts into WordPress and can be activated
- The payment confirm contract path exists as `POST /wp-json/op-travel/v1/payment-confirm`
- The business service exposes:
  - `POST /api/bookings`
  - `POST /api/payments/payos/webhook`
  - `GET /api/reports/revenue`

## Current Verification Notes

- Node service tests are runnable locally with `node --test services/booking-payment-service/test/*.test.js`
- Docker Compose configuration has been validated locally
- PHP CLI is not installed on this machine, so direct `php -l` syntax checks for theme and plugin were not run locally
