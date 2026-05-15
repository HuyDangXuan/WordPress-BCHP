<?php

defined('ABSPATH') || exit;

$user = wp_get_current_user();

do_action('woocommerce_before_edit_account_form');
?>
<section class="op-account-section op-account-form-shell" data-reveal>
    <header class="op-account-section__head">
        <div>
            <p class="op-kicker"><?php esc_html_e('Hồ sơ cá nhân', 'op-travel-shop'); ?></p>
            <h1><?php esc_html_e('Cập nhật thông tin dùng cho những lần booking tiếp theo.', 'op-travel-shop'); ?></h1>
        </div>
    </header>

    <form class="woocommerce-EditAccountForm edit-account op-account-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
        <div class="op-account-form__grid">
            <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                <label for="account_first_name"><?php esc_html_e('Tên', 'woocommerce'); ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
                <label for="account_last_name"><?php esc_html_e('Họ', 'woocommerce'); ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="account_display_name"><?php esc_html_e('Tên hiển thị', 'woocommerce'); ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="account_email"><?php esc_html_e('Email', 'woocommerce'); ?></label>
                <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" />
            </p>
        </div>

        <fieldset>
            <legend><?php esc_html_e('Đổi mật khẩu', 'op-travel-shop'); ?></legend>

            <div class="op-account-form__grid">
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password_current"><?php esc_html_e('Mật khẩu hiện tại', 'woocommerce'); ?></label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="off" />
                </p>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password_1"><?php esc_html_e('Mật khẩu mới', 'woocommerce'); ?></label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="off" />
                </p>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password_2"><?php esc_html_e('Xác nhận mật khẩu mới', 'woocommerce'); ?></label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="off" />
                </p>
            </div>
        </fieldset>

        <?php do_action('woocommerce_edit_account_form'); ?>

        <p>
            <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
            <button type="submit" class="woocommerce-Button button op-button" name="save_account_details" value="<?php esc_attr_e('Lưu thay đổi', 'op-travel-shop'); ?>"><?php esc_html_e('Lưu thay đổi', 'op-travel-shop'); ?></button>
            <input type="hidden" name="action" value="save_account_details" />
        </p>
    </form>
</section>
<?php do_action('woocommerce_after_edit_account_form'); ?>
