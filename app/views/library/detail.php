<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$isFileUpload = ($request['mode'] === 'FILE_UPLOAD');
?>

<style>
/* Excel Sheet Tabs CSS */
.sheet-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 1px solid #ccc;
    background: #f1f1f1;
}
.btn-sheet {
    border: 1px solid #ccc;
    border-bottom: none;
    background: #e0e0e0;
    padding: 8px 16px;
    cursor: pointer;
    font-size: 13px;
    margin-right: 2px;
}
.btn-sheet.active {
    background: #fff;
    font-weight: bold;
    border-top: 2px solid green;
}
.sheet-pane {
    padding: 15px;
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
    overflow: auto;
}

/* READ-ONLY MODE STYLES */
.library-content-readonly {
    user-select: text; /* Allow text selection for copying */
    pointer-events: auto;
}

.library-content-readonly * {
    cursor: default !important;
}

/* IMPORTANT: Allow tab buttons to be clickable */
.library-content-readonly .btn-sheet,
.library-content-readonly .btn-media-tab {
    pointer-events: auto !important;
    cursor: pointer !important;
}

/* Remove any contenteditable from tables/content */
.library-content-readonly [contenteditable],
.library-content-readonly table,
.library-content-readonly .sheet-pane > * {
    -moz-user-modify: read-only !important;
    -webkit-user-modify: read-only !important;
    pointer-events: none !important;
}

.read-only-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}
</style>

<!-- Load SheetJS Style (with Styling Support) -->
<script src="assets/js/xlsx.full.min.js"></script>
<script>if(typeof XLSX==='undefined'){document.write('<scr'+'ipt src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.6.4/dist/xlsx.bundle.js"><\/scr'+'ipt>')}</script>

<script>
// 1. FOR FILE UPLOAD (Sheets)
function changeSheet(id, idx) {
    // Hide all panes
    document.querySelectorAll('.sheet-pane').forEach(p => p.style.display = 'none');
    
    // Show target pane
    const target = document.getElementById(id);
    if(target) target.style.display = 'block';
    
    // Update button states
    document.querySelectorAll('.btn-sheet-tab').forEach(btn => {
        btn.style.background = '#f3f4f6';
        btn.style.color = '#4b5563';
        btn.style.border = '1px solid #e5e7eb';
    });
    
    const activeBtn = document.getElementById('btn-sheet-' + idx);
    if(activeBtn) {
        activeBtn.style.background = '#3b82f6';
        activeBtn.style.color = 'white';
        activeBtn.style.border = 'none';
    }
}

// 2. FOR FREE INPUT (Media Tabs)
function switchMediaTab(idx) {
    // Hide all panes
    document.querySelectorAll('.media-tab-pane').forEach(p => p.style.display = 'none');
    
    // Show target pane
    document.getElementById('media-pane-' + idx).style.display = 'block';
    
    // Update button states
    document.querySelectorAll('.btn-media-tab-unified').forEach(btn => {
        btn.style.background = '#f3f4f6';
        btn.style.color = '#4b5563';
        btn.style.border = '1px solid #e5e7eb';
    });
    
    const activeBtn = document.getElementById('media-btn-' + idx);
    if(activeBtn) {
        activeBtn.style.background = '#3b82f6';
        activeBtn.style.color = 'white';
        activeBtn.style.border = 'none';
    }
}

// Helper function to get clean DOM (unwraps revisions/red text to plain text)
function getCleanDOM(element) {
    const clonedElement = element.cloneNode(true);
    // Unwrap spans to keep text without formatting/color
    clonedElement.querySelectorAll('.revision-span, .inline-comment, span[style*="color: red"], span[style*="color:red"]').forEach(el => {
        el.replaceWith(...el.childNodes);
    });
    // Remove UI-only elements
    clonedElement.querySelectorAll('.comment-highlight, .btn-resolve, .tab-badge-dot').forEach(el => el.remove());
    return clonedElement;
}

// Helper function to auto-fit column widths
function fitToColumn(worksheet) {
    if (!worksheet['!ref']) return;
    const range = XLSX.utils.decode_range(worksheet['!ref']);
    worksheet['!cols'] = [];
    for (let C = range.s.c; C <= range.e.c; ++C) {
        let max_width = 10; // Minimum width
        for (let R = range.s.r; R <= range.e.r; ++R) {
            const cell = worksheet[XLSX.utils.encode_cell({c:C, r:R})];
            if (cell && cell.v) {
                // Approximate width based on char length (clamped)
                const value = String(cell.v);
                const lines = value.split('\n');
                let width = 0;
                lines.forEach(line => {
                    if (line.length > width) width = line.length;
                });
                
                // Cap max width to avoid super wide columns for long text
                if (width > 80) width = 80; 
                if (width > max_width) max_width = width;
            }
        }
        worksheet['!cols'][C] = { wch: max_width + 2 }; 
    }
}

// DOWNLOAD FUNCTION (USING SHEETJS)
function downloadContentAsExcel() {
    if (typeof XLSX === 'undefined') {
        alert('SheetJS library not loaded. Pastikan file assets/js/xlsx.full.min.js tersedia.');
        return;
    }

    // Support various content structures
    let sheets = document.querySelectorAll('.sheet-pane, .downloadable-sheet, .media-pane, .review-tab-content');
    if (sheets.length === 0) {
        sheets = document.querySelectorAll('.media-tab-pane');
    }
    
    // Fallback
    if (sheets.length === 0) {
        const tables = document.querySelectorAll('.library-content-readonly table');
        if (tables.length > 0) {
            sheets = tables; 
        }
    }
    
    if (sheets.length === 0) {
        alert('No content available to download (Content container not found)');
        return;
    }
    
    const scriptNum = '<?php echo $request["script_number"] ?? "Unknown"; ?>';
    const inputMode = '<?php echo $request["mode"] ?? "FREE_INPUT"; ?>';
    const originalFilename = '<?php echo addslashes(!empty($scriptFile["original_filename"]) ? $scriptFile["original_filename"] : ""); ?>';
    
    // Create new workbook
    const wb = XLSX.utils.book_new();
    let hasContent = false;
    
    sheets.forEach((sheet, index) => {
        // 1. Try data-media (Free Input Mode)
        let sheetName = sheet.getAttribute('data-media');
        
        // 2. Try finding matching tab button
        if (!sheetName && sheet.id) {
            const sheetId = sheet.id;
            const buttons = document.querySelectorAll('.btn-sheet, .sheet-tab-btn, .btn-media-tab, .btn-media-tab-unified');
            for (let btn of buttons) {
                const clickAttr = btn.getAttribute('onclick');
                if (clickAttr && (clickAttr.includes(`'${sheetId}'`) || clickAttr.includes(`"${sheetId}"`))) {
                    sheetName = btn.innerText.trim();
                    break;
                }
            }
            if (!sheetName) sheetName = sheetId.replace('sheet-', '');
        }

        // 3. Fallbacks
        if (!sheetName && sheet.parentElement && sheet.parentElement.id && sheet.parentElement.id.includes('sheet-')) {
            sheetName = parent.id.replace('sheet-', '');
        }
        if (!sheetName) sheetName = "Sheet " + (index + 1);
        
        // Clean Sheet Name
        sheetName = sheetName.replace(/[:\\/?*[\]]/g, '');
        if (sheetName.length > 31) sheetName = sheetName.substring(0, 31);
        
        // PREPARE CONTENT
        const cleanDOM = getCleanDOM(sheet);
        
        let table = cleanDOM.querySelector('table');
        if (!table && cleanDOM.tagName === 'TABLE') {
            table = cleanDOM;
        }

        let ws;
        
        if (table) {
            ws = XLSX.utils.table_to_sheet(table);
        } else {
            // Text content fallback (Free Input)
            let text = cleanDOM.innerText.trim();
            let aoa = [
                ["Script Content (" + sheetName + ")"],
                [text]
            ];
            ws = XLSX.utils.aoa_to_sheet(aoa);
        }
        
        // Auto-fit columns
        fitToColumn(ws);

        // APPLY STYLES
        if (ws['!ref']) {
            const range = XLSX.utils.decode_range(ws['!ref']);
            for(let R = range.s.r; R <= range.e.r; ++R) {
                for(let C = range.s.c; C <= range.e.c; ++C) {
                    const cell_ref = XLSX.utils.encode_cell({c:C, r:R});
                    if(!ws[cell_ref]) ws[cell_ref] = { t: 's', v: '' }; // Create empty cell if missing
                    
                    if(!ws[cell_ref].s) ws[cell_ref].s = {};
                    
                    // Borders
                    ws[cell_ref].s.border = {
                        top: { style: "thin", color: {rgb: "000000"} },
                        bottom: { style: "thin", color: {rgb: "000000"} },
                        left: { style: "thin", color: {rgb: "000000"} },
                        right: { style: "thin", color: {rgb: "000000"} }
                    };
                    
                    // Alignment & Wrap [FIXED: Enable Wrap]
                    if(!ws[cell_ref].s.alignment) ws[cell_ref].s.alignment = {};
                    ws[cell_ref].s.alignment.wrapText = true; // Enabled wrapping for all cells
                    ws[cell_ref].s.alignment.vertical = 'top';
                    
                    // Header Style (First Row)
                    if (R === 0) {
                        ws[cell_ref].s.fill = { fgColor: { rgb: "E0E0E0" } };
                        ws[cell_ref].s.font = { bold: true, color: { rgb: "000000" } };
                        ws[cell_ref].s.alignment.horizontal = 'center';
                        ws[cell_ref].s.alignment.vertical = 'center';
                    }
                }
            }
        }
        
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        hasContent = true;
    });
    
    if (!hasContent) {
        alert('Failed to generate Excel content');
        return;
    }
    
    // Customize Filename based on Input Mode
    let downloadFilename;
    if (inputMode === 'FILE_UPLOAD' && originalFilename) {
        // File Upload: use original filename
        downloadFilename = originalFilename.replace(/\.[^/.]+$/, '') + '.xlsx'; // Replace extension with .xlsx
    } else {
        // Free Input: FINAL SCRIPT_[ScriptNumber]
        let safeNum = scriptNum.replace(/[/\\?%*:|"<>]/g, '_');
        downloadFilename = 'FINAL SCRIPT_' + safeNum + '.xlsx';
    }
    XLSX.writeFile(wb, downloadFilename);
}

</script>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="?controller=dashboard&action=library" style="background:#f1f5f9; color:#64748b; padding:8px; border-radius:50%; display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#334155'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h2 style="color:var(--primary-red); margin:0;">Library Script Detail</h2>
                <div style="display:flex; align-items:center; gap:10px;">
                    <?php 
                        if (isset($isActive)) {
                            // Cek apakah statusnya Scheduled (Telah diaktifkan, tapi Start Date masih besok/lusa)
                            $isScheduled = false;
                            if ($isActive && !empty($startDate)) {
                                $todayDate = new DateTime($sqlServerToday ?? 'today');
                                $todayDate->setTime(0, 0, 0);
                                
                                $startDt = ($startDate instanceof DateTime) ? clone $startDate : new DateTime($startDate);
                                $startDt->setTime(0, 0, 0);
                                if ($startDt > $todayDate) {
                                    $isScheduled = true;
                                }
                            }
                            
                            if (!$isActive) {
                                $badgeStyle = 'background:#fef2f2; color:#b91c1c; border-color:#fecaca;';
                                $badgeText = 'INACTIVE';
                                $badgeIcon = '';
                            } elseif ($isScheduled) {
                                $badgeStyle = 'background:#fff7ed; color:#c2410c; border-color:#ffedd5;';
                                $badgeText = 'SCHEDULED';
                                $badgeIcon = '<i class="fi fi-rr-calendar-clock" style="font-size:11px; margin-right:4px;"></i>';
                            } else {
                                $badgeStyle = 'background:#ecfdf5; color:#047857; border-color:#d1fae5;';
                                $badgeText = 'ACTIVE';
                                $badgeIcon = '';
                            }
                        }
                    ?>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS AREA -->
        <div style="display:flex; gap:10px; align-items:center;">
            
            <!-- REUSE / REVISE ACTION AREA -->
            <?php if (isset($_SESSION['user']['dept']) && $_SESSION['user']['dept'] === 'MAKER'): ?>
                 <button onclick="document.getElementById('reuseModal').style.display='flex'" 
                         style="background:#ef4444; color:white; padding:8px 16px; border:1px solid #ef4444; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; height:36px; box-sizing:border-box;">
                     <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:flex;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                     Gunakan Script
                 </button>
            <?php endif; ?>

            <!-- ACTIVE TOGGLE (Only Maker & Procedure/CPMS) -->
            <?php if (in_array($_SESSION['user']['dept'] ?? '', ['MAKER', 'PROCEDURE', 'CPMS'])): ?>
                 <?php $isAct = $isActive ?? 1; ?>
                 <button onclick="toggleActiveStatus(<?php echo $request['id']; ?>, <?php echo $isAct ? 0 : 1; ?>)" 
                         style="background:<?php echo $isAct ? '#fff' : '#10b981'; ?>; color:<?php echo $isAct ? '#ef4444' : 'white'; ?>; padding:8px 16px; border:1px solid <?php echo $isAct ? '#ef4444' : '#10b981'; ?>; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; height:36px; box-sizing:border-box;">
                     <i class="fi fi-rr-<?php echo $isAct ? 'cross-circle' : 'calendar-check'; ?>" style="font-size:14px; display:flex;"></i>
                     <?php echo $isAct ? 'Deactivate Script' : 'Activate Script'; ?>
                 </button>
            <?php endif; ?>
            
        </div>

        <!-- SPECIAL PROCEDURE REVISION ENTRY POINT -->
        <?php if (isset($_SESSION['user']['dept']) && ($_SESSION['user']['dept'] === 'PROCEDURE' || $_SESSION['user']['dept'] === 'CPMS')): ?>
        <div style="display:flex; gap:10px;">
             <a href="?controller=request&action=review_library_script&id=<?php echo $request['id']; ?>" style="background:#7c3aed; color:white; padding:8px 16px; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:8px; text-decoration:none;">
                 <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                 Review / Revisi Script
             </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- REUSE MODAL -->
    <div id="reuseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;" onclick="closeModalIfBackdrop(event)">
        <div style="background:white; padding:25px; border-radius:12px; width:400px; max-width:90%; position:relative; box-shadow:0 10px 25px rgba(0,0,0,0.2);" onclick="event.stopPropagation()">
            <div style="margin-bottom:20px;">
                <h3 style="margin:0 0 10px 0; color:#1e293b;">Create New Version</h3>
                <p style="margin:0; color:#64748b; font-size:13px; line-height:1.5;">
                    This will duplicate the current script (Content & Files) into a new Draft Request. 
                    <br><br>
                    <strong>New Script Number:</strong> 
                    <?php 
                        // Preview Next Version Calculation
                        $parts = explode('-', $request['script_number']);
                        $last = end($parts);
                        $nextVer = is_numeric($last) ? intval($last) + 1 : 2;
                        $base = implode('-', array_slice($parts, 0, count($parts)-1));
                        echo $base . '-' . sprintf("%02d", $nextVer);
                    ?>
                </p>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:700; font-size:13px; color:#334155; margin-bottom:8px;">Select Supervisor to Review:</label>
                <select id="reuseSpvSelect" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; background:#fff;">
                    <option value="">-- Choose Supervisor --</option>
                    <?php if(isset($spvList) && is_array($spvList)): foreach($spvList as $spv): ?>
                        <option value="<?php echo htmlspecialchars($spv['userid']); ?>">
                            <?php echo htmlspecialchars($spv['fullname'] ?? $spv['userid']); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
                <div id="reuseError" style="color:#ef4444; font-size:12px; margin-top:5px; display:none;">Please select a supervisor.</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="closeReuseModal()" style="background:#f1f5f9; color:#64748b; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Cancel</button>
                <button onclick="confirmReuse()" id="btnConfirmReuse" style="background:#0f172a; color:white; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Create Draft</button>
            </div>
        </div>
    </div>

    <script>
    let currentFetchController = null; // Global abort controller
    
    function closeModalIfBackdrop(event) {
        // Only close if clicking the backdrop (not the modal content)
        if (event.target.id === 'reuseModal') {
            closeReuseModal();
        }
    }
    
    function closeReuseModal() {
        // Abort any pending request
        if (currentFetchController) {
            currentFetchController.abort();
            currentFetchController = null;
        }
        
        // Reset form
        document.getElementById('reuseSpvSelect').value = '';
        document.getElementById('reuseError').style.display = 'none';
        
        // Reset button
        const btn = document.getElementById('btnConfirmReuse');
        btn.innerHTML = 'Create Draft';
        btn.disabled = false;
        btn.style.opacity = '1';
        
        // Hide modal
        document.getElementById('reuseModal').style.display = 'none';
    }
    
    function confirmReuse() {
        const spv = document.getElementById('reuseSpvSelect').value;
        const btn = document.getElementById('btnConfirmReuse');
        const err = document.getElementById('reuseError');

        if (!spv) {
            err.style.display = 'block';
            return;
        }
        err.style.display = 'none';
        
        // Disable button
        btn.innerHTML = 'Creating...';
        btn.disabled = true;
        btn.style.opacity = '0.7';

        // Create new abort controller
        currentFetchController = new AbortController();
        
        // Generate unique token for this request (prevents duplicate processing)
        const requestToken = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        // Ajax Call with abort signal
        fetch('?controller=request&action=reuse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                original_id: <?php echo $request['id']; ?>,
                selected_spv: spv,
                request_token: requestToken
            }),
            signal: currentFetchController.signal
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Clear abort controller since request completed successfully
                currentFetchController = null;
                // Redirect to Edit Page of NEW request
                window.location.href = '?controller=request&action=edit&id=' + data.new_id;
            } else {
                alert('Error: ' + data.error);
                btn.innerHTML = 'Create Draft';
                btn.disabled = false;
                btn.style.opacity = '1';
                currentFetchController = null;
            }
        })
        .catch(err => {
            // Don't show error if request was aborted (user cancelled)
            if (err.name === 'AbortError') {
                console.log('Request cancelled by user');
                return;
            }
            
            console.error(err);
            alert('Network Error');
            btn.innerHTML = 'Create Draft';
            btn.disabled = false;
            btn.style.opacity = '1';
            currentFetchController = null;
        });
    }
    
    // CRITICAL: Cancel request if user navigates away or switches tabs
    window.addEventListener('beforeunload', function(e) {
        if (currentFetchController) {
            currentFetchController.abort();
            currentFetchController = null;
        }
    });
    
    // CRITICAL: Cancel request if user switches tab/minimizes window
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && currentFetchController) {
            console.log('Page hidden - aborting pending request');
            currentFetchController.abort();
            currentFetchController = null;
            
            // Also close and reset modal
            const modal = document.getElementById('reuseModal');
            if (modal.style.display !== 'none') {
                closeReuseModal();
            }
        }
    });
    </script>
    </script>

    <!-- 2-COLUMN LAYOUT -->
    <div style="display:flex; gap:20px; align-items:flex-start;">
        
        <!-- LEFT COLUMN: MAIN CONTENT (75%) -->
        <div style="flex:1; min-width:0;">
             
             <!-- Metadata Card -->
            <div class="card" style="margin-bottom:20px; border-top: 4px solid #3b82f6;">
                <h4 style="margin:0 0 20px 0; color:#1e293b; font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Script Information & Metadata
                </h4>
                
                <div class="request-info-grid" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px;">
                    <!-- Row 1: Title across 4 columns -->
                    <div style="grid-column: span 4; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; display:flex; flex-direction:column; justify-content:center;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <strong style="color:#64748b; font-size:11px; letter-spacing:0.5px;">Title / Tujuan Script</strong>
                            <div style="display:flex; gap:8px;">
                                <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:11px;">v<?php echo htmlspecialchars($request['version'] ?? '1.0'); ?></span>
                            </div>
                        </div>
                        <div style="font-weight:700; font-size:16px; color:#0f172a; margin-top:5px; line-height:1.4;">
                            <?php echo htmlspecialchars($request['title'] ?? '-'); ?>
                        </div>
                    </div>

                    <!-- Row 2: Identifiers & Details -->
                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Script Number</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px; word-break:break-word;">
                            <?php echo htmlspecialchars($request['script_number'] ?? '-'); ?>
                        </div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Ticket ID</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                            <?php 
                                $tID = $request['ticket_id'];
                                if (is_numeric($tID)) $tID = sprintf("SC-%04d", $tID);
                                echo htmlspecialchars($tID ?? '-');
                            ?>
                        </div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Jenis</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;"><?php echo htmlspecialchars($request['jenis'] ?? '-'); ?></div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Produk</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;"><?php echo htmlspecialchars($request['produk'] ?? '-'); ?></div>
                    </div>

                    <!-- Row 3: Details Cont & Media -->
                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Kategori</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;"><?php echo htmlspecialchars($request['kategori'] ?? '-'); ?></div>
                    </div>

                    <div style="padding:5px; grid-column: span 3;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Media Channels</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                            <?php 
                                $medias = preg_split('/[,;]/', $request['media'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
                                echo htmlspecialchars(implode(', ', array_map('trim', $medias)));
                            ?>
                        </div>
                    </div>

                    <!-- Row 4: People -->
                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Maker</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                             <?php echo htmlspecialchars($request['maker_name'] ?? $request['created_by'] ?? '-'); ?>
                        </div>
                    </div>

                    <?php 
                       // [ENHANCEMENT] Extract Approver Names from Logs
                       $spvName = '-';
                       $picName = '-';
                       $procName = '-';
                       
                       foreach($logs as $log) {
                           if ($log['action'] === 'APPROVE_SPV') $spvName = $log['full_name'] ?? $log['user_id'];
                           if ($log['action'] === 'APPROVE_PIC') $picName = $log['full_name'] ?? $log['user_id'];
                           if ($log['action'] === 'APPROVE_PROCEDURE' || $log['action'] === 'LIBRARY_UPDATE') $procName = $log['full_name'] ?? $log['user_id'];
                       }
                    ?>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">SPV</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                             <?php echo htmlspecialchars($spvName); ?>
                        </div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">PIC</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                             <?php echo htmlspecialchars($picName); ?>
                        </div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Procedure</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;">
                             <?php echo htmlspecialchars($procName); ?>
                        </div>
                    </div>

                    <!-- Row 5: Dates & Activation Info -->
                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Created Date</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;"><?php 
                            if (isset($request['created_at'])) {
                                echo ($request['created_at'] instanceof DateTime) ? $request['created_at']->format('d M Y') : date('d M Y', strtotime($request['created_at']));
                            } else { echo "-"; }
                        ?></div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Published Date</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px;"><?php 
                            $pubDate = $request['updated_at'] ?? $request['created_at'];
                            if (isset($pubDate)) {
                                echo ($pubDate instanceof DateTime) ? $pubDate->format('d M Y') : date('d M Y', strtotime($pubDate));
                            } else { echo "-"; }
                        ?></div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Start Date</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px; display:flex; align-items:center; gap:8px;">
                            <?php 
                                if (isset($startDate) && !empty($isActive)) {
                                    echo ($startDate instanceof DateTime) ? $startDate->format('d M Y') : date('d M Y', strtotime($startDate));
                                } else { echo "-"; }
                            ?>
                            <!-- NEW: Status Badge moved here -->
                            <?php if (isset($isActive)): ?>
                                <span style="font-size:10px; padding:2px 6px; border-radius:12px; font-weight:600; border:1px solid currentColor; <?php echo $badgeStyle; ?> display:inline-flex; align-items:center;">
                                    <?php echo $badgeIcon . $badgeText; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="padding:5px;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600;">Activated By</div>
                        <div style="color:#334155; font-weight:700; margin-top:2px; font-size:12px;">
                            <?php if (!empty($activatorInfo) && $isActive): ?>
                                <?php
                                    $actName = htmlspecialchars($activatorInfo['fullname']);
                                    $actId = htmlspecialchars($activatorInfo['userid']);
                                    $actDept = strtoupper(trim($activatorInfo['dept'] ?? ''));
                                    if ($actDept === 'PROCEDURE' || $actDept === 'CPMS' || stripos($activatorInfo['divisi'] ?? '', 'Quality Analysis') !== false) {
                                        $actLabel = 'CPMS';
                                    } else {
                                        $actLabel = htmlspecialchars($activatorInfo['job_function'] ?: $actDept ?: 'User');
                                    }
                                ?>
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo $actName . ' (' . $actId . ')'; ?>">
                                    <?php echo $actName; ?>
                                </div>
                                <div style="color:#64748b; font-size:10px; font-weight:600; margin-top:2px;">
                                    <?php echo $actLabel; ?>
                                    <?php if (!empty($activatedAt)): ?>
                                        &bull; <?php echo ($activatedAt instanceof DateTime) ? $activatedAt->format('d M') : date('d M', strtotime($activatedAt)); ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                 </div>
             </div>

 

              <!-- Script Content -->
             <div class="card">
                 <div style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                     <h4 style="margin:0;">Script Content</h4>
                     
                     <?php if ( (!empty($scriptFile) && !empty($scriptFile['original_filename'])) || (!empty($content)) ): ?>
                     <div style="display:flex; gap:8px;">
                         <!-- Export Excel (Client-Side SheetJS) -->
                         <a href="javascript:void(0);" onclick="downloadContentAsExcel()" style="background:#16a34a; color:white; padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; display:flex; align-items:center; gap:6px; text-decoration:none;">
                             <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                             Export Excel
                         </a>
                         
                         <a href="?controller=audit&action=detail&id=<?php echo $request['id']; ?>" style="background:#8b5cf6; color:white; padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; display:flex; align-items:center; gap:6px; text-decoration:none;">
                             <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            View Revision History
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="library-content-readonly">
                <?php if ($isFileUpload): ?>
                    <!-- File Upload Mode -->
                    <?php 
                         // FIX: Normalize Stream Resources (SQL Server TEXT/NVARCHAR) to String
                         // This is critical for stripos checks to work correctly
                         foreach ($content as &$rRef) {
                             if (isset($rRef['content']) && is_resource($rRef['content'])) {
                                 $rRef['content'] = stream_get_contents($rRef['content']);
                             }
                         }
                         unset($rRef);

                         $hasPrebuiltTabs = false;
                         foreach ($content as $row) {
                             // FIX: Check for ANY sign of prebuilt tabs (legacy or new)
                             if (
                                 stripos($row['content'], 'sheet-tabs-nav') !== false || 
                                 stripos($row['content'], 'btn-sheet') !== false ||
                                 stripos($row['content'], 'btn-media-tab') !== false ||
                                 stripos($row['content'], 'media-pane') !== false
                             ) {
                                 $hasPrebuiltTabs = true;
                                 break;
                             }
                         }
                    ?>

                    <?php if ($hasPrebuiltTabs && isset($content[0])): ?>
                         <?php 
                            $readonly_content = str_replace(['contenteditable="true"', "contenteditable='true'"], '', $content[0]['content']);
                            echo $readonly_content; 
                         ?>
                    <?php else: ?>
                        <?php 
                        // NEW LOGIC: If multiple sheets, render TABS first
                        if (count($content) > 1) {
                            echo '<div style="display:flex; gap:10px; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; overflow-x:auto;">';
                            foreach ($content as $idx => $row) {
                                $active = ($idx === 0);
                                $mediaName = htmlspecialchars($row['media'] ?? 'Sheet '.($idx+1));
                                // Clean ID for switching
                                $cleanId = 'sheet-auto-' . $idx;
                                
                                $btnStyle = $active 
                                   ? "background: #3b82f6; color: white; border: none;"
                                   : "background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;";

                                echo "<button type='button' id='btn-sheet-$idx' class='btn-sheet-tab' 
                                       style='padding:8px 20px; border-radius:30px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s; white-space:nowrap; $btnStyle'
                                       onclick=\"changeSheet('$cleanId', $idx)\">$mediaName</button>";
                            }
                            echo '</div>';
                        }
                        ?>

                        <?php foreach ($content as $idx => $row): ?>
                            <?php 
                                $readonly_content = str_replace(['contenteditable="true"', "contenteditable='true'"], '', $row['content']);
                                
                                // Dynamic ID matching the tabs above
                                $mediaId = (count($content) > 1) ? 'sheet-auto-' . $idx : 'sheet-' . htmlspecialchars(preg_replace('/[^a-zA-Z0-9_-]/', '', $row['media'] ?? 'Sheet'));
                                
                                // Visibility logic
                                $displayStyle = (count($content) > 1 && $idx > 0) ? 'display:none;' : 'display:block;';
                                
                                // Class sheet-pane is CRITICAL for the JS toggle function
                            ?>
                            <div id="<?php echo $mediaId; ?>" class="sheet-pane downloadable-sheet" style="<?php echo $displayStyle; ?>" data-media="<?php echo htmlspecialchars($row['media'] ?? $mediaId); ?>">
                                <?php echo $readonly_content; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Free Input Mode -->
                    <?php if (!empty($content)): ?>
                        <div style="display:flex; gap:10px; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; overflow-x:auto;">
                            <?php foreach ($content as $idx => $row): ?>
                                <button 
                                    type="button"
                                    id="media-btn-<?php echo $idx; ?>"
                                    class="btn-media-tab-unified"
                                    onclick="switchMediaTab(<?php echo $idx; ?>)"
                                    style="padding:8px 20px; border-radius:30px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s; white-space:nowrap;
                                           <?php echo $idx === 0 ? 'background: #3b82f6; color: white; border: none;' : 'background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;'; ?>">
                                    <?php echo htmlspecialchars($row['media']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($content as $idx => $row): ?>
                            <div id="media-pane-<?php echo $idx; ?>" data-media="<?php echo htmlspecialchars($row['media']); ?>" class="media-tab-pane" style="display: <?php echo $idx === 0 ? 'block' : 'none'; ?>;">
                                <div style="padding:20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; color:#000;">
                                    <?php echo $row['content']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#888; font-style:italic;">No content available.</p>
                    <?php endif; ?>
                <?php endif; ?>
                </div> 
            </div>
        </div>

        <!-- RIGHT COLUMN: SIDEBAR (25%) -->
        <div style="width:300px; flex-shrink:0;">
            
            <!-- Review Documents -->
            <?php if (!empty($reviewDocs)): ?>
                <?php 
                $groupedDocs = [];
                foreach ($reviewDocs as $doc) {
                    $type = strtoupper($doc['file_type'] ?? 'OTHER');
                    if (!isset($groupedDocs[$type])) $groupedDocs[$type] = [];
                    $groupedDocs[$type][] = $doc;
                }
                ?>
            <div class="card" style="margin-bottom:20px;">
                <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                    Attachments & Evidence
                </h4>
                
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php 
                        $typeColors = [
                            'LEGAL' => ['#dc2626', '#fef2f2', '#fee2e2'],
                            'CX' => ['#2563eb', '#eff6ff', '#dbeafe'],
                            'SYARIAH' => ['#16a34a', '#f0fdf4', '#bbf7d0'],
                            'LPP' => ['#d97706', '#fffbeb', '#fef3c7']
                        ];
                        
                        foreach ($groupedDocs as $type => $docs): 
                            $colors = $typeColors[$type] ?? ['#64748b', '#f8fafc', '#e2e8f0'];
                    ?>
                        <div style="background:white; border:1px solid <?php echo $colors[2]; ?>; border-radius:10px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <!-- Category Header -->
                            <div style="background:<?php echo $colors[1]; ?>; padding:8px 12px; border-bottom:1px solid <?php echo $colors[2]; ?>; display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:24px; height:24px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; border:1px solid <?php echo $colors[2]; ?>;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="<?php echo $colors[0]; ?>" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    </div>
                                    <span style="font-weight:800; color:<?php echo $colors[0]; ?>; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;"><?php echo htmlspecialchars($type); ?></span>
                                </div>
                                <span style="background:<?php echo $colors[0]; ?>; color:white; padding:1px 6px; border-radius:10px; font-size:9px; font-weight:700;"><?php echo count($docs); ?></span>
                            </div>
                            
                            <!-- File List -->
                            <div style="padding:5px 0;">
                                <?php foreach ($docs as $doc): ?>
                                <div style="padding:8px 12px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid <?php echo $colors[1]; ?> last-child:border-bottom-none;">
                                    <div style="display:flex; align-items:center; gap:10px; overflow:hidden; flex:1;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                        <span style="font-size:11px; color:#334155; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($doc['original_filename']); ?>">
                                            <?php echo htmlspecialchars($doc['original_filename']); ?>
                                        </span>
                                    </div>
                                    <a href="?controller=request&action=downloadReviewDoc&file_id=<?php echo $doc['id']; ?>" download 
                                       style="color:<?php echo $colors[0]; ?>; text-decoration:none; padding:4px; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approval History -->
            <?php if (!empty($logs)): ?>
            <div class="card" style="margin-bottom:20px;">
                <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:20px; margin-top:0; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Request History
                </h4>
                
                <?php
                // [NEW] Build User Map for ID Replacement
                $userMap = [];
                if (isset($allUsers) && is_array($allUsers)) {
                    foreach($allUsers as $u) {
                        if (!empty($u['userid']) && !empty($u['fullname'])) {
                            $userMap[$u['userid']] = $u['fullname'];
                        }
                    }
                    // Sort by length desc to prevent partial replacements
                    uksort($userMap, function($a, $b) { return strlen($b) - strlen($a); });
                }
                ?>
                
                <div style="max-height:500px; overflow-y:auto; padding:10px 15px; position:relative;">
                    <!-- Vertical Line -->
                    <div style="position:absolute; left:24px; top:0; bottom:0; width:2px; background:#e5e7eb;"></div>
                    
                    <div style="position:relative;">
                        <?php foreach ($logs as $index => $log): 
                            // Skip draft-related entries (noise in Library view)
                            $rawAct = $log['action_type'] ?? $log['action'] ?? '';
                            if (in_array($rawAct, ['DRAFT_INIT', 'DRAFT_SAVED', 'DRAFT_STARTED'])) continue;
                            
                            $isFirst = ($index === 0);
                            $roleColor = '#6b7280';
                            if (($log['user_role'] ?? '') === 'MAKER') $roleColor = '#3b82f6';
                            if (($log['user_role'] ?? '') === 'SPV') $roleColor = '#f59e0b';
                            if (($log['user_role'] ?? '') === 'PIC') $roleColor = '#8b5cf6';
                            if (($log['user_role'] ?? '') === 'PROCEDURE' || ($log['user_role'] ?? '') === 'LIBRARIAN') $roleColor = '#10b981';

                            // Action Mapping
                            $rawAction = $log['action_type'] ?? $log['action'] ?? 'Status Update';
                            $actionMap = [
                                'CREATED' => 'Request Submitted',
                                'SUBMIT_REQUEST' => 'Request Submitted', // Handle legacy
                                'APPROVE_SPV' => 'Approved (SPV)',
                                'APPROVED_SPV' => 'Approved (SPV)',
                                'APPROVE_PIC' => 'Approved (PIC)',
                                'APPROVED_PIC' => 'Approved (PIC)',
                                'APPROVE_PROCEDURE' => 'Published to Library',
                                'APPROVED_PROCEDURE' => 'Published to Library',
                                'LIBRARY' => 'Published to Library',
                                'REVISION' => 'Revision Requested',
                                'MINOR_REVISION' => 'Minor Revision',
                                'MAJOR_REVISION' => 'Major Revision (Reset)',
                                'REJECTED' => 'Rejected',
                                'DRAFT_INIT' => 'Draft Started',
                                'DRAFT_SAVED' => 'Draft Saved',
                                'LIBRARY_UPDATE' => 'Library Updated'
                            ];
                            $displayAction = $actionMap[$rawAction] ?? ucwords(strtolower(str_replace('_', ' ', $rawAction)));
                        ?>
                        <div style="position:relative; padding-left:35px; padding-bottom:25px;">
                            <!-- Dot -->
                            <div style="position:absolute; left:0; top:4px; width:10px; height:10px; background:white; border-radius:50%; border:2px solid <?php echo $isFirst ? '#dc2626' : $roleColor; ?>; box-shadow:0 0 0 1px #e5e7eb; z-index:1;"></div>
                            
                            <!-- Content -->
                            <div>
                                <div style="font-weight:700; color:#1f2937; font-size:13px; margin-bottom:4px;">
                                    <?php echo htmlspecialchars($displayAction); ?>
                                </div>
                                <div style="font-size:12px; color:#666; display: flex; flex-direction: column; gap: 2px;">
                                    <div>
                                        by <span style="font-weight:600; color:<?php echo $roleColor; ?>"><?php echo htmlspecialchars($log['full_name'] ?? $log['user_id']); ?></span>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8;">
                                        <?php echo htmlspecialchars($log['job_function'] ?? $log['group_name'] ?? 'Unit'); ?>
                                    </div>
                                </div>
                                <div style="font-size:11px; color:#94a3b8; margin-top:4px;">
                                    <?php 
                                        $dt = $log['created_at']; 
                                        if ($dt instanceof DateTime) echo $dt->format('d M Y, H:i'); else echo date('d M Y, H:i', strtotime($dt));
                                    ?>
                                </div>
                                
                                <?php if (!empty($log['details'])): ?>
                                    <div style="background:#fef3c7; border-left:3px solid #f59e0b; padding:6px 10px; margin-top:6px; border-radius:4px; font-size:11px; color:#92400e;">
                                        <?php 
                                        $detailText = $log['details'];
                                        
                                        // 1. Handle Legacy/Generic Strings
                                        if ($detailText === 'Approved by Supervisor') $detailText = 'Approved by ' . ($log['full_name'] ?? $log['user_id']);
                                        elseif ($detailText === 'Approved by PIC') $detailText = 'Approved by ' . ($log['full_name'] ?? $log['user_id']);
                                        elseif ($detailText === 'Initial Submission') $detailText = 'Submitted by ' . ($log['full_name'] ?? $log['user_id']);
                                        elseif ($detailText === 'Published to Library') $detailText = 'Published by ' . ($log['full_name'] ?? $log['user_id']);
                                        elseif ($detailText === 'Re-submitted by Maker') $detailText = 'Re-submitted by ' . ($log['full_name'] ?? $log['user_id']);
                                        elseif ($detailText === 'Draft saved by Maker') $detailText = 'Draft saved by ' . ($log['full_name'] ?? $log['user_id']);
                                        
                                        // 2. [NEW] Replace IDs with Names in Historical Text
                                        if (!empty($userMap)) {
                                            foreach($userMap as $uid => $fname) {
                                                if (strpos($detailText, $uid) !== false) {
                                                    $detailText = str_replace($uid, $fname, $detailText);
                                                }
                                            }
                                        }
                                        
                                        echo htmlspecialchars($detailText); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div> <!-- End Sidebar -->

    </div> <!-- End Flex Container -->

</div>


<script>
    function toggleActiveStatus(requestId, newStatus) {
        if (newStatus === 1) {
            // ACTIVATE: Show Custom Date Picker Modal
            const defaultDate = '<?php echo !empty($startDate) ? date("Y-m-d", strtotime($startDate)) : date("Y-m-d"); ?>';
            
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.id = 'activate-modal-overlay';
            overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center; animation:fadeIn 0.2s;';
            
            overlay.innerHTML = `
                <div style="background:white; border-radius:16px; padding:30px; width:380px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; animation:scaleIn 0.25s ease-out;" onclick="event.stopPropagation()">
                    <div style="margin-bottom:20px;">
                        <i class="fi fi-rr-calendar-check" style="font-size:42px; color:#10b981;"></i>
                    </div>
                    <h3 style="margin:0 0 8px 0; font-size:20px; color:#1e293b; font-weight:700;">Activate Script</h3>
                    <p style="margin:0 0 20px 0; color:#64748b; font-size:13px;">Pilih Tanggal Mulai Berlaku (Start Date)</p>
                    
                    <div style="text-align:left; margin-bottom:24px;">
                        <input type="date" id="activate-date-input" value="${defaultDate}" 
                               style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; font-family:inherit; color:#334155; background:#f8fafc; box-sizing:border-box; outline:none; transition:border-color 0.2s;"
                               onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                        <div id="activate-date-error" style="color:#ef4444; font-size:12px; margin-top:6px; display:none;">⚠️ Tanggal harus diisi!</div>
                    </div>
                    
                    <div style="display:flex; gap:10px; justify-content:center;">
                        <button onclick="closeActivateModal()" 
                                style="background:#f1f5f9; color:#64748b; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;"
                                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            Batal
                        </button>
                        <button onclick="confirmActivate(${requestId})" 
                                style="background:#10b981; color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s;"
                                onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            <i class="fi fi-rr-check" style="font-size:13px;"></i> Activate
                        </button>
                    </div>
                </div>
            `;
            
            // Close on backdrop click
            overlay.addEventListener('click', closeActivateModal);
            document.body.appendChild(overlay);
            
            // Focus the date input
            setTimeout(() => document.getElementById('activate-date-input').focus(), 100);
            
        } else {
            // DEACTIVATE: Custom styled confirm modal
            const overlay = document.createElement('div');
            overlay.id = 'activate-modal-overlay';
            overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center;';
            
            overlay.innerHTML = `
                <div style="background:white; border-radius:16px; padding:30px; width:380px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;" onclick="event.stopPropagation()">
                    <div style="margin-bottom:20px;">
                        <i class="fi fi-rr-cross-circle" style="font-size:42px; color:#ef4444;"></i>
                    </div>
                    <h3 style="margin:0 0 8px 0; font-size:20px; color:#1e293b; font-weight:700;">Deactivate Script?</h3>
                    <p style="margin:0 0 24px 0; color:#64748b; font-size:13px; line-height:1.5;">Script akan menjadi tidak aktif dan tidak tampil di pencarian Library.</p>
                    
                    <div style="display:flex; gap:10px; justify-content:center;">
                        <button onclick="closeActivateModal()" 
                                style="background:#f1f5f9; color:#64748b; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;"
                                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            Batal
                        </button>
                        <button onclick="closeActivateModal(); performToggle(${requestId}, 0, null);" 
                                style="background:#ef4444; color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s;"
                                onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                            <i class="fi fi-rr-cross-circle" style="font-size:13px;"></i> Deactivate
                        </button>
                    </div>
                </div>
            `;
            
            overlay.addEventListener('click', closeActivateModal);
            document.body.appendChild(overlay);
        }
    }
    
    function closeActivateModal() {
        const overlay = document.getElementById('activate-modal-overlay');
        if (overlay) overlay.remove();
    }
    
    function confirmActivate(requestId) {
        const dateInput = document.getElementById('activate-date-input');
        const errorDiv = document.getElementById('activate-date-error');
        
        if (!dateInput.value) {
            errorDiv.style.display = 'block';
            dateInput.style.borderColor = '#ef4444';
            return;
        }
        
        closeActivateModal();
        performToggle(requestId, 1, dateInput.value);
    }

    async function performToggle(requestId, newStatus, startDate) {
        try {
            const body = { request_id: requestId, is_active: newStatus };
            if (startDate) body.start_date = startDate;
            
            const res = await fetch('index.php?controller=request&action=toggle_active', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(body)
            });
            const data = await res.json();
            
            if (data.success) {
                const msg = newStatus ? 'Script berhasil diaktifkan!' : 'Script berhasil dinonaktifkan.';
                
                // Show Custom Success Modal
                const overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center; animation:fadeIn 0.2s;';
                
                overlay.innerHTML = `
                    <div style="background:white; border-radius:16px; padding:30px; width:340px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; animation:scaleIn 0.25s ease-out;">
                        <div style="margin-bottom:20px;">
                            <i class="fi fi-rr-check-circle" style="font-size:48px; color:#10b981;"></i>
                        </div>
                        <h3 style="margin:0 0 10px 0; font-size:20px; color:#1e293b; font-weight:700;">Sukses!</h3>
                        <p style="margin:0 0 24px 0; color:#64748b; font-size:14px;">${msg}</p>
                        <button onclick="location.reload()" 
                                style="background:#10b981; color:white; border:none; padding:10px 30px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s; width:100%;"
                                onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            OK
                        </button>
                    </div>
                `;
                document.body.appendChild(overlay);
                
            } else {
                alert('Failed: ' + (data.error || 'Unknown error'));
            }
        } catch (e) {
            console.error(e);
            alert('System Error');
        }
    }
</script>
<?php require_once 'app/views/layouts/footer.php'; ?>
