<?php
// debug_template_status.php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die("DB Connection Failed.\n");

echo "=== COLUMN CHECK ===\n";
$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_templates'";
$stmt = sqlsrv_query($conn, $sql);
$hasDesc = false;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
    if ($row['COLUMN_NAME'] == 'description') $hasDesc = true;
}

if (!$hasDesc) {
    echo "\n[CRITICAL] 'description' column is MISSING!\n";
} else {
    echo "\n[OK] 'description' column EXISTS.\n";
}

echo "\n=== RECENT DATA (Last 5) ===\n";
$sql2 = "SELECT TOP 5 id, title, filename, len(description) as desc_len, CAST(description AS VARCHAR(100)) as desc_preview FROM script_templates ORDER BY id DESC";
$stmt2 = sqlsrv_query($conn, $sql2);
if ($stmt2 === false) {
    echo "Query Failed: ";
    print_r(sqlsrv_errors());
} else {
    while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Title: " . $row['title'] . "\n";
        echo "   -> Filename: " . $row['filename'] . "\n";
        echo "   -> Desc Length: " . ($row['desc_len'] ?? 'NULL') . "\n";
        echo "   -> Content: " . ($row['desc_preview'] ?? 'NULL') . "\n";
        echo "------------------------------------------------\n";
    }
}
?>
