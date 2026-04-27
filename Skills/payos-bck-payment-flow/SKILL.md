---
name: payos-bck-payment-flow
description: Use when designing or implementing HV-Travel payment behavior with payOS as the main gateway, BCK or QR demo as fallback, and webhook-driven status updates back into WordPress.
---

# payOS and BCK Payment Flow

## Phase Sources
- `Phases/06-phase-6-thanh-toan-truc-tuyen-qr-va-thong-bao-thanh-cong.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`

## Overview
Skill này chuẩn hóa toàn bộ luồng thanh toán của HV-Travel: payOS là đường chính, BCK/QR demo là phương án fallback, còn webhook mới là nguồn xác nhận thanh toán đáng tin cậy. Nó giúp agent không dừng ở mức “hiển thị QR”, mà đi hết đường tạo giao dịch, nhận callback, cập nhật order và hiển thị trạng thái cuối trên thank-you page.

## When to Use
- Cần tích hợp payOS hoặc mô tả payment lifecycle của HV-Travel.
- Cần xử lý `returnUrl`, `cancelUrl`, webhook hoặc idempotency.
- Cần cập nhật order state từ service về WordPress.
- Cần giữ fallback BCK/QR demo cho môi trường sandbox hoặc buổi bảo vệ.

## Project Rules
- payOS là hướng thanh toán online chính; `BCK` và QR demo là fallback/minh họa.
- Không coi browser return là xác nhận thanh toán cuối cùng; webhook mới là nguồn xác nhận chuẩn.
- Status business phải giữ đúng vocabulary trong `hv-travel-technical-contracts`.
- Service callback về WordPress phải dùng `PAYMENT_SYNC_SECRET`.
- Xác thực chữ ký/checksum và idempotency là bắt buộc.
- Thank-you page phải biểu diễn rõ `pending`, `paid`, `failed`, `expired` và khi cần cả `cancelled`.

## Source of Truth
- Tài liệu phase: `Phases/06-phase-6-thanh-toan-truc-tuyen-qr-va-thong-bao-thanh-cong.md`, `Phases/10-phu-luc-api-env-va-checklist.md`
- Contract reference: `Skills/hv-travel-technical-contracts/contracts-reference.md`

## Workflow/Checklist
1. Tạo order WooCommerce và chuẩn bị payment request từ plugin/service.
2. Tạo payment link hoặc QR bằng payOS.
3. Cấu hình:
   - `returnUrl` về order received/thank-you
   - `cancelUrl` về checkout hoặc cart
4. Khi webhook tới:
   - xác thực chữ ký
   - kiểm tra order tồn tại
   - kiểm tra amount và transaction id
   - chặn xử lý trùng
5. Ghi payment event và cập nhật payment state trong service.
6. Gọi `POST /wp-json/op-travel/v1/payment-confirm` về WordPress bằng `PAYMENT_SYNC_SECRET`.
7. Render thank-you/view-order theo state cuối cùng.
8. Nếu sandbox lỗi, chuyển sang BCK hoặc QR demo mà vẫn giữ nguyên câu chuyện payment states.

## Common Mistakes
- Đổi order sang `paid` chỉ vì khách quay lại `returnUrl`.
- Không lưu `payment_events`, khiến callback trùng phá trạng thái.
- Bỏ qua checksum/signature vì đang ở sandbox.
- Dùng BCK như nhánh mặc định trong tài liệu dù phase đã chốt payOS là hướng chính.
- Hiển thị QR xong nhưng không có câu chuyện cập nhật order state.

## Cross-references
- `hv-travel-technical-contracts` cho route, env, status và payload chuẩn.
- `op-travel-core-plugin-extension` cho payment hooks phía WordPress.
- `mongodb-sync-service` cho audit trail, payment records và event log.
- `demo-and-defense-prep` cho fallback plan khi cổng online lỗi lúc demo.
