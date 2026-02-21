<?php if (($totalPages ?? 1) > 1): ?>
<?php
    // Build base URL preserving all current GET params except 'page' and 'ajax'
    $paginationParams = $_GET;
    unset($paginationParams['page']);
    unset($paginationParams['ajax']); // Don't include ajax param in links
    $baseUrl = '?' . http_build_query($paginationParams);
    
    // Ensure current page/total items are set (defaults handled in Controller but good for safety)
    $currentPage = $currentPage ?? 1;
    $perPage = $perPage ?? 10;
    $totalItems = $totalItems ?? 0;
    
    $startItem = (($currentPage - 1) * $perPage) + 1;
    $endItem = min($currentPage * $perPage, $totalItems);
?>
<div style="display:flex; justify-content:space-between; align-items:center; padding:16px 0; margin-top:16px; border-top:1px solid #e2e8f0;">
    <!-- Info -->
    <div style="font-size:13px; color:#64748b;">
        Showing <strong><?php echo $startItem; ?>-<?php echo $endItem; ?></strong> of <strong><?php echo $totalItems; ?></strong> scripts
    </div>
    
    <!-- Nav Buttons -->
    <div style="display:flex; align-items:center; gap:4px;">
        <!-- Prev -->
        <?php if ($currentPage > 1): ?>
            <a href="<?php echo htmlspecialchars($baseUrl . '&page=' . ($currentPage - 1)); ?>" class="pagination-link" data-page="<?php echo ($currentPage - 1); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">&laquo; Prev</a>
        <?php else: ?>
            <span style="padding:8px 12px; border:1px solid #f1f5f9; border-radius:6px; color:#cbd5e1; font-size:13px; font-weight:600; cursor:not-allowed;">&laquo; Prev</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php
        $range = 2; // Show 2 pages before and after current
        $startPage = max(1, $currentPage - $range);
        $endPage = min($totalPages, $currentPage + $range);
        
        if ($startPage > 1): ?>
            <a href="<?php echo htmlspecialchars($baseUrl . '&page=1'); ?>" class="pagination-link" data-page="1" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600;">1</a>
            <?php if ($startPage > 2): ?><span style="padding:8px 4px; color:#94a3b8;">...</span><?php endif; ?>
        <?php endif; ?>
        
        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
            <?php if ($p == $currentPage): ?>
                <span style="padding:8px 12px; background:var(--primary-red); color:white; border-radius:6px; font-size:13px; font-weight:700;"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($baseUrl . '&page=' . $p); ?>" class="pagination-link" data-page="<?php echo $p; ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?><span style="padding:8px 4px; color:#94a3b8;">...</span><?php endif; ?>
            <a href="<?php echo htmlspecialchars($baseUrl . '&page=' . $totalPages); ?>" class="pagination-link" data-page="<?php echo $totalPages; ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600;"><?php echo $totalPages; ?></a>
        <?php endif; ?>
        
        <!-- Next -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($baseUrl . '&page=' . ($currentPage + 1)); ?>" class="pagination-link" data-page="<?php echo ($currentPage + 1); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Next &raquo;</a>
        <?php else: ?>
            <span style="padding:8px 12px; border:1px solid #f1f5f9; border-radius:6px; color:#cbd5e1; font-size:13px; font-weight:600; cursor:not-allowed;">Next &raquo;</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
