<?php

get_header();
?>
<main class="op-shell op-section">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('op-page-content'); ?>>
                <header class="op-section-heading">
                    <p class="op-kicker"><?php esc_html_e('HV-Travel', 'op-travel-shop'); ?></p>
                    <h1><?php the_title(); ?></h1>
                </header>
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
