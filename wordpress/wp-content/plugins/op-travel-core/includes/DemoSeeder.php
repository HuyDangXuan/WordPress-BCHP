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
            <p><?php esc_html_e('Tạo pages chuẩn, taxonomy destination và tour_style, promotion, testimonial và các tour mẫu cho storefront demo.', 'op-travel-core'); ?></p>

            <?php if ($status === 'success') : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Demo data đã được đồng bộ. Chạy lại sẽ tận dụng slug cũ để tránh nhân bản.', 'op-travel-core'); ?></p></div>
            <?php endif; ?>

            <?php if (! class_exists('WooCommerce') || ! post_type_exists('product')) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('WooCommerce chưa được kích hoạt. Hãy cài đặt và activate WooCommerce trước khi seed product tour.', 'op-travel-core'); ?></p></div>
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
            wp_die(esc_html__('Bạn không có quyền thực hiện thao tác này.', 'op-travel-core'));
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
            self::upsert_term('destination', $slug, $name);
        }

        foreach (self::get_tour_styles() as $slug => $name) {
            self::upsert_term('tour_style', $slug, $name);
        }
    }

    private static function upsert_term($taxonomy, $slug, $name)
    {
        $existing = term_exists($slug, $taxonomy);

        if (! $existing) {
            wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            return;
        }

        $term_id = is_array($existing) ? (int) $existing['term_id'] : (int) $existing;

        if ($term_id > 0) {
            wp_update_term($term_id, $taxonomy, [
                'name' => $name,
                'slug' => $slug,
            ]);
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
            'phu-quoc' => 'Phú Quốc',
            'da-nang' => 'Đà Nẵng',
            'ha-giang' => 'Hà Giang',
        ];
    }

    private static function get_tour_styles()
    {
        return [
            'bien-nghi-duong' => 'Biển Nghỉ Dưỡng',
            'van-hoa-am-thuc' => 'Văn Hóa & Ẩm Thực',
            'trekking-canh-quan' => 'Trekking & Cảnh Quan',
        ];
    }

    private static function get_demo_products()
    {
        return array_merge([
            [
                'slug' => 'premium-hoang-hon-phu-quoc',
                'title' => 'Premium Hoàng Hôn Phú Quốc 4N3Đ',
                'price' => '6490000',
                'excerpt' => 'Hành trình nghỉ dưỡng biển cao cấp với sunset cruise và resort ven biển.',
                'content' => 'Shortlist cho nhóm khách muốn nghỉ dưỡng nhẹ, ăn tốt và khóa lại những khoảnh khắc hoàng hôn ở Nam đảo.',
                'destination' => 'phu-quoc',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'PQ-401',
                    '_duration_text' => '4 ngày 3 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Sân bay Tân Sơn Nhất',
                    '_available_departure_dates' => "2026-05-10\n2026-05-17\n2026-05-24",
                    '_tour_highlights' => "Sunset cruise riêng nhóm\nResort 4 sao ven biển\nSeafood dinner curated",
                    '_tour_itinerary' => "Ngày 1 - Bay TP.HCM > Phú Quốc, nhận resort, chill hoàng hôn\nNgày 2 - Cano 4 đảo, snorkeling và beach club\nNgày 3 - Sunset Town, cáp treo Hòn Thơm, dinner cruise\nNgày 4 - Brunch ven biển và bay về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn 4 sao + breakfast\nXe đưa đón và vé tham quan",
                    '_tour_excludes' => "Chi phí cá nhân\nMini bar và spa\nVAT theo nhu cầu xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'di-san-da-nang-hoi-an',
                'title' => 'Di Sản Đà Nẵng - Hội An 3N2Đ',
                'price' => '4290000',
                'excerpt' => 'Nhịp city-break gọn gàng, cân bằng biển, phố cổ và ẩm thực miền Trung.',
                'content' => 'Tour phù hợp cho khách muốn có một chuyến đi ngắn nhưng vẫn đủ câu chuyện về destination và lifestyle.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'DN-218',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Ga đi nội địa sân bay Nội Bài',
                    '_available_departure_dates' => "2026-05-08\n2026-05-15\n2026-05-22",
                    '_tour_highlights' => "Bà Nà sáng sớm\nDạo phố cổ Hội An buổi tối\nFood map đặc sản địa phương",
                    '_tour_itinerary' => "Ngày 1 - Bay Hà Nội > Đà Nẵng, check-in beach hotel\nNgày 2 - Bà Nà Hills, cà phê tầng mây, tối vào Hội An\nNgày 3 - Chợ Hàn, brunch, bay về Hà Nội",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn trung tâm\nXe shuttle và 2 bữa chính",
                    '_tour_excludes' => "Mua sắm cá nhân\nĐồ uống ngoài menu\nChi phí tip tự nguyện",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'cao-nguyen-da-ha-giang',
                'title' => 'Cao Nguyên Đá Hà Giang 4N3Đ',
                'price' => '5190000',
                'excerpt' => 'Hành trình cảnh quan mát lạnh, cung đường đẹp và nhịp trekking vừa phải.',
                'content' => 'Dành cho nhóm khách ưu tiên trải nghiệm natural scenery và muốn có itinerary rõ nhịp từng ngày.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'HG-114',
                    '_duration_text' => '4 ngày 3 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Nhà hát Lớn Hà Nội',
                    '_available_departure_dates' => "2026-05-12\n2026-05-19\n2026-05-26",
                    '_tour_highlights' => "Mã Pì Lèng lookout\nĐồng Văn old quarter\nTrekking nhẹ và homestay curated",
                    '_tour_itinerary' => "Ngày 1 - Hà Nội > Hà Giang, city check-in\nNgày 2 - Quản Bạ, Yên Minh, Đồng Văn\nNgày 3 - Mã Pì Lèng, Nho Quế, trekking nhẹ\nNgày 4 - Brunch bình yên và xe về Hà Nội",
                    '_tour_includes' => "Xe limousine khứ hồi\nKhách sạn/homestay curated\nHướng dẫn viên và vé các điểm chính",
                    '_tour_excludes' => "Chi phí xe ôm/tàu riêng\nNước uống ngoài bữa ăn\nBảo hiểm cực cao theo yêu cầu",
                    '_gallery_ids' => '',
                ],
            ],
        ], self::get_additional_demo_products());
    }

    private static function get_additional_demo_products()
    {
        return [
            [
                'slug' => 'con-dao-blue-retreat',
                'title' => 'Con Dao Blue Retreat 3N2D',
                'price' => '5890000',
                'excerpt' => 'Private island rhythm with quiet beaches, reef time, and a slower premium stay.',
                'content' => 'Designed for small groups that want a calm coastal escape with enough structure to compare tour cards and checkout states.',
                'destination' => 'phu-quoc',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'CD-302',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'San bay Tan Son Nhat',
                    '_available_departure_dates' => "2026-06-02\n2026-06-09\n2026-06-16",
                    '_tour_highlights' => "Dam Trau beach hour\nReef snorkeling window\nSlow seafood dinner",
                    '_tour_itinerary' => "Day 1 - Fly to Con Dao, coastal check-in\nDay 2 - Reef route, beach pause, local dinner\nDay 3 - Morning cafe and return flight",
                    '_tour_includes' => "Round-trip flight\nBoutique hotel breakfast\nPrivate transfer and guide",
                    '_tour_excludes' => "Personal expenses\nOptional spa\nVAT on request",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'sapa-cloud-hike',
                'title' => 'Sapa Cloud Hike 3N2D',
                'price' => '3790000',
                'excerpt' => 'A cool-weather mountain route with soft trekking, terrace views, and lodge comfort.',
                'content' => 'Adds another mountain card for testing long archive grids, image skeleton timing, and product detail loading.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'SP-303',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'Ha Noi',
                    '_meeting_point' => 'Pho co Ha Noi',
                    '_available_departure_dates' => "2026-06-04\n2026-06-11\n2026-06-18",
                    '_tour_highlights' => "Cloudy ridge walk\nTerrace valley lunch\nWarm lodge evening",
                    '_tour_itinerary' => "Day 1 - Ha Noi to Sapa, check-in and village walk\nDay 2 - Soft hike through terrace routes\nDay 3 - Mountain cafe and return",
                    '_tour_includes' => "Limousine transfer\nLodge stay with breakfast\nLocal guide and entry tickets",
                    '_tour_excludes' => "Personal drinks\nCable car upgrades\nTips",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'ninh-binh-heritage-escape',
                'title' => 'Ninh Binh Heritage Escape 2N1D',
                'price' => '2490000',
                'excerpt' => 'Short heritage escape with boat routes, limestone scenery, and compact logistics.',
                'content' => 'A short-haul option that creates more archive diversity without changing the booking workflow.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'NB-201',
                    '_duration_text' => '2 ngay 1 dem',
                    '_departure_city' => 'Ha Noi',
                    '_meeting_point' => 'Nha hat Lon Ha Noi',
                    '_available_departure_dates' => "2026-06-06\n2026-06-13\n2026-06-20",
                    '_tour_highlights' => "Trang An boat route\nTam Coc sunset\nLocal goat dinner",
                    '_tour_itinerary' => "Day 1 - Ha Noi to Ninh Binh, boat route and dinner\nDay 2 - Morning viewpoint and return",
                    '_tour_includes' => "Round-trip shuttle\nHotel breakfast\nBoat ticket and dinner",
                    '_tour_excludes' => "Bike rental upgrades\nPersonal coffee\nVAT on request",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'da-lat-slow-living',
                'title' => 'Da Lat Slow Living 3N2D',
                'price' => '3590000',
                'excerpt' => 'Pine forest air, cafe stops, garden lunch, and a relaxed highland itinerary.',
                'content' => 'Useful for skeleton testing because it adds another visually distinct tour card and detail page.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'DL-320',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'San bay Tan Son Nhat',
                    '_available_departure_dates' => "2026-06-07\n2026-06-14\n2026-06-21",
                    '_tour_highlights' => "Forest cafe route\nGarden lunch\nEvening market walk",
                    '_tour_itinerary' => "Day 1 - Fly to Da Lat and check in\nDay 2 - Forest route, garden lunch, cafe crawl\nDay 3 - Brunch and return flight",
                    '_tour_includes' => "Round-trip flight\nHotel breakfast\nPrivate car and selected meals",
                    '_tour_excludes' => "Personal shopping\nSpecialty coffee tasting\nSpa",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'mekong-private-cruise',
                'title' => 'Mekong Private Cruise 2N1D',
                'price' => '2990000',
                'excerpt' => 'Private river rhythm with orchard stops, local kitchens, and sunset on deck.',
                'content' => 'A river product for checkout and cart testing with shorter duration and different price density.',
                'destination' => 'phu-quoc',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'MK-211',
                    '_duration_text' => '2 ngay 1 dem',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Quan 1, TP.HCM',
                    '_available_departure_dates' => "2026-06-08\n2026-06-15\n2026-06-22",
                    '_tour_highlights' => "Private boat hour\nOrchard tasting\nSunset deck dinner",
                    '_tour_itinerary' => "Day 1 - Drive to Mekong, cruise and dinner\nDay 2 - Market breakfast, orchard stop, return",
                    '_tour_includes' => "Private transfer\nBoat route\nMeals in itinerary",
                    '_tour_excludes' => "Personal drinks\nExtra boat time\nVAT on request",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'quy-nhon-coastal-hideaway',
                'title' => 'Quy Nhon Coastal Hideaway 3N2D',
                'price' => '4690000',
                'excerpt' => 'A quieter central coast trip with island routes, seafood, and resort downtime.',
                'content' => 'Adds another coastal card so the archive has enough rows to inspect skeleton loading behavior.',
                'destination' => 'da-nang',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'QN-322',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'Ha Noi',
                    '_meeting_point' => 'San bay Noi Bai',
                    '_available_departure_dates' => "2026-06-10\n2026-06-17\n2026-06-24",
                    '_tour_highlights' => "Ky Co beach\nEo Gio viewpoint\nSeafood tasting",
                    '_tour_itinerary' => "Day 1 - Fly to Quy Nhon, resort check-in\nDay 2 - Island route and seafood lunch\nDay 3 - Cafe morning and return",
                    '_tour_includes' => "Round-trip flight\nResort breakfast\nIsland route transfer",
                    '_tour_excludes' => "Water sport upgrades\nPersonal snacks\nTips",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'hue-imperial-food-trail',
                'title' => 'Hue Imperial Food Trail 3N2D',
                'price' => '3390000',
                'excerpt' => 'Imperial city pacing with local kitchens, river evening, and compact culture stops.',
                'content' => 'Adds a culture-heavy card to test taxonomy filtering and visual rhythm across multiple rows.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'HU-318',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'San bay Tan Son Nhat',
                    '_available_departure_dates' => "2026-06-12\n2026-06-19\n2026-06-26",
                    '_tour_highlights' => "Imperial city morning\nHue food trail\nPerfume river evening",
                    '_tour_itinerary' => "Day 1 - Fly to Hue, dinner trail\nDay 2 - Imperial city and garden house lunch\nDay 3 - Market stop and return flight",
                    '_tour_includes' => "Round-trip flight\nHotel breakfast\nFood trail and guide",
                    '_tour_excludes' => "Personal shopping\nExtra river boat hour\nVAT on request",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'cat-ba-lan-ha-adventure',
                'title' => 'Cat Ba Lan Ha Adventure 3N2D',
                'price' => '3990000',
                'excerpt' => 'Bay kayaking, island routes, and light adventure with a comfortable overnight base.',
                'content' => 'A northern coast option that gives the archive more cards for skeleton timing tests.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'CB-327',
                    '_duration_text' => '3 ngay 2 dem',
                    '_departure_city' => 'Ha Noi',
                    '_meeting_point' => 'Pho co Ha Noi',
                    '_available_departure_dates' => "2026-06-13\n2026-06-20\n2026-06-27",
                    '_tour_highlights' => "Lan Ha kayaking\nIsland lookout\nSeafood dinner",
                    '_tour_itinerary' => "Day 1 - Ha Noi to Cat Ba\nDay 2 - Bay route, kayak and beach stop\nDay 3 - Brunch and return",
                    '_tour_includes' => "Round-trip shuttle\nHotel breakfast\nBoat and kayak route",
                    '_tour_excludes' => "Personal drinks\nSingle room surcharge\nTips",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'moc-chau-tea-valley',
                'title' => 'Moc Chau Tea Valley 2N1D',
                'price' => '2190000',
                'excerpt' => 'Tea hills, soft meadow routes, and a quick cool-air reset from Hanoi.',
                'content' => 'A lightweight product that helps fill the second archive page and exercise card placeholders.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'MC-209',
                    '_duration_text' => '2 ngay 1 dem',
                    '_departure_city' => 'Ha Noi',
                    '_meeting_point' => 'Nha hat Lon Ha Noi',
                    '_available_departure_dates' => "2026-06-14\n2026-06-21\n2026-06-28",
                    '_tour_highlights' => "Tea valley walk\nMeadow photo route\nLocal dinner",
                    '_tour_itinerary' => "Day 1 - Ha Noi to Moc Chau, tea valley and dinner\nDay 2 - Morning meadow route and return",
                    '_tour_includes' => "Round-trip shuttle\nHotel breakfast\nGuide and dinner",
                    '_tour_excludes' => "Personal coffee\nATV or photo props\nVAT on request",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'sepay-test-tour',
                'title' => 'SePay Test Tour 2K',
                'price' => '2000',
                'excerpt' => 'Low-value booking used to validate SePay QR and webhook flow without a high payment amount.',
                'content' => 'A lightweight test itinerary for local and staging payment checks where the team needs a real WooCommerce product at 2000 VND.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'SP-002',
                    '_duration_text' => '1 ngay',
                    '_departure_city' => 'Da Nang',
                    '_meeting_point' => 'Trung tam Da Nang',
                    '_available_departure_dates' => "2026-06-30\n2026-07-07\n2026-07-14",
                    '_tour_highlights' => "Low-value checkout\nSePay webhook validation\nFast QA turnaround",
                    '_tour_itinerary' => "Day 1 - Quick check-in, confirm booking metadata, and complete payment test",
                    '_tour_includes' => "Booking hold\nPayment QR test\nOrder confirmation",
                    '_tour_excludes' => "Transport\nMeals\nManual support outside QA scope",
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
                'title' => 'Ưu đãi Premium Summer',
                'excerpt' => 'Tặng dinner sunset cho nhóm đặt sớm.',
                'content' => 'Áp dụng cho các itinerary biển khi booking trước 21 ngày. Tư vấn viên xác nhận quà tặng khi chốt booking.',
            ],
            [
                'slug' => 'combo-gia-dinh-thang-5',
                'title' => 'Combo Gia Đình Tháng 5',
                'excerpt' => 'Ưu tiên khung phòng family và xe riêng.',
                'content' => 'Dành cho booking có từ 2 người lớn và 1 trẻ em trở lên. Đi kèm hotline riêng để chốt các yêu cầu logistics.',
            ],
        ];
    }

    private static function get_testimonials()
    {
        return [
            [
                'slug' => 'review-phu-quoc-sunset',
                'title' => 'Linh & Khánh, HCM',
                'excerpt' => 'Lịch trình rất gọn và có gu.',
                'content' => 'Điều mình thích nhất là tour không nhồi nhét điểm đến. Từ page tour đến checkout đều cho cảm giác premium và rõ ràng.',
            ],
            [
                'slug' => 'review-ha-giang-itinerary',
                'title' => 'Minh Anh, Hà Nội',
                'excerpt' => 'Booking flow rõ và yêu cầu riêng được ghi nhận ngay.',
                'content' => 'Mình có ghi chú về chế độ ăn và điểm đón. Khi vào admin/order review nhìn rất rõ booking snapshot nên cảm giác hệ thống khá chắc tay.',
            ],
        ];
    }
}
