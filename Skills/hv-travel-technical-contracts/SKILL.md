---
name: hv-travel-technical-contracts
description: Use when working on HV-Travel and needing exact endpoint names, environment variables, status values, payload fields, or MongoDB collection contracts without redefining them.
---

# HV-Travel Technical Contracts

## Phase Sources
- `Phases/README.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`

## Overview
Skill này khóa toàn bộ hợp đồng kỹ thuật dùng chung của HV-Travel: endpoint nội bộ, endpoint service, status nghiệp vụ, biến môi trường và payload mẫu. Mục tiêu là ngăn việc agent tự đặt lại tên route, env hoặc trạng thái khi đi qua theme, plugin, payment flow, MongoDB hoặc deploy.

## When to Use
- Cần biết chính xác route, method, secret hoặc env name.
- Cần viết tài liệu, code hoặc plan liên quan đến booking/payment/webhook/report.
- Cần map order/business status mà không được tự sáng tác vocabulary mới.
- Cần payload mẫu cho booking, webhook hoặc collection MongoDB.
- Không dùng skill này để quyết định kiến trúc tổng thể; khi cần boundary hệ thống, nạp `hv-travel-project-context`.

## Project Rules
- Chỉ có một nguồn sự thật cho contracts: `contracts-reference.md` trong skill này.
- Không copy-paste lại raw contracts sang các skill khác nếu không thật cần thiết; thay vào đó phải trỏ về file reference.
- Status business chuẩn là: `pending`, `paid`, `failed`, `expired`, `cancelled`.
- Xác nhận nội bộ về WordPress dùng `POST /wp-json/op-travel/v1/payment-confirm`.
- Callback từ service về WordPress phải đi kèm `PAYMENT_SYNC_SECRET`.
- Nếu muốn đổi tên endpoint, env hay payload field, xem đó là thay đổi kiến trúc và phải rà lại toàn bộ phase liên quan.

## Source of Truth
- Skill reference: `Skills/hv-travel-technical-contracts/contracts-reference.md`
- Tài liệu phase: `Phases/README.md`, `Phases/10-phu-luc-api-env-va-checklist.md`

## Workflow/Checklist
1. Xác định mình đang cần loại contract nào:
   - endpoint
   - env
   - status
   - payload
   - collection
2. Mở `contracts-reference.md` và lấy đúng tên chuẩn từ đó.
3. Kiểm tra contract này đang được dùng ở lớp nào:
   - WordPress/plugin
   - payment service
   - MongoDB/report
   - deploy
4. Reuse y nguyên tên field, route, status và casing.
5. Nếu contract đang được nhắc trong skill khác, chỉ trỏ chéo thay vì định nghĩa lại.

## Common Mistakes
- Tự đổi `payment-confirm` sang route khác vì “nghe hợp lý hơn”.
- Dùng status WooCommerce thay cho vocabulary business đã chốt.
- Đặt thêm env mới mà không chứng minh vì sao env cũ không đủ.
- Tạo payload mới nhưng bỏ mất `wordpress_order_id`, `payment_code` hoặc `provider_transaction_id`.
- Ghi contract ở nhiều nơi rồi để chúng lệch nhau.

## Cross-references
- `hv-travel-project-context` cho boundary tổng thể.
- `payos-bck-payment-flow` cho webhook và payment lifecycle.
- `mongodb-sync-service` cho collections, audit trail và report.
- `docker-render-deploy` cho mapping env theo service.
