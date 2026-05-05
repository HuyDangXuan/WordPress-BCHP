<?php

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', 'op_travel_theme_setup');
add_action('wp_enqueue_scripts', 'op_travel_enqueue_assets');
add_action('wp_head', 'op_travel_seo_meta', 1);
add_action('wp_head', 'op_travel_render_product_json_ld', 5);

function op_travel_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('Primary Menu', 'op-travel-shop'),
        'footer' => __('Footer Menu', 'op-travel-shop'),
    ]);
}

function op_travel_enqueue_assets()
{
    $theme_css_path = get_template_directory() . '/assets/css/theme.css';
    $theme_js_path = get_template_directory() . '/assets/js/theme.js';

    wp_enqueue_style(
        'op-travel-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'op-travel-theme',
        get_template_directory_uri() . '/assets/css/theme.css',
        ['op-travel-fonts'],
        file_exists($theme_css_path) ? filemtime($theme_css_path) : wp_get_theme()->get('Version')
    );

    wp_enqueue_script(
        'op-travel-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        file_exists($theme_js_path) ? filemtime($theme_js_path) : wp_get_theme()->get('Version'),
        true
    );
}

/**
 * Dynamic SEO meta: description, Open Graph, Twitter Cards.
 */
function op_travel_seo_meta()
{
    $description = '';

    if (is_front_page()) {
        $description = get_bloginfo('description');
    } elseif (is_singular('product') && function_exists('wc_get_product')) {
        $prod = wc_get_product(get_the_ID());
        if ($prod) {
            $description = wp_strip_all_tags($prod->get_short_description() ?: get_the_excerpt());
        }
    } elseif (is_singular()) {
        $description = wp_strip_all_tags(get_the_excerpt());
    } elseif (function_exists('is_shop') && (is_shop() || is_post_type_archive('product'))) {
        $description = __('Khám phá shortlist tour du lịch theo điểm đến và phong cách phù hợp với lịch trình của bạn.', 'op-travel-shop');
    }

    if ($description) {
        $description = wp_trim_words($description, 30, '…');
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    $og_title = wp_get_document_title();
    $og_desc  = $description ?: get_bloginfo('description');
    $og_url   = is_singular() ? get_permalink() : home_url(add_query_arg(null, null));
    $og_type  = is_singular() ? 'article' : 'website';
    $og_image = '';

    if (is_singular() && has_post_thumbnail()) {
        $og_image = get_the_post_thumbnail_url(null, 'large');
    }

    echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($og_desc) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:locale" content="vi_VN">' . "\n";

    if ($og_image) {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($og_desc) . '">' . "\n";

    if ($og_image) {
        echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
    }
}

/**
 * JSON-LD Product structured data for single product pages.
 */
function op_travel_render_product_json_ld()
{
    if (! is_singular('product') || ! function_exists('wc_get_product')) {
        return;
    }

    $prod = wc_get_product(get_the_ID());

    if (! $prod) {
        return;
    }

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $prod->get_name(),
        'description' => wp_strip_all_tags($prod->get_short_description() ?: get_the_excerpt()),
        'url'         => get_permalink(),
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => $prod->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability'  => $prod->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ],
    ];

    if ($prod->get_image_id()) {
        $schema['image'] = wp_get_attachment_url($prod->get_image_id());
    }

    $tour_code = get_post_meta($prod->get_id(), '_tour_code', true);

    if ($tour_code) {
        $schema['sku'] = $tour_code;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/**
 * Render a 4-step progress indicator for the booking journey.
 */
function op_travel_render_step_progress($current_step = 1)
{
    $steps = [
        1 => __('Chọn tour', 'op-travel-shop'),
        2 => __('Giữ chỗ', 'op-travel-shop'),
        3 => __('Thanh toán', 'op-travel-shop'),
        4 => __('Hoàn tất', 'op-travel-shop'),
    ];

    echo '<nav class="op-step-progress" aria-label="' . esc_attr__('Tiến trình đặt tour', 'op-travel-shop') . '">';
    echo '<ol>';

    foreach ($steps as $num => $label) {
        $class = 'op-step-progress__item';

        if ($num < $current_step) {
            $class .= ' is-completed';
        } elseif ($num === $current_step) {
            $class .= ' is-active';
        }

        $aria = $num === $current_step ? ' aria-current="step"' : '';

        echo '<li class="' . esc_attr($class) . '"' . $aria . '>';
        echo '<span class="op-step-progress__number">' . esc_html(sprintf('%02d', $num)) . '</span>';
        echo '<span class="op-step-progress__label">' . esc_html($label) . '</span>';
        echo '</li>';
    }

    echo '</ol>';
    echo '</nav>';
}

/**
 * Render breadcrumb navigation.
 */
function op_travel_render_breadcrumb($items = [])
{
    if (empty($items)) {
        return;
    }

    echo '<nav class="op-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'op-travel-shop') . '">';

    $last = count($items) - 1;

    foreach ($items as $i => $item) {
        if ($i === $last) {
            echo '<span aria-current="page">' . esc_html($item['label']) . '</span>';
        } else {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
            echo '<span class="op-breadcrumb__sep" aria-hidden="true">›</span>';
        }
    }

    echo '</nav>';
}
