<?php
require_once __DIR__ . '/app/helpers/DbAdapter.php';
require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');

$config = require __DIR__ . '/config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

$sql = "SELECT USERID, FULLNAME, DEPT FROM tbluser";
$stmt = db_query($conn, $sql);

echo "<h1>User List Debug</h1>";
echo "<table border='1'><tr><th>UserID</th><th>Fullname</th><th>Dept</th><th>Dept (Hex)</th></tr>";

while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $dept = $row['DEPT'];
    $hex = bin2hex($dept);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['USERID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['FULLNAME']) . "</td>";
    echo "<td>" . htmlspecialchars($dept) . "</td>";
    echo "<td>" . $hex . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
