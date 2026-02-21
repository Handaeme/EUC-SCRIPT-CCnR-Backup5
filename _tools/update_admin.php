<?php
// _tools/update_admin.php
use App\Helpers\EnvLoader;

require_once __DIR__ . '/../app/helpers/DbAdapter.php';
require_once __DIR__ . '/../app/helpers/EnvLoader.php';

// Load Env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    EnvLoader::load($envPath);
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

$targetUser = 'admin_script';
$targetName = 'EUC Script Admin';
$targetPass = '123'; // User Requested Simple Password
$targetRole = 'ADMIN';

echo "<h2>Setup User: '$targetUser'</h2>";
echo "<p>Password will be set to: <strong>$targetPass</strong></p>";
echo "<hr>";

// 1. Check if ANY 'admin' user exists (just for info)
$sqlCheckOld = "SELECT * FROM tbluser WHERE USERID = 'admin'";
$stmtOld = db_query($conn, $sqlCheckOld);
if ($stmtOld && db_has_rows($stmtOld)) {
    echo "<p style='color:blue'>ℹ️ Existing user 'admin' found. <strong>It will NOT be modified.</strong></p>";
}

// 2. Check if main target 'admin_script' exists
$sqlCheck = "SELECT * FROM tbluser WHERE USERID = ?";
$stmt = db_query($conn, $sqlCheck, [$targetUser]);

if ($stmt && db_has_rows($stmt)) {
    // UPDATE EXISTING
    echo "<p>User '$targetUser' already exists. Updating credentials...</p>";
    $sqlUpdate = "UPDATE tbluser SET PASSWORD = ?, FULLNAME = ?, [GROUP] = 'ADMIN', AKTIF = 1, LDAP = 0 WHERE USERID = ?";
    if (db_query($conn, $sqlUpdate, [$targetPass, $targetName, $targetUser])) {
         echo "<h3 style='color:green'>Success! User '$targetUser' Updated.</h3>";
         echo "<ul><li>Password: $targetPass</li><li>Role: ADMIN</li></ul>";
    } else {
         echo "<h3 style='color:red'>Failed to update '$targetUser'.</h3>";
         print_r(db_errors());
    }

} else {
    // CREATE NEW
    echo "<p>User '$targetUser' does not exist. Creating new...</p>";
    // Using brackets [] for GROUP because it's a reserved keyword in some SQL dialects, good practice for MSSQL
    $sqlCreate = "INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, [GROUP], AKTIF, CREATED_DATE) VALUES (?, ?, ?, 0, 'ADMIN', 'ADMIN', 'ADMIN', 1, GETDATE())";
     if (db_query($conn, $sqlCreate, [$targetUser, $targetName, $targetPass])) {
         echo "<h3 style='color:green'>Success! Created user '$targetUser'.</h3>";
         echo "<ul><li>Password: $targetPass</li><li>Role: ADMIN</li></ul>";
    } else {
         echo "<h3 style='color:red'>Failed to create '$targetUser'.</h3>";
         print_r(db_errors());
    }
}
?>
