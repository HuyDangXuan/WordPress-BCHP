<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Bạn cần đăng nhập để tiếp tục thanh toán.', 'op-travel-shop')));
    return;
}

$cart_bookings = [];
$checkout_translations = [
    'Checkout' => __('Thanh toán', 'op-travel-shop'),
    'Billing details' => __('Thông tin khách', 'op-travel-shop'),
    'First name' => __('Tên', 'op-travel-shop'),
    'Last name' => __('Họ và tên đệm', 'op-travel-shop'),
    'Company name' => __('Công ty', 'op-travel-shop'),
    'Company name (optional)' => __('Công ty (không bắt buộc)', 'op-travel-shop'),
    'Country / Region' => __('Quốc gia / Khu vực', 'op-travel-shop'),
    'Street address' => __('Địa chỉ', 'op-travel-shop'),
    'Apartment, suite, unit, etc.' => __('Căn hộ, tầng, tòa nhà...', 'op-travel-shop'),
    'Apartment, suite, unit, etc. (optional)' => __('Căn hộ, tầng, tòa nhà... (không bắt buộc)', 'op-travel-shop'),
    'Postcode / ZIP' => __('Mã bưu chính', 'op-travel-shop'),
    'Postcode / ZIP (optional)' => __('Mã bưu chính (không bắt buộc)', 'op-travel-shop'),
    'Town / City' => __('Tỉnh / Thành phố', 'op-travel-shop'),
    'Phone' => __('Số điện thoại', 'op-travel-shop'),
    'Email address' => __('Email', 'op-travel-shop'),
    'Additional information' => __('Thông tin thêm', 'op-travel-shop'),
    'Order notes' => __('Ghi chú đơn hàng', 'op-travel-shop'),
    'Order notes (optional)' => __('Ghi chú đơn hàng (không bắt buộc)', 'op-travel-shop'),
    'Notes about your order, e.g. special notes for delivery.' => __('Ghi chú thêm cho booking, ví dụ yêu cầu đón trả hoặc lưu ý đặc biệt.', 'op-travel-shop'),
    'Your order' => __('Đơn giữ chỗ của bạn', 'op-travel-shop'),
    'Product' => __('Tour', 'op-travel-shop'),
    'Subtotal' => __('Tạm tính', 'op-travel-shop'),
    'Total' => __('Tổng cộng', 'op-travel-shop'),
    'Place order' => __('Đặt tour và thanh toán', 'op-travel-shop'),
    'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our privacy policy.' => __('Thông tin cá nhân của bạn được dùng để xử lý đơn giữ chỗ, hỗ trợ trải nghiệm trên website và theo chính sách bảo mật.', 'op-travel-shop'),
    'Scan the ZaloPay QR after placing your tour order.' => __('Quét mã ZaloPay sau khi đặt tour để hoàn tất thanh toán.', 'op-travel-shop'),
];

$checkout_i18n_filter = static function ($translated, $text) use ($checkout_translations) {
    return $checkout_translations[$text] ?? $translated;
};

$checkout_gateway_description_filter = static function ($description) {
    if (trim(wp_strip_all_tags($description)) === 'Scan the ZaloPay QR after placing your tour order.') {
        return __('Quét mã ZaloPay sau khi đặt tour để hoàn tất thanh toán.', 'op-travel-shop');
    }

    return $description;
};

add_filter('gettext', $checkout_i18n_filter, 20, 2);
add_filter('woocommerce_gateway_description', $checkout_gateway_description_filter, 20);

foreach (WC()->cart->get_cart() as $cart_item) {
    $booking = op_travel_get_cart_booking_snapshot($cart_item);
    $_product = $cart_item['data'];

    if ($booking) {
        $pid = $_product ? $_product->get_id() : 0;
        $booking['duration_text']   = $pid ? get_post_meta($pid, '_duration_text', true) : '';
        $booking['departure_city']  = $pid ? get_post_meta($pid, '_departure_city', true) : '';
        $cart_bookings[] = $booking;
    }
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => __('Giỏ hàng', 'op-travel-shop'), 'url' => wc_get_cart_url()],
        ['label' => __('Thanh toán', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(3);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 3 · Thanh toán', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Hoàn thiện thông tin khách và chốt phương thức thanh toán.', 'op-travel-shop'); ?></h1>
    </header>

    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Thanh toán', 'op-travel-shop'); ?>">
        <div class="op-checkout-grid op-checkout-stack">
            <section class="op-summary-panel op-checkout-customer-panel">
                <p class="op-kicker"><?php esc_html_e('Thông tin khách', 'op-travel-shop'); ?></p>
                <?php if ($checkout->get_checkout_fields()) : ?>
                    <?php do_action('woocommerce_checkout_before_customer_details'); ?>
                    <div id="customer_details">
                        <?php do_action('woocommerce_checkout_billing'); ?>
                        <?php do_action('woocommerce_checkout_shipping'); ?>
                    </div>
                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>
                <?php endif; ?>
            </section>

            <aside class="op-summary-panel op-checkout-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Tóm tắt booking', 'op-travel-shop'); ?></p>
                <?php if (! empty($cart_bookings)) : ?>
                    <?php foreach ($cart_bookings as $booking) : ?>
                        <div class="op-booking-item op-checkout-booking-item">
                            <h3><?php echo esc_html($booking['tour_name']); ?></h3>
                            <div class="op-cart-booking-grid">
                                <?php if ($booking['tour_code']) : ?>
                                    <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                                <?php endif; ?>
                                <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><time datetime="<?php echo esc_attr($booking['departure_date']); ?>"><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></time></strong></p>
                                <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                                <?php if (! empty($booking['departure_city'])) : ?>
                                    <p><span><?php esc_html_e('Khởi hành', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['departure_city']); ?></strong></p>
                                <?php endif; ?>
                            </div>
                            <?php if (! empty($booking['duration_text'])) : ?>
                                <p class="op-checkout-booking-note"><?php echo esc_html($booking['duration_text']); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['customer_note']) : ?><p class="op-checkout-booking-note"><?php echo esc_html($booking['customer_note']); ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="op-kicker op-checkout-review-heading"><?php esc_html_e('Tóm tắt đơn và thanh toán', 'op-travel-shop'); ?></p>
                <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                <div id="order_review" class="woocommerce-checkout-review-order op-checkout-review-table">
                    <?php do_action('woocommerce_checkout_order_review'); ?>
                </div>
            </aside>
        </div>
    </form>
</main>
<?php
do_action('woocommerce_after_checkout_form', $checkout);
remove_filter('woocommerce_gateway_description', $checkout_gateway_description_filter, 20);
remove_filter('gettext', $checkout_i18n_filter, 20);
?>
