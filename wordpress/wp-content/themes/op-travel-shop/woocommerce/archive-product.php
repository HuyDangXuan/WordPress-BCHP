<?php

defined('ABSPATH') || exit;

get_header('shop');

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$destination_terms = get_terms([
    'taxonomy' => 'destination',
    'hide_empty' => false,
]);
$style_terms = get_terms([
    'taxonomy' => 'tour_style',
    'hide_empty' => false,
]);
$selected_destination = isset($_GET['destination']) ? sanitize_title(wp_unslash($_GET['destination'])) : '';
$selected_style = isset($_GET['tour_style']) ? sanitize_title(wp_unslash($_GET['tour_style'])) : '';
$selected_destination_term = $selected_destination ? get_term_by('slug', $selected_destination, 'destination') : null;
$selected_style_term = $selected_style ? get_term_by('slug', $selected_style, 'tour_style') : null;
$result_count = isset($GLOBALS['wp_query']->found_posts) ? absint($GLOBALS['wp_query']->found_posts) : 0;
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(1);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 1 · Chọn tour', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Chọn tour theo điểm đến và phong cách phù hợp.', 'op-travel-shop'); ?></h1>
        <?php if ($selected_destination_term || $selected_style_term) : ?>
            <p>
                <?php
                echo esc_html(sprintf(
                    __('Shortlist hiện có %1$d hành trình khớp bộ lọc %2$s %3$s.', 'op-travel-shop'),
                    $result_count,
                    $selected_destination_term ? $selected_destination_term->name : __('tất cả điểm đến', 'op-travel-shop'),
                    $selected_style_term ? $selected_style_term->name : __('và mọi phong cách tour', 'op-travel-shop')
                ));
                ?>
            </p>
        <?php else : ?>
            <p><?php esc_html_e('Shortlist hành trình được dẫn dắt bởi taxonomy du lịch, giúp bạn chọn tour theo điểm đến và phong cách riêng.', 'op-travel-shop'); ?></p>
        <?php endif; ?>
    </header>

    <form class="op-filter-shell" method="get" aria-label="<?php esc_attr_e('Lọc tour', 'op-travel-shop'); ?>">
        <div>
            <label for="op-filter-destination"><?php esc_html_e('Điểm đến', 'op-travel-shop'); ?></label>
            <select id="op-filter-destination" name="destination">
                <option value=""><?php esc_html_e('Tất cả điểm đến', 'op-travel-shop'); ?></option>
                <?php foreach ($destination_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_destination, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="op-filter-style"><?php esc_html_e('Phong cách tour', 'op-travel-shop'); ?></label>
            <select id="op-filter-style" name="tour_style">
                <option value=""><?php esc_html_e('Tất cả phong cách', 'op-travel-shop'); ?></option>
                <?php foreach ($style_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_style, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;align-items:flex-end;">
            <button type="submit"><?php esc_html_e('Lọc shortlist', 'op-travel-shop'); ?></button>
        </div>
    </form>

    <?php if (woocommerce_product_loop()) : ?>
        <div class="op-tour-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
        </div>
        <?php woocommerce_pagination(); ?>
    <?php else : ?>
        <section class="op-summary-panel">
            <p class="op-kicker"><?php esc_html_e('Không có kết quả', 'op-travel-shop'); ?></p>
            <h2><?php esc_html_e('Chưa có tour khớp shortlist hiện tại.', 'op-travel-shop'); ?></h2>
            <p><?php esc_html_e('Thử giảm bớt bộ lọc destination hoặc tour style để mở rộng shortlist.', 'op-travel-shop'); ?></p>
        </section>
    <?php endif; ?>
</main>
<?php
get_footer('shop');
