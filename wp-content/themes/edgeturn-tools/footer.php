<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <section>
            <h2><?php bloginfo('name'); ?></h2>
            <p>Diamond tools, grinding wheels, sanding supplies, and glass cutting accessories imported from the DMSPHD Amazon catalog.</p>
        </section>
        <section>
            <h3>Shop</h3>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container' => false,
                'fallback_cb' => 'edgeturn_tools_footer_fallback_menu',
            ]);
            ?>
        </section>
        <section>
            <h3>Support</h3>
            <p>Email: <a href="mailto:zzdm168@outlook.com">zzdm168@outlook.com</a></p>
            <p>Shipping, returns, fitment questions, and wholesale inquiries.</p>
        </section>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
