<?php
// Test Connection using Multiple Drivers
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');

$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<h1>Driver Compatibility Test</h1>";
echo "<p>Testing connection to: <strong>$host</strong> | DB: <strong>$db</strong> | User: <strong>" . ($user ? $user : 'Windows Auth') . "</strong></p>";

// ---------------------------------------------------------
// TEST 1: Native SQLSRV (Yang kita pakai sekarang)
// ---------------------------------------------------------
echo "<h2>1. Testing Native SQLSRV (Current App Method)</h2>";
if (!function_exists('sqlsrv_connect')) {
    echo "<div style='color:red'>❌ Extension `sqlsrv` NOT Loaded</div>";
} else {
    $options = [
        "Database" => $db,
        "ReturnDatesAsStrings" => true
    ];
    if ($user) {
        $options["UID"] = $user;
        $options["PWD"] = $pass;
    }
    
    $conn = sqlsrv_connect($host, $options);
    
    if ($conn) {
        echo "<div style='color:green'>✅ Connection SUCCESS</div>";
        sqlsrv_close($conn);
    } else {
        echo "<div style='color:red'>❌ Connection FAILED</div>";
        echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    }
}

// ---------------------------------------------------------
// TEST 2: PDO (Alternative)
// ---------------------------------------------------------
echo "<h2>2. Testing PDO_SQLSRV</h2>";
if (!in_array('sqlsrv', PDO::getAvailableDrivers())) {
    echo "<div style='color:red'>❌ Extension `pdo_sqlsrv` NOT Loaded</div>";
} else {
    try {
        $dsn = "sqlsrv:Server=$host;Database=$db";
        // PDO options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "<div style='color:green'>✅ Connection SUCCESS</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red'>❌ Connection FAILED</div>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
}

// ---------------------------------------------------------
// TEST 3: ODBC (Old School Fallback)
// ---------------------------------------------------------
echo "<h2>3. Testing ODBC (Fallback)</h2>";
if (!function_exists('odbc_connect')) {
    echo "<div style='color:orange'>⚠️ ODBC Extension not available</div>";
} else {
    // Driver 17 is standard for modern SQL Server
    $connection_string = "Driver={ODBC Driver 17 for SQL Server};Server=$host;Database=$db;";
    
    if ($user) {
         // ODBC uses user/pass differently in connect function
    } else {
         $connection_string .= "Trusted_Connection=yes;";
    }

    // Suppress warning to catch error gracefully
    $conn = @odbc_connect($connection_string, $user, $pass);
    
    if ($conn) {
        echo "<div style='color:green'>✅ Connection SUCCESS</div>";
        odbc_close($conn);
    } else {
        echo "<div style='color:red'>❌ Connection FAILED</div>";
        echo "<pre>" . odbc_errormsg() . "</pre>";
    }
}
?>
