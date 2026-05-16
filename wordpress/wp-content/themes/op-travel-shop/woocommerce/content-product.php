<?php

defined('ABSPATH') || exit;

global $product;

if (! $product || ! $product->is_visible()) {
    return;
}

$product_id = $product->get_id();
$destination = get_the_terms($product_id, 'destination');
$styles = get_the_terms($product_id, 'tour_style');
$tour_code = get_post_meta($product_id, '_tour_code', true);
$duration = get_post_meta($product_id, '_duration_text', true);
$departure = get_post_meta($product_id, '_departure_city', true);
$available_departure_dates = op_travel_get_multiline_meta_values($product_id, '_available_departure_dates');
$next_departure = ! empty($available_departure_dates) ? $available_departure_dates[0] : '';
$destination_name = ($destination && ! is_wp_error($destination)) ? $destination[0]->name : __('Điểm đến nổi bật', 'op-travel-shop');
$style_name = ($styles && ! is_wp_error($styles)) ? $styles[0]->name : __('Tour chọn lọc', 'op-travel-shop');
$tour_code_label = $tour_code ?: __('Mã tour đang cập nhật', 'op-travel-shop');
$duration_label = $duration ?: __('Lịch trình đang cập nhật', 'op-travel-shop');
$departure_label = $departure ?: __('Khởi hành linh hoạt', 'op-travel-shop');
$next_departure_label = $next_departure
    ? op_travel_format_departure_date($next_departure)
    : __('Liên hệ để chọn ngày phù hợp', 'op-travel-shop');
$price_html = $product->get_price_html();
?>
<article <?php wc_product_class('op-tour-card op-is-loading', $product); ?> data-reveal itemscope itemtype="https://schema.org/Product">
    <a class="op-tour-card__media op-skeleton-media op-is-loading" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
        <?php echo $product->get_image('large'); ?>
    </a>
    <div class="op-tour-card__content">
        <div class="op-eyebrow-list">
            <span class="op-eyebrow"><?php echo esc_html($destination_name); ?></span>
            <span class="op-eyebrow"><?php echo esc_html($style_name); ?></span>
        </div>
        <h3 itemprop="name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="op-tour-card__facts">
            <p class="op-tour-card__fact">
                <span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html($tour_code_label); ?></strong>
            </p>
            <p class="op-tour-card__fact">
                <span><?php esc_html_e('Thời lượng', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html($duration_label); ?></strong>
            </p>
            <p class="op-tour-card__fact">
                <span><?php esc_html_e('Khởi hành', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html($departure_label); ?></strong>
            </p>
        </div>
        <p class="op-tour-card__date">
            <?php if ($next_departure) : ?>
                <time datetime="<?php echo esc_attr($next_departure); ?>"><?php echo esc_html(sprintf(__('Gần nhất: %s', 'op-travel-shop'), $next_departure_label)); ?></time>
            <?php else : ?>
                <span><?php echo esc_html(sprintf(__('Gần nhất: %s', 'op-travel-shop'), $next_departure_label)); ?></span>
            <?php endif; ?>
        </p>
        <div class="op-tour-card__footer">
            <p class="op-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span itemprop="price" content="<?php echo esc_attr($product->get_price()); ?>"><?php echo $price_html ? wp_kses_post($price_html) : esc_html__('Liên hệ tư vấn', 'op-travel-shop'); ?></span>
                <meta itemprop="priceCurrency" content="<?php echo esc_attr(get_woocommerce_currency()); ?>">
            </p>
            <a class="op-tour-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e('Xem chi tiết', 'op-travel-shop'); ?></a>
        </div>
    </div>
</article>
