<?php
$url = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
$dir = __DIR__ . '/public/js';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$dest = $dir . '/chart.umd.min.js';
$content = file_get_contents($url);
if ($content === false) {
    echo "ERROR: Could not download from $url\n";
    exit(1);
}
file_put_contents($dest, $content);
echo "Downloaded " . strlen($content) . " bytes to $dest\n";
