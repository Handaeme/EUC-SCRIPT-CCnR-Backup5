<?php
namespace App\Models;

class UserModel {
    private $conn;

    public function __construct() {
        $configFile = __DIR__ . '/../../config/database.php';
        if (!file_exists($configFile)) {
            die("Database config not found at: " . $configFile);
        }
        $config = require $configFile;
        $this->conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        if (!$this->conn) {
            die(print_r(db_errors(), true));
        }
    }

    public function getAll() {
        $sql = "SELECT * FROM tbluser ORDER BY CREATED_DATE DESC";
        $stmt = db_query($this->conn, $sql);
        $users = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $row = array_change_key_case($row, CASE_UPPER); // Normalize column keys
                // Normalize for App Usage
                $row['group_name'] = $row['GROUP'] ?? '';
                $row['is_active'] = $row['AKTIF'] ?? 1;
                $users[] = $row;
            }
        }
        return $users;
    }

    public function getById($id) {
        $sql = "SELECT * FROM tbluser WHERE USERID = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
             $row = array_change_key_case($row, CASE_UPPER); // Normalize column keys
             // Normalize for App Usage
             $row['group_name'] = $row['GROUP'] ?? '';
             $row['is_active'] = $row['AKTIF'] ?? 1;
            return $row;
        }
        return null;
    }

    public function create($data) {
        // Check if exists
        if ($this->getById($data['userid'])) {
            return ['status' => 'error', 'message' => 'UserID already exists'];
        }

        // Adapted for Office Schema: [GROUP], AKTIF
        $sql = "INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], AKTIF, CREATED_DATE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
        
        $params = [
            $data['userid'],
            $data['fullname'],
            $data['password'], 
            $data['ldap'] ?? 0,
            $data['dept'],
            $data['job_function'] ?? '',
            $data['divisi'] ?? '',
            $data['group_name'] ?? '', 
            $data['is_active'] ?? 1 
        ];

        $stmt = db_query($this->conn, $sql, $params);
        if ($stmt) {
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => print_r(db_errors(), true)];
    }

    public function update($id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['fullname'])) { $fields[] = "FULLNAME = ?"; $params[] = $data['fullname']; }
        if (isset($data['password']) && !empty($data['password'])) { $fields[] = "PASSWORD = ?"; $params[] = $data['password']; }
        if (isset($data['ldap'])) { $fields[] = "LDAP = ?"; $params[] = $data['ldap']; }
        if (isset($data['dept'])) { 
            $fields[] = "DEPT = ?"; $params[] = $data['dept']; 
        }
        if (isset($data['job_function'])) {
            $fields[] = "JOB_FUNCTION = ?"; $params[] = $data['job_function']; 
        }
        if (isset($data['divisi'])) {
            $fields[] = "DIVISI = ?"; $params[] = $data['divisi']; 
        }
        // Adapted Columns
        if (isset($data['group_name'])) { $fields[] = "[GROUP] = ?"; $params[] = $data['group_name']; }
        if (isset($data['is_active'])) { $fields[] = "AKTIF = ?"; $params[] = $data['is_active']; }

        if (empty($fields)) {
            return ['status' => 'success', 'message' => 'No changes'];
        }

        $params[] = $id; // For WHERE clause
        $sql = "UPDATE tbluser SET " . implode(', ', $fields) . " WHERE USERID = ?";
        
        $stmt = db_query($this->conn, $sql, $params);
        if ($stmt) {
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => print_r(db_errors(), true)];
    }

    public function delete($id) {
        // Soft Delete (Adapted: AKTIF)
        $sql = "UPDATE tbluser SET AKTIF = 0 WHERE USERID = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        if ($stmt) {
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => print_r(db_errors(), true)];
    }
}
