<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/menu-fallback.php';

function edgeturn_tools_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => __('Primary Menu', 'edgeturn-tools'),
        'footer' => __('Footer Menu', 'edgeturn-tools'),
    ]);
}
add_action('after_setup_theme', 'edgeturn_tools_setup');

function edgeturn_tools_assets(): void
{
    wp_enqueue_style(
        'edgeturn-tools-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'edgeturn_tools_assets');

function edgeturn_tools_widgets(): void
{
    register_sidebar([
        'name' => __('Shop Sidebar', 'edgeturn-tools'),
        'id' => 'shop-sidebar',
        'description' => __('Filters and product widgets for shop pages.', 'edgeturn-tools'),
        'before_widget' => '<section class="shop-widget">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ]);
}
add_action('widgets_init', 'edgeturn_tools_widgets');

function edgeturn_tools_body_classes(array $classes): array
{
    $classes[] = 'edgeturn-store';
    return $classes;
}
add_filter('body_class', 'edgeturn_tools_body_classes');

function edgeturn_tools_woocommerce_products_per_page(): int
{
    return 16;
}
add_filter('loop_shop_per_page', 'edgeturn_tools_woocommerce_products_per_page');

function edgeturn_tools_product_category_url(string $slug): string
{
    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) {
            return $link;
        }
    }

    return home_url('/?product_cat=' . $slug);
}
