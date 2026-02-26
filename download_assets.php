<?php
$jsUrl = "https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js";
$cssUrl = "https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css";

if (!is_dir('public/js')) mkdir('public/js', 0777, true);
if (!is_dir('public/css')) mkdir('public/css', 0777, true);

file_put_contents('public/js/xlsx.bundle.js', file_get_contents($jsUrl));
file_put_contents('public/css/uicons-regular-rounded.css', file_get_contents($cssUrl));

echo "Download completed successfully.\n";
