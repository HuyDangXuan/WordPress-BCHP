<?php

get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tours/');
$featured_query = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 6,
]);
$destinations = get_terms([
    'taxonomy' => 'destination',
    'hide_empty' => false,
    'number' => 4,
]);
$styles = get_terms([
    'taxonomy' => 'tour_style',
    'hide_empty' => false,
    'number' => 4,
]);
?>
<section class="op-hero">
    <div class="op-hero__inner">
        <div data-reveal>
            <p class="op-kicker"><?php esc_html_e('HV-Travel Premium Journey', 'op-travel-shop'); ?></p>
            <h1 class="op-hero__headline"><?php esc_html_e('Hành trình đáng nhớ bắt đầu từ đây.', 'op-travel-shop'); ?></h1>
            <p class="op-hero__body"><?php esc_html_e('OP Travel Shop biến WooCommerce thành một luxury booking journey với taxonomy du lịch, thông tin tour rõ ràng và nhịp chuyển đổi được giữ xuyên suốt từ chọn tour tới hoàn tất.', 'op-travel-shop'); ?></p>
            <div class="op-hero__actions">
                <a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Khám phá shortlist tour', 'op-travel-shop'); ?></a>
                <a class="op-button op-button--ghost" href="#workflow"><?php esc_html_e('Xem hành trình 4 bước', 'op-travel-shop'); ?></a>
            </div>
        </div>
        <aside class="op-hero__aside" data-reveal>
            <div class="op-hero__badge">
                <p class="op-kicker"><?php esc_html_e('Travel-first Commerce', 'op-travel-shop'); ?></p>
                <p><?php esc_html_e('MySQL giữ storefront và WooCommerce, MongoDB giữ payment events và report, còn OP Travel Core lo booking state và payment confirm cho từng order.', 'op-travel-shop'); ?></p>
            </div>
        </aside>
    </div>
</section>

<section class="op-section" id="workflow">
    <div class="op-shell">
        <div class="op-split-copy">
            <div class="op-section-heading">
                <p class="op-kicker"><?php esc_html_e('Journey Blueprint', 'op-travel-shop'); ?></p>
                <h2><?php esc_html_e('Booking flow 4 bước được thiết kế cho tour du lịch.', 'op-travel-shop'); ?></h2>
            </div>
            <div>
                <p><?php esc_html_e('Theme không tô màu cho WooCommerce mặc định. Nó đổi giọng điệu toàn bộ hành trình: archive là shortlist, single là chốt lịch khởi hành, cart là bước xác nhận giữ chỗ, còn thank-you là nơi trạng thái thanh toán được kể rõ ràng.', 'op-travel-shop'); ?></p>
            </div>
        </div>
        <?php op_travel_render_workflow_steps(); ?>
    </div>
</section>

<section class="op-section">
    <div class="op-shell">
        <div class="op-section-heading">
            <p class="op-kicker"><?php esc_html_e('Curated Journeys', 'op-travel-shop'); ?></p>
            <h2><?php esc_html_e('Shortlist tour nổi bật cho hành trình tiếp theo.', 'op-travel-shop'); ?></h2>
        </div>
        <div class="op-tour-grid">
            <?php
            if ($featured_query->have_posts()) :
                while ($featured_query->have_posts()) :
                    $featured_query->the_post();
                    wc_get_template_part('content', 'product');
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <article class="op-tour-card" data-reveal>
                    <div class="op-tour-card__content">
                        <p class="op-kicker"><?php esc_html_e('Demo Data Needed', 'op-travel-shop'); ?></p>
                        <h3><?php esc_html_e('Chưa có tour mẫu trong local stack.', 'op-travel-shop'); ?></h3>
                        <p><?php esc_html_e('Hãy thêm vài sản phẩm tour để thấy archive, single và booking flow vận hành trọn vẹn.', 'op-travel-shop'); ?></p>
                    </div>
                </article>
                <?php
            endif;
            ?>
        </div>
        <div class="op-section-cta">
            <a class="op-button op-button--ghost" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Xem tất cả tours →', 'op-travel-shop'); ?></a>
        </div>
    </div>
</section>

<section class="op-section">
    <div class="op-shell">
        <div class="op-section-heading">
            <p class="op-kicker"><?php esc_html_e('Taxonomy-led Discovery', 'op-travel-shop'); ?></p>
            <h2><?php esc_html_e('Điểm đến và phong cách tour dẫn dắt hành trình khám phá.', 'op-travel-shop'); ?></h2>
        </div>
        <div class="op-discovery-grid">
            <section class="op-discovery-panel" data-reveal>
                <p class="op-kicker"><?php esc_html_e('Destinations', 'op-travel-shop'); ?></p>
                <?php if (! empty($destinations) && ! is_wp_error($destinations)) : ?>
                    <div class="op-eyebrow-list">
                        <?php foreach ($destinations as $term) : ?>
                            <a class="op-eyebrow" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Taxonomy destination sẽ hiển thị ở đây sau khi seed dữ liệu.', 'op-travel-shop'); ?></p>
                <?php endif; ?>
            </section>
            <section class="op-discovery-panel" data-reveal>
                <p class="op-kicker"><?php esc_html_e('Tour Styles', 'op-travel-shop'); ?></p>
                <?php if (! empty($styles) && ! is_wp_error($styles)) : ?>
                    <div class="op-eyebrow-list">
                        <?php foreach ($styles as $term) : ?>
                            <a class="op-eyebrow" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Taxonomy tour_style sẽ hiển thị ở đây sau khi seed dữ liệu.', 'op-travel-shop'); ?></p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<section class="op-section">
    <div class="op-shell">
        <div class="op-split-copy">
            <div class="op-section-heading">
                <p class="op-kicker"><?php esc_html_e('Conversion Narrative', 'op-travel-shop'); ?></p>
                <h2><?php esc_html_e('Trạng thái thanh toán là một phần trải nghiệm.', 'op-travel-shop'); ?></h2>
            </div>
            <div>
                <p><?php esc_html_e('Trang thank-you được chuẩn bị để kể đủ trạng thái pending, paid, failed, expired và cancelled. Điều đó giúp demo payment flow rõ ràng hơn nhiều so với việc chỉ hiển thị "đặt hàng thành công" chung chung.', 'op-travel-shop'); ?></p>
                <div class="op-eyebrow-list" style="margin-top:16px;">
                    <span class="op-status-pill op-status-pill--pending">pending</span>
                    <span class="op-status-pill op-status-pill--paid">paid</span>
                    <span class="op-status-pill op-status-pill--failed">failed</span>
                    <span class="op-status-pill op-status-pill--expired">expired</span>
                    <span class="op-status-pill op-status-pill--cancelled">cancelled</span>
                </div>
                <p style="margin-top:24px;"><a class="op-button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Bắt đầu với một tour mẫu', 'op-travel-shop'); ?></a></p>
            </div>
        </div>
    </div>
</section>
<?php
get_footer();
