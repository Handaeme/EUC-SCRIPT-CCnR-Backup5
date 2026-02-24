<?php foreach ($libraryItems as $item): ?>
<tr style="border-bottom:1px solid #eee;">
    <td style="padding:12px;">
        <div style="display:flex; gap:5px; align-items:center;">
            <a href="?controller=request&action=viewLibrary&id=<?php echo $item['request_id']; ?>" 
               style="display:inline-flex; align-items:center; gap:5px; padding:6px 14px; background:#fff; border:1px solid var(--primary-red); border-radius:6px; color:var(--primary-red); font-weight:700; text-decoration:none; font-size:11px; transition:all 0.2s; box-shadow:0 1px 2px rgba(211,47,47,0.05);" onmouseover="this.style.background='var(--primary-red)'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='var(--primary-red)';">
                View
            </a>
            
            <?php 
            $u = strtolower($_SESSION['user']['userid'] ?? '');
            $r = $_SESSION['user']['dept'] ?? '';
            if ($r === 'ADMIN' || in_array($u, ['admin', 'admin_script'])): 
            ?>
            <a href="?controller=audit&action=delete&id=<?php echo $item['request_id']; ?>&redirect=library" onclick="return confirm('Yakin ingin menghapus script ini dari Library?');" style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:#fee2e2; border:1px solid #f87171; border-radius:6px; color:#b91c1c; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05);" title="Delete Script">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </a>
            <?php endif; ?>
            
            <?php if (!empty($canManage)): ?>
            <div style="margin-top:5px;">
                <?php 
                   $isActive = (int)($item['is_active'] ?? 0); 
                   $isSched = false;
                   if ($isActive && !empty($item['start_date'])) {
                       // Handle both string or DateTime object from database driver
                       $todayVal = $sqlServerToday ?? 'today';
                       $td = ($todayVal instanceof DateTime) ? clone $todayVal : new DateTime($todayVal);
                       $td->setTime(0,0,0);
                       
                       $dbDate = $item['start_date'];
                       $st = ($dbDate instanceof DateTime) ? clone $dbDate : new DateTime($dbDate);
                       $st->setTime(0,0,0);
                       
                       if ($st > $td) $isSched = true;
                   }
                ?>
                <button onclick="toggleActivation(<?php echo $item['request_id']; ?>, <?php echo $isActive; ?>)" 
                        style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:4px; border:1px solid <?php echo !$isActive ? '#cbd5e1' : ($isSched ? '#fcd34d' : '#16a34a'); ?>; background:<?php echo !$isActive ? '#f8fafc' : ($isSched ? '#fffbeb' : '#f0fdf4'); ?>; color:<?php echo !$isActive ? '#64748b' : ($isSched ? '#d97706' : '#15803d'); ?>; border-radius:6px; padding:4px; font-size:10px; font-weight:600; cursor:pointer;" title="Click to toggle">
                    <?php echo !$isActive ? 'Inactive' : ($isSched ? '<i class="fi fi-rr-calendar-clock"></i> Sched' : 'Active'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </td>
    <td style="padding:12px; white-space:nowrap;">
        <div style="font-weight:600; color:var(--primary-red);"><?php 
            $tId = $item['ticket_id'] ?? 'Pending';
            echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
        ?></div>
    </td>
    <td style="padding:12px; white-space:nowrap;">
        <?php echo htmlspecialchars($item['script_number']); ?>
    </td>
    <td style="padding:12px;">
        <?php echo htmlspecialchars($item['jenis'] ?? '-'); ?>
    </td>
    <td style="padding:12px;">
        <?php echo htmlspecialchars($item['produk'] ?? '-'); ?>
    </td>
    <td style="padding:12px;">
        <?php echo htmlspecialchars($item['kategori'] ?? '-'); ?>
    </td>
    <td style="padding:12px; white-space:nowrap;">
        <?php 
            // Use request_media for deduplicated list (e.g. WA, SMS)
            echo htmlspecialchars($item['request_media'] ?? $item['media'] ?? '');
        ?>
    </td>
    <td style="padding:12px; max-width:300px;">
        <?php 
            // Use aggregated content (Filename for File Upload, Consolidated Text for Free Input)
            $showContent = $item['content_aggregated'] ?? $item['content'] ?? '';
            
            if (!empty($showContent)) {
                 // Use fixed width and truncated text to prevent layout breakage
                 echo '<div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12px; max-width:250px; display:block;" title="' . htmlspecialchars($showContent) . '">';
                 echo htmlspecialchars($showContent);
                 echo '</div>';
            } else {
                 echo '<span style="color:#999; font-style:italic;">-</span>';
            }
        ?>
    </td>
    <!-- Created Date (Request) -->
    <?php 
        $createdTimestamp = 0;
        if (isset($item['request_created_at'])) {
            $createdTimestamp = ($item['request_created_at'] instanceof DateTime) ? $item['request_created_at']->getTimestamp() : strtotime($item['request_created_at']);
        }
    ?>
    <td style="padding:12px; white-space:nowrap; color: #888;" data-sort-val="<?php echo $createdTimestamp; ?>">
         <?php 
             if (isset($item['request_created_at'])) {
                 echo ($item['request_created_at'] instanceof DateTime) ? $item['request_created_at']->format('d M Y') : date('d M Y', strtotime($item['request_created_at']));
             } else {
                 echo '-';
             }
         ?>
    </td>
    <!-- Published Date (Library) -->
    <?php 
        $publishedTimestamp = ($item['created_at'] instanceof DateTime) ? $item['created_at']->getTimestamp() : strtotime($item['created_at']);
    ?>
    <td style="padding:12px; white-space:nowrap;" data-sort-val="<?php echo $publishedTimestamp; ?>">
         <span style="font-weight:bold;"><?php echo ($item['created_at'] instanceof DateTime) ? $item['created_at']->format('d M Y') : date('d M Y', strtotime($item['created_at'])); ?></span>
    </td>
    <!-- Start Date -->
    <?php 
        $startTimestamp = 0;
        if (!empty($item['start_date'])) {
            $startTimestamp = ($item['start_date'] instanceof DateTime) ? $item['start_date']->getTimestamp() : strtotime($item['start_date']);
        }
    ?>
    <td style="padding:12px; white-space:nowrap;" data-sort-val="<?php echo $startTimestamp; ?>">
         <span style="font-weight:700; color:#334155;">
             <?php if (!empty($item['start_date'])): ?>
                <?php echo ($item['start_date'] instanceof DateTime) ? $item['start_date']->format('d M Y') : date('d M Y', strtotime($item['start_date'])); ?>
             <?php else: ?>
                 -
             <?php endif; ?>
         </span>
    </td>
</tr>
<?php endforeach; ?>
