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
        <div style="display:flex; align-items:center; gap:10px;">
            <?php // [START UPDATE 03-Mar-2026] Feature: Audit Package Export (Admin Only) ?>
            <?php if (isset($_SESSION['user']) && ($_SESSION['user']['dept'] ?? '') === 'ADMIN'): ?>
            <button type="button" onclick="openAuditExportModal()"
               style="background:#7c3aed; color:white; text-decoration:none; padding:8px 16px; border:none; border-radius:8px; font-weight:bold; font-size:13px; display:flex; align-items:center; gap:8px; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.2); cursor:pointer;"
               title="Export Audit Package (ZIP) — Admin Only">
               <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
               Export Audit ZIP
            </button>
            <?php endif; ?>
            <?php // [END UPDATE 03-Mar-2026] ?>
            <a href="<?php echo htmlspecialchars($libExportUrl); ?>" 
               style="background:#10b981; color:white; text-decoration:none; padding:8px 16px; border-radius:8px; font-weight:bold; font-size:13px; display:flex; align-items:center; gap:8px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
               <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
               Export Excel
            </a>
        </div>
    </div>


    <!-- KPI Indicator requested by User -->
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #ef4444; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s; box-shadow: 0 0 0 2px #ef4444; width: max-content;">
            <div style="background: #fee2e2; padding: 6px; border-radius: 8px; color: #dc2626; display: flex; justify-content: center; align-items: center; width: 32px; height: 32px;">
                <i class="fi fi-rr-document" style="font-size:18px; line-height: 0;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Script</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b; line-height: 1; margin-top: 4px;"><?php echo number_format($totalItems ?? 0); ?></div>
            </div>
        </div>
        
        <?php if(isset($search) && trim($search) !== ''): ?>
        <div style="font-size:13px; color:#64748b; background: white; padding: 10px 15px; border-radius: 8px; border: 1px dashed #cbd5e1; height: 100%; display: flex; align-items: center;">
            Pencarian Aktif: <strong style="color:#0f172a; margin-left:5px;">"<?php echo htmlspecialchars($search); ?>"</strong>
        </div>
        <?php endif; ?>
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
                        <?php if(!empty($_GET['status']) || !empty($_GET['start_date']) || !empty($_GET['jenis']) || !empty($_GET['produk']) || !empty($_GET['kategori']) || !empty($_GET['media'])): ?>
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
                        'status' => 'Status',
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
                            <th style="padding:12px; border-bottom:2px solid #eee; width:1px; white-space:nowrap;">Action</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; width:1px; white-space:nowrap; text-align:center;">Status</th>
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
    if (!currentStatus) {
        // ACTIVATE: Show Date Picker Modal
        const defaultDate = new Date().toISOString().split('T')[0];
        
        const overlay = document.createElement('div');
        overlay.id = 'lib-toggle-modal';
        overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center; animation:fadeIn 0.2s;';
        
        overlay.innerHTML = `
            <div style="background:white; border-radius:16px; padding:30px; width:380px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;" onclick="event.stopPropagation()">
                <div style="margin-bottom:20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg>
                </div>
                <h3 style="margin:0 0 8px 0; font-size:20px; color:#1e293b; font-weight:700;">Activate Script</h3>
                <p style="margin:0 0 20px 0; color:#64748b; font-size:13px;">Pilih Tanggal Mulai Berlaku (Start Date)</p>
                
                <div style="text-align:left; margin-bottom:24px;">
                    <input type="date" id="lib-activate-date" value="${defaultDate}" 
                           style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; font-family:inherit; color:#334155; background:#f8fafc; box-sizing:border-box; outline:none; transition:border-color 0.2s;"
                           onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                    <div id="lib-activate-error" style="color:#ef4444; font-size:12px; margin-top:6px; display:none;">⚠️ Tanggal harus diisi!</div>
                </div>
                
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button onclick="closeLibToggleModal()" 
                            style="background:#f1f5f9; color:#64748b; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;"
                            onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        Batal
                    </button>
                    <button onclick="performLibToggle(${requestId}, true)" 
                            style="background:#10b981; color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s;"
                            onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                        Activate
                    </button>
                </div>
            </div>
        `;
        
        overlay.addEventListener('click', closeLibToggleModal);
        document.body.appendChild(overlay);
        setTimeout(() => document.getElementById('lib-activate-date')?.focus(), 100);
        
    } else {
        // DEACTIVATE: Styled Confirm Modal
        const overlay = document.createElement('div');
        overlay.id = 'lib-toggle-modal';
        overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center;';
        
        overlay.innerHTML = `
            <div style="background:white; border-radius:16px; padding:30px; width:380px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;" onclick="event.stopPropagation()">
                <div style="margin-bottom:20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <h3 style="margin:0 0 8px 0; font-size:20px; color:#1e293b; font-weight:700;">Deactivate Script?</h3>
                <p style="margin:0 0 24px 0; color:#64748b; font-size:13px; line-height:1.5;">Script akan menjadi tidak aktif dan tidak tampil di pencarian Library.</p>
                
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button onclick="closeLibToggleModal()" 
                            style="background:#f1f5f9; color:#64748b; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;"
                            onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        Batal
                    </button>
                    <button onclick="performLibToggle(${requestId}, false)" 
                            style="background:#ef4444; color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s;"
                            onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Deactivate
                    </button>
                </div>
            </div>
        `;
        
        overlay.addEventListener('click', closeLibToggleModal);
        document.body.appendChild(overlay);
    }
}

function closeLibToggleModal() {
    const modal = document.getElementById('lib-toggle-modal');
    if (modal) modal.remove();
}

function showLibNotification(message, isSuccess) {
    const overlay = document.createElement('div');
    overlay.id = 'lib-toggle-modal';
    overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center;';
    
    const color = isSuccess ? '#10b981' : '#ef4444';
    const icon = isSuccess 
        ? '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>'
        : '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    overlay.innerHTML = `
        <div style="background:white; border-radius:16px; padding:30px; width:340px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;" onclick="event.stopPropagation()">
            <div style="margin-bottom:16px;">${icon}</div>
            <h3 style="margin:0 0 8px 0; font-size:18px; color:#1e293b; font-weight:700;">${isSuccess ? 'Berhasil!' : 'Error'}</h3>
            <p style="margin:0 0 20px 0; color:#64748b; font-size:13px;">${message}</p>
            <button onclick="closeLibToggleModal(); ${isSuccess ? 'location.reload();' : ''}" 
                    style="background:${color}; color:white; border:none; padding:10px 28px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                OK
            </button>
        </div>
    `;
    
    overlay.addEventListener('click', function(e) { if (e.target === overlay) { closeLibToggleModal(); if (isSuccess) location.reload(); } });
    document.body.appendChild(overlay);
}

function performLibToggle(requestId, activate) {
    let startDate = null;
    if (activate) {
        const dateInput = document.getElementById('lib-activate-date');
        if (dateInput && !dateInput.value) {
            const errDiv = document.getElementById('lib-activate-error');
            if (errDiv) errDiv.style.display = 'block';
            if (dateInput) dateInput.style.borderColor = '#ef4444';
            return;
        }
        startDate = dateInput ? dateInput.value : null;
    }
    
    // Close modal only after extracting the value
    closeLibToggleModal();
    
    fetch('?controller=dashboard&action=activateScript', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            request_id: requestId,
            is_active: activate,
            start_date: startDate
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showLibNotification(activate ? 'Script berhasil diaktifkan!' : 'Script berhasil dinonaktifkan.', true);
        } else if (data.error === 'newer_version_exists') {
            // [VERSION GUARD] Show version warning popup
            const overlay = document.createElement('div');
            overlay.id = 'version-guard-overlay';
            overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; display:flex; justify-content:center; align-items:center;';
            overlay.innerHTML = `
                <div style="background:white; border-radius:16px; padding:30px; width:380px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;" onclick="event.stopPropagation()">
                    <div style="margin-bottom:16px;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3 style="margin:0 0 10px 0; font-size:18px; color:#1e293b; font-weight:700;">Versi Lebih Baru Sudah Ada</h3>
                    <p style="margin:0 0 8px 0; color:#64748b; font-size:13px;">Script ini sudah memiliki versi yang lebih baru:</p>
                    <div style="background:#fef3c7; border:1px solid #fbbf24; border-radius:8px; padding:10px; margin:0 0 16px 0;">
                        <div style="font-weight:700; color:#92400e; font-size:14px;">\u2192 ${data.newer_script}</div>
                        <div style="color:#a16207; font-size:12px; margin-top:4px;">Status: ${data.newer_status}</div>
                    </div>
                    <p style="margin:0 0 20px 0; color:#64748b; font-size:12px;">Silakan gunakan versi terbaru untuk melakukan perubahan.</p>
                    <div style="display:flex; gap:10px; justify-content:center;">
                        <button onclick="document.getElementById('version-guard-overlay').remove()" 
                                style="background:#f1f5f9; color:#64748b; border:none; padding:10px 20px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer;"
                                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            Tutup
                        </button>
                        <button onclick="window.location.href='?controller=audit&action=detail&id=${data.newer_id}'" 
                                style="background:#f59e0b; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:6px;"
                                onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Lihat di Audit Trail
                        </button>
                    </div>
                </div>
            `;
            overlay.addEventListener('click', function(e) { if (e.target === overlay) document.getElementById('version-guard-overlay').remove(); });
            document.body.appendChild(overlay);
        } else {
            showLibNotification(data.message || 'Gagal mengubah status.', false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showLibNotification('Gagal terhubung ke server. Silakan coba lagi.', false);
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

<?php // [START UPDATE 03-Mar-2026] Feature: Audit Export Modal (Admin Only) ?>
<?php if (isset($_SESSION['user']) && ($_SESSION['user']['dept'] ?? '') === 'ADMIN'): ?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!--  AUDIT EXPORT MODAL (Admin Only)                          -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="auditExportOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; justify-content:center; align-items:center; animation:fadeIn 0.2s;">
    <div style="background:white; border-radius:16px; padding:30px 35px; width:520px; max-width:92%; box-shadow:0 20px 60px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;" onclick="event.stopPropagation()">
        <!-- Header -->
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:44px; height:44px; background:#f5f3ff; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            </div>
            <div>
                <h3 style="margin:0; font-size:18px; color:#1e293b; font-weight:700;">Export Audit Package</h3>
                <p style="margin:2px 0 0 0; font-size:12px; color:#94a3b8;">Pilih kriteria data yang ingin diekspor ke file ZIP</p>
            </div>
        </div>

        <form id="auditExportForm" method="GET" action="">
            <input type="hidden" name="controller" value="auditExport">
            <input type="hidden" name="action" value="export">

            <!-- Date Range -->
            <div style="margin-bottom:16px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px;">Rentang Tanggal (Date Range)</label>
                <div style="display:flex; gap:10px;">
                    <input type="date" name="start_date" id="auditStartDate" style="flex:1; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; color:#334155; outline:none;" onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#cbd5e1'">
                    <span style="align-self:center; color:#94a3b8; font-size:13px;">s.d.</span>
                    <input type="date" name="end_date" id="auditEndDate" style="flex:1; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; color:#334155; outline:none;" onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#cbd5e1'">
                </div>
            </div>

            <!-- Filter Grid -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:18px;">
                <?php
                $auditFilterLabels = [
                    'jenis'    => 'Jenis Script',
                    'produk'   => 'Produk',
                    'kategori' => 'Kategori',
                    'media'    => 'Media Channel',
                ];
                foreach ($auditFilterLabels as $fKey => $fLabel):
                    $fOptions = $filterOptions[$fKey] ?? [];
                ?>
                <div>
                    <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px;"><?php echo $fLabel; ?></label>
                    <select name="<?php echo $fKey; ?>" style="width:100%; padding:9px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; color:#334155; background:white; outline:none; cursor:pointer;" onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#cbd5e1'">
                        <option value="">— Semua —</option>
                        <?php foreach($fOptions as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Info Box -->
            <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:10px; padding:12px 14px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div style="font-size:12px; color:#5b21b6; line-height:1.5;">
                    <strong>Isi Paket ZIP:</strong> Info Script, Audit Trail (CSV), Konten Naskah (HTML), File Asli (.xlsx/.docx), dan Bukti Dokumen (Legal/CX/LPP).
                </div>
            </div>

            <!-- Buttons -->
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeAuditExportModal()"
                        style="background:#f1f5f9; color:#64748b; border:none; padding:10px 22px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Batal
                </button>
                <button type="submit" id="auditExportSubmitBtn"
                        style="background:#7c3aed; color:white; border:none; padding:10px 22px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px;"
                        onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download ZIP
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAuditExportModal() {
    const overlay = document.getElementById('auditExportOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function closeAuditExportModal() {
    const overlay = document.getElementById('auditExportOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Close on clicking outside the modal box
document.getElementById('auditExportOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) closeAuditExportModal();
});

// Show loading state on form submit
document.getElementById('auditExportForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('auditExportSubmitBtn');
    if (btn) {
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Mengekspor...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
    }
    // Re-enable after 30 seconds (fallback in case download completes silently)
    setTimeout(function() {
        if (btn) {
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Download ZIP';
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
        closeAuditExportModal();
    }, 30000);
});
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
<?php endif; ?>
<?php // [END UPDATE 03-Mar-2026] ?>

<?php require_once 'app/views/layouts/footer.php'; ?>
