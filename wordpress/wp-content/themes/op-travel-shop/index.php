<?php

get_header();
?>
<main class="op-shell op-archive-shell">
    <header class="op-section-heading">
        <p class="op-kicker"><?php esc_html_e('HV-Travel Journal', 'op-travel-shop'); ?></p>
        <h1><?php single_post_title(); ?></h1>
    </header>
    <?php if (have_posts()) : ?>
        <div class="op-story-grid">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('op-story-card'); ?>>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div><?php the_excerpt(); ?></div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('Chưa có nội dung để hiển thị.', 'op-travel-shop'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
