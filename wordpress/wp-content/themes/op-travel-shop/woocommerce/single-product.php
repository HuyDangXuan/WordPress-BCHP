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
                <span class="op-eyebrow"><?php esc_html_e('Bước 2', 'op-travel-shop'); ?></span>
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
                        <p><?php esc_html_e('Tour này chưa có media. Theme giữ phần gallery ở trạng thái premium fallback để seeder không phụ thuộc asset bên ngoài.', 'op-travel-shop'); ?></p>
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
                    <p class="op-kicker"><?php esc_html_e('Lịch khởi hành', 'op-travel-shop'); ?></p>
                    <?php if (! empty($available_departure_dates)) : ?>
                        <div class="op-eyebrow-list">
                            <?php foreach ($available_departure_dates as $departure_date) : ?>
                                <span class="op-eyebrow"><?php echo esc_html(op_travel_format_departure_date($departure_date)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e('Hiện chưa có lịch khởi hành. Vui lòng liên hệ để được xác nhận slot.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($highlights) || $highlights_raw !== '') : ?>
                <div class="op-summary-panel" style="margin-top:24px;">
                    <p class="op-kicker"><?php esc_html_e('Điểm nhấn hành trình', 'op-travel-shop'); ?></p>
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
            <p><?php esc_html_e('Single product được tổ chức lại như trang đặt tour: chọn lịch khởi hành, số lượng khách và chốt giữ chỗ trước khi đi tiếp sang checkout.', 'op-travel-shop'); ?></p>
            <div class="summary entry-summary">
                <?php woocommerce_template_single_price(); ?>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </aside>
    </div>

    <section class="op-checkout-grid" style="margin-top:32px;">
        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Lịch trình', 'op-travel-shop'); ?></p>
            <?php if (! empty($itinerary) || $itinerary_raw !== '') : ?>
                <ol>
                    <?php foreach ($itinerary as $step) : ?>
                        <li><?php echo esc_html($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <p><?php esc_html_e('Đang cập nhật lịch trình chi tiết cho tour này.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>

        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Giá bao gồm', 'op-travel-shop'); ?></p>
            <?php if (! empty($includes) || $includes_raw !== '') : ?>
                <ul>
                    <?php foreach ($includes as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('Đang cập nhật danh sách bao gồm.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>

        <article class="op-summary-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Không bao gồm', 'op-travel-shop'); ?></p>
            <?php if (! empty($excludes) || $excludes_raw !== '') : ?>
                <ul>
                    <?php foreach ($excludes as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('Đang cập nhật danh sách không bao gồm.', 'op-travel-shop'); ?></p>
            <?php endif; ?>
        </article>
    </section>
</main>
<?php
get_footer('shop');
