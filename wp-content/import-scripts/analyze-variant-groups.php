<?php

require_once __DIR__ . '/../../wp-load.php';

$products = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

function dmsphd_variant_attrs(string $title): array
{
    $clean = html_entity_decode($title, ENT_QUOTES | ENT_HTML5);
    $clean = str_replace(["\xEF\xBF\xBD", '?'], ' ', $clean);
    $attrs = [];

    if (preg_match('/(\d{2,5}\s*\/\s*\d{2,5})\s*Grit/i', $clean, $m)) {
        $attrs['Grit'] = str_replace(' ', '', $m[1]);
    } elseif (preg_match('/(\d{2,5})\s*Grit/i', $clean, $m)) {
        $attrs['Grit'] = $m[1];
    }

    if (preg_match('/(?:\(|\b)(\d+)\s*pcs?\b/i', $clean, $m)) {
        $attrs['Pack Quantity'] = $m[1];
    } elseif (preg_match('/Pack\s*\((\d+)\)/i', $clean, $m)) {
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

function dmsphd_variant_key(string $title): string
{
    $t = strtolower(html_entity_decode($title, ENT_QUOTES | ENT_HTML5));
    $t = str_replace(["\xEF\xBF\xBD", '?'], ' ', $t);
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

$groups = [];
foreach ($products as $post) {
    $attrs = dmsphd_variant_attrs($post->post_title);
    if (!$attrs) {
        continue;
    }

    $key = dmsphd_variant_key($post->post_title);
    $groups[$key][] = [
        'id' => $post->ID,
        'title' => $post->post_title,
        'sku' => get_post_meta($post->ID, '_sku', true),
        'price' => get_post_meta($post->ID, '_regular_price', true),
        'stock' => get_post_meta($post->ID, '_stock', true),
        'attrs' => $attrs,
    ];
}

$candidates = array_filter($groups, fn($items) => count($items) >= 2);
uasort($candidates, fn($a, $b) => count($b) <=> count($a));

echo "Candidate variant groups: " . count($candidates) . PHP_EOL;
$i = 1;
foreach ($candidates as $key => $items) {
    echo PHP_EOL . "GROUP {$i} | count=" . count($items) . PHP_EOL;
    echo "KEY: {$key}" . PHP_EOL;
    foreach ($items as $item) {
        echo "  #{$item['id']} {$item['sku']} \${$item['price']} stock={$item['stock']} attrs=" . json_encode($item['attrs'], JSON_UNESCAPED_SLASHES) . PHP_EOL;
        echo "    " . mb_substr($item['title'], 0, 180) . PHP_EOL;
    }
    $i++;
}
