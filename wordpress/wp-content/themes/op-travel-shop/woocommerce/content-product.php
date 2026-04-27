<?php

defined('ABSPATH') || exit;

global $product;

if (! $product || ! $product->is_visible()) {
    return;
}

$destination = get_the_terms($product->get_id(), 'destination');
$styles = get_the_terms($product->get_id(), 'tour_style');
$duration = get_post_meta($product->get_id(), '_duration_text', true);
$departure = get_post_meta($product->get_id(), '_departure_city', true);
?>
<article <?php wc_product_class('op-tour-card', $product); ?> data-reveal>
    <a class="op-tour-card__media" href="<?php the_permalink(); ?>">
        <?php echo $product->get_image('large'); ?>
    </a>
    <div class="op-tour-card__content">
        <div class="op-eyebrow-list">
            <?php if ($destination && ! is_wp_error($destination)) : ?>
                <span class="op-eyebrow"><?php echo esc_html($destination[0]->name); ?></span>
            <?php endif; ?>
            <?php if ($styles && ! is_wp_error($styles)) : ?>
                <span class="op-eyebrow"><?php echo esc_html($styles[0]->name); ?></span>
            <?php endif; ?>
        </div>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="op-meta-line">
            <?php if ($duration) : ?><span><?php echo esc_html($duration); ?></span><?php endif; ?>
            <?php if ($departure) : ?><span><?php echo esc_html($departure); ?></span><?php endif; ?>
        </div>
        <p class="op-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
        <p><a class="op-button" href="<?php the_permalink(); ?>"><?php esc_html_e('Xem chi tiết tour', 'op-travel-shop'); ?></a></p>
    </div>
</article>
