<?php
require_once 'app/init.php';

// Get the latest request
$db = new App\Core\Database;
$conn = $db->conn();

// 1. Get request and raw user IDs
$sql = "SELECT TOP 1 id, script_number, created_by, selected_spv, selected_pic 
        FROM script_request 
        ORDER BY id DESC";
$stmt = custom_db_query($conn, $sql); // Use custom wrapper if available
$req = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "<h1>Request Data</h1>";
echo "<pre>";
print_r($req);
echo "</pre>";

if ($req) {
    echo "<h2>User Lookup (Direct Query)</h2>";
    
    // Check MAKER
    $makerId = $req['created_by'];
    $sqlMaker = "SELECT USERID, FULLNAME FROM tbluser WHERE USERID = ?";
    $stmtMaker = sqlsrv_query($conn, $sqlMaker, [$makerId]);
    $maker = sqlsrv_fetch_array($stmtMaker, SQLSRV_FETCH_ASSOC);
    echo "<strong>Maker ($makerId):</strong> ";
    print_r($maker);
    echo "<br>";

    // Check SPV
    $spvId = $req['selected_spv'];
    $sqlSpv = "SELECT USERID, FULLNAME FROM tbluser WHERE USERID = ?";
    $stmtSpv = sqlsrv_query($conn, $sqlSpv, [$spvId]);
    $spv = sqlsrv_fetch_array($stmtSpv, SQLSRV_FETCH_ASSOC);
    echo "<strong>SPV ($spvId):</strong> ";
    print_r($spv);
    echo "<br>";

    // Check PIC
    $picId = $req['selected_pic'];
    if ($picId) {
        $sqlPic = "SELECT USERID, FULLNAME FROM tbluser WHERE USERID = ?";
        $stmtPic = sqlsrv_query($conn, $sqlPic, [$picId]);
        $pic = sqlsrv_fetch_array($stmtPic, SQLSRV_FETCH_ASSOC);
        echo "<strong>PIC ($picId):</strong> ";
        print_r($pic);
        echo "<br>";
    }
}

// Helper functions (simplified from init.php/Database.php)
function custom_db_query($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}
