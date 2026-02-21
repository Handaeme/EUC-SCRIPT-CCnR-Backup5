<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Check: script_templates</h1>";

if (!file_exists('config/database.php')) {
    die("config/database.php not found!");
}

$config = require 'config/database.php';
echo "<p>Connecting to Host: " . $config['host'] . "</p>";
echo "<p>Database: " . ($config['options']['Database'] ?? 'N/A') . "</p>";

$conn = sqlsrv_connect($config['host'], $config['options'] ?? []);

if (!$conn) {
    die("<h2>Connection Failed</h2><pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}
echo "<p style='color:green'>Connection Successful!</p>";

// Check Table
$sqlCheck = "SELECT * FROM script_templates";
$stmt = sqlsrv_query($conn, $sqlCheck);

if ($stmt === false) {
    echo "<p style='color:red'>Table 'script_templates' does NOT exist (or error selecting).</p>";
    echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    
    // Attempt Create
    echo "<hr><h3>Attempting to Create Table...</h3>";
    $sqlCreate = "CREATE TABLE script_templates (
        template_id INT IDENTITY(1,1) PRIMARY KEY,
        title VARCHAR(100),
        filename VARCHAR(255),
        filepath VARCHAR(255),
        uploaded_by VARCHAR(50),
        created_at DATETIME DEFAULT GETDATE()
    )";
    
    $stmtCreate = sqlsrv_query($conn, $sqlCreate);
    if ($stmtCreate) {
        echo "<h2 style='color:green'>Table Created Successfully!</h2>";
    } else {
        echo "<h2 style='color:red'>Creation Failed!</h2>";
        echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    }
} else {
    echo "<h2 style='color:blue'>Table 'script_templates' Exists!</h2>";
    echo "<p>Row Count: " . sqlsrv_num_rows($stmt) . " (Note: num_rows requires scrollable cursor, might show nothing if empty)</p>";
}
