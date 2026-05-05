<?php

defined('ABSPATH') || exit;

if (! $order) {
    echo '<main class="op-shell op-section"><p>' . esc_html__('Không tìm thấy đơn để hiển thị trạng thái thanh toán.', 'op-travel-shop') . '</p></main>';
    return;
}

$state = function_exists('op_travel_get_payment_state') ? op_travel_get_payment_state($order) : 'pending';
$labels = [
    'pending'   => __('Đơn đã được tạo và đang chờ xác nhận thanh toán.', 'op-travel-shop'),
    'paid'      => __('Thanh toán đã được xác nhận hợp lệ. Hành trình của bạn đã được khóa chỗ.', 'op-travel-shop'),
    'failed'    => __('Thanh toán chưa thành công. Bạn có thể thử lại hoặc đổi phương thức khác.', 'op-travel-shop'),
    'expired'   => __('QR hoặc payment link đã hết hạn. Hãy tạo giao dịch mới để tiếp tục.', 'op-travel-shop'),
    'cancelled' => __('Giao dịch đã bị hủy. Bạn có thể quay lại giỏ hàng hoặc checkout.', 'op-travel-shop'),
];
$state_labels = [
    'pending'   => __('Chờ thanh toán', 'op-travel-shop'),
    'paid'      => __('Đã thanh toán', 'op-travel-shop'),
    'failed'    => __('Thanh toán lỗi', 'op-travel-shop'),
    'expired'   => __('Đã hết hạn', 'op-travel-shop'),
    'cancelled' => __('Đã hủy', 'op-travel-shop'),
];
$bookings = op_travel_get_order_booking_snapshots($order);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$state_label = $state_labels[$state] ?? $state;
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => __('Hoàn tất', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(4);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 4 · Hoàn tất', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Trạng thái đơn hàng và booking của bạn.', 'op-travel-shop'); ?></h1>
    </header>

    <section class="op-status-panel op-thankyou-panel">
        <div class="op-thankyou-panel__head">
            <div>
                <p class="op-kicker"><?php esc_html_e('Trạng thái đơn', 'op-travel-shop'); ?></p>
                <h2><?php echo esc_html($state_label); ?></h2>
            </div>
            <span class="op-status-pill op-status-pill--lg op-status-pill--<?php echo esc_attr($state); ?>"><?php echo esc_html($state_label); ?></span>
        </div>
        <p class="op-thankyou-message"><?php echo esc_html($labels[$state] ?? $labels['pending']); ?></p>

        <div class="op-thankyou-facts">
            <p><span><?php esc_html_e('Mã đơn', 'op-travel-shop'); ?></span><strong><?php echo esc_html($order->get_order_number()); ?></strong></p>
            <p><span><?php esc_html_e('Ngày đặt', 'op-travel-shop'); ?></span><strong><time datetime="<?php echo esc_attr($order->get_date_created()->format('c')); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></time></strong></p>
            <p><span><?php esc_html_e('Tổng thanh toán', 'op-travel-shop'); ?></span><strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong></p>
            <p><span><?php esc_html_e('Phương thức', 'op-travel-shop'); ?></span><strong><?php echo esc_html($order->get_payment_method_title()); ?></strong></p>
        </div>

        <?php if (! empty($bookings)) : ?>
            <div class="op-thankyou-booking-list">
                <?php foreach ($bookings as $booking) : ?>
                    <?php
                    $booking_state = $booking['payment_status'] ?? $state;
                    $booking_state_label = $state_labels[$booking_state] ?? $booking_state;
                    ?>
                    <article class="op-thankyou-booking-card">
                        <div class="op-thankyou-booking-card__head">
                            <div>
                                <p class="op-kicker"><?php esc_html_e('Chi tiết giữ chỗ', 'op-travel-shop'); ?></p>
                                <h3><?php echo esc_html($booking['tour_name']); ?></h3>
                            </div>
                            <span class="op-status-pill op-status-pill--<?php echo esc_attr($booking_state); ?>"><?php echo esc_html($booking_state_label); ?></span>
                        </div>

                        <div class="op-thankyou-detail-grid">
                            <?php if ($booking['tour_code']) : ?>
                                <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                            <?php endif; ?>
                            <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><time datetime="<?php echo esc_attr($booking['departure_date']); ?>"><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></time></strong></p>
                            <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                            <?php if ($booking['customer_note']) : ?>
                                <p class="op-thankyou-detail-grid__wide"><span><?php esc_html_e('Ghi chú', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['customer_note']); ?></strong></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="op-hero__actions op-thankyou-actions">
            <a class="op-button" href="<?php echo esc_url($order->get_view_order_url()); ?>"><?php esc_html_e('Xem chi tiết đơn', 'op-travel-shop'); ?></a>
            <a class="op-button op-button--ghost" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Quay lại tours', 'op-travel-shop'); ?></a>
        </div>
    </section>

    <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
    <?php
    if (class_exists('\OPTravelCore\DemoPaymentQrHooks')) {
        \OPTravelCore\DemoPaymentQrHooks::render_for_order($order->get_id());
    }
    ?>
</main>
