# HV-Travel Fullstack Monorepo

HV-Travel is a WordPress and WooCommerce tour-booking stack organized as a monorepo. The repository keeps the original phase documents and skills alongside the runnable local stack for the storefront, business plugin, payment service, and Docker topology.

## Structure

- `Phases/`: project phase documents
- `Skills/`: HV-Travel project skills
- `docker/`: local stack Docker assets
- `env/`: runtime env files and `.example` templates
- `services/booking-payment-service/`: Mongo-backed business service
- `wordpress/`: theme and plugin source mounted into WordPress

## Local Setup

The current implementation branch is `codex/hv-travel-fullstack`.

The local stack targets four core services:

- `wordpress`
- `mysql`
- `mongodb`
- `booking-payment-service`

For a public SePay webhook on your own hostname, the Compose file also includes an optional `cloudflared` service behind the `public-webhook` profile.

1. If `env/wordpress.env`, `env/service.env`, or `env/tunnel.env` do not exist yet, create them from the matching `*.example` files.
2. Review and adjust the runtime values in `env/wordpress.env` and `env/service.env`.
3. If you want SePay to call your local stack through a real domain, set `TUNNEL_TOKEN` in `env/tunnel.env`.
4. Keep the `*.example` files as placeholders only; do not store live secrets in them.
5. Start the core stack:

```powershell
docker compose -f docker/compose.local.yml up -d --build
```

6. If you want the public webhook domain live from Docker, start the tunnel profile:

```powershell
docker compose -f docker/compose.local.yml --profile public-webhook up -d cloudflared
```

7. Point SePay webhook to `https://<your-domain>/api/payments/sepay/webhook`.
8. Open `http://localhost:8080` and complete the normal WordPress install flow.
9. Install and activate `WooCommerce`.
10. Activate the theme `OP Travel Shop`.
11. Activate the plugin `OP Travel Core`.
12. Activate the plugin `OP Travel SePay`.
13. Confirm the shop, cart, checkout, and account page mapping created by the plugin set.

## Smoke Checklist

- `docker compose -f docker/compose.local.yml config` renders without errors
- WordPress boots through Docker Compose on `http://localhost:8080`
- The booking-payment service responds on `http://localhost:8787/health`
- The optional `public-webhook` profile can run `cloudflared` with `TUNNEL_TOKEN` from `env/tunnel.env`
- The `op-travel-shop` theme mounts into WordPress and can be activated
- The `op-travel-core` plugin mounts into WordPress and can be activated
- The `op-travel-sepay` plugin mounts into WordPress and can be activated
- The payment confirm contract path exists as `POST /wp-json/op-travel/v1/payment-confirm`
- The business service exposes:
  - `POST /api/bookings`
  - `POST /api/payments/payos/webhook`
  - `POST /api/payments/sepay/webhook`
  - `GET /api/reports/revenue`

Run the local acceptance smoke script after seeding demo data:

```powershell
node scripts/acceptance-smoke.mjs
```

## Render-Ready Deployment

This phase is Render-ready only. It adds deployment packaging and QA runbooks, but does not perform a live Render deploy or require production credentials.

- `render.yaml`: Render Blueprint for public WordPress plus private `booking-payment-service`, `mysql`, and `mongodb`.
- `docs/deploy/render-runbook.md`: service matrix, env mapping, persistent disk paths, backup/restore, rollback, and post-deploy smoke checklist.
- `docs/demo/local-e2e-acceptance.md`: local E2E checklist, sample `pending` and `paid` orders, webhook duplicate test, revenue report, and fallback QR acceptance.

## Render Free Demo

Use this path if you need a no-cost Render demo instead of the full paid four-service topology.

- `render.free-demo.yaml`: deploys only `booking-payment-service` as a Render Free Web Service.
- `docs/deploy/render-free-demo.md`: explains how to pair the free Render service with local WordPress and an external MongoDB Atlas free-tier URI.

This is not a full-stack Render deployment. WordPress, MySQL, and the full storefront acceptance flow still run locally.

## Current Verification Notes

- Node service tests are runnable locally with `node --test services/booking-payment-service/test/*.test.js`
- Docker Compose configuration has been validated locally
- PHP syntax checks should be run inside the WordPress container for the mounted plugin and theme
