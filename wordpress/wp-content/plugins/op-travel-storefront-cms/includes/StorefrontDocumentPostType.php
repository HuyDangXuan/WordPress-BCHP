<?php

namespace OPTravelStorefrontCMS;

final class StorefrontDocumentPostType
{
    public static function boot()
    {
        add_action('init', [__CLASS__, 'register']);
    }

    public static function register()
    {
        register_post_type('storefront_document', [
            'labels' => [
                'name' => __('Storefront CMS', 'op-travel-storefront-cms'),
                'singular_name' => __('Storefront Document', 'op-travel-storefront-cms'),
                'add_new_item' => __('Add Storefront Document', 'op-travel-storefront-cms'),
                'edit_item' => __('Edit Storefront Document', 'op-travel-storefront-cms'),
                'new_item' => __('New Storefront Document', 'op-travel-storefront-cms'),
                'view_item' => __('View Storefront Document', 'op-travel-storefront-cms'),
                'search_items' => __('Search Storefront Documents', 'op-travel-storefront-cms'),
                'not_found' => __('No storefront documents found.', 'op-travel-storefront-cms'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'show_in_rest' => false,
            'menu_position' => 58,
            'menu_icon' => 'dashicons-layout',
            'supports' => ['title', 'revisions'],
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
        ]);
    }
}
