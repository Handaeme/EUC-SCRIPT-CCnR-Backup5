<?php
// Debug: Check actual paths generated
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . "://" . $host . $script;
$baseUrl = preg_replace('#/app/views/request$#', '', $baseUrl);

echo "<!DOCTYPE html><html><head><title>TinyMCE Path Debug</title></head><body>";
echo "<h2>TinyMCE Path Debug</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";
echo "<tr><td>Protocol</td><td>$protocol</td></tr>";
echo "<tr><td>Host</td><td>$host</td></tr>";
echo "<tr><td>Script Name</td><td>{$_SERVER['SCRIPT_NAME']}</td></tr>";
echo "<tr><td>Dirname(Script)</td><td>$script</td></tr>";
echo "<tr><td><strong>Base URL</strong></td><td><strong>$baseUrl</strong></td></tr>";
echo "<tr><td><strong>TinyMCE URL</strong></td><td><strong>{$baseUrl}/public/assets/js/tinymce/tinymce.min.js</strong></td></tr>";
echo "</table>";

echo "<h3>Test Access:</h3>";
$tinyPath = $_SERVER['DOCUMENT_ROOT'] . "/public/assets/js/tinymce/tinymce.min.js";
echo "<p>Server Path: <code>$tinyPath</code></p>";
echo "<p>File exists: " . (file_exists($tinyPath) ? "✅ YES" : "❌ NO") . "</p>";

$altPath = $_SERVER['DOCUMENT_ROOT'] . "/citra8/public/assets/js/tinymce/tinymce.min.js";
echo "<p>Alternative Path: <code>$altPath</code></p>";
echo "<p>File exists: " . (file_exists($altPath) ? "✅ YES" : "❌ NO") . "</p>";

echo "<h3>Try Direct Access:</h3>";
echo "<p><a href='{$baseUrl}/public/assets/js/tinymce/tinymce.min.js' target='_blank'>Click to test TinyMCE file access</a></p>";
echo "</body></html>";
