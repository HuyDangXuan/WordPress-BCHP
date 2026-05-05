<?php

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/thanh-toan/');
$payment_status_labels = [
    'pending' => __('Chờ thanh toán', 'op-travel-shop'),
    'paid' => __('Đã thanh toán', 'op-travel-shop'),
    'failed' => __('Thanh toán lỗi', 'op-travel-shop'),
    'expired' => __('Đã hết hạn', 'op-travel-shop'),
    'cancelled' => __('Đã hủy', 'op-travel-shop'),
];
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => __('Giỏ hàng', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(2);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 2 · Xác nhận giữ chỗ', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Xác nhận giữ chỗ trước khi chuyển sang thanh toán.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Rà soát thông tin booking bên dưới, sau đó tiếp tục sang bước chọn phương thức thanh toán.', 'op-travel-shop'); ?></p>
    </header>

    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <div class="op-cart-tour-list">
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : ?>
                <?php
                $_product = $cart_item['data'];
                if (! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0) {
                    continue;
                }

                $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
                $booking = op_travel_get_cart_booking_snapshot($cart_item);
                $pid = $_product->get_id();
                $duration_text = get_post_meta($pid, '_duration_text', true);
                $departure_city = get_post_meta($pid, '_departure_city', true);
                $payment_status = $booking['payment_status'] ?? 'pending';
                $payment_status_label = $payment_status_labels[$payment_status] ?? $payment_status;
                ?>
                <article class="op-tour-card op-cart-tour-card">
                    <a class="op-cart-tour-card__media" href="<?php echo esc_url($product_permalink ?: '#'); ?>" aria-label="<?php echo esc_attr($_product->get_name()); ?>">
                        <?php echo $_product->get_image('medium'); ?>
                    </a>

                    <div class="op-cart-tour-card__content">
                        <div class="op-cart-tour-card__header">
                            <div>
                                <p class="op-kicker"><?php esc_html_e('Tour đã chọn', 'op-travel-shop'); ?></p>
                                <h3>
                                    <?php if ($product_permalink) : ?>
                                        <a href="<?php echo esc_url($product_permalink); ?>"><?php echo wp_kses_post($_product->get_name()); ?></a>
                                    <?php else : ?>
                                        <?php echo wp_kses_post($_product->get_name()); ?>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <span class="op-status-pill op-status-pill--pending"><?php echo esc_html($payment_status_label); ?></span>
                        </div>

                        <div class="op-cart-meta-row">
                            <span class="op-meta-detail__item">
                                <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
                                <?php echo wp_kses_post(WC()->cart->get_product_price($_product)); ?>
                            </span>
                            <span class="op-meta-detail__item"><?php echo esc_html(sprintf(__('Số lượng: %d', 'op-travel-shop'), $cart_item['quantity'])); ?></span>
                            <?php if ($duration_text) : ?>
                                <span class="op-meta-detail__item">
                                    <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                                    <?php echo esc_html($duration_text); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($departure_city) : ?>
                                <span class="op-meta-detail__item">
                                    <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                                    <?php echo esc_html($departure_city); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($booking) : ?>
                            <div class="op-cart-booking-grid">
                                <?php if ($booking['tour_code']) : ?>
                                    <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                                <?php endif; ?>
                                <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><time datetime="<?php echo esc_attr($booking['departure_date']); ?>"><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></time></strong></p>
                                <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                                <p><span><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></span><strong><?php echo esc_html($payment_status_label); ?></strong></p>
                                <?php if ($booking['customer_note']) : ?>
                                    <p class="op-cart-booking-grid__wide"><span><?php esc_html_e('Ghi chú', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['customer_note']); ?></strong></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="op-checkout-grid op-cart-actions-grid">
            <section class="op-summary-panel op-cart-total-panel">
                <p class="op-kicker"><?php esc_html_e('Tổng giữ chỗ', 'op-travel-shop'); ?></p>
                <div class="op-cart-summary-row">
                    <span><?php esc_html_e('Tạm tính', 'op-travel-shop'); ?></span>
                    <strong><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></strong>
                </div>
                <div class="op-cart-summary-row op-cart-summary-row--total">
                    <span><?php esc_html_e('Tổng cộng', 'op-travel-shop'); ?></span>
                    <strong><?php echo wp_kses_post(WC()->cart->get_total()); ?></strong>
                </div>
            </section>
            <section class="op-summary-panel op-cart-next-panel">
                <p class="op-kicker"><?php esc_html_e('Tiếp tục', 'op-travel-shop'); ?></p>
                <p><?php esc_html_e('Sau khi kiểm tra thông tin giữ chỗ, hãy chuyển sang bước thanh toán để chọn phương thức phù hợp.', 'op-travel-shop'); ?></p>
                <a class="op-button" href="<?php echo esc_url($checkout_url); ?>"><?php esc_html_e('Tiếp tục thanh toán', 'op-travel-shop'); ?></a>
            </section>
        </div>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </form>
</main>
