<?php

require_once __DIR__ . '/../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$csv = $argv[1] ?? '';
$max_images = isset($argv[2]) ? max(1, (int) $argv[2]) : 5;

if ($csv === '' || !is_readable($csv)) {
    fwrite(STDERR, "Usage: php wp-content/import-scripts/import-amazon-product-images.php /path/to/image-map.csv [max-images-per-product]\n");
    exit(1);
}

function dmsphd_attachment_for_url(string $url): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_dmsphd_source_image_url',
        'meta_value' => $url,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    return $existing ? (int) $existing[0] : 0;
}

function dmsphd_download_image(string $url, int $product_id): int
{
    $existing_id = dmsphd_attachment_for_url($url);
    if ($existing_id) {
        return $existing_id;
    }

    $tmp = download_url($url, 60);
    if (is_wp_error($tmp)) {
        throw new RuntimeException($tmp->get_error_message());
    }

    $path = parse_url($url, PHP_URL_PATH);
    $name = $path ? basename($path) : ('product-' . md5($url) . '.jpg');
    if (!preg_match('/\\.(jpe?g|png|webp|gif)$/i', $name)) {
        $name .= '.jpg';
    }

    $file = [
        'name' => sanitize_file_name($name),
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file, $product_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        throw new RuntimeException($attachment_id->get_error_message());
    }

    update_post_meta($attachment_id, '_dmsphd_source_image_url', $url);
    return (int) $attachment_id;
}

$handle = fopen($csv, 'r');
$headers = fgetcsv($handle);

$matched = 0;
$downloaded_or_reused = 0;
$missing_products = 0;
$failed = 0;

while (($row = fgetcsv($handle)) !== false) {
    $sku = trim($row[0] ?? '');
    if ($sku === '') {
        continue;
    }

    $product_id = wc_get_product_id_by_sku($sku);
    if (!$product_id) {
        $missing_products++;
        continue;
    }

    $matched++;
    $urls = array_values(array_filter(array_map('trim', array_slice($row, 1))));
    $urls = array_slice(array_unique($urls), 0, $max_images);
    $attachment_ids = [];

    foreach ($urls as $url) {
        try {
            $attachment_ids[] = dmsphd_download_image($url, $product_id);
            $downloaded_or_reused++;
        } catch (Throwable $error) {
            $failed++;
            error_log("Image import failed for SKU {$sku}: {$url} :: " . $error->getMessage());
        }
    }

    $attachment_ids = array_values(array_unique(array_filter($attachment_ids)));
    if (!$attachment_ids) {
        continue;
    }

    set_post_thumbnail($product_id, $attachment_ids[0]);
    if (count($attachment_ids) > 1) {
        update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($attachment_ids, 1)));
    }
}

fclose($handle);

echo "Imported Amazon product images\n";
echo "Matched products: {$matched}\n";
echo "Missing products: {$missing_products}\n";
echo "Downloaded or reused images: {$downloaded_or_reused}\n";
echo "Failed image downloads: {$failed}\n";
