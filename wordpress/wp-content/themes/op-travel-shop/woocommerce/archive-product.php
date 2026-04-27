<?php

defined('ABSPATH') || exit;

get_header('shop');

$destination_terms = get_terms([
    'taxonomy' => 'destination',
    'hide_empty' => false,
]);
$style_terms = get_terms([
    'taxonomy' => 'tour_style',
    'hide_empty' => false,
]);
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 1', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Chọn tour theo điểm đến và phong cách phù hợp với lịch trình của bạn.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Archive tour không còn là product grid mặc định. Nó là shortlist hành trình, nơi taxonomy du lịch dẫn dắt quyết định đầu tiên của khách.', 'op-travel-shop'); ?></p>
    </header>

    <form class="op-filter-shell" method="get">
        <select name="destination">
            <option value=""><?php esc_html_e('Chọn điểm đến', 'op-travel-shop'); ?></option>
            <?php foreach ($destination_terms as $term) : ?>
                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected(isset($_GET['destination']) ? sanitize_title(wp_unslash($_GET['destination'])) : '', $term->slug); ?>><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tour_style">
            <option value=""><?php esc_html_e('Chọn phong cách tour', 'op-travel-shop'); ?></option>
            <?php foreach ($style_terms as $term) : ?>
                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected(isset($_GET['tour_style']) ? sanitize_title(wp_unslash($_GET['tour_style'])) : '', $term->slug); ?>><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit"><?php esc_html_e('Lọc shortlist', 'op-travel-shop'); ?></button>
    </form>

    <?php if (woocommerce_product_loop()) : ?>
        <div class="op-tour-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
        </div>
        <?php woocommerce_pagination(); ?>
    <?php else : ?>
        <?php do_action('woocommerce_no_products_found'); ?>
    <?php endif; ?>
</main>
<?php
get_footer('shop');
