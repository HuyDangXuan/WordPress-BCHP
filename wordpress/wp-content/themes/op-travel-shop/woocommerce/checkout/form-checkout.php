<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Bạn cần đăng nhập để tiếp tục thanh toán.', 'op-travel-shop')));
    return;
}

$cart_bookings = [];

foreach (WC()->cart->get_cart() as $cart_item) {
    $booking = op_travel_get_cart_booking_snapshot($cart_item);

    if ($booking) {
        $cart_bookings[] = $booking;
    }
}
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 3', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Hoàn thiện thông tin khách và chốt phương thức thanh toán cho booking.', 'op-travel-shop'); ?></h1>
    </header>

    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'op-travel-shop'); ?>">
        <div class="op-checkout-grid">
            <section class="op-summary-panel">
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

            <aside class="op-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Tóm tắt booking', 'op-travel-shop'); ?></p>
                <?php if (! empty($cart_bookings)) : ?>
                    <?php foreach ($cart_bookings as $booking) : ?>
                        <div style="padding:12px 0;border-bottom:1px solid rgba(18, 38, 47, 0.12);">
                            <p><strong><?php echo esc_html($booking['tour_name']); ?></strong></p>
                            <?php if ($booking['tour_code']) : ?><p><?php echo esc_html($booking['tour_code']); ?></p><?php endif; ?>
                            <p><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></p>
                            <p><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></p>
                            <?php if ($booking['customer_note']) : ?><p><?php echo esc_html($booking['customer_note']); ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="op-kicker" style="margin-top:20px;"><?php esc_html_e('Tóm tắt đơn và thanh toán', 'op-travel-shop'); ?></p>
                <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                <div id="order_review" class="woocommerce-checkout-review-order">
                    <?php do_action('woocommerce_checkout_order_review'); ?>
                </div>
            </aside>
        </div>
    </form>
</main>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
