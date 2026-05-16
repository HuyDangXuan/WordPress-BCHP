<?php

namespace OPTravelCore\Payment;

if (! defined('ABSPATH')) {
    exit;
}

final class ZaloPayQrGateway
{
    public static function boot()
    {
        add_action('woocommerce_init', [__CLASS__, 'register_gateway_class']);
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_gateway']);
    }

    public static function register_gateway_class()
    {
        self::ensure_gateway_class();
    }

    public static function add_gateway($gateways)
    {
        self::ensure_gateway_class();

        if (class_exists(__NAMESPACE__ . '\\ZaloPayQrGatewayMethod')) {
            $gateways[] = __NAMESPACE__ . '\\ZaloPayQrGatewayMethod';
        }

        return $gateways;
    }

    private static function ensure_gateway_class()
    {
        if (! class_exists('WC_Payment_Gateway') || class_exists(__NAMESPACE__ . '\\ZaloPayQrGatewayMethod')) {
            return;
        }

        require_once OP_TRAVEL_CORE_PATH . 'includes/Payment/ZaloPayQrGatewayMethod.php';
    }
}
