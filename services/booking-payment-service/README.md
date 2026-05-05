# Booking Payment Service

This service owns the MongoDB-facing business endpoints for HV-Travel.

## Endpoints

- `GET /health`
- `POST /api/bookings`
- `POST /api/payments/payos/webhook`
- `POST /api/payments/zalopay/callback`
- `GET /api/reports/revenue`

## Environment

Required variables:

- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `ZALOPAY_APP_ID`
- `ZALOPAY_KEY1`
- `ZALOPAY_KEY2`
- `ZALOPAY_ENV`
- `ZALOPAY_CALLBACK_URL`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

## Local Run

```powershell
node src/app.js
```

Default port: `8787`
