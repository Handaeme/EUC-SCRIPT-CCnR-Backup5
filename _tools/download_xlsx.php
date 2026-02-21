<?php
$url = 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js';
$path = 'public/assets/js/xlsx.full.min.js';

echo "Downloading from $url to $path...\n";

// Ensure dir exists
if (!is_dir('public/assets/js')) {
    mkdir('public/assets/js', 0777, true);
}

$content = file_get_contents($url);
if ($content === false) {
    echo "Error: file_get_contents failed.\n";
    exit(1);
}

$bytes = file_put_contents($path, $content);
if ($bytes !== false) {
    echo "Success! Downloaded $bytes bytes.\n";
} else {
    echo "Error: file_put_contents failed.\n";
    exit(1);
}
?>
