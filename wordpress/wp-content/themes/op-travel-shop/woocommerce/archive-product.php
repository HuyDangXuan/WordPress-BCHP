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
$selected_search = function_exists('op_travel_get_product_archive_search_term') ? op_travel_get_product_archive_search_term() : '';
$selected_destination = isset($_GET['destination']) ? sanitize_title(wp_unslash($_GET['destination'])) : '';
$selected_style = isset($_GET['tour_style']) ? sanitize_title(wp_unslash($_GET['tour_style'])) : '';
$selected_destination_term = $selected_destination ? get_term_by('slug', $selected_destination, 'destination') : null;
$selected_style_term = $selected_style ? get_term_by('slug', $selected_style, 'tour_style') : null;
$result_count = isset($GLOBALS['wp_query']->found_posts) ? absint($GLOBALS['wp_query']->found_posts) : 0;

if (function_exists('op_travel_storefront_render_route') && op_travel_storefront_render_route('shop_archive', [
    'destination_terms' => $destination_terms,
    'style_terms' => $style_terms,
    'selected_search' => $selected_search,
    'selected_destination' => $selected_destination,
    'selected_style' => $selected_style,
    'selected_destination_term' => $selected_destination_term,
    'selected_style_term' => $selected_style_term,
    'result_count' => $result_count,
])) {
    get_footer('shop');
    return;
}
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(1);
    ?>

    <header class="op-section-heading op-archive-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 1 · Chọn tour', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Chọn tour theo điểm đến và phong cách phù hợp.', 'op-travel-shop'); ?></h1>
        <p class="op-archive-heading__summary">
            <?php if ($selected_search !== '' || $selected_destination_term || $selected_style_term) : ?>
                <?php
                $summary_scope = [];

                if ($selected_search !== '') {
                    $summary_scope[] = sprintf(__('từ khóa "%s"', 'op-travel-shop'), $selected_search);
                }

                $summary_scope[] = $selected_destination_term ? $selected_destination_term->name : __('tất cả điểm đến', 'op-travel-shop');
                $summary_scope[] = $selected_style_term ? $selected_style_term->name : __('mọi phong cách tour', 'op-travel-shop');

                echo esc_html(sprintf(
                    __('Shortlist hiện có %1$d hành trình khớp %2$s.', 'op-travel-shop'),
                    $result_count,
                    implode(' · ', $summary_scope)
                ));
                ?>
            <?php else : ?>
                <?php esc_html_e('Duyệt tour như một shortlist biên tập: sáng, gọn và ưu tiên những thông tin giúp bạn chốt nhanh hành trình phù hợp.', 'op-travel-shop'); ?>
            <?php endif; ?>
        </p>
    </header>

    <form class="op-filter-shell" method="get" aria-label="<?php esc_attr_e('Lọc tour', 'op-travel-shop'); ?>" data-op-loading-form data-op-skeleton-target=".op-tour-grid">
        <div class="op-filter-shell__field">
            <label for="op-filter-search"><?php esc_html_e('Tìm tour', 'op-travel-shop'); ?></label>
            <input
                id="op-filter-search"
                type="search"
                name="tour_search"
                value="<?php echo esc_attr($selected_search); ?>"
                placeholder="<?php esc_attr_e('Tên tour, mã tour, mô tả hoặc điểm đến', 'op-travel-shop'); ?>"
            >
        </div>
        <div class="op-filter-shell__field">
            <label for="op-filter-destination"><?php esc_html_e('Điểm đến', 'op-travel-shop'); ?></label>
            <select id="op-filter-destination" name="destination">
                <option value=""><?php esc_html_e('Tất cả điểm đến', 'op-travel-shop'); ?></option>
                <?php foreach ($destination_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_destination, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="op-filter-shell__field">
            <label for="op-filter-style"><?php esc_html_e('Phong cách tour', 'op-travel-shop'); ?></label>
            <select id="op-filter-style" name="tour_style">
                <option value=""><?php esc_html_e('Tất cả phong cách', 'op-travel-shop'); ?></option>
                <?php foreach ($style_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_style, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="op-filter-shell__actions">
            <button type="submit"><?php esc_html_e('Lọc shortlist', 'op-travel-shop'); ?></button>
        </div>
    </form>

    <?php if (woocommerce_product_loop()) : ?>
        <div class="op-tour-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
        </div>
        <?php
        $total_pages = isset($GLOBALS['wp_query']->max_num_pages) ? max(1, absint($GLOBALS['wp_query']->max_num_pages)) : 1;
        $current_page = max(1, absint(get_query_var('paged') ?: 1));
        $pagination_args = [];

        if ($selected_search !== '') {
            $pagination_args['tour_search'] = $selected_search;
        }

        if ($selected_destination) {
            $pagination_args['destination'] = $selected_destination;
        }

        if ($selected_style) {
            $pagination_args['tour_style'] = $selected_style;
        }

        $pagination_links = $total_pages > 1 ? paginate_links([
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '',
            'current' => $current_page,
            'total' => $total_pages,
            'type' => 'array',
            'add_args' => $pagination_args,
            'prev_text' => __('Trước', 'op-travel-shop'),
            'next_text' => __('Tiếp', 'op-travel-shop'),
        ]) : [];
        ?>
        <?php if (! empty($pagination_links)) : ?>
            <nav class="op-tour-pagination" aria-label="<?php esc_attr_e('Phân trang tour', 'op-travel-shop'); ?>">
                <p class="op-tour-pagination__summary">
                    <?php
                    echo esc_html(sprintf(
                        __('Trang %1$d / %2$d', 'op-travel-shop'),
                        $current_page,
                        $total_pages
                    ));
                    ?>
                </p>
                <div class="op-tour-pagination__links">
                    <?php foreach ($pagination_links as $link) : ?>
                        <?php echo wp_kses_post(str_replace('<a ', '<a data-op-loading-link data-op-skeleton-target=".op-tour-grid" ', $link)); ?>
                    <?php endforeach; ?>
                </div>
            </nav>
        <?php endif; ?>
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
