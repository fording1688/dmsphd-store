<?php

require_once __DIR__ . '/../../wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$csv_path = $argv[1] ?? '';
if ($csv_path === '' || !is_readable($csv_path)) {
    fwrite(STDERR, "Usage: php merge-parent-child-variants.php /path/to/amazon-parent-child-map.csv\n");
    exit(1);
}

function dmsphd_pc_slug(string $name): string
{
    return sanitize_title($name);
}

function dmsphd_pc_find_product_by_sku(string $sku): ?WC_Product
{
    $product_id = wc_get_product_id_by_sku($sku);
    if (!$product_id) {
        return null;
    }
    $product = wc_get_product($product_id);
    return $product instanceof WC_Product ? $product : null;
}

function dmsphd_pc_copy_product_meta(int $from_id, int $to_id): void
{
    foreach (['_amazon_asin', '_amazon_listing_id', '_amazon_image_urls'] as $key) {
        $value = get_post_meta($from_id, $key, true);
        if ($value !== '') {
            update_post_meta($to_id, $key, $value);
        }
    }
}

$rows = [];
if (($handle = fopen($csv_path, 'r')) === false) {
    fwrite(STDERR, "Could not open CSV.\n");
    exit(1);
}

$header = fgetcsv($handle);
while (($data = fgetcsv($handle)) !== false) {
    $row = array_combine($header, $data);
    if (!$row || ($row['duplicate_attrs'] ?? '') === '1') {
        continue;
    }
    $attrs = json_decode($row['attrs_json'] ?? '{}', true);
    if (!is_array($attrs) || !$attrs) {
        continue;
    }
    $row['attrs'] = array_filter($attrs, fn($value) => (string) $value !== '');
    if ($row['attrs']) {
        $rows[$row['parent_sku']][] = $row;
    }
}
fclose($handle);

$created_parents = 0;
$created_variations = 0;
$skipped_groups = 0;
$skipped_children = 0;

foreach ($rows as $parent_sku => $items) {
    if (count($items) < 2) {
        $skipped_groups++;
        continue;
    }

    $source_products = [];
    $combo_keys = [];
    $all_attr_values = [];
    $valid = true;

    foreach ($items as $row) {
        $source = dmsphd_pc_find_product_by_sku($row['child_sku']);
        if (!$source || $source->get_type() !== 'simple') {
            $skipped_children++;
            $valid = false;
            break;
        }

        $combo = [];
        foreach ($row['attrs'] as $name => $value) {
            $value = (string) $value;
            $combo[] = $name . '=' . $value;
            $all_attr_values[$name][$value] = true;
        }
        sort($combo);
        $combo_key = implode('|', $combo);
        if ($combo_key === '' || isset($combo_keys[$combo_key])) {
            $valid = false;
            break;
        }
        $combo_keys[$combo_key] = true;
        $source_products[$row['child_sku']] = $source;
    }

    if (!$valid) {
        $skipped_groups++;
        continue;
    }

    $existing_parent_ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'private'],
        'meta_key' => '_dmsphd_amazon_parent_sku',
        'meta_value' => $parent_sku,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);
    if ($existing_parent_ids) {
        $skipped_groups++;
        continue;
    }

    $first_row = $items[0];
    $first_product = $source_products[$first_row['child_sku']];
    $first_post = get_post($first_product->get_id());

    $parent = new WC_Product_Variable();
    $parent->set_name($first_row['parent_title'] ?: $first_product->get_name());
    $parent->set_status('publish');
    $parent->set_catalog_visibility('visible');
    $parent->set_description($first_post ? $first_post->post_content : '');
    $parent->set_short_description($first_post ? $first_post->post_excerpt : '');

    $thumb_id = get_post_thumbnail_id($first_product->get_id());
    if ($thumb_id) {
        $parent->set_image_id($thumb_id);
    }

    $cat_ids = wp_get_object_terms($first_product->get_id(), 'product_cat', ['fields' => 'ids']);
    if (!is_wp_error($cat_ids)) {
        $parent->set_category_ids(array_map('intval', $cat_ids));
    }

    $wc_attributes = [];
    foreach ($all_attr_values as $name => $values) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($name);
        $attribute->set_options(array_keys($values));
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $wc_attributes[] = $attribute;
    }
    $parent->set_attributes($wc_attributes);
    $parent_id = $parent->save();
    update_post_meta($parent_id, '_dmsphd_amazon_parent_sku', $parent_sku);
    update_post_meta($parent_id, '_dmsphd_parent_child_merge', '1');

    foreach ($items as $row) {
        $source_product = $source_products[$row['child_sku']];
        $source_id = $source_product->get_id();
        $source_post = get_post($source_id);
        $source_sku = (string) $source_product->get_sku();

        if ($source_sku !== '') {
            update_post_meta($source_id, '_dmsphd_original_sku', $source_sku);
            $source_product->set_sku('');
            $source_product->save();
        }

        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parent_id);
        $variation->set_status('publish');
        if ($source_sku !== '') {
            $variation->set_sku($source_sku);
        }
        $variation->set_regular_price((string) $source_product->get_regular_price());
        $variation->set_sale_price((string) $source_product->get_sale_price());
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($source_product->get_stock_quantity());
        $variation->set_stock_status($source_product->get_stock_status());
        $variation->set_description($source_post ? $source_post->post_content : '');

        $variation_attrs = [];
        foreach ($row['attrs'] as $name => $value) {
            $variation_attrs[dmsphd_pc_slug($name)] = (string) $value;
        }
        $variation->set_attributes($variation_attrs);

        $image_id = get_post_thumbnail_id($source_id);
        if ($image_id) {
            $variation->set_image_id($image_id);
        }

        $variation_id = $variation->save();
        update_post_meta($variation_id, '_dmsphd_source_product_id', $source_id);
        update_post_meta($variation_id, '_dmsphd_amazon_parent_sku', $parent_sku);
        dmsphd_pc_copy_product_meta($source_id, $variation_id);

        wp_update_post([
            'ID' => $source_id,
            'post_status' => 'draft',
        ]);
        update_post_meta($source_id, '_merged_into_variable_id', $parent_id);

        $created_variations++;
    }

    WC_Product_Variable::sync($parent_id);
    wc_delete_product_transients($parent_id);
    $created_parents++;
}

echo "Created parent-child variable products: {$created_parents}\n";
echo "Created child variations: {$created_variations}\n";
echo "Skipped groups: {$skipped_groups}\n";
echo "Skipped/missing children: {$skipped_children}\n";
