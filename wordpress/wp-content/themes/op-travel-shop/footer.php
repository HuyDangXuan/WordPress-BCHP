<?php

if (! defined('ABSPATH')) {
    exit;
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
$contact_url = home_url('/lien-he/');
?>
<footer class="op-site-footer" role="contentinfo">
    <div class="op-shell">
        <div class="op-footer-grid">
            <div class="op-footer-brand">
                <a class="op-brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <span class="op-brand__mark">HV</span>
                    <span class="op-brand__text"><?php bloginfo('name'); ?></span>
                </a>
                <p><?php esc_html_e('Một storefront tour được dàn như một shortlist biên tập: dễ duyệt, rõ booking và giữ nhịp nhẹ từ lúc xem tour tới khi thanh toán.', 'op-travel-shop'); ?></p>
            </div>
            <nav class="op-footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'op-travel-shop'); ?>">
                <p class="op-kicker"><?php esc_html_e('Khám phá', 'op-travel-shop'); ?></p>
                <ul>
                    <li><a href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Tours đang mở bán', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/gio-hang/')); ?>"><?php esc_html_e('Giỏ tour', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>"><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Liên hệ tư vấn', 'op-travel-shop'); ?></a></li>
                </ul>
            </nav>
            <nav class="op-footer-nav" aria-label="<?php esc_attr_e('Footer support', 'op-travel-shop'); ?>">
                <p class="op-kicker"><?php esc_html_e('Hỗ trợ', 'op-travel-shop'); ?></p>
                <ul>
                    <li><a href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Tài khoản', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Gửi yêu cầu', 'op-travel-shop'); ?></a></li>
                    <li><a href="mailto:noreply.hvtravel@gmail.com">noreply.hvtravel@gmail.com</a></li>
                    <li><a href="tel:0877504883">0877 504 883</a></li>
                </ul>
            </nav>
        </div>
        <div class="op-footer-bottom">
            <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Curated travel storefront experience.', 'op-travel-shop'); ?></p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
