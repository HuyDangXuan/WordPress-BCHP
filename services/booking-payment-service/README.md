# Booking Payment Service

This service owns the MongoDB-facing business endpoints for HV-Travel.

## Endpoints

- `GET /health`
- `POST /api/bookings`
- `POST /api/payments/payos/webhook`
- `GET /api/reports/revenue`

## Environment

Required variables:

- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

## Local Run

```powershell
node src/app.js
```

Default port: `8787`
