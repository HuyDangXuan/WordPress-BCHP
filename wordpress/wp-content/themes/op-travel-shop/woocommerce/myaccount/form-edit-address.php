<?php

defined('ABSPATH') || exit;

$page_title = ('billing' === $load_address)
    ? __('Thông tin liên hệ chính', 'op-travel-shop')
    : __('Địa chỉ nhận thông tin bổ sung', 'op-travel-shop');

$page_description = ('billing' === $load_address)
    ? __('Thông tin này được dùng mặc định ở bước booking và giúp đội ngũ xác nhận nhanh hơn sau khi thanh toán.', 'op-travel-shop')
    : __('Lưu thêm một địa chỉ riêng khi bạn muốn nhận tài liệu hoặc thông tin hậu booking khác với thông tin liên hệ chính.', 'op-travel-shop');

$address_book_url = wc_get_endpoint_url('edit-address', '', op_travel_get_account_url());

$highlights = ('billing' === $load_address)
    ? [
        [
            'kicker' => __('Áp dụng mặc định', 'op-travel-shop'),
            'title' => __('Tự điền cho các booking mới', 'op-travel-shop'),
            'body' => __('Tên, số điện thoại và địa chỉ này sẽ được ưu tiên dùng khi bạn đặt tour tiếp theo.', 'op-travel-shop'),
        ],
        [
            'kicker' => __('Hỗ trợ xác nhận', 'op-travel-shop'),
            'title' => __('Giữ thông tin luôn chính xác', 'op-travel-shop'),
            'body' => __('Thông tin rõ ràng giúp hệ thống thanh toán và đội ngũ hỗ trợ xử lý booking thuận hơn.', 'op-travel-shop'),
        ],
    ]
    : [
        [
            'kicker' => __('Địa chỉ phụ', 'op-travel-shop'),
            'title' => __('Tách riêng nơi nhận thông tin', 'op-travel-shop'),
            'body' => __('Phù hợp khi bạn muốn nhận tài liệu hoặc thông tin hậu booking ở một địa chỉ khác.', 'op-travel-shop'),
        ],
        [
            'kicker' => __('Linh hoạt hơn', 'op-travel-shop'),
            'title' => __('Giữ liên hệ chính không bị xáo trộn', 'op-travel-shop'),
            'body' => __('Bạn vẫn có thể lưu một địa chỉ bổ sung mà không thay đổi bộ thông tin liên hệ mặc định.', 'op-travel-shop'),
        ],
    ];

$footer_note = ('billing' === $load_address)
    ? __('Đây là bộ thông tin chính hệ thống sẽ ưu tiên dùng cho những lần booking tiếp theo.', 'op-travel-shop')
    : __('Địa chỉ này chỉ dùng như một lựa chọn bổ sung và không thay thế thông tin liên hệ chính của bạn.', 'op-travel-shop');

do_action('woocommerce_before_edit_account_address_form');
?>

<?php if (! $load_address) : ?>
    <?php wc_get_template('myaccount/my-address.php'); ?>
<?php else : ?>
    <section class="op-account-section op-account-form-shell op-account-address-editor" data-reveal>
        <header class="op-account-section__head">
            <div>
                <p class="op-kicker"><?php esc_html_e('Cập nhật địa chỉ', 'op-travel-shop'); ?></p>
                <h1><?php echo esc_html(apply_filters('woocommerce_my_account_edit_address_title', $page_title, $load_address)); ?></h1>
                <p class="op-account-address-editor__lead"><?php echo esc_html($page_description); ?></p>
            </div>
            <a class="op-button op-button--ghost op-account-address-editor__back" href="<?php echo esc_url($address_book_url); ?>">
                <?php esc_html_e('Quay lại sổ địa chỉ', 'op-travel-shop'); ?>
            </a>
        </header>

        <div class="op-account-address-editor__notes">
            <?php foreach ($highlights as $highlight) : ?>
                <article>
                    <p class="op-kicker"><?php echo esc_html($highlight['kicker']); ?></p>
                    <h2><?php echo esc_html($highlight['title']); ?></h2>
                    <p><?php echo esc_html($highlight['body']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <form method="post" class="woocommerce-AddressForm address op-account-form op-account-address-form" novalidate>
            <div class="woocommerce-address-fields">
                <?php do_action("woocommerce_before_edit_address_form_{$load_address}"); ?>

                <div class="woocommerce-address-fields__field-wrapper op-account-form__grid">
                    <?php foreach ($address as $key => $field) : ?>
                        <?php woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value'] ?? '')); ?>
                    <?php endforeach; ?>
                </div>

                <?php do_action("woocommerce_after_edit_address_form_{$load_address}"); ?>

                <div class="op-account-address-form__actions">
                    <p class="op-account-address-form__hint"><?php echo esc_html($footer_note); ?></p>
                    <div class="op-account-address-form__actions-group">
                        <a class="op-button op-button--ghost" href="<?php echo esc_url($address_book_url); ?>">
                            <?php esc_html_e('Quay lại', 'op-travel-shop'); ?>
                        </a>
                        <button type="submit" class="button op-button" name="save_address" value="<?php esc_attr_e('Lưu địa chỉ', 'op-travel-shop'); ?>"><?php esc_html_e('Lưu địa chỉ', 'op-travel-shop'); ?></button>
                    </div>
                    <?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
                    <input type="hidden" name="action" value="edit_address" />
                </div>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php do_action('woocommerce_after_edit_account_address_form'); ?>
