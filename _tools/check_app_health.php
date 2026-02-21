<?php
// APPLICATION HEALTH CHECK (Uses New DbAdapter)
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';

echo "<h1>Application Health Check</h1>";
echo "<h3>Testing via DbAdapter (PDO Wrapper)...</h3>";

// 1. Test Connection
$config = require __DIR__ . '/../config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if ($conn) {
    echo "<div style='color:green; font-weight:bold; font-size:1.2em'>✅ DATABASE CONNECTION SUCCESS!</div>";
    
    // 2. Test Query
    $sql = "SELECT COUNT(*) as total FROM tbluser";
    $stmt = db_query($conn, $sql);
    
    if ($stmt) {
        $row = db_fetch_array($stmt, DB_FETCH_ASSOC);
        echo "<p>✅ Query Test: Success (Found " . $row['total'] . " users)</p>";
        echo "<hr>";
        echo "<h2 style='color:blue'>KESIMPULAN: APLIKASI UTAMA AMAN DIGUNAKAN! 🚀</h2>";
        echo "<p>Silakan tutup semua tab debug dan login lewat: <a href='/EUC-Script-CCnR-Migrasi/'>Halaman Utama</a></p>";
    } else {
        echo "<div style='color:red'>❌ Query Test Failed: " . print_r(db_errors(), true) . "</div>";
    }
} else {
    echo "<div style='color:red'>❌ Connection Failed: " . print_r(db_errors(), true) . "</div>";
}
?>
