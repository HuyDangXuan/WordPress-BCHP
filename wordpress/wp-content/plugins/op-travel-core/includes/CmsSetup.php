<?php

namespace OPTravelCore;

final class CmsSetup
{
    public static function boot()
    {
        add_action('init', [__CLASS__, 'register_taxonomies']);
        add_action('init', [__CLASS__, 'register_post_types']);
        add_action('init', [__CLASS__, 'register_shortcodes']);
        add_filter('register_post_type_args', [__CLASS__, 'filter_product_post_type_args'], 10, 2);
    }

    public static function activate()
    {
        self::register_taxonomies();
        self::register_post_types();
        self::seed_pages();
        self::configure_woocommerce_pages();
        flush_rewrite_rules();
    }

    public static function register_taxonomies()
    {
        register_taxonomy('destination', ['product'], [
            'label' => __('Destinations', 'op-travel-core'),
            'public' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => ['slug' => 'destination'],
        ]);

        register_taxonomy('tour_style', ['product'], [
            'label' => __('Tour Styles', 'op-travel-core'),
            'public' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => ['slug' => 'tour-style'],
        ]);
    }

    public static function register_post_types()
    {
        register_post_type('promotion', [
            'label' => __('Promotions', 'op-travel-core'),
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ]);

        register_post_type('testimonial', [
            'label' => __('Testimonials', 'op-travel-core'),
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ]);
    }

    public static function register_shortcodes()
    {
        add_shortcode('op_travel_contact_form', [__CLASS__, 'render_contact_form']);
    }

    public static function render_contact_form()
    {
        return '<form class="op-travel-contact-form"><div class="op-field"><label>Họ và tên</label><input type="text" name="full_name" /></div><div class="op-field"><label>Email</label><input type="email" name="email" /></div><div class="op-field"><label>Số điện thoại</label><input type="text" name="phone" /></div><div class="op-field"><label>Nội dung</label><textarea name="message" rows="5"></textarea></div><button type="submit">Gửi yêu cầu tư vấn</button></form>';
    }

    public static function filter_product_post_type_args($args, $post_type)
    {
        if ($post_type !== 'product') {
            return $args;
        }

        $args['rewrite'] = [
            'slug' => 'tours',
            'with_front' => false,
        ];

        return $args;
    }

    private static function seed_pages()
    {
        $pages = [
            'trang-chu' => 'Trang chủ',
            'blog' => 'Blog',
            'lien-he' => 'Liên hệ',
            'tours' => 'Tours',
            'gio-hang' => 'Giỏ hàng',
            'thanh-toan' => 'Thanh toán',
            'tai-khoan' => 'Tài khoản',
        ];

        foreach ($pages as $slug => $title) {
            $page = get_page_by_path($slug, OBJECT, 'page');

            if ($page) {
                continue;
            }

            wp_insert_post([
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
        }
    }

    private static function configure_woocommerce_pages()
    {
        $mapping = [
            'woocommerce_shop_page_id' => 'tours',
            'woocommerce_cart_page_id' => 'gio-hang',
            'woocommerce_checkout_page_id' => 'thanh-toan',
            'woocommerce_myaccount_page_id' => 'tai-khoan',
        ];

        foreach ($mapping as $option => $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');

            if ($page) {
                update_option($option, $page->ID);
            }
        }
    }
}
