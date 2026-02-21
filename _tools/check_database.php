<?php
// Simple database diagnostic tool
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/database.php';

echo "<h2>Database Connection Test</h2>";
echo "<pre>";
echo "Host: " . ($config['host'] ?? 'NOT SET') . "\n";
echo "Database: " . ($config['dbname'] ?? 'NOT SET') . "\n";
echo "</pre>";

$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    echo "<h3 style='color:red;'>❌ Connection FAILED</h3>";
    echo "<pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
    die();
}

echo "<h3 style='color:green;'>✅ Connection SUCCESS</h3>";

// Check Tables
echo "<h3>Tables Check:</h3>";
$tables = ['tbluser', 'script_request', 'script_library', 'script_audit_trail', 'script_preview_content', 'script_files'];

foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        echo "<div style='color:red;'>❌ Table <b>$table</b>: NOT FOUND or ERROR</div>";
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
    } else {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $count = $row['count'] ?? 0;
        $status = $count > 0 ? '✅' : '⚠️';
        echo "<div>$status Table <b>$table</b>: $count rows</div>";
    }
}

// Check specific data
echo "<h3>Sample Data from script_request:</h3>";
$sql = "SELECT TOP 5 id, ticket_id, title, status FROM script_request ORDER BY id DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "<div style='color:red;'>Error querying script_request</div>";
    echo "<pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Ticket ID</th><th>Title</th><th>Status</th></tr>";
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['ticket_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

sqlsrv_close($conn);
?>
