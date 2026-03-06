<?php
/**
 * ============================================================
 * SERVER COMPATIBILITY CHECKER
 * ============================================================
 * 
 * Run this script on the TARGET server to verify that
 * the EUC Script CCnR application will work correctly.
 * 
 * USAGE (via browser):
 *   http://your-server/EUC-Script-CCnR-Migrasi/tools/server_check.php
 * 
 * USAGE (via CLI):
 *   php tools/server_check.php
 * ============================================================
 */

date_default_timezone_set('Asia/Jakarta');
$ROOT = dirname(__DIR__);
$isCLI = (php_sapi_name() === 'cli');

// ── OUTPUT HELPERS ──
function h1($text) {
    global $isCLI;
    if ($isCLI) {
        echo "\n╔══════════════════════════════════════════════════════╗\n";
        echo "║  $text" . str_repeat(' ', max(0, 53 - strlen($text))) . "║\n";
        echo "╚══════════════════════════════════════════════════════╝\n\n";
    } else {
        echo "<h1 style='color:#d32f2f;font-family:Inter,sans-serif;'>$text</h1>";
    }
}

function h2($text) {
    global $isCLI;
    if ($isCLI) {
        echo "\n── $text ──\n";
    } else {
        echo "<h2 style='color:#334155;font-family:Inter,sans-serif;margin-top:24px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;'>$text</h2>";
    }
}

function result($label, $status, $detail = '') {
    global $isCLI, $allResults;
    $icon = $status === 'OK' ? '✅' : ($status === 'WARN' ? '⚠️' : '❌');
    $allResults[] = ['label' => $label, 'status' => $status, 'detail' => $detail];
    
    if ($isCLI) {
        printf("  %s %-35s %s\n", $icon, $label, $detail);
    } else {
        $bg = $status === 'OK' ? '#dcfce7' : ($status === 'WARN' ? '#fef9c3' : '#fee2e2');
        $color = $status === 'OK' ? '#166534' : ($status === 'WARN' ? '#854d0e' : '#991b1b');
        echo "<div style='display:flex;align-items:center;gap:12px;padding:10px 16px;margin:4px 0;border-radius:8px;background:$bg;font-family:Inter,sans-serif;'>";
        echo "<span style='font-size:18px;'>$icon</span>";
        echo "<span style='font-weight:600;color:$color;min-width:250px;'>$label</span>";
        echo "<span style='color:#64748b;font-size:13px;'>$detail</span>";
        echo "</div>";
    }
}

$allResults = [];

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>Server Check - EUC Script</title>
    <style>body{max-width:800px;margin:40px auto;padding:0 20px;background:#f8fafc;font-family:Inter,sans-serif;}</style>
    </head><body>";
}

h1("EUC Script CCnR — Server Check");
echo $isCLI ? "Checking server compatibility...\n" : "<p style='color:#64748b;'>Checking server compatibility for deployment...</p>";

// ═══════════════════════════════════════════
// 1. PHP VERSION
// ═══════════════════════════════════════════
h2("1. PHP Environment");

$phpVer = phpversion();
$phpOk = version_compare($phpVer, '7.4.0', '>=');
result('PHP Version', $phpOk ? 'OK' : 'FAIL', "v$phpVer (minimum: 7.4+)");

// Arrow functions (fn() =>) require PHP 7.4
result('Arrow Functions (fn())', $phpOk ? 'OK' : 'FAIL', $phpOk ? 'Supported' : 'Requires PHP 7.4+');

// Check max execution time
$maxExec = ini_get('max_execution_time');
result('Max Execution Time', ($maxExec >= 120 || $maxExec == 0) ? 'OK' : 'WARN', "{$maxExec}s (recommended: 120+, app sets 300 at runtime)");

// ═══════════════════════════════════════════
// 2. PHP EXTENSIONS
// ═══════════════════════════════════════════
h2("2. Required PHP Extensions");

// SQL Server drivers
$hasPDO = extension_loaded('pdo_sqlsrv');
$hasNative = extension_loaded('sqlsrv');
$hasAnySQL = $hasPDO || $hasNative;

result('pdo_sqlsrv', $hasPDO ? 'OK' : 'WARN', $hasPDO ? 'Loaded (preferred driver)' : 'Not loaded');
result('sqlsrv (native)', $hasNative ? 'OK' : 'WARN', $hasNative ? 'Loaded (fallback driver)' : 'Not loaded');
result('SQL Server Driver (any)', $hasAnySQL ? 'OK' : 'FAIL', $hasAnySQL ? ($hasPDO ? 'Using PDO_SQLSRV' : 'Using native SQLSRV') : 'CRITICAL: No SQL Server driver found!');

// Other useful extensions
$extensions = [
    'mbstring'  => 'String handling (UTF-8)',
    'json'      => 'JSON encode/decode',
    'fileinfo'  => 'File MIME type detection',
    'ldap'      => 'LDAP Authentication (optional, for SSO)',
    'openssl'   => 'SSL/TLS support',
    'gd'        => 'Image processing (optional)',
];

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $required = in_array($ext, ['mbstring', 'json', 'fileinfo']);
    result($ext, $loaded ? 'OK' : ($required ? 'FAIL' : 'WARN'), $loaded ? "Loaded — $desc" : "Not loaded — $desc");
}

// ═══════════════════════════════════════════
// 3. DATABASE CONNECTION
// ═══════════════════════════════════════════
h2("3. Database Connection");

$envFile = $ROOT . '/.env';
$hasEnv = file_exists($envFile);
result('.env File', $hasEnv ? 'OK' : 'FAIL', $hasEnv ? 'Found at project root' : 'Missing! Create .env with DB_HOST, DB_NAME, DB_USER, DB_PASS');

if ($hasEnv && $hasAnySQL) {
    require_once $ROOT . '/app/helpers/EnvLoader.php';
    App\Helpers\EnvLoader::load($envFile);
    require_once $ROOT . '/app/helpers/DbAdapter.php';
    
    $config = require $ROOT . '/config/database.php';
    $connectionInfo = [
        'Database' => $config['dbname'],
        'UID'      => $config['user'],
        'PWD'      => $config['pass'],
    ];
    if (isset($config['options'])) {
        $connectionInfo = array_merge($connectionInfo, $config['options']);
    }
    
    $testConn = db_connect($config['host'], $connectionInfo);
    result('DB Connection', $testConn ? 'OK' : 'FAIL', $testConn 
        ? "Connected to {$config['host']} / {$config['dbname']}" 
        : 'Connection failed: ' . json_encode(db_errors()));
    
    if ($testConn) {
        // Check required tables
        $tables = ['script_request', 'script_preview_content', 'script_files', 'script_audit_trail', 'script_library', 'tbluser'];
        foreach ($tables as $table) {
            $tStmt = db_query($testConn, "SELECT TOP 1 * FROM $table");
            result("Table: $table", $tStmt !== false ? 'OK' : 'FAIL', $tStmt !== false ? 'Exists & accessible' : 'Not found or access denied');
        }
        
        // Driver info
        $driverInfo = db_driver_info();
        result('DB Driver', 'OK', "Using: {$driverInfo['using']}");
        
        db_close($testConn);
    }
} else {
    if (!$hasEnv) result('DB Connection', 'FAIL', 'Cannot test — .env file missing');
    if (!$hasAnySQL) result('DB Connection', 'FAIL', 'Cannot test — no SQL Server driver available');
}

// ═══════════════════════════════════════════
// 4. FILE SYSTEM & PERMISSIONS
// ═══════════════════════════════════════════
h2("4. File System & Permissions");

$dirs = [
    'storage/uploads'       => 'Script files & review documents',
    'uploads/templates'     => 'Template files',
    'logs'                  => 'Application logs',
];

foreach ($dirs as $rel => $desc) {
    $path = $ROOT . '/' . $rel;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    if (!$exists) {
        // Try to create
        $created = @mkdir($path, 0777, true);
        $exists = $created;
        $writable = $created;
    }
    
    result("$rel/", 
        ($exists && $writable) ? 'OK' : ($exists ? 'WARN' : 'FAIL'),
        ($exists && $writable) ? "Exists & writable — $desc" : ($exists ? "Exists but NOT writable!" : "Does not exist & cannot create")
    );
}

// Check disk space
$freeSpace = @disk_free_space($ROOT);
if ($freeSpace !== false) {
    $freeGB = round($freeSpace / 1024 / 1024 / 1024, 2);
    result('Free Disk Space', $freeGB > 1 ? 'OK' : 'WARN', "{$freeGB} GB available");
}

// ═══════════════════════════════════════════
// 5. UPLOAD LIMITS
// ═══════════════════════════════════════════
h2("5. Upload Configuration");

$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');

$uploadBytes = return_bytes($uploadMax);
$postBytes = return_bytes($postMax);

result('upload_max_filesize', $uploadBytes >= 10 * 1024 * 1024 ? 'OK' : 'WARN', "$uploadMax (recommended: 20M+)");
result('post_max_size', $postBytes >= 10 * 1024 * 1024 ? 'OK' : 'WARN', "$postMax (recommended: 20M+, must be >= upload_max_filesize)");
result('file_uploads', ini_get('file_uploads') ? 'OK' : 'FAIL', ini_get('file_uploads') ? 'Enabled' : 'Disabled!');

// ═══════════════════════════════════════════
// 6. FOLDER STRUCTURE CHECK
// ═══════════════════════════════════════════
h2("6. SC-XXXX Folder Structure (Flat)");

$uploadsDir = $ROOT . '/storage/uploads';
if (is_dir($uploadsDir)) {
    $scFolders = [];
    $emptyFolders = [];
    $totalDocs = 0;
    $strayFiles = 0;
    
    foreach (scandir($uploadsDir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $uploadsDir . '/' . $item;
        
        if (is_dir($fullPath) && preg_match('/^SC-\d+$/', $item)) {
            $files = array_diff(scandir($fullPath), ['.', '..']);
            $count = count($files);
            $scFolders[$item] = $count;
            $totalDocs += $count;
            if ($count === 0) $emptyFolders[] = $item;
        } elseif ($item === 'review_docs' && is_dir($fullPath)) {
            $oldFiles = array_diff(scandir($fullPath), ['.', '..']);
            $strayFiles = count($oldFiles);
        }
    }
    
    result('SC-XXXX Folders', count($scFolders) > 0 ? 'OK' : 'WARN', count($scFolders) . ' folders found, ' . $totalDocs . ' total documents');
    result('Empty SC Folders', empty($emptyFolders) ? 'OK' : 'WARN', empty($emptyFolders) ? 'None' : implode(', ', $emptyFolders));
    result('Stray Files (review_docs/)', $strayFiles === 0 ? 'OK' : 'WARN', $strayFiles === 0 ? 'Clean — no files in old location' : "$strayFiles files still in review_docs/ — run migrate_review_docs.php");
    
    // Show folder details
    if (!$isCLI && !empty($scFolders)) {
        echo "<details style='margin:10px 0;'><summary style='cursor:pointer;font-weight:600;color:#334155;'>📁 Folder Details (" . count($scFolders) . " folders)</summary>";
        echo "<table style='width:100%;border-collapse:collapse;margin:8px 0;font-size:13px;'>";
        echo "<tr style='background:#f1f5f9;'><th style='padding:6px 12px;text-align:left;'>Folder</th><th style='padding:6px 12px;text-align:right;'>Files</th></tr>";
        foreach ($scFolders as $folder => $count) {
            $bg = $count === 0 ? '#fef9c3' : '';
            echo "<tr style='background:$bg;border-bottom:1px solid #e2e8f0;'><td style='padding:6px 12px;'>$folder</td><td style='padding:6px 12px;text-align:right;'>$count</td></tr>";
        }
        echo "</table></details>";
    }
} else {
    result('storage/uploads/', 'FAIL', 'Directory does not exist');
}

// ═══════════════════════════════════════════
// 7. APP FILES INTEGRITY
// ═══════════════════════════════════════════
h2("7. Core Application Files");

$coreFiles = [
    'index.php'                          => 'Main entry point',
    'config/database.php'                => 'Database configuration',
    'app/helpers/DbAdapter.php'          => 'Database abstraction layer',
    'app/helpers/EnvLoader.php'          => 'Environment variable loader',
    'app/models/RequestModel.php'        => 'Request data model',
    'app/controllers/RequestController.php' => 'Request controller',
    'app/controllers/DashboardController.php' => 'Dashboard controller',
    'app/views/layouts/header.php'       => 'Layout header',
    'app/views/layouts/sidebar.php'      => 'Layout sidebar',
    'public/js/chart.umd.min.js'         => 'Chart.js library',
];

foreach ($coreFiles as $file => $desc) {
    $path = $ROOT . '/' . $file;
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    result(basename($file), $exists ? 'OK' : 'FAIL', $exists ? "Found (" . round($size / 1024, 1) . " KB) — $desc" : "MISSING — $desc");
}

// ═══════════════════════════════════════════
// FINAL SUMMARY
// ═══════════════════════════════════════════
h2("Summary");

$okCount = count(array_filter($allResults, fn($r) => $r['status'] === 'OK'));
$warnCount = count(array_filter($allResults, fn($r) => $r['status'] === 'WARN'));
$failCount = count(array_filter($allResults, fn($r) => $r['status'] === 'FAIL'));
$total = count($allResults);

if ($isCLI) {
    echo "\n";
    echo "┌──────────────────────────────────────────────────────┐\n";
    echo "│  RESULTS                                            │\n";
    echo "├──────────────────────────────────────────────────────┤\n";
    printf("│  Total Checks:  %-35s│\n", $total);
    printf("│  ✅ Passed:     %-35s│\n", $okCount);
    printf("│  ⚠️  Warnings:  %-35s│\n", $warnCount);
    printf("│  ❌ Failed:     %-35s│\n", $failCount);
    echo "├──────────────────────────────────────────────────────┤\n";
    
    if ($failCount === 0) {
        echo "│  🎉 SERVER IS READY FOR DEPLOYMENT!                 │\n";
    } else {
        echo "│  ⛔ FIX " . $failCount . " ISSUE(S) BEFORE DEPLOYING                  │\n";
    }
    echo "└──────────────────────────────────────────────────────┘\n\n";
} else {
    $summaryBg = $failCount === 0 ? '#dcfce7' : '#fee2e2';
    $summaryColor = $failCount === 0 ? '#166534' : '#991b1b';
    $summaryIcon = $failCount === 0 ? '🎉' : '⛔';
    $summaryText = $failCount === 0 ? 'Server is READY for deployment!' : "Fix $failCount issue(s) before deploying";
    
    echo "<div style='margin:20px 0;padding:20px;border-radius:12px;background:$summaryBg;text-align:center;'>";
    echo "<div style='font-size:32px;margin-bottom:8px;'>$summaryIcon</div>";
    echo "<div style='font-size:18px;font-weight:700;color:$summaryColor;'>$summaryText</div>";
    echo "<div style='margin-top:12px;display:flex;justify-content:center;gap:24px;'>";
    echo "<span style='color:#166534;font-weight:600;'>✅ $okCount Passed</span>";
    echo "<span style='color:#854d0e;font-weight:600;'>⚠️ $warnCount Warnings</span>";
    echo "<span style='color:#991b1b;font-weight:600;'>❌ $failCount Failed</span>";
    echo "</div></div>";
    echo "</body></html>";
}

// ── HELPER ──
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    switch ($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
