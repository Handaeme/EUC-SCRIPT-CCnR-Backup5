<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main" style="background: #f8fafc; min-height: 100vh; padding: 15px;">
    
    <!-- HEADER -->
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">User Management</h2>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Manage system users and access roles</p>
        </div>
        <div>
            <a href="?controller=user&action=create" style="background: var(--primary-red); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fi fi-rr-user-add"></i> Add New User
            </a>
        </div>
    </div>

    <!-- ROLE SWITCHER (ADMIN ONLY) -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #eff6ff; padding: 10px; border-radius: 8px; color: #2563eb;">
                    <i class="fi fi-rr-user-robot" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h5 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;">Super Admin View Switcher</h5>
                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #64748b;">Switch your dashboard view to test other roles</p>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="?controller=user&action=switchRole&role=MAKER" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none; background: white; transition: all 0.2s;">MAKER</a>
                <a href="?controller=user&action=switchRole&role=SPV" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none; background: white; transition: all 0.2s;">SPV</a>
                <a href="?controller=user&action=switchRole&role=PIC" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none; background: white; transition: all 0.2s;">PIC</a>
                <a href="?controller=user&action=switchRole&role=PROCEDURE" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none; background: white; transition: all 0.2s;">PROCEDURE</a>
            </div>
        </div>
    </div>

    <!-- SEARCH & TABLE -->
    <div style="margin-bottom: 20px;">
        <input type="text" id="liveSearchInput" placeholder="Live Search users..." style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
    </div>

    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">User ID</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Fullname</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Role (Derived)</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Jabatan / Divisi</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Unit / Group</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Auth Type</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px;">Status</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" style="padding:30px; text-align:center; color:#94a3b8;">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            // Derive Role for Display
                            $d_job  = strtoupper(trim($u['JOB_FUNCTION'] ?? ''));
                            $d_dept = strtoupper(trim($u['DEPT'] ?? ''));
                            $d_div  = trim($u['DIVISI'] ?? '');
                            
                            $displayRole = 'USER';
                            if ($d_job === 'DEPARTMENT HEAD') $displayRole = 'MAKER';
                            elseif ($d_job === 'DIVISION HEAD') $displayRole = 'SPV';
                            elseif ($d_dept === 'PIC') $displayRole = 'PIC';
                            elseif (stripos($d_div, 'Quality Analysis Monitoring') !== false) $displayRole = 'PROCEDURE';
                            elseif ($d_dept === 'ADMIN') $displayRole = 'ADMIN';

                            // Determine Job/Divisi Display
                            $jobDivDisplay = $u['JOB_FUNCTION'] ?: ($u['DIVISI'] ?: '-');
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.1s;">
                            <td style="padding: 12px 20px; color: #1e293b; font-weight: 600;">
                                <?php echo htmlspecialchars($u['USERID']); ?>
                            </td>
                            <td style="padding: 12px 20px; color: #334155;">
                                <?php echo htmlspecialchars($u['FULLNAME']); ?>
                            </td>
                            <td style="padding: 12px 20px;">
                                <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #e2e8f0;">
                                    <?php echo htmlspecialchars($displayRole); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 20px; color: #334155; font-size: 13px;">
                                <?php echo htmlspecialchars($jobDivDisplay); ?>
                            </td>
                            <td style="padding: 12px 20px; color: #334155; font-size: 13px;">
                                <?php echo htmlspecialchars($u['GROUP'] ?? $u['group_name'] ?? '-'); ?>
                            </td>
                            <td style="padding: 12px 20px;">
                                <?php if($u['LDAP'] == 1): ?>
                                    <span style="color: #0284c7; font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                        <div style="width: 6px; height: 6px; background: #0284c7; border-radius: 50%;"></div> LDAP
                                    </span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                        <div style="width: 6px; height: 6px; background: #94a3b8; border-radius: 50%;"></div> Local
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 20px;">
                                <?php if(($u['AKTIF'] ?? $u['is_active']) == 1): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; border: 1px solid #bbf7d0;">Active</span>
                                <?php else: ?>
                                    <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; border: 1px solid #fecaca;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 20px; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                    <a href="?controller=user&action=edit&id=<?php echo urlencode($u['USERID']); ?>" 
                                       title="Edit User"
                                       style="width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; text-decoration: none; transition: all 0.2s;">
                                        <i class="fi fi-rr-edit"></i>
                                    </a>
                                    <?php if(($u['AKTIF'] ?? $u['is_active']) == 1 && $u['USERID'] !== 'admin'): ?>
                                    <a href="?controller=user&action=delete&id=<?php echo urlencode($u['USERID']); ?>" 
                                       onclick="return confirm('Deactivate this user?');"
                                       title="Deactivate User"
                                       style="width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; color: #ef4444; text-decoration: none; transition: all 0.2s;">
                                        <i class="fi fi-rr-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php include __DIR__ . '/../layouts/pagination.php'; ?>
    </div>
</div>

<script>
document.getElementById('liveSearchInput').addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var rows = document.querySelectorAll('table tbody tr');

    rows.forEach(function(row) {
        // Collect text from all cells except the last (Actions)
        var cells = row.getElementsByTagName('td');
        var text = '';
        for (var i = 0; i < cells.length - 1; i++) {
            text += cells[i].textContent || cells[i].innerText;
        }
        
        if (text.toLowerCase().indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
