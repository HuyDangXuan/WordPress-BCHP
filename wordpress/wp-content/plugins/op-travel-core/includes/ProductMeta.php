<?php

namespace OPTravelCore;

final class ProductMeta
{
    public static function boot()
    {
        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'register_tab']);
        add_action('woocommerce_product_data_panels', [__CLASS__, 'render_panel']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save']);
    }

    public static function register_tab($tabs)
    {
        $tabs['op_travel_tour_meta'] = [
            'label' => __('Thông tin tour', 'op-travel-core'),
            'target' => 'op_travel_tour_meta_panel',
            'class' => ['show_if_simple', 'show_if_variable'],
        ];

        return $tabs;
    }

    public static function render_panel()
    {
        echo '<div id="op_travel_tour_meta_panel" class="panel woocommerce_options_panel">';

        woocommerce_wp_text_input([
            'id' => '_tour_code',
            'label' => __('Mã tour', 'op-travel-core'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_duration_text',
            'label' => __('Thời lượng', 'op-travel-core'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_departure_city',
            'label' => __('Nơi khởi hành', 'op-travel-core'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_meeting_point',
            'label' => __('Điểm hẹn', 'op-travel-core'),
        ]);

        woocommerce_wp_textarea_input([
            'id' => '_available_departure_dates',
            'label' => __('Ngày khởi hành', 'op-travel-core'),
            'description' => __('Mỗi ngày một dòng, định dạng YYYY-MM-DD.', 'op-travel-core'),
        ]);

        woocommerce_wp_textarea_input([
            'id' => '_tour_highlights',
            'label' => __('Điểm nhấn hành trình', 'op-travel-core'),
        ]);

        echo '</div>';
    }

    public static function save($post_id)
    {
        $keys = [
            '_tour_code',
            '_duration_text',
            '_departure_city',
            '_meeting_point',
            '_available_departure_dates',
            '_tour_highlights',
        ];

        foreach ($keys as $key) {
            if (! isset($_POST[$key])) {
                continue;
            }

            update_post_meta($post_id, $key, wp_kses_post(wp_unslash($_POST[$key])));
        }
    }
}
