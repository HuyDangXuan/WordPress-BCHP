<?php

namespace OPTravelCore;

final class CheckoutSelectionFlow
{
    private const SESSION_SELECTED_CART_ITEM_KEY = 'op_travel_selected_checkout_cart_item_key';
    private const SESSION_BACKUP_CART_ITEMS = 'op_travel_checkout_backup_cart_items';

    public static function boot()
    {
        add_action('wp', [__CLASS__, 'restore_checkout_backup_when_returning_to_cart'], 5);
        add_action('wp', [__CLASS__, 'handle_cart_checkout_selection'], 20);
        add_action('wp', [__CLASS__, 'prepare_checkout_with_selected_booking'], 25);
        add_action('woocommerce_thankyou', [__CLASS__, 'restore_checkout_backup_after_payment'], 5);
        add_action('woocommerce_cart_emptied', [__CLASS__, 'clear_selection_state'], 20);
    }

    public static function restore_checkout_backup_when_returning_to_cart()
    {
        if (! self::is_cart_request()) {
            return;
        }

        $backup_items = self::get_session_value(self::SESSION_BACKUP_CART_ITEMS);
        if (! is_array($backup_items) || empty($backup_items)) {
            return;
        }

        $merged_items = array_merge(self::get_cart_for_session(), $backup_items);
        self::replace_cart_contents($merged_items, true);
        self::set_session_value(
            self::SESSION_SELECTED_CART_ITEM_KEY,
            self::resolve_selected_cart_item_key($merged_items, self::get_selected_cart_item_key())
        );
        self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, null);
    }

    public static function handle_cart_checkout_selection()
    {
        if (! self::is_cart_request() || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        if (! isset($_POST['op_travel_checkout_selected_booking'])) {
            return;
        }

        if (! isset($_POST['woocommerce-cart-nonce']) || ! wp_verify_nonce(wp_unslash($_POST['woocommerce-cart-nonce']), 'woocommerce-cart')) {
            wc_add_notice(__('Phiên giữ chỗ đã thay đổi. Hãy thử lại từ giỏ hàng.', 'op-travel-core'), 'error');
            return;
        }

        $cart_items = self::get_cart_for_session();
        $selected_key = self::resolve_selected_cart_item_key(
            $cart_items,
            isset($_POST['op_travel_selected_cart_item']) ? sanitize_text_field(wp_unslash($_POST['op_travel_selected_cart_item'])) : ''
        );

        if ($selected_key === '') {
            wc_add_notice(__('Chọn một tour trước khi chuyển sang bước thanh toán.', 'op-travel-core'), 'error');
            return;
        }

        self::set_session_value(self::SESSION_SELECTED_CART_ITEM_KEY, $selected_key);
        self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, null);

        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    public static function prepare_checkout_with_selected_booking()
    {
        if (! self::is_checkout_request()) {
            return;
        }

        $cart_items = self::get_cart_for_session();
        if (empty($cart_items)) {
            self::clear_selection_state();
            return;
        }

        $selected_key = self::resolve_selected_cart_item_key($cart_items, self::get_selected_cart_item_key());
        if ($selected_key === '') {
            self::clear_selection_state();
            return;
        }

        self::set_session_value(self::SESSION_SELECTED_CART_ITEM_KEY, $selected_key);

        $existing_backup = self::get_session_value(self::SESSION_BACKUP_CART_ITEMS);
        if (is_array($existing_backup) && ! empty($existing_backup) && count($cart_items) === 1 && isset($cart_items[$selected_key])) {
            return;
        }

        if (count($cart_items) === 1 && isset($cart_items[$selected_key])) {
            self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, null);
            return;
        }

        $backup_items = $cart_items;
        $selected_item = $backup_items[$selected_key];
        unset($backup_items[$selected_key]);

        self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, $backup_items);
        self::replace_cart_contents([$selected_key => $selected_item], false);
    }

    public static function restore_checkout_backup_after_payment($order_id)
    {
        if (absint($order_id) <= 0) {
            return;
        }

        $backup_items = self::get_session_value(self::SESSION_BACKUP_CART_ITEMS);
        if (! is_array($backup_items) || empty($backup_items)) {
            self::set_session_value(self::SESSION_SELECTED_CART_ITEM_KEY, null);
            return;
        }

        self::replace_cart_contents($backup_items, true);
        self::set_session_value(
            self::SESSION_SELECTED_CART_ITEM_KEY,
            self::resolve_selected_cart_item_key($backup_items, self::get_selected_cart_item_key())
        );
        self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, null);
    }

    public static function clear_selection_state()
    {
        if (! self::has_session()) {
            return;
        }

        self::set_session_value(self::SESSION_SELECTED_CART_ITEM_KEY, null);
        self::set_session_value(self::SESSION_BACKUP_CART_ITEMS, null);
    }

    private static function is_cart_request()
    {
        return function_exists('is_cart') && is_cart() && function_exists('WC') && WC()->cart;
    }

    private static function is_checkout_request()
    {
        return function_exists('is_checkout')
            && is_checkout()
            && (! function_exists('is_order_received_page') || ! is_order_received_page())
            && function_exists('WC')
            && WC()->cart;
    }

    private static function has_session()
    {
        return function_exists('WC') && WC()->session;
    }

    private static function get_session_value($key)
    {
        if (! self::has_session()) {
            return null;
        }

        return WC()->session->get($key);
    }

    private static function set_session_value($key, $value)
    {
        if (! self::has_session()) {
            return;
        }

        WC()->session->set($key, $value);
    }

    private static function get_selected_cart_item_key()
    {
        $selected_key = self::get_session_value(self::SESSION_SELECTED_CART_ITEM_KEY);

        return is_string($selected_key) ? $selected_key : '';
    }

    private static function get_runtime_cart()
    {
        if (! function_exists('WC') || ! WC()->cart) {
            return [];
        }

        return WC()->cart->get_cart();
    }

    private static function get_cart_for_session()
    {
        $runtime_cart = self::get_runtime_cart();
        $cart_for_session = [];

        foreach ($runtime_cart as $cart_item_key => $cart_item) {
            if (! is_array($cart_item)) {
                continue;
            }

            $cart_for_session[$cart_item_key] = $cart_item;
            unset($cart_for_session[$cart_item_key]['data']);
        }

        return $cart_for_session;
    }

    private static function resolve_selected_cart_item_key($cart_items, $preferred_key = '')
    {
        if (! is_array($cart_items) || empty($cart_items)) {
            return '';
        }

        if (is_string($preferred_key) && $preferred_key !== '' && isset($cart_items[$preferred_key])) {
            return $preferred_key;
        }

        $keys = array_keys($cart_items);

        return isset($keys[0]) ? (string) $keys[0] : '';
    }

    private static function replace_cart_contents($cart_items, $persist_for_account)
    {
        if (! function_exists('WC') || ! WC()->cart) {
            return;
        }

        $runtime_cart = [];

        foreach ($cart_items as $cart_item_key => $cart_item) {
            if (! is_array($cart_item)) {
                continue;
            }

            $product_id = ! empty($cart_item['variation_id']) ? absint($cart_item['variation_id']) : absint($cart_item['product_id'] ?? 0);
            $product = $product_id ? wc_get_product($product_id) : false;

            if (! $product || ! $product->exists()) {
                continue;
            }

            $cart_item['data'] = $product;
            $cart_item['key'] = (string) $cart_item_key;
            $cart_item['quantity'] = max(1, absint($cart_item['quantity'] ?? 1));
            $runtime_cart[(string) $cart_item_key] = $cart_item;
        }

        WC()->cart->cart_contents = $runtime_cart;
        WC()->cart->removed_cart_contents = [];
        WC()->cart->calculate_totals();
        WC()->cart->set_session();

        if ($persist_for_account) {
            self::persist_cart_for_current_user($cart_items);
        }
    }

    private static function persist_cart_for_current_user($cart_items)
    {
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        update_user_meta(
            $user_id,
            '_woocommerce_persistent_cart_' . get_current_blog_id(),
            [
                'cart' => is_array($cart_items) ? $cart_items : [],
            ]
        );
    }
}
