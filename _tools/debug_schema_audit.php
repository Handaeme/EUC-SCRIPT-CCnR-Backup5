<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Debug: script_audit_trail Schema</h2>";

// Get column information
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'script_audit_trail' 
        ORDER BY ORDINAL_POSITION";

$stmt = sqlsrv_query($conn, $sql);

echo "<h3>Actual Columns in Database:</h3>";
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

// Now get sample data using SELECT *
echo "<h3>Sample Data (Latest 3 Records):</h3>";
$sql2 = "SELECT TOP 3 * FROM script_audit_trail ORDER BY created_at DESC";
$stmt2 = sqlsrv_query($conn, $sql2);

if ($stmt2 && sqlsrv_has_rows($stmt2)) {
    $row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:11px;'>";
    echo "<tr style='background:#eee;'>";
    foreach ($row as $key => $val) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    // Reset and display all rows
    $stmt2 = sqlsrv_query($conn, $sql2);
    while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $val) {
            if ($val instanceof DateTime) {
                echo "<td>" . $val->format('Y-m-d H:i:s') . "</td>";
            } else {
                echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
}

sqlsrv_close($conn);
?>
