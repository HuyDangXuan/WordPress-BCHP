<?php

namespace OPTravelCore;

use OPTravelCore\Support\Env;

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
            <p><?php esc_html_e('Tạo các trang chuẩn, taxonomy destination và tour_style, promotion, testimonial và các tour mẫu cho storefront demo.', 'op-travel-core'); ?></p>

            <?php if ($status === 'success') : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Demo data đã được đồng bộ. Chạy lại sẽ tận dụng slug cũ để tránh nhân bản.', 'op-travel-core'); ?></p></div>
            <?php endif; ?>

            <?php if (! class_exists('WooCommerce') || ! post_type_exists('product')) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('WooCommerce chưa được kích hoạt. Hãy cài đặt và kích hoạt WooCommerce trước khi seed product tour.', 'op-travel-core'); ?></p></div>
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

        self::reset_existing_business_records();
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

    private static function reset_existing_business_records()
    {
        self::reset_booking_service_records();
        self::delete_existing_orders();
    }

    private static function reset_booking_service_records()
    {
        $endpoint = self::get_booking_service_reset_endpoint();
        $secret = trim((string) Env::get('PAYMENT_SYNC_SECRET'));

        if ($endpoint === '' || $secret === '') {
            return;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $secret,
            ],
        ]);

        if (is_wp_error($response)) {
            return;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return;
        }
    }

    private static function get_booking_service_reset_endpoint()
    {
        $endpoint = trim((string) Env::get('BOOKING_SERVICE_ENDPOINT'));

        if ($endpoint === '') {
            return '';
        }

        if (preg_match('#/api/bookings/?$#', $endpoint) !== 1) {
            return '';
        }

        return (string) preg_replace('#/api/bookings/?$#', '/api/admin/demo-data/reset', $endpoint);
    }

    private static function delete_existing_orders()
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        $order_ids = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
        ]);

        foreach ($order_ids as $order_id) {
            if (function_exists('wc_delete_order')) {
                wc_delete_order($order_id, true);
                continue;
            }

            wp_delete_post($order_id, true);
        }
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
            'bien-nghi-duong' => 'Nghỉ Dưỡng Biển',
            'van-hoa-am-thuc' => 'Văn Hóa & Ẩm Thực',
            'trekking-canh-quan' => 'Khám Phá & Cảnh Quan',
        ];
    }

    private static function get_demo_products()
    {
        return array_merge([
            [
                'slug' => 'premium-hoang-hon-phu-quoc',
                'title' => 'Kỳ Nghỉ Hoàng Hôn Phú Quốc 4N3Đ',
                'price' => '6490000',
                'excerpt' => 'Hành trình nghỉ dưỡng biển cao cấp với du thuyền ngắm hoàng hôn và khu nghỉ ven biển.',
                'content' => 'Lựa chọn phù hợp cho nhóm khách muốn nghỉ dưỡng thư thái, ăn ngon và lưu lại những khoảnh khắc đẹp ở Nam đảo.',
                'destination' => 'phu-quoc',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'PQ-401',
                    '_duration_text' => '4 ngày 3 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Sân bay Tân Sơn Nhất',
                    '_available_departure_dates' => "2026-05-10\n2026-05-17\n2026-05-24",
                    '_tour_highlights' => "Du thuyền ngắm hoàng hôn riêng cho nhóm\nKhu nghỉ 4 sao ven biển\nBữa tối hải sản chọn lọc",
                    '_tour_itinerary' => "Ngày 1 - Bay từ TP.HCM đến Phú Quốc, nhận phòng và ngắm hoàng hôn\nNgày 2 - Ca nô tham quan 4 đảo, lặn ngắm san hô và vui chơi ven biển\nNgày 3 - Tham quan thị trấn Hoàng Hôn, cáp treo Hòn Thơm và ăn tối trên tàu\nNgày 4 - Ăn sáng muộn bên biển và bay về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn 4 sao kèm ăn sáng\nXe đưa đón và vé tham quan",
                    '_tour_excludes' => "Chi phí cá nhân\nĐồ uống và dịch vụ tự chọn tại phòng\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'di-san-da-nang-hoi-an',
                'title' => 'Di Sản Đà Nẵng - Hội An 3N2Đ',
                'price' => '4290000',
                'excerpt' => 'Chuyến đi ngắn ngày cân bằng biển, phố cổ và ẩm thực miền Trung.',
                'content' => 'Phù hợp cho khách muốn đổi gió ngắn ngày nhưng vẫn có đủ trải nghiệm về di sản, nhịp sống và món ăn địa phương.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'DN-218',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Ga đi nội địa sân bay Nội Bài',
                    '_available_departure_dates' => "2026-05-08\n2026-05-15\n2026-05-22",
                    '_tour_highlights' => "Tham quan Bà Nà từ sớm\nDạo phố cổ Hội An buổi tối\nDanh sách món ngon địa phương",
                    '_tour_itinerary' => "Ngày 1 - Bay từ Hà Nội đến Đà Nẵng, nhận phòng khách sạn ven biển\nNgày 2 - Tham quan khu du lịch Bà Nà, ngắm mây và tối vào Hội An\nNgày 3 - Ghé chợ Hàn, ăn sáng muộn và bay về Hà Nội",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn trung tâm\nXe trung chuyển và 2 bữa chính",
                    '_tour_excludes' => "Mua sắm cá nhân\nĐồ uống gọi thêm ngoài thực đơn\nChi phí bồi dưỡng tự nguyện",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'cao-nguyen-da-ha-giang',
                'title' => 'Cao Nguyên Đá Hà Giang 4N3Đ',
                'price' => '5190000',
                'excerpt' => 'Hành trình cảnh quan mát lạnh, cung đường đẹp và nhịp khám phá vừa phải.',
                'content' => 'Dành cho nhóm khách ưu tiên cảnh sắc tự nhiên và muốn có lịch trình rõ ràng theo từng ngày.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'HG-114',
                    '_duration_text' => '4 ngày 3 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Nhà hát Lớn Hà Nội',
                    '_available_departure_dates' => "2026-05-12\n2026-05-19\n2026-05-26",
                    '_tour_highlights' => "Điểm ngắm cảnh Mã Pì Lèng\nPhố cổ Đồng Văn\nĐi bộ nhẹ và nơi nghỉ bản địa chọn lọc",
                    '_tour_itinerary' => "Ngày 1 - Di chuyển từ Hà Nội đến Hà Giang, nhận phòng và nghỉ ngơi\nNgày 2 - Khám phá Quản Bạ, Yên Minh và Đồng Văn\nNgày 3 - Tham quan Mã Pì Lèng, sông Nho Quế và đi bộ nhẹ\nNgày 4 - Ăn sáng muộn, thư thả và trở về Hà Nội",
                    '_tour_includes' => "Xe ghế thương gia khứ hồi\nKhách sạn hoặc nơi nghỉ bản địa chọn lọc\nHướng dẫn viên và vé các điểm chính",
                    '_tour_excludes' => "Chi phí xe ôm hoặc tàu riêng\nNước uống ngoài bữa ăn\nBảo hiểm mức cao theo yêu cầu",
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
                'title' => 'Côn Đảo Biển Xanh 3N2Đ',
                'price' => '5890000',
                'excerpt' => 'Nhịp nghỉ biển chậm rãi với bãi tắm yên, lặn ngắm san hô và nơi lưu trú riêng tư.',
                'content' => 'Phù hợp cho nhóm nhỏ muốn nghỉ dưỡng tĩnh lặng, lịch trình vừa đủ và cảm giác tách khỏi nhịp ồn ào thường ngày.',
                'destination' => 'phu-quoc',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'CD-302',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Sân bay Tân Sơn Nhất',
                    '_available_departure_dates' => "2026-06-02\n2026-06-09\n2026-06-16",
                    '_tour_highlights' => "Bãi Đầm Trầu yên ả\nKhung giờ lặn ngắm san hô đẹp\nBữa tối hải sản thong thả",
                    '_tour_itinerary' => "Ngày 1 - Bay từ TP.HCM đến Côn Đảo, nhận phòng và dạo biển\nNgày 2 - Khám phá cung san hô, nghỉ chân ở bãi biển và ăn tối địa phương\nNgày 3 - Uống cà phê sáng, mua quà nhẹ và bay về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn nhỏ kèm ăn sáng\nXe đưa đón riêng và hướng dẫn viên",
                    '_tour_excludes' => "Chi phí cá nhân\nDịch vụ chăm sóc sức khỏe tự chọn\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'sapa-cloud-hike',
                'title' => 'Sapa Mây Núi 3N2Đ',
                'price' => '3790000',
                'excerpt' => 'Hành trình núi rừng mát lạnh với đi bộ nhẹ, ruộng bậc thang và chỗ nghỉ ấm cúng.',
                'content' => 'Bổ sung một lựa chọn vùng cao có nhịp nhẹ nhàng, hợp cho khách thích cảnh đẹp và không muốn di chuyển quá dày.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'SP-303',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Phố cổ Hà Nội',
                    '_available_departure_dates' => "2026-06-04\n2026-06-11\n2026-06-18",
                    '_tour_highlights' => "Đường núi phủ mây\nBữa trưa giữa thung lũng ruộng bậc thang\nBuổi tối nghỉ dưỡng ấm cúng",
                    '_tour_itinerary' => "Ngày 1 - Di chuyển từ Hà Nội đến Sa Pa, nhận phòng và đi bộ quanh bản\nNgày 2 - Đi bộ nhẹ qua các cung ruộng bậc thang\nNgày 3 - Uống cà phê núi và trở về Hà Nội",
                    '_tour_includes' => "Xe đưa đón khứ hồi\nChỗ nghỉ kèm ăn sáng\nHướng dẫn viên địa phương và vé vào điểm tham quan",
                    '_tour_excludes' => "Đồ uống cá nhân\nChi phí cáp treo tự chọn\nChi phí bồi dưỡng",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'ninh-binh-heritage-escape',
                'title' => 'Di Sản Ninh Bình 2N1Đ',
                'price' => '2490000',
                'excerpt' => 'Chuyến đi di sản ngắn ngày với đường thuyền, núi đá vôi và lịch trình gọn gàng.',
                'content' => 'Lựa chọn gần Hà Nội cho khách muốn đổi gió nhanh mà vẫn có đủ cảnh quan và trải nghiệm địa phương.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'NB-201',
                    '_duration_text' => '2 ngày 1 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Nhà hát Lớn Hà Nội',
                    '_available_departure_dates' => "2026-06-06\n2026-06-13\n2026-06-20",
                    '_tour_highlights' => "Tuyến thuyền Tràng An\nHoàng hôn Tam Cốc\nBữa tối dê núi địa phương",
                    '_tour_itinerary' => "Ngày 1 - Di chuyển từ Hà Nội đến Ninh Bình, đi thuyền và ăn tối\nNgày 2 - Lên điểm ngắm cảnh buổi sáng rồi trở về",
                    '_tour_includes' => "Xe đưa đón khứ hồi\nKhách sạn kèm ăn sáng\nVé thuyền và bữa tối",
                    '_tour_excludes' => "Chi phí thuê xe đạp tự chọn\nCà phê gọi thêm\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'da-lat-slow-living',
                'title' => 'Đà Lạt Sống Chậm 3N2Đ',
                'price' => '3590000',
                'excerpt' => 'Không khí rừng thông, điểm dừng cà phê và bữa trưa sân vườn trong nhịp cao nguyên thư thái.',
                'content' => 'Phù hợp cho khách thích không khí chậm rãi, nhiều khoảng nghỉ và lịch trình nhẹ nhàng giữa thành phố mù sương.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'DL-320',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Sân bay Tân Sơn Nhất',
                    '_available_departure_dates' => "2026-06-07\n2026-06-14\n2026-06-21",
                    '_tour_highlights' => "Cung cà phê giữa rừng thông\nBữa trưa sân vườn\nDạo chợ tối Đà Lạt",
                    '_tour_itinerary' => "Ngày 1 - Bay từ TP.HCM đến Đà Lạt và nhận phòng\nNgày 2 - Đi qua cung rừng thông, ăn trưa sân vườn và dạo quán cà phê\nNgày 3 - Ăn sáng muộn rồi bay về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn kèm ăn sáng\nXe riêng và một số bữa ăn chọn lọc",
                    '_tour_excludes' => "Mua sắm cá nhân\nThưởng thức cà phê đặc sản tự chọn\nDịch vụ chăm sóc sức khỏe tự chọn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'mekong-private-cruise',
                'title' => 'Miền Tây Sông Nước 2N1Đ',
                'price' => '2990000',
                'excerpt' => 'Nhịp sông nước riêng tư với điểm dừng miệt vườn, bếp địa phương và bữa tối ngắm hoàng hôn.',
                'content' => 'Phù hợp cho khách muốn một hành trình ngắn, êm và đậm không khí miền Tây Nam Bộ.',
                'destination' => 'phu-quoc',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'MK-211',
                    '_duration_text' => '2 ngày 1 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Quận 1, TP.HCM',
                    '_available_departure_dates' => "2026-06-08\n2026-06-15\n2026-06-22",
                    '_tour_highlights' => "Du thuyền riêng theo giờ\nThưởng thức trái cây tại vườn\nBữa tối trên boong lúc hoàng hôn",
                    '_tour_itinerary' => "Ngày 1 - Đi từ TP.HCM xuống miền Tây, xuống thuyền và ăn tối\nNgày 2 - Ăn sáng tại chợ, ghé miệt vườn rồi trở về",
                    '_tour_includes' => "Xe đưa đón riêng\nLộ trình thuyền\nCác bữa ăn trong lịch trình",
                    '_tour_excludes' => "Đồ uống cá nhân\nPhụ thu kéo dài thời gian trên thuyền\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'quy-nhon-coastal-hideaway',
                'title' => 'Quy Nhơn Ven Biển 3N2Đ',
                'price' => '4690000',
                'excerpt' => 'Chuyến biển miền Trung yên tĩnh với cung đảo, hải sản và nhiều thời gian nghỉ dưỡng.',
                'content' => 'Bổ sung một lựa chọn ven biển nhẹ nhàng cho khách thích không khí thoáng và lịch trình không quá gấp.',
                'destination' => 'da-nang',
                'tour_style' => 'bien-nghi-duong',
                'meta' => [
                    '_tour_code' => 'QN-322',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Sân bay Nội Bài',
                    '_available_departure_dates' => "2026-06-10\n2026-06-17\n2026-06-24",
                    '_tour_highlights' => "Bãi Kỳ Co trong xanh\nĐiểm ngắm gió Eo Gió\nThưởng thức hải sản địa phương",
                    '_tour_itinerary' => "Ngày 1 - Bay đến Quy Nhơn, nhận phòng khu nghỉ và nghỉ ngơi\nNgày 2 - Đi tuyến đảo, ăn trưa hải sản và tắm biển\nNgày 3 - Uống cà phê sáng rồi trở về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhu nghỉ kèm ăn sáng\nXe đưa đón theo tuyến đảo",
                    '_tour_excludes' => "Trò chơi thể thao nước tự chọn\nĐồ ăn nhẹ cá nhân\nChi phí bồi dưỡng",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'hue-imperial-food-trail',
                'title' => 'Huế Cố Đô Ẩm Thực 3N2Đ',
                'price' => '3390000',
                'excerpt' => 'Nhịp khám phá cố đô với bếp Huế, buổi tối bên sông và các điểm văn hóa vừa đủ.',
                'content' => 'Phù hợp cho khách thích trải nghiệm văn hóa, món ăn địa phương và lịch trình gọn gàng trong thành phố di sản.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'HU-318',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'TP.HCM',
                    '_meeting_point' => 'Sân bay Tân Sơn Nhất',
                    '_available_departure_dates' => "2026-06-12\n2026-06-19\n2026-06-26",
                    '_tour_highlights' => "Buổi sáng ở Đại Nội\nHành trình món Huế\nBuổi tối bên sông Hương",
                    '_tour_itinerary' => "Ngày 1 - Bay đến Huế, nhận phòng và khám phá ẩm thực buổi tối\nNgày 2 - Tham quan Đại Nội và ăn trưa tại nhà vườn\nNgày 3 - Ghé chợ, mua quà nhẹ rồi bay về",
                    '_tour_includes' => "Vé máy bay khứ hồi\nKhách sạn kèm ăn sáng\nHành trình ẩm thực và hướng dẫn viên",
                    '_tour_excludes' => "Mua sắm cá nhân\nPhụ thu đi thuyền thêm giờ\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'cat-ba-lan-ha-adventure',
                'title' => 'Cát Bà - Lan Hạ Khám Phá 3N2Đ',
                'price' => '3990000',
                'excerpt' => 'Chèo thuyền, đi vịnh và trải nghiệm nhẹ nhàng với điểm nghỉ qua đêm thoải mái.',
                'content' => 'Lựa chọn biển phía Bắc cho khách thích vận động vừa phải nhưng vẫn muốn nghỉ ngơi dễ chịu.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'CB-327',
                    '_duration_text' => '3 ngày 2 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Phố cổ Hà Nội',
                    '_available_departure_dates' => "2026-06-13\n2026-06-20\n2026-06-27",
                    '_tour_highlights' => "Chèo thuyền ở vịnh Lan Hạ\nĐiểm nhìn toàn cảnh đảo\nBữa tối hải sản",
                    '_tour_itinerary' => "Ngày 1 - Di chuyển từ Hà Nội đến Cát Bà\nNgày 2 - Đi vịnh, chèo thuyền và dừng ở bãi tắm\nNgày 3 - Ăn sáng muộn rồi trở về",
                    '_tour_includes' => "Xe đưa đón khứ hồi\nKhách sạn kèm ăn sáng\nThuyền tham quan và chèo thuyền",
                    '_tour_excludes' => "Đồ uống cá nhân\nPhụ thu phòng đơn\nChi phí bồi dưỡng",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'moc-chau-tea-valley',
                'title' => 'Mộc Châu Đồi Chè 2N1Đ',
                'price' => '2190000',
                'excerpt' => 'Đồi chè, đồng cỏ và chuyến đi ngắn giúp đổi gió mát lành từ Hà Nội.',
                'content' => 'Lựa chọn gọn nhẹ cho khách muốn hít thở không khí cao nguyên mà không cần nghỉ quá dài ngày.',
                'destination' => 'ha-giang',
                'tour_style' => 'trekking-canh-quan',
                'meta' => [
                    '_tour_code' => 'MC-209',
                    '_duration_text' => '2 ngày 1 đêm',
                    '_departure_city' => 'Hà Nội',
                    '_meeting_point' => 'Nhà hát Lớn Hà Nội',
                    '_available_departure_dates' => "2026-06-14\n2026-06-21\n2026-06-28",
                    '_tour_highlights' => "Đi bộ giữa đồi chè\nCung chụp ảnh đồng cỏ\nBữa tối đậm vị địa phương",
                    '_tour_itinerary' => "Ngày 1 - Đi từ Hà Nội đến Mộc Châu, tham quan đồi chè và ăn tối\nNgày 2 - Dạo đồng cỏ buổi sáng rồi trở về",
                    '_tour_includes' => "Xe đưa đón khứ hồi\nKhách sạn kèm ăn sáng\nHướng dẫn viên và bữa tối",
                    '_tour_excludes' => "Cà phê cá nhân\nChi phí xe địa hình hoặc đạo cụ chụp ảnh\nThuế GTGT khi cần xuất hóa đơn",
                    '_gallery_ids' => '',
                ],
            ],
            [
                'slug' => 'sepay-test-tour',
                'title' => 'Hành Trình Kiểm Thử Thanh Toán 2K',
                'price' => '2000',
                'excerpt' => 'Sản phẩm giá thấp dùng để kiểm tra mã thanh toán và luồng xác nhận đơn mà không cần số tiền lớn.',
                'content' => 'Lịch trình mẫu gọn nhẹ phục vụ kiểm tra thanh toán ở môi trường nội bộ, vẫn giữ đúng luồng đặt chỗ của hệ thống bán tour.',
                'destination' => 'da-nang',
                'tour_style' => 'van-hoa-am-thuc',
                'meta' => [
                    '_tour_code' => 'SP-002',
                    '_duration_text' => '1 ngày',
                    '_departure_city' => 'Đà Nẵng',
                    '_meeting_point' => 'Trung tâm Đà Nẵng',
                    '_available_departure_dates' => "2026-06-30\n2026-07-07\n2026-07-14",
                    '_tour_highlights' => "Đơn giá thấp để kiểm tra thanh toán\nXác minh luồng gọi ngược của SePay\nThời gian kiểm tra nhanh",
                    '_tour_itinerary' => "Ngày 1 - Giữ chỗ nhanh, xác nhận dữ liệu đặt chỗ và hoàn tất kiểm tra thanh toán",
                    '_tour_includes' => "Giữ chỗ mẫu\nMã thanh toán thử nghiệm\nXác nhận đơn hàng",
                    '_tour_excludes' => "Phương tiện di chuyển\nBữa ăn\nHỗ trợ thủ công ngoài phạm vi kiểm tra",
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
                'title' => 'Ưu Đãi Mùa Hè Chọn Lọc',
                'excerpt' => 'Tặng bữa tối ngắm hoàng hôn cho nhóm đặt sớm.',
                'content' => 'Áp dụng cho các lịch trình biển khi đặt trước 21 ngày. Tư vấn viên xác nhận quà tặng khi chốt booking.',
            ],
            [
                'slug' => 'combo-gia-dinh-thang-5',
                'title' => 'Combo Gia Đình Tháng 5',
                'excerpt' => 'Ưu tiên khung phòng gia đình và xe riêng.',
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
                'content' => 'Điều mình thích nhất là tour không nhồi nhét điểm đến. Từ trang tour đến thanh toán đều cho cảm giác cao cấp và rõ ràng.',
            ],
            [
                'slug' => 'review-ha-giang-itinerary',
                'title' => 'Minh Anh, Hà Nội',
                'excerpt' => 'Luồng đặt chỗ rõ và yêu cầu riêng được ghi nhận ngay.',
                'content' => 'Mình có ghi chú về chế độ ăn và điểm đón. Khi vào trang quản trị đơn hàng nhìn rất rõ dữ liệu đặt chỗ nên cảm giác hệ thống khá chắc tay.',
            ],
        ];
    }
}
