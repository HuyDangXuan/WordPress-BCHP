<?php

namespace OPTravelSePay;

use OPTravelCore\Support\OrderMeta;

final class SePayPaymentQrHooks
{
    public static function boot()
    {
        add_action('woocommerce_thankyou', [__CLASS__, 'render_for_order']);
        add_action('woocommerce_view_order', [__CLASS__, 'render_for_order']);
    }

    public static function render_for_order($order_id)
    {
        $order = wc_get_order($order_id);

        if (! $order || $order->get_payment_method() !== 'op_travel_sepay_qr') {
            return;
        }

        $state = $order->get_meta(OrderMeta::PAYMENT_STATE) ?: 'pending';
        $amount = (int) round($order->get_total());
        $provider = $order->get_meta(OrderMeta::PAYMENT_PROVIDER, true) ?: 'sepay';
        $payment_qr_url = $order->get_meta(OrderMeta::PAYMENT_QR_URL, true);
        $payment_checkout_url = $order->get_meta(OrderMeta::PAYMENT_CHECKOUT_URL, true);
        $fallback_note = sprintf('PMT-%s', $order->get_id());
        $status_lookup_url = rest_url(sprintf('op-travel/v1/payment-status/%d', $order->get_id()));
        $fallback_qr_url = sprintf(
            'https://img.vietqr.io/image/970415-000123456789-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            $amount,
            rawurlencode($fallback_note),
            rawurlencode('HV Travel Demo')
        );
        $qr_url = $payment_qr_url ?: $fallback_qr_url;
        $state_label = self::state_label($state);
        $provider_label = self::provider_label($provider);
        ?>
        <section class="op-demo-qr op-payment-state-<?php echo esc_attr($state); ?>">
            <h2><?php esc_html_e('SePay QR', 'op-travel-sepay'); ?></h2>
            <p>
                <?php
                if ($payment_qr_url || $payment_checkout_url) {
                    esc_html_e('Dữ liệu thanh toán SePay đã sẵn sàng. Quét mã QR để chuyển khoản và đợi hệ thống tự xác nhận giao dịch.', 'op-travel-sepay');
                } else {
                    esc_html_e('Đang hiển thị QR dự phòng vì dữ liệu SePay chưa sẵn sàng đầy đủ trên đơn hàng này.', 'op-travel-sepay');
                }
                ?>
            </p>
            <div class="op-demo-qr__grid">
                <div class="op-demo-qr__media">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('QR thanh toan', 'op-travel-sepay'); ?>" />
                </div>
                <div class="op-demo-qr__details">
                    <p><strong><?php esc_html_e('Trạng thái', 'op-travel-sepay'); ?>:</strong> <span data-op-payment-state-detail><?php echo esc_html($state_label); ?></span></p>
                    <p><strong><?php esc_html_e('Số tiền', 'op-travel-sepay'); ?>:</strong> <?php echo wp_kses_post(wc_price($amount)); ?></p>
                    <p><strong><?php esc_html_e('Cổng thanh toán', 'op-travel-sepay'); ?>:</strong> <?php echo esc_html($provider_label); ?></p>
                    <?php if ($payment_checkout_url) : ?>
                        <p>
                            <a class="button alt" href="<?php echo esc_url($payment_checkout_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Mở link thanh toán SePay', 'op-travel-sepay'); ?>
                            </a>
                        </p>
                    <?php else : ?>
                        <p><strong><?php esc_html_e('Nội dung chuyển khoản', 'op-travel-sepay'); ?>:</strong> <?php echo esc_html($fallback_note); ?></p>
                    <?php endif; ?>
                    <div class="op-demo-qr__actions">
                        <button
                            type="button"
                            class="button"
                            data-op-payment-status-check
                            data-op-status-url="<?php echo esc_url($status_lookup_url); ?>"
                            data-op-order-key="<?php echo esc_attr($order->get_order_key()); ?>"
                        >
                            <?php esc_html_e('Kiểm tra trạng thái thanh toán', 'op-travel-sepay'); ?>
                        </button>
                        <p class="op-demo-qr__feedback" data-op-payment-status-feedback>
                            <?php esc_html_e('Sau khi chuyển khoản xong, bấm nút này để đồng bộ trạng thái mới nhất từ hệ thống.', 'op-travel-sepay'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    private static function state_label($state)
    {
        $labels = [
            'pending' => __('Chờ thanh toán', 'op-travel-sepay'),
            'paid' => __('Đã thanh toán', 'op-travel-sepay'),
            'failed' => __('Thanh toán lỗi', 'op-travel-sepay'),
            'expired' => __('Đã hết hạn', 'op-travel-sepay'),
            'cancelled' => __('Đã hủy', 'op-travel-sepay'),
        ];

        return $labels[$state] ?? $labels['pending'];
    }

    private static function provider_label($provider)
    {
        $provider = strtolower(trim((string) $provider));
        $labels = [
            'sepay' => __('SePay', 'op-travel-sepay'),
            'payos' => __('payOS', 'op-travel-sepay'),
            'fallback' => __('Mã QR dự phòng', 'op-travel-sepay'),
        ];

        return $labels[$provider] ?? strtoupper($provider ?: 'SePay');
    }
}
