<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

// Defaults
$title = isset($pageTitle) ? $pageTitle : 'Dashboard Approval';
$desc = isset($pageDesc) ? $pageDesc : 'Manage pending script requests.';
$currentViewMode = $viewMode ?? 'pending';
$pendingCount = $stats['pending'] ?? 0;
$historyCount = $stats['history'] ?? 0;
?>

<div class="main" style="background: #f8fafc; padding: 15px 15px 60px 15px;">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;"><?php echo htmlspecialchars($title); ?></h2>
            <p style="color:var(--text-secondary); margin:4px 0 0 0; font-size:13px;">Manage pending script requests & approval history.</p>
        </div>
    </div>

    <?php if (isset($stats)): ?>
    <!-- Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
        
        <!-- Need to Approval -->
        <a href="?controller=dashboard&action=index&view_mode=pending" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #ef4444; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentViewMode === 'pending' ? ' box-shadow: 0 0 0 2px #ef4444;' : ''; ?>">
            <div style="background: #fee2e2; padding: 6px; border-radius: 8px; color: #dc2626;">
                <i class="fi fi-rr-clock" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">Need to Approval</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $pendingCount; ?></div>
            </div>
        </a>
        
        <!-- My History -->
        <a href="?controller=dashboard&action=index&view_mode=history" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentViewMode === 'history' ? ' box-shadow: 0 0 0 2px #3b82f6;' : ''; ?>">
            <div style="background: #eff6ff; padding: 6px; border-radius: 8px; color: #3b82f6;">
                <i class="fi fi-rr-time-past" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">My History</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $historyCount; ?></div>
            </div>
        </a>
        
    </div>

    <!-- View Mode Tabs -->
    <div style="display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 15px;">
        <?php
        $tabs = [
            'pending' => ['label' => 'Need to Approval', 'count' => $pendingCount, 'color' => '#ef4444'],
            'history' => ['label' => 'My History', 'count' => $historyCount, 'color' => '#3b82f6'],
        ];
        foreach ($tabs as $modeVal => $tab):
            $isActive = ($currentViewMode === $modeVal);
            $tabColor = $isActive ? $tab['color'] : 'transparent';
            $textColor = $isActive ? '#1e293b' : '#94a3b8';
            $fontWeight = $isActive ? '700' : '500';
        ?>
            <a href="?controller=dashboard&action=index&view_mode=<?php echo $modeVal; ?>" style="padding: 10px 20px; text-decoration: none; color: <?php echo $textColor; ?>; font-weight: <?php echo $fontWeight; ?>; font-size: 13px; border-bottom: 3px solid <?php echo $tabColor; ?>; transition: all 0.2s; display:flex; align-items:center; gap:6px;">
                <?php echo $tab['label']; ?>
                <?php if ($tab['count'] > 0): ?>
                    <span style="background: <?php echo $isActive ? $tab['color'] : '#e2e8f0'; ?>; color: <?php echo $isActive ? 'white' : '#64748b'; ?>; font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700;">
                        <?php echo $tab['count']; ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="dashboard">
                <input type="hidden" name="action" value="index">
                <input type="hidden" name="view_mode" value="<?php echo htmlspecialchars($currentViewMode); ?>">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Moved to Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <input type="text" id="searchInput" oninput="handleSearchInput(this)" autocomplete="off" placeholder="Quick Search..." style="padding:8px 30px 8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px;">
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>
                        
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
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                        <?php if(!empty($_GET)): ?>
                            <a href="?controller=dashboard&action=index&view_mode=<?php echo htmlspecialchars($currentViewMode); ?>" style="color:#64748b; font-size:13px; text-decoration:none;">Reset All</a>
                        <?php endif; ?>
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
                    
                     <a href="?controller=dashboard&action=index&view_mode=<?php echo htmlspecialchars($currentViewMode); ?>" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        
        <?php if ($currentViewMode === 'pending'): ?>
        <!-- ====== PENDING APPROVAL TABLE ====== -->
        <?php if (empty($pendingRequests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No pending requests found in your queue.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x: auto; margin-top: 20px; border-radius: 8px; border: 1px solid #eee;">
            <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:13px; min-width: 1000px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Action</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Ticket ID</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Script No</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Produk</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Media</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Kategori</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Jenis</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase; width:150px;">Isi Script</th>
                         <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Current Status</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Requester</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase; cursor:pointer;" onclick="sortTableByDate(10, 'dataTable')">
                            Date <i class="fi fi-rr-sort" style="font-size:10px; margin-left:2px; vertical-align:middle;"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $index => $req): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;">
                            <a href="index.php?controller=request&action=review&id=<?php echo $req['id']; ?>" 
                               style="background:var(--primary-red); color:white; text-decoration:none; padding:4px 10px; border-radius:4px; font-size:11px; white-space:nowrap; font-weight:700;">
                                View
                            </a>
                        </td>
                        <td style="padding:10px; color:#64748b;">
                            <a href="index.php?controller=request&action=review&id=<?php echo $req['id']; ?>" style="text-decoration:none; color:inherit; font-weight:600;">
                                <?php 
                                    $tId = $req['ticket_id'] ?? '-';
                                    echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                                ?>
                            </a>
                        </td>
                        <td style="padding:10px; color:#334155;">
                            <?php echo htmlspecialchars($req['script_number']); ?>
                        </td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['produk']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['media']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['kategori']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['jenis']); ?></td>
                        <td style="padding:10px; color:#64748b; font-size:11px;">
                            <?php 
                                $content = strip_tags($req['content'] ?? '');
                                if (empty($content) && isset($req['input_mode']) && $req['input_mode'] === 'FILE_UPLOAD') {
                                     echo '<span style="color:#3b82f6;">File Upload</span>';
                                } else {
                                     echo htmlspecialchars(strlen($content) > 30 ? substr($content, 0, 27) . '...' : ($content ?: '-'));
                                }
                            ?>
                        </td>
                        <td style="padding:10px;">
                             <span style="background:#fef3c7; color:#b45309; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                            <?php if (!empty($req['has_draft'])): ?>
                                <span style="font-size:10px; color:#6b21a8; font-weight:700; margin-left:3px;">(DRAFT)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px; font-weight:600; color:#334155;"><?php echo htmlspecialchars($req['created_by']); ?></td>
                        <td style="padding:10px;" data-sort-val="<?php 
                            $rawDate = $req['created_at'];
                            echo ($rawDate instanceof DateTime) ? $rawDate->getTimestamp() : strtotime($rawDate); 
                        ?>">
                            <?php 
                            if ($req['created_at'] instanceof DateTime) {
                                echo $req['created_at']->format('d M');
                            } else {
                                echo date('d M', strtotime($req['created_at']));
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- ====== HISTORY TABLE ====== -->
        <?php if (empty($historyRequests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No approval history found.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x: auto; margin-top: 20px; border-radius: 8px; border: 1px solid #eee;">
            <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:13px; min-width: 1000px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Action</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Ticket ID</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Script No</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Produk</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Media</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Kategori</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Jenis</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase; width:150px;">Isi Script</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Current Status</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Requester</th>
                        <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase; cursor:pointer;" onclick="sortTableByDate(10, 'dataTable')">
                            Date <i class="fi fi-rr-sort" style="font-size:10px; margin-left:2px; vertical-align:middle;"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyRequests as $req): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;">
                            <?php 
                            $isCompleted = in_array(strtoupper($req['status']), ['LIBRARY', 'CLOSED', 'APPROVED_PROCEDURE']);
                            $userDept = strtoupper($_SESSION['user']['dept'] ?? '');
                            $currentRole = strtoupper($req['current_role'] ?? '');
                            $isMyTurn = !$isCompleted && $currentRole === $userDept;
                            $viewUrl = $isMyTurn 
                                ? "?controller=request&action=review&id={$req['id']}" 
                                : "?controller=audit&action=detail&id={$req['id']}";
                            ?>
                            <a href="<?php echo $viewUrl; ?>" style="text-decoration:none; background:var(--primary-red); color:white; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                                View
                            </a>
                        </td>
                        <td style="padding:10px; color:#64748b; font-weight:600;">
                            <?php 
                                $tId = $req['ticket_id'] ?? '-';
                                echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                            ?>
                        </td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['script_number']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['produk']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['media']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['kategori']); ?></td>
                        <td style="padding:10px; color:#334155;"><?php echo htmlspecialchars($req['jenis']); ?></td>
                        <td style="padding:10px; color:#64748b; font-size:11px;">
                            <?php 
                                $content = strip_tags($req['content'] ?? '');
                                if (empty($content) && isset($req['input_mode']) && $req['input_mode'] === 'FILE_UPLOAD') {
                                     echo '<span style="color:#3b82f6;">File Upload</span>';
                                } else {
                                     echo htmlspecialchars(strlen($content) > 30 ? substr($content, 0, 27) . '...' : ($content ?: '-'));
                                }
                            ?>
                        </td>
                        <td style="padding:10px;">
                            <?php 
                                $statusColor = '#fef3c7';
                                $statusTextColor = '#b45309';
                                $statusLabel = $req['status'];
                                if (in_array($req['status'], ['LIBRARY', 'CLOSED'])) {
                                    $statusColor = '#d1fae5';
                                    $statusTextColor = '#065f46';
                                } elseif (in_array($req['status'], ['REJECTED', 'REVISION', 'MINOR_REVISION', 'MAJOR_REVISION'])) {
                                    $statusColor = '#fee2e2';
                                    $statusTextColor = '#b91c1c';
                                } elseif ($req['status'] === 'DRAFT') {
                                    $statusColor = '#f3f4f6';
                                    $statusTextColor = '#4b5563';
                                } elseif (in_array($req['status'], ['CREATED', 'APPROVED_SPV', 'APPROVED_PIC', 'APPROVED_PROCEDURE'])) {
                                    $statusColor = '#eff6ff';
                                    $statusTextColor = '#1d4ed8';
                                } elseif ($req['status'] === 'PENDING_MAKER_CONFIRMATION') {
                                    $statusColor = '#ede9fe';
                                    $statusTextColor = '#7c3aed';
                                    $statusLabel = 'PERLU KONFIRMASI';
                                }
                            ?>
                            <span style="background:<?php echo $statusColor; ?>; color:<?php echo $statusTextColor; ?>; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:700;">
                                <?php echo htmlspecialchars($statusLabel); ?>
                            </span>
                        </td>
                        <td style="padding:10px; font-weight:600; color:#334155;"><?php echo htmlspecialchars($req['created_by']); ?></td>
                        <td style="padding:10px;" data-sort-val="<?php 
                            $actionDate = $req['my_action_date'] ?? $req['created_at'];
                            echo ($actionDate instanceof DateTime) ? $actionDate->getTimestamp() : strtotime($actionDate); 
                        ?>">
                            <?php 
                                if ($actionDate instanceof DateTime) {
                                    echo $actionDate->format('d M, H:i');
                                } else {
                                    echo date('d M, H:i', strtotime($actionDate));
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script>
let currentDateSortOrder = 'desc';

function sortTableByDate(columnIndex, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length > 1);
    
    // Toggle sort order
    currentDateSortOrder = currentDateSortOrder === 'asc' ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        const valA = a.cells[columnIndex].getAttribute('data-sort-val') || 0;
        const valB = b.cells[columnIndex].getAttribute('data-sort-val') || 0;
        
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
