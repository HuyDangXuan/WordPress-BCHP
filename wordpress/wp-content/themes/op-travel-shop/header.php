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
<header class="op-site-header">
    <div class="op-site-header__inner">
        <a class="op-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="op-brand__mark">HV</span>
            <span><?php bloginfo('name'); ?></span>
        </a>
        <nav class="op-primary-menu" aria-label="<?php esc_attr_e('Primary menu', 'op-travel-shop'); ?>">
            <a href="<?php echo esc_url(home_url('/tours/')); ?>"><?php esc_html_e('Tours', 'op-travel-shop'); ?></a>
            <a href="<?php echo esc_url(home_url('/gio-hang/')); ?>"><?php esc_html_e('Giữ chỗ', 'op-travel-shop'); ?></a>
            <a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>"><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></a>
            <a href="<?php echo esc_url(home_url('/lien-he/')); ?>"><?php esc_html_e('Liên hệ', 'op-travel-shop'); ?></a>
        </nav>
    </div>
</header>
