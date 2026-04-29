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
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'render_admin_order_booking_panel']);
    }

    public static function render_fields()
    {
        global $product;

        if (! $product || ! method_exists($product, 'get_id')) {
            return;
        }

        $tour_data = ProductMeta::get_product_tour_data($product->get_id());
        $available_departure_dates = $tour_data['available_departure_dates'];
        $selected_departure_date = isset($_POST['op_departure_date']) ? sanitize_text_field(wp_unslash($_POST['op_departure_date'])) : '';

        wp_nonce_field('op_travel_booking', 'op_travel_booking_nonce');
        ?>
        <section class="op-booking-fields">
            <h3><?php esc_html_e('XÃ¡c nháº­n giá»¯ chá»—', 'op-travel-core'); ?></h3>
            <p><?php esc_html_e('Chá»n ngÃ y khá»Ÿi hÃ nh vÃ  sá»‘ lÆ°á»£ng khÃ¡ch trÆ°á»›c khi giá»¯ chá»—.', 'op-travel-core'); ?></p>
            <?php if (! empty($tour_data['tour_code'])) : ?>
                <p><strong><?php esc_html_e('MÃ£ tour', 'op-travel-core'); ?>:</strong> <?php echo esc_html($tour_data['tour_code']); ?></p>
            <?php endif; ?>
            <p class="form-row form-row-first">
                <label for="op_departure_date"><?php esc_html_e('NgÃ y khá»Ÿi hÃ nh', 'op-travel-core'); ?></label>
                <select id="op_departure_date" name="op_departure_date" required>
                    <option value=""><?php esc_html_e('Chá»n má»™t lá»‹ch khá»Ÿi hÃ nh', 'op-travel-core'); ?></option>
                    <?php foreach ($available_departure_dates as $departure_date) : ?>
                        <option value="<?php echo esc_attr($departure_date); ?>" <?php selected($selected_departure_date, $departure_date); ?>>
                            <?php echo esc_html(self::format_departure_date($departure_date)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($available_departure_dates)) : ?>
                    <small><?php esc_html_e('Tour nÃ y chÆ°a Ä‘Æ°á»£c khai bÃ¡o lá»‹ch khá»Ÿi hÃ nh. HÃ£y cáº­p nháº­t product meta trÆ°á»›c khi nháº­n booking.', 'op-travel-core'); ?></small>
                <?php endif; ?>
            </p>
            <p class="form-row form-row-first">
                <label for="op_adult_count"><?php esc_html_e('NgÆ°á»i lá»›n', 'op-travel-core'); ?></label>
                <input type="number" id="op_adult_count" name="op_adult_count" min="1" value="<?php echo esc_attr(isset($_POST['op_adult_count']) ? absint(wp_unslash($_POST['op_adult_count'])) : 1); ?>" required />
            </p>
            <p class="form-row form-row-last">
                <label for="op_child_count"><?php esc_html_e('Tráº» em', 'op-travel-core'); ?></label>
                <input type="number" id="op_child_count" name="op_child_count" min="0" value="<?php echo esc_attr(isset($_POST['op_child_count']) ? absint(wp_unslash($_POST['op_child_count'])) : 0); ?>" />
            </p>
            <p class="form-row form-row-wide">
                <label for="op_customer_note"><?php esc_html_e('Ghi chÃº thÃªm', 'op-travel-core'); ?></label>
                <textarea id="op_customer_note" name="op_customer_note" rows="4" placeholder="<?php esc_attr_e('Ä‚n chay, Ä‘Ã³n táº¡i Quáº­n 1, yÃªu cáº§u Ä‘áº·c biá»‡t...', 'op-travel-core'); ?>"><?php echo isset($_POST['op_customer_note']) ? esc_textarea(wp_unslash($_POST['op_customer_note'])) : ''; ?></textarea>
            </p>
        </section>
        <?php
    }

    public static function validate($passed, $product_id, $quantity)
    {
        if (! isset($_POST['op_travel_booking_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['op_travel_booking_nonce']), 'op_travel_booking')) {
            wc_add_notice(__('PhiÃªn giá»¯ chá»— khÃ´ng há»£p lá»‡. Vui lÃ²ng thá»­ láº¡i.', 'op-travel-core'), 'error');
            return false;
        }

        $available_departure_dates = ProductMeta::get_available_departure_dates($product_id);
        $departure_date = isset($_POST['op_departure_date']) ? sanitize_text_field(wp_unslash($_POST['op_departure_date'])) : '';
        $adult_count = isset($_POST['op_adult_count']) ? absint(wp_unslash($_POST['op_adult_count'])) : 0;

        if (empty($available_departure_dates)) {
            wc_add_notice(__('Tour nÃ y chÆ°a cÃ³ lá»‹ch khá»Ÿi hÃ nh há»£p lá»‡. Vui lÃ²ng cáº­p nháº­t metadata tour.', 'op-travel-core'), 'error');
            return false;
        }

        if ($departure_date === '' || ! in_array($departure_date, $available_departure_dates, true)) {
            wc_add_notice(__('Vui lÃ²ng chá»n má»™t ngÃ y khá»Ÿi hÃ nh cÃ³ sáºµn trong danh sÃ¡ch.', 'op-travel-core'), 'error');
            return false;
        }

        if ($adult_count < 1) {
            wc_add_notice(__('Pháº£i cÃ³ Ã­t nháº¥t má»™t ngÆ°á»i lá»›n cho má»—i booking.', 'op-travel-core'), 'error');
            return false;
        }

        return $passed;
    }

    public static function capture_cart_item_data($cart_item_data, $product_id)
    {
        $booking = self::build_booking_snapshot($product_id);

        $cart_item_data[OrderMeta::BOOKING_DATA] = $booking;
        $cart_item_data['op_travel_booking_hash'] = md5(wp_json_encode($booking));

        return $cart_item_data;
    }

    public static function display_cart_item_data($item_data, $cart_item)
    {
        if (empty($cart_item[OrderMeta::BOOKING_DATA])) {
            return $item_data;
        }

        $booking = OrderMeta::normalize_booking_snapshot($cart_item[OrderMeta::BOOKING_DATA]);

        if ($booking['tour_code'] !== '') {
            $item_data[] = [
                'key' => __('MÃ£ tour', 'op-travel-core'),
                'value' => esc_html($booking['tour_code']),
            ];
        }

        $item_data[] = [
            'key' => __('NgÃ y khá»Ÿi hÃ nh', 'op-travel-core'),
            'value' => esc_html(self::format_departure_date($booking['departure_date'])),
        ];
        $item_data[] = [
            'key' => __('NgÆ°á»i lá»›n', 'op-travel-core'),
            'value' => esc_html((string) $booking['adult_count']),
        ];
        $item_data[] = [
            'key' => __('Tráº» em', 'op-travel-core'),
            'value' => esc_html((string) $booking['child_count']),
        ];

        if (! empty($booking['customer_note'])) {
            $item_data[] = [
                'key' => __('Ghi chÃº', 'op-travel-core'),
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

        $booking = OrderMeta::normalize_booking_snapshot($values[OrderMeta::BOOKING_DATA]);
        $line_total = method_exists($item, 'get_total') ? $item->get_total() : '';

        if ($line_total !== '') {
            $booking['amount'] = wc_format_decimal((string) $line_total, 2);
        }

        if ($booking['tour_code'] !== '') {
            $item->add_meta_data(__('MÃ£ tour', 'op-travel-core'), $booking['tour_code'], true);
        }

        $item->add_meta_data(__('TÃªn tour', 'op-travel-core'), $booking['tour_name'], true);
        $item->add_meta_data(__('NgÃ y khá»Ÿi hÃ nh', 'op-travel-core'), self::format_departure_date($booking['departure_date']), true);
        $item->add_meta_data(__('NgÆ°á»i lá»›n', 'op-travel-core'), $booking['adult_count'], true);
        $item->add_meta_data(__('Tráº» em', 'op-travel-core'), $booking['child_count'], true);
        $item->add_meta_data(__('GiÃ¡ booking', 'op-travel-core'), $booking['amount'], true);
        $item->add_meta_data(__('payment_status', 'op-travel-core'), $booking['payment_status'], true);

        if ($booking['customer_note'] !== '') {
            $item->add_meta_data(__('Ghi chÃº', 'op-travel-core'), $booking['customer_note'], true);
        }

        $item->add_meta_data(OrderMeta::BOOKING_DATA, wp_json_encode($booking), true);

        OrderMeta::append_booking_snapshot($order, $booking);

        if (! $order->get_meta(OrderMeta::PAYMENT_STATE, true)) {
            $order->update_meta_data(OrderMeta::PAYMENT_STATE, 'pending');
        }
    }

    public static function render_admin_order_booking_panel($order)
    {
        $bookings = OrderMeta::get_booking_snapshots($order);

        if (empty($bookings)) {
            return;
        }

        $payment_status = $order->get_meta(OrderMeta::PAYMENT_STATE, true) ?: 'pending';
        ?>
        <div class="order_data_column" style="width:100%;">
            <h3><?php esc_html_e('OP Travel Booking Snapshot', 'op-travel-core'); ?></h3>
            <p><strong><?php esc_html_e('payment_status', 'op-travel-core'); ?>:</strong> <?php echo esc_html($payment_status); ?></p>
            <?php foreach ($bookings as $index => $booking) : ?>
                <div style="margin:12px 0;padding:12px;border:1px solid #dcdcde;border-radius:8px;background:#fff;">
                    <p><strong><?php echo esc_html(sprintf(__('Booking #%d', 'op-travel-core'), $index + 1)); ?></strong></p>
                    <p><strong><?php esc_html_e('tour_name', 'op-travel-core'); ?>:</strong> <?php echo esc_html($booking['tour_name']); ?></p>
                    <p><strong><?php esc_html_e('tour_code', 'op-travel-core'); ?>:</strong> <?php echo esc_html($booking['tour_code']); ?></p>
                    <p><strong><?php esc_html_e('departure_date', 'op-travel-core'); ?>:</strong> <?php echo esc_html(self::format_departure_date($booking['departure_date'])); ?></p>
                    <p><strong><?php esc_html_e('KhÃ¡ch', 'op-travel-core'); ?>:</strong> <?php echo esc_html(sprintf('%d ngÆ°á»i lá»›n, %d tráº» em', $booking['adult_count'], $booking['child_count'])); ?></p>
                    <p><strong><?php esc_html_e('amount', 'op-travel-core'); ?>:</strong> <?php echo esc_html($booking['amount']); ?></p>
                    <p><strong><?php esc_html_e('payment_status', 'op-travel-core'); ?>:</strong> <?php echo esc_html($booking['payment_status']); ?></p>
                    <?php if ($booking['customer_note'] !== '') : ?>
                        <p><strong><?php esc_html_e('customer_note', 'op-travel-core'); ?>:</strong> <?php echo nl2br(esc_html($booking['customer_note'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private static function build_booking_snapshot($product_id)
    {
        $product = wc_get_product($product_id);
        $tour_data = ProductMeta::get_product_tour_data($product_id);

        return OrderMeta::normalize_booking_snapshot([
            'departure_date' => isset($_POST['op_departure_date']) ? sanitize_text_field(wp_unslash($_POST['op_departure_date'])) : '',
            'adult_count' => isset($_POST['op_adult_count']) ? absint(wp_unslash($_POST['op_adult_count'])) : 1,
            'child_count' => isset($_POST['op_child_count']) ? absint(wp_unslash($_POST['op_child_count'])) : 0,
            'customer_note' => isset($_POST['op_customer_note']) ? sanitize_textarea_field(wp_unslash($_POST['op_customer_note'])) : '',
            'tour_code' => $tour_data['tour_code'],
            'tour_name' => $product ? $product->get_name() : get_the_title($product_id),
            'amount' => $product ? $product->get_price() : '',
            'payment_status' => 'pending',
        ]);
    }

    private static function format_departure_date($departure_date)
    {
        $timestamp = strtotime((string) $departure_date);

        if (! $timestamp) {
            return (string) $departure_date;
        }

        return wp_date(get_option('date_format'), $timestamp);
    }
}
