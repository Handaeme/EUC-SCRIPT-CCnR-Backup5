<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main" style="background: #f8fafc; min-height: 100vh; padding: 25px;">
    
    <div style="margin-bottom: 25px;">
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">Backup & Restore</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Manage database backups and perform restoration.</p>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <i class="fi fi-rr-check" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fi fi-rr-exclamation" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        
        <!-- BACKUP CARD -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="background: #eff6ff; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #2563eb; margin-bottom: 15px;">
                <i class="fi fi-rr-download" style="font-size: 20px;"></i>
            </div>
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Create Backup</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
                Download a full SQL dump of the database (`EUC_CITRA`). <br>
                This file contains all tables, including Requests, Templates, and Users.
            </p>
            <a href="?controller=backup&action=download" style="display: inline-block; width: 100%; text-align: center; background: #2563eb; color: white; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none;">
                Download SQL Dump
            </a>
        </div>

        <!-- RESTORE CARD -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="background: #fef3c7; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #d97706; margin-bottom: 15px;">
                <i class="fi fi-rr-upload" style="font-size: 20px;"></i>
            </div>
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Restore Database</h3>
            
            <div style="background: #fffbeb; border: 1px solid #fcd34d; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                 <p style="font-size: 12px; color: #92400e; margin: 0; display: flex; gap: 8px;">
                    <i class="fi fi-rr-info"></i>
                    <span>
                        <strong>Smart Restore:</strong> System Users (`tbluser`) will <u>NOT</u> be restored/overwritten. This preserves your Admin access.
                    </span>
                 </p>
            </div>

            <form action="?controller=backup&action=restore" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px;">Select Backup File (.sql)</label>
                    <input type="file" name="backup_file" accept=".sql" required style="width: 100%; font-size: 13px; color: #334155; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <button type="submit" onclick="return confirm('WARNING: This will overwrite existing data (except Users). Continue?');" style="width: 100%; background: white; border: 1px solid #cbd5e1; color: #334155; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                    Restore Backup
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
