<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>📋 Database Schema Dump</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin-bottom: 30px; font-size: 12px; }
    th { background: #d32f2f; color: white; padding: 8px; text-align: left; }
    td { padding: 6px; border: 1px solid #ddd; }
    .table-name { background: #f5f5f5; padding: 10px; margin-top: 20px; font-weight: bold; color: #d32f2f; }
    .section { background: #e3f2fd; padding: 5px 10px; margin-top: 10px; font-weight: bold; }
</style>";

// Get all tables
$tablesSql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME";
$tablesStmt = sqlsrv_query($conn, $tablesSql);

$tables = [];
while ($row = sqlsrv_fetch_array($tablesStmt, SQLSRV_FETCH_ASSOC)) {
    $tables[] = $row['TABLE_NAME'];
}

echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";

// For each table
foreach ($tables as $tableName) {
    echo "<div class='table-name'>📊 TABLE: $tableName</div>";
    
    // 1. Get columns
    echo "<div class='section'>Structure (Columns)</div>";
    $columnsSql = "SELECT 
                    COLUMN_NAME, 
                    DATA_TYPE, 
                    CHARACTER_MAXIMUM_LENGTH,
                    IS_NULLABLE,
                    COLUMN_DEFAULT
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_NAME = ? 
                   ORDER BY ORDINAL_POSITION";
    
    $columnsStmt = sqlsrv_query($conn, $columnsSql, [$tableName]);
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Max Length</th><th>Nullable</th><th>Default</th></tr>";
    
    while ($col = sqlsrv_fetch_array($columnsStmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . $col['COLUMN_NAME'] . "</strong></td>";
        echo "<td>" . $col['DATA_TYPE'] . "</td>";
        echo "<td>" . ($col['CHARACTER_MAXIMUM_LENGTH'] ?? 'N/A') . "</td>";
        echo "<td>" . $col['IS_NULLABLE'] . "</td>";
        echo "<td>" . ($col['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Get row count
    $countSql = "SELECT COUNT(*) as row_count FROM $tableName";
    $countStmt = sqlsrv_query($conn, $countSql);
    $countResult = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $rowCount = $countResult['row_count'];
    
    echo "<div class='section'>Data (Total Rows: $rowCount)</div>";
    
    if ($rowCount == 0) {
        echo "<p style='color:#999; padding:10px;'>⚠️ Table is empty</p>";
    } else {
        // Show sample data (first 3 rows)
        $dataSql = "SELECT TOP 3 * FROM $tableName";
        $dataStmt = sqlsrv_query($conn, $dataSql);
        
        if ($dataStmt && sqlsrv_has_rows($dataStmt)) {
            // Get first row to determine columns
            $firstRow = sqlsrv_fetch_array($dataStmt, SQLSRV_FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr>";
            foreach ($firstRow as $key => $val) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            // Display first row
            echo "<tr>";
            foreach ($firstRow as $key => $val) {
                if ($val instanceof DateTime) {
                    echo "<td>" . $val->format('Y-m-d H:i:s') . "</td>";
                } else {
                    $displayVal = htmlspecialchars(substr($val ?? '', 0, 100));
                    if (strlen($val ?? '') > 100) $displayVal .= '...';
                    echo "<td>" . $displayVal . "</td>";
                }
            }
            echo "</tr>";
            
            // Display remaining rows (if any)
            $dataStmt = sqlsrv_query($conn, $dataSql); // Re-query to get all 3
            sqlsrv_fetch($dataStmt); // Skip first (already shown)
            
            while ($row = sqlsrv_fetch_array($dataStmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>";
                foreach ($row as $key => $val) {
                    if ($val instanceof DateTime) {
                        echo "<td>" . $val->format('Y-m-d H:i:s') . "</td>";
                    } else {
                        $displayVal = htmlspecialchars(substr($val ?? '', 0, 100));
                        if (strlen($val ?? '') > 100) $displayVal .= '...';
                        echo "<td>" . $displayVal . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
            
            if ($rowCount > 3) {
                echo "<p style='color:#666; font-style:italic;'>... and " . ($rowCount - 3) . " more rows</p>";
            }
        }
    }
    
    echo "<hr style='margin: 20px 0; border: none; border-top: 2px solid #eee;'>";
}

echo "<h3 style='color:green;'>✅ Database Dump Complete!</h3>";

sqlsrv_close($conn);
?>
