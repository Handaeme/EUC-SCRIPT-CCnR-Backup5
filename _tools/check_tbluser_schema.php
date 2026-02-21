<?php
// Check tbluser schema
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

echo "<h2>tbluser Table Structure Check</h2>";

// Query to get column information
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'tbluser'
        ORDER BY ORDINAL_POSITION";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "<div style='color:red;'>❌ Query FAILED</div><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    echo "<h3>Columns in tbluser:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Max Length</th><th>Nullable</th></tr>";
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $highlight = '';
        // Highlight columns that might be the group column
        if (stripos($row['COLUMN_NAME'], 'GROUP') !== false || 
            stripos($row['COLUMN_NAME'], 'GRP') !== false ||
            stripos($row['COLUMN_NAME'], 'DEPT') !== false) {
            $highlight = ' style="background:#ffeb3b; font-weight:bold;"';
        }
        
        echo "<tr$highlight>";
        echo "<td>" . htmlspecialchars($row['COLUMN_NAME']) . "</td>";
        echo "<td>" . $row['DATA_TYPE'] . "</td>";
        echo "<td>" . ($row['CHARACTER_MAXIMUM_LENGTH'] ?? '-') . "</td>";
        echo "<td>" . $row['IS_NULLABLE'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Also show sample data
echo "<h3>Sample Data (first 3 rows):</h3>";
$sql2 = "SELECT TOP 3 * FROM tbluser";
$stmt2 = sqlsrv_query($conn, $sql2);

if ($stmt2 === false) {
    echo "<div style='color:red;'>Error</div><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    echo "<table border='1' cellpadding='5' style='font-size:12px;'>";
    $firstRow = true;
    while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        if ($firstRow) {
            echo "<tr>";
            foreach (array_keys($row) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>";
            $firstRow = false;
        }
        
        echo "<tr>";
        foreach ($row as $val) {
            if ($val instanceof DateTime) {
                $val = $val->format('Y-m-d');
            }
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

sqlsrv_close($conn);
?>
