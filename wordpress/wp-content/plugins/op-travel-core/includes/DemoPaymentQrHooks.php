<?php

namespace OPTravelCore;

use OPTravelCore\Support\OrderMeta;

final class DemoPaymentQrHooks
{
    public static function boot()
    {
        add_action('woocommerce_thankyou', [__CLASS__, 'render_for_order']);
        add_action('woocommerce_view_order', [__CLASS__, 'render_for_order']);
    }

    public static function render_for_order($order_id)
    {
        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        $state = $order->get_meta(OrderMeta::PAYMENT_STATE) ?: 'pending';
        $amount = (int) round($order->get_total());
        $provider = $order->get_meta(OrderMeta::PAYMENT_PROVIDER, true) ?: 'fallback';
        $payment_qr_url = $order->get_meta(OrderMeta::PAYMENT_QR_URL, true);
        $payment_checkout_url = $order->get_meta(OrderMeta::PAYMENT_CHECKOUT_URL, true);
        $fallback_note = sprintf('HVTRAVEL-%s', $order->get_order_number());
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
            <h2><?php esc_html_e('ZaloPay QR', 'op-travel-core'); ?></h2>
            <p>
                <?php
                if ($payment_qr_url || $payment_checkout_url) {
                    esc_html_e('Dữ liệu thanh toán ZaloPay đã sẵn sàng. Quét mã QR hoặc mở link thanh toán để hoàn tất giao dịch.', 'op-travel-core');
                } else {
                    esc_html_e('Đang hiển thị QR dự phòng vì môi trường sandbox hoặc dữ liệu provider chưa sẵn sàng.', 'op-travel-core');
                }
                ?>
            </p>
            <div class="op-demo-qr__grid">
                <div class="op-demo-qr__media">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('QR thanh toan', 'op-travel-core'); ?>" />
                </div>
                <div class="op-demo-qr__details">
                    <p><strong><?php esc_html_e('Trạng thái', 'op-travel-core'); ?>:</strong> <span data-op-payment-state-detail><?php echo esc_html($state_label); ?></span></p>
                    <p><strong><?php esc_html_e('Số tiền', 'op-travel-core'); ?>:</strong> <?php echo wp_kses_post(wc_price($amount)); ?></p>
                    <p><strong><?php esc_html_e('Cổng thanh toán', 'op-travel-core'); ?>:</strong> <?php echo esc_html($provider_label); ?></p>
                    <?php if ($payment_checkout_url) : ?>
                        <p>
                            <a class="button alt" href="<?php echo esc_url($payment_checkout_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Mở link thanh toán ZaloPay', 'op-travel-core'); ?>
                            </a>
                        </p>
                    <?php else : ?>
                        <p><strong><?php esc_html_e('Nội dung chuyển khoản dự phòng', 'op-travel-core'); ?>:</strong> <?php echo esc_html($fallback_note); ?></p>
                    <?php endif; ?>
                    <div class="op-demo-qr__actions">
                        <button
                            type="button"
                            class="button"
                            data-op-payment-status-check
                            data-op-status-url="<?php echo esc_url($status_lookup_url); ?>"
                            data-op-order-key="<?php echo esc_attr($order->get_order_key()); ?>"
                        >
                            <?php esc_html_e('Kiểm tra trạng thái thanh toán', 'op-travel-core'); ?>
                        </button>
                        <p class="op-demo-qr__feedback" data-op-payment-status-feedback>
                            <?php esc_html_e('Sau khi thanh toán xong, bấm nút này để cập nhật trạng thái mới nhất.', 'op-travel-core'); ?>
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
            'pending' => __('Chờ thanh toán', 'op-travel-core'),
            'paid' => __('Đã thanh toán', 'op-travel-core'),
            'failed' => __('Thanh toán lỗi', 'op-travel-core'),
            'expired' => __('Đã hết hạn', 'op-travel-core'),
            'cancelled' => __('Đã hủy', 'op-travel-core'),
        ];

        return $labels[$state] ?? $labels['pending'];
    }

    private static function provider_label($provider)
    {
        $provider = strtolower(trim((string) $provider));
        $labels = [
            'zalopay' => __('ZaloPay', 'op-travel-core'),
            'payos' => __('payOS', 'op-travel-core'),
            'fallback' => __('Mã QR dự phòng', 'op-travel-core'),
        ];

        return $labels[$provider] ?? strtoupper($provider ?: 'ZaloPay');
    }
}
