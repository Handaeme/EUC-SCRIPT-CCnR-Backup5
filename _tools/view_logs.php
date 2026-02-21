<?php
// Simple script to check recent error logs
echo "<h2>Recent PHP Error Logs (Last 50 lines)</h2>";

// Try to find PHP error log location
$possibleLogs = [
    'D:\xampp\php\logs\php_error_log.txt',
    'D:\xampp\apache\logs\error.log',
    'C:\xampp\php\logs\php_error_log.txt',
    'C:\xampp\apache\logs\error.log',
    ini_get('error_log'),
    __DIR__ . '/error.log'
];

foreach ($possibleLogs as $logPath) {
    if ($logPath && file_exists($logPath)) {
        echo "<h3>Log: $logPath</h3>";
        $lines = file($logPath);
        $recentLines = array_slice($lines, -50);
        
        echo "<pre style='background:#f5f5f5; padding:10px; font-size:11px; max-height:400px; overflow:auto;'>";
        foreach ($recentLines as $line) {
            // Highlight DEBUG and ERROR
            if (strpos($line, 'DEBUG:') !== false) {
                echo "<span style='color:blue'>$line</span>";
            } elseif (strpos($line, 'ERROR:') !== false) {
                echo "<span style='color:red; font-weight:bold'>$line</span>";
            } elseif (strpos($line, 'SUCCESS:') !== false) {
                echo "<span style='color:green; font-weight:bold'>$line</span>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre>";
        break;
    }
}

if (!file_exists($logPath ?? '')) {
    echo "<p style='color:red'>No error log found. Possible locations checked:</p><ul>";
    foreach ($possibleLogs as $path) {
        echo "<li>$path</li>";
    }
    echo "</ul>";
}

echo "<p><a href='index.php'>Back to App</a> | <a href='debug_files.php'>Check Database</a></p>";
?>
