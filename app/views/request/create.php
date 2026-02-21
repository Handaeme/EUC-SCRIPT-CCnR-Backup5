<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

// Detect base URL dinamis (lokal)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . "://" . $host . $script;
// Remove /app/views/request if present
$baseUrl = preg_replace('#/app/views/request$#', '', $baseUrl);
?>

<div class="main">
    <style>
    /* CSS for Excel Preview Tabs */
    .sheet-tabs-nav { display: flex; overflow-x: auto; border-bottom: 1px solid #ccc; background: #f1f1f1; scrollbar-width: none; -ms-overflow-style: none; }
    .sheet-tabs-nav::-webkit-scrollbar { display: none; }
    .btn-sheet { border: 1px solid #ccc; border-bottom: none; background: #e0e0e0; padding: 8px 16px; cursor: pointer; font-size: 13px; margin-right: 2px; }
    .btn-sheet.active { background: #fff; font-weight: bold; border-top: 2px solid var(--primary-red); }
    .sheet-pane { padding: 15px; background: #fff; border: 1px solid #ccc; border-top: none; overflow: auto; }
    .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
    .tab-item { padding: 10px 20px; cursor: pointer; font-weight: bold; color: #555; }
    .tab-item.active { border-bottom: 3px solid var(--primary-red); color: var(--primary-red); }
    /* Disabled Tab Style */
    .tab-item.disabled-tab { 
        color: #999; 
        cursor: not-allowed; 
        opacity: 0.6;
        background-color: #f9f9f9;
    }
    
    .form-group { margin-bottom: 20px; }
    .form-label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 14px; }
    .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .checkbox-group label { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; margin: 0; }
    .checkbox-group input[type="checkbox"] { margin: 0; }
    
    .upload-area { border: 2px dashed #bbb; padding: 40px; text-align: center; border-radius: 12px; cursor: pointer; transition: 0.3s; background-color: #fafafa; }
    .upload-area:hover { border-color: var(--primary-red); background: #fff5f5; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    
    .upload-icon { font-size: 48px; margin-bottom: 15px; color: #888; transition:0.3s; }
    .upload-area:hover .upload-icon { color: var(--primary-red); transform: scale(1.1); }
    
    /* Bento Grid Styles - Compact 12 Column */
    .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 15px; margin-bottom: 20px; }
    .bento-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
    
    .col-span-12 { grid-column: span 12; }
    .col-span-8 { grid-column: span 8; }
    .col-span-6 { grid-column: span 6; }
    .col-span-4 { grid-column: span 4; }
    
    @media (max-width: 768px) { 
        .col-span-12, .col-span-8, .col-span-6, .col-span-4 { grid-column: span 12; } 
    }
    .form-control:focus { outline: 2px solid var(--primary-red); border-color:transparent; }

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

    /* Plain Text Editor Styles */
    #shared-editor {
        width: 100%; height: 400px; padding: 15px; border: 1px solid #ccc; border-radius: 4px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;
        resize: vertical; outline: none; transition: border-color 0.2s;
        background: #fff; color: #333;
    }
    #shared-editor:focus { border-color: var(--primary-red); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); }
    </style>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">
                Create New Request
                <button onclick="showInfoPopup()" style="background:none; border:none; color:#666; cursor:pointer; margin-left:8px;" title="Guide / Info">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </button>
            </h2>
            <p style="color:#666; font-size:13px; margin-top:5px;">Submit new script for approval (Plain Text Mode)</p>
        </div>
        
        <!-- Browse Library Button -->
        <button onclick="openLibraryModal()" style="background:#3b82f6; color:white; padding:8px 16px; border-radius:6px; border:none; font-weight:600; font-size:13px; display:flex; align-items:center; gap:8px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); cursor:pointer; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            Browse Library Templates
        </button>

    </div>
    
    <!-- Library Browser Modal -->
    <div id="libraryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; padding:20px;" onclick="closeLibraryModal(event)">
        <div style="background:white; border-radius:12px; width:100%; max-width:900px; max-height:90vh; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3);" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
                <div>
                    <h3 style="margin:0; color:#1e293b; font-size:18px; font-weight:700;">Template Library</h3>
                    <p style="margin:4px 0 0 0; color:#64748b; font-size:13px;">Pilih dan download template untuk request baru</p>
                </div>
                <button onclick="closeLibraryModal()" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px; transition:color 0.2s;" onmouseover="this.style.color='#64748b'" onmouseout="this.style.color='#94a3b8'">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div style="padding:16px 24px; border-bottom:1px solid #f1f5f9;">
                <input type="text" id="librarySearch" placeholder="Cari template... (title, filename, uploader)" oninput="filterLibraryItems()" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px;">
            </div>
            
            <!-- Library Items List -->
            <div id="libraryItemsList" style="flex:1; overflow-y:auto; padding:16px 24px;">
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card" style="background:white; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
    
        <!-- Modals replaced by SweetAlert2 -->

        <!-- Metadata Form -->
        <!-- Metadata Form -->
        <div class="bento-grid">
            <!-- Row 1: Judul (6) + Jenis (6) -->
            <div class="bento-box col-span-6">
                <label class="form-label" style="font-size:13px;">Judul Script / Tujuan <span style="color:red">*</span></label>
                <input type="text" id="input_title" class="form-control" placeholder="Isi judul..." style="width:100%; padding:8px 10px; font-size:14px;">
            </div>

            <div class="bento-box col-span-6">
                <label class="form-label" style="font-size:13px;">Jenis <span style="color:red">*</span></label>
                <div class="checkbox-group">
                    <label><input type="radio" name="jenis" value="Konvensional" onchange="filterProduk()"> Konvensional</label>
                    <label><input type="radio" name="jenis" value="Syariah" onchange="filterProduk()"> Syariah</label>
                </div>
            </div>

            <!-- Row 2: Produk (Full) -->
            <div class="bento-box col-span-12" id="produk-container">
                <label class="form-label" style="font-size:13px;">Produk <span style="color:red">*</span></label>
                
                <p id="produk-placeholder" style="color:#999; font-style:italic; font-size:13px; margin:0;">Please select 'Jenis' first to see products.</p>

                <div id="produk-konv" style="display:none; padding:10px 15px; background:#fff; border-left:3px solid var(--primary-red); margin-top:5px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                        <strong style="font-size:11px; color:#d32f2f; letter-spacing:0.5px; min-width:80px;">KONVENSIONAL</strong>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="produk" value="Kartu Kredit"> Kartu Kredit</label>
                            <label><input type="checkbox" name="produk" value="Extra Dana"> Extra Dana</label>
                            <label><input type="checkbox" name="produk" value="KPR"> KPR</label>
                            <label><input type="checkbox" name="produk" value="Others" onchange="toggleInput('prod_konv_other', this.checked)"> Others</label>
                       </div>
                       <input type="text" id="prod_konv_other" class="form-control" style="display:none; width:auto; flex-grow:1; padding:4px 8px; font-size:13px;" placeholder="Other product...">
                    </div>
                </div>

                <div id="produk-syariah" style="display:none; padding:10px 15px; background:#fff; border-left:3px solid #16a34a; margin-top:8px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                        <strong style="font-size:11px; color:#16a34a; letter-spacing:0.5px; min-width:80px;">SYARIAH</strong>
                         <div class="checkbox-group">
                            <label><input type="checkbox" name="produk" value="Kartu Syariah"> Kartu Syariah</label>
                            <label><input type="checkbox" name="produk" value="Extra Dana iB"> Extra Dana iB</label>
                            <label><input type="checkbox" name="produk" value="KPR iB"> KPR iB</label>
                            <label><input type="checkbox" name="produk" value="Others" onchange="toggleInput('prod_syr_other', this.checked)"> Others</label>
                        </div>
                        <input type="text" id="prod_syr_other" class="form-control" style="display:none; width:auto; flex-grow:1; padding:4px 8px; font-size:13px;" placeholder="Other product...">
                    </div>
                </div>
            </div>

            <!-- Row 3: Kategori (6) + Media (6) -->
            <div class="bento-box col-span-6">
                <label class="form-label" style="font-size:13px;">Kategori <span style="color:red">*</span></label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="kategori" value="Pre Due"> Pre Due</label>
                    <label><input type="checkbox" name="kategori" value="Past Due"> Past Due</label>
                    <label><input type="checkbox" name="kategori" value="Program Offer"> Program Offer</label>
                    <label><input type="checkbox" name="kategori" value="Others" onchange="toggleInput('kategori_other', this.checked)"> Others</label>
                </div>
                <input type="text" id="kategori_other" class="form-control" style="display:none; margin-top:5px; width:100%; padding:5px; font-size:13px;" placeholder="Other category...">
            </div>

            <div class="bento-box col-span-6">
                <label class="form-label" style="font-size:13px;">Media <span style="color:red">*</span></label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="media" value="WhatsApp" onchange="updateFreeInputTabs()"> WhatsApp</label>
                    <label><input type="checkbox" name="media" value="SMS" onchange="updateFreeInputTabs()"> SMS</label>
                    <label><input type="checkbox" name="media" value="Email" onchange="updateFreeInputTabs()"> Email</label>
                    <label><input type="checkbox" name="media" value="Robocoll" onchange="updateFreeInputTabs()"> Robocoll</label>
                    <label><input type="checkbox" name="media" value="Surat" onchange="updateFreeInputTabs()"> Surat</label>
                    <label><input type="checkbox" name="media" value="VB" onchange="updateFreeInputTabs()"> VB</label>
                    <label><input type="checkbox" name="media" value="Chatbot" onchange="updateFreeInputTabs()"> Chatbot</label>
                    <label><input type="checkbox" name="media" value="Others" onchange="toggleInput('media_other', this.checked); updateFreeInputTabs()"> Others</label>
                </div>
                <input type="text" id="media_other" class="form-control" style="display:none; margin-top:5px; width:100%; padding:5px; font-size:13px;" placeholder="Other media...">
            </div>
            

        </div>

        <!-- MODE TABS -->
        <div class="tabs">
            <div class="tab-item active" id="tab-upload" onclick="requestSwitch('upload')">File Upload</div>
            <div class="tab-item" id="tab-manual" onclick="requestSwitch('manual')">Free Input (Plain Text)</div>
        </div>
        <input type="hidden" id="input_mode" value="FILE_UPLOAD">

        <!-- VIEW: UPLOAD -->
        <div id="upload-panel" style="display:block;">
            <div class="upload-area" id="drop-zone" onclick="document.getElementById('fileInput').click()">
                <div class="upload-icon"><i class="fi fi-rr-document"></i></div>
                <h4 style="margin:0; color:#444;">Upload Script File</h4>
                <p style="color:#666; font-size:14px; margin:5px 0;">Click to browse</p>
                <input type="file" id="fileInput" hidden onchange="handleFileSelect(this)" accept=".xls,.xlsx,.doc,.docx">
            </div>
            <div id="file-list-container" style="margin-top:15px; display:none; border:1px solid #eee; padding:10px; border-radius:4px; background:#f9f9f9;"></div>
            <div id="preview-container" style="display:none; margin-top:20px; border:1px solid #ddd; padding:10px; max-height:400px; overflow:auto;"></div>
        </div>

        <!-- VIEW: MANUAL (PLAIN TEXT SHARED EDITOR) -->
        <div id="manual-panel" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <label class="form-label" style="margin:0;">Isi Script (Per Media)</label>
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

            <div id="shared-editor-container" class="sheet-pane">
                <textarea id="shared-editor" oninput="syncToStorage()" placeholder="Tulis script di sini..."></textarea>
                <div id="char-counter" style="text-align:right; font-size:12px; color:#666; margin-top:5px; display:none;">
                    <span id="char-count">0</span> / <span id="char-max">0</span>
                </div>
            </div>
            
            <!-- Hidden Storage -->
            <?php foreach($mediaTypes as $media): ?>
                <textarea id="storage-<?= $media ?>" name="content_<?= $media ?>" style="display:none;"></textarea>
            <?php endforeach; ?>
        </div>

        <!-- SPV SELECTION -->
         <div style="margin-top:30px; padding-top:20px; border-top:1px solid #eee;">
            <label class="form-label">Pilih Supervisor (Approver) <span style="color:red">*</span></label>
            <select id="selected_spv" class="form-select" style="max-width:400px; padding:8px; width:100%; border:1px solid var(--primary-red); border-radius:4px;">
                <option value="">-- Pilih SPV --</option>
                <?php foreach ($spvList as $spv) : ?>
                    <option value="<?php echo htmlspecialchars($spv['userid']); ?>">
                        <?php echo htmlspecialchars($spv['fullname'] . ' (' . $spv['userid'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top:30px; display:flex; justify-content:flex-end; gap:10px; padding-top:20px; border-top:1px solid #eee;">
            <a href="index.php" class="btn-cancel" style="padding:10px 20px; text-decoration:none; border:1px solid #ccc; border-radius:4px; color:#555; background:white;">Cancel</a>
            <button id="btnSubmitRequest" class="btn btn-primary" onclick="submitRequest()" style="background:var(--primary-red); color:white; border:none; padding:10px 20px; border-radius:4px; font-weight:bold; cursor:pointer;">Submit Request</button>
        </div>

    </div>
</div>

<script>
    let selectedFile = null;
    let currentActiveMedia = null;
    let libraryData = []; // Store library items
    const MEDIA_LIMITS = {
        'WhatsApp': 1014,
        'SMS': 160
    };

    // === LIBRARY MODAL ===
    function openLibraryModal() {
        const modal = document.getElementById('libraryModal');
        modal.style.display = 'flex';
        
        // Load library items
        loadLibraryItems();
    }
    
    function closeLibraryModal(event) {
        if (event && event.target.id !== 'libraryModal') return;
        document.getElementById('libraryModal').style.display = 'none';
        document.getElementById('librarySearch').value = '';
    }
    
    function loadLibraryItems() {
        const container = document.getElementById('libraryItemsList');
        container.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;"><p>Loading...</p></div>';
        
        fetch('?controller=dashboard&action=getLibraryData')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    libraryData = data.items;
                    renderLibraryItems(libraryData);
                } else {
                    container.innerHTML = '<div style="text-align:center; padding:40px; color:#ef4444;"><p>Failed to load library</p></div>';
                }
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div style="text-align:center; padding:40px; color:#ef4444;"><p>Error loading library</p></div>';
            });
    }
    
    function renderLibraryItems(items) {
        const container = document.getElementById('libraryItemsList');
        
        if (items.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;"><p>No templates found</p></div>';
            return;
        }
        
        let html = '<div style="display:flex; flex-direction:column; gap:12px;">';
        items.forEach(item => {
            // Get file extension for icon
            const ext = (item.filename || '').split('.').pop().toLowerCase();
            const isExcel = ['xls', 'xlsx'].includes(ext);
 const iconColor = isExcel ? '#166534' : '#1e40af';
            const bgColor = isExcel ? '#f0fdf4' : '#eff6ff';
            const borderColor = isExcel ? '#bbf7d0' : '#bfdbfe';
            
            const description = (item.description || '').substring(0, 150) + (item.description && item.description.length > 150 ? '...' : '');
            
            html += `
                <div style="border:1px solid #e2e8f0; border-radius:8px; padding:16px; transition:all 0.2s; background:white;" onmouseover="this.style.borderColor='#cbd5e1'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    <div style="display:flex; justify-content:space-between; align-items:start; gap:16px;">
                        <div style="flex:1;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <div style="color:${iconColor}; background:${bgColor}; padding:6px; border-radius:50%; border:1px solid ${borderColor};">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#1e293b; font-size:14px;">${item.title}</div>
                                    <div style="font-size:11px; color:#94a3b8; font-family:monospace;">${item.filename}</div>
                                </div>
                            </div>
                            ${description ? `<div style="font-size:12px; color:#64748b; line-height:1.5; margin-top:8px;">${description}</div>` : ''}
                            <div style="font-size:11px; color:#94a3b8; margin-top:8px;">
                                Uploaded by <span style="font-weight:600; color:#64748b;">${item.uploaded_by}</span> • 
                                ${item.created_at}
                            </div>
                        </div>
                        <a href="${item.filepath}" download
                           style="background:#16a34a; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:600; white-space:nowrap; display:flex; align-items:center; gap:6px; flex-shrink:0;"
                           onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Download
                        </a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    function filterLibraryItems() {
        const query = document.getElementById('librarySearch').value.toLowerCase();
        
        if (!query) {
            renderLibraryItems(libraryData);
            return;
        }
        
        const filtered = libraryData.filter(item => {
            return (item.title || '').toLowerCase().includes(query) ||
                   (item.filename || '').toLowerCase().includes(query) ||
                   (item.uploaded_by || '').toLowerCase().includes(query) ||
                   (item.description || '').toLowerCase().includes(query);
        });
        
        renderLibraryItems(filtered);
    }



    function filterProduk() {
        const types = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value);
        const konv = document.getElementById('produk-konv');
        const syr = document.getElementById('produk-syariah');
        const placeholder = document.getElementById('produk-placeholder');

        konv.style.display = types.includes('Konvensional') ? 'block' : 'none';
        syr.style.display = types.includes('Syariah') ? 'block' : 'none';
        placeholder.style.display = (types.length > 0) ? 'none' : 'block';
    }

    function toggleInput(id, checked) {
        document.getElementById(id).style.display = checked ? 'block' : 'none';
    }

    function requestSwitch(targetMode) {
        const currentMode = document.getElementById('input_mode').value === 'FILE_UPLOAD' ? 'upload' : 'manual';
        if (targetMode === currentMode) return; // Already here

        // Check if locked
        if (targetMode === 'manual' && selectedFile) {
            // Locked by File
            Swal.fire({
                title: 'Ganti Mode?',
                text: "File yang sudah diupload akan dihapus jika Anda pindah ke Free Input. Lanjutkan?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--primary-red)',
                confirmButtonText: 'Ya, Pindah & Hapus File',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    clearFile();
                    switchMode('manual');
                }
            });
            return;
        }

        if (targetMode === 'upload' && hasTextInput()) {
            // Locked by Text
            Swal.fire({
                title: 'Ganti Mode?',
                text: "Teks yang sudah diketik akan dihapus jika Anda pindah ke File Upload. Lanjutkan?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--primary-red)',
                confirmButtonText: 'Ya, Pindah & Hapus Teks',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    clearAllText();
                    switchMode('upload');
                }
            });
            return;
        }

        // Not locked, just switch
        switchMode(targetMode);
    }

    function switchMode(mode) {
        document.getElementById('input_mode').value = (mode === 'upload') ? 'FILE_UPLOAD' : 'FREE_INPUT';
        document.getElementById('tab-upload').className = (mode === 'upload') ? 'tab-item active' : 'tab-item';
        document.getElementById('tab-manual').className = (mode === 'manual') ? 'tab-item active' : 'tab-item';
        document.getElementById('upload-panel').style.display = (mode === 'upload') ? 'block' : 'none';
        document.getElementById('manual-panel').style.display = (mode === 'manual') ? 'block' : 'none';
        
        checkInputState(); // Update locks visual
        
        // Always force update tabs when switching to manual
        if (mode === 'manual') {
             setTimeout(() => {
                updateFreeInputTabs();
                // If media selected but no tab active, force click first available
                if (currentActiveMedia === null) {
                     const firstMedia = document.querySelector('input[name="media"]:checked');
                     if (firstMedia) activateSharedTab(firstMedia.value);
                } else {
                     // If media already active, just force refresh counter
                     const el = document.getElementById('storage-' + currentActiveMedia);
                     if(el) updateCharCounter(currentActiveMedia, el.value.length);
                }
             }, 50);
        }
    }
    
    function hasTextInput() {
        const medias = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
        return medias.some(m => {
            const el = document.getElementById('storage-' + m);
            return el && el.value.trim().length > 0;
        });
    }

    function clearAllText() {
        const medias = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
        medias.forEach(m => {
            document.getElementById('storage-' + m).value = '';
        });
        document.getElementById('shared-editor').value = '';
        updateCharCounter('', 0); // Hide counter
        checkInputState();
    }

    function checkInputState() {
        const tabUpload = document.getElementById('tab-upload');
        const tabManual = document.getElementById('tab-manual');
        
        const fileExists = (selectedFile !== null);
        const textExists = hasTextInput();

        if (fileExists) {
             tabManual.classList.add('disabled-tab');
             tabManual.title = "Hapus file dulu untuk pindah ke Free Input";
        } else {
             tabManual.classList.remove('disabled-tab');
             tabManual.title = "";
        }

        if (textExists) {
             tabUpload.classList.add('disabled-tab');
             tabUpload.title = "Hapus teks dulu untuk pindah ke File Upload";
        } else {
             tabUpload.classList.remove('disabled-tab');
             tabUpload.title = "";
        }
    }

    function updateFreeInputTabs() {
        // We removed the "Skip if FILE_UPLOAD" check because we need this to run 
        // immediately when switching TO manual mode.
         
        const medias = Array.from(document.querySelectorAll('input[name="media"]:checked')).map(c => c.value);
        // IMPORTANT: Only hide/show tabs within the static-tabs-nav container (Free Input tabs ONLY)
        document.querySelectorAll('#static-tabs-nav .btn-sheet').forEach(el => el.style.display = 'none');

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

    function activateSharedTab(media) {
        // Sync current to storage before switching
        syncToStorage();
        
        currentActiveMedia = media;
        document.getElementById('active-media-label').innerText = media;
        
        document.querySelectorAll('.btn-sheet').forEach(b => b.classList.remove('active'));
        const btn = document.getElementById('tab-btn-' + media);
        if (btn) btn.classList.add('active');

        // Load content from storage
        const storageValue = document.getElementById('storage-' + media).value;
        const editor = document.getElementById('shared-editor');
        editor.value = storageValue;
        
        // Handle Limits
        updateCharCounter(media, storageValue.length);
        
        editor.focus();
    }

    function syncToStorage() {
        if (currentActiveMedia) {
            const content = document.getElementById('shared-editor').value;
            document.getElementById('storage-' + currentActiveMedia).value = content;
            updateCharCounter(currentActiveMedia, content.length);
            checkInputState(); // Check locks on input
        }
    }

    function updateCharCounter(media, currentLength) {
        const counterDiv = document.getElementById('char-counter');
        
        // Define limits again just to be sure scope is correct or use global
        const limit = (MEDIA_LIMITS && MEDIA_LIMITS[media]) ? MEDIA_LIMITS[media] : 0;
        
        if (limit > 0) {
            counterDiv.style.display = 'block';
            const remaining = limit - currentLength;
            
            // Format: "Terpakai: 5 / 114 | Sisa: 109"
            let html = `Terpakai: <strong>${currentLength}</strong> / ${limit} &nbsp;|&nbsp; Sisa: <strong>${remaining}</strong>`;
            
            counterDiv.innerHTML = html;
            
            if (currentLength > limit) {
                counterDiv.style.color = 'red';
                counterDiv.style.fontWeight = 'bold';
            } else {
                counterDiv.style.color = '#666';
                counterDiv.style.fontWeight = 'normal';
            }
        } else {
            counterDiv.style.display = 'none';
        }
    }

    function applyMakerColor(node) {
        // Safe check for node or selection
        let target = node;
        if (!target) {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) return;
            target = sel.anchorNode;
            if (target && target.nodeType === 3) target = target.parentNode;
        }
        
        if (target) {
             const style = window.getComputedStyle(target);
             const color = style.color; 
             if (color === 'rgb(51, 51, 51)' || color === '#333333' || color === 'rgb(0, 0, 0)' || color === 'black') {
                 return; 
             }
        }
        
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('foreColor', false, '#333333');
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            const container = document.getElementById('file-list-container');
            container.style.display = 'block';
            container.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span><strong>${selectedFile.name}</strong> (${(selectedFile.size/1024).toFixed(1)} KB)</span>
                    <button onclick="clearFile()" style="color:red; background:none; border:none; cursor:pointer;">❌ Remove</button>
                </div>
            `;
            uploadAndPreview(selectedFile);
            checkInputState(); // Lock text tab
        }
    }
    
    function uploadAndPreview(file) {
        const formData = new FormData();
        formData.append('file', file);
        const previewContainer = document.getElementById('preview-container');
        previewContainer.style.display = 'block';
        previewContainer.innerHTML = '<p style="text-align:center; padding:20px;">⏳ Loading preview...</p>';
        
        fetch('index.php?controller=request&action=upload', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                previewContainer.innerHTML = data.preview;
                lastPreviewHtml = data.preview;
                
                // [FIX] Allow editing for Maker Create Preview
                // We no longer force contentEditable = "false" here
                
                // Add color enforcement to all editable spans/cells
                previewContainer.querySelectorAll('[contenteditable="true"]').forEach(node => {
                    node.addEventListener('keyup', () => applyMakerColor(node));
                    node.addEventListener('click', () => applyMakerColor(node));
                });
                
            } else {
                previewContainer.innerHTML = `<p style="color:red;">⚠️ error: ${data.message}</p>`;
                lastPreviewHtml = '';
            }
        })
        .catch(() => { 
            previewContainer.innerHTML = '<p style="color:red;">❌ Failed to generate preview</p>'; 
            lastPreviewHtml = '';
        });
    }

    function clearFile() {
        selectedFile = null;
        lastPreviewHtml = '';
        document.getElementById('fileInput').value = '';
        document.getElementById('file-list-container').style.display = 'none';
        document.getElementById('preview-container').style.display = 'none';
        checkInputState(); // Unlock text tab
    }

    function changeSheet(sheetId) {
        // Hide all sheets
        document.querySelectorAll('.sheet-pane').forEach(pane => pane.style.display = 'none');
        // Remove active class from all buttons
        document.querySelectorAll('.btn-sheet').forEach(btn => btn.classList.remove('active'));
        
        // Show selected sheet
        const selectedSheet = document.getElementById(sheetId);
        if (selectedSheet) {
            selectedSheet.style.display = 'block';
        }
        
        // Set active class to clicked button
        event.target.classList.add('active');
    }

    let isSubmitting = false; // Global guard against double submit

    function submitRequest() {
        const btn = document.getElementById('btnSubmitRequest');
        if (btn.disabled || isSubmitting) return; // Prevent double click
        
        isSubmitting = true; // Lock immediately
        btn.disabled = true;
        btn.innerHTML = 'Submitting...';
        btn.style.opacity = '0.7';

        syncToStorage(); // Force sync one last time

        const title = document.getElementById('input_title').value.trim();
        const jenis = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value).join(',');
        const spv = document.getElementById('selected_spv').value;
        const inputMode = document.getElementById('input_mode').value;

        // Produk logic — filter out hidden inputs (e.g. Syariah products when Konvensional is selected)
        let selectedProduk = Array.from(document.querySelectorAll('input[name="produk"]:checked'))
            .filter(c => c.value !== 'Others')
            .filter(c => c.offsetParent !== null)
            .map(c => c.value);
        if (document.getElementById('prod_konv_other').offsetParent && document.getElementById('prod_konv_other').value.trim()) 
            selectedProduk.push(document.getElementById('prod_konv_other').value.trim());
        if (document.getElementById('prod_syr_other').offsetParent && document.getElementById('prod_syr_other').value.trim()) 
            selectedProduk.push(document.getElementById('prod_syr_other').value.trim());

        // Kategori logic
        let selectedKategori = Array.from(document.querySelectorAll('input[name="kategori"]:checked'))
            .map(c => (c.value === 'Others') ? (document.getElementById('kategori_other').value.trim() || 'Others') : c.value);

        // Media logic
        let selMedNodes = Array.from(document.querySelectorAll('input[name="media"]:checked'));
        let mediaNames = selMedNodes.map(c => (c.value === 'Others') ? (document.getElementById('media_other').value.trim() || 'Others') : c.value);

        // Validations
        if (!title) { resetSubmitBtn(); return showModal('Validation Error', "Judul wajib diisi!", 'error'); }
        if (!jenis) { resetSubmitBtn(); return showModal('Validation Error', "Pilih Jenis!", 'error'); }
        if (selectedProduk.length === 0) { resetSubmitBtn(); return showModal('Validation Error', "Pilih Produk!", 'error'); }
        if (selectedKategori.length === 0) { resetSubmitBtn(); return showModal('Validation Error', "Pilih Kategori!", 'error'); }
        if (mediaNames.length === 0) { resetSubmitBtn(); return showModal('Validation Error', "Pilih Media!", 'error'); }
        if (!spv) { resetSubmitBtn(); return showModal('Validation Error', "Pilih SPV!", 'error'); }

        const formData = new FormData();
        formData.append('title', title);
        formData.append('jenis', jenis);
        formData.append('produk', selectedProduk.join(','));
        formData.append('kategori', selectedKategori.join(','));
        formData.append('media', mediaNames.join(','));
        formData.append('input_mode', inputMode);
        formData.append('selected_spv', spv);
        


        if (inputMode === 'FILE_UPLOAD') {
            if (!selectedFile) { resetSubmitBtn(); return showModal('Validation Error', "Upload file!", 'error'); }
            formData.append('script_file', selectedFile);
            
            // [FIX] Scrape EDITED content from the preview container
            const panes = document.querySelectorAll('#preview-container .sheet-pane');
            if (panes.length > 0) {
                let scrapedData = [];
                panes.forEach(pane => {
                    // Try to get sheet name from corresponding button
                    const sheetId = pane.id;
                    const btn = document.querySelector(`.btn-sheet[onclick*="${sheetId}"]`);
                    const sheetName = btn ? btn.innerText.trim() : 'Sheet';
                    
                    scrapedData.push({
                        sheet_name: sheetName,
                        content: pane.innerHTML // Send full HTML including edits
                    });
                });
                formData.append('script_content', JSON.stringify(scrapedData));
            } else {
                // Fallback if no panes found (Word or simple Doc)
                const wordPreview = document.querySelector('#preview-container .word-preview');
                const finalContent = wordPreview ? wordPreview.innerHTML : lastPreviewHtml;
                formData.append('script_content', finalContent);
            }
        } else {
            let contentData = [];
            let validationError = null;
            selMedNodes.forEach(c => {
                const label = (c.value === 'Others') ? (document.getElementById('media_other').value.trim() || 'Others') : c.value;
                const text = document.getElementById('storage-' + c.value).value;
                
                // Check Limits
                if (MEDIA_LIMITS[label] && text.length > MEDIA_LIMITS[label]) {
                    validationError = `Script ${label} melebihi batas ${MEDIA_LIMITS[label]} karakter! (Saat ini: ${text.length})`;
                }
                
                contentData.push({ sheet_name: label, content: text });
            });

            if (validationError) { resetSubmitBtn(); return showModal('Validation Error', validationError, 'error'); }
            if (contentData.every(i => !i.content.trim())) { resetSubmitBtn(); return showModal('Validation Error', "Isi script!", 'error'); }
            formData.append('script_content', JSON.stringify(contentData));
        }

        fetch('?controller=request&action=store', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const msg = "Ticket ID: <strong>" + data.ticket_id + "</strong><br><small style='color:#666'>Script No: " + data.script_number + "</small>";
                showSuccess('Submission Successful!', msg, false);
            }
            else {
                showModal('Gagal', data.message || 'Error', 'error');
                resetSubmitBtn();
            }
        })
        .catch(() => {
            showModal('Error', "Unexpected error.", 'error');
            resetSubmitBtn();
        });
    }

    function resetSubmitBtn() {
        isSubmitting = false; // Unlock global guard
        const btn = document.getElementById('btnSubmitRequest');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Submit Request';
            btn.style.opacity = '1';
        }
    }

    // ===== SWEETALERT2 IMPLEMENTATION =====

    function showModal(title, message, type = 'success') {
        Swal.fire({
            title: title,
            text: message, // Note: SweetAlert2 'text' is plain text. Use 'html' property if message contains HTML.
            icon: type,
            confirmButtonText: 'OK',
            confirmButtonColor: 'var(--primary-red)'
        });
    }

    // Helper for HTML content if needed (e.g. ticket ID)
    function showSuccess(title, htmlMessage, reload = false) {
        // CRITICAL: Prevent "Unsaved Changes" popup immediately
        hasUnsavedChanges = false; 
        window.onbeforeunload = null;

        // [FIX] GUARANTEED REDIRECT: Always redirect after 2 seconds
        // This prevents the duplicate submit issue when SweetAlert is blocked on server PC
        const redirectTarget = reload ? window.location.href : 'index.php';
        const redirectTimer = setTimeout(() => {
            window.location.href = redirectTarget;
        }, 2000);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                html: htmlMessage,
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: 'var(--primary-red)',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                clearTimeout(redirectTimer);
                window.location.href = redirectTarget;
            });
        } else {
            // Fallback — native alert blocks, then redirect
            const plainMsg = title + "\n" + htmlMessage.replace(/<[^>]*>?/gm, '');
            alert(plainMsg);
            clearTimeout(redirectTimer);
            window.location.href = redirectTarget;
        }
    }

    function showCustomConfirm(title, message, onConfirm, onCancel) {
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Submit',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                if (onConfirm) onConfirm();
            } else {
                if (onCancel) onCancel();
            }
        });
    }

    // --- UNSAVED CHANGES PROTECTION ---
    let hasUnsavedChanges = false;
    document.addEventListener('input', (e) => {
        if (e.target.closest('input, textarea')) hasUnsavedChanges = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // --- INFO POPUP ---
    function showInfoPopup() {
        Swal.fire({
            title: '<strong>Panduan Pengisian Request</strong>',
            icon: 'info',
            html:
                '<div style="text-align:left; font-size:13px; line-height:1.6;">' +
                '<p>Gunakan form ini untuk membuat ticket request script baru.</p>' +
                '<ul style="margin-left:15px; padding-left:0;">' +
                '<li><strong>Judul:</strong> Masukkan tujuan singkat script (misal: "Blast Promo Kemerdekaan").</li>' +
                '<li><strong>Jenis & Produk:</strong> Pilih <em>Konvensional</em> atau <em>Syariah</em>, lalu pilih produk yang relevan.</li>' +
                '<li><strong>Media:</strong> Pilih channel pengiriman (WhatsApp, SMS, dll). Pilih minimal satu.</li>' +
                '<li><strong>Mode Input:</strong>' +
                '<ul>' +
                '<li><em>File Upload:</em> Unggah file Excel/Doc yang sudah jadi.</li>' +
                '<li><em>Free Input:</em> Ketik langsung text script di editor yang disediakan.</li>' +
                '</ul>' +
                '</li>' +
                '<li><strong>Supervisor:</strong> Pilih SPV yang akan mereview request ini.</li>' +
                '</ul>' +
                '</div>',
            showCloseButton: true,
            focusConfirm: false,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: 'var(--primary-red)'
        });
    }

    window.addEventListener('submit', () => { hasUnsavedChanges = false; });

    // --- SYNC "Others" Tab Label with custom input ---
    document.getElementById('media_other').addEventListener('input', function() {
        const btn = document.getElementById('tab-btn-Others');
        if (btn) {
            btn.innerText = this.value.trim() || 'Others';
        }
        // Also update the "Editing:" label if Others tab is active
        if (currentActiveMedia === 'Others') {
            document.getElementById('active-media-label').innerText = this.value.trim() || 'Others';
        }
    });
</script>
<?php require_once 'app/views/layouts/footer.php'; ?>
