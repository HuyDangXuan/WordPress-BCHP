# Booking Payment Service

This service owns the MongoDB-facing business endpoints for HV-Travel.

## Endpoints

- `GET /health`
- `POST /api/bookings`
- `POST /api/admin/demo-data/reset`
- `POST /api/payments/payos/webhook`
- `POST /api/payments/sepay/webhook`
- `GET /api/payments/status`
- `GET /api/reports/revenue`

## Environment

Documented variables:

- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `SEPAY_API_KEY`
- `SEPAY_API_TOKEN` optional, for active reconciliation against SePay transaction history when webhook delivery is delayed or fails
- `SEPAY_BANK_CODE`
- `SEPAY_ACCOUNT_NUMBER`
- `SEPAY_ACCOUNT_NAME`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

## Local Run

```powershell
node src/app.js
```

Default port: `8787`
