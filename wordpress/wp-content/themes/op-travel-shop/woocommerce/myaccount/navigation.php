<?php

defined('ABSPATH') || exit;

$account_user = function_exists('op_travel_get_account_user_summary') ? op_travel_get_account_user_summary() : [];
$menu_items = function_exists('wc_get_account_menu_items') ? wc_get_account_menu_items() : [];
?>
<nav class="woocommerce-MyAccount-navigation op-account-nav" aria-label="<?php esc_attr_e('Điều hướng tài khoản', 'op-travel-shop'); ?>">
    <div class="op-account-nav__summary">
        <span class="op-account-nav__badge"><?php echo esc_html($account_user['initials'] ?? 'HV'); ?></span>
        <div>
            <p class="op-kicker"><?php esc_html_e('HV-Travel Account', 'op-travel-shop'); ?></p>
            <h2><?php echo esc_html($account_user['display_name'] ?? __('Khách HV-Travel', 'op-travel-shop')); ?></h2>
            <p><?php esc_html_e('Booking của tôi và trạng thái thanh toán gần nhất.', 'op-travel-shop'); ?></p>
            <?php if (! empty($account_user['secondary_label'])) : ?>
                <p><?php echo esc_html($account_user['secondary_label']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <ul>
        <?php foreach ($menu_items as $endpoint => $label) : ?>
            <?php $classes = apply_filters('woocommerce_account_menu_item_classes', [], $endpoint); ?>
            <li class="<?php echo esc_attr(trim(implode(' ', array_filter((array) $classes)))); ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
                    <span><?php echo esc_html($label); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
