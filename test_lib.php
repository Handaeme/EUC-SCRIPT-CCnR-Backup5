<?php
require 'vendor/autoload.php';
session_start();
require 'config/database.php';
require 'app/helpers/DateTimeHelper.php';

$conn = db_connect($host, ['Database' => $dbname, 'UID' => $user, 'PWD' => $pass]);

$showInactive = false;
$whereClauses = [];
$params = [];

if (!$showInactive) {
    $todayDate = \App\Helpers\DateTimeHelper::today();
    $whereClauses[] = "(l.is_active = 1 AND (l.start_date IS NULL OR CAST(l.start_date AS DATE) <= CAST(? AS DATE)))";
    $params[] = $todayDate;
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

$sql = "SELECT l.*, r.ticket_id, r.title, r.mode, r.jenis, r.produk, r.kategori, r.media as request_media, r.created_at as request_created_at 
        FROM script_library l
        JOIN script_request r ON l.request_id = r.id
        $whereSql
        AND r.is_deleted = 0 
        ORDER BY l.created_at DESC";

echo "SQL:\n$sql\n";
echo "Params:\n"; print_r($params);

$stmt = db_query($conn, $sql, $params);
if ($stmt === false) {
    echo "SQL ERROR: " . print_r(db_errors(), true) . "\n";
} else {
    $rows = [];
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    echo "Total rows: " . count($rows) . "\n";
}
