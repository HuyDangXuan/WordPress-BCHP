<?php

defined('ABSPATH') || exit;

$account_user = function_exists('op_travel_get_account_user_summary') ? op_travel_get_account_user_summary() : [];
$metrics = function_exists('op_travel_get_account_dashboard_metrics') ? op_travel_get_account_dashboard_metrics() : ['total' => 0, 'pending' => 0, 'paid' => 0];
$recent_orders = function_exists('op_travel_get_recent_account_orders') ? op_travel_get_recent_account_orders(get_current_user_id(), 3) : [];
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$orders_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', op_travel_get_account_url()) : op_travel_get_account_url();
?>
<section class="op-account-dashboard">
    <header class="op-account-hero" data-reveal>
        <div>
            <p class="op-kicker"><?php esc_html_e('Travel Control Room', 'op-travel-shop'); ?></p>
            <h1><?php printf(esc_html__('Xin chào, %s.', 'op-travel-shop'), esc_html($account_user['display_name'] ?? __('bạn', 'op-travel-shop'))); ?></h1>
            <p><?php esc_html_e('Theo dõi booking, thanh toán và những chặng khởi hành sắp tới trong một nơi gọn gàng hơn WooCommerce mặc định.', 'op-travel-shop'); ?></p>
        </div>
        <div class="op-account-hero__actions">
            <a class="op-button" href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('Xem booking của tôi', 'op-travel-shop'); ?></a>
            <a class="op-button op-button--ghost" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Quay lại tours', 'op-travel-shop'); ?></a>
        </div>
    </header>

    <section class="op-account-summary" data-reveal>
        <div class="op-account-summary__grid">
            <article>
                <span><?php esc_html_e('Booking đã tạo', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html((string) ($metrics['total'] ?? 0)); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Cần theo dõi thanh toán', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html((string) ($metrics['pending'] ?? 0)); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Đã xác nhận hợp lệ', 'op-travel-shop'); ?></span>
                <strong><?php echo esc_html((string) ($metrics['paid'] ?? 0)); ?></strong>
            </article>
        </div>
    </section>

    <section class="op-account-section" data-reveal>
        <div class="op-account-section__head">
            <div>
                <p class="op-kicker"><?php esc_html_e('Booking gần nhất', 'op-travel-shop'); ?></p>
                <h2><?php esc_html_e('Những hành trình cần bạn theo dõi trước ngày khởi hành.', 'op-travel-shop'); ?></h2>
            </div>
            <a href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('Xem toàn bộ', 'op-travel-shop'); ?></a>
        </div>

        <?php if (! empty($recent_orders)) : ?>
            <div class="op-account-orders">
                <?php foreach ($recent_orders as $order) : ?>
                    <?php
                    $card = function_exists('op_travel_build_account_order_card') ? op_travel_build_account_order_card($order) : [];
                    $order_date = $card['created_at'] instanceof WC_DateTime ? wp_date(get_option('date_format'), $card['created_at']->getTimestamp()) : '';
                    ?>
                    <article class="op-account-order-card">
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

                        <div class="op-account-order-card__actions">
                            <p><?php echo wp_kses_post($card['total_html'] ?? ''); ?></p>
                            <a class="op-button" href="<?php echo esc_url($card['primary_action_url'] ?? $orders_url); ?>"><?php echo esc_html($card['primary_action_label'] ?? __('Xem chi tiết booking', 'op-travel-shop')); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <article class="op-account-empty">
                <p class="op-kicker"><?php esc_html_e('Chưa có booking', 'op-travel-shop'); ?></p>
                <h3><?php esc_html_e('Bạn chưa lưu hành trình nào trong tài khoản này.', 'op-travel-shop'); ?></h3>
                <p><?php esc_html_e('Khám phá shortlist tour để bắt đầu một booking mới và quay lại đây khi cần theo dõi trạng thái thanh toán.', 'op-travel-shop'); ?></p>
                <a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Khám phá tours', 'op-travel-shop'); ?></a>
            </article>
        <?php endif; ?>
    </section>
</section>
