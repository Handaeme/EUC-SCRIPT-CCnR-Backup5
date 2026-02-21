<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <style>
    /* CSS for Excel Preview Tabs */
    .sheet-tabs-nav { 
        display: flex; 
        overflow-x: auto; 
        border-bottom: 1px solid #ccc; 
        background: #f1f1f1; 
        scrollbar-width: none; 
        -ms-overflow-style: none;
        /* CRITICAL: Force clickability even inside contenteditable */
        pointer-events: auto !important;
        user-select: none !important;
        -webkit-user-select: none !important;
    }
    .sheet-tabs-nav::-webkit-scrollbar { display: none; }
    
    .btn-sheet { 
        border: 1px solid #ccc; 
        border-bottom: none; 
        background: #e0e0e0; 
        padding: 8px 16px; 
        cursor: pointer !important; 
        font-size: 13px; 
        margin-right: 2px;
        /* CRITICAL: Force clickability */
        pointer-events: auto !important;
        user-select: none !important;
        -webkit-user-select: none !important;
    }
    .btn-sheet.active { background: #fff; font-weight: bold; border-top: 2px solid var(--primary-red); }
    .sheet-pane { padding: 15px; background: #fff; border: 1px solid #ccc; border-top: none; overflow: auto; max-height: 400px; }
    
    .form-group { margin-bottom: 12px; }
    .form-label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 14px; }
    
    /* Plain Text Editor Styles */
    #shared-editor {
        width: 100%; height: 300px; padding: 15px; border: 1px solid #ccc; border-radius: 4px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;
        resize: vertical; outline: none; transition: border-color 0.2s;
        background: #fff; color: #333; box-sizing: border-box;
    }
    #shared-editor:focus { border-color: var(--primary-red); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); }

    /* Inline Comment CSS */
    .inline-comment { background-color: #fef08a; border-bottom: 2px solid #eab308; cursor: pointer; transition: background 0.2s; }
    .inline-comment:hover, .inline-comment.active { background-color: #fde047; }
    .comment-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; border-left: 3px solid transparent; margin-bottom: 10px; }
    .comment-card.active { border-color: #eab308; border-left-color: #eab308; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .comment-header { font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px; }
    .comment-body { color: #334155; line-height: 1.4; }
    /* Inline Comment Style */
    .inline-comment {
        background-color: #fef08a !important;
        cursor: pointer;
        border-bottom: 2px solid #eab308;
        color: inherit !important; /* CRITICAL: Keep text color */
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
    .active-highlight {
        outline: 2px solid #dc2626 !important;
        background-color: #fef08a !important; /* Yellow-200 (Stabilo) */
        color: inherit !important; /* Keep original text color */
        box-shadow: 0 0 15px rgba(220, 38, 38, 0.3);
        z-index: 100;
        position: relative;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    /* Resolved/Disabled Revision Card Styles */
    .comment-card.resolved {
        background: #f1f5f9;
        color: #94a3b8;
        opacity: 0.8;
        border-left-color: #10b981;
        cursor: default;
    }
    .comment-card.resolved .comment-body {
        text-decoration: line-through;
    }
    .comment-card.resolved .comment-header {
        color: #94a3b8;
    }
    
    /* Inline Comment Style (Placed LAST to override revision red) */
    .inline-comment {
        background-color: #fef08a !important;
        cursor: pointer;
        border-bottom: 2px solid #eab308;
        color: #333333 !important; /* Force Black for Highlights */
        transition: background-color 0.3s;
    }
    .inline-comment:hover {
        background-color: #fde047 !important;
    }

    /* Deletion Tracking (Strikethrough Red) */
    .deletion-span {
        color: #ef4444 !important;
        text-decoration: line-through !important;
        text-decoration-color: #ef4444 !important;
        opacity: 0.7;
        cursor: default;
    }
    /* Draft Revision Span (New Typing - Red) */
    .revision-span.draft {
        color: red;
    }
    </style>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 style="color:var(--primary-red); margin:0;">Revise Script Request</h2>
        <?php if(isset($request['has_draft']) && $request['has_draft'] == 1): ?>
            <span style="background:#fef08a; color:#854d0e; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:bold; border:1px solid #fde047; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                DRAFT SAVED
            </span>
        <?php endif; ?>
    </div>
    
    <!-- REJECTION NOTE -->
    <?php if (!empty($rejectionNote)): ?>
    <div style="background:#fff5f5; border:1px solid #feb2b2; color:#c53030; padding:12px; border-radius:8px; margin-bottom:15px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:center; margin-bottom:6px;">
            <span style="font-size:16px; margin-right:8px;">⚠️</span>
            <strong style="font-size:14px;"><?php echo htmlspecialchars($lastRole ?? 'Reviewer'); ?> Revision Note:</strong>
        </div>
        <div style="padding-left:24px; line-height:1.4; font-size:13px;">
            <?php echo nl2br(htmlspecialchars($rejectionNote)); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2-COLUMN LAYOUT CONTAINER -->
    <div class="grid-container" style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">
        
        <!-- LEFT COLUMN: MAIN CONTENT (Form + Editor) -->
        <div class="main-column" style="min-width: 0;">
            
            <!-- 1. REQUEST METADATA CARD -->
            <div class="card" style="background:white; padding:20px; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Request Details</h3>
                
                <input type="hidden" id="request_id" value="<?php echo $request['id']; ?>">
                <input type="hidden" id="script_number" value="<?php echo $request['script_number']; ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- COL 1: Title & Type -->
                    <div>
                        <!-- TUJUAN -->
                        <div style="margin-bottom: 15px;">
                            <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Judul Script / Tujuan</label>
                            <textarea id="title" class="form-control" rows="2" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-family:'Inter', sans-serif; resize:vertical;" placeholder="Jelaskan tujuan..."><?php echo htmlspecialchars($request['title'] ?? ''); ?></textarea>
                        </div>

                        <!-- JENIS -->
                        <div style="margin-bottom: 15px;">
                            <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Jenis</label>
                            <div style="display:flex; gap:15px;">
                                <label style="font-size:13px; color:#334155;"><input type="radio" name="jenis" value="Konvensional" onchange="filterProduk()" <?php echo (strpos($request['jenis'], 'Konvensional')!==false)?'checked':''; ?>> Konvensional</label>
                                <label style="font-size:13px; color:#334155;"><input type="radio" name="jenis" value="Syariah" onchange="filterProduk()" <?php echo (strpos($request['jenis'], 'Syariah')!==false)?'checked':''; ?>> Syariah</label>
                            </div>
                        </div>
                    </div>

                    <!-- COL 2: Product & Category -->
                    <div>
                         <!-- PRODUK -->
                         <?php 
                            $prodVals = array_map('trim', explode(',', (string)$request['produk'])); 
                            $otherProd = '';
                            foreach ($prodVals as $p) {
                                if (strpos($p, 'Others:') !== false) $otherProd = trim(explode(':', $p)[1]);
                            }
                        ?>
                        <div id="produk-container" style="margin-bottom: 15px;">
                            <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Produk</label>
                            
                            <div id="produk-konv" style="display:<?php echo (strpos($request['jenis'], 'Konvensional')!==false)?'block':'none'; ?>; padding:10px; background:#f8fafc; border-left:3px solid var(--primary-red); margin-bottom:8px; border-radius:4px;">
                                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="Kartu Kredit" <?php echo in_array('Kartu Kredit', $prodVals)?'checked':''; ?>> Kartu Kredit</label>
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="Extra Dana" <?php echo in_array('Extra Dana', $prodVals)?'checked':''; ?>> Extra Dana</label>
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="KPR" <?php echo in_array('KPR', $prodVals)?'checked':''; ?>> KPR</label>
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="Others" onchange="toggleInput('prod_konv_other', this.checked)" <?php echo ($otherProd)?'checked':''; ?>> Others</label>
                                </div>
                                <input type="text" id="prod_konv_other" class="form-control" style="display:<?php echo ($otherProd)?'block':'none'; ?>; margin-top:5px; width:100%; padding:6px; border:1px solid #ddd; border-radius:4px; font-size:12px;" placeholder="Other product..." value="<?php echo htmlspecialchars($otherProd); ?>">
                            </div>

                            <div id="produk-syariah" style="display:<?php echo (strpos($request['jenis'], 'Syariah')!==false)?'block':'none'; ?>; padding:10px; background:#f8fafc; border-left:3px solid #16a34a; margin-bottom:8px; border-radius:4px;">
                                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="Kartu Syariah" <?php echo in_array('Kartu Syariah', $prodVals)?'checked':''; ?>> Kartu Syariah</label>
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="Extra Dana iB" <?php echo in_array('Extra Dana iB', $prodVals)?'checked':''; ?>> Extra Dana iB</label>
                                    <label style="font-size:12px;"><input type="checkbox" name="produk" value="KPR iB" <?php echo in_array('KPR iB', $prodVals)?'checked':''; ?>> KPR iB</label>
                                </div>
                            </div>
                        </div>

                        <!-- KATEGORI -->
                        <?php $kats = array_map('trim', explode(',', $request['kategori'])); ?>
                        <div style="margin-bottom: 15px;">
                            <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Kategori</label>
                            <div style="display:flex; gap:10px;">
                                <label style="font-size:12px;"><input type="checkbox" name="kategori" value="Pre Due" <?php echo in_array('Pre Due', $kats)?'checked':''; ?>> Pre Due</label>
                                <label style="font-size:12px;"><input type="checkbox" name="kategori" value="Past Due" <?php echo in_array('Past Due', $kats)?'checked':''; ?>> Past Due</label>
                                <label style="font-size:12px;"><input type="checkbox" name="kategori" value="Program Offer" <?php echo in_array('Program Offer', $kats)?'checked':''; ?>> Program Offer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MEDIA (Full Row) -->
                 <?php $medVals = array_map('trim', explode(',', (string)$request['media'])); ?>
                <div style="margin-top: 5px;">
                    <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Media</label>
                    <div class="checkbox-group" id="media-list" style="display:flex; flex-wrap:wrap; gap:15px;">
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="WhatsApp" onchange="updateFreeInputTabs()" <?php echo in_array('WhatsApp', $medVals)?'checked':''; ?>> WhatsApp</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="SMS" onchange="updateFreeInputTabs()" <?php echo in_array('SMS', $medVals)?'checked':''; ?>> SMS</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="Email" onchange="updateFreeInputTabs()" <?php echo in_array('Email', $medVals)?'checked':''; ?>> Email</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="Robocoll" onchange="updateFreeInputTabs()" <?php echo in_array('Robocoll', $medVals)?'checked':''; ?>> Robocoll</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="Surat" onchange="updateFreeInputTabs()" <?php echo in_array('Surat', $medVals)?'checked':''; ?>> Surat</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="VB" onchange="updateFreeInputTabs()" <?php echo in_array('VB', $medVals)?'checked':''; ?>> VB</label>
                        <label style="font-size:12px;"><input type="checkbox" name="media" value="Chatbot" onchange="updateFreeInputTabs()" <?php echo in_array('Chatbot', $medVals)?'checked':''; ?>> Chatbot</label>
                    </div>
                </div>

                <!-- START DATE (Optional) - Hidden for Revisions per Request -->
                <?php 
                $isRevision = in_array($request['status'], ['REVISION', 'MINOR_REVISION', 'MAJOR_REVISION']);
                $displayDate = $isRevision ? 'none' : 'block';
                ?>
                <div style="margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px; display: <?php echo $displayDate; ?>;">
                     <label class="form-label" style="display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#64748b;">Tanggal Efektif (Start Date)</label>
                     <input type="date" id="start_date" class="form-control" style="width:100%; max-width:250px; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;" value="<?php echo htmlspecialchars($request['start_date'] ?? ''); ?>">
                     <p style="font-size:11px; color:#94a3b8; margin-top:4px;">Kosongkan jika berlaku segera. Script hanya akan muncul di Library Search mulai tanggal ini.</p>
                </div>
            </div>

            <!-- 2. EDITOR SECTION -->
            
            <!-- MODE TABS (LOCKED) -->
            <div class="tabs" style="margin-bottom: 15px;">
                <div class="tab-item <?php echo ($request['mode']=='FILE_UPLOAD')?'active':'disabled'; ?>" 
                     style="<?php echo ($request['mode']!='FILE_UPLOAD') ? 'opacity:0.5; cursor:not-allowed; background:#f1f5f9; color:#94a3b8;' : ''; ?>">
                     File Upload
                </div>
                <div class="tab-item <?php echo ($request['mode']!='FILE_UPLOAD')?'active':'disabled'; ?>" 
                     style="<?php echo ($request['mode']=='FILE_UPLOAD') ? 'opacity:0.5; cursor:not-allowed; background:#f1f5f9; color:#94a3b8;' : ''; ?>">
                     Free Input
                </div>
            </div>
            <input type="hidden" id="input_mode" value="<?php echo $request['mode']; ?>">
    
            <!-- EDITOR CONTENT -->
            <div id="upload-panel" style="display:<?php echo ($request['mode']=='FILE_UPLOAD')?'block':'none'; ?>; margin-bottom: 20px;">
                <div class="upload-area" id="drop-zone" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size:40px; margin-bottom:10px;">📄</div>
                    <div style="font-size:14px; color:#555;">Upload New Version (Optional)</div>
                    <div style="font-size:12px; color:#888;">Click to replace current file</div>
                </div>
                <input type="file" id="fileInput" name="file" accept=".xlsx, .xls" style="display:none" onchange="handleFile(this.files)">
                <div id="upload-status" style="margin-top:10px; color:#666; font-style:italic;"></div>
            </div>
    
            <div style="background:white; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 15px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                    <label class="form-label" style="margin-bottom:0;">Live Preview & Fixer</label>
                    <div style="font-size:11px; color:#64748b;">
                        * Langsung ketik di bawah untuk revisi.
                    </div>
                </div>
    
                <div class="split-container" style="display:flex; flex-direction:column; min-height:600px;">
                    
                    <!-- EDITOR AREA -->
                    <div style="flex:1; display:flex; flex-direction:column; min-width:0;">
                        <!-- 1. FILE UPLOAD EDITOR -->
                        <div id="editor-container" style="display:<?php echo ($request['mode']=='FILE_UPLOAD')?'flex':'none'; ?>; flex-direction:column; height:600px;">
                            <!-- EMBEDDED TOOLBAR -->
                            <div id="internal-toolbar" style="background:#f1f5f9; padding:8px 12px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                                 <div style="display:flex; align-items:center; gap:2px; margin-right:auto;">
                                    <span style="font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Mode:</span>
                                    <span style="font-size:11px; color:#3b82f6; font-weight:bold; background:#eff6ff; padding:2px 6px; border-radius:4px;">File Upload Preview</span>
                                 </div>
                                 <div id="color-tools" style="display:flex; align-items:center; gap:6px;">
                                     <button type="button" onclick="performUndo()" style="background:white; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:4px 8px; cursor:pointer;" title="Undo (Ctrl+Z)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                                     </button>
                                     <button type="button" onclick="performRedo()" style="background:white; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:4px 8px; cursor:pointer;" title="Redo (Ctrl+Y)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 3.7"></path></svg>
                                     </button>
                                 </div>
                            </div>
    
                            <!-- SCROLLABLE CONTENT AREA -->
                            <div id="editor-content" contenteditable="true" style="flex:1; padding:15px; overflow:auto; outline:none; font-family:'Inter', sans-serif; font-size:13px; line-height:1.6;">
                            <?php 
                                if ($request['mode'] === 'FILE_UPLOAD' && !empty($content)) {
                                    $rawContent = $content[0]['content'];
                                    if (strpos($rawContent, '&lt;span') !== false || strpos($rawContent, '&lt;table') !== false) {
                                        $rawContent = htmlspecialchars_decode($rawContent);
                                    }
                                    $hasPrebuiltTabs = (strpos($rawContent, 'sheet-tabs-nav') !== false);
                                    
                                    if ($hasPrebuiltTabs) {
                                        echo $rawContent;
                                    } 
                                    elseif (count($content) > 1) {
                                        echo '<div class="sheet-tabs-nav">';
                                        foreach ($content as $idx => $row) {
                                            $active = ($idx === 0) ? 'active' : '';
                                            $media = htmlspecialchars($row['media'] ?? 'Part ' . ($idx+1));
                                            echo "<div id='btn-tab-media-$idx' class='btn-sheet btn-media-tab $active' onclick=\"changeSheet('tab-media-$idx')\">$media</div>";
                                        }
                                        echo '</div>';
                                        foreach ($content as $idx => $row) {
                                            $display = ($idx === 0) ? 'block' : 'none';
                                            echo "<div id='tab-media-$idx' class='media-pane sheet-pane' style='display:$display'>";
                                            echo $row['content']; 
                                            echo "</div>";
                                        }
                                    } 
                                    else {
                                        echo $content[0]['content']; 
                                    }
                                } else {
                                    echo '<p style="color:#999; font-style:italic;">No file preview available.</p>';
                                }
                            ?>
                            </div>
                        </div>
    
                        <!-- 2. FREE INPUT EDITOR -->
                        <div id="manual-panel" style="display:<?php echo ($request['mode']!='FILE_UPLOAD')?'block':'none'; ?>;">
                             <div style="display:flex; justify-content:space-between; align-items:center; padding:10px;">
                                <div style="font-size:12px; color:#666;">
                                    Editing: <span id="active-media-label" style="font-weight:bold; color:var(--primary-red);">None Selected</span>
                                </div>
                            </div>
    
                            <?php 
                            $mediaTypes = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
                            ?>
                            
                            <div id="static-tabs-nav" class="sheet-tabs-nav">
                                <?php foreach($mediaTypes as $media): ?>
                                    <div id="tab-btn-<?= $media ?>" class="btn-sheet" onclick="activateSharedTab('<?= $media ?>')" style="display:none;">
                                        <?= $media ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
    
                            <div id="shared-editor-container" class="sheet-pane" style="padding:0; height:auto; border:2px solid #3b82f6; border-top:none;">
                                <div style="background:#f1f5f9; padding:6px 12px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                                     <span id="char-counter" style="font-size:13px; color:#334155; font-family:'Inter', sans-serif; background:#f1f5f9; padding:4px 8px; border-radius:4px; font-weight:600;">0 / 2000 chars</span>
                                     <div style="display:flex; align-items:center; gap:6px;">
                                         <button type="button" onclick="performUndo()" style="background:white; border:1px solid #cbd5e1; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Undo">↩</button>
                                         <button type="button" onclick="performRedo()" style="background:white; border:1px solid #cbd5e1; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Redo">↪</button>
                                     </div>
                                </div>
                                <div id="shared-editor" contenteditable="true" oninput="syncToStorage()" style="min-height:500px; padding:15px; outline:none; font-family:'Inter', sans-serif; font-size:13px; line-height:1.6;"></div>
                            </div>
                            
                            <?php foreach($mediaTypes as $media): ?>
                                <?php 
                                    $existingText = '';
                                    if (!empty($content)) {
                                        foreach($content as $row) {
                                            $rowSheetName = $row['sheet_name'] ?? $row['media'] ?? '';
                                            if (strtoupper($rowSheetName) === strtoupper($media)) {
                                                $existingText = $row['content'];
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <textarea id="storage-<?= $media ?>" style="display:none;"><?php 
                                    if (strpos($existingText, '&lt;span') !== false) {
                                        echo htmlspecialchars_decode($existingText);
                                    } else {
                                        echo $existingText;
                                    }
                                ?></textarea>
                            <?php endforeach; ?>
                        </div>
    
                    </div>
                </div>
            </div>
            
            <!-- RE-SUBMIT SECTION (Now in Center Column) -->
             <div style="margin-top:20px; padding:12px; background:#fff; border-radius:8px; border:1px solid #e2e8f0; border-top:3px solid var(--primary-red); shadow:0 1px 2px rgba(0,0,0,0.05);">
                <h4 style="margin:0 0 10px 0; color:#1e293b; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Resubmit Revision
                </h4>
    
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Catatan Perbaikan <span style="color:red">*</span></label>
                <textarea id="maker_note" class="form-control" rows="2" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background-color:#f8fafc; font-family:inherit; font-size:13px; resize:none;" placeholder="Jelaskan apa saja yang telah diperbaiki..."><?php echo isset($draftNote)?htmlspecialchars($draftNote):''; ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Pilih Supervisor (For Re-Approval) <span style="color:red">*</span></label>
                <select id="selected_spv" class="form-select" style="width:100%; max-width:400px; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background-color:#f8fafc; font-weight:600; font-size:13px; color:#334155; cursor:pointer;">
                    <option value="">-- Pilih SPV --</option>
                    <?php foreach ($spvList as $spv) : ?>
                        <option value="<?php echo htmlspecialchars($spv['userid']); ?>" <?php echo ($request['selected_spv'] == $spv['userid'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($spv['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <p style="font-size:11px; color:#94a3b8; margin-top:10px; font-style:italic;">Pastikan semua perbaikan sudah sesuai dengan catatan supervisor.</p>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px; padding-top:15px; border-top:1px solid #e2e8f0;">
                <button type="button" onclick="cancelEdit()" class="btn-cancel" style="padding:10px 24px; border:1px solid #cbd5e1; border-radius:6px; color:#64748b; background:white; font-weight:600; font-size:14px; display:inline-flex; align-items:center; transition:all 0.2s; cursor:pointer;">Cancel</button>
                <button type="button" class="btn btn-secondary" onclick="submitUpdate(true)" style="background:white; color:#64748b; border:1px solid #cbd5e1; padding:10px 24px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;">Save Draft</button>
                <button type="button" class="btn btn-primary" onclick="submitUpdate(false)" style="background:var(--primary-red); color:white; border:none; padding:10px 24px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 2px 4px rgba(211,47,47,0.3); transition:all 0.2s;">Resubmit Request</button>
            </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: HISTORY & NOTES -->
        <div class="right-sidebar" style="padding-bottom: 20px;">
                    
                    <!-- Request History card (Reused from review.php) -->
                    <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); flex-shrink: 0;">
                        <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Request History
                        </h4>
                        
                        <div style="position: relative;">
                            <!-- Vertical Timeline Line -->
                            <div style="position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; z-index: 1;"></div>
                            
                            <?php if (!empty($timeline)): ?>
                                <?php foreach (array_reverse($timeline) as $idx => $log): ?>
                                    <div style="position: relative; padding-left: 30px; margin-bottom: 20px; z-index: 2;">
                                        <!-- Timeline Dot -->
                                        <div style="position: absolute; left: 6px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: <?php echo $idx === 0 ? 'var(--primary-red)' : '#cbd5e1'; ?>; border: 2px solid #white; box-shadow: 0 0 0 2px #fff;"></div>
                                        
                                        <div style="font-size: 11px; font-weight: 700; color: #1e293b; margin-bottom: 2px;">
                                            <?php echo htmlspecialchars($log['action_type'] ?? $log['action'] ?? 'Status Update'); ?>
                                        </div>
                                        <div style="font-size: 10px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                                            <span style="font-weight: 600; color: #475569;"><?php echo htmlspecialchars($log['user_id']); ?></span>
                                            •
                                            <span><?php echo htmlspecialchars($log['group_name'] ?? 'Unit'); ?></span>
                                        </div>
                                        <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
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
                                            <div style="margin-top: 6px; padding: 6px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; font-size: 10px; color: #475569; font-style: normal; border-left: 3px solid #f59e0b;">
                                                <?php echo htmlspecialchars($note); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #94a3b8; font-size: 11px; font-style: italic; text-align: center; margin-left: 30px;">No history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="comment-sidebar" class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; display:none; flex-shrink: 0; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div style="font-size:13px; font-weight:700; color:#1e293b; margin-bottom:15px; text-transform:uppercase; border-bottom:1px solid #e2e8f0; padding-bottom:10px; display:flex; align-items:center; gap:8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <?php echo htmlspecialchars($lastRole ?? 'Reviewer'); ?> Notes
                        </div>
                        <div id="comment-list"></div>
                    </div>
                </div>

            </div>
        </div>



<!-- Modals replaced by SweetAlert2 -->

<script>
    // ===== CRITICAL: DEFINE GLOBAL FUNCTIONS FIRST =====
    
    // Global Tab Switcher for Excel Preview (File Upload Mode)
    // Must be defined early for onclick handlers
    window.changeSheet = function(sheetId) {
        // PREVENT HANG: Suspend observer during tab switch
        isInternalChange = true;
        
        // Hide standard panes AND legacy media panes
        document.querySelectorAll('.sheet-pane, .media-pane').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.btn-sheet').forEach(el => el.classList.remove('active'));
        
        const target = document.getElementById(sheetId);
        if (target) target.style.display = 'block';
        
        // Use ID selector (more robust), with fallback
        let btn = document.getElementById('btn-' + sheetId);
        if (!btn) {
            // Fallback for old HTML without IDs or Legacy style
            btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
            if (!btn) btn = document.getElementById(sheetId.replace('tab-media-', 'tab-media-btn-')); // Legacy fallback
        }
        if (btn) btn.classList.add('active');
        
        // Re-enable observer after short delay
        setTimeout(() => isInternalChange = false, 100);
    };
    
    // ===== VARIABLES =====
    let selectedFile = null;
    let currentActiveMedia = null;
    const SERVER_CONTENT = <?php echo json_encode($content); ?>;
    let lastPreviewHtml = (SERVER_CONTENT.length > 0) ? SERVER_CONTENT[0].content : '';
    
    // ===== REVISION REGISTRY (Track initial revisions for persistent cards) =====
    const revisionRegistry = {};

    // ===== MAKER TRACKING GLOBALS =====
    const CURRENT_USER_NAME = "<?php echo htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['userid'] ?? 'Maker'); ?>";
    const CURRENT_USER_ROLE = "<?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'Maker'); ?>";
    const CURRENT_USER_JOB_FUNCTION = "<?php echo htmlspecialchars($_SESSION['user']['job_function'] ?? ''); ?>";
    const DELETED_SPANS = new Set();
    window.CURRENT_USER_NAME = CURRENT_USER_NAME;
    window.CURRENT_USER_ROLE = CURRENT_USER_ROLE;
    window.CURRENT_USER_JOB_FUNCTION = CURRENT_USER_JOB_FUNCTION;
    window.DELETED_SPANS = DELETED_SPANS;

    // Helper to fix Excel Tabs in ContentEditable
    function makeTabsNonEditable() {
        const editor = document.getElementById('editor-content');
        if (!editor) return;
        
        // Find common tab containers (adjust selector based on your HTML generator)
        // Usually PHPExcel/PhpSpreadsheet uses specific classes or inline styles
        // Or if it matches '.sheet-tabs-nav' logic or buttons
        const potentialTabs = editor.querySelectorAll('.sheet-tabs-nav, .nav-tabs, ul[role="tablist"], div[style*="border-bottom"] button');
        
        potentialTabs.forEach(el => {
            el.contentEditable = "false";
            // Determine parent wrapper if needed
            if(el.parentElement) el.parentElement.contentEditable = "false";
        });
        
        // Specific fix for the 'btn-sheet' class if injected
        // Don't set contentEditable=false on buttons individually, as parent .sheet-tabs-nav handles it.
        // And setting it on button might block click in some browsers.
        const sheetBtns = editor.querySelectorAll('.btn-sheet');
        sheetBtns.forEach(btn => {
            btn.style.cursor = "pointer"; // Force pointer
        });
    }

    // Call this after any new content load (e.g. file upload)
    function afterContentLoad() {
        makeTabsNonEditable();
        initializeRevisionRegistry();
        renderSideComments();
        updateSheetTabBadges();
    }
    
    // ===== REVISION REGISTRY MANAGEMENT =====
    function initializeRevisionRegistry() {
        // Scan all editors for existing revision spans and register them
        const editors = [document.getElementById('editor-content'), document.getElementById('shared-editor')];
        
        editors.forEach(editor => {
            if (!editor) return;
            
            const spans = editor.querySelectorAll('.revision-span, .inline-comment');
            spans.forEach(span => {
                const id = span.id || span.getAttribute('data-comment-id');
                if (id && !revisionRegistry[id]) {
                    // FIX: Capture Comment Note (data-comment-text) for comments, innerText for revisions
                    const noteText = span.getAttribute('data-comment-text') || span.getAttribute('title');
                    const contentText = span.innerText || span.textContent;
                    
                    revisionRegistry[id] = {
                        text: noteText || contentText, // Prefer Note if available
                        type: span.classList.contains('revision-span') ? 'revision' : 'comment',
                        status: 'active'
                    };
                }
            });
        });
        
        // Also scan hidden storage for Free Input mode
        document.querySelectorAll('textarea[id^="storage-"]').forEach(storage => {
            const temp = document.createElement('div');
            temp.innerHTML = storage.value;
            const spans = temp.querySelectorAll('.revision-span, .inline-comment');
            
            spans.forEach(span => {
                const id = span.id || span.getAttribute('data-comment-id');
                if (id && !revisionRegistry[id]) {
                    // FIX: Capture Comment Note (data-comment-text) for comments
                    const noteText = span.getAttribute('data-comment-text') || span.getAttribute('title');
                    const contentText = span.innerText || span.textContent;
                    
                    revisionRegistry[id] = {
                        text: noteText || contentText,
                        type: span.classList.contains('revision-span') ? 'revision' : 'comment',
                        status: 'active'
                    };
                }
            });
        });
    }
    
    // ===== SIDEBAR RENDERING =====


    function updateTabBadges() {
        // MATCHING STYLE WITH FILE UPLOAD MODE
        const mediaTypes = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
        mediaTypes.forEach(media => {
            const storage = document.getElementById('storage-' + media);
            const btn = document.getElementById('tab-btn-' + media);
            if (storage && btn) {
                // ROBUST CHECK: Parse HTML to find elements
                // Create a temp dummy element to parse the string
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = storage.value;
                
                // Check if any element has the required classes (exclude resolved)
                const hasComments = tempDiv.querySelector('.inline-comment:not(.resolved-comment), .revision-span:not(.resolved-comment)');

                if (hasComments) {
                    // Add badge if not exists
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
                        dot.title = "Has Unresolved Revisions";
                        btn.appendChild(dot);
                    }
                } else {
                     // Remove badge if resolved
                     const dot = btn.querySelector('.tab-badge-dot');
                     if(dot) dot.remove();
                }
            }
        });
    }

    // --- ADVANCED UNDO / REDO HISTORY ENGINE (Content + Actions) ---
    const historyStack = { undo: [], redo: [] };
    let isInternalChange = false; // Flag to prevent observer loops
    let debounceTimer = null;

    // INIT OBSERVER ON LOAD
    // Helper to get active editor
    function getCurrentEditor() {
         const uploadMode = document.getElementById('upload-panel').style.display !== 'none';
         return uploadMode ? document.getElementById('editor-content') : document.getElementById('shared-editor');
    }

    // INIT OBSERVER ON LOAD (Target BODY or handle dynamic switching)
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize revision registry on page load
        initializeRevisionRegistry();
        renderSideComments();
        
        // Observer needs to attach to BOTH editors
        ['editor-content', 'shared-editor'].forEach(id => {
            const editor = document.getElementById(id);
            if (editor) {
                // Initial State
                if (id === 'editor-content') {
                     historyStack.undo.push({ action: 'content-change', html: editor.innerHTML });
                }

                const observer = new MutationObserver((mutations) => {
                    if (isInternalChange) return;
                    // Debounce
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        // Push State of CURRENTLY ACTIVE EDITOR
                        const activeEd = getCurrentEditor();
                        if (activeEd) {
                            pushSnapshot(activeEd.innerHTML);
                            // FIX: Auto-update sidebar when content changes (Deleted text -> update card status)
                            renderSideComments();
                            updateSheetTabBadges();
                        }
                    }, 500);
                });
                
                observer.observe(editor, { childList: true, subtree: true, characterData: true, attributes: true });
            }
        });
    });

    function pushSnapshot(html) {
        // Optimize: Don't push if same as last
        const last = historyStack.undo[historyStack.undo.length - 1];
        if (last && last.action === 'content-change' && last.html === html) return;
        
        historyStack.undo.push({ action: 'content-change', html: html });
        historyStack.redo = []; 
        updateUndoRedoUI();
        // REMOVED: markUnsaved() - will be called by explicit actions only
    }

    function pushHistory(action, data) {
        historyStack.undo.push({ action, data });
        historyStack.redo = []; 
        updateUndoRedoUI();
    }

    function performUndo() {
        if (historyStack.undo.length <= 1) return; // Keep initial state

        const lastAction = historyStack.undo.pop();
        historyStack.redo.push(lastAction);
        
        // Peek previous state to restore
        const prevState = historyStack.undo[historyStack.undo.length - 1];
        
        if (lastAction.action === 'content-change') {
            applyContentRestoration(prevState.html);
        } else if (lastAction.action === 'resolve') {
            // Special Logic for Resolve: Revert DOM + Attribute
            applyContentRestoration(lastAction.data.parentHTML);
        }
        
        renderSideComments(); 
        updateSheetTabBadges();
        updateUndoRedoUI();
    }

    function performRedo() {
        const nextAction = historyStack.redo.pop();
        if (!nextAction) return;

        historyStack.undo.push(nextAction);

        if (nextAction.action === 'content-change') {
            applyContentRestoration(nextAction.html);
        } else if (nextAction.action === 'resolve') {
            // Re-Apply Resolve (Unwrap logic tricky here because DOM changed)
            // Better to rely on the Snapshot stored in "parentHTML" if we unified logic?
            // Actually, for consistency, "Resolve" should ALSO trigger a Mutation. 
            // BUT to keep "Resolve" atomic with attributes, we handle it explicitly.
            // Simplified: If 'resolve' data contained the AFTER html, we could just restore that.
            // For now, let's just trigger the unwrap logic again if element exists.
             const spanId = nextAction.data.spanId;
             const span = document.querySelector(`[data-comment-id="${spanId}"], #${spanId}`);
             if (span) {
                const parent = span.parentNode;
                while (span.firstChild) parent.insertBefore(span.firstChild, span);
                parent.removeChild(span);
             }
        }

        renderSideComments();
        updateSheetTabBadges();
        updateUndoRedoUI();
    }

    function applyContentRestoration(html) {
        const editor = getCurrentEditor(); // Use Active Editor
        if (editor) {
            isInternalChange = true;
            editor.innerHTML = html || "";
            setTimeout(() => isInternalChange = false, 50);
        }
    }

    function updateUndoRedoUI() {
        // Optional: Toggle buttons visibility
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
            e.preventDefault();
            performUndo();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
            e.preventDefault();
            performRedo();
        }
    });

    // Modified Resolve Logic with History
    // === RESOLVE COMMENT LOGIC ===
    function resolveComment(id, event) {
        event.stopPropagation(); 
        
        // 1. INSTANT DISABLE BUTTON (Prevent multiple clicks)
        const btn = event.target.closest('.btn-resolve');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<span style="font-size:10px;">Processing...</span>';
        }
        
        // 2. Find and modify the span
        // 2. Find and modify the span
        let span = document.getElementById(id);
        if(!span) span = document.querySelector(`.inline-comment[data-comment-id='${id}'], .revision-span[id='${id}']`);
        
        if (!span) {
            console.warn('Comment span not found:', id);
            // GRACEFUL HANDLING: If span is missing, it means user deleted the text.
            // Treat as "Resolved/Deleted" update.
            if(btn) {
                btn.innerHTML = 'Scan Update...';
            }
            // Force refresh sidebar to remove the stale card
            setTimeout(() => {
                renderSideComments();
                updateSheetTabBadges();
            }, 500);
            return;
        }

        // Visual Feedback: UNWRAP the span to remove highlight permanently
        // This converts the highlighted span back to plain text
        const parent = span.parentNode;
        while (span.firstChild) {
            parent.insertBefore(span.firstChild, span);
        }
        parent.removeChild(span);

        // Update Undo History
        pushSnapshot(getCurrentEditor().innerHTML);

        // Update UI (with debounce)
        renderSideComments(); // This will effectively "remove" the card from active list
        updateSheetTabBadges();
        if (typeof updateTabBadges === 'function') updateTabBadges();
        markUnsaved(); // Mark dirty so user knows to save
    }

    // Render control flags
    let isRendering = false;
    let rafId = null;

    function renderSideComments() {
        // Cancel any pending RAF
        if (rafId) {
            cancelAnimationFrame(rafId);
            console.log('[SIDEBAR] Cancelled pending RAF');
        }
        
        // Schedule render on next animation frame (sync with browser paint)
        rafId = requestAnimationFrame(() => {
            rafId = null;
            actualRenderSideComments();
        });
    }

    function actualRenderSideComments() {
        // Prevent duplicate rendering
        if (isRendering) {
            console.log('[SIDEBAR] Already rendering, skipping...');
            return;
        }
        
        console.log('[SIDEBAR] Starting render...');
        isRendering = true;
        
        try {
            const list = document.getElementById('comment-list');
            const sidebar = document.getElementById('comment-sidebar');
            if(!list || !sidebar) {
                console.warn('[SIDEBAR] List or sidebar element not found');
                return;
            }
            
            // CRITICAL: Clear all existing cards first
            list.innerHTML = '';
            console.log('[SIDEBAR] Cleared existing cards');
            
            // 1. Unresolved Comments
            // FIX: Include legacy red-colored spans
            const selector = '.inline-comment, .revision-span, ' + 
                             'span[style*="color: rgb(239, 68, 68)"], span[style*="color:#ef4444"], span[style*="color: #ef4444"], span[style*="color:red"]';
            
            // SCOPED SEARCH: Only look inside editors to avoid picking up UI elements (like form asterisks)
            const roots = [document.getElementById('editor-content'), document.getElementById('shared-editor')];
            let rawSpans = [];

            roots.forEach(root => {
                if (root) {
                    const found = root.querySelectorAll(selector);
                    rawSpans = [...rawSpans, ...Array.from(found)];
                }
            });

            // FIX: Hidden Stored Spans (Free Input Mode)
            if (document.getElementById('manual-panel') && document.getElementById('manual-panel').style.display !== 'none') {
                 // Determine Active Media to skip it (already in shared-editor)
                 const activeMedia = document.getElementById('active-media-label') ? document.getElementById('active-media-label').innerText : '';
                 
                 const storageInputs = document.querySelectorAll('textarea[id^="storage-"]');
                 storageInputs.forEach(input => {
                      const mediaName = input.id.replace('storage-', '');
                      if (mediaName === activeMedia) return; // Skip active

                      const temp = document.createElement('div');
                      temp.innerHTML = input.value; // Parse HTML
                      const found = temp.querySelectorAll(selector);
                      
                      found.forEach(span => {
                          // Clone attributes to object since span is detached
                          // STRICT FILTER: Skip if no manual note
                          if (!span.getAttribute('data-comment-text')) return;

                          rawSpans.push({
                              isVirtual: true,
                              mediaName: mediaName,
                              id: span.getAttribute('data-comment-id') || span.id,
                              text: span.getAttribute('data-comment-text'),
                              user: span.getAttribute('data-comment-user') || 'Reviewer',
                              time: span.getAttribute('data-comment-time') || '',
                              isResolved: false // Revisions are unresolved by default
                          });
                      });
                 });
            }



            // FIX: Separate DOM Elements from Virtual Objects
            const domSpans = rawSpans.filter(s => !s.isVirtual);
            
            const activeComments = domSpans.map((span, index) => {
                 // AUTO-ASSIGN ID for Legacy items (Only for DOM elements)
                 if (!span.id && !span.getAttribute('data-comment-id')) {
                     // CRITICAL: Suppress Observer Loop
                     isInternalChange = true;
                     
                     // USE STABLE ID: text-based hash or just a count to avoid rotation
                     const stableIdx = index + 1;
                     span.id = 'legacy-rev-' + stableIdx;
                     span.classList.add('revision-span'); 
                     
                     setTimeout(() => isInternalChange = false, 50);
                 }

                 // Determine text content
                 let commentText = span.getAttribute('data-comment-text');
                 // Deletion spans: show deletion marker
                 if (!commentText && span.getAttribute('data-is-deletion')) {
                     commentText = '🗑️ Deleted: "' + (span.getAttribute('data-deleted-text') || span.textContent) + '"';
                 }
                 // Draft revision spans: show typed text
                 if (!commentText && span.classList.contains('revision-span') && span.classList.contains('draft')) {
                     const draftText = (span.innerText || span.textContent || '').replace(/\u200B/g, '').trim();
                     if (draftText) commentText = draftText;
                 }


                 return {
                     id: span.getAttribute('data-comment-id') || span.id,
                     element: span,
                     text: commentText,
                     user: span.getAttribute('data-comment-user') || span.getAttribute('data-user') || 'Reviewer',
                     time: span.getAttribute('data-comment-time') || '',
                     timestamp: parseInt((span.getAttribute('data-comment-id') || span.id).replace(/\D/g, '')) || 0,
                     isRevision: true,
                     isDeletion: span.classList.contains('deletion-span'),
                     isDraft: span.classList.contains('draft'),
                     isResolved: false,
                     mediaName: null
                 };
            }).filter(c => c.text);
            
            // Normalize Virtual Spans
            const virtualComments = rawSpans.filter(s => s.isVirtual).map(s => ({
                  id: s.id || 'virtual-' + Date.now(),
                  element: null, // Detached
                  text: s.text,
                  user: s.user,
                  time: s.time,
                  timestamp: parseInt((s.id || '').replace(/\D/g, '')) || 0,
                  isRevision: true,
                  isResolved: false,
                  mediaName: s.mediaName
             }));

            const activeCommentsFinal = [...activeComments, ...virtualComments];

            // 2. Resolved Comments (Tracked by class 'resolved-comment' or History)
             const resolvedComments = Array.from(document.querySelectorAll('.resolved-comment')).map(span => ({
                 id: span.getAttribute('data-comment-id') || span.id,
                 element: span,
                 text: span.getAttribute('data-comment-text') || span.getAttribute('title') || "Resolved Item",
                 user: span.getAttribute('data-comment-user') || 'Reviewer',
                  time: span.getAttribute('data-comment-time') || '',
                  timestamp: parseInt((span.getAttribute('data-comment-id') || span.id).replace(/\D/g, '')) || 0,
                  isRevision: false,
                  isResolved: true
             }));
             
             // PERSISTENT CARDS: Check registry for deleted spans (not found in DOM but were initially present)
             const currentSpanIds = new Set(activeCommentsFinal.map(c => c.id));
             const deletedSpans = Object.keys(revisionRegistry).filter(id => !currentSpanIds.has(id)).map(id => ({
                 id: id,
                 element: null, // Deleted, no longer in DOM
                 text: revisionRegistry[id].text,
                 user: 'Reviewer',
                 time: '',
                 timestamp: parseInt(id.replace(/\D/g, '')) || 0,
                 isRevision: true,
                 isResolved: true // Mark as resolved since it was deleted
             }));

             // [NEW] CLEANUP: Find and unwrap metadata spans that shouldn't be visible
             function cleanupInternalSpans() {
                const targetRoots = [document.getElementById('editor-content'), document.getElementById('shared-editor')];
                targetRoots.forEach(root => {
                    if (!root) return;
                    root.querySelectorAll('.original-content').forEach(span => {
                        const parent = span.parentNode;
                        while(span.firstChild) parent.insertBefore(span.firstChild, span);
                        span.remove();
                    });
                });
             }
             cleanupInternalSpans();

             const allItems = [...activeCommentsFinal, ...resolvedComments, ...deletedSpans];
            console.log('[SIDEBAR] Found items:', { active: activeComments.length, resolved: resolvedComments.length, total: allItems.length });

            // CRITICAL: Deduplicate by ID using Set
            const uniqueItems = [];
            const seenIds = new Set();
            
            allItems.forEach(item => {
                // Only add if we haven't seen this ID before
                if (item.id && !seenIds.has(item.id)) {
                    seenIds.add(item.id);
                    uniqueItems.push(item);
                } else if (item.id) {
                    console.warn('[SIDEBAR] Skipping duplicate comment ID:', item.id);
                }
            });
            
            console.log('[SIDEBAR] After deduplication:', { unique: uniqueItems.length, duplicates: allItems.length - uniqueItems.length });
            
            // STABLE SORT: By Timestamp Descending
            uniqueItems.sort((a, b) => b.timestamp - a.timestamp);

            if (uniqueItems.length === 0) {
                sidebar.style.display = 'none';
                console.log('[SIDEBAR] No items, hiding sidebar');
                return;
            }
            
            sidebar.style.display = 'block';

            // Render Cards (using uniqueItems instead of allItems)
            uniqueItems.forEach(c => {
                const card = document.createElement('div');
                card.className = 'comment-card';
                if (c.isResolved) card.classList.add('resolved');
                
                // Apply Styling Directly to Card (Consistent with Review.php)
                card.style.background = 'white';
                card.style.border = c.isResolved ? '1px solid #bbf7d0' : '1px solid #e2e8f0';
                card.style.borderRadius = '10px';
                card.style.padding = '12px';
                card.style.marginBottom = '10px';
                card.style.boxShadow = '0 1px 2px rgba(0,0,0,0.02)';
                card.style.transition = 'all 0.2s';
                card.style.cursor = 'pointer';
                if (c.isResolved) card.style.opacity = '0.6';

                // PERSISTENT ACTIVE STATE
                if (c.id === window.activeSideCommentId) {
                     card.style.borderColor = '#ef4444'; 
                     card.style.borderWidth = '2px';
                     card.style.backgroundColor = '#fee2e2'; // Stronger Pink
                }

                let iconStr = (c.user||'R').charAt(0).toUpperCase();
                let iconBg = '#eff6ff';
                let iconColor = '#3b82f6';
                let borderColor = '#dbeafe';
                let titleText = c.user;
                
                if (c.isRevision) {
                    iconBg = '#fef2f2';
                    iconColor = '#ef4444'; 
                    borderColor = '#fecaca';
                    titleText = "Revision Required";
                    iconStr = "!";
                }

                if (c.isDeletion) {
                    iconBg = '#fef2f2';
                    iconColor = '#ef4444';
                    borderColor = '#fecaca';
                    titleText = "Deleted Text";
                    iconStr = "🗑️";
                    card.style.borderLeft = '3px solid #ef4444';
                }

                if (c.isDraft && !c.isDeletion) {
                    iconBg = '#fef2f2';
                    iconColor = '#ef4444';
                    borderColor = '#fecaca';
                    titleText = "New Revision";
                    iconStr = "✎";
                    card.style.borderLeft = '3px solid #ef4444';
                }

                if (c.isResolved) {
                    iconBg = '#dcfce7';
                    iconColor = '#16a34a';
                    borderColor = '#bbf7d0';
                    iconStr = "✓";
                    titleText = "Resolved";
                }

                let actionButton = '';
                // Only show "Mark as Done" for reviewer highlights (not for deletions or drafts)
                if (!c.isResolved && !c.isDeletion && !c.isDraft) {
                    actionButton = `
                    <button onclick="resolveComment('${c.id}', event)" class="btn-resolve" style="width:100%; margin-top:10px; padding:6px 12px; background:white; border:1px solid #16a34a; color:#16a34a; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:all 0.2s;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Mark as Done
                    </button>`;
                } else if (c.isResolved) {
                    actionButton = `
                    <div style="margin-top:8px; width:100%; text-align:center; font-size:10px; color:#16a34a; font-weight:bold; padding:4px; background:#f0fdf4; border-radius:4px;">
                        ✅ Resolved
                    </div>`;
                }

                // Determine text color: red for draft/deletion items
                const textColor = (c.isDraft || c.isDeletion) ? '#ef4444' : '#334155';
                const timeLabel = c.isDraft ? `Draft • ${c.user}` : c.time;

                card.id = `card-${c.id}`;
                card.innerHTML = `
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <div style="width:24px; height:24px; background:${iconBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:${iconColor}; font-weight:bold; border:1px solid ${borderColor}; flex-shrink:0;">
                                ${iconStr}
                            </div>
                            <div>
                                <div style="font-size:12px; font-weight:700; color:#334155;">${titleText}</div>
                                <div style="font-size:10px; color:#94a3b8;">${timeLabel}</div>
                            </div>
                        </div>
                        <div class="draft-body" style="font-size:12px; color:${textColor}; line-height:1.4; word-wrap:break-word;${c.isDeletion ? ' text-decoration:line-through;' : ''}">
                            ${c.text}
                        </div>
                        ${actionButton}
                `;

                card.onclick = (e) => {
                    if (e.target.closest('button')) return; 
                    
                    e.preventDefault();
                    e.stopPropagation();

                    const targetId = c.id;
                    console.log('[SIDEBAR] Navigating to:', targetId);

                    // 1. Handle Tab Switching (Cross-Tab)
                    if (c.mediaName) {
                        // Free Input Tab Switch
                        if (typeof activateSharedTab === 'function') {
                            activateSharedTab(c.mediaName);
                        }
                    } else if (c.element) {
                        // File Upload Tab Switch
                        const parentSheet = c.element.closest('.sheet-pane, .media-pane');
                        if (parentSheet && (parentSheet.style.display === 'none' || getComputedStyle(parentSheet).display === 'none')) {
                            const sheetId = parentSheet.id;
                            let tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"], div[onclick*="'${sheetId}'"]`);
                            
                            if (!tabBtn && sheetId.startsWith('tab-media-')) {
                                const legacyId = sheetId.replace('tab-media-', 'tab-media-btn-');
                                tabBtn = document.getElementById(legacyId);
                            }
                            
                            if (!tabBtn && sheetId.startsWith('tab-')) {
                                const idx = sheetId.replace('tab-', '');
                                tabBtn = document.getElementById(`tab-btn-${idx}`);
                            }
           
                            if (tabBtn) tabBtn.click();
                        }
                    }

                    // 2. Perform Scroll & Highlight (With Timeout for State Sync)
                    setTimeout(() => {
                        window.activeSideCommentId = targetId; 

                        // IMPORTANT: Re-fetch the element because it might have been replaced (innerHTML switch)
                        const realElement = document.getElementById(targetId) || document.querySelector(`[data-comment-id="${targetId}"]`);
                        
                        if (realElement) {
                            // Suppress Observer during visual update
                            isInternalChange = true;

                            // Clear previous highlights (cleanup)
                            document.querySelectorAll('.active-highlight').forEach(el => el.classList.remove('active-highlight'));
                            document.querySelectorAll('.blink-highlight').forEach(el => el.classList.remove('blink-highlight'));
                            
                            // Scroll to position
                            realElement.scrollIntoView({behavior: "smooth", block: "center"});
                            
                            // Add single blink effect (will auto-remove after 1.5s animation)
                            realElement.classList.remove('blink-highlight');
                            void realElement.offsetWidth; // Force reflow
                            realElement.classList.add('blink-highlight');
                            
                            // Auto-remove blink class after animation completes
                            setTimeout(() => {
                                realElement.classList.remove('blink-highlight');
                            }, 1500); // Match animation duration
                            
                            // Re-enable observer
                            setTimeout(() => isInternalChange = false, 300); 
                        } else {
                            console.warn('[SIDEBAR] Target element not found for scroll:', targetId);
                        }

                        // Update Card Visuals in Sidebar
                        document.querySelectorAll('.comment-card').forEach(x => { 
                             x.style.borderColor = x.classList.contains('resolved') ? '#bbf7d0' : '#e2e8f0';
                             x.style.borderWidth = '1px';
                             x.style.backgroundColor = 'white';
                             x.style.transform = 'scale(1)';
                        });

                        card.style.borderColor = '#ef4444'; 
                        card.style.borderWidth = '2px';
                        card.style.backgroundColor = '#fee2e2';
                        card.style.transform = 'scale(1.02)';
                    }, 150);
                };

                list.appendChild(card);
            });
            
            console.log('[SIDEBAR] Rendering complete, added', uniqueItems.length, 'unique cards');
            
        } catch (error) {
            console.error('[SIDEBAR] Error during rendering:', error);
        } finally {
            // CRITICAL: Reset flag in finally block to ensure it's always reset
            isRendering = false;
            console.log('[SIDEBAR] Render flag reset');
        }
    }

    function filterProduk() {
        const types = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value);
        const konv = document.getElementById('produk-konv');
        const syr = document.getElementById('produk-syariah');
        if(konv) konv.style.display = types.includes('Konvensional') ? 'block' : 'none';
        if(syr) syr.style.display = types.includes('Syariah') ? 'block' : 'none';
    }

    function toggleInput(id, checked) {
        const el = document.getElementById(id);
        if(el) el.style.display = checked ? 'block' : 'none';
    }

    function switchMode(mode) {
        document.getElementById('input_mode').value = (mode === 'upload') ? 'FILE_UPLOAD' : 'FREE_INPUT';
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        event.target.classList.add('active');
        
        document.getElementById('upload-panel').style.display = (mode === 'upload') ? 'block' : 'none';
        document.getElementById('manual-panel').style.display = (mode === 'manual') ? 'block' : 'none';
        
        if (mode === 'manual') {
            updateFreeInputTabs();
            // Clear history or reset stack potentially?
            // For now just ensure new editor is focused
        }
    }



    function updateSheetTabBadges() {
        // 1. Clear existing badges
        document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
        
        // 2. Scan Sheets for Comments
        document.querySelectorAll('.sheet-pane, .media-pane').forEach(pane => {
            // ROBUST SELECTOR: Check for class AND various color formats (Hex, Name, RGB)
            // EXCLUDE resolved comments (they are intentionally black now)
            const hasComments = pane.querySelector('.inline-comment:not(.resolved-comment), .revision-span:not(.resolved-comment), span[style*="#ef4444"]:not(.resolved-comment), span[style*="color:red"]:not(.resolved-comment), span[style*="color: red"]:not(.resolved-comment), span[style*="rgb(255, 0, 0)"]:not(.resolved-comment)');
            
            if (hasComments) {
                const sheetId = pane.id;
                let btn = document.getElementById('btn-' + sheetId);
                
                // Fallback for Free Input Tabs
                if (!btn && sheetId.startsWith('tab-')) {
                    const idx = sheetId.replace('tab-', '');
                    btn = document.getElementById(`tab-btn-${idx}`);
                }
                
                // Fallback for Legacy File Upload Tabs (tab-media-0 -> tab-media-btn-0)
                if (!btn && sheetId.startsWith('tab-media-')) {
                     const legacyId = sheetId.replace('tab-media-', 'tab-media-btn-');
                     btn = document.getElementById(legacyId);
                }

                // Generic Fallback based on onclick
                if (!btn) {
                     btn = document.querySelector(`button[onclick*="'${sheetId}'"], div[onclick*="'${sheetId}'"]`);
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
        
        // 3. Scan Hidden Free Input Sheets (Storage)
        if (document.getElementById('manual-panel') && document.getElementById('manual-panel').style.display !== 'none') {
             const activeMedia = document.getElementById('active-media-label') ? document.getElementById('active-media-label').innerText : '';
             // EXCLUDE resolved comments from badge detection
             const selector = '.inline-comment:not(.resolved-comment), .revision-span:not(.resolved-comment), span[style*="#ef4444"]:not(.resolved-comment), span[style*="color:red"]:not(.resolved-comment), span[style*="color: red"]:not(.resolved-comment), span[style*="rgb(255, 0, 0)"]:not(.resolved-comment)';

             document.querySelectorAll('textarea[id^="storage-"]').forEach(store => {
                  const mediaName = store.id.replace('storage-', '');
                  if (mediaName === activeMedia) return; // Active is handled by wrapper

                  const temp = document.createElement('div');
                  temp.innerHTML = store.value;
                  if (temp.querySelector(selector)) {
                       const btn = document.getElementById('tab-btn-' + mediaName);
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
    }

    function updateFreeInputTabs() {
        const medias = Array.from(document.querySelectorAll('input[name="media"]:checked')).map(c => c.value);
        document.querySelectorAll('.btn-sheet').forEach(el => el.style.display = 'none');

        if (medias.length === 0) {
            document.getElementById('active-media-label').innerText = "None Selected";
            document.getElementById('shared-editor').value = "";
            currentActiveMedia = null;
            return;
        }

        medias.forEach(media => {
            const btn = document.getElementById('tab-btn-' + media);
            if (btn) btn.style.display = 'inline-block';
        });

        if (!currentActiveMedia || !medias.includes(currentActiveMedia)) {
            activateSharedTab(medias[0]);
        }
    }

    // --- CHARACTER LIMIT LOGIC ---
    const MEDIA_LIMITS = {
        'SMS': 160,
        'WhatsApp': 1024,
        'Robocoll': 1000,
        'Chatbot': 1000,
        'Email': 5000,
        'Surat': 5000,
        'VB': 5000,
        'Others': 2000
    };

    function updateCharCounter() {
        if (!currentActiveMedia) return;
        
        const editor = document.getElementById('shared-editor');
        const limit = MEDIA_LIMITS[currentActiveMedia] || 2000;
        const currentLength = editor.innerText.length; // Use innerText for accurate char count (ignoring HTML tags)
        
        const counterEl = document.getElementById('char-counter');
        if (counterEl) {
            counterEl.innerText = `${currentLength} / ${limit} chars`;
            
            if (currentLength > limit) {
                counterEl.style.color = 'red';
                counterEl.style.fontWeight = 'bold';
                counterEl.style.background = '#fee2e2'; // Light red bg
            } else {
                counterEl.style.color = '#334155'; // Darker Slate
                counterEl.style.fontWeight = '600'; // Semicold
                counterEl.style.background = '#f1f5f9'; // Restore gray bg
            }
        }
    }

    function activateSharedTab(media) {
        if (typeof syncToStorage === 'function') syncToStorage(); // Safety check
        currentActiveMedia = media;
        document.getElementById('active-media-label').innerText = media;
        
        document.querySelectorAll('.btn-sheet').forEach(b => b.classList.remove('active'));
        const btn = document.getElementById('tab-btn-' + media);
        if (btn) btn.classList.add('active');

        const storage = document.getElementById('storage-' + media);
        const editor = document.getElementById('shared-editor');
        
        // Load content
        let html = storage ? storage.value : '';
        
        // [FIX] AUTO-RECOVERY for Free Input
        if (html.includes('&lt;span') || html.includes('&lt;table')) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            html = temp.textContent || temp.innerText || html;
        }

        editor.innerHTML = html;
        
        // [NEW] Cleanup internal tracking spans immediately
        if (typeof cleanupInternalSpans === 'function') cleanupInternalSpans();

        editor.focus();
        
        // Update Counter
        updateCharCounter();
        
        // CRITICAL: Trigger Sidebar & Badges Update
        if (typeof renderSideComments === 'function') renderSideComments();
        if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
        
        // Update History for new tab content
        pushSnapshot(editor.innerHTML);
    }

    function syncToStorage() {
        if (currentActiveMedia) {
            const editor = document.getElementById('shared-editor');
            const content = editor.innerHTML; 
            document.getElementById('storage-' + currentActiveMedia).value = content;
            
            // [NEW] Ensure storage in Free Input also stays decoded if possible
            // (Actually let it stay as is, activateSharedTab handles the view)

            // Auto-update counter on typing
            updateCharCounter();
        }
    }

    async function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            const status = document.getElementById('upload-status');
            const preview = document.getElementById('editor-content'); // TARGET NEW INNER DIV
            
            status.innerText = "Processing file...";
            const formData = new FormData();
            formData.append('file', selectedFile);
            
            try {
                const res = await fetch('index.php?controller=request&action=upload', { method:'POST', body:formData });
                const data = await res.json();
                if (data.success) {
                    status.innerHTML = "✔ " + selectedFile.name + " ready.";
                    preview.innerHTML = data.preview;
                    lastPreviewHtml = data.preview;
                    
                    // SELF-HEAL: Repair Broken Tables on new upload
                    const corruptSpans = document.querySelectorAll('tr > span.inline-comment, tbody > span.inline-comment, table > span.inline-comment');
                    if (corruptSpans.length > 0) {
                        corruptSpans.forEach(span => {
                            const parent = span.parentNode;
                            while (span.firstChild) parent.insertBefore(span.firstChild, span);
                            parent.removeChild(span);
                        });
                    }

                    // Render comments and badges for newly uploaded file
                    renderSideComments();
                    updateSheetTabBadges();
                } else {
                    status.innerHTML = "❌ " + data.message;
                    lastPreviewHtml = '';
                }
            } catch(e) {
                status.innerText = "❌ Error uploading file.";
            }
        }
    }

    // --- MAKER REVISION TRACKING ENGINE ---
    // Ported from review.php - Full handleBeforeInput with insertion, deletion, and paste tracking
    let isMakerEditing = true; // ALWAYS ON

    // [HELPER] Create strikethrough span for selected non-draft text before replacement
    function createStrikethroughForSelection(range) {
        if (!range || range.collapsed) return;
        
        const selectedText = range.toString();
        if (!selectedText || selectedText.trim().length === 0) return;
        
        const commonAncestor = range.commonAncestorContainer;
        const ancestorEl = commonAncestor.nodeType === Node.TEXT_NODE ? commonAncestor.parentElement : commonAncestor;
        const insideDraft = ancestorEl?.closest?.('.revision-span.draft');
        if (insideDraft) {
            const owner = insideDraft.getAttribute('data-comment-user') || insideDraft.getAttribute('data-user') || '';
            if (owner === (window.CURRENT_USER_NAME || '')) return;
        }
        
        if (ancestorEl?.closest?.('.deletion-span')) return;
        
        let textToTrack = '';
        const fragment = range.cloneContents();
        const tempDiv = document.createElement('div');
        tempDiv.appendChild(fragment);
        
        function collectNonDraftText(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                textToTrack += node.textContent;
                return;
            }
            if (node.nodeType === Node.ELEMENT_NODE) {
                if (node.classList.contains('revision-span') && node.classList.contains('draft')) {
                    const draftOwner = node.getAttribute('data-comment-user') || node.getAttribute('data-user') || '';
                    if (draftOwner === (window.CURRENT_USER_NAME || '')) return;
                }
                if (node.classList.contains('deletion-span')) return;
                for (const child of node.childNodes) {
                    collectNonDraftText(child);
                }
            }
        }
        collectNonDraftText(tempDiv);
        
        if (!textToTrack || textToTrack.trim().length === 0) return;
        
        const revId = `rev-${Date.now()}-del-${Math.random().toString(36).substr(2, 5)}`;
        const strikeSpan = document.createElement('span');
        strikeSpan.id = revId;
        strikeSpan.className = 'revision-span deletion-span';
        strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
        strikeSpan.setAttribute('data-is-deletion', 'true');
        strikeSpan.setAttribute('data-deleted-text', textToTrack);
        strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'Maker');
        strikeSpan.setAttribute('data-comment-dept', window.CURRENT_USER_ROLE || '');
        strikeSpan.setAttribute('data-comment-role', window.CURRENT_USER_ROLE || '');
        strikeSpan.setAttribute('data-comment-job', window.CURRENT_USER_JOB_FUNCTION || '');
        strikeSpan.setAttribute('data-comment-time', new Date().toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}));
        strikeSpan.contentEditable = 'false';
        strikeSpan.textContent = textToTrack;
        
        range.insertNode(strikeSpan);
        range.setStartAfter(strikeSpan);
        
        if (window._sidebarTimer) clearTimeout(window._sidebarTimer);
        window._sidebarTimer = setTimeout(renderSideComments, 300);
    }

    // [LIVE SIDEBAR] Update or create a draft card in the sidebar as user types
    function updateDraftCard(revId, text) {
        if (typeof DELETED_SPANS !== 'undefined' && DELETED_SPANS.has(revId)) return;
        
        const cleanText = text.replace(/\u200B/g, '').trim();
        if (!cleanText) {
            const existingCard = document.getElementById(`card-${revId}`);
            if (existingCard) existingCard.remove();
            return;
        }
        
        const list = document.getElementById('comment-list');
        const sidebar = document.getElementById('comment-sidebar');
        if (!list || !sidebar) return;
        
        sidebar.style.display = 'block';
        
        let card = document.getElementById(`card-${revId}`);
        if (card) {
            // Try to find the text body by class, then fallback to last child div
            let bodyEl = card.querySelector('.draft-body');
            if (!bodyEl) {
                // Fallback: find the text content div (second major div child)
                const divs = card.querySelectorAll('div');
                for (let i = divs.length - 1; i >= 0; i--) {
                    if (divs[i].style.lineHeight === '1.4' || divs[i].style.wordWrap === 'break-word') {
                        bodyEl = divs[i];
                        break;
                    }
                }
            }
            if (bodyEl) bodyEl.textContent = cleanText;
            return;
        }
        
        card = document.createElement('div');
        card.className = 'comment-card';
        card.id = `card-${revId}`;
        card.style.cssText = 'background:white; border:1px solid #fecaca; border-radius:10px; padding:12px; margin-bottom:10px; box-shadow:0 1px 2px rgba(0,0,0,0.02); cursor:pointer; transition:all 0.2s; border-left:3px solid #ef4444;';
        card.setAttribute('data-for', revId);
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:24px; height:24px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:#ef4444; font-weight:bold; border:1px solid #fecaca; flex-shrink:0;">✎</div>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:#334155;">New Revision</div>
                        <div style="font-size:10px; color:#94a3b8;">Draft • ${CURRENT_USER_NAME}</div>
                    </div>
                </div>
                <button class="btn-delete-draft" title="Hapus revisi ini" style="background:none; border:none; cursor:pointer; color:#ef4444; opacity:0.5; padding:2px 4px; font-size:14px; line-height:1; transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
            <div class="draft-body" style="font-size:12px; color:#ef4444; line-height:1.4; word-wrap:break-word; font-weight:500;">${cleanText}</div>
        `;
        
        // DELETE BUTTON: Remove draft span + card + blacklist
        const deleteBtn = card.querySelector('.btn-delete-draft');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const span = document.getElementById(revId);
                if (span) {
                    window._suppressRevisionPreservation = true;
                    span.remove();
                    window._suppressRevisionPreservation = false;
                }
                card.remove();
                if (typeof DELETED_SPANS !== 'undefined') DELETED_SPANS.add(revId);
                if (typeof renderSideComments === 'function') renderSideComments();
                if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
            });
        }
        
        // CLICK-TO-SCROLL: Navigate to the revision span in editor
        card.addEventListener('click', (e) => {
            if (e.target.closest('.btn-delete-draft')) return;
            const span = document.getElementById(revId);
            if (span) {
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
                card.style.borderColor = '#ef4444';
                card.style.transform = 'scale(1.02)';
            }
        });
        
        list.insertBefore(card, list.firstChild);
    }

    // [MAIN ENGINE] Handle all input events for revision tracking
    function handleMakerBeforeInput(e) {
        if (!isMakerEditing) return;

        // TARGET VALIDATION: Only handle input for actual editor panes
        const target = e.target;
        const isStandardInput = (target.tagName === 'TEXTAREA' || target.tagName === 'INPUT');
        const isSidebarOrModal = target.closest('#comment-sidebar') || target.closest('#comment-modal');
        const isInsideEditor = target.closest('#editor-content') || target.closest('#shared-editor') || target.id === 'editor-content' || target.id === 'shared-editor';

        if (isStandardInput || isSidebarOrModal || !isInsideEditor) return;

        // SUPPRESS MutationObserver during our DOM changes (prevents renderSideComments from clearing draft cards)
        isInternalChange = true;

        // [FIX UNDO] Save snapshot BEFORE changing DOM
        // Without this, undo doesn't restore to the pre-typing state
        const activeEdBefore = getCurrentEditor ? getCurrentEditor() : (document.getElementById('editor-content') || document.getElementById('shared-editor'));
        if (activeEdBefore) pushSnapshot(activeEdBefore.innerHTML);
        // Reset after a longer delay to prevent sidebar rebuild while actively typing
        clearTimeout(window._internalChangeTimer);
        window._internalChangeTimer = setTimeout(() => isInternalChange = false, 2000);

        // A. HANDLE INSERTION (Typing)
        if ((e.inputType === 'insertText' || e.inputType === 'insertCompositionText') && e.data) {
            e.preventDefault();
            window._suppressRevisionPreservation = true;

            const selection = window.getSelection();
            if (!selection.rangeCount) return;

            const range = selection.getRangeAt(0);
            if (!range.collapsed) {
                createStrikethroughForSelection(range);
                range.deleteContents();
            }

            // Find if we are inserting inside an existing revision span
            let el = selection.anchorNode;
            if (el.nodeType === 3) el = el.parentNode;

            let targetSpan = null;
            let walker = el;
            while (walker && walker !== document.body && !walker.isContentEditable) {
                if (walker.nodeType === 1 && walker.classList.contains('revision-span') && walker.style.color === 'red') {
                    if (DELETED_SPANS.has(walker.id) || !document.contains(walker)) {
                        targetSpan = null;
                    } else {
                        targetSpan = walker;
                    }
                    break;
                }
                walker = walker.parentNode;
            }
            if (el.nodeType === 1 && el.classList.contains('revision-span')) targetSpan = el;

            if (targetSpan) {
                // Append to existing span
                const textNode = targetSpan.childNodes[0] || targetSpan.appendChild(document.createTextNode(''));
                if (textNode.nodeType === 3) {
                    textNode.nodeValue += e.data;
                } else {
                    targetSpan.textContent += e.data;
                }
                const cursorNode = targetSpan.lastChild || targetSpan;
                const range2 = document.createRange();
                if (cursorNode.nodeType === 3) {
                    range2.setStart(cursorNode, cursorNode.textContent.length);
                    range2.setEnd(cursorNode, cursorNode.textContent.length);
                } else {
                    range2.selectNodeContents(targetSpan);
                    range2.collapse(false);
                }
                selection.removeAllRanges();
                selection.addRange(range2);
                const cleanText = targetSpan.innerText.replace(/\u200B/g, '');
                updateDraftCard(targetSpan.id, cleanText);
            } else {
                // CREATE NEW RED SPAN
                const revId = "rev-" + Date.now() + "-" + Math.random().toString(36).substr(2, 5);
                const span = document.createElement("span");
                span.className = "revision-span draft";
                span.id = revId;
                span.setAttribute('data-comment-id', revId);
                span.style.color = "red";
                span.textContent = e.data;
                span.setAttribute('data-user', CURRENT_USER_NAME);
                span.setAttribute('data-time', new Date().toISOString());

                const range3 = selection.getRangeAt(0);
                if (!range3.collapsed) range3.deleteContents();
                range3.insertNode(span);

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
                updateDraftCard(revId, e.data);
            }
            
            // Trigger history snapshot
            const activeEd = document.getElementById('editor-content') || document.getElementById('shared-editor');
            if (activeEd) pushSnapshot(activeEd.innerHTML);
            
            window._suppressRevisionPreservation = false;
            // Sync storage for free input
            if (typeof syncToStorage === 'function') syncToStorage();
        }

        // A2. HANDLE PASTE (Ctrl+V)
        if (e.inputType === 'insertFromPaste') {
            e.preventDefault();
            const pastedText = (e.dataTransfer && e.dataTransfer.getData('text/plain')) || '';
            if (!pastedText.trim()) return;

            window._suppressRevisionPreservation = true;
            const selection = window.getSelection();
            if (!selection.rangeCount) { window._suppressRevisionPreservation = false; return; }

            const range = selection.getRangeAt(0);
            if (!range.collapsed) {
                createStrikethroughForSelection(range);
                range.deleteContents();
            }

            let el = selection.anchorNode;
            if (el && el.nodeType === 3) el = el.parentNode;

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
                const textNode = targetSpan.childNodes[0] || targetSpan.appendChild(document.createTextNode(''));
                if (textNode.nodeType === 3) {
                    textNode.nodeValue += pastedText;
                } else {
                    targetSpan.textContent += pastedText;
                }
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
                updateDraftCard(targetSpan.id, cleanText);
            } else {
                const revId = "rev-" + Date.now() + "-" + Math.random().toString(36).substr(2, 5);
                const span = document.createElement("span");
                span.className = "revision-span draft";
                span.id = revId;
                span.setAttribute('data-comment-id', revId);
                span.style.color = "red";
                span.textContent = pastedText;
                span.setAttribute('data-user', CURRENT_USER_NAME);
                span.setAttribute('data-time', new Date().toISOString());

                range.insertNode(span);
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
                updateDraftCard(revId, pastedText);
            }
            
            const activeEd = document.getElementById('editor-content') || document.getElementById('shared-editor');
            if (activeEd) pushSnapshot(activeEd.innerHTML);
            
            window._suppressRevisionPreservation = false;
            if (typeof syncToStorage === 'function') syncToStorage();
        }

        // B. HANDLE DELETION (Strikethrough Tracking)
        if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) return;
            const range = sel.getRangeAt(0);

            // If cursor is inside own draft revision-span, allow normal deletion
            const cursorSpan = sel.anchorNode?.parentElement?.closest?.('.revision-span.draft');
            const spanOwner = cursorSpan ? (cursorSpan.getAttribute('data-comment-user') || cursorSpan.getAttribute('data-user') || '') : '';
            if (cursorSpan && spanOwner === (window.CURRENT_USER_NAME || '')) {
                // Let browser handle the delete natively, then clean up
                const spanId = cursorSpan.id; // Capture ID before potential DOM changes
                
                // Use 50ms delay to let browser finish its native deletion first
                setTimeout(() => {
                    window._suppressRevisionPreservation = true;
                    isInternalChange = true;
                    
                    // TARGETED CLEANUP: Check the specific span we were editing
                    const spanRef = document.getElementById(spanId);
                    if (spanRef) {
                        let txt = (spanRef.innerText || spanRef.textContent || '').replace(/[\u200B\u00A0\n\r]/g, '').trim();
                        if (txt.length === 0) {
                            spanRef.remove();
                            const card = document.getElementById(`card-${spanId}`);
                            if (card) card.remove();
                        } else {
                            updateDraftCard(spanId, txt);
                        }
                    } else {
                        // Span was already removed by browser
                        const card = document.getElementById(`card-${spanId}`);
                        if (card) card.remove();
                    }
                    
                    // Secondary sweep for any other empty spans
                    document.querySelectorAll('.revision-span.draft').forEach(s => {
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
                    setTimeout(() => isInternalChange = false, 100);
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

            // STRIKETHROUGH TRACKING: Prevent actual deletion
            e.preventDefault();
            window._suppressRevisionPreservation = true;

            let textToStrike = '';
            let targetNode = null;
            let targetOffset = 0;

            if (!range.collapsed) {
                textToStrike = range.toString();
            } else {
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
                    if (e.inputType === 'deleteContentBackward') {
                        let prevNode = offset > 0 ? node.childNodes[offset - 1] : null;
                        if (prevNode && prevNode.classList && prevNode.classList.contains('deletion-span')) {
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

            // Check if already inside a strikethrough (avoid double-strike)
            const anchorEl = sel.anchorNode?.nodeType === Node.TEXT_NODE ? sel.anchorNode.parentElement : sel.anchorNode;
            const existingStrike = anchorEl?.closest?.('.deletion-span');
            if (existingStrike) {
                window._suppressRevisionPreservation = false;
                return;
            }

            // CONSECUTIVE DELETE: Check for adjacent deletion span to extend
            let adjacentStrike = null;
            if (range.collapsed && targetNode) {
                if (e.inputType === 'deleteContentBackward') {
                    let nextSib = targetNode.nextSibling;
                    if (nextSib && nextSib.classList && nextSib.classList.contains('deletion-span')) {
                        adjacentStrike = nextSib;
                    }
                } else if (e.inputType === 'deleteContentForward') {
                    let prevSib = targetNode.previousSibling;
                    if (prevSib && prevSib.classList && prevSib.classList.contains('deletion-span')) {
                        adjacentStrike = prevSib;
                    }
                }
            }

            if (adjacentStrike && range.collapsed && targetNode) {
                if (e.inputType === 'deleteContentBackward') {
                    const before = targetNode.textContent.substring(0, targetOffset - 1);
                    const after = targetNode.textContent.substring(targetOffset);
                    targetNode.textContent = before + after;
                    adjacentStrike.textContent = textToStrike + adjacentStrike.textContent;
                    adjacentStrike.setAttribute('data-deleted-text', adjacentStrike.textContent);
                    if (targetNode.textContent.length === 0) targetNode.remove();
                } else {
                    const before = targetNode.textContent.substring(0, targetOffset);
                    const after = targetNode.textContent.substring(targetOffset + 1);
                    targetNode.textContent = before + after;
                    adjacentStrike.textContent = adjacentStrike.textContent + textToStrike;
                    adjacentStrike.setAttribute('data-deleted-text', adjacentStrike.textContent);
                    if (targetNode.textContent.length === 0) targetNode.remove();
                }
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
                // SELECTION: Create new strikethrough
                const revId = `rev-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
                const strikeSpan = document.createElement('span');
                strikeSpan.id = revId;
                strikeSpan.className = 'revision-span deletion-span';
                strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
                strikeSpan.setAttribute('data-is-deletion', 'true');
                strikeSpan.setAttribute('data-deleted-text', textToStrike);
                strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'Maker');
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
                // SINGLE CHAR: Create new strikethrough
                const revId = `rev-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
                const strikeSpan = document.createElement('span');
                strikeSpan.id = revId;
                strikeSpan.className = 'revision-span deletion-span';
                strikeSpan.style.cssText = 'color: #ef4444; text-decoration: line-through; text-decoration-color: #ef4444; opacity: 0.7;';
                strikeSpan.setAttribute('data-is-deletion', 'true');
                strikeSpan.setAttribute('data-deleted-text', textToStrike);
                strikeSpan.setAttribute('data-comment-user', window.CURRENT_USER_NAME || 'Maker');
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
            if (typeof syncToStorage === 'function') syncToStorage();
        }
    }

    async function submitUpdate(isDraft = false) {
        // VALIDATION: Soft Warning for Unresolved Comments (Skip for Drafts)
        if (!isDraft) {
            const unresolvedCount = document.querySelectorAll('.inline-comment, .revision-span').length;
            
            if (unresolvedCount > 0) {
                const plural = unresolvedCount > 1 ? 'catatan revisi' : 'catatan revisi';
                const message = `Anda masih memiliki <strong>${unresolvedCount} ${plural}</strong> yang belum ditandai selesai.<br><br>Yakin ingin mengirim ulang sekarang?`;
                
                // Show custom confirmation modal
                showCustomConfirm('⚠️ Perhatian', message, () => {
                    // User clicked OK - proceed with submission
                    proceedWithSubmit(isDraft);
                }, () => {
                    // User clicked Cancel - do nothing
                });
                return; // Stop here, callback will handle submission
            }
        }

        // If no warning needed, proceed directly
        proceedWithSubmit(isDraft);
    }

    async function proceedWithSubmit(isDraft) {
        syncToStorage();
        
        const title = "<?php echo addslashes($request['title']); ?>"; 
        const requestId = document.getElementById('request_id').value;
        const ticketId = document.getElementById('script_number').value;
        const jenis = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value).join(',');
        const spv = document.getElementById('selected_spv').value;
        const note = document.getElementById('maker_note').value.trim();
        const inputMode = document.getElementById('input_mode').value;

        // ===== COMPREHENSIVE VALIDATION =====
        if (!isDraft) {
            // Validasi 1: Jenis
            if (!jenis) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Jenis (Konvensional/Syariah)!', 'error');
                return;
            }

            // Validasi 2: Kategori
            const kategoriCheck = Array.from(document.querySelectorAll('input[name="kategori"]:checked'));
            if (kategoriCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Kategori!', 'error');
                return;
            }

            // Validasi 3: Produk
            const produkCheck = Array.from(document.querySelectorAll('input[name="produk"]:checked'));
            if (produkCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Produk!', 'error');
                return;
            }

            // Validasi 4: Media
            const mediaCheck = Array.from(document.querySelectorAll('input[name="media"]:checked'));
            if (mediaCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Media!', 'error');
                return;
            }

            // Validasi 5: SPV
            if (!spv) {
                showModal('Validasi Gagal', 'Mohon pilih Supervisor untuk approval!', 'error');
                return;
            }

            // Validasi 6: Catatan Perbaikan
            if (!note) {
                showModal('Validasi Gagal', 'Mohon isi Catatan Perbaikan untuk menjelaskan apa yang telah diperbaiki!', 'error');
                return;
            }

            // Validasi 7: Content (File Upload or Free Input)
            if (inputMode === 'FILE_UPLOAD') {
                const editorContent = document.getElementById('editor-content');
                if (!editorContent || !editorContent.innerHTML.trim() || editorContent.innerHTML.trim() === '<p style="color:#999; font-style:italic;">No file preview available.</p>') {
                    showModal('Validasi Gagal', 'Konten script masih kosong. Mohon upload file atau isi konten!', 'error');
                    return;
                }
            } else {
                // Free Input Mode - check if at least one media has content
                let hasContent = false;
                mediaCheck.forEach(m => {
                    const storage = document.getElementById('storage-' + m.value);
                    if (storage && storage.value.trim()) hasContent = true;
                });
                if (!hasContent) {
                    showModal('Validasi Gagal', 'Mohon isi script content untuk minimal satu media yang dipilih!', 'error');
                    return;
                }

                // Validasi 8: Character Limit Check
                // Loop through selected media and check length against limits
                let limitError = null;
                mediaCheck.forEach(m => {
                    if (limitError) return; // Stop if error found
                    
                    const mediaName = m.value;
                    const storage = document.getElementById('storage-' + mediaName);
                    // Create temp div to get innerText length (ignore HTML)
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = storage ? storage.value : '';
                    const len = tempDiv.innerText.length;
                    
                    const limit = MEDIA_LIMITS[mediaName] || 2000;
                    
                    if (len > limit) {
                        limitError = `Konten untuk media <strong>${mediaName}</strong> melebihi batas karakter (${len} / ${limit}).<br><br>Mohon persingkat script Anda sebelum mengirim.`;
                        // Switch to that tab so user can see it
                        activateSharedTab(mediaName);
                    }
                });

                if (limitError) {
                    showModal('Batas Karakter Terlampaui', limitError, 'error');
                    return; // CRITICAL: Stop submission but keep data intact
                }
            }
        }

        // Collect Produk
        let selectedProduk = Array.from(document.querySelectorAll('input[name="produk"]:checked'))
            .filter(c => c.value !== 'Others')
            .map(c => c.value);
        const otherKonv = document.getElementById('prod_konv_other');
        if (otherKonv && otherKonv.style.display !== 'none' && otherKonv.value.trim()) selectedProduk.push(otherKonv.value.trim());

        // Collect Kategori & Media
        const kategori = Array.from(document.querySelectorAll('input[name="kategori"]:checked')).map(c => c.value).join(',');
        const selMedNodes = Array.from(document.querySelectorAll('input[name="media"]:checked'));
        const mediaNames = selMedNodes.map(c => c.value).join(',');

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('script_number', ticketId);
        formData.append('jenis', jenis);
        formData.append('produk', selectedProduk.join(','));
        formData.append('kategori', kategori);
        formData.append('media', mediaNames);
        formData.append('title', document.getElementById('title')?.value || '');
        formData.append('input_mode', document.getElementById('input_mode').value);
        formData.append('selected_spv', spv);
        formData.append('maker_note', note);
        
        // Start Date
        const startDate = document.getElementById('start_date').value;
        if (startDate) formData.append('start_date', startDate);
        
        // DRAFT FLAG
        if (isDraft) formData.append('is_draft', '1');

        if (inputMode === 'FILE_UPLOAD') {
            if (selectedFile) formData.append('script_file', selectedFile);
            
            // CAPTURE CONTENT: PARSE SHEETS MANUALLY (Robust match for Reviewer-style tabs)
            const editorRoot = document.getElementById('editor-content');
            
            // --- SANITIZATION: CLEAN DISHES BEFORE RESUBMIT ---
            // Remove "Zombie Spans" (Black text with revision class) & Reset IDs
            try {
                const zombies = editorRoot.querySelectorAll('.revision-span');
                zombies.forEach(z => {
                   // Check strictly for RED (Robust)
                   const s = z.style.color ? z.style.color.toLowerCase().replace(/\s/g, '') : '';
                   const isRed = s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)';
                   
                   if (!isRed) {
                       // ZOMBIE DETECTED: UNWRAP (Remove span, keep text)
                       const parent = z.parentNode;
                       while (z.firstChild) parent.insertBefore(z.firstChild, z);
                       parent.removeChild(z);
                   } else {
                       // VALID RED TEXT: RESET ID
                       // Ensure fresh session for Reviewer
                       z.removeAttribute('data-comment-id');
                       z.removeAttribute('id');
                   }
                });
            } catch(e) {
                console.warn("Sanitization warning:", e);
            }
            // --- END SANITIZATION ---

            // Support both .sheet-pane (Legacy) and .media-pane (New)
            const sheets = editorRoot.querySelectorAll('.sheet-pane, .media-pane');
            
            if (sheets.length > 0) {
                // Multi-sheet structure found
                let shData = [];
                sheets.forEach(sh => {
                    const shId = sh.id;
                    let shName = "Sheet"; 
                    
                    // Strategy 1: Find button by onclick (Reviewer Style)
                    // Pattern: changeSheet('tab-media-0') or similar
                    let btn = editorRoot.querySelector(`div[onclick*="'${shId}'"], button[onclick*="'${shId}'"]`);
                    
                    // Strategy 2: ID Correlation (tab-media-0 -> btn-tab-media-0)
                    if (!btn && shId.startsWith('tab-media-')) {
                        btn = document.getElementById('btn-' + shId);
                    }

                    if (btn) {
                        shName = btn.innerText;
                    } else {
                        shName = sh.getAttribute('data-name') || shName;
                    }
                    
                    shData.push({
                        sheet_name: shName.trim(),
                        content: sh.innerHTML // Save inner content only
                    });
                });
                formData.append('script_content', JSON.stringify(shData));
            } else {
                // Fallback: No sheets found (Legacy Single View or non-Excel)
                // Just take innerHTML but try to avoid taking the Editor Wrapper if possible
                // For Word doc, it's usually just .word-preview class inside
                const currentHtml = editorRoot.innerHTML;
                formData.append('script_content', currentHtml);
            }
        } else {
            let contentData = [];
            selMedNodes.forEach(c => {
                const text = document.getElementById('storage-' + c.value).value;
                contentData.push({ sheet_name: c.value, content: text });
            });
            formData.append('script_content', JSON.stringify(contentData));
        }

        

        const confirmTitle = isDraft ? "Simpan Draft" : "Konfirmasi Pengiriman";
        const confirmMsg = isDraft 
            ? "Simpan perubahan sementara (Draft)?<br><span style='font-size:12px; color:#64748b;'>Status data tidak akan berubah.</span>" 
            : "Yakin ingin mengirim ulang revisi ini ke Supervisor?";

        showCustomConfirm(confirmTitle, confirmMsg, async () => {
            try {
                const res = await fetch('?controller=request&action=update', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    if (isDraft) {
                        showSuccess('Draft Tersimpan', 'Perubahan Anda telah disimpan sebagai draft.', true);
                    } else {
                        showSuccess('Berhasil Dikirim!', 'Revisi script berhasil dikirim ulang ke Supervisor untuk approval.', false);
                    }
                } else {
                    showModal('Gagal', data.message || data.error || 'Terjadi kesalahan saat menyimpan.', 'error');
                }
            } catch(e) {
                showModal('Error', 'Terjadi kesalahan sistem. Mohon coba lagi.', 'error');
            }
        });
    }

    function reloadPage(reload) {
         if (reload) {
            setTimeout(() => window.location.reload(), 2000);
        } else {
            setTimeout(() => window.location.href = 'index.php', 2000);
        }
    }

    // ===== SWEETALERT2 IMPLEMENTATION =====

    function showModal(title, message, type = 'success') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                html: message, 
                icon: type,
                confirmButtonText: 'OK',
                confirmButtonColor: 'var(--primary-red)'
            });
        } else {
            alert(title + "\n" + message.replace(/<[^>]*>?/gm, ''));
        }
    }

    function showSuccess(title, message, reload = false) {
        const plainMsg = title + "\n" + message.replace(/<[^>]*>?/gm, '');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                html: message,
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: 'var(--primary-red)'
            }).then((result) => {
                if (result.isConfirmed || result.isDismissed) {
                     hasUnsavedChanges = false; 
                     window.onbeforeunload = null; 
                     reloadPage(reload);
                }
            });
        } else {
             alert(plainMsg);
             hasUnsavedChanges = false; 
             window.onbeforeunload = null; 
             reloadPage(reload);
        }
    }

    function closeModal() {
        // No-op for SweetAlert (handled internally)
    }

    function showCustomConfirm(title, message, onConfirm, onCancel) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (onConfirm) onConfirm();
                } else {
                    if (onCancel) onCancel();
                }
            });
        } else {
            if (confirm(title + "\n" + message)) {
               if (onConfirm) onConfirm(); 
            } else {
               if (onCancel) onCancel();
            }
        }
    }

    // --- UNSAVED CHANGES PROTECTION ---
    let hasUnsavedChanges = false;

    function markUnsaved() {
        hasUnsavedChanges = true;
    }

    // Attach to history actions
    function pushSnapshot(html) {
        const last = historyStack.undo[historyStack.undo.length - 1];
        if (last && last.action === 'content-change' && last.html === html) return;
        
        historyStack.undo.push({ action: 'content-change', html: html });
        historyStack.redo = []; 
        updateUndoRedoUI();
        markUnsaved(); // Mark dirty
    }

    function performUndo() {
        if (historyStack.undo.length <= 1) return; 

        const lastAction = historyStack.undo.pop();
        historyStack.redo.push(lastAction);
        
        const prevState = historyStack.undo[historyStack.undo.length - 1];
        
        if (lastAction.action === 'content-change') {
            // SET FLAG to prevent mutation observer from triggering
            isInternalChange = true;
            applyContentRestoration(prevState.html);
            // Small delay before re-enabling observer
            setTimeout(() => { isInternalChange = false; }, 50);
        }
        
        renderSideComments(); 
        updateSheetTabBadges();
        updateUndoRedoUI();
        markUnsaved(); 
    }

    function performRedo() {
        const nextAction = historyStack.redo.pop();
        if (!nextAction) return;

        historyStack.undo.push(nextAction);

        if (nextAction.action === 'content-change') {
            // SET FLAG to prevent mutation observer from triggering
            isInternalChange = true;
            applyContentRestoration(nextAction.html);
            // Small delay before re-enabling observer
            setTimeout(() => { isInternalChange = false; }, 50);
        }

        renderSideComments();
        updateSheetTabBadges();
        updateUndoRedoUI();
        markUnsaved(); 
    }

    // Attach to Resolve
    // Note: resolveComment already modifies DOM which triggers observer -> pushSnapshot -> markUnsaved
    // So explicit markUnsaved in resolveComment might be redundant but safe.

    // BROWSER WARNING
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            // Standard way to trigger browser confirmation dialog
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    // Clear flag on valid submit
    window.addEventListener('submit', () => { hasUnsavedChanges = false; });

    // ==========================================
    // COMMENT & HIGHLIGHT LOGIC (Ported from Review)
    // ==========================================
    
    let savedRange = null;

    function addComment() {
        // Toggle red/black buttons if needed or just use default
        
        const selection = window.getSelection();
        if (selection.rangeCount === 0 || selection.toString().length === 0) {
            showCustomAlert("⚠️ Tidak Ada Teks Dipilih", "Mohon blok/sorot teks terlebih dahulu baru klik tombol 'Comment'.");
            return;
        }

        savedRange = selection.getRangeAt(0);

        // Show Modal (Simple Prompt for now, or Custom Modal)
        // Using simple prompt for MVP consistency with Review, but Review uses a modal div "comment-modal"
        // Since edit.php doesn't have the modal HTML, let's inject it dynamically or use Prompt
        
        // BETTER: Inject Modal HTML if missing
        if (!document.getElementById('comment-modal')) {
            const modalHtml = `
            <div id="comment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:white; padding:20px; border-radius:8px; width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                    <h3 style="margin-top:0;">Add Comment</h3>
                    <textarea id="comment-input" style="width:100%; height:100px; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;" placeholder="Type your comment..."></textarea>
                    <div style="text-align:right; gap:10px; display:flex; justify-content:flex-end;">
                        <button onclick="closeCommentModal()" style="padding:8px 16px; background:#ccc; border:none; border-radius:4px; cursor:pointer;">Cancel</button>
                        <button onclick="submitComment()" style="padding:8px 16px; background:var(--primary-red); color:white; border:none; border-radius:4px; cursor:pointer;">Save</button>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        document.getElementById('comment-modal').style.display = 'flex';
        document.getElementById('comment-input').value = '';
        document.getElementById('comment-input').focus();
    }

    function closeCommentModal() {
        const modal = document.getElementById('comment-modal');
        if(modal) modal.style.display = 'none';
        savedRange = null;
    }

    function submitComment() {
        const inp = document.getElementById('comment-input');
        const text = inp.value.trim();
        if (!text) { alert("Komentar tidak boleh kosong."); return; }
        
        if (!savedRange) { 
            closeCommentModal(); return; 
        }

        // SAFETY CHECK: Prevent Multi-Cell Selection
        const clone = savedRange.cloneContents();
        if (clone.querySelector('td, th, tr, tbody, table')) {
            showCustomAlert("⚠️ Aksi Dibatasi", "Tidak dapat memberi komentar lintas kolom tabel.<br>Mohon blok teks di dalam satu kolom saja agar susunan tabel tetap rapi.");
            closeCommentModal();
            return;
        }

        // Generate ID
        const commentId = 'c' + Date.now();
        // Use global CURRENT_USER_NAME (from PHP session)
        
        const timeObj = new Date();
        const commentTime = timeObj.toLocaleString('id-ID', { day:'numeric', month:'short', hour: '2-digit', minute:'2-digit' }).replace('.', ':');

        try {
            // MANUAL DOM WRAPPING
            const wrapper = document.createElement('span');
            wrapper.className = 'inline-comment';
            wrapper.setAttribute('data-comment-id', commentId);
            wrapper.setAttribute('data-comment-text', text);
            wrapper.setAttribute('data-comment-user', CURRENT_USER_NAME);
            wrapper.setAttribute('data-comment-time', commentTime);
            wrapper.title = text;
            wrapper.style.backgroundColor = 'yellow'; // Fallback
            
            const fragment = savedRange.extractContents();
            wrapper.appendChild(fragment);
            savedRange.insertNode(wrapper);
            
            window.getSelection().removeAllRanges();
            
            // Render Sidebar
            renderSideComments();
            updateSheetTabBadges();
            
            hasUnsavedChanges = true;
            closeCommentModal();
        } catch (e) {
            console.error(e);
            alert("Gagal menyimpan komentar. Pastikan blok teks valid.");
        }
    }

    // Custom Alert Helper
    function showCustomAlert(title, message) {
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

    function showCustomConfirm(title, message, onConfirm, onCancel) {
        const existing = document.getElementById('custom-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'custom-confirm-overlay';
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        `;
        
        overlay.innerHTML = `
            <div style="background:white; padding:24px; border-radius:8px; width:400px; max-width:90%; box-shadow:0 4px 12px rgba(0,0,0,0.15); text-align:center;">
                <h3 style="margin:0 0 16px 0; color:#1e293b; font-size:16px; font-weight:600;">${title}</h3>
                <p style="margin:0 0 20px 0; color:#64748b; font-size:14px; line-height:1.5;">${message}</p>
                <div style="display:flex; gap:8px; justify-content:center;">
                    <button id="confirm-cancel-btn" style="background:white; color:#64748b; border:1px solid #cbd5e1; padding:8px 16px; border-radius:4px; font-size:13px; font-weight:500; cursor:pointer;">
                        Batal
                    </button>
                    <button id="confirm-ok-btn" style="background:#dc2626; color:white; border:none; padding:8px 16px; border-radius:4px; font-size:13px; font-weight:500; cursor:pointer;">
                        Ya, Kirim
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);

        document.getElementById('confirm-cancel-btn').onclick = () => {
            overlay.remove();
            if (onCancel) onCancel();
        };
        
        document.getElementById('confirm-ok-btn').onclick = () => {
            overlay.remove();
            if (onConfirm) onConfirm();
        };
    }

    // INIT ON LOAD
    document.addEventListener('DOMContentLoaded', () => {
        console.log("Initializing Editor...");

        // 1. Track Inputs for Unsaved Changes
        const tracked = ['shared-editor', 'maker_note'];
        tracked.forEach(id => {
            const trackedEl = document.getElementById(id);
            if (trackedEl) trackedEl.addEventListener('input', () => markUnsaved());
        });

        // 2. GLOBAL EVENT DELEGATION for Tab Buttons (critical fix for contenteditable blocking clicks)
        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-sheet');
            if (btn) {
                const onclickAttr = btn.getAttribute('onclick');
                if (onclickAttr) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('Tab button clicked:', btn);

                    const matchChange = onclickAttr.match(/changeSheet\('([^']+)'\)/);
                    if (matchChange && matchChange[1]) {
                        window.changeSheet(matchChange[1]);
                        return false;
                    }
                    const matchActivate = onclickAttr.match(/activateSharedTab\('([^']+)'\)/);
                    if (matchActivate && matchActivate[1]) {
                        window.activateSharedTab(matchActivate[1]);
                        return false;
                    }
                    const matchOpen = onclickAttr.match(/openMediaTab\([^,]+,\s*'([^']+)'\)/);
                    if (matchOpen && matchOpen[1]) {
                         window.changeSheet(matchOpen[1]);
                         return false;
                    }
                }
            }
        }, true);

        // 3. ATTACH GLOBAL beforeinput LISTENER (Maker Revision Tracking)
        if (!window._hasMakerBeforeInputListener) {
            document.addEventListener('beforeinput', (e) => {
                if (isMakerEditing && typeof handleMakerBeforeInput === 'function') {
                    handleMakerBeforeInput(e);
                }
            });
            window._hasMakerBeforeInputListener = true;
            console.log('[MAKER] Global beforeinput listener attached for revision tracking');
        }

        // 4. Initial Render Logic (Delayed slightly to ensure content paint)
        setTimeout(() => {
            if (document.getElementById('manual-panel').style.display === 'block') {
                updateFreeInputTabs();
                updateTabBadges();
            }
            
            if (document.getElementById('upload-panel').style.display === 'block') {
                renderSideComments();
                updateSheetTabBadges();
                makeTabsNonEditable(); 
            }
        }, 100); 
    });
    function cancelEdit() {
        const requestId = document.getElementById('request_id').value;
        // Check current status from hidden input or via PHP (better to inject as logic var)
        const isDraft = <?php echo ($request['status'] === 'DRAFT' || $request['has_draft'] == 1) ? 'true' : 'false'; ?>;
        
        if (isDraft) {
            Swal.fire({
                title: 'Discard Draft?',
                text: "This will permanently delete this draft revision. Are you sure?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Discard',
                cancelButtonText: 'No, Keep Draft'
            }).then((result) => {
                if (result.isConfirmed) {
                     fetch('?controller=request&action=cancelDraft', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ request_id: requestId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                             window.location.href = 'index.php?controller=dashboard&action=library';
                        } else {
                             Swal.fire('Error', data.error || 'Failed to delete draft', 'error');
                        }
                    })
                    .catch(e => Swal.fire('Error', 'Network Error', 'error'));
                } else {
                    // Do nothing (stay on page) or redirect to dashboard without deleting?
                    // Usually "Cancel" means "Leave Page". If they say "No, Keep Draft", we might just go back to Index.
                    // But to be safe, stick to page unless confirmed.
                    // Option 2: "Keep Draft & Exit" vs "Discard & Exit"
                    // Current logic: Cancel button implies "I don't want to do this anymore".
                    // If established draft: Just redirect. If NEW draft: Delete.
                    
                    // Since we treat 'Reuse' as creating a NEW DRAFT, discarding is correct behavior for "Cancel".
                    // If they want to save for later, they should click "Save Draft".
                    
                    // So if they click Cancel button but say "No" to discard, we assume they want to stay on page to save?
                }
            });
        } else {
            // Not a draft (editing an active revision or rejection) - Just redirect
            window.location.href = 'index.php?controller=dashboard&action=library';
        }
    }
</script>
<?php require_once 'app/views/layouts/footer.php'; ?>
