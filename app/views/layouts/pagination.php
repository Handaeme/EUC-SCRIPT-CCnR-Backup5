<?php
/**
 * Reusable Pagination Bar
 * 
 * Required variables (set before including this file):
 *   $currentPage  - Current page number (int)
 *   $totalPages   - Total number of pages (int)
 *   $totalItems   - Total number of items (int)
 *   $perPage      - Items per page (int)
 * 
 * Optional:
 *   $_GET params are automatically preserved in links
 */

if (!isset($currentPage) || !isset($totalPages) || !isset($totalItems) || !isset($perPage)) return;
if ($totalPages <= 1 && $totalItems <= $perPage) {
    // Still show the per-page dropdown even on single page if there are items
    if ($totalItems <= 0) return;
}

// Build base URL preserving all current GET params except 'page'
$_pgParams = $_GET;
unset($_pgParams['page']);
$_pgBase = '?' . http_build_query($_pgParams);
$_pgStart = (($currentPage - 1) * $perPage) + 1;
$_pgEnd = min($currentPage * $perPage, $totalItems);

// Build per-page change URL (reset to page 1)
$_ppParams = $_GET;
unset($_ppParams['page']);
unset($_ppParams['per_page']);
$_ppBase = '?' . http_build_query($_ppParams);
$_ppOptions = [5, 10, 20, 30, 50];
?>

<div style="display:flex; justify-content:space-between; align-items:center; padding:16px 0; margin-top:16px; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:10px;">
    <!-- Info + Per Page Selector -->
    <div style="display:flex; align-items:center; gap:15px;">
        <div style="font-size:13px; color:#64748b;">
            Showing <strong><?php echo $_pgStart; ?>-<?php echo $_pgEnd; ?></strong> of <strong><?php echo $totalItems; ?></strong> items
        </div>
        <div style="display:flex; align-items:center; gap:6px; font-size:13px; color:#64748b;">
            <span>Show</span>
            <select onchange="window.location.href='<?php echo htmlspecialchars($_ppBase); ?>&per_page='+this.value+'&page=1'" style="padding:4px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155; background:white; cursor:pointer;">
                <?php foreach ($_ppOptions as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo ($perPage == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
            <span>per page</span>
        </div>
    </div>
    
    <!-- Nav Buttons -->
    <div style="display:flex; align-items:center; gap:4px;">
        <!-- Prev -->
        <?php if ($currentPage > 1): ?>
            <a href="<?php echo htmlspecialchars($_pgBase . '&page=' . ($currentPage - 1)); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s; background:white;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">&laquo; Prev</a>
        <?php else: ?>
            <span style="padding:8px 12px; border:1px solid #f1f5f9; border-radius:6px; color:#cbd5e1; font-size:13px; font-weight:600; cursor:not-allowed;">&laquo; Prev</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php
        $range = 2;
        $startPage = max(1, $currentPage - $range);
        $endPage = min($totalPages, $currentPage + $range);
        
        if ($startPage > 1): ?>
            <a href="<?php echo htmlspecialchars($_pgBase . '&page=1'); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; background:white;">1</a>
            <?php if ($startPage > 2): ?><span style="padding:8px 4px; color:#94a3b8;">...</span><?php endif; ?>
        <?php endif; ?>
        
        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
            <?php if ($p == $currentPage): ?>
                <span style="padding:8px 12px; background:var(--primary-red, #d32f2f); color:white; border-radius:6px; font-size:13px; font-weight:700;"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($_pgBase . '&page=' . $p); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s; background:white;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?><span style="padding:8px 4px; color:#94a3b8;">...</span><?php endif; ?>
            <a href="<?php echo htmlspecialchars($_pgBase . '&page=' . $totalPages); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; background:white;"><?php echo $totalPages; ?></a>
        <?php endif; ?>
        
        <!-- Next -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($_pgBase . '&page=' . ($currentPage + 1)); ?>" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px; text-decoration:none; color:#374151; font-size:13px; font-weight:600; transition:all 0.2s; background:white;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Next &raquo;</a>
        <?php else: ?>
            <span style="padding:8px 12px; border:1px solid #f1f5f9; border-radius:6px; color:#cbd5e1; font-size:13px; font-weight:600; cursor:not-allowed;">Next &raquo;</span>
        <?php endif; ?>
    </div>
</div>
