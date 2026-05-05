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
        $fallback_qr_url = sprintf(
            'https://img.vietqr.io/image/970415-000123456789-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            $amount,
            rawurlencode($fallback_note),
            rawurlencode('HV Travel Demo')
        );
        $qr_url = $payment_qr_url ?: $fallback_qr_url;
        ?>
        <section class="op-demo-qr op-payment-state-<?php echo esc_attr($state); ?>">
            <h2><?php esc_html_e('ZaloPay QR', 'op-travel-core'); ?></h2>
            <p>
                <?php
                if ($payment_qr_url || $payment_checkout_url) {
                    esc_html_e('ZaloPay payment data is available. Scan the QR or open the payment link to complete this order.', 'op-travel-core');
                } else {
                    esc_html_e('Fallback QR is shown because ZaloPay sandbox credentials or provider data are not available yet.', 'op-travel-core');
                }
                ?>
            </p>
            <div class="op-demo-qr__grid">
                <div class="op-demo-qr__media">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('QR thanh toan', 'op-travel-core'); ?>" />
                </div>
                <div class="op-demo-qr__details">
                    <p><strong><?php esc_html_e('Trang thai', 'op-travel-core'); ?>:</strong> <?php echo esc_html($state); ?></p>
                    <p><strong><?php esc_html_e('So tien', 'op-travel-core'); ?>:</strong> <?php echo wp_kses_post(wc_price($amount)); ?></p>
                    <p><strong><?php esc_html_e('Provider', 'op-travel-core'); ?>:</strong> <?php echo esc_html($provider); ?></p>
                    <?php if ($payment_checkout_url) : ?>
                        <p>
                            <a class="button alt" href="<?php echo esc_url($payment_checkout_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Open ZaloPay payment link', 'op-travel-core'); ?>
                            </a>
                        </p>
                    <?php else : ?>
                        <p><strong><?php esc_html_e('Noi dung fallback', 'op-travel-core'); ?>:</strong> <?php echo esc_html($fallback_note); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
