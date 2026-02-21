<?php
require_once 'config/database.php';
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request'";
$stmt = sqlsrv_query($conn, $sql);
$cols = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $cols[] = $row['COLUMN_NAME'];
}

echo "Columns: " . implode(', ', $cols) . "\n";

if (!in_array('ticket_id', $cols)) {
    echo "Adding ticket_id...\n";
    $sql = "ALTER TABLE script_request ADD ticket_id INT";
    if (sqlsrv_query($conn, $sql)) echo "ticket_id added.\n";
    else echo "Failed to add ticket_id.\n";
}

if (!in_array('id', $cols) && in_array('script_id', $cols)) {
    echo "ID column missing, script_id found. Renaming or creating alias?\n";
    // Usually we want 'id' for simplicity.
    // Let's just create an alias for now in queries or rename it.
    // Better to rename it to 'id' if 'script_id' is the identity.
    // EXEC sp_rename 'script_request.script_id', 'id', 'COLUMN';
}
?>
