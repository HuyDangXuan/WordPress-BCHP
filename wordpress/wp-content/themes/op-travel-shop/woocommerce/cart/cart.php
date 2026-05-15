<?php

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/thanh-toan/');
$contact_url = home_url('/lien-he/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
$cart_items = WC()->cart ? WC()->cart->get_cart() : [];
$is_empty_cart = ! WC()->cart || WC()->cart->is_empty();
$selected_cart_item_key = op_travel_get_selected_checkout_cart_item_key($cart_items);
$selected_cart_item = $selected_cart_item_key !== '' && isset($cart_items[$selected_cart_item_key]) ? $cart_items[$selected_cart_item_key] : null;
$selected_cart_total_html = $selected_cart_item ? op_travel_get_cart_item_total_html($selected_cart_item) : '';
$selected_cart_item_name = '';
$selected_cart_item_subtotal_html = $selected_cart_total_html;
$remaining_cart_count = max(0, count($cart_items) - ($selected_cart_item ? 1 : 0));

if ($selected_cart_item && ! empty($selected_cart_item['data']) && $selected_cart_item['data'] instanceof WC_Product) {
    $selected_cart_item_name = $selected_cart_item['data']->get_name();
}

$payment_status_labels = [
    'pending' => __('Chờ thanh toán', 'op-travel-shop'),
    'paid' => __('Đã thanh toán', 'op-travel-shop'),
    'failed' => __('Thanh toán lỗi', 'op-travel-shop'),
    'expired' => __('Đã hết hạn', 'op-travel-shop'),
    'cancelled' => __('Đã hủy', 'op-travel-shop'),
];
?>
<main class="op-shell op-section">
    <?php
    op_travel_render_breadcrumb([
        ['label' => __('Trang chủ', 'op-travel-shop'), 'url' => home_url('/')],
        ['label' => __('Tours', 'op-travel-shop'), 'url' => $shop_url],
        ['label' => __('Giỏ hàng', 'op-travel-shop'), 'url' => ''],
    ]);

    op_travel_render_step_progress(2);
    ?>

    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('Bước 2 · Xác nhận giữ chỗ', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Xác nhận giữ chỗ trước khi chuyển sang thanh toán.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Rà soát thông tin booking bên dưới, sau đó tiếp tục sang bước chọn phương thức thanh toán.', 'op-travel-shop'); ?></p>
    </header>

    <?php if ($is_empty_cart) : ?>
        <section class="op-tour-card op-cart-empty">
            <div class="op-cart-empty__layout">
                <div class="op-cart-empty__copy">
                    <p class="op-kicker"><?php esc_html_e('Giỏ hàng đang trống', 'op-travel-shop'); ?></p>
                    <h2><?php esc_html_e('Chưa có hành trình nào được giữ chỗ để chuyển sang bước thanh toán.', 'op-travel-shop'); ?></h2>
                    <p><?php esc_html_e('Trang thanh toán chỉ mở khi bạn đã giữ chỗ ít nhất một tour.', 'op-travel-shop'); ?></p>
                    <p><?php esc_html_e('Nếu bạn vừa bấm vào mục "Thanh toán" trên header, WooCommerce đã đưa bạn quay lại đây vì hiện chưa có booking nào trong giỏ.', 'op-travel-shop'); ?></p>
                    <div class="op-cart-empty__actions">
                        <a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Quay lại shortlist tour', 'op-travel-shop'); ?></a>
                        <a class="op-button op-button--ghost" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Cần tư vấn lịch trình', 'op-travel-shop'); ?></a>
                    </div>
                    <p class="op-cart-empty__note">
                        <?php
                        printf(
                            wp_kses(
                                __('Muốn xem lại các booking trước đó? Mở <a href="%s">khu vực tài khoản</a> để kiểm tra trạng thái đơn.', 'op-travel-shop'),
                                ['a' => ['href' => []]]
                            ),
                            esc_url($account_url)
                        );
                        ?>
                    </p>
                </div>

                <aside class="op-summary-panel op-cart-empty__aside">
                    <p class="op-kicker"><?php esc_html_e('Đi tiếp như thế nào', 'op-travel-shop'); ?></p>
                    <ol class="op-cart-empty__steps">
                        <li><?php esc_html_e('Chọn tour phù hợp và hoàn tất form giữ chỗ.', 'op-travel-shop'); ?></li>
                        <li><?php esc_html_e('Quay lại giỏ hàng để rà soát ngày khởi hành, số khách và ghi chú.', 'op-travel-shop'); ?></li>
                        <li><?php esc_html_e('Chuyển sang bước thanh toán khi booking đã sẵn sàng.', 'op-travel-shop'); ?></li>
                    </ol>
                    <p><?php esc_html_e('Khi đã có tour trong giỏ, hai mục "Giữ chỗ" và "Thanh toán" trên header sẽ dẫn bạn đi tiếp đúng bước.', 'op-travel-shop'); ?></p>
                </aside>
            </div>
        </section>
    <?php else : ?>
        <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
            <div class="op-cart-tour-list">
                <?php foreach ($cart_items as $cart_item_key => $cart_item) : ?>
                    <?php
                    $_product = $cart_item['data'];
                    if (! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0) {
                        continue;
                    }

                    $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
                    $booking = op_travel_get_cart_booking_snapshot($cart_item);
                    $pid = $_product->get_id();
                    $duration_text = get_post_meta($pid, '_duration_text', true);
                    $departure_city = get_post_meta($pid, '_departure_city', true);
                    $payment_status = $booking['payment_status'] ?? 'pending';
                    $payment_status_label = $payment_status_labels[$payment_status] ?? $payment_status;
                    $cart_item_total_html = op_travel_get_cart_item_total_html($cart_item);
                    ?>
                    <article class="op-tour-card op-cart-tour-card op-is-loading">
                        <a class="op-cart-tour-card__media op-skeleton-media op-is-loading" href="<?php echo esc_url($product_permalink ?: '#'); ?>" aria-label="<?php echo esc_attr($_product->get_name()); ?>">
                            <?php echo $_product->get_image('medium'); ?>
                        </a>

                        <div class="op-cart-tour-card__content">
                            <div class="op-cart-selection">
                                <label class="op-cart-radio">
                                    <input
                                        type="radio"
                                        name="op_travel_selected_cart_item"
                                        value="<?php echo esc_attr($cart_item_key); ?>"
                                        data-op-cart-radio
                                        data-op-cart-tour-name="<?php echo esc_attr(wp_strip_all_tags($_product->get_name())); ?>"
                                        data-op-cart-subtotal-html="<?php echo esc_attr($cart_item_total_html); ?>"
                                        data-op-cart-total-html="<?php echo esc_attr($cart_item_total_html); ?>"
                                        <?php checked($selected_cart_item_key, $cart_item_key); ?>
                                    >
                                    <span class="op-cart-radio__copy">
                                        <strong><?php esc_html_e('Thanh toán tour đã chọn này', 'op-travel-shop'); ?></strong>
                                        <small><?php esc_html_e('Các tour còn lại vẫn tiếp tục nằm trong giỏ hàng.', 'op-travel-shop'); ?></small>
                                    </span>
                                </label>
                            </div>

                            <div class="op-cart-tour-card__header">
                                <div>
                                    <p class="op-kicker"><?php esc_html_e('Tour đã chọn', 'op-travel-shop'); ?></p>
                                    <h3>
                                        <?php if ($product_permalink) : ?>
                                            <a href="<?php echo esc_url($product_permalink); ?>"><?php echo wp_kses_post($_product->get_name()); ?></a>
                                        <?php else : ?>
                                            <?php echo wp_kses_post($_product->get_name()); ?>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <span class="op-status-pill op-status-pill--pending"><?php echo esc_html($payment_status_label); ?></span>
                            </div>

                            <div class="op-cart-meta-row">
                                <span class="op-meta-detail__item">
                                    <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
                                    <?php echo wp_kses_post(WC()->cart->get_product_price($_product)); ?>
                                </span>
                                <span class="op-meta-detail__item"><?php echo esc_html(sprintf(__('Số lượng: %d', 'op-travel-shop'), $cart_item['quantity'])); ?></span>
                                <?php if ($duration_text) : ?>
                                    <span class="op-meta-detail__item">
                                        <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                                        <?php echo esc_html($duration_text); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($departure_city) : ?>
                                    <span class="op-meta-detail__item">
                                        <span class="op-meta-detail__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                                        <?php echo esc_html($departure_city); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($booking) : ?>
                                <div class="op-cart-booking-grid">
                                    <?php if ($booking['tour_code']) : ?>
                                        <p><span><?php esc_html_e('Mã tour', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['tour_code']); ?></strong></p>
                                    <?php endif; ?>
                                    <p><span><?php esc_html_e('Ngày khởi hành', 'op-travel-shop'); ?></span><strong><time datetime="<?php echo esc_attr($booking['departure_date']); ?>"><?php echo esc_html(op_travel_format_departure_date($booking['departure_date'])); ?></time></strong></p>
                                    <p><span><?php esc_html_e('Số khách', 'op-travel-shop'); ?></span><strong><?php echo esc_html(sprintf(__('%d người lớn, %d trẻ em', 'op-travel-shop'), $booking['adult_count'], $booking['child_count'])); ?></strong></p>
                                    <p><span><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></span><strong><?php echo esc_html($payment_status_label); ?></strong></p>
                                    <?php if ($booking['customer_note']) : ?>
                                        <p class="op-cart-booking-grid__wide"><span><?php esc_html_e('Ghi chú', 'op-travel-shop'); ?></span><strong><?php echo esc_html($booking['customer_note']); ?></strong></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="op-checkout-grid op-cart-actions-grid">
                <section class="op-summary-panel op-cart-total-panel">
                    <p class="op-kicker"><?php esc_html_e('Tổng giữ chỗ', 'op-travel-shop'); ?></p>
                    <p class="op-cart-selection-note"><?php esc_html_e('Bạn đang chốt thanh toán cho một tour đã chọn trong giỏ.', 'op-travel-shop'); ?></p>
                    <p class="op-cart-selected-tour" data-op-cart-selected-tour><?php echo esc_html($selected_cart_item_name); ?></p>
                    <div class="op-cart-summary-row">
                        <span><?php esc_html_e('Tạm tính tour chọn', 'op-travel-shop'); ?></span>
                        <strong data-op-cart-selected-subtotal><?php echo wp_kses_post($selected_cart_item_subtotal_html ?: WC()->cart->get_cart_subtotal()); ?></strong>
                    </div>
                    <div class="op-cart-summary-row op-cart-summary-row--total">
                        <span><?php esc_html_e('Tổng cộng tour chọn', 'op-travel-shop'); ?></span>
                        <strong data-op-cart-selected-total><?php echo wp_kses_post($selected_cart_total_html ?: WC()->cart->get_total()); ?></strong>
                    </div>
                    <?php if ($remaining_cart_count > 0) : ?>
                        <p class="op-cart-selection-note">
                            <?php
                            echo esc_html(sprintf(
                                _n('Còn %d tour khác tiếp tục được giữ trong giỏ.', 'Còn %d tour khác tiếp tục được giữ trong giỏ.', $remaining_cart_count, 'op-travel-shop'),
                                $remaining_cart_count
                            ));
                            ?>
                        </p>
                        <div class="op-cart-summary-row">
                            <span><?php esc_html_e('Tổng toàn bộ giỏ', 'op-travel-shop'); ?></span>
                            <strong><?php echo wp_kses_post(WC()->cart->get_total()); ?></strong>
                        </div>
                    <?php endif; ?>
                </section>
                <section class="op-summary-panel op-cart-next-panel">
                    <p class="op-kicker"><?php esc_html_e('Tiếp tục', 'op-travel-shop'); ?></p>
                    <p><?php esc_html_e('Tiếp tục thanh toán cho đúng tour bạn muốn xử lý trước. Các booking còn lại sẽ ở lại trong giỏ để xử lý ở lượt tiếp theo.', 'op-travel-shop'); ?></p>
                    <button class="op-button" type="submit" name="op_travel_checkout_selected_booking" value="1" data-op-loading-link data-op-skeleton-target=".op-cart-actions-grid"><?php esc_html_e('Thanh toán tour đã chọn', 'op-travel-shop'); ?></button>
                </section>
            </div>

            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
        </form>
    <?php endif; ?>
</main>
