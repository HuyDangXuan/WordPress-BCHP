<?php

if (! defined('ABSPATH')) {
    exit;
}

$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
$register_url = add_query_arg('op_auth', 'register', $account_url) . '#op-register';
$account_user = function_exists('op_travel_get_account_user_summary') ? op_travel_get_account_user_summary() : null;
$logout_url = wp_logout_url(home_url('/'));
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');
$cart_count = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$header_classes = ['op-site-header'];

if (is_front_page() || is_page('lien-he')) {
    $header_classes[] = 'op-site-header--overlay';
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
<header class="<?php echo esc_attr(implode(' ', $header_classes)); ?>" role="banner">
    <div class="op-site-header__topline">
        <div class="op-site-header__topline-inner">
            <p><?php esc_html_e('Curated travel storefront for flexible departures and cleaner booking flows.', 'op-travel-shop'); ?></p>
            <div class="op-site-header__support">
                <a href="tel:0877504883">0877 504 883</a>
                <span class="op-site-header__dot" aria-hidden="true"></span>
                <a href="mailto:noreply.hvtravel@gmail.com">noreply.hvtravel@gmail.com</a>
            </div>
        </div>
    </div>

    <div class="op-site-header__inner">
        <a class="op-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> - <?php esc_attr_e('Trang chủ', 'op-travel-shop'); ?>">
            <span class="op-brand__mark">HV</span>
            <span class="op-brand__text"><?php bloginfo('name'); ?></span>
        </a>

        <button class="op-mobile-toggle" aria-label="<?php esc_attr_e('Menu', 'op-travel-shop'); ?>" aria-expanded="false" aria-controls="op-primary-nav">
            <span></span><span></span><span></span>
        </button>

        <div class="op-site-header__nav-shell">
            <nav id="op-primary-nav" class="op-primary-menu" aria-label="<?php esc_attr_e('Primary menu', 'op-travel-shop'); ?>">
                <?php if (has_nav_menu('primary')) : ?>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container' => false,
                        'menu_class' => '',
                        'depth' => 1,
                    ]);
                    ?>
                <?php else : ?>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/tours/')); ?>"><?php esc_html_e('Tours', 'op-travel-shop'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/gio-hang/')); ?>"><?php esc_html_e('Giỏ tour', 'op-travel-shop'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>"><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>"><?php esc_html_e('Liên hệ', 'op-travel-shop'); ?></a></li>
                    </ul>
                <?php endif; ?>
            </nav>

            <div class="op-site-header__tools">
                <a class="op-header-cart" href="<?php echo esc_url($cart_url); ?>" aria-label="<?php esc_attr_e('Mở giỏ hàng', 'op-travel-shop'); ?>">
                    <span class="op-header-cart__label"><?php esc_html_e('Giỏ tour', 'op-travel-shop'); ?></span>
                    <span class="op-header-cart__count"><?php echo esc_html((string) $cart_count); ?></span>
                </a>

                <?php if (is_user_logged_in() && is_array($account_user)) : ?>
                    <div class="op-header-profile" data-op-account-menu>
                        <button
                            type="button"
                            class="op-header-profile__trigger"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="op-header-profile-menu"
                        >
                            <span class="op-header-profile__badge"><?php echo esc_html($account_user['initials']); ?></span>
                            <span class="op-header-profile__meta">
                                <strong><?php echo esc_html($account_user['display_name']); ?></strong>
                                <span><?php echo esc_html($account_user['secondary_label']); ?></span>
                            </span>
                        </button>

                        <div id="op-header-profile-menu" class="op-header-profile__menu" hidden>
                            <a href="<?php echo esc_url($account_user['account_url']); ?>"><?php esc_html_e('Tài khoản', 'op-travel-shop'); ?></a>
                            <a href="<?php echo esc_url($account_user['orders_url']); ?>"><?php esc_html_e('Booking của tôi', 'op-travel-shop'); ?></a>
                            <a href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Đăng xuất', 'op-travel-shop'); ?></a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="op-header-auth" aria-label="<?php esc_attr_e('Tài khoản khách hàng', 'op-travel-shop'); ?>">
                        <a class="op-header-auth__link" href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Đăng nhập', 'op-travel-shop'); ?></a>
                        <a class="op-header-auth__register" href="<?php echo esc_url($register_url); ?>"><?php esc_html_e('Đăng ký', 'op-travel-shop'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
