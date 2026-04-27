---
name: op-travel-core-plugin-extension
description: Use when extending HV-Travel business logic in the op-travel-core plugin, including taxonomy, custom post types, tour metadata, booking fields, order persistence, REST integration, or QR-related hooks.
---

# OP Travel Core Plugin Extension

## Phase Sources
- `Phases/05-phase-5-plugin-nghiep-vu-op-travel-core.md`

## Overview
Skill này chuẩn hóa cách mở rộng `op-travel-core`, là nơi hiện thực hóa nghiệp vụ tour của HV-Travel. Plugin sở hữu taxonomy, custom post type, metadata tour, booking fields, persistence qua cart/order và là bàn đạp cho REST endpoint, payOS và MongoDB sync.

## When to Use
- Cần thêm hoặc sửa taxonomy, CPT, shortcode hay CMS setup.
- Cần thêm metadata tour hoặc thay đổi admin product tab.
- Cần chỉnh booking flow từ single product tới cart/order.
- Cần thêm REST bridge hoặc lớp gọi service business.
- Cần mở rộng QR demo, payment confirm hoặc report-related hooks.

## Project Rules
- Business logic sống trong plugin, không sống trong theme.
- Bootstrap phải rõ ràng; tránh rải logic tùy tiện ra nhiều file không thuộc module.
- Metadata tour phải phục vụ cả admin lẫn frontend.
- Booking data phải sống xuyên suốt:
  - render trên single
  - validate trước add to cart
  - persist vào cart item
  - persist tiếp vào order item
- Không dựa vào session tạm nếu dữ liệu cuối cùng cần xuất hiện trong order.
- REST endpoint mới phải bám naming và contract của `hv-travel-technical-contracts`.

## Source of Truth
- Tài liệu phase: `Phases/05-phase-5-plugin-nghiep-vu-op-travel-core.md`
- Path tham chiếu từ phase:
  - `wp-content/plugins/op-travel-core/op-travel-core.php`
  - `wp-content/plugins/op-travel-core/includes/Bootstrap.php`
  - `wp-content/plugins/op-travel-core/includes/CmsSetup.php`
  - `wp-content/plugins/op-travel-core/includes/ProductMeta.php`
  - `wp-content/plugins/op-travel-core/includes/BookingHooks.php`
  - `wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`

## Workflow/Checklist
1. Xác định nhóm thay đổi:
   - bootstrap/cấu hình CMS
   - metadata tour
   - booking flow
   - QR/payment hooks
   - service integration
2. Đặt logic vào đúng module hiện có trước khi nghĩ tới việc tạo module mới.
3. Nếu thêm field mới cho tour hoặc booking, kiểm tra luôn 3 điểm:
   - admin input
   - frontend render
   - persistence tới order
4. Nếu thêm integration outbound:
   - giữ contract theo `hv-travel-technical-contracts`
   - giữ secret boundary rõ
   - chuẩn bị điểm nối sang `mongodb-sync-service` hoặc `payos-bck-payment-flow`
5. Nếu thêm endpoint inbound như payment confirm, coi đây là business logic của plugin, không phải template concern.

## Common Mistakes
- Đẩy logic sang `functions.php` của theme vì “dễ sửa hơn”.
- Chỉ lưu booking ở cart/session rồi làm mất dữ liệu khi tạo order.
- Tạo metadata mới nhưng không cho frontend dùng lại.
- Trộn QR demo markup với xác thực payment thật mà không có boundary.
- Thêm REST route nhưng tự đổi contract so với file reference.

## Cross-references
- `hv-travel-project-context` cho boundary theme/plugin/service.
- `hv-travel-technical-contracts` cho route, env, payload và status.
- `payos-bck-payment-flow` cho luồng payment và webhook confirm.
- `mongodb-sync-service` cho outbound sync và read-model/report.
