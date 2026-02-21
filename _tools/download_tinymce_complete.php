<?php
$baseUrl = "https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/";
$targetDir = __DIR__ . "/public/assets/js/tinymce/";

// Complete minimal files including skins (required for UI)
$files = [
    "tinymce.min.js",
    "themes/silver/theme.min.js",
    "models/dom/model.min.js",
    "icons/default/icons.min.js",
    // Skins (UI CSS - WAJIB!)
    "skins/ui/oxide/skin.min.css",
    "skins/ui/oxide/content.min.css",
    "skins/content/default/content.min.css"
];

if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

echo "<!DOCTYPE html><html><head><title>Download TinyMCE Complete Core</title></head><body>";
echo "<h2>Downloading TinyMCE Core + Skins...</h2>";
echo "<pre>";

foreach ($files as $file) {
    $url = $baseUrl . $file;
    $path = $targetDir . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    echo "Downloading $file... ";
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "FAILED!\n";
    } else {
        file_put_contents($path, $content);
        echo "OK (" . number_format(strlen($content)) . " bytes)\n";
    }
}

echo "\nDone! All core files + skins downloaded.\n";
echo "</pre>";
echo "<p><strong>Now refresh your Create page!</strong></p>";
echo "<p><a href='/citra8/app/views/request/create.php'>Go to Create Page</a></p>";
echo "</body></html>";
