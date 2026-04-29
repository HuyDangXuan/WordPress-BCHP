# HV-Travel Local E2E Acceptance

This checklist prepares a local demo baseline before moving to a Render deployment.

## Automated Smoke

Run:

```powershell
node scripts/acceptance-smoke.mjs
```

The script uses:

- `WORDPRESS_BASE_URL`, default `http://localhost:8080`.
- `SERVICE_BASE_URL`, default `http://localhost:8787`.

It checks service health, homepage, `/tours/`, the seeded single tour, the payment confirm route, and mojibake artifacts.

## Demo Data

1. Start the local stack with `docker compose -f docker/compose.local.yml up -d --build`.
2. Confirm WooCommerce, `OP Travel Shop`, and `OP Travel Core` are active.
3. Run `Tools > OP Travel Seeder > Seed Demo Data`.
4. Confirm at least 3 seeded tours are visible in `/tours/`.
5. Confirm seeded taxonomy terms exist for `destination` and `tour_style`.

## Manual Booking Flow

1. Open `/tours/premium-hoang-hon-phu-quoc/`.
2. Select a valid departure date.
3. Set `adult_count` to at least `1`.
4. Set `child_count` to `0` or more.
5. Add a customer note.
6. Add to cart and confirm booking metadata persists.
7. Checkout and create an order.
8. Confirm the thank-you page shows the payment panel.
9. Open wp-admin order detail and confirm booking metadata is readable.

## Required Sample Orders

- Keep one order in `pending` for fallback QR and waiting-payment narration.
- Keep one order in `paid` for successful payment narration.
- The paid order can be produced with a signed webhook fixture; it does not require live payOS credentials in this phase.

## Webhook Fixture Acceptance

1. Create a fresh pending order through storefront checkout.
2. Confirm MongoDB has a matching record in `bookings`.
3. Confirm MongoDB has a matching record in `payments`.
4. Send a signed `paid` webhook fixture to `POST /api/payments/payos/webhook`.
5. Confirm MongoDB writes one `payment_events` record.
6. Confirm `payments.status` becomes `paid`.
7. Confirm `bookings.payment_status` becomes `paid`.
8. Confirm WordPress order meta changes through `POST /wp-json/op-travel/v1/payment-confirm`.
9. Send the same webhook again and confirm the duplicate result does not create a second processed event.

## Revenue Report Acceptance

Run:

```powershell
Invoke-WebRequest -UseBasicParsing "http://localhost:8787/api/reports/revenue?from=2026-01-01&to=2026-12-31"
```

Expected:

- `revenue_total` includes only `paid` bookings.
- `paid_bookings` matches paid booking count.
- `total_bookings` includes pending and paid bookings in the date range.

## Fallback QR

- If payOS provider URLs are empty, the theme must still show the fallback QR panel.
- The fallback QR is the default demo-safe path for local and defense rehearsal.
- Do not present fallback QR as the primary production gateway; payOS remains the main target for later live integration.

## Demo Risk Notes

- If live checkout is slow, use the prepared `pending` and `paid` orders.
- If webhook testing is slow, show MongoDB records and the order meta state.
- If networking fails, switch to screenshots or the local stack instead of debugging in front of the panel.
