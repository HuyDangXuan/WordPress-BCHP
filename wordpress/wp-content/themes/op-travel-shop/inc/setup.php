<?php

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', 'op_travel_theme_setup');
add_action('wp_enqueue_scripts', 'op_travel_enqueue_assets');

function op_travel_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('Primary Menu', 'op-travel-shop'),
        'footer' => __('Footer Menu', 'op-travel-shop'),
    ]);
}

function op_travel_enqueue_assets()
{
    wp_enqueue_style(
        'op-travel-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'op-travel-theme',
        get_template_directory_uri() . '/assets/css/theme.css',
        ['op-travel-fonts'],
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script(
        'op-travel-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
}
