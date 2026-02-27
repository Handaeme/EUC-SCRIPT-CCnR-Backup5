<?php
/**
 * FIX PIC DEPT - UPDATE SCRIPT
 * Run this ONCE on the server to update EC33067X to be a PIC.
 */
session_start();
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🛠️ Fix PIC Department</h2>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
if (!$conn) die("<p style='color:red;'>❌ DB connection failed</p>");

// List of UserIDs to update as PICs
// Add more UserIDs to this array if you need more PICs
$pic_users = ['EC33067X'];

foreach ($pic_users as $userId) {
    echo "<h3>Updating User: {$userId}...</h3>";
    
    // Check current state
    $checkSql = "SELECT FULLNAME, DEPT FROM tbluser WHERE USERID = ?";
    $stmtCheck = db_query($conn, $checkSql, [$userId]);
    
    if ($stmtCheck && $row = db_fetch_array($stmtCheck, DB_FETCH_ASSOC)) {
        echo "<p>Current DEPT for <b>{$row['FULLNAME']}</b>: [{$row['DEPT']}]</p>";
        
        // Execute Update
        $updateSql = "UPDATE tbluser SET DEPT = 'PIC' WHERE USERID = ?";
        $stmtUpdate = db_query($conn, $updateSql, [$userId]);
        
        if ($stmtUpdate) {
            echo "<p style='color:green;'>✅ Successfully updated {$row['FULLNAME']} to DEPT = 'PIC'!</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to update. Check database permissions.</p>";
            echo "<pre>" . print_r(db_errors(), true) . "</pre>";
        }
    } else {
        echo "<p style='color:red;'>❌ User {$userId} not found in tbluser!</p>";
    }
}

echo "<h3><a href='../index.php' style='padding:10px 15px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Aplikasi</a></h3>";
echo "</body></html>";
