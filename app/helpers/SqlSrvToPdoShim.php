<?php
/**
 * SQLSRV to PDO Shim Layer
 * -------------------------------------------------------------------------
 * This file provides a compatibility layer for applications using native
 * sqlsrv_* functions to run on top of PDO_SQLSRV driver.
 * 
 * It is automatically loaded if sqlsrv_connect function is missing but
 * PDO is available.
 */

if (!defined('SQLSRV_FETCH_ASSOC')) define('SQLSRV_FETCH_ASSOC', PDO::FETCH_ASSOC);
if (!defined('SQLSRV_FETCH_NUMERIC')) define('SQLSRV_FETCH_NUMERIC', PDO::FETCH_NUM);
if (!defined('SQLSRV_FETCH_BOTH')) define('SQLSRV_FETCH_BOTH', PDO::FETCH_BOTH);
if (!defined('SQLSRV_ERR_ALL')) define('SQLSRV_ERR_ALL', 2);

// Global variable to store last error
global $TEST_PDO_ERRORS;
$TEST_PDO_ERRORS = null;

if (!function_exists('sqlsrv_connect')) {
    
    function sqlsrv_connect($serverName, $connectionInfo) {
        try {
            $db = $connectionInfo['Database'] ?? '';
            $uid = $connectionInfo['UID'] ?? null;
            $pwd = $connectionInfo['PWD'] ?? null;
            
            $dsn = "sqlsrv:Server=$serverName;Database=$db";
            
            // Connection options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                PDO::ATTR_STRINGIFY_FETCHES => true // Simulate sqlsrv behavior
            ];
            
            $pdo = new PDO($dsn, $uid, $pwd, $options);
            return $pdo;
        } catch (PDOException $e) {
            global $TEST_PDO_ERRORS;
            $TEST_PDO_ERRORS = [
                [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'SQLSTATE' => '08001'
                ]
            ];
            return false;
        }
    }

    function sqlsrv_query($conn, $sql, $params = [], $options = []) {
        global $TEST_PDO_ERRORS;
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare($sql);
            
            // Bind parameters (handling nulls strictly if needed, but PDO usually handles it)
            if (!empty($params)) {
               // Re-index params to 1-based for bindValue? No, execute takes 0-based array ok.
               // Just ensure it's an array of values
               $params = array_values($params); 
            }
            
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $TEST_PDO_ERRORS = [
                [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'SQLSTATE' => $e->getCode()
                ]
            ];
            return false;
        }
    }

    function sqlsrv_fetch_array($stmt, $fetchType = SQLSRV_FETCH_BOTH) {
        if (!$stmt) return false;
        return $stmt->fetch($fetchType);
    }
    
    function sqlsrv_has_rows($stmt) {
        if (!$stmt) return false;
        // PDO doesn't have a direct 'has_rows' without fetching or query parsing.
        // However, rowCount() in SQL Server PDO usually works for SELECTs (Cursor Dependent)
        // Safer fallback: try to fetch? No, that consumes the row.
        // Best effort:
        return ($stmt->rowCount() > 0); 
    }

    function sqlsrv_rows_affected($stmt) {
        if (!$stmt) return -1;
        return $stmt->rowCount();
    }

    function sqlsrv_errors($flag = SQLSRV_ERR_ALL) {
        global $TEST_PDO_ERRORS;
        return $TEST_PDO_ERRORS;
    }

    function sqlsrv_next_result($stmt) {
        if (!$stmt) return false;
        return $stmt->nextRowset();
    }

    function sqlsrv_close($conn) {
        $conn = null;
        return true;
    }
    
    function sqlsrv_free_stmt($stmt) {
        $stmt = null;
        return true;
    }

    function sqlsrv_begin_transaction($conn) {
        return $conn->beginTransaction();
    }

    function sqlsrv_commit($conn) {
        return $conn->commit();
    }

    function sqlsrv_rollback($conn) {
        return $conn->rollBack();
    }
}
?>
