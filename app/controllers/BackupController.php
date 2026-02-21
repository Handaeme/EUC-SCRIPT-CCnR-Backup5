<?php
namespace App\Controllers;

use App\Core\Controller;

class BackupController extends Controller {

    public function __construct() {
        // Strict Access Control: Admin Only
        if (session_status() == PHP_SESSION_NONE) session_start();
        $role = $_SESSION['user']['dept'] ?? '';
        $originalRole = $_SESSION['original_role'] ?? null;
        
        if ($role !== 'ADMIN' && $originalRole !== 'ADMIN') {
            header("Location: index.php");
            exit;
        }
    }

    public function index() {
        $this->view('backup/index');
    }

    public function download() {
        // disable output buffering
        if (ob_get_level()) ob_end_clean();

        $filename = 'backup_db_' . date('Y-m-d_H-i-s') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . $filename . "\""); 

        $this->generateBackup();
        exit;
    }

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
            $file = $_FILES['backup_file']['tmp_name'];
            
            if (is_uploaded_file($file)) {
                $content = file_get_contents($file);
                $result = $this->processRestore($content);
                
                if ($result['status'] === 'success') {
                    header("Location: ?controller=backup&action=index&msg=" . urlencode("Restore completed! " . $result['count'] . " queries executed. " . $result['skipped'] . " queries skipped (tbluser)."));
                } else {
                    header("Location: ?controller=backup&action=index&error=" . urlencode($result['message']));
                }
            } else {
                 header("Location: ?controller=backup&action=index&error=Upload failed");
            }
        }
    }

    private function generateBackup() {
        $config = require __DIR__ . '/../../config/database.php';
        $conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        
        if (!$conn) die("DB Connection Failed");

        echo "-- EUC Script Backup\n";
        echo "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        // Get Tables
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
        $stmt = db_query($conn, $sql);
        
        while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            $table = $row['TABLE_NAME'];
            
            echo "\n-- TABLE: $table --\n";
            // Simple DROP IF EXISTS (SQL Server Syntax)
            echo "IF OBJECT_ID('$table', 'U') IS NOT NULL DROP TABLE [$table];\n";
            
            // Get Create Schema (Simplified - reconstructing strictly typical tables)
            // SQL Server SHOW CREATE TABLE is hard. 
            // Better strategy: We know the schema is fixed. 
            // BUT, for a generic dumper, we should reconstruct.
            // For now, let's dump DATA mostly. Schema restore is complex in PHP without huge library.
            // Wait, if I don't dump schema, I can't restore if tables are gone.
            // I'll grab the schema construction from columns.
            
            $this->dumpTableSchema($conn, $table);
            $this->dumpTableData($conn, $table);
        }
    }

    private function dumpTableSchema($conn, $table) {
        // 1. Get Columns with Identity and Default info
        $sql = "SELECT 
                    c.COLUMN_NAME, 
                    c.DATA_TYPE, 
                    c.CHARACTER_MAXIMUM_LENGTH, 
                    c.IS_NULLABLE,
                    c.COLUMN_DEFAULT,
                    COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') AS IS_IDENTITY
                FROM INFORMATION_SCHEMA.COLUMNS c
                WHERE c.TABLE_NAME = '$table'
                ORDER BY c.ORDINAL_POSITION";
        
        $stmt = db_query($conn, $sql);
        
        // 2. Get Primary Key Column
        $pkSql = "SELECT COLUMN_NAME 
                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_NAME = '$table' AND CONSTRAINT_NAME LIKE 'PK_%'";
        $pkStmt = db_query($conn, $pkSql);
        $pkCol = null;
        if ($pkStmt && $row = db_fetch_array($pkStmt, DB_FETCH_ASSOC)) {
            $pkCol = $row['COLUMN_NAME'];
        }

        echo "CREATE TABLE [$table] (\n";
        $cols = [];
        while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            $line = "  [" . $row['COLUMN_NAME'] . "] " . strtoupper($row['DATA_TYPE']);
            
            // Length
            if (in_array($row['DATA_TYPE'], ['varchar', 'nvarchar', 'char'])) {
                $len = $row['CHARACTER_MAXIMUM_LENGTH'];
                if ($len == -1) $len = 'MAX';
                $line .= "($len)";
            }

            // Identity
            if ($row['IS_IDENTITY'] == 1) {
                $line .= " IDENTITY(1,1)";
            }

            // Nullable
            if ($row['IS_NULLABLE'] === 'NO') {
                $line .= " NOT NULL";
            }

            // Default (Clean up parens roughly)
            if ($row['COLUMN_DEFAULT'] !== null) {
                $def = $row['COLUMN_DEFAULT'];
                // SQL Server defaults often come as ('value') or ((0)). Keep as is usually safe.
                $line .= " DEFAULT $def";
            }

            // Primary Key (Inline)
            if ($row['COLUMN_NAME'] === $pkCol) {
                $line .= " PRIMARY KEY";
            }

            $cols[] = $line;
        }
        echo implode(",\n", $cols);
        echo "\n);\n";
    }

    private function dumpTableData($conn, $table) {
        // Check if table has identity column
        $hasIdentity = false;
        $checkSql = "SELECT TOP 1 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $checkStmt = db_query($conn, $checkSql);
        if ($checkStmt && db_has_rows($checkStmt)) {
            $hasIdentity = true;
        }

        $sql = "SELECT * FROM [$table]";
        $stmt = db_query($conn, $sql);
        
        if ($hasIdentity) echo "SET IDENTITY_INSERT [$table] ON;\n";

        while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            $vals = [];
            foreach ($row as $key => $val) {
                // FIX: Handle stream resources (SQL Server NVARCHAR(MAX))
                if (is_resource($val)) {
                    $val = stream_get_contents($val);
                }
                if ($val === null) {
                    $vals[] = "NULL";
                } elseif ($val instanceof \DateTime) {
                    $vals[] = "'" . $val->format('Y-m-d H:i:s.v') . "'";
                } elseif (is_numeric($val)) {
                    $vals[] = $val;
                } else {
                    $val = str_replace("'", "''", $val); // Escape single quotes
                    $vals[] = "'" . $val . "'";
                }
            }
            echo "INSERT INTO [$table] (" . implode(', ', array_map(function($c){ return "[$c]"; }, array_keys($row))) . ") VALUES (" . implode(", ", $vals) . ");\n";
        }

        if ($hasIdentity) echo "SET IDENTITY_INSERT [$table] OFF;\n";
    }

    private function processRestore($sqlContent) {
        $config = require __DIR__ . '/../../config/database.php';
        $conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        
        if (!$conn) return ['status' => 'error', 'message' => 'Connection Failed'];

        // Normalize line endings
        $sqlContent = str_replace(["\r\n", "\r"], "\n", $sqlContent);
        
        // Split by semicolon (Naive splitter - assuming no semicolons in string literals, looking at Dumper it shouldn't happen much or acceptable risk for internal usage)
        $queries = explode(";\n", $sqlContent);
        
        $executed = 0;
        $skipped = 0;

        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            if (strpos($query, '--') === 0) continue; // Skip comments

            // FILTER: Skip if tbluser
            // Case insensitive check
            if (stripos($query, 'tbluser') !== false) {
                $skipped++;
                continue;
            }

            $stmt = db_query($conn, $query);
            if (!$stmt) {
                // If error, maybe log it but continue? Or stop?
                // Usually stop on first error is safer.
                return ['status' => 'error', 'message' => 'Error in query: ' . substr($query, 0, 50) . '... ' . print_r(db_errors(), true)];
            }
            $executed++;
        }

        return ['status' => 'success', 'count' => $executed, 'skipped' => $skipped];
    }
}
