<?php
namespace App\Models;

class TemplateModel {
    private $conn;

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
        } else {
            // Optimization: Try to use global variable first to avoid new connection overhead
            global $conn;
            if ($conn) {
                $this->conn = $conn;
            } else {
                // Fallback: Create new connection if absolutely necessary
                $configFile = __DIR__ . '/../../config/database.php';
                if (file_exists($configFile)) {
                    $config = require $configFile;
                    $this->conn = db_connect($config['host'], isset($config['options']) ? $config['options'] : []);
                }
            }
        }
        
        if (!$this->conn) {
           // Don't die with HTML, just set null and let Model handle (or throw Exception if stricter)
           // But legacy code relied on die. Let's output a JSON-friendly error if possible, 
           // but since we are in constructor, we can't easily control output format.
           // We will rely on Controller to catch this? 
           // No, constructor failure is hard to catch unless we throw Exception.
           // Let's throw Exception instead of die.
           throw new \Exception("TemplateModel Error: No database connection available.");
        }
    }

    public function getAll($startDate = null, $endDate = null, $filters = []) {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($startDate && $endDate) {
            $where .= " AND CAST(t.created_at AS DATE) >= ? AND CAST(t.created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Filter: Uploaded By
        if (!empty($filters['uploaded_by']) && is_array($filters['uploaded_by'])) {
            $placeholders = implode(',', array_fill(0, count($filters['uploaded_by']), '?'));
            $where .= " AND t.uploaded_by IN ($placeholders)";
            $params = array_merge($params, $filters['uploaded_by']);
        }
        
        // JOIN to get Group Name (Mapped from GROUP column)
        $sql = "SELECT t.*, u.[GROUP] as group_name 
                FROM script_templates t 
                OUTER APPLY (SELECT TOP 1 u2.[GROUP] FROM tbluser u2 WHERE u2.USERID = t.uploaded_by) u
                $where 
                ORDER BY t.created_at DESC";
        $stmt = db_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getDistinctTemplateValues($column) {
        $allowed = ['uploaded_by'];
        if (!in_array($column, $allowed)) return [];

        $sql = "SELECT DISTINCT $column FROM script_templates WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = db_query($this->conn, $sql);
        $values = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $values[] = $row[$column];
            }
        }
        return $values;
    }

    public function add($title, $filename, $filepath, $user, $description = null) {
        // [FIX] ATOMIC LOCK: Prevent race condition double-upload
        $lockFile = sys_get_temp_dir() . '/tmpl_lock_' . md5($user) . '.lock';
        $fp = fopen($lockFile, 'w+');
        
        // Wait for lock
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        try {
            // [FIX] DEDUP GUARD: Prevent duplicate inserts from double-submit or fallback logic
            $dedupSql = "SELECT TOP 1 id FROM script_templates WHERE title = ? AND filename = ? AND DATEDIFF(SECOND, created_at, GETDATE()) < 30";
            $dedupStmt = db_query($this->conn, $dedupSql, [$title, $filename]);
            if ($dedupStmt && ($dedupRow = db_fetch_array($dedupStmt, DB_FETCH_ASSOC))) {
                error_log("[DEDUP] Skipping duplicate template insert: title=$title filename=$filename (existing id={$dedupRow['id']})");
                return true; // Already exists, skip
            }

            // Try INSERT with Description
            $sql = "INSERT INTO script_templates (title, filename, filepath, uploaded_by, description) VALUES (?, ?, ?, ?, ?)";
            $params = [$title, $filename, $filepath, $user, $description];
            $stmt = db_query($this->conn, $sql, $params);
            
            if ($stmt === false) {
                // Check if failure is due to missing 'description' column
                $errors = db_errors();
                $isColumnMissing = false;
                
                if ($errors) {
                    foreach ($errors as $error) {
                        if ($error['SQLSTATE'] == '42S22' || strpos($error['message'], 'Invalid column name') !== false) {
                             $isColumnMissing = true;
                             break;
                        }
                    }
                }
                
                // [FIX] Only run fallback if it's truly a column-missing error
                if ($isColumnMissing) {
                    $sqlFallback = "INSERT INTO script_templates (title, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)";
                    $paramsFallback = [$title, $filename, $filepath, $user];
                    $stmtFallback = db_query($this->conn, $sqlFallback, $paramsFallback);
                    
                    if ($stmtFallback === false) {
                        return false; // Both failed
                    }
                    return db_rows_affected($stmtFallback);
                }
                return false; // Non-column error, don't retry
            }
            return db_rows_affected($stmt);

        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function delete($id) {
        $sql = "DELETE FROM script_templates WHERE id = ?";
        return db_query($this->conn, $sql, [$id]);
    }

    public function getById($id) {
        $sql = "SELECT * FROM script_templates WHERE id = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row;
        }
        return null;
    }
}
