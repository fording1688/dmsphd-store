<?php
if (!defined('ABSPATH')) {
    exit;
}

function edgeturn_tools_fallback_menu(): void
{
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

    echo '<ul>';
    echo '<li><a href="' . esc_url($shop_url) . '">Shop</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('diamond-band-saw-blades')) . '">Diamond Blades</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('grinding-wheels')) . '">Grinding Wheels</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('sanding-supplies')) . '">Sanding</a></li>';
    echo '<li><a href="' . esc_url(home_url('/blog/')) . '">Guides</a></li>';
    echo '</ul>';
}

function edgeturn_tools_footer_fallback_menu(): void
{
    echo '<ul>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('diamond-band-saw-blades')) . '">Diamond Band Saw Blades</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('grinding-wheels')) . '">Grinding Wheels</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('sanding-supplies')) . '">Sanding Supplies</a></li>';
    echo '<li><a href="' . esc_url(edgeturn_tools_product_category_url('glass-cutting-tools')) . '">Glass Cutting Tools</a></li>';
    echo '</ul>';
}
