<?php foreach ($libraryItems as $item): ?>
<div style="background:white; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:all 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column;">
    <!-- Card Header -->
    <div style="padding:15px; border-bottom:1px solid #f1f5f9; background:#f8fafc;">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <!-- Top Left: Media Channel (Red) -->
            <div style="font-weight:700; color:var(--primary-red); font-size:16px;">
                <?php 
                    // REQ: Media displayed prominently in Red
                    echo htmlspecialchars($item['request_media'] ?? $item['media'] ?? '-');
                ?>
            </div>
            <!-- Top Right: Start Date -->
            <div style="font-size:13px; font-weight:500; color:#334155;">
                <?php 
                    $startDateVal = $item['start_date'] ?? null;
                    if ($startDateVal instanceof DateTime) {
                        echo $startDateVal->format('d M Y');
                    } elseif (is_string($startDateVal) && !empty($startDateVal)) {
                        echo date('d M Y', strtotime($startDateVal));
                    } else {
                        echo '-';
                    }
                ?>
                <?php if (!empty($canManage)): ?>
                    <!-- Active Badge Removed (Moved to Footer Button) -->
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Second Row: Metadata (Jenis | Produk | Kategori) -->
        <div style="margin-top:5px; font-size:12px; color:#64748b; font-weight:500; display:flex; gap:5px; flex-wrap:wrap;">
             <span><?php echo htmlspecialchars($item['jenis'] ?? '-'); ?></span>
             <span style="color:#cbd5e1;">|</span>
             <span><?php echo htmlspecialchars($item['produk'] ?? '-'); ?></span>
             <span style="color:#cbd5e1;">|</span>
             <span><?php echo htmlspecialchars($item['kategori'] ?? '-'); ?></span>
        </div>
        

    </div>

    <!-- Card Body (Content Highlight) -->
    <div style="padding:15px; flex-grow:1; display:flex; flex-direction:column; gap:10px;">
        <!-- TITLE (Optional now if info is in header, but good to keep) -->
        <div style="font-weight:700; font-size:14px; color:#1e293b; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:40px;">
             <?php echo htmlspecialchars($item['title'] ?? '-'); ?>
        </div>

        <?php 
            // Simplified extraction - just strip tags and show text
            $rawContent = $item['content'] ?? '';
            $cleanText = strip_tags($rawContent);
            $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText)); // normalize whitespace
            
            // For FILE_UPLOAD, if no clean text found, show friendly message
            if (empty($cleanText) && ($item['mode'] ?? '') === 'FILE_UPLOAD') {
                $cleanText = '📊 Excel file attached - Click "View Detail" to see full content';
            }
        ?>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0; color:#334155; height:70px; overflow:hidden; position:relative; font-size:12px; line-height:1.6;">
            <?php 
                if (!empty($cleanText)) {
                    echo htmlspecialchars(substr($cleanText, 0, 200));
                    if (strlen($cleanText) > 200) echo '...';
                } else {
                    echo '<span style="color:#999; font-style:italic;">No preview available</span>';
                }
            ?>
        </div>

        <?php 
            // If File Upload, show small filename badge at bottom of preview (Moved from top)
            if (($item['mode'] ?? '') === 'FILE_UPLOAD'):
        ?>
                <div style="display:flex; align-items:center; gap:6px; color:#166534; background:#f0fdf4; padding:4px 8px; border-radius:6px; border:1px solid #bbf7d0; font-size:10px; margin-top:-2px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($item['filename'] ?? 'Attached File'); ?></span>
                </div>
        <?php endif; ?>
    </div>

    <!-- Card Footer -->
    <div style="padding:15px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
        <!-- Left: Ticket ID & Script Number -->
        <div>
            <!-- Ticket ID -->
            <div style="font-size:12px; font-weight:700; color:#475569;">
                <?php 
                    $tId = $item['ticket_id'] ?? $item['request_id'];
                    echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                ?>
            </div>
            <!-- Script Number (Ordinary Gray) -->
            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">
                <?php echo htmlspecialchars($item['script_number']); ?>
            </div>
        </div>

        <!-- Right: Action Buttons -->
        <div style="display:flex; gap:5px; align-items:center;">
            <?php 
            $u = strtolower($_SESSION['user']['userid'] ?? '');
            $r = $_SESSION['user']['dept'] ?? '';
            if ($r === 'ADMIN' || in_array($u, ['admin', 'admin_script'])): 
            ?>
            <a href="?controller=audit&action=delete&id=<?php echo $item['request_id']; ?>&redirect=library" onclick="return confirm('Yakin ingin menghapus script ini dari Library?');" style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; background:#fee2e2; border:1px solid #f87171; border-radius:6px; color:#b91c1c; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05);" title="Delete Script">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </a>
            <?php endif; ?>
            
            <a href="?controller=request&action=viewLibrary&id=<?php echo $item['request_id']; ?>" 
               style="display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#fff; border:1px solid var(--primary-red); border-radius:6px; color:var(--primary-red); font-weight:700; text-decoration:none; font-size:12px; transition:all 0.2s; box-shadow:0 2px 4px rgba(211,47,47,0.08);" onmouseover="this.style.background='var(--primary-red)'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='var(--primary-red)';">
                <i class="fi fi-rr-eye" style="font-size:14px;"></i>
                View Detail
            </a>
            
            <?php if (!empty($canManage)): ?>
            <?php 
                $isActive = (int)($item['is_active'] ?? 0); 
                $isSched = false;
                if ($isActive && !empty($item['start_date'])) {
                    $td = new DateTime('today');
                    $st = ($item['start_date'] instanceof DateTime) ? clone $item['start_date'] : new DateTime($item['start_date']);
                    $st->setTime(0,0,0);
                    if ($st > $td) $isSched = true;
                }
            ?>
            <button onclick="toggleActivation(<?php echo $item['request_id']; ?>, <?php echo $isActive; ?>)" 
                   style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; background:<?php echo !$isActive ? 'white' : ($isSched ? '#fffbeb' : '#f0fdf4'); ?>; border:1px solid <?php echo !$isActive ? '#cbd5e1' : ($isSched ? '#fcd34d' : '#16a34a'); ?>; border-radius:6px; color:<?php echo !$isActive ? '#475569' : ($isSched ? '#d97706' : '#15803d'); ?>; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.05);" title="<?php echo !$isActive ? 'Activate' : ($isSched ? 'Scheduled - Click to Deact' : 'Active - Click to Deact'); ?>">
                <?php if ($isSched): ?>
                    <i class="fi fi-rr-calendar-clock" style="font-size:16px;"></i>
                <?php else: ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                <?php endif; ?>
            </button>
            <?php endif; ?>
            

        </div>
    </div>
</div>
<?php endforeach; ?>
