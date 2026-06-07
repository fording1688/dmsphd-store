<?php

require_once __DIR__ . '/../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'woocommerce/woocommerce.php';

if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) {
        fwrite(STDERR, $result->get_error_message() . PHP_EOL);
        exit(1);
    }
}

echo "WooCommerce active\n";
