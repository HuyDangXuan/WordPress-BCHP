<?php
/**
 * Plugin Name: OP Travel Storefront CMS
 * Description: Route-bound storefront CMS documents for the OP Travel Shop theme.
 * Version: 0.1.0
 * Author: Codex
 */

if (! defined('ABSPATH')) {
    exit;
}

define('OP_TRAVEL_STOREFRONT_CMS_PATH', plugin_dir_path(__FILE__));
define('OP_TRAVEL_STOREFRONT_CMS_URL', plugin_dir_url(__FILE__));

require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Domain/RouteKey.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Domain/SectionSanitizer.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/StorefrontDocumentPostType.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Documents/DocumentRepository.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Sections/SectionRegistry.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Admin/AdminAssets.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Admin/DocumentMetaBoxes.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Admin/DocumentSave.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Front/PreviewResolver.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Front/RouteRenderer.php';
require_once OP_TRAVEL_STOREFRONT_CMS_PATH . 'includes/Bootstrap.php';

add_action('plugins_loaded', ['OPTravelStorefrontCMS\\Bootstrap', 'boot']);

if (! function_exists('op_travel_storefront_render_route')) {
    function op_travel_storefront_render_route($routeKey, $context = [])
    {
        return \OPTravelStorefrontCMS\Front\RouteRenderer::render((string) $routeKey, is_array($context) ? $context : []);
    }
}
