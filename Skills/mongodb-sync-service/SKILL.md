---
name: mongodb-sync-service
description: Use when adding or explaining the HV-Travel business service that syncs bookings, payments, webhook events, contacts, or reports into MongoDB without replacing the WordPress MySQL core.
---

# MongoDB Sync Service

## Phase Sources
- `Phases/07-phase-7-tich-hop-mongodb-cho-nghiep-vu.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`

## Overview
Skill này khóa boundary MongoDB của HV-Travel: `WordPress -> service -> MongoDB`. MongoDB tồn tại để chứa booking snapshot, payment records, payment events, contacts và reports; nó không phải database thay thế cho WordPress core.

## When to Use
- Cần thiết kế service business hoặc API sync từ WordPress ra MongoDB.
- Cần chọn collection đúng cho booking, payment, event, contact hoặc report.
- Cần giải thích vì sao MongoDB không đi thẳng vào WordPress core.
- Cần mô tả reporting layer hoặc audit/event log.

## Project Rules
- WordPress/WooCommerce tiếp tục chạy trên `MySQL`.
- `MongoDB` chỉ được chạm qua service business riêng.
- `bookings` là snapshot nghiệp vụ của đơn/booking, không thay order WooCommerce.
- `payments` tách lớp giao dịch khỏi order status WooCommerce.
- `payment_events` là bắt buộc cho audit, idempotency và debug webhook.
- `contacts` và `reports` là lớp business/read-model, không làm phình core tables.

## Source of Truth
- Tài liệu phase: `Phases/07-phase-7-tich-hop-mongodb-cho-nghiep-vu.md`, `Phases/10-phu-luc-api-env-va-checklist.md`
- Contract reference: `Skills/hv-travel-technical-contracts/contracts-reference.md`

## Workflow/Checklist
1. Xác định trigger:
   - tạo booking
   - nhận webhook payment
   - gửi contact form
   - tạo report
2. Chọn collection đúng:
   - `bookings`
   - `payments`
   - `payment_events`
   - `contacts`
   - `reports`
3. Ghi snapshot đủ dữ liệu business, không chỉ ghi ID tối thiểu.
4. Khi payment state đổi:
   - cập nhật collection business
   - ghi event
   - đồng bộ ngược về WordPress nếu cần
5. Nếu làm reporting endpoint, dùng read-model/report snapshot thay vì query trực tiếp vào WordPress tables.
6. Nếu bị hỏi vì sao không dùng Mongo cho core, trả lời theo boundary kỹ thuật chứ không trả lời kiểu “để đủ công nghệ”.

## Common Mistakes
- Đề xuất gắn MongoDB trực tiếp vào WordPress core tables.
- Ghi payment state nhưng không giữ event history.
- Dùng order WooCommerce như bản ghi payment duy nhất.
- Gửi booking sang MongoDB nhưng bỏ mất thông tin khách, ngày đi hoặc snapshot tour.
- Dùng MongoDB chỉ để “cho có”, không nêu rõ use case log/report/audit.

## Cross-references
- `hv-travel-project-context` cho kiến trúc tổng thể.
- `hv-travel-technical-contracts` cho endpoint, payload và schema field names.
- `payos-bck-payment-flow` cho webhook-driven sync.
- `docker-render-deploy` cho MongoDB service, persistent disk và backup.
