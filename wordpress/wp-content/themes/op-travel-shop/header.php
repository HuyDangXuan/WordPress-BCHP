<?php

if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="op-site-header" role="banner">
    <div class="op-site-header__inner">
        <a class="op-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> — <?php esc_attr_e('Trang chủ', 'op-travel-shop'); ?>">
            <span class="op-brand__mark">HV</span>
            <span><?php bloginfo('name'); ?></span>
        </a>
        <button class="op-mobile-toggle" aria-label="<?php esc_attr_e('Menu', 'op-travel-shop'); ?>" aria-expanded="false" aria-controls="op-primary-nav">
            <span></span><span></span><span></span>
        </button>
        <nav id="op-primary-nav" class="op-primary-menu" aria-label="<?php esc_attr_e('Primary menu', 'op-travel-shop'); ?>">
            <ul>
                <li><a href="<?php echo esc_url(home_url('/tours/')); ?>"><?php esc_html_e('Tours', 'op-travel-shop'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/gio-hang/')); ?>"><?php esc_html_e('Giữ chỗ', 'op-travel-shop'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>"><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>"><?php esc_html_e('Liên hệ', 'op-travel-shop'); ?></a></li>
            </ul>
        </nav>
    </div>
</header>
