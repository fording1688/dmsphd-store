<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="top-strip">
        <span>Ships from stocked inventory</span>
        <span>Fitment support before you buy</span>
        <span>Wholesale quotes available</span>
    </div>
    <div class="header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="brand-mark">DM</span>
            <span class="brand-copy">
                <span class="brand-name"><?php bloginfo('name'); ?></span>
                <span class="brand-tagline">Diamond tools, glass cutting, sanding, and grinding supplies</span>
            </span>
        </a>
        <nav class="main-nav" aria-label="<?php esc_attr_e('Primary menu', 'edgeturn-tools'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'fallback_cb' => 'edgeturn_tools_fallback_menu',
            ]);
            ?>
        </nav>
        <div class="header-actions">
            <a class="button secondary header-quote" href="<?php echo esc_url(home_url('/contact/')); ?>">Quote</a>
            <?php if (function_exists('wc_get_cart_url')) : ?>
                <a class="button secondary" href="<?php echo esc_url(wc_get_cart_url()); ?>">Cart</a>
            <?php else : ?>
                <a class="button secondary" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">
