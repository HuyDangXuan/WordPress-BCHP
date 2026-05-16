<?php

defined('ABSPATH') || exit;
?>
<section class="op-account-shell" data-reveal>
    <div class="op-account-layout">
        <aside class="op-account-layout__sidebar">
            <?php do_action('woocommerce_account_navigation'); ?>
        </aside>

        <div class="op-account-layout__content">
            <?php do_action('woocommerce_account_content'); ?>
        </div>
    </div>
</section>
