<?php
/**
 * Empty cart UI for HV-Travel.
 *
 * @package op-travel-shop
 */

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$contact_url = home_url('/lien-he/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
?>
<section class="op-tour-card op-cart-empty" aria-labelledby="op-cart-empty-title">
    <div class="op-cart-empty__layout">
        <div class="op-cart-empty__copy">
            <p class="op-kicker"><?php esc_html_e('Giỏ hàng đang trống', 'op-travel-shop'); ?></p>
            <h2 id="op-cart-empty-title"><?php esc_html_e('Chưa có hành trình nào được giữ chỗ để chuyển sang bước thanh toán.', 'op-travel-shop'); ?></h2>
            <p><?php esc_html_e('Trang thanh toán chỉ mở khi bạn đã giữ chỗ ít nhất một tour.', 'op-travel-shop'); ?></p>
            <p><?php esc_html_e('Nếu bạn vừa bấm vào mục "Thanh toán" trên header, hệ thống đã đưa bạn quay lại đây vì hiện chưa có booking nào trong giỏ.', 'op-travel-shop'); ?></p>

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
