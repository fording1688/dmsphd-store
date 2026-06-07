<?php

require_once __DIR__ . '/../../wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$report = $argv[1] ?? '';
if ($report === '' || !is_readable($report)) {
    fwrite(STDERR, "Usage: php wp-content/import-scripts/import-amazon-active-listings.php /path/to/report.txt\n");
    exit(1);
}

$handle = fopen($report, 'r');
if (!$handle) {
    fwrite(STDERR, "Unable to open report.\n");
    exit(1);
}

$headers = fgetcsv($handle, 0, "\t");
if (!$headers) {
    fwrite(STDERR, "Report has no header row.\n");
    exit(1);
}

$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
$created = 0;
$updated = 0;
$skipped = 0;
$categories = [];

function et_category_for_listing(string $title, string $description): string
{
    $text = strtolower($title . ' ' . $description);

    if (str_contains($text, 'band saw') || str_contains($text, 'bandsaw')) {
        return 'Diamond Band Saw Blades';
    }
    if (str_contains($text, 'glass cutter') || str_contains($text, 'cutting tool')) {
        return 'Glass Cutting Tools';
    }
    if (str_contains($text, 'grinding wheel') || str_contains($text, 'cbn') || str_contains($text, 'diamond wheel')) {
        return 'Grinding Wheels';
    }
    if (str_contains($text, 'sanding') || str_contains($text, 'disc') || str_contains($text, 'pad')) {
        return 'Sanding Supplies';
    }
    if (str_contains($text, 'blade')) {
        return 'Saw Blades';
    }

    return 'Shop Supplies';
}

function et_term_id(string $category, array &$cache): int
{
    if (isset($cache[$category])) {
        return $cache[$category];
    }

    $term = term_exists($category, 'product_cat');
    if (!$term) {
        $term = wp_insert_term($category, 'product_cat');
    }

    $cache[$category] = is_array($term) ? (int) $term['term_id'] : (int) $term;
    return $cache[$category];
}

while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    if (count($row) < count($headers)) {
        $row = array_pad($row, count($headers), '');
    }

    $item = array_combine($headers, array_slice($row, 0, count($headers)));
    if (!$item) {
        $skipped++;
        continue;
    }

    $title = trim($item['item-name'] ?? '');
    $sku = trim($item['seller-sku'] ?? '');
    $asin = trim($item['asin1'] ?? '');
    $description = trim($item['item-description'] ?? '');
    $price = trim($item['price'] ?? '');
    $quantity = trim($item['quantity'] ?? '');

    if ($title === '' || $sku === '') {
        $skipped++;
        continue;
    }

    $product_id = wc_get_product_id_by_sku($sku);
    $is_new = !$product_id;
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

    if (!$product) {
        $skipped++;
        continue;
    }

    $product->set_name(wp_strip_all_tags($title));
    $product->set_sku($sku);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price($price !== '' ? $price : '');
    $product->set_manage_stock(true);
    $product->set_stock_quantity($quantity !== '' && is_numeric($quantity) ? (int) $quantity : null);
    $product->set_stock_status($quantity !== '' && is_numeric($quantity) && (int) $quantity <= 0 ? 'outofstock' : 'instock');
    $product->set_description(wp_kses_post(wpautop($description)));
    $product->set_short_description(wp_trim_words(wp_strip_all_tags($description ?: $title), 32));

    $product_id = $product->save();

    update_post_meta($product_id, '_amazon_asin', $asin);
    update_post_meta($product_id, '_amazon_listing_id', trim($item['listing-id'] ?? ''));
    update_post_meta($product_id, '_amazon_fulfillment_channel', trim($item['fulfillment-channel'] ?? ''));
    update_post_meta($product_id, '_amazon_source_report', basename($report));

    $category = et_category_for_listing($title, $description);
    wp_set_object_terms($product_id, [et_term_id($category, $categories)], 'product_cat');

    if ($is_new) {
        $created++;
    } else {
        $updated++;
    }
}

fclose($handle);

echo "Imported Amazon active listings\n";
echo "Created products: {$created}\n";
echo "Updated products: {$updated}\n";
echo "Skipped rows: {$skipped}\n";
echo "Categories: " . implode(', ', array_keys($categories)) . "\n";
