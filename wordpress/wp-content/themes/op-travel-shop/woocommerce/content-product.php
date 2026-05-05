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
<article <?php wc_product_class('op-tour-card', $product); ?> data-reveal itemscope itemtype="https://schema.org/Product">
    <a class="op-tour-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
        <?php echo $product->get_image('large'); ?>
    </a>
    <div class="op-tour-card__content">
        <div class="op-eyebrow-list">
            <span class="op-eyebrow"><?php echo esc_html($destination_name); ?></span>
            <span class="op-eyebrow"><?php echo esc_html($style_name); ?></span>
        </div>
        <h3 itemprop="name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="op-meta-detail">
            <span class="op-meta-detail__item">
                <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg></span>
                <?php echo esc_html($tour_code_label); ?>
            </span>
            <span class="op-meta-detail__item">
                <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                <?php echo esc_html($duration_label); ?>
            </span>
            <span class="op-meta-detail__item">
                <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                <?php echo esc_html($departure_label); ?>
            </span>
        </div>
        <p class="op-meta-detail__item" style="margin-top:8px;">
            <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
            <?php if ($next_departure) : ?>
                <time datetime="<?php echo esc_attr($next_departure); ?>"><?php echo esc_html(sprintf(__('Gần nhất: %s', 'op-travel-shop'), $next_departure_label)); ?></time>
            <?php else : ?>
                <span><?php echo esc_html(sprintf(__('Gần nhất: %s', 'op-travel-shop'), $next_departure_label)); ?></span>
            <?php endif; ?>
        </p>
        <p class="op-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <span itemprop="price" content="<?php echo esc_attr($product->get_price()); ?>"><?php echo $price_html ? wp_kses_post($price_html) : esc_html__('Liên hệ tư vấn', 'op-travel-shop'); ?></span>
            <meta itemprop="priceCurrency" content="<?php echo esc_attr(get_woocommerce_currency()); ?>">
        </p>
        <p><a class="op-button" href="<?php the_permalink(); ?>"><?php esc_html_e('Xem chi tiết tour', 'op-travel-shop'); ?></a></p>
    </div>
</article>
