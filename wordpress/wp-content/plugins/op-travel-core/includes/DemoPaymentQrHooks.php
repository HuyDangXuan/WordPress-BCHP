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
        $note = sprintf('HVTRAVEL-%s', $order->get_order_number());
        $qr_url = sprintf(
            'https://img.vietqr.io/image/970415-000123456789-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            $amount,
            rawurlencode($note),
            rawurlencode('HV Travel Demo')
        );
        ?>
        <section class="op-demo-qr op-payment-state-<?php echo esc_attr($state); ?>">
            <h2><?php esc_html_e('Bảng thanh toán demo', 'op-travel-core'); ?></h2>
            <p><?php esc_html_e('Panel này dùng cho fallback BCK hoặc QR demo trong môi trường demo/sandbox.', 'op-travel-core'); ?></p>
            <div class="op-demo-qr__grid">
                <div class="op-demo-qr__media">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('QR thanh toán demo', 'op-travel-core'); ?>" />
                </div>
                <div class="op-demo-qr__details">
                    <p><strong><?php esc_html_e('Trạng thái', 'op-travel-core'); ?>:</strong> <?php echo esc_html($state); ?></p>
                    <p><strong><?php esc_html_e('Số tiền', 'op-travel-core'); ?>:</strong> <?php echo wp_kses_post(wc_price($amount)); ?></p>
                    <p><strong><?php esc_html_e('Nội dung chuyển khoản', 'op-travel-core'); ?>:</strong> <?php echo esc_html($note); ?></p>
                    <p><strong><?php esc_html_e('Ngân hàng nhận', 'op-travel-core'); ?>:</strong> <?php esc_html_e('Demo VietQR', 'op-travel-core'); ?></p>
                </div>
            </div>
        </section>
        <?php
    }
}
