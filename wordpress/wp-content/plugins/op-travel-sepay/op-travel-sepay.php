<?php
/**
 * Plugin Name: OP Travel SePay
 * Description: SePay QR payment gateway and sync bridge for HV-Travel bookings.
 * Version: 0.1.0
 * Author: Codex
 */

if (! defined('ABSPATH')) {
    exit;
}

define('OP_TRAVEL_SEPAY_PATH', plugin_dir_path(__FILE__));
define('OP_TRAVEL_SEPAY_URL', plugin_dir_url(__FILE__));

require_once OP_TRAVEL_SEPAY_PATH . 'includes/BookingServiceSync.php';
require_once OP_TRAVEL_SEPAY_PATH . 'includes/SePayPaymentQrHooks.php';
require_once OP_TRAVEL_SEPAY_PATH . 'includes/Payment/SePayQrGateway.php';
require_once OP_TRAVEL_SEPAY_PATH . 'includes/Bootstrap.php';

add_action('plugins_loaded', ['OPTravelSePay\\Bootstrap', 'boot']);
