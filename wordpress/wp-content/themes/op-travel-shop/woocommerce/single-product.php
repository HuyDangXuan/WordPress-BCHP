<?php

defined('ABSPATH') || exit;

get_header('shop');

global $product;

$product = (is_object($product) && method_exists($product, 'get_id')) ? $product : wc_get_product(get_the_ID());

if (! $product || ! method_exists($product, 'get_id')) {
    get_footer('shop');
    return;
}

$product_id = $product->get_id();
$tour_code = get_post_meta($product_id, '_tour_code', true);
$duration = get_post_meta($product_id, '_duration_text', true);
$departure = get_post_meta($product_id, '_departure_city', true);
$meeting_point = get_post_meta($product_id, '_meeting_point', true);
$available_departure_dates_raw = get_post_meta($product_id, '_available_departure_dates', true);
$highlights_raw = get_post_meta($product_id, '_tour_highlights', true);
$itinerary_raw = get_post_meta($product_id, '_tour_itinerary', true);
$includes_raw = get_post_meta($product_id, '_tour_includes', true);
$excludes_raw = get_post_meta($product_id, '_tour_excludes', true);
$available_departure_dates = op_travel_get_multiline_meta_values($product_id, '_available_departure_dates');
$highlights = op_travel_get_multiline_meta_values($product_id, '_tour_highlights');
$itinerary = op_travel_get_multiline_meta_values($product_id, '_tour_itinerary');
$includes = op_travel_get_multiline_meta_values($product_id, '_tour_includes');
$excludes = op_travel_get_multiline_meta_values($product_id, '_tour_excludes');
$gallery_ids = op_travel_get_product_gallery_ids($product_id);
$destination_terms = get_the_terms($product_id, 'destination');
$style_terms = get_the_terms($product_id, 'tour_style');
$short_description = $product->get_short_description() ?: get_the_excerpt();
?>
<main class="op-shell op-section">
    <?php do_action('woocommerce_before_single_product'); ?>

    <div class="op-detail-grid">
        <section class="op-detail-gallery" data-reveal>
            <div class="op-eyebrow-list">
                <span class="op-eyebrow"><?php esc_html_e('BÆ°á»›c 2', 'op-travel-shop'); ?></span>
                <?php if ($tour_code) : ?><span class="op-eyebrow"><?php echo esc_html($tour_code); ?></span><?php endif; ?>
                <?php if ($destination_terms && ! is_wp_error($destination_terms)) : ?><span class="op-eyebrow"><?php echo esc_html($destination_terms[0]->name); ?></span><?php endif; ?>
                <?php if ($style_terms && ! is_wp_error($style_terms)) : ?><span class="op-eyebrow"><?php echo esc_html($style_terms[0]->name); ?></span><?php endif; ?>
            </div>
            <h1><?php the_title(); ?></h1>
            <?php if ($short_description) : ?>
                <p><?php echo esc_html($short_description); ?></p>
            <?php endif; ?>
            <div class="op-meta-line">
                <?php if ($duration) : ?><span><?php echo esc_html($duration); ?></span><?php endif; ?>
                <?php if ($departure) : ?><span><?php echo esc_html($departure); ?></span><?php endif; ?>
                <?php if ($meeting_point) : ?><span><?php echo esc_html($meeting_point); ?></span><?php endif; ?>
            </div>
            <div class="woocommerce-product-gallery">
                <?php if ($product->get_image_id()) : ?>
                    <?php echo $product->get_image('large'); ?>
                <?php else : ?>
                    <div class="op-summary-panel">
                        <p class="op-kicker"><?php esc_html_e('Fallback Visual', 'op-travel-shop'); ?></p>
                        <p><?php esc_html_e('Tour nÃ y chÆ°a cÃ³ media. Theme giá»¯ pháº§n gallery á»Ÿ tráº¡ng thÃ¡i premium fallback Ä‘á»ƒ seeder khÃ´ng phá»¥ thuá»™c asset bÃªn ngoÃ i.', 'op-travel-shop'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (! empty($gallery_ids)) : ?>
                <div class="op-eyebrow-list" style="margin-top:18px;">
                    <?php foreach ($gallery_ids as $attachment_id) : ?>
                        <span class="op-eyebrow"><?php echo esc_html(sprintf(__('Gallery ID %d', 'op-travel-shop'), $attachment_id)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($available_departure_dates) || $available_departure_dates_raw !== '') : ?>
                <div class="op-summary-panel" style="margin-top:24px;">
                    <p class="op-kicker"><?php esc_html_e('Lá»‹ch khá»Ÿi hÃ nh', 'op-travel-shop'); ?></p>
                    <?php if (! empty($available_departure_dates)) : ?>
                        <div class="op-eyebrow-list">
                            <?php foreach ($available_departure_dates as $departure_date) : ?>
                                <span class="op-eyebrow"><?php echo esc_html(op_travel_format_departure_date($departure_date)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e('Hiá»‡n chÆ°a cÃ³ lá»‹ch khá»Ÿi hÃ nh. Vui lÃ²ng liÃªn há»‡ Ä‘á»ƒ Ä‘Æ°á»£c xÃ¡c nháº­n slot.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($highlights) || $highlights_raw !== '') : ?>
                <div class="op-summary-panel" style="margin-top:24px;">
                    <p class="op-kicker"><?php esc_html_e('Äiá»ƒm nháº¥n hÃ nh trÃ¬nh', 'op-travel-shop'); ?></p>
                    <ul>
                        <?php foreach ($highlights as $highlight) : ?>
                            <li><?php echo esc_html($highlight); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>

        <aside class="op-booking-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Booking Panel', 'op-travel-shop'); ?></p>
            <p><?php esc_html_e('Single product Ä‘Æ°á»£c tá»• chá»©c láº¡i nhÆ° trang Ä‘áº·t tour: chá»n lá»‹ch khá»Ÿi hÃ nh, sá»‘ lÆ°á»£ng khÃ¡ch vÃ  chá»‘t giá»¯ chá»— trÆ°á»›c khi Ä‘i tiáº¿p sang checkout.', 'op-travel-shop'); ?></p>
            <div class="summary entry-summary">
                <?php woocommerce_template_single_price(); ?>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </aside>
    </div>

    <section class="op-checkout-grid" style="margin-top:32px;">
        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Lá»‹ch trÃ¬nh', 'op-travel-shop'); ?></p>
            <?php if (! empty($itinerary) || $itinerary_raw !== '') : ?>
                <ol>
                    <?php foreach ($itinerary as $step) : ?>
                        <li><?php echo esc_html($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <p><?php esc_html_e('Äang cáº­p nháº­t lá»‹ch trÃ¬nh chi tiáº¿t cho tour nÃ y.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>

        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('GiÃ¡ bao gá»“m', 'op-travel-shop'); ?></p>
            <?php if (! empty($includes) || $includes_raw !== '') : ?>
                <ul>
                    <?php foreach ($includes as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('Äang cáº­p nháº­t danh sÃ¡ch bao gá»“m.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>

        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('KhÃ´ng bao gá»“m', 'op-travel-shop'); ?></p>
            <?php if (! empty($excludes) || $excludes_raw !== '') : ?>
                <ul>
                    <?php foreach ($excludes as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('Äang cáº­p nháº­t danh sÃ¡ch khÃ´ng bao gá»“m.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>
    </section>
</main>
<?php
get_footer('shop');
