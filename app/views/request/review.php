<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

// Determine Mode
$isFileUpload = ($request['mode'] === 'FILE_UPLOAD');
?>

<style>
/* Global Box Sizing */
*, *::before, *::after { box-sizing: border-box; }

/* Custom Scrollbar: Right Sidebar */
.right-sidebar::-webkit-scrollbar { width: 8px; }
.right-sidebar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.right-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.right-sidebar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Custom Scrollbar: Comment List */
#comment-list::-webkit-scrollbar { width: 8px; }
#comment-list::-webkit-scrollbar-track { background: #fef2f2; border-radius: 10px; }
#comment-list::-webkit-scrollbar-thumb { background: #fca5a5; border-radius: 10px; }
#comment-list::-webkit-scrollbar-thumb:hover { background: #ef4444; }

/* [FIX] Sidebar container overflow protection */
.right-sidebar, #comment-sidebar {
    overflow-x: hidden !important;
    padding-right: 6px !important;
}
#comment-list {
    padding-right: 6px !important;
    overflow-x: hidden !important;
}
/* [FIX] Reduce card size to prevent clipping */
.comment-card {
    padding: 12px !important;
    margin-bottom: 10px !important;
    border-radius: 10px !important;
    max-width: 100% !important;
    overflow: hidden !important;
}

/* Custom Scrollbar: Review Popup */
#review-popup-list::-webkit-scrollbar { width: 8px; }
#review-popup-list::-webkit-scrollbar-track { background: #f8fafc; border-radius: 10px; }
#review-popup-list::-webkit-scrollbar-thumb { background: #93c5fd; border-radius: 10px; }
#review-popup-list::-webkit-scrollbar-thumb:hover { background: #3b82f6; }

/* CSS for Excel Preview Tabs */
.sheet-tabs-nav { display: flex; flex-wrap: wrap; border-bottom: 1px solid #ccc; background: #f1f1f1; }
.sheet-tabs-nav::-webkit-scrollbar { display: none; }
.btn-sheet { border: 1px solid #ccc; border-bottom: none; background: #e0e0e0; padding: 8px 16px; cursor: pointer; font-size: 13px; margin-right: 2px; }
.btn-sheet.active { background: #fff; font-weight: bold; border-top: 2px solid var(--primary-red); }
.sheet-pane { 
    padding: 15px; 
    background: #fff; 
    border: 1px solid #ccc; 
    border-top: none; 
    overflow: auto; 
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

/* Plain Text Editor Styles for Review */
.review-editor {
    width: 100%; min-height: 400px; max-height: 600px; padding: 15px; border: 1px solid #ccc; border-radius: 4px;
    font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;
    resize: vertical; outline: none; background: #fff; color: #333;
    overflow: auto !important; max-width: 100%; display: block; box-sizing: border-box;
}
.review-editor:focus { border-color: var(--primary-red); }

/* Blink Animation for Comment Navigation */
@keyframes blink-anim {
    0%, 100% { background-color: #fef08a; }
    50% { background-color: #fde047; }
}
.blink-highlight {
    animation: blink-anim 0.5s ease-in-out 3;
}

/* Inline Comment Style (Enforced) */
.inline-comment {
    background-color: #fef08a !important; /* Force Yellow */
    cursor: pointer;
    border-bottom: 2px solid #eab308;
    color: inherit !important; /* CRITICAL: Keep text color (Red for revision, Black for normal) */
    transition: background-color 0.3s;
}
.inline-comment:hover {
    background-color: #fde047 !important; /* Darker Yellow on Hover */
}

/* Blink Animation for Comment Navigation */
/* Only animate Outline and Shadow, NEVER background (handled by class) */
@keyframes blink-animation {
    0% { outline: 3px solid #eab308; box-shadow: 0 0 10px rgba(234, 179, 8, 0.5); transform: scale(1.02); z-index: 10; position: relative; }
    50% { outline: 3px solid #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.7); }
    100% { outline: 3px solid transparent; box-shadow: none; transform: scale(1); z-index: auto; position: static; }
}
.blink-highlight {
    animation: blink-animation 1.5s ease-out forwards;
}

/* Real-Time Revision Styles */
.revision-span {
    color: var(--primary-red) !important;
}

/* Inline Comment Style (Placed AFTER to override revision red if needed) */
/* REMOVED DUPLICATE .inline-comment definition to prevent conflicts */
/* We keep the one at lines 40-46 which uses color: inherit !important */

.revision-span.draft {
    
}
.comment-card.draft {
    border: 1px dashed #ef4444 !important;
    background: #fef2f2 !important;
    opacity: 0.95;
}

/* Hover Effects for Sidebar Cards */
.comment-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    cursor: pointer;
}
/* [FIX] Deletion cards ALWAYS get amber border, not gray */
.comment-card[data-card-type="deletion"] {
    border-color: #fde68a !important;
    background: #fffdf5 !important;
}
.comment-card:hover:not(.selected) {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08) !important;
}
/* Type-aware hover tints */
.comment-card[data-card-type="revision"]:hover:not(.selected) {
    border-color: #fca5a5 !important;
    background: #fff5f5 !important;
}
.comment-card[data-card-type="deletion"]:hover:not(.selected) {
    border-color: #f59e0b !important;
    background: #fef3c7 !important;
}
.comment-card[data-card-type="comment"]:hover:not(.selected),
.comment-card:hover:not(.selected):not([data-card-type]) {
    border-color: #93c5fd !important;
    background: #f0f7ff !important;
}
.comment-card.draft:hover:not(.selected) {
    border-color: #ef4444 !important;
    background: #fee2e2 !important;
    border-style: dashed !important;
}

/* Selected/Active Card */
.comment-card.selected {
    transform: scale(1.02) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
}
.comment-card.selected[data-card-type="revision"] {
    border-color: #ef4444 !important;
    background: #fef2f2 !important;
    border-left: 4px solid #ef4444 !important;
}
.comment-card.selected[data-card-type="deletion"] {
    border-color: #d97706 !important;
    background: #fef3c7 !important;
    border-left: 4px solid #d97706 !important;
}
.comment-card.selected[data-card-type="comment"],
.comment-card.selected:not([data-card-type]) {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
    border-left: 4px solid #3b82f6 !important;
}
/* [FIX] Draft selected = VERY distinct from unselected */
.comment-card.draft.selected {
    border: 3px solid #ef4444 !important;
    border-left: 6px solid #dc2626 !important;
    background: #fecaca !important;
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.25) !important;
    transform: scale(1.03) !important;
}
/* Global Modal Styles (Sync with edit.php) */
@keyframes modalFadeIn {
    from { opacity:0; transform:scale(0.9) translateY(-20px); }
    to { opacity:1; transform:scale(1) translateY(0); }
}
@keyframes warningPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.show { display: flex !important; }

/* PRINT STYLES */
/* PRINT STYLES */
@media print {
    @page { size: A4; margin: 0; }
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 210mm;
        min-height: 297mm;
        padding: 10mm; /* Reduced from 15mm for 1-page fit */
        background: white;
        color: black; /* Force black text */
        font-family: 'Times New Roman', Times, serif; /* Formal Font */
    }
    
    /* Hide specific non-print elements explicitly */
    .no-print { display: none !important; }
    
    /* FORCE REMOVE HIGHLIGHTS IN PRINT */
    .inline-comment,
    span[style*="background:#fef08a"],
    span[style*="background: #fef08a"],
    span[class*="highlight"] {
        background: transparent !important;
        background-color: transparent !important;
        border-bottom: none !important;
    }

    /* Layout Utilities for Print - FORMAL B&W */
    .print-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px; }
    /* Layout Utilities for Print - FORMAL B&W COMPACT */
    .print-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px; }
    .print-logo { max-height: 80px; } /* Logo increased as requested */
    .print-title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 5px; letter-spacing: 0.5px; }
    
    .print-section { margin-bottom: 12px; } /* Slightly relaxed margin */
    .print-section-title { font-size: 11px; font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #000; text-transform: uppercase; padding-bottom: 1px; }
    
    .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 9px; } /* Tighter grid */
    .print-label { font-weight: bold; color: #000; } 
    
    .print-content-box { border: 1px solid #000; padding: 5px; font-size: 10px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.3; text-align: justify; }
    
    /* Timeline Table - Simple Black Borders COMPACT */
    .print-timeline-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 10px; }
    .print-timeline-table th, .print-timeline-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
    .print-timeline-table th { background: transparent; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; }

    /* Approval Columns - Formal Box COMPACT */
    .approval-container { display: flex; justify-content: space-between; gap: 10px; margin-top: 15px; }
    .approval-box { flex: 1; border: 1px solid #000; padding: 0; display: flex; flex-direction: column; min-height: 80px; }
    .approval-title { background: transparent; border-bottom: 1px solid #000; padding: 2px; text-align: center; font-weight: bold; font-size: 9px; text-transform: uppercase; }
    .approval-sign { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 5px; font-size: 8px; line-height: 1.2; overflow: hidden; }
    .approval-line { width: 90%; border-bottom: 1px solid #000; text-align: center; font-size: 8px; padding-top: 2px; margin-top: auto; }
    
    .print-footer { position: fixed; bottom: 5mm; left: 10mm; right: 10mm; font-size: 8px; text-align: center; border-top: 1px solid #000; padding-top: 5px; display: flex; justify-content: space-between; font-style: italic; }
}
</style>

<!-- Load SheetJS Local -->
<script src="public/assets/js/xlsx.full.min.js"></script>
<script src="public/assets/js/exceljs.min.js"></script>

<!-- SUCCESS/ERROR MODAL -->
<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:32px; max-width:400px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalFadeIn 0.3s ease;">
        <div id="modalIcon" style="width:64px; height:64px; background:#dcfce7; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3 id="modalTitle" style="color:#1e293b; font-size:20px; font-weight:700; margin:0 0 10px 0;">Success!</h3>
        <p id="modalMessage" style="color:#64748b; font-size:14px; line-height:1.6; margin:0;">Operation completed successfully.</p>
        <button id="modalBtn" onclick="closeModal()" style="display:none; background:var(--primary-red); color:white; border:none; padding:12px 32px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 2px 8px rgba(211,47,47,0.3); transition:all 0.2s; margin-top:20px;">OK</button>
    </div>
</div>

<!-- WARNING/CONFIRM MODAL -->
<div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:32px; max-width:450px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalFadeIn 0.3s ease;">
        <div style="width:64px; height:64px; background:#fef3c7; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; animation:warningPulse 2s ease-in-out infinite;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 id="confirmTitle" style="color:#1e293b; font-size:20px; font-weight:700; margin:0 0 10px 0;">⚠️ Perhatian</h3>
        <div id="confirmMessage" style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #f59e0b; padding:15px; border-radius:8px; margin-bottom:24px; text-align:left; color:#475569; font-size:14px; line-height:1.6;">
        </div>
        <div style="display:flex; gap:12px; justify-content:center;">
            <button id="confirmCancelBtn" style="flex:1; background:white; color:#64748b; border:1px solid #cbd5e1; padding:12px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;">Batal</button>
            <button id="confirmOkBtn" style="flex:1; background:#f59e0b; color:white; border:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(245, 158, 11, 0.2); transition:all 0.2s;">Ya, Kirim</button>
        </div>
    </div>
</div>


<div class="main">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Review Request</h2>
            <p style="color:var(--text-secondary);">Script No: <strong><?php echo htmlspecialchars($request['script_number']); ?></strong> (<?php echo $request['mode']; ?> Mode)</p>
        </div>
        <div>
             <a href="index.php" class="btn-cancel" style="text-decoration:none; padding:8px 16px; border:1px solid #ccc; border-radius:4px; color:#555; background:white;">Back to Dashboard</a>
        </div>
    </div>

    <?php if (!empty($newerVersionInfo)): 
        // Map status to readable label
        $statusMap = [
            'CREATED' => 'Menunggu Approval SPV',
            'APPROVED_SPV' => 'Menunggu Approval PIC',
            'APPROVED_PIC' => 'Menunggu Approval Procedure',
            'MINOR_REVISION' => 'Dikembalikan ke Maker (Minor Revision)',
            'MAJOR_REVISION' => 'Dikembalikan ke Maker (Major Revision - Reset)',
            'LIBRARY' => 'Sudah Published ke Library',
            'COMPLETED' => 'Sudah Published ke Library',
            'DRAFT' => 'Masih Draft',
            'DRAFT_TEMP' => 'Masih Draft',
        ];
        $newerStatus = $newerVersionInfo['status'] ?? '';
        $statusLabel = $statusMap[$newerStatus] ?? ucwords(strtolower(str_replace('_', ' ', $newerStatus)));
        $newerRole = $newerVersionInfo['current_role'] ?? '';
    ?>
    <div id="version-guard-banner" style="background:linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border:2px solid #f59e0b; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 4px 12px rgba(245,158,11,0.2);">
        <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="flex-shrink:0; background:#f59e0b; color:white; border-radius:10px; padding:10px; display:flex; align-items:center; justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div style="flex:1;">
                <h3 style="margin:0 0 8px 0; font-size:16px; color:#92400e; font-weight:700;">⚠️ PERHATIAN: Script ini sudah memiliki versi yang lebih baru</h3>
                <div style="background:white; border-radius:8px; padding:12px 16px; margin-bottom:12px; border:1px solid #fbbf24;">
                    <table style="width:100%; font-size:13px; border-collapse:collapse;">
                        <tr>
                            <td style="color:#92400e; font-weight:600; padding:3px 0; width:130px;">Script Number</td>
                            <td style="color:#1e293b; font-weight:700; padding:3px 0;">: <?php echo htmlspecialchars($newerVersionInfo['script_number']); ?></td>
                        </tr>
                        <tr>
                            <td style="color:#92400e; font-weight:600; padding:3px 0;">Ticket ID</td>
                            <td style="color:#1e293b; font-weight:700; padding:3px 0;">: <?php echo htmlspecialchars($newerVersionInfo['ticket_id']); ?></td>
                        </tr>
                        <tr>
                            <td style="color:#92400e; font-weight:600; padding:3px 0;">Status</td>
                            <td style="padding:3px 0;">
                                : <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:700; font-size:12px;"><?php echo htmlspecialchars($statusLabel); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <p style="margin:0 0 12px 0; color:#a16207; font-size:12px;">Tombol Update Library / Minor / Major Revision di halaman ini sudah <strong>dinonaktifkan</strong>. Silakan gunakan versi terbaru.</p>
                <?php 
                    // Link to the correct page based on status
                    $newerSt = $newerVersionInfo['status'] ?? '';
                    if (in_array($newerSt, ['LIBRARY', 'COMPLETED', 'APPROVED_PROCEDURE'])) {
                        $newerLink = '?controller=request&action=viewLibrary&id=' . htmlspecialchars($newerVersionInfo['id']);
                    } else {
                        $newerLink = '?controller=request&action=review_library_script&id=' . htmlspecialchars($newerVersionInfo['id']);
                    }
                ?>
                <a href="<?php echo $newerLink; ?>" 
                   style="display:inline-flex; align-items:center; gap:6px; background:#f59e0b; color:white; text-decoration:none; padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; transition:all 0.2s;"
                   onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Buka Versi Terbaru
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid-container" style="display:grid; grid-template-columns: 3fr 1fr; gap:25px; align-items: start;">
        
        <!-- LEFT COLUMN: SCRIPT CONTENT -->
        <div class="card left-column" style="background:white; padding:20px; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.1); min-width:0; grid-column: 1;">
            
            <!-- Redesigned Request Metadata Card -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; font-family: 'Inter', system-ui, sans-serif;">
                <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="background: #fee2e2; color: #dc2626; padding: 8px; border-radius: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Request Information</h3>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <span style="background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                            <?php echo $request['mode']; ?> MODE
                        </span>
                        <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                            VERSION: <?php echo $request['version_number'] ?? '1.0'; ?>
                        </span>
                    </div>
                </div>
                
                <div style="padding: 20px;">
                    <div class="request-info-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <!-- Row 1: Title & Ticket ID -->
                        <div style="grid-column: span 3; padding: 5px;">
                            <div style="color: #64748b; font-size: 11px; font-weight: 500;">Purpose / Title</div>
                            <div style="color: #0f172a; font-weight: 600; font-size: 15px; margin-top: 4px;"><?php echo htmlspecialchars($request['title']); ?></div>
                        </div>
                        <div style="padding: 5px; text-align: left;">
                            <div style="color: #dc2626; font-size: 11px; font-weight: 500;">Ticket ID</div>
                            <div style="color: #dc2626; font-weight: 700; font-size: 16px; margin-top: 4px;">
                                <?php 
                                    $dispID = $request['ticket_id'];
                                    if (is_numeric($dispID)) $dispID = sprintf("SC-%04d", $dispID);
                                    echo htmlspecialchars($dispID);
                                ?>
                            </div>
                        </div>

                        <!-- Row 2: Roles (Maker, SPV, PIC, PROC) -->
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">Maker</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php echo htmlspecialchars($request['maker_name'] ?? $request['created_by'] ?? '-'); ?>
                            </div>
                        </div>

                        <?php 
                           $spvName = '-';
                           $picName = '-';
                           $procName = '-';
                           if (isset($logs) && is_array($logs)) {
                               foreach($logs as $l) {
                                   if ($l['action'] === 'APPROVE_SPV') $spvName = $l['full_name'] ?? $l['user_id'];
                                   if ($l['action'] === 'APPROVE_PIC') $picName = $l['full_name'] ?? $l['user_id'];
                                   if ($l['action'] === 'APPROVE_PROCEDURE' || $l['action'] === 'LIBRARY_UPDATE') $procName = $l['full_name'] ?? $l['user_id'];
                               }
                           }
                           
                           // Fallback for SPV: If not approved yet, show assigned SPV
                           if ($spvName === '-' && !empty($request['selected_spv_name'])) {
                               $spvName = $request['selected_spv_name'] . ' (Assigned)';
                           } elseif ($spvName === '-' && !empty($request['selected_spv'])) {
                               $spvName = $request['selected_spv'] . ' (Assigned)';
                           }

                           // Fallback for PIC: If not approved yet, show assigned PIC
                           if ($picName === '-' && !empty($request['selected_pic_name'])) {
                               $picName = $request['selected_pic_name'] . ' (Assigned)';
                           } elseif ($picName === '-' && !empty($request['selected_pic'])) {
                               $picName = $request['selected_pic'] . ' (Assigned)';
                           }
                        ?>

                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">SPV</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php echo htmlspecialchars($spvName); ?>
                            </div>
                        </div>

                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">PIC</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php echo htmlspecialchars($picName); ?>
                            </div>
                        </div>

                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">Procedure</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php echo htmlspecialchars($procName); ?>
                            </div>
                        </div>

                        <!-- Row 3: Metadata -->
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500; text-transform: uppercase;">Script Number</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;"><?php echo htmlspecialchars($request['script_number']); ?></div>
                        </div>
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500; text-transform: uppercase;">Product</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;"><?php echo htmlspecialchars($request['produk']); ?></div>
                        </div>
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500; text-transform: uppercase;">Created Date</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php 
                                    if ($request['created_at'] instanceof DateTime) {
                                        echo $request['created_at']->format('d M Y, H:i');
                                    } else {
                                        echo date('d M Y, H:i', strtotime($request['created_at']));
                                    }
                                ?>
                            </div>
                        </div>


                        <!-- Row 4: Classification -->
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">Jenis</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;"><?php echo htmlspecialchars($request['jenis']); ?></div>
                        </div>
                        <div style="padding: 5px;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">Kategori</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;"><?php echo htmlspecialchars($request['kategori']); ?></div>
                        </div>
                        <div style="padding: 5px; grid-column: span 2;">
                            <div style="color: #94a3b8; font-size: 11px; font-weight: 500;">Media Channels</div>
                            <div style="color: #334155; font-weight: 600; margin-top: 2px;">
                                <?php 
                                    $medias = preg_split('/[,;]/', $request['media'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
                                    echo htmlspecialchars(implode(', ', array_map('trim', $medias)));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <?php
            // [BANNER] Maker Confirmed Alert — show to Procedure when script was confirmed by Maker
            $makerConfirmEntry = null;
            if (isset($timeline) && is_array($timeline)) {
                foreach ($timeline as $log) {
                    if (($log['action'] ?? '') === 'MAKER_CONFIRM') {
                        $makerConfirmEntry = $log;
                        // Don't break — keep going to get the LATEST one (logs are ASC)
                    }
                }
            }
            $currentDeptForBanner = strtoupper($_SESSION['user']['dept'] ?? '');
            if ($makerConfirmEntry && in_array($currentDeptForBanner, ['PROCEDURE', 'CPMS'])): 
                $confirmBy = $makerConfirmEntry['full_name'] ?? $makerConfirmEntry['user_id'] ?? 'Maker';
                $confirmDate = '';
                if (!empty($makerConfirmEntry['created_at'])) {
                    $confirmDate = ($makerConfirmEntry['created_at'] instanceof \DateTime) 
                        ? $makerConfirmEntry['created_at']->format('d M Y, H:i')
                        : date('d M Y, H:i', strtotime($makerConfirmEntry['created_at']));
                }
            ?>
            <div style="background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border:2px solid #10b981; border-radius:12px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 8px rgba(16,185,129,0.15);">
                <div style="flex-shrink:0; background:#10b981; color:white; border-radius:10px; padding:10px; display:flex; align-items:center; justify-content:center;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; color:#065f46; margin-bottom:4px;">✅ Dikonfirmasi oleh Maker</div>
                    <div style="font-size:12px; color:#047857; line-height:1.5;">
                        <strong><?php echo htmlspecialchars($confirmBy); ?></strong> telah mereview dan mengkonfirmasi script ini<?php if ($confirmDate): ?> pada <strong><?php echo $confirmDate; ?></strong><?php endif; ?>. Silakan lanjutkan proses publish.
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:12px; color:#333; display:flex; justify-content:space-between; align-items:center;">
                <span>Script Content (Editable)</span>
                <span style="font-size:12px; color:#888; font-weight:normal;">Approver can refine the script before final approval</span>
            </h4>

            <!-- Unified Editor Toolbar -->
            <style>
                .btn-tool {
                    background: white; border:1px solid #cbd5e1; border-radius:4px; padding:6px 10px; 
                    cursor:pointer; font-weight:600; font-size:12px; transition:all 0.2s;
                    display:flex; align-items:center; gap:6px; color: #64748b;
                }
                .btn-tool:hover { background: #f8fafc; border-color: #94a3b8; }
            </style>

            <div class="editor-toolbar" style="background:#f1f5f9; padding:10px 15px; border-radius:10px; border:1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content: flex-end; margin-bottom:15px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                 <!-- UNDO/REDO TOOLS -->
                 <div style="display:flex; align-items:center; gap:6px;">
                     <button type="button" onmousedown="event.preventDefault();" onclick="performUndo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:6px; padding:6px; cursor:pointer; transition:all 0.2s;" title="Undo (Ctrl+Z)" onmouseenter="this.style.borderColor='#94a3b8'; this.style.background='#f8fafc';" onmouseleave="this.style.borderColor='#cbd5e1'; this.style.background='#white';">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                     </button>
                     <button type="button" onmousedown="event.preventDefault();" onclick="performRedo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:6px; padding:6px; cursor:pointer; transition:all 0.2s;" title="Redo (Ctrl+Y)" onmouseenter="this.style.borderColor='#94a3b8'; this.style.background='#f8fafc';" onmouseleave="this.style.borderColor='#cbd5e1'; this.style.background='#white';">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 3.7"></path></svg>
                     </button>
                 </div>

                 <div style="width:1px; height:24px; background:#e2e7eb; margin:0 5px;"></div>

                 <?php 
                     // FIX: Case-insensitive Check
                     $currentDept = isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : '';
                     if (in_array($currentDept, ['SPV', 'PIC', 'PROC', 'PROCEDURE'])): 
                 ?>
                 
                 <?php if ($currentDept === 'PROC' || $currentDept === 'PROCEDURE'): ?>
                 <!-- PROCEDURE MODE PRESET -->
                 <script>
                    window._procEditingMode = 'suggesting'; // Default to Revisi Maker
                 </script>
                 <?php endif; ?>

                 <!-- Add Highlight Button (Available for SPV, PIC, and PROCEDURE) -->
                 <button type="button" onclick="addComment()" id="btn-comment" style="background:linear-gradient(135deg, #eab308 0%, #f59e0b 100%); color:#fff; border:none; border-radius:8px; padding:10px 18px; cursor:pointer; font-weight:700; font-size:12px; display:flex; align-items:center; gap:8px; box-shadow:0 4px 6px rgba(234, 179, 8, 0.2); transition:all 0.2s ease;" title="Add Comment / Highlight" onmouseenter="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(234, 179, 8, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(234, 179, 8, 0.2)';" onmousedown="event.preventDefault();">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        Add Highlight
                 </button>
                 <?php endif; ?>
            </div>

            <script>
                // --- Unified Procedure Editor Logic ---
                // [FIX] Ensure SPV/PIC Roles are detected for Auto Red
                // FIX: Normalize role to uppercase for safe comparison
                window.CURRENT_USER_ROLE = "<?php echo isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : ''; ?>";
                window.CURRENT_USER_NAME = "<?php echo $_SESSION['user']['fullname'] ?? $_SESSION['user']['userid'] ?? 'User'; ?>";
                
                // [FIX] Role Lookup for Old Revisions (Database Fallback)
                window.USER_ROLE_MAP = {};
                <?php
                // Fetch all users to build role map
                require_once 'app/models/UserModel.php';
                $userModel = new \App\Models\UserModel();
                $allUsers = $userModel->getAll();
                foreach ($allUsers as $u) {
                    // Map both Fullname and UserID to Role (Dept)
                    $role = $u['DEPT'] ?? '';
                    if ($role) {
                        echo "window.USER_ROLE_MAP['" . addslashes($u['FULLNAME']) . "'] = '$role';\n";
                        echo "window.USER_ROLE_MAP['" . addslashes($u['USERID']) . "'] = '$role';\n";
                    }
                }
                ?>

                window.procMode = 'suggesting'; // Default
                
                function setProcMode(mode) {
                    window.procMode = mode;
                    const btnSuggest = document.getElementById('btn-mode-suggesting');
                    const btnEdit = document.getElementById('btn-mode-editing');
                    const btnComment = document.getElementById('btn-comment');
                    
                    if(mode === 'suggesting') {
                        // UI State
                        btnSuggest.style.background = '#0284c7';
                        btnSuggest.style.color = 'white';
                        btnSuggest.style.boxShadow = '0 2px 4px rgba(2, 132, 199, 0.2)';
                        btnEdit.style.background = 'transparent';
                        btnEdit.style.color = '#64748b';
                        btnEdit.style.boxShadow = 'none';
                        
                        // Editor Visual
                        document.querySelectorAll('.review-editor').forEach(el => {
                            el.style.borderTop = '3px solid #ef4444'; // Red Border indicator
                        });
                        
                        // Interaction
                        if(btnComment) {
                            btnComment.style.opacity = '1';
                            btnComment.style.pointerEvents = 'auto';
                            btnComment.style.filter = 'none';
                            btnComment.title = "Add Comment / Highlight";
                        }
                    } else {
                        // UI State
                        btnEdit.style.background = '#000'; // Black for Legal/CX
                        btnEdit.style.color = 'white';
                        btnEdit.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.2)';
                        btnSuggest.style.background = 'transparent';
                        btnSuggest.style.color = '#64748b';
                        btnSuggest.style.boxShadow = 'none';
                        
                        // Editor Visual
                        document.querySelectorAll('.review-editor').forEach(el => {
                            el.style.borderTop = '3px solid #000000'; // Black Border indicator
                        });
                        
                        // Interaction
                        if(btnComment) {
                            btnComment.style.opacity = '0.3';
                            btnComment.style.pointerEvents = 'none';
                            btnComment.style.filter = 'grayscale(1)';
                            btnComment.title = "Comment tool disabled in Legal/CX mode";
                        }
                    }
                }

                // [FIX] Removed showProcModeInfo function per user request
            </script>

            <!-- MODE: FILE UPLOAD -->
            <?php if ($isFileUpload): ?>
                <div style="margin-bottom:10px; font-size:12px; color:#666; font-style:italic; display:block;">
                    <div style="margin-bottom:5px;">* Editing content in File Upload mode updates the preview for all media channels. Grouping edits by session.</div>
                </div>

                <div id="unified-file-editor" class="review-editor" style="border-top:3px solid #ef4444;">
                    <?php 
                        // If file upload and we have content
                        if ($isFileUpload && !empty($content)) {
                             // Check if content already contains tabs (Pre-formatted HTML from Editor/FileHandler)
                             // This handles both "Single Formatted Row" (Fixed) and "Multiple Duplicate Media Rows" (Corrupt)
                             $hasPrebuiltTabs = false;
                             foreach ($content as $row) {
                                 if (strpos($row['content'], 'sheet-tabs-nav') !== false) {
                                     $hasPrebuiltTabs = true;
                                     break;
                                 }
                             }

                             if ($hasPrebuiltTabs) {
                                 // Case A: Content is already a full container (with internal tabs).
                                 // Just render the FIRST one. Ignores duplicates if they exist (Corrupt Data Fix).
                                 echo $content[0]['content'];
                             } elseif (count($content) > 1) {
                                 // Case B: Content is separate sheets (Tables only). Render our own tabs.
                                 // Render Tabs
                                 echo '<div style="display:flex; gap:10px; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; overflow-x:auto;">';
                                 foreach ($content as $idx => $row) {
                                     $active = ($idx === 0);
                                     $media = htmlspecialchars($row['media'] ?? 'Part ' . ($idx+1));
                                     
                                     $btnStyle = $active 
                                        ? "background: #3b82f6; color: white; border: none;"
                                        : "background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;";

                                     echo "<button type='button' id='tab-media-btn-$idx' class='btn-media-tab' 
                                            style='padding:8px 20px; border-radius:30px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s; white-space:nowrap; $btnStyle'
                                            onclick=\"openMediaTab(event, $idx)\">$media</button>";
                                 }
                                 echo '</div>';
                                 
                                 // Render Panes (Using media-pane class)
                                 foreach ($content as $idx => $row) {
                                     $display = ($idx === 0) ? 'block' : 'none';
                                     echo "<div id='tab-media-$idx' class='media-pane review-tab-content' style='display:$display; min-height: 400px;'>";
                                     
                                     // [FIX] AUTO-RECOVERY for Multi-Sheet
                                     $html = $row['content'];
                                     if (strpos($html, '&lt;table') !== false || strpos($html, '&lt;td') !== false) {
                                         $html = htmlspecialchars_decode($html);
                                     }
                                     echo $html; 
                                     echo "</div>";
                                 }
                                 
                                 // Inline JS for Media Tabs to avoid global scope conflict
                                 echo "<script>
                                    function openMediaTab(evt, idx) {
                                        console.log(`[DEBUG] openMediaTab called for index ${idx}`);
                                        // Hide all media panes
                                        var panes = document.getElementsByClassName('media-pane');
                                        for (var i = 0; i < panes.length; i++) {
                                            panes[i].style.display = 'none';
                                        }
                                        // Deactivate buttons
                                        var btns = document.getElementsByClassName('btn-media-tab');
                                        for (var i = 0; i < btns.length; i++) {
                                            btns[i].style.background = '#f3f4f6';
                                            btns[i].style.color = '#4b5563';
                                            btns[i].style.border = '1px solid #e5e7eb';
                                            btns[i].classList.remove('active'); // Remove active class
                                        }
                                        // Show target
                                        document.getElementById('tab-media-' + idx).style.display = 'block';
                                        
                                        // Activate current button
                                        var currentBtn = document.getElementById('tab-media-btn-' + idx);
                                        currentBtn.style.background = '#3b82f6';
                                        currentBtn.style.color = 'white';
                                        currentBtn.style.border = 'none';
                                        currentBtn.classList.add('active'); // Add active class for detection
                                        console.log('[DEBUG] Activated button ' + idx + ': classes=\"' + currentBtn.className + '\"');
                                        
                                        // Update Sidebar Comments
                                        if(typeof renderSideComments === 'function') renderSideComments();
                                    }
                                    
                                    // Set initial active state
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var firstBtn = document.getElementById('tab-media-btn-0');
                                        if(firstBtn) firstBtn.classList.add('active');
                                    });
                                 </script>";
                             } else {
                                 // Case C: Single raw sheet
                                 echo $content[0]['content']; 
                             }
                        }
                    ?>
                </div>

            <?php else: ?>
            <!-- MODE: FREE INPUT (Plain Text Tabs) -->
                
                 <div style="display:flex; gap:10px; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; overflow-x:auto;">
                    <?php foreach ($content as $idx => $row): ?>
                        <button 
                            type="button"
                            id="tab-btn-review-<?php echo $idx; ?>" 
                            class="btn-media-tab" 
                            style="padding:8px 20px; border-radius:30px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s; white-space:nowrap; 
                                   <?php echo $idx === 0 ? 'background: #3b82f6; color: white; border: none;' : 'background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;'; ?>"
                            onclick="openTab(event, 'tab-<?php echo $idx; ?>')">
                            <?php echo htmlspecialchars($row['media'] ?? 'Content'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($content as $idx => $row): ?>
                <div id="tab-<?php echo $idx; ?>" class="sheet-pane review-tab-content" style="display:<?php echo $idx===0?'block':'none'; ?>;">
                    <div 
                        class="review-editor free-input-editor" 
                        id="free-editor-<?php echo $idx; ?>"
                        data-id="<?php echo $row['id']; ?>"
                        data-media="<?php echo htmlspecialchars($row['media'] ?? 'Content'); ?>"
                        contenteditable="true"
                        style="overflow:auto !important; white-space:pre-wrap; word-wrap:break-word; padding:10px;"><?php echo trim($row['content']); ?></div>
        


        </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- Closes .left-column.card -->
        
        <!-- RIGHT COLUMN: APPROVAL FORM -->
        <div class="panel-column right-sidebar" style="grid-column: 2; position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto;">
            
            <!-- Redesigned Approval History Timeline -->
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Request History
                </h4>
                
                <div style="max-height: 400px; overflow-y: auto; padding-right: 5px; position: relative;">
                    <!-- Vertical Timeline Line -->
                    <div style="position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; z-index: 1;"></div>
                    
                    <?php if (!empty($timeline)): ?>
                        <?php foreach (array_reverse($timeline) as $idx => $log): ?>
                            <div style="position: relative; padding-left: 30px; margin-bottom: 20px; z-index: 2;">
                                <!-- Timeline Dot -->
                                <div style="position: absolute; left: 6px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: <?php echo $idx === 0 ? 'var(--primary-red)' : '#cbd5e1'; ?>; border: 2px solid #white; box-shadow: 0 0 0 2px #fff;"></div>
                                
                                <?php 
                                    $rawAction = $log['action_type'] ?? $log['action'] ?? 'Status Update';
                                    $actionMap = [
                                        'CREATED' => 'Request Submitted',
                                        'APPROVE_SPV' => 'Approved (SPV)',
                                        'APPROVED_SPV' => 'Approved (SPV)', // Handle both tense forms just in case
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
                                <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($displayAction); ?>
                                </div>
                                <div style="font-size: 11px; color: #64748b; display: flex; flex-direction: column; gap: 2px;">
                                    <div>
                                        by <span style="font-weight: 600; color: #475569;"><?php echo htmlspecialchars($log['full_name'] ?? $log['user_id']); ?></span>
                                    </div>
                                    <div style="font-size: 10px; color: #94a3b8;">
                                        <?php echo htmlspecialchars($log['job_function'] ?? $log['group_name'] ?? 'Unit'); ?>
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                    <?php 
                                        $dt = $log['created_at'];
                                        if (!($dt instanceof DateTime)) {
                                            $dt = new DateTime($dt);
                                        }
                                        echo ($dt instanceof DateTime) ? $dt->format('d M Y, H:i') : date('d M Y, H:i', strtotime($dt)); 
                                    ?>
                                </div>
                                <?php 
                                    $note = $log['details'] ?? $log['remarks'] ?? '';
                                    if (!empty($note)): 
                                ?>
                                    <div style="margin-top: 6px; padding: 6px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; font-size: 11px; color: #475569; font-style: normal; border-left: 3px solid #f59e0b;">
                                        <?php echo htmlspecialchars($note); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #94a3b8; font-size: 12px; font-style: italic; text-align: center; margin-left: 30px;">No history available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comment Sidebar (Moved Below History) -->
            <div id="comment-sidebar" style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:20px; display:none;">
                 <h4 style="margin:0 0 15px 0; color:#333; font-size:14px; font-weight:bold; border-bottom:1px solid #eee; padding-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
                    <span>Review Comments</span>
                    <span id="comment-count-badge" style="font-size:11px; background:#ef4444; color:white; padding:2px 8px; border-radius:10px; font-weight:600; display:none;">0</span>
                 </h4>
                 <div id="comment-list" style="position:relative; max-height:500px; overflow-y:auto; padding-right:4px;"></div>
                 <!-- Lihat Semua Button -->
                 <div id="btn-lihat-semua-wrap" style="display:none; margin-top:10px;">
                     <button id="btn-lihat-semua" onclick="openReviewPopup()" style="width:100%; padding:10px; background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; border:none; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; box-shadow:0 2px 4px rgba(59,130,246,0.2);">
                         <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                         Lihat Semua Review (<span id="review-total-count">0</span>)
                     </button>
                 </div>
            </div>

            <!-- REVIEW POPUP MODAL (Full Screen) -->
            <div id="review-popup-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; backdrop-filter:blur(4px); animation:fadeIn 0.2s ease;" onclick="if(event.target===this)closeReviewPopup()">
                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:90%; max-width:700px; max-height:85vh; background:white; border-radius:16px; box-shadow:0 25px 50px rgba(0,0,0,0.15); display:flex; flex-direction:column; overflow:hidden;">
                    <!-- Header -->
                    <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:#1e293b;">📋 All Review Comments</h3>
                            <p id="popup-subtitle" style="margin:4px 0 0 0; font-size:12px; color:#94a3b8;">Overview of all tracked changes</p>
                        </div>
                        <button onclick="closeReviewPopup()" style="background:none; border:none; cursor:pointer; color:#94a3b8; padding:8px; border-radius:8px; transition:all 0.2s;" onmouseover="this.style.background='#f1f5f9';this.style.color='#334155'" onmouseout="this.style.background='none';this.style.color='#94a3b8'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <!-- Filter Bar -->
                    <div style="padding:12px 24px; border-bottom:1px solid #f1f5f9; display:flex; gap:8px; flex-shrink:0;">
                        <button class="review-filter-btn active" data-filter="all" onclick="filterReviewPopup('all',this)" style="padding:6px 14px; border:1px solid #e2e8f0; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; background:#1e293b; color:white; transition:all 0.15s;">All</button>
                        <button class="review-filter-btn" data-filter="revision" onclick="filterReviewPopup('revision',this)" style="padding:6px 14px; border:1px solid #fecaca; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; background:#fef2f2; color:#ef4444; transition:all 0.15s;">Revisions</button>
                        <button class="review-filter-btn" data-filter="deletion" onclick="filterReviewPopup('deletion',this)" style="padding:6px 14px; border:1px solid #fde68a; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; background:#fffbeb; color:#d97706; transition:all 0.15s;">Deletions</button>
                        <button class="review-filter-btn" data-filter="comment" onclick="filterReviewPopup('comment',this)" style="padding:6px 14px; border:1px solid #dbeafe; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; background:#eff6ff; color:#3b82f6; transition:all 0.15s;">Comments</button>
                    </div>
                    <!-- Content -->
                    <div id="review-popup-list" style="flex:1; overflow-y:auto; padding:16px 24px;"></div>
                </div>
            </div>

            <!-- Approval Form (Only for Pending Requests) -->
            <?php 
            $isFinal = in_array($request['status'], ['LIBRARY', 'REJECTED', 'CANCELLED']);
            if (!$isFinal): 
            ?>
            <div class="card" style="background:white; padding:16px; border-radius:10px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-top: 4px solid var(--primary-red);">
                <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Decision Block
                </h4>

                <?php if (isset($_SESSION['user']) && $_SESSION['user']['dept'] === 'PROCEDURE'): ?>
                <!-- SHARED PROCEDURE DOCUMENTS SECTION -->
                <div style="display:flex; gap:10px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f1f5f9;">
                    <button type="button" onclick="downloadReviewExcel()" style="flex:1; padding:10px; background:#0f766e; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(15, 118, 110, 0.2); transition:all 0.2s;" onmouseenter="this.style.background='#0d9488'" onmouseleave="this.style.background='#0f766e'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Download Excel
                    </button>
                    
                    <?php if (!isset($isLibraryRevision) || !$isLibraryRevision): ?>
                    <button type="button" onclick="printTicketScript()" style="flex:1; padding:10px; background:#d32f2f; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(211, 47, 47, 0.2); transition:all 0.2s;" onmouseenter="this.style.background='#c62828'" onmouseleave="this.style.background='#d32f2f'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Tiket Final Script
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-secondary);"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <?php echo (isset($isLibraryRevision) && $isLibraryRevision) ? 'Revision Decision' : 'Approval Decision'; ?>
                </h4>
                
                <?php 
                $currentDeptUpper = isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : '';
                $isMakerConfirmation = ($currentDeptUpper === 'MAKER' && $request['status'] === 'PENDING_MAKER_CONFIRMATION');
                ?>

                <?php if ($isMakerConfirmation): ?>
                    <!-- MAKER CONFIRMATION PANEL -->
                    <div style="padding:15px; background:#faf5ff; border-radius:8px; border:1px solid #e9d5ff; margin-bottom:15px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span style="font-weight:700; color:#7c3aed; font-size:13px;">Konfirmasi dari Procedure</span>
                        </div>
                        <p style="font-size:12px; color:#64748b; margin:0 0 12px 0; line-height:1.5;">
                            Procedure telah mengirimkan script ini untuk Anda periksa. Silakan review konten dan catatan di sidebar, lalu pilih tindakan:
                        </p>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <button type="button" onclick="makerConfirmFromReview('confirm')" style="width:100%; padding:12px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 4px rgba(16,185,129,0.2);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Selesei Review & Kirim ke Procedure
                            </button>
                        </div>
                    </div>

                    <script>
                    function makerConfirmFromReview(decision) {
                        const requestId = <?php echo $request['id']; ?>;
                        
                        Swal.fire({
                            title: 'Selesai Review & Kirim?',
                            html: 'Script akan dikirimkan kembali ke <b>Procedure</b> untuk di-publish ke Library.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#10b981',
                            cancelButtonColor: '#aaa',
                            confirmButtonText: 'Ya, Kirim Sekarang!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch('index.php?controller=request&action=makerConfirm', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({ request_id: requestId, decision: 'confirm' })
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Berhasil Dikirim!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonText: 'OK, Kembali ke Dashboard',
                                            allowOutsideClick: false
                                        }).then(() => {
                                            window.location.href = 'index.php?controller=dashboard';
                                        });
                                    } else {
                                        Swal.fire('Error', data.error || 'Gagal memproses.', 'error');
                                    }
                                })
                                .catch(err => Swal.fire('Error', 'Network error: ' + err.message, 'error'));
                            }
                        });
                    }
                    </script>

                <?php elseif (isset($isLibraryRevision) && $isLibraryRevision): ?>
                    <!-- SPECIAL REVISION DECISION UI FOR PROCEDURE -->
                    <div id="revision-decision-box">
                        <input type="hidden" id="rev_request_id" value="<?php echo $request['id']; ?>">
                        
                        <p style="font-size:12px; color:#64748b; margin-bottom:15px; background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #e2e8f0;">
                            Pilih tindakan untuk naskah ini. Pastikan Anda sudah memberikan revisi pada editor jika diperlukan.
                        </p>

                        <!-- [NEW] REVISION NOTE INPUT (ON PAGE) -->
                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:700; font-size:11px; color:#64748b; margin-bottom:5px;">Catatan Revisi / Instruksi (Wajib jika Revisi)</label>
                            <textarea id="main-revision-note" class="form-control" style="width:100% !important; height:80px !important; font-size:12px !important; padding:8px !important; border:1px solid #cbd5e1 !important; border-radius:6px !important; color:#333 !important; background-color:#fff !important; font-weight:400 !important; opacity:1 !important; text-transform:none !important; box-shadow:none !important;" placeholder="Tuliskan alasan revisi atau instruksi untuk Maker di sini..."></textarea>
                        </div>

                        <!-- Option 1: Update Library Direct -->
                        <button type="button" onclick="submitLibraryUpdate()" <?php if (!empty($newerVersionInfo)) echo 'disabled style="width:100%; border:none; background:#94a3b8; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; opacity:0.6;"'; else echo 'style="width:100%; border:none; background:#3b82f6; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; transition:all 0.2s;"'; ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            1. Update Library (Direct)
                        </button>
                        <div style="font-size:10px; color:#64748b; margin-top:-5px; margin-bottom:15px; padding-left:5px;">
                            • Versi naik (misal 1 -> 2)<br>• Legal/CX Documents: <strong style="color:#16a34a">KEEP (Tetap Ada)</strong>
                        </div>

                        <!-- Option 2: Minor Revision -->
                        <button type="button" onclick="requestMinorRevision()" <?php if (!empty($newerVersionInfo)) echo 'disabled style="width:100%; border:none; background:#94a3b8; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; opacity:0.6;"'; else echo 'style="width:100%; border:none; background:#f97316; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; transition:all 0.2s;"'; ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            2. Request Minor Revision
                        </button>
                        <div style="font-size:10px; color:#64748b; margin-top:-5px; margin-bottom:15px; padding-left:5px;">
                            • Kembali ke Maker<br>• Legal/CX Documents: <strong style="color:#16a34a">KEEP (Tetap Ada)</strong>
                        </div>

                        <!-- Option 3: Major Revision -->
                        <button type="button" onclick="requestMajorRevision()" <?php if (!empty($newerVersionInfo)) echo 'disabled style="width:100%; border:none; background:#94a3b8; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; opacity:0.6;"'; else echo 'style="width:100%; border:none; background:#dc2626; color:white; padding:12px; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; transition:all 0.2s;"'; ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            3. Request Major Revision
                        </button>
                        <div style="font-size:10px; color:#64748b; margin-top:-5px; margin-bottom:0; padding-left:5px;">
                            • Kembali ke Maker (Approval Ulang)<br>• Legal/CX Documents: <strong style="color:#dc2626">RESET (DIHAPUS)</strong>
                        </div>
                    </div>

                    <script>
                    const reqId = document.getElementById('rev_request_id').value;

                    function submitLibraryUpdate() {
                        const noteInput = document.getElementById('main-revision-note');
                        const noteValue = noteInput ? noteInput.value.trim() : '';
                        
                        // VALIDATION: Note is MANDATORY
                        if (!noteValue) {
                            Swal.fire('Catatan Wajib Diisi', 'Mohon isi "Catatan Revisi / Instruksi" pada kolom di atas tombol sebelum melakukan Update Library.', 'warning');
                            if(noteInput) noteInput.focus();
                            return;
                        }

                        Swal.fire({
                            title: 'Update Library Langsung?',
                            html: 'Versi script akan dinaikkan (misal 1 -> 2).<br>Dokumen Legal/CX akan <b>TETAP ADA</b>.<br><br>Pastikan perubahan sudah final.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Update Library',
                            confirmButtonColor: '#3b82f6'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                postAction('update_library_direct', { note: noteValue });
                            }
                        });
                    }

                    function requestMinorRevision() {
                        const noteInput = document.getElementById('main-revision-note');
                        const noteValue = noteInput ? noteInput.value.trim() : '';
                        
                        // VALIDATION: Note is MANDATORY
                        if (!noteValue) {
                            Swal.fire('Catatan Wajib Diisi', 'Mohon isi kolom Catatan Revisi / Instruksi di atas tombol sebelum meminta Minor Revision.', 'warning');
                            if(noteInput) noteInput.focus();
                            return;
                        }

                        Swal.fire({
                            title: 'Minor Revision',
                            text: 'Script akan dikembalikan ke MAKER untuk diperbaiki. Dokumen Legal/CX tetap ada. Lanjutkan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Kirim ke Maker',
                            confirmButtonColor: '#f97316',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                postAction('revise_minor', { note: noteValue });
                            }
                        });
                    }

                    function requestMajorRevision() {
                        const noteInput = document.getElementById('main-revision-note');
                        const noteValue = noteInput ? noteInput.value.trim() : '';
                        
                        // VALIDATION: Note is MANDATORY
                        if (!noteValue) {
                             Swal.fire('Catatan Wajib Diisi', 'Mohon isi kolom Catatan Revisi / Instruksi di atas tombol sebelum meminta Major Revision.', 'warning');
                             if(noteInput) noteInput.focus();
                             return;
                        }

                        // Use prompt() for MAJOR confirmation (Custom Swal doesn't support input)
                        const confirm = prompt('MAJOR REVISION\n\nStatus akan di-reset (Draft Awal).\nReview akan dimulai dari nol.\nDokumen Legal/CX akan DIHAPUS.\n\nKetik MAJOR untuk konfirmasi:');
                        
                        if (confirm === 'MAJOR') {
                            postAction('revise_major', { note: noteValue });
                        } else if (confirm !== null) {
                            Swal.fire('Gagal', 'Anda harus mengetik MAJOR (huruf besar) untuk konfirmasi.', 'error');
                        }
                    }

                    function postAction(action, data = {}) {
                        // 1. Capture Content (Replicated from executeAction)
                        const updatedContent = {};
                        if (typeof IS_FILE_UPLOAD !== 'undefined' && IS_FILE_UPLOAD) {
                            const panes = document.querySelectorAll('.media-pane');
                            if (panes.length > 0 && typeof SERVER_CONTENT !== 'undefined' && SERVER_CONTENT.length > 0) {
                                SERVER_CONTENT.forEach((row, index) => {
                                    const specificPane = document.getElementById(`tab-media-${index}`);
                                    if (specificPane) {
                                        updatedContent[row.id] = specificPane.innerHTML;
                                    }
                                });
                            } else {
                                // [FIX] CROSS-SHEET CONTAMINATION FIX
                                // When content has pre-built tabs (Case A), extract each .sheet-pane separately
                                const editor = document.getElementById('unified-file-editor');
                                if(editor && typeof SERVER_CONTENT !== 'undefined') {
                                    const internalPanes = editor.querySelectorAll('.sheet-pane');
                                    if (internalPanes.length > 0 && internalPanes.length >= SERVER_CONTENT.length) {
                                        // Extract each internal sheet-pane's content separately
                                        SERVER_CONTENT.forEach((row, index) => {
                                            if (internalPanes[index]) {
                                                updatedContent[row.id] = internalPanes[index].innerHTML;
                                                console.log(`[FIX] postAction: Extracted sheet-pane ${index} for ID ${row.id}`);
                                            }
                                        });
                                    } else if (SERVER_CONTENT.length === 1) {
                                        // Truly single sheet — safe to use unified editor
                                        updatedContent[SERVER_CONTENT[0].id] = editor.innerHTML;
                                    } else {
                                        console.error('[ERROR] postAction: Cannot map sheets — pane/content mismatch!', internalPanes.length, SERVER_CONTENT.length);
                                        // Last resort: save unified content to prevent data loss
                                        const html = editor.innerHTML;
                                        SERVER_CONTENT.forEach(row => updatedContent[row.id] = html);
                                    }
                                }
                            }
                        } else {
                            document.querySelectorAll('.free-input-editor').forEach(editor => {
                                const id = editor.getAttribute('data-id');
                                if(id) updatedContent[id] = editor.innerHTML;
                            });
                        }

                        const payload = { 
                            request_id: reqId, 
                            updated_content: updatedContent,
                            ...data 
                        };

                        fetch('?controller=request&action=' + action, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                hasUnsavedChanges = false; // Bypass "Leave Site" check
                                window.onbeforeunload = null;
                                
                                // DETAILED SUCCESS MESSAGE
                                let title = 'Success';
                                let msg = 'Action completed successfully';
                                
                                if (action === 'revise_minor') {
                                    title = 'Revisi Terkirim!';
                                    msg = 'Status script telah diubah menjadi MINOR REVISION.<br>Tugas sekarang dikembalikan ke MAKER untuk diperbaiki.';
                                } else if (action === 'revise_major') {
                                    title = 'Reset Berhasil!';
                                    msg = 'Script telah dikembalikan ke awal (MAJOR). Dokumen Legal/CX telah dihapus.';
                                } else if (action === 'update_library_direct') {
                                    title = 'Library Updated!';
                                    msg = 'Script Library berhasil diperbarui ke versi baru.';
                                }

                                Swal.fire({
                                    title: title,
                                    html: msg,
                                    icon: 'success',
                                    confirmButtonText: 'OK, Kembali ke Dashboard',
                                    allowOutsideClick: false
                                }).then(() => {
                                    window.location.href = 'index.php?controller=dashboard';
                                });
                                
                            } else if (data.error === 'newer_version_exists') {
                                // [VERSION GUARD] Show warning popup with Audit Trail link
                                Swal.fire({
                                    title: 'Versi Lebih Baru Sudah Ada',
                                    html: `<p style="color:#64748b; font-size:13px; margin-bottom:12px;">Script ini sudah memiliki versi yang lebih baru:</p>
                                           <div style="background:#fef3c7; border:1px solid #fbbf24; border-radius:8px; padding:10px; margin-bottom:12px;">
                                               <div style="font-weight:700; color:#92400e; font-size:14px;">\u2192 ${data.newer_script}</div>
                                               <div style="color:#a16207; font-size:12px; margin-top:4px;">Status: ${data.newer_status}</div>
                                           </div>
                                           <p style="color:#64748b; font-size:12px;">Silakan gunakan versi terbaru untuk melakukan perubahan.</p>`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Lihat di Audit Trail',
                                    cancelButtonText: 'Tutup',
                                    confirmButtonColor: '#f59e0b',
                                    cancelButtonColor: '#94a3b8'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = '?controller=audit&action=detail&id=' + data.newer_id;
                                    }
                                });
                            } else {
                                Swal.fire('Error', data.error || 'Operation failed', 'error');
                            }
                        })
                        .catch(err => Swal.fire('Error', 'Network error: ' + err, 'error'));
                    }
                    </script>

                <?php else: ?>
                    <!-- STANDARD APPROVAL FORM (Existing) -->
                    <form id="approvalForm">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        
                        <?php 
                        // FIX: Case-insensitive Check for Form
                        $currentDeptForm = isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : '';
                        if ($currentDeptForm === 'PROCEDURE'): 
                        ?>
                        <!-- PROCEDURE DOCUMENTS SECTION (Moved to shared block) -->

                        <div style="margin-bottom:15px; padding:15px; background:#f1f5f9; border-radius:8px;">
                            <h5 style="margin:0 0 10px 0; color:#475569; font-size:11px; font-weight:700; text-transform:uppercase;">Revision Documents</h5>
                            
                            <div style="margin-bottom:10px;">
                                <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">Legal Document <span style="color:red">*</span></label>
                                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                                    <input type="file" id="file_legal" class="form-control" style="font-size:11px; padding:4px; flex: 1; min-width: 0;">
                                    <button type="button" onclick="previewLocalDocument('file_legal')" class="btn btn-sm" style="background:#e2e8f0; border:1px solid #cbd5e1; font-size:14px; padding:4px 10px; border-radius:4px; cursor:pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" title="Buka / Download Dokumen"><i class="fi fi-rr-download" style="line-height: 1;"></i></button>
                                </div>
                                <div id="status_legal" style="font-size:10px; margin-top:2px;"></div>
                            </div>
                            <div style="margin-bottom:10px;">
                                <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">CX Document <span style="color:red">*</span></label>
                                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                                    <input type="file" id="file_cx" class="form-control" style="font-size:11px; padding:4px; flex: 1; min-width: 0;">
                                    <button type="button" onclick="previewLocalDocument('file_cx')" class="btn btn-sm" style="background:#e2e8f0; border:1px solid #cbd5e1; font-size:14px; padding:4px 10px; border-radius:4px; cursor:pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" title="Buka / Download Dokumen"><i class="fi fi-rr-download" style="line-height: 1;"></i></button>
                                </div>
                                <div id="status_cx" style="font-size:10px; margin-top:2px;"></div>
                            </div>
                             <div style="margin-bottom:10px;">
                                <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">Legal Syariah</label>
                                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                                    <input type="file" id="file_syariah" class="form-control" style="font-size:11px; padding:4px; flex: 1; min-width: 0;">
                                    <button type="button" onclick="previewLocalDocument('file_syariah')" class="btn btn-sm" style="background:#e2e8f0; border:1px solid #cbd5e1; font-size:14px; padding:4px 10px; border-radius:4px; cursor:pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" title="Buka / Download Dokumen"><i class="fi fi-rr-download" style="line-height: 1;"></i></button>
                                </div>
                                <div id="status_syariah" style="font-size:10px; margin-top:2px;"></div>
                            </div>
                             <div>
                                <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">LPP Document</label>
                                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                                    <input type="file" id="file_lpp" class="form-control" style="font-size:11px; padding:4px; flex: 1; min-width: 0;">
                                    <button type="button" onclick="previewLocalDocument('file_lpp')" class="btn btn-sm" style="background:#e2e8f0; border:1px solid #cbd5e1; font-size:14px; padding:4px 10px; border-radius:4px; cursor:pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" title="Buka / Download Dokumen"><i class="fi fi-rr-download" style="line-height: 1;"></i></button>
                                </div>
                                <div id="status_lpp" style="font-size:10px; margin-top:2px;"></div>
                            </div>
                        </div>
                        
                        <script>
                        // Client-Side Document Preview Logic
                        function previewLocalDocument(inputId) {
                            const input = document.getElementById(inputId);
                            const docType = inputId.replace('file_', '').toUpperCase();
                            
                            let fileURL = '';
                            let fileName = '';
                            let isLocal = false;
                            
                            // 1. Check if file is already uploaded to server (REVIEW_EVIDENCE)
                            if (typeof REVIEW_EVIDENCE !== 'undefined' && REVIEW_EVIDENCE[docType] && REVIEW_EVIDENCE[docType].length > 0) {
                                // For preview, we just take the first/latest one if multiple exist
                                const serverFile = REVIEW_EVIDENCE[docType][0];
                                fileName = serverFile.filename;
                                // Use the controller endpoint to fetch the file safely
                                fileURL = '?controller=request&action=downloadReviewDoc&file_id=' + serverFile.id;
                            }  
                            // 2. Check if file is selected locally but not yet uploaded
                            else if (input && input.files && input.files.length > 0) {
                                const localFile = input.files[0];
                                fileName = localFile.name;
                                fileURL = URL.createObjectURL(localFile);
                                isLocal = true;
                            } 
                            // 3. No file found
                            else {
                                Swal.fire('Info', 'Belum ada file yang dipilih atau diunggah untuk di-preview.', 'info');
                                return;
                            }
                            
                            // Determine file type from extension
                            const ext = fileName.split('.').pop().toLowerCase();
                            const isPdf = (ext === 'pdf');
                            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                            
                            // Check file type
                            if (isPdf) {
                                // For PDFs, open in a new popup window or tab
                                window.open(fileURL, '_blank', 'width=800,height=600,titlebar=no,toolbar=no');
                            } else if (isImage) {
                                // For Images, use SweetAlert for a quick modal
                                Swal.fire({
                                    title: fileName,
                                    imageUrl: fileURL,
                                    imageAlt: 'Document Preview',
                                    html: '<span style="font-size:12px;color:#64748b;">Pratinjau Gambar</span>',
                                    width: 'auto',
                                    padding: '1em',
                                    showCloseButton: true,
                                    confirmButtonText: 'Tutup'
                                });
                            } else {
                                // For Word, Excel, Email messages (.msg, .eml) -> Fallback to download prompt
                                Swal.fire({
                                    title: 'Preview Tidak Didukung',
                                    text: 'Browser tidak dapat menampilkan format file ini (hanya PDF/Gambar). File akan diunduh untuk Anda buka di aplikasi bawannya.',
                                    icon: 'info',
                                    showCancelButton: true,
                                    confirmButtonText: 'Unduh & Buka',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const a = document.createElement('a');
                                        a.href = fileURL;
                                        a.download = isLocal ? 'Preview_' + fileName : fileName;
                                        if(!isLocal) a.target = '_blank'; // server files might need this
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                    }
                                });
                            }
                        }
                        </script>
                        <?php endif; ?>

                        <?php if ($currentDeptForm === 'SPV'): ?>
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:5px; font-weight:600; color:#334155; font-size:12px;">
                                PILIH PIC <span style="color:red;">*</span>
                            </label>
                            <select id="selected_pic" name="selected_pic" class="form-select" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155; outline:none; transition:all 0.2s;">
                                <option value="">-- Pilih PIC --</option>
                                <?php
                                $reqModel = new \App\Models\RequestModel();
                                $pics = $reqModel->getPICs();
                                foreach ($pics as $pic):
                                ?>
                                <option value="<?= htmlspecialchars($pic['userid']) ?>">
                                    <?= htmlspecialchars($pic['fullname']) ?> (<?= htmlspecialchars($pic['userid']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:5px; font-weight:600; color:#334155; font-size:12px;">DECISION</label>
                            <select id="decision" name="decision" class="form-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155; outline:none; transition:all 0.2s;" onchange="toggleRemarks()">
                                <option value="" disabled>-- Select Decision --</option>
                                <option value="APPROVE" selected>Approve (Acc)</option>
                                <option value="REVISE">Revise (Perbaikan)</option>
                                <option value="REJECT">Reject (Tolak)</option>
                            </select>
                        </div>
        
                        <div id="remarksGroup" style="display:none; margin-top:15px;">
                            <label id="remarksLabel" class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
                                Remarks / Notes <span id="remarksRequired" style="color:red">*</span>
                            </label>
                            <textarea name="remarks" id="remarks" rows="3" class="form-control" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; background-color:#f8fafc; font-family:inherit; font-size:13px; resize:none;" placeholder="Reason..."></textarea>
                        </div>
        
                        <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                            <button type="button" onclick="submitDecision()" id="btnSubmitDecision" style="width:100%; padding:12px; background:linear-gradient(135deg, var(--primary-red) 0%, #be123c 100%); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; box-shadow:0 2px 4px rgba(220, 38, 38, 0.15); height:45px; display:flex; align-items:center; justify-content:center;">
                                Submit Decision
                            </button>
                            <?php if (isset($_SESSION['user']['dept']) && strtoupper($_SESSION['user']['dept']) === 'PROCEDURE'): ?>
                            <button type="button" onclick="sendToMakerFlow()" style="width:100%; padding:12px; background:linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; box-shadow:0 2px 4px rgba(139, 92, 246, 0.2); height:45px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"></path><path d="M22 2L15 22L11 13L2 9L22 2Z"></path></svg>
                                Kirim ke Maker
                            </button>
                            <?php endif; ?>
                             <button type="button" onclick="saveDraft()" style="width:100%; padding:12px; background:#fff; color:#475569; border:1px solid #e2e8f0; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px; height:45px; display:flex; align-items:center; justify-content:center; transition:all 0.2s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='#fff'">
                                Save Draft
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <!-- READ ONLY MODE / FINAL STATUS -->
                <div class="card" style="background:#f8fafc; padding:20px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                    <div style="background:<?php echo ($request['status']=='REJECTED')?'#fee2e2':'#dcfce7'; ?>; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px auto;">
                        <?php if($request['status']=='REJECTED'): ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <?php else: ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <h4 style="margin:0 0 5px 0; color:#1e293b;">Request Finalized</h4>
                    <p style="margin:0; font-size:13px; color:#64748b;">
                        Status: <strong style="color:<?php echo ($request['status']=='REJECTED')?'#ef4444':'#16a34a'; ?>;"><?php echo $request['status']; ?></strong>
                    </p>
                    
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['dept'] === 'PROCEDURE' && $request['status'] === 'LIBRARY'): ?>
                        <div style="margin-top:15px; padding-top:15px; border-top:1px solid #e2e8f0;">
                             <button type="button" onclick="downloadReviewExcel()" style="padding:8px 16px; background:#0f766e; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; display:inline-flex; align-items:center; gap:6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Download Final Excel
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
    </div>
</div>

<!-- PRINT AREA (Hidden by default, Visible on Print) -->
<div id="print-area" style="display:none;">
    <div class="print-header">
        <div style="display:flex; align-items:center; gap:10px;">
            <img src="public/assets/images/logo.png" alt="Logo" class="print-logo" onerror="this.style.display='none'">
            <div class="print-title">Final Ticket Script</div>
        </div>
        <div style="text-align:right; font-size:9px;">
            <div>Ref: <span id="p-script-id" style="font-weight:bold;"></span></div>
            <div>Generated: <span id="p-generated-date"></span></div>
        </div>
    </div>

    <!-- Request Information COMPACT -->
    <div class="print-section">
        <div class="print-section-title">Request Info</div>
        <div style="display:grid; grid-template-columns: auto 1fr; gap: 2px 8px; font-size: 9px; align-items: baseline;">
            <div class="print-label">Ticket ID:</div> <div id="p-ticket-id"></div>
            <div class="print-label">Product:</div> <div id="p-product"></div>
            <div class="print-label">Title:</div> <div id="p-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
            <div class="print-label">Jenis:</div> <div id="p-jenis"></div>
            <div class="print-label">Kategori:</div> <div id="p-kategori"></div>
            <div class="print-label">Media:</div> <div id="p-media"></div>
            <div class="print-label">Request Created:</div> <div id="p-created"></div>
        </div>
    </div>

    <!-- Script Content -->
    <div class="print-section">
        <div class="print-section-title">Script Content</div>
        <div id="p-content-container">
            <!-- Content will be injected here -->
        </div>
    </div>

    <!-- Timeline / History -->
    <div class="print-section">
        <div class="print-section-title">Timeline & History</div>
        <style>
            .print-timeline-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 10px;
                table-layout: fixed; /* [FIX] Force fixed layout */
            }
            .print-timeline-table th, .print-timeline-table td {
                border: 1px solid #000;
                padding: 6px 8px; /* [FIX] Better padding */
                vertical-align: middle;
                word-wrap: break-word; /* [FIX] Prevent overflow */
            }
            .print-timeline-table th {
                background-color: #f0f0f0;
                font-weight: bold;
                text-align: left;
            }
        </style>
        <table class="print-timeline-table">
            <colgroup>
                <col style="width: 15%;"> <!-- Action -->
                <col style="width: 20%;"> <!-- User -->
                <col style="width: 25%;"> <!-- Group -->
                <col style="width: 25%;"> <!-- Date -->
                <col style="width: 15%;"> <!-- Status -->
            </colgroup>
            <thead>
                <tr>
                    <th>ACTION</th>
                    <th>USER</th>
                    <th>GROUP</th>
                    <th>DATE</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Created</td>
                    <td id="p-maker-name" style="font-weight:bold;"></td>
                    <td id="p-maker-group"></td> 
                    <td id="p-maker-date"></td>
                    <td>Submitted</td>
                </tr>
                <tr>
                    <td>Reviewed</td>
                    <td id="p-spv-name" style="font-weight:bold;">(Pending SPV)</td>
                    <td id="p-spv-group">-</td>
                    <td id="p-spv-date">-</td>
                    <td id="p-spv-status">Pending</td>
                </tr>
                <tr>
                    <td>Checked</td>
                    <td id="p-pic-name" style="font-weight:bold;">(Pending PIC)</td>
                    <td id="p-pic-group">-</td>
                    <td id="p-pic-date">-</td>
                    <td id="p-pic-status">Pending</td>
                </tr>
                 <tr>
                    <td>Finalized</td>
                    <td id="p-proc-name" style="font-weight:bold;"><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></td>
                    <td id="p-proc-group">-</td>
                    <td id="p-proc-date">-</td>
                    <td id="p-proc-status">In Review</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Approval Columns -->
    <div class="approval-container">
        <div class="approval-box">
            <div class="approval-title">LEGAL</div>
            <div class="approval-sign" id="p-sign-legal">
                <div class="approval-line">Name & Signature</div>
            </div>
        </div>
        <div class="approval-box">
            <div class="approval-title">CX</div>
            <div class="approval-sign" id="p-sign-cx">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
        <div class="approval-box">
            <div class="approval-title">LEGAL SYARIAH</div>
            <div class="approval-sign" id="p-sign-syariah">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
         <div class="approval-box">
            <div class="approval-title">LPP</div>
            <div class="approval-sign" id="p-sign-lpp">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
    </div>

    <div class="print-footer">
        <div>Printed by System</div>
        <div>Page <span class="page-number"></span></div>
    </div>
</div>

<!-- Comment Modal -->
<div id="comment-modal" data-ignore-revision="true" onclick="if(event.target.id==='comment-modal') closeCommentModal();" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:20px; border-radius:8px; width:400px; max-width:90%; box-shadow:0 4px 12px rgba(0,0,0,0.15); font-family:'Inter', sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; font-size:16px; font-weight:700; color:#333;">Add Comment</h3>
            <button onclick="closeCommentModal()" style="background:none; border:none; color:#888; cursor:pointer; font-size:18px;">&times;</button>
        </div>
        
        <div style="margin-bottom:12px; font-size:12px; color:#666; background:#f8fafc; padding:8px; border-left:3px solid #eab308; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; max-width:100%;">
            Currently selected: <strong id="comment-selected-text" style="color:#333;">-</strong>
        </div>
        
        <textarea id="comment-input" style="width:100%; box-sizing:border-box; height:100px; padding:10px; border:1px solid #ccc; border-radius:4px; font-family:inherit; margin-bottom:15px; font-size:14px; resize:vertical; outline:none; line-height:1.5;" placeholder="Type correction here..."></textarea>
        
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button onclick="closeCommentModal()" style="padding:8px 16px; border:1px solid #ddd; background:white; color:#555; border-radius:4px; cursor:pointer; font-size:13px; font-weight:500;">Cancel</button>
            <button onclick="submitComment()" style="padding:8px 20px; background:#eab308; color:white; border:none; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">Save</button>
        </div>
    </div>
</div>

<!-- Hidden container for deleted/removed revision spans -->
<div id="deleted-revisions-container" style="display:none;" aria-hidden="true"></div>


<script>
    // Inject PHP Data for Print Function
    const requestData = <?php echo json_encode($request ?? []); ?>;
    const requestLogs = <?php echo json_encode($timeline ?? []); ?>;
    const currentUser = <?php echo json_encode($_SESSION['user'] ?? []); ?>;
    // [NEW] Inject Script File Data
    const scriptFileData = <?php echo json_encode($scriptFile ?? null); ?>;
</script>

<script>
// ========================================
// REVISION SPAN PRESERVATION (MutationObserver)
// ========================================
// When user deletes revision text, preserve the span in hidden container
// so override detection still works

document.addEventListener('DOMContentLoaded', () => {
    const hiddenContainer = document.getElementById('deleted-revisions-container');
    if (!hiddenContainer) {
        console.warn('Deleted revisions container not found');
        return;
    }
    
    // MutationObserver to detect when revision spans are removed from DOM
    // [FIX] Global flag: skip preservation during internal DOM operations
    // (undo/redo, span merger, deletion cleanup, etc.)
    if (typeof window._suppressRevisionPreservation === 'undefined') window._suppressRevisionPreservation = false;

    const revisionObserver = new MutationObserver((mutations) => {
        // [FIX] Skip preservation during internal DOM operations
        if (window._suppressRevisionPreservation) return;

        mutations.forEach((mutation) => {
            if (mutation.type === 'childList' && mutation.removedNodes.length > 0) {
                mutation.removedNodes.forEach((node) => {
                    // Check if removed node is a revision span or contains revision spans
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        let revisionSpans = [];
                        
                        // Check if node itself is a revision span (by data-revision-location attribute)
                        if (node.hasAttribute && node.hasAttribute('data-revision-location')) {
                            revisionSpans.push(node);
                        }
                        
                        // Check if node contains revision spans
                        if (node.querySelectorAll) {
                            const childSpans = node.querySelectorAll('[data-revision-location]');
                            revisionSpans.push(...Array.from(childSpans));
                        }
                        
                        // Move revision spans to hidden container
                        revisionSpans.forEach((span) => {
                            // Only preserve if not already in hidden container
                            if (!hiddenContainer.contains(span)) {
                                console.log('[Revision Preservation] Moving deleted span to hidden container:', span.id);
                                hiddenContainer.appendChild(span);
                            }
                        });
                    }
                });
            }
        });
    });
    
    // Observe the entire document body for span removals
    revisionObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('[Revision Versioning] MutationObserver initialized - spans will be preserved on deletion');
});

const IS_FILE_UPLOAD = <?php echo ($isFileUpload || !empty($files['TEMPLATE'])) ? 'true' : 'false'; ?>;
const SERVER_CONTENT = <?php echo json_encode($content); ?>;
<?php
// Extract real filename for JS
$realFilename = 'Document.docx'; // Default
if (!empty($files)) {
    foreach ($files as $f) {
        if ($f['file_type'] === 'TEMPLATE') {
            $realFilename = $f['original_filename'];
            break;
        }
    }
}
// Extract Review Evidence (Array of files)
$reviewEvidence = [
    'LEGAL' => [],
    'CX' => [],
    'SYARIAH' => [],
    'LPP' => []
];
if (!empty($files)) {
    foreach ($files as $f) {
        if (array_key_exists($f['file_type'], $reviewEvidence)) {
            $reviewEvidence[$f['file_type']][] = [
                'id' => $f['id'],
                'filename' => $f['original_filename'],
                'uploaded_at' => $f['uploaded_at'] ?? null,
                'uploaded_by' => $f['uploaded_by'] ?? null
            ];
        }
    }
}
?>
const REVIEW_EVIDENCE = <?php echo json_encode($reviewEvidence); ?>;
const CURRENT_USER_NAME = "<?php echo htmlspecialchars($_SESSION['user']['fullname'] ?? 'Reviewer'); ?>";
const CURRENT_USER_ROLE = "<?php echo isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : ''; ?>";
const CURRENT_USER_JOB_FUNCTION = "<?php echo htmlspecialchars($_SESSION['user']['job_function'] ?? ($_SESSION['user']['dept'] ?? 'Reviewer')); ?>";
<?php
// [FIX] Resolve Name of Last Modifier for Legacy Comments
$lastModifierName = 'Reviewer';
$lastModifierId = null;
$lastModifierRole = 'Reviewer';

// HEURISTIC: Check Audit Trail for last relevant action
$logs = $auditTrail ?? ($detail['logs'] ?? []);
if (!empty($logs)) {
    // Iterate to find the LATEST relevant action
    foreach ($logs as $log) {
        // Actions that imply modification or review
        if (in_array($log['action'], ['REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION', 'APPROVED_SPV', 'APPROVED_PIC', 'APPROVED_PROCEDURE', 'DRAFT_SAVED'])) {
             $lastModifierId = $log['user_id'];
             $lastModifierRole = $log['user_role'] ?? 'Reviewer';
             // Keep looping to find the VERY LAST one (assuming ASC order)
        }
    }
}

// Fallback to updated_by if no audit log found (e.g. migration)
if (!$lastModifierId && !empty($request['updated_by'])) {
    $lastModifierId = $request['updated_by'];
    $lastModifierRole = $request['current_role'] ?? 'Reviewer';
}

// Resolve Name
if ($lastModifierId) {
    if ($lastModifierId == $request['created_by']) { // Use loose comparison for string/int IDs
        $lastModifierName = $request['maker_name'] ?? 'Maker';
    } elseif ($lastModifierId == $request['selected_spv']) {
        $lastModifierName = $request['selected_spv_name'] ?? 'Supervisor';
    } elseif (!empty($request['selected_pic']) && $lastModifierId == $request['selected_pic']) {
        $lastModifierName = $request['selected_pic_name'] ?? 'PIC';
    } else {
        // Check if we can map usage of ADMIN
        if (strtoupper($lastModifierId) === 'ADMIN') $lastModifierName = 'Administrator';
        else $lastModifierName = $lastModifierRole; 
    }
}
?>
const LAST_MODIFIER_NAME = "<?php echo htmlspecialchars($lastModifierName); ?>";
console.log("[DEBUG] Resolved LAST_MODIFIER_NAME:", LAST_MODIFIER_NAME);



var AUDIT_LOG_INFO = "<?php echo isset($logs) ? (is_array($logs) ? 'Array('.count($logs).')' : gettype($logs)) : 'Undefined'; ?>";
console.log("[DEBUG] Audit Trail Info:", AUDIT_LOG_INFO);

var REQ_DEBUG_INFO = "<?php 
    echo 'CreatedBy:' . ($request['created_by']??'N/A') . ', UpdatedBy:' . ($request['updated_by']??'N/A') . 
         ', Spv:' . ($request['selected_spv']??'N/A') . ', Pic:' . ($request['selected_pic']??'N/A') .
         ', LastModID:' . ($lastModifierId??'NULL') . ', LastModRole:' . ($lastModifierRole??'NULL');
?>";
console.log("[DEBUG] Request Info:", REQ_DEBUG_INFO);

// Init Status if files exist
document.addEventListener('DOMContentLoaded', () => {
    // Helper to render list
    const renderList = (type, containerId) => {
        const container = document.getElementById(containerId);
        if(!container) return;
        container.innerHTML = '';
        
        if (REVIEW_EVIDENCE[type] && REVIEW_EVIDENCE[type].length > 0) {
            REVIEW_EVIDENCE[type].forEach(file => {
                const div = document.createElement('div');
                div.style.marginBottom = '2px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.justifyContent = 'space-between';
                div.innerHTML = `
                    <span style="color:green">✅ ${file.filename}</span>
                    <button type="button" onclick="deleteReviewDoc('${type}', '${file.id}', this)" style="background:none; border:none; color:red; cursor:pointer; font-size:10px; margin-left:4px;" title="Delete">❌</button>
                `;
                container.appendChild(div);
            });
        }
    };

    renderList('LEGAL', 'status_legal');
    renderList('CX', 'status_cx');
    renderList('SYARIAH', 'status_syariah');
    renderList('LPP', 'status_lpp');

    // AUTO-ENABLE EDIT MODE for SPV to ensure Auto-Red Listeners are active
    // This fixes the 'Resubmit -> Auto Red Fails' issue
    if (typeof enableEditMode === 'function') {
        // You might want to restrict this by Status too, but for SPV on review page, 
        // they usually expect to edit.
        if (CURRENT_USER_ROLE === 'SPV' || CURRENT_USER_ROLE === 'PROC' || CURRENT_USER_ROLE === 'PIC') {
             console.log("[DEBUG] Auto-Enabling Edit Mode for Reviewer");
             enableEditMode();
        }
    }
});
function checkValidLawrAttributes() {
    return true;
}

// SHEETJS DOWNLOAD FUNCTION (DOM-BASED - Works for old and new requests)
async function downloadReviewExcel() {
    if (typeof ExcelJS === 'undefined') {
        Swal.fire({
            title: 'Module Missing', 
            html: 'ExcelJS libraries not found. <br><small>Please save exceljs.min.js in public/assets/js/</small>', 
            icon: 'error'
        });
        return;
    }

    const scriptNum = '<?php echo htmlspecialchars($request["script_number"]); ?>';

    // [FIX] Temporarily show ALL hidden tab panes so cloneNode picks up content
    const hiddenPanes = [];
    document.querySelectorAll('.media-pane, .sheet-pane, .review-tab-content, [id^="tab-media-"], [id^="tab-"]').forEach(el => {
        if (el.style.display === 'none' || getComputedStyle(el).display === 'none') {
            hiddenPanes.push({ el, orig: el.style.display });
            el.style.display = 'block';
        }
    });

    let sheets = Array.from(document.querySelectorAll('.media-pane, .sheet-pane, .review-tab-content'));
    sheets = [...new Set(sheets)];
    if (sheets.length === 0) {
        sheets = Array.from(document.querySelectorAll('.media-tab-pane, [id^="sheet-"], [id^="tab-media-"]'));
    }
    
    if (sheets.length === 0) {
        // Restore hidden panes before returning
        hiddenPanes.forEach(p => p.el.style.display = p.orig);
        Swal.fire('Error', 'No content available to download', 'warning');
        return;
    }

    console.log(`Found ${sheets.length} sheet(s) in DOM`);

    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'System';
    workbook.created = new Date();

    let hasContent = false;
    
    sheets.forEach((sheet, index) => {
        let sheetName = sheet.getAttribute('data-media');
        
        if (!sheetName && sheet.id) {
            const sheetId = sheet.id;
            let btnId = sheetId.replace('tab-media-', 'tab-media-btn-').replace('tab-', 'tab-btn-review-');
            let btn = document.getElementById(btnId);
            
            if (!btn) {
                const buttons = document.querySelectorAll('.btn-sheet, .btn-media-tab, .btn-media-tab-unified');
                for (let b of buttons) {
                    const clickAttr = b.getAttribute('onclick');
                    if (clickAttr && (clickAttr.includes(`'${sheetId}'`) || clickAttr.includes(`"${sheetId}"`))) {
                        btn = b;
                        break;
                    }
                }
            }

            if (!btn && (sheetId.startsWith('tab-media-') || sheetId.startsWith('tab-'))) {
                 const idx = sheetId.split('-').pop();
                 btn = document.getElementById(`tab-media-btn-${idx}`) || document.getElementById(`tab-btn-review-${idx}`);
            }
            
            if (btn) sheetName = btn.innerText.trim();
            if (!sheetName) sheetName = sheetId.replace('tab-media-', 'Media ').replace('tab-', 'Content ');
        }

        if (!sheetName) sheetName = `Sheet ${index + 1}`;
        sheetName = sheetName.replace(/[:\\/?*[\]]/g, '');
        if (sheetName.length > 31) sheetName = sheetName.substring(0, 31);
        
        // Prevent duplicate tab names
        let finalSheetName = sheetName;
        let suffix = 1;
        while (workbook.getWorksheet(finalSheetName)) {
             finalSheetName = `${sheetName} (${suffix})`;
             suffix++;
        }
        
        console.log(`Processing sheet: "${finalSheetName}"`);
        
        // Add native worksheet tab!
        const worksheet = workbook.addWorksheet(finalSheetName);
        hasContent = true;

        const cleanSheet = sheet.cloneNode(true);
        cleanSheet.querySelectorAll('.deletion-span').forEach(el => el.remove());
        
        let table = cleanSheet.querySelector('table');
        
        if (table) {
            let cols = [];
            const headerRow = table.querySelector('tr');
            if (headerRow) {
                Array.from(headerRow.cells).forEach((cell, i) => {
                    let w = 25;
                    if (i === 1) w = 10;
                    if (i === 3) w = 100;
                    cols.push({ header: cell.innerText.trim(), width: w });
                });
            }
            if (cols.length === 0) Object.assign(cols, [ {width: 25}, {width: 10}, {width: 25}, {width: 100}, {width: 25}, {width: 25} ]);
            worksheet.columns = cols;
            
            // Format Header
            worksheet.getRow(1).eachCell((cell) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0E0E0' } };
                cell.font = { bold: true };
                cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            const rows = table.querySelectorAll('tr');
            rows.forEach((tr, rowIndex) => {
                if (rowIndex === 0 && headerRow) return; 
                
                const excelRow = worksheet.addRow([]);
                Array.from(tr.cells).forEach((td, cellIndex) => {
                    const cell = excelRow.getCell(cellIndex + 1);
                    cell.alignment = { vertical: 'top', wrapText: true };
                    cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };

                    const richTextParams = [];
                    function parseNodeEx(node, isRed) {
                        if (node.nodeType === 3) {
                            if (node.textContent) {
                                let txt = node.textContent.replace(/\n\s*\n/g, '\n'); 
                                richTextParams.push({ text: txt, font: isRed ? { name: 'Arial', size: 11, color: { argb: 'FFFF0000' }, bold: true } : { name: 'Arial', size: 11 } });
                            }
                        } else if (node.nodeType === 1) {
                            if (node.tagName.toLowerCase() === 'br') {
                                richTextParams.push({ text: '\n' });
                            } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                                if (richTextParams.length > 0 && !richTextParams[richTextParams.length-1].text.endsWith('\n')) {
                                    richTextParams.push({ text: '\n' });
                                }
                                node.childNodes.forEach(n => parseNodeEx(n, isRed));
                                richTextParams.push({ text: '\n' });
                            } else {
                                let currentlyRed = isRed || node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444';
                                node.childNodes.forEach(n => parseNodeEx(n, currentlyRed));
                            }
                        }
                    }
                    td.childNodes.forEach(n => parseNodeEx(n, false));
                    
                    if (richTextParams.length > 0) {
                        cell.value = { richText: richTextParams };
                    } else {
                        cell.value = td.innerText;
                        cell.font = { name: 'Arial', size: 11 };
                    }
                });
            });
        } else {
            worksheet.columns = [
                { width: 120 }
            ];

            const richTextParams = [];
            function parseNodeEx(node, isRed) {
                if (node.nodeType === 3) {
                    if (node.textContent) {
                        let txt = node.textContent.replace(/\n\s*\n/g, '\n'); 
                        richTextParams.push({ text: txt, font: isRed ? { name: 'Arial', size: 11, color: { argb: 'FFFF0000' }, bold: true } : { name: 'Arial', size: 11 } });
                    }
                } else if (node.nodeType === 1) {
                    if (node.tagName.toLowerCase() === 'br') {
                        richTextParams.push({ text: '\n' });
                    } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                        if (richTextParams.length > 0 && !richTextParams[richTextParams.length-1].text.endsWith('\n')) {
                            richTextParams.push({ text: '\n' });
                        }
                        node.childNodes.forEach(n => parseNodeEx(n, isRed));
                        richTextParams.push({ text: '\n' });
                    } else {
                        let currentlyRed = isRed || node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444';
                        node.childNodes.forEach(n => parseNodeEx(n, currentlyRed));
                    }
                }
            }
            cleanSheet.childNodes.forEach(n => parseNodeEx(n, false));

            const cellContent = worksheet.getCell('A1');
            if (richTextParams.length > 0) {
                cellContent.value = { richText: richTextParams };
            } else {
                cellContent.value = cleanSheet.innerText;
                cellContent.font = { name: 'Arial', size: 11 };
            }
            cellContent.alignment = { vertical: 'top', wrapText: true };
        }
    });

    if (!hasContent) {
        Swal.fire('Error', 'No content to download', 'warning');
        return;
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Script_${scriptNum}.xlsx`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    // [FIX] Restore hidden panes
    hiddenPanes.forEach(p => p.el.style.display = p.orig);
    
    console.log('✓ ExcelJS Download complete!');
}


let currentColorMode = 'RED'; // Legacy Global
let isEditing = false;
let savedRange = null; 
let hasUnsavedChanges = false;
const uploadedDocs = { legal: null, cx: null };

// --- TOGGLE EDIT MODE ---
// --- RESTORED FUNCTIONS FOR AUTO RED & HIGHLIGHT MOVED/CONSOLIDATED BELOW ---
// This block contained duplicates which are now removed to prevent conflicts.


// --- LEGACY COMMENT MODAL ---
function addComment() {
    // For Free Input, always allow comment (no need enable editing)
    const currentMode = IS_FILE_UPLOAD;
    
    if (currentMode && !isEditing) {
        Swal.fire({ title: 'Enable Editing First', text: 'Please click Enable Editing before adding comments.', icon: 'warning' });
        return;
    }
    
    // [REMOVED] Legal/CX mode check for comment disabling
    
    const selection = window.getSelection();
    if (!selection.rangeCount || selection.isCollapsed) {
        Swal.fire({ title: 'Select Text First', text: 'Please select some text to comment on.', icon: 'info' });
        return;
    }
    
    // Safety: Check if selection is inside editable area
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;
    const editableParent = container.nodeType === 3 
        ? container.parentElement.closest('[contenteditable="true"]')
        : container.closest('[contenteditable="true"]');
    
    if (!editableParent) {
        Swal.fire({ title: 'Invalid Selection', text: 'Please select text inside the editor.', icon: 'warning' });
        return;
    }
    
    savedRange = range;
    
    const modal = document.getElementById('comment-modal');
    // Set Selected Text Preview
    const previewEl = document.getElementById('comment-selected-text');
    if (previewEl) {
        let text = range.toString();
        if (text.length > 50) text = text.substring(0, 50) + '...';
        previewEl.textContent = text;
    }

    modal.style.display = 'flex';
    void modal.offsetWidth; 
    modal.style.opacity = '1';
    modal.querySelector('div').style.transform = 'scale(1)';
    
    const inp = document.getElementById('comment-input');
    
    // NUCLEAR OPTION: Clone textarea to kill all attached listeners
    const newInp = inp.cloneNode(true);
    inp.parentNode.replaceChild(newInp, inp);
    const cleanInp = document.getElementById('comment-input');
    
    cleanInp.value = '';
    
    // TRAP FOCUS & BLOCK BACKGROUND EDITING
    // 1. Disable all editors temporarily to prevent typing leak
    document.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.setAttribute('contenteditable', 'false');
        el.classList.add('temp-disabled-edit');
    });
    
    // 2. CRITICAL: Block ALL events from textarea from bubbling up
    // This prevents revision logic from capturing keystrokes
    const blockEvents = ['keydown', 'keyup', 'keypress', 'input', 'beforeinput', 'textInput'];
    blockEvents.forEach(eventType => {
        cleanInp.addEventListener(eventType, (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }, { once: false, capture: true });
    });
    
    // 3. Focus input with delay
    setTimeout(() => {
        cleanInp.focus();
    }, 150);
}

function closeCommentModal() {
    const modal = document.getElementById('comment-modal');
    modal.style.opacity = '0';
    modal.querySelector('div').style.transform = 'scale(0.95)';
    
    // RESTORE EDITING
    document.querySelectorAll('.temp-disabled-edit').forEach(el => {
        el.setAttribute('contenteditable', 'true');
        el.classList.remove('temp-disabled-edit');
    });
    
    setTimeout(() => {
        modal.style.display = 'none';
        savedRange = null;
    }, 200);
}

function submitComment() {
    const inp = document.getElementById('comment-input');
    const text = inp.value.trim();
    if (!text) { 
        Swal.fire('Error', 'Comment cannot be empty.', 'error');
        return; 
    }
    
    if (!savedRange) { 
        closeCommentModal(); return; 
    }
    
    // CRITICAL FIX: Re-enable contenteditable BEFORE inserting span
    // Otherwise insertNode/surroundContents will fail silently
    document.querySelectorAll('.temp-disabled-edit').forEach(el => {
        el.setAttribute('contenteditable', 'true');
        el.classList.remove('temp-disabled-edit');
    });
    
    
    // SAFETY CHECK: Prevent Multi-Cell Selection (File Upload only)
    if (IS_FILE_UPLOAD) {
        const clone = savedRange.cloneContents();
        if (clone.querySelector('td, th, tr, tbody, table')) {
             Swal.fire({ 
                 title: '⚠️ Aksi Dibatasi', 
                 html: 'Tidak dapat memberi komentar lintas kolom tabel.<br>Mohon blok teks di dalam satu kolom saja.', 
                 icon: 'warning' 
             });
             closeCommentModal();
             return;
        }
    }
    
    // Safety: Check for nested comments
    const cloneTest = savedRange.cloneContents();
    if (cloneTest.querySelector('.inline-comment')) {
        Swal.fire({ 
            title: 'Cannot Nest Comments', 
            text: 'Selected text already contains a comment. Please select plain text only.', 
            icon: 'warning' 
        });
        closeCommentModal();
        return;
    }
    
    // Restore selection
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);

    const commentId = "c" + Date.now();
    const commentTime = new Date().toLocaleString('id-ID', {
        day:'numeric', month:'short', year:'numeric', 
        hour:'2-digit', minute:'2-digit', hour12: false
    }).replace(/\./g, ':');
    
    // PROTECTIVE LOCK
    const previousEditingState = isEditing;
    isEditing = true; // Use true to ensure history state is saved correctly

    try {
        // [HISTORY] Save State BEFORE modification
        const activeEd = getActiveEditor();
        if (activeEd) historyMgr.saveState(activeEd.id, activeEd.innerHTML);

        // MANUAL DOM WRAPPING
        const wrapper = document.createElement('span');
        wrapper.className = 'inline-comment';
        wrapper.id = commentId;
        wrapper.setAttribute('data-comment-id', commentId);
        wrapper.setAttribute('data-comment-text', text);
        wrapper.setAttribute('data-comment-user', CURRENT_USER_NAME);
        wrapper.setAttribute('data-comment-dept', CURRENT_USER_ROLE);
        wrapper.setAttribute('data-comment-job', CURRENT_USER_JOB_FUNCTION);
        wrapper.setAttribute('data-comment-time', commentTime);
        wrapper.title = text;
        wrapper.style.backgroundColor = 'yellow';
        wrapper.style.cursor = 'pointer';
        
        // CRITICAL FIX: INHERIT COLOR 
        wrapper.style.color = 'inherit'; 

        // STRATEGY 1: Try surroundContents (Preserves Nesting & Red Color)
        let strategyApplied = false;
        try {
            savedRange.surroundContents(wrapper);
            strategyApplied = true;
        } catch (err) {
            // STRATEGY 2: Fallback to extractContents (Complex/Partial Selections)
            console.warn("[Comment] surroundContents failed, falling back to extractContents", err);
            
            const fragment = savedRange.extractContents();
            let wasRed = false;
            
            // Detect if we pulled from a red source
            const commonParent = savedRange.commonAncestorContainer;
            if (commonParent) {
                const parentEl = commonParent.nodeType === 3 ? commonParent.parentNode : commonParent;
                // Strict check: Must be inside a revision span
                if (parentEl.closest('.revision-span')) {
                    wasRed = true;
                } else if (parentEl.style && (parentEl.style.color === 'red' || parentEl.style.color === 'rgb(255, 0, 0)' || parentEl.style.color === '#ef4444')) {
                    // Check if it's really a content element and not just some container
                    if (parentEl.classList.contains('revision-span')) wasRed = true;
                }
            }

            wrapper.appendChild(fragment);
            savedRange.insertNode(wrapper);

            if (wasRed) {
                 wrapper.classList.add('revision-span'); 
                 wrapper.style.color = 'red'; 
                 // Ensure background stays yellow for comment
                 wrapper.style.backgroundColor = '#fef08a';
            } else {
                 wrapper.style.color = 'inherit';
            }
            strategyApplied = true;
        }

        if (strategyApplied) {
            // Clear selection
            window.getSelection().removeAllRanges();
            
            hasUnsavedChanges = true; 
            setTimeout(() => {
                if (typeof renderSideComments === 'function') renderSideComments();
                if (IS_FILE_UPLOAD) updateSheetTabBadges();
                else updateFreeInputTabBadges();
            }, 50);
            
            if (activeEd) historyMgr.saveState(activeEd.id, activeEd.innerHTML);

            closeCommentModal();
        }
    } catch (e) {
        console.error("DOM Wrap Error:", e);
        Swal.fire("Error", "Gagal menerapkan komentar pada teks yang dipilih. Coba pilih teks yang lebih sederhana.", "error");
    } finally {
        isEditing = previousEditingState;
    }
}

// [REMOVED] renderSideCommentsV2 to avoid conflict with existing logic

/**
 * Robustly ensures the tab containing 'element' is visible.
 * Supports both File Upload and Free Input modes.
 */
function ensureTabVisible(element) {
    if (!element) return false;
    
    // ROBUST VISIBILITY CHECK
    // element.offsetParent is null if the element (or any parent) is display:none
    if (element.offsetParent !== null) return false;
    
    // Find the container pane
    let pane = element.closest('.sheet-pane, .media-pane, .review-tab-content, .free-input-editor');
    if (!pane) return false;
    
    const paneId = pane.id;
    if (!paneId) return false;

    let tabBtn = document.querySelector(`button[onclick*="'${paneId}'"], div[onclick*="'${paneId}'"]`);
    
    if (!tabBtn && paneId.startsWith('tab-media-')) {
        tabBtn = document.getElementById(paneId.replace('tab-media-', 'tab-media-btn-'));
    }
    
    if (!tabBtn) {
        let idx = null;
        if (paneId.startsWith('tab-')) idx = paneId.replace('tab-', '');
        else if (paneId.startsWith('free-editor-')) idx = paneId.replace('free-editor-', '');
        
        if (idx !== null) tabBtn = document.getElementById(`tab-btn-review-${idx}`);
    }

    if (tabBtn) {
        console.log(`[Navigation] Switching to tab for pane: ${paneId}`);
        tabBtn.click();
        return true;
    }
    return false;
}

// [FEATURE] Read More Toggle for Long Comments
function toggleLongText(id) {
    const content = document.getElementById(`content-${id}`);
    const btn = document.getElementById(`toggle-${id}`);
    
    if (content && btn) {
        if (content.style.maxHeight !== 'none') {
            content.style.maxHeight = 'none';
            content.style.overflow = 'visible'; // Allow full expansion
            content.style.maskImage = 'none'; // Remove fade effect
            content.style.webkitMaskImage = 'none';
            btn.innerText = 'Show Less';
        } else {
            content.style.maxHeight = '60px'; // Approx 3 lines (20px line-height * 3)
            content.style.overflow = 'hidden';
            content.style.maskImage = 'linear-gradient(to bottom, black 50%, transparent 100%)';
            content.style.webkitMaskImage = 'linear-gradient(to bottom, black 50%, transparent 100%)';
            btn.innerText = 'Read More';
        }
    }
}

function removeComment(id) {
    // START: Remove restriction - Allow deletion even if not explicitly in 'Edit Mode'
    // if (IS_FILE_UPLOAD && !isEditing) return;
    
    Swal.fire({
        title: 'Hapus Item?',
        text: 'Apakah Anda yakin ingin menghapus komentar/revisi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#cbd5e1',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            // Support both Legacy Comments and Revisions
            let span = document.querySelector(`.inline-comment[data-comment-id='${id}']`);
            if (!span) {
                // Try finding by ID directly (for Revisions)
                span = document.getElementById(id);
                // Try finding by data-comment-id attribute generic query
                if (!span) span = document.querySelector(`[data-comment-id='${id}']`);
            }
            
            if (span) {
                 // [HISTORY] Save State BEFORE modification (only if editing enabled, otherwise skip history for now or enable temp)
                 // If not editing, getActiveEditor might fail or historyMgr not attached?
                 // But cleaning up DOM is fine.
                 const activeEd = getActiveEditor();
                 if (activeEd && historyMgr) historyMgr.saveState(activeEd.id, activeEd.innerHTML);
    
                if (span.classList.contains('revision-span')) {
                    // [FIX V15] CONTEXT BREAKER
                    // Insert Zero-Width Space at position to break cursor inheritance
                    const zwsp = document.createTextNode('\u200B');
                    span.parentNode.insertBefore(zwsp, span);
                    
                    // Add ID to Diplomat Blacklist
                    if (typeof DELETED_SPANS !== 'undefined' && id) {
                         DELETED_SPANS.add(id);
                    }
                    
                    span.remove();
                    
                    // Move cursor to ZWSP (Neutral Ground)
                    const range = document.createRange();
                    range.setStartAfter(zwsp);
                    range.collapse(true);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                    
                    // Force Black Color
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand('foreColor', false, '#000000');
                    
                } else {
                    // [FIX] Explicitly remove highlighting class and attributes
                    span.classList.remove('inline-comment');
                    span.classList.remove('blink-highlight');
                    
                    // Add ID to Diplomat Blacklist
                    if (typeof DELETED_SPANS !== 'undefined' && id) {
                         DELETED_SPANS.add(id);
                    }
                    
                    const parent = span.parentNode;
                    while (span.firstChild) parent.insertBefore(span.firstChild, span);
                    parent.removeChild(span);
                }
                
                hasUnsavedChanges = true;
                
                // [FIX] Force Sync immediately
                renderSideComments();
                
                // Call appropriate badge update based on mode
                if (typeof IS_FILE_UPLOAD !== 'undefined' && IS_FILE_UPLOAD) {
                    if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
                } else {
                    if (typeof updateFreeInputTabBadges === 'function') updateFreeInputTabBadges();
                }
                
                // [HISTORY] Save State AFTER modification
                if (activeEd && historyMgr) historyMgr.saveState(activeEd.id, activeEd.innerHTML);
                
                // [FIX] Safety Net: Remove card UI directly if render didn't catch it
                const sidebarCard = document.querySelector(`.comment-card[data-for='${id}']`);
                if(sidebarCard) sidebarCard.remove();
            } else {
                // If span not found but Card exists (Stale card?), remove card
                 const sidebarCard = document.querySelector(`.comment-card[data-for='${id}']`);
                 if(sidebarCard) sidebarCard.remove();
            }
        }
    });
}

// --- NEW ROBUST REAL-TIME SYNC (Replaces Flaky Observer) ---
// --- NEW ROBUST REAL-TIME SYNC (Delegated) ---
// --- NEW ROBUST REAL-TIME SYNC (Selection-Based) ---
function handleRealtimeInput(e) {
    if (!isEditing) return;
    
    // IGNORE Sidebar Inputs & Comment Modals
    if (e.target.tagName === 'TEXTAREA' || e.target.closest('#comment-modal')) return;

    // Helper to check inline red (Robust) - DEFINED FIRST
    const isStyleRed = (el) => {
        if (!el || !el.style) return false;
        const c = el.style.color;
        if (!c) return false;
        const s = c.toLowerCase().replace(/\s/g, ''); 
        // Add Tailwind Red-500 (rgb(239, 68, 68))
        return s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)';
    };

    // DEBUG: Logs removed for production
    
    // Critical Fix: e.target is the CONTAINER (div), not the span.
    // We must use Selection to find the actual cursor position.
    const selection = window.getSelection();
    if (!selection.anchorNode) return;
    
    let target = selection.anchorNode;
    // Normalize text node to element
    if (target.nodeType === 3) target = target.parentNode;
    
    // [FIX] Skip if cursor is inside or adjacent to a deletion-span
    // Deletion spans have class 'deletion-span' and should NOT be treated as regular revisions.
    // Without this check, the MutationObserver could corrupt the deletion span's attributes
    // or group it with new typing, causing the strikethrough to disappear.
    if (target && target.closest && target.closest('.deletion-span')) return;
    
    // Walk up to look for EXISTING revision container first
    let current = target;
    let groupId = null;
    
    // Pass 1: Ancestry Check (STRICTLY RED PARENTS ONLY)
    let d = 0;
    while (current && current !== document.body && d < 5) {
        if (current.nodeType === 1) {
             // STRICT: Only consider parent if it is VISUALLY RED and NOT a deletion span
             if (isStyleRed(current) && !current.classList.contains('deletion-span')) {
                // [FIX] Skip COMMITTED (non-draft) spans from previous reviewers
                // Committed spans = have 'revision-span' class but NOT 'draft' class
                // This covers both auto-red and highlight comments
                const isCommitted = current.classList.contains('revision-span') && !current.classList.contains('draft');
                if (isCommitted) {
                    console.log('[DEBUG] Hit committed span from previous reviewer, skipping ancestry.');
                    break; // Don't link new typing to old committed span
                }
                groupId = current.getAttribute('data-comment-id') || current.id;
                if (groupId) {
                    console.log(`[DEBUG] Found Group ID: ${groupId}`);
                    break;
                }
             } else {
                 // Stop identifying if we hit a non-red container (like original-content)
                 if (current.classList.contains('original-content') || !current.classList.contains('revision-span')) {
                     console.log("[DEBUG] Hit non-red boundary, stopping ancestry check.");
                     break; 
                 }
             }
        }
        current = current.parentNode;
        d++;
    }

    // Pass 2: Sibling Check (If no parent ID, maybe we just typed next to one)
    if (!groupId && target.nodeType === 1) {
        const prev = target.previousElementSibling;
        // [FIX] Skip deletion spans — they should NOT be treated as group siblings
        // [FIX] Also skip COMMITTED spans from previous reviewers to prevent duplication
        if (prev && (prev.classList.contains('revision-span') || prev.style.color === 'red') && !prev.classList.contains('deletion-span')) {
            const prevIsCommitted = prev.classList.contains('revision-span') && !prev.classList.contains('draft');
            if (!prevIsCommitted) {
                groupId = prev.getAttribute('data-comment-id') || prev.id;
            }
        }
    }

    // LINKING LOGIC
    // Apply or Create ID
    if (!groupId) {
        // Truly new session
        groupId = "rev-" + Date.now();
        // STRICT: Only tag SPANS that are ALREADY RED (created by handleEditorInput)
        if (target.nodeType === 1 && target.tagName === 'SPAN' && isStyleRed(target)) {
             target.id = groupId; // Primary ID
             target.setAttribute('data-comment-id', groupId);
             
             // [FIX] Inject Metadata for Auto-Red
             if (!target.getAttribute('data-comment-user')) {
                 target.setAttribute('data-comment-user', CURRENT_USER_NAME);
                 target.setAttribute('data-comment-dept', CURRENT_USER_ROLE);
                 target.setAttribute('data-comment-job', CURRENT_USER_JOB_FUNCTION);
                 target.setAttribute('data-comment-time', new Date().toLocaleString('id-ID'));
             }
             
             target.classList.add('revision-span');
        }
    } else {
        // Link to existing session
        // STRICT: Only tag RED SPANS.
        if (target.nodeType === 1 && target.tagName === 'SPAN' && isStyleRed(target) && !target.getAttribute('data-comment-id')) {
            target.setAttribute('data-comment-id', groupId);

            // [FIX] Inject Metadata for Auto-Red linking
            if (!target.getAttribute('data-comment-user')) {
                target.setAttribute('data-comment-user', CURRENT_USER_NAME);
                target.setAttribute('data-comment-dept', CURRENT_USER_ROLE);
                target.setAttribute('data-comment-job', CURRENT_USER_JOB_FUNCTION);
            }

            target.classList.add('revision-span');
        }
    }

    // [FIX] EARLY EXIT: Don't process committed (non-draft) revisions
    // This prevents SPV/PIC saved revisions from appearing in sidebar when user deletes text
    if (groupId) {
        const span = document.getElementById(groupId) || document.querySelector(`[data-comment-id="${groupId}"]`);
        console.log(`[DEBUG handleRealtimeInput] groupId=${groupId}, span found:`, span);
        
        if (span) {
            const hasDataCommentText = span.getAttribute('data-comment-text');
            const hasDraftClass = span.classList.contains('draft');
            console.log(`[DEBUG] hasDataCommentText=${hasDataCommentText}, hasDraftClass=${hasDraftClass}`);
            
            // If span exists and is NOT a draft → skip processing
            if (!hasDraftClass) {
                console.log(`[SKIP] ${groupId} is not a draft (committed revision), ignoring`);
                return; // Stop processing - this is a saved revision
            }
        }
    }

    if (groupId) {
        setTimeout(() => {
            // AGGREGATE TEXT from all spans in this group
            const groupParams = document.querySelectorAll(`[data-comment-id="${groupId}"], #${groupId}`);
            let fullText = "";
            let uniqueNodes = new Set();
            let validNodesCount = 0;

            groupParams.forEach(node => {
                // STRICT FILTER: Only include if explicitly VISUALLY RED
                const isActuallyRed = isStyleRed(node);
                
                if (!isActuallyRed) return; // Skip non-red text
                // [FIX] Never include deletion spans in text aggregation
                if (node.classList && node.classList.contains('deletion-span')) return;
                // [FIX] Skip COMMITTED spans from previous reviewers
                // Committed = has 'revision-span' class but NOT 'draft' class
                if (node.classList && node.classList.contains('revision-span') && !node.classList.contains('draft')) return;

                if (!uniqueNodes.has(node)) {
                    fullText += node.textContent;
                    uniqueNodes.add(node);
                    validNodesCount++;
                }
            });
            
            // ABORT if no valid RED nodes were found.
            // This prevents "Ghost Drafts" when typing in non-revision areas (Remarks, etc.)
            // or when the fallback logic generated a groupId but failed to tag any DOM element.
            if (validNodesCount === 0) return;

            // Fallback: If query failed but we have target (Removed to prevent leakage)
            // if (!fullText && target) fullText = target.textContent;

            if (fullText.trim() !== "") {
                updateDraftCard(groupId, fullText.replace(/\u200B/g, ''));
            }
        }, 0);
    }
}

// Global Attachment REMOVED to prevent performance issues and ghost comments
// document.addEventListener('input', (e) => { handleRealtimeInput(e); });

// --- ORIGINAL SPAN CREATION LOGIC (Restored) ---
// --- ORIGINAL SPAN CREATION LOGIC REMOVED (Duplicate) ---
// Use 'handleBeforeInput' defined below instead.


// [DUPLICATE updateDraftCard REMOVED]

// [FIX] Utility: Simple Hash Code for Legacy IDs
String.prototype.hashCode = function() {
    var hash = 0, i, chr;
    if (this.length === 0) return hash;
    for (i = 0; i < this.length; i++) {
        chr   = this.charCodeAt(i);
        hash  = ((hash << 5) - hash) + chr;
        hash |= 0; // Convert to 32bit integer
    }
    return hash;
};

// --- SIDEBAR RENDER ---
function renderSideComments() {
    const list = document.getElementById('comment-list');
    const sidebar = document.getElementById('comment-sidebar');
    if (!list) return;

    // [FIX V29] SPAN MERGER (Unity Protocol)
    // Consolidate adjacent revision spans to prevent "Double Card" glitch on typing
    // This runs BEFORE we extract comments to ensure clean DOM state.
    // [FIX] Suppress MutationObserver during merge to prevent ghost duplicates
    window._suppressRevisionPreservation = true;
    const candidates = document.querySelectorAll('.revision-span, span[style*="color: red"], span[style*="color:red"]');
    candidates.forEach(span => {
        let next = span.nextSibling;
        // Skip empty text nodes between spans
        while (next && next.nodeType === 3 && next.textContent.trim() === '') {
            next = next.nextSibling;
        }
        
        if (next && next.nodeType === 1 && 
            (next.classList.contains('revision-span') || next.style.color === 'red' || next.style.color === 'rgb(255, 0, 0)') &&
            !next.classList.contains('draft') && // Don't merge drafts with saved revisions
            !span.classList.contains('deletion-span') && !next.classList.contains('deletion-span') // Don't merge deletion spans
           ) {
            
            // MERGE NEXT INTO CURRENT
            // 1. Move content
            while(next.firstChild) {
                span.appendChild(next.firstChild);
            }
            // 2. Remove next
            next.remove();
            // 3. Update ID consistency? 
            // We keep span.id. The merged content now belongs to span.
            console.log("[MERGE] Consolidated adjacent red spans.");
        }
    });
    window._suppressRevisionPreservation = false;

    // PRESERVE DRAFTS: Capture existing draft cards AND FILTER ORPHANS
    // Only keep drafts where the corresponding span still exists IN AN EDITOR (not in hidden container)
    const _hiddenRevContainer = document.getElementById('deleted-revisions-container');
    const existingDrafts = Array.from(list.querySelectorAll('.comment-card.draft')).filter(card => {
        const revId = card.id.replace('card-', '');
        // Check if the span exists in the document AND is not in the hidden preservation container
        const el = document.getElementById(revId) || document.querySelector(`[data-comment-id="${revId}"]`);
        if (!el || (_hiddenRevContainer && _hiddenRevContainer.contains(el))) return false;
        // [FIX] Also verify the SPAN still has draft class (commitRevision removes it on save)
        // If span no longer has 'draft', don't preserve the draft card — it'll be recreated as committed
        return el.classList.contains('draft');
    });

    list.innerHTML = '';
    
    // FETCH BOTH COMMENTS AND REVISIONS (GENERIC SELECTOR)
    // We want ALL comments from the document, regardless of container
    // [FIX] Robust Selector: Also catch inline styles if class was stripped
    // [FIX] Exclude .draft spans — they are handled by existingDrafts above
    let commentsRaw = Array.from(document.querySelectorAll('.inline-comment, .revision-span:not(.draft), span[style*="color: red"]:not(.draft), span[style*="color:red"]:not(.draft), span[style*="color: #ef4444"]:not(.draft)'));

    const uniqueComments = [];
    const seenIds = new Set();
    
    // [FIX] Pre-populate seenIds with existingDraft IDs to prevent duplicate cards
    existingDrafts.forEach(card => {
        const draftId = card.id.replace('card-', '');
        if (draftId) seenIds.add(draftId);
    });
    
    commentsRaw.forEach(span => {
        // FILTER: Ignore UI Elements (Modals, Popovers)
        if (span.closest('#comment-modal')) return;
        // [FIX] Skip spans preserved in hidden container (undone revisions)
        if (_hiddenRevContainer && _hiddenRevContainer.contains(span)) return;
        
        // HEURISTIC: Ignore if text looks like the Modal UI
        const checkText = span.textContent || "";
        if (checkText.includes("Add Comment") && checkText.includes("Cancel")) return;

        let id = span.getAttribute('data-comment-id') || span.id; // Support both
        
        // [FIX] Fallback for styled spans without ID (Legacy/Sanitized)
        if (!id && span.style.color && (span.style.color === 'red' || span.style.color === 'rgb(255, 0, 0)')) {
             // Generate a temporary ID based on content to show the card
             id = "legacy-" + Math.abs(checkText.hashCode());
             
             // [CRITICAL FIX] Persist ID to DOM so Janitor/Observer can find it
             // This prevents "Removing orphan card" issues
             span.id = id;
             span.setAttribute('data-comment-id', id);
        }
        

        if (id && !seenIds.has(id)) {
            // [FIX V15] THE DIPLOMAT (Safe Gatekeeper)
            // Ensure deleted comments DO NOT reappear as cards during Undo
            // BUT ensure we don't accidentally block shared legacy IDs (Collision Protection)
            if (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.has(id)) {
                // SAFETY: Only block if ID is a guaranteed Unique Timestamp (rev-...)
                if (id.startsWith('rev-')) {
                    console.log(`[DIPLOMAT] Blocked Zombie Card: ${id}`);
                    return; 
                }
            }

            seenIds.add(id);
            
            // ROBUST TEXT EXTRACTION
            // [FIX] Auto-Red Visibility: If no manual note, use the revised text itself.
            let rawText = span.getAttribute('data-comment-text');
            if (!rawText) {
                 rawText = span.innerText || span.textContent || "";
            }
            
            // Clean up ZWSP
            rawText = rawText.replace(/\u200B/g, '');
            
            // FILTER: Skip empty or whitespace-only comments
            if (!rawText || rawText.trim().length === 0) return;
            
            let timestamp = 0;
            // Robust int parse
            try {
                timestamp = parseInt(id.replace(/^(c|rev-)/, '')) || 0;
            } catch(e) { timestamp = 0; }

            uniqueComments.push({
                id: id,
                text: rawText,
                // [FIX] Fallback to Last Modifier Name if no specific user on span (Legacy/AutoRed)
                user: span.getAttribute('data-comment-user') || (typeof LAST_MODIFIER_NAME !== 'undefined' ? LAST_MODIFIER_NAME : 'Reviewer'),
                time: span.getAttribute('data-comment-time') || '',
                timestamp: timestamp,
                element: span
            });
        }
    });

    uniqueComments.sort((a, b) => b.timestamp - a.timestamp);
    
    // [FIX] GROUP ADJACENT DELETION SPANS
    // If multiple deletion spans are DOM-adjacent (nextSibling), merge them into one comment entry
    const groupedComments = [];
    const processedDeletionIds = new Set();
    
    uniqueComments.forEach(c => {
        if (processedDeletionIds.has(c.id)) return; // Already grouped
        
        const isDeletion = c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span'));
        
        if (isDeletion && c.element) {
            // Check for adjacent deletion siblings and group them
            let combinedText = c.element.getAttribute('data-deleted-text') || c.text;
            let next = c.element.nextSibling;
            const groupedIds = [c.id];
            
            // Walk forward through adjacent deletion spans
            while (next) {
                // Skip empty text nodes
                if (next.nodeType === Node.TEXT_NODE && next.textContent.trim() === '') {
                    next = next.nextSibling;
                    continue;
                }
                if (next.nodeType === 1 && next.classList && next.classList.contains('deletion-span')) {
                    const nextId = next.getAttribute('data-comment-id') || next.id;
                    combinedText += next.getAttribute('data-deleted-text') || next.textContent;
                    if (nextId) {
                        groupedIds.push(nextId);
                        processedDeletionIds.add(nextId);
                    }
                    next = next.nextSibling;
                } else {
                    break;
                }
            }
            
            // Update the text for the grouped entry
            if (groupedIds.length > 1) {
                c.text = combinedText;
                c._groupedIds = groupedIds;
            }
        }
        
        groupedComments.push(c);
    });

    // Re-Add Drafts FIRST (Top of list)
    existingDrafts.forEach(draftCard => {
        list.appendChild(draftCard);
    });

    if (groupedComments.length === 0 && existingDrafts.length === 0) {
        if(sidebar) sidebar.style.display = 'none';
        return;
    }
    
    if(sidebar) sidebar.style.display = 'block';



    groupedComments.forEach(c => {
        const card = document.createElement('div');
        card.className = 'comment-card';
        card.setAttribute('data-for', c.id);
        card.style.marginBottom = '15px';
        card.style.background = 'white';
        card.style.borderRadius = '12px';
        card.style.padding = '16px';
        card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.02)';
        card.style.border = '1px solid #e2e8f0';
        
        // Determine Icon based on type (Comment 'C' vs Revision 'R' vs Deletion 'D')
        const isRevision = c.id.startsWith('rev-');
        const isDeletion = c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span'));
        const cardType = isDeletion ? 'deletion' : (isRevision ? 'revision' : 'comment');
        card.setAttribute('data-card-type', cardType);
        const iconChar = isDeletion ? 'D' : (isRevision ? 'R' : 'C');
        const iconBg = isDeletion ? '#fef3c7' : (isRevision ? '#fef2f2' : '#eff6ff');
        const iconColor = isDeletion ? '#d97706' : (isRevision ? '#ef4444' : '#3b82f6');
        const iconBorder = isDeletion ? '#fde68a' : (isRevision ? '#fecaca' : '#dbeafe');

        // [VERSIONING] Check for version info from saved element
        let versionBadge = '';
        let supersededBadge = '';
        let cardOpacity = '1';
        let cardBorder = isDeletion ? '1px solid #fde68a' : '1px solid #e2e8f0';
        let isSuperseded = false; 
        
        // [FIX] Initialize these variables outside the 'isRevision' block
        // so they are available for ALL comment types (including highlights)
        let replacesBadge = ''; 
        let deletionBadge = '';
        let reviewerRoleLabel = '';
        
        // [DELETION TRACKING] Show deletion badge
        if (isDeletion) {
            const deletedText = c.element.getAttribute('data-deleted-text') || c.text;
            const preview = deletedText.length > 40 ? deletedText.substring(0, 40) + '...' : deletedText;
            deletionBadge = `<div style="font-size:11px; color:#d97706; margin-top:4px; font-weight:600;">🗑 Deleted: "${preview}"</div>`;
        }

        if (isRevision && c.element) {
            const versionNum = c.element.getAttribute('data-revision-version');
            isSuperseded = c.element.getAttribute('data-superseded') === 'true'; 
            const supersededBy = c.element.getAttribute('data-superseded-by');
            
            if (versionNum && parseInt(versionNum) > 1) {
                // [DISABLED] Version Badge by user request
                // versionBadge = ` <span style="font-size:10px; background:#3b82f6; color:white; padding:2px 6px; border-radius:4px; margin-left:6px;">v${versionNum}</span>`;
            }
            
            if (isSuperseded) {
                supersededBadge = `<div style="font-size:10px; color:#f59e0b; margin-top:4px;">🚫 Superseded by ${supersededBy || 'newer version'}</div>`;
                cardOpacity = '0.6';
                cardBorder = '1px dashed #cbd5e1';
            }

            // [RESTORED] Check for "Replaces" attribute
            const replacedText = c.element.getAttribute('data-replaces-text');
            const replacedUser = c.element.getAttribute('data-replaces-user');
            const replacedRole = c.element.getAttribute('data-replaces-role');
            
            if (replacedText && !isSuperseded) {
                    const preview = replacedText.length > 50 ? replacedText.substring(0, 50) + '...' : replacedText;
                    
                    let byInfo = '';
                    if (replacedUser) {
                        // Try attribute first, then fallback to global map
                        let role = replacedRole;
                        if (!role && window.USER_ROLE_MAP && window.USER_ROLE_MAP[replacedUser]) {
                            role = window.USER_ROLE_MAP[replacedUser];
                        }
                        
                        const roleLabel = role ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${role})</span>` : '';
                        byInfo = ` <span style="color:#64748b; font-weight:400;">(by ${replacedUser}${roleLabel})</span>`;
                    }
                    
                    replacesBadge = `<div onclick="window.viewRevisionHistory && viewRevisionHistory('${c.id}')" style="font-size:11px; color:#ef4444; margin-top:6px; font-weight:500; cursor:pointer; line-height:1.4;" title="Click to view full history">Replaces: "${preview}"${byInfo}</div>`;
            }
        }
        
        // [RESTORED] Add Role Badge to current Reviewer Name (For BOTH Revisions and Highlights)
        // Moved outside 'if(isRevision)' to ensure it renders for regular comments too if they have role data
        let reviewerRole = '';
        if (c.element) reviewerRole = c.element.getAttribute('data-comment-role') || c.element.getAttribute('data-user-role'); // Added fallback
        if (!reviewerRole && window.USER_ROLE_MAP && window.USER_ROLE_MAP[c.user]) {
            reviewerRole = window.USER_ROLE_MAP[c.user];
        }
        reviewerRoleLabel = reviewerRole ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${reviewerRole})</span>` : '';

        // [PERMISSION] Only show delete button if current user owns the comment
        const isOwner = (typeof CURRENT_USER_NAME !== 'undefined' && c.user === CURRENT_USER_NAME);
        
        // [FIX] Allow Supervisors/PIC to delete generic "Reviewer" artifacts (Cleanup Mode)
        const isLegacyArtifact = ['Reviewer', 'Maker', 'Supervisor', 'PIC'].includes(c.user) || c.user.includes('Reviewer');
        const isPrivileged = (typeof CURRENT_USER_ROLE !== 'undefined' && ['SPV', 'PIC', 'ADMIN'].includes(CURRENT_USER_ROLE));
        const canDelete = isOwner || (isLegacyArtifact && isPrivileged);

        const deleteBtnStyle = canDelete ? "background:none; border:none; cursor:pointer; color:#ef4444; opacity:0.6;" : "display:none !important;";

        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:${iconBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:${iconColor}; font-weight:bold; border:1px solid ${iconBorder};">${iconChar}</div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${c.user}${versionBadge}${reviewerRoleLabel}</div>
                        <div style="font-size:11px; color:#94a3b8;">${c.time}</div>
                        ${supersededBadge}
                        ${replacesBadge}
                        ${deletionBadge}
                    </div>
                </div>
                <button class="btn-delete-comment" onclick="removeComment('${c.id}')" title="Delete" style="${deleteBtnStyle}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
            </div>
            <div class="draft-body" style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;${isSuperseded ? ' text-decoration: line-through; color: #94a3b8;' : ''}">
                ${(() => {
                    const text = c.text || '';
                    const threshold = 150; // Character limit for truncation
                    if (text.length > threshold) {
                         return `
                            <div id="content-${c.id}" style="max-height: 60px; overflow: hidden; transition: max-height 0.3s ease; mask-image: linear-gradient(to bottom, black 50%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);">
                                ${text}
                            </div>
                            <div id="toggle-${c.id}" onclick="event.stopPropagation(); toggleLongText('${c.id}')" style="color:#0369a1; cursor:pointer; font-size:12px; font-weight:600; margin-top:4px;">
                                Read More
                            </div>
                         `;
                    }
                    return text;
                })()}
            </div>
        `;
        
        // Apply opacity and border for superseded cards
        card.style.opacity = cardOpacity;
        card.style.border = cardBorder;
        if (isSuperseded) {
            card.style.pointerEvents = 'none';
            card.style.cursor = 'not-allowed';
        }
        
        
        // Interaction
        card.onclick = (e) => {
             // Prevent triggering if clicked on inner buttons
             if (e.target.closest('button')) return;

             // [VERSIONING] Check if this is a superseded revision
             const isSuperseded = c.element && c.element.getAttribute('data-superseded') === 'true';
             
             if (isSuperseded) {
                 // Find the latest revision at the same location
                 const location = c.element.getAttribute('data-revision-location');
                 if (location) {
                     const allAtLocation = getRevisionsAtLocation(location);
                     const latestRevision = allAtLocation.find(span => !span.getAttribute('data-superseded'));
                     
                     if (latestRevision) {
                         // Navigate to latest revision instead
                         ensureTabVisible(latestRevision);
                         latestRevision.scrollIntoView({ behavior: 'smooth', block: 'center' });
                         
                         // Highlight both old and new for comparison
                         latestRevision.classList.add('blink-highlight');
                         setTimeout(() => latestRevision.classList.remove('blink-highlight'), 2000);
                         
                         // Show notification
                         showCustomAlert('This revision has been superseded. Showing latest version.', 'info');
                         return;
                     }
                 }
             }

             // Normal navigation for non-superseded revisions
             navigateToComment(c.id, c.element);
        };
        
        list.appendChild(card);
    });
    
    // [FIX] UPDATE COUNT BADGE + LIHAT SEMUA BUTTON
    const totalCount = groupedComments.length + existingDrafts.length;
    const countBadge = document.getElementById('comment-count-badge');
    if (countBadge) {
        countBadge.textContent = totalCount;
        countBadge.style.display = totalCount > 0 ? 'inline-block' : 'none';
    }
    const totalCountEl = document.getElementById('review-total-count');
    if (totalCountEl) totalCountEl.textContent = totalCount;
    
    const lihatSemuaWrap = document.getElementById('btn-lihat-semua-wrap');
    if (lihatSemuaWrap) {
        lihatSemuaWrap.style.display = totalCount > 0 ? 'block' : 'none';
    }
    
    // [FIX] POPUP VISIBILITY FOR DRAFTS
    // Merge existing Drafts into the popup data source so they appear in "Lihat Semua"
    // (Drafts are excluded from uniqueComments above, but exist in DOM)
    let popupComments = [...groupedComments];
    
    existingDrafts.forEach(card => {
        const draftId = card.id.replace('card-', '');
        
        // Find at least one DOM element for metadata (user, time, etc.)
        const el = document.getElementById(draftId) || document.querySelector(`[data-comment-id="${draftId}"]`);
        
        // [FIX V2] PRIMARY SOURCE: Use card's pre-aggregated stored text
        // updateDraftCard() already correctly aggregates text from ALL spans in the group,
        // so data-stored-text always has the full word (e.g., "Hai", "SMS", "berbicara").
        // Reading from DOM spans is unreliable because each character may have its own unique ID.
        let rawText = card.getAttribute('data-stored-text') || '';
        
        // Fallback: If stored text is empty, try the card's visible body text
        if (!rawText.trim()) {
            const bodyEl = card.querySelector('.draft-body');
            if (bodyEl) rawText = bodyEl.textContent || '';
        }
        
        // Last resort: Read from DOM span element
        if (!rawText.trim() && el) {
            rawText = el.getAttribute('data-comment-text') || el.innerText || el.textContent || '';
        }
        
        rawText = rawText.replace(/\u200B/g, ''); // Clean ZWSP
        
        // Push to popup list
        popupComments.push({
            id: draftId,
            text: rawText,
            user: (el && el.getAttribute('data-comment-user')) || (typeof CURRENT_USER_NAME !== 'undefined' ? CURRENT_USER_NAME : 'Me'),
            time: (el && el.getAttribute('data-comment-time')) || 'Just now',
            timestamp: Date.now(), // High timestamp to show at top
            element: el,
            isDraft: true
        });
    });
    
    // Re-sort to ensure drafts are at top (by timestamp)
    popupComments.sort((a, b) => b.timestamp - a.timestamp);

    // Store comments data globally for popup
    window._allReviewComments = popupComments;
}

// --- REVIEW POPUP FUNCTIONS ---

function navigateToComment(commentId, element) {
    // 1. ROBUST RE-FETCH (Handle element detachment/replacement)
    let targetEl = document.getElementById(commentId);
    if (!targetEl) {
        targetEl = document.querySelector(`[data-comment-id="${commentId}"]`);
    }
    if (!targetEl) targetEl = element; // Last resort fallback

    if (!targetEl) return;

    // 2. Cross-Tab Navigation
    if (typeof ensureTabVisible === 'function') ensureTabVisible(targetEl);

    // 3. Scroll with Delay (Allow tab transition/render)
    setTimeout(() => {
        let finalEl = document.getElementById(commentId) || document.querySelector(`[data-comment-id="${commentId}"]`) || targetEl;

        if (finalEl) {
            finalEl.scrollIntoView({behavior: "smooth", block: "center"});
            finalEl.classList.remove('blink-highlight');
            void finalEl.offsetWidth; // Trigger reflow
            finalEl.classList.add('blink-highlight');
        }
        
        // Highlight sidebar Card if visible (type-aware via CSS .selected class)
        const sideCard = document.querySelector(`.comment-card[data-for="${commentId}"]`);
        if (sideCard) {
            // Remove 'selected' from all cards first
            document.querySelectorAll('.comment-card.selected').forEach(x => {
                x.classList.remove('selected');
            });
            // Add 'selected' to clicked card — CSS handles colors based on data-card-type
            sideCard.classList.add('selected');
            // Auto-scroll sidebar to the selected card
            sideCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, 150);
}

function openReviewPopup() {
    const overlay = document.getElementById('review-popup-overlay');
    const listEl = document.getElementById('review-popup-list');
    if (!overlay || !listEl) return;
    // [FIX V3] Refresh draft texts LIVE from sidebar cards before display
    // window._allReviewComments is cached from last renderSideComments() call,
    // which may be stale if user typed more after that. Re-read from cards now.
    const comments = (window._allReviewComments || []).map(c => {
        if (c.isDraft) {
            const card = document.getElementById(`card-${c.id}`);
            if (card) {
                // Primary: card's pre-aggregated stored text (always correct)
                const storedText = card.getAttribute('data-stored-text');
                if (storedText && storedText.trim()) {
                    c.text = storedText;
                } else {
                    // Fallback: card's visible body text
                    const bodyEl = card.querySelector('.draft-body');
                    if (bodyEl && bodyEl.textContent.trim()) {
                        c.text = bodyEl.textContent.trim();
                    }
                }
            }
        }
        return c;
    });
    const subtitle = document.getElementById('popup-subtitle');
    if (subtitle) {
        const revCount = comments.filter(c => c.id.startsWith('rev-') && !(c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span')))).length;
        const delCount = comments.filter(c => c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span'))).length;
        const comCount = comments.filter(c => !c.id.startsWith('rev-')).length;
        subtitle.textContent = `${revCount} revisions · ${delCount} deletions · ${comCount} comments`;
    }
    
    renderPopupItems(comments, listEl);
    overlay.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent body scroll
    
    // Reset filter
    document.querySelectorAll('.review-filter-btn').forEach(b => {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
    });
    const allBtn = document.querySelector('.review-filter-btn[data-filter="all"]');
    if (allBtn) {
        allBtn.classList.add('active');
        allBtn.style.background = '#1e293b';
        allBtn.style.color = 'white';
    }
}

function closeReviewPopup() {
    const overlay = document.getElementById('review-popup-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
}

function filterReviewPopup(type, btn) {
    const comments = window._allReviewComments || [];
    const listEl = document.getElementById('review-popup-list');
    if (!listEl) return;
    
    let filtered = comments;
    if (type === 'revision') {
        filtered = comments.filter(c => c.id.startsWith('rev-') && !(c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span'))));
    } else if (type === 'deletion') {
        filtered = comments.filter(c => c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span')));
    } else if (type === 'comment') {
        filtered = comments.filter(c => !c.id.startsWith('rev-'));
    }
    
    renderPopupItems(filtered, listEl);
    
    // Update active button style
    document.querySelectorAll('.review-filter-btn').forEach(b => {
        b.classList.remove('active');
        if (b.dataset.filter === 'all') { b.style.background = '#f8fafc'; b.style.color = '#64748b'; }
        else if (b.dataset.filter === 'revision') { b.style.background = '#fef2f2'; b.style.color = '#ef4444'; }
        else if (b.dataset.filter === 'deletion') { b.style.background = '#fffbeb'; b.style.color = '#d97706'; }
        else if (b.dataset.filter === 'comment') { b.style.background = '#eff6ff'; b.style.color = '#3b82f6'; }
    });
    if (btn) {
        btn.classList.add('active');
        btn.style.background = '#1e293b';
        btn.style.color = 'white';
    }
}

function renderPopupItems(items, container) {
    if (!container) return;
    
    if (items.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8; font-size:14px;">No items found</div>';
        return;
    }
    
    let html = '';
    items.forEach((c, idx) => {
        const isDeletion = c.element && (c.element.getAttribute('data-is-deletion') === 'true' || c.element.classList.contains('deletion-span'));
        const isRevision = c.id.startsWith('rev-');
        
        const iconChar = isDeletion ? 'D' : (isRevision ? 'R' : 'C');
        const iconBg = isDeletion ? '#fef3c7' : (isRevision ? '#fef2f2' : '#eff6ff');
        const iconColor = isDeletion ? '#d97706' : (isRevision ? '#ef4444' : '#3b82f6');
        const iconBorder = isDeletion ? '#fde68a' : (isRevision ? '#fecaca' : '#dbeafe');
        const cardBorderColor = isDeletion ? '#fde68a' : '#e2e8f0';
        
        // Get role
        let role = '';
        if (c.element) role = c.element.getAttribute('data-comment-role') || c.element.getAttribute('data-user-role') || '';
        if (!role && window.USER_ROLE_MAP && window.USER_ROLE_MAP[c.user]) role = window.USER_ROLE_MAP[c.user];
        const roleLabel = role ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${role})</span>` : '';
        
        // Deletion badge
        let deletionBadge = '';
        if (isDeletion) {
            const deletedText = c.element.getAttribute('data-deleted-text') || c.text;
            const preview = deletedText.length > 60 ? deletedText.substring(0, 60) + '...' : deletedText;
            deletionBadge = `<div style="font-size:11px; color:#d97706; margin-top:4px; font-weight:600;">🗑 Deleted: "${preview}"</div>`;
        }
        
        // Replaces badge (for revisions that replaced text)
        let replacesBadge = '';
        if (isRevision && !isDeletion && c.element) {
            const replacedText = c.element.getAttribute('data-replaces-text');
            const replacedUser = c.element.getAttribute('data-replaces-user');
            if (replacedText) {
                const rPreview = replacedText.length > 50 ? replacedText.substring(0, 50) + '...' : replacedText;
                const byInfo = replacedUser ? ` <span style="color:#64748b;">(by ${replacedUser})</span>` : '';
                replacesBadge = `<div style="font-size:11px; color:#ef4444; margin-top:4px; font-weight:500;">Replaces: "${rPreview}"${byInfo}</div>`;
            }
        }
        
        // [FIX] DRAFT INDICATOR (Status Diskusi)
        // Helps distinguish "Auto Red" session drafts from saved DB revisions
        let draftBadge = '';
        let timeDisplay = c.time || '';
        
        if (c.isDraft) {
            draftBadge = `<span style="background:#fef3c7; color:#d97706; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; border:1px solid #fcd34d; margin-left:6px; vertical-align:middle;">DRAFT / UNSAVED</span>`;
            // Override time for clarity
            timeDisplay = 'Just now (Session)';
        }

        // Full text content
        const textContent = c.text || '';
        const textStyle = isDeletion ? 'text-decoration: line-through; color: #94a3b8;' : '';
        
        html += `
            <div class="popup-review-item" data-type="${isDeletion ? 'deletion' : (isRevision ? 'revision' : 'comment')}" 
                 onclick="closeReviewPopup(); setTimeout(()=>navigateToComment('${c.id}'), 200);"
                 style="margin-bottom:12px; background:white; border-radius:12px; padding:16px; box-shadow:0 4px 6px rgba(0,0,0,0.02); border:1px solid ${cardBorderColor}; cursor:pointer; transition:all 0.2s;"
                 onmouseover="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 4px 12px rgba(59,130,246,0.1)'; this.style.transform='translateY(-1px)';"
                 onmouseout="this.style.borderColor='${cardBorderColor}'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.02)'; this.style.transform='translateY(0)';">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:32px; height:32px; background:${iconBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:${iconColor}; font-weight:bold; border:1px solid ${iconBorder};">${iconChar}</div>
                        <div>
                            <div style="font-size:13px; font-weight:700; color:#1e293b;">${c.user}${roleLabel}${draftBadge}</div>
                            <div style="font-size:11px; color:#94a3b8;">${timeDisplay}</div>
                            ${deletionBadge}
                            ${replacesBadge}
                        </div>
                    </div>
                    <div style="color:#cbd5e1; flex-shrink:0; margin-top:4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
                <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6; ${textStyle}">
                    ${textContent}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// --- HELPER FUNCTION: Apply Mode Color (Global Scope) ---
function applyModeColor() {
    if (typeof isEditing !== 'undefined' && !isEditing) return;
    // Remove isUndoing check from here if it causes issues, or ensure isUndoing is global
    // But isUndoing IS global.
    // However, during Undo/Redo, we trigger input events when replacing HTML.
    // We want to avoid RE-APPLYING color logic during that restoration.
    // Wait, undo/redo replaces innerHTML, which DOES NOT trigger 'input' event on the element itself usually, 
    // unless we dispatch it manually? Browser behavior varies.
    // But if we restore listeners, they might fire on subsequent interactions.
    
    // SAFEGUARD: Don't apply if already inside a red/revision span
    const sel = window.getSelection();
    if (sel.rangeCount === 0) return;

    let node = sel.anchorNode;
    if (node && node.nodeType === 3) node = node.parentNode;
    
    let isRed = false;
    let walker = node;
    while(walker && walker !== document.body && !walker.hasAttribute('contenteditable')) {
        if (walker.nodeType === 1) {
            const s = walker.style.color || "";
            if (walker.classList.contains('revision-span') || s.includes('red') || s.includes('rgb(255, 0, 0)') || s.includes('#ef4444')) {
                isRed = true;
                break;
            }
        }
        walker = walker.parentNode;
    }
    
    let targetColor = '#ef4444'; // Always Red for Revisi Maker

    if (isRed && targetColor === '#ef4444') return;
    
    // CRITICAL FIX: Only apply color to CARET (collapsed), NOT selection.
    // This prevents selecting text from automatically turning it red/black,
    // which breaks the "Add Highlight" flow and Undo stack.
    if (!sel.isCollapsed) return;

    // [FIX UNDO] Save history snapshot BEFORE execCommand changes DOM
    // This ensures undo returns to pre-color-change state
    const _editor = sel.anchorNode ? (sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode : sel.anchorNode) : null;
    if (_editor && typeof historyMgr !== 'undefined') {
        const _editable = _editor.closest ? _editor.closest('[contenteditable="true"]') : null;
        if (_editable && _editable.id) {
            historyMgr.captureDebounced(_editable.id, _editable.innerHTML);
        }
    }

    document.execCommand('styleWithCSS', false, true);
    document.execCommand('foreColor', false, targetColor);
}

// [FIX] Pre-emptive Red Span Escape for Legal/CX mode
// Runs on keydown (BEFORE browser inserts character)
// [REMOVED] escapeRedSpanBeforeTyping was specific to Legal/CX mode

// --- INIT ---
document.addEventListener('DOMContentLoaded', () => {
    // [FIX V20] GLOBAL PROTECTION FOR SERVER-LOADED CONTENT
    // Identify ALL revisions present on load (Legacy + SPV/Maker saved rev- IDs)
    // [FIX V25] AGGRESSIVE STAR ASSASSIN (Operasi Bersih Bintang V2)
    // Target both .revision-span AND any red span acting as a comment
    const candidates = document.querySelectorAll('.revision-span, span[style*="color: red"], span[style*="color:red"]');
    
    // [FIX V28] RESTORE PROTECTED_IDS INIT (Was accidentally deleted in V25)
    window.PROTECTED_IDS = new Set();
    
    // ENSURE DELETED_SPANS EXISTS (Fix for TypeError)
    if (typeof window.DELETED_SPANS === 'undefined') {
        window.DELETED_SPANS = new Set();
    }
    
    candidates.forEach(span => {
        // Normalize text: remove ZWSP, trim, remove &nbsp;
        const text = span.textContent.replace(/\u200B/g, '').replace(/\u00A0/g, ' ').trim();
        
        // KILL IF: Content is exactly "*" OR just whitespace
        if (text === '*' || text === '') {
            console.log(`[STAR ASSASSIN] Executing trash span: ${span.id || 'no-id'}`);
            if(span.id) DELETED_SPANS.add(span.id);
            span.remove();
        } else {
            // Only protect if it survives the assassin
            if (span.id) window.PROTECTED_IDS.add(span.id);
        }
    });
    console.log(`[PROTECTION] Secured ${window.PROTECTED_IDS.size} server-loaded revisions (Trash filtered).`);

    // [FIX V15] JANITOR: Clear Blacklist on Load
    if (typeof DELETED_SPANS !== 'undefined') DELETED_SPANS.clear();

    // NEUTERED: Prevent legacy ghost logic
    // [RESTORED] Keep handleRealtimeInput functional if defined
    // window.handleRealtimeInput = function(e) { return; };

    // Auto color logic
    if (IS_FILE_UPLOAD) {
        // SELF-HEAL: Repair Broken Tables
        const corruptSpans = document.querySelectorAll('tr > span.inline-comment, tbody > span.inline-comment');
        corruptSpans.forEach(span => {
            const parent = span.parentNode;
            while (span.firstChild) parent.insertBefore(span.firstChild, span);
            parent.removeChild(span);
        });
        
        const editableElements = document.querySelectorAll('.excel-preview td, .word-preview p');
        editableElements.forEach(el => {
            if (el.innerHTML.trim() !== '' && !el.querySelector('span.original-content')) {
                const originalText = el.innerHTML;
                el.innerHTML = `<span class="original-content" style="color:#333;">${originalText}</span>`;
            }
        });
        const editor = document.getElementById('unified-file-editor');
        if (editor) {
            // RE-ENABLED: handleBeforeInput for red text support
            // Guard against duplicate attachment
            // FIXED: Redundant listener causing double typing
            if (false && typeof handleBeforeInput === 'function' && !editor._hasBeforeInputListener) {
                editor.addEventListener('beforeinput', handleBeforeInput);
                editor._hasBeforeInputListener = true; // Mark as attached
            }
            
            editor.addEventListener('focus', applyModeColor);
            editor.addEventListener('click', applyModeColor);
            editor.addEventListener('keyup', applyModeColor);
            editor.addEventListener('input', (e) => { 
                applyModeColor(); 
                // Sync Sidebar on Input (Handles Undo/Redo/Delete)
                // GUARD: Don't rebuild sidebar during active typing (isInternalChange suppression)
                if (window._isInternalChange) return;
                if(window._sidebarTimer) clearTimeout(window._sidebarTimer);
                window._sidebarTimer = setTimeout(renderSideComments, 300);
            });
            
            // Apply to cells
            editor.querySelectorAll('td').forEach(cell => {
                cell.addEventListener('mousedown', applyModeColor);
                cell.addEventListener('focus', applyModeColor);
                cell.addEventListener('input', applyModeColor);
            });
            
        }
        
        renderSideComments();
        updateSheetTabBadges();
        if (typeof enableEditMode === 'function') enableEditMode();
    
    } else {
        // FREE INPUT MODE
        document.querySelectorAll('.free-input-editor').forEach(editor => {
            // RE-ENABLED: handleBeforeInput for red text support
            // Guard against duplicate attachment
            // FIXED: Redundant listener causing double typing
            if (false && typeof handleBeforeInput === 'function' && !editor._hasBeforeInputListener) {
                editor.addEventListener('beforeinput', handleBeforeInput);
                editor._hasBeforeInputListener = true; // Mark as attached
            }
            
            editor.addEventListener('focus', applyModeColor);
            editor.addEventListener('click', applyModeColor);
            editor.addEventListener('keyup', applyModeColor);
            editor.addEventListener('input', () => { 
                hasUnsavedChanges = true; 
                applyModeColor(); // Real-time Auto Red
                
                // Sync Sidebar
                // GUARD: Don't rebuild sidebar during active typing
                if (window._isInternalChange) return;
                if(window._sidebarTimer) clearTimeout(window._sidebarTimer);
                window._sidebarTimer = setTimeout(renderSideComments, 300);
            });
            
        });
        
        renderSideComments();
        if (typeof updateFreeInputTabBadges === 'function') updateFreeInputTabBadges();
        if (typeof enableEditMode === 'function') enableEditMode();
        
        // [FIX] Set initial .active class on first Free Input tab button
        // Without this, getRevisionLocation resolves to 'default' on first load,
        // causing location mismatch when user clicks tab 2+ later
        const firstFreeInputBtn = document.querySelector('.btn-media-tab:not(.active)');
        if (firstFreeInputBtn && !document.querySelector('.btn-media-tab.active')) {
            const btn0 = document.getElementById('tab-btn-review-0');
            if (btn0) btn0.classList.add('active');
        }
    }
    

    // Set Editing Flag
    isEditing = true;
    const ec = document.getElementById('edit-controls');
    if(ec) ec.style.display = 'block';

    // --- RE-ATTACH LISTENERS WITH HISTORY SUPPORT ---
    if (typeof IS_FILE_UPLOAD !== 'undefined') {
        const attachHistory = (el) => {
            el.addEventListener('focus', function() {
                // Ensure initial state is saved when we start interacting
                if (historyMgr.getStack(this.id).undo.length === 0) {
                     historyMgr.saveState(this.id, this.innerHTML);
                }
            });
            el.addEventListener('input', function() {
                // Determine if we need to capture (Normal black typing handled here)
                // If Auto-Red handled by handleEditorInput, this might be redundant but safe
                historyMgr.captureDebounced(this.id, this.innerHTML);
            });
        };

        const editor = document.getElementById('unified-file-editor');
        if(editor) attachHistory(editor);
        
        document.querySelectorAll('.media-pane, .sheet-pane').forEach(attachHistory);
        document.querySelectorAll('.free-input-editor').forEach(attachHistory);
    }


    // Unsaved Warning
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) { e.preventDefault(); e.returnValue = ''; }
    });
    
    const forms = document.querySelectorAll('form');
    forms.forEach(f => {
        f.addEventListener('submit', () => { hasUnsavedChanges = false; });
    });
    
    // Upload Handlers
    ['file_legal', 'file_cx', 'file_syariah', 'file_lpp'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', function() { handleReviewDocUpload(this, id.replace('file_','')); });
    });
});




// --- CUSTOM HISTORY MANAGER ---
class HistoryManager {
    constructor(limit = 50) {
        this.stacks = {}; // Keyed by editorId: { undo: [], redo: [] }
        this.limit = limit;
        this.debounceTimers = {};
    }

    // Get stack for specific editor
    getStack(editorId) {
        if (!this.stacks[editorId]) {
            this.stacks[editorId] = { undo: [], redo: [] };
        }
        return this.stacks[editorId];
    }

    // Save state (Snapshot)
    saveState(editorId, content) {
        const stack = this.getStack(editorId);
        
        // Don't save if identical to last state (Optimization)
        if (stack.undo.length > 0 && stack.undo[stack.undo.length - 1] === content) {
            return;
        }

        stack.undo.push(content);
        if (stack.undo.length > this.limit) stack.undo.shift();
        
        // Clear redo stack on new branch
        stack.redo = [];
        
        this.updateButtons(editorId);
        // console.log(`[History] Saved state for ${editorId}. Undo size: ${stack.undo.length}`);
    }

    // Undo Action
    undo(editorId, currentContent) {
        const stack = this.getStack(editorId);
        
        if (stack.undo.length === 0) return null;

        // Push current into redo before undoing
        stack.redo.push(currentContent);
        
        const prevState = stack.undo.pop();
        this.updateButtons(editorId);
        return prevState;
    }

    // Redo Action
    redo(editorId, currentContent) {
        const stack = this.getStack(editorId);
        
        if (stack.redo.length === 0) return null;

        // Push current into undo before redoing
        stack.undo.push(currentContent);
        
        const nextState = stack.redo.pop();
        this.updateButtons(editorId);
        return nextState;
    }
    
    // Direct capture helper (Debounced for detailed typing)
    captureDebounced(editorId, content) {
        if (this.debounceTimers[editorId]) clearTimeout(this.debounceTimers[editorId]);
        
        this.debounceTimers[editorId] = setTimeout(() => {
            this.saveState(editorId, content);
        }, 500); // 500ms pause triggers save
    }

    updateButtons(editorId) {
        // Optional: Update UI opacity if needed
        // We currently share buttons, so we might check the *Active* editor
    }
}

// Global Instance
const historyMgr = new HistoryManager();

// --- INTEGRATION UTILITIES ---

// Helper to get active editor pane
function getActiveEditor() {
    // 1. Check File Upload Panes (Separated Tabs)
    if (typeof IS_FILE_UPLOAD !== 'undefined' && IS_FILE_UPLOAD) {
        const visibleTab = Array.from(document.querySelectorAll('.media-pane')).find(el => el.style.display !== 'none');
        if (visibleTab) return visibleTab;
        
        // Fallback for Single Sheet
        const single = document.getElementById('unified-file-editor');
        if (single && single.style.display !== 'none') return single;
    } 
    
    // 2. Check Free Input Panes
    const visibleFree = Array.from(document.querySelectorAll('.free-input-editor')).find(el => {
        // Check if parent tab is visible
        const parent = el.closest('.review-tab-content');
        return parent && parent.style.display !== 'none';
        // OR if it's not tabbed (legacy logic?)
    });
    if (visibleFree) return visibleFree;

    return null;
}

function restoreCursor(el) {
    el.focus();
    // Move caret to end (Simple fallback)
    // Precise restoration is complex, but end-focus is usually acceptable for undo
    const range = document.createRange();
    range.selectNodeContents(el);
    range.collapse(false);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
}

function triggerBadgeUpdate() {
    if (typeof IS_FILE_UPLOAD !== 'undefined' && IS_FILE_UPLOAD) {
        if(typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
    } else {
        if(typeof updateFreeInputTabBadges === 'function') updateFreeInputTabBadges();
    }
}

function performUndo() { 
    const editor = getActiveEditor();
    if (!editor) {
        // Fallback: Try native if no editor found (e.g. textareas)
        document.execCommand('undo');
        return;
    }

    const prev = historyMgr.undo(editor.id, editor.innerHTML);
    if (prev !== null) {
        // [FIX] Set flag to prevent MutationObserver from preserving removed spans during undo
        window._suppressRevisionPreservation = true;
        editor.innerHTML = prev;
        window._suppressRevisionPreservation = false;
        
        // Restore Listeners (Inner HTML replace kills internal listeners if attached directly, 
        // but we rely on delegated listeners mostly. EXCEPT cell events in table)
        // Re-attach cell events if table
        if (editor.querySelector('td')) {
            editor.querySelectorAll('td').forEach(cell => {
                cell.addEventListener('mousedown', applyModeColor);
                cell.addEventListener('focus', applyModeColor);
                cell.addEventListener('input', applyModeColor);
            });
        }
        
        // [FIX] ORPHAN CARD CLEANUP after Undo
        // Remove draft/non-draft cards whose spans no longer exist inside any editor
        // (spans in hidden container don't count — they're preserved for version history, not sidebar)
        const list = document.getElementById('comment-list');
        const hiddenContainer = document.getElementById('deleted-revisions-container');
        if (list) {
            const isSpanInEditor = (id) => {
                const el = document.getElementById(id) || document.querySelector(`[data-comment-id="${id}"]`);
                // Span must exist AND not be inside hidden preservation container
                return el && (!hiddenContainer || !hiddenContainer.contains(el));
            };
            list.querySelectorAll('.comment-card.draft').forEach(card => {
                const revId = card.id.replace('card-', '');
                if (!isSpanInEditor(revId)) card.remove();
            });
            list.querySelectorAll('.comment-card:not(.draft)').forEach(card => {
                const forId = card.getAttribute('data-for');
                if (forId && !isSpanInEditor(forId)) card.remove();
            });
        }

        // Also clear spans from hidden container that were put there by previous (non-undo) operations
        // but whose content was just undone back into the editor
        
        renderSideComments();
        triggerBadgeUpdate();
    }
}

function performRedo() { 
    const editor = getActiveEditor();
    if (!editor) {
        document.execCommand('redo');
        return;
    }

    const next = historyMgr.redo(editor.id, editor.innerHTML);
    if (next !== null) {
        // [FIX] Set flag to prevent MutationObserver from preserving removed spans during redo
        window._suppressRevisionPreservation = true;
        editor.innerHTML = next;
        window._suppressRevisionPreservation = false;
        
        // Re-attach cell events if table
        if (editor.querySelector('td')) {
            editor.querySelectorAll('td').forEach(cell => {
                cell.addEventListener('mousedown', applyModeColor);
                cell.addEventListener('focus', applyModeColor);
                cell.addEventListener('input', applyModeColor);
            });
        }
        
        renderSideComments();
        triggerBadgeUpdate();
    }
}

// --- PROCEDURE MODE TOGGLE ---
window.procMode = 'suggesting'; // Default: Reviewer Mode (Red)

function setProcMode(mode) {
    window.procMode = mode;
    
    const btnSuggesting = document.getElementById('btn-mode-suggesting');
    const btnEditing = document.getElementById('btn-mode-editing');
    
    if (!btnSuggesting || !btnEditing) return;
    
    if (mode === 'suggesting') {
        // Activate Suggesting (Revisi Maker) Mode
        btnSuggesting.style.background = '#0284c7';
        btnSuggesting.style.color = 'white';
        btnSuggesting.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        
        btnEditing.style.background = 'transparent';
        btnEditing.style.color = '#0369a1';
        btnEditing.style.boxShadow = 'none';
        
        // Enable Comment Button
        const btnComment = document.getElementById('btn-comment');
        if (btnComment) {
            btnComment.style.opacity = '1';
            btnComment.style.pointerEvents = 'auto';
            btnComment.title = 'Add Comment / Highlight';
        }
    } else {
        // Activate Editing (Legal/CX Update) Mode
        btnEditing.style.background = '#0284c7';
        btnEditing.style.color = 'white';
        btnEditing.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        
        btnSuggesting.style.background = 'transparent';
        btnSuggesting.style.color = '#0369a1';
        btnSuggesting.style.boxShadow = 'none';
        
        // Disable Comment Button (visual only, addComment will also block)
        const btnComment = document.getElementById('btn-comment');
        if (btnComment) {
            btnComment.style.opacity = '0.5';
            btnComment.style.pointerEvents = 'none';
            btnComment.title = 'Comment disabled in Legal/CX Update mode';
        }
    } // Close setProcMode (this was missing!)
}

// [DUPLICATES REMOVED]
// Legacy renderSideComments, createSidebarCard, showProcModeInfo were duplicates of functionality already defined/restored below.



function setMode(mode) {
    currentColorMode = mode;
    const btnBlack = document.getElementById('btn-black');
    const btnRed = document.getElementById('btn-red');
    if(btnBlack && btnRed) {
        if (mode === 'BLACK') {
            btnBlack.classList.add('active-black');
            btnRed.classList.remove('active-red');
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('foreColor', false, '#333333');
        } else {
            btnRed.classList.add('active-red');
            btnBlack.classList.remove('active-black');
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('foreColor', false, '#ef4444');
        }
    }
}

function openTab(evt, tabId) {
    // Hide all tab content
    document.querySelectorAll(".review-tab-content").forEach(el => {
        el.style.display = "none";
    });
    
    // RESET ALL BUTTON STYLES (Free Input uses btn-media-tab with inline styles)
    document.querySelectorAll(".btn-media-tab").forEach(el => {
        el.style.background = '#f3f4f6';
        el.style.color = '#4b5563';
        el.style.border = '1px solid #e5e7eb';
        el.classList.remove('active');
    });
    
    // Show selected tab content
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
        targetTab.style.display = "block";
        
        // Focus the editor in this tab (for Free Input mode)
        const editor = targetTab.querySelector('.free-input-editor');
        if (editor) {
            setTimeout(() => editor.focus(), 100);
        }
    }
    
    // ACTIVATE BUTTON
    // Try to find button by event or ID
    let activeBtn = null;
    if (evt && evt.currentTarget) {
        activeBtn = evt.currentTarget;
    } else {
        // Fallback: Infer button ID from tabId (tab-0 -> tab-btn-review-0)
        const idx = tabId.replace('tab-', '');
        activeBtn = document.getElementById(`tab-btn-review-${idx}`);
    }

    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = '#3b82f6';
        activeBtn.style.color = 'white';
        activeBtn.style.border = 'none';
    }

    // FORCE REFRESH BADGES: Ensure "!" persists after style reset
    setTimeout(() => {
        if(typeof updateFreeInputTabBadges === 'function') updateFreeInputTabBadges();
    }, 50);
}

function updateSheetTabBadges() {
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    document.querySelectorAll('.sheet-pane, .media-pane').forEach(pane => {
        // ROBUST SELECTOR: Check for class AND various color formats (Hex, Name, RGB)
        const hasComments = pane.querySelector('.inline-comment, .revision-span, span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]');
        if (hasComments) {
            const paneId = pane.id;
            let btn = null;
            
            // Strategy 1: Find by onclick (Button or Div)
            btn = document.querySelector(`button[onclick*="'${paneId}'"], div[onclick*="'${paneId}'"]`);
            
            // Strategy 2: Specific Legacy ID Pattern (tab-media-0 -> tab-media-btn-0)
            if (!btn && paneId.startsWith('tab-media-')) {
                const legacyId = paneId.replace('tab-media-', 'tab-media-btn-');
                btn = document.getElementById(legacyId);
            }

            // Strategy 3: Free Input Pattern (tab-0 -> tab-btn-review-0)
            if (!btn && paneId.startsWith('tab-')) {
                const idx = paneId.replace('tab-', '');
                btn = document.getElementById(`tab-btn-review-${idx}`);
            }

            if (btn && !btn.querySelector('.tab-badge-dot')) {
                const dot = document.createElement('span');
                dot.className = 'tab-badge-dot';
                dot.style.cssText = "display:inline-flex;justify-content:center;align-items:center;width:16px;height:16px;background:#ef4444;color:white;font-size:10px;font-weight:bold;border-radius:50%;margin-left:6px;vertical-align:middle;";
                dot.innerText = '!';
                btn.appendChild(dot);
            }
        }
    });
}

function updateFreeInputTabBadges() {
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    
    document.querySelectorAll('.review-tab-content').forEach(pane => {
        // ROBUST SELECTOR: Check for BOTH inline-comments (Yellow) AND revision-spans (Red)
        // This ensures badges appear for ANY type of annotation.
        const hasComments = pane.querySelector('.inline-comment, .revision-span, span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]');
        
        if (hasComments) {
            const paneId = pane.id; // e.g., "tab-0"
            const idx = paneId.replace('tab-', '');
            const btn = document.getElementById(`tab-btn-review-${idx}`);
            
            if (btn && !btn.querySelector('.tab-badge-dot')) {
                const dot = document.createElement('span');
                dot.className = 'tab-badge-dot';
                dot.style.cssText = "display:inline-flex; width:16px; height:16px; background:#ef4444; color:white; font-size:10px; font-weight:bold; border-radius:50%; margin-left:6px; align-items:center; justify-content:center;";
                dot.innerText = '!';
                btn.appendChild(dot);
            }
        }
    });
}




function toggleRemarks() {
    const val = document.getElementById('decision').value;
    const remarksGroup = document.getElementById('remarksGroup');
    const remarksLabel = document.getElementById('remarksLabel');
    const remarksRequired = document.getElementById('remarksRequired');
    const remarksInput = document.getElementById('remarks');

    // Show Remarks for ALL decisions (Approve, Revise, Reject)
    // But optional for Approve
    if (val) {
        remarksGroup.style.display = 'block';
        
        if (val === 'APPROVE') {
            remarksRequired.style.display = 'none';
            remarksInput.placeholder = 'Optional notes (e.g. "Approved with conditions")...';
        } else {
            remarksRequired.style.display = 'inline';
            remarksInput.placeholder = 'Reason for revision/rejection...';
        }
    } else {
        remarksGroup.style.display = 'none';
    }

    // Show PIC Selection only for APPROVE (if role is SPV)
    if (CURRENT_USER_ROLE === 'SPV') {
        const picSelect = document.getElementById('selected_pic');
        if (picSelect) {
            // Find parent container (div with margin-bottom:15px)
            const container = picSelect.closest('div');
            if (container) {
                container.style.display = (val === 'APPROVE') ? 'block' : 'none';
            }
        }
    }
}

function saveDraft() {
    Swal.fire({
        title: 'Simpan Draft?',
        text: "Perubahan Anda akan disimpan sebagai draft.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // AUTO-COMMIT ALL DRAFTS BEFORE SAVING
            if (typeof autoCommitDrafts === 'function') autoCommitDrafts();
            executeAction('saveDraft');
        }
    });
}

let isSubmittingDecision = false; // Global guard against double submit

function submitDecision() {
    if (isSubmittingDecision) return; // Prevent double click
    const decision = document.getElementById('decision').value;
    
    // VALIDATION: PIC Selection Required for SPV Approval
    if (decision === 'APPROVE' && CURRENT_USER_ROLE === 'SPV') {
        const selectedPic = document.getElementById('selected_pic');
        if (!selectedPic || !selectedPic.value) {
            Swal.fire({
                title: 'Hold On!',
                html: 'Anda WAJIB memilih <b>PIC</b> sebelum menyetujui request ini.',
                icon: 'error'
            });
            return; // STOP
        }
    }
    
    // VALIDATION: Legal & CX Required for Approval (ONLY FOR PROCEDURE ROLE)
    if (decision === 'APPROVE' && CURRENT_USER_ROLE === 'PROCEDURE') {
        const hasLegal = REVIEW_EVIDENCE['LEGAL'] && REVIEW_EVIDENCE['LEGAL'].length > 0;
        const hasCX = REVIEW_EVIDENCE['CX'] && REVIEW_EVIDENCE['CX'].length > 0;
        
        if (!hasLegal || !hasCX) {
            let msg = 'Dokumen evidence berikut WAJIB diupload sebelum Approval:<br>';
            if (!hasLegal) msg += '- <b>Legal</b><br>';
            if (!hasCX) msg += '- <b>CX</b><br>';
            
            Swal.fire({
                title: 'Hold On!',
                html: msg,
                icon: 'error'
            });
            return; // STOP
        }
    }

    const action = decision === 'APPROVE' ? 'approve' : (decision === 'REVISE' ? 'revise' : 'reject');
    const actionLabel = decision === 'APPROVE' ? 'Menyetujui' : (decision === 'REVISE' ? 'Meminta Perbaikan' : 'Menolak');
    const actionColor = decision === 'APPROVE' ? '#16a34a' : (decision === 'REVISE' ? '#f59e0b' : '#dc2626');

    Swal.fire({
        title: 'Konfirmasi Decision',
        html: `Apakah Anda yakin ingin <b>${actionLabel}</b> request ini?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // AUTO-COMMIT ALL DRAFTS BEFORE SUBMITTING
            if (typeof autoCommitDrafts === 'function') {
                autoCommitDrafts();
            }
            window._sendToMaker = false;
            executeAction(action);
        }
    });
}

// PHASE 2: Dedicated "Kirim ke Maker" flow with review notes preview
function sendToMakerFlow() {
    if (isSubmittingDecision) return;

    // Collect all review comments from the sidebar
    const cards = document.querySelectorAll('.comment-card');
    let commentsList = '';
    let commentCount = 0;
    cards.forEach(card => {
        // Use semantic classes for accurate extraction
        const nameEl = card.querySelector('.name-label');
        const textEl = card.querySelector('.comment-body');
        
        if (textEl) {
            commentCount++;
            const name = nameEl ? nameEl.textContent.trim() : 'Reviewer';
            const text = textEl.textContent.trim();
            const shortText = text.length > 120 ? text.substring(0, 117) + '...' : text;
            
            commentsList += `<div style="padding:10px 14px; margin-bottom:8px; background:#f8fafc; border-radius:8px; border-left:4px solid #8b5cf6; font-size:13px; text-align:left; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <span style="color:#8b5cf6; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">${name}</span><br>
                <span style="color:#1e293b; line-height:1.5;">"${shortText}"</span>
            </div>`;
        }
    });

    // NEW: Collect uploaded attachments from REVIEW_EVIDENCE
    let attachmentsHtml = '';
    let attachmentCount = 0;
    const docTypes = ['LEGAL', 'CX', 'SYARIAH', 'LPP'];
    
    docTypes.forEach(type => {
        if (typeof REVIEW_EVIDENCE !== 'undefined' && REVIEW_EVIDENCE[type] && REVIEW_EVIDENCE[type].length > 0) {
            REVIEW_EVIDENCE[type].forEach(file => {
                attachmentCount++;
                const label = type.charAt(0) + type.slice(1).toLowerCase();
                attachmentsHtml += `<div style="padding:6px 12px; margin-bottom:4px; background:#ecfdf5; border-radius:6px; border-left:3px solid #10b981; font-size:12px; text-align:left; color:#065f46;">
                    <span style="font-weight:700; font-size:10px; text-transform:uppercase;">${label}</span>: ${file.filename}
                </div>`;
            });
        }
    });

    const summaryHtml = `
        <div style="text-align:left; padding: 0 5px;">
            ${commentCount > 0 ? `
                <p style="color:#475569; margin-bottom:10px; font-size:14px; font-weight:600;">📝 Revisi & Catatan (${commentCount})</p>
                <div style="max-height:200px; overflow-y:auto; margin-bottom:15px; padding-right:5px; border-radius:8px;">${commentsList}</div>
            ` : `<p style="color:#64748b; margin-bottom:15px; font-size:13px; font-style:italic;">Tidak ada catatan revisi teks.</p>`}
            
            ${attachmentCount > 0 ? `
                <p style="color:#475569; margin-bottom:10px; font-size:14px; font-weight:600;">📎 Dokumen Terlampir (${attachmentCount})</p>
                <div style="max-height:150px; overflow-y:auto; margin-bottom:15px; padding-right:5px;">${attachmentsHtml}</div>
            ` : `<p style="color:#64748b; margin-bottom:15px; font-size:13px; font-style:italic;">Tidak ada dokumen yang dilampirkan.</p>`}
            
            <p style="color:#64748b; font-size:11px; background:#f1f5f9; padding:10px; border-radius:6px; border-top:1px solid #e2e8f0;">
                Maker akan menerima semua catatan dan file di atas untuk kemudian dilakukan konfirmasi balik.
            </p>
        </div>`;

    Swal.fire({
        title: 'Kirim Konfirmasi ke Maker?',
        html: summaryHtml,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Kirim ke Maker!',
        cancelButtonText: 'Batal',
        width: 500
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof autoCommitDrafts === 'function') autoCommitDrafts();
            window._sendToMaker = true;
            executeAction('approve');
        }
    });
}

function autoCommitDrafts() {
    // Find all Draft Cards and simulate Save
    const draftCards = document.querySelectorAll('.comment-card.draft');
    draftCards.forEach(card => {
        const id = card.id.replace('card-', '');
        if (id) {
            commitRevision(id); 
        }
    });

    // Also check for any loose revision spans that missed card creation (Safety)
    const draftSpans = document.querySelectorAll('.revision-span.draft');
    draftSpans.forEach(span => {
        if (span.id && !span.getAttribute('data-comment-text')) {
             commitRevision(span.id);
        }
    });
}

function executeAction(action) {
    if (isSubmittingDecision && action !== 'saveDraft') return; // Prevent double execution
    if (action !== 'saveDraft') isSubmittingDecision = true; // Lock (but allow Save Draft)
    const form = document.getElementById('approvalForm');
    const formData = new FormData(form);
    const updatedContent = {};
    if (IS_FILE_UPLOAD) {
        // ADAPTIVE SAVE LOGIC:
        // 1. Check if we have split panes (New Multi-Sheet System)
        const panes = document.querySelectorAll('.media-pane');
        
        console.log('[DEBUG] File Upload Mode - Panes found:', panes.length, 'SERVER_CONTENT rows:', SERVER_CONTENT.length);
        
        if (panes.length > 0 && SERVER_CONTENT.length > 0) {
            // MULTI-SHEET MODE: Map each pane to its Server ID
            SERVER_CONTENT.forEach((row, index) => {
                // Try to find the specific pane for this row
                const specificPane = document.getElementById(`tab-media-${index}`);
                
                if (specificPane) {
                    updatedContent[row.id] = specificPane.innerHTML;
                    console.log(`[DEBUG] Saved pane ${index} for ID ${row.id}`);
                } else {
                    console.warn(`[WARN] Pane for index ${index} not found. Trying unified editor...`);
                    
                    // FALLBACK: Try unified editor
                    const unifiedEditor = document.getElementById('unified-file-editor');
                    if (unifiedEditor) {
                        updatedContent[row.id] = unifiedEditor.innerHTML;
                        console.log(`[DEBUG] Fallback: Used unified editor for ID ${row.id}`);
                    }
                }
            });
        } else {
            // [FIX] CROSS-SHEET CONTAMINATION FIX
            // When no .media-pane found, check for internal .sheet-pane elements
            // (from pre-built tab content rendered in Case A)
            const unifiedEditor = document.getElementById('unified-file-editor');
            if (unifiedEditor && SERVER_CONTENT.length > 0) {
                const internalPanes = unifiedEditor.querySelectorAll('.sheet-pane');
                console.log('[FIX] No .media-pane found. Internal .sheet-pane count:', internalPanes.length, 'SERVER_CONTENT:', SERVER_CONTENT.length);
                
                if (internalPanes.length > 0 && internalPanes.length >= SERVER_CONTENT.length) {
                    // MULTI-SHEET inside unified editor: Extract each sheet-pane separately
                    SERVER_CONTENT.forEach((row, index) => {
                        if (internalPanes[index]) {
                            updatedContent[row.id] = internalPanes[index].innerHTML;
                            console.log(`[FIX] Extracted sheet-pane ${index} for ID ${row.id} (size: ${internalPanes[index].innerHTML.length})`);
                        }
                    });
                } else if (SERVER_CONTENT.length === 1) {
                    // Truly single sheet — safe to use unified editor
                    updatedContent[SERVER_CONTENT[0].id] = unifiedEditor.innerHTML;
                    console.log('[DEBUG] Single Sheet Mode - content length:', unifiedEditor.innerHTML.length);
                } else {
                    console.error('[ERROR] Cannot map sheets — pane/content count mismatch!', internalPanes.length, SERVER_CONTENT.length);
                    // Last resort: save unified content to prevent data loss (but log warning)
                    const html = unifiedEditor.innerHTML;
                    SERVER_CONTENT.forEach(row => {
                        updatedContent[row.id] = html;
                    });
                }
            } else {
                console.error('[ERROR] No editor found for File Upload mode!');
            }
        }
    } else {
        // Free Input: Save HTML content (includes comment tags)
        document.querySelectorAll('.free-input-editor').forEach(editor => {
            const id = editor.getAttribute('data-id');
            updatedContent[id] = editor.innerHTML; // Changed from .value to .innerHTML
        });
    }

    console.log('[DEBUG] Final updatedContent:', Object.keys(updatedContent).length, 'items');


    const data = {};
    formData.forEach((value, key) => data[key] = value);
    data['updated_content'] = updatedContent;
    
    // [FIX V21] SEND DELETED IDS TO BACKEND
    // Convert Set to comma-separated string
    if (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.size > 0) {
        data['deleted_ids'] = Array.from(DELETED_SPANS).join(',');
        console.log('[DEBUG] Sending deleted_ids:', data['deleted_ids']);
    }
    
    // NEW: Include selected_pic for SPV approval
    if (action === 'approve' && CURRENT_USER_ROLE === 'SPV') {
        const selectedPicElem = document.getElementById('selected_pic');
        if (selectedPicElem && selectedPicElem.value) {
            data['selected_pic'] = selectedPicElem.value;
        }
    }
    
    // PHASE 2: Include send_to_maker flag for PROCEDURE approval
    if (action === 'approve' && window._sendToMaker === true) {
        data['send_to_maker'] = true;
    }
    
    fetch('index.php?controller=request&action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(json => {
        console.log('Backend response:', json); // Debug
        
        if (json.success || json.status === 'success') {
            // Clear unsaved changes flag
            hasUnsavedChanges = false;
            window.onbeforeunload = null;
            
            if (action === 'saveDraft') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Draft berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Decision berhasil disubmit.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php';
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: json.error || json.message || 'Terjadi kesalahan'
            });
            isSubmittingDecision = false; // Unlock on error so user can retry
        }
    })
    .catch(err => {
        console.error('Submit error:', err);
        isSubmittingDecision = false; // Unlock on error so user can retry
        Swal.fire({
            icon: 'error',
            title: 'Sistem Error',
            text: 'Terjadi kesalahan pada server.'
        });
    });
}

// ===== MODAL FUNCTIONS (Sync with edit.php) =====
function showModal(title, message, type = 'success') {
    const modal = document.getElementById('successModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalBtn = document.getElementById('modalBtn');
    const modalIcon = document.getElementById('modalIcon');
    
    modalTitle.textContent = title;
    modalMessage.innerHTML = message;
    
    if (type === 'error') {
        modalIcon.style.background = '#fee2e2';
        modalIcon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        modalBtn.style.background = '#dc2626';
        modalBtn.style.display = 'inline-block';
    } else {
        modalIcon.style.background = '#dcfce7';
        modalIcon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        modalBtn.style.background = 'var(--primary-red)';
        modalBtn.style.display = 'none';
    }
    
    modal.classList.add('show');
}

function showSuccess(title, message, reload = false) {
    showModal(title, message, 'success');
    
    // CRITICAL: Prevent "Unsaved Changes" popup on reload
    hasUnsavedChanges = false; 
    window.onbeforeunload = null; 

    if (reload) {
        setTimeout(() => window.location.reload(), 2000);
    } else {
        setTimeout(() => window.location.href = 'index.php', 2000);
    }
}

function closeModal() {
    document.getElementById('successModal').classList.remove('show');
}

function showCustomConfirm(title, message, onConfirm, onCancel) {
    const modal = document.getElementById('confirmModal');
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOkBtn = document.getElementById('confirmOkBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    
    confirmTitle.textContent = title;
    confirmMessage.innerHTML = `<p style="margin:0;">${message}</p>`;
    
    const newOkBtn = confirmOkBtn.cloneNode(true);
    const newCancelBtn = confirmCancelBtn.cloneNode(true);
    confirmOkBtn.parentNode.replaceChild(newOkBtn, confirmOkBtn);
    confirmCancelBtn.parentNode.replaceChild(newCancelBtn, confirmCancelBtn);
    
    newOkBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        if (onConfirm) onConfirm();
    });
    
    newCancelBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        if (onCancel) onCancel();
    });
    
    modal.classList.add('show');
}

async function handleReviewDocUpload(fileInput, docType) {
    // Normalize to uppercase immediately
    docType = docType.toUpperCase();
    
    const file = fileInput.files[0];
    if (!file) return;
    
    // Show loading indicator without clearing existing files
    const container = document.getElementById('status_' + docType.toLowerCase());
    const loadingDiv = document.createElement('div');
    loadingDiv.style.color = 'orange';
    loadingDiv.style.fontSize = '10px';
    loadingDiv.style.marginBottom = '4px';
    loadingDiv.innerHTML = '⏳ Uploading ' + file.name + '...';
    loadingDiv.id = 'temp-loading-' + Date.now();
    if (container) container.appendChild(loadingDiv);
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('doc_type', docType);
    formData.append('request_id', document.querySelector('input[name="request_id"]').value);
    
    try {
        const res = await fetch('?controller=request&action=uploadReviewDoc', { method: 'POST', body: formData });
        const json = await res.json();
        
        // Remove loading indicator
        if (loadingDiv && loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        
        if (json.success) {
            // Append to List
            if (!REVIEW_EVIDENCE[docType]) REVIEW_EVIDENCE[docType] = [];
            REVIEW_EVIDENCE[docType].push({ id: json.id, filename: file.name });
            
            // Render UI
            if (container) {
                const div = document.createElement('div');
                div.style.marginBottom = '2px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.justifyContent = 'space-between';
                div.innerHTML = `
                    <span style="color:green">✅ ${file.name}</span>
                    <button type="button" onclick="deleteReviewDoc('${docType}', '${json.id}', this)" style="background:none; border:none; color:red; cursor:pointer; font-size:10px; margin-left:4px;" title="Delete">❌</button>
                `;
                container.appendChild(div);
            }
            // Clear input for next upload
            fileInput.value = ''; 
            
            // Show success notification
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'File "' + file.name + '" berhasil di-upload',
                timer: 2000,
                showConfirmButton: false,
                position: 'center'
            });
        } else {
            Swal.fire('Error', json.error, 'error');
        }
    } catch (e) { 
        // Remove loading indicator on error too
        if (loadingDiv && loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        Swal.fire('Error', 'Upload failed: ' + e.message, 'error'); 
    }
}

async function deleteReviewDoc(docType, fileId, btnElement) {
    if (!confirm('Are you sure you want to remove this file?')) return;
    
    // Prevent form submission if event is passed (though type=button should enough)
    if(event) event.preventDefault();

    try {
        const res = await fetch('?controller=request&action=deleteReviewDoc', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ file_id: fileId }) 
        });
        const json = await res.json();
        
        if (json.success) {
            // Remove from UI
            btnElement.parentElement.remove();
            
            // Remove from Data
            if (REVIEW_EVIDENCE[docType]) {
                REVIEW_EVIDENCE[docType] = REVIEW_EVIDENCE[docType].filter(f => f.id != fileId);
            }
        } else {
             Swal.fire('Error', json.error, 'error');
        }
    } catch (e) { Swal.fire('Error', 'Delete failed', 'error'); }
}

// Global Tab Helper
function changeSheet(sheetId) {
    document.querySelectorAll('.sheet-pane').forEach(pane => pane.style.display = 'none');
    document.querySelectorAll('.btn-sheet').forEach(btn => btn.classList.remove('active'));
    const selectedSheet = document.getElementById(sheetId);
    if (selectedSheet) selectedSheet.style.display = 'block';
    if (event && event.target) {
        // Remove active from siblings? No, handled above.
        event.target.classList.add('active');
    }
    
    // Update Sidebar Comments
    if(typeof renderSideComments === 'function') renderSideComments();
}

// 3. ENSURE TAB VISIBLE (Auto Switcher)
function ensureTabVisible(targetElement) {
    if (!targetElement) return;
    
    // Find parent pane
    let pane = targetElement.closest('.media-pane, .sheet-pane, .review-tab-content');
    if (!pane) return;
    
    // Check if hidden
    if (pane.style.display === 'none' || getComputedStyle(pane).display === 'none') {
        console.log(`[DEBUG] Target is in hidden pane: ${pane.id}. Switching...`);
        
        // Strategy A: ID-based Button Matching (Robust)
        // Media Panes: id="tab-media-X" -> btn="tab-media-btn-X"
        // Free Input: id="tab-X" -> btn="tab-btn-review-X"
        
        // 1. Try Media Tab ID pattern
        if (pane.id.startsWith('tab-media-')) {
            const index = pane.id.replace('tab-media-', '');
            const btn = document.getElementById(`tab-media-btn-${index}`);
            if (btn) {
                btn.click();
                return;
            }
            // Fallback: Try openMediaTab directly if button not found?
            if (typeof openMediaTab === 'function') {
                 openMediaTab(null, index);
                 return;
            }
        }
        
        // 2. Try Free Input Tab ID pattern
        if (pane.id.startsWith('tab-')) {
            const index = pane.id.replace('tab-', '');
             const btn = document.getElementById(`tab-btn-review-${index}`);
            if (btn) {
                btn.click();
                return;
            }
        }
        
        // Strategy B: Legacy Class Matching (Fallback)
        // Iterate all active tab buttons and find one that targets this pane? 
        // Hard to do without explicit link. A above is better.
    }
}




// ==========================================
// ROBUST LIVE TYPING ENGINE (Restored)
// ==========================================

// ==========================================
// ROBUST LIVE TYPING ENGINE (Restored)
// ==========================================

// [FIX] Tracking Deleted Spans to prevent Zombie Resurrection
const DELETED_SPANS = new Set();
const BLACKLIST_IDS = new Set(); // [FIX V7] Persistent Blacklist for Exorcism

// [FIX] MutationObserver to catch removed nodes and EXORCISE zombies
const zombieObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        // A. Catch nodes being removed
        mutation.removedNodes.forEach((node) => {
            if (node.nodeType === 1 && node.classList && node.classList.contains('revision-span')) {
                // [FIX V20] PROTECT ALL SERVER-LOADED SPANS (Legacy or Rev)
                if (window.PROTECTED_IDS && window.PROTECTED_IDS.has(node.id)) {
                    console.log(`[ZOMBIE HUNTER] Ignoring protected span removal: ${node.id}`);
                    return;
                }
                
                // [FIX V19] PROTECT LEGACY SPANS (Fallback)
                if (node.id && !node.id.startsWith('legacy-')) {
                    DELETED_SPANS.add(node.id);
                    // [FIX] Also remove the sidebar card immediately
                    const card = document.getElementById(`card-${node.id}`);
                    if (card) {
                        card.remove();
                        // Re-render side comments to update positions/empty state
                        if(typeof renderSideComments === 'function') setTimeout(renderSideComments, 50);
                    }
                }
            }
        });

        // B. [FIX V7] Exorcise Zombies (DISABLED for V10 Backend Sanitizer)
        // We WANT the ID to persist so the backend can find and delete it!
        /* 
        mutation.addedNodes.forEach((node) => {
             if (node.nodeType === 1 && node.id && BLACKLIST_IDS.has(node.id)) {
                 node.removeAttribute('id');
                 node.classList.remove('revision-span'); 
                 node.style.color = ''; 
             }
        });
        */
        // C. [FIX V12] Instant Empty Check (Character Data Changes)
        // Detects backspace/delete inside a span immediately
        if (mutation.type === 'characterData' || mutation.type === 'childList') {
            const target = mutation.target;
            // Traverse up to find if we are inside a revision span
            let span = target.nodeType === 1 ? target : target.parentNode;
            if (span && span.classList && span.classList.contains('revision-span')) {
                const text = span.innerText || span.textContent || "";
                // Use strict empty check (stripping ZWSP)
                if (text.replace(/\u200B/g, '').trim().length === 0) {
                     // [FIX V20] PROTECT ALL SERVER-LOADED SPANS
                     if (window.PROTECTED_IDS && window.PROTECTED_IDS.has(span.id)) {
                         console.log(`[ZOMBIE HUNTER] Ignoring empty protected span: ${span.id}`);
                         // OPTIONAL: Restore placeholder text to keep it visible?
                         // span.innerHTML = '&nbsp;'; 
                         return;
                     }

                     // [FIX V19] PROTECT LEGACY SPANS (Don't kill empty legacy)
                     if (span.id && !span.id.startsWith('legacy-')) {
                         const card = document.getElementById(`card-${span.id}`);
                         if (card) {
                             console.log(`[INSTANT CLEANUP] Removing empty span & card: ${span.id}`);
                             card.remove();
                             if(typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
                         }
                         DELETED_SPANS.add(span.id); // Valid kill
                         span.remove(); // Kill empty span
                     }
                }
            }
        }
    });
});

// [FIX] Observer Config to watch for text changes
const observerConfig = { 
    childList: true, 
    subtree: true, 
    characterData: true,
    attributes: false 
};

// [FIX] START THE OBSERVER! (This was likely missing or lost)
zombieObserver.observe(document.body, observerConfig);
console.log('[ZOMBIE HUNTER] Observer started with Instant Cleanup (V12)');

// [FIX] Observer is already declared above.


// [FIX V8] Active Polling Hunter (The Singularity)
// Aggressively hunt down blacklisted IDs that might sneak back in
    // [FIX V11] Robust Card Cleanup (The Janitor)
// Periodically checks if cards exist for non-existent or empty spans
function cleanupZombieCards() {
    const cards = document.querySelectorAll('.comment-card');
    cards.forEach(card => {
        const spanId = card.getAttribute('data-for');
        if (!spanId) return;

        // [FIX] Ignore Legacy IDs for Janitor cleanup (Protection Mode)
        // Legacy spans might not be found by ID immediately, so we trust they exist.
        if (spanId.startsWith('legacy-')) return; 

        const span = document.getElementById(spanId);
        
        // Helper to check for content (ignoring ZWSP and whitespace)
        const hasContent = (element) => {
            if (!element) return false;
            const text = element.innerText || element.textContent || "";
            // Remove ZWSP and whitespace
            const clean = text.replace(/\u200B/g, '').trim();
            return clean.length > 0;
        };

        // Condition to kill: Span gone OR Span empty OR [V18] Span in Death Note
        // [FIX] Disable Blacklist check for Janitor to prevent false positives
        const isBlacklisted = false; // (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.has(spanId));
        
        if (!span || !hasContent(span) || isBlacklisted) {
            // [FIX] CRITICAL: Add to Death Note so Backend Sanitizer kills it
            if(span && span.id) DELETED_SPANS.add(span.id); 
            
            console.log(`[JANITOR] Removing orphan card & span for ID: ${spanId}`);
            card.remove();
            
            // Also remove span if it's empty but still in DOM
            if (span) span.remove();
            
            // Refresh Badges (Notif Pentung)
            if(typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
        }
    });
}

// Attach Janitor to heavy-traffic events
document.addEventListener('input', () => setTimeout(cleanupZombieCards, 100));
document.addEventListener('keyup', () => setTimeout(cleanupZombieCards, 100));
document.addEventListener('click', () => setTimeout(cleanupZombieCards, 100)); // For cut/paste/context menu

// [FIX V13] UNDO/REDO Listener
// Force cleanup when user presses Ctrl+Z or Ctrl+Y
// [FIX V15] UNDO/REDO "The Snapshot" Logic
// Detects specific comments resurfacing during Undo and adds them to Death Note
// [FIX V16] UNDO/REDO "The Discriminator" Logic
// V15 Snapshot was too aggressive and killed cards on Redo.
// Now we distinguish:
// Ctrl+Z (Undo): Mark newly appearing items as Zombies (if they shouldn't be there)
// Ctrl+Y (Redo): Just refresh UI, DO NOT mark as Zombies.

// [FIX V17] UNDO/REDO "Time Travel Logic"
// Smarter handling of DELETED_SPANS based on Time Direction
// Undo (Backwards): Forgive disappearances (undo create), Condemn resurrections (undo delete)
// Redo (Forwards): Condemn disappearances (redo delete), Forgive resurrections (redo create)

document.addEventListener('keydown', (e) => {
    // Check for Ctrl+Z (Undo) OR Ctrl+Y (Redo)
    const isUndo = (e.ctrlKey || e.metaKey) && e.key === 'z';
    const isRedo = (e.ctrlKey || e.metaKey) && e.key === 'y';

    if (isUndo || isRedo) {
        // [FIX UNDO] Check if we're inside an editable area
        const activeEditor = typeof getActiveEditor === 'function' ? getActiveEditor() : null;
        
        if (activeEditor && typeof historyMgr !== 'undefined') {
            // PREVENT browser native undo — use custom HistoryManager exclusively
            e.preventDefault();
            e.stopPropagation();
            
            console.log(`[HISTORY] Custom ${isUndo ? 'UNDO (<<)' : 'REDO (>>)'} via HistoryManager`);
            
            // 1. Capture State BEFORE Action
            const preStateIds = new Set();
            document.querySelectorAll('.revision-span, [data-comment-id]').forEach(el => {
                const id = el.getAttribute('data-comment-id') || el.id;
                if (id) preStateIds.add(id);
            });

            // 2. Execute custom undo/redo
            if (isUndo) {
                performUndo();
            } else {
                performRedo();
            }

            // 3. Wait for DOM to settle, then sync sidebar
            setTimeout(() => {
                // Capture State AFTER Action
                const postStateIds = new Set();
                document.querySelectorAll('.revision-span, [data-comment-id]').forEach(el => {
                    const id = el.getAttribute('data-comment-id') || el.id;
                    if (id) postStateIds.add(id);
                });

                // Calculate Diffs
                const disappeared = new Set([...preStateIds].filter(x => !postStateIds.has(x)));
                const resurrected = new Set([...postStateIds].filter(x => !preStateIds.has(x)));

                if (typeof DELETED_SPANS !== 'undefined') {
                    if (isUndo) {
                        disappeared.forEach(id => {
                            console.log(`[UNDO] Forgiving ${id} (Undo Creation)`);
                            DELETED_SPANS.delete(id);
                        });
                        resurrected.forEach(id => {
                            console.log(`[UNDO] Condemning ${id} (Undo Deletion)`);
                            DELETED_SPANS.add(id);
                        });
                    } 
                    else if (isRedo) {
                        disappeared.forEach(id => {
                            console.log(`[REDO] Condemning ${id} (Redo Deletion)`);
                            DELETED_SPANS.add(id);
                        });
                        resurrected.forEach(id => {
                            console.log(`[REDO] Forgiving ${id} (Redo Creation)`);
                            DELETED_SPANS.delete(id);
                        });
                    }
                }

                // Force UI Sync
                cleanupZombieCards();
                if(typeof renderSideComments === 'function') renderSideComments();
                if(typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
                
            }, 100);
        } else {
            // Not in an editable area — let browser handle natively
            console.log(`[HISTORY] Native ${isUndo ? 'UNDO' : 'REDO'} (No active editor)`);
        }
    }
});

// [FIX V8] Active Polling Hunter (DISABLED for V10)
    /*
    setInterval(() => {
        BLACKLIST_IDS.forEach(id => {
            const zombie = document.getElementById(id);
            if (zombie) {
                console.warn(`[HUNTER] Caught zombie ID: ${id}. Stripping ID now.`);
                zombie.removeAttribute('id');
                zombie.classList.remove('revision-span');
                zombie.style.color = ''; // Optional reset
            }
        });
    }, 500);
    */

// 1. Handle Input (Auto Red) - Robust Version
// [HELPER] Create strikethrough span for selected non-draft text before replacement
// Called when user selects original text and types/pastes over it
function createStrikethroughForSelection(range) {
    if (!range || range.collapsed) return;
    
    const selectedText = range.toString();
    if (!selectedText || selectedText.trim().length === 0) return;
    
    // Check if the ENTIRE selection is inside a draft span belonging to current user
    // If so, no need to track — it's their own draft being edited
    const commonAncestor = range.commonAncestorContainer;
    const ancestorEl = commonAncestor.nodeType === Node.TEXT_NODE ? commonAncestor.parentElement : commonAncestor;
    const insideDraft = ancestorEl?.closest?.('.revision-span.draft');
    if (insideDraft) {
        const owner = insideDraft.getAttribute('data-comment-user') || insideDraft.getAttribute('data-user') || '';
        if (owner === (window.CURRENT_USER_NAME || '')) return; // Own draft, skip
    }
    
    // Check if selection is entirely inside an existing deletion span (already tracked)
    if (ancestorEl?.closest?.('.deletion-span')) return;
    
    // [FIX] Check if selection is inside another user's COMMITTED revision-span
    // This handles PIC replacing SPV's red text, etc.
    const committedSpan = ancestorEl?.closest?.('.revision-span:not(.draft):not(.deletion-span)');
    if (committedSpan && committedSpan.id) {
        const spanOwner = committedSpan.getAttribute('data-comment-user') || committedSpan.getAttribute('data-user') || '';
        const spanRole = committedSpan.getAttribute('data-comment-role') || committedSpan.getAttribute('data-comment-dept') || '';
        
        // Only apply special handling if it's NOT the current user's span
        if (spanOwner !== (window.CURRENT_USER_NAME || '')) {
            console.log(`[REPLACE] Replacing committed span ${committedSpan.id} by ${spanOwner} (${spanRole})`);
            
            // Store metadata for the new replacement span to use
            window._lastReplacedSpanInfo = {
                text: selectedText,
                user: spanOwner,
                role: spanRole,
                spanId: committedSpan.id
            };
            
            // Create deletion-span OUTSIDE the committed span
            const revId = `rev-${Date.now()}-del-${Math.random().toString(36).substr(2, 5)}`;
            const strikeSpan = document.createElement('span');
            strikeSpan.id = revId;
            strikeSpan.className = 'revision-span deletion-span';
            strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
            strikeSpan.setAttribute('data-is-deletion', 'true');
            strikeSpan.setAttribute('data-deleted-text', selectedText);
            strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'USER');
            strikeSpan.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
            strikeSpan.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
            strikeSpan.contentEditable = 'false';
            strikeSpan.textContent = selectedText;
            
            // Insert deletion-span BEFORE the committed span (outside it)
            committedSpan.parentNode.insertBefore(strikeSpan, committedSpan);
            
            // Mark original span for removal after deleteContents
            // Mark as superseded so sidebar treats it correctly
            committedSpan.setAttribute('data-superseded', 'true');
            DELETED_SPANS.add(committedSpan.id);
            
            // Remove the committed span entirely (unwrap its content is not needed since we're replacing)
            committedSpan.remove();
            
            // Reposition range right after the deletion-span for new text insertion
            range.setStartAfter(strikeSpan);
            range.setEndAfter(strikeSpan);
            range.collapse(true);
            
            // Update selection to match
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            
            // Trigger sidebar refresh
            if (window._sidebarTimer) clearTimeout(window._sidebarTimer);
            window._sidebarTimer = setTimeout(renderSideComments, 300);
            return; // Done — skip the generic path below
        }
    }
    
    // Extract the non-draft text parts from selection
    // We need to collect text that is NOT inside a draft span of the current user
    let textToTrack = '';
    const fragment = range.cloneContents();
    const tempDiv = document.createElement('div');
    tempDiv.appendChild(fragment);
    
    // Walk through the cloned fragment and collect text NOT in current user's drafts
    function collectNonDraftText(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            textToTrack += node.textContent;
            return;
        }
        if (node.nodeType === Node.ELEMENT_NODE) {
            // Skip current user's draft spans
            if (node.classList.contains('revision-span') && node.classList.contains('draft')) {
                const draftOwner = node.getAttribute('data-comment-user') || node.getAttribute('data-user') || '';
                if (draftOwner === (window.CURRENT_USER_NAME || '')) return; // Skip own draft text
            }
            // Skip existing deletion spans
            if (node.classList.contains('deletion-span')) return;
            
            for (const child of node.childNodes) {
                collectNonDraftText(child);
            }
        }
    }
    collectNonDraftText(tempDiv);
    
    if (!textToTrack || textToTrack.trim().length === 0) return;
    
    // Create strikethrough span for the tracked text
    const revId = `rev-${Date.now()}-del-${Math.random().toString(36).substr(2, 5)}`;
    const strikeSpan = document.createElement('span');
    strikeSpan.id = revId;
    strikeSpan.className = 'revision-span deletion-span';
    strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
    strikeSpan.setAttribute('data-is-deletion', 'true');
    strikeSpan.setAttribute('data-deleted-text', textToTrack);
    strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'USER');
    strikeSpan.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
    strikeSpan.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
    strikeSpan.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
    strikeSpan.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
    strikeSpan.contentEditable = 'false';
    strikeSpan.textContent = textToTrack;
    
    // Insert strikethrough BEFORE the selection (it will persist after deleteContents)
    range.insertNode(strikeSpan);
    
    // Move the range to AFTER the strikethrough span so deleteContents removes the original text
    range.setStartAfter(strikeSpan);
    
    // Trigger sidebar refresh
    if (window._sidebarTimer) clearTimeout(window._sidebarTimer);
    window._sidebarTimer = setTimeout(renderSideComments, 300);
}

function handleBeforeInput(e) {
    if (typeof isEditing !== 'undefined' && !isEditing) return;

    // [FIX] TARGET VALIDATION: Only handle input for actual editor panes
    // This prevents "Auto Red Leak" in sidebars, textareas, or comment modals.
    const target = e.target;
    const isStandardInput = (target.tagName === 'TEXTAREA' || target.tagName === 'INPUT');
    const isSidebarOrModal = target.closest('#comment-sidebar') || target.closest('#comment-modal');
    const isInsideEditor = target.closest('.review-editor') || target.closest('.media-pane') || target.classList.contains('review-editor') || target.classList.contains('media-pane');

    if (isStandardInput || isSidebarOrModal || !isInsideEditor) {
        // [DEBUG] console.log("[LEAK PREVENTED] Ignoring input in non-editor area:", target);
        return; 
    }

    // DEFAULT TO SUGGESTING (RED) IF PROCMODE UNDEFINED
    // This restores Auto Red for users who haven't explicitly set mode (Legacy)
    const mode = window.procMode || 'suggesting';
    
    // [FIX] SKIP Auto Red entirely in Legal/CX Update mode
    // Let the browser handle typing normally (black text)
    if (mode === 'editing') return;

    // A. HANDLE INSERTION (Typing)
    if ((e.inputType === 'insertText' || e.inputType === 'insertCompositionText') && e.data) {
        e.preventDefault(); 
        
        // SUPPRESS sidebar rebuild during our DOM changes (prevents draft cards from flickering)
        window._isInternalChange = true;
        clearTimeout(window._internalChangeTimer);
        window._internalChangeTimer = setTimeout(() => window._isInternalChange = false, 2000);
        
        // [FIX] Suppress observer during DOM manipulation to prevent ghost duplicates
        window._suppressRevisionPreservation = true;
        // [FIX] DOUBLE INPUT PREVENTION
        // Ensure we kill any native composition if possible, though preventDefault should handle it for insertText.
        // For Android/IME, sometimes composition events bypass.
        
        const selection = window.getSelection();
        if (!selection.rangeCount) return;
        
        // [FIX] Pre-emptive Cleanup: Delete whatever is currently selected or about to be overwritten
        // This ensures the browser hasn't already slipped text in.
        const range = selection.getRangeAt(0);
        if (!range.collapsed) {
             // [FIX] TRACK SELECTION REPLACEMENT: Create strikethrough for selected non-draft text
             createStrikethroughForSelection(range);
             // After createStrikethroughForSelection, range may already be collapsed 
             // (committed span replacement path handles its own cleanup)
             if (!range.collapsed) {
                 range.deleteContents();
             }
        }
        
        // [FIX] Reposition cursor AFTER any deletion-span (contentEditable=false)
        // This prevents new text from being sandwiched inside non-editable areas
        const curSel = window.getSelection();
        if (curSel.rangeCount > 0) {
            let curNode = curSel.anchorNode;
            if (curNode && curNode.nodeType === 3) curNode = curNode.parentNode;
            const nearDeletion = curNode?.closest?.('.deletion-span');
            if (nearDeletion) {
                const escapeRange = document.createRange();
                escapeRange.setStartAfter(nearDeletion);
                escapeRange.collapse(true);
                curSel.removeAllRanges();
                curSel.addRange(escapeRange);
            }
        }
        
        // Find if we are inserting inside an existing revision span
        let el = selection.anchorNode;
        if (el.nodeType === 3) el = el.parentNode; 
        
        let targetSpan = null;
        let walker = el;
        // Search up but stop at contenteditable
        while (walker && walker !== document.body && !walker.isContentEditable) {
            if (walker.nodeType === 1 && walker.classList.contains('revision-span') && walker.style.color === 'red'
                && !walker.classList.contains('deletion-span')) { // [FIX] Never target deletion spans for text insertion
                // [FIX] ZOMBIE CHECK V2: Check DELETED_SPANS and Detachment
                if (DELETED_SPANS.has(walker.id) || !document.contains(walker)) {
                    targetSpan = null; 
                // [FIX] Skip COMMITTED spans (revision-span WITHOUT draft class) from previous reviewers
                // These should NOT absorb new typed text — instead, a fresh draft span will be created
                } else if (!walker.classList.contains('draft')) {
                    targetSpan = null;
                    console.log(`[FIX] Skipping committed span ${walker.id} — will create new draft span instead`);
                } else {
                    targetSpan = walker;
                }
                break;
            }
            walker = walker.parentNode;
        }
        
        // Also check if we are directly inside a span but anchor is text
        // [FIX] Only use draft spans as target, skip committed spans from previous reviewers
        if(el.nodeType === 1 && el.classList.contains('revision-span') && el.classList.contains('draft')) targetSpan = el;

        if (targetSpan) {
            // [FIX] INSERT at cursor position inside existing span (not just append)
            // Determine the cursor offset within the text node
            const anchorNode = selection.anchorNode;
            const anchorOffset = selection.anchorOffset;
            
            // Find the text node the cursor is in (or create one)
            let textNode = null;
            let insertOffset = 0;
            
            if (anchorNode && anchorNode.nodeType === 3 && targetSpan.contains(anchorNode)) {
                // Cursor is inside a text node within this span
                textNode = anchorNode;
                insertOffset = anchorOffset;
            } else {
                // Fallback: use first text node or create one
                textNode = targetSpan.childNodes[0] || targetSpan.appendChild(document.createTextNode(''));
                insertOffset = textNode.nodeType === 3 ? textNode.textContent.length : 0;
            }
            
            if (textNode && textNode.nodeType === 3) {
                // Insert character at the exact cursor position
                const before = textNode.nodeValue.substring(0, insertOffset);
                const after = textNode.nodeValue.substring(insertOffset);
                textNode.nodeValue = before + e.data + after;
                insertOffset += e.data.length;
            } else {
                targetSpan.textContent += e.data;
                textNode = targetSpan.firstChild;
                insertOffset = textNode ? textNode.textContent.length : 0;
            }

            // Place cursor right after the inserted character
            const cursorNode = textNode || targetSpan.lastChild || targetSpan;
            const range2 = document.createRange();
            if (cursorNode.nodeType === 3) {
                range2.setStart(cursorNode, insertOffset);
                range2.setEnd(cursorNode, insertOffset);
            } else {
                range2.selectNodeContents(targetSpan);
                range2.collapse(false);
            }
            selection.removeAllRanges();
            selection.addRange(range2);
            
            const cleanText = targetSpan.innerText.replace(/\u200B/g, '');
            if(typeof updateDraftCard === 'function') updateDraftCard(targetSpan.id, cleanText);
        } else {
            // CREATE NEW RED SPAN (Auto Red)
            const revId = "rev-" + Date.now();
            const span = document.createElement("span");
            span.className = "revision-span draft";
            span.id = revId;
            span.setAttribute('data-comment-id', revId);
            span.style.color = "red";
            span.textContent = e.data; 
            span.setAttribute('data-user', CURRENT_USER_NAME);
            span.setAttribute('data-comment-user', CURRENT_USER_NAME);
            span.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
            span.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
            span.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
            span.setAttribute('data-time', new Date().toISOString());
            span.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
            
            // [FIX] Attach "Replaces" metadata if this is a replacement of another user's span
            if (window._lastReplacedSpanInfo) {
                const info = window._lastReplacedSpanInfo;
                span.setAttribute('data-replaces-text', info.text);
                span.setAttribute('data-replaces-user', info.user);
                span.setAttribute('data-replaces-role', info.role);
                span.setAttribute('data-revision-version', '2');
                console.log(`[REPLACE] New span ${revId} replaces "${info.text}" by ${info.user}`);
                window._lastReplacedSpanInfo = null; // Clear after use
            }
            
            const range = selection.getRangeAt(0);
            if (!range.collapsed) range.deleteContents();
            range.insertNode(span);
            
            // [FIX] Place cursor explicitly INSIDE the text node of the new span
            const spanTextNode = span.firstChild || span;
            const newRange = document.createRange();
            if (spanTextNode.nodeType === 3) {
                newRange.setStart(spanTextNode, spanTextNode.textContent.length);
                newRange.setEnd(spanTextNode, spanTextNode.textContent.length);
            } else {
                newRange.selectNodeContents(span);
                newRange.collapse(false);
            }
            selection.removeAllRanges();
            selection.addRange(newRange);
            
            if(typeof updateDraftCard === 'function') updateDraftCard(revId, e.data);
            
            // [HISTORY] Capture State
            if (typeof getActiveEditor === 'function' && typeof historyMgr !== 'undefined') {
                const activeEd = getActiveEditor();
                if (activeEd) historyMgr.captureDebounced(activeEd.id, activeEd.innerHTML);
            }
        }
        window._suppressRevisionPreservation = false;
    } 
    
    // A2. HANDLE PASTE (Ctrl+V / Right-click Paste)
    if (e.inputType === 'insertFromPaste') {
        e.preventDefault();
        
        // Extract plain text from paste (strip HTML formatting)
        const pastedText = (e.dataTransfer && e.dataTransfer.getData('text/plain')) || '';
        if (!pastedText.trim()) return;
        
        window._suppressRevisionPreservation = true;
        
        const selection = window.getSelection();
        if (!selection.rangeCount) { window._suppressRevisionPreservation = false; return; }
        
        const range = selection.getRangeAt(0);
        if (!range.collapsed) {
            // [FIX] TRACK SELECTION REPLACEMENT: Create strikethrough for selected non-draft text
            createStrikethroughForSelection(range);
            // Guard: committed-span path collapses range on its own
            if (!range.collapsed) {
                range.deleteContents();
            }
        }
        
        // Check if cursor is inside an existing red span
        let el = selection.anchorNode;
        if (el && el.nodeType === 3) el = el.parentNode;
        
        // [FIX] PASTE-IN-MIDDLE-OF-DELETION: If cursor is inside or adjacent to a deletion-span,
        // reposition the insertion point AFTER the deletion span before creating new content.
        const closestDeletion = el?.closest?.('.deletion-span');
        if (closestDeletion) {
            const newRange = document.createRange();
            newRange.setStartAfter(closestDeletion);
            newRange.collapse(true);
            selection.removeAllRanges();
            selection.addRange(newRange);
            // Re-fetch anchor after repositioning
            el = selection.anchorNode;
            if (el && el.nodeType === 3) el = el.parentNode;
        }
        
        let targetSpan = null;
        let walker = el;
        while (walker && walker !== document.body && !walker.isContentEditable) {
            if (walker.nodeType === 1 && walker.classList.contains('revision-span') && walker.style.color === 'red') {
                if (!DELETED_SPANS.has(walker.id) && document.contains(walker)) {
                    targetSpan = walker;
                }
                break;
            }
            walker = walker.parentNode;
        }
        if (el && el.nodeType === 1 && el.classList.contains('revision-span')) targetSpan = el;
        
        if (targetSpan) {
            // Append pasted text to existing span
            const textNode = targetSpan.childNodes[0] || targetSpan.appendChild(document.createTextNode(''));
            if (textNode.nodeType === 3) {
                textNode.nodeValue += pastedText;
            } else {
                targetSpan.textContent += pastedText;
            }
            // Cursor positioning
            const cursorNode = targetSpan.lastChild || targetSpan;
            const r = document.createRange();
            if (cursorNode.nodeType === 3) {
                r.setStart(cursorNode, cursorNode.textContent.length);
                r.setEnd(cursorNode, cursorNode.textContent.length);
            } else {
                r.selectNodeContents(targetSpan);
                r.collapse(false);
            }
            selection.removeAllRanges();
            selection.addRange(r);
            
            const cleanText = targetSpan.innerText.replace(/\u200B/g, '');
            if (typeof updateDraftCard === 'function') updateDraftCard(targetSpan.id, cleanText);
        } else {
            // Create NEW red span with pasted text
            const revId = "rev-" + Date.now();
            const span = document.createElement("span");
            span.className = "revision-span draft";
            span.id = revId;
            span.setAttribute('data-comment-id', revId);
            span.style.color = "red";
            span.textContent = pastedText;
            span.setAttribute('data-user', CURRENT_USER_NAME);
            span.setAttribute('data-comment-user', CURRENT_USER_NAME);
            span.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
            span.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
            span.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
            span.setAttribute('data-time', new Date().toISOString());
            span.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
            
            // [FIX] Attach "Replaces" metadata if pasting over another user's span
            if (window._lastReplacedSpanInfo) {
                const info = window._lastReplacedSpanInfo;
                span.setAttribute('data-replaces-text', info.text);
                span.setAttribute('data-replaces-user', info.user);
                span.setAttribute('data-replaces-role', info.role);
                span.setAttribute('data-revision-version', '2');
                window._lastReplacedSpanInfo = null;
            }
            
            range.insertNode(span);
            
            // Cursor after pasted text
            const spanTextNode = span.firstChild || span;
            const newRange = document.createRange();
            if (spanTextNode.nodeType === 3) {
                newRange.setStart(spanTextNode, spanTextNode.textContent.length);
                newRange.setEnd(spanTextNode, spanTextNode.textContent.length);
            } else {
                newRange.selectNodeContents(span);
                newRange.collapse(false);
            }
            selection.removeAllRanges();
            selection.addRange(newRange);
            
            if (typeof updateDraftCard === 'function') updateDraftCard(revId, pastedText);
            
            // Capture history
            if (typeof getActiveEditor === 'function' && typeof historyMgr !== 'undefined') {
                const activeEd = getActiveEditor();
                if (activeEd) historyMgr.captureDebounced(activeEd.id, activeEd.innerHTML);
            }
        }
        window._suppressRevisionPreservation = false;
    }
    
    // B. HANDLE DELETION (Strikethrough Tracking)
    // [FIX] Also handle deleteByCut (Ctrl+X) and deleteByDrag to prevent content loss
    if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward' || e.inputType === 'deleteByCut' || e.inputType === 'deleteByDrag') {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        const range = sel.getRangeAt(0);
        
        // [FIX] If cursor is inside a DRAFT revision-span belonging to current user,
        // allow normal deletion (editing own work). Only track deletion of original/committed content.
        const cursorSpan = sel.anchorNode?.parentElement?.closest?.('.revision-span.draft');
        const spanOwner = cursorSpan ? (cursorSpan.getAttribute('data-comment-user') || cursorSpan.getAttribute('data-user') || '') : '';
        if (cursorSpan && spanOwner === (window.CURRENT_USER_NAME || '')) {
            // Own draft — let browser delete normally, then cleanup
            const spanId = cursorSpan.id; // Capture ID before potential DOM changes
            
            // SUPPRESS sidebar rebuild during cleanup
            window._isInternalChange = true;
            clearTimeout(window._internalChangeTimer);
            
            // Use 50ms delay to let browser finish its native deletion first
            setTimeout(() => {
                window._suppressRevisionPreservation = true;
                
                // TARGETED CLEANUP: Check the specific span we were editing
                const spanRef = document.getElementById(spanId);
                if (spanRef) {
                    let txt = (spanRef.innerText || spanRef.textContent || '').replace(/[\u200B\u00A0\n\r]/g, '').trim();
                    if (txt.length === 0) {
                        spanRef.remove();
                        const card = document.getElementById(`card-${spanId}`);
                        if (card) card.remove();
                    } else {
                        if (typeof updateDraftCard === 'function') updateDraftCard(spanId, txt);
                    }
                } else {
                    // Span was already removed by browser
                    const card = document.getElementById(`card-${spanId}`);
                    if (card) card.remove();
                }
                
                // Secondary sweep for any other empty draft spans
                document.querySelectorAll('.revision-span.draft').forEach(s => {
                    const hc = document.getElementById('deleted-revisions-container');
                    if (hc && hc.contains(s)) return;
                    let txt = (s.innerText || s.textContent || '').replace(/[\u200B\u00A0\n\r]/g, '').trim();
                    if (txt.length === 0) {
                        const cId = s.id;
                        s.remove();
                        const c = document.getElementById(`card-${cId}`);
                        if (c) c.remove();
                    }
                });
                
                // Refresh sidebar and badges after cleanup
                renderSideComments();
                if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
                
                window._suppressRevisionPreservation = false;
                setTimeout(() => window._isInternalChange = false, 100);
            }, 50);
            
            // Safety net: secondary check at 150ms for slow browsers
            setTimeout(() => {
                const spanRef2 = document.getElementById(spanId);
                if (spanRef2) {
                    let txt2 = (spanRef2.innerText || spanRef2.textContent || '').replace(/[\u200B\u00A0\n\r]/g, '').trim();
                    if (txt2.length === 0) {
                        spanRef2.remove();
                        const card2 = document.getElementById(`card-${spanId}`);
                        if (card2) card2.remove();
                        renderSideComments();
                    }
                }
            }, 150);
            
            return; // Let browser handle this delete
        }
        
        // [FIX] Save undo snapshot BEFORE modifying DOM for strikethrough
        // Without this, Ctrl+Z would restore a state that already has the deletion span
        if (typeof getActiveEditor === 'function' && typeof historyMgr !== 'undefined') {
            const activeEd = getActiveEditor();
            if (activeEd) historyMgr.saveState(activeEd.id, activeEd.innerHTML);
        }
        
        // STRIKETHROUGH TRACKING: Prevent actual deletion, wrap in strikethrough span
        e.preventDefault();
        window._suppressRevisionPreservation = true;
        
        let textToStrike = '';
        let targetNode = null;
        let targetOffset = 0;
        
        if (!range.collapsed) {
            // SELECTION EXISTS: Wrap entire selection in strikethrough
            textToStrike = range.toString();
        } else {
            // NO SELECTION: Get single character to strike
            const node = sel.anchorNode;
            const offset = sel.anchorOffset;
            
            if (node && node.nodeType === Node.TEXT_NODE) {
                if (e.inputType === 'deleteContentBackward' && offset > 0) {
                    textToStrike = node.textContent.charAt(offset - 1);
                    targetNode = node;
                    targetOffset = offset;
                } else if (e.inputType === 'deleteContentForward' && offset < node.textContent.length) {
                    textToStrike = node.textContent.charAt(offset);
                    targetNode = node;
                    targetOffset = offset;
                }
            } else if (node && node.nodeType === Node.ELEMENT_NODE) {
                // Cursor might be between elements (e.g. after a contentEditable=false span)
                // Try to find the adjacent text node
                if (e.inputType === 'deleteContentBackward') {
                    // Look for text node before cursor position
                    let prevNode = offset > 0 ? node.childNodes[offset - 1] : null;
                    // If prevNode is a deletion span, skip (already struck)
                    if (prevNode && prevNode.classList && prevNode.classList.contains('deletion-span')) {
                        // Try the node before the deletion span
                        prevNode = prevNode.previousSibling;
                    }
                    if (prevNode && prevNode.nodeType === Node.TEXT_NODE && prevNode.textContent.length > 0) {
                        textToStrike = prevNode.textContent.charAt(prevNode.textContent.length - 1);
                        targetNode = prevNode;
                        targetOffset = prevNode.textContent.length;
                    }
                } else if (e.inputType === 'deleteContentForward') {
                    let nextNode = offset < node.childNodes.length ? node.childNodes[offset] : null;
                    if (nextNode && nextNode.classList && nextNode.classList.contains('deletion-span')) {
                        nextNode = nextNode.nextSibling;
                    }
                    if (nextNode && nextNode.nodeType === Node.TEXT_NODE && nextNode.textContent.length > 0) {
                        textToStrike = nextNode.textContent.charAt(0);
                        targetNode = nextNode;
                        targetOffset = 0;
                    }
                }
            }
        }
        
        if (!textToStrike || textToStrike.trim().length === 0) {
            window._suppressRevisionPreservation = false;
            return;
        }
        
        // Check if target text is already inside a strikethrough span (avoid double-strike)
        const anchorEl = sel.anchorNode?.nodeType === Node.TEXT_NODE ? sel.anchorNode.parentElement : sel.anchorNode;
        const existingStrike = anchorEl?.closest?.('.deletion-span');
        if (existingStrike) {
            window._suppressRevisionPreservation = false;
            return;
        }
        
        // [FIX] CONSECUTIVE DELETE: Check for adjacent deletion span to extend
        let adjacentStrike = null;
        if (range.collapsed && targetNode) {
            if (e.inputType === 'deleteContentBackward') {
                // Backspace: check if there's a deletion span RIGHT AFTER the char being deleted
                // (Because previous backspace placed cursor before its strikethrough span)
                let nextSib = targetNode.nextSibling;
                // After splitting, the next sibling might be the afterNode text, and the span is after that
                // Actually let's check: after we remove a char and place strikethrough, cursor is BEFORE the span
                // So on next backspace, targetNode is the text BEFORE the span, nextSibling IS the span
                if (nextSib && nextSib.classList && nextSib.classList.contains('deletion-span')) {
                    adjacentStrike = nextSib;
                }
                // Also check: cursor might be right at offset 0 of a text node that follows a deletion span
                if (!adjacentStrike && targetOffset === 1) {
                    // The char we're striking is at position 0 of targetNode
                    // Check if previous sibling is a deletion span
                    // No — for backspace, we want to PREPEND to the span that comes AFTER
                }
            } else if (e.inputType === 'deleteContentForward') {
                // Delete key: check if there's a deletion span RIGHT BEFORE
                let prevSib = targetNode.previousSibling;
                if (prevSib && prevSib.classList && prevSib.classList.contains('deletion-span')) {
                    adjacentStrike = prevSib;
                }
            }
        }
        
        if (adjacentStrike && range.collapsed && targetNode) {
            // EXTEND existing deletion span instead of creating a new one
            if (e.inputType === 'deleteContentBackward') {
                // Remove char from text node and PREPEND to deletion span
                const before = targetNode.textContent.substring(0, targetOffset - 1);
                const after = targetNode.textContent.substring(targetOffset);
                targetNode.textContent = before + after;
                
                // Prepend char to deletion span
                adjacentStrike.textContent = textToStrike + adjacentStrike.textContent;
                adjacentStrike.setAttribute('data-deleted-text', adjacentStrike.textContent);
                
                // Clean up empty text nodes
                if (targetNode.textContent.length === 0) targetNode.remove();
            } else {
                // Delete key: remove char and APPEND to deletion span
                const before = targetNode.textContent.substring(0, targetOffset);
                const after = targetNode.textContent.substring(targetOffset + 1);
                targetNode.textContent = before + after;
                
                adjacentStrike.textContent = adjacentStrike.textContent + textToStrike;
                adjacentStrike.setAttribute('data-deleted-text', adjacentStrike.textContent);
                
                if (targetNode.textContent.length === 0) targetNode.remove();
            }
            
            // Position cursor appropriately
            const newRange = document.createRange();
            if (e.inputType === 'deleteContentBackward') {
                newRange.setStartBefore(adjacentStrike);
            } else {
                newRange.setStartAfter(adjacentStrike);
            }
            newRange.collapse(true);
            sel.removeAllRanges();
            sel.addRange(newRange);
            
        } else if (!range.collapsed) {
            // SELECTION: Create new strikethrough span wrapping the selection
            const revId = `rev-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
            const strikeSpan = document.createElement('span');
            strikeSpan.id = revId;
            strikeSpan.className = 'revision-span deletion-span';
            strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
            strikeSpan.setAttribute('data-is-deletion', 'true');
            strikeSpan.setAttribute('data-deleted-text', textToStrike);
            strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'USER');
            strikeSpan.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
            strikeSpan.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
            strikeSpan.contentEditable = 'false';
            
            try {
                range.surroundContents(strikeSpan);
            } catch (ex) {
                const fragment = range.extractContents();
                strikeSpan.appendChild(fragment);
                range.insertNode(strikeSpan);
            }
            
            const newRange = document.createRange();
            newRange.setStartAfter(strikeSpan);
            newRange.collapse(true);
            sel.removeAllRanges();
            sel.addRange(newRange);
            
        } else if (targetNode) {
            // SINGLE CHAR: Create new strikethrough span
            const revId = `rev-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
            const strikeSpan = document.createElement('span');
            strikeSpan.id = revId;
            strikeSpan.className = 'revision-span deletion-span';
            strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
            strikeSpan.setAttribute('data-is-deletion', 'true');
            strikeSpan.setAttribute('data-deleted-text', textToStrike);
            strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'USER');
            strikeSpan.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
            strikeSpan.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
            strikeSpan.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
            strikeSpan.contentEditable = 'false';
            
            if (e.inputType === 'deleteContentBackward') {
                const beforeText = targetNode.textContent.substring(0, targetOffset - 1);
                const afterText = targetNode.textContent.substring(targetOffset);
                
                strikeSpan.textContent = textToStrike;
                const parent = targetNode.parentNode;
                
                if (beforeText) {
                    const beforeNode = document.createTextNode(beforeText);
                    parent.insertBefore(beforeNode, targetNode);
                }
                parent.insertBefore(strikeSpan, targetNode);
                if (afterText) {
                    const afterNode = document.createTextNode(afterText);
                    parent.insertBefore(afterNode, targetNode);
                }
                parent.removeChild(targetNode);
            } else {
                const beforeText = targetNode.textContent.substring(0, targetOffset);
                const afterText = targetNode.textContent.substring(targetOffset + 1);
                
                strikeSpan.textContent = textToStrike;
                const parent = targetNode.parentNode;
                
                if (beforeText) {
                    const beforeNode = document.createTextNode(beforeText);
                    parent.insertBefore(beforeNode, targetNode);
                }
                parent.insertBefore(strikeSpan, targetNode);
                if (afterText) {
                    const afterNode = document.createTextNode(afterText);
                    parent.insertBefore(afterNode, targetNode);
                }
                parent.removeChild(targetNode);
            }
            
            // Position cursor BEFORE the strikethrough span (for backspace continuity)
            const newRange = document.createRange();
            if (e.inputType === 'deleteContentBackward') {
                newRange.setStartBefore(strikeSpan);
            } else {
                newRange.setStartAfter(strikeSpan);
            }
            newRange.collapse(true);
            sel.removeAllRanges();
            sel.addRange(newRange);
        }
        
        // Trigger sidebar refresh
        if (window._sidebarTimer) clearTimeout(window._sidebarTimer);
        window._sidebarTimer = setTimeout(renderSideComments, 300);
        
        window._suppressRevisionPreservation = false;
    }
}
window.handleBeforeInput = handleBeforeInput;


// [REMOVED] Duplicate renderSideComments function that caused conflict
// Original function at line 1968 is the correct one.
        
/* DEAD CODE
        // Delete button only for current user and active revision
        // CURRENT_USER_NAME is defined globally in PHP block
        const canDelete = (typeof CURRENT_USER_NAME !== 'undefined' && user === CURRENT_USER_NAME && !isSuperseded);
        const deleteBtn = canDelete ? `<button onclick="removeComment('${id}')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:16px; line-height:1;" title="Delete">&times;</button>` : '';

        const supersededBadge = isSuperseded ? `<div style="font-size:10px; color:#f59e0b; margin-top:4px;">🚫 Superseded</div>` : '';

        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:28px; height:28px; background:${isSuperseded ? '#e2e8f0' : '#fef2f2'}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:${isSuperseded ? '#64748b' : '#ef4444'}; font-weight:bold;">R</div>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:#334155;">${user}</div>
                        <div style="font-size:10px; color:#94a3b8;">${time}</div>
                        ${supersededBadge}
                    </div>
                </div>
                ${deleteBtn}
            </div>
            <div style="background:${isSuperseded ? '#e2e8f0' : '#f8fafc'}; border:1px solid #f1f5f9; border-radius:8px; padding:10px; font-size:13px; color:${isSuperseded ? '#94a3b8' : '#334155'}; ${isSuperseded ? 'text-decoration:line-through;' : ''}">
                ${text}
            </div>
        `;
        
        card.onclick = (e) => {
            if (e.target.tagName !== 'BUTTON') {
                const target = document.getElementById(id);
                if (target) {
                     if (typeof ensureTabVisible === 'function') ensureTabVisible(target);
                     target.scrollIntoView({behavior: "smooth", block: "center"});
                     target.classList.add('blink-highlight');
                     setTimeout(() => target.classList.remove('blink-highlight'), 2000);
                }
            }
        };

        list.appendChild(card);
        count++;
    });

    if (count > 0) {
        sidebar.style.display = 'block';
    } else {
        sidebar.style.display = 'none';
    }
}
*/

// 2. CREATE SIDEBAR CARD (Helper)
function createSidebarCard(revId, text, user, time, location, spanElement) {
    const list = document.getElementById('comment-list');
    
    // Create wrapper
    const card = document.createElement('div');
    card.className = 'comment-card';
    card.id = `card-${revId}`;
    card.setAttribute('data-for', revId);

    // [FIX V20] GATEKEEPER IN HELPER
    if (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.has(revId)) {
        console.log(`[GATEKEEPER] createSidebarCard blocked for zombie: ${revId}`);
        return; 
    }
    
    // Base Styles
    card.style.marginBottom = '15px';
    card.style.background = 'white';
    card.style.borderRadius = '12px';
    card.style.padding = '16px';
    card.style.boxShadow = 'rgba(0, 0, 0, 0.02) 0px 4px 6px';
    card.style.border = '1px solid rgb(226, 232, 240)';
    card.style.transition = 'all 0.2s ease-in-out';
    
    // Check superseded
    const isSuperseded = spanElement.hasAttribute('data-superseded');
    if (isSuperseded) {
          card.style.opacity = '0.6';
          card.style.pointerEvents = 'none'; // Prevent interaction
          card.style.cursor = 'not-allowed';
          card.style.background = '#f1f5f9';
          card.style.border = '1px dashed #cbd5e1';
    }

    // Determine if Deletable
    const canDelete = (typeof CURRENT_USER_NAME !== 'undefined' && user === CURRENT_USER_NAME && !isSuperseded);
    const deleteBtn = canDelete ? 
        `<button onclick="removeComment('${revId}')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:18px; line-height:1; padding:0 4px;" title="Delete">&times;</button>` : '';

    // [FIX] Check for "Replaces" attribute to restore badge on reload/tab switch
    let replacesBadge = '';
    const replacedText = spanElement.getAttribute('data-replaces-text');
    const replacedUser = spanElement.getAttribute('data-replaces-user');
    const replacedRole = spanElement.getAttribute('data-replaces-role'); // New Attribute
    
    if (replacedText && !isSuperseded) {
         const preview = replacedText.length > 50 ? replacedText.substring(0, 50) + '...' : replacedText;
         
         let byInfo = '';
         if (replacedUser) {
             // Try attribute first, then fallback to global map
             let role = replacedRole;
             if (!role && window.USER_ROLE_MAP && window.USER_ROLE_MAP[replacedUser]) {
                 role = window.USER_ROLE_MAP[replacedUser];
             }
             
             const roleLabel = role ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${role})</span>` : '';
             byInfo = ` <span style="color:#64748b; font-weight:400;">(by ${replacedUser}${roleLabel})</span>`;
         }
         

         
         replacesBadge = `<div onclick="viewRevisionHistory('${revId}')" style="font-size:12px; color:#ef4444; margin-top:4px; font-weight:500; cursor:pointer;" title="Click to view full history">Replaces: "${preview}"${byInfo}</div>`;
    }


    // [FIX] Add Role Badge to Reviewer Name (e.g. "Budi [PIC]")
    let reviewerRole = spanElement.getAttribute('data-comment-role') || '';
    if (!reviewerRole && window.USER_ROLE_MAP && window.USER_ROLE_MAP[user]) {
        reviewerRole = window.USER_ROLE_MAP[user];
    }
    const reviewerRoleLabel = reviewerRole ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${reviewerRole})</span>` : '';

    card.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:32px; height:32px; background:${isSuperseded ? '#e2e8f0' : '#fef2f2'}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:${isSuperseded ? '#64748b' : '#ef4444'}; font-weight:bold;">${user.charAt(0).toUpperCase()}</div>
                <div>
                    <div class="name-label" style="font-size:13px; font-weight:700; color:#334155;">${user}${reviewerRoleLabel}</div>
                    <div style="font-size:11px; color:#94a3b8;">${time}</div>
                    ${isSuperseded ? '<div style="font-size:10px; color:#f59e0b; margin-top:4px;">🚫 Superseded</div>' : ''}
                    ${replacesBadge}
                </div>
            </div>
            ${deleteBtn}
        </div>
        <div class="comment-body" style="background:${isSuperseded ? '#e2e8f0' : '#f8fafc'}; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:${isSuperseded ? '#94a3b8' : '#334155'}; line-height:1.5; ${isSuperseded ? 'text-decoration:line-through;' : ''}">
            ${text}
        </div>
    `;
    
    // Interaction: Click to Scroll
    card.onclick = (e) => {
         // Don't trigger scroll if clicking delete button
         if (e.target.closest('button')) return;

         const target = document.getElementById(revId);
         if(target) {
             // 1. Ensure tab is visible FIRST (Switch Tab)
             // This might trigger a sidebar re-render, so we need a delay for the scroll
             if (typeof ensureTabVisible === 'function') ensureTabVisible(target);
             
             // 2. Scroll & Highlight with Delay (to allow layout update)
             setTimeout(() => {
                 target.scrollIntoView({behavior:'smooth', block:'center'});
                 
                 // Highlight Effect on Target Text
                 target.classList.remove('blink-highlight');
                 void target.offsetWidth; // Trigger reflow
                 target.classList.add('blink-highlight');
                 
                 // Highlight Effect on Card
                 // Check if card still exists (it might have been re-rendered)
                 // If re-rendered, find the NEW card by ID
                 const currentCard = document.getElementById(`card-${revId}`);
                 if (currentCard) {
                     document.querySelectorAll('.comment-card').forEach(c => {
                         c.style.borderColor = '#e2e8f0';
                         c.style.transform = 'scale(1)';
                         c.style.boxShadow = 'rgba(0, 0, 0, 0.02) 0px 4px 6px';
                     });
                     currentCard.style.borderColor = '#ef4444';
                     currentCard.style.transform = 'scale(1.02)';
                     currentCard.style.boxShadow = 'rgba(239, 68, 68, 0.1) 0px 10px 15px -3px';
                 }
             }, 100); 
         }
    };

    list.appendChild(card);
}

function updateDraftCard(revId, text) {
    const list = document.getElementById('comment-list');
    const sidebar = document.getElementById('comment-sidebar');
    if (!list || !sidebar) return;

    // GATEKEEPER: Block zombie/deleted spans
    if (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.has(revId)) {
        console.log(`[GATEKEEPER] updateDraftCard blocked for zombie: ${revId}`);
        return; 
    }
    
    const cleanText = (text || '').replace(/\u200B/g, '').trim();
    if (!cleanText) {
        const existingCard = document.getElementById(`card-${revId}`);
        if (existingCard) existingCard.remove();
        return;
    }
    
    sidebar.style.display = 'block';
    
    let card = document.getElementById(`card-${revId}`);
    if (card) {
        // Update existing card text
        let bodyEl = card.querySelector('.draft-body') || document.getElementById(`text-${revId}`);
        if (!bodyEl) {
            // Fallback: find text div by style
            const divs = card.querySelectorAll('div');
            for (let i = divs.length - 1; i >= 0; i--) {
                if (divs[i].style.lineHeight === '1.4' || divs[i].style.wordWrap === 'break-word') {
                    bodyEl = divs[i];
                    break;
                }
            }
        }
        if (bodyEl) bodyEl.textContent = cleanText;
        card.setAttribute('data-stored-text', cleanText);
        return;
    }
    
    // Create new card (no Save/Cancel buttons — auto-commit on Submit)
    card = document.createElement('div');
    card.id = `card-${revId}`;
    card.className = 'comment-card draft';
    card.style.cssText = 'background:white; border:1px solid #fecaca; border-radius:10px; padding:12px; margin-bottom:10px; box-shadow:0 1px 2px rgba(0,0,0,0.02); cursor:pointer; transition:all 0.2s; border-left:3px solid #ef4444;';
    card.setAttribute('data-stored-text', cleanText);
    card.setAttribute('data-for', revId);
    
    card.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:24px; height:24px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:#ef4444; font-weight:bold; border:1px solid #fecaca; flex-shrink:0;">✎</div>
                <div>
                    <div class="name-label" style="font-size:12px; font-weight:700; color:#334155;">New Revision</div>
                    <div style="font-size:10px; color:#94a3b8;">Draft • ${typeof CURRENT_USER_NAME !== 'undefined' ? CURRENT_USER_NAME : 'Reviewer'}</div>
                </div>
            </div>
            <button class="btn-delete-draft" title="Hapus revisi ini" style="background:none; border:none; cursor:pointer; color:#ef4444; opacity:0.5; padding:2px 4px; font-size:14px; line-height:1; transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
        </div>
        <div class="draft-body comment-body" style="font-size:12px; color:#ef4444; line-height:1.4; word-wrap:break-word; font-weight:500;">${cleanText}</div>
    `;
    
    // DELETE BUTTON: Remove draft span + card + blacklist
    const deleteBtn = card.querySelector('.btn-delete-draft');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Don't trigger card click
            const span = document.getElementById(revId);
            if (span) {
                window._suppressRevisionPreservation = true;
                span.remove();
                window._suppressRevisionPreservation = false;
            }
            card.remove();
            // Blacklist to prevent zombie re-creation
            if (typeof DELETED_SPANS !== 'undefined') DELETED_SPANS.add(revId);
            // Refresh sidebar and badges
            renderSideComments();
            if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
        });
    }
    
    // CLICK-TO-SCROLL: Navigate to the revision span in editor
    card.addEventListener('click', (e) => {
        if (e.target.closest('.btn-delete-draft')) return; // Skip if delete btn
        const span = document.getElementById(revId);
        if (span) {
            // Switch to correct tab first (for multi-tab editors)
            if (typeof ensureTabVisible === 'function') ensureTabVisible(span);
            
            setTimeout(() => {
                span.scrollIntoView({behavior: 'smooth', block: 'center'});
                span.classList.remove('blink-highlight');
                void span.offsetWidth;
                span.classList.add('blink-highlight');
                setTimeout(() => span.classList.remove('blink-highlight'), 1500);
                
                // Highlight this card
                document.querySelectorAll('.comment-card').forEach(c => {
                    c.style.borderColor = '';
                    c.style.transform = 'scale(1)';
                });
                const currentCard = document.getElementById(`card-${revId}`);
                if (currentCard) {
                    currentCard.style.borderColor = '#ef4444';
                    currentCard.style.transform = 'scale(1.02)';
                }
            }, 100);
        }
    });
    
    list.prepend(card);
    if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
}

// ========================================
// SIMPLE REVISION VERSIONING
// ========================================

// Get revision location from span (use row + parent cell + TAB to avoid conflicts)
function getRevisionLocation(span) {
    if (!span) return null;
    
    // Try to get row/cell context from DOM position
    let parent = span;
    let row = null;
    let cell = null;
    let rowNumber = 'general';
    let cellIndex = 0;
    
    // Find parent table cell first
    while (parent && parent.tagName !== 'TD') {
        parent = parent.parentElement;
        if (!parent || parent === document.body) break;
    }
    
    if (parent && parent.tagName === 'TD') {
        cell = parent;
        row = cell.parentElement;
        
        if (row && row.tagName === 'TR') {
            // Get row number from SECOND cell (Process | Row | Node Name)
            const rowCell = row.querySelector('td:nth-child(2)');
            if (rowCell) rowNumber = rowCell.innerText.trim() || 'general';
            
            // Get column index of current cell
            const cells = Array.from(row.cells);
            cellIndex = cells.indexOf(cell);
        }
    }
    
    // [FIX] Get active tab/sheet to differentiate between Email, SMS, etc.
    let activeTab = 'default';
    
    // [FIX - PRIMARY] Derive tab from span's OWN editor (most reliable)
    // This ensures location is consistent regardless of button .active state
    const spanEditor = span.closest('.free-input-editor, .review-editor');
    if (spanEditor && spanEditor.getAttribute('data-media')) {
        activeTab = spanEditor.getAttribute('data-media');
        console.log(`[Location] Tab from editor data-media: "${activeTab}"`);
    } else if (spanEditor) {
        // Fallback: use the parent pane's data-media or ID
        const pane = spanEditor.closest('.sheet-pane, .media-pane, .review-tab-content');
        if (pane) {
            activeTab = pane.getAttribute('data-media') || pane.id || 'default';
            console.log(`[Location] Tab from pane: "${activeTab}"`);
        }
    }
    
    // [FALLBACK] If span-based detection fails, use active button
    if (activeTab === 'default') {
        const activeSheetBtn = document.querySelector('.btn-media-tab.active');
        if (activeSheetBtn) {
            activeTab = activeSheetBtn.textContent.trim();
            console.log(`[Location] Tab from active button: "${activeTab}"`);
        } else {
            // Last resort: Check which editor/pane is visible
            const editors = document.querySelectorAll('[contenteditable="true"]');
            editors.forEach((editor) => {
                if (editor.offsetParent !== null) {
                    const pane = editor.closest('.sheet-pane, .tab-pane, .media-pane');
                    if (pane && pane.id) {
                        activeTab = pane.id;
                    }
                }
            });
        }
    }
    
    // Use tab + row + column index (full unique location)
    // [FIX] For Free Input (no table cells), add character offset to make locations unique
    // Without this, every span in the same editor gets "general-col0" → false overrides
    let charOffset = '';
    if (rowNumber === 'general' && cellIndex === 0) {
        // Calculate approximate character offset within the editor
        let offset = 0;
        const editor = span.closest('[contenteditable="true"]');
        if (editor) {
            const walker = document.createTreeWalker(editor, NodeFilter.SHOW_TEXT, null, false);
            let node;
            while (node = walker.nextNode()) {
                if (span.contains(node) || span === node.parentNode) break;
                offset += (node.textContent || '').length;
            }
        }
        charOffset = `-pos${offset}`;
    }
    const fullLocation = `${activeTab}:${rowNumber}-col${cellIndex}${charOffset}`;
    console.log(`[Location Detection] Tab: "${activeTab}", Row: "${rowNumber}", Col: ${cellIndex}, Full: "${fullLocation}"`);
    return fullLocation;
}

// Get all existing revisions at same location (search both visible and hidden)
function getRevisionsAtLocation(location) {
    if (!location) return [];
    
    //  Search in visible DOM (editor)
    const visibleRevisions = document.querySelectorAll('[data-comment-user][data-revision-location]');
    const visibleFiltered = Array.from(visibleRevisions).filter(span => {
        return span.getAttribute('data-revision-location') === location;
    });
    
    // Search in hidden container (deleted/removed spans)
    const hiddenContainer = document.getElementById('deleted-revisions-container');
    let hiddenFiltered = [];
    if (hiddenContainer) {
        const hiddenRevisions = hiddenContainer.querySelectorAll('[data-revision-location]');
        hiddenFiltered = Array.from(hiddenRevisions).filter(span => {
            return span.getAttribute('data-revision-location') === location;
        });
    }
    
    // Combine both visible and hidden
    return [...visibleFiltered, ...hiddenFiltered];
}

// Calculate next version number for location
function calculateVersionNumber(location) {
    const existing = getRevisionsAtLocation(location);
    return existing.length + 1;
}

// Show override confirmation dialog
function showOverrideConfirmation(revId, existingRevisions, onConfirm, onCancel) {
    const latestRev = existingRevisions[existingRevisions.length - 1];
    const author = latestRev.getAttribute('data-comment-user') || 'Unknown';
    const text = latestRev.getAttribute('data-comment-text') || latestRev.innerText;
    const nextVersion = existingRevisions.length + 1;
    
    Swal.fire({
        title: 'Override Existing Revision?',
        html: `
            <div style="text-align:left; padding:12px; background:#f8fafc; border-radius:8px;">
                <p style="margin-bottom:8px; color:#64748b;"><strong>${author}</strong> already has a revision here:</p>
                <div style="background:white; padding:12px; border-left:3px solid #ef4444; border-radius:4px; margin:8px 0;">
                    <div style="font-size:13px; color:#1e293b;">"${text}"</div>
                </div>
                <p style="margin-top:12px; color:#475569;">Replace with your revision?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✓ Replace',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        customClass: {
            popup: 'swal-wide',
            confirmButton: 'swal-confirm-btn',
            cancelButton: 'swal-cancel-btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        } else if (onCancel) {
            onCancel();
        }
    });
}

function commitRevision(revId) {
    const span = document.getElementById(revId);
    const card = document.getElementById(`card-${revId}`);
    if (span && card) {
        // [FORK] If this is an existing committed revision being edited, ARCHIVE the old state first
        if (span.getAttribute('data-comment-text') && span.getAttribute('data-revision-version')) {
            const oldVerText = span.getAttribute('data-comment-text');
            const currentText = span.innerText;
            
            // Only fork if text has actually changed
            if (oldVerText.trim() !== currentText.trim()) {
                console.log(`[FORK] Detected edit to committed revision ${revId}. Archiving v${span.getAttribute('data-revision-version')}...`);
                
                // 1. Create Archive Clone
                const archiveSpan = span.cloneNode(true);
                archiveSpan.id = `archived-${revId}-${Date.now()}`; // New Unique ID
                archiveSpan.innerText = oldVerText; // Revert text to old version
                archiveSpan.setAttribute('data-is-archived', 'true');
                archiveSpan.classList.remove('revision-span'); // Remove class to avoid UI confusion? Keep for selector finding.
                
                // 2. Move to Hidden Container
                let hiddenContainer = document.getElementById('deleted-revisions-container');
                if (!hiddenContainer) {
                    hiddenContainer = document.createElement('div');
                    hiddenContainer.id = 'deleted-revisions-container';
                    hiddenContainer.style.display = 'none';
                    document.body.appendChild(hiddenContainer);
                }
                hiddenContainer.appendChild(archiveSpan);
                console.log(`[FORK] Archived old version to ${archiveSpan.id}`);
                
                // 3. Reset Current Span metadata to treat it as "New" (but keep ID)
                // Actually, commitRevision logic below will handle versioning if it finds the archived span in getRevisionsAtLocation
            }
        }

        // [VERSIONING] Get location and check for existing revisions
        // [FIX] If span already has a saved location (from SPV/previous commit),
        // search using THAT location first. This ensures we find SPV's old revision
        // even if the location format has changed between sessions.
        const newLocation = getRevisionLocation(span);
        const oldLocation = span.getAttribute('data-revision-location');
        const searchLocation = oldLocation || newLocation;
        const location = newLocation; // New location to save
        let existingRevs = getRevisionsAtLocation(searchLocation);
        // [FIX] If old location didn't find anything, try new location as fallback
        if (existingRevs.length === 0 && oldLocation && oldLocation !== newLocation) {
            existingRevs = getRevisionsAtLocation(newLocation);
        }
        
        // Check if user has already confirmed override for this draft
        const confirmedOverride = span.getAttribute('data-confirmed-override');
        
        // If there are existing revisions and user hasn't confirmed yet, show dialog
        if (existingRevs.length > 0 && !confirmedOverride) {
            showOverrideConfirmation(revId, existingRevs, () => {
                // User confirmed override
                span.setAttribute('data-confirmed-override', 'true');
                commitRevision(revId); // Re-call with confirmation flag set
            }, () => {
                // User cancelled - keep as draft
                console.log('Override cancelled by user');
            });
            return; // Wait for user decision
        }
        
        // [VERSIONING] Calculate version number (use existingRevs we already found)
        const versionNumber = existingRevs.length + 1;
        
        // Mark old revisions as superseded if this is an override
        let oldText = '';
        let oldUser = '';
        let oldRole = ''; // New
        if (existingRevs.length > 0) {
            // Get text from latest revision being superseded
            const latestOldRev = existingRevs[existingRevs.length - 1];
            oldText = latestOldRev.getAttribute('data-comment-text') || latestOldRev.innerText || '';
            oldUser = latestOldRev.getAttribute('data-comment-user') || 'Unknown';
            oldRole = latestOldRev.getAttribute('data-comment-role') || ''; // Assuming we store role too
            
            // [FIX] Fallback lookup if role missing in attribute (for old revisions)
            if (!oldRole && window.USER_ROLE_MAP && window.USER_ROLE_MAP[oldUser]) {
                oldRole = window.USER_ROLE_MAP[oldUser];
                console.log(`[DEBUG] Role for ${oldUser} found in DB Map: ${oldRole}`);
            }
            
            existingRevs.forEach(oldSpan => {
                oldSpan.setAttribute('data-superseded', 'true');
                oldSpan.setAttribute('data-superseded-by', CURRENT_USER_NAME);
                
                // [FIX] Update old card UI to show superseded status
                // Use data-for selector instead of id (cards don't have id attribute)
                const oldCard = document.querySelector(`.comment-card[data-for="${oldSpan.id}"]`);
                if (oldCard) {
                    // Update card styling - grayed out and disabled
                    oldCard.style.opacity = '0.6';
                    oldCard.style.border = '1px dashed #cbd5e1';
                    oldCard.style.pointerEvents = 'none'; // Disable clicking
                    oldCard.style.cursor = 'not-allowed';
                    
                    // Add strikethrough to the text content
                    // Text div has background:#f8fafc, not id attribute
                    const textDiv = oldCard.querySelector('div[style*="background:#f8fafc"]');
                    if (textDiv) {
                        textDiv.style.textDecoration = 'line-through';
                        textDiv.style.color = '#94a3b8'; // Gray out text
                    }
                    
                    // Add superseded badge if not already present
                    const userDiv = oldCard.querySelector('[style*="font-weight:700"]');
                    if (userDiv && !userDiv.parentElement.innerHTML.includes('Superseded')) {
                        userDiv.insertAdjacentHTML('afterend', 
                            `<div style="font-size:10px; color:#f59e0b; margin-top:4px;">🚫 Superseded by ${CURRENT_USER_NAME}</div>`
                        );
                    }
                }
            });
        }
        
        span.classList.remove('draft');
        card.classList.remove('draft');
        card.style.border = '1px solid #e2e8f0';
        
        let text = card.getAttribute('data-stored-text') || span.innerText || "";
        const timeStr = new Date().toLocaleTimeString('id-ID', { day:'numeric', month:'short', hour: '2-digit', minute:'2-digit' }).replace('.', ':');
        
        span.setAttribute('data-comment-text', text);
        span.setAttribute('data-comment-user', CURRENT_USER_NAME);
        span.setAttribute('data-comment-dept', CURRENT_USER_ROLE);
        span.setAttribute('data-comment-job', CURRENT_USER_JOB_FUNCTION);
        span.setAttribute('data-comment-time', timeStr);
        span.setAttribute('data-revision-location', location);
        span.setAttribute('data-revision-version', versionNumber);
        // [FIX] Store current user's role for future reference
        const currentRole = window.CURRENT_USER_ROLE || 'USER';
        span.setAttribute('data-comment-role', currentRole);
        
        // [FIX] Ensure variable is declared BEFORE usage
        const isContentIdentical = (oldText && text && oldText.trim() === text.trim());
        
        // [FIX] Persist replaced text for sidebar reconstruction
        if (versionNumber > 1 && oldText && !isContentIdentical) {
            span.setAttribute('data-replaces-text', oldText);
            if(oldUser) span.setAttribute('data-replaces-user', oldUser);
            if(oldRole) span.setAttribute('data-replaces-role', oldRole);
        }
        
        // Version badge - hidden from UI, only tracked in backend
        const versionBadge = ''; // Version tracking kept in data-revision-version attribute only
        
        // Override badge with old text preview
        let overrideBadge = '';
        
        if (versionNumber > 1 && oldText && !isContentIdentical) {
            const oldTextPreview = oldText.length > 50 ? oldText.substring(0, 50) + '...' : oldText;
            
            let byInfo = '';
            if (oldUser) {
                 const roleLabel = oldRole ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${oldRole})</span>` : '';
                 byInfo = ` <span style="color:#64748b; font-weight:400;">(by ${oldUser}${roleLabel})</span>`;
            }
            
            overrideBadge = `<div onclick="viewRevisionHistory('${revId}')" style="font-size:12px; color:#ef4444; margin-top:4px; font-weight:500; cursor:pointer;" title="Click to view full history">Replaces: "${oldTextPreview}"${byInfo}</div>`;
        } else if (versionNumber > 1 && !isContentIdentical) {
            overrideBadge = `<div style="font-size:10px; color:#10b981; margin-top:4px;">📝 Overrides v${versionNumber - 1}</div>`;
        }
        
        // [FIX] Add Role Badge to Current Reviewer Name
        const currentRoleLabel = currentRole ? ` <span style="font-size:11px; color:#64748b; font-weight:400; font-style:italic;">(${currentRole})</span>` : '';
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#ef4444; font-weight:bold;">R</div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#dc2626;">Revision (Saved)${versionBadge}${currentRoleLabel}</div>
                        <div style="font-size:11px; color:#94a3b8;">${timeStr}</div>
                        ${overrideBadge}
                    </div>
                </div>
                <button onclick="removeComment('${revId}')" style="background:none; border:none; color:#94a3b8;" title="Delete">x</button>
            </div>
            <div id="text-${revId}" style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155;">${text}</div>
        `;
        
        card.onclick = () => {
             // 1. ROBUST RE-FETCH
             const targetSpan = document.getElementById(revId) || span;

             // 2. Cross-Tab Navigation
             ensureTabVisible(targetSpan);

             // 3. Scroll Editor to Span with Timeout for Tab Switch Effect
             setTimeout(() => {
                 // Final re-fetch for scrolling
                 const finalSpan = document.getElementById(revId) || targetSpan;

                 if (finalSpan) {
                     finalSpan.scrollIntoView({behavior: "smooth", block: "center"});
                     finalSpan.classList.remove('blink-highlight');
                     void finalSpan.offsetWidth;
                     finalSpan.classList.add('blink-highlight');
                 }
                 
                 // Highlight Card
                  document.querySelectorAll('.comment-card').forEach(x => {
                      x.style.borderColor = '#e2e8f0';
                      x.style.backgroundColor = 'white';
                      x.style.transform = 'scale(1)';
                  });
                  card.style.borderColor = '#ef4444';
                  card.style.backgroundColor = '#fef2f2';
                  card.style.transform = 'scale(1.02)';
             }, 150);
        };
        hasUnsavedChanges = true;
        if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
    }
}


// Custom Alert Helper
function showCustomAlert(title, message) {
    // Remove existing
    const existing = document.getElementById('custom-alert-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'custom-alert-overlay';
    overlay.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(2px); animation: fadeIn 0.2s;
    `;
    
    overlay.innerHTML = `
        <div style="background:white; padding:25px; border-radius:12px; width:400px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2); text-align:center; transform:scale(0.9); animation:popIn 0.3s forwards;">
            <div style="width:50px; height:50px; background:#fef2f2; border-radius:50%; color:#dc2626; display:flex; align-items:center; justify-content:center; margin:0 auto 15px auto;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <h3 style="margin:0 0 10px 0; color:#1e293b; font-size:18px;">${title}</h3>
            <p style="margin:0 0 20px 0; color:#64748b; font-size:14px; line-height:1.5;">${message}</p>
            <button onclick="document.getElementById('custom-alert-overlay').remove()" style="background:#dc2626; color:white; border:none; padding:10px 24px; border-radius:6px; font-weight:600; cursor:pointer; width:100%; transition:background 0.2s;">Mengerti</button>
        </div>
        <style>
            @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
            @keyframes popIn { from { transform:scale(0.9); opacity:0; } to { transform:scale(1); opacity:1; } }
        </style>
    `;
    
    document.body.appendChild(overlay);
}

// [FIX] MISSING FUNCTION REMOVE COMMENT
function removeComment(id) {
    // 1. Find the element (Span)
    const span = document.getElementById(id);
    if (!span) {
        // Only remove card if span is gone (cleanup)
        const card = document.getElementById(`card-${id}`) || document.querySelector(`.comment-card[data-for="${id}"]`);
        if(card) card.remove();
        renderSideComments();
        return; 
    }

    // 2. Determine Action based on type
    const isRevision = span.classList.contains('revision-span');
    
    // 3. Remove from DOM
    // For revisions: Remove the span and its content? Or just unwrap?
    // "Delete Comment" usually means "Cancel Revision". 
    // IF it was a REPLACEMENT (has data-replaced-text), we should ideally RESTORE the old text.
    // But for MVP, let's just remove the span node entirely (delete the new text).
    
    // [FIX] Track deletion
    if (span.id) DELETED_SPANS.add(span.id);
    
    // [FIX] Aggressive Deletion V3 + Sibling Cleanup V5
    // 1. Find ALL occurrences (in case of duplicates)
    const allSpans = document.querySelectorAll(`[id="${id}"]`);
    console.log(`[DELETE] Found ${allSpans.length} spans for ID ${id}`);
    
    // [FIX V5] Get Stored Text from Card for Sibling Cleanup
    let storedText = '';
    const sidebarCard = document.getElementById(`card-${id}`) || document.querySelector(`.comment-card[data-for="${id}"]`);
    if (sidebarCard) {
        storedText = sidebarCard.getAttribute('data-stored-text') || '';
        storedText = storedText.trim();
    }

    // [FIX V6] Radioactive Cleanup
    // Helper to normalize text (strip ZWSP and trim)
    const normalize = (str) => {
        return str ? str.replace(/\u200B/g, '').trim() : '';
    };
    
    const cleanStoredText = normalize(storedText);

    // [FIX V7] The Exorcist - Blacklist this ID
    if (span.id) {
        DELETED_SPANS.add(span.id);
        BLACKLIST_IDS.add(span.id); // Add to permanent blacklist
    }

    allSpans.forEach(s => {
    // [FIX V9] The Surgeon (Grandparent Scope + Substring Replacement)
    // User Insight: "Text merges with HTML".
    // We scan Grandparent, but instead of deleting nodes, we REPLACE text content.
    
    if (storedText && s.parentNode && s.parentNode.parentNode) {
        const grandParent = s.parentNode.parentNode;
        
        // Safety: Limit scope
        if (grandParent.textContent.length < 3000) { 
             const walker = document.createTreeWalker(grandParent, NodeFilter.SHOW_TEXT, null, false);
             let node;
             
             // Use RAW storedText (no trim) if possible to match exact spacing, 
             // but fallback to trim if raw fails? 
             // Let's use storedText as is from the card attribute.
             // If card attribute was trimmed by logic, we might need to be careful.
             // But usually attributs preserve spaces.
             
             // Escape special regex chars if we were using regex, but for simple string replace it's fine.
             
             while(node = walker.nextNode()) {
                 const text = node.textContent;
                 // Check if text contains the stored text
                 if (text.includes(storedText) && storedText.length > 2) {
                     // VALIDATION: Ensure this node is NOT inside another Valid Active Span
                     let parent = node.parentNode;
                     let isProtected = false;
                     while(parent && parent !== grandParent) {
                         if (parent.classList && parent.classList.contains('revision-span') && parent.id && !BLACKLIST_IDS.has(parent.id)) {
                             isProtected = true; 
                             break;
                         }
                         parent = parent.parentNode;
                     }
                     
                     if (!isProtected) {
                         console.log("[DELETE V9] Surgeon: Cutting out text:", storedText);
                         // SURGICAL REMOVAL
                         node.textContent = text.replace(storedText, '');
                     }
                 }
                 // Fallback: Try trimmed version if exact match fails
                 else if (text.includes(cleanStoredText) && cleanStoredText.length > 2) {
                      // Same validation
                      let parent = node.parentNode;
                      let isProtected = false;
                      while(parent && parent !== grandParent) {
                         if (parent.classList && parent.classList.contains('revision-span') && parent.id && !BLACKLIST_IDS.has(parent.id)) {
                             isProtected = true; 
                             break;
                         }
                         parent = parent.parentNode;
                     }
                     if (!isProtected) {
                         console.log("[DELETE V9] Surgeon: Cutting out trimmed text:", cleanStoredText);
                         node.textContent = text.replace(cleanStoredText, '');
                     }
                 }
             }
        }
    }

    // [FIX V6] Expand Scan Radius (Check next 3 siblings) -> Keeping this as fallback
    let sibling = s.nextSibling;
        let attempts = 0;
        
        while(sibling && attempts < 3) {
            if (sibling.nodeType === 3) {
                const cleanSibling = normalize(sibling.textContent);
                // Fuzzy match with normalization
                if (cleanSibling.length > 0 && cleanStoredText.length > 0 && 
                   (cleanSibling === cleanStoredText || cleanSibling.startsWith(cleanStoredText) || cleanStoredText.startsWith(cleanSibling))) {
                    console.log("[DELETE V6] Removed Zombie Sibling Text:", cleanSibling);
                    const toRemove = sibling;
                    sibling = sibling.nextSibling; // Move to next before removing
                    toRemove.remove();
                    continue; // Check next just in case
                }
            } else if (sibling.nodeType === 1 && sibling.tagName === 'BR') {
                 // Skip BRs
            }
            sibling = sibling.nextSibling;
            attempts++;
        }

        // 2. FORCE KILL (V10 Aggressive)
        // If the span is still in the DOM after surgery (or if surgery was skipped),
        // we MUST remove it to satisfy "Delete means Delete".
        if (s.parentNode) {
            console.log("[DELETE V10] Force Kill Span:", s.id);
            s.remove(); 
        }
    });
    
    // 4. Remove Sidebar Card
    const card = document.getElementById(`card-${id}`) || document.querySelector(`.comment-card[data-for="${id}"]`);
    if (card) card.remove();
    
    // 5. Update State
    hasUnsavedChanges = true;
    
    // 6. Refresh Sidebar & Badges
    // Small delay to ensure DOM is updated
    setTimeout(() => {
        renderSideComments(); 
        if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
        
        // Update History
        if (typeof getActiveEditor === 'function' && typeof historyMgr !== 'undefined') {
            const activeEd = getActiveEditor();
            if (activeEd) historyMgr.captureDebounced(activeEd.id, activeEd.innerHTML);
        }

        // [FIX] User Feedback: Remind to Save
        // Since we don't auto-save deletes (risky), we must tell user.
        if (typeof showCustomAlert === 'function') {
            // Use a non-blocking toast if available, or just a console log?
            // Alert is too intrusive for every delete. 
            // Better: Just relying on "Unsaved Changes" warning on unload.
            // But user said "Refresh ada lagi" -> They might ignore warning.
            
            // Let's TRY to Auto-Save depending on mode?
            // No, auto-save might be dangerous if they delete by mistake.
            
            // Let's ADD a visual cue.
            const saveBtn = document.querySelector('button[onclick="saveDraft()"]');
            if(saveBtn) {
                saveBtn.classList.add('blink-highlight');
                setTimeout(() => saveBtn.classList.remove('blink-highlight'), 2000);
                 saveBtn.innerText = 'Save Draft (Changes Pending)';
            }
            
            // Show small toast
            const toast = document.createElement('div');
            toast.innerText = 'Deleted! Don\'t forget to SAVE.';
            toast.style.cssText = 'position:fixed; bottom:20px; right:20px; background:#334155; color:white; padding:10px 20px; border-radius:8px; z-index:9999; animation:fadeIn 0.3s;';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }
    }, 50);
}

// PRINT FUNCTION FOR FINAL TICKET SCRIPT
// EXPOSE TO GLOBAL SCOPE

// --- RESTORED PRINT FUNCTION ---

function printTicketScript() {
    console.log('[DEBUG] printTicketScript Called');

    // Helper to format date (handles MSSQL DateTime objects, strings, and Date objects)
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            let d;
            // MSSQL DateTime objects are json_encoded as {date:"...", timezone_type:3, timezone:"..."}
            if (typeof dateStr === 'object' && dateStr.date) {
                d = new Date(dateStr.date.replace(' ', 'T')); // "2026-02-08 13:28:00.000000" → ISO
            } else if (typeof dateStr === 'string') {
                // Try ISO format first, then replace spaces for compatibility
                d = new Date(dateStr.replace(' ', 'T'));
            } else {
                d = new Date(dateStr);
            }
            if (isNaN(d.getTime())) return dateStr.date || dateStr || '-'; // Fallback to raw string
            return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' });
        } catch(e) { return (typeof dateStr === 'object' && dateStr.date) ? dateStr.date : (dateStr || '-'); }
    };

    // 1. Populate Header Info
    if (typeof requestData !== 'undefined') {
        document.getElementById('p-ticket-id').textContent = requestData.ticket_id ? 'SC-' + String(requestData.ticket_id).padStart(4, '0') : '-';
        document.getElementById('p-script-id').textContent = requestData.script_number || '-';
        document.getElementById('p-product').textContent = requestData.produk || '-';
        document.getElementById('p-title').textContent = requestData.purpose || requestData.title || '-';
        document.getElementById('p-jenis').textContent = requestData.jenis || '-';
        document.getElementById('p-kategori').textContent = requestData.kategori || '-';
        document.getElementById('p-media').textContent = requestData.media || '-';
        document.getElementById('p-created').textContent = formatDate(requestData.created_at);
        document.getElementById('p-generated-date').textContent = new Date().toLocaleString('id-ID');
    }

    // 2. Populate Content
    const container = document.getElementById('p-content-container');
    if (container) {
        container.innerHTML = ''; // Clear previous

        // Check Mode
        const isFileUpload = (requestData && requestData.mode === 'FILE_UPLOAD');
        
                if (isFileUpload) {
            // [REQUEST] Use Script Number as Filename
            let filename = 'Attachment';
            // Use Script Number or Ticket ID
            if (requestData && (requestData.script_number || requestData.ticket_id)) {
                let baseName = requestData.script_number || ('SC-' + String(requestData.ticket_id).padStart(4, '0'));
                
                // Try to get extension from actual file data if available
                let ext = '.xlsx'; // Default
                if (typeof scriptFileData !== 'undefined' && scriptFileData && scriptFileData.original_filename) {
                     const parts = scriptFileData.original_filename.split('.');
                     if (parts.length > 1) ext = '.' + parts.pop();
                }
                
                filename = baseName + ext;
            } else if (typeof scriptFileData !== 'undefined' && scriptFileData && scriptFileData.original_filename) {
                // Fallback to original filename if no script number
                filename = scriptFileData.original_filename;
            } else {
                 // Fallback to DOM
                 const fileLink = document.querySelector('.file-link');
                 if (fileLink) filename = fileLink.textContent.trim();
            }
            
            container.innerHTML = `
                <div style="border:1px dashed #64748b; padding:20px; text-align:center; background:#f8fafc; border-radius:8px; margin-top:10px;">
                    <div style="font-style:italic; color:#64748b; margin-bottom:5px; font-size:11px;">File Upload Mode</div>
                    <div style="font-weight:bold; font-size:14px; color:#0f172a;">${filename}</div>
                    <div style="font-size:10px; color:#94a3b8; margin-top:5px;">(Refer to attached file for script content)</div>
                </div>`;
        } else {
            // Free Input: Grab content from the visible editors in the DOM
            const editors = document.querySelectorAll('.review-editor');
            if (editors.length > 0) {
                editors.forEach((editor, idx) => {
                    const mediaName = editor.getAttribute('data-media') || 'Section ' + (idx + 1);
                    
                    // CLEAN CONTENT: Remove styling/spans, just keep text and red color
                    const clone = editor.cloneNode(true);
                    clone.querySelectorAll('.deletion-span').forEach(el => el.remove());
                    
                    let htmlContent = '';
                    
                    // Recursive function to extract text and wrap red spans in <span style="color:red">
                    function processNodePrint(node) {
                        if (node.nodeType === 3) { // Text node
                            const text = node.textContent;
                            htmlContent += text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                        } else if (node.nodeType === 1) { // Element node
                            if (node.tagName.toLowerCase() === 'br') {
                                htmlContent += '<br>';
                            } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                                if (htmlContent !== '' && !htmlContent.endsWith('<br>')) htmlContent += '<br>';
                                node.childNodes.forEach(processNodePrint);
                                htmlContent += '<br>';
                            } else {
                                let isRed = false;
                                if (node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444') {
                                    isRed = true;
                                }
                                
                                if (isRed) htmlContent += '<span style="color:#ef4444; font-weight:bold;">';
                                node.childNodes.forEach(processNodePrint);
                                if (isRed) htmlContent += '</span>';
                            }
                        }
                    }
                    
                    clone.childNodes.forEach(processNodePrint);
                    
                    const section = document.createElement('div');
                    section.style.marginBottom = '20px';
                    section.style.breakInside = 'avoid'; // Prevent splitting inside section if possible
                    
                    const title = document.createElement('div');
                    title.style.fontWeight = 'bold';
                    title.style.borderBottom = '1px solid #000';
                    title.style.marginBottom = '5px';
                    title.style.fontSize = '10px';
                    title.style.textTransform = 'uppercase';
                    title.textContent = mediaName;
                    
                    const body = document.createElement('div');
                    body.style.whiteSpace = 'pre-wrap';
                    body.style.fontSize = '10px';
                    body.style.color = '#000000'; // Default black
                    body.innerHTML = htmlContent; // Use clean HTML to allow the safe red spans
                    
                    section.appendChild(title);
                    section.appendChild(body);
                    container.appendChild(section);
                });
            } else {
                 container.innerHTML = '<div style="font-style:italic;">No visible script content found.</div>';
            }
        }
    }

    // 3. Populate Timeline
    if (typeof requestLogs !== 'undefined' && Array.isArray(requestLogs)) {
        // Reset
        ['maker', 'spv', 'pic', 'proc'].forEach(role => {
            const elName = document.getElementById(`p-${role}-name`);
            if(elName) elName.textContent = '-';
            const elDate = document.getElementById(`p-${role}-date`);
            if(elDate) elDate.textContent = '-';
            const elStatus = document.getElementById(`p-${role}-status`);
            if(elStatus) elStatus.textContent = 'Pending';
        });

        // Maker
        const makerLog = requestLogs.find(l => l.action === 'SUBMIT_REQUEST' || l.action === 'CREATED');
        if (makerLog) {
            document.getElementById('p-maker-name').textContent = makerLog.user_id;
            // document.getElementById('p-maker-role').textContent = makerLog.role; // [REMOVED] Role column gone
            document.getElementById('p-maker-group').textContent = makerLog.group_name || '-';
            document.getElementById('p-maker-date').textContent = formatDate(makerLog.created_at);
        } else if (requestData) {
            document.getElementById('p-maker-name').textContent = requestData.created_by;
            document.getElementById('p-maker-date').textContent = formatDate(requestData.created_at);
        }

        // SPV
        const spvLog = requestLogs.find(l => l.action.includes('SPV') && !l.action.includes('REJECT'));
        if (spvLog) {
            document.getElementById('p-spv-name').textContent = spvLog.user_id;
            // document.getElementById('p-spv-role').textContent = spvLog.role; // [REMOVED] Role column gone
            document.getElementById('p-spv-group').textContent = spvLog.group_name || '-';
            document.getElementById('p-spv-date').textContent = formatDate(spvLog.created_at);
            document.getElementById('p-spv-status').textContent = 'Approved';
        }

        // PIC
        const picLog = requestLogs.find(l => l.action.includes('PIC') && !l.action.includes('REJECT'));
        if (picLog) {
            document.getElementById('p-pic-name').textContent = picLog.user_id;
            // document.getElementById('p-pic-role').textContent = picLog.role; // [REMOVED] Role column gone
            document.getElementById('p-pic-group').textContent = picLog.group_name || '-';
            document.getElementById('p-pic-date').textContent = formatDate(picLog.created_at);
            document.getElementById('p-pic-status').textContent = 'Approved';
        }

        // PROC
        const procLog = requestLogs.find(l => l.action.includes('PROCEDURE'));
        if (procLog) {
            document.getElementById('p-proc-name').textContent = procLog.user_id;
            // document.getElementById('p-proc-role').textContent = procLog.role; // [REMOVED] Role column gone
            document.getElementById('p-proc-group').textContent = procLog.group_name || '-';
            document.getElementById('p-proc-date').textContent = formatDate(procLog.created_at);
            document.getElementById('p-proc-status').textContent = 'Finalized';
        }
    }

    // 4. Trigger Print
    let printFilename = "Tiket Final Script";
    if (typeof requestData !== 'undefined') {
        let parts = ["Final Script"];
        if (requestData.produk) parts.push(requestData.produk);
        if (requestData.kategori) parts.push(requestData.kategori);
        if (requestData.media) parts.push(requestData.media);
        
        // Remove illegal filename characters
        printFilename = parts.join('_').replace(/[\/\\?%*:|"<>]/g, '-'); 
    }

    const originalTitle = document.title;
    document.title = printFilename;

    setTimeout(() => {
        window.print();
        // Restore original title after print dialog closes
        setTimeout(() => { document.title = originalTitle; }, 500);
    }, 500); // Small delay to ensure DOM update
}

window.printTicketScript = printTicketScript;

window.downloadReviewExcel = downloadReviewExcel;

// ==========================================
// CENTRALIZED EDIT MODE CONTROL
// ==========================================

function enableEditMode() {
    console.log('[DEBUG] enableEditMode Called');
    
    // Prevent redundant initialization if already active
    if (isEditing && window.hasGlobalEditorListener) {
        console.log('[DEBUG] enableEditMode: Already active, skipping init.');
        return;
    }

    isEditing = true;
    
    // Remove old observer if any
    if (window.revisionObserver) {
        window.revisionObserver.disconnect();
        window.revisionObserver = null;
    }

    // Attach GLOBAL Event Listener for Typing (Auto Red)
    // We attach just once to 'document', allowing handling anywhere inside contenteditable
    if (!window.hasGlobalEditorListener) {
        document.addEventListener('beforeinput', (e) => {
            // Check if function exists and mode is active
            if (isEditing && typeof window.handleBeforeInput === 'function') {
                window.handleBeforeInput(e);
            }
        });
        
        // [FIX] Block Ctrl+X (cut) for non-own content in suggesting mode
        // Safety net for browsers that don't fire beforeinput for cut operations
        document.addEventListener('cut', (e) => {
            if (!isEditing) return;
            const mode = window.procMode || 'suggesting';
            if (mode === 'editing') return; // Legal/CX Update mode allows cut
            
            // Check if target is inside an editor
            const target = e.target;
            const isInsideEditor = target.closest && (target.closest('.review-editor') || target.closest('.media-pane'));
            if (!isInsideEditor) return;
            
            // Allow cut for OWN draft spans
            const sel = window.getSelection();
            if (sel && sel.anchorNode) {
                const cursorSpan = sel.anchorNode.parentElement?.closest?.('.revision-span.draft');
                const spanOwner = cursorSpan ? (cursorSpan.getAttribute('data-comment-user') || cursorSpan.getAttribute('data-user') || '') : '';
                if (cursorSpan && spanOwner === (window.CURRENT_USER_NAME || '')) {
                    return; // Allow cutting own draft text
                }
            }
            
            // Block cut for original/committed content
            e.preventDefault();
            console.log('[AUTO RED] Cut blocked — use Backspace/Delete to create strikethrough tracking');
        });
        
        window.hasGlobalEditorListener = true;
        console.log('[DEBUG] Global Editor Listener Attached');
    }

    // Enable contentEditable on panes
    const panes = document.querySelectorAll('.media-pane');
    console.log(`[DEBUG] Found ${panes.length} media panes`);
    
    if (panes.length > 0) {
        // Multi-Sheet Mode
        panes.forEach(pane => {
            pane.contentEditable = "true";
            pane.style.pointerEvents = 'auto'; 
            pane.style.outline = "none"; 
        });
        // Lock container
        const editor = document.getElementById('unified-file-editor');
        if (editor) editor.contentEditable = "false"; 
    } else {
        // Single Sheet / Legacy Mode OR Free Input
        const editor = document.getElementById('unified-file-editor');
        if(editor) {
            editor.contentEditable = "true";
            editor.style.pointerEvents = 'auto';
            editor.style.opacity = '1';
            editor.style.background = '#fff';
            editor.focus();
        } else {
            // [FIX] Handle Free Input editors
            const freeEditors = document.querySelectorAll('.free-input-editor');
            if (freeEditors.length > 0) {
                console.log(`[DEBUG] Found ${freeEditors.length} Free Input editors`);
                freeEditors.forEach(ed => {
                    ed.contentEditable = "true";
                    ed.style.pointerEvents = 'auto';
                    ed.style.outline = "none";
                });
                // Focus the first visible one
                const visibleFree = Array.from(freeEditors).find(ed => {
                    const parent = ed.closest('.review-tab-content');
                    return parent && parent.style.display !== 'none';
                });
                if (visibleFree) visibleFree.focus();
            } else {
                console.error('[ERROR] No editor container found!');
            }
        }
    }
    
    // Toolbar UI Update
    const ec = document.getElementById('edit-controls');
    if(ec) ec.style.display = 'none';
    const ct = document.getElementById('color-tools');
    if(ct) ct.style.display = 'flex';
    
    // [REMOVED] Annoying Toast Message
    // Just silent activation is enough
}
window.enableEditMode = enableEditMode;

function disableEditMode() {
    isEditing = false;
    console.log('[DEBUG] disableEditMode Called');
    
    const panes = document.querySelectorAll('.media-pane');
    if (panes.length > 0) {
        panes.forEach(pane => {
            pane.contentEditable = "false";
        });
    }

    const editor = document.getElementById('unified-file-editor');
    if(editor) {
        editor.contentEditable = "false";
        if (panes.length === 0) {
             editor.style.pointerEvents = 'none'; // Lock for legacy
             editor.style.opacity = '0.9';
             editor.style.background = '#f9f9f9';
        } else {
             editor.style.pointerEvents = 'auto'; // Keep buttons clickable
             editor.style.opacity = '1';
             editor.style.background = 'transparent';
        }
    }
    
    // Toolbar UI Update
    const ec = document.getElementById('edit-controls');
    if(ec) ec.style.display = 'block';
    const ct = document.getElementById('color-tools');
    if(ct) ct.style.display = 'none';
}
window.disableEditMode = disableEditMode;

// [FIX] cancelRevision — Undo a DRAFT revision (remove span + card)
function cancelRevision(revId) {
    console.log('[CANCEL] Cancelling draft revision:', revId);
    // Delegate to the robust removeComment (V9 Surgeon Logic)
    removeComment(revId);
}
window.cancelRevision = cancelRevision;

// --- RESTORED ADD COMMENT (MISSING) ---
// [REMOVED REDUNDANT ADD COMMENT FUNCTION]
// The correct version is defined above around line 1452

</script>

<?php require_once 'app/views/layouts/sidebar.php'; ?>
<?php require_once 'app/views/layouts/footer.php'; ?>

<script>
// [NEW] View Full Revision History Detail
function viewRevisionHistory(revId) {
    const span = document.getElementById(revId);
    if (!span) return;
    
    // Get replaced data from attributes
    const oldText = span.getAttribute('data-replaces-text');
    const oldUser = span.getAttribute('data-replaces-user');
    const oldRole = span.getAttribute('data-replaces-role');
    
    if (!oldText) return;
    
    // Resolve Role Label
    let roleLabel = '';
    if (oldRole) {
        roleLabel = ` <span style="font-size:12px; color:#64748b; font-weight:400; font-style:italic;">(${oldRole})</span>`;
    } else if (oldUser && window.USER_ROLE_MAP && window.USER_ROLE_MAP[oldUser]) {
        // Fallback Lookup
        roleLabel = ` <span style="font-size:12px; color:#64748b; font-weight:400; font-style:italic;">(${window.USER_ROLE_MAP[oldUser]})</span>`;
    }

    Swal.fire({
        title: 'Revision History Detail',
        html: `
            <div style="text-align:left; padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <div style="margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid #e2e8f0;">
                    <span style="font-size:13px; color:#64748b; font-weight:600;">Previous Version by:</span>
                    <div style="font-size:15px; font-weight:700; color:#334155; margin-top:4px;">
                        ${oldUser || 'Unknown User'}${roleLabel}
                    </div>
                </div>
                <div>
                    <span style="font-size:13px; color:#64748b; font-weight:600;">Content:</span>
                    <div style="background:white; padding:12px; border:1px solid #cbd5e1; border-radius:6px; margin-top:6px; font-size:14px; color:#333; white-space:pre-wrap; max-height:300px; overflow-y:auto;">
                        "${oldText}"
                    </div>
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Close',
        confirmButtonColor: '#64748b'
    });
}
window.viewRevisionHistory = viewRevisionHistory;

// --- DUPLICATE ALERT HANDLER ---
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('duplicate_alert')) {
        const ticket = urlParams.get('dup_ticket') || 'Unknown';
        const status = urlParams.get('dup_status') || 'Unknown';
        
        Swal.fire({
            title: 'Sedang Dalam Proses Revisi',
            html: `
                <div style="text-align:left; font-size:14px; line-height:1.6;">
                    Script ini sudah memiliki tiket revisi aktif yang belum selesai.<br><br>
                    <b>Tiket:</b> ${ticket}<br>
                    <b>Status:</b> <span class="badge bg-warning text-dark">${status}</span><br><br>
                    Sistem otomatis mengarahkan Anda ke tiket yang sedang berjalan ini.
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'OK, Mengerti',
            cancelButtonText: 'Lihat di Audit Trail',
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#f59e0b',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                const dupId = urlParams.get('dup_id');
                if (dupId) {
                    window.location.href = '?controller=audit&action=detail&id=' + dupId;
                }
            }
        });
        
        // Clean URL to prevent popup on refresh
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?controller=' + urlParams.get('controller') + '&action=' + urlParams.get('action') + '&id=' + urlParams.get('id');
        window.history.replaceState({path: newUrl}, '', newUrl);
    }
    
    // [RESTORED] Render Side Comments on Load
    if (typeof renderSideComments === 'function') {
        renderSideComments();
    }

    // [DEBUG] Force Enable Edit Mode if not already enabled
    // This ensures 'addComment' and 'handleBeforeInput' work
    console.log("[DEBUG] Current Role:", typeof CURRENT_USER_ROLE !== 'undefined' ? CURRENT_USER_ROLE : 'Undefined');
    
    // Define Global Helper for Debugging
    window.debugRed = function() {
        console.log("isEditing:", isEditing);
        console.log("handleBeforeInput defined:", typeof handleBeforeInput === 'function');
        console.log("procMode:", window.procMode);
        console.log("panes:", document.querySelectorAll('.media-pane').length);
    };

    if (typeof enableEditMode === 'function' && !isEditing) {
        // [FIX] Only enable Auto Red for Reviewers (SPV, PIC, PROC)
        const role = window.CURRENT_USER_ROLE || '';
        if (['SPV', 'PIC', 'PROC', 'PROCEDURE'].includes(role)) {
            console.log(`[DEBUG] Auto-Enabling Edit Mode for Reviewer (${role})`);
            enableEditMode();
        } else {
             console.log(`[DEBUG] Edit Mode SKIPPED for non-reviewer (${role})`);
        }
    }
});
</script>
