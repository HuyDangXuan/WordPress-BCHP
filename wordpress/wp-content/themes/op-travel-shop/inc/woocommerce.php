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
    return __('Đặt tour ngay', 'op-travel-shop');
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
