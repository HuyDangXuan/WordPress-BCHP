<?php

namespace OPTravelCore;

final class CustomerAuthFlow
{
    private const LOGIN_CART_REFRESH_META = '_op_travel_replace_session_cart_after_login';
    private const SESSION_LOGIN_CART_REFRESH = 'op_travel_replace_session_cart_after_login';

    public static function boot()
    {
        add_filter('woocommerce_login_redirect', [__CLASS__, 'redirect_after_login'], 10, 2);
        add_action('wp_login', [__CLASS__, 'prepare_account_cart_after_login'], 20, 2);
        add_action('woocommerce_load_cart_from_session', [__CLASS__, 'maybe_replace_guest_cart_after_login'], 1);
    }

    public static function redirect_after_login($redirect, $user)
    {
        $fallback = self::account_url();
        $posted_redirect = isset($_POST['redirect']) && is_scalar($_POST['redirect'])
            ? esc_url_raw(wp_unslash($_POST['redirect']))
            : '';

        if ($posted_redirect) {
            return wp_validate_redirect($posted_redirect, $fallback);
        }

        if (is_string($redirect) && $redirect !== '') {
            return wp_validate_redirect($redirect, $fallback);
        }

        return $fallback;
    }

    private static function account_url()
    {
        return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    }

    public static function prepare_account_cart_after_login($user_login, $user)
    {
        if (! $user instanceof \WP_User) {
            return;
        }

        update_user_meta($user->ID, self::LOGIN_CART_REFRESH_META, 1);

        if (! function_exists('WC') || ! WC()->session) {
            return;
        }

        WC()->session->set(self::SESSION_LOGIN_CART_REFRESH, (int) $user->ID);
    }

    public static function maybe_replace_guest_cart_after_login()
    {
        if (! is_user_logged_in() || ! function_exists('WC') || ! WC()->session) {
            return;
        }

        $user_id = get_current_user_id();
        $queued_user_id = absint(WC()->session->get(self::SESSION_LOGIN_CART_REFRESH));
        $should_replace_cart = (int) get_user_meta($user_id, self::LOGIN_CART_REFRESH_META, true) === 1;

        if ($queued_user_id === $user_id) {
            $should_replace_cart = true;
        }

        if (! $should_replace_cart) {
            return;
        }

        delete_user_meta($user_id, self::LOGIN_CART_REFRESH_META);
        delete_user_meta($user_id, '_woocommerce_load_saved_cart_after_login');

        WC()->session->set(self::SESSION_LOGIN_CART_REFRESH, null);
        WC()->session->set('op_travel_selected_checkout_cart_item_key', null);
        WC()->session->set('op_travel_checkout_backup_cart_items', null);
        WC()->session->set('cart', self::get_saved_cart_for_user($user_id));
        WC()->session->set('cart_totals', null);
        WC()->session->set('applied_coupons', null);
        WC()->session->set('coupon_discount_totals', null);
        WC()->session->set('coupon_discount_tax_totals', null);
        WC()->session->set('removed_cart_contents', null);
    }

    private static function get_saved_cart_for_user($user_id)
    {
        $persistent_cart = get_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true);

        if (! is_array($persistent_cart) || empty($persistent_cart['cart']) || ! is_array($persistent_cart['cart'])) {
            return null;
        }

        return $persistent_cart['cart'];
    }
}
