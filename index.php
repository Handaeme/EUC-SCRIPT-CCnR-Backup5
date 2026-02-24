<?php
session_start();
// Simple Router / Dispatcher placeholder
// For now, if no session, show Login.
// If session, show Dashboard (which matches "Header merah... Sidebar abu muda").

// TIMEOUT & TIMEZONE SETTINGS
date_default_timezone_set('Asia/Jakarta');
set_time_limit(300); // 5 Minutes max execution (for uploads)

$request = $_SERVER['REQUEST_URI'];

// Load Environment Variables
require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');

// =========================================================================
// [PRODUCTION INTEGRATION CONFIGURATION]
// Use these settings when moving the application to the main CITRA Portal.
// =========================================================================

// 1. Set ini ke TRUE saat sudah terintegrasi penuh dengan portal utama.
// Jika TRUE: Form login bawaan akan disembunyikan, dan user akan dialihkan ke PORTAL_LOGIN_URL.
$USE_PORTAL_SSO = true; 

// 2. URL Login Utama (Tempat user di-redirect jika belum login)
// Sesuaikan IP/Domain dengan environment Production Anda.
 $PORTAL_LOGIN_URL = 'http://172.17.37.172/CITRASF/index.php';

// 3. URL Logout Utama (Tempat user di-redirect setelah klik Logout)
$PORTAL_LOGOUT_URL = 'http://172.17.37.172/CITRASF/logout.php';// Ganti dng URL logout citra yg sebenarnya

// =========================================================================

// DB ADAPTER: Load this BEFORE any database usage/checks
require_once __DIR__ . '/app/helpers/DbAdapter.php';

// Basic Install Check
if (!file_exists(__DIR__ . '/config/database.php')) {
  header("Location: setup.php");
  exit;
}

// Basic Install Check
if (!file_exists(__DIR__ . '/config/database.php')) {
  header("Location: setup.php");
  exit;
}

// NATIVE AUTOLOADER (No Composer)
spl_autoload_register(function ($class) {
  $prefix = 'App\\';
  $base_dir = __DIR__ . '/app/';
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) return;
  $relative_class = substr($class, $len);
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
  if (file_exists($file)) {
    require $file;
  }
});

// Logic: Check DB Connection
$config = require __DIR__ . '/config/database.php';

// We suppress errors here to avoid leaking sensitive info, or show friendly error
$connectionInfo = [
    'Database' => $config['dbname'],
    'UID' => $config['user'],
    'PWD' => $config['pass']
];

// Add extra options if any
if (isset($config['options'])) {
    $connectionInfo = array_merge($connectionInfo, $config['options']);
}

$conn = db_connect($config['host'], $connectionInfo);
if (!$conn) {
    die("Database Connection Failed<br><small>" . print_r(db_errors(), true) . "</small><br>Please ensure SQL Server is running and config/database.php is correct.");
}

// Logout Logic (MUST be before Router to catch all ?action=logout regardless of controller)
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
  session_destroy();
  if ($USE_PORTAL_SSO) {
      header("Location: " . $PORTAL_LOGOUT_URL);
  } else {
      header("Location: index.php");
  }
  exit;
}

// Router
$controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Home';
$methodName = isset($_GET['action']) ? $_GET['action'] : 'index';

// Normalize Controller Name (e.g. 'request' -> 'RequestController')
$controllerClass = "App\\Controllers\\" . ucfirst($controllerName) . "Controller";

// If Controller doesn't exist, fall back to default logic (Dashboard in this file)
// BUT, if we want to move Dashboard to a controller, we should do that.
// For now, let's keep the existing "Dashboard" as the fallback if 'Home' controller is requested but not found (yet).

if (class_exists($controllerClass)) {
  $controller = new $controllerClass();
  if (method_exists($controller, $methodName)) {
    $controller->$methodName();
    exit; // Stop execution here, don't show the dashboard below
  } else {
    die("Method $methodName not found in $controllerClass");
  }
}

// --- FALLBACK DASHBOARD (Existing Code) ---

// Login Logic
// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $userid = $_POST['userid']; 
  $password = $_POST['password']; 
  
  // Log Attempt
  log_message("Attempted login for user: $userid");

  // 1. Query user from tbluser
  $sql = "SELECT * FROM tbluser WHERE USERID = ? AND AKTIF = 1";
  $stmt = db_query($conn, $sql, [$userid]);
  
  if ($stmt && db_has_rows($stmt)) {
    $user = db_fetch_array($stmt, DB_FETCH_ASSOC);
    $user = array_change_key_case($user, CASE_UPPER); // Normalize column keys for case-insensitive access
    
    $loginSuccess = false;

    // 2. Auth Logic based on User Flag
    if ($user['LDAP'] == 1) {
      // STRICT LDAP MODE
      $isLdapSuccess = false;
      $ldapErrorMsg = "LDAP Connection Failed";

      if (function_exists('ldap_connect')) {
        $ldap_host = "ldap://cimbniaga.co.id:389";
        $ldap_con = ldap_connect($ldap_host);
        
        if ($ldap_con) {
          ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
          ldap_set_option($ldap_con, LDAP_OPT_NETWORK_TIMEOUT, 5); 
          
          $ldap_rn = "cimbniaga\\" . $userid; 
          // Suppress warning to handle error manually
          $bind = @ldap_bind($ldap_con, $ldap_rn, $password);
          
          if ($bind) {
            log_message("LDAP bind successful for user: $userid");
            $loginSuccess = true;
            @ldap_unbind($ldap_con);
          } else {
            $ldapErrorMsg = "LDAP Error: " . ldap_error($ldap_con);
            log_message("LDAP Bind Failed for $userid: " . $ldapErrorMsg);
          }
        }
      } else {
        $ldapErrorMsg = "PHP LDAP Module not enabled";
      }

      if (!$loginSuccess) {
        // User asked for Notification on failure, NO FALLBACK for LDAP users
        $error = "Login LDAP Gagal. Pastikan password domain benar. (" . $ldapErrorMsg . ")";
      }

    } else {
      // LOCAL MODE (LDAP = 0)
      if ($user['PASSWORD'] === $password) { 
         log_message("Local login successful for user: $userid");
         $loginSuccess = true;
      } else {
         log_message("Local login failed for user: $userid");
         $error = "Password Salah (Local User)";
      }
    }

    if ($loginSuccess) {
      // 3. Determine Role from DB columns
      // MAKER = JOB_FUNCTION 'DEPARTMENT HEAD'
      // SPV   = JOB_FUNCTION 'DIVISION HEAD'
      // PIC   = DEPT 'PIC'
      // PROCEDURE = DIVISI 'Quality Analysis Monitoring & Procedure'
      $jobFunc = strtoupper(trim($user['JOB_FUNCTION'] ?? ''));
      $dept    = strtoupper(trim($user['DEPT'] ?? ''));
      $divisi  = trim($user['DIVISI'] ?? '');

      if ($jobFunc === 'DEPARTMENT HEAD') {
          $derivedRole = 'MAKER';
          $roleLabel = 'DEPARTMENT HEAD';
      } elseif ($jobFunc === 'DIVISION HEAD') {
          $derivedRole = 'SPV';
          $roleLabel = 'DIVISION HEAD';
      } elseif ($dept === 'PIC') {
          $derivedRole = 'PIC';
          $roleLabel = 'Coordinator Script';
      } elseif (stripos($divisi, 'Quality Analysis Monitoring') !== false) {
          $derivedRole = 'PROCEDURE';
          $groupName = $user['GROUP'] ?? '';
          $roleLabel = !empty($groupName) ? $groupName : 'CPMS/QPM';
      } elseif ($dept === 'ADMIN') {
          $derivedRole = 'ADMIN';
          $roleLabel = 'ADMIN';
      } else {
          $derivedRole = 'MAKER'; // fallback
          $roleLabel = $jobFunc ?: $dept ?: 'USER';
      }

      $_SESSION['user'] = [
        'userid' => $user['USERID'], 
        'fullname' => $user['FULLNAME'],
        'dept' => $derivedRole,           // Role code used throughout the app
        'role_label' => $roleLabel,       // Human-readable label for display
        'job_function' => $user['JOB_FUNCTION'] ?? '',
        'divisi' => $user['DIVISI'] ?? '',
        'group_name' => $user['GROUP'] ?? '',
        'ldap' => $user['LDAP']
      ];
      
      log_message("Redirecting $userid to index.php");
      header("Location: index.php"); 
      exit;
    }

  } else {
    log_message("User not found or inactive: $userid");
    $error = "User not found or inactive";
  }
}

// Function for logging (Adapted from ldap.txt)
function log_message($message) {
  // Ensure logs dir exists
  $logDir = __DIR__ . '/logs';
  if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
  }

  $logFile = $logDir . '/login.log';
  $timestamp = date('Y-m-d H:i:s');
  $logEntry = "[$timestamp] $message" . PHP_EOL;

  file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// --- SSO SESSION MAPPING ---
// If SSO is active, the main portal stores data in the root of $_SESSION (e.g., $_SESSION['NIK'])
// EUC-Script expects this data to be inside $_SESSION['user']. We map it here automatically.
if ($USE_PORTAL_SSO && isset($_SESSION['NIK']) && !isset($_SESSION['user']['userid'])) {
    $jobFunc = strtoupper(trim($_SESSION['JOB_FUNCTION'] ?? ''));
    $dept    = strtoupper(trim($_SESSION['DEPT'] ?? ''));
    $divisi  = trim($_SESSION['DIVISI'] ?? '');

    // Determine Role using the exact same logic as local login
    if ($jobFunc === 'DEPARTMENT HEAD') {
        $derivedRole = 'MAKER';
        $roleLabel = 'DEPARTMENT HEAD';
    } elseif ($jobFunc === 'DIVISION HEAD') {
        $derivedRole = 'SPV';
        $roleLabel = 'DIVISION HEAD';
    } elseif ($dept === 'PIC') {
        $derivedRole = 'PIC';
        $roleLabel = 'Coordinator Script';
    } elseif (stripos($divisi, 'Quality Analysis Monitoring') !== false || $dept === 'PROCEDURE') {
        $derivedRole = 'PROCEDURE';
        $groupName = $_SESSION['GROUP'] ?? '';
        $roleLabel = !empty($groupName) ? $groupName : 'CPMS/QPM';
    } elseif ($dept === 'ADMIN' || (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1)) {
        $derivedRole = 'ADMIN';
        $roleLabel = 'ADMIN';
    } else {
        $derivedRole = 'MAKER'; // fallback
        $roleLabel = $jobFunc ?: $dept ?: 'USER';
    }

    // Build the array expected by EUC-Script
    $_SESSION['user'] = [
        'userid' => $_SESSION['NIK'], 
        'fullname' => $_SESSION['USER_NAME'] ?? $_SESSION['NAMA'] ?? $_SESSION['NIK'], // Try to find a name, fallback to NIK
        'dept' => $derivedRole,           
        'role_label' => $roleLabel,       
        'job_function' => $_SESSION['JOB_FUNCTION'] ?? '',
        'divisi' => $_SESSION['DIVISI'] ?? '',
        'group_name' => $_SESSION['GROUP'] ?? '',
        'ldap' => 1 // Assume LDAP if coming from main portal
    ];
}


// --- FALLBACK DASHBOARD (Role Based) ---
if (isset($_SESSION['user']) && isset($_SESSION['user']['dept'])) {
  $dashboard = new App\Controllers\DashboardController();
  $dashboard->index();
} else {
  // JIKA BELUM LOGIN:
  if ($USE_PORTAL_SSO) {
      // Production Mode: Redirect ke portal utama CITRA
      header("Location: " . $PORTAL_LOGIN_URL);
      exit;
  }
  
  // Standalone Mode: Tampilkan halaman LOGIN PAGE bawaan
  ?>
<!DOCTYPE html>
<html>
<head>
  <title>Login - CITRA</title>
  <style>
    :root {
      --primary-red: #d32f2f;
      --primary-dark: #b71c1c;
      --text-main: #1e293b;
      --text-muted: #64748b;
    }
    body { 
      margin: 0; 
      font-family: 'Inter', -apple-system, sans-serif; 
      height: 100vh; 
      display: flex; 
      overflow: hidden;
      background: #fff;
    }
    
    /* Responsive Desktop */
    .login-container {
      display: flex;
      width: 100%;
      height: 100%;
    }

    /* Left Section: Image/Branding */
    .login-visual {
      flex: 1.5; /* Give slightly more width to image on large screens */
      background: white; /* All white background */
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: flex-start; /* Align content to start (left) */
    }

    .login-visual img {
      width: 100%;
      height: 100%;
      object-fit: contain; /* Shows full image without cropping */
      object-position: left center; /* Push image to the left edge */
      position: absolute;
      top: 0;
      left: 0;
      padding: 0; /* MAXIMIZED SIZE */
    }
    
    /* Right Section: Form */
    .login-form-area {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
      background: white;
      position: relative;
      z-index: 2;
    }

    /* Form Styles (Restored) */
    .login-box-wrapper {
      width: 100%;
      max-width: 420px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-box { 
      width: 100%;
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    }

    .login-header {
      margin-bottom: 32px;
    }

    .login-header h2 { 
      color: var(--primary-red); 
      margin: 0; 
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    
    .login-header p {
      color: var(--text-muted);
      margin: 8px 0 0 0;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }

    input[type="text"], input[type="password"] { 
      width: 100%; 
      padding: 12px 16px; 
      border: 1.5px solid #e2e8f0; 
      border-radius: 10px; 
      box-sizing: border-box; 
      font-size: 15px;
      transition: all 0.2s;
      background: #f8fafc;
    }

    input:focus {
      outline: none;
      border-color: var(--primary-red);
      background: white;
      box-shadow: 0 0 0 4px rgba(211, 47, 47, 0.1);
    }

    button { 
      width: 100%; 
      padding: 13px; 
      background: var(--primary-red); 
      color: white; 
      border: none; 
      border-radius: 10px; 
      cursor: pointer; 
      font-weight: 700; 
      font-size: 15px;
      margin-top: 10px;
      transition: all 0.2s;
      box-shadow: 0 4px 6px -1px rgba(211, 47, 47, 0.2);
    }

    button:hover { 
      background: var(--primary-dark); 
      transform: translateY(-1px);
      box-shadow: 0 6px 12px -2px rgba(211, 47, 47, 0.3);
    }

    button:active {
      transform: translateY(0);
    }

    .error { 
      background: #fef2f2;
      color: #dc2626; 
      font-size: 13px; 
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #fee2e2;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .hint { 
      font-size: 12px; 
      color: #94a3b8; 
      margin-top: 32px; 
      text-align: center;
      padding: 12px;
      border-top: 1px solid #f1f5f9;
    }

    .hint b { color: #64748b; }

    /* Mobile / Tablet Responsiveness */
    @media (max-width: 900px) {
      .login-container {
        flex-direction: column;
      }
      
      .login-visual {
        flex: none;
        height: 250px; /* Smaller height for banner on mobile */
        width: 100%;
      }
      
      .login-form-area {
        flex: 1;
        padding: 20px;
        align-items: flex-start; /* Align form to top on mobile */
        padding-top: 40px;
      }

      .visual-placeholder {
         display: none;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- Left Section -->
    <div class="login-visual">
       <img src="assets/images/logo.png" alt="Company Logo">
    </div>

    <!-- Right Section -->
    <div class="login-form-area">
      <div class="login-box-wrapper">
        <div class="login-box">
          <div class="login-header">
            <h2>Sign In to CITRA</h2>
            <p>Please sign in to your CITRA account</p>
          </div>

          <?php if(isset($error)): ?>
            <div class='error'>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <?php echo $error; ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="form-group">
              <label>User ID</label>
              <input type="text" name="userid" placeholder="Enter your ID" required autofocus>
            </div>
            
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login">Sign In</button>
          </form>

          <div class="hint">
            Default Access: <b>maker01</b> / <b>123</b>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
  <?php
}
?>

