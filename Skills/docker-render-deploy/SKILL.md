---
name: docker-render-deploy
description: Use when packaging or deploying HV-Travel with Docker and Render, especially when defining the four-service topology, environment variables, persistent storage, backup, restore, logging, or post-deploy smoke tests.
---

# Docker and Render Deploy

## Phase Sources
- `Phases/08-phase-8-docker-va-deploy-render.md`
- `Phases/10-phu-luc-api-env-va-checklist.md`

## Overview
Skill này chuẩn hóa topology deploy của HV-Travel: `WordPress web service`, `MySQL private service`, `MongoDB private service` và `booking/payment API service`. Nó giữ agent bám đúng tư duy production nhỏ gọn nhưng có boundary, private networking, persistent storage và quy trình backup/restore/smoke test.

## When to Use
- Cần tạo Docker local target hoặc Dockerfile/compose cho HV-Travel.
- Cần mô tả hoặc triển khai kiến trúc 4 service trên Render.
- Cần map env, disk, domain, HTTPS, backup, restore hay log monitoring.
- Cần viết smoke test checklist sau deploy hoặc redeploy.

## Project Rules
- Không mô tả production như một container all-in-one.
- Chỉ `WordPress web service` là public.
- `MySQL`, `MongoDB` và service business dùng private networking.
- Persistent disk là bắt buộc cho DB; uploads WordPress cũng phải có chiến lược giữ dữ liệu.
- Env tách theo service; không trộn secret payment vào sai container.
- Sau deploy phải có smoke test cho homepage, archive, single, order, payment và thank-you flow.

## Source of Truth
- Tài liệu phase: `Phases/08-phase-8-docker-va-deploy-render.md`, `Phases/10-phu-luc-api-env-va-checklist.md`
- Contract reference: `Skills/hv-travel-technical-contracts/contracts-reference.md`

## Workflow/Checklist
1. Giữ đúng 4 service:
   - WordPress
   - MySQL
   - MongoDB
   - booking/payment API
2. Xác định env theo service và nạp đúng biến từ reference file.
3. Cấu hình persistent storage cho:
   - MySQL
   - MongoDB
   - uploads WordPress nếu lưu local
4. Map domain, `Site URL`, `Home URL` và HTTPS cho production.
5. Bật auto-deploy từ nhánh phát hành chuẩn, thường là `main`.
6. Chuẩn bị backup/restore:
   - `mysqldump`
   - `mongodump`
   - media/plugin/theme nếu cần
7. Sau deploy, chạy smoke test:
   - homepage
   - tour archive
   - single tour
   - test order
   - payment/QR
   - thank-you page

## Common Mistakes
- Gom WordPress, MySQL, MongoDB và API vào một container vì “nhanh”.
- Public database service ra internet.
- Redeploy mà quên chiến lược giữ `uploads`.
- Cấu hình env thiếu `PAYMENT_SYNC_SECRET` hoặc `WORDPRESS_CONFIRM_ENDPOINT`.
- Kết thúc deploy mà không có smoke test và log review.

## Cross-references
- `hv-travel-project-context` cho boundary hệ thống.
- `hv-travel-technical-contracts` cho env names chuẩn.
- `mongodb-sync-service` cho MongoDB role và backup expectations.
- `payos-bck-payment-flow` cho webhook endpoint và production callback flow.
- `demo-and-defense-prep` khi deploy online phục vụ buổi bảo vệ.
