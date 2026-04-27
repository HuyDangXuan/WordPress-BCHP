---
name: demo-and-defense-prep
description: Use when preparing HV-Travel test runs, demo scripts, fallback plans, presentation flow, or rebuttal answers for a 5-minute or 10-minute BCCĐ defense session.
---

# Demo and Defense Prep

## Phase Sources
- `Phases/09-phase-9-kiem-thu-nghiem-thu-va-kich-ban-demo.md`
- `Phases/11-kich-ban-thuyet-trinh-va-phan-cong-trinh-bay.md`

## Overview
Skill này gom phần QA, demo và bảo vệ của HV-Travel thành một workflow thống nhất. Nó buộc agent chuẩn bị test matrix, sample orders, live demo flow 5/10 phút, phương án fallback và câu trả lời phản biện, thay vì chỉ tập trung vào phần code.

## When to Use
- Cần chuẩn bị buổi demo hoặc buổi bảo vệ BCCĐ.
- Cần test nhanh luồng khách hàng đầu-cuối trước khi trình bày.
- Cần dựng script nói, phân vai hoặc câu trả lời phản biện.
- Cần fallback plan cho payment sandbox, mạng, QR, email hoặc cold start.

## Project Rules
- Demo mặc định phải đi theo journey khách hàng trước, không nhảy vào admin trừ khi đang giải thích plugin/theme internals.
- Luôn chuẩn bị ít nhất:
  - 1 order `pending`
  - 1 order `paid`
  - 2-3 tour mẫu
  - screenshot/video fallback
- Có hai chế độ trình bày chuẩn:
  - 5 phút
  - 10 phút
- Nếu payment sandbox lỗi, vẫn phải giữ được câu chuyện trạng thái đơn và thank-you page.
- Trả lời phản biện phải bám đúng kiến trúc phase, không ứng biến sai boundary.

## Source of Truth
- Tài liệu phase: `Phases/09-phase-9-kiem-thu-nghiem-thu-va-kich-ban-demo.md`, `Phases/11-kich-ban-thuyet-trinh-va-phan-cong-trinh-bay.md`
- Contract reference: `Skills/hv-travel-technical-contracts/contracts-reference.md`

## Workflow/Checklist
1. Chọn format demo:
   - 5 phút cho live flow ngắn
   - 10 phút cho live flow + kiến trúc
2. Chuẩn bị dữ liệu:
   - tour mẫu
   - order `pending`
   - order `paid`
   - account admin/test nếu cần
3. Chạy test matrix tối thiểu:
   - homepage
   - archive
   - single tour
   - booking fields
   - cart metadata
   - checkout
   - thank-you/payment state
   - responsive mobile
4. Chuẩn bị fallback:
   - BCK hoặc QR demo
   - screenshot/video
   - route thank-you mẫu
5. Chuẩn bị lời nói:
   - mở đầu 30 giây
   - kiến trúc tổng thể
   - theme
   - plugin
   - payment
   - MongoDB
   - deploy
6. Chuẩn bị trả lời 5 câu phản biện chuẩn:
   - vì sao WordPress
   - vì sao MySQL + MongoDB
   - nếu cổng thanh toán lỗi thì sao
   - plugin custom khác gì plugin có sẵn
   - vì sao cần Docker và Render

## Common Mistakes
- Live demo mà không có order mẫu hoặc backup hình/video.
- Bắt đầu từ admin khiến câu chuyện người dùng bị đứt.
- Mô tả MongoDB như database core của WordPress.
- Giải thích BCK là giải pháp chính trong khi phase đã chốt payOS là hướng chính.
- Không luyện câu trả lời phản biện nên tự mâu thuẫn với phase tài liệu.

## Cross-references
- `hv-travel-project-context` cho kiến trúc tổng thể và scope.
- `hv-travel-technical-contracts` cho endpoint/status/env khi bị hỏi chi tiết.
- `payos-bck-payment-flow` cho fallback payment story.
- `docker-render-deploy` khi cần nói về deploy online, disk, backup và smoke test.
