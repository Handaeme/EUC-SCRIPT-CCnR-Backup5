<?php
require_once '../config/database.php';
$conn = db_connect($config['host'], ['Database'=>$config['dbname'], 'UID'=>$config['user'], 'PWD'=>$config['pass']]);

$sql = "SELECT USERID, FULLNAME, DEPT FROM tbluser WHERE DEPT = 'SPV'";
$stmt = db_query($conn, $sql);

echo "<pre>";
if ($stmt) {
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        print_r($row);
    }
} else {
    echo "Query failed: " . print_r(db_errors(), true);
}
echo "</pre>";
