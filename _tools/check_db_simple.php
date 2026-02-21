<?php
$serverName = "LAPTOP-T9BEF7E1\SQLEXPRESS";
$connectionOptions = [
    "Database" => "CITRA",
    "UID" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if( $conn ) {
     echo "Connection established.\n";
}else{
     echo "Connection could not be established.\n";
     die( print_r( sqlsrv_errors(), true));
}

// 1. Check Column
$sqlCol = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'has_draft'";
$stmtCol = sqlsrv_query($conn, $sqlCol);
$hasCol = sqlsrv_fetch_array($stmtCol);

if ($hasCol) {
    echo "Column 'has_draft' exists (Type: " . $hasCol['DATA_TYPE'] . ").\n";
} else {
    echo "Column 'has_draft' DOES NOT EXIST.\n";
}

// 2. Check Data
$sqlData = "SELECT id, ticket_id, has_draft FROM script_request WHERE ticket_id = 'SC-0010'";
$stmtData = sqlsrv_query($conn, $sqlData);
$row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC);

if ($row) {
    echo "Record SC-0010 Found.\n";
    echo "has_draft (Raw): ";
    var_dump($row['has_draft']);
} else {
    echo "Record SC-0010 NOT Found.\n";
}
