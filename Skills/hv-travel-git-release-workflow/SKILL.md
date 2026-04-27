---
name: hv-travel-git-release-workflow
description: Use when planning or documenting HV-Travel branch strategy, commit naming, release tags, demo releases, or rollback flow for theme, plugin, docs, and deploy changes.
---

# HV-Travel Git Release Workflow

## Phase Sources
- `Phases/03-phase-3-quan-ly-phien-ban-va-quy-trinh-git.md`

## Overview
Skill này chuẩn hóa workflow Git được phase 3 đề xuất cho HV-Travel: `main`, `develop`, `feature/*`, `release/*`, `hotfix/*`, cộng với commit/tag naming cho các mốc demo và deploy. Nó dùng để giữ câu chuyện version control đủ chặt cho BCCĐ, kể cả khi workspace hiện tại chưa có `.git`.

## When to Use
- Cần mô tả branch model hoặc release flow của dự án.
- Cần đặt tên branch, commit hoặc tag nhất quán với HV-Travel.
- Cần lập kế hoạch release demo, rollback hoặc hotfix production.
- Cần tách thay đổi theme/plugin/payment/deploy/docs thành các nhịp version rõ ràng.

## Project Rules
- `main` là nhánh ổn định cho demo/deploy.
- `develop` là nhánh tích hợp chung trước release.
- `feature/*` cho tính năng mới; `release/*` cho gom bản demo; `hotfix/*` cho lỗi gấp.
- Commit message phải rõ ngữ nghĩa như `feat`, `fix`, `docs`, `refactor`.
- Tag nên phản ánh milestone kỹ thuật hoặc demo, ví dụ `v0.3-qr-demo`, `v1.0-bccd-demo`.
- Nếu repo hiện tại chưa là git repository, dùng skill này như target workflow cần áp dụng khi mã nguồn được version hóa.

## Source of Truth
- Tài liệu phase: `Phases/03-phase-3-quan-ly-phien-ban-va-quy-trinh-git.md`

## Workflow/Checklist
1. Xác định loại thay đổi:
   - theme
   - plugin
   - payment
   - deploy
   - docs
2. Tạo branch từ `develop` theo đúng prefix:
   - `feature/theme-checkout-workflow`
   - `feature/plugin-booking-metadata`
   - `feature/payment-payos-integration`
3. Commit theo đơn vị chức năng, không gộp nhiều subsystem không liên quan.
4. Chạy verify local trước khi merge về `develop`.
5. Khi chuẩn bị demo/bảo vệ:
   - tách `release/*`
   - chỉ nhận fix nhẹ
   - gắn tag release
6. Chỉ merge sang `main` khi nhánh release đã ổn định.
7. Nếu production/demo lỗi, vá trên `hotfix/*` thay vì sửa tay trên `main`.

## Common Mistakes
- Commit kiểu `first commit`, `update`, `fix stuff`.
- Làm việc trực tiếp trên `main`.
- Trộn tài liệu, payment và deploy vào một commit khó đọc.
- Tạo release từ nhánh feature chưa qua `develop`.
- Không gắn tag nên khó khóa bản demo dùng trong bảo vệ.

## Cross-references
- `docker-render-deploy` cho liên kết giữa merge `main` và auto-deploy.
- `demo-and-defense-prep` cho các mốc release demo và fallback plan.
- `op-travel-shop-theme-customization` và `op-travel-core-plugin-extension` cho cách tách work theo subsystem.
