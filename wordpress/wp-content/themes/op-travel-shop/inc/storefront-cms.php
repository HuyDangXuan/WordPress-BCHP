<?php

if (! defined('ABSPATH')) {
    exit;
}

function op_travel_theme_render_storefront_document($payload, $context = [])
{
    $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

    foreach ($sections as $section) {
        if (empty($section['enabled'])) {
            continue;
        }

        op_travel_theme_render_storefront_section($section, $context, $payload);
    }

    return true;
}

function op_travel_theme_render_storefront_section($section, $context = [], $payload = [])
{
    $type = (string) ($section['type'] ?? '');

    switch ($type) {
        case 'hero':
            op_travel_theme_render_storefront_hero($section, $context);
            return;

        case 'rich_text':
        case 'media_text':
        case 'stats':
        case 'faq':
            op_travel_theme_render_storefront_content_section($section, $context);
            return;

        case 'cta_band':
            op_travel_theme_render_storefront_cta_band($section, $context);
            return;

        case 'featured_tours':
            op_travel_theme_render_storefront_featured_tours($section, $context, $payload);
            return;

        case 'taxonomy_grid':
            op_travel_theme_render_storefront_taxonomy_grid($section, $context);
            return;

        case 'testimonial_list':
        case 'promotion_list':
            op_travel_theme_render_storefront_post_list($section, $context);
            return;

        case 'tour_highlights':
            op_travel_theme_render_storefront_tour_highlights($section, $context);
            return;

        case 'tour_itinerary':
            op_travel_theme_render_storefront_tour_itinerary($section, $context);
            return;

        case 'tour_includes_excludes':
            op_travel_theme_render_storefront_tour_includes_excludes($section, $context);
            return;

        case 'tour_booking_panel':
            op_travel_theme_render_storefront_tour_booking_panel($section, $context);
            return;
    }
}

function op_travel_theme_render_storefront_hero($section, $context = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    ?>
    <section class="op-hero">
        <div class="op-hero__inner">
            <div data-reveal>
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h1 class="op-hero__headline"><?php echo esc_html($content['title']); ?></h1>
                <?php endif; ?>
                <?php if ($content['body'] !== '') : ?>
                    <div class="op-hero__body">
                        <?php op_travel_theme_render_storefront_body($content['body'], $content['body_is_html']); ?>
                    </div>
                <?php endif; ?>
                <?php op_travel_theme_render_storefront_buttons($content); ?>
            </div>
            <?php if ($content['aside'] !== '') : ?>
                <aside class="op-hero__aside" data-reveal>
                    <div class="op-hero__badge">
                        <p><?php echo esc_html($content['aside']); ?></p>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_content_section($section, $context = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-split-copy">
                <div class="op-section-heading">
                    <?php if ($content['eyebrow'] !== '') : ?>
                        <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                    <?php endif; ?>
                    <?php if ($content['title'] !== '') : ?>
                        <h2><?php echo esc_html($content['title']); ?></h2>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($content['body'] !== '') : ?>
                        <?php op_travel_theme_render_storefront_body($content['body'], $content['body_is_html']); ?>
                    <?php endif; ?>
                    <?php op_travel_theme_render_storefront_buttons($content); ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_cta_band($section, $context = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-summary-panel" data-reveal>
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
                <?php if ($content['body'] !== '') : ?>
                    <?php op_travel_theme_render_storefront_body($content['body'], $content['body_is_html']); ?>
                <?php endif; ?>
                <?php op_travel_theme_render_storefront_buttons($content); ?>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_featured_tours($section, $context = [], $payload = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    $mode = (string) (($section['bindings']['mode'] ?? 'manual'));
    $itemCount = max(1, absint($section['settings']['item_count'] ?? 4));
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-section-heading">
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
                <?php if ($content['body'] !== '') : ?>
                    <?php op_travel_theme_render_storefront_body($content['body'], $content['body_is_html']); ?>
                <?php endif; ?>
            </div>

            <?php if (($payload['route_key'] ?? '') === 'shop_archive' && $mode === 'query') : ?>
                <?php op_travel_theme_render_storefront_archive_query($context); ?>
            <?php else : ?>
                <div class="op-tour-grid">
                    <?php
                    $query = new WP_Query([
                        'post_type' => 'product',
                        'posts_per_page' => $itemCount,
                    ]);

                    if ($query->have_posts()) :
                        while ($query->have_posts()) :
                            $query->the_post();
                            wc_get_template_part('content', 'product');
                        endwhile;
                        wp_reset_postdata();
                    else :
                        ?>
                        <article class="op-tour-card">
                            <div class="op-tour-card__content">
                                <p class="op-kicker"><?php esc_html_e('No tours yet', 'op-travel-shop'); ?></p>
                                <h3><?php esc_html_e('Add products to see this storefront section.', 'op-travel-shop'); ?></h3>
                            </div>
                        </article>
                        <?php
                    endif;
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_taxonomy_grid($section, $context = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    $taxonomy = (string) ($section['bindings']['taxonomy'] ?? 'destination');
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'number' => max(1, absint($section['settings']['item_count'] ?? 4)),
    ]);
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-section-heading">
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
            </div>
            <div class="op-discovery-grid">
                <section class="op-discovery-panel" data-reveal>
                    <div class="op-eyebrow-list">
                        <?php if (! empty($terms) && ! is_wp_error($terms)) : ?>
                            <?php foreach ($terms as $term) : ?>
                                <a class="op-eyebrow" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p><?php esc_html_e('No taxonomy terms available yet.', 'op-travel-shop'); ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_post_list($section, $context = [])
{
    $content = op_travel_theme_resolve_section_content($section, $context);
    $postType = $section['type'] === 'promotion_list' ? 'promotion' : 'testimonial';
    $posts = get_posts([
        'post_type' => $postType,
        'numberposts' => max(1, absint($section['settings']['item_count'] ?? 3)),
        'post_status' => 'publish',
    ]);
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-section-heading">
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
            </div>
            <div class="op-discovery-grid">
                <?php if (! empty($posts)) : ?>
                    <?php foreach ($posts as $entry) : ?>
                        <article class="op-discovery-panel" data-reveal>
                            <p class="op-kicker"><?php echo esc_html(get_the_date('', $entry)); ?></p>
                            <h3><?php echo esc_html(get_the_title($entry)); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags((string) $entry->post_content), 30)); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <article class="op-discovery-panel" data-reveal>
                        <p><?php esc_html_e('No supporting content has been published yet.', 'op-travel-shop'); ?></p>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_tour_highlights($section, $context = [])
{
    $product = op_travel_theme_get_storefront_product($context);
    $highlights = $product ? op_travel_get_multiline_meta_values($product->get_id(), '_tour_highlights') : [];
    $content = op_travel_theme_resolve_section_content($section, $context);

    if (empty($highlights)) {
        return;
    }
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-summary-panel">
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php else : ?>
                    <p class="op-kicker"><?php esc_html_e('Điểm nhấn hành trình', 'op-travel-shop'); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
                <ul>
                    <?php foreach ($highlights as $highlight) : ?>
                        <li><?php echo esc_html($highlight); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_tour_itinerary($section, $context = [])
{
    $product = op_travel_theme_get_storefront_product($context);
    $itinerary = $product ? op_travel_get_multiline_meta_values($product->get_id(), '_tour_itinerary') : [];
    $content = op_travel_theme_resolve_section_content($section, $context);

    if (empty($itinerary)) {
        return;
    }
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-summary-panel">
                <?php if ($content['eyebrow'] !== '') : ?>
                    <p class="op-kicker"><?php echo esc_html($content['eyebrow']); ?></p>
                <?php else : ?>
                    <p class="op-kicker"><?php esc_html_e('Lịch trình', 'op-travel-shop'); ?></p>
                <?php endif; ?>
                <?php if ($content['title'] !== '') : ?>
                    <h2><?php echo esc_html($content['title']); ?></h2>
                <?php endif; ?>
                <ol>
                    <?php foreach ($itinerary as $step) : ?>
                        <li><?php echo esc_html($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_tour_includes_excludes($section, $context = [])
{
    $product = op_travel_theme_get_storefront_product($context);

    if (! $product) {
        return;
    }

    $includes = op_travel_get_multiline_meta_values($product->get_id(), '_tour_includes');
    $excludes = op_travel_get_multiline_meta_values($product->get_id(), '_tour_excludes');

    if (empty($includes) && empty($excludes)) {
        return;
    }
    ?>
    <section class="op-section">
        <div class="op-shell">
            <div class="op-discovery-grid">
                <?php if (! empty($includes)) : ?>
                    <section class="op-discovery-panel" data-reveal>
                        <p class="op-kicker"><?php esc_html_e('Giá bao gồm', 'op-travel-shop'); ?></p>
                        <ul>
                            <?php foreach ($includes as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
                <?php if (! empty($excludes)) : ?>
                    <section class="op-discovery-panel" data-reveal>
                        <p class="op-kicker"><?php esc_html_e('Không bao gồm', 'op-travel-shop'); ?></p>
                        <ul>
                            <?php foreach ($excludes as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_tour_booking_panel($section, $context = [])
{
    $product = op_travel_theme_get_storefront_product($context);

    if (! $product) {
        return;
    }
    ?>
    <section class="op-section">
        <div class="op-shell">
            <aside class="op-booking-panel" data-reveal>
                <p class="op-kicker"><?php esc_html_e('Booking Panel', 'op-travel-shop'); ?></p>
                <p><?php esc_html_e('Chọn lịch khởi hành, số lượng khách và chốt giữ chỗ trước khi đi tiếp sang checkout.', 'op-travel-shop'); ?></p>
                <div class="summary entry-summary">
                    <?php woocommerce_template_single_price(); ?>
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>
            </aside>
        </div>
    </section>
    <?php
}

function op_travel_theme_render_storefront_archive_query($context = [])
{
    $destinationTerms = $context['destination_terms'] ?? get_terms([
        'taxonomy' => 'destination',
        'hide_empty' => false,
    ]);
    $styleTerms = $context['style_terms'] ?? get_terms([
        'taxonomy' => 'tour_style',
        'hide_empty' => false,
    ]);
    $selectedSearch = (string) ($context['selected_search'] ?? '');
    $selectedDestination = (string) ($context['selected_destination'] ?? '');
    $selectedStyle = (string) ($context['selected_style'] ?? '');
    ?>
    <form class="op-filter-shell" method="get" aria-label="<?php esc_attr_e('Lọc tour', 'op-travel-shop'); ?>" data-op-loading-form data-op-skeleton-target=".op-tour-grid">
        <div class="op-filter-shell__field">
            <label for="op-filter-search"><?php esc_html_e('Tìm tour', 'op-travel-shop'); ?></label>
            <input
                id="op-filter-search"
                type="search"
                name="tour_search"
                value="<?php echo esc_attr($selectedSearch); ?>"
                placeholder="<?php esc_attr_e('Tên tour, mã tour, mô tả hoặc điểm đến', 'op-travel-shop'); ?>"
            >
        </div>
        <div class="op-filter-shell__field">
            <label for="op-filter-destination"><?php esc_html_e('Điểm đến', 'op-travel-shop'); ?></label>
            <select id="op-filter-destination" name="destination">
                <option value=""><?php esc_html_e('Tất cả điểm đến', 'op-travel-shop'); ?></option>
                <?php foreach ($destinationTerms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selectedDestination, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="op-filter-shell__field">
            <label for="op-filter-style"><?php esc_html_e('Phong cách tour', 'op-travel-shop'); ?></label>
            <select id="op-filter-style" name="tour_style">
                <option value=""><?php esc_html_e('Tất cả phong cách', 'op-travel-shop'); ?></option>
                <?php foreach ($styleTerms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selectedStyle, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
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
        <?php op_travel_theme_render_archive_pagination($context); ?>
    <?php else : ?>
        <section class="op-summary-panel">
            <p class="op-kicker"><?php esc_html_e('Không có kết quả', 'op-travel-shop'); ?></p>
            <h2><?php esc_html_e('Chưa có tour khớp shortlist hiện tại.', 'op-travel-shop'); ?></h2>
            <p><?php esc_html_e('Thử giảm bớt bộ lọc destination hoặc tour style để mở rộng shortlist.', 'op-travel-shop'); ?></p>
        </section>
    <?php endif; ?>
    <?php
}

function op_travel_theme_render_archive_pagination($context = [])
{
    $selectedSearch = (string) ($context['selected_search'] ?? '');
    $selectedDestination = (string) ($context['selected_destination'] ?? '');
    $selectedStyle = (string) ($context['selected_style'] ?? '');
    $totalPages = isset($GLOBALS['wp_query']->max_num_pages) ? max(1, absint($GLOBALS['wp_query']->max_num_pages)) : 1;
    $currentPage = max(1, absint(get_query_var('paged') ?: 1));
    $paginationArgs = [];

    if ($selectedSearch !== '') {
        $paginationArgs['tour_search'] = $selectedSearch;
    }

    if ($selectedDestination !== '') {
        $paginationArgs['destination'] = $selectedDestination;
    }

    if ($selectedStyle !== '') {
        $paginationArgs['tour_style'] = $selectedStyle;
    }

    $paginationLinks = $totalPages > 1 ? paginate_links([
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format' => '',
        'current' => $currentPage,
        'total' => $totalPages,
        'type' => 'array',
        'add_args' => $paginationArgs,
        'prev_text' => __('Trước', 'op-travel-shop'),
        'next_text' => __('Tiếp', 'op-travel-shop'),
    ]) : [];

    if (empty($paginationLinks)) {
        return;
    }
    ?>
    <nav class="op-tour-pagination" aria-label="<?php esc_attr_e('Phân trang tour', 'op-travel-shop'); ?>">
        <p class="op-tour-pagination__summary">
            <?php echo esc_html(sprintf(__('Trang %1$d / %2$d', 'op-travel-shop'), $currentPage, $totalPages)); ?>
        </p>
        <div class="op-tour-pagination__links">
            <?php foreach ($paginationLinks as $link) : ?>
                <?php echo wp_kses_post(str_replace('<a ', '<a data-op-loading-link data-op-skeleton-target=".op-tour-grid" ', $link)); ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php
}

function op_travel_theme_resolve_section_content($section, $context = [])
{
    $content = is_array($section['content'] ?? null) ? $section['content'] : [];
    $bindings = is_array($section['bindings'] ?? null) ? $section['bindings'] : [];
    $mode = (string) ($bindings['mode'] ?? 'manual');

    $resolved = [
        'eyebrow' => (string) ($content['eyebrow'] ?? ''),
        'title' => (string) ($content['title'] ?? ''),
        'body' => (string) ($content['body'] ?? ''),
        'body_is_html' => false,
        'button_label' => (string) ($content['button_label'] ?? ''),
        'button_url' => (string) ($content['button_url'] ?? ''),
        'secondary_label' => (string) ($content['secondary_label'] ?? ''),
        'secondary_url' => (string) ($content['secondary_url'] ?? ''),
        'aside' => (string) ($content['aside'] ?? ''),
    ];

    if ($mode === 'current_product') {
        $product = op_travel_theme_get_storefront_product($context);

        if ($product) {
            if ($resolved['eyebrow'] === '') {
                $resolved['eyebrow'] = __('Tour hiện tại', 'op-travel-shop');
            }

            if ($resolved['title'] === '') {
                $resolved['title'] = $product->get_name();
            }

            if ($resolved['body'] === '') {
                $resolved['body'] = wp_strip_all_tags($product->get_short_description() ?: get_the_excerpt($product->get_id()));
            }
        }
    }

    if ($mode === 'current_page') {
        $pageId = op_travel_theme_get_storefront_page_id($context);

        if ($pageId > 0) {
            if ($resolved['eyebrow'] === '') {
                $resolved['eyebrow'] = __('Page content', 'op-travel-shop');
            }

            if ($resolved['title'] === '') {
                $resolved['title'] = get_the_title($pageId);
            }

            if ($resolved['body'] === '') {
                $resolved['body'] = apply_filters('the_content', (string) get_post_field('post_content', $pageId));
                $resolved['body_is_html'] = true;
            }
        }
    }

    return $resolved;
}

function op_travel_theme_render_storefront_buttons($content)
{
    $hasPrimary = $content['button_label'] !== '' && $content['button_url'] !== '';
    $hasSecondary = $content['secondary_label'] !== '' && $content['secondary_url'] !== '';

    if (! $hasPrimary && ! $hasSecondary) {
        return;
    }
    ?>
    <div class="op-hero__actions">
        <?php if ($hasPrimary) : ?>
            <a class="op-button" href="<?php echo esc_url($content['button_url']); ?>"><?php echo esc_html($content['button_label']); ?></a>
        <?php endif; ?>
        <?php if ($hasSecondary) : ?>
            <a class="op-button op-button--ghost" href="<?php echo esc_url($content['secondary_url']); ?>"><?php echo esc_html($content['secondary_label']); ?></a>
        <?php endif; ?>
    </div>
    <?php
}

function op_travel_theme_render_storefront_body($body, $isHtml = false)
{
    if (! $isHtml) {
        echo '<p>' . esc_html($body) . '</p>';
        return;
    }

    echo wp_kses_post($body);
}

function op_travel_theme_get_storefront_product($context = [])
{
    $product = $context['product'] ?? null;

    if (class_exists('WC_Product') && $product instanceof WC_Product) {
        return $product;
    }

    if (function_exists('wc_get_product') && is_singular('product')) {
        return wc_get_product(get_queried_object_id());
    }

    return null;
}

function op_travel_theme_get_storefront_page_id($context = [])
{
    $pageId = isset($context['page_id']) ? absint($context['page_id']) : 0;

    if ($pageId > 0) {
        return $pageId;
    }

    if (is_page()) {
        return get_queried_object_id();
    }

    return 0;
}
