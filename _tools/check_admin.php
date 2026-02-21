<?php
// _tools/check_admin.php
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
require_once __DIR__ . '/../app/helpers/DbAdapter.php';

// Load Env
// Load Env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    // Mimic index.php exactly
    App\Helpers\EnvLoader::load($envPath);
}

// Connect
$serverName = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'EUC_KS_MIGRASI';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? '';
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

$conn = db_connect($serverName, ['Database' => $dbName, 'UID' => $user, 'PWD' => $pass]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

echo "<h2>Checking for Admin Users...</h2>";

// Check for users with role 'ADMIN' or username 'admin'
$sql = "SELECT * FROM tbluser WHERE [GROUP] = 'ADMIN' OR USERID = 'admin'";
$stmt = db_query($conn, $sql);

if ($stmt && db_has_rows($stmt)) {
    echo "<table border='1' cellspacing='0' cellpadding='5'>";
    echo "<tr><th>USERID</th><th>NAME</th><th>ROLE (GROUP)</th><th>PASSWORD (HASH)</th></tr>";
    while ($row = db_fetch_array($stmt, PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['USERID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['FULLNAME'] ?? $row['NAME'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['GROUP']) . "</td>";
        echo "<td>" . substr($row['PASSWORD'], 0, 10) . "...</td>"; 
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h3 style='color:red;'>No Admin User Found!</h3>";
    echo "<p>No user with ROLE='ADMIN' or USERID='admin' exists in the database.</p>";
}
echo "<br><hr><br>";

// Also list all roles present
$sqlRoles = "SELECT DISTINCT [GROUP] FROM tbluser";
$stmtRoles = db_query($conn, $sqlRoles);
echo "<h3>Existing Roles in Database:</h3>";
echo "<ul>";
while ($row = db_fetch_array($stmtRoles, PDO::FETCH_ASSOC)) {
    echo "<li>" . htmlspecialchars($row['GROUP']) . "</li>";
}
echo "</ul>";
?>
