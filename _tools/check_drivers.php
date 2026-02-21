<?php
/**
 * Check SQL Server Drivers
 * Test which SQL Server extensions are available
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQL Server Driver Checker</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #d32f2f;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: 600;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        .warning {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #ff9800;
        }
        .info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }
        .detail {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #ccc;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .icon {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 SQL Server Driver Checker</h1>
        <p>Checking PHP extensions for SQL Server connectivity...</p>
    </div>

    <div class="card">
        <h2>📊 PHP Information</h2>
        <div class="detail">
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
        </div>
        <div class="detail">
            <strong>Server API:</strong> <?php echo php_sapi_name(); ?>
        </div>
        <div class="detail">
            <strong>PHP Extension Directory:</strong> <?php echo ini_get('extension_dir'); ?>
        </div>
    </div>

    <div class="card">
        <h2>🔌 SQL Server Extensions</h2>
        
        <?php
        // Check PDO
        $pdo_loaded = extension_loaded('pdo');
        $pdo_sqlsrv_loaded = extension_loaded('pdo_sqlsrv');
        $sqlsrv_loaded = extension_loaded('sqlsrv');
        ?>
        
        <!-- PDO Base -->
        <div class="status <?php echo $pdo_loaded ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $pdo_loaded ? '✅' : '❌'; ?></span>
            <strong>PDO (Base)</strong>
            <?php if ($pdo_loaded): ?>
                - Installed & Loaded
                <div style="margin-top:10px; font-weight:normal;">
                    Available PDO Drivers: <?php echo implode(', ', PDO::getAvailableDrivers()); ?>
                </div>
            <?php else: ?>
                - Not Installed
            <?php endif; ?>
        </div>

        <!-- PDO_SQLSRV -->
        <div class="status <?php echo $pdo_sqlsrv_loaded ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $pdo_sqlsrv_loaded ? '✅' : '❌'; ?></span>
            <strong>PDO_SQLSRV</strong> (Microsoft PDO Driver for SQL Server)
            <?php if ($pdo_sqlsrv_loaded): ?>
                - Installed & Loaded ✨
                <div style="margin-top:10px; font-weight:normal;">
                    <strong>Status:</strong> This application uses PDO, and it's ready!
                </div>
            <?php else: ?>
                - Not Installed
                <div style="margin-top:10px; font-weight:normal; color:#666;">
                    <strong>Action:</strong> Download from <a href="https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server" target="_blank">Microsoft Drivers for PHP</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Native SQLSRV -->
        <div class="status <?php echo $sqlsrv_loaded ? 'success' : 'warning'; ?>">
            <span class="icon"><?php echo $sqlsrv_loaded ? '✅' : '⚠️'; ?></span>
            <strong>SQLSRV</strong> (Native Microsoft SQL Server Driver)
            <?php if ($sqlsrv_loaded): ?>
                - Installed & Loaded
                <div style="margin-top:10px; font-weight:normal;">
                    <strong>Note:</strong> This application uses PDO, not native SQLSRV
                </div>
            <?php else: ?>
                - Not Installed (Optional)
                <div style="margin-top:10px; font-weight:normal;">
                    <strong>Note:</strong> Not required. This application uses PDO.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>🎯 Application Status</h2>
        <?php if ($pdo_loaded && $pdo_sqlsrv_loaded): ?>
            <div class="status success">
                <span class="icon">🚀</span>
                <strong>READY TO RUN!</strong>
                <div style="margin-top:10px; font-weight:normal;">
                    All required drivers are installed. The EUC Script CCnR application will work perfectly.
                </div>
            </div>
        <?php elseif ($pdo_loaded && !$pdo_sqlsrv_loaded): ?>
            <div class="status error">
                <span class="icon">⛔</span>
                <strong>PDO_SQLSRV MISSING</strong>
                <div style="margin-top:10px; font-weight:normal;">
                    PDO is installed but <code>pdo_sqlsrv</code> extension is missing.<br>
                    Download and install from <a href="https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server" target="_blank">Microsoft's official site</a>
                </div>
            </div>
        <?php else: ?>
            <div class="status error">
                <span class="icon">⛔</span>
                <strong>PDO NOT AVAILABLE</strong>
                <div style="margin-top:10px; font-weight:normal;">
                    PDO extension is not loaded. This should not happen in modern PHP installations.<br>
                    Check your <code>php.ini</code> configuration.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📋 Next Steps</h2>
        <?php if (!$pdo_loaded || !$pdo_sqlsrv_loaded): ?>
            <ol>
                <li>Download <strong>Microsoft Drivers for PHP for SQL Server</strong> matching your PHP version</li>
                <li>Extract <code>php_pdo_sqlsrv_XX_ts.dll</code> (or nts) to your PHP extension directory</li>
                <li>Add to <code>php.ini</code>:
                    <pre style="background:#f5f5f5; padding:10px; margin:10px 0;">extension=pdo_sqlsrv</pre>
                </li>
                <li>Restart Apache/PHP-FPM</li>
                <li>Refresh this page to verify</li>
            </ol>
        <?php else: ?>
            <div class="status info">
                <span class="icon">✨</span>
                Everything is configured correctly! You're all set.
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>🔗 Useful Links</h2>
        <ul>
            <li><a href="https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server" target="_blank">Microsoft Drivers for PHP for SQL Server</a></li>
            <li><a href="https://www.php.net/manual/en/ref.pdo-sqlsrv.php" target="_blank">PDO_SQLSRV Documentation</a></li>
            <li><a href="phpinfo.php" target="_blank">View Full phpinfo()</a> (if available)</li>
        </ul>
    </div>
</body>
</html>
