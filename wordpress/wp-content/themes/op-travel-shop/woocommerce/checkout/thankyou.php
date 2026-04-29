<?php

defined('ABSPATH') || exit;

if (! $order) {
    echo '<main class="op-shell op-section"><p>' . esc_html__('KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n Ä‘á»ƒ hiá»ƒn thá»‹ tráº¡ng thÃ¡i thanh toÃ¡n.', 'op-travel-shop') . '</p></main>';
    return;
}

$state = function_exists('op_travel_get_payment_state') ? op_travel_get_payment_state($order) : 'pending';
$labels = [
    'pending' => __('ÄÆ¡n Ä‘Ã£ Ä‘Æ°á»£c táº¡o vÃ  Ä‘ang chá» xÃ¡c nháº­n thanh toÃ¡n.', 'op-travel-shop'),
    'paid' => __('Thanh toÃ¡n Ä‘Ã£ Ä‘Æ°á»£c xÃ¡c nháº­n há»£p lá»‡. HÃ nh trÃ¬nh cá»§a báº¡n Ä‘Ã£ Ä‘Æ°á»£c khÃ³a chá»—.', 'op-travel-shop'),
    'failed' => __('Thanh toÃ¡n chÆ°a thÃ nh cÃ´ng. Báº¡n cÃ³ thá»ƒ thá»­ láº¡i hoáº·c Ä‘á»•i phÆ°Æ¡ng thá»©c khÃ¡c.', 'op-travel-shop'),
    'expired' => __('QR hoáº·c payment link Ä‘Ã£ háº¿t háº¡n. HÃ£y táº¡o giao dá»‹ch má»›i Ä‘á»ƒ tiáº¿p tá»¥c.', 'op-travel-shop'),
    'cancelled' => __('Giao dá»‹ch Ä‘Ã£ bá»‹ há»§y. Báº¡n cÃ³ thá»ƒ quay láº¡i giá» hÃ ng hoáº·c checkout.', 'op-travel-shop'),
];
$bookings = op_travel_get_order_booking_snapshots($order);
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('BÆ°á»›c 4', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('HoÃ n táº¥t hÃ nh trÃ¬nh Ä‘áº·t tour vá»›i má»™t tráº¡ng thÃ¡i rÃµ rÃ ng cho tá»«ng Ä‘Æ¡n.', 'op-travel-shop'); ?></h1>
    </header>

    <section class="op-status-panel">
        <span class="op-status-pill op-status-pill--<?php echo esc_attr($state); ?>"><?php echo esc_html($state); ?></span>
        <p style="margin-top:18px;"><?php echo esc_html($labels[$state] ?? $labels['pending']); ?></p>
        <div class="op-summary-grid" style="margin-top:28px;">
            <p><strong><?php esc_html_e('MÃ£ Ä‘Æ¡n', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($order->get_order_number()); ?></p>
            <p><strong><?php esc_html_e('NgÃ y Ä‘áº·t', 'op-travel-shop'); ?>:</strong> <?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></p>
            <p><strong><?php esc_html_e('Tá»•ng thanh toÃ¡n', 'op-travel-shop'); ?>:</strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
            <p><strong><?php esc_html_e('PhÆ°Æ¡ng thá»©c', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($order->get_payment_method_title()); ?></p>
        </div>

        <?php if (! empty($bookings)) : ?>
            <div class="op-checkout-grid" style="margin-top:28px;">
                <?php foreach ($bookings as $booking) : ?>
                    <article class="op-summary-panel">
                        <p class="op-kicker"><?php echo esc_html($booking['payment_status']); ?></p>
                        <h2><?php echo esc_html($booking['tour_name']); ?></h2>
                        <?php if ($booking['tour_code']) : ?><p><?php echo esc_html($booking['tour_code']); ?></p><?php endif; ?>
                        <p><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></p>
                        <p><?php echo esc_html(sprintf(__('%d ngÆ°á»i lá»›n, %d tráº» em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></p>
                        <?php if ($booking['customer_note']) : ?><p><?php echo esc_html($booking['customer_note']); ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="op-hero__actions" style="margin-top:28px;">
            <a class="op-button" href="<?php echo esc_url($order->get_view_order_url()); ?>"><?php esc_html_e('Xem chi tiáº¿t Ä‘Æ¡n', 'op-travel-shop'); ?></a>
            <a class="op-button op-button--ghost" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/')); ?>"><?php esc_html_e('Quay láº¡i tours', 'op-travel-shop'); ?></a>
        </div>
    </section>

    <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
    <?php do_action('woocommerce_thankyou', $order->get_id()); ?>
</main>
