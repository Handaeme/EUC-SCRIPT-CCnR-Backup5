<?php
// GLOBAL MIGRATION & FIX FOR CITRA
require_once 'config/database.php';
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h1>Database Fix & Migration</h1>";

function checkColumn($conn, $table, $col) {
    // Check if table exists first
    $checkTable = "SELECT * FROM sysobjects WHERE name = ? AND xtype = 'U'";
    $stmtTable = sqlsrv_query($conn, $checkTable, [$table]);
    if (!$stmtTable || !sqlsrv_has_rows($stmtTable)) return false;

    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = sqlsrv_query($conn, $sql, [$table, $col]);
    return sqlsrv_has_rows($stmt);
}

function runSql($conn, $sql, $desc) {
    echo "<li>$desc... ";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $isWarning = true;
        foreach ($errors as $error) {
            if ($error['SQLSTATE'] != '01000') { // 01000 is General Warning (like sp_rename caution)
                $isWarning = false;
                break;
            }
        }
        
        if ($isWarning) {
            echo "<span style='color:green;'>Success (with warning)</span></li>";
        } else {
            echo "<span style='color:red;'>Failed</span> (" . print_r($errors, true) . ")</li>";
        }
    } else {
        echo "<span style='color:green;'>Success</span></li>";
    }
}

echo "<ul>";

// 1. ticket_id in script_request
if (!checkColumn($conn, 'script_request', 'ticket_id')) {
    runSql($conn, "ALTER TABLE script_request ADD ticket_id INT", "Adding ticket_id to script_request");
}

// 2. Renaming script_id -> id (Primary Keys)
$idRenames = [
    'script_request' => 'script_id',
    'script_files' => 'file_id',
    'script_audit_trail' => 'log_id',
    'script_library' => 'lib_id',
    'script_templates' => 'template_id'
];

foreach ($idRenames as $table => $oldCol) {
    if (checkColumn($conn, $table, $oldCol) && !checkColumn($conn, $table, 'id')) {
        runSql($conn, "exec sp_rename '$table.$oldCol', 'id', 'COLUMN'", "Renaming $oldCol to id in $table");
    }
}

// 3. Renaming script_id -> request_id (Foreign Keys)
$fkRenames = [
    'script_preview_content' => 'script_id',
    'script_files' => 'script_id',
    'script_audit_trail' => 'script_id',
    'script_library' => 'script_id'
];

foreach ($fkRenames as $table => $oldCol) {
    if (checkColumn($conn, $table, $oldCol) && !checkColumn($conn, $table, 'request_id')) {
        runSql($conn, "exec sp_rename '$table.$oldCol', 'request_id', 'COLUMN'", "Renaming $oldCol to request_id in $table");
    }
}

// 4. Audit Trail Column Refactor (role -> user_role, note -> details)
if (checkColumn($conn, 'script_audit_trail', 'role') && !checkColumn($conn, 'script_audit_trail', 'user_role')) {
    runSql($conn, "exec sp_rename 'script_audit_trail.role', 'user_role', 'COLUMN'", "Renaming role to user_role in script_audit_trail");
}
if (checkColumn($conn, 'script_audit_trail', 'note') && !checkColumn($conn, 'script_audit_trail', 'details')) {
    runSql($conn, "exec sp_rename 'script_audit_trail.note', 'details', 'COLUMN'", "Renaming note to details in script_audit_trail");
}

// 5. script_files table - handle filename/original_filename column
if (checkColumn($conn, 'script_files', 'filename')) {
    // Old column exists, rename it
    if (!checkColumn($conn, 'script_files', 'original_filename')) {
        runSql($conn, "exec sp_rename 'script_files.filename', 'original_filename', 'COLUMN'", "Renaming filename to original_filename in script_files");
    }
} else {
    // Column doesn't exist at all, add it
    if (!checkColumn($conn, 'script_files', 'original_filename')) {
        runSql($conn, "ALTER TABLE script_files ADD original_filename VARCHAR(255)", "Adding original_filename to script_files");
    }
}

// 6. script_files table - handle file_path/filepath column  
if (checkColumn($conn, 'script_files', 'file_path')) {
    // Old column exists, rename it
    if (!checkColumn($conn, 'script_files', 'filepath')) {
        runSql($conn, "exec sp_rename 'script_files.file_path', 'filepath', 'COLUMN'", "Renaming file_path to filepath in script_files");
    }
} else {
    // Column doesn't exist at all, add it
    if (!checkColumn($conn, 'script_files', 'filepath')) {
        runSql($conn, "ALTER TABLE script_files ADD filepath VARCHAR(255)", "Adding filepath to script_files");
    }
}

// 7. Fix script_files.uploaded_by column type (INT -> VARCHAR)
if (checkColumn($conn, 'script_files', 'uploaded_by')) {
    echo "<li>Fixing uploaded_by column type in script_files... ";
    
    // Get all FK constraints on this column
    $getFKs = "SELECT name FROM sys.foreign_keys 
               WHERE parent_object_id = OBJECT_ID('script_files') 
               AND COL_NAME(parent_object_id, parent_column_id) = 'uploaded_by'";
    $fkStmt = sqlsrv_query($conn, $getFKs);
    
    // Drop each FK found
    if ($fkStmt) {
        while ($fk = sqlsrv_fetch_array($fkStmt, SQLSRV_FETCH_ASSOC)) {
            $dropSQL = "ALTER TABLE script_files DROP CONSTRAINT " . $fk['name'];
            sqlsrv_query($conn, $dropSQL);
            echo "<span style='color:orange'>(Dropped FK: {$fk['name']})</span> ";
        }
    }
    
    // Now alter column
    $alterResult = sqlsrv_query($conn, "ALTER TABLE script_files ALTER COLUMN uploaded_by VARCHAR(50)");
    if ($alterResult === false) {
        echo "<span style='color:red'>Failed</span> (" . print_r(sqlsrv_errors(), true) . ")</li>";
    } else {
        echo "<span style='color:green'>Success</span></li>";
    }
}

echo "</ul>";
echo "<h3>Migration Finished.</h3>";
echo "<p><a href='index.php'>Back to App</a></p>";
sqlsrv_close($conn);
?>
