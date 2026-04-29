<?php

defined('ABSPATH') || exit;
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('BÆ°á»›c 2', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('XÃ¡c nháº­n giá»¯ chá»— trÆ°á»›c khi chuyá»ƒn sang thanh toÃ¡n.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Cart Ä‘Æ°á»£c dÃ¹ng nhÆ° má»™t bÆ°á»›c rà soÃ¡t booking snapshot thay vÃ¬ chá»‰ lÃ  danh sÃ¡ch sáº£n pháº©m thÃ´.', 'op-travel-shop'); ?></p>
    </header>

    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <div class="op-tour-grid" style="grid-template-columns:1fr;">
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : ?>
                <?php
                $_product = $cart_item['data'];
                if (! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0) {
                    continue;
                }

                $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
                $booking = op_travel_get_cart_booking_snapshot($cart_item);
                ?>
                <article class="op-tour-card">
                    <div class="op-tour-card__content">
                        <h3>
                            <?php if ($product_permalink) : ?>
                                <a href="<?php echo esc_url($product_permalink); ?>"><?php echo wp_kses_post($_product->get_name()); ?></a>
                            <?php else : ?>
                                <?php echo wp_kses_post($_product->get_name()); ?>
                            <?php endif; ?>
                        </h3>
                        <div class="op-meta-line">
                            <span><?php echo wp_kses_post(WC()->cart->get_product_price($_product)); ?></span>
                            <span><?php echo esc_html(sprintf(__('Sá»‘ lÆ°á»£ng: %d', 'op-travel-shop'), $cart_item['quantity'])); ?></span>
                        </div>

                        <?php if ($booking) : ?>
                            <div class="op-summary-grid" style="margin-top:18px;">
                                <?php if ($booking['tour_code']) : ?><p><strong><?php esc_html_e('MÃ£ tour', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($booking['tour_code']); ?></p><?php endif; ?>
                                <p><strong><?php esc_html_e('NgÃ y khá»Ÿi hÃ nh', 'op-travel-shop'); ?>:</strong> <?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></p>
                                <p><strong><?php esc_html_e('KhÃ¡ch', 'op-travel-shop'); ?>:</strong> <?php echo esc_html(sprintf('%d ngÆ°á»i lá»›n, %d tráº» em', $booking['adult_count'], $booking['child_count'])); ?></p>
                                <p><strong><?php esc_html_e('Tráº¡ng thÃ¡i thanh toÃ¡n', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($booking['payment_status']); ?></p>
                                <?php if ($booking['customer_note']) : ?><p><strong><?php esc_html_e('Ghi chÃº', 'op-travel-shop'); ?>:</strong> <?php echo esc_html($booking['customer_note']); ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php foreach (wc_get_formatted_cart_item_data($cart_item) as $meta) : ?>
                            <p><strong><?php echo esc_html($meta['key']); ?>:</strong> <?php echo wp_kses_post($meta['display']); ?></p>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="op-checkout-grid" style="margin-top:32px;">
            <section class="op-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Giá»¯ chá»—', 'op-travel-shop'); ?></p>
                <?php woocommerce_cart_totals(); ?>
            </section>
            <section class="op-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Tiáº¿p tá»¥c', 'op-travel-shop'); ?></p>
                <p><?php esc_html_e('Sau khi rà soÃ¡t metadata booking, hÃ£y tiáº¿p tá»¥c sang checkout Ä‘á»ƒ chá»n phÆ°Æ¡ng thá»©c thanh toÃ¡n.', 'op-travel-shop'); ?></p>
                <?php do_action('woocommerce_proceed_to_checkout'); ?>
            </section>
        </div>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </form>
</main>
