<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$cat_diamond_blades = edgeturn_tools_product_category_url('diamond-band-saw-blades');
$cat_glass_cutting = edgeturn_tools_product_category_url('glass-cutting-tools');
$cat_grinding = edgeturn_tools_product_category_url('grinding-wheels');
$cat_sanding = edgeturn_tools_product_category_url('sanding-supplies');
$cat_shop = edgeturn_tools_product_category_url('shop-supplies');
$cat_saw = edgeturn_tools_product_category_url('saw-blades');
?>
<section class="hero">
    <div class="hero-inner">
        <div>
            <span class="eyebrow">DMSPHD tool supply</span>
            <h1>Diamond cutting, sanding, and grinding tools for glass, stone, tile, and shop work</h1>
            <p>Shop diamond band saw blades, flat lap discs, CBN wheels, glass cutters, grinder bits, sanding supplies, and replacement parts from the imported Amazon catalog.</p>
            <div class="hero-actions">
                <a class="button" href="<?php echo esc_url($shop_url); ?>">Shop Products</a>
                <a class="button secondary hero-secondary" href="<?php echo esc_url($cat_diamond_blades); ?>">Diamond Blades</a>
            </div>
            <dl class="hero-metrics">
                <div><dt>187</dt><dd>imported listings</dd></div>
                <div><dt>6</dt><dd>shop categories</dd></div>
                <div><dt>US</dt><dd>Amazon catalog source</dd></div>
            </dl>
        </div>
        <aside class="hero-panel">
            <h2>Shop selection checklist</h2>
            <ul class="spec-list">
                <li><span>Material</span><strong>Glass / stone / tile</strong></li>
                <li><span>Tooling</span><strong>Blade / wheel / disc</strong></li>
                <li><span>Specs</span><strong>Grit, arbor, size</strong></li>
                <li><span>Use</span><strong>Cutting / grinding</strong></li>
            </ul>
            <a class="fitment-link" href="<?php echo esc_url($shop_url); ?>">Browse catalog</a>
        </aside>
    </div>
</section>

<section class="quick-shop">
    <div class="quick-shop-inner">
        <a href="<?php echo esc_url($cat_diamond_blades); ?>">Diamond band saw blades</a>
        <a href="<?php echo esc_url($cat_grinding); ?>">Grinding wheels</a>
        <a href="<?php echo esc_url($cat_sanding); ?>">Sanding supplies</a>
        <a href="<?php echo esc_url($cat_glass_cutting); ?>">Glass cutting tools</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2>Shop by category</h2>
                <p>Imported from the Amazon active listing report and grouped for independent-store browsing.</p>
            </div>
        </div>
        <div class="category-grid">
            <a class="category-card" href="<?php echo esc_url($cat_diamond_blades); ?>">
                <span class="category-code">01</span>
                <strong>Diamond Band Saw Blades</strong>
                <span>Wet-cutting blades for glass, stone, marble, tile, and ceramic work.</span>
            </a>
            <a class="category-card" href="<?php echo esc_url($cat_grinding); ?>">
                <span class="category-code">02</span>
                <strong>Grinding Wheels</strong>
                <span>CBN wheels, flat lap discs, diamond wheels, and polishing supplies.</span>
            </a>
            <a class="category-card" href="<?php echo esc_url($cat_sanding); ?>">
                <span class="category-code">03</span>
                <strong>Sanding Supplies</strong>
                <span>Sanding discs, pads, belts, and finishing consumables.</span>
            </a>
            <a class="category-card" href="<?php echo esc_url($cat_glass_cutting); ?>">
                <span class="category-code">04</span>
                <strong>Glass Cutting Tools</strong>
                <span>Cutters and accessories for glass, tile, mosaic, and mirror work.</span>
            </a>
            <a class="category-card" href="<?php echo esc_url($cat_shop); ?>">
                <span class="category-code">05</span>
                <strong>Shop Supplies</strong>
                <span>Useful replacement parts and accessories for workshop tasks.</span>
            </a>
            <a class="category-card" href="<?php echo esc_url($cat_saw); ?>">
                <span class="category-code">06</span>
                <strong>Saw Blades</strong>
                <span>Specialty blade replacements and cutting accessories.</span>
            </a>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-header">
            <div>
                <h2>Popular products</h2>
                <p>Live products from your imported Amazon active listing report.</p>
            </div>
            <a href="<?php echo esc_url($shop_url); ?>">View all</a>
        </div>
        <?php if (shortcode_exists('products')) : ?>
            <?php echo do_shortcode('[products limit="8" columns="4" orderby="popularity"]'); ?>
        <?php else : ?>
            <div class="product-preview-grid">
                <article class="product-preview">
                    <span class="product-badge">Best seller</span>
                    <h3>37.7 Inch Diamond Band Saw Blade</h3>
                    <p>Wet cutting diamond grit blade for stone, marble, ceramic, and glass.</p>
                    <strong>$75.00</strong>
                </article>
                <article class="product-preview">
                    <span class="product-badge">Coarse</span>
                    <h3>42 Inch Diamond Coated Blade Pack</h3>
                    <p>Replacement blades for Gryphon Tall C-40 CR and Kent AquaSaw XL.</p>
                    <strong>$124.98</strong>
                </article>
                <article class="product-preview">
                    <span class="product-badge">Bundle</span>
                    <h3>Diamond Flat Lap Disc</h3>
                    <p>Grinding and polishing disc for gemstone, glass, and ceramics.</p>
                    <strong>From Amazon report</strong>
                </article>
                <article class="product-preview">
                    <span class="product-badge">Accessory</span>
                    <h3>Glass Oil Cutter</h3>
                    <p>Pencil-style carbide tip glass cutting tool with oil feed.</p>
                    <strong>$13.98</strong>
                </article>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section buying-guide">
    <div class="container guide-layout">
        <div>
            <span class="eyebrow">Buying guide</span>
            <h2>Independent-store product pages built from your Amazon catalog</h2>
            <p>Each product keeps SKU, ASIN, price, stock, and listing description from the active listings report, so the WooCommerce catalog starts from real store data.</p>
        </div>
        <div class="guide-steps">
            <div><strong>1</strong><span>Imported SKU, ASIN, price, and stock</span></div>
            <div><strong>2</strong><span>Grouped into practical shop categories</span></div>
            <div><strong>3</strong><span>Ready for product image enrichment</span></div>
            <div><strong>4</strong><span>Can be rerun to update future reports</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container trust-row">
        <div class="trust-item">
            <strong>Amazon data imported</strong>
            <span>SKU, ASIN, pricing, inventory, and descriptions are stored in WooCommerce.</span>
        </div>
        <div class="trust-item">
            <strong>DMSPHD catalog</strong>
            <span>Diamond tools, glass cutting tools, sanding supplies, and grinding wheels.</span>
        </div>
        <div class="trust-item">
            <strong>Spec-first shopping</strong>
            <span>Product pages surface grit, size, arbor, pack count, and application details.</span>
        </div>
        <div class="trust-item">
            <strong>Repeatable imports</strong>
            <span>Future Amazon active listing reports can update the WooCommerce catalog.</span>
        </div>
    </div>
</section>
<?php
get_footer();
