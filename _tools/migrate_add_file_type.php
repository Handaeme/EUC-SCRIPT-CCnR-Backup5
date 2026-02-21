<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Migration: Add file_type to script_files</h2>";

// Step 1: Check if column already exists
echo "<p>Step 1: Checking if column already exists...</p>";
$checkSql = "SELECT COUNT(*) as col_count 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_NAME = 'script_files' AND COLUMN_NAME = 'file_type'";
$checkStmt = sqlsrv_query($conn, $checkSql);
$result = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if ($result['col_count'] > 0) {
    echo "<p style='color:orange;'>⚠️ Column 'file_type' already exists. Skipping migration.</p>";
    sqlsrv_close($conn);
    exit;
}

echo "<p style='color:green;'>✅ Column does not exist. Proceeding with migration...</p>";

// Step 2: Add file_type column
echo "<p>Step 2: Adding file_type column...</p>";
$alterSql = "ALTER TABLE script_files 
             ADD file_type NVARCHAR(50) DEFAULT 'TEMPLATE'";

$alterStmt = sqlsrv_query($conn, $alterSql);

if ($alterStmt === false) {
    die("<p style='color:red;'>❌ Failed to add column: " . print_r(sqlsrv_errors(), true) . "</p>");
}

echo "<p style='color:green;'>✅ Column 'file_type' added successfully</p>";

// Step 3: Add constraint (optional but recommended)
echo "<p>Step 3: Adding check constraint...</p>";
$constraintSql = "ALTER TABLE script_files 
                  ADD CONSTRAINT CHK_file_type 
                  CHECK (file_type IN ('TEMPLATE', 'LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP'))";

$constraintStmt = sqlsrv_query($conn, $constraintSql);

if ($constraintStmt === false) {
    echo "<p style='color:orange;'>⚠️ Failed to add constraint (may already exist): " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    echo "<p style='color:green;'>✅ Check constraint added successfully</p>";
}

// Step 4: Verify
echo "<p>Step 4: Verifying table structure...</p>";
$verifySql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'script_files' 
              ORDER BY ORDINAL_POSITION";
$verifyStmt = sqlsrv_query($conn, $verifySql);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
echo "<tr style='background:#eee;'><th>Column</th><th>Type</th><th>Max Length</th><th>Default</th></tr>";
while ($row = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td><strong>" . $row['COLUMN_NAME'] . "</strong></td>";
    echo "<td>" . $row['DATA_TYPE'] . "</td>";
    echo "<td>" . ($row['CHARACTER_MAXIMUM_LENGTH'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color:green;'>✅ Migration Complete!</h3>";
echo "<p><strong>Allowed file_type values:</strong></p>";
echo "<ul>";
echo "<li><code>TEMPLATE</code> - File template asli (default)</li>";
echo "<li><code>LEGAL</code> - Dokumen Legal Review</li>";
echo "<li><code>CX</code> - Dokumen CX Review</li>";
echo "<li><code>LEGAL_SYARIAH</code> - Dokumen Legal Syariah (Optional)</li>";
echo "<li><code>LPP</code> - Dokumen LPP (Optional)</li>";
echo "</ul>";
echo "<p>Sekarang Anda bisa upload dokumen di tahap Procedure dengan tipe dokumen yang berbeda.</p>";

sqlsrv_close($conn);
?>
