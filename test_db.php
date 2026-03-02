<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$conn = App\Core\Database::getConnection();
$stmt = sqlsrv_query($conn, 'SELECT TOP 1 uploaded_at FROM script_files');
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
var_dump($row);
