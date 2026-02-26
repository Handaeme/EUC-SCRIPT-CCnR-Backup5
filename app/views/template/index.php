<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<div class="main">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Template Library</h2>
            <p style="color:var(--text-secondary);">Download template standar untuk script.</p>
        </div>
        <?php if ($isProcedure): ?>
        <div>
            <button onclick="document.getElementById('uploadModal').style.display='block'" 
                    style="background:var(--primary-red); color:white; border:none; padding:10px 20px; border-radius:4px; font-weight:bold; cursor:pointer;">
                + Upload Template
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alert Messages (Custom Modal) -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
    <div id="dynamicAlertModal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(4px); display:flex; justify-content:center; align-items:center;">
        <div style="background:white; border-radius:12px; width:400px; max-width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden; animation:modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <div style="padding:40px 24px 24px 24px; text-align:center;">
                <?php if (isset($_GET['success'])): ?>
                    <div style="display:flex; justify-content:center; margin-bottom:20px;">
                        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h3 style="margin:0 0 10px; color:#1f2937; font-size:20px; font-weight:700;">Berhasil!</h3>
                    <p style="margin:0; color:#6b7280; font-size:14px;"><?php echo htmlspecialchars($_GET['success']); ?></p>
                <?php else: ?>
                    <div style="display:flex; justify-content:center; margin-bottom:20px;">
                        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <h3 style="margin:0 0 10px; color:#1f2937; font-size:20px; font-weight:700;">Gagal</h3>
                    <p style="margin:0; color:#6b7280; font-size:14px;"><?php echo htmlspecialchars($_GET['error']); ?></p>
                <?php endif; ?>
                
                <div style="margin-top:25px;">
                    <button onclick="closeAlertModal()" style="background:#dc2626; border:none; padding:8px 30px; border-radius:6px; font-weight:600; color:white; cursor:pointer; font-size:14px; transition:all 0.2s; box-shadow:0 1px 2px rgba(220,38,38,0.2);">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function closeAlertModal() {
            const modal = document.getElementById('dynamicAlertModal');
            if (modal) modal.style.display = 'none';
            // Clean URL
            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url);
        }
        // Auto-close after 3 seconds
        setTimeout(() => {
            closeAlertModal();
        }, 3000);
    </script>
    <?php endif; ?>

    <div class="card">
        <!-- View Toggle & Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h4 style="margin:0;">Files</h4>
            <div style="background:#f1f5f9; padding:4px; border-radius:8px; display:flex; gap:4px;">
                <button onclick="setViewMode('list')" id="btn-list" style="border:none; background:white; padding:6px 10px; border-radius:6px; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.1); color:#374151;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </button>
                <button onclick="setViewMode('grid')" id="btn-grid" style="border:none; background:transparent; padding:6px 10px; border-radius:6px; cursor:pointer; color:#64748b; box-shadow:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </button>
            </div>
        </div>

        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="template">
                <input type="hidden" name="action" value="index">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Moved to Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <input type="text" id="searchInput" oninput="handleSearchInput(this)" autocomplete="off" placeholder="Quick Search..." style="padding:8px 30px 8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px;">
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                        
                         <?php if(!empty($_GET) && (isset($_GET['uploaded_by']) || isset($_GET['start_date']))): ?>
                             <a href="?controller=template&action=index" style="margin-left:5px; color:#64748b; font-size:13px; text-decoration:none;">Reset</a>
                        <?php endif; ?>

                        <script>
                            function handleSearchInput(el) {
                                document.getElementById('clearSearchBtn').style.display = el.value.length > 0 ? 'block' : 'none';
                                window.filterTable('searchInput', 'dataTable');
                            }
                            function clearSearch() {
                                const el = document.getElementById('searchInput');
                                el.value = '';
                                handleSearchInput(el);
                                el.focus();
                            }
                        </script>
                    </div>
                    <div>
                        <!-- Right Side Empty -->
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:15px; align-items: end;">
                    
                    <!-- Date Range -->
                    <div style="grid-column: span 2;">
                        <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px;">Date Range</label>
                        <div style="display:flex; gap:10px;">
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; color:#334155;">
                        </div>
                    </div>

                    <?php 
                    $filterLabels = [
                        'uploaded_by' => 'Uploaded By'
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
                    
                     <a href="?controller=template&action=index" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        
        <?php if (empty($templates)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No templates found.</p>
            </div>
        <?php else: ?>
        
            <!-- LIST VIEW -->
            <div id="view-list" style="display:block;">
                <div style="overflow-x: auto;">
                <table id="dataTable" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left;">
                            <th style="padding:15px; border-bottom:2px solid #eee;">Title</th>
                            <th style="padding:15px; border-bottom:2px solid #eee;">Filename</th>
                            <th style="padding:15px; border-bottom:2px solid #eee;">Uploaded By</th>
                            <th onclick="sortTableByDate(3)" style="padding:15px; border-bottom:2px solid #eee; cursor: pointer; user-select: none;">
                                Created Date 
                                <span style="font-size: 10px; margin-left: 4px;">▼▲</span>
                            </th>
                            <th style="padding:15px; border-bottom:2px solid #eee; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $t): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:15px; font-weight:bold; color:#374151;">
                                <?php echo htmlspecialchars($t['title']); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo htmlspecialchars($t['filename']); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo htmlspecialchars($t['uploaded_by']); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo ($t['created_at'] instanceof DateTime) ? $t['created_at']->format('d M Y') : date('d M Y', strtotime($t['created_at'])); ?>
                            </td>
                            <td style="padding:15px; text-align:right;">
                                <a href="?controller=template&action=downloadFile&id=<?php echo $t['id']; ?>" 
                                   style="text-decoration:none; color:#3b82f6; font-weight:bold; margin-right:15px;">
                                    Download
                                </a>
                                <button onclick='openPreview("?controller=template&action=downloadFile&id=<?php echo $t["id"]; ?>", "<?php echo strtolower(pathinfo($t["filename"], PATHINFO_EXTENSION)); ?>")' 
                                        style="background:none; border:none; color:#10b981; font-weight:bold; cursor:pointer; margin-right:15px; font-size:16px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    View
                                </button>
                                <?php if ($isProcedure): ?>
                                <button onclick="confirmDeleteTemplate(<?php echo $t['id']; ?>)" 
                                        style="background:none; border:none; color:#dc2626; font-weight:bold; cursor:pointer; font-size:13px; display:inline-flex; align-items:center;">
                                    Delete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- GRID VIEW -->
            <div id="view-grid" style="display:none; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
                <?php foreach ($templates as $t): 
                    // Icon Detection
                    $ext = strtolower(pathinfo($t['filename'], PATHINFO_EXTENSION));
                    $iconColor = '#166534'; // Green for Excel
                    $bgColor = '#f0fdf4';
                    $borderColor = '#bbf7d0';
                    $iconSvg = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';

                    if (in_array($ext, ['doc', 'docx'])) {
                        $iconColor = '#1e40af'; // Blue for Word
                        $bgColor = '#eff6ff';
                        $borderColor = '#bfdbfe';
                        $iconSvg = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="8" y1="9" x2="8" y2="9"></line></svg>'; // Slightly diff for generic file
                    }
                ?>
                <div style="background:white; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:all 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column;">
                    <!-- Card Body -->
                    <div style="padding:20px; flex-grow:1; display:flex; flex-direction:column; gap:12px;">
                        
                        <!-- Info Row (Icon + Text) Horizontal -->
                        <div style="display:flex; align-items:center; gap:8px; text-align:left;">
                            <div style="color:<?php echo $iconColor; ?>; background:<?php echo $bgColor; ?>; padding:8px; border-radius:50%; border:1px solid <?php echo $borderColor; ?>; flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </div>
                            <div style="overflow:hidden;">
                                <div style="font-weight:700; color:#1f2937; margin-bottom:1px; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($t['title']); ?></div>
                                <div style="font-size:11px; color:#6b7280; font-family:monospace; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($t['filename']); ?></div>
                            </div>
                        </div>

                        <!-- Content Preview (Middle) -->
                        <?php if (!empty($t['description'])): ?>
                        <div style="width:100%; background:#f9fafb; padding:12px; border-radius:6px; border:1px solid #e5e7eb; text-align:left; box-sizing:border-box;">
                            <div style="font-size:13px; color:#4b5563; font-family:sans-serif; line-height:1.6; overflow:hidden; display:-webkit-box; -webkit-line-clamp:6; -webkit-box-orient:vertical;">
                                <?php 
                                    $desc = $t['description'];
                                    echo htmlspecialchars(strlen($desc) > 300 ? substr($desc, 0, 300) . '...' : $desc); 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div style="font-size:12px; color:#9ca3af; margin-top:4px; display:flex; justify-content:space-between; align-items:center;">
                            <span>Uploaded by <span style="font-weight:600; color:#4b5563;"><?php echo htmlspecialchars(!empty($t['group_name']) ? $t['group_name'] : $t['uploaded_by']); ?></span></span>
                            <span style="font-weight:bold; color:#374151;"><?php echo ($t['created_at'] instanceof DateTime) ? $t['created_at']->format('d M Y') : date('d M Y', strtotime($t['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div style="padding:12px 16px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; gap:15px; align-items:center;">
                            <a href="?controller=template&action=downloadFile&id=<?php echo $t['id']; ?>" style="color:var(--primary-red); font-weight:600; text-decoration:none; font-size:13px; display:flex; align-items:center; gap:5px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Download
                            </a>
                            
                            <button onclick='openPreview("?controller=template&action=downloadFile&id=<?php echo $t["id"]; ?>", "<?php echo strtolower(pathinfo($t["filename"], PATHINFO_EXTENSION)); ?>")' 
                                    style="background:none; border:none; color:#10b981; font-weight:bold; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:5px; padding:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                View
                            </button>
                        </div>
                        
                        <?php if ($isProcedure): ?>
                        <button onclick="confirmDeleteTemplate(<?php echo $t['id']; ?>)" 
                                style="background:none; border:none; color:#dc2626; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; opacity:0.8; padding:0;">
                             <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                             Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php include __DIR__ . '/../layouts/pagination.php'; ?>
        <?php endif; ?>
    </div>

<script>
function setViewMode(mode) {
    const listBtn = document.getElementById('btn-list');
    const gridBtn = document.getElementById('btn-grid');
    const listView = document.getElementById('view-list');
    const gridView = document.getElementById('view-grid');
    
    if (mode === 'grid') {
        if(listView) listView.style.display = 'none';
        if(gridView) gridView.style.display = 'grid';
        
        gridBtn.style.background = 'white';
        gridBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        gridBtn.style.color = '#374151';
        
        listBtn.style.background = 'transparent';
        listBtn.style.boxShadow = 'none';
        listBtn.style.color = '#64748b';
    } else {
        if(listView) listView.style.display = 'block';
        if(gridView) gridView.style.display = 'none';
        
        listBtn.style.background = 'white';
        listBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        listBtn.style.color = '#374151';
        
        gridBtn.style.background = 'transparent';
        gridBtn.style.boxShadow = 'none';
        gridBtn.style.color = '#64748b';
    }
    
    localStorage.setItem('templateViewMode', mode);
}

document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('templateViewMode') || 'list';
    setViewMode(savedMode);
});
</script>
</div>

<!-- Upload Modal (Procedure Only) -->
<?php if ($isProcedure): ?>
<div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:white; width:800px; margin:50px auto; padding:20px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0;">Upload New Template</h3>
        <form action="?controller=template&action=upload" method="POST" enctype="multipart/form-data" onsubmit="var btn=this.querySelector('button[type=submit]'); if(btn.disabled) return false; btn.disabled=true; btn.textContent='Uploading...'; btn.style.opacity='0.7';">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:600; margin-bottom:5px;">Template Title</label>
                <input type="text" name="title" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:600; margin-bottom:5px;">File (Excel/Word)</label>
                <input type="file" name="template_file" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:600; margin-bottom:5px;">Script Preview / Description <span style="color:var(--primary-red);">*</span></label>
                <textarea name="description" required placeholder="Script content will appear here automatically..." style="width:100%; height:364px; padding:8px; margin-top:5px; border:1px solid #ddd; border-radius:4px; font-size:13px; resize:vertical; line-height:1.5;"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('uploadModal').style.display='none'" 
                        style="padding:8px 16px; border:none; background:#eee; cursor:pointer; margin-right:10px; border-radius:4px;">Cancel</button>
                <button type="submit" style="padding:8px 16px; border:none; background:var(--primary-red); color:white; cursor:pointer; border-radius:4px;">Upload</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Client-Side Preview Logic (Hybrid: Local with CDN Fallback) -->
<script src="assets/js/xlsx.full.min.js" onerror="this.src='https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js'"></script>
<script src="assets/js/mammoth.browser.min.js" onerror="this.src='https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js'"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="template_file"]');
    if (!fileInput) return;

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const descBox = document.querySelector('textarea[name="description"]');
        const ext = file.name.split('.').pop().toLowerCase();

        if (ext === 'xlsx' || ext === 'xls') {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    
                    // Skip Sheet 1 (Instructions) if Sheet 2 exists
                    const sheetIndex = workbook.SheetNames.length > 1 ? 1 : 0;
                    const sheetName = workbook.SheetNames[sheetIndex];
                    const sheet = workbook.Sheets[sheetName];
                    
                    // Convert to JSON (Array of Arrays)
                    const rows = XLSX.utils.sheet_to_json(sheet, {header: 1, defval: ''});
                    if (rows.length === 0) return;

                    let content = [];
                    let targetColIdx = -1;

                    // 1. Find Header
                    const headerRow = rows[0];
                    headerRow.forEach((cell, idx) => {
                        if (cell && cell.toString().toLowerCase().includes('bahasa script')) {
                            targetColIdx = idx;
                        }
                    });

                    // 2. Extract Data
                    for (let i = 1; i < rows.length; i++) { // Skip header
                        if (targetColIdx !== -1) {
                             if (rows[i][targetColIdx]) content.push(rows[i][targetColIdx]);
                        } else {
                            // Fallback: Join first 3 cells
                            content.push(rows[i].slice(0,3).join(' '));
                        }
                        if (content.length >= 5) break; 
                    }

                    descBox.value = content.join('\n').trim();
                } catch(err) {
                    console.error("Excel Parse Error:", err);
                }
            };
            reader.readAsArrayBuffer(file);

        } else if (ext === 'docx') {
            const reader = new FileReader();
            reader.onload = function(e) {
                const arrayBuffer = e.target.result;
                if (window.mammoth) {
                    mammoth.extractRawText({arrayBuffer: arrayBuffer})
                        .then(function(result){
                            let text = result.value;
                            if(text.length > 500) text = text.substring(0, 500) + '...';
                            descBox.value = text;
                        })
                        .catch(function(err){ console.log(err); });
                }
            };
            reader.readAsArrayBuffer(file);
        }
    });
});
</script>

<!-- Preview Modal -->
<div id="previewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; overflow-y:auto;">
    <div style="background:white; width:900px; margin:50px auto; padding:20px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <h3 style="margin:0;">Full File Content Preview</h3>
            <button onclick="document.getElementById('previewModal').style.display='none'" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        
        <div id="previewLoading" style="display:none; text-align:center; padding:50px;">
            Loading content... <br>
            <span style="font-size:12px; color:#666;">Parsing tables (this may take a moment)...</span>
        </div>

        <!-- Tabs Container -->
        <div id="previewTabs" style="display:flex; gap:5px; margin-bottom:0; border-bottom:1px solid #ddd; overflow-x:auto;">
            <!-- Tabs injected by JS -->
        </div>

        <div id="previewContent" style="height:600px; overflow:auto; padding:15px; border:1px solid #ddd; border-top:none; border-radius:0 0 4px 4px; font-family:sans-serif; font-size:13px; background:#fff; box-sizing:border-box;">
            <!-- Content will be injected here -->
        </div>

         <div style="text-align:right; margin-top:10px;">
            <button onclick="document.getElementById('previewModal').style.display='none'" style="padding:8px 16px; background:#374151; color:white; border:none; border-radius:4px; cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal (Centered) -->
<div id="deleteConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div style="background:white; border-radius:12px; width:400px; max-width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow:hidden; animation:modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="padding:40px 24px 24px 24px; text-align:center;">
            <div style="display:flex; justify-content:center; margin-bottom:20px;">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            
            <h3 style="margin:0 0 10px; color:#1f2937; font-size:20px; font-weight:700;">Hapus Template?</h3>
            
            <p style="margin:0; color:#6b7280; font-size:14px; line-height:1.5;">
                Apakah Anda yakin ingin menghapus template ini? Tindakan ini tidak dapat dibatalkan.
            </p>
            
            <div style="margin-top:25px; display:flex; justify-content:center; gap:12px;">
                <button onclick="closeDeleteModal()" style="padding:8px 30px; border:1px solid #cbd5e1; border-radius:6px; font-weight:600; color:#475569; background:white; cursor:pointer; font-size:14px; transition:all 0.2s;">Batal</button>
                <button id="confirmDeleteBtn" style="padding:8px 30px; border:none; border-radius:6px; font-weight:600; color:white; background:#dc2626; cursor:pointer; font-size:14px; transition:all 0.2s; box-shadow:0 1px 2px rgba(220,38,38,0.2);">Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes modalPop {
    0% { transform: scale(0.95); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<style>
    /* Table Styles for Preview */
    #previewContent table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:12px; }
    #previewContent th, #previewContent td { border:1px solid #ccc; padding:6px 10px; text-align:left; }
    #previewContent tr:nth-child(even) { background-color:#f9fafb; }
    #previewContent th { background-color:#f3f4f6; font-weight:bold; }
    
    /* Tab Styles */
    .preview-tab-btn {
        padding: 8px 16px;
        background: #f1f5f9;
        border: 1px solid #ddd;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
        cursor: pointer;
        font-size: 13px;
        color: #64748b;
        margin-bottom: -1px;
    }
    .preview-tab-btn.active {
        background: #fff;
        border-bottom: 2px solid #fff; /* Blend with content */
        color: #2563eb;
        font-weight: 600;
        z-index: 10;
    }
    .preview-sheet-content { display: none; }
    .preview-sheet-content.active { display: block; }
</style>

<script>
function openPreview(url, ext) {
    const modal = document.getElementById('previewModal');
    const contentDiv = document.getElementById('previewContent');
    const loadingDiv = document.getElementById('previewLoading');
    const tabsDiv = document.getElementById('previewTabs');
    
    modal.style.display = 'block';
    
    // Reset State
    contentDiv.innerHTML = '';
    tabsDiv.innerHTML = '';
    contentDiv.style.display = 'none';
    tabsDiv.style.display = 'none';
    loadingDiv.style.display = 'block';

    fetch(url)
        .then(res => res.arrayBuffer())
        .then(data => {
            loadingDiv.style.display = 'none';
            contentDiv.style.display = 'block';

            if (ext === 'xlsx' || ext === 'xls') {
                tabsDiv.style.display = 'flex'; // Show tabs only for Excel
                const workbook = XLSX.read(new Uint8Array(data), {type: 'array'});
                
                // Process Sheets
                workbook.SheetNames.forEach((sheetName, index) => {
                    // 1. Create Tab Button
                    const btn = document.createElement('button');
                    btn.className = `preview-tab-btn ${index === 0 ? 'active' : ''}`;
                    btn.innerText = sheetName;
                    btn.onclick = () => switchTab(index);
                    tabsDiv.appendChild(btn);

                    // 2. Create Content Div
                    const sheetDiv = document.createElement('div');
                    sheetDiv.className = `preview-sheet-content ${index === 0 ? 'active' : ''}`;
                    sheetDiv.id = `sheet-content-${index}`;
                    
                    // Render HTML Table
                    const sheet = workbook.Sheets[sheetName];
                    const html = XLSX.utils.sheet_to_html(sheet);
                    sheetDiv.innerHTML = html;
                    
                    contentDiv.appendChild(sheetDiv);
                });

            } else if (ext === 'docx') {
                mammoth.convertToHtml({arrayBuffer: data})
                    .then(result => {
                        contentDiv.innerHTML = result.value;
                    })
                    .catch(err => {
                        contentDiv.innerHTML = '<p style="color:red;">Error parsing Word file.</p>';
                    });
            } else {
                contentDiv.innerHTML = '<p>Preview not available for this file type.</p>';
            }
        })
        .catch(err => {
            loadingDiv.style.display = 'none';
            contentDiv.innerHTML = '<p style="color:red;">Failed to load file.</p>';
        });
}

let templateIdToDelete = null;

function confirmDeleteTemplate(id) {
    templateIdToDelete = id;
    const modal = document.getElementById('deleteConfirmModal');
    modal.style.display = 'flex'; // Use flex to center
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    templateIdToDelete = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (templateIdToDelete) {
        // Change button state to loading
        this.innerHTML = '<svg class="spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="animation: spin 1s linear infinite; margin-right:6px;"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4 31.4" stroke-linecap="round"></circle></svg> Menghapus...';
        this.style.opacity = '0.7';
        this.disabled = true;
        
        // Redirect to delete action
        window.location.href = '?controller=template&action=delete&id=' + templateIdToDelete;
    }
});

// Add spin animation if not exists
if (!document.getElementById('spinner-style')) {
    const style = document.createElement('style');
    style.id = 'spinner-style';
    style.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
    document.head.appendChild(style);
}

function switchTab(activeIndex) {
    // Update Buttons
    const buttons = document.querySelectorAll('.preview-tab-btn');
    buttons.forEach((btn, idx) => {
        if(idx === activeIndex) btn.classList.add('active');
        else btn.classList.remove('active');
    });

    // Update Content
    const contents = document.querySelectorAll('.preview-sheet-content');
    contents.forEach((div, idx) => {
        if(idx === activeIndex) div.classList.add('active');
        else div.classList.remove('active');
    });
}
</script>

<!-- No Results Message -->
<div id="no-search-results" style="display:none; text-align:center; padding:40px; color:#6b7280;">
    <div style="margin-bottom:10px;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </div>
    <div style="font-size:16px; font-weight:600;">Tidak ada data yang dicari</div>
    <div style="font-size:13px;">Coba kata kunci lain atau periksa ejaan Anda.</div>
</div>

<script>
// Date Sorting Function
let currentDateSortOrder = 'desc'; // Default: newest first

function sortTableByDate(columnIndex) {
    const table = document.getElementById('dataTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > 1);
    
    // Toggle sort order
    currentDateSortOrder = currentDateSortOrder === 'asc' ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        const dateA = a.cells[columnIndex].textContent.trim();
        const dateB = b.cells[columnIndex].textContent.trim();
        
        // Parse dates (format: "06 Feb 2026")
        const parseDate = (dateStr) => {
            if (!dateStr) return new Date(0);
            const months = {
                'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
            };
            const parts = dateStr.split(' ');
            if (parts.length !== 3) return new Date(0);
            const day = parseInt(parts[0]);
            const month = months[parts[1]];
            const year = parseInt(parts[2]);
            return new Date(year, month, day);
        };
        
        const d1 = parseDate(dateA);
        const d2 = parseDate(dateB);
        
        return currentDateSortOrder === 'asc' ? d1 - d2 : d2 - d1;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}
</script>

<script>
// Custom File Filter for both List and Grid Views
// Assign to window to ensure we override any existing function with the same name
window.filterTable = function(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const filter = input.value.toUpperCase();
    let hasResults = false;
    
    // Check which view is currently active (List or Grid)
    const listView = document.getElementById('view-list');
    const gridView = document.getElementById('view-grid');
    const isListActive = listView && listView.style.display !== 'none';
    const isGridActive = gridView && gridView.style.display !== 'none';

    // 1. Filter List View (Table)
    if (listView) {
        const table = document.getElementById(tableId);
        const tr = table.getElementsByTagName("tr");
        let tableHits = 0;
        
        for (let i = 1; i < tr.length; i++) { // Skip header
            let visible = false;
            const td = tr[i].getElementsByTagName("td");
            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    // Use innerText to match only visible content
                    if (td[j].innerText.toUpperCase().indexOf(filter) > -1) {
                        visible = true;
                        break;
                    }
                }
            }
            tr[i].style.display = visible ? "" : "none";
            if (visible) tableHits++;
        }
        if (isListActive && tableHits > 0) hasResults = true;
    }

    // 2. Filter Grid View (Cards)
    if (gridView) {
        const cards = gridView.children;
        let gridHits = 0;
        
        for (let i = 0; i < cards.length; i++) {
            const card = cards[i];
            // Use innerText to match only visible content
            // Using innerText ensures we don't match hidden HTML attributes or scripts
            if (card.innerText.toUpperCase().indexOf(filter) > -1) {
                card.style.display = ""; 
                gridHits++;
            } else {
                card.style.display = "none";
            }
        }
        if (isGridActive && gridHits > 0) hasResults = true;
    }

    // Toggle No Results Message
    const noResultsDiv = document.getElementById('no-search-results');
    if (noResultsDiv) {
        // Only show if the ACTIVE view has no results
        // If search is empty, we consider it having results (all shown)
        if (filter !== "" && !hasResults) {
            noResultsDiv.style.display = "block";
        } else {
            noResultsDiv.style.display = "none";
        }
    }
};
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
