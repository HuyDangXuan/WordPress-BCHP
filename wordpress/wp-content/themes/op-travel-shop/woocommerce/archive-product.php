<?php

defined('ABSPATH') || exit;

get_header('shop');

$destination_terms = get_terms([
    'taxonomy' => 'destination',
    'hide_empty' => false,
]);
$style_terms = get_terms([
    'taxonomy' => 'tour_style',
    'hide_empty' => false,
]);
$selected_destination = isset($_GET['destination']) ? sanitize_title(wp_unslash($_GET['destination'])) : '';
$selected_style = isset($_GET['tour_style']) ? sanitize_title(wp_unslash($_GET['tour_style'])) : '';
$selected_destination_term = $selected_destination ? get_term_by('slug', $selected_destination, 'destination') : null;
$selected_style_term = $selected_style ? get_term_by('slug', $selected_style, 'tour_style') : null;
$result_count = isset($GLOBALS['wp_query']->found_posts) ? absint($GLOBALS['wp_query']->found_posts) : 0;
?>
<main class="op-shell op-section">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('BÆ°á»›c 1', 'op-travel-shop'); ?></p>
        <h1><?php esc_html_e('Chá»n tour theo Ä‘iá»ƒm Ä‘áº¿n vÃ  phong cÃ¡ch phÃ¹ há»£p vá»›i lá»‹ch trÃ¬nh cá»§a báº¡n.', 'op-travel-shop'); ?></h1>
        <?php if ($selected_destination_term || $selected_style_term) : ?>
            <p>
                <?php
                echo esc_html(sprintf(
                    __('Shortlist hiá»‡n cÃ³ %1$d hÃ nh trÃ¬nh khá»›p bá»™ lá»c %2$s %3$s.', 'op-travel-shop'),
                    $result_count,
                    $selected_destination_term ? $selected_destination_term->name : __('táº¥t cáº£ Ä‘iá»ƒm Ä‘áº¿n', 'op-travel-shop'),
                    $selected_style_term ? $selected_style_term->name : __('vÃ  má»i phong cÃ¡ch tour', 'op-travel-shop')
                ));
                ?>
            </p>
        <?php else : ?>
            <p><?php esc_html_e('Archive tour khÃ´ng cÃ²n lÃ  product grid máº·c Ä‘á»‹nh. NÃ³ lÃ  shortlist hÃ nh trÃ¬nh, nÆ¡i taxonomy du lá»‹ch dáº«n dáº¯t quyáº¿t Ä‘á»‹nh Ä‘áº§u tiÃªn cá»§a khÃ¡ch.', 'op-travel-shop'); ?></p>
        <?php endif; ?>
    </header>

    <form class="op-filter-shell" method="get">
        <select name="destination">
            <option value=""><?php esc_html_e('Chá»n Ä‘iá»ƒm Ä‘áº¿n', 'op-travel-shop'); ?></option>
            <?php foreach ($destination_terms as $term) : ?>
                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_destination, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tour_style">
            <option value=""><?php esc_html_e('Chá»n phong cÃ¡ch tour', 'op-travel-shop'); ?></option>
            <?php foreach ($style_terms as $term) : ?>
                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_style, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit"><?php esc_html_e('Lá»c shortlist', 'op-travel-shop'); ?></button>
    </form>

    <?php if (woocommerce_product_loop()) : ?>
        <div class="op-tour-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
        </div>
        <?php woocommerce_pagination(); ?>
    <?php else : ?>
        <section class="op-summary-panel">
            <p class="op-kicker"><?php esc_html_e('KhÃ´ng cÃ³ káº¿t quáº£', 'op-travel-shop'); ?></p>
            <h2><?php esc_html_e('ChÆ°a cÃ³ tour khá»›p shortlist hiá»‡n táº¡i.', 'op-travel-shop'); ?></h2>
            <p><?php esc_html_e('Thá»­ giáº£m bá»›t bá»™ lá»c destination hoáº·c tour style Ä‘á»ƒ má»Ÿ rá»™ng shortlist.', 'op-travel-shop'); ?></p>
        </section>
    <?php endif; ?>
</main>
<?php
get_footer('shop');
