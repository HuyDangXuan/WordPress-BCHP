<?php

defined('ABSPATH') || exit;

get_header('shop');

global $product;

if (! $product) {
    get_footer('shop');
    return;
}

$duration = get_post_meta($product->get_id(), '_duration_text', true);
$departure = get_post_meta($product->get_id(), '_departure_city', true);
$meeting_point = get_post_meta($product->get_id(), '_meeting_point', true);
$highlights = get_post_meta($product->get_id(), '_tour_highlights', true);
?>
<main class="op-shell op-section">
    <?php do_action('woocommerce_before_single_product'); ?>

    <div class="op-detail-grid">
        <section class="op-detail-gallery" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Bước 2', 'op-travel-shop'); ?></p>
            <h1><?php the_title(); ?></h1>
            <div class="op-meta-line">
                <?php if ($duration) : ?><span><?php echo esc_html($duration); ?></span><?php endif; ?>
                <?php if ($departure) : ?><span><?php echo esc_html($departure); ?></span><?php endif; ?>
                <?php if ($meeting_point) : ?><span><?php echo esc_html($meeting_point); ?></span><?php endif; ?>
            </div>
            <div class="woocommerce-product-gallery">
                <?php echo $product->get_image('large'); ?>
            </div>
            <?php if ($highlights) : ?>
                <div class="op-summary-panel" style="margin-top:24px;">
                    <p class="op-kicker"><?php esc_html_e('Điểm nhấn hành trình', 'op-travel-shop'); ?></p>
                    <div><?php echo wpautop(wp_kses_post($highlights)); ?></div>
                </div>
            <?php endif; ?>
        </section>

        <aside class="op-booking-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Booking Panel', 'op-travel-shop'); ?></p>
            <p><?php esc_html_e('Single product được tổ chức lại như trang đặt tour: chọn ngày khởi hành, số lượng khách và chốt giữ chỗ trước khi đi tiếp sang checkout.', 'op-travel-shop'); ?></p>
            <div class="summary entry-summary">
                <?php woocommerce_template_single_price(); ?>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </aside>
    </div>
</main>
<?php
get_footer('shop');
