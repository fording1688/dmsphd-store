<?php

require_once __DIR__ . '/../../wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

function dmsphd_title_clean(string $title): string
{
    return str_replace(["\xEF\xBF\xBD", '?', '、'], [' ', ' ', ','], html_entity_decode($title, ENT_QUOTES | ENT_HTML5));
}

function dmsphd_variant_attrs_for_merge(string $title): array
{
    $clean = dmsphd_title_clean($title);
    $attrs = [];

    if (preg_match('/\((\d{2,5}\s*\/\s*\d{2,5})\s*Grit\)/i', $clean, $m)) {
        $attrs['Grit'] = str_replace(' ', '', $m[1]);
    } elseif (preg_match('/\((\d{2,5})\s*Grit\)/i', $clean, $m)) {
        $attrs['Grit'] = $m[1];
    } elseif (preg_match('/(\d{2,5}\s*\/\s*\d{2,5})\s*Grit/i', $clean, $m)) {
        $attrs['Grit'] = str_replace(' ', '', $m[1]);
    } elseif (preg_match('/(\d{2,5})\s*Grit/i', $clean, $m)) {
        $attrs['Grit'] = $m[1];
    }

    if (preg_match('/Pack\s*\((\d+)\)/i', $clean, $m)) {
        $attrs['Pack Quantity'] = $m[1];
    } elseif (preg_match('/(?:\(|\b)(\d+)\s*pcs?\b/i', $clean, $m)) {
        $attrs['Pack Quantity'] = $m[1];
    }

    if (preg_match('/\((\d+(?:\.\d+)?)\s*inch\)/i', $clean, $m)) {
        $attrs['Size'] = $m[1] . ' inch';
    }

    if (preg_match('/\((CBN|PCBN|PCD),\s*([^)]+)\)/i', $clean, $m)) {
        $attrs['Material'] = strtoupper($m[1]);
        $attrs['Insert Size'] = trim($m[2]);
    }

    return $attrs;
}

function dmsphd_variant_key_for_merge(string $title): string
{
    $t = strtolower(dmsphd_title_clean($title));
    $t = preg_replace('/^dmsphd\s+/', '', $t);
    $t = preg_replace('/\((?:[^)]*?\d{2,5}\s*\/\s*\d{2,5}\s*grit[^)]*?)\)/i', '(variant)', $t);
    $t = preg_replace('/\((?:[^)]*?\d{2,5}\s*grit[^)]*?)\)/i', '(variant)', $t);
    $t = preg_replace('/\b\d{2,5}\s*\/\s*\d{2,5}\s*grit\b/i', 'variant-grit', $t);
    $t = preg_replace('/\b\d{2,5}\s*grit\b/i', 'variant-grit', $t);
    $t = preg_replace('/\((?:\d+\s*)?pcs?[^)]*?\)/i', '(variant-pack)', $t);
    $t = preg_replace('/\b\d+\s*pcs?\b/i', 'variant-pack', $t);
    $t = preg_replace('/pack\s*\(\d+\)/i', 'pack (variant-pack)', $t);
    $t = preg_replace('/\((CBN|PCBN|PCD),\s*[^)]*\)/i', '(variant-insert)', $t);
    $t = preg_replace('/\s+/', ' ', trim($t));
    return trim($t, " \t\n\r\0\x0B,.-");
}

function dmsphd_parent_title(string $title, array $attrs): string
{
    $t = dmsphd_title_clean($title);
    $t = preg_replace('/^dmsphd\s+/i', 'DMSPHD ', $t);
    $t = preg_replace('/\((?:[^)]*?\d{2,5}\s*\/\s*\d{2,5}\s*Grit[^)]*?)\)/i', '', $t);
    $t = preg_replace('/\((?:[^)]*?\d{2,5}\s*Grit[^)]*?)\)/i', '', $t);
    $t = preg_replace('/\b\d{2,5}\s*\/\s*\d{2,5}\s*Grit\b/i', 'Grit Options', $t);
    $t = preg_replace('/\b\d{2,5}\s*Grit\b/i', 'Grit Options', $t);
    $t = preg_replace('/\((?:\d+\s*)?pcs?[^)]*?\)/i', '', $t);
    $t = preg_replace('/\b\d+\s*pcs?\b/i', '', $t);
    $t = preg_replace('/Pack\s*\(\d+\)/i', 'Pack Options', $t);
    $t = preg_replace('/\((CBN|PCBN|PCD),\s*[^)]*\)/i', '', $t);
    $t = preg_replace('/\s+/', ' ', trim($t));
    $t = trim($t, " \t\n\r\0\x0B,.-");

    if (isset($attrs['Grit']) && !str_contains(strtolower($t), 'grit options')) {
        $t .= ' - Grit Options';
    }
    if (isset($attrs['Pack Quantity']) && !str_contains(strtolower($t), 'pack options')) {
        $t .= ' - Pack Options';
    }
    if (isset($attrs['Insert Size'])) {
        $t .= ' - Insert Size Options';
    }

    return $t;
}

function dmsphd_attribute_slug(string $name): string
{
    return sanitize_title($name);
}

$products = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
    'tax_query' => [
        [
            'taxonomy' => 'product_type',
            'field' => 'slug',
            'terms' => ['simple'],
        ],
    ],
]);

$groups = [];
foreach ($products as $post) {
    $attrs = dmsphd_variant_attrs_for_merge($post->post_title);
    if (!$attrs) {
        continue;
    }
    $key = dmsphd_variant_key_for_merge($post->post_title);
    $groups[$key][] = ['post' => $post, 'attrs' => $attrs];
}

$merged_groups = 0;
$created_variations = 0;
$skipped_groups = 0;

foreach ($groups as $key => $items) {
    if (count($items) < 2) {
        continue;
    }

    $attr_names = array_keys($items[0]['attrs']);
    $attr_names = array_values(array_filter($attr_names, fn($name) => isset($items[0]['attrs'][$name])));

    // Merge only groups that use the same attribute set and have unique combinations.
    $combos = [];
    $all_attr_values = [];
    $valid = true;
    foreach ($items as $item) {
        $names = array_keys($item['attrs']);
        sort($names);
        $expected = $attr_names;
        sort($expected);
        if ($names !== $expected) {
            $valid = false;
            break;
        }

        $combo_parts = [];
        foreach ($attr_names as $name) {
            $value = (string) $item['attrs'][$name];
            $combo_parts[] = $name . '=' . $value;
            $all_attr_values[$name][$value] = true;
        }
        $combo = implode('|', $combo_parts);
        if (isset($combos[$combo])) {
            $valid = false;
            break;
        }
        $combos[$combo] = true;
    }

    if (!$valid) {
        $skipped_groups++;
        continue;
    }

    $existing_parent_ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'private'],
        'meta_key' => '_dmsphd_merged_variant_group_key',
        'meta_value' => $key,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    $first_post = $items[0]['post'];
    $parent_id = $existing_parent_ids ? (int) $existing_parent_ids[0] : 0;
    $existing_children = $parent_id ? get_children([
        'post_parent' => $parent_id,
        'post_type' => 'product_variation',
        'fields' => 'ids',
    ]) : [];

    if ($parent_id && $existing_children) {
        $skipped_groups++;
        continue;
    }

    $parent = $parent_id ? wc_get_product($parent_id) : new WC_Product_Variable();
    if (!$parent instanceof WC_Product_Variable) {
        $skipped_groups++;
        continue;
    }

    $parent->set_name(dmsphd_parent_title($first_post->post_title, $items[0]['attrs']));
    $parent->set_status('publish');
    $parent->set_catalog_visibility('visible');
    $parent->set_description($first_post->post_content);
    $parent->set_short_description($first_post->post_excerpt);

    $thumb_id = get_post_thumbnail_id($first_post->ID);
    if ($thumb_id) {
        $parent->set_image_id($thumb_id);
    }

    $cat_ids = wp_get_object_terms($first_post->ID, 'product_cat', ['fields' => 'ids']);
    if (!is_wp_error($cat_ids)) {
        $parent->set_category_ids(array_map('intval', $cat_ids));
    }

    $attributes = [];
    foreach ($attr_names as $name) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($name);
        $attribute->set_options(array_keys($all_attr_values[$name]));
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $attributes[] = $attribute;
    }
    $parent->set_attributes($attributes);
    $parent_id = $parent->save();

    update_post_meta($parent_id, '_dmsphd_merged_variant_group_key', $key);

    foreach ($items as $item) {
        $post = $item['post'];
        $product = wc_get_product($post->ID);
        if (!$product) {
            continue;
        }

        $source_sku = (string) $product->get_sku();

        // WooCommerce enforces unique SKUs across products and variations, so release
        // the source simple product SKU before assigning it to the new variation.
        if ($source_sku !== '') {
            update_post_meta($post->ID, '_dmsphd_original_sku', $source_sku);
            $product->set_sku('');
            $product->save();
        }

        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parent_id);
        $variation->set_status('publish');
        if ($source_sku !== '') {
            $variation->set_sku($source_sku);
        }
        $variation->set_regular_price((string) $product->get_regular_price());
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($product->get_stock_quantity());
        $variation->set_stock_status($product->get_stock_status());
        $variation->set_description($post->post_content);

        $variation_attrs = [];
        foreach ($item['attrs'] as $name => $value) {
            $variation_attrs[dmsphd_attribute_slug($name)] = (string) $value;
        }
        $variation->set_attributes($variation_attrs);

        $image_id = get_post_thumbnail_id($post->ID);
        if ($image_id) {
            $variation->set_image_id($image_id);
        }

        $variation_id = $variation->save();
        update_post_meta($variation_id, '_dmsphd_source_product_id', $post->ID);
        update_post_meta($variation_id, '_amazon_asin', get_post_meta($post->ID, '_amazon_asin', true));
        update_post_meta($variation_id, '_amazon_listing_id', get_post_meta($post->ID, '_amazon_listing_id', true));

        wp_update_post([
            'ID' => $post->ID,
            'post_status' => 'draft',
        ]);
        update_post_meta($post->ID, '_merged_into_variable_id', $parent_id);

        $created_variations++;
    }

    WC_Product_Variable::sync($parent_id);
    wc_delete_product_transients($parent_id);
    $merged_groups++;
}

echo "Merged variant groups: {$merged_groups}\n";
echo "Created variations: {$created_variations}\n";
echo "Skipped groups with duplicate/mixed attributes: {$skipped_groups}\n";
