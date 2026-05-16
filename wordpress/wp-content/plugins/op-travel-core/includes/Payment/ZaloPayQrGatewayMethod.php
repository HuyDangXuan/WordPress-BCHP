<?php

namespace OPTravelCore\Payment;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_Payment_Gateway') && ! class_exists(__NAMESPACE__ . '\\ZaloPayQrGatewayMethod', false)) :
    final class ZaloPayQrGatewayMethod extends \WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'op_travel_zalopay_qr';
            $this->method_title = __('ZaloPay QR', 'op-travel-core');
            $this->method_description = __('Nhận thanh toán tour bằng mã QR ZaloPay hiển thị ở trang hoàn tất đơn.', 'op-travel-core');
            $this->has_fields = false;
            $this->supports = ['products'];

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled', 'yes');
            $this->title = $this->get_option('title', __('ZaloPay QR', 'op-travel-core'));
            $this->description = $this->get_option('description', __('Quét mã ZaloPay sau khi đặt tour để hoàn tất thanh toán.', 'op-travel-core'));

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => __('Enable/Disable', 'op-travel-core'),
                    'type' => 'checkbox',
                    'label' => __('Enable ZaloPay QR', 'op-travel-core'),
                    'default' => 'yes',
                ],
                'title' => [
                    'title' => __('Title', 'op-travel-core'),
                    'type' => 'text',
                    'default' => __('ZaloPay QR', 'op-travel-core'),
                ],
                'description' => [
                    'title' => __('Description', 'op-travel-core'),
                    'type' => 'textarea',
                    'default' => __('Quét mã ZaloPay sau khi đặt tour để hoàn tất thanh toán.', 'op-travel-core'),
                ],
            ];
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            if (! $order) {
                return [
                    'result' => 'failure',
                ];
            }

            $order->set_payment_method($this);
            $order->update_meta_data(\OPTravelCore\Support\OrderMeta::PAYMENT_STATE, 'pending');
            $order->update_status('pending', __('Đang chờ thanh toán qua ZaloPay QR.', 'op-travel-core'));
            $order->save();

            if (function_exists('WC') && WC()->cart) {
                WC()->cart->empty_cart();
            }

            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url(),
            ];
        }
    }
endif;
