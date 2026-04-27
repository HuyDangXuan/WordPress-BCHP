# HV-Travel Contracts Reference

## Phase Sources
- `Phases/README.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`

## Canonical Endpoints

| Layer | Method | Path | Notes |
| --- | --- | --- | --- |
| WordPress internal | `POST` | `/wp-json/op-travel/v1/payment-confirm` | Callback nội bộ từ payment service về WordPress |
| Booking service | `POST` | `/api/bookings` | Ghi snapshot booking sang service business |
| Payment webhook | `POST` | `/api/payments/payos/webhook` | Nhận webhook từ payOS |
| Revenue report | `GET` | `/api/reports/revenue` | Đọc dữ liệu báo cáo doanh thu |

## Business Status Vocabulary

| Status | Ý nghĩa |
| --- | --- |
| `pending` | Đơn/giao dịch đã tạo nhưng chưa xác nhận thanh toán |
| `paid` | Đã có xác nhận thanh toán hợp lệ |
| `failed` | Giao dịch thất bại |
| `expired` | Link/QR hết hạn |
| `cancelled` | Người dùng hoặc admin hủy |

## Environment Variables

### WordPress
- `WORDPRESS_DB_HOST`
- `WORDPRESS_DB_NAME`
- `WORDPRESS_DB_USER`
- `WORDPRESS_DB_PASSWORD`
- `WP_DEBUG`

### Service nghiệp vụ
- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

### Email
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`

## Internal Confirm Contract

```http
POST /wp-json/op-travel/v1/payment-confirm
Content-Type: application/json
Authorization: Bearer <PAYMENT_SYNC_SECRET>
```

## Booking Payload Example

```json
{
  "wordpress_order_id": 1024,
  "wordpress_order_key": "wc_order_abcd1234",
  "product_id": 88,
  "tour_code": "DL-HUE-3N2D",
  "tour_name": "Hue - Da Nang - Hoi An 3N2D",
  "departure_date": "2026-05-20",
  "adult_count": 2,
  "child_count": 1,
  "customer_note": "An chay, don tai quan 1",
  "customer_name": "Nguyen Van A",
  "customer_email": "a@example.com",
  "customer_phone": "0900000000",
  "amount": 12990000,
  "currency": "VND",
  "payment_status": "pending"
}
```

## payOS Webhook Payload Example

```json
{
  "provider": "payos",
  "provider_transaction_id": "PAYOS-2026-000012",
  "wordpress_order_id": 1024,
  "payment_code": "PMT-2026-1024",
  "amount": 12990000,
  "currency": "VND",
  "status": "paid",
  "signature": "abcdef123456",
  "paid_at": "2026-04-27T15:30:00Z",
  "raw_payload": {
    "code": "00",
    "desc": "success"
  }
}
```

## MongoDB Collections

### `bookings`
- `booking_code`
- `wordpress_order_id`
- `wordpress_order_key`
- `product_id`
- `tour_code`
- `tour_name`
- `departure_date`
- `adult_count`
- `child_count`
- `customer_note`
- `customer_name`
- `customer_email`
- `customer_phone`
- `amount`
- `currency`
- `payment_status`
- `created_at`
- `updated_at`

### `payments`
- `payment_code`
- `booking_code`
- `wordpress_order_id`
- `gateway`
- `amount`
- `currency`
- `status`
- `checkout_url`
- `qr_url`
- `provider_transaction_id`
- `paid_at`
- `created_at`
- `updated_at`

### `payment_events`
- `event_id`
- `payment_code`
- `provider`
- `event_type`
- `signature_valid`
- `payload`
- `received_at`
- `processed_at`
- `result`

### `contacts`
- `full_name`
- `email`
- `phone`
- `message`
- `source_page`
- `created_at`

### `reports`
- `report_date`
- `total_bookings`
- `paid_bookings`
- `revenue_total`
- `top_destination`
- `generated_at`
