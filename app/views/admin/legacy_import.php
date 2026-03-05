<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
$currentMode = $import_mode ?? 'file'; // file | text
?>

<div class="main">
    <div class="header-box" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">📥 Legacy Script Importer</h2>
            <p style="color:var(--text-secondary);">Bulk upload script lama langsung ke Script Library.</p>
        </div>
        <!-- Template Download Buttons (both modes) -->
        <div style="display:flex; gap:8px;" id="templateButtons">
            <a href="?controller=legacyImport&action=downloadTemplate" id="btnTemplateA"
               style="background:#3b82f6; color:white; text-decoration:none; padding:8px 14px; border-radius:8px; font-weight:bold; font-size:12px; display:flex; align-items:center; gap:6px; box-shadow: 0 4px 6px -1px rgba(59,130,246,0.2);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Template File (CSV+ZIP)
            </a>
            <a href="?controller=legacyImport&action=downloadTextTemplate" id="btnTemplateB"
               style="background:#8b5cf6; color:white; text-decoration:none; padding:8px 14px; border-radius:8px; font-weight:bold; font-size:12px; display:flex; align-items:center; gap:6px; box-shadow: 0 4px 6px -1px rgba(139,92,246,0.2);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Template Teks (1 File)
            </a>
        </div>
    </div>

    <?php // ── ERROR MESSAGES ──────────────────────────────── ?>
    <?php if (!empty($errors)): ?>
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:14px 18px; margin-bottom:20px; color:#991b1b;">
            <strong>⚠️ Error:</strong>
            <ul style="margin:8px 0 0 0; padding-left:20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (($step ?? 'upload') === 'upload'): ?>
    <!-- ═══════════════════════════════════════════════════════ -->
    <!--  STEP 1: UPLOAD FORM                                   -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="card" style="max-width:750px;">
        <h4 style="margin:0 0 15px 0; display:flex; align-items:center; gap:8px;">
            <span style="background:#dbeafe; color:#2563eb; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700;">STEP 1</span>
            Pilih Mode & Upload Data
        </h4>

        <!-- ── MODE TABS ──────────────────────────────── -->
        <div style="display:flex; gap:0; margin-bottom:22px; border-radius:10px; overflow:hidden; border:2px solid #e2e8f0;">
            <button type="button" onclick="switchMode('file')" id="tabFile"
                    style="flex:1; padding:12px; border:none; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; background:#3b82f6; color:white;">
                📦 Mode A: File Fisik (CSV + ZIP)
            </button>
            <button type="button" onclick="switchMode('text')" id="tabText"
                    style="flex:1; padding:12px; border:none; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; background:#f8fafc; color:#64748b;">
                📝 Mode B: Teks Langsung (1 File CSV)
            </button>
        </div>

        <!-- ── MODE A: File Upload (CSV + ZIP) ──────── -->
        <div id="formModeA">
            <form method="POST" action="?controller=legacyImport&action=preview" enctype="multipart/form-data">
                <div style="margin-bottom:20px;">
                    <label style="font-size:13px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">
                        📄 File CSV Template (Rekap Data)
                    </label>
                    <input type="file" name="excel_file" accept=".csv" required
                           style="width:100%; padding:10px; border:2px dashed #cbd5e1; border-radius:8px; font-size:13px; background:#f8fafc; cursor:pointer; box-sizing:border-box;">
                    <p style="margin:4px 0 0 0; font-size:11px; color:#94a3b8;">Format: .csv — Gunakan Template File (biru) yang sudah didownload</p>
                </div>
                <div style="margin-bottom:24px;">
                    <label style="font-size:13px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">
                        📦 File ZIP Dokumen (Berisi semua file Word/Excel naskah asli)
                    </label>
                    <input type="file" name="zip_file" accept=".zip" required
                           style="width:100%; padding:10px; border:2px dashed #cbd5e1; border-radius:8px; font-size:13px; background:#f8fafc; cursor:pointer; box-sizing:border-box;">
                    <p style="margin:4px 0 0 0; font-size:11px; color:#94a3b8;">Format: .zip — Berisi file-file yang disebut di kolom "Nama_File" pada CSV</p>
                </div>
                <!-- Extract HTML Checkbox -->
                <div style="margin-bottom:20px; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                    <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="extract_html" value="1" id="chkExtractHtml"
                               style="margin-top:3px; width:18px; height:18px; accent-color:#f59e0b; flex-shrink:0;">
                        <div>
                            <span style="font-size:13px; font-weight:700; color:#92400e;">
                                📊 Ekstrak Excel ke HTML Tabs (Khusus File .xlsx)
                            </span>
                            <p style="margin:4px 0 0 0; font-size:11px; color:#a16207; line-height:1.5;">
                                Membaca isi Excel multi-sheet agar bisa ditampilkan langsung di web Library (seperti Create Request).
                                <strong>Maks 10 file sekali upload</strong>. Proses agak lebih lambat karena server membaca tiap file.
                            </p>
                        </div>
                    </label>
                </div>
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <div style="font-size:12px; color:#166534; line-height:1.5;">
                        <strong>Cara Kerja:</strong> Sistem membaca CSV, mencocokkan nama file dengan isi ZIP, dan menampilkan <strong>Preview</strong> sebelum data dimasukkan ke Library.
                    </div>
                </div>
                <button type="submit" style="background:var(--primary-red); color:white; border:none; padding:12px 28px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Analyze & Preview
                </button>
            </form>
        </div>

        <!-- ── MODE B: Text Import (1 CSV only) ────── -->
        <div id="formModeB" style="display:none;">
            <form method="POST" action="?controller=legacyImport&action=previewText" enctype="multipart/form-data">
                <div style="margin-bottom:20px;">
                    <label style="font-size:13px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">
                        📝 File CSV Template Teks (Berisi Metadata + Isi Script)
                    </label>
                    <input type="file" name="text_csv_file" accept=".csv" required
                           style="width:100%; padding:10px; border:2px dashed #c4b5fd; border-radius:8px; font-size:13px; background:#faf5ff; cursor:pointer; box-sizing:border-box;">
                    <p style="margin:4px 0 0 0; font-size:11px; color:#94a3b8;">Format: .csv — Gunakan Template Teks (ungu) yang sudah didownload</p>
                </div>
                <div style="background:#f5f3ff; border:1px solid #c4b5fd; border-radius:10px; padding:12px 14px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <div style="font-size:12px; color:#5b21b6; line-height:1.5;">
                        <strong>Mode Teks Langsung:</strong> Cukup 1 file CSV saja. Isi teks naskah dipaste langsung di kolom terakhir. <strong>Tidak perlu ZIP</strong>. Sistem akan auto-repair jika ada Enter yang salah.
                    </div>
                </div>
                <button type="submit" style="background:#7c3aed; color:white; border:none; padding:12px 28px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Analyze & Preview Teks
                </button>
            </form>
        </div>
    </div>

    <script>
    function switchMode(mode) {
        var tabFile = document.getElementById('tabFile');
        var tabText = document.getElementById('tabText');
        var formA = document.getElementById('formModeA');
        var formB = document.getElementById('formModeB');

        if (mode === 'text') {
            tabText.style.background = '#7c3aed';
            tabText.style.color = 'white';
            tabFile.style.background = '#f8fafc';
            tabFile.style.color = '#64748b';
            formA.style.display = 'none';
            formB.style.display = 'block';
        } else {
            tabFile.style.background = '#3b82f6';
            tabFile.style.color = 'white';
            tabText.style.background = '#f8fafc';
            tabText.style.color = '#64748b';
            formA.style.display = 'block';
            formB.style.display = 'none';
        }
    }
    </script>

    <?php elseif ($step === 'preview'): ?>
    <!-- ═══════════════════════════════════════════════════════ -->
    <!--  STEP 2: PREVIEW & VALIDATION                          -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="card">
        <h4 style="margin:0 0 15px 0; display:flex; align-items:center; gap:8px;">
            <span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700;">STEP 2</span>
            Preview & Validasi Data
            <?php if ($currentMode === 'text'): ?>
                <span style="background:#ede9fe; color:#6d28d9; padding:3px 8px; border-radius:5px; font-size:11px; font-weight:600;">Mode Teks</span>
            <?php else: ?>
                <span style="background:#dbeafe; color:#2563eb; padding:3px 8px; border-radius:5px; font-size:11px; font-weight:600;">Mode File</span>
            <?php endif; ?>
            <?php if (!empty($extract_html)): ?>
                <span style="background:#fef3c7; color:#92400e; padding:3px 8px; border-radius:5px; font-size:11px; font-weight:600;">📊 Extract HTML</span>
            <?php endif; ?>
        </h4>

        <!-- Stats -->
        <div style="display:flex; gap:15px; margin-bottom:20px;">
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px 18px; border-radius:10px; flex:1; text-align:center;">
                <div style="font-size:24px; font-weight:700; color:#16a34a;"><?php echo $readyRows ?? 0; ?></div>
                <div style="font-size:12px; color:#166534;">✅ Siap Import</div>
            </div>
            <div style="background:<?php echo $hasErrors ? '#fef2f2' : '#f8fafc'; ?>; border:1px solid <?php echo $hasErrors ? '#fca5a5' : '#e2e8f0'; ?>; padding:12px 18px; border-radius:10px; flex:1; text-align:center;">
                <div style="font-size:24px; font-weight:700; color:<?php echo $hasErrors ? '#dc2626' : '#94a3b8'; ?>;"><?php echo ($totalRows ?? 0) - ($readyRows ?? 0); ?></div>
                <div style="font-size:12px; color:<?php echo $hasErrors ? '#991b1b' : '#64748b' ; ?>;"><?php echo $hasErrors ? '❌ Ada Error' : '— Tidak Ada Error'; ?></div>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px 18px; border-radius:10px; flex:1; text-align:center;">
                <div style="font-size:24px; font-weight:700; color:#334155;"><?php echo $totalRows ?? 0; ?></div>
                <div style="font-size:12px; color:#64748b;">📊 Total Baris</div>
            </div>
        </div>

        <!-- Data Table -->
        <div style="overflow-x:auto; margin-bottom:20px;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:center; width:40px;">Status</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">No Tiket</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Judul</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Jenis</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Produk</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Media</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Tanggal</th>
                        <?php if ($currentMode === 'text'): ?>
                            <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Cuplikan Teks</th>
                        <?php else: ?>
                            <th style="padding:10px; border-bottom:2px solid #e2e8f0;">File</th>
                        <?php endif; ?>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $idx => $row): ?>
                    <tr style="border-bottom:1px solid #f1f5f9; background:<?php echo $row['status'] === 'error' ? '#fff1f2' : 'white'; ?>;">
                        <td style="padding:10px; text-align:center;">
                            <?php echo $row['status'] === 'ready' ? '✅' : '❌'; ?>
                        </td>
                        <td style="padding:10px; font-weight:600;">
                            <?php echo !empty($row['ticket']) ? htmlspecialchars($row['ticket']) : '<span style="color:#94a3b8; font-style:italic;">Auto</span>'; ?>
                        </td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['jenis']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['produk']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['media']); ?></td>
                        <td style="padding:10px; white-space:nowrap;"><?php echo htmlspecialchars($row['tgl_dibuat']); ?></td>
                        <?php if ($currentMode === 'text'): ?>
                            <td style="padding:10px;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:11px; color:#475569; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block;">
                                        <?php echo htmlspecialchars($row['content_preview'] ?? ''); ?>
                                    </span>
                                    <button type="button" onclick="showTextModal(<?php echo $idx; ?>)"
                                            style="background:#ede9fe; border:1px solid #c4b5fd; color:#7c3aed; padding:2px 8px; border-radius:5px; font-size:11px; cursor:pointer; white-space:nowrap; font-weight:600;">
                                        👁️ Lihat
                                    </button>
                                </div>
                            </td>
                        <?php else: ?>
                            <td style="padding:10px;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="display:flex; align-items:center; gap:4px;">
                                        <?php echo ($row['file_found'] ?? false) ? '📎' : '⚠️'; ?>
                                        <span style="font-size:11px; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($row['nama_file'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($row['nama_file'] ?? ''); ?>
                                        </span>
                                    </span>
                                    <?php if (!empty($extract_html) && !empty($row['content_preview_html'])): ?>
                                        <button type="button" onclick="showHtmlModal(<?php echo $idx; ?>)"
                                                style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:2px 8px; border-radius:5px; font-size:11px; cursor:pointer; white-space:nowrap; font-weight:600;">
                                            👁️ Preview
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                        <td style="padding:10px; font-size:11px; color:<?php echo $row['status'] === 'error' ? '#dc2626' : '#16a34a'; ?>;">
                            <?php echo $row['status'] === 'ready' ? 'Siap' : implode(', ', $row['errors']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a href="?controller=legacyImport&action=index"
               style="background:#f1f5f9; color:#64748b; text-decoration:none; padding:10px 22px; border-radius:8px; font-weight:600; font-size:14px;">
                ← Upload Ulang
            </a>
            <?php if (!$hasErrors): ?>
            <form method="POST" action="?controller=legacyImport&action=<?php echo $currentMode === 'text' ? 'executeText' : 'execute'; ?>" style="display:inline;">
                <button type="submit" onclick="this.disabled=true; this.innerHTML='⏳ Mengimport...'; this.form.submit();"
                        style="background:#16a34a; color:white; border:none; padding:10px 22px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    Confirm & Import ke Library
                </button>
            </form>
            <?php else: ?>
            <div style="background:#fef2f2; border:1px solid #fca5a5; padding:10px 18px; border-radius:8px; color:#991b1b; font-size:13px; font-weight:600;">
                ❌ Perbaiki error dulu sebelum import
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentMode === 'text'): ?>
    <!-- ── TEXT PREVIEW MODAL ───────────────────────── -->
    <div id="textPreviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:white; border-radius:16px; max-width:700px; width:90%; max-height:80vh; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="padding:18px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; color:#1e293b;" id="modalTitle">Preview Teks Script</h4>
                <button onclick="closeTextModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:#94a3b8; padding:4px;">✕</button>
            </div>
            <div id="modalBody" style="padding:24px; overflow-y:auto; max-height:60vh; font-size:14px; line-height:1.8; color:#334155; white-space:pre-wrap; word-wrap:break-word;">
            </div>
        </div>
    </div>
    <script>
    var textData = <?php echo json_encode(array_map(function($r) {
        return ['title' => $r['title'], 'text' => $r['isi_teks'] ?? ''];
    }, $rows)); ?>;

    function showTextModal(idx) {
        var item = textData[idx];
        document.getElementById('modalTitle').textContent = item.title;
        document.getElementById('modalBody').textContent = item.text;
        var modal = document.getElementById('textPreviewModal');
        modal.style.display = 'flex';
    }
    function closeTextModal() {
        document.getElementById('textPreviewModal').style.display = 'none';
    }
    document.getElementById('textPreviewModal').addEventListener('click', function(e) {
        if (e.target === this) closeTextModal();
    });
    </script>
    <?php endif; ?>

    <?php if (!empty($extract_html)): ?>
    <!-- ── HTML PREVIEW MODAL (Extract Excel Mode) ──── -->
    <style>
    #htmlPreviewModal .sheet-tabs-nav { display:flex; flex-wrap:wrap; border-bottom:1px solid #ccc; background:#f1f1f1; }
    #htmlPreviewModal .btn-sheet { border:1px solid #ccc; border-bottom:none; background:#e0e0e0; padding:8px 16px; cursor:pointer; font-size:13px; margin-right:2px; }
    #htmlPreviewModal .btn-sheet.active { background:#fff; font-weight:bold; border-top:2px solid green; }
    #htmlPreviewModal .sheet-pane { padding:15px; background:#fff; border:1px solid #ccc; border-top:none; overflow:auto; }
    #htmlPreviewModal table { border-collapse:collapse; width:100%; }
    #htmlPreviewModal td, #htmlPreviewModal th { border:1px solid #dee2e6; padding:6px 10px; font-size:12px; }
    </style>
    <div id="htmlPreviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:white; border-radius:16px; max-width:900px; width:95%; max-height:85vh; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="padding:18px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#fffbeb;">
                <h4 style="margin:0; color:#92400e;" id="htmlModalTitle">📊 Preview Excel → HTML</h4>
                <button onclick="closeHtmlModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:#94a3b8; padding:4px;">✕</button>
            </div>
            <div id="htmlModalBody" style="padding:24px; overflow-y:auto; max-height:70vh;">
            </div>
        </div>
    </div>
    <script>
    var htmlPreviews = <?php echo json_encode(array_map(function($r) {
        return ['title' => $r['title'], 'html' => $r['content_preview_html'] ?? ''];
    }, $rows)); ?>;

    function changeSheet(id, idx) {
        var modal = document.getElementById('htmlPreviewModal');
        // Hide all sheet panes
        modal.querySelectorAll('.sheet-pane').forEach(function(p) { p.style.display = 'none'; });
        // Show target pane
        var target = document.getElementById(id);
        if (target) target.style.display = 'block';
        // Reset all buttons to inactive (gray pill)
        modal.querySelectorAll('.btn-sheet-tab').forEach(function(b) {
            b.style.background = '#f3f4f6';
            b.style.color = '#4b5563';
            b.style.border = '1px solid #e5e7eb';
        });
        // Set active button (blue pill)
        var btn = document.getElementById('btn-sheet-' + idx);
        if (btn) {
            btn.style.background = '#3b82f6';
            btn.style.color = 'white';
            btn.style.border = 'none';
        }
    }

    function showHtmlModal(idx) {
        var item = htmlPreviews[idx];
        document.getElementById('htmlModalTitle').textContent = '📊 ' + item.title;
        document.getElementById('htmlModalBody').innerHTML = item.html;
        // Remove contenteditable from all panes
        document.querySelectorAll('#htmlModalBody [contenteditable]').forEach(function(el) {
            el.removeAttribute('contenteditable');
        });
        document.getElementById('htmlPreviewModal').style.display = 'flex';
    }
    function closeHtmlModal() {
        document.getElementById('htmlPreviewModal').style.display = 'none';
        document.getElementById('htmlModalBody').innerHTML = '';
    }
    document.getElementById('htmlPreviewModal').addEventListener('click', function(e) {
        if (e.target === this) closeHtmlModal();
    });
    </script>
    <?php endif; ?>

    <?php elseif ($step === 'result'): ?>
    <!-- ═══════════════════════════════════════════════════════ -->
    <!--  STEP 3: IMPORT RESULTS                                -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="card">
        <h4 style="margin:0 0 15px 0; display:flex; align-items:center; gap:8px;">
            <span style="background:#dcfce7; color:#166534; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700;">SELESAI</span>
            Hasil Import
        </h4>

        <!-- Stats -->
        <div style="display:flex; gap:15px; margin-bottom:20px;">
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:16px 24px; border-radius:12px; flex:1; text-align:center;">
                <div style="font-size:32px; font-weight:700; color:#16a34a;"><?php echo $successCount ?? 0; ?></div>
                <div style="font-size:13px; color:#166534; font-weight:600;">✅ Berhasil</div>
            </div>
            <?php if (($failCount ?? 0) > 0): ?>
            <div style="background:#fef2f2; border:1px solid #fca5a5; padding:16px 24px; border-radius:12px; flex:1; text-align:center;">
                <div style="font-size:32px; font-weight:700; color:#dc2626;"><?php echo $failCount; ?></div>
                <div style="font-size:13px; color:#991b1b; font-weight:600;">❌ Gagal</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Result Table -->
        <div style="overflow-x:auto; margin-bottom:20px;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0; width:40px;">Status</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">No Tiket</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Judul</th>
                        <th style="padding:10px; border-bottom:2px solid #e2e8f0;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($results ?? []) as $res): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px; text-align:center;"><?php echo $res['status'] === 'success' ? '✅' : '❌'; ?></td>
                        <td style="padding:10px; font-weight:600;"><?php echo htmlspecialchars($res['ticket']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($res['title']); ?></td>
                        <td style="padding:10px; font-size:12px; color:<?php echo $res['status'] === 'success' ? '#16a34a' : '#dc2626'; ?>;">
                            <?php echo htmlspecialchars($res['message']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; gap:10px;">
            <a href="?controller=legacyImport&action=index"
               style="background:#3b82f6; color:white; text-decoration:none; padding:10px 20px; border-radius:8px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:6px;">
                📥 Import Lagi
            </a>
            <a href="?controller=dashboard&action=library"
               style="background:#f1f5f9; color:#334155; text-decoration:none; padding:10px 20px; border-radius:8px; font-weight:600; font-size:14px;">
                Lihat Script Library →
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
