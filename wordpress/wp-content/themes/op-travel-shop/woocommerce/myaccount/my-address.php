<?php

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();
$get_addresses = [
    'billing' => __('Thông tin liên hệ chính', 'woocommerce'),
];

if (! wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses['shipping'] = __('Địa chỉ nhận thông tin bổ sung', 'op-travel-shop');
}
?>
<section class="op-account-addresses" data-reveal>
    <header class="op-account-section__head">
        <div>
            <p class="op-kicker"><?php esc_html_e('Thông tin liên hệ', 'op-travel-shop'); ?></p>
            <h1><?php esc_html_e('Địa chỉ và thông tin dùng cho những booking tiếp theo.', 'op-travel-shop'); ?></h1>
        </div>
    </header>

    <div class="op-account-addresses__grid">
        <?php foreach ($get_addresses as $name => $title) : ?>
            <?php
            $edit_url = wc_get_endpoint_url('edit-address', $name, op_travel_get_account_url());
            $formatted_address = wc_get_account_formatted_address($name);
            ?>
            <article class="op-account-address-card">
                <div class="op-account-address-card__head">
                    <div>
                        <p class="op-kicker"><?php echo esc_html($title); ?></p>
                        <h2><?php echo esc_html($title); ?></h2>
                    </div>
                    <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Chỉnh sửa', 'op-travel-shop'); ?></a>
                </div>

                <div class="op-account-address-card__body">
                    <?php if ($formatted_address) : ?>
                        <address><?php echo wp_kses_post($formatted_address); ?></address>
                    <?php else : ?>
                        <p><?php esc_html_e('Bạn chưa lưu địa chỉ này. Hoàn thiện trước khi tạo booking tiếp theo để thanh toán nhanh hơn.', 'op-travel-shop'); ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
