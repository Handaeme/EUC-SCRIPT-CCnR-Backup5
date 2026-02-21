<?php
$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], isset($config['options']) ? $config['options'] : []);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

$sqlFile = __DIR__ . '/../migration_full.sql';
$sqlContent = file_get_contents($sqlFile);

// Remove "GO" statements as they are SSMS specific and sqlsrv_query doesn't support them in batch
$batches = preg_split('/^GO\s*$/m', $sqlContent);

foreach ($batches as $batch) {
    $batch = trim($batch);
    if (empty($batch)) continue;

    $stmt = sqlsrv_query($conn, $batch);
    if ($stmt === false) {
        // If error implies "Table already exists", we might ignore or print warning.
        // But for now let's print all errors.
        echo "Error in batch:\n" . substr($batch, 0, 100) . "...\n";
        print_r(sqlsrv_errors());
    } else {
        echo "Batch executed successfully.\n";
    }
}

echo "Migration script completed.\n";
sqlsrv_close($conn);
