<?php

get_header();

if (function_exists('op_travel_storefront_render_route')) {
    $cmsRouteKey = 'page:' . absint(get_queried_object_id());

    if (op_travel_storefront_render_route($cmsRouteKey, [
        'page_id' => absint(get_queried_object_id()),
    ])) {
        get_footer();
        return;
    }
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
?>
<section class="op-contact-page">
    <section class="op-contact-hero">
        <div class="op-contact-hero__inner">
            <div data-reveal>
                <p class="op-kicker"><?php esc_html_e('HV-Travel Concierge', 'op-travel-shop'); ?></p>
                <h1><?php esc_html_e('Liên hệ đúng người trước khi chốt hành trình.', 'op-travel-shop'); ?></h1>
                <p><?php esc_html_e('Dùng biểu mẫu này khi bạn cần tư vấn tour, kiểm tra lịch khởi hành, hoặc muốn đội ngũ HV-Travel gọi lại để đi nhanh hơn từ shortlist sang booking.', 'op-travel-shop'); ?></p>
                <div class="op-contact-hero__actions">
                    <a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Xem tours đang mở bán', 'op-travel-shop'); ?></a>
                    <a class="op-button op-button--ghost" href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Mở tài khoản của tôi', 'op-travel-shop'); ?></a>
                </div>
            </div>

            <aside class="op-contact-hero__panel" data-reveal>
                <p class="op-kicker"><?php esc_html_e('Kênh nhanh', 'op-travel-shop'); ?></p>
                <ul>
                    <li>
                        <span><?php esc_html_e('Email', 'op-travel-shop'); ?></span>
                        <strong>noreply.hvtravel@gmail.com</strong>
                    </li>
                    <li>
                        <span><?php esc_html_e('Hotline', 'op-travel-shop'); ?></span>
                        <strong>0877 504 883</strong>
                    </li>
                    <li>
                        <span><?php esc_html_e('Nhịp phản hồi', 'op-travel-shop'); ?></span>
                        <strong><?php esc_html_e('Phản hồi trong ngày làm việc.', 'op-travel-shop'); ?></strong>
                    </li>
                </ul>
            </aside>
        </div>
    </section>

    <main class="op-shell op-section op-contact-body">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('op-contact-layout'); ?>>
                    <div class="op-contact-grid">
                        <section class="op-contact-main" data-reveal>
                            <div>
                                <p class="op-kicker"><?php esc_html_e('Gửi yêu cầu', 'op-travel-shop'); ?></p>
                                <h2><?php esc_html_e('Nói ngắn gọn về chuyến đi bạn đang cân nhắc.', 'op-travel-shop'); ?></h2>
                                <p><?php esc_html_e('Chúng tôi cần điểm đến, số khách, thời gian dự kiến hoặc bất kỳ lưu ý nào giúp tư vấn sát hơn thay vì trả lời chung chung.', 'op-travel-shop'); ?></p>
                            </div>
                            <div class="op-travel-contact-form-shell">
                                <?php the_content(); ?>
                            </div>
                        </section>

                        <aside class="op-contact-aside" data-reveal>
                            <div>
                                <p class="op-kicker"><?php esc_html_e('Chuẩn bị trước khi gửi', 'op-travel-shop'); ?></p>
                                <h2><?php esc_html_e('Thông tin nào giúp đội vận hành trả lời nhanh hơn?', 'op-travel-shop'); ?></h2>
                            </div>

                            <section>
                                <strong><?php esc_html_e('Nếu bạn đang so shortlist', 'op-travel-shop'); ?></strong>
                                <ul>
                                    <li><?php esc_html_e('Ghi rõ điểm đến hoặc tên tour bạn đang cân nhắc.', 'op-travel-shop'); ?></li>
                                    <li><?php esc_html_e('Cho biết tháng đi và số lượng người lớn/trẻ em.', 'op-travel-shop'); ?></li>
                                </ul>
                            </section>

                            <section>
                                <strong><?php esc_html_e('Nếu bạn đã có booking', 'op-travel-shop'); ?></strong>
                                <ul>
                                    <li><?php esc_html_e('Đính kèm mã booking hoặc email đã dùng để giữ chỗ.', 'op-travel-shop'); ?></li>
                                    <li><?php esc_html_e('Nêu rõ bạn đang cần cập nhật thanh toán, ngày đi hay thông tin hành khách.', 'op-travel-shop'); ?></li>
                                </ul>
                            </section>

                            <section>
                                <strong><?php esc_html_e('Lối tắt phù hợp', 'op-travel-shop'); ?></strong>
                                <p><?php esc_html_e('Nếu bạn đã có tài khoản, hãy vào khu tài khoản để xem lại booking gần nhất trước khi gửi thêm yêu cầu.', 'op-travel-shop'); ?></p>
                                <a href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Mở khu tài khoản', 'op-travel-shop'); ?></a>
                            </section>
                        </aside>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php esc_html_e('Chưa có nội dung để hiển thị.', 'op-travel-shop'); ?></p>
        <?php endif; ?>
    </main>
</section>
<?php
get_footer();
