# HV-Travel Fullstack Monorepo

HV-Travel là một stack WordPress và WooCommerce chạy bằng Docker cho bài toán bán tour và xử lý thanh toán. Repo này chứa theme và plugin storefront, service backend dùng MongoDB cho booking và payment, cùng cấu hình hạ tầng local để bạn clone về và chạy ngay.

## Repo Này Có Gì

- WordPress storefront chạy tại `http://localhost:8080`
- MySQL cho dữ liệu WordPress và WooCommerce
- MongoDB cho `bookings`, `payments`, và `payment_events`
- `booking-payment-service` chạy tại `http://localhost:8787`
- `cloudflared` tùy chọn nếu bạn muốn public webhook hoặc public storefront bằng Cloudflare Tunnel

## Cấu Trúc Thư Mục

- `docker/`: Dockerfiles và cấu hình Docker Compose local
- `env/`: file môi trường runtime và các file mẫu `.example`
- `services/booking-payment-service/`: backend Node.js cho booking và payment
- `wordpress/`: theme và plugin custom mount vào WordPress
- `docs/demo/`: checklist demo và acceptance local
- `scripts/`: script smoke test và script hỗ trợ

## Yêu Cầu Trước Khi Chạy

- Git
- Docker Desktop có Docker Compose v2
- Node.js 20+ nếu bạn muốn chạy smoke script từ máy host
- Tài khoản Cloudflare và domain riêng nếu bạn muốn public hostname
- SePay credentials nếu bạn muốn test thanh toán live thay vì dùng giá trị demo

## Khởi Động Nhanh Ở Local

1. Clone repo:

```powershell
git clone <your-repo-url>
cd WordPress
```

2. Tạo file môi trường từ file mẫu:

```powershell
Copy-Item env\wordpress.env.example env\wordpress.env
Copy-Item env\service.env.example env\service.env
Copy-Item env\tunnel.env.example env\tunnel.env
```

3. Kiểm tra các file môi trường trước khi chạy:

- `env/wordpress.env`
  - Giữ `PUBLIC_SITE_URL=http://localhost:8080` nếu bạn chỉ chạy local.
  - Chỉ đổi `PUBLIC_SITE_URL` sang `https://<hostname-public>` khi bạn public WordPress qua Cloudflare Tunnel.
  - Điền SMTP nếu bạn cần test luồng email.
- `env/service.env`
  - File mẫu đang dùng giá trị demo-safe cho payment.
  - Chỉ thay `SEPAY_*` bằng thông tin thật nếu bạn muốn test live với SePay.
- `env/tunnel.env`
  - Giữ `TUNNEL_TOKEN=change-me` nếu bạn chưa cần public hostname.

4. Khởi động stack local:

```powershell
docker compose -f docker/compose.local.yml up -d --build
```

5. Mở WordPress và hoàn tất bước cài đặt ban đầu:

- Storefront: `http://localhost:8080`
- WordPress admin: `http://localhost:8080/wp-admin`
- phpMyAdmin: `http://localhost:8081`
- Service health: `http://localhost:8787/health`

Kết nối database đã được cấu hình sẵn qua environment variables. Ở bước cài WordPress bạn chỉ cần điền thông tin site và tài khoản admin.

6. Trong WordPress admin, cài và bật các thành phần cần thiết:

- Cài `WooCommerce`
- Activate theme `OP Travel Shop`
- Activate plugin `OP Travel Core`
- Activate plugin `OP Travel SePay`
- Activate plugin `OP Travel Storefront CMS` nếu bạn muốn chỉnh nội dung storefront theo route trực tiếp trong `wp-admin`

7. Seed dữ liệu demo:

- Vào `Tools > OP Travel Seeder`
- Chạy seed một lần
- Kiểm tra danh sách tour đã xuất hiện ở `/tours/`

## Các URL Hữu Ích Ở Local

- Storefront: `http://localhost:8080`
- WordPress admin: `http://localhost:8080/wp-admin`
- phpMyAdmin: `http://localhost:8081`
- Service health: `http://localhost:8787/health`
- Revenue report: `http://localhost:8787/api/reports/revenue`

## Public Bằng Cloudflare Tunnel

Phần này chỉ cần khi bạn muốn:

- nhận webhook SePay từ Internet
- public storefront bằng domain riêng

1. Điền Cloudflare named tunnel token vào `env/tunnel.env`:

```text
TUNNEL_TOKEN=<your-token>
```

2. Chạy container tunnel:

```powershell
docker compose -f docker/compose.local.yml --profile public-webhook up -d cloudflared
```

3. Trong Cloudflare Tunnel, tạo published application routes:

- `sepay.<your-domain>` -> `http://booking-payment-service:8787`
- `wp.<your-domain>` -> `http://wordpress:80`

4. Cập nhật `PUBLIC_SITE_URL` trong `env/wordpress.env`:

```text
PUBLIC_SITE_URL=https://wp.<your-domain>
```

5. Khởi động lại WordPress sau khi đổi public site URL:

```powershell
docker compose -f docker/compose.local.yml up -d wordpress
```

6. Mở hostname public bằng cửa sổ incognito trước. Nếu trước đó bạn đã truy cập site bằng `localhost:8080`, trình duyệt chính có thể đang giữ redirect cũ trong cache.

7. Nếu bạn muốn SePay gọi webhook thật, dùng URL:

```text
https://sepay.<your-domain>/api/payments/sepay/webhook
```

## Kiểm Tra Hệ Thống

Kiểm tra Docker Compose:

```powershell
docker compose -f docker/compose.local.yml config
docker compose -f docker/compose.local.yml ps
Invoke-WebRequest -UseBasicParsing http://localhost:8787/health
```

Chạy smoke script sau khi setup WordPress và seed dữ liệu demo:

```powershell
node scripts/acceptance-smoke.mjs
```

Smoke script sẽ kiểm tra:

- service health
- homepage
- `/tours/`
- một trang tour chi tiết đã seed
- route `payment-confirm` của WordPress
- lỗi mojibake / encoding

## Xử Lý Sự Cố Thường Gặp

- Public site bị redirect về `:8080`
  - Trình duyệt đang giữ cache redirect cũ. Hãy thử bằng incognito rồi xóa site data của domain đó.
- Public WordPress hostname báo lỗi SSL hoặc protocol
  - Hãy chắc rằng Cloudflare route đang trỏ tới `http://wordpress:80`, không phải `localhost:8080`.
- Service public chạy nhưng storefront không vào được
  - Bạn mới public backend hostname. Hãy tạo thêm route riêng cho WordPress.
- WordPress chạy local ổn nhưng link public vẫn sai domain
  - Cập nhật `PUBLIC_SITE_URL` trong `env/wordpress.env` rồi restart lại container `wordpress`.
- Cloudflare route chỉ match một phần site
  - Để path trống hoặc dùng pattern áp dụng cho toàn bộ đường dẫn của WordPress.

## Tài Liệu Liên Quan

- [docs/demo/local-e2e-acceptance.md](docs/demo/local-e2e-acceptance.md)
- [services/booking-payment-service/README.md](services/booking-payment-service/README.md)
