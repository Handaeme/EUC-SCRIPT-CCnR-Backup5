    <!-- SIDEBAR -->
    <?php
    function isActive($c, $a = null) {
        $currC = $_GET['controller'] ?? 'dashboard';
        $currA = $_GET['action'] ?? 'index';
        
        // Strict Comparison
        if ($c === $currC && ($a === null || $a === $currA)) {
            return 'active';
        }
        return '';
    }
    // Remove standalone variables that confuse scope active state
    ?>

    
    <div class="sidebar">
        <?php
        // Safety check for session
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }
        
        $role = $_SESSION['user']['dept'] ?? '';
        $reqModel = new \App\Models\RequestModel();
        $badgeCount = 0;
        $userid = $_SESSION['user']['userid'] ?? '';

        // Determine badge count
        if ($role === 'SPV') {
            $pending = $reqModel->getPendingRequests($userid, 'SPV');
            $badgeCount = count($pending);
        } elseif ($role === 'PIC') {
            $pending = $reqModel->getPendingRequests($userid, 'PIC');
            $badgeCount = count($pending);
        } elseif ($role === 'PROCEDURE') {
            $pending = $reqModel->getPendingRequests($userid, 'PROCEDURE');
            $badgeCount = count($pending);
        } elseif ($role === 'MAKER' || $role === 'Maker') {
            // Count Pending Revisions for Maker
            $stats = $reqModel->getMakerStats($userid);
            $revCount = $stats['pending'] ?? 0;
            // Use same badgeCount variable for Dashboard if we want it there?
            // User asked for "dashboard kasih notif juga" (Dashboard give notif ALSO)
            // So we can set badgeCount for Dashboard too, OR keep separate variables.
            // Let's set a specific variable for Dashboard badge to be clear, 
            // or just reuse badgeCount if "Revise" is the dashboard.
            // Let's use $badgeCount for the main "Dashboard/Revise/Approval" link.
            $badgeCount = $revCount;
        }
        ?>
        
        <!-- App Title at Top -->
        <div class="sidebar-title">
            <h3>EUC Script CCnR</h3>
        </div>
        
        <?php if ($role === 'MAKER' || $role === 'Maker'): ?>
            <!-- MAKER: Create New Request first, then My Tasks -->
            <a href="?controller=request&action=create" class="menu-item <?php echo isActive('request', 'create'); ?>">
                <i class="fi fi-rr-add-document"></i>
                Create New Request
            </a>
            <a href="?controller=request&action=index" class="menu-item <?php echo isActive('request', 'index'); ?>">
                <i class="fi fi-rr-list-check"></i>
                My Tasks
                <?php if ($badgeCount > 0): ?>
                    <span style="background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:auto; font-weight:bold;"><?php echo $badgeCount; ?></span>
                <?php endif; ?>
            </a>
        <?php else: ?>
            <!-- Non-MAKER: Dashboard/Approval link -->
            <a href="index.php" class="menu-item <?php echo isActive('dashboard', 'index'); ?>">
                <i class="fi fi-rr-apps"></i>
                <?php 
                    if (in_array($role, ['SPV', 'PIC', 'PROCEDURE'])) {
                        echo 'Need to Approval';
                    } else {
                        echo 'Dashboard';
                    }
                ?>
                <?php if ($badgeCount > 0): ?>
                    <span style="background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:auto; font-weight:bold;"><?php echo $badgeCount; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        
        <a href="?controller=template&action=index" class="menu-item <?php echo isActive('template'); ?>">
            <i class="fi fi-rr-layout-fluid"></i>
            Template
        </a>
        
        <!-- Script Library - Visible for All -->
        <a href="?controller=dashboard&action=library" class="menu-item <?php echo isActive('dashboard', 'library'); ?>">
            <i class="fi fi-rr-book-alt"></i>
            Script Library
        </a>

        <!-- Audit Trail - Visible for ALL Roles -->
        <!-- Audit Trail - Visible for ALL Roles -->
        <a href="?controller=audit&action=index" class="menu-item <?php echo isActive('audit'); ?>">
            <i class="fi fi-rr-clock-three"></i>
            Audit Trail
        </a>

        <?php 
        // ADMIN / IMPERSONATION SECTION
        $originalRole = $_SESSION['original_role'] ?? null;
        if ($role === 'ADMIN' || $originalRole === 'ADMIN') {
        ?>
            <div style="margin:15px 0 5px 20px; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase;">Admin Tools</div>
            
            <a href="?controller=user&action=index" class="menu-item <?php echo isActive('user'); ?>">
                <i class="fi fi-rr-users-alt"></i>
                User Management
            </a>
            
            <a href="?controller=backup&action=index" class="menu-item <?php echo isActive('backup'); ?>">
                <i class="fi fi-rr-cloud-download-alt"></i>
                Backup & Restore
            </a>

            <?php if ($originalRole === 'ADMIN'): ?>
            <a href="?controller=user&action=restoreRole" class="menu-item" style="color:#ef4444; background:#fef2f2; border:1px solid #fca5a5; margin:10px 15px; text-align:center; justify-content:center;">
                <i class="fi fi-rr-undo" style="margin-right:5px;"></i>
                Exit View Mode
            </a>
            <?php endif; ?>
        <?php } ?>
    </div>
