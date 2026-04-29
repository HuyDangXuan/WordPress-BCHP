<?php

if (! defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_enqueue_styles', '__return_empty_array');
add_filter('loop_shop_per_page', 'op_travel_loop_shop_per_page');
add_filter('woocommerce_product_add_to_cart_text', 'op_travel_add_to_cart_text');
add_filter('woocommerce_get_availability_text', 'op_travel_availability_text', 10, 2);
add_filter('woocommerce_available_payment_gateways', 'op_travel_sort_payment_gateways');
add_action('pre_get_posts', 'op_travel_filter_product_archive');

function op_travel_loop_shop_per_page()
{
    return 6;
}

function op_travel_add_to_cart_text()
{
    return __('Äáº·t tour ngay', 'op-travel-shop');
}

function op_travel_availability_text($text, $product)
{
    if ($product && $product->is_in_stock()) {
        return __('CÃ²n chá»— cho lá»‹ch khá»Ÿi hÃ nh gáº§n nháº¥t', 'op-travel-shop');
    }

    return $text;
}

function op_travel_sort_payment_gateways($gateways)
{
    if (! is_array($gateways)) {
        return $gateways;
    }

    $priority = ['mpay_up_vnpay', 'bacs'];
    $sorted = [];

    foreach ($priority as $gateway_id) {
        if (isset($gateways[$gateway_id])) {
            $sorted[$gateway_id] = $gateways[$gateway_id];
            unset($gateways[$gateway_id]);
        }
    }

    return array_merge($sorted, $gateways);
}

function op_travel_filter_product_archive($query)
{
    if (is_admin() || ! $query->is_main_query() || ! function_exists('is_shop')) {
        return;
    }

    if (! is_shop() && ! is_post_type_archive('product') && ! is_tax(['destination', 'tour_style'])) {
        return;
    }

    $tax_query = [];

    if (! empty($_GET['destination'])) {
        $tax_query[] = [
            'taxonomy' => 'destination',
            'field' => 'slug',
            'terms' => sanitize_title(wp_unslash($_GET['destination'])),
        ];
    }

    if (! empty($_GET['tour_style'])) {
        $tax_query[] = [
            'taxonomy' => 'tour_style',
            'field' => 'slug',
            'terms' => sanitize_title(wp_unslash($_GET['tour_style'])),
        ];
    }

    if (! empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }
}

function op_travel_get_payment_state($order)
{
    if (! $order) {
        return 'pending';
    }

    $state = $order->get_meta('_op_travel_payment_state');

    if ($state) {
        return $state;
    }

    $status = $order->get_status();
    if (in_array($status, ['processing', 'completed'], true)) {
        return 'paid';
    }

    if (in_array($status, ['failed'], true)) {
        return 'failed';
    }

    if (in_array($status, ['cancelled'], true)) {
        return 'cancelled';
    }

    return 'pending';
}

function op_travel_get_multiline_meta_values($product_id, $meta_key)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) get_post_meta($product_id, $meta_key, true));

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

function op_travel_get_product_gallery_ids($product_id)
{
    $raw_value = (string) get_post_meta($product_id, '_gallery_ids', true);

    if ($raw_value === '') {
        return [];
    }

    $ids = array_map('absint', array_map('trim', explode(',', $raw_value)));

    return array_values(array_filter($ids));
}

function op_travel_normalize_booking_snapshot($booking)
{
    $booking = is_array($booking) ? $booking : [];

    return [
        'departure_date' => sanitize_text_field((string) ($booking['departure_date'] ?? '')),
        'adult_count' => max(1, absint($booking['adult_count'] ?? 1)),
        'child_count' => max(0, absint($booking['child_count'] ?? 0)),
        'customer_note' => sanitize_textarea_field((string) ($booking['customer_note'] ?? '')),
        'tour_code' => sanitize_text_field((string) ($booking['tour_code'] ?? '')),
        'tour_name' => sanitize_text_field((string) ($booking['tour_name'] ?? '')),
        'amount' => (string) ($booking['amount'] ?? ''),
        'payment_status' => sanitize_text_field((string) ($booking['payment_status'] ?? 'pending')),
    ];
}

function op_travel_get_cart_booking_snapshot($cart_item)
{
    if (empty($cart_item['_op_travel_booking_data']) || ! is_array($cart_item['_op_travel_booking_data'])) {
        return null;
    }

    return op_travel_normalize_booking_snapshot($cart_item['_op_travel_booking_data']);
}

function op_travel_get_order_booking_snapshots($order)
{
    if (! $order) {
        return [];
    }

    $bookings = $order->get_meta('_op_travel_booking_data', true);

    if (! is_array($bookings)) {
        return [];
    }

    if (isset($bookings['departure_date'])) {
        return [op_travel_normalize_booking_snapshot($bookings)];
    }

    $normalized = [];

    foreach ($bookings as $booking) {
        if (! is_array($booking)) {
            continue;
        }

        $normalized[] = op_travel_normalize_booking_snapshot($booking);
    }

    return $normalized;
}

function op_travel_format_departure_date($date)
{
    $timestamp = strtotime((string) $date);

    if (! $timestamp) {
        return (string) $date;
    }

    return wp_date(get_option('date_format'), $timestamp);
}
