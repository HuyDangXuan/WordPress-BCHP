<?php

defined('ABSPATH') || exit;
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 2', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Xác nhận giữ chỗ trước khi chuyển sang thanh toán.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Cart được dùng như một bước rà lại thông tin booking thay vì chỉ là danh sách sản phẩm thô.', 'op-travel-shop'); ?></p>
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
                            <span><?php echo esc_html(sprintf(__('Số lượng: %d', 'op-travel-shop'), $cart_item['quantity'])); ?></span>
                        </div>
                        <?php foreach (wc_get_formatted_cart_item_data($cart_item) as $meta) : ?>
                            <p><strong><?php echo esc_html($meta['key']); ?>:</strong> <?php echo wp_kses_post($meta['display']); ?></p>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="op-checkout-grid" style="margin-top:32px;">
            <section class="op-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Giữ chỗ', 'op-travel-shop'); ?></p>
                <?php woocommerce_cart_totals(); ?>
            </section>
            <section class="op-summary-panel">
                <p class="op-kicker"><?php esc_html_e('Tiếp tục', 'op-travel-shop'); ?></p>
                <p><?php esc_html_e('Sau khi rà lại metadata booking, hãy tiếp tục sang checkout để chọn phương thức thanh toán.', 'op-travel-shop'); ?></p>
                <?php do_action('woocommerce_proceed_to_checkout'); ?>
            </section>
        </div>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </form>
</main>
