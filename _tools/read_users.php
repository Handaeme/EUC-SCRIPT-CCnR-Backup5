<?php
// Adjust path to config
$config = require __DIR__ . '/../config/database.php';

// Connect using sqlsrv
$conn = sqlsrv_connect($config['host'], isset($config['options']) ? $config['options'] : []);

if (!$conn) {
    echo "Connection failed.\n";
    die(print_r(sqlsrv_errors(), true));
}

// Query
$sql = "SELECT * FROM script_users";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "Query failed.\n";
    die(print_r(sqlsrv_errors(), true));
}

echo "Query successful. Fetching data...\n\n";

$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}

if (empty($rows)) {
    echo "No users found.\n";
    exit;
}

// Get columns from first row
$columns = array_keys($rows[0]);

// Print Header
foreach ($columns as $col) {
    echo str_pad($col, 20) . " | ";
}
echo "\n" . str_repeat("-", count($columns) * 23) . "\n";

// Print Rows
foreach ($rows as $row) {
    foreach ($row as $val) {
        if ($val instanceof DateTime) {
            $val = $val->format('Y-m-d H:i:s');
        }
        echo str_pad((string)$val, 20) . " | ";
    }
    echo "\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
