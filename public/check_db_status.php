<?php
// Simple DB Check accessible via browser
define('BASE_PATH', __DIR__ . '/..');
require_once BASE_PATH . '/app/helpers/DbAdapter.php';
require_once BASE_PATH . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/database.php';

echo "<h1>Database Check</h1>";

// 1. Connection
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    echo "<h2 style='color:red;'>Connection Failed</h2>";
    echo "<pre>" . print_r(db_errors(), true) . "</pre>";
    exit;
}
echo "<h2 style='color:green;'>Connection Successful</h2>";

// 2. Schema Check
echo "<h3>Schema Check (script_library)</h3>";
$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_library'";
$stmt = db_query($conn, $sql);
$cols = [];
while($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $cols[] = $row['COLUMN_NAME'];
}

$missing = [];
foreach(['is_active', 'activated_at', 'activated_by'] as $c) {
    if(!in_array($c, $cols)) $missing[] = $c;
}

if(empty($missing)) {
    echo "<p style='color:green;'>All activation columns present.</p>";
} else {
    echo "<p style='color:red;'>Missing columns: " . implode(', ', $missing) . "</p>";
    echo "<p>Please run the migration script.</p>";
}

// 3. Data Check
$sql = "SELECT COUNT(*) as total FROM script_library";
$stmt = db_query($conn, $sql);
$row = db_fetch_array($stmt, DB_FETCH_ASSOC);
echo "<h3>Data Check</h3>";
echo "<p>Total Scripts in Library: <strong>" . $row['total'] . "</strong></p>";

if ($row['total'] > 0) {
    $sql = "SELECT TOP 5 id, request_id, is_active FROM script_library ORDER BY created_at DESC";
    $stmt = db_query($conn, $sql);
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Req ID</th><th>Is Active</th></tr>";
    while($d = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        echo "<tr><td>{$d['id']}</td><td>{$d['request_id']}</td><td>{$d['is_active']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Library is empty.</p>";
}
