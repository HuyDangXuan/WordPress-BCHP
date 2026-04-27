# HV-Travel Skills

Bộ `Skills/` này phục vụ agent/Codex làm việc với dự án HV-Travel theo các phase trong `Phases/`. Mục tiêu là giúp agent nạp đúng ngữ cảnh, giữ đúng boundary kỹ thuật và trả lời nhất quán khi triển khai, kiểm thử, demo hoặc chuẩn bị bảo vệ.

Trong workspace hiện tại, nguồn sự thật trực tiếp là `Phases/`. Các path như `wp-config.php`, `wp-content/themes/op-travel-shop/` hay `wp-content/plugins/op-travel-core/` được giữ như target paths tham chiếu theo tài liệu phase, ngay cả khi mã nguồn WordPress chưa nằm trong repo này.

## Quy ước chung
- Tên folder và `name` trong frontmatter dùng English kebab-case.
- Nội dung `SKILL.md` viết bằng tiếng Việt.
- `description` luôn mở đầu bằng `Use when...` và chỉ mô tả điều kiện kích hoạt skill.
- Hợp đồng kỹ thuật dùng chung chỉ được định nghĩa ở `hv-travel-technical-contracts/contracts-reference.md`.
- Mọi skill phải ưu tiên trỏ chéo sang `hv-travel-technical-contracts` thay vì copy lại endpoint, env, status hay payload.

## Skill Index

| Skill | Phase nguồn | Dùng khi | Phụ thuộc chính |
| --- | --- | --- | --- |
| `hv-travel-project-context` | `README`, `Phase 1` | Cần scope hệ thống, boundary kiến trúc, vai trò WordPress/WooCommerce/MySQL/MongoDB/Docker/Render | None |
| `hv-travel-technical-contracts` | `README`, `Phase 10` | Cần endpoint, env, status, payload, collection mẫu hoặc naming chuẩn | `hv-travel-project-context` |
| `wordpress-setup-and-ops` | `Phase 2` | Setup WordPress/WooCommerce/theme/plugin, permalink, page mapping, SMTP, BCK, local Docker target | `hv-travel-project-context`, `hv-travel-technical-contracts` |
| `hv-travel-git-release-workflow` | `Phase 3` | Tổ chức nhánh, commit, release, tag và rollback cho HV-Travel | `hv-travel-project-context` |
| `op-travel-shop-theme-customization` | `Phase 4` | Sửa storefront, WooCommerce overrides, responsive hoặc visual journey 4 bước | `hv-travel-project-context`, `hv-travel-technical-contracts` |
| `op-travel-core-plugin-extension` | `Phase 5` | Thêm taxonomy, CPT, metadata, booking flow, REST hooks hoặc business logic plugin | `hv-travel-project-context`, `hv-travel-technical-contracts` |
| `payos-bck-payment-flow` | `Phase 6`, `Phase 10` | Tích hợp payOS, BCK fallback, webhook, status mapping, thank-you page payment states | `hv-travel-technical-contracts`, `op-travel-core-plugin-extension` |
| `mongodb-sync-service` | `Phase 7`, `Phase 10` | Dựng service, collections, MongoDB sync, report read-model hoặc payment events | `hv-travel-technical-contracts`, `payos-bck-payment-flow` |
| `docker-render-deploy` | `Phase 8`, `Phase 10` | Đóng gói Docker local, tách 4 service, deploy Render, backup/restore, smoke test | `hv-travel-project-context`, `hv-travel-technical-contracts` |
| `demo-and-defense-prep` | `Phase 9`, `Phase 11` | Chạy test matrix, chuẩn bị demo 5/10 phút, fallback plan, script bảo vệ và trả lời phản biện | `hv-travel-project-context`, `hv-travel-technical-contracts` |

## Trình tự nạp khuyến nghị
1. `hv-travel-project-context`
2. `hv-travel-technical-contracts`
3. Chọn một nhánh chuyên môn:
   - `wordpress-setup-and-ops`
   - `hv-travel-git-release-workflow`
   - `op-travel-shop-theme-customization`
   - `op-travel-core-plugin-extension`
   - `payos-bck-payment-flow`
   - `mongodb-sync-service`
   - `docker-render-deploy`
   - `demo-and-defense-prep`

## Mục tiêu thiết kế
- Giữ WordPress/WooCommerce là lớp storefront và commerce chính.
- Giữ `op-travel-shop` là nơi sở hữu giao diện và WooCommerce overrides.
- Giữ `op-travel-core` là nơi sở hữu business logic du lịch, booking metadata và REST integration.
- Giữ `MongoDB` ở lớp service business, không đẩy vào WordPress core.
- Giữ payOS là hướng thanh toán chính, BCK/QR demo là fallback.
- Giữ logic demo và defense bám sát đúng flow khách hàng và các phase đã chốt.
