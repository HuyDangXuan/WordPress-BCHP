<?php

namespace OPTravelCore\Rest;

use OPTravelCore\Support\Env;
use OPTravelCore\Support\OrderMeta;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PaymentConfirmController
{
    public static function boot()
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes()
    {
        register_rest_route('op-travel/v1', '/payment-confirm', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request)
    {
        $provided_secret = self::extract_secret($request);
        $expected_secret = Env::get('PAYMENT_SYNC_SECRET');

        if (! $expected_secret || ! hash_equals($expected_secret, $provided_secret)) {
            return new WP_Error('op_travel_forbidden', __('PAYMENT_SYNC_SECRET is invalid.', 'op-travel-core'), ['status' => 403]);
        }

        $order_id = absint($request->get_param('wordpress_order_id'));
        $status = sanitize_text_field((string) $request->get_param('status'));
        $payment_code = sanitize_text_field((string) $request->get_param('payment_code'));
        $provider_transaction_id = sanitize_text_field((string) $request->get_param('provider_transaction_id'));
        $provider = sanitize_text_field((string) $request->get_param('provider'));
        $amount = wc_format_decimal((string) $request->get_param('amount'));

        $allowed_states = ['pending', 'paid', 'failed', 'expired', 'cancelled'];
        if (! in_array($status, $allowed_states, true)) {
            return new WP_Error('op_travel_invalid_status', __('Unsupported payment status.', 'op-travel-core'), ['status' => 400]);
        }

        $order = wc_get_order($order_id);
        if (! $order) {
            return new WP_Error('op_travel_missing_order', __('WooCommerce order not found.', 'op-travel-core'), ['status' => 404]);
        }

        $order->update_meta_data(OrderMeta::PAYMENT_STATE, $status);
        $order->update_meta_data(OrderMeta::PAYMENT_CODE, $payment_code);
        $order->update_meta_data(OrderMeta::PROVIDER_TXN, $provider_transaction_id);
        $order->update_meta_data(OrderMeta::PAYMENT_PROVIDER, $provider);
        OrderMeta::update_booking_payment_state($order, $status, $amount !== '' ? $amount : $order->get_total());
        $order->save();

        if ($status === 'paid') {
            $order->payment_complete($provider_transaction_id);
            $order->add_order_note(sprintf(__('Thanh toán đã được xác nhận qua %s.', 'op-travel-core'), $provider ?: 'payOS'));
        } elseif ($status === 'failed') {
            $order->update_status('failed', __('Thanh toán thất bại từ payment service.', 'op-travel-core'));
        } elseif ($status === 'cancelled') {
            $order->update_status('cancelled', __('Đơn đã bị hủy từ payment service.', 'op-travel-core'));
        } elseif ($status === 'expired') {
            $order->add_order_note(__('Link hoặc QR thanh toán đã hết hạn.', 'op-travel-core'));
        } else {
            $order->add_order_note(__('Đơn vẫn đang chờ xác nhận thanh toán.', 'op-travel-core'));
        }

        return new WP_REST_Response([
            'status' => 'ok',
            'wordpress_order_id' => $order_id,
            'payment_status' => $status,
            'payment_code' => $payment_code,
            'amount' => $amount,
        ], 200);
    }

    private static function extract_secret(WP_REST_Request $request)
    {
        $authorization = (string) $request->get_header('authorization');

        if (stripos($authorization, 'Bearer ') === 0) {
            return trim(substr($authorization, 7));
        }

        return (string) $request->get_header('x-payment-sync-secret');
    }
}
