<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main" style="background: #f8fafc; min-height: 100vh; padding: 25px;">

    <!-- Breadcrumb / Header -->
    <div style="margin-bottom: 20px;">
        <a href="?controller=user&action=index" style="text-decoration: none; color: #64748b; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 10px;">
            <i class="fi fi-rr-arrow-small-left"></i> Back to List
        </a>
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">Create New User</h2>
    </div>

    <!-- Form Card -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        
        <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
            <h5 style="margin: 0; font-size: 15px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 8px;">
                <i class="fi fi-rr-user-add"></i> User Details
            </h5>
        </div>

        <div style="padding: 25px;">
            <?php if(isset($_GET['error'])): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fecaca;">
                    <i class="fi fi-rr-exclamation" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="?controller=user&action=store" method="POST">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">User ID (Username)</label>
                    <input type="text" name="userid" required placeholder="e.g. maker01" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none; transition: border 0.2s;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Fullname</label>
                    <input type="text" name="fullname" required placeholder="e.g. John Doe" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Password</label>
                    <input type="password" name="password" required placeholder="Default password" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Dept <small style="color:#94a3b8">(hanya untuk PIC/ADMIN)</small></label>
                        <input type="text" name="dept" placeholder="e.g. PIC, ADMIN" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Job Function <small style="color:#94a3b8">(MAKER/SPV)</small></label>
                        <input type="text" name="job_function" placeholder="e.g. DEPARTMENT HEAD" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Divisi <small style="color:#94a3b8">(PROCEDURE)</small></label>
                        <input type="text" name="divisi" placeholder="e.g. Quality Analysis Monitoring & Procedure" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px;">Group Name (Unit)</label>
                        <input type="text" name="group_name" placeholder="e.g. CPMS, UNSECURED" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
                        <input type="checkbox" name="ldap" value="1" style="width: 16px; height: 16px; cursor: pointer;">
                        <div>
                            <span style="display: block; font-size: 13px; font-weight: 600; color: #334155;">Is LDAP User?</span>
                            <span style="display: block; font-size: 11px; color: #64748b;">If checked, password authenticates against LDAP</span>
                        </div>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                    <a href="?controller=user&action=index" style="padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; background: white;">Cancel</a>
                    <button type="submit" style="padding: 10px 20px; background: var(--primary-red); color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Create User</button>
                    <!-- Fallback style for button in case variable missing -->
                    <style>button[type="submit"] { background-color: #ef4444; }</style>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
