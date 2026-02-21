<?php
require_once 'config/database.php';

//http://localhost/citra8/reset_data.php?confirm=yes

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>🔄 Reset Database (Keep Users)</h2>";
echo "<p style='color:orange;'><strong>⚠️ WARNING:</strong> Script ini akan menghapus SEMUA DATA kecuali tabel <code>script_users</code></p>";

// Konfirmasi (manual protection)
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<p style='color:red; font-size:16px;'>Untuk melanjutkan, tambahkan parameter <code>?confirm=yes</code> di URL.</p>";
    echo "<p>Contoh: <code>http://localhost/citra8/reset_data.php?confirm=yes</code></p>";
    sqlsrv_close($conn);
    exit;
}

echo "<p style='color:green;'>Konfirmasi diterima. Memulai reset...</p>";

// List tabel yang akan di-TRUNCATE (semua kecuali script_users)
$tablesToReset = [
    'script_library',
    'script_audit_trail',
    'script_files',
    'script_preview_content',
    'script_request'
];

echo "<h3>Tables to Reset:</h3>";
echo "<ul>";
foreach ($tablesToReset as $table) {
    echo "<li><code>$table</code></li>";
}
echo "</ul>";

// Disable foreign key constraints temporarily
echo "<p>Disabling foreign key constraints...</p>";
$disableFkSql = "EXEC sp_MSForEachTable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'";
sqlsrv_query($conn, $disableFkSql);

// Delete in correct order (children first, then parents)
$tablesToReset = [
    'script_library',       // No dependencies
    'script_audit_trail',   // References script_request
    'script_preview_content', // References script_request
    'script_files',         // References script_request
    'script_request'        // Parent table, delete last
];

// Delete each table
$success = [];
$errors = [];

foreach ($tablesToReset as $table) {
    echo "<p>Deleting data from <code>$table</code>...</p>";
    
    // Use DELETE instead of TRUNCATE (works with FK constraints)
    $deleteSql = "DELETE FROM $table";
    $stmt = sqlsrv_query($conn, $deleteSql);
    
    if ($stmt === false) {
        $error = print_r(sqlsrv_errors(), true);
        $errors[] = "$table: $error";
        echo "<p style='color:red;'>❌ Failed: $table</p>";
        echo "<pre style='color:red;'>$error</pre>";
    } else {
        // Reset identity column (auto-increment) to 1
        $reseedSql = "DBCC CHECKIDENT ('$table', RESEED, 0)";
        sqlsrv_query($conn, $reseedSql); // Ignore errors for tables without identity
        
        $success[] = $table;
        echo "<p style='color:green;'>✅ Success: $table (data deleted, identity reset)</p>";
    }
}

// Re-enable foreign key constraints
echo "<p>Re-enabling foreign key constraints...</p>";
$enableFkSql = "EXEC sp_MSForEachTable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL'";
sqlsrv_query($conn, $enableFkSql);

// Summary
echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Successfully Reset:</strong> " . count($success) . " tables</p>";
if (!empty($success)) {
    echo "<ul style='color:green;'>";
    foreach ($success as $table) {
        echo "<li>✅ $table</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<p><strong>Failed:</strong> " . count($errors) . " tables</p>";
    echo "<ul style='color:red;'>";
    foreach ($errors as $error) {
        echo "<li>❌ $error</li>";
    }
    echo "</ul>";
}

// Verify script_users is intact
echo "<hr>";
echo "<h3>Verifying script_users (should be unchanged):</h3>";
$verifySql = "SELECT COUNT(*) as user_count FROM script_users";
$verifyStmt = sqlsrv_query($conn, $verifySql);
$result = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC);

echo "<p style='color:green;'><strong>✅ script_users:</strong> " . $result['user_count'] . " users still exist</p>";

echo "<hr>";
echo "<h3 style='color:green;'>🎉 Reset Complete!</h3>";
echo "<p>Database telah di-reset. Semua request, audit log, files sudah terhapus.</p>";
echo "<p>User accounts tetap ada, jadi Anda bisa langsung login dan test dari awal.</p>";

sqlsrv_close($conn);
?>
