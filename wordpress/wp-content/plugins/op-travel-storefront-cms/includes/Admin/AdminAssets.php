<?php

namespace OPTravelStorefrontCMS\Admin;

final class AdminAssets
{
    public static function boot()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue($hookSuffix)
    {
        if (! self::isStorefrontDocumentScreen($hookSuffix)) {
            return;
        }

        $scriptPath = OP_TRAVEL_STOREFRONT_CMS_PATH . 'assets/admin/storefront-cms-admin.js';
        $stylePath = OP_TRAVEL_STOREFRONT_CMS_PATH . 'assets/admin/storefront-cms-admin.css';

        wp_enqueue_style(
            'op-travel-storefront-cms-admin',
            OP_TRAVEL_STOREFRONT_CMS_URL . 'assets/admin/storefront-cms-admin.css',
            [],
            file_exists($stylePath) ? filemtime($stylePath) : '0.1.0'
        );

        wp_enqueue_script(
            'op-travel-storefront-cms-admin',
            OP_TRAVEL_STOREFRONT_CMS_URL . 'assets/admin/storefront-cms-admin.js',
            [],
            file_exists($scriptPath) ? filemtime($scriptPath) : '0.1.0',
            true
        );

        wp_localize_script('op-travel-storefront-cms-admin', 'opTravelStorefrontCms', [
            'sectionTypes' => \OPTravelStorefrontCMS\Sections\SectionRegistry::all(),
        ]);
    }

    private static function isStorefrontDocumentScreen($hookSuffix)
    {
        if (! is_string($hookSuffix)) {
            return false;
        }

        return strpos($hookSuffix, 'storefront_document') !== false;
    }
}
