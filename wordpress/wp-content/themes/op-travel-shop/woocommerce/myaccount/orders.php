<?php

defined('ABSPATH') || exit;

$orders = function_exists('op_travel_get_recent_account_orders') ? op_travel_get_recent_account_orders(get_current_user_id(), -1) : [];
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
?>
<section class="op-account-section op-account-orders">
    <header class="op-account-section__head">
        <div>
            <p class="op-kicker"><?php esc_html_e('Booking của tôi', 'op-travel-shop'); ?></p>
            <h1><?php esc_html_e('Tất cả hành trình đã giữ chỗ trong tài khoản này.', 'op-travel-shop'); ?></h1>
        </div>
    </header>

    <?php if (! empty($orders)) : ?>
        <div class="op-account-orders__list">
            <?php foreach ($orders as $order) : ?>
                <?php
                $card = function_exists('op_travel_build_account_order_card') ? op_travel_build_account_order_card($order) : [];
                $order_date = $card['created_at'] instanceof WC_DateTime ? wp_date(get_option('date_format'), $card['created_at']->getTimestamp()) : '';
                ?>
                <article class="op-account-order-card" data-reveal>
                    <div class="op-account-order-card__head">
                        <div>
                            <p class="op-kicker"><?php printf(esc_html__('Booking #%s', 'op-travel-shop'), esc_html($card['order_number'] ?? '')); ?></p>
                            <h3><?php echo esc_html($card['booking_name'] ?: __('Hành trình đang cập nhật', 'op-travel-shop')); ?></h3>
                        </div>
                        <span class="op-status-pill op-status-pill--<?php echo esc_attr($card['payment_state'] ?? 'pending'); ?>">
                            <?php echo esc_html($card['payment_state_label'] ?? __('Chờ thanh toán', 'op-travel-shop')); ?>
                        </span>
                    </div>

                    <div class="op-account-order-card__grid">
                        <?php if (! empty($card['tour_code'])) : ?>
                            <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($card['tour_code']); ?></strong></p>
                        <?php endif; ?>
                        <?php if (! empty($card['departure_date'])) : ?>
                            <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><?php echo esc_html(op_travel_format_departure_date($card['departure_date'])); ?></strong></p>
                        <?php endif; ?>
                        <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html($card['guest_summary'] ?? ''); ?></strong></p>
                        <?php if ($order_date !== '') : ?>
                            <p><span><?php esc_html_e('Tạo booking', 'op-travel-shop'); ?></span><strong><?php echo esc_html($order_date); ?></strong></p>
                        <?php endif; ?>
                    </div>

                    <?php if (! empty($card['customer_note'])) : ?>
                        <p class="op-account-order-card__note"><?php echo esc_html($card['customer_note']); ?></p>
                    <?php endif; ?>

                    <div class="op-account-order-card__actions">
                        <p><?php echo wp_kses_post($card['total_html'] ?? ''); ?></p>
                        <a class="op-button" href="<?php echo esc_url($card['primary_action_url'] ?? op_travel_get_account_url()); ?>"><?php echo esc_html($card['primary_action_label'] ?? __('Xem chi tiết booking', 'op-travel-shop')); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <article class="op-account-empty">
            <p class="op-kicker"><?php esc_html_e('Danh sách còn trống', 'op-travel-shop'); ?></p>
            <h3><?php esc_html_e('Bạn chưa có booking nào để theo dõi.', 'op-travel-shop'); ?></h3>
            <p><?php esc_html_e('Bắt đầu từ một tour phù hợp rồi quay lại đây để xem trạng thái giữ chỗ và thanh toán.', 'op-travel-shop'); ?></p>
            <a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Khám phá tours', 'op-travel-shop'); ?></a>
        </article>
    <?php endif; ?>
</section>
