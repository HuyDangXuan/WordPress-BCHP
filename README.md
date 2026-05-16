# HV-Travel - Website Bán Tour Du Lịch Trực Tuyến

## 1. Tên đề tài
**HV-Travel - Website bán tour du lịch trực tuyến tích hợp WooCommerce, booking service và thanh toán QR**

## 2. Giới thiệu website/hệ thống
HV-Travel là hệ thống website bán tour du lịch trực tuyến được xây dựng trên nền tảng `WordPress` và `WooCommerce`. Hệ thống hỗ trợ khách hàng xem danh sách tour, xem chi tiết tour, chọn ngày khởi hành, nhập thông tin booking, thêm vào giỏ hàng, thanh toán và theo dõi trạng thái đơn hàng.

Về kiến trúc, dự án tách thành 3 lớp chính:
- `WordPress + WooCommerce + MySQL`: quản trị nội dung, tour, giỏ hàng, checkout, đơn hàng và trang quản trị.
- `OP Travel Core + OP Travel Shop`: xử lý nghiệp vụ tour, booking flow, giao diện storefront và QR demo theo order.
- `booking-payment-service + MongoDB`: đồng bộ booking/payment, nhận webhook thanh toán, lưu event log và dữ liệu báo cáo nghiệp vụ.

## 3. Danh sách thành viên
> Repo không chứa thông tin họ tên/MSSV thực tế. Nhóm cần thay các dòng mẫu bên dưới bằng thông tin chính thức trước khi nộp.

| STT | Họ và tên | MSSV | Vai trò chính |
| --- | --- | --- | --- |
| 1 | `Đặng Xuân Huy` | `23810310007` | WordPress/WooCommerce, theme, plugin nghiệp vụ, booking flow, kiểm thử demo |
| 2 | `Nguyễn Lê Quang Vũ` | `23810310404` | Kiến trúc tổng thể, MongoDB, backend service, Docker, Render |

## 4. Các thành viên trong nhóm
- Đặng Xuân Huy: `23810310007`
- Nguyễn Lê Quang Vũ: `23810310404`

## 5. Phân công nhiệm vụ cụ thể
### Nguyễn Lê Quang Vũ
- Phân tích đề tài, mô tả bài toán nghiệp vụ và kiến trúc tổng thể.
- Trình bày vai trò của `WordPress`, `WooCommerce`, `MySQL`, `MongoDB`, `Docker`, `Render`.
- Phụ trách `booking-payment-service`, MongoDB, luồng đồng bộ booking/payment, webhook và báo cáo doanh thu.
- Cấu hình `Docker Compose`, môi trường chạy local và định hướng deploy lên `Render`.

### Đặng Xuân Huy
- Cài đặt `WordPress`, `WooCommerce`, theme `OP Travel Shop` và các plugin cần thiết.
- Phát triển plugin `OP Travel Core`, `OP Travel SePay` và các thành phần booking nghiệp vụ.
- Tùy biến giao diện storefront, archive tour, single tour, cart, checkout và thank-you page.
- Xử lý booking fields, metadata tour, QR thanh toán, payment confirm, seed dữ liệu demo và kiểm thử hệ thống.

## 6. Công nghệ sử dụng
| Nhóm công nghệ | Công nghệ/Thành phần |
| --- | --- |
| CMS / Storefront | `WordPress` |
| E-commerce engine | `WooCommerce` |
| Theme custom | `wordpress/wp-content/themes/op-travel-shop` |
| Plugin nghiệp vụ | `wordpress/wp-content/plugins/op-travel-core` |
| Plugin thanh toán QR | `wordpress/wp-content/plugins/op-travel-sepay` |
| Plugin CMS nội dung | `wordpress/wp-content/plugins/op-travel-storefront-cms` |
| Backend service | `Node.js` (`services/booking-payment-service`) |
| Cơ sở dữ liệu core | `MySQL` |
| Cơ sở dữ liệu nghiệp vụ | `MongoDB` |
| Thanh toán / QR | `SePay`, `payOS`, fallback QR demo |
| Đóng gói môi trường | `Docker`, `Docker Compose` |
| Deploy | `Render` |
| Public webhook / hostname | `Cloudflared` (nếu cần) |

## 7. Hướng dẫn cài đặt
### Yêu cầu trước khi cài đặt
- `Git`
- `Docker Desktop` có `Docker Compose v2`
- `Node.js 20+` nếu muốn chạy smoke script từ máy host

### Các bước cài đặt
1. Clone source code:

```powershell
git clone `https://github.com/HuyDangXuan/WordPress-BCHP`
cd WordPress
```

2. Tạo các file môi trường từ file mẫu:

```powershell
Copy-Item env\wordpress.env.example env\wordpress.env
Copy-Item env\service.env.example env\service.env
Copy-Item env\tunnel.env.example env\tunnel.env
```

3. Kiểm tra và cập nhật các biến môi trường quan trọng:
- `env/wordpress.env`: `PUBLIC_SITE_URL`, `PAYMENT_SYNC_SECRET`, cấu hình SMTP nếu cần gửi mail.
- `env/service.env`: `MONGO_URI`, `PAYOS_*`, `SEPAY_*`, `WORDPRESS_CONFIRM_ENDPOINT`.
- `env/tunnel.env`: `TUNNEL_TOKEN` nếu cần public hostname hoặc webhook.

4. Chuẩn bị plugin/theme trong `wordpress/wp-content/` theo đúng cấu trúc repo hiện tại.

## 8. Hướng dẫn chạy project
### Khởi động stack local
```powershell
docker compose -f docker/compose.local.yml up -d --build
```

### Các URL local quan trọng
- Website: `http://localhost:8080`
- WordPress admin: `http://localhost:8080/wp-admin`
- phpMyAdmin: `http://localhost:8081`
- Service health: `http://localhost:8787/health`
- Revenue API: `http://localhost:8787/api/reports/revenue`

### Cấu hình sau khi mở project lần đầu
1. Mở `http://localhost:8080` và hoàn tất cài đặt WordPress ban đầu.
2. Đăng nhập `wp-admin`.
3. Cài và kích hoạt `WooCommerce`.
4. Kích hoạt theme `OP Travel Shop`.
5. Kích hoạt các plugin:
   - `OP Travel Core`
   - `OP Travel SePay`
   - `OP Travel Storefront CMS` (nếu muốn chỉnh nội dung storefront trong admin)
6. Vào `Tools > OP Travel Seeder` để seed dữ liệu demo.
7. Kiểm tra route `/tours/`, trang chi tiết tour, cart, checkout và thank-you page.

### Kiểm tra nhanh sau khi chạy
```powershell
docker compose -f docker/compose.local.yml config
docker compose -f docker/compose.local.yml ps
Invoke-WebRequest -UseBasicParsing http://localhost:8787/health
node scripts/acceptance-smoke.mjs
```

## 9. Tài khoản demo (nếu có)

Gợi ý chuẩn bị tài khoản cho buổi demo:
- `Admin WordPress`: Tài khoản: `hhunu114@gmail.com` Mật khẩu: `X#a!CZcmukRmhze!3u!@xD39`
- `Tài khoản khách hàng`: có thể đăng ký tại trang `My Account`/đăng nhập sau khi cấu hình OTP/email.

## 10. Hình ảnh minh họa hệ thống
### Sơ đồ kiến trúc tổng thể
```mermaid
flowchart LR
    U["Khách hàng"] --> W["WordPress + WooCommerce"]
    A["Quản trị viên"] --> W
    W --> T["OP Travel Shop"]
    W --> P["OP Travel Core / OP Travel SePay"]
    W --> MY["MySQL"]
    P --> S["booking-payment-service"]
    S --> MG["MongoDB"]
    S --> PAY["SePay / payOS"]
```

### Luồng đặt tour và thanh toán
```mermaid
sequenceDiagram
    participant K as Khách hàng
    participant WP as WordPress/WooCommerce
    participant PL as Plugin nghiệp vụ
    participant SV as Booking Service
    participant DB as MongoDB

    K->>WP: Chọn tour và nhập thông tin booking
    WP->>PL: Lưu metadata booking vào cart/order
    PL->>SV: POST /api/bookings
    SV->>DB: Lưu booking pending
    K->>WP: Chọn thanh toán và tạo đơn
    WP-->>K: Hiển thị QR / trang thank-you
    SV->>WP: Payment confirm / webhook callback
    WP-->>K: Cập nhật trạng thái đơn hàng
```

## 11. Link video demo
- `https://drive.google.com/drive/folders/1CaMaU7uGnts0bBciaZbrOzIylhWnkgtj?usp=sharing`

## 12. Link online đã deploy (nếu có)
- Public site theo cấu hình hiện tại trong `env/wordpress.env`: [https://wp-hv-travel.fshdx2105.id.vn](https://wp-hv-travel.fshdx2105.id.vn)
- Nếu cần deploy đầy đủ lên `Render`, tham khảo thêm:
  - `render.yaml`
  - `render.free-demo.yaml`
  - `docs/deploy/render-runbook.md`

## 13. Tài liệu tham khảo trong repo
- `Phases/01-phase-1-tong-quan-de-tai-va-kien-truc.md`
- `Phases/11-kich-ban-thuyet-trinh-va-phan-cong-trinh-bay.md`
- `docs/demo/local-e2e-acceptance.md`
- `services/booking-payment-service/README.md`
