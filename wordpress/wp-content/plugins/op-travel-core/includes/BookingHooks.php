<?php

namespace OPTravelCore;

use OPTravelCore\Support\OrderMeta;

final class BookingHooks
{
    public static function boot()
    {
        add_action('woocommerce_before_add_to_cart_button', [__CLASS__, 'render_fields']);
        add_filter('woocommerce_add_to_cart_validation', [__CLASS__, 'validate'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [__CLASS__, 'capture_cart_item_data'], 10, 2);
        add_filter('woocommerce_get_item_data', [__CLASS__, 'display_cart_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'add_order_item_meta'], 10, 4);
    }

    public static function render_fields()
    {
        wp_nonce_field('op_travel_booking', 'op_travel_booking_nonce');
        ?>
        <section class="op-booking-fields">
            <h3><?php esc_html_e('Xác nhận giữ chỗ', 'op-travel-core'); ?></h3>
            <p><?php esc_html_e('Chọn ngày khởi hành và số lượng khách trước khi giữ chỗ.', 'op-travel-core'); ?></p>
            <p class="form-row form-row-first">
                <label for="op_departure_date"><?php esc_html_e('Ngày khởi hành', 'op-travel-core'); ?></label>
                <input type="date" id="op_departure_date" name="op_departure_date" required />
            </p>
            <p class="form-row form-row-first">
                <label for="op_adult_count"><?php esc_html_e('Người lớn', 'op-travel-core'); ?></label>
                <input type="number" id="op_adult_count" name="op_adult_count" min="1" value="1" required />
            </p>
            <p class="form-row form-row-last">
                <label for="op_child_count"><?php esc_html_e('Trẻ em', 'op-travel-core'); ?></label>
                <input type="number" id="op_child_count" name="op_child_count" min="0" value="0" />
            </p>
            <p class="form-row form-row-wide">
                <label for="op_customer_note"><?php esc_html_e('Ghi chú thêm', 'op-travel-core'); ?></label>
                <textarea id="op_customer_note" name="op_customer_note" rows="4" placeholder="<?php esc_attr_e('Ăn chay, đón tại Quận 1, yêu cầu đặc biệt...', 'op-travel-core'); ?>"></textarea>
            </p>
        </section>
        <?php
    }

    public static function validate($passed, $product_id, $quantity)
    {
        if (! isset($_POST['op_travel_booking_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['op_travel_booking_nonce']), 'op_travel_booking')) {
            wc_add_notice(__('Phiên giữ chỗ không hợp lệ. Vui lòng thử lại.', 'op-travel-core'), 'error');
            return false;
        }

        $departure_date = isset($_POST['op_departure_date']) ? sanitize_text_field(wp_unslash($_POST['op_departure_date'])) : '';
        $adult_count = isset($_POST['op_adult_count']) ? absint(wp_unslash($_POST['op_adult_count'])) : 0;

        if ($departure_date === '') {
            wc_add_notice(__('Vui lòng chọn ngày khởi hành.', 'op-travel-core'), 'error');
            return false;
        }

        if ($adult_count < 1) {
            wc_add_notice(__('Phải có ít nhất một người lớn cho mỗi booking.', 'op-travel-core'), 'error');
            return false;
        }

        return $passed;
    }

    public static function capture_cart_item_data($cart_item_data, $product_id)
    {
        $cart_item_data[OrderMeta::BOOKING_DATA] = [
            'departure_date' => isset($_POST['op_departure_date']) ? sanitize_text_field(wp_unslash($_POST['op_departure_date'])) : '',
            'adult_count' => isset($_POST['op_adult_count']) ? absint(wp_unslash($_POST['op_adult_count'])) : 1,
            'child_count' => isset($_POST['op_child_count']) ? absint(wp_unslash($_POST['op_child_count'])) : 0,
            'customer_note' => isset($_POST['op_customer_note']) ? sanitize_textarea_field(wp_unslash($_POST['op_customer_note'])) : '',
        ];

        return $cart_item_data;
    }

    public static function display_cart_item_data($item_data, $cart_item)
    {
        if (empty($cart_item[OrderMeta::BOOKING_DATA])) {
            return $item_data;
        }

        $booking = $cart_item[OrderMeta::BOOKING_DATA];

        $item_data[] = [
            'key' => __('Ngày khởi hành', 'op-travel-core'),
            'value' => esc_html($booking['departure_date']),
        ];
        $item_data[] = [
            'key' => __('Người lớn', 'op-travel-core'),
            'value' => esc_html((string) $booking['adult_count']),
        ];
        $item_data[] = [
            'key' => __('Trẻ em', 'op-travel-core'),
            'value' => esc_html((string) $booking['child_count']),
        ];

        if (! empty($booking['customer_note'])) {
            $item_data[] = [
                'key' => __('Ghi chú', 'op-travel-core'),
                'value' => esc_html($booking['customer_note']),
            ];
        }

        return $item_data;
    }

    public static function add_order_item_meta($item, $cart_item_key, $values, $order)
    {
        if (empty($values[OrderMeta::BOOKING_DATA])) {
            return;
        }

        $booking = $values[OrderMeta::BOOKING_DATA];

        $item->add_meta_data(__('Ngày khởi hành', 'op-travel-core'), $booking['departure_date'], true);
        $item->add_meta_data(__('Người lớn', 'op-travel-core'), $booking['adult_count'], true);
        $item->add_meta_data(__('Trẻ em', 'op-travel-core'), $booking['child_count'], true);
        $item->add_meta_data(__('Ghi chú', 'op-travel-core'), $booking['customer_note'], true);
        $item->add_meta_data(OrderMeta::BOOKING_DATA, wp_json_encode($booking), true);

        $order->update_meta_data(OrderMeta::BOOKING_DATA, $booking);
        $order->update_meta_data(OrderMeta::PAYMENT_STATE, 'pending');
    }
}
