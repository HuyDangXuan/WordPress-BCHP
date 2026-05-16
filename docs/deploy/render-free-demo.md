# HV-Travel Render Free Demo

This path deploys only `booking-payment-service` as a Render Free Web Service. It is not a full-stack deployment.

Use this when the goal is to show that the business service can run online on Render without paying for private services or persistent disks.

## What This Deploys

| Item | Deployed to Render | Cost intent | Notes |
| --- | --- | --- | --- |
| `booking-payment-service-free` | Yes | Free Web Service | Uses `render.free-demo.yaml` |
| WordPress | No | Local only | Keep Docker local at `http://localhost:8080` |
| MySQL | No | Local only | Keep Docker local |
| MongoDB | Optional external | Free tier if available | Use MongoDB Atlas free tier or another external Mongo URI |

This is not full-stack Render. The full architecture remains in `render.yaml` and should be treated as a paid deployment because it uses private services and persistent disks.

## Required Render Values

Create a Blueprint from `render.free-demo.yaml`, then fill:

- `MONGO_URI`: an external MongoDB URI, recommended MongoDB Atlas free tier.
- `WORDPRESS_CONFIRM_ENDPOINT`: use `https://example.invalid/wp-json/op-travel/v1/payment-confirm` if you only need `/health`, `/api/bookings`, and `/api/reports/revenue`.
- `WORDPRESS_CONFIRM_ENDPOINT`: use a real public WordPress URL or tunnel URL if you want webhook callback E2E.

The demo payOS variables are intentionally non-live:

- `PAYOS_CLIENT_ID=demo-client-id`
- `PAYOS_API_KEY=demo-api-key`
- `PAYOS_CHECKSUM_KEY=demo-checksum-key`
- `PAYMENT_SYNC_SECRET=free-demo-change-me`

The demo SePay variables point the QR flow at a demo VietQR account:

- `SEPAY_API_KEY=free-demo-sepay-api-key`
- `SEPAY_BANK_CODE=Vietcombank`
- `SEPAY_ACCOUNT_NUMBER=0010000000355`
- `SEPAY_ACCOUNT_NAME=HV Travel Demo`

Replace these values with your real SePay API key and receiving bank account before running a real end-to-end payment flow. Do not use the demo values for production.

## MongoDB Atlas Free Setup

1. Create an Atlas free cluster.
2. Create a database user for the demo.
3. Add a temporary network access rule. For a short defense/demo, `0.0.0.0/0` is the simplest option, but remove it after the demo.
4. Copy the connection string and set it as `MONGO_URI`.
5. Keep the database name as `hv_travel` in the URI if you want to match local collection names.

## Deploy Steps

1. Push the repo to GitHub.
2. In Render, choose `New > Blueprint`.
3. Select this repo and set the Blueprint file path to `render.free-demo.yaml`.
4. Enter `MONGO_URI` and `WORDPRESS_CONFIRM_ENDPOINT`.
5. Deploy `booking-payment-service-free`.
6. Open `https://<render-service>.onrender.com/health`.
7. If local WordPress needs to call this service, set `BOOKING_SERVICE_ENDPOINT=https://<render-service>.onrender.com/api/bookings` in the WordPress environment and restart the local WordPress container.

## Smoke Checks

Run these from your machine:

```powershell
$env:SERVICE_BASE_URL="https://<render-service>.onrender.com"
$env:WORDPRESS_BASE_URL="http://localhost:8080"
node scripts/acceptance-smoke.mjs
```

Expected:

- Render service `/health` returns `200`.
- Local WordPress pages still pass.
- `payment-confirm` local route still returns a non-404 status.

## Limitations

- This is a service-only Render demo, not full WordPress deployment.
- Render Free Web Services can spin down when idle, so the first request can be slow.
- Free services do not provide persistent disks.
- Webhook callback to WordPress requires WordPress to be reachable from Render, usually through a public deploy or a tunnel.
- The full paid blueprint remains the correct production-style answer for Phase 8.
