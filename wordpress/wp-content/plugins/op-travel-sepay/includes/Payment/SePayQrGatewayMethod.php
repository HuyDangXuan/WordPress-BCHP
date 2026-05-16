<?php

namespace OPTravelSePay\Payment;

use OPTravelCore\Support\OrderMeta;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_Payment_Gateway') && ! class_exists(__NAMESPACE__ . '\\SePayQrGatewayMethod', false)) :
    final class SePayQrGatewayMethod extends \WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'op_travel_sepay_qr';
            $this->method_title = __('SePay QR', 'op-travel-sepay');
            $this->method_description = __('Nhận thanh toán tour bằng mã QR SePay hiển thị ở trang hoàn tất đơn.', 'op-travel-sepay');
            $this->has_fields = false;
            $this->supports = ['products'];

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled', 'yes');
            $this->title = $this->get_option('title', __('SePay QR', 'op-travel-sepay'));
            $this->description = $this->get_option('description', __('Scan the SePay QR after placing your tour order.', 'op-travel-sepay'));

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => __('Enable/Disable', 'op-travel-sepay'),
                    'type' => 'checkbox',
                    'label' => __('Enable SePay QR', 'op-travel-sepay'),
                    'default' => 'yes',
                ],
                'title' => [
                    'title' => __('Title', 'op-travel-sepay'),
                    'type' => 'text',
                    'default' => __('SePay QR', 'op-travel-sepay'),
                ],
                'description' => [
                    'title' => __('Description', 'op-travel-sepay'),
                    'type' => 'textarea',
                    'default' => __('Scan the SePay QR after placing your tour order.', 'op-travel-sepay'),
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
            $order->update_meta_data(OrderMeta::PAYMENT_STATE, 'pending');
            $order->update_meta_data(OrderMeta::PAYMENT_PROVIDER, 'sepay');
            $order->update_status('pending', __('Đang chờ thanh toán qua SePay QR.', 'op-travel-sepay'));
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
