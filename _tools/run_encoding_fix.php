<?php
require_once '../config/database.php';
$conn = sqlsrv_connect($config['host'], ['Database'=>$config['dbname'], 'UID'=>$config['user'], 'PWD'=>$config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$sql_file = __DIR__ . '/fix_symbol_encoding.sql';
$sql_content = file_get_contents($sql_file);

// Split by command (assuming no ; inside string literals for now, or just run one by one if split)
// SQL Server uses GO usually, but here I just used ;
// `sqlsrv_query` can handle multiple statements in a batch usually, or we split.
// Let's try splitting by semicolon for safety.

$queries = explode(';', $sql_content);

echo "Starting Migration...\n";

foreach ($queries as $q) {
    $q = trim($q);
    if (empty($q)) continue;
    
    echo "Executing: " . substr($q, 0, 50) . "...\n";
    $stmt = sqlsrv_query($conn, $q);
    
    if ($stmt === false) {
        echo "ERROR: " . print_r(sqlsrv_errors(), true) . "\n";
    } else {
        echo "SUCCESS.\n";
    }
}

echo "Migration Complete.";
