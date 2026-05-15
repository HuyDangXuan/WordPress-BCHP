<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
$login_redirect = $account_url;

if (isset($_GET['redirect_to']) && is_scalar($_GET['redirect_to'])) {
    $login_redirect = wp_validate_redirect(esc_url_raw(wp_unslash($_GET['redirect_to'])), $account_url);
}

$show_registration = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$otp_token = isset($_GET['otp_token']) ? sanitize_text_field(wp_unslash($_GET['otp_token'])) : '';
$pending_email = class_exists('OPTravelCore\\CustomerRegistrationOtp') ? OPTravelCore\CustomerRegistrationOtp::get_pending_email($otp_token) : '';
$is_otp_step = $otp_token && $pending_email;
?>

<section class="op-auth-shell" data-reveal>
    <div class="op-auth-intro">
        <p class="op-kicker"><?php esc_html_e('Tài khoản HV Travel', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Theo dõi booking, thanh toán và lịch khởi hành trong một nơi.', 'op-travel-shop'); ?></h1>
        <p><?php esc_html_e('Đăng nhập để xem lại hành trình đã giữ chỗ, hoặc tạo tài khoản mới trước khi hoàn tất booking.', 'op-travel-shop'); ?></p>
    </div>

    <div class="op-auth-grid <?php echo esc_attr($show_registration ? 'op-auth-grid--dual' : ''); ?>">
        <section class="op-auth-panel op-auth-panel--login" id="op-login">
            <div class="op-auth-panel__head">
                <p class="op-kicker"><?php esc_html_e('Đăng nhập', 'op-travel-shop'); ?></p>
                <h2><?php esc_html_e('Quay lại tài khoản của bạn.', 'op-travel-shop'); ?></h2>
            </div>

            <form class="woocommerce-form woocommerce-form-login login op-auth-form" method="post" action="<?php echo esc_url($account_url); ?>" data-op-loading-form data-op-skeleton-target=".op-auth-panel--login">
                <?php do_action('woocommerce_login_form_start'); ?>

                <p class="form-row form-row-wide">
                    <label for="username"><?php esc_html_e('Email hoặc tên đăng nhập', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" />
                </p>
                <p class="form-row form-row-wide">
                    <label for="password"><?php esc_html_e('Mật khẩu', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
                </p>

                <?php do_action('woocommerce_login_form'); ?>

                <p class="form-row op-auth-form__actions">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme op-auth-remember">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                        <span><?php esc_html_e('Ghi nhớ đăng nhập', 'woocommerce'); ?></span>
                    </label>
                    <input type="hidden" name="redirect" value="<?php echo esc_attr($login_redirect); ?>" />
                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-login__submit op-button" name="login" value="<?php esc_attr_e('Đăng nhập', 'op-travel-shop'); ?>"><?php esc_html_e('Đăng nhập', 'op-travel-shop'); ?></button>
                </p>
                <p class="woocommerce-LostPassword lost_password">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Quên mật khẩu?', 'woocommerce'); ?></a>
                </p>

                <?php do_action('woocommerce_login_form_end'); ?>
            </form>
        </section>

        <?php if ($show_registration) : ?>
            <section class="op-auth-panel op-auth-panel--register" id="op-register">
                <div class="op-auth-panel__head">
                    <p class="op-kicker"><?php esc_html_e('Đăng ký', 'op-travel-shop'); ?></p>
                    <h2><?php echo esc_html($is_otp_step ? __('Nhập OTP và tạo mật khẩu.', 'op-travel-shop') : __('Nhận OTP qua email để tạo tài khoản.', 'op-travel-shop')); ?></h2>
                </div>

                <?php if ($is_otp_step) : ?>
                    <form method="post" class="op-auth-form op-auth-otp-step" action="<?php echo esc_url(add_query_arg(['op_auth' => 'register', 'otp_token' => $otp_token], $account_url)); ?>" data-op-loading-form data-op-skeleton-target=".op-auth-panel--register">
                        <input type="hidden" name="op_travel_registration_action" value="verify_otp" />
                        <input type="hidden" name="otp_token" value="<?php echo esc_attr($otp_token); ?>" />
                        <?php wp_nonce_field('op_travel_registration_verify_otp', 'op_travel_registration_verify_otp_nonce'); ?>

                        <p class="op-auth-note">
                            <?php esc_html_e('OTP đã được gửi tới email bạn vừa nhập. Email được giữ ẩn ở bước này để tập trung xác thực và tạo mật khẩu.', 'op-travel-shop'); ?>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="otp_code"><?php esc_html_e('Mã OTP', 'op-travel-shop'); ?>&nbsp;<span class="required">*</span></label>
                            <input type="text" class="input-text" name="otp_code" id="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
                        </p>
                        <div class="op-auth-password-grid">
                            <p class="form-row form-row-wide">
                                <label for="reg_password"><?php esc_html_e('Mật khẩu', 'op-travel-shop'); ?>&nbsp;<span class="required">*</span></label>
                                <input type="password" class="input-text" name="password" id="reg_password" autocomplete="new-password" />
                            </p>
                            <p class="form-row form-row-wide">
                                <label for="reg_password_confirm"><?php esc_html_e('Nhập lại mật khẩu', 'op-travel-shop'); ?>&nbsp;<span class="required">*</span></label>
                                <input type="password" class="input-text" name="password_confirm" id="reg_password_confirm" autocomplete="new-password" />
                            </p>
                        </div>
                        <p class="woocommerce-form-row form-row op-auth-form__actions">
                            <a class="op-auth-secondary-link" href="<?php echo esc_url(add_query_arg('op_auth', 'register', $account_url)); ?>#op-register"><?php esc_html_e('Đổi email', 'op-travel-shop'); ?></a>
                            <button type="submit" class="woocommerce-Button button op-button" value="<?php esc_attr_e('Xác thực OTP', 'op-travel-shop'); ?>"><?php esc_html_e('Xác thực OTP', 'op-travel-shop'); ?></button>
                        </p>
                    </form>
                    <form method="post" class="op-auth-resend-form" action="<?php echo esc_url(add_query_arg(['op_auth' => 'register', 'otp_token' => $otp_token], $account_url)); ?>" data-op-loading-form data-op-skeleton-target=".op-auth-panel--register">
                        <input type="hidden" name="op_travel_registration_action" value="resend_otp" />
                        <input type="hidden" name="otp_token" value="<?php echo esc_attr($otp_token); ?>" />
                        <?php wp_nonce_field('op_travel_registration_resend_otp', 'op_travel_registration_resend_otp_nonce'); ?>
                        <span class="op-auth-secondary-text"><?php esc_html_e('Chưa thấy email?', 'op-travel-shop'); ?></span>
                        <button type="submit" class="op-auth-secondary-link op-auth-link-button" value="<?php esc_attr_e('Gửi lại OTP', 'op-travel-shop'); ?>"><?php esc_html_e('Gửi lại OTP', 'op-travel-shop'); ?></button>
                    </form>
                <?php else : ?>
                    <form method="post" class="op-auth-form op-auth-email-step" action="<?php echo esc_url(add_query_arg('op_auth', 'register', $account_url)); ?>" data-op-loading-form data-op-skeleton-target=".op-auth-panel--register">
                        <input type="hidden" name="op_travel_registration_action" value="send_otp" />
                        <?php wp_nonce_field('op_travel_registration_send_otp', 'op_travel_registration_send_otp_nonce'); ?>

                        <p class="form-row form-row-wide">
                            <label for="reg_email"><?php esc_html_e('Email', 'op-travel-shop'); ?>&nbsp;<span class="required">*</span></label>
                            <input type="email" class="input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" />
                        </p>
                        <p class="op-auth-note"><?php esc_html_e('Chúng tôi sẽ gửi OTP 6 số tới email này. Sau khi xác thực, bạn sẽ đặt mật khẩu và được đăng nhập tự động.', 'op-travel-shop'); ?></p>
                        <p class="woocommerce-form-row form-row op-auth-form__actions">
                            <span class="op-auth-secondary-text"><?php esc_html_e('OTP hết hạn sau 10 phút.', 'op-travel-shop'); ?></span>
                            <button type="submit" class="woocommerce-Button button op-button" value="<?php esc_attr_e('Gửi OTP', 'op-travel-shop'); ?>"><?php esc_html_e('Gửi OTP', 'op-travel-shop'); ?></button>
                        </p>
                    </form>
                <?php endif; ?>
            </section>
        <?php else : ?>
            <section class="op-auth-panel op-auth-panel--register" id="op-register">
                <div class="op-auth-panel__head">
                    <p class="op-kicker"><?php esc_html_e('Đăng ký', 'op-travel-shop'); ?></p>
                    <h2><?php esc_html_e('Đăng ký tài khoản đang tắt.', 'op-travel-shop'); ?></h2>
                    <p><?php esc_html_e('Bật tùy chọn tạo tài khoản ở WooCommerce để hiển thị form đăng ký tại đây.', 'op-travel-shop'); ?></p>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php do_action('woocommerce_after_customer_login_form'); ?>
