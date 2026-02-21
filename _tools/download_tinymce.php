<?php
$baseUrl = "https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/";
$targetDir = __DIR__ . "/public/assets/js/tinymce/";

$files = [
    "tinymce.min.js",
    "themes/silver/theme.min.js",
    "models/dom/model.min.js",
    "icons/default/icons.min.js",
    "skins/ui/oxide/skin.min.css",
    "skins/ui/oxide/content.min.css",
    "skins/content/default/content.min.css"
];

if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

foreach ($files as $file) {
    $url = $baseUrl . $file;
    $path = $targetDir . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    echo "Downloading $file... ";
    $content = file_get_contents($url);
    if ($content === false) {
        echo "FAILED!\n";
    } else {
        file_put_contents($path, $content);
        echo "OK\n";
    }
}
echo "Done.\n";
