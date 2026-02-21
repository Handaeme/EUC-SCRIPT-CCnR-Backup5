<div class="filter-bar" style="margin-bottom:20px; display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end; background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee;">
    <!-- Date Filter -->
    <form method="GET" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <!-- Preserve other GET params -->
        <?php foreach($_GET as $key => $val): ?>
            <?php if(!in_array($key, ['start_date', 'end_date'])): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div>
            <label style="font-size:12px; font-weight:bold; color:#555; display:block; margin-bottom:4px;">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <div>
            <label style="font-size:12px; font-weight:bold; color:#555; display:block; margin-bottom:4px;">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:7px 15px; background:var(--primary-red); color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer; height:36px;">Filter</button>
        <?php if(!empty($startDate) || !empty($endDate)): ?>
             <a href="?<?php echo http_build_query(array_diff_key($_GET, array_flip(['start_date', 'end_date']))); ?>" style="color:#d32f2f; font-size:13px; text-decoration:underline; align-self:center; margin-left:5px;">Reset</a>
        <?php endif; ?>
    </form>
    
    <!-- Search Box -->
    <div style="flex:1; text-align:right; min-width:200px;">
         <label style="font-size:12px; font-weight:bold; color:#555; display:block; margin-bottom:4px;">Quick Search (Live)</label>
         <input type="text" id="searchInput" oninput="window.filterTable('searchInput', 'dataTable')" autocomplete="off" placeholder="Type to search..." style="padding:7px 10px; width:100%; max-width:250px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
    </div>
</div>
