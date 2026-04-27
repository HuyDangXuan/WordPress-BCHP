# Phase 10 - Phụ lục API, biến môi trường và checklist

## Mục tiêu phase
Tập hợp mọi thông tin kỹ thuật tra cứu nhanh của đồ án: plugin, biến môi trường, endpoint, payload mẫu, cấu trúc MongoDB và checklist vận hành.

## Đầu vào
- Kiến trúc tổng thể đã chốt
- Luồng booking/payment/MongoDB/deploy ở các phase trước

## Đầu ra
- Một file phụ lục dùng để dựng slide, viết báo cáo hoặc cấu hình nhanh
- Nơi tập trung các hợp đồng kỹ thuật quan trọng nhất của dự án

## Ý nghĩa với BCCĐ
Phụ lục làm cho bộ tài liệu trở nên thực dụng. Khi cần tra cứu nhanh endpoint, env hay checklist trước demo, người làm đồ án không phải lục lại toàn bộ các phase trước.

## Nguyên tắc dùng phụ lục
- Đây là nguồn tra cứu nhanh cho contract kỹ thuật, không phải nơi tự đặt lại tên route, status hay env.
- Nếu một phase khác dùng endpoint/env khác với file này, phải xem file kia là lệch và chỉnh lại.
- Các status business chuẩn chỉ gồm: `pending`, `paid`, `failed`, `expired`, `cancelled`.

## Danh sách plugin
- `WooCommerce`: lõi thương mại điện tử
- `OP Travel Core`: plugin nghiệp vụ tùy biến
- `payOS`: thanh toán online chính
- `BCK`: QR/chuyển khoản tự động dự phòng
- `WP Mail SMTP`: gửi email
- `UpdraftPlus`: backup
- `Wordfence`: bảo mật

## Danh sách biến môi trường
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

## Mẫu endpoint
### Nội bộ WordPress
```http
POST /wp-json/op-travel/v1/payment-confirm
Content-Type: application/json
Authorization: Bearer <PAYMENT_SYNC_SECRET>
```

### Booking service
```http
POST /api/bookings
Content-Type: application/json
```

### Payment webhook
```http
POST /api/payments/payos/webhook
Content-Type: application/json
```

### Revenue report
```http
GET /api/reports/revenue?from=2026-01-01&to=2026-12-31
```

## Vocabulary trạng thái nghiệp vụ
| Status | Ý nghĩa ngắn |
| --- | --- |
| `pending` | Đơn/giao dịch đã tạo nhưng chưa xác nhận thanh toán |
| `paid` | Đã có xác nhận thanh toán hợp lệ |
| `failed` | Giao dịch lỗi |
| `expired` | QR/link thanh toán hết hạn |
| `cancelled` | Giao dịch hoặc đơn bị hủy |

## Mẫu payload booking
```json
{
  "wordpress_order_id": 1024,
  "wordpress_order_key": "wc_order_abcd1234",
  "product_id": 88,
  "tour_code": "DL-HUE-3N2D",
  "tour_name": "Huế - Đà Nẵng - Hội An 3N2Đ",
  "departure_date": "2026-05-20",
  "adult_count": 2,
  "child_count": 1,
  "customer_note": "Ăn chay, đón tại quận 1",
  "customer_name": "Nguyễn Văn A",
  "customer_email": "a@example.com",
  "customer_phone": "0900000000",
  "amount": 12990000,
  "currency": "VND",
  "payment_status": "pending"
}
```

## Mẫu payload webhook
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

## Mẫu cấu trúc collection MongoDB
### `bookings`
```json
{
  "booking_code": "BK-2026-0001",
  "wordpress_order_id": 1024,
  "tour_code": "DL-HUE-3N2D",
  "departure_date": "2026-05-20",
  "adult_count": 2,
  "child_count": 1,
  "payment_status": "pending",
  "created_at": "2026-04-27T15:00:00Z"
}
```

### `payments`
```json
{
  "payment_code": "PMT-2026-1024",
  "booking_code": "BK-2026-0001",
  "gateway": "payos",
  "amount": 12990000,
  "status": "paid",
  "checkout_url": "https://pay.example.com/checkout/123",
  "qr_url": "https://pay.example.com/qr/123",
  "paid_at": "2026-04-27T15:30:00Z"
}
```

### `payment_events`
```json
{
  "event_id": "evt_00001",
  "payment_code": "PMT-2026-1024",
  "provider": "payos",
  "event_type": "payment.succeeded",
  "signature_valid": true,
  "result": "processed",
  "received_at": "2026-04-27T15:30:01Z"
}
```

## Checklist trước khi demo
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Dữ liệu tour | Có ít nhất 2-3 tour mẫu | Cần chuẩn bị |
| Order mẫu | Có ít nhất 1 order `pending`, 1 order `paid` | Cần chuẩn bị |
| QR | Kiểm tra QR tải được | Cần kiểm tra |
| Network | Kiểm tra internet | Cần kiểm tra |
| SMTP | Kiểm tra mail nếu demo email | Cần kiểm tra |
| MongoDB | Kiểm tra service và collections | Cần kiểm tra |
| Slide | Chuẩn bị slide hoặc sơ đồ | Cần chuẩn bị |
| Video fallback | Có sẵn nếu live demo lỗi | Cần chuẩn bị |

## Checklist trước khi deploy
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Dockerfile | Có image cho WordPress | Cần bổ sung |
| Compose local | Có file local để test stack | Cần bổ sung |
| API service | Có `booking-payment-service` riêng | Cần bổ sung |
| Env production | Khai báo đầy đủ | Cần chuẩn bị |
| DB backup | Có bản sao trước deploy | Cần chuẩn bị |
| Persistent disk | Gắn cho DB và uploads | Cần xác nhận |
| Domain | Trỏ DNS đúng | Cần chuẩn bị |
| HTTPS | Đã bật SSL | Cần xác nhận |
| Smoke test | Có checklist sau deploy | Cần chuẩn bị |

## Checklist trước khi bảo vệ
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Source code | Mở đúng repo, đúng branch | Cần kiểm tra |
| Tài liệu | Bộ `Phases/` đầy đủ và nhất quán | Đã có |
| Site demo | Truy cập được | Cần kiểm tra |
| Tài khoản admin | Đăng nhập được | Cần kiểm tra |
| Tài khoản test | Có user khách mẫu nếu cần | Cần chuẩn bị |
| Luồng demo | Đã tập trước | Cần thực hiện |
| Câu hỏi phản biện | Có câu trả lời ngắn gọn | Cần chuẩn bị |
| Fallback plan | Có ảnh/video/order mẫu | Cần chuẩn bị |

## Tra cứu nhanh theo lớp hệ thống
| Lớp | Cần nhớ gì |
| --- | --- |
| WordPress/WooCommerce | `WORDPRESS_DB_*`, `WP_DEBUG`, `POST /wp-json/op-travel/v1/payment-confirm` |
| Booking/payment service | `MONGO_URI`, `PAYOS_*`, `PAYMENT_SYNC_SECRET`, `WORDPRESS_CONFIRM_ENDPOINT` |
| MongoDB | `bookings`, `payments`, `payment_events`, `contacts`, `reports` |
| Demo/bảo vệ | tour mẫu, order `pending`/`paid`, fallback QR/video, smoke checklist |

## Minh chứng trong source code
- `wp-config.php`
- `wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- `wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- `wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`
- `wp-content/plugins/bck-tu-dong-xac-nhan-thanh-toan-chuyen-khoan-ngan-hang/readme.txt`

## Những gì đã có
- Hệ thống đã có theme, plugin, QR demo, checkout flow
- Các điểm hook quan trọng cho payment và booking đã hiện diện
- Bộ tài liệu phase đã định nghĩa rõ hợp đồng kỹ thuật

## Những gì cần bổ sung để hoàn thiện đồ án
- Hoàn thiện service thật cho MongoDB và payOS
- Hoàn thiện Docker assets
- Gắn biến môi trường production
- Soạn hướng dẫn thao tác nhanh cho team demo
- Rà lại phụ lục này mỗi khi endpoint, env hay status thay đổi để tránh lệch phase

## Cách trình bày khi bảo vệ
- Dùng file này như “tờ nháp kỹ thuật” khi cần tra cứu nhanh.
- Nếu bị hỏi endpoint hoặc env, trả lời trực tiếp từ đây.
- Nếu bị hỏi MongoDB lưu gì, mở ngay phần collection mẫu.
- Nếu bị hỏi trước demo cần chuẩn bị gì, mở checklist cuối file.

## Kết luận phase
Phase 10 biến toàn bộ hệ thống thành một bộ tài liệu tra cứu nhanh có tính vận hành. Đây là phần đệm rất tốt trước phase cuối cùng, nơi mọi nội dung kỹ thuật được chuyển hóa thành lời nói ngắn gọn, mạch lạc để dùng ngay trong buổi bảo vệ.
