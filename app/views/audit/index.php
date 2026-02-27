<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Audit Trail</h2>
            <p style="color:var(--text-secondary);">System-wide activity log.</p>
        </div>
        <div>
            <?php 
                // Build Export URL with current filters
                $exportParams = $_GET;
                $exportParams['action'] = 'export';
                $exportUrl = '?' . http_build_query($exportParams);
            ?>
            <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="btn btn-primary" style="background:#16a34a; text-decoration:none; padding:10px 20px; border-radius:4px; color:white; font-weight:bold;">
                Export to Excel
            </a>
        </div>
    </div>

    <div class="card">
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Audit Summary</h4>
        
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="audit">
                <input type="hidden" name="action" value="index">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <input type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" oninput="handleSearchInput(this)" autocomplete="off" placeholder="Quick Search..." style="padding:8px 30px 8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px;">
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>
                        
                        <script>
                            let searchTimeout;
                            function handleSearchInput(el) {
                                document.getElementById('clearSearchBtn').style.display = el.value.length > 0 ? 'block' : 'none';
                                
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    performSearch(el.value);
                                }, 500); // Debounce 500ms
                            }
                            
                            function performSearch(query) {
                                const url = new URL(window.location.href);
                                if (query) {
                                    url.searchParams.set('search', query);
                                } else {
                                    url.searchParams.delete('search');
                                }
                                url.searchParams.set('page', '1'); // Reset to page 1
                                
                                // Update Browser Address Bar (for reload consistency)
                                window.history.pushState({}, '', url);

                                // Prepare AJAX Request
                                url.searchParams.set('ajax', '1');

                                fetch(url)
                                    .then(response => response.json())
                                    .then(data => {
                                        // Update Table Rows
                                        const tbody = document.querySelector('#dataTable tbody');
                                        if (tbody) tbody.innerHTML = data.rows;
                                        
                                        // Update Pagination
                                        const pagContainer = document.getElementById('pagination-container');
                                        if (pagContainer) pagContainer.innerHTML = data.pagination;
                                        
                                        // Update Total Items Count (if displayed somewhere) - optional
                                    })
                                    .catch(err => console.error('Search Error:', err));
                            }

                            function clearSearch() {
                                const el = document.getElementById('searchInput');
                                el.value = '';
                                handleSearchInput(el);
                                el.focus();
                            }
                        </script>
                    </div>
                    
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Action Buttons (Moved to Right) -->
                        <?php if(!empty($_GET) && (isset($_GET['jenis']) || isset($_GET['produk']) || isset($_GET['kategori']) || isset($_GET['media']) || isset($_GET['status']) || isset($_GET['start_date']) || isset($_GET['search']))): ?>
                             <a href="?controller=audit&action=index" style="margin-right:10px; color:#64748b; font-size:13px; text-decoration:none;">Reset</a>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                    </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:15px; align-items: end;">
                    
                    <!-- Date Range -->
                    <div style="flex: 0 0 320px; max-width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px;">Date Range</label>
                        <div style="display:flex; gap:10px;">
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                        </div>
                    </div>

                    <?php 
                    $filterLabels = [
                        'jenis' => 'Jenis', 
                        'produk' => 'Produk', 
                        'kategori' => 'Kategori', 
                        'media' => 'Media Channel',
                        'status' => 'Status'
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
                    <div class="dropdown" style="position:relative; flex: 1 1 200px;">
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

                <!-- Active Filter Tags -->
                <?php 
                $hasActiveFilters = false;
                foreach($activeFilters as $key => $vals) {
                    if(!empty($vals)) $hasActiveFilters = true;
                }
                
                if ($hasActiveFilters): 
                ?>
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #f1f5f9; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span style="font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-right:5px;">Active Filters:</span>
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
                    
                     <a href="?controller=audit&action=index" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        
        <?php if (empty($logs)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No activity recorded yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:12px;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left; color:#555;">
                            <th style="padding:10px; border-bottom:2px solid #eee; width:1px; white-space:nowrap;">Action</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Maker</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Ticket ID</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Script No</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Produk</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Media</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Kategori</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Jenis</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Isi Script</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Status</th>
                            <th style="padding:10px; border-bottom:2px solid #eee; text-align:right; cursor:pointer;" onclick="sortTableByDate(10)">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:5px;">
                                    Last Update
                                    <svg id="sort-icon-10" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:<?php echo isset($sortUpdated) ? '1' : '0.3'; ?>;">
                                        <?php if (isset($sortUpdated) && $sortUpdated === 'ASC'): ?>
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        <?php elseif (isset($sortUpdated)): ?>
                                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                                        <?php else: ?>
                                            <path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php include __DIR__ . '/table_rows.php'; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="pagination-container">
                <?php include __DIR__ . '/../layouts/pagination.php'; ?>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<script>
let currentDateSortOrder = 'desc';

function sortTableByDate(columnIndex) {
    const table = document.getElementById('dataTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > 1);
    
    // Toggle sort order
    currentDateSortOrder = currentDateSortOrder === 'asc' ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        const valA = parseInt(a.cells[columnIndex].getAttribute('data-sort-val')) || 0;
        const valB = parseInt(b.cells[columnIndex].getAttribute('data-sort-val')) || 0;
        
        return currentDateSortOrder === 'asc' ? valA - valB : valB - valA;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update Icons
    document.querySelectorAll('[id^="sort-icon-"]').forEach(icon => {
        icon.style.opacity = '0.3';
        icon.innerHTML = '<path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>';
    });
    
    const activeIcon = document.getElementById('sort-icon-' + columnIndex);
    if (activeIcon) {
        activeIcon.style.opacity = '1';
        if (currentDateSortOrder === 'asc') {
            activeIcon.innerHTML = '<path d="M12 19V5M5 12l7-7 7 7"/>';
        } else {
            activeIcon.innerHTML = '<path d="M12 5v14M19 12l-7 7-7-7"/>';
        }
    }
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
