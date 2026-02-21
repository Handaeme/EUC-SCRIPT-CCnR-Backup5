<?php
/**
 * DEPLOYMENT DIAGNOSTIC TOOL
 * Run this FIRST when deploying to a new machine
 */

echo "<h1>Deployment Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #4CAF50; color: white; }
</style>";

// 1. Check PHP Extensions
echo "<h2>1. PHP Extensions Check</h2>";
$required = ['sqlsrv', 'pdo_sqlsrv'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext is loaded</div>";
    } else {
        echo "<div class='error'>❌ $ext is NOT loaded</div>";
    }
}

// 2. Check .env file
echo "<h2>2. .env File Check</h2>";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    echo "<div class='success'>✅ .env file exists</div>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents($envPath));
    echo "</pre>";
    
    // Load it
    require_once __DIR__ . '/../app/helpers/EnvLoader.php';
    App\Helpers\EnvLoader::load($envPath);
    
    echo "<h3>Environment Variables Loaded:</h3>";
    echo "<table>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    $env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($env_vars as $var) {
        $val = getenv($var);
        echo "<tr><td>$var</td><td>" . ($val !== false ? htmlspecialchars($val) : '<span class="error">NOT SET</span>') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ .env file NOT FOUND at: $envPath</div>";
}

// 3. Check config/database.php
echo "<h2>3. Database Config Check</h2>";
$configPath = __DIR__ . '/../config/database.php';
if (file_exists($configPath)) {
    echo "<div class='success'>✅ config/database.php exists</div>";
    $config = require $configPath;
    
    echo "<table>";
    echo "<tr><th>Config Key</th><th>Value</th></tr>";
    echo "<tr><td>host</td><td>" . htmlspecialchars($config['host'] ?? 'NOT SET') . "</td></tr>";
    echo "<tr><td>dbname</td><td>" . htmlspecialchars($config['dbname'] ?? 'NOT SET') . "</td></tr>";
    echo "<tr><td>username</td><td>" . htmlspecialchars($config['username'] ?? 'NOT SET') . "</td></tr>";
    echo "<tr><td>password</td><td>" . (isset($config['password']) ? '***' : 'NOT SET') . "</td></tr>";
    echo "</table>";
} else {
    echo "<div class='error'>❌ config/database.php NOT FOUND</div>";
    die();
}

// 4. Detect SQL Server Instances
echo "<h2>4. SQL Server Instance Detection</h2>";
echo "<p>Trying to detect SQL Server instances on this machine...</p>";

$possibleServers = [
    '(local)',
    'localhost',
    '.\SQLEXPRESS',
    '(local)\SQLEXPRESS',
    gethostname() . '\SQLEXPRESS',
    gethostname(),
];

echo "<table>";
echo "<tr><th>Server Name</th><th>Status</th></tr>";

foreach ($possibleServers as $server) {
    $testConn = @sqlsrv_connect($server, [
        'Database' => 'master',
        'ReturnDatesAsStrings' => true
    ]);
    
    if ($testConn !== false) {
        echo "<tr><td><strong>$server</strong></td><td class='success'>✅ CONNECTED</td></tr>";
        sqlsrv_close($testConn);
    } else {
        echo "<tr><td>$server</td><td class='error'>❌ Failed</td></tr>";
    }
}
echo "</table>";

// 5. Test Connection with Config
echo "<h2>5. Test Connection with Current Config</h2>";

if (isset($config)) {
    echo "<p>Attempting connection with:</p>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . htmlspecialchars($config['host']) . "</li>";
    echo "<li><strong>Database:</strong> " . htmlspecialchars($config['dbname']) . "</li>";
    echo "</ul>";
    
    $conn = sqlsrv_connect($config['host'], $config['options']);
    
    if ($conn === false) {
        echo "<div class='error'><h3>❌ CONNECTION FAILED</h3></div>";
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
        
        // Try without database
        echo "<h3>Trying connection to 'master' database instead...</h3>";
        $masterOptions = $config['options'];
        $masterOptions['Database'] = 'master';
        $masterConn = sqlsrv_connect($config['host'], $masterOptions);
        
        if ($masterConn !== false) {
            echo "<div class='success'>✅ Connected to master database!</div>";
            echo "<p class='warning'>⚠️ This means SQL Server is running, but database '" . htmlspecialchars($config['dbname']) . "' doesn't exist!</p>";
            
            // List databases
            $sql = "SELECT name FROM sys.databases ORDER BY name";
            $stmt = sqlsrv_query($masterConn, $sql);
            echo "<h4>Available Databases:</h4><ul>";
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<li>" . htmlspecialchars($row['name']) . "</li>";
            }
            echo "</ul>";
            
            sqlsrv_close($masterConn);
        } else {
            echo "<div class='error'>❌ Cannot connect to master either. SQL Server might not be running or server name is wrong.</div>";
        }
    } else {
        echo "<div class='success'><h3>✅ CONNECTION SUCCESS!</h3></div>";
        echo "<p>Database connection is working correctly!</p>";
        sqlsrv_close($conn);
    }
}

// 6. Recommendations
echo "<h2>6. Recommendations</h2>";
echo "<div style='background: #ffffcc; padding: 15px; border-left: 4px solid #ffeb3b;'>";
echo "<p><strong>If connection failed:</strong></p>";
echo "<ol>";
echo "<li><strong>Copy correct server name</strong> from 'SQL Server Instance Detection' section above (the one with ✅)</li>";
echo "<li><strong>Edit .env file</strong> and update DB_HOST with that server name</li>";
echo "<li><strong>Create database</strong> if it doesn't exist (run setup.php or migration_full.sql)</li>";
echo "<li><strong>Refresh this page</strong> to verify</li>";
echo "</ol>";
echo "</div>";
?>
