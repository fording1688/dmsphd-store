<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section">
    <div class="container">
        <?php woocommerce_content(); ?>
    </div>
</section>
<?php
get_footer();
