<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Insert: script_templates</h1>";

if (!file_exists('config/database.php')) die("Config missing");
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die("Connection Failed: " . print_r(sqlsrv_errors(), true));
echo "<p>Connected.</p>";

// TEST INSERT
$title = "Debug Test " . date('H:i:s');
$filename = "debug.xlsx";
$filepath = "uploads/templates/debug.xlsx";
$user = "SYSTEM_DEBUG";

$sql = "INSERT INTO script_templates (title, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)";
$params = [$title, $filename, $filepath, $user]; // 4 params

echo "<p>Executing SQL: $sql</p>";
echo "<p>Params: " . print_r($params, true) . "</p>";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo "<h2 style='color:green'>INSERT Successful!</h2>";
    
    $rows = sqlsrv_rows_affected($stmt);
    echo "<p>Rows Affected: $rows</p>";
    
    // VERIFY
    $verify = sqlsrv_query($conn, "SELECT TOP 1 * FROM script_templates ORDER BY created_at DESC");
    if ($r = sqlsrv_fetch_array($verify, SQLSRV_FETCH_ASSOC)) {
        echo "<pre>Row Found in DB: " . print_r($r, true) . "</pre>";
    } else {
        echo "<h2 style='color:red'>Insert reported success but SELECT returned nothing!</h2>";
    }
} else {
    echo "<h2 style='color:red'>INSERT FAILED</h2>";
    echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
}
