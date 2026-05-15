<?php

namespace OPTravelCore\Rest;

use OPTravelCore\Support\Env;
use OPTravelCore\Support\OrderMeta;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PaymentConfirmController
{
    private const ALLOWED_STATES = ['pending', 'paid', 'failed', 'expired', 'cancelled'];

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

        register_rest_route('op-travel/v1', '/payment-status/(?P<order_id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_status_lookup'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request)
    {
        $provided_secret = self::extract_secret($request);
        $expected_secret = Env::get('PAYMENT_SYNC_SECRET');

        if (! $expected_secret || ! hash_equals($expected_secret, $provided_secret)) {
            return new WP_Error('op_travel_forbidden', __('PAYMENT_SYNC_SECRET không hợp lệ.', 'op-travel-core'), ['status' => 403]);
        }

        $order_id = absint($request->get_param('wordpress_order_id'));
        $status = sanitize_text_field((string) $request->get_param('status'));
        $payment_code = sanitize_text_field((string) $request->get_param('payment_code'));
        $provider_transaction_id = sanitize_text_field((string) $request->get_param('provider_transaction_id'));
        $provider = sanitize_text_field((string) $request->get_param('provider'));
        $amount = wc_format_decimal((string) $request->get_param('amount'));

        if (! in_array($status, self::ALLOWED_STATES, true)) {
            return new WP_Error('op_travel_invalid_status', __('Trạng thái thanh toán không được hỗ trợ.', 'op-travel-core'), ['status' => 400]);
        }

        $order = wc_get_order($order_id);
        if (! $order) {
            return new WP_Error('op_travel_missing_order', __('Không tìm thấy đơn hàng WooCommerce.', 'op-travel-core'), ['status' => 404]);
        }

        $payment = self::apply_payment_update($order, [
            'status' => $status,
            'payment_code' => $payment_code,
            'provider_transaction_id' => $provider_transaction_id,
            'provider' => $provider,
            'amount' => $amount,
        ]);

        return new WP_REST_Response([
            'status' => 'ok',
            'wordpress_order_id' => $order_id,
            'payment_status' => $payment['payment_status'],
            'payment_code' => $payment['payment_code'],
            'amount' => $payment['amount'],
        ], 200);
    }

    public static function handle_status_lookup(WP_REST_Request $request)
    {
        $order_id = absint($request['order_id']);
        $order = wc_get_order($order_id);

        if (! $order) {
            return new WP_Error('op_travel_missing_order', __('Không tìm thấy đơn hàng WooCommerce.', 'op-travel-core'), ['status' => 404]);
        }

        if (! self::can_view_order_status($order, $request)) {
            return new WP_Error('op_travel_forbidden_status', __('Bạn không có quyền xem trạng thái thanh toán này.', 'op-travel-core'), ['status' => 403]);
        }

        $lookup = self::refresh_status_from_payment_service($order);
        $state = $lookup['payment_status'] ?? self::normalize_state((string) $order->get_meta(OrderMeta::PAYMENT_STATE, true));

        return new WP_REST_Response([
            'status' => 'ok',
            'wordpress_order_id' => $order->get_id(),
            'payment_status' => $state,
            'state_label' => self::get_state_label($state),
            'state_message' => self::get_state_message($state),
            'feedback_message' => $lookup['feedback_message'] ?? self::get_lookup_feedback_message($state, 'local'),
            'payment_code' => (string) $order->get_meta(OrderMeta::PAYMENT_CODE, true),
            'provider' => (string) $order->get_meta(OrderMeta::PAYMENT_PROVIDER, true),
            'order_status' => (string) $order->get_status(),
            'synced_via' => $lookup['synced_via'] ?? 'order-meta',
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

    private static function can_view_order_status($order, WP_REST_Request $request)
    {
        if (! $order) {
            return false;
        }

        if (is_user_logged_in() && ((int) $order->get_user_id() === get_current_user_id())) {
            return true;
        }

        $provided_key = sanitize_text_field((string) $request->get_param('order_key'));

        return $provided_key !== '' && hash_equals((string) $order->get_order_key(), $provided_key);
    }

    private static function refresh_status_from_payment_service($order)
    {
        $local_state = self::normalize_state((string) $order->get_meta(OrderMeta::PAYMENT_STATE, true));
        $endpoint = self::get_payment_status_endpoint();
        $secret = Env::get('PAYMENT_SYNC_SECRET');

        if ($endpoint === '' || $secret === '') {
            return [
                'payment_status' => $local_state,
                'feedback_message' => self::get_lookup_feedback_message($local_state, 'local'),
                'synced_via' => 'order-meta',
            ];
        }

        $query_args = [
            'order_id' => (int) $order->get_id(),
        ];

        $payment_code = (string) $order->get_meta(OrderMeta::PAYMENT_CODE, true);
        if ($payment_code !== '') {
            $query_args['payment_code'] = $payment_code;
        }

        $response = wp_remote_get(add_query_arg($query_args, $endpoint), [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $secret,
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'payment_status' => $local_state,
                'feedback_message' => self::get_lookup_feedback_message($local_state, 'unavailable'),
                'synced_via' => 'order-meta',
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status_code === 404) {
            return [
                'payment_status' => $local_state,
                'feedback_message' => self::get_lookup_feedback_message($local_state, 'missing'),
                'synced_via' => 'order-meta',
            ];
        }

        if ($status_code < 200 || $status_code >= 300 || ! is_array($body)) {
            return [
                'payment_status' => $local_state,
                'feedback_message' => self::get_lookup_feedback_message($local_state, 'unavailable'),
                'synced_via' => 'order-meta',
            ];
        }

        $payment = self::apply_payment_update($order, [
            'status' => $body['payment_status'] ?? 'pending',
            'payment_code' => $body['payment_code'] ?? '',
            'provider_transaction_id' => $body['provider_transaction_id'] ?? '',
            'provider' => $body['provider'] ?? '',
            'amount' => $body['amount'] ?? '',
        ]);
        $status_source = sanitize_text_field((string) ($body['status_source'] ?? 'payment'));
        $lookup_mode = $status_source === 'sepay-api'
            ? 'synced'
            : ($payment['payment_status'] === 'pending' ? 'missing' : 'synced');

        return [
            'payment_status' => $payment['payment_status'],
            'feedback_message' => self::get_lookup_feedback_message($payment['payment_status'], $lookup_mode),
            'synced_via' => 'payment-service',
        ];
    }

    private static function get_payment_status_endpoint()
    {
        $endpoint = trim((string) Env::get('BOOKING_SERVICE_ENDPOINT'));

        if ($endpoint === '') {
            return '';
        }

        if (preg_match('#/api/bookings/?$#', $endpoint) !== 1) {
            return '';
        }

        return (string) preg_replace('#/api/bookings/?$#', '/api/payments/status', $endpoint);
    }

    private static function apply_payment_update($order, array $payment_data)
    {
        $current_state = self::normalize_state((string) $order->get_meta(OrderMeta::PAYMENT_STATE, true));
        $current_payment_code = (string) $order->get_meta(OrderMeta::PAYMENT_CODE, true);
        $current_provider_transaction_id = (string) $order->get_meta(OrderMeta::PROVIDER_TXN, true);
        $current_provider = (string) $order->get_meta(OrderMeta::PAYMENT_PROVIDER, true);

        $status = self::normalize_state((string) ($payment_data['status'] ?? $payment_data['payment_status'] ?? $current_state));
        $payment_code = sanitize_text_field((string) ($payment_data['payment_code'] ?? $current_payment_code));
        $provider_transaction_id = sanitize_text_field((string) ($payment_data['provider_transaction_id'] ?? $current_provider_transaction_id));
        $provider = sanitize_text_field((string) ($payment_data['provider'] ?? $current_provider));
        $amount = (string) ($payment_data['amount'] ?? '');
        $normalized_amount = $amount !== '' ? wc_format_decimal($amount) : '';

        $state_changed = $status !== $current_state;
        $provider_transaction_changed = $provider_transaction_id !== '' && $provider_transaction_id !== $current_provider_transaction_id;
        $provider_changed = $provider !== '' && $provider !== $current_provider;

        $order->update_meta_data(OrderMeta::PAYMENT_STATE, $status);

        if ($payment_code !== '') {
            $order->update_meta_data(OrderMeta::PAYMENT_CODE, $payment_code);
        }

        if ($provider_transaction_id !== '') {
            $order->update_meta_data(OrderMeta::PROVIDER_TXN, $provider_transaction_id);
        }

        if ($provider !== '') {
            $order->update_meta_data(OrderMeta::PAYMENT_PROVIDER, $provider);
        }

        OrderMeta::update_booking_payment_state($order, $status, $normalized_amount !== '' ? $normalized_amount : $order->get_total());
        $order->save();

        if ($state_changed || $provider_transaction_changed || $provider_changed) {
            self::apply_order_status_transition($order, $status, $provider, $provider_transaction_id);
        }

        return [
            'payment_status' => $status,
            'payment_code' => $payment_code,
            'provider' => $provider,
            'provider_transaction_id' => $provider_transaction_id,
            'amount' => $normalized_amount !== '' ? $normalized_amount : (string) $order->get_total(),
        ];
    }

    private static function apply_order_status_transition($order, $status, $provider, $provider_transaction_id)
    {
        if ($status === 'paid') {
            if (! $order->is_paid()) {
                $order->payment_complete($provider_transaction_id);
            }

            $order->add_order_note(sprintf(
                __('Thanh toán đã được xác nhận qua %s.', 'op-travel-core'),
                self::get_provider_label($provider)
            ));
            return;
        }

        if ($status === 'failed') {
            if ($order->get_status() !== 'failed') {
                $order->update_status('failed', __('Thanh toán thất bại từ payment service.', 'op-travel-core'));
            }
            return;
        }

        if ($status === 'cancelled') {
            if ($order->get_status() !== 'cancelled') {
                $order->update_status('cancelled', __('Đơn đã bị hủy từ payment service.', 'op-travel-core'));
            }
            return;
        }

        if ($status === 'expired') {
            $order->add_order_note(__('Link hoặc QR thanh toán đã hết hạn.', 'op-travel-core'));
            return;
        }

        $order->add_order_note(__('Đơn vẫn đang chờ xác nhận thanh toán.', 'op-travel-core'));
    }

    private static function get_provider_label($provider)
    {
        $provider = strtolower(trim((string) $provider));
        $labels = [
            'sepay' => 'SePay',
            'payos' => 'payOS',
            'zalopay' => 'ZaloPay',
            'fallback' => __('Mã QR dự phòng', 'op-travel-core'),
        ];

        return $labels[$provider] ?? ($provider !== '' ? $provider : __('payment service', 'op-travel-core'));
    }

    private static function normalize_state($state)
    {
        return in_array($state, self::ALLOWED_STATES, true) ? $state : 'pending';
    }

    private static function get_state_label($state)
    {
        $labels = [
            'pending' => __('Chờ thanh toán', 'op-travel-core'),
            'paid' => __('Đã thanh toán', 'op-travel-core'),
            'failed' => __('Thanh toán lỗi', 'op-travel-core'),
            'expired' => __('Đã hết hạn', 'op-travel-core'),
            'cancelled' => __('Đã hủy', 'op-travel-core'),
        ];

        return $labels[$state] ?? $labels['pending'];
    }

    private static function get_state_message($state)
    {
        $messages = [
            'pending' => __('Đơn đã được tạo và đang chờ xác nhận thanh toán.', 'op-travel-core'),
            'paid' => __('Thanh toán đã được xác nhận hợp lệ. Hành trình của bạn đã được khóa chỗ.', 'op-travel-core'),
            'failed' => __('Thanh toán chưa thành công. Bạn có thể thử lại hoặc đổi phương thức khác.', 'op-travel-core'),
            'expired' => __('QR hoặc link thanh toán đã hết hạn. Hãy tạo giao dịch mới để tiếp tục.', 'op-travel-core'),
            'cancelled' => __('Giao dịch đã bị hủy. Bạn có thể quay lại giỏ hàng hoặc checkout.', 'op-travel-core'),
        ];

        return $messages[$state] ?? $messages['pending'];
    }

    private static function get_lookup_feedback_message($state, $mode)
    {
        $messages = [
            'synced' => [
                'pending' => __('Đã kiểm tra với hệ thống thanh toán. Giao dịch vẫn đang chờ xác nhận.', 'op-travel-core'),
                'paid' => __('Hệ thống đã đồng bộ và xác nhận thanh toán thành công.', 'op-travel-core'),
                'failed' => __('Hệ thống thanh toán đã phản hồi giao dịch chưa thành công.', 'op-travel-core'),
                'expired' => __('Hệ thống thanh toán xác nhận QR hoặc link này đã hết hạn.', 'op-travel-core'),
                'cancelled' => __('Hệ thống thanh toán xác nhận giao dịch đã bị hủy.', 'op-travel-core'),
            ],
            'missing' => [
                'pending' => __('Đã kiểm tra hệ thống thanh toán nhưng chưa thấy cập nhật mới. Vui lòng thử lại sau ít phút.', 'op-travel-core'),
                'paid' => __('Đã kiểm tra hệ thống thanh toán nhưng chưa thấy cập nhật mới. Vui lòng thử lại sau ít phút.', 'op-travel-core'),
                'failed' => __('Đã kiểm tra hệ thống thanh toán nhưng chưa thấy cập nhật mới. Vui lòng thử lại sau ít phút.', 'op-travel-core'),
                'expired' => __('Đã kiểm tra hệ thống thanh toán nhưng chưa thấy cập nhật mới. Vui lòng thử lại sau ít phút.', 'op-travel-core'),
                'cancelled' => __('Đã kiểm tra hệ thống thanh toán nhưng chưa thấy cập nhật mới. Vui lòng thử lại sau ít phút.', 'op-travel-core'),
            ],
            'unavailable' => [
                'pending' => __('Không thể kết nối tới hệ thống thanh toán lúc này. Đang hiển thị trạng thái gần nhất trên đơn.', 'op-travel-core'),
                'paid' => __('Không thể kết nối tới hệ thống thanh toán lúc này. Đang hiển thị trạng thái gần nhất trên đơn.', 'op-travel-core'),
                'failed' => __('Không thể kết nối tới hệ thống thanh toán lúc này. Đang hiển thị trạng thái gần nhất trên đơn.', 'op-travel-core'),
                'expired' => __('Không thể kết nối tới hệ thống thanh toán lúc này. Đang hiển thị trạng thái gần nhất trên đơn.', 'op-travel-core'),
                'cancelled' => __('Không thể kết nối tới hệ thống thanh toán lúc này. Đang hiển thị trạng thái gần nhất trên đơn.', 'op-travel-core'),
            ],
            'local' => [
                'pending' => __('Đang hiển thị trạng thái hiện có trên đơn. Hãy bấm lại sau khi hệ thống nhận được webhook từ ngân hàng.', 'op-travel-core'),
                'paid' => __('Đang hiển thị trạng thái hiện có trên đơn.', 'op-travel-core'),
                'failed' => __('Đang hiển thị trạng thái hiện có trên đơn.', 'op-travel-core'),
                'expired' => __('Đang hiển thị trạng thái hiện có trên đơn.', 'op-travel-core'),
                'cancelled' => __('Đang hiển thị trạng thái hiện có trên đơn.', 'op-travel-core'),
            ],
        ];

        return $messages[$mode][$state] ?? self::get_state_message($state);
    }
}
