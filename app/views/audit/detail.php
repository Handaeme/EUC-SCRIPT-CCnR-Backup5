<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>
<!-- SheetJS with Style Support for Excel Export (Red Font for Revisions) -->
<!-- SheetJS with Style Support for Excel Export (Red Font for Revisions) -->
<!-- We must use xlsx-js-style directly because standard xlsx.full.min.js does not support styling -->
<script src="public/js/xlsx.bundle.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';"></script>
<script src="public/assets/js/exceljs.min.js"></script>

<?php
$req = $data['request'];
$logs = $data['logs'];
$files = $data['files'];
$content = $data['content'];

// Format Ticket ID
// Format Ticket ID
$ticketId = isset($req['ticket_id']) ? $req['ticket_id'] : null;
if (is_numeric($ticketId)) {
    $ticketId = sprintf("SC-%04d", $ticketId);
}

// Status Color Logic
$statusColor = '#6b7280'; // Gray (default)
if ($req['status'] === 'CLOSED') $statusColor = '#16a34a'; // Green
else if ($req['status'] === 'WIP') $statusColor = '#f59e0b'; // Orange
else if ($req['status'] === 'REJECTED') $statusColor = '#dc2626'; // Red
else if ($req['status'] === 'CREATED' || $req['status'] === 'SUBMITTED') $statusColor = '#6b7280'; // Gray
?>

<style>
    /* Scoped Styles for Audit Detail */
    .detail-container {
        padding: 20px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 340px; /* Fluid Content + Fixed Sidebar */
        gap: 24px;
        align-items: start;
    }

    /* Content & Panel Columns */
    .content-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0; /* Prevents flex/grid blowout */
    }

    .panel-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 100%; /* Stack vertically */
        }
        .panel-column {
            order: -1; /* Optional: Show metadata/history first on mobile? Or keep below. Let's keep below for now. */
            order: 1; 
        }
    }

    /* Excel Tab Styles - Global */
    .sheet-tabs-nav { 
        background: #f8fafc; 
        padding: 8px; 
        border-bottom: 1px solid #e2e8f0; 
        display: flex; 
        gap: 6px; 
        overflow-x: auto;
    }
    .btn-sheet { 
        padding: 8px 16px; 
        border: 1px solid #e2e8f0; 
        background: #fff; 
        cursor: pointer; 
        border-radius: 8px; 
        font-size: 13px; 
        font-weight: 600; 
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .btn-sheet:hover {
        background: #f1f5f9;
        color: #334155;
    }
    .btn-sheet.active { 
        background: #3b82f6; 
        color: white; 
        border-color: #3b82f6; 
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }
    .sheet-container { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .excel-preview { width:100%; border-collapse:collapse; font-size:13px; }
    .excel-preview td { border:1px solid #ddd; padding:8px; min-width:50px; }

    /* Inline Comment Style */
    .inline-comment {
        background-color: #fef08a !important;
        cursor: pointer;
        border-bottom: 2px solid #eab308;
        transition: background-color 0.3s;
    }
    .inline-comment:hover {
        background-color: #fde047 !important;
    }

    /* Blink Animation */
    @keyframes blink-animation {
        0% { outline: 3px solid #eab308; box-shadow: 0 0 10px rgba(234, 179, 8, 0.5); transform: scale(1.02); z-index: 10; position: relative; }
        50% { outline: 3px solid #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.7); }
        100% { outline: 3px solid transparent; box-shadow: none; transform: scale(1); z-index: auto; position: static; }
    }
    .blink-highlight {
        animation: blink-animation 1.5s ease-out forwards;
    }
</style>

<div class="main detail-container">
    <!-- Breadcrumb & Header -->
    <div style="margin-bottom: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <!-- Simplified back button (Circle SVG version) -->
                <a href="?controller=audit" style="background: rgb(241, 245, 249); color: rgb(100, 116, 139); padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#334155'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'" title="Back to Audit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div>
                    <h2 style="color:var(--primary-red); margin:0;">Audit Trail Detail</h2>
                    <p style="color:#64748b; margin:0; font-size:13px;">Review detailed history and audit logs for this request.</p>
                </div>
            </div>
            
            <div style="text-align:right;">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#888; font-weight:700; margin-bottom:2px;">Created Date</div>
                <div style="font-weight:700; color:#334155; font-size:14px;">
                    <?php 
                    $date = $req['created_at'];
                    echo ($date instanceof DateTime) ? $date->format('d M Y, H:i') : date('d M Y, H:i', strtotime($date)); 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="detail-grid">
        
        <!-- LEFT CONTENT COLUMN -->
        <div class="content-column">
            
            <!-- Metadata Card (Grid inside Grid) -->
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">Request Information</h4>
                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px 24px; font-size:13px;">
                    <!-- Row 1: Primary Identification -->
                    <div>
                         <div style="color:#64748b; font-size:11px; font-weight:600; margin-bottom:4px;">Status</div>
                         <div>
                            <span style="font-size:11px; background:<?php echo $statusColor; ?>; color:white; padding:2px 8px; border-radius:12px; font-weight:600; display:inline-block;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div style="color:#dc2626; font-size:11px; font-weight:600; margin-bottom:4px;">Ticket ID</div>
                        <div style="color:#dc2626; font-weight:600; font-size:15px;"><?php echo htmlspecialchars(!empty($ticketId) ? $ticketId : 'Pending'); ?></div>
                    </div>
                    <div style="grid-column: span 2;">
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Script Number</div>
                        <div style="color:#334155; font-weight:600; font-family:monospace; font-size:13px;"><?php echo htmlspecialchars($req['script_number']); ?></div>
                    </div>

                    <!-- Row 2: Purpose (Full Width) -->
                    <div style="grid-column: span 4; background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #f1f5f9;">
                        <div style="color:#64748b; font-size:11px; font-weight:600; margin-bottom:4px;">Purpose / Title</div>
                        <div style="color:#0f172a; font-weight:700; font-size:15px;"><?php echo htmlspecialchars($req['title'] ?? '-'); ?></div>
                    </div>

                     <!-- Row 3: Roles -->
                     <?php 
                        // Extract Approver Names from Logs
                        $spvDisplay = '-';
                        $picDisplay = '-';
                        $procDisplay = '-';
                        
                        foreach($logs as $log) {
                            // Helper to format: "Name (UserID)"
                            $fullName = $log['full_name'] ?? '';
                            $userId = $log['user_id'] ?? '';
                            $display = $fullName ? "$fullName ($userId)" : $userId;

                            if ($log['action'] === 'APPROVE_SPV') $spvDisplay = $display;
                            if ($log['action'] === 'APPROVE_PIC') $picDisplay = $display;
                            if ($log['action'] === 'APPROVE_PROCEDURE' || $log['action'] === 'LIBRARY_UPDATE') $procDisplay = $display;
                        }

                        // Fallback: Show assigned names if not yet approved
                        if ($spvDisplay === '-' && !empty($req['selected_spv'])) {
                            // If name available, use "Name (ID)", else just "ID (Assigned)"
                            $spvName = $req['selected_spv_name'] ?? '';
                            $spvId = $req['selected_spv'];
                            $spvDisplay = ($spvName ? "$spvName ($spvId)" : $spvId) . ' <span style="color:#94a3b8; font-size:11px;">(Assigned)</span>';
                        }
                        if ($picDisplay === '-' && !empty($req['selected_pic'])) {
                            $picName = $req['selected_pic_name'] ?? '';
                            $picId = $req['selected_pic'];
                            $picDisplay = ($picName ? "$picName ($picId)" : $picId) . ' <span style="color:#94a3b8; font-size:11px;">(Assigned)</span>';
                        }

                        // Maker Display
                        $makerName = $req['maker_name'] ?? '';
                        $makerId = $req['created_by'] ?? '';
                        $makerDisplay = $makerName ? "$makerName ($makerId)" : $makerId;
                     ?>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Maker</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo $makerDisplay; ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">SPV</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo $spvDisplay; ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">PIC</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo $picDisplay; ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Procedure</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo $procDisplay; ?></div>
                    </div>

                    <!-- Row 4: Attributes -->


                    <!-- Row 5: System -->


                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Input Mode</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;">
                            <?php 
                            $mode = $req['mode'] ?? 'FREE_INPUT';
                            echo $mode === 'FILE_UPLOAD' ? 'File Upload' : 'Free Input';
                            ?>
                        </div>
                    </div>
                    
                    <!-- Row 4 -->
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Jenis</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo htmlspecialchars($req['jenis']); ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Produk</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo htmlspecialchars($req['produk']); ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Media</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo htmlspecialchars($req['media']); ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Kategori</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;"><?php echo htmlspecialchars($req['kategori']); ?></div>
                    </div>

                    <!-- Row 5: System Dates (Moved to Bottom) -->
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Created Date</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;">
                            <?php 
                            $date = $req['created_at'];
                            echo ($date instanceof DateTime) ? $date->format('d M Y, H:i') : date('d M Y, H:i', strtotime($date)); 
                            ?>
                        </div>
                    </div>
                    <div>
                        <div style="color:#94a3b8; font-size:11px; font-weight:600; margin-bottom:4px;">Last Updated</div>
                        <div style="color:#334155; font-weight:600; font-size:14px;">
                            <?php 
                            $updated = $req['updated_at'] ?? $req['created_at'];
                            echo ($updated instanceof DateTime) ? $updated->format('d M Y, H:i') : date('d M Y, H:i', strtotime($updated)); 
                            ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Content Card -->
            <div class="card" style="flex:1; box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="margin:0; color:#444; font-size:15px; font-weight:700;">Script Content</h4>
                    
                    <!-- EXPORT EXCEL BUTTON (Server-Side: Per-Reviewer Breakdown) -->
                    <?php 
                    $totalVers = !empty($content['versions']) ? count($content['versions']) : 0;
                    $latestVerNum = $totalVers; // Default to latest version
                    ?>
                    <button id="exportExcelBtn" onclick="downloadAuditDetailExcel()" style="background:#0f766e; color:white; border:none; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background 0.2s;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export Excel
                    </button>
                </div>
                
                <?php if (($req['mode'] ?? '') === 'FILE_UPLOAD'): ?>
                    <!-- File Preview - Version Timeline -->
                    <?php include 'app/views/audit/_version_timeline.php'; ?>
                <?php else: ?>
                    <!-- Free Input Display (Tabbed) -->
                    <div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; min-height:450px;">
                        
                        <?php if (empty($content['data'])): ?>
                            <div style="color:#9ca3af; font-style:italic; text-align:center; padding:40px;">(No Content Available)</div>
                        <?php else: ?>
                            
                            <!-- 1. Tabs Header -->
                            <div class="sheet-tabs-nav" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">
                                <?php foreach ($content['data'] as $index => $item): ?>
                                    <?php 
                                    $isActive = ($index === 0) ? 'active' : '';
                                    $mediaType = htmlspecialchars($item['media']);
                                    $uniqueId = 'tab-btn-' . $index;
                                    $sheetId = 'sheet-free-' . $index;
                                    ?>
                                    <button 
                                        id="<?php echo $uniqueId; ?>"
                                        class="btn-sheet <?php echo $isActive; ?>" 
                                        onclick="changeSheet('<?php echo $sheetId; ?>')"
                                        style="display:flex; align-items:center; gap:6px;">
                                        
                                        <!-- Icon Logic based on Media -->
                                        <?php if(stripos($mediaType, 'WHATSAPP') !== false): ?>
                                            <i class="bi-whatsapp" style="font-size:11px;"></i>
                                        <?php elseif(stripos($mediaType, 'EMAIL') !== false): ?>
                                            <i class="bi-envelope" style="font-size:11px;"></i>
                                        <?php elseif(stripos($mediaType, 'SMS') !== false): ?>
                                            <i class="bi-chat-dots" style="font-size:11px;"></i>
                                        <?php else: ?>
                                            <i class="bi-file-text" style="font-size:11px;"></i>
                                        <?php endif; ?>

                                        <?php echo $mediaType; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 2. Content Panes -->
                            <div class="sheet-container" id="audit-editor-container" style="padding:0;">
                                <?php foreach ($content['data'] as $index => $item): ?>
                                    <?php 
                                    $displayStyle = ($index === 0) ? 'block' : 'none';
                                    $sheetId = 'sheet-free-' . $index;
                                    ?>
                                    <div id="<?php echo $sheetId; ?>" class="sheet-pane free-input-pane" data-media="<?php echo htmlspecialchars($item['media']); ?>" style="display:<?php echo $displayStyle; ?>; padding:20px;">
                                        <div style="font-family:'Inter', system-ui, -apple-system, sans-serif; white-space:pre-line; font-size:13px; color:#333; line-height:1.6; background:white;" contenteditable="false"><?php echo trim($item['content']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT PANEL COLUMN -->
        <div class="panel-column">
            
            <!-- Attachments Card -->
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="color:#444; font-size:15px; margin:0; font-weight:700;">Attachments</h4>
                </div>
                
                <?php 
                $hasDocs = false;
                $docTypes = ['LEGAL' => 'Legal Review', 'CX' => 'CX Review', 'LEGAL_SYARIAH' => 'Legal Syariah', 'LPP' => 'Checklist LPP'];
                ?>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php foreach ($docTypes as $type => $label): ?>
                        <?php if (isset($files[$type])): $hasDocs = true; ?>
                            <li style="margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; background:#fafafa; padding:12px; border-radius:6px; border:1px solid #eee; transition:background 0.2s;">
                                <div style="overflow:hidden; flex:1; margin-right:10px;">
                                    <div style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px; font-weight:600;"><?php echo $label; ?></div>
                                    <div style="font-weight:600; font-size:13px; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($files[$type]['original_filename'] ?? $files[$type]['filename'] ?? 'Attached File'); ?>">
                                        <?php echo htmlspecialchars($files[$type]['original_filename'] ?? $files[$type]['filename'] ?? 'Attached File'); ?>
                                    </div>
                                </div>
                                <a href="?controller=request&action=download&file=<?php echo $type; ?>&id=<?php echo $req['id']; ?>" class="btn-icon" style="color:#3b82f6; padding:6px; border-radius:4px; background:#eff6ff;">
                                    ⬇
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php if (!$hasDocs): ?>
                    <div style="text-align:center; padding:25px 10px; color:#9ca3af; font-size:13px; font-style:italic; background:#fafafa; border-radius:6px; border:1px dashed #eee;">
                        No documents attached.
                    </div>
                <?php endif; ?>
            </div>

            
            <!-- EX-COMMENT SIDEBAR (Review Notes) -->
            <!-- Hidden by default, shown by JS if comments exist -->
            <div id="comment-sidebar" class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee; display:none; max-height:600px; overflow-y:auto;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">
                    Review Notes
                    <span style="font-size:11px; color:#ef4444; background:#fef2f2; padding:2px 8px; border-radius:12px; margin-left:8px; border:1px solid #fecaca;">Action Required</span>
                </h4>
                <!-- General remarks removed (redundant with timeline) -->
                <!-- Divider if both exist -->
                <div id="remarks-divider" style="border-top:1px dashed #eee; margin:15px 0; display:none;"></div>
                
                <div id="comment-list"></div>
            </div>

            <!-- Original File Card -->
            <?php if (($req['mode'] ?? '') === 'FILE_UPLOAD' && isset($content['filename'])): ?>
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="color:#444; font-size:15px; margin:0; font-weight:700;">Original File</h4>
                </div>
                
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px; background:#fafafa; padding:12px; border-radius:6px; border:1px solid #e5e7eb;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                            <rect x="4" y="2" width="12" height="20" rx="2" fill="#10b981" opacity="0.1"/>
                            <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2V8H20" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 13H15" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M9 17H15" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:12px; color:#111; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($content['filename']); ?>">
                                <?php echo htmlspecialchars($content['filename']); ?>
                            </div>
                            <div style="font-size:10px; color:#888; margin-top:2px;">Excel File</div>
                        </div>
                    </div>
                    <a href="?controller=request&action=download&file=TEMPLATE&id=<?php echo $req['id']; ?>&source=library" style="background:#dc2626; color:white; padding:10px 16px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:background 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Download File
                    </a>
                </div>
            </div>
            <?php endif; ?>
            


            <!-- Approval History Card -->
            <div class="card" style="flex:1; box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="color:#444; font-size:15px; font-weight:700; margin:0;">Timeline</h4>
                    <button id="timeline-sort-btn" onclick="toggleTimelineSort()" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:4px 10px; font-size:11px; font-weight:600; color:#64748b; cursor:pointer; display:flex; align-items:center; gap:4px; transition:all 0.2s;" title="Toggle sort order">
                        <svg id="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
                        <span id="sort-label">Newest First</span>
                    </button>
                </div>
                
                <div class="timeline-wrapper" style="max-height: 600px; overflow-y: auto; overflow-x: hidden; padding-left: 10px; padding-right: 5px;">
                    <div class="timeline" id="timeline-container" style="border-left:2px solid #e5e7eb; margin-left:35px; padding-left:24px; padding-bottom:10px; position:relative;">
                    <?php 
                    // Default: Newest First
                    $sortedLogs = array_reverse($logs);
                    foreach ($sortedLogs as $log): 
                        // Skip draft-related entries (noise)
                        $rawAct0 = $log['action_type'] ?? $log['action'] ?? '';
                        if (in_array($rawAct0, ['DRAFT_INIT', 'DRAFT_SAVED', 'DRAFT_STARTED'])) continue;

                        $roleColor = '#6b7280';
                        if ($log['user_role'] === 'MAKER') $roleColor = '#3b82f6';
                        if ($log['user_role'] === 'SPV') $roleColor = '#f59e0b';
                        if ($log['user_role'] === 'PIC') $roleColor = '#8b5cf6';
                        if ($log['user_role'] === 'PROCEDURE' || $log['user_role'] === 'LIBRARIAN') $roleColor = '#10b981';

                        // Action Mapping
                        $rawAction = $log['action_type'] ?? $log['action'] ?? 'Status Update';
                        $actionMap = [
                            'CREATED' => 'Request Submitted',
                            'SUBMIT_REQUEST' => 'Request Submitted',
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
                            'DELETED' => 'Deleted',
                            'CANCELLED' => 'Cancelled',
                            'SEND_TO_MAKER_CONFIRMATION' => 'Sent to Maker for Confirmation',
                            'MAKER_CONFIRM' => 'Maker Confirmed',
                            'MAKER_REJECT_CONFIRMATION' => 'Maker Rejected Confirmation',
                            'DRAFT_INIT' => 'Draft Started',
                            'DRAFT_SAVED' => 'Draft Saved',
                            'LIBRARY_UPDATE' => 'Library Updated',
                            'REUSE_SCRIPT' => 'Reuse Script (dari Library)',
                            'REVISION_DRAFT_CREATED' => 'Revision Draft Created',
                            'ACTIVATED' => 'Activated',
                            'DEACTIVATED' => 'Deactivated',
                        ];
                        
                        $displayAction = $actionMap[$rawAction] ?? ucwords(strtolower(str_replace('_', ' ', $rawAction)));
                        
                        // [FIX] Auto-detect resubmit origin
                        if ($rawAction === 'RESUBMIT' || $rawAction === 'SUBMIT_REQUEST' || $rawAction === 'CREATED') {
                            // Check if this is a resubmit (not the first submission)
                            $isResubmit = false;
                            $resubmitOrigin = '';
                            
                            // Look backwards through ALL logs (unsorted) for a revision action that happened BEFORE this log
                            $thisDate = $log['created_at'];
                            $thisTs = ($thisDate instanceof \DateTime) ? $thisDate->getTimestamp() : strtotime($thisDate);
                            
                            foreach ($logs as $prevLog) {
                                $prevDate = $prevLog['created_at'];
                                $prevTs = ($prevDate instanceof \DateTime) ? $prevDate->getTimestamp() : strtotime($prevDate);
                                
                                if ($prevTs >= $thisTs) continue; // Only look at earlier entries
                                
                                $prevAction = $prevLog['action_type'] ?? $prevLog['action'] ?? '';
                                $prevBy = $prevLog['full_name'] ?? $prevLog['user_id'] ?? '';
                                
                                if (in_array($prevAction, ['REVISION', 'MINOR_REVISION', 'MAJOR_REVISION', 'MAKER_REJECT_CONFIRMATION', 'REJECTED'])) {
                                    $isResubmit = true;
                                    $originMap = [
                                        'REVISION' => 'Revision',
                                        'MINOR_REVISION' => 'Minor Revision',
                                        'MAJOR_REVISION' => 'Major Revision',
                                        'MAKER_REJECT_CONFIRMATION' => 'Maker Rejection',
                                        'REJECTED' => 'Rejection',
                                    ];
                                    $resubmitOrigin = ($originMap[$prevAction] ?? $prevAction) . ' by ' . $prevBy;
                                    // Don't break — keep searching for the LATEST revision before this resubmit
                                }
                            }
                            
                            if ($isResubmit && $rawAction !== 'CREATED') {
                                $displayAction = 'Re-submitted';
                                if ($resubmitOrigin) $displayAction .= ' (dari ' . $resubmitOrigin . ')';
                            } elseif ($isResubmit && $rawAction === 'CREATED') {
                                // CREATED after a revision = resubmit
                                $displayAction = 'Re-submitted';
                                if ($resubmitOrigin) $displayAction .= ' (dari ' . $resubmitOrigin . ')';
                            }
                        }
                        
                        // [FIX] Override dot color based on action type (more informative than role alone)
                        if (strpos($rawAction, 'REJECT') !== false || $rawAction === 'DELETED' || $rawAction === 'CANCELLED') {
                            $roleColor = '#dc2626'; // Red
                        } elseif ($rawAction === 'REVISION' || $rawAction === 'MINOR_REVISION' || $rawAction === 'MAJOR_REVISION') {
                            $roleColor = '#dc2626'; // Red for revision requests
                        } elseif ($rawAction === 'ACTIVATED') {
                            $roleColor = '#f59e0b'; // Orange/Amber
                        } elseif ($rawAction === 'DEACTIVATED') {
                            $roleColor = '#6b7280'; // Gray
                        } elseif ($rawAction === 'REUSE_SCRIPT' || $rawAction === 'REVISION_DRAFT_CREATED') {
                            $roleColor = '#0ea5e9'; // Sky blue
                        }
                    ?>
                    <div style="position:relative; margin-bottom:28px;">
                        <!-- Dot -->
                        <div style="position:absolute; left:-31px; top:4px; width:10px; height:10px; border-radius:50%; background:white; border:2px solid <?php echo $roleColor; ?>; box-shadow:0 0 0 2px white;"></div>
                        
                        <div style="font-size:11px; color:#9ca3af; margin-bottom:3px; font-family:var(--font-family, sans-serif);">
                            <?php 
                            $logDate = $log['created_at'];
                            echo ($logDate instanceof DateTime) ? $logDate->format('d M Y, H:i') : date('d M Y, H:i', strtotime($logDate)); 
                            ?>
                        </div>
                        <div style="font-weight:700; color:#333; font-size:13px; margin-bottom:3px; line-height:1.4;">
                            <?php echo htmlspecialchars($displayAction); ?>
                        </div>
                        <?php 
                        // Show script number for version-related actions
                        $versionActions = ['REUSE_SCRIPT', 'REVISION_DRAFT_CREATED', 'MINOR_REVISION', 'MAJOR_REVISION', 'LIBRARY_UPDATE', 'LIBRARY'];
                        if (in_array($rawAction, $versionActions)):
                            $logScriptNum = $log['req_script_number'] ?? $log['script_number'] ?? '';
                            if ($logScriptNum):
                        ?>
                            <div style="font-size:11px; color:#0ea5e9; font-weight:600; margin-bottom:3px; display:inline-flex; align-items:center; gap:4px;">
                                → <?php echo htmlspecialchars($logScriptNum); ?>
                            </div>
                        <?php endif; endif; ?>
                        <div style="font-size:12px; color:#666; display: flex; flex-direction: column; gap: 2px;">
                            <div>
                                by <span style="font-weight:600; color:<?php echo $roleColor; ?>">
                                    <?php 
                                        $fName = strtoupper($log['full_name'] ?? '');
                                        $uId = strtoupper($log['user_id'] ?? '');
                                        echo htmlspecialchars($fName ? "$fName ($uId)" : $uId); 
                                    ?>
                                </span>
                            </div>
                            <div style="font-size: 11px; color: #94a3b8;">
                                <?php 
                                    $dispRole = strtoupper($log['user_role'] ?? '');
                                    if ($dispRole === 'MAKER') $dispRole = 'DEPT HEAD';
                                    elseif ($dispRole === 'SPV') $dispRole = 'DIV HEAD';
                                    elseif ($dispRole === 'PIC') $dispRole = 'COORDINATOR SCRIPT';
                                    elseif ($dispRole === 'PROCEDURE') $dispRole = 'CPMS';
                                    elseif (empty($dispRole)) $dispRole = strtoupper($log['job_function'] ?? $log['group_name'] ?? 'UNIT');
                                    
                                    echo htmlspecialchars($dispRole);
                                ?>
                            </div>
                        </div>
                        <?php if (!empty($log['details'])): 
                            // Color-code detail box based on action type
                            $detailBg = '#fffbeb'; $detailBorder = '#fcd34d'; $detailColor = '#92400e'; // Default: amber
                            if (strpos($rawAction, 'REJECT') !== false) { $detailBg = '#fef2f2'; $detailBorder = '#fca5a5'; $detailColor = '#991b1b'; }
                            elseif (strpos($rawAction, 'DELETE') !== false || strpos($rawAction, 'CANCEL') !== false) { $detailBg = '#fef2f2'; $detailBorder = '#fca5a5'; $detailColor = '#991b1b'; }
                            elseif (strpos($rawAction, 'APPROVE') !== false || $rawAction === 'LIBRARY') { $detailBg = '#f0fdf4'; $detailBorder = '#86efac'; $detailColor = '#166534'; }
                            elseif (strpos($rawAction, 'CREATED') !== false || strpos($rawAction, 'SUBMIT') !== false) { $detailBg = '#eff6ff'; $detailBorder = '#93c5fd'; $detailColor = '#1e40af'; }
                        ?>
                            <div style="margin-top:8px; background:<?php echo $detailBg; ?>; padding:10px; border-radius:6px; font-size:12px; border:1px solid <?php echo $detailBorder; ?>; color:<?php echo $detailColor; ?>; line-height:1.5;">
                                <?php 
                                $detailText = $log['details'];
                                // Logic: If it's a generic legacy string, inject the username
                                if ($detailText === 'Approved by Supervisor') $detailText = 'Approved by ' . ($log['full_name'] ?? $log['user_id']);
                                elseif ($detailText === 'Approved by PIC') $detailText = 'Approved by ' . ($log['full_name'] ?? $log['user_id']);
                                elseif ($detailText === 'Initial Submission') $detailText = 'Submitted by ' . ($log['full_name'] ?? $log['user_id']);
                                elseif ($detailText === 'Published to Library') $detailText = 'Published by ' . ($log['full_name'] ?? $log['user_id']);
                                elseif ($detailText === 'Re-submitted by Maker') $detailText = 'Re-submitted by ' . ($log['full_name'] ?? $log['user_id']);
                                elseif ($detailText === 'Draft saved by Maker') $detailText = 'Draft saved by ' . ($log['full_name'] ?? $log['user_id']);
                                
                                // FIX: Use htmlspecialchars to preserve <Nama> and other <Tags> inside review notes
                                echo htmlspecialchars(trim($detailText), ENT_QUOTES | ENT_HTML401); 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Start/End Point -->
                    <div class="timeline-anchor" style="position:relative;">
                        <div style="position:absolute; left:-30px; top:5px; width:8px; height:8px; border-radius:50%; background:#d1d5db;"></div>
                        <div style="font-size:12px; color:#9ca3af; font-style:italic;">Created</div>
                    </div>
                </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
// Timeline Sort Toggle
let timelineSortNewest = true; // Default: newest first
function toggleTimelineSort() {
    const container = document.getElementById('timeline-container');
    if (!container) return;
    
    // Get all timeline entries (skip the anchor "Created" div)
    const items = Array.from(container.children);
    const anchor = container.querySelector('.timeline-anchor');
    
    // Reverse all items
    items.reverse();
    items.forEach(item => container.appendChild(item));
    
    // Toggle state
    timelineSortNewest = !timelineSortNewest;
    
    // Update button label & icon
    const label = document.getElementById('sort-label');
    const icon = document.getElementById('sort-icon');
    if (label) label.textContent = timelineSortNewest ? 'Newest First' : 'Oldest First';
    if (icon) {
        icon.innerHTML = timelineSortNewest 
            ? '<path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/>'   // Arrow down (newest)
            : '<path d="M12 19V5"/><path d="M5 12l7-7 7 7"/>';    // Arrow up (oldest)
    }
    
    // Button hover feedback
    const btn = document.getElementById('timeline-sort-btn');
    if (btn) {
        btn.style.background = '#eff6ff';
        btn.style.borderColor = '#93c5fd';
        setTimeout(() => { btn.style.background = '#f8fafc'; btn.style.borderColor = '#e2e8f0'; }, 300);
    }
}

// Version Timeline Toggle Function
function toggleVersionContent(idx) {
    const content = document.getElementById('version-content-' + idx);
    const icon = document.getElementById('icon-' + idx);
    const header = content.previousElementSibling;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');
        header.style.background = '#f3f4f6';
    } else {
        content.style.display = 'none';
        icon.classList.remove('bi-chevron-up');
        icon.classList.add('bi-chevron-down');
        header.style.background = '#f9fafb';
    }
    
    // Re-render side comments if they exist in this version
    if (typeof renderSideComments === 'function') {
        setTimeout(() => renderSideComments(), 100);
    }
}

// Global Excel Tab Switching Function
// Global Excel Tab Switching Function (Robust)
function changeSheet(sheetId) {
    const selectedSheet = document.getElementById(sheetId);
    if (!selectedSheet) return;

    // 1. Find the parent wrapper that contains BOTH the nav and the content
    // Usually it's a .card or a version-pane
    const wrapper = selectedSheet.closest('.version-pane') || selectedSheet.closest('.card') || selectedSheet.parentElement.parentElement;
    if (!wrapper) return;

    // 2. Hide all panes within this wrapper
    const sheets = wrapper.querySelectorAll('.sheet-pane');
    sheets.forEach(pane => pane.style.display = 'none');
    
    // 3. Deactivate all buttons within the navigation section of this wrapper
    const nav = wrapper.querySelector('.sheet-tabs-nav');
    if (nav) {
        const btns = nav.querySelectorAll('.btn-sheet');
        btns.forEach(btn => btn.classList.remove('active'));
        
        // 4. Activate the button that targets this sheetId
        const activeBtn = Array.from(btns).find(btn => {
            const onclick = btn.getAttribute('onclick');
            return onclick && onclick.includes(`'${sheetId}'`);
        });
        if (activeBtn) activeBtn.classList.add('active');
    }
    
    // 5. Show selected sheet
    selectedSheet.style.display = 'block';

    // 6. Optional: Sync side comments if applicable
    if (typeof renderSideComments === 'function') setTimeout(renderSideComments, 50);
}

document.addEventListener('DOMContentLoaded', () => {
    // Audit View Context
    const editorContainer = document.getElementById('audit-editor-container');
    const sidebar = document.getElementById('comment-sidebar');
    
    if (editorContainer && sidebar) {
        // Initial Render
        renderSideComments();
        
        // Show Sidebar if there are comments or revision-spans
        const comments = editorContainer.querySelectorAll('span[data-comment-id]');
        const revisionSpans = editorContainer.querySelectorAll('.revision-span, span[style*="text-decoration: line-through"], span[style*="text-decoration:line-through"]');
        
        if (comments.length > 0 || revisionSpans.length > 0) {
            sidebar.style.display = 'block';
        }

        // Add Resize Observer to re-align comments if layout changes
        const resizeObserver = new ResizeObserver(() => {
            renderSideComments();
        });
        resizeObserver.observe(editorContainer);
    }
    
    // Initialize Excel features if present
    renderSideComments();
    updateSheetTabBadges();
    
    // STRICT READ-ONLY ENFORCEMENT
    setTimeout(enforceReadOnly, 500); // Run after initial render
    
    // Re-run on clicks (tab switches) just in case
    document.addEventListener('click', () => setTimeout(enforceReadOnly, 100));
});

function enforceReadOnly() {
    // 1. Selector for all preview containers (Timeline versions + Free Input tabs)
    const containers = document.querySelectorAll('.version-content, .sheet-pane, #audit-editor-container');
    
    containers.forEach(container => {
        // A. Disable ContentEditable
        container.setAttribute('contenteditable', 'false');
        container.querySelectorAll('[contenteditable]').forEach(el => el.setAttribute('contenteditable', 'false'));
        
        // B. Disable Inputs
        container.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);
        
        // C. Visual Cues inside tables
        container.querySelectorAll('td').forEach(td => {
            td.style.cursor = 'default'; 
            td.onclick = null; // Remove inline click handlers if any
        });
    });
}

function renderSideComments() {
    const editor = document.getElementById('audit-editor-container');
    const commentsList = document.getElementById('comment-list');
    
    if (!editor || !commentsList) return;
    
    commentsList.innerHTML = '';
    commentsList.style.position = 'static';
    
    // ============================================================
    // PHASE 1: Collect ALL entries from ALL tabs
    //Includes: span[data-comment-id] (inline comments) 
    //          AND .revision-span / strikethrough (deleted/changed)
    // ============================================================
    const allEntries = [];
    const processedIds = new Set();
    const processedElements = new Set(); // To avoid double-counting nested elements
    
    // A) Inline Comments (span[data-comment-id])
    const commentSpans = editor.querySelectorAll('span[data-comment-id]');
    commentSpans.forEach(span => {
        if (processedElements.has(span)) return;
        
        // Skip hidden elements (e.g. inside hidden parent) - check offsetParent
        // Note: For tabs, parent might be hidden, so we check if the element ITSELF is hidden?
        // Actually, we WANT to show comments from hidden tabs.
        // But we skip duplicate spans.
        
        const id = span.getAttribute('data-comment-id');
        if (processedIds.has(id)) return;
        processedIds.add(id);
        
        // Mark this element and all children as processed
        processedElements.add(span);
        span.querySelectorAll('*').forEach(child => processedElements.add(child));
        
        // Find which tab this comment is in
        const parentSheet = span.closest('.sheet-pane');
        const tabName = getTabName(parentSheet);
        
        allEntries.push({
            id: id,
            type: 'comment',
            text: span.getAttribute('data-comment-text') || span.textContent.substring(0, 80),
            user: span.getAttribute('data-comment-user') || 'Reviewer',
            time: span.getAttribute('data-comment-time') || '',
            timestamp: parseInt(id.replace('c', '')) || 0,
            element: span,
            tabName: tabName
        });
    });
    
    // B) Revision Spans (auto-red deleted/changed text)
    // Expanded selectors: s, strike, del, .revision-span, style tags
    const revisionSpans = editor.querySelectorAll('.revision-span, span[style*="text-decoration: line-through"], span[style*="text-decoration:line-through"], s, strike, del');
    let revIdx = 0;
    
    revisionSpans.forEach(span => {
        if (processedElements.has(span)) return;
        
        // Skip if already captured as a comment container
        if (span.closest('[data-comment-id]')) return; 
        
        // Determine type: Deleted (strikethrough) or Changed (red text)
        const style = span.style.cssText || '';
        const tagName = span.tagName.toLowerCase();
        
        const isStrikethrough = style.includes('line-through') || ['s', 'strike', 'del'].includes(tagName);
        const isRedText = style.includes('red') || style.includes('#ef4444') || style.includes('#dc2626') || span.classList.contains('revision-span');
        
        if (!isStrikethrough && !isRedText) return;
        
        const origText = span.textContent.trim();
        if (!origText || origText.length < 1) return;
        
        // Mark as processed (and children)
        processedElements.add(span);
        span.querySelectorAll('*').forEach(child => processedElements.add(child));
        
        const parentSheet = span.closest('.sheet-pane');
        const tabName = getTabName(parentSheet);
        
        // If parentSheet is hidden, that's fine (we want to show it).
        // But checking for 'display:none' on the element itself?
        if (span.style.display === 'none') return;

        const entryType = isStrikethrough ? 'deleted' : 'changed';
        const uniqueKey = `rev_${tabName}_${revIdx++}`;
        
        if (processedIds.has(uniqueKey)) return;
        processedIds.add(uniqueKey);
        
        // Extract user from data attributes or parent context
        const user = span.getAttribute('data-comment-user') || span.closest('[data-comment-user]')?.getAttribute('data-comment-user') || 'Reviewer';
        const time = span.getAttribute('data-comment-time') || '';
        
        allEntries.push({
            id: uniqueKey,
            type: entryType,
            text: origText.length > 60 ? origText.substring(0, 60) + '...' : origText,
            user: user,
            time: time,
            timestamp: Date.now() - (1000 * revIdx), // Order within group
            element: span,
            tabName: tabName
        });
    });
    
    // ============================================================
    // PHASE 2: Group by Tab and Render
    // ============================================================
    const grouped = {};
    allEntries.forEach(entry => {
        const key = entry.tabName || 'General';
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(entry);
    });
    
    // Sort entries within each group
    Object.values(grouped).forEach(group => {
        group.sort((a, b) => b.timestamp - a.timestamp);
    });
    
    const tabNames = Object.keys(grouped);
    const showTabHeaders = tabNames.length > 1 || (tabNames.length === 1 && tabNames[0] !== 'General');
    
    tabNames.forEach(tabName => {
        const entries = grouped[tabName];
        
        // Tab Header (only if multiple tabs)
        if (showTabHeaders) {
            const header = document.createElement('div');
            header.style.cssText = 'font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; padding:8px 0 4px 0; margin-top:8px; border-bottom:1px solid #f1f5f9; margin-bottom:8px;';
            header.textContent = '📄 ' + tabName;
            commentsList.appendChild(header);
        }
        
        entries.forEach(c => {
            const card = document.createElement('div');
            card.className = 'comment-card';
            card.setAttribute('data-for', c.id);
            
            card.style.cssText = 'position:relative; margin-bottom:12px; background:white; border:1px solid #e2e8f0; border-radius:12px; padding:14px; box-shadow:0 2px 4px rgba(0,0,0,0.02); cursor:pointer; transition:all 0.15s;';
            
            // Badge based on type
            let badge = '';
            if (c.type === 'deleted') {
                badge = '<span style="font-size:9px; font-weight:700; background:#fef2f2; color:#dc2626; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">Deleted</span>';
            } else if (c.type === 'changed') {
                badge = '<span style="font-size:9px; font-weight:700; background:#fffbeb; color:#d97706; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">Changed</span>';
            } else {
                badge = '<span style="font-size:9px; font-weight:700; background:#eff6ff; color:#3b82f6; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">Comment</span>';
            }
            
            // Avatar color based on type
            const avatarBg = c.type === 'deleted' ? '#fef2f2' : c.type === 'changed' ? '#fffbeb' : '#eff6ff';
            const avatarColor = c.type === 'deleted' ? '#dc2626' : c.type === 'changed' ? '#d97706' : '#3b82f6';
            const avatarBorder = c.type === 'deleted' ? '#fecaca' : c.type === 'changed' ? '#fde68a' : '#dbeafe';
            
            // Text styling for deleted
            const textStyle = c.type === 'deleted' 
                ? 'text-decoration:line-through; color:#991b1b;' 
                : c.type === 'changed' 
                    ? 'color:#92400e;' 
                    : 'color:#334155;';
            
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:28px; height:28px; background:${avatarBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:${avatarColor}; font-weight:bold; border:1px solid ${avatarBorder};">
                            ${c.user.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-size:12px; font-weight:600; color:#1e293b;">${c.user}</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        ${badge}
                        ${c.time ? '<span style="font-size:10px; color:#94a3b8;">' + c.time + '</span>' : ''}
                    </div>
                </div>
                <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:10px; font-size:13px; line-height:1.5; ${textStyle}">
                    ${escapeHtml(c.text)}
                </div>
            `;
            
            // Interaction: Click to navigate to element
            card.addEventListener('click', () => {
                // 1. Cross-Tab Navigation
                const parentSheet = c.element.closest('.sheet-pane');
                if (parentSheet && parentSheet.style.display === 'none') {
                    const sheetId = parentSheet.id;
                    let tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
                    if (!tabBtn && sheetId.startsWith('tab-')) {
                        const idx = sheetId.replace('tab-', '');
                        tabBtn = document.getElementById(`tab-btn-${idx}`);
                    }
                    if (tabBtn) tabBtn.click();
                }

                // 2. Scroll and Blink
                setTimeout(() => {
                    c.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    c.element.classList.remove('blink-highlight');
                    void c.element.offsetWidth;
                    c.element.classList.add('blink-highlight');

                    document.querySelectorAll('.comment-card').forEach(x => {
                        x.style.borderColor = '#e2e8f0'; 
                        x.style.backgroundColor = 'white';
                        x.style.transform = 'scale(1)';
                    });
                    card.style.borderColor = '#ef4444';
                    card.style.backgroundColor = '#fef2f2';
                 card.style.transform = 'scale(1.02)';
             }, 100);
        });

        commentsList.appendChild(card);
        });
    });
}

// Helper: Get tab name from a sheet-pane element
function getTabName(sheetPane) {
    if (!sheetPane) return 'General';
    
    const sheetId = sheetPane.id || '';
    
    // Try to find the corresponding button label
    let btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
    if (!btn && sheetId.startsWith('tab-')) {
        const idx = sheetId.replace('tab-', '');
        btn = document.getElementById(`tab-btn-${idx}`);
    }
    if (!btn && sheetId.startsWith('sheet-free-')) {
        const idx = sheetId.replace('sheet-free-', '');
        btn = document.getElementById(`tab-btn-${idx}`);
    }
    
    if (btn) {
        // Get button text without badge text
        const clone = btn.cloneNode(true);
        clone.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
        return clone.textContent.trim() || sheetId;
    }
    
    return sheetId || 'General';
}

// Helper: Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function updateSheetTabBadges() {
    // 1. Clear existing badges
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    
    // 2. Scan Sheets for Comments
    document.querySelectorAll('.sheet-pane').forEach(pane => {
        // ROBUST SELECTOR: Check for BOTH inline-comments (Yellow) AND revision-spans (Red)
        const hasComments = pane.querySelector('.inline-comment, .revision-span, span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]');
        
        if (hasComments) {
            const sheetId = pane.id;
            let btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
            
            // Try Free Input naming (sheet-free-{index})
            if (!btn && sheetId.startsWith('sheet-free-')) {
                const idx = sheetId.replace('sheet-free-', '');
                btn = document.getElementById(`tab-btn-${idx}`);
            }
            // Legacy/Review fallback
            else if (!btn && sheetId.startsWith('tab-')) {
                const idx = sheetId.replace('tab-', '');
                btn = document.getElementById(`tab-btn-${idx}`);
            }

            if (btn) {
                if (!btn.querySelector('.tab-badge-dot')) {
                    const dot = document.createElement('span');
                    dot.className = 'tab-badge-dot';
                    dot.style.cssText = `
                        display: inline-flex; 
                        justify-content: center; 
                        align-items: center; 
                        width: 16px; 
                        height: 16px; 
                        background: #ef4444; 
                        color: white;
                        font-size: 10px;
                        font-weight: bold;
                        border-radius: 50%; 
                        margin-left: 6px; 
                        vertical-align: middle;
                    `;
                    dot.innerText = '!';
                    dot.title = "Review Notes Inside";
                    btn.appendChild(dot);
                }
            }
        }
    });
}

function downloadAuditExcel() {
    const workbook = XLSX.utils.book_new();
    let panes;

    // Detect Mode (Robust Check: Mode string OR existence of TEMPLATE file)
    const isFileUpload = <?php echo (($req['mode'] ?? '') === 'FILE_UPLOAD' || !empty($files['TEMPLATE'])) ? 'true' : 'false'; ?>;
    
    if (isFileUpload) {
         // FILE UPLOAD MODE: Export ALL SHEETS from CURRENTLY SELECTED Version
         // Find the visible version container
         const versionContainers = Array.from(document.querySelectorAll('.version-pane'));
         const visibleVersion = versionContainers.find(el => el.style.display !== 'none' && el.offsetParent !== null);
         
         if (visibleVersion) {
             // Get sheets from VISIBLE version
             panes = visibleVersion.querySelectorAll('.sheet-pane');
         } else {
             // Fallback: Default to first/latest if nothing visible (shouldn't happen)
             if (versionContainers.length > 0) panes = versionContainers[0].querySelectorAll('.sheet-pane');
             else panes = document.querySelectorAll('.sheet-pane');
         }
         
         if (!panes || panes.length === 0) {
             alert('No active version content found to export.');
             return;
         }
         
         panes.forEach((pane, idx) => {
             // Extract sheet name from data attribute or button label
             let sheetName = pane.getAttribute('data-sheet-name') || pane.getAttribute('data-media') || `Sheet_${idx+1}`;
             
             const cleanDOM = getCleanDOM(pane);
             let worksheet;
             const table = cleanDOM.querySelector('table, .excel-preview');
             
             if (table) {
                 worksheet = XLSX.utils.table_to_sheet(table, {raw:true});
             } else {
                 // If no table, try to get text content
                 const lines = cleanDOM.textContent.trim().split('\n');
                 const data = lines.map(l => [l.trim()]).filter(l => l[0]); // Remove empty lines
                 worksheet = XLSX.utils.aoa_to_sheet(data);
             }
             
             fitToColumn(worksheet);
             applyRevisionStyles(worksheet, cleanDOM);
             
             // Clean sheet name for Excel compatibility
             sheetName = sheetName.replace(/[:\\/?*\[\]]/g, '_').substring(0, 31);
             
             // Check for duplicate names
             if (workbook.SheetNames.includes(sheetName)) sheetName += `_${idx}`;
             
             XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
         });
         
    } else {
        // FREE INPUT MODE: Export Visible Tabs
        // Note: Free Input usually doesn't have version panes like File Upload in this specific UI implementation
        // But if it does (future proofing), we check visible parents first
        
        // 1. Try to find visible version pane first (if Free Input uses version timeline)
        const visibleVer = Array.from(document.querySelectorAll('.version-pane')).find(el => el.style.display !== 'none');
        if (visibleVer) {
             panes = visibleVer.querySelectorAll('.sheet-pane.free-input-pane');
        } else {
             // Standard Free Input (Static or Tabbed without Version Pane wrapper)
             panes = document.querySelectorAll('.sheet-pane.free-input-pane');
        }
        
        if (panes.length === 0) {
             // Fallback
             panes = document.querySelectorAll('.sheet-pane');
        }
        
        if (panes.length === 0) {
            alert('No content found to export.');
            return;
        }

            // Free Input: HTML-based Export (For Mixed Styling Support)
            // We construct a single HTML table for all tabs to ensure mixed formatting (red/black) works perfectly
            // Free Input: MHTML Export for Multi-Sheet + Rich Text
            const boundary = "----=_NextPart_Dummy_Boundary";
            let mhtml = `MIME-Version: 1.0\r\nContent-Type: multipart/related; boundary="${boundary}"\r\n\r\n`;

            // 1. Workbook Definition Part (The wrapper that defines sheets)
            mhtml += `--${boundary}\r\nContent-Location: file:///C:/dummy/workbook.htm\r\nContent-Type: text/html; charset="utf-8"\r\n\r\n`;
            mhtml += `<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>`;

            // Define Sheets in XML
            panes.forEach((pane, idx) => {
                let sheetName = (pane.getAttribute('data-media') || `Sheet ${idx+1}`).replace(/[:\\/?*\[\]]/g, '_').substring(0, 31);
                mhtml += `
   <x:ExcelWorksheet>
    <x:Name>${sheetName}</x:Name>
    <x:WorksheetSource HRef="sheet${idx}.htm"/>
   </x:ExcelWorksheet>`;
            });

            mhtml += `
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
</head>
<body></body>
</html>\r\n\r\n`;

            // 2. Individual Sheet Parts
            panes.forEach((pane, idx) => {
                mhtml += `--${boundary}\r\nContent-Location: file:///C:/dummy/sheet${idx}.htm\r\nContent-Type: text/html; charset="utf-8"\r\n\r\n`;
                mhtml += `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>`;
                mhtml += `<style>td { mso-number-format:"\@"; font-family: "Times New Roman", serif; font-size:11pt; } .red-text { color: red; font-weight: bold; }</style>`;
                mhtml += `<table border="1">`;

                const origContent = pane.querySelector('[contenteditable]') || pane;
                let currentLineHTML = "";
                const rowsHTML = [];

                function traverseToHTML(node, isRed) {
                    if (node.nodeType === 3) { // Text Node
                        let text = node.textContent; 
                        text = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
                        
                        if (isRed) {
                            currentLineHTML += `<font color="#FF0000"><b>${text}</b></font>`;
                        } else {
                            currentLineHTML += text;
                        }
                    } else if (node.nodeType === 1) { // Element Node
                        const tag = node.tagName.toLowerCase();
                        const s = node.getAttribute('style') || '';
                        
                        if (tag === 'del' || tag === 's' || tag === 'strike' || s.includes('line-through') || s.includes('text-decoration:line-through')) {
                            return; 
                        }

                        const isBlock = ['div', 'p', 'br', 'li'].includes(tag);
                        
                        if (isBlock && tag !== 'br' && currentLineHTML !== "") {
                            rowsHTML.push(currentLineHTML);
                            currentLineHTML = "";
                        }

                        const nodeRed = isRed || s.includes('color: red') || s.includes('color:red') || s.includes('color:#ff0000') || node.classList.contains('revision-span') || node.classList.contains('inline-comment');
                        
                        if (tag === 'br') {
                            rowsHTML.push(currentLineHTML);
                            currentLineHTML = "";
                        } else {
                            node.childNodes.forEach(child => traverseToHTML(child, nodeRed));
                        }
                        
                        if (isBlock && tag !== 'br' && currentLineHTML !== "") {
                            rowsHTML.push(currentLineHTML);
                            currentLineHTML = "";
                        }
                    }
                }

                traverseToHTML(origContent, false);
                if (currentLineHTML !== "") rowsHTML.push(currentLineHTML);

                if (rowsHTML.length === 0) {
                    mhtml += '<tr><td>(Empty)</td></tr>';
                } else {
                    rowsHTML.forEach(r => {
                        mhtml += `<tr><td style="vertical-align:top;">${r}</td></tr>`;
                    });
                }
                
                mhtml += `</table></body></html>\r\n\r\n`;
            });

            mhtml += `--${boundary}--\r\n`;

            // Download as .xls
            const blob = new Blob([mhtml], {type: 'application/vnd.ms-excel'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Audit_Export_Content.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            return;
        }
    
    // Helper function to get a clean DOM for export
    // Marks cells containing revision text with data-has-revision, then unwraps spans
    function getCleanDOM(element) {
        const clonedElement = element.cloneNode(true);
        
        // [FIX] Remove struck-through text (deleted content) FIRST so it doesn't get processed as valid revision
        // Matches: <del>, <s>, <strike>, style="...line-through...", style="...text-decoration:line-through..."
        clonedElement.querySelectorAll('del, s, strike, [style*="line-through"], [style*="text-decoration:line-through"]').forEach(el => el.remove());

        // [FIX] Mark parent cells that contain revision text (for red styling in Excel)
        clonedElement.querySelectorAll('.revision-span, .inline-comment, span[style*="color: red"], span[style*="color:red"]').forEach(el => {
            // Mark the closest table cell (if in a table)
            const cell = el.closest('td');
            if (cell) cell.setAttribute('data-has-revision', 'true');
            // Unwrap span (keep text, remove formatting wrapper)
            el.replaceWith(...el.childNodes);
        });
        // Remove UI-only elements (buttons, badges, etc.)
        clonedElement.querySelectorAll('.comment-highlight, .btn-resolve, .tab-badge-dot').forEach(el => el.remove());

        return clonedElement;
    }

    // [NEW] Apply red font color to cells that contained revision text
    function applyRevisionStyles(worksheet, cleanDOM) {
        const table = cleanDOM.querySelector('table');
        if (!table || !worksheet['!ref']) return;
        
        const rows = table.querySelectorAll('tr');
        rows.forEach((row, rIdx) => {
            const cells = row.querySelectorAll('td, th');
            cells.forEach((cell, cIdx) => {
                if (cell.getAttribute('data-has-revision') === 'true') {
                    const cellRef = XLSX.utils.encode_cell({ r: rIdx, c: cIdx });
                    if (worksheet[cellRef]) {
                        // Apply red font color using xlsx-js-style
                        worksheet[cellRef].s = {
                            font: { color: { rgb: 'FF0000' }, bold: true }
                        };
                    }
                }
            });
        });
    }

    // Helper function to auto-fit column widths
    function fitToColumn(worksheet) {
        const range = XLSX.utils.decode_range(worksheet['!ref']);
        worksheet['!cols'] = [];
        for (let C = range.s.c; C <= range.e.c; ++C) {
            let max_width = 10; // Minimum width
            for (let R = range.s.r; R <= range.e.r; ++R) {
                const cell = worksheet[XLSX.utils.encode_cell({c:C, r:R})];
                if (cell && cell.v) {
                    const cell_text = XLSX.utils.format_cell(cell);
                    const width = cell_text.length;
                    if (width > max_width) {
                        max_width = width;
                    }
                }
            }
            worksheet['!cols'][C] = { wch: max_width + 2 }; // Add a little padding
        }
    }

    // Generate and download the Excel file
    XLSX.writeFile(workbook, 'Audit_Export.xlsx');
}

// ====================================================================
// [NEW] EXCELJS-BASED AUDIT DETAIL EXPORT
// Creates a proper multi-sheet .xlsx with formatting and borders
// ====================================================================
async function downloadAuditDetailExcel() {
    if (typeof ExcelJS === 'undefined') {
        alert('ExcelJS library not loaded. Please check public/assets/js/exceljs.min.js');
        return;
    }

    const ticketId = <?php echo json_encode($ticketId ?? ''); ?>;
    const scriptNum = <?php echo json_encode($req['script_number'] ?? ''); ?>;
    const reqTitle = <?php echo json_encode($req['title'] ?? '-'); ?>;
    const reqStatus = <?php echo json_encode($req['status'] ?? '-'); ?>;
    const reqMode = <?php echo json_encode($req['mode'] ?? 'FREE_INPUT'); ?>;
    const reqJenis = <?php echo json_encode($req['jenis'] ?? '-'); ?>;
    const reqProduk = <?php echo json_encode($req['produk'] ?? '-'); ?>;
    const reqMedia = <?php echo json_encode($req['media'] ?? '-'); ?>;
    const reqKategori = <?php echo json_encode($req['kategori'] ?? '-'); ?>;

    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'EUC Script System';
    workbook.created = new Date();

    const borderStyle = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
    const headerFont = { bold: true, name: 'Times New Roman', size: 11 };
    const cellFont = { name: 'Times New Roman', size: 11 };
    const headerFill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF2F2F2' } };

    // ====== SHEET 1: REQUEST INFO ======
    const wsInfo = workbook.addWorksheet('Request Info');
    wsInfo.columns = [
        { width: 22 },
        { width: 50 }
    ];

    const infoData = [
        ['Ticket ID', ticketId],
        ['Script Number', scriptNum],
        ['Status', reqStatus],
        ['Title / Purpose', reqTitle],
        ['Input Mode', reqMode === 'FILE_UPLOAD' ? 'File Upload' : 'Free Input'],
        ['Jenis', reqJenis],
        ['Produk', reqProduk],
        ['Media', reqMedia],
        ['Kategori', reqKategori],
    ];

    // Read role info from DOM
    const metaGrid = document.querySelector('.detail-grid .content-column .card');
    if (metaGrid) {
        const labels = metaGrid.querySelectorAll('div[style*="font-size:11px"]');
        labels.forEach(label => {
            const text = label.textContent.trim();
            if (['Maker','SPV','PIC','Procedure','Created Date','Last Updated'].includes(text)) {
                const valueEl = label.nextElementSibling;
                if (valueEl) {
                    let val = valueEl.textContent.trim();
                    if (val) infoData.push([text, val]);
                }
            }
        });
    }

    infoData.forEach((row, i) => {
        const excelRow = wsInfo.addRow(row);
        excelRow.getCell(1).font = headerFont;
        excelRow.getCell(1).fill = headerFill;
        excelRow.getCell(1).border = borderStyle;
        excelRow.getCell(1).alignment = { vertical: 'middle' };
        excelRow.getCell(2).font = cellFont;
        excelRow.getCell(2).border = borderStyle;
        excelRow.getCell(2).alignment = { vertical: 'middle', wrapText: true };
    });

    // ====== SHEET 2+: SCRIPT CONTENT ======
    // Collect content panes from ACTIVE version only
    let contentPanes = [];
    
    // A) Free Input: direct tab panes with data-media
    document.querySelectorAll('.sheet-pane.free-input-pane').forEach(p => contentPanes.push(p));
    
    // B) File Upload: only from the ACTIVE (visible) version-pane
    const allVersionPanes = document.querySelectorAll('.version-pane');
    let activeVersionPane = null;
    allVersionPanes.forEach(vp => {
        if (vp.style.display !== 'none' && getComputedStyle(vp).display !== 'none') {
            activeVersionPane = vp;
        }
    });
    
    if (activeVersionPane) {
        const innerSheets = activeVersionPane.querySelectorAll('.sheet-pane');
        if (innerSheets.length > 0) {
            innerSheets.forEach(sp => contentPanes.push(sp));
        } else {
            contentPanes.push(activeVersionPane);
        }
    }
    
    // Helper: collect review notes from a DOM element
    function collectReviewNotes(el) {
        const notes = [];
        const revSelector = '.revision-span, .deletion-span, .inline-comment, span[style*="text-decoration: line-through"], span[style*="text-decoration:line-through"], span[style*="color: red"], span[style*="color:red"], span[style*="color:#ef4444"], span[style*="color:#dc2626"], s, strike, del';
        const processedEls = new Set();
        
        el.querySelectorAll(revSelector).forEach(span => {
            if (processedEls.has(span)) return;
            if (span.closest && span.closest('.deletion-span') && span !== span.closest('.deletion-span')) return;
            processedEls.add(span);
            span.querySelectorAll('*').forEach(child => processedEls.add(child));
            
            const style = span.style ? span.style.cssText || '' : '';
            const tagName = span.tagName.toLowerCase();
            const isStrikethrough = style.includes('line-through') || ['s', 'strike', 'del'].includes(tagName) || span.classList.contains('deletion-span');
            const isRedText = style.includes('red') || style.includes('#ef4444') || style.includes('#dc2626') || span.classList.contains('revision-span');
            
            if (!isStrikethrough && !isRedText) return;
            
            const origText = span.textContent.trim();
            if (!origText || origText.length < 1) return;
            if (span.style && span.style.display === 'none') return;
            
            const action = isStrikethrough ? 'Dihapus' : 'Direvisi/Ditambahkan';
            const user = span.getAttribute('data-comment-user') || span.getAttribute('data-user') || '';
            const dept = span.getAttribute('data-comment-dept') || span.getAttribute('data-dept') || '';
            const job = span.getAttribute('data-comment-job') || '';
            const time = span.getAttribute('data-comment-time') || '';
            
            let byLine = '';
            if (user) {
                byLine = user;
                if (dept) byLine += ' (' + dept + ')';
                else if (job) byLine += ' (' + job + ')';
            }
            
            const textPreview = origText.length > 60 ? origText.substring(0, 57) + '...' : origText;
            let note = '• "' + textPreview + '" - ' + action;
            if (byLine) note += ' oleh ' + byLine;
            if (time) note += ' [' + time + ']';
            
            notes.push(note);
        });
        
        return notes;
    }
    
    if (contentPanes.length > 0) {
        // Temporarily show hidden panes
        const hiddenPanes = [];
        contentPanes.forEach(el => {
            const vParent = el.closest('.version-pane');
            if (vParent && (vParent.style.display === 'none' || getComputedStyle(vParent).display === 'none')) {
                if (!hiddenPanes.find(p => p.el === vParent)) {
                    hiddenPanes.push({ el: vParent, orig: vParent.style.display });
                    vParent.style.display = 'block';
                }
            }
            if (el.style.display === 'none' || getComputedStyle(el).display === 'none') {
                hiddenPanes.push({ el, orig: el.style.display });
                el.style.display = 'block';
            }
        });

        contentPanes.forEach((pane, idx) => {
            // --- ROBUST SHEET NAME DETECTION ---
            let sheetName = pane.getAttribute('data-media') || pane.getAttribute('data-sheet-name') || '';
            
            if (!sheetName && pane.id) {
                const wrapper = pane.closest('.version-pane') || pane.closest('.card') || pane.parentElement;
                if (wrapper) {
                    const btns = wrapper.querySelectorAll('.btn-sheet, button[onclick*="changeSheet"]');
                    for (const btn of btns) {
                        const onclick = btn.getAttribute('onclick') || '';
                        if (onclick.includes("'" + pane.id + "'") || onclick.includes('"' + pane.id + '"')) {
                            const clone = btn.cloneNode(true);
                            clone.querySelectorAll('.tab-badge-dot, i').forEach(el => el.remove());
                            sheetName = clone.textContent.trim();
                            break;
                        }
                    }
                }
            }
            
            if (!sheetName) {
                const btn = document.getElementById('tab-btn-' + idx);
                if (btn) {
                    const clone = btn.cloneNode(true);
                    clone.querySelectorAll('.tab-badge-dot, i').forEach(el => el.remove());
                    sheetName = clone.textContent.trim();
                }
            }
            
            if (!sheetName) sheetName = 'Content ' + (idx + 1);
            sheetName = sheetName.replace(/[:\\\\/?*[\]]/g, '').substring(0, 31);

            let finalName = sheetName;
            let suffix = 1;
            while (workbook.getWorksheet(finalName)) {
                finalName = sheetName.substring(0, 27) + ' (' + suffix + ')';
                suffix++;
            }

            const ws = workbook.addWorksheet(finalName);

            // Collect review notes from ORIGINAL pane (before cloning/cleaning)
            const reviewNotes = collectReviewNotes(pane);

            // Clone and clean for content (remove deletion-spans for clean text)
            const cleanPane = pane.cloneNode(true);
            cleanPane.querySelectorAll('.deletion-span').forEach(el => el.remove());

            const table = cleanPane.querySelector('table');
            if (table) {
                // TABLE MODE (File Upload)
                const origTable = pane.querySelector('table');
                const rows = table.querySelectorAll('tr');
                const origRows = origTable ? origTable.querySelectorAll('tr') : [];
                let maxCols = 0;
                rows.forEach(tr => { if (tr.cells.length > maxCols) maxCols = tr.cells.length; });
                
                // Add 1 extra column for "Catatan Revisi"
                const colWidths = [];
                for (let i = 0; i < maxCols; i++) {
                    colWidths.push({ width: i === 0 ? 8 : (i === 1 ? 10 : 25) });
                }
                colWidths.push({ width: 40 }); // Catatan Revisi column
                ws.columns = colWidths;

                rows.forEach((tr, rowIdx) => {
                    const excelRow = ws.addRow([]);
                    Array.from(tr.cells).forEach((td, cellIdx) => {
                        const cell = excelRow.getCell(cellIdx + 1);
                        cell.border = borderStyle;
                        cell.alignment = { vertical: 'top', wrapText: true };

                        // Rich text with red color detection
                        const richText = [];
                        function walkNode(node, isRed) {
                            if (node.nodeType === 3) {
                                const txt = node.textContent;
                                if (txt) {
                                    richText.push({
                                        text: txt,
                                        font: isRed 
                                            ? { name: 'Times New Roman', size: 11, color: { argb: 'FFFF0000' }, bold: true }
                                            : { name: 'Times New Roman', size: 11 }
                                    });
                                }
                            } else if (node.nodeType === 1) {
                                const tag = node.tagName.toLowerCase();
                                if (tag === 'br') {
                                    richText.push({ text: '\n' });
                                } else if (tag === 'div' || tag === 'p') {
                                    if (richText.length > 0 && !richText[richText.length-1].text?.endsWith('\n')) {
                                        richText.push({ text: '\n' });
                                    }
                                    node.childNodes.forEach(n => walkNode(n, isRed));
                                    richText.push({ text: '\n' });
                                } else {
                                    let nowRed = isRed || node.classList?.contains('revision-span') 
                                        || node.style?.color === 'red' || node.style?.color === 'rgb(255, 0, 0)' || node.style?.color === '#ef4444';
                                    node.childNodes.forEach(n => walkNode(n, nowRed));
                                }
                            }
                        }
                        td.childNodes.forEach(n => walkNode(n, false));

                        if (richText.length > 0) {
                            cell.value = { richText: richText };
                        } else {
                            cell.value = td.innerText;
                            cell.font = cellFont;
                        }

                        // Header row styling
                        if (rowIdx === 0) {
                            cell.font = headerFont;
                            cell.fill = headerFill;
                            cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                        }
                    });
                    
                    // Add "Catatan Revisi" header/content in the extra column
                    const notesCell = excelRow.getCell(maxCols + 1);
                    notesCell.border = borderStyle;
                    notesCell.alignment = { vertical: 'top', wrapText: true };
                    
                    if (rowIdx === 0) {
                        // Header row
                        notesCell.value = 'Catatan Revisi';
                        notesCell.font = headerFont;
                        notesCell.fill = headerFill;
                        notesCell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                    } else {
                        // Collect per-row notes from the ORIGINAL table row
                        const origRow = origRows[rowIdx];
                        if (origRow) {
                            const rowNotes = collectReviewNotes(origRow);
                            if (rowNotes.length > 0) {
                                notesCell.value = rowNotes.join('\n');
                                notesCell.font = { name: 'Times New Roman', size: 10, color: { argb: 'FFCC0000' } };
                            }
                        }
                    }
                });
            } else {
                // TEXT MODE (Free Input)
                ws.columns = [{ width: 100 }, { width: 45 }];
                
                // Column A: Script Content (clean)
                const richText = [];
                function walkFreeNode(node, isRed) {
                    if (node.nodeType === 3) {
                        const txt = node.textContent;
                        if (txt) {
                            richText.push({
                                text: txt,
                                font: isRed 
                                    ? { name: 'Times New Roman', size: 11, color: { argb: 'FFFF0000' }, bold: true }
                                    : { name: 'Times New Roman', size: 11 }
                            });
                        }
                    } else if (node.nodeType === 1) {
                        const tag = node.tagName.toLowerCase();
                        if (tag === 'br') {
                            richText.push({ text: '\n' });
                        } else if (tag === 'div' || tag === 'p') {
                            if (richText.length > 0 && !richText[richText.length-1].text?.endsWith('\n')) {
                                richText.push({ text: '\n' });
                            }
                            node.childNodes.forEach(n => walkFreeNode(n, isRed));
                            richText.push({ text: '\n' });
                        } else {
                            let nowRed = isRed || node.classList?.contains('revision-span')
                                || node.style?.color === 'red' || node.style?.color === 'rgb(255, 0, 0)' || node.style?.color === '#ef4444';
                            node.childNodes.forEach(n => walkFreeNode(n, nowRed));
                        }
                    }
                }
                cleanPane.childNodes.forEach(n => walkFreeNode(n, false));

                // Header row
                const hdrRow = ws.addRow(['Script Content', 'Catatan Revisi']);
                hdrRow.getCell(1).font = headerFont;
                hdrRow.getCell(1).fill = headerFill;
                hdrRow.getCell(1).border = borderStyle;
                hdrRow.getCell(2).font = headerFont;
                hdrRow.getCell(2).fill = headerFill;
                hdrRow.getCell(2).border = borderStyle;

                // Content row
                const contentRow = ws.addRow([]);
                const cellA = contentRow.getCell(1);
                if (richText.length > 0) {
                    cellA.value = { richText: richText };
                } else {
                    cellA.value = cleanPane.innerText;
                    cellA.font = cellFont;
                }
                cellA.alignment = { vertical: 'top', wrapText: true };
                cellA.border = borderStyle;
                
                // Column B: Review Notes
                const cellB = contentRow.getCell(2);
                if (reviewNotes.length > 0) {
                    cellB.value = reviewNotes.join('\n');
                    cellB.font = { name: 'Times New Roman', size: 10, color: { argb: 'FFCC0000' } };
                } else {
                    cellB.value = '(Tidak ada revisi)';
                    cellB.font = { name: 'Times New Roman', size: 10, italic: true, color: { argb: 'FF999999' } };
                }
                cellB.alignment = { vertical: 'top', wrapText: true };
                cellB.border = borderStyle;
            }
        });

        // Restore hidden panes
        hiddenPanes.forEach(p => p.el.style.display = p.orig);
    }

    // ====== SHEET: TIMELINE & REVIEW NOTES ======
    const wsTimeline = workbook.addWorksheet('Timeline & Notes');
    wsTimeline.columns = [
        { width: 6 },   // No
        { width: 20 },  // Date
        { width: 25 },  // Action
        { width: 20 },  // User
        { width: 15 },  // Role
        { width: 40 },  // Details
    ];

    // Header
    const tlHeader = wsTimeline.addRow(['No', 'Date', 'Action', 'User', 'Role', 'Details']);
    tlHeader.eachCell(cell => {
        cell.font = headerFont;
        cell.fill = headerFill;
        cell.border = borderStyle;
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
    });

    // Read timeline from DOM
    const timelineContainer = document.getElementById('timeline-container');
    if (timelineContainer) {
        const entries = timelineContainer.querySelectorAll('div[style*="position:relative"]');
        let no = 1;
        entries.forEach(entry => {
            // Skip the anchor ("Created" label)
            if (entry.classList.contains('timeline-anchor')) return;

            const children = entry.querySelectorAll(':scope > div');
            let date = '', action = '', user = '', role = '', details = '';

            children.forEach(div => {
                const text = div.textContent.trim();
                const style = div.getAttribute('style') || '';
                // Date line (11px, color gray)
                if (style.includes('font-size:11px') && style.includes('color:#9ca3af')) {
                    date = text;
                }
                // Action line (font-weight:700)
                else if (style.includes('font-weight:700') && style.includes('font-size:13px')) {
                    action = text;
                }
                // User/role block
                else if (style.includes('font-size:12px') && style.includes('color:#666')) {
                    const parts = text.split('\n').map(s => s.trim()).filter(Boolean);
                    if (parts.length >= 1) user = parts[0].replace(/^by\s+/i, '');
                    if (parts.length >= 2) role = parts[1];
                }
                // Details box
                else if (style.includes('margin-top:8px') && style.includes('border-radius:6px')) {
                    details = text;
                }
            });

            if (action || user) {
                const row = wsTimeline.addRow([no++, date, action, user, role, details]);
                row.eachCell(cell => {
                    cell.font = cellFont;
                    cell.border = borderStyle;
                    cell.alignment = { vertical: 'top', wrapText: true };
                });
            }
        });
    }

    // ====== DOWNLOAD ======
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Audit_Detail_' + (ticketId || scriptNum || 'export') + '.xlsx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    console.log('✓ ExcelJS Audit Detail Download complete!');
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
