<?php

if (! defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_enqueue_styles', '__return_empty_array');
add_filter('loop_shop_per_page', 'op_travel_loop_shop_per_page');
add_filter('woocommerce_product_add_to_cart_text', 'op_travel_add_to_cart_text');
add_filter('woocommerce_product_single_add_to_cart_text', 'op_travel_add_to_cart_text');
add_filter('woocommerce_get_availability_text', 'op_travel_availability_text', 10, 2);
add_filter('woocommerce_add_to_cart_redirect', 'op_travel_redirect_booking_add_to_cart');
add_filter('woocommerce_available_payment_gateways', 'op_travel_sort_payment_gateways');
add_filter('woocommerce_account_menu_items', 'op_travel_filter_account_menu_items');
add_filter('woocommerce_account_menu_item_classes', 'op_travel_filter_account_menu_item_classes', 10, 2);
add_action('pre_get_posts', 'op_travel_filter_product_archive');
add_action('template_redirect', 'op_travel_redirect_hidden_account_endpoints');

function op_travel_loop_shop_per_page()
{
    return 6;
}

function op_travel_add_to_cart_text()
{
    return __('Đặt tour ngay', 'op-travel-shop');
}

function op_travel_redirect_booking_add_to_cart($url)
{
    if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || empty($_POST['add-to-cart'])) {
        return $url;
    }

    $has_booking_context = isset($_POST['op_travel_booking_nonce'])
        || isset($_POST['op_departure_date'])
        || isset($_POST['op_adult_count'])
        || isset($_POST['op_child_count'])
        || isset($_POST['op_customer_note']);

    if (! $has_booking_context) {
        return $url;
    }

    return function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');
}

function op_travel_availability_text($text, $product)
{
    if ($product && $product->is_in_stock()) {
        return __('Còn chỗ cho lịch khởi hành gần nhất', 'op-travel-shop');
    }

    return $text;
}

function op_travel_sort_payment_gateways($gateways)
{
    if (! is_array($gateways)) {
        return $gateways;
    }

    $priority = ['op_travel_sepay_qr', 'mpay_up_vnpay', 'bacs'];
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

function op_travel_get_selected_checkout_cart_item_key($cart_items = null)
{
    if (! is_array($cart_items)) {
        $cart_items = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];
    }

    if (empty($cart_items)) {
        return '';
    }

    $selected_key = '';

    if (function_exists('WC') && WC()->session) {
        $session_value = WC()->session->get('op_travel_selected_checkout_cart_item_key');
        if (is_string($session_value)) {
            $selected_key = $session_value;
        }
    }

    if ($selected_key !== '' && isset($cart_items[$selected_key])) {
        return $selected_key;
    }

    $keys = array_keys($cart_items);

    return isset($keys[0]) ? (string) $keys[0] : '';
}

function op_travel_get_cart_item_total_html($cart_item)
{
    if (! is_array($cart_item)) {
        return '';
    }

    $line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : 0;
    $line_tax = isset($cart_item['line_tax']) ? (float) $cart_item['line_tax'] : 0;

    if ($line_total > 0 || $line_tax > 0) {
        return wc_price($line_total + $line_tax);
    }

    $product = $cart_item['data'] ?? null;
    $quantity = max(1, absint($cart_item['quantity'] ?? 1));

    if ($product instanceof WC_Product && function_exists('WC') && WC()->cart) {
        return WC()->cart->get_product_subtotal($product, $quantity);
    }

    return '';
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

function op_travel_get_account_url()
{
    return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
}

function op_travel_filter_account_menu_items($items)
{
    return [
        'dashboard' => __('Tổng quan', 'op-travel-shop'),
        'orders' => __('Booking của tôi', 'op-travel-shop'),
        'edit-address' => __('Thông tin liên hệ', 'op-travel-shop'),
        'edit-account' => __('Hồ sơ', 'op-travel-shop'),
        'customer-logout' => __('Đăng xuất', 'op-travel-shop'),
    ];
}

function op_travel_filter_account_menu_item_classes($classes, $endpoint)
{
    $classes = is_array($classes) ? $classes : [];
    $classes[] = 'op-account-nav__item';
    $classes[] = sprintf('op-account-nav__item--%s', sanitize_html_class((string) $endpoint));

    if (function_exists('wc_is_current_account_menu_item') && wc_is_current_account_menu_item($endpoint)) {
        $classes[] = 'is-active';
    }

    return array_values(array_unique($classes));
}

function op_travel_redirect_hidden_account_endpoints()
{
    if (! function_exists('is_account_page') || ! is_account_page() || ! function_exists('is_wc_endpoint_url')) {
        return;
    }

    $hidden_endpoints = ['downloads', 'payment-methods'];

    foreach ($hidden_endpoints as $endpoint) {
        if (! is_wc_endpoint_url($endpoint)) {
            continue;
        }

        wp_safe_redirect(op_travel_get_account_url());
        exit;
    }
}

function op_travel_get_account_user_summary($user = null)
{
    if ($user instanceof WP_User) {
        $account_user = $user;
    } elseif (is_numeric($user) && (int) $user > 0) {
        $account_user = get_user_by('id', (int) $user);
    } else {
        $account_user = wp_get_current_user();
    }

    if (! $account_user instanceof WP_User || ! $account_user->exists()) {
        return [
            'display_name' => __('Khách HV-Travel', 'op-travel-shop'),
            'secondary_label' => '',
            'initials' => 'HV',
            'account_url' => op_travel_get_account_url(),
            'orders_url' => function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', op_travel_get_account_url()) : op_travel_get_account_url(),
            'logout_url' => wp_logout_url(home_url('/')),
        ];
    }

    $preferred_name = trim((string) $account_user->display_name);
    $full_name = trim(sprintf('%s %s', (string) $account_user->first_name, (string) $account_user->last_name));

    if ($full_name !== '') {
        $preferred_name = $full_name;
    }

    if ($preferred_name === '') {
        $preferred_name = (string) $account_user->user_login;
    }

    $secondary_label = (string) $account_user->user_email;

    if ($secondary_label === '') {
        $secondary_label = (string) $account_user->user_login;
    }

    return [
        'display_name' => $preferred_name,
        'secondary_label' => $secondary_label,
        'initials' => op_travel_get_account_user_initials($preferred_name),
        'account_url' => op_travel_get_account_url(),
        'orders_url' => function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', op_travel_get_account_url()) : op_travel_get_account_url(),
        'logout_url' => wp_logout_url(home_url('/')),
    ];
}

function op_travel_get_account_user_initials($label)
{
    $label = trim(wp_strip_all_tags((string) $label));

    if ($label === '') {
        return 'HV';
    }

    $parts = preg_split('/\s+/u', $label);

    if (! is_array($parts) || empty($parts)) {
        return strtoupper(substr($label, 0, 2));
    }

    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part === '') {
            continue;
        }

        if (function_exists('mb_substr')) {
            $initials .= mb_substr($part, 0, 1);
            continue;
        }

        $initials .= substr($part, 0, 1);
    }

    if ($initials === '') {
        $initials = function_exists('mb_substr') ? mb_substr($label, 0, 2) : substr($label, 0, 2);
    }

    return function_exists('mb_strtoupper') ? mb_strtoupper($initials) : strtoupper($initials);
}

function op_travel_get_recent_account_orders($user_id = 0, $limit = 3)
{
    if (! function_exists('wc_get_orders')) {
        return [];
    }

    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if ($user_id <= 0) {
        return [];
    }

    $order_limit = -1 === (int) $limit ? -1 : max(1, absint($limit));

    return wc_get_orders([
        'customer_id' => $user_id,
        'limit' => $order_limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => array_keys(wc_get_order_statuses()),
    ]);
}

function op_travel_get_account_status_label($state)
{
    $labels = [
        'pending' => __('Chờ thanh toán', 'op-travel-shop'),
        'paid' => __('Đã xác nhận', 'op-travel-shop'),
        'failed' => __('Thanh toán lỗi', 'op-travel-shop'),
        'expired' => __('Hết hạn', 'op-travel-shop'),
        'cancelled' => __('Đã hủy', 'op-travel-shop'),
    ];

    return $labels[$state] ?? ucfirst((string) $state);
}

function op_travel_build_account_order_card($order)
{
    if (! $order instanceof WC_Order) {
        return [];
    }

    $bookings = op_travel_get_order_booking_snapshots($order);
    $primary_booking = $bookings[0] ?? [];
    $payment_state = op_travel_get_payment_state($order);
    $account_url = op_travel_get_account_url();
    $view_url = function_exists('wc_get_endpoint_url')
        ? wc_get_endpoint_url('view-order', $order->get_id(), $account_url)
        : $account_url;

    $primary_action_url = $view_url;
    $primary_action_label = __('Xem chi tiết booking', 'op-travel-shop');

    if ($order->needs_payment()) {
        $primary_action_url = $order->get_checkout_payment_url();
        $primary_action_label = __('Tiếp tục thanh toán', 'op-travel-shop');
    }

    return [
        'order' => $order,
        'order_id' => $order->get_id(),
        'order_number' => $order->get_order_number(),
        'view_url' => $view_url,
        'primary_action_url' => $primary_action_url,
        'primary_action_label' => $primary_action_label,
        'payment_state' => $payment_state,
        'payment_state_label' => op_travel_get_account_status_label($payment_state),
        'created_at' => $order->get_date_created(),
        'total_html' => $order->get_formatted_order_total(),
        'bookings' => $bookings,
        'booking_name' => sanitize_text_field((string) ($primary_booking['tour_name'] ?? '')),
        'tour_code' => sanitize_text_field((string) ($primary_booking['tour_code'] ?? '')),
        'departure_date' => sanitize_text_field((string) ($primary_booking['departure_date'] ?? '')),
        'guest_summary' => sprintf(
            __('%d người lớn, %d trẻ em', 'op-travel-shop'),
            max(1, absint($primary_booking['adult_count'] ?? 1)),
            max(0, absint($primary_booking['child_count'] ?? 0))
        ),
        'customer_note' => sanitize_textarea_field((string) ($primary_booking['customer_note'] ?? '')),
        'booking_count' => count($bookings),
    ];
}

function op_travel_get_account_dashboard_metrics($user_id = 0)
{
    $orders = op_travel_get_recent_account_orders($user_id, 12);
    $metrics = [
        'total' => 0,
        'pending' => 0,
        'paid' => 0,
    ];

    foreach ($orders as $order) {
        if (! $order instanceof WC_Order) {
            continue;
        }

        $metrics['total']++;

        $state = op_travel_get_payment_state($order);

        if ($state === 'paid') {
            $metrics['paid']++;
            continue;
        }

        if (in_array($state, ['pending', 'failed', 'expired'], true)) {
            $metrics['pending']++;
        }
    }

    return $metrics;
}
