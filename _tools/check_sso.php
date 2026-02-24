<?php
session_start();

// Basic CSS for readability
echo '<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f8fafc; color: #334155; }
    h2 { color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
    .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    pre { background: #1e293b; color: #a5b4fc; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 14px; line-height: 1.5; }
    .status-ok { color: #10b981; font-weight: bold; }
    .status-warning { color: #f59e0b; font-weight: bold; }
    .status-error { color: #ef4444; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
    th { background: #f1f5f9; color: #475569; font-weight: 600; }
</style>';

echo "<h2>SSO Integration Debugger</h2>";

echo '<div class="card">';
echo "<h3>1. Raw Session Data (<code>\$_SESSION</code>)</h3>";
echo "<p>This shows exactly what the main CITRA portal has stored in your browser session.</p>";
echo "<pre>";
if (empty($_SESSION)) {
    echo "SESSION IS EMPTY! \nIf you are logged into the main portal, the session variables are not reaching this folder.\nMake sure you access this file on the same domain/IP as the main portal.";
} else {
    print_r($_SESSION);
}
echo "</pre>";
echo '</div>';

echo '<div class="card">';
echo "<h3>2. EUC-Script Compatibility Check</h3>";
echo "<p>EUC-Script expects certain variables to be present in <code>\$_SESSION['user']</code> to work correctly.</p>";

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    
    $expectedKeys = [
        'userid' => 'Used to identify the user (NIK/ID).',
        'dept' => 'CRITICAL: Used to determine Role (MAKER, SPV, PIC, PROCEDURE).',
        'fullname' => 'Used for display names.',
    ];
    
    echo "<table>";
    echo "<tr><th>Expected Key</th><th>Found Status</th><th>Current Value</th><th>Description</th></tr>";
    
    foreach ($expectedKeys as $key => $desc) {
        $found = isset($_SESSION['user'][$key]);
        $val = $found ? $_SESSION['user'][$key] : 'NULL';
        
        $statusStyle = $found ? 'status-ok' : 'status-error';
        $statusText = $found ? '✔ Found' : '✘ Missing';
        
        // Specific check for dept values
        if ($key === 'dept' && $found) {
            $validRoles = ['MAKER', 'SPV', 'PIC', 'PROCEDURE', 'CPMS', 'ADMIN'];
            $upperVal = strtoupper($val);
            if (!in_array($upperVal, $validRoles)) {
                $statusStyle = 'status-warning';
                $statusText = '⚠ Warning';
                $val .= " (Not a standard EUC-Script role)";
            }
        }
        
        echo "<tr>";
        echo "<td><code>\$_SESSION['user']['$key']</code></td>";
        echo "<td class='$statusStyle'>$statusText</td>";
        echo "<td><strong>" . htmlspecialchars($val) . "</strong></td>";
        echo "<td style='color: #64748b; font-size: 13px;'>$desc</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p class='status-error'>✘ Missing <code>\$_SESSION['user']</code> array entirely.</p>";
    echo "<p>If the main portal stores user data directly in <code>\$_SESSION['userid']</code> instead of inside a <code>['user']</code> array, we will need to map it in <code>index.php</code>.</p>";
}
echo '</div>';

echo '<div class="card">';
echo "<h3>3. Next Steps</h3>";
echo "<ul>";
echo "<li>If <strong>All Checks Pass (✔ Found)</strong>, you can safely set <code>\$USE_PORTAL_SSO = true;</code> in <code>index.php</code>.</li>";
echo "<li>If critical keys are missing (or the structure is different), copy the output of 'Raw Session Data' and show it to the AI assistant so the mapping logic in <code>index.php</code> can be updated.</li>";
echo "</ul>";
echo '</div>';
?>
