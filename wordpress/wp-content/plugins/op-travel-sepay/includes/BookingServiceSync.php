<?php

namespace OPTravelSePay;

use OPTravelCore\Support\Env;
use OPTravelCore\Support\OrderMeta;

final class BookingServiceSync
{
    public static function boot()
    {
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'sync_order'], 20, 3);
    }

    public static function sync_order($order_id, $posted_data, $order)
    {
        if (! $order || ! is_object($order)) {
            $order = wc_get_order($order_id);
        }

        if (! $order || $order->get_payment_method() !== 'op_travel_sepay_qr') {
            return;
        }

        if ($order->get_meta(OrderMeta::BOOKING_CODE, true)) {
            return;
        }

        $endpoint = Env::get('BOOKING_SERVICE_ENDPOINT');
        if ($endpoint === '') {
            $order->add_order_note(__('BOOKING_SERVICE_ENDPOINT is missing. Booking sync skipped.', 'op-travel-sepay'));
            return;
        }

        $booking_snapshots = OrderMeta::get_booking_snapshots($order);
        if (empty($booking_snapshots)) {
            return;
        }

        $line_items = $order->get_items('line_item');
        $first_item = reset($line_items);
        $product_id = $first_item ? $first_item->get_product_id() : 0;
        $primary_booking = $booking_snapshots[0];
        $payload = [
            'wordpress_order_id' => (int) $order->get_id(),
            'wordpress_order_key' => (string) $order->get_order_key(),
            'product_id' => (int) $product_id,
            'tour_code' => (string) ($primary_booking['tour_code'] ?? ''),
            'tour_name' => (string) ($primary_booking['tour_name'] ?? ''),
            'departure_date' => (string) ($primary_booking['departure_date'] ?? ''),
            'adult_count' => (int) ($primary_booking['adult_count'] ?? 1),
            'child_count' => (int) ($primary_booking['child_count'] ?? 0),
            'customer_note' => (string) ($primary_booking['customer_note'] ?? ''),
            'customer_name' => trim((string) $order->get_formatted_billing_full_name()),
            'customer_email' => (string) $order->get_billing_email(),
            'customer_phone' => (string) $order->get_billing_phone(),
            'amount' => (float) $order->get_total(),
            'currency' => (string) $order->get_currency(),
            'payment_status' => (string) $order->get_meta(OrderMeta::PAYMENT_STATE, true) ?: 'pending',
            'return_url' => $order->get_checkout_order_received_url(),
            'cancel_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/thanh-toan/'),
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            $order->add_order_note(sprintf(
                __('Booking sync failed: %s', 'op-travel-sepay'),
                $response->get_error_message()
            ));
            return;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status_code < 200 || $status_code >= 300 || ! is_array($body)) {
            $order->add_order_note(__('Booking sync returned an invalid response.', 'op-travel-sepay'));
            return;
        }

        OrderMeta::store_payment_reference_data($order, [
            'booking_code' => $body['booking_code'] ?? '',
            'payment_code' => $body['payment_code'] ?? '',
            'payment_checkout_url' => $body['checkout_url'] ?? '',
            'payment_qr_url' => $body['qr_url'] ?? '',
            'payment_provider' => $body['provider'] ?? '',
        ]);

        if (! empty($body['payment_status'])) {
            $order->update_meta_data(OrderMeta::PAYMENT_STATE, sanitize_text_field((string) $body['payment_status']));
            OrderMeta::update_booking_payment_state($order, $body['payment_status'], $order->get_total());
        }

        if (! empty($body['payment_diagnostics']) && is_array($body['payment_diagnostics'])) {
            $order->add_order_note(sprintf(
                __('SePay diagnostics: %s', 'op-travel-sepay'),
                wp_json_encode($body['payment_diagnostics'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
        }

        $order->save();
    }
}
