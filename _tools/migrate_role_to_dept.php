<?php
// _tools/migrate_role_to_dept.php
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

echo "Starting Migration: ROLE_CODE -> DEPT\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Check if ROLE_CODE exists
    echo "Checking columns...\n";
    $stm = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbluser' AND COLUMN_NAME = 'ROLE_CODE'");
    $hasRoleCode = $stm->fetch();

    $stm2 = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbluser' AND COLUMN_NAME = 'DEPT'");
    $hasDept = $stm2->fetch();

    if ($hasDept) {
        echo "WARNING: Column 'DEPT' already exists. Skipping rename.\n";
    } elseif ($hasRoleCode) {
        echo "Found 'ROLE_CODE'. Renaming to 'DEPT'...\n";
        
        // SQL Server Syntax for Rename
        $sql = "EXEC sp_rename 'tbluser.ROLE_CODE', 'DEPT', 'COLUMN';";
        $conn->exec($sql);
        
        echo "SUCCESS: Column renamed to DEPT.\n";
    } else {
        echo "ERROR: Column 'ROLE_CODE' not found found. Maybe already renamed?\n";
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
