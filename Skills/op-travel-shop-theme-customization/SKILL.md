---
name: op-travel-shop-theme-customization
description: Use when changing HV-Travel storefront screens, WooCommerce template overrides, workflow-driven UI, responsive behavior, or the premium travel identity owned by the op-travel-shop theme.
---

# OP Travel Shop Theme Customization

## Phase Sources
- `Phases/04-phase-4-tuy-bien-skin-theme-op-travel-shop.md`

## Overview
Skill này khóa vai trò của theme `op-travel-shop`: biến WooCommerce từ shop mặc định thành hành trình mua tour 4 bước với nhận diện travel premium. Theme sở hữu template overrides, visual language, CTA flow và responsive behavior, nhưng không được nuốt business logic đáng lẽ thuộc plugin.

## When to Use
- Cần sửa homepage, archive tour, single tour, cart, checkout hoặc thank-you page.
- Cần chỉnh WooCommerce template overrides hoặc filter hành vi UI trong theme.
- Cần giữ đúng journey 4 bước và visual identity của HV-Travel.
- Cần kiểm tra responsive/mobile trước demo.

## Project Rules
- Theme sở hữu storefront và WooCommerce template override; plugin sở hữu business logic và data persistence.
- Journey chuẩn là:
  - chọn tour
  - xác nhận giữ chỗ
  - thanh toán
  - hoàn tất
- Archive phải gắn chặt với taxonomy `destination` và `tour_style`.
- Single product là trang “đặt tour”, không phải product detail chung chung.
- Cart, checkout và thank-you phải kể tiếp cùng một câu chuyện booking.
- Phải giữ typography và palette premium đã chốt: `Cormorant Garamond`, `Manrope`, `op-sand`, `op-cream`, `op-ink`, `op-gold`, `op-sea`.

## Source of Truth
- Tài liệu phase: `Phases/04-phase-4-tuy-bien-skin-theme-op-travel-shop.md`
- Path tham chiếu từ phase:
  - `wp-content/themes/op-travel-shop/front-page.php`
  - `wp-content/themes/op-travel-shop/inc/workflow.php`
  - `wp-content/themes/op-travel-shop/inc/woocommerce.php`
  - `wp-content/themes/op-travel-shop/assets/css/theme.css`
  - `wp-content/themes/op-travel-shop/woocommerce/`

## Workflow/Checklist
1. Xác định màn hình đang sửa thuộc bước nào trong journey 4 bước.
2. Kiểm tra template override hoặc file `inc/woocommerce.php` liên quan trước khi chạm markup/CSS.
3. Giữ đúng ngôn ngữ giao diện:
   - archive = shortlist/chọn tour
   - single = đặt tour
   - cart = xác nhận giữ chỗ
   - checkout = hoàn tất thông tin và thanh toán
   - thank-you = trạng thái cuối cùng
4. Nếu cần dữ liệu mới, xác nhận plugin đã có metadata/taxonomy tương ứng thay vì hardcode ở theme.
5. Sau khi đổi giao diện, luôn rà:
   - taxonomy filters
   - CTA text
   - booking panel visibility
   - responsive mobile/tablet
6. Nếu thay đổi chạm payment messaging, đối chiếu thêm `payos-bck-payment-flow`.

## Common Mistakes
- Nhét validate booking hoặc business rule vào template theme.
- Làm đẹp một màn hình nhưng phá nhịp 4 bước toàn flow.
- Override WooCommerce nhưng quên rà filter text/gateway order trong `inc/woocommerce.php`.
- Làm archive tour như product grid chung, bỏ mất taxonomy du lịch.
- Chỉnh desktop đẹp nhưng không kiểm tra mobile.

## Cross-references
- `hv-travel-project-context` cho boundary theme vs plugin.
- `hv-travel-technical-contracts` khi UI chạm route/status payment.
- `op-travel-core-plugin-extension` cho metadata và booking fields.
- `payos-bck-payment-flow` cho thank-you state và payment messaging.
