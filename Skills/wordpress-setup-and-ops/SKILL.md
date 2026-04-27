---
name: wordpress-setup-and-ops
description: Use when setting up or troubleshooting HV-Travel WordPress, WooCommerce, theme/plugin activation, permalink and page mapping, SMTP, BCK fallback, or the local Docker target stack.
---

# WordPress Setup and Ops

## Phase Sources
- `Phases/02-phase-2-cau-hinh-moi-truong-va-he-thong.md`

## Overview
Skill này chuẩn hóa phần “dựng hệ thống” của HV-Travel: từ `wp-config.php`, WordPress, WooCommerce, theme `op-travel-shop`, plugin `op-travel-core`, BCK, SMTP cho tới target Docker local 4 service. Mục tiêu là trả lời đúng thứ tự setup, đúng các điểm kiểm tra và đúng các lỗi thường gặp khi demo hoặc deploy.

## When to Use
- Cần setup local hoặc staging cho HV-Travel.
- Cần rà lại activation của WordPress, WooCommerce, theme và plugin.
- Cần kiểm tra permalink, page mapping, SMTP hay BCK.
- Cần phân tích lỗi `/tours/`, booking fields, QR, email hoặc mapping sai page.
- Cần mô tả Docker local target stack trước khi có assets hoàn chỉnh.

## Project Rules
- `WordPress` và `WooCommerce` phải hoạt động trước khi kết luận theme/plugin có lỗi.
- Theme mặc định của dự án là `op-travel-shop`; plugin nghiệp vụ mặc định là `op-travel-core`.
- Slug sản phẩm du lịch phải đi theo rewrite `/tours/`.
- Mapping các page shop/cart/checkout/account phải bám theo logic `CmsSetup`.
- `WP Mail SMTP`, `BCK`, `UpdraftPlus`, `Wordfence` là các plugin vận hành được phase tài liệu thống nhất.
- Target Docker local chuẩn gồm 4 service: `wordpress`, `mysql`, `mongodb`, `booking-payment-service`.

## Source of Truth
- Tài liệu phase: `Phases/02-phase-2-cau-hinh-moi-truong-va-he-thong.md`
- Path tham chiếu từ phase:
  - `wp-config.php`
  - `wp-content/plugins/op-travel-core/includes/CmsSetup.php`
  - `wp-content/themes/op-travel-shop/functions.php`
  - `wp-content/themes/op-travel-shop/inc/setup.php`
  - `wp-content/themes/op-travel-shop/inc/woocommerce.php`

## Workflow/Checklist
1. Kiểm tra nền tảng local:
   - PHP `8.1+`
   - MySQL hoặc MariaDB tương thích
   - web server
   - Docker Desktop nếu dùng local stack
2. Cấu hình `wp-config.php`:
   - DB host, DB name, DB user, DB password
   - `WP_DEBUG`
   - `WP_DEBUG_LOG`
3. Kích hoạt phần lõi:
   - `WooCommerce`
   - theme `op-travel-shop`
   - `OP Travel Core`
   - `BCK`
   - `WP Mail SMTP`
4. Xác minh permalink và page mapping:
   - `Post name`
   - `/tours/`
   - `/gio-hang/`
   - `/thanh-toan/`
   - `/tai-khoan/`
5. Kiểm tra mapping từ `CmsSetup` cho:
   - `trang-chu`
   - `tours`
   - `gio-hang`
   - `thanh-toan`
   - `tai-khoan`
6. Cấu hình vận hành:
   - SMTP
   - BCK fallback
   - payOS env target theo `hv-travel-technical-contracts`
7. Nếu đang chuẩn bị Docker local, giữ đúng target 4 service và đừng thu nhỏ xuống mô hình all-in-one.

## Common Mistakes
- Quên save permalink nên `/tours/` không hoạt động.
- Kết luận booking fields hỏng trong khi `WooCommerce` chưa active.
- Kiểm tra QR sai gateway hoặc sai order context.
- Để page mapping WooCommerce lệch với các slug được seed.
- Cấu hình SMTP nhưng không phân biệt env local với production.
- Viết tài liệu local Docker chỉ có `wordpress + mysql`, bỏ mất `mongodb` và service business.

## Cross-references
- `hv-travel-project-context` để giữ đúng boundary hệ thống.
- `hv-travel-technical-contracts` cho env names chuẩn.
- `op-travel-core-plugin-extension` cho logic `CmsSetup` và booking fields.
- `payos-bck-payment-flow` cho payment fallback.
- `docker-render-deploy` cho topology triển khai thật.
