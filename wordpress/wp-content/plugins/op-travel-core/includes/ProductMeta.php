<?php

namespace OPTravelCore;

final class ProductMeta
{
    private const FIELD_DEFINITIONS = [
        '_tour_code' => [
            'type' => 'text',
            'label' => 'MÃ£ tour',
        ],
        '_duration_text' => [
            'type' => 'text',
            'label' => 'Thá»i lÆ°á»£ng',
        ],
        '_departure_city' => [
            'type' => 'text',
            'label' => 'NÆ¡i khá»Ÿi hÃ nh',
        ],
        '_meeting_point' => [
            'type' => 'text',
            'label' => 'Äiá»ƒm háº¹n',
        ],
        '_available_departure_dates' => [
            'type' => 'textarea',
            'label' => 'NgÃ y khá»Ÿi hÃ nh',
            'description' => 'Má»—i ngÃ y má»™t dÃ²ng, Ä‘á»‹nh dáº¡ng YYYY-MM-DD.',
        ],
        '_tour_highlights' => [
            'type' => 'textarea',
            'label' => 'Äiá»ƒm nháº¥n hÃ nh trÃ¬nh',
            'description' => 'Má»—i dÃ²ng má»™t highlight hoáº·c nhÃ³m ná»™i dung ngáº¯n.',
        ],
        '_tour_itinerary' => [
            'type' => 'textarea',
            'label' => 'Lá»‹ch trÃ¬nh chi tiáº¿t',
            'description' => 'Má»—i dÃ²ng tÆ°Æ¡ng á»©ng vá»›i má»™t cháº·ng hoáº·c má»™t ngÃ y.',
        ],
        '_tour_includes' => [
            'type' => 'textarea',
            'label' => 'GiÃ¡ bao gá»“m',
            'description' => 'Má»—i dÃ²ng má»™t háº¡ng má»¥c bao gá»“m.',
        ],
        '_tour_excludes' => [
            'type' => 'textarea',
            'label' => 'GiÃ¡ khÃ´ng bao gá»“m',
            'description' => 'Má»—i dÃ²ng má»™t háº¡ng má»¥c khÃ´ng bao gá»“m.',
        ],
        '_gallery_ids' => [
            'type' => 'text',
            'label' => 'Gallery Attachment IDs',
            'description' => 'Nháº­p danh sÃ¡ch attachment ID, phÃ¢n tÃ¡ch báº±ng dáº¥u pháº©y.',
        ],
    ];

    public static function boot()
    {
        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'register_tab']);
        add_action('woocommerce_product_data_panels', [__CLASS__, 'render_panel']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save']);
    }

    public static function register_tab($tabs)
    {
        $tabs['op_travel_tour_meta'] = [
            'label' => __('ThÃ´ng tin tour', 'op-travel-core'),
            'target' => 'op_travel_tour_meta_panel',
            'class' => ['show_if_simple', 'show_if_variable'],
        ];

        return $tabs;
    }

    public static function render_panel()
    {
        echo '<div id="op_travel_tour_meta_panel" class="panel woocommerce_options_panel">';

        foreach (self::FIELD_DEFINITIONS as $meta_key => $field) {
            $args = [
                'id' => $meta_key,
                'label' => __($field['label'], 'op-travel-core'),
            ];

            if (! empty($field['description'])) {
                $args['description'] = __($field['description'], 'op-travel-core');
            }

            if ($field['type'] === 'textarea') {
                woocommerce_wp_textarea_input($args);
                continue;
            }

            woocommerce_wp_text_input($args);
        }

        echo '</div>';
    }

    public static function save($post_id)
    {
        foreach (array_keys(self::FIELD_DEFINITIONS) as $meta_key) {
            if (! isset($_POST[$meta_key])) {
                continue;
            }

            update_post_meta(
                $post_id,
                $meta_key,
                self::sanitize_meta_value($meta_key, wp_unslash($_POST[$meta_key]))
            );
        }
    }

    public static function get_product_tour_data($product_id)
    {
        return [
            'tour_code' => (string) get_post_meta($product_id, '_tour_code', true),
            'duration_text' => (string) get_post_meta($product_id, '_duration_text', true),
            'departure_city' => (string) get_post_meta($product_id, '_departure_city', true),
            'meeting_point' => (string) get_post_meta($product_id, '_meeting_point', true),
            'available_departure_dates' => self::get_multiline_values($product_id, '_available_departure_dates'),
            'highlights' => self::get_multiline_values($product_id, '_tour_highlights'),
            'itinerary' => self::get_multiline_values($product_id, '_tour_itinerary'),
            'includes' => self::get_multiline_values($product_id, '_tour_includes'),
            'excludes' => self::get_multiline_values($product_id, '_tour_excludes'),
            'gallery_ids' => self::get_gallery_ids($product_id),
        ];
    }

    public static function get_available_departure_dates($product_id)
    {
        return self::get_multiline_values($product_id, '_available_departure_dates');
    }

    public static function get_multiline_values($product_id, $meta_key)
    {
        $raw_value = (string) get_post_meta($product_id, $meta_key, true);
        $lines = preg_split('/\r\n|\r|\n/', $raw_value);

        if (! is_array($lines)) {
            return [];
        }

        $values = [];

        foreach ($lines as $line) {
            $line = trim(wp_strip_all_tags((string) $line));

            if ($line === '') {
                continue;
            }

            $values[] = $line;
        }

        return array_values(array_unique($values));
    }

    public static function get_gallery_ids($product_id)
    {
        $raw_value = (string) get_post_meta($product_id, '_gallery_ids', true);

        if ($raw_value === '') {
            return [];
        }

        $ids = array_map('absint', array_map('trim', explode(',', $raw_value)));
        $ids = array_filter($ids);

        return array_values(array_unique($ids));
    }

    private static function sanitize_meta_value($meta_key, $value)
    {
        if ($meta_key === '_gallery_ids') {
            return self::sanitize_gallery_ids($value);
        }

        if (in_array($meta_key, [
            '_available_departure_dates',
            '_tour_highlights',
            '_tour_itinerary',
            '_tour_includes',
            '_tour_excludes',
        ], true)) {
            return self::sanitize_multiline_text($value);
        }

        return sanitize_text_field((string) $value);
    }

    private static function sanitize_multiline_text($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);

        if (! is_array($lines)) {
            return '';
        }

        $sanitized = [];

        foreach ($lines as $line) {
            $line = sanitize_text_field((string) $line);

            if ($line === '') {
                continue;
            }

            $sanitized[] = $line;
        }

        return implode("\n", $sanitized);
    }

    private static function sanitize_gallery_ids($value)
    {
        $ids = array_map('absint', array_map('trim', explode(',', (string) $value)));
        $ids = array_filter($ids);

        if (empty($ids)) {
            return '';
        }

        return implode(',', array_values(array_unique($ids)));
    }
}
