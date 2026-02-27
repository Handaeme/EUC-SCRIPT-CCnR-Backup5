<?php
/**
 * Quick Error Log Reader
 */
$logFile = ini_get('error_log');
if (!$logFile) {
    // Try common locations
    if (file_exists('/var/log/apache2/error.log')) $logFile = '/var/log/apache2/error.log';
    elseif (file_exists('C:\\xampp\\apache\\logs\\error.log')) $logFile = 'C:\\xampp\\apache\\logs\\error.log';
    else $logFile = 'php_errors.log';
}

echo "<html><body style='font-family:sans-serif; padding:20px;'>";
echo "<h2>📜 PHP Error Log</h2>";

if (file_exists($logFile)) {
    echo "<p>Reading from: " . htmlspecialchars($logFile) . "</p>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ccc; max-height: 500px; overflow-y:auto;'>";
    foreach ($lastLines as $line) {
        $color = (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) ? 'red' : 'black';
        echo "<span style='color:$color; display:block; border-bottom:1px solid #eee; padding:3px 0;'>" . htmlspecialchars($line) . "</span>";
    }
    echo "</pre>";
} else {
    echo "<p>No error log found at $logFile</p>";
    echo "<h3>Raw PHP Error Test</h3>";
    // Check if sqlsrv_errors is defined
    echo "<p>Function <b>sqlsrv_errors</b> exists? " . (function_exists('sqlsrv_errors') ? 'YES' : 'NO') . "</p>";
}
echo "</body></html>";
