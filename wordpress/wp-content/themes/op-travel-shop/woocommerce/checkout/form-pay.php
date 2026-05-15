<?php

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');
$payment_state = function_exists('op_travel_get_payment_state') ? op_travel_get_payment_state($order) : 'pending';
$payment_state_label = function_exists('op_travel_get_account_status_label') ? op_travel_get_account_status_label($payment_state) : ucfirst($payment_state);
$bookings = function_exists('op_travel_get_order_booking_snapshots') ? op_travel_get_order_booking_snapshots($order) : [];
$has_payment_assets = (bool) ($order->get_meta('_op_travel_payment_qr_url', true) || $order->get_meta('_op_travel_payment_checkout_url', true) || $order->get_meta('_op_travel_payment_code', true));
$localized_order_button_text = trim((string) $order_button_text);

if ($localized_order_button_text === '' || strtolower($localized_order_button_text) === 'pay for order') {
    $localized_order_button_text = __('Thanh toán booking này', 'op-travel-shop');
}

$gateway_description_filter = static function ($description) {
    if (trim(wp_strip_all_tags($description)) === 'Scan the SePay QR after placing your tour order.') {
        return __('Quét mã SePay sau khi xác nhận booking để hoàn tất thanh toán.', 'op-travel-shop');
    }

    return $description;
};

add_filter('woocommerce_gateway_description', $gateway_description_filter, 20);

$order_items = [];
$booking_index = 0;

foreach ($order->get_items() as $item_id => $item) {
    if (! apply_filters('woocommerce_order_item_visible', true, $item)) {
        continue;
    }

    $product = $item->get_product();
    $product_id = $product instanceof WC_Product ? $product->get_id() : 0;
    $booking = isset($bookings[$booking_index]) ? $bookings[$booking_index] : null;

    $order_items[] = [
        'name' => $item->get_name(),
        'quantity_html' => apply_filters('woocommerce_order_item_quantity_html', sprintf('&times;&nbsp;%s', esc_html($item->get_quantity())), $item),
        'subtotal_html' => $order->get_formatted_line_subtotal($item),
        'duration_text' => $product_id ? get_post_meta($product_id, '_duration_text', true) : '',
        'departure_city' => $product_id ? get_post_meta($product_id, '_departure_city', true) : '',
        'booking' => $booking,
    ];

    $booking_index++;
}

$order_totals = $order->get_order_item_totals();
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => __('Giỏ hàng', 'op-travel-shop'), 'url' => $cart_url],
        ['label' => __('Thanh toán', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(3);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 3 · Thanh toán', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Hoàn tất thanh toán cho booking đang chờ xử lý.', 'op-travel-shop'); ?></h1>
    </header>

    <form id="order_review" method="post" class="op-pay-order-form" data-op-loading-form data-op-skeleton-target=".op-pay-order-payment">
        <div class="op-pay-order-layout op-checkout-grid op-checkout-stack">
            <section class="op-summary-panel op-checkout-customer-panel op-pay-order-summary op-pay-order-info-panel">
                <p class="op-kicker"><?php esc_html_e('Thông tin booking', 'op-travel-shop'); ?></p>
                <div class="op-pay-order-summary__head">
                    <div>
                        <h2><?php printf(esc_html__('Booking #%s đang chờ thanh toán.', 'op-travel-shop'), esc_html($order->get_order_number())); ?></h2>
                        <p class="op-pay-order-note"><?php esc_html_e('Rà soát lại toàn bộ thông tin giữ chỗ trước khi tiếp tục sang đúng bước thanh toán cho booking này.', 'op-travel-shop'); ?></p>
                    </div>
                    <span class="op-status-pill op-status-pill--<?php echo esc_attr($payment_state); ?>"><?php echo esc_html($payment_state_label); ?></span>
                </div>

                <div class="op-pay-order-facts">
                    <p><span><?php esc_html_e('Mã đơn', 'op-travel-shop'); ?></span><strong>#<?php echo esc_html($order->get_order_number()); ?></strong></p>
                    <p><span><?php esc_html_e('Ngày tạo', 'op-travel-shop'); ?></span><strong><?php echo esc_html($order->get_date_created() ? wc_format_datetime($order->get_date_created()) : ''); ?></strong></p>
                    <p><span><?php esc_html_e('Số tiền cần xử lý', 'op-travel-shop'); ?></span><strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong></p>
                    <p><span><?php esc_html_e('Phương thức hiện tại', 'op-travel-shop'); ?></span><strong><?php echo esc_html($order->get_payment_method_title() ?: __('Chưa chọn', 'op-travel-shop')); ?></strong></p>
                </div>

                <p class="op-kicker"><?php esc_html_e('Chi tiết booking đang chờ thanh toán', 'op-travel-shop'); ?></p>
                <?php foreach ($order_items as $entry) : ?>
                    <?php
                    $booking = is_array($entry['booking']) ? $entry['booking'] : null;
                    $booking_state = $booking['payment_status'] ?? $payment_state;
                    $booking_state_label = function_exists('op_travel_get_account_status_label') ? op_travel_get_account_status_label($booking_state) : ucfirst((string) $booking_state);
                    ?>
                    <article class="op-booking-item op-checkout-booking-item op-pay-order-card">
                        <div class="op-pay-order-card__head">
                            <div>
                                <h3><?php echo esc_html($entry['name']); ?></h3>
                                <p class="op-pay-order-card__meta"><?php echo wp_kses_post($entry['quantity_html']); ?></p>
                            </div>
                            <div class="op-pay-order-card__aside">
                                <span class="op-status-pill op-status-pill--<?php echo esc_attr($booking_state); ?>"><?php echo esc_html($booking_state_label); ?></span>
                                <strong><?php echo wp_kses_post($entry['subtotal_html']); ?></strong>
                            </div>
                        </div>

                        <div class="op-cart-booking-grid op-pay-order-card__grid">
                            <?php if (! empty($booking['tour_code'])) : ?>
                                <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                            <?php endif; ?>
                            <?php if (! empty($booking['departure_date'])) : ?>
                                <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></strong></p>
                            <?php endif; ?>
                            <?php if (! empty($entry['departure_city'])) : ?>
                                <p><span><?php esc_html_e('Khởi hành', 'op-travel-shop'); ?></span><strong><?php echo esc_html($entry['departure_city']); ?></strong></p>
                            <?php endif; ?>
                            <?php if ($booking) : ?>
                                <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                            <?php endif; ?>
                        </div>

                        <?php if (! empty($entry['duration_text'])) : ?>
                            <p class="op-checkout-booking-note op-pay-order-card__note"><?php echo esc_html($entry['duration_text']); ?></p>
                        <?php endif; ?>
                        <?php if (! empty($booking['customer_note'])) : ?>
                            <p class="op-checkout-booking-note op-pay-order-card__note"><?php echo esc_html($booking['customer_note']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="op-summary-panel op-checkout-summary-panel op-pay-order-payment op-skeleton-card">
                <p class="op-kicker"><?php esc_html_e('Tóm tắt đơn và thanh toán', 'op-travel-shop'); ?></p>
                <p class="op-checkout-selection-note"><?php esc_html_e('Trang này tiếp tục đúng flow của bước thanh toán thường, nhưng chỉ áp dụng cho booking đang chờ thanh toán lại.', 'op-travel-shop'); ?></p>

                <?php if (! empty($order_totals)) : ?>
                    <div class="op-pay-order-total-list">
                        <?php foreach ($order_totals as $key => $total) : ?>
                            <div class="op-pay-order-total <?php echo esc_attr($key === 'order_total' ? 'op-pay-order-total--grand' : ''); ?>">
                                <span><?php echo wp_kses_post($total['label']); ?></span>
                                <strong><?php echo wp_kses_post($total['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="op-kicker op-checkout-review-heading"><?php esc_html_e('Phương thức thanh toán', 'op-travel-shop'); ?></p>

                <?php do_action('woocommerce_pay_order_before_payment'); ?>

                <div id="payment" class="op-checkout-review-table op-pay-order-payment__box">
                    <?php if ($order->needs_payment()) : ?>
                        <ul class="wc_payment_methods payment_methods methods">
                            <?php if (! empty($available_gateways)) : ?>
                                <?php foreach ($available_gateways as $gateway) : ?>
                                    <?php wc_get_template('checkout/payment-method.php', ['gateway' => $gateway]); ?>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li>
                                    <?php
                                    wc_print_notice(
                                        __('Hiện chưa có phương thức thanh toán phù hợp cho booking này. Vui lòng liên hệ HV-Travel nếu bạn cần hỗ trợ hoàn tất giao dịch.', 'op-travel-shop'),
                                        'notice'
                                    );
                                    ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php else : ?>
                        <p class="op-pay-order-note"><?php esc_html_e('Booking này hiện không yêu cầu thanh toán thêm.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>

                    <div class="form-row">
                        <input type="hidden" name="woocommerce_pay" value="1" />

                        <?php wc_get_template('checkout/terms.php'); ?>

                        <?php do_action('woocommerce_pay_order_before_submit'); ?>

                        <?php
                        echo apply_filters(
                            'woocommerce_pay_order_button_html',
                            '<button type="submit" class="button alt op-button" id="place_order" value="' . esc_attr($localized_order_button_text) . '" data-value="' . esc_attr($localized_order_button_text) . '">' . esc_html($localized_order_button_text) . '</button>'
                        );
                        ?>

                        <?php do_action('woocommerce_pay_order_after_submit'); ?>

                        <?php wp_nonce_field('woocommerce-pay', 'woocommerce-pay-nonce'); ?>
                    </div>
                </div>

                <?php if ($has_payment_assets && class_exists('\OPTravelSePay\SePayPaymentQrHooks')) : ?>
                    <div class="op-pay-order-assets" data-op-skeleton-target="payment-assets">
                        <?php \OPTravelSePay\SePayPaymentQrHooks::render_for_order($order->get_id()); ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </form>
</main>
<?php
remove_filter('woocommerce_gateway_description', $gateway_description_filter, 20);
