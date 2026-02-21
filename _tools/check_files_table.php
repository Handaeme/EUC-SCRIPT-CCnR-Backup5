<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>🔍 Check script_files Table</h2>";

// Get columns
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'script_files' 
        ORDER BY ORDINAL_POSITION";
        
$stmt = sqlsrv_query($conn, $sql);

echo "<h3>Columns:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr style='background:#eee;'><th>Column Name</th><th>Data Type</th><th>Max Length</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td><strong>" . $row['COLUMN_NAME'] . "</strong></td>";
    echo "<td>" . $row['DATA_TYPE'] . "</td>";
    echo "<td>" . ($row['CHARACTER_MAXIMUM_LENGTH'] ?? 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get sample data
echo "<h3>Sample Data:</h3>";
$dataSql = "SELECT TOP 3 * FROM script_files";
$dataStmt = sqlsrv_query($conn, $dataSql);

if ($dataStmt && sqlsrv_has_rows($dataStmt)) {
    $firstRow = sqlsrv_fetch_array($dataStmt, SQLSRV_FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:11px;'>";
    echo "<tr style='background:#eee;'>";
    foreach ($firstRow as $key => $val) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    // Show first row
    echo "<tr>";
    foreach ($firstRow as $key => $val) {
        if ($val instanceof DateTime) {
            echo "<td>" . $val->format('Y-m-d H:i:s') . "</td>";
        } else {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
    }
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p style='color:#999;'>No data in table yet.</p>";
}

sqlsrv_close($conn);
?>
