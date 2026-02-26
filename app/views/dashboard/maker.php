<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$pendingCount = $stats['pending'] ?? 0;
$wipCount = $stats['wip'] ?? 0;
$completedCount = $stats['completed'] ?? 0;
$confirmCount = $stats['confirmation'] ?? 0;
?>
<div class="main" style="background: #f8fafc; padding: 15px 15px 60px 15px;">
    
    <!-- Compact Header -->
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">Dashboard</h2>
        <div style="font-size: 13px; color: #64748b;">
            Logged: <strong><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></strong>
        </div>
    </div>

    <!-- Small Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        
        <!-- Pending -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #fef3c7; padding: 6px; border-radius: 8px; color: #d97706;">
                <i class="fi fi-rr-time-forward" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Revise</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $pendingCount; ?></div>
            </div>
        </div>

        <!-- WIP -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #eff6ff; padding: 6px; border-radius: 8px; color: #2563eb;">
                <i class="fi fi-rr-settings" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">WIP</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $wipCount; ?></div>
            </div>
        </div>

        <!-- Completed -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #ecfdf5; padding: 6px; border-radius: 8px; color: #059669;">
                <i class="fi fi-rr-check" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Done</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $completedCount; ?></div>
            </div>
        </div>

        <!-- Perlu Konfirmasi -->
        <?php if ($confirmCount > 0): ?>
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #8b5cf6; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #ede9fe; padding: 6px; border-radius: 8px; color: #7c3aed;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Konfirmasi</div>
                <div style="font-size: 20px; font-weight: 700; color: #8b5cf6;"><?php echo $confirmCount; ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Data Section -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="dashboard">
                <input type="hidden" name="action" value="index">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Moved to Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <input type="text" id="searchInput" oninput="handleSearchInput(this)" autocomplete="off" placeholder="Quick Search..." style="padding:8px 30px 8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px;">
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>

                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                        
                        <?php if(!empty($_GET) && (isset($_GET['jenis']) || isset($_GET['produk']) || isset($_GET['kategori']) || isset($_GET['media']) || isset($_GET['start_date']))): ?>
                             <a href="?controller=dashboard&action=maker" style="margin-left:5px; color:#64748b; font-size:13px; text-decoration:none;">Reset</a>
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
                            if (strlen($displayText) > 18) {
                                $displayText = substr($displayText, 0, 16) . '...';
                            }
                        }
                    ?>
                    <div class="dropdown" style="position:relative;">
                        <label style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:6px; display:block; text-transform:uppercase; letter-spacing:0.5px;"><?php echo $label; ?></label>
                        <button type="button" onclick="toggleDropdown('dd-<?php echo $key; ?>')" style="width:100%; text-align:left; background:white; border:1px solid #cbd5e1; padding:8px 10px; border-radius:6px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#334155; height: 35px;">
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;"><?php echo htmlspecialchars($displayText); ?></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        
                        <!-- Dropdown Content -->
                        <div id="dd-<?php echo $key; ?>" class="dropdown-content" style="display:none; position:absolute; top:100%; left:0; width:100%; min-width: 200px; background:white; border:1px solid #e2e8f0; border-radius:6px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); z-index:100; margin-top:4px; max-height:220px; overflow-y:auto; padding:8px;">
                             <?php if(empty($options)): ?>
                                <div style="color:#94a3b8; font-size:12px; padding:4px;">No options available</div>
                            <?php else: ?>
                                <?php foreach($options as $opt): 
                                    $isChecked = in_array($opt, $selected) ? 'checked' : '';
                                ?>
                                <label style="display:flex; align-items:center; gap:8px; padding:6px; font-size:12px; color:#334155; cursor:pointer; border-radius:4px; transition:background 0.1s; border-bottom:1px solid #f8fafc;">
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
                        // Close all others
                        document.querySelectorAll('.dropdown-content').forEach(d => {
                            if(d.id !== id) d.style.display = 'none';
                        });
                        
                        var el = document.getElementById(id);
                        // Toggle
                        if (el.style.display === 'none') {
                            el.style.display = 'block';
                            // Ensure z-index is highest
                            el.style.zIndex = '999';
                        } else {
                            el.style.display = 'none';
                        }
                    }
                    </script>

                </div>

                <!-- Moved Apply Button to Bottom Right -->
                <div style="margin-top:20px; display:flex; justify-content:flex-end; align-items:center;">
                    <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                    <?php if(!empty($_GET)): ?>
                            <a href="?controller=dashboard&action=index" style="margin-left:10px; color:#64748b; font-size:13px; text-decoration:none;">Reset All</a>
                    <?php endif; ?>
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
                    
                     <a href="?controller=dashboard&action=index" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>

        <!-- TAB SYSTEM -->
        <div style="margin-top: 15px;">
            <div style="display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 15px;">
                <button onclick="switchMakerTab('revisions')" id="tab-revisions" style="padding: 10px 20px; border: none; background: transparent; cursor: pointer; font-size: 13px; font-weight: 700; color: var(--primary-red); border-bottom: 2px solid var(--primary-red); margin-bottom: -2px; transition: all 0.2s;">
                    Revisi
                    <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-left: 5px;"><?php echo count($revisions); ?></span>
                </button>
                <button onclick="switchMakerTab('confirmations')" id="tab-confirmations" style="padding: 10px 20px; border: none; background: transparent; cursor: pointer; font-size: 13px; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s;">
                    Perlu Konfirmasi
                    <?php if ($confirmCount > 0): ?>
                    <span style="background: #ede9fe; color: #7c3aed; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-left: 5px;"><?php echo $confirmCount; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- REVISIONS TAB -->
            <div id="panel-revisions">

            <div style="overflow-x: auto;">
                <table id="dataTable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc;">
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; width: 80px;">Action</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Ticket ID</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Script No</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Produk</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Media</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Kategori</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Jenis</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; width: 200px;">Isi Script</th>
                            <th onclick="sortTableByDate(8)" style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; cursor: pointer; user-select: none;">
                                Created Date 
                                <span style="font-size: 8px; margin-left: 4px;">▼▲</span>
                            </th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($revisions)): ?>
                            <tr>
                                <td colspan="10" style="padding: 30px; text-align: center; color: #94a3b8;">
                                    No revisions needed.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($revisions as $rev): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; vertical-align: middle;">
                                    <a href="index.php?controller=request&action=edit&id=<?php echo $rev['id']; ?>" 
                                       style="background: #facc15; color: #1e293b; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; border:1px solid #eab308;">
                                       Revise
                                    </a>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                                    <?php 
                                    $tId = $rev['ticket_id'] ?? '-'; 
                                    echo htmlspecialchars(is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId);
                                    ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                    <?php echo htmlspecialchars($rev['script_number']); ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                    <?php echo htmlspecialchars($rev['produk']); ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                    <?php echo htmlspecialchars($rev['media']); ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                    <?php echo htmlspecialchars($rev['kategori']); ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                    <?php echo htmlspecialchars($rev['jenis']); ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #64748b; font-size: 12px;">
                                    <?php 
                                    // Handle Content Truncation
                                    $content = strip_tags($rev['content'] ?? '');
                                    if (empty($content) && isset($rev['input_mode']) && $rev['input_mode'] === 'FILE_UPLOAD') {
                                        echo '<span style="color:#3b82f6;">File Upload</span>';
                                    } else {
                                        echo htmlspecialchars(strlen($content) > 50 ? substr($content, 0, 47) . '...' : ($content ?: '-'));
                                    }
                                    ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                                    <?php 
                                    if (isset($rev['created_at']) && $rev['created_at'] instanceof DateTime) {
                                        echo ($rev['created_at'] instanceof DateTime) ? $rev['created_at']->format('d M Y') : date('d M Y', strtotime($rev['created_at']));
                                    } elseif (isset($rev['created_at'])) {
                                        echo ($rev['created_at'] instanceof DateTime) ? $rev['created_at']->format('d M Y') : date('d M Y', strtotime($rev['created_at']));
                                    }
                                    ?>
                                </td>
                                <td style="padding: 10px; vertical-align: middle;">
                                    <span style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:700;">
                                        <?php echo htmlspecialchars($rev['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
            <!-- END REVISIONS TAB -->

            <!-- CONFIRMATIONS TAB -->
            <div id="panel-confirmations" style="display:none;">
                <div style="overflow-x: auto;">
                    <table id="confirmTable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="text-align: left; background: #f8fafc;">
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; width: 80px;">Action</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Ticket ID</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Script No</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Produk</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Media</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Kategori</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; width: 200px;">Isi Script</th>
                                <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($confirmations)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 30px; text-align: center; color: #94a3b8;">
                                        Tidak ada request yang perlu dikonfirmasi.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($confirmations as $conf): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px; vertical-align: middle;">
                                        <div style="display:flex; gap:5px; align-items:center;">
                                            <a href="index.php?controller=request&action=review&id=<?php echo $conf['id']; ?>" 
                                               style="background:#eff6ff; color:#3b82f6; text-decoration:none; padding:5px 10px; border-radius:6px; font-size:10px; font-weight:700; border:1px solid #bfdbfe;"
                                               title="Lihat Detail & Evidence">
                                                View
                                            </a>
                                        </div>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                                        <?php 
                                        $tId = $conf['ticket_id'] ?? '-'; 
                                        echo htmlspecialchars(is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId);
                                        ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                        <?php echo htmlspecialchars($conf['script_number']); ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                        <?php echo htmlspecialchars($conf['produk']); ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                        <?php echo htmlspecialchars($conf['media']); ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #334155;">
                                        <?php echo htmlspecialchars($conf['kategori']); ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #64748b; font-size: 12px;">
                                        <?php 
                                        $content = strip_tags($conf['content'] ?? '');
                                        if (empty($content) && isset($conf['input_mode']) && $conf['input_mode'] === 'FILE_UPLOAD') {
                                            echo '<span style="color:#3b82f6;">File Upload</span>';
                                        } else {
                                            echo htmlspecialchars(strlen($content) > 50 ? substr($content, 0, 47) . '...' : ($content ?: '-'));
                                        }
                                        ?>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                                        <?php 
                                        if (isset($conf['created_at']) && $conf['created_at'] instanceof DateTime) {
                                            echo $conf['created_at']->format('d M Y');
                                        } elseif (isset($conf['created_at'])) {
                                            echo date('d M Y', strtotime($conf['created_at']));
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- END CONFIRMATIONS TAB -->

        </div>
    </div>
</div>

<script>
let currentDateSortOrder = 'desc';

function sortTableByDate(columnIndex) {
    const table = document.getElementById('dataTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > 1);
    currentDateSortOrder = currentDateSortOrder === 'asc' ? 'desc' : 'asc';
    rows.sort((a, b) => {
        const parseDate = (str) => {
            if (!str) return new Date(0);
            const m = {'Jan':0,'Feb':1,'Mar':2,'Apr':3,'May':4,'Jun':5,'Jul':6,'Aug':7,'Sep':8,'Oct':9,'Nov':10,'Dec':11};
            const p = str.trim().split(' ');
            return p.length === 3 ? new Date(parseInt(p[2]), m[p[1]], parseInt(p[0])) : new Date(0);
        };
        const d1 = parseDate(a.cells[columnIndex].textContent);
        const d2 = parseDate(b.cells[columnIndex].textContent);
        return currentDateSortOrder === 'asc' ? d1 - d2 : d2 - d1;
    });
    rows.forEach(row => tbody.appendChild(row));
}

// Tab Switching
function switchMakerTab(tab) {
    const tabs = ['revisions', 'confirmations'];
    tabs.forEach(t => {
        document.getElementById('panel-' + t).style.display = (t === tab) ? 'block' : 'none';
        const btn = document.getElementById('tab-' + t);
        if (t === tab) {
            btn.style.color = 'var(--primary-red)';
            btn.style.borderBottomColor = 'var(--primary-red)';
            btn.style.fontWeight = '700';
        } else {
            btn.style.color = '#94a3b8';
            btn.style.borderBottomColor = 'transparent';
            btn.style.fontWeight = '600';
        }
    });
}

// Maker Confirm Request
function confirmRequest(requestId) {
    Swal.fire({
        title: 'Selesai Review & Kirim?',
        html: 'Apakah Anda yakin telah selesai mereview script ini? Script akan dikirimkan kembali ke <b>Procedure</b> untuk di-publish.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Kirim Sekarang!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('index.php?controller=request&action=makerConfirm', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ request_id: requestId, decision: 'confirm' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil Dikirim!', data.message || 'Script dikembalikan ke Procedure.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Gagal mengirim.', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Network error: ' + err.message, 'error'));
        }
    });
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>


