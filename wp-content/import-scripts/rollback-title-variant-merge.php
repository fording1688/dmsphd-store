<?php

require_once __DIR__ . '/../../wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$parent_ids = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'meta_key' => '_dmsphd_merged_variant_group_key',
    'fields' => 'ids',
    'posts_per_page' => -1,
]);

$restored_sources = 0;
$deleted_variations = 0;
$deleted_parents = 0;

foreach ($parent_ids as $parent_id) {
    $variation_ids = get_children([
        'post_parent' => (int) $parent_id,
        'post_type' => 'product_variation',
        'post_status' => ['publish', 'draft', 'private'],
        'fields' => 'ids',
    ]);

    foreach ($variation_ids as $variation_id) {
        $source_id = (int) get_post_meta((int) $variation_id, '_dmsphd_source_product_id', true);
        $variation = wc_get_product((int) $variation_id);
        $variation_sku = $variation ? (string) $variation->get_sku() : '';

        wp_delete_post((int) $variation_id, true);
        $deleted_variations++;

        if ($source_id > 0 && get_post($source_id)) {
            $source_product = wc_get_product($source_id);
            if ($source_product) {
                $original_sku = (string) get_post_meta($source_id, '_dmsphd_original_sku', true);
                $sku_to_restore = $original_sku !== '' ? $original_sku : $variation_sku;
                if ($sku_to_restore !== '') {
                    $source_product->set_sku($sku_to_restore);
                }
                $source_product->save();
            }

            wp_update_post([
                'ID' => $source_id,
                'post_status' => 'publish',
            ]);
            delete_post_meta($source_id, '_merged_into_variable_id');
            $restored_sources++;
        }
    }

    wp_delete_post((int) $parent_id, true);
    $deleted_parents++;
}

echo "Restored source products: {$restored_sources}\n";
echo "Deleted variations: {$deleted_variations}\n";
echo "Deleted title-merged parents: {$deleted_parents}\n";
