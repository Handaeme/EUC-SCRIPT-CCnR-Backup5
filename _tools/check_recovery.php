<?php
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
$envLoader = new App\Helpers\EnvLoader(__DIR__ . '/../.env');
$envLoader->load();

$conn = sqlsrv_connect(getenv('DB_HOST'), [
    "Database" => getenv('DB_NAME'),
    "UID" => getenv('DB_USER'),
    "PWD" => getenv('DB_PASS')
]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$requestId = 3;
$sql = "SELECT COUNT(*) as total FROM script_library WHERE request_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "Total Rows in Library for ID $requestId: " . $row['total'] . "\n";
?>
