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
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');

$has_itinerary = ! empty($itinerary) || $itinerary_raw !== '';
$has_includes  = ! empty($includes) || $includes_raw !== '';
$has_excludes  = ! empty($excludes) || $excludes_raw !== '';
$has_tabs      = $has_itinerary || $has_includes || $has_excludes;
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => get_the_title(), 'url' => ''],
    ]);
    ?>

    <?php do_action('woocommerce_before_single_product'); ?>

    <div class="op-detail-grid">
        <!-- Left column: Gallery + Meta -->
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

            <!-- Meta detail row with icons -->
            <div class="op-meta-detail">
                <?php if ($duration) : ?>
                    <span class="op-meta-detail__item">
                        <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                        <?php echo esc_html($duration); ?>
                    </span>
                <?php endif; ?>
                <?php if ($departure) : ?>
                    <span class="op-meta-detail__item">
                        <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                        <?php echo esc_html($departure); ?>
                    </span>
                <?php endif; ?>
                <?php if ($meeting_point) : ?>
                    <span class="op-meta-detail__item">
                        <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>
                        <?php echo esc_html($meeting_point); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Main product image -->
            <div class="woocommerce-product-gallery op-skeleton-media op-is-loading">
                <?php if ($product->get_image_id()) : ?>
                    <?php echo $product->get_image('large'); ?>
                <?php else : ?>
                    <div class="op-summary-panel">
                        <p class="op-kicker"><?php esc_html_e('Fallback Visual', 'op-travel-shop'); ?></p>
                        <p><?php esc_html_e('Tour này chưa có media. Theme giữ phần gallery ở trạng thái premium fallback.', 'op-travel-shop'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Gallery thumbnails: render actual images instead of IDs -->
            <?php if (! empty($gallery_ids)) : ?>
                <div class="op-gallery-grid">
                    <?php foreach ($gallery_ids as $attachment_id) : ?>
                        <?php echo wp_get_attachment_image($attachment_id, 'medium', false, ['loading' => 'lazy']); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Departure dates -->
            <?php if (! empty($available_departure_dates) || $available_departure_dates_raw !== '') : ?>
                <div class="op-summary-panel" style="margin-top:24px;">
                    <p class="op-kicker"><?php esc_html_e('Lịch khởi hành', 'op-travel-shop'); ?></p>
                    <?php if (! empty($available_departure_dates)) : ?>
                        <div class="op-eyebrow-list">
                            <?php foreach ($available_departure_dates as $departure_date) : ?>
                                <time class="op-eyebrow" datetime="<?php echo esc_attr($departure_date); ?>"><?php echo esc_html(op_travel_format_departure_date($departure_date)); ?></time>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e('Hiện chưa có lịch khởi hành. Vui lòng liên hệ để được xác nhận slot.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Highlights -->
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

        <!-- Right column: Booking panel -->
        <aside class="op-booking-panel" data-reveal>
            <p class="op-kicker"><?php esc_html_e('Booking Panel', 'op-travel-shop'); ?></p>
            <p><?php esc_html_e('Chọn lịch khởi hành, số lượng khách và chốt giữ chỗ trước khi đi tiếp sang checkout.', 'op-travel-shop'); ?></p>
            <div class="summary entry-summary">
                <?php woocommerce_template_single_price(); ?>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </aside>
    </div>

    <!-- Tabbed content: Itinerary / Includes / Excludes -->
    <?php if ($has_tabs) : ?>
        <div class="op-detail-tabs" data-reveal>
            <div class="op-detail-tabs__nav" role="tablist">
                <?php if ($has_itinerary) : ?>
                    <button class="op-detail-tabs__btn is-active" data-tab="itinerary" role="tab" aria-selected="true"><?php esc_html_e('Lịch trình', 'op-travel-shop'); ?></button>
                <?php endif; ?>
                <?php if ($has_includes) : ?>
                    <button class="op-detail-tabs__btn <?php echo ! $has_itinerary ? 'is-active' : ''; ?>" data-tab="includes" role="tab"><?php esc_html_e('Giá bao gồm', 'op-travel-shop'); ?></button>
                <?php endif; ?>
                <?php if ($has_excludes) : ?>
                    <button class="op-detail-tabs__btn" data-tab="excludes" role="tab"><?php esc_html_e('Không bao gồm', 'op-travel-shop'); ?></button>
                <?php endif; ?>
            </div>

            <?php if ($has_itinerary) : ?>
                <div class="op-detail-tabs__panel is-active" data-tab-panel="itinerary" role="tabpanel">
                    <?php if (! empty($itinerary)) : ?>
                        <ol>
                            <?php foreach ($itinerary as $step) : ?>
                                <li><?php echo esc_html($step); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else : ?>
                        <p><?php esc_html_e('Đang cập nhật lịch trình chi tiết cho tour này.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_includes) : ?>
                <div class="op-detail-tabs__panel <?php echo ! $has_itinerary ? 'is-active' : ''; ?>" data-tab-panel="includes" role="tabpanel">
                    <?php if (! empty($includes)) : ?>
                        <ul>
                            <?php foreach ($includes as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php esc_html_e('Đang cập nhật danh sách bao gồm.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_excludes) : ?>
                <div class="op-detail-tabs__panel" data-tab-panel="excludes" role="tabpanel">
                    <?php if (! empty($excludes)) : ?>
                        <ul>
                            <?php foreach ($excludes as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php esc_html_e('Đang cập nhật danh sách không bao gồm.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
<?php
get_footer('shop');
