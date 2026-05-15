<?php

defined('ABSPATH') || exit;

$order_id = isset($order_id) ? absint($order_id) : absint(get_query_var('view-order'));
$order = wc_get_order($order_id);

if (! $order || (int) $order->get_user_id() !== get_current_user_id()) {
    wc_print_notice(__('Không tìm thấy booking bạn yêu cầu.', 'op-travel-shop'), 'error');
    return;
}

$card = function_exists('op_travel_build_account_order_card') ? op_travel_build_account_order_card($order) : [];
$bookings = function_exists('op_travel_get_order_booking_snapshots') ? op_travel_get_order_booking_snapshots($order) : [];
$orders_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', op_travel_get_account_url()) : op_travel_get_account_url();
?>
<section class="op-account-order-detail" data-reveal>
    <header class="op-account-section__head">
        <div>
            <p class="op-kicker"><?php printf(esc_html__('Booking #%s', 'op-travel-shop'), esc_html($card['order_number'] ?? $order->get_order_number())); ?></p>
            <h1><?php esc_html_e('Chi tiết booking và trạng thái thanh toán.', 'op-travel-shop'); ?></h1>
        </div>
        <a href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('Quay lại danh sách booking', 'op-travel-shop'); ?></a>
    </header>

    <section class="op-account-summary">
        <div class="op-account-summary__grid">
            <article>
                <span><?php esc_html_e('Trạng thái', 'op-travel-shop'); ?></span>
                <strong data-op-payment-state-text><?php echo esc_html($card['payment_state_label'] ?? __('Chờ thanh toán', 'op-travel-shop')); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Tổng thanh toán', 'op-travel-shop'); ?></span>
                <strong><?php echo wp_kses_post($card['total_html'] ?? $order->get_formatted_order_total()); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Mã đơn', 'op-travel-shop'); ?></span>
                <strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
            </article>
        </div>
    </section>

    <?php if (! empty($bookings)) : ?>
        <div class="op-account-orders__list">
            <?php foreach ($bookings as $booking) : ?>
                <article class="op-account-order-card">
                    <div class="op-account-order-card__head">
                        <div>
                            <p class="op-kicker"><?php esc_html_e('Thông tin booking', 'op-travel-shop'); ?></p>
                            <h3><?php echo esc_html($booking['tour_name'] ?: __('Hành trình đang cập nhật', 'op-travel-shop')); ?></h3>
                        </div>
                        <span class="op-status-pill op-status-pill--<?php echo esc_attr($booking['payment_status'] ?? ($card['payment_state'] ?? 'pending')); ?>" data-op-payment-state-pill>
                            <?php echo esc_html(op_travel_get_account_status_label($booking['payment_status'] ?? ($card['payment_state'] ?? 'pending'))); ?>
                        </span>
                    </div>

                    <div class="op-account-order-card__grid">
                        <?php if (! empty($booking['tour_code'])) : ?>
                            <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                        <?php endif; ?>
                        <?php if (! empty($booking['departure_date'])) : ?>
                            <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></strong></p>
                        <?php endif; ?>
                        <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                        <?php if (! empty($booking['amount'])) : ?>
                            <p><span><?php esc_html_e('Giá trị booking', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['amount']); ?></strong></p>
                        <?php endif; ?>
                    </div>

                    <?php if (! empty($booking['customer_note'])) : ?>
                        <p class="op-account-order-card__note"><?php echo esc_html($booking['customer_note']); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="op-account-order-card__actions">
        <p><?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
        <a class="op-button" href="<?php echo esc_url($card['primary_action_url'] ?? $orders_url); ?>"><?php echo esc_html($card['primary_action_label'] ?? __('Xem chi tiết booking', 'op-travel-shop')); ?></a>
    </div>

    <div class="op-payment-assets op-skeleton-card" data-op-skeleton-target="payment-assets">
        <?php
        if (class_exists('\OPTravelSePay\SePayPaymentQrHooks')) {
            \OPTravelSePay\SePayPaymentQrHooks::render_for_order($order->get_id());
        }
        ?>
    </div>
</section>
