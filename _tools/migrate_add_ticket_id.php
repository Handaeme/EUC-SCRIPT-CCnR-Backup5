<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Migration: Add ticket_id Column</h2>";

// Step 1: Check if column already exists
echo "<p>Step 1: Checking if column already exists...</p>";
$checkSql = "SELECT COUNT(*) as col_count 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
$checkStmt = sqlsrv_query($conn, $checkSql);
$result = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if ($result['col_count'] > 0) {
    echo "<p style='color:orange;'>⚠️ Column 'ticket_id' already exists. Skipping migration.</p>";
    sqlsrv_close($conn);
    exit;
}

echo "<p style='color:green;'>✅ Column does not exist. Proceeding with migration...</p>";

// Step 2: Add ticket_id column
echo "<p>Step 2: Adding ticket_id column...</p>";
$alterSql = "ALTER TABLE script_request 
             ADD ticket_id NVARCHAR(50)";

$alterStmt = sqlsrv_query($conn, $alterSql);

if ($alterStmt === false) {
    die("<p style='color:red;'>❌ Failed to add column: " . print_r(sqlsrv_errors(), true) . "</p>");
}

echo "<p style='color:green;'>✅ Column 'ticket_id' added successfully</p>";

// Step 3: Generate ticket IDs for existing records
echo "<p>Step 3: Generating ticket IDs for existing records...</p>";
$getSql = "SELECT id FROM script_request ORDER BY id ASC";
$getStmt = sqlsrv_query($conn, $getSql);

$counter = 1;
while ($row = sqlsrv_fetch_array($getStmt, SQLSRV_FETCH_ASSOC)) {
    $ticketId = sprintf("SC-%04d", $counter);
    $updateSql = "UPDATE script_request SET ticket_id = ? WHERE id = ?";
    sqlsrv_query($conn, $updateSql, [$ticketId, $row['id']]);
    $counter++;
}

echo "<p style='color:green;'>✅ Updated " . ($counter - 1) . " existing records with ticket IDs</p>";

// Step 4: Verify
echo "<p>Step 4: Verifying table structure...</p>";
$verifySql = "SELECT TOP 5 id, ticket_id, script_number FROM script_request ORDER BY id DESC";
$verifyStmt = sqlsrv_query($conn, $verifySql);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
echo "<tr style='background:#eee;'><th>ID</th><th>Ticket ID</th><th>Script Number</th></tr>";
while ($row = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['ticket_id']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['script_number']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color:green;'>✅ Migration Complete!</h3>";
echo "<p><strong>New Format:</strong></p>";
echo "<ul>";
echo "<li><strong>Ticket ID:</strong> SC-0001, SC-0002, etc.</li>";
echo "<li><strong>Script Number:</strong> KONV/SYR-MEDIA-DD/MM/YY-0001-01</li>";
echo "</ul>";

sqlsrv_close($conn);
?>
