<?php

get_header();

$is_account_page = function_exists('is_account_page') && is_account_page();
$has_custom_woocommerce_shell = $is_account_page
    || (function_exists('is_cart') && is_cart())
    || (function_exists('is_checkout') && is_checkout());
?>
<main class="op-shell op-section <?php echo $is_account_page ? 'op-section--account' : ''; ?>">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class($is_account_page ? 'op-page-content op-page-content--account' : 'op-page-content'); ?>>
                <?php if (! $has_custom_woocommerce_shell) : ?>
                    <header class="op-section-heading">
                        <p class="op-kicker"><?php esc_html_e('HV-Travel', 'op-travel-shop'); ?></p>
                        <h1><?php the_title(); ?></h1>
                    </header>
                <?php endif; ?>
                <div class="op-page-body">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('Chua co noi dung de hien thi.', 'op-travel-shop'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
