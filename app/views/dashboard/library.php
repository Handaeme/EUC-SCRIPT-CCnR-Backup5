<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
require_once __DIR__ . '/library_helpers.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Script Library</h2>
            <p style="color:var(--text-secondary);">Browse all finalized and published scripts.</p>
        </div>
        <?php 
            $libExportParams = $_GET;
            $libExportParams['controller'] = 'dashboard';
            $libExportParams['action'] = 'exportLibrary';
            $libExportUrl = '?' . http_build_query($libExportParams);
        ?>
        <a href="<?php echo htmlspecialchars($libExportUrl); ?>" 
           style="background:#10b981; color:white; text-decoration:none; padding:8px 16px; border-radius:8px; font-weight:bold; font-size:13px; display:flex; align-items:center; gap:8px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
           <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
           Export Excel
        </a>
    </div>

    <!-- LIBRARY STATS CARDS -->
    <?php 
        $totalScripts = $totalItems ?? count($libraryItems);
        
        // Define Dynamic Filters to show as cards
        $dynamicStats = [];
        
        // 1. Media Channel (Prioritized as requested)
        if (!empty($_GET['media'])) {
            $val = is_array($_GET['media']) ? implode(', ', $_GET['media']) : $_GET['media'];
            $dynamicStats[] = [
                'label' => 'Media Channel',
                'value' => $val,
                'color' => 'blue', // Theme color
                'icon' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>'
            ];
        }
        
        // 2. Jenis
        if (!empty($_GET['jenis'])) {
            $val = is_array($_GET['jenis']) ? implode(', ', $_GET['jenis']) : $_GET['jenis'];
            $dynamicStats[] = [
                'label' => 'Jenis Script',
                'value' => $val,
                'color' => 'indigo',
                'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>'
            ];
        }
        
        // 3. Produk
        if (!empty($_GET['produk'])) {
            $val = is_array($_GET['produk']) ? implode(', ', $_GET['produk']) : $_GET['produk'];
            $dynamicStats[] = [
                'label' => 'Produk',
                'value' => $val,
                'color' => 'emerald',
                'icon' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>'
            ];
        }
        
        // 4. Kategori
        if (!empty($_GET['kategori'])) {
            $val = is_array($_GET['kategori']) ? implode(', ', $_GET['kategori']) : $_GET['kategori'];
            $dynamicStats[] = [
                'label' => 'Kategori',
                'value' => $val,
                'color' => 'amber',
                'icon' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>'
            ];
        }
    ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:25px;">
        <!-- Total Scripts Card (Always Visible) -->
        <div class="card" style="padding:20px; display:flex; align-items:center; gap:15px; border-left:4px solid var(--primary-red);">
            <div style="width:48px; height:48px; background:#fee2e2; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--primary-red);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div>
                <div style="font-size:24px; font-weight:700; color:#1e293b;"><?php echo number_format($totalScripts); ?></div>
                <div style="font-size:13px; color:#64748b;">Total Scripts</div>
            </div>
        </div>
        
        <!-- Dynamic Filter Cards -->
        <?php foreach($dynamicStats as $stat): 
            // Color Mapping
            $colors = [
                'blue' => ['bg'=>'#eff6ff', 'text'=>'#3b82f6', 'border'=>'#3b82f6'],
                'indigo' => ['bg'=>'#eef2ff', 'text'=>'#6366f1', 'border'=>'#6366f1'],
                'emerald' => ['bg'=>'#ecfdf5', 'text'=>'#10b981', 'border'=>'#10b981'],
                'amber' => ['bg'=>'#fffbeb', 'text'=>'#f59e0b', 'border'=>'#f59e0b'],
            ];
            $theme = $colors[$stat['color']] ?? $colors['blue'];
        ?>
        <div class="card" style="padding:20px; display:flex; align-items:center; gap:15px; border-left:4px solid <?php echo $theme['border']; ?>;">
            <div style="width:48px; height:48px; background:<?php echo $theme['bg']; ?>; border-radius:12px; display:flex; align-items:center; justify-content:center; color:<?php echo $theme['text']; ?>;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $stat['icon']; ?></svg>
            </div>
            <div style="min-width:0;"> <!-- min-width:0 required for flex child truncation -->
                <div style="font-size:18px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($stat['value']); ?>">
                    <?php echo htmlspecialchars($stat['value']); ?>
                </div>
                <div style="font-size:13px; color:#64748b; margin-top:4px; display:flex; align-items:center; gap:8px;">
                    <?php echo htmlspecialchars($stat['label']); ?>
                    <span style="background:<?php echo $theme['bg']; ?>; color:<?php echo $theme['text']; ?>; padding:4px 10px; border-radius:16px; font-size:14px; font-weight:700;">
                        <?php echo number_format($totalScripts); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <h4 style="margin:0;">Published Documents</h4>
            </div>
            
            <!-- View Mode Toggles -->
            <div style="background:#f1f5f9; padding:4px; border-radius:8px; display:flex; gap:4px;">
                <button onclick="setViewMode('list')" id="btn-list" style="border:none; background:white; padding:6px 10px; border-radius:6px; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.1); color:#374151;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </button>
                <button onclick="setViewMode('grid')" id="btn-grid" style="border:none; background:transparent; padding:6px 10px; border-radius:6px; cursor:pointer; color:#64748b;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </button>
            </div>
        </div>
        
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="dashboard">
                <input type="hidden" name="action" value="library">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Moved to Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <!-- Search Icon -->
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            
                            <input type="text" id="searchInput" oninput="performLiveSearch(this.value)" autocomplete="off" placeholder="Search Title, Content, Ticket..." value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                   style="padding:8px 30px 8px 32px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px; transition:border-color 0.2s; outline:none;"
                                   onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#cbd5e1'">
                            
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>
                        
                        <script>
                            let searchTimeout;
                            
                            function performLiveSearch(query) {
                                const clearBtn = document.getElementById('clearSearchBtn');
                                if (clearBtn) clearBtn.style.display = query.length > 0 ? 'block' : 'none';

                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    updateLibraryResults(query);
                                }, 300); // Debounce 300ms
                            }

                            function updateLibraryResults(query) {
                                // Construct URL with existing filters + new search
                                const urlParams = new URLSearchParams(window.location.search);
                                if (query) {
                                    urlParams.set('search', query);
                                    urlParams.delete('page'); // Reset to page 1 on search
                                } else {
                                    urlParams.delete('search');
                                    urlParams.delete('page');
                                }
                                urlParams.set('ajax', '1');

                                // Show loading state (optional)
                                document.getElementById('dataTable').style.opacity = '0.5';
                                document.getElementById('view-grid').style.opacity = '0.5';

                                fetch(`${window.location.pathname}?${urlParams.toString()}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        // Update Table
                                        const tbody = document.querySelector('#dataTable tbody');
                                        if (tbody) tbody.innerHTML = data.rows;
                                        
                                        // Update Grid
                                        const grid = document.getElementById('view-grid');
                                        if (grid) grid.innerHTML = data.grid;
                                        
                                        // Update Pagination
                                        const pagContainer = document.getElementById('pagination-container');
                                        if (pagContainer) pagContainer.innerHTML = data.pagination;
                                        
                                        // Update Total Count (optional, if we want to update the stats card)
                                        // But stats card logic is PHP-based. We might want to update it via JS if returned in JSON.
                                        // For now, simple Table/Grid update.
                                        
                                        // Restore Opacity
                                        document.getElementById('dataTable').style.opacity = '1';
                                        document.getElementById('view-grid').style.opacity = '1';
                                        
                                        // Update Browser URL (History)
                                        urlParams.delete('ajax');
                                        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
                                        window.history.pushState({path: newUrl}, '', newUrl);
                                        
                                        // Re-attach listeners if needed (e.g. pagination clicks to be AJAX? For now links work as full reload)
                                        
                                    })
                                    .catch(err => {
                                        console.error('Search Error:', err);
                                        document.getElementById('dataTable').style.opacity = '1';
                                        document.getElementById('view-grid').style.opacity = '1';
                                    });
                            }

                            function clearSearch() {
                                const el = document.getElementById('searchInput');
                                el.value = '';
                                performLiveSearch('');
                                el.focus();
                            }
                        </script>
                    </div>
                    <div style="display:flex; align-items:center;">
                        <?php if(!empty($_GET['start_date']) || !empty($_GET['jenis']) || !empty($_GET['produk']) || !empty($_GET['kategori']) || !empty($_GET['media'])): ?>
                            <a href="?controller=dashboard&action=library" style="margin-right:15px; color:#64748b; font-size:13px; text-decoration:none;">Reset All</a>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                            Apply Filters
                        </button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:15px; align-items: end;">
                    
                    <!-- Date Range -->
                    <div style="grid-column: span 2;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:8px;">
                            <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Date Range</label>
                            <select name="date_type" style="font-size:11px; border:none; background:transparent; color:#3b82f6; font-weight:700; cursor:pointer; outline:none;" onchange="this.form.submit()">
                                <option value="created_at" <?php echo ($dateType ?? 'created_at') === 'created_at' ? 'selected' : ''; ?>>Published Date</option>
                                <option value="start_date" <?php echo ($dateType ?? 'created_at') === 'start_date' ? 'selected' : ''; ?>>Start Date</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                        </div>
                    </div>

                    <?php 
                    $filterLabels = [
                        'jenis' => 'Jenis', 
                        'produk' => 'Produk', 
                        'kategori' => 'Kategori', 
                        'media' => 'Media Channel'
                    ];
                    
                    foreach ($filterLabels as $key => $label): 
                        $options = $filterOptions[$key] ?? [];
                        $selected = $activeFilters[$key] ?? [];
                        $displayText = "Select $label";
                        $count = count($selected);
                        if ($count > 0) {
                            $displayText = implode(', ', $selected);
                            if (strlen($displayText) > 20) {
                                $displayText = substr($displayText, 0, 18) . '...';
                            }
                        }
                    ?>
                    <div class="dropdown" style="position:relative;">
                        <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px;"><?php echo $label; ?></label>
                        <button type="button" onclick="toggleDropdown('dd-<?php echo $key; ?>')" style="width:100%; text-align:left; background:white; border:1px solid #cbd5e1; padding:8px 12px; border-radius:6px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#334155;">
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px;"><?php echo htmlspecialchars($displayText); ?></span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        
                        <!-- Dropdown Content -->
                        <div id="dd-<?php echo $key; ?>" class="dropdown-content" style="display:none; position:absolute; top:100%; left:0; width:100%; background:white; border:1px solid #e2e8f0; border-radius:6px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); z-index:50; margin-top:4px; max-height:200px; overflow-y:auto; padding:8px;">
                             <?php if(empty($options)): ?>
                                <div style="color:#94a3b8; font-size:12px; padding:4px;">No options available</div>
                            <?php else: ?>
                                <?php foreach($options as $opt): 
                                    $isChecked = in_array($opt, $selected) ? 'checked' : '';
                                ?>
                                <label style="display:flex; align-items:center; gap:8px; padding:6px; font-size:13px; color:#334155; cursor:pointer; border-radius:4px; transition:background 0.1s;">
                                    <input type="checkbox" name="<?php echo $key; ?>[]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo $isChecked; ?> style="accent-color:#0f172a;">
                                    <?php echo htmlspecialchars($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Sort Options -->
                    <div style="grid-column: span 1;">
                        <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px;">Sort By</label>
                        <select name="sort_by" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155; background:white;" onchange="this.form.submit()">
                            <option value="request_created_at" <?php echo ($sortBy ?? 'created_at') === 'request_created_at' ? 'selected' : ''; ?>>Created Date</option>
                            <option value="created_at" <?php echo ($sortBy ?? 'created_at') === 'created_at' ? 'selected' : ''; ?>>Published Date</option>
                            <option value="start_date" <?php echo ($sortBy ?? 'created_at') === 'start_date' ? 'selected' : ''; ?>>Start Date</option>
                        </select>
                    </div>

                    <div style="grid-column: span 1;">
                        <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px;">Order</label>
                        <select name="sort_published" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155; background:white;" onchange="this.form.submit()">
                            <option value="DESC" <?php echo ($sortPublished ?? 'DESC') === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="ASC" <?php echo ($sortPublished ?? 'DESC') === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>

                    <script>
                    function toggleDropdown(id) {
                        // Close all others first
                        document.querySelectorAll('.dropdown-content').forEach(d => {
                            if(d.id !== id) d.style.display = 'none';
                        });
                        
                        var el = document.getElementById(id);
                        if (el.style.display === 'none') {
                            el.style.display = 'block';
                        } else {
                            el.style.display = 'none';
                        }
                    }
                    
                    // Close when clicking outside
                    window.addEventListener('click', function(e) {
                        if (!e.target.closest('.dropdown')) {
                            document.querySelectorAll('.dropdown-content').forEach(d => {
                                d.style.display = 'none';
                            });
                        }
                    });
                    </script>

                </div>

                <!-- Moved Apply Button to Bottom Right -->
                <!-- Apply Button Moved to Top -->

                <!-- Active Filter Tags -->
                <?php 
                $hasActiveFilters = false;
                foreach($activeFilters as $key => $vals) {
                    if(!empty($vals)) $hasActiveFilters = true;
                }
                
                if ($hasActiveFilters || !empty($startDate)): 
                ?>
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #f1f5f9; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span style="font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-right:5px;">Active Filters:</span>
                    <?php if(!empty($startDate)): ?>
                        <span style="background:#fef2f2; color:#991b1b; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; border:1px solid #fecaca;">
                            <?php echo ($dateType === 'start_date' ? 'Start' : 'Published'); ?> Date: <?php echo date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)); ?>
                            <?php 
                                $params = $_GET;
                                unset($params['start_date'], $params['end_date']);
                                $url = '?' . http_build_query($params);
                            ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" style="text-decoration:none; color:#991b1b; display:flex; align-items:center;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php foreach($activeFilters as $key => $vals): ?>
                        <?php foreach($vals as $val): ?>
                            <span style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; border:1px solid #bae6fd;">
                                <?php echo htmlspecialchars($filterLabels[$key] ?? $key) . ': ' . htmlspecialchars($val); ?>
                                <!-- Simple removal link by excluding this value -->
                                <?php 
                                    // Build URL to remove this specific filter
                                    $params = $_GET;
                                    $k = array_search($val, $params[$key]);
                                    if($k !== false) unset($params[$key][$k]);
                                    $url = '?' . http_build_query($params);
                                ?>
                                <a href="<?php echo htmlspecialchars($url); ?>" style="text-decoration:none; color:#0369a1; display:flex; align-items:center;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </a>
                            </span>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                     <a href="?controller=dashboard&action=library" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        
        <?php if (empty($libraryItems)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No finalized scripts in the library yet.</p>
            </div>
        <?php else: ?>
            
            <!-- LIST VIEW (TABLE) -->
            <div id="view-list" style="display:block; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left;">
                            <th style="padding:12px; border-bottom:2px solid #eee; width:100px;">Action</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Ticket ID</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; min-width:150px;">Script Number</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Jenis</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Produk</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Kategori</th>
                            <th style="padding:12px; border-bottom:2px solid #eee;">Media</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; width: 250px; min-width: 250px;">Content Script</th>
                            <!-- Created Date Sort Header -->
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap; cursor:pointer;" onclick="sortLibraryByDate(8)">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    Created Date
                                    <svg id="sort-icon-8" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:<?php echo $sortBy === 'request_created_at' ? '1' : '0.3'; ?>;">
                                        <?php if ($sortBy === 'request_created_at' && ($sortPublished ?? 'DESC') === 'ASC'): ?>
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        <?php elseif ($sortBy === 'request_created_at'): ?>
                                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                                        <?php else: ?>
                                            <path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                            </th> 
                            
                            <!-- Published Date Sort Header -->
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap; cursor:pointer;" onclick="sortLibraryByDate(9)">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    Published Date
                                    <svg id="sort-icon-9" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:<?php echo $sortBy === 'created_at' ? '1' : '0.3'; ?>;">
                                        <?php if ($sortBy === 'created_at' && ($sortPublished ?? 'DESC') === 'ASC'): ?>
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        <?php elseif ($sortBy === 'created_at'): ?>
                                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                                        <?php else: ?>
                                            <path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                            </th>
                            
                            <!-- Start Date Sort Header -->
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap; cursor:pointer;" onclick="sortLibraryByDate(10)">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    Start Date
                                    <svg id="sort-icon-10" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:<?php echo $sortBy === 'start_date' ? '1' : '0.3'; ?>;">
                                        <?php if ($sortBy === 'start_date' && ($sortPublished ?? 'DESC') === 'ASC'): ?>
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        <?php elseif ($sortBy === 'start_date'): ?>
                                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                                        <?php else: ?>
                                            <path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                            </th>

                            <!-- Status Header Removed -->
                        </tr>
                    </thead>
                    <tbody>
                    <tbody id="libraryTableBody">
                        <?php include __DIR__ . '/library_rows.php'; ?>
                    </tbody>
                    </tbody>
                </table>
            </div>

            <!-- GRID VIEW (CARDS) -->
            <!-- Helper moved to library_helpers.php -->
            <div id="view-grid" style="display:none; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
                <?php include __DIR__ . '/library_grid.php'; ?>
            </div>

            </div>

            <!-- PAGINATION BAR -->
            <!-- PAGINATION BAR -->
            <div id="pagination-container">
                <?php include __DIR__ . '/library_pagination.php'; ?>
            </div>
            
            <!-- (Logic kept in partial) -->

        <?php endif; ?>
    </div>
</div>

<script>
// View Mode Switcher Logic
function setViewMode(mode) {
    const listBtn = document.getElementById('btn-list');
    const gridBtn = document.getElementById('btn-grid');
    const listView = document.getElementById('view-list');
    const gridView = document.getElementById('view-grid');
    
    // Safety check if elements don't exist (e.g. empty library)
    if (!listView || !gridView || !listBtn || !gridBtn) return;
    
    if (mode === 'grid') {
        listView.style.display = 'none';
        gridView.style.display = 'grid';
        
        gridBtn.style.background = 'white';
        gridBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        gridBtn.style.color = '#374151';
        
        listBtn.style.background = 'transparent';
        listBtn.style.boxShadow = 'none';
        listBtn.style.color = '#64748b';
    } else {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        
        listBtn.style.background = 'white';
        listBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        listBtn.style.color = '#374151';
        
        gridBtn.style.background = 'transparent';
        gridBtn.style.boxShadow = 'none';
        gridBtn.style.color = '#64748b';
    }
    
    // Save preference
    localStorage.setItem('libraryViewMode', mode);
}

// Load preference on start
document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('libraryViewMode') || 'list';
    setViewMode(savedMode);
});

// Activation Toggle Logic
function toggleActivation(requestId, currentStatus) {
    const action = currentStatus ? 'Deactivate' : 'Activate';
    const confirmMsg = `Are you sure you want to ${action} this script?\n\n` + 
                       (currentStatus ? "It will be hidden from everyone except Makers/Admins." : "It will be visible to all Agents.");
    
    if (!confirm(confirmMsg)) return;
    
    // Optimistic UI Update (optional, but let's wait for server)
    fetch('?controller=dashboard&action=activateScript', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            request_id: requestId,
            is_active: !currentStatus
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Script ${action}d successfully!`);
            location.reload(); // Reload to reflect changes (simplest way to update UI/Badges)
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update status. Please try again.');
    });
}
// Sorting Logic for Library Table
let currentLibrarySortCol = -1;
let currentLibrarySortDir = 'desc';

function sortLibraryByDate(columnIndex) {
    const table = document.getElementById('dataTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle direction if same column, else default to desc
    if (currentLibrarySortCol === columnIndex) {
        currentLibrarySortDir = currentLibrarySortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentLibrarySortCol = columnIndex;
        currentLibrarySortDir = 'desc';
    }
    
    // Sort rows
    rows.sort((a, b) => {
        const valA = parseInt(a.cells[columnIndex].getAttribute('data-sort-val')) || 0;
        const valB = parseInt(b.cells[columnIndex].getAttribute('data-sort-val')) || 0;
        return currentLibrarySortDir === 'asc' ? valA - valB : valB - valA;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update Icons
    document.querySelectorAll('[id^="sort-icon-"]').forEach(icon => {
        icon.style.opacity = '0.3';
        icon.innerHTML = '<path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>'; // Default up-down arrow
    });
    
    const activeIcon = document.getElementById('sort-icon-' + columnIndex);
    if (activeIcon) {
        activeIcon.style.opacity = '1';
        if (currentLibrarySortDir === 'asc') {
            activeIcon.innerHTML = '<path d="M12 19V5M5 12l7-7 7 7"/>'; // Up arrow
        } else {
            activeIcon.innerHTML = '<path d="M12 5v14M19 12l-7 7-7-7"/>'; // Down arrow
        }
    }
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
