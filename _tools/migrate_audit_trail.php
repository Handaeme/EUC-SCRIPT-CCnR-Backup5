<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Migration: Reset script_audit_trail</h2>";

// Step 1: Drop existing table
echo "<p>Step 1: Dropping existing table...</p>";
$dropSql = "IF OBJECT_ID('script_audit_trail', 'U') IS NOT NULL DROP TABLE script_audit_trail";
$dropStmt = sqlsrv_query($conn, $dropSql);

if ($dropStmt === false) {
    die("<p style='color:red;'>❌ Failed to drop table: " . print_r(sqlsrv_errors(), true) . "</p>");
}
echo "<p style='color:green;'>✅ Table dropped successfully</p>";

// Step 2: Create new table with correct schema
echo "<p>Step 2: Creating new table with correct schema...</p>";
$createSql = "
CREATE TABLE script_audit_trail (
    audit_id INT IDENTITY(1,1) PRIMARY KEY,
    request_id INT NOT NULL,
    script_number NVARCHAR(100),
    action NVARCHAR(50) NOT NULL,
    user_role NVARCHAR(50),
    user_id NVARCHAR(50),
    details NVARCHAR(MAX),
    created_at DATETIME2 DEFAULT GETDATE()
)";

$createStmt = sqlsrv_query($conn, $createSql);

if ($createStmt === false) {
    die("<p style='color:red;'>❌ Failed to create table: " . print_r(sqlsrv_errors(), true) . "</p>");
}
echo "<p style='color:green;'>✅ Table created successfully with correct schema</p>";

// Step 3: Verify
echo "<p>Step 3: Verifying new table structure...</p>";
$verifySql = "SELECT COLUMN_NAME, DATA_TYPE 
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'script_audit_trail' 
              ORDER BY ORDINAL_POSITION";
$verifyStmt = sqlsrv_query($conn, $verifySql);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr style='background:#eee;'><th>Column Name</th><th>Data Type</th></tr>";
while ($row = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td><strong>" . $row['COLUMN_NAME'] . "</strong></td>";
    echo "<td>" . $row['DATA_TYPE'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color:green;'>✅ Migration Complete!</h3>";
echo "<p>Tabel <code>script_audit_trail</code> sudah di-reset dengan struktur yang benar.</p>";
echo "<p>Silakan test submit request baru untuk populate audit trail.</p>";

sqlsrv_close($conn);
?>
