<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>🔍 Audit Trail Debug</h2>";

// Check script_audit_trail
echo "<h3>1. Audit Trail Records:</h3>";
$auditSql = "SELECT TOP 10 * FROM script_audit_trail ORDER BY created_at DESC";
$auditStmt = sqlsrv_query($conn, $auditSql);

if (!$auditStmt) {
    echo "<p style='color:red;'>Query failed: " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    $count = 0;
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
    echo "<tr style='background:#eee;'>";
    echo "<th>ID</th><th>Request ID</th><th>Script Number</th><th>Action</th><th>Role</th><th>User ID</th><th>Details</th><th>Created At</th>";
    echo "</tr>";
    
    while ($row = sqlsrv_fetch_array($auditStmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>" . $row['audit_id'] . "</td>";
        echo "<td>" . $row['request_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['script_number']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['action']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['user_role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['details']) . "</td>";
        echo "<td>" . ($row['created_at'] ? $row['created_at']->format('Y-m-d H:i:s') : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($count == 0) {
        echo "<p style='color:red;'>❌ Tabel script_audit_trail KOSONG!</p>";
    } else {
        echo "<p style='color:green;'>✅ Found $count audit records</p>";
    }
}

// Check script_request
echo "<h3>2. Recent Requests:</h3>";
$reqSql = "SELECT TOP 5 id, script_number, title, status, created_by, created_at FROM script_request ORDER BY created_at DESC";
$reqStmt = sqlsrv_query($conn, $reqSql);

if (!$reqStmt) {
    echo "<p style='color:red;'>Query failed: " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    $count = 0;
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
    echo "<tr style='background:#eee;'>";
    echo "<th>ID</th><th>Script Number</th><th>Title</th><th>Status</th><th>Created By</th><th>Created At</th>";
    echo "</tr>";
    
    while ($row = sqlsrv_fetch_array($reqStmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['script_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
        echo "<td>" . ($row['created_at'] ? $row['created_at']->format('Y-m-d H:i:s') : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($count == 0) {
        echo "<p style='color:red;'>❌ Tidak ada request!</p>";
    } else {
        echo "<p style='color:green;'>✅ Found $count requests</p>";
    }
}

// Check if logAudit is being called
echo "<h3>3. Check Session User:</h3>";
session_start();
if (isset($_SESSION['user'])) {
    echo "<p style='color:green;'>✅ Session user: <strong>" . htmlspecialchars($_SESSION['user']['userid']) . "</strong></p>";
    echo "<p>Role: " . htmlspecialchars($_SESSION['user']['dept']) . "</p>";
} else {
    echo "<p style='color:red;'>❌ No user session!</p>";
}

sqlsrv_close($conn);
?>
