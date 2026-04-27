<?php
/**
 * Plugin Name: OP Travel Core
 * Description: Business logic foundation for HV-Travel tours, booking flow, and payment state integration.
 * Version: 0.1.0
 * Author: Codex
 */

if (! defined('ABSPATH')) {
    exit;
}

define('OP_TRAVEL_CORE_PATH', plugin_dir_path(__FILE__));
define('OP_TRAVEL_CORE_URL', plugin_dir_url(__FILE__));

require_once OP_TRAVEL_CORE_PATH . 'includes/Support/Env.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/Support/OrderMeta.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/CmsSetup.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/ProductMeta.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/BookingHooks.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/DemoPaymentQrHooks.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/Rest/PaymentConfirmController.php';
require_once OP_TRAVEL_CORE_PATH . 'includes/Bootstrap.php';

register_activation_hook(__FILE__, ['OPTravelCore\\CmsSetup', 'activate']);

add_action('plugins_loaded', ['OPTravelCore\\Bootstrap', 'boot']);
