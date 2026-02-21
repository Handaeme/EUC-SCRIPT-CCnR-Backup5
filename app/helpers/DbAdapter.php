<?php
/**
 * DbAdapter Helper
 * -------------------------------------------------------------------------
 * A robust database wrapper with DUAL DRIVER SUPPORT:
 * - Prefers PDO_SQLSRV (modern, portable, PHP 8+)
 * - Falls back to native SQLSRV (if PDO not available)
 * 
 * Automatically detects which driver is available and uses it.
 * All application code remains unchanged - transparent switching!
 */

// Define fetch constants (compatible with both drivers)
if (!defined('DB_FETCH_ASSOC')) {
    if (extension_loaded('pdo_sqlsrv')) {
        define('DB_FETCH_ASSOC', PDO::FETCH_ASSOC);
        define('DB_FETCH_NUMERIC', PDO::FETCH_NUM);
        define('DB_FETCH_BOTH', PDO::FETCH_BOTH);
    } elseif (extension_loaded('sqlsrv')) {
        define('DB_FETCH_ASSOC', SQLSRV_FETCH_ASSOC);
        define('DB_FETCH_NUMERIC', SQLSRV_FETCH_NUMERIC);
        define('DB_FETCH_BOTH', SQLSRV_FETCH_BOTH);
    } else {
        // Fallback: No driver available, use generic values
        define('DB_FETCH_ASSOC', 2);
        define('DB_FETCH_NUMERIC', 3);
        define('DB_FETCH_BOTH', 4);
    }
}

class DbAdapter {
    private static $lastErrors = null;
    private static $usePDO = null;
    
    /**
     * Detect which driver to use
     */
    private static function detectDriver() {
        if (self::$usePDO === null) {
            // Prefer PDO, fallback to native SQLSRV
            self::$usePDO = extension_loaded('pdo_sqlsrv');
        }
        return self::$usePDO;
    }

    /**
     * Connect to SQL Server
     * Returns: PDO object or native SQLSRV resource
     */
    public static function connect($serverName, $connectionInfo) {
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            try {
                $db = $connectionInfo['Database'] ?? '';
                $uid = $connectionInfo['UID'] ?? null;
                $pwd = $connectionInfo['PWD'] ?? null;
                
                $dsn = "sqlsrv:Server=$serverName;Database=$db";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                    PDO::ATTR_STRINGIFY_FETCHES => true 
                ];
                
                $pdo = new PDO($dsn, $uid, $pwd, $options);
                return $pdo;
            } catch (PDOException $e) {
                self::$lastErrors = [[
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'SQLSTATE' => '08001'
                ]];
                return false;
            }
        } else {
            // === NATIVE SQLSRV MODE ===
            $conn = @sqlsrv_connect($serverName, $connectionInfo);
            if ($conn === false) {
                self::$lastErrors = sqlsrv_errors();
                return false;
            }
            return $conn;
        }
    }

    /**
     * Execute query
     * Returns: PDOStatement or native SQLSRV statement resource
     */
    public static function query($conn, $sql, $params = []) {
        if (!$conn) return false;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            try {
                $stmt = $conn->prepare($sql);
                $params = is_array($params) ? array_values($params) : [];
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                self::$lastErrors = [[
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'SQLSTATE' => $e->getCode()
                ]];
                error_log("DbAdapter Query Error (PDO): " . $e->getMessage() . " | SQL: $sql");
                return false;
            }
        } else {
            // === NATIVE SQLSRV MODE ===
            $params = is_array($params) ? array_values($params) : [];
            $stmt = @sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                self::$lastErrors = sqlsrv_errors();
                error_log("DbAdapter Query Error (SQLSRV): " . print_r(sqlsrv_errors(), true) . " | SQL: $sql");
                return false;
            }
            return $stmt;
        }
    }

    /**
     * Fetch row from result
     */
    public static function fetch_array($stmt, $fetchType = DB_FETCH_BOTH) {
        if (!$stmt) return false;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            try {
                return $stmt->fetch($fetchType);
            } catch (PDOException $e) {
                // Handle Microsoft PDO driver's "no more rows" exception
                if (strpos($e->getMessage(), 'There are no more rows in the active result set') !== false) {
                    return false;
                }
                throw $e;
            }
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_fetch_array($stmt, $fetchType);
        }
    }
    
    /**
     * Check if statement has rows
     */
    public static function has_rows($stmt) {
        if (!$stmt) return false;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            try {
                return ($stmt->columnCount() > 0 && $stmt->rowCount() !== 0);
            } catch (Exception $e) {
                return false;
            }
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_has_rows($stmt);
        }
    }

    /**
     * Get number of affected rows
     */
    public static function rows_affected($stmt) {
        if (!$stmt) return -1;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            return $stmt->rowCount();
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_rows_affected($stmt);
        }
    }

    /**
     * Move to next result set
     */
    public static function next_result($stmt) {
        if (!$stmt) return false;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            return $stmt->nextRowset();
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_next_result($stmt);
        }
    }

    /**
     * Free statement resources
     */
    public static function free_stmt($stmt) {
        if (!$stmt) return true;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            $stmt = null;
            return true;
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_free_stmt($stmt);
        }
    }

    /**
     * Close connection
     */
    public static function close($conn) {
        if (!$conn) return true;
        
        $usePDO = self::detectDriver();
        
        if ($usePDO) {
            // === PDO MODE ===
            $conn = null;
            return true;
        } else {
            // === NATIVE SQLSRV MODE ===
            return sqlsrv_close($conn);
        }
    }

    /**
     * Get last errors
     */
    public static function errors() {
        return self::$lastErrors;
    }
    
    /**
     * Get current driver info (for debugging)
     */
    public static function getDriverInfo() {
        $usePDO = self::detectDriver();
        return [
            'using' => $usePDO ? 'PDO_SQLSRV' : 'Native SQLSRV',
            'pdo_available' => extension_loaded('pdo_sqlsrv'),
            'sqlsrv_available' => extension_loaded('sqlsrv')
        ];
    }
}

// ========================================
// Global procedural wrappers
// Drop-in replacement for sqlsrv_* functions
// ========================================

function db_connect($server, $info) { 
    return DbAdapter::connect($server, $info); 
}

function db_query($conn, $sql, $params=[]) { 
    return DbAdapter::query($conn, $sql, $params); 
}

function db_fetch_array($stmt, $type=DB_FETCH_BOTH) { 
    return DbAdapter::fetch_array($stmt, $type); 
}

function db_has_rows($stmt) { 
    return DbAdapter::has_rows($stmt); 
}

function db_rows_affected($stmt) { 
    return DbAdapter::rows_affected($stmt); 
}

function db_next_result($stmt) { 
    return DbAdapter::next_result($stmt); 
}

function db_free_stmt($stmt) { 
    return DbAdapter::free_stmt($stmt); 
}

function db_close($conn) { 
    return DbAdapter::close($conn); 
}

function db_errors() { 
    return DbAdapter::errors(); 
}

function db_driver_info() { 
    return DbAdapter::getDriverInfo(); 
}
?>
