<?php

namespace OPTravelCore;

final class DemoSeeder
{
    public static function boot()
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_menu', [__CLASS__, 'register_page']);
        add_action('admin_post_op_travel_seed_demo_data', [__CLASS__, 'handle_seed_request']);
    }

    public static function register_page()
    {
        add_management_page(
            __('OP Travel Seeder', 'op-travel-core'),
            __('OP Travel Seeder', 'op-travel-core'),
            'manage_options',
            'op-travel-demo-seeder',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $status = isset($_GET['op_travel_seed_status']) ? sanitize_text_field(wp_unslash($_GET['op_travel_seed_status'])) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OP Travel Demo Seeder', 'op-travel-core'); ?></h1>
            <p><?php esc_html_e('Táº¡o pages chuáº©n, taxonomy destination vÃ  tour_style, promotion, testimonial vÃ  cÃ¡c tour máº«u cho storefront demo.', 'op-travel-core'); ?></p>

            <?php if ($status === 'success') : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Demo data Ä‘Ã£ Ä‘Æ°á»£c Ä‘á»“ng bá»™. Cháº¡y láº¡i sáº½ táº­n dá»¥ng slug cÅ© Ä‘á»ƒ trÃ¡nh nhÃ¢n báº£n.', 'op-travel-core'); ?></p></div>
            <?php endif; ?>

            <?php if (! class_exists('WooCommerce') || ! post_type_exists('product')) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('WooCommerce chÆ°a Ä‘Æ°á»£c kÃ­ch hoáº¡t. HÃ£y cÃ i Ä‘áº·t vÃ  activate WooCommerce trÆ°á»›c khi seed product tour.', 'op-travel-core'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="op_travel_seed_demo_data" />
                <?php wp_nonce_field('op_travel_seed_demo_data', 'op_travel_seed_demo_data_nonce'); ?>
                <?php submit_button(__('Seed Demo Data', 'op-travel-core')); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_seed_request()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Báº¡n khÃ´ng cÃ³ quyá»n thá»±c hiá»‡n thao tÃ¡c nÃ y.', 'op-travel-core'));
        }

        check_admin_referer('op_travel_seed_demo_data', 'op_travel_seed_demo_data_nonce');

        self::seed_pages();
        self::seed_terms();
        self::seed_products();
        self::seed_content_posts('promotion', self::get_promotions());
        self::seed_content_posts('testimonial', self::get_testimonials());

        wp_safe_redirect(add_query_arg([
            'page' => 'op-travel-demo-seeder',
            'op_travel_seed_status' => 'success',
        ], admin_url('tools.php')));
        exit;
    }

    private static function seed_pages()
    {
        CmsSetup::ensure_pages();
        CmsSetup::configure_woocommerce_pages();
    }

    private static function seed_terms()
    {
        foreach (self::get_destinations() as $slug => $name) {
            if (! term_exists($slug, 'destination')) {
                wp_insert_term($name, 'destination', ['slug' => $slug]);
            }
        }

        foreach (self::get_tour_styles() as $slug => $name) {
            if (! term_exists($slug, 'tour_style')) {
                wp_insert_term($name, 'tour_style', ['slug' => $slug]);
            }
        }
    }

    private static function seed_products()
    {
        if (! post_type_exists('product')) {
            return;
        }

        foreach (self::get_demo_products() as $product_data) {
            $product_id = self::upsert_product($product_data);

            if (! $product_id || is_wp_error($product_id)) {
                continue;
            }

            wp_set_object_terms($product_id, 'simple', 'product_type');
            wp_set_object_terms($product_id, [$product_data['destination']], 'destination', false);
            wp_set_object_terms($product_id, [$product_data['tour_style']], 'tour_style', false);

            update_post_meta($product_id, '_regular_price', $product_data['price']);
            update_post_meta($product_id, '_price', $product_data['price']);
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_visibility', 'visible');

            foreach ($product_data['meta'] as $meta_key => $meta_value) {
                update_post_meta($product_id, $meta_key, $meta_value);
            }
        }
    }

    private static function seed_content_posts($post_type, $items)
    {
        if (! post_type_exists($post_type)) {
            return;
        }

        foreach ($items as $item) {
            $existing = get_page_by_path($item['slug'], OBJECT, $post_type);
            $post_args = [
                'post_title' => $item['title'],
                'post_name' => $item['slug'],
                'post_status' => 'publish',
                'post_type' => $post_type,
                'post_content' => $item['content'],
                'post_excerpt' => $item['excerpt'],
            ];

            if ($existing) {
                $post_args['ID'] = $existing->ID;
                wp_update_post($post_args);
                continue;
            }

            wp_insert_post($post_args);
        }
    }

    private static function upsert_product($product_data)
    {
        $existing = get_page_by_path($product_data['slug'], OBJECT, 'product');
        $post_args = [
            'post_title' => $product_data['title'],
            'post_name' => $product_data['slug'],
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_excerpt' => $product_data['excerpt'],
            'post_content' => $product_data['content'],
        ];

        if ($existing) {
            $post_args['ID'] = $existing->ID;
            return wp_update_post($post_args);
        }

        return wp_insert_post($post_args);
    }

    private static function get_destinations()
    {
        return [
            'phu-quoc' => 'PhÃº Quá»‘c',
            'da-nang' => 'ÄÃ  Náºµng',
            'ha-giang' => 'HÃ  Giang',
        ];
    }

    private static function get_tour_styles()
    {
        return [
            'bien-nghi-duong' => 'Biá»ƒn Nghá»‰ DÆ°á»¡ng',
            'van-hoa-am-thuc' => 'VÄƒn HÃ³a & áº¨m Thá»±c',
            'trekking-canh-quan' => 'Trekking & Cáº£nh Quan',
        ];
    }

    private static function get_demo_products()
    {
        return [
            [
                'slug' => 'premium-hoang-hon-phu-quoc',
                'title' => 'Premium HoÃ ng HÃ´n PhÃº Quá»‘c 4N3Ä',
                'price' => '6490000',
                'excerpt' => 'HÃ nh trÃ¬nh nghá»‰ dÆ°á»¡ng biá»ƒn cao cáº¥p vá»›i sunset cruise vÃ  resort ven biá»ƒn.',
                'content' => 'Shortlist cho nhÃ³m khÃ¡ch muá»‘n nghá»‰ dÆ°á»¡ng nháº¹, Äƒn tá»‘t vÃ  khÃ³a láº¡i nhá»¯ng khoáº£nh kháº¯c hoÃ ng hÃ´n á»Ÿ Nam Ä‘áº£o.',
                'destination' => 'phu-quoc',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'PQ-401',
                    '_duration_text' => '4 ngÃ y 3 Ä‘Ãªm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'SÃ¢n bay TÃ¢n SÆ¡n Nháº¥t',
                    '_available_departure_dates' => "2026-05-10\n2026-05-17\n2026-05-24",
                    '_tour_highlights' => "Sunset cruise riÃªng nhÃ³m\nResort 4 sao ven biá»ƒn\nSeafood dinner curated",
                    '_tour_itinerary' => "NgÃ y 1 - Bay TP.HCM > PhÃº Quá»‘c, nháº­n resort, chill hoÃ ng hÃ´n\nNgÃ y 2 - Cano 4 Ä‘áº£o, snorkeling vÃ  beach club\nNgÃ y 3 - Sunset Town, cÃ¡p treo HÃ²n ThÆ¡m, dinner cruise\nNgÃ y 4 - Brunch ven biá»ƒn vÃ  bay vá»",
                    '_tour_includes' => "VÃ© mÃ¡y bay khá»© há»“i\nKhÃ¡ch sáº¡n 4 sao + breakfast\nXe Ä‘Æ°a Ä‘Ã³n vÃ  véº» tham quan",
                    '_tour_excludes' => "Chi phÃ­ cÃ¡ nhÃ¢n\nMini bar vÃ  spa\nVAT theo nhu cáº§u xuáº¥t hÃ³a Ä‘Æ¡n",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'di-san-da-nang-hoi-an',
                'title' => 'Di Sáº£n ÄÃ  Náºµng - Há»™i An 3N2Ä',
                'price' => '4290000',
                'excerpt' => 'Nhá»‹p city-break gá»n gÃ ng, cÃ¢n báº±ng biá»ƒn, phá»‘ cá»• vÃ  áº©m thá»±c miá»n Trung.',
                'content' => 'Tour phÃ¹ há»£p cho khÃ¡ch muá»‘n cÃ³ má»™t chuyáº¿n Ä‘i ngáº¯n nhÆ°ng váº«n Ä‘á»§ cÃ¢u chuyá»‡n vá» destination vÃ  lifestyle.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'DN-218',
                    '_duration_text' => '3 ngÃ y 2 Ä‘Ãªm',
                    '_departure_city' => 'HÃ  Ná»™i',
                    '_meeting_point' => 'Ga Ä‘i ná»™i Ä‘á»‹a sÃ¢n bay Ná»™i BÃ i',
                    '_available_departure_dates' => "2026-05-08\n2026-05-15\n2026-05-22",
                    '_tour_highlights' => "BÃ  NÃ  sáng sớm\nDáº¡o phá»‘ cá»• Há»™i An buá»•i tá»‘i\nFood map Ä‘áº·c sáº£n Ä‘á»‹a phÆ°Æ¡ng",
                    '_tour_itinerary' => "NgÃ y 1 - Bay HÃ  Ná»™i > ÄÃ  Náºµng, check-in beach hotel\nNgÃ y 2 - BÃ  NÃ  Hills, cÃ  phÃª táº§ng mÃ¢y, tá»‘i vÃ o Há»™i An\nNgÃ y 3 - Chá»£ HÃ n, brunch, bay vá» HÃ  Ná»™i",
                    '_tour_includes' => "VÃ© mÃ¡y bay khá»© há»“i\nKhÃ¡ch sáº¡n trung tÃ¢m\nXe shuttle vÃ  2 bữa chÃ­nh",
                    '_tour_excludes' => "Mua sáº¯m cÃ¡ nhÃ¢n\nÄá»“ uá»‘ng ngoÃ i menu\nChi phÃ­ tip tá»± nguyá»‡n",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'cao-nguyen-da-ha-giang',
                'title' => 'Cao NguyÃªn ÄÃ¡ HÃ  Giang 4N3Ä',
                'price' => '5190000',
                'excerpt' => 'HÃ nh trÃ¬nh cáº£nh quan mÃ¡t láº¡nh, cung Ä‘Æ°á»ng Ä‘áº¹p vÃ  nhá»‹p trekking vá»«a pháº£i.',
                'content' => 'DÃ nh cho nhÃ³m khÃ¡ch Æ°u tiÃªn tráº£i nghiá»‡m natural scenery vÃ  muá»‘n cÃ³ itinerary rÃµ nhá»‹p tá»«ng ngÃ y.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'HG-114',
                    '_duration_text' => '4 ngÃ y 3 Ä‘Ãªm',
                    '_departure_city' => 'HÃ  Ná»™i',
                    '_meeting_point' => 'NhÃ  hÃ¡t Lá»›n HÃ  Ná»™i',
                    '_available_departure_dates' => "2026-05-12\n2026-05-19\n2026-05-26",
                    '_tour_highlights' => "MÃ£ PÃ¬ LÃ¨ng lookout\nÄá»“ng VÄƒn old quarter\nTrekking nháº¹ vÃ  homestay curated",
                    '_tour_itinerary' => "NgÃ y 1 - HÃ  Ná»™i > HÃ  Giang, city check-in\nNgÃ y 2 - Quáº£n Báº¡, YÃªn Minh, Äá»“ng VÄƒn\nNgÃ y 3 - MÃ£ PÃ¬ LÃ¨ng, Nho Quáº¿, trekking nháº¹\nNgÃ y 4 - Brunch bÃ¬nh yÃªn vÃ  xe vá» HÃ  Ná»™i",
                    '_tour_includes' => "Xe limousine khá»© há»“i\nKhÃ¡ch sáº¡n/homestay curated\nHÆ°á»›ng dáº«n viÃªn vÃ  véº» cÃ¡c Ä‘iá»ƒm chÃ­nh",
                    '_tour_excludes' => "Chi phÃ­ xe Ã´m/tÃ u riÃªng\nNÆ°á»›c uá»‘ng ngoÃ i bữa Äƒn\nBáº£o hiá»ƒm cá»±c cao theo yÃªu cáº§u",
                    '_gallery_ids' => '',
                ],
            ],
        ];
    }

    private static function get_promotions()
    {
        return [
            [
                'slug' => 'uu-dai-premium-summer',
                'title' => 'Æ¯u Ä‘Ã£i Premium Summer',
                'excerpt' => 'Táº·ng dinner sunset cho nhÃ³m Ä‘áº·t sớm.',
                'content' => 'Ãp dá»¥ng cho cÃ¡c itinerary biá»ƒn khi booking trÆ°á»›c 21 ngÃ y. TÆ° váº¥n viÃªn xÃ¡c nháº­n quÃ  táº·ng khi chốt booking.',
            ],
            [
                'slug' => 'combo-gia-dinh-thang-5',
                'title' => 'Combo Gia ÄÃ¬nh ThÃ¡ng 5',
                'excerpt' => 'Æ¯u tiÃªn khung phÃ²ng family vÃ  xe riÃªng.',
                'content' => 'DÃ nh cho booking cÃ³ tá»« 2 ngÆ°á»i lá»›n vÃ  1 tráº» em trá»Ÿ lÃªn. Äi kÃ¨m hotline riÃªng Ä‘á»ƒ chá»‘t cÃ¡c yÃªu cáº§u logistics.',
            ],
        ];
    }

    private static function get_testimonials()
    {
        return [
            [
                'slug' => 'review-phu-quoc-sunset',
                'title' => 'Linh & KhÃ¡nh, HCM',
                'excerpt' => 'Lá»‹ch trÃ¬nh ráº¥t gá»n vÃ  cÃ³ gu.',
                'content' => 'Äiá»u mÃ¬nh thÃ­ch nháº¥t lÃ  tour khÃ´ng nhá»“i nhÃ©t Ä‘iá»ƒm Ä‘áº¿n. Tá»« page tour Ä‘áº¿n checkout Ä‘á»u cho cáº£m giÃ¡c premium vÃ  rÃµ rÃ ng.',
            ],
            [
                'slug' => 'review-ha-giang-itinerary',
                'title' => 'Minh Anh, HÃ  Ná»™i',
                'excerpt' => 'Booking flow rÃµ vÃ  yÃªu cáº§u riÃªng Ä‘Æ°á»£c ghi nháº­n ngay.',
                'content' => 'MÃ¬nh cÃ³ ghi chÃº vá» cháº¿ Ä‘á»™ Äƒn vÃ  Ä‘iá»ƒm Ä‘Ã³n. Khi vÃ o admin/order review nhÃ¬n ráº¥t rá»µ booking snapshot nÃªn cáº£m giÃ¡c há»‡ thá»‘ng khÃ¡ cháº¯c tay.',
            ],
        ];
    }
}
