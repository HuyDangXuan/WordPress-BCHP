<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<footer class="op-site-footer" role="contentinfo">
    <div class="op-shell">
        <div class="op-footer-grid">
            <div class="op-footer-brand">
                <a class="op-brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <span class="op-brand__mark">HV</span>
                    <span><?php bloginfo('name'); ?></span>
                </a>
                <p><?php esc_html_e('Một storefront tour được tổ chức như một hành trình, không phải một trang shop mặc định.', 'op-travel-shop'); ?></p>
            </div>
            <nav class="op-footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'op-travel-shop'); ?>">
                <p class="op-kicker"><?php esc_html_e('Hành trình', 'op-travel-shop'); ?></p>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/tours/')); ?>"><?php esc_html_e('Tours', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/gio-hang/')); ?>"><?php esc_html_e('Giữ chỗ', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>"><?php esc_html_e('Thanh toán', 'op-travel-shop'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>"><?php esc_html_e('Liên hệ', 'op-travel-shop'); ?></a></li>
                </ul>
            </nav>
            <div class="op-footer-contact">
                <p class="op-kicker"><?php esc_html_e('Liên hệ', 'op-travel-shop'); ?></p>
                <address>
                    <p><?php esc_html_e('Email: hello@hv-travel.vn', 'op-travel-shop'); ?></p>
                    <p><?php esc_html_e('Hotline: 1900 xxxx', 'op-travel-shop'); ?></p>
                </address>
            </div>
        </div>
        <div class="op-footer-bottom">
            <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'op-travel-shop'); ?></p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
