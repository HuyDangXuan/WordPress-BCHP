<?php

defined('ABSPATH') || exit;

if (! $order) {
    echo '<main class="op-shell op-section"><p>' . esc_html__('Không tìm thấy đơn để hiển thị trạng thái thanh toán.', 'op-travel-shop') . '</p></main>';
    return;
}

$state = function_exists('op_travel_get_payment_state') ? op_travel_get_payment_state($order) : 'pending';
$labels = [
    'pending' => __('Đơn đã được tạo và đang chờ xác nhận thanh toán.', 'op-travel-shop'),
    'paid' => __('Thanh toán đã được xác nhận hợp lệ. Hành trình của bạn đã được khóa chỗ.', 'op-travel-shop'),
    'failed' => __('Thanh toán chưa thành công. Bạn có thể thử lại hoặc đổi phương thức khác.', 'op-travel-shop'),
    'expired' => __('QR hoặc payment link đã hết hạn. Hãy tạo giao dịch mới để tiếp tục.', 'op-travel-shop'),
    'cancelled' => __('Giao dịch đã bị hủy. Bạn có thể quay lại giỏ hàng hoặc checkout.', 'op-travel-shop'),
];
$bookings = op_travel_get_order_booking_snapshots($order);
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 4', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Hoàn tất hành trình đặt tour với một trạng thái rõ ràng cho từng đơn.', 'op-travel-shop'); ?></h1>
    </header>

    <section class="op-status-panel">
        <span class="op-status-pill op-status-pill--<?php echo esc_attr($state); ?>"><?php echo esc_html($state); ?></span>
        <p style="margin-top:18px;"><?php echo esc_html($labels[$state] ?? $labels['pending']); ?></p>
        <div class="op-summary-grid" style="margin-top:28px;">
            <p><strong><?php esc_html_e('Mã đơn', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($order->get_order_number()); ?></p>
            <p><strong><?php esc_html_e('Ngày đặt', 'op-travel-shop'); ?>:</strong> <?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></p>
            <p><strong><?php esc_html_e('Tổng thanh toán', 'op-travel-shop'); ?>:</strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
            <p><strong><?php esc_html_e('Phương thức', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($order->get_payment_method_title()); ?></p>
        </div>

        <?php if (! empty($bookings)) : ?>
            <div class="op-checkout-grid" style="margin-top:28px;">
                <?php foreach ($bookings as $booking) : ?>
                    <article class="op-summary-panel">
                        <p class="op-kicker"><?php echo esc_html($booking['payment_status']); ?></p>
                        <h2><?php echo esc_html($booking['tour_name']); ?></h2>
                        <?php if ($booking['tour_code']) : ?><p><?php echo esc_html($booking['tour_code']); ?></p><?php endif; ?>
                        <p><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></p>
                        <p><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></p>
                        <?php if ($booking['customer_note']) : ?><p><?php echo esc_html($booking['customer_note']); ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="op-hero__actions" style="margin-top:28px;">
            <a class="op-button" href="<?php echo esc_url($order->get_view_order_url()); ?>"><?php esc_html_e('Xem chi tiết đơn', 'op-travel-shop'); ?></a>
            <a class="op-button op-button--ghost" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/')); ?>"><?php esc_html_e('Quay lại tours', 'op-travel-shop'); ?></a>
        </div>
    </section>

    <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
    <?php do_action('woocommerce_thankyou', $order->get_id()); ?>
</main>
