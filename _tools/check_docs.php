<?php
$serverName = "LAPTOP-T9BEF7E1\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "EUC_CITRA",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Helper to execute and fetch
function fetchAll($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die("SQL Error: " . print_r(sqlsrv_errors(), true) . "\nSQL: $sql\nParams: " . json_encode($params));
    }
    $res = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $res[] = $row;
    }
    return $res;
}

// 1. Get the ID for -03
$reqs = fetchAll($conn, "SELECT id, script_number FROM script_request WHERE script_number LIKE '%-0037%'");
echo "Found Requests:\n";
print_r($reqs);

if (empty($reqs)) die("No requests found");

$id03 = null;
foreach($reqs as $r) {
    if (strpos($r['script_number'], '-03') !== false) {
        $id03 = $r['id'];
    }
}

echo "Testing RequestModel logic for ID: $id03\n";

// Emulate RequestModel logic
$sqlRequest = "SELECT script_number FROM script_request WHERE id = ?";
$reqRow = fetchAll($conn, $sqlRequest, [$id03])[0] ?? null;

if (!$reqRow) die("Request ID not found in query");

$scriptNumber = $reqRow['script_number'];
$baseExact = $scriptNumber;
        
if (preg_match('/-(\d{2})$/', $scriptNumber)) {
    $baseExact = preg_replace('/-(\d{2})$/', '', $scriptNumber);
}
$basePattern = $baseExact . '-%';

echo "BaseExact: $baseExact\n";
echo "BasePattern: $basePattern\n";

$sql = "SELECT f.id, f.request_id, f.file_type, f.original_filename, r.script_number
        FROM script_files f
        INNER JOIN script_request r ON f.request_id = r.id
        WHERE (r.script_number = ? OR r.script_number LIKE ?)
          AND f.file_type IN ('LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP')
        ORDER BY f.id DESC";
          
$docs = fetchAll($conn, $sql, [$baseExact, $basePattern]);
echo "\nMatched Documents:\n";
print_r($docs);

// Let's also check ALL documents just in case the type is different
$sqlAll = "SELECT f.id, f.request_id, f.file_type, f.original_filename, r.script_number
        FROM script_files f
        INNER JOIN script_request r ON f.request_id = r.id
        WHERE (r.script_number = ? OR r.script_number LIKE ?)";
$allDocs = fetchAll($conn, $sqlAll, [$baseExact, $basePattern]);
echo "\nALL Documents (ignoring type filter):\n";
print_r($allDocs);
