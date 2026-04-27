---
name: hv-travel-project-context
description: Use when working on HV-Travel and needing the system scope, architecture boundaries, or the roles of WordPress, WooCommerce, MySQL, MongoDB, Docker, and Render before proposing changes.
---

# HV-Travel Project Context

## Phase Sources
- `Phases/README.md`
- `Phases/01-phase-1-tong-quan-de-tai-va-kien-truc.md`

## Overview
HV-Travel là website bán tour du lịch xây trên `WordPress + WooCommerce`, với `op-travel-shop` làm theme storefront riêng và `op-travel-core` làm plugin business riêng. Kiến trúc chuẩn của dự án là `WordPress/WooCommerce + MySQL` cho web chính, cộng thêm service riêng dùng `MongoDB` cho dữ liệu business, và định hướng đóng gói bằng `Docker` để triển khai trên `Render`.

## When to Use
- Cần hiểu hệ thống đang giải quyết bài toán gì trước khi code hoặc viết tài liệu.
- Cần xác định thay đổi thuộc theme, plugin, service hay hạ tầng.
- Cần giải thích vì sao dùng WordPress/WooCommerce thay vì viết mới từ đầu.
- Cần chốt boundary giữa `MySQL` của WordPress và `MongoDB` cho nghiệp vụ mở rộng.
- Không dùng skill này để tra endpoint, env hay payload cụ thể. Khi cần chi tiết hợp đồng kỹ thuật, chuyển sang `hv-travel-technical-contracts`.

## Project Rules
- `WordPress + WooCommerce + MySQL` luôn là lõi storefront, cart, checkout, order và admin content.
- `MongoDB` không thay WordPress core database; chỉ xuất hiện sau một service business riêng.
- `op-travel-shop` sở hữu trải nghiệm người dùng, workflow 4 bước và WooCommerce template overrides.
- `op-travel-core` sở hữu taxonomy, CPT, metadata tour, booking flow, QR demo và integration business.
- `payOS` là hướng thanh toán online chính; `BCK` và QR demo là fallback/minh họa.
- `Docker` là lớp chuẩn hóa local/online; `Render` là đích deploy online.
- Khi tài liệu phase và mã nguồn mâu thuẫn, ưu tiên kiểm tra lại phase nguồn rồi mới đề xuất thay đổi.

## Source of Truth
- Tài liệu kiến trúc: `Phases/README.md`, `Phases/01-phase-1-tong-quan-de-tai-va-kien-truc.md`
- Path ứng dụng được phase tham chiếu:
  - `wp-config.php`
  - `wp-content/themes/op-travel-shop/`
  - `wp-content/plugins/op-travel-core/`
  - `wp-content/plugins/bck-tu-dong-xac-nhan-thanh-toan-chuyen-khoan-ngan-hang/`

## Workflow/Checklist
1. Xác định yêu cầu đang chạm lớp nào: storefront, plugin business, payment service, MongoDB hay deploy.
2. Kiểm tra thay đổi có nằm trong phạm vi BCCĐ không: tour, booking, payment, Mongo sync, Docker/Render.
3. Map trách nhiệm:
   - UI, layout, WooCommerce template override -> theme
   - taxonomy, metadata, booking persistence, REST bridge -> plugin
   - webhook, audit event, report read-model -> service + MongoDB
   - hosting, storage, backup, HTTPS -> deploy
4. Nếu cần giải thích kiến trúc, kể theo luồng khách: chọn tour -> giữ chỗ -> thanh toán -> xác nhận -> đồng bộ business data.
5. Nếu thấy đề xuất đi ngược boundary, dừng và chỉnh lại trước khi triển khai.

## Common Mistakes
- Đề xuất thay `MySQL` của WordPress bằng `MongoDB`.
- Đưa business logic booking vào theme chỉ vì đang sửa giao diện.
- Xem WooCommerce như shop sản phẩm thông thường thay vì workflow đặt tour.
- Gộp WordPress, DB và service business thành một khối không có boundary rõ.
- Mô tả BCK như cổng thanh toán chính thay vì fallback.

## Cross-references
- Dùng `hv-travel-technical-contracts` cho endpoint, env, status và payload chuẩn.
- Dùng `op-travel-shop-theme-customization` khi sửa storefront và WooCommerce templates.
- Dùng `op-travel-core-plugin-extension` khi sửa logic tour, metadata và booking flow.
- Dùng `mongodb-sync-service` cho boundary service business.
- Dùng `docker-render-deploy` cho topology Docker/Render.
- Dùng `demo-and-defense-prep` khi cần chuyển kiến trúc thành demo hoặc lời trình bày.
