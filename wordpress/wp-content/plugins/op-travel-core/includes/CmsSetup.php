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
        self::ensure_pages();
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
        return '<form class="op-travel-contact-form"><div class="op-field"><label>Há» vÃ  tÃªn</label><input type="text" name="full_name" /></div><div class="op-field"><label>Email</label><input type="email" name="email" /></div><div class="op-field"><label>Sá»‘ Ä‘iá»‡n thoáº¡i</label><input type="text" name="phone" /></div><div class="op-field"><label>Ná»™i dung</label><textarea name="message" rows="5"></textarea></div><button type="submit">Gá»­i yÃªu cáº§u tÆ° váº¥n</button></form>';
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

    public static function get_page_blueprint()
    {
        return [
            'trang-chu' => 'Trang chá»§',
            'blog' => 'Blog',
            'lien-he' => 'LiÃªn há»‡',
            'tours' => 'Tours',
            'gio-hang' => 'Giá» hÃ ng',
            'thanh-toan' => 'Thanh toÃ¡n',
            'tai-khoan' => 'TÃ i khoáº£n',
        ];
    }

    public static function ensure_pages()
    {
        $page_ids = [];

        foreach (self::get_page_blueprint() as $slug => $title) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            $content = self::get_seed_page_content($slug);

            if ($page) {
                $page_ids[$slug] = (int) $page->ID;
                if ($content !== '' && trim((string) $page->post_content) === '') {
                    wp_update_post([
                        'ID' => $page->ID,
                        'post_content' => $content,
                    ]);
                }
                continue;
            }

            $page_ids[$slug] = (int) wp_insert_post([
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => $content,
            ]);
        }

        return $page_ids;
    }

    private static function get_seed_page_content($slug)
    {
        $content = [
            'lien-he' => '[op_travel_contact_form]',
            'gio-hang' => '[woocommerce_cart]',
            'thanh-toan' => '[woocommerce_checkout]',
            'tai-khoan' => '[woocommerce_my_account]',
        ];

        return $content[$slug] ?? '';
    }

    public static function configure_woocommerce_pages()
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
