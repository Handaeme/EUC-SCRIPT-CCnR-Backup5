<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;">My History (Approvals)</h2>
        <p style="color:var(--text-secondary);">List of requests you have processed.</p>
    </div>

    <div class="card">
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="request">
                <input type="hidden" name="action" value="history">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="font-weight:700; color:#1e293b; font-size:15px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        Advanced Filters
                    </div>
                    <div>
                        <!-- Quick Search -->
                        <div style="display:inline-block; margin-right:15px; vertical-align:middle;">
                            <input type="text" id="searchInput" oninput="window.filterTable('searchInput', 'dataTable')" autocomplete="off" placeholder="Quick Search..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:200px;">
                        </div>

                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                        <?php if(!empty($_GET['jenis']) || !empty($_GET['produk']) || !empty($_GET['kategori']) || !empty($_GET['media']) || !empty($_GET['start_date'])): ?>
                             <a href="?controller=request&action=history" style="margin-left:10px; color:#64748b; font-size:13px; text-decoration:none;">Reset All</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:20px;">
                    
                    <!-- Date Range -->
                    <div style="flex: 0 0 320px; max-width:100%;">
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
                    
                    if (isset($filterOptions)):
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
                    <div class="dropdown" style="position:relative; flex:1 1 200px;">
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
                    <?php endforeach; 
                    endif;
                    ?>

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
                if(!empty($activeFilters)) {
                    foreach($activeFilters as $key => $vals) {
                        if(!empty($vals)) $hasActiveFilters = true;
                    }
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
                    
                     <a href="?controller=request&action=history" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        <?php if (empty($history)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No approval history found.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:13px;">
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
                            <th style="padding:10px; border-bottom:2px solid #eee; color:#64748b; font-size:11px; text-transform:uppercase;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $req): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;">
                                <?php 
                                // Redirect to Audit Trail for completed requests or when it's NOT this user's turn
                                $isCompleted = in_array(strtoupper($req['status']), ['LIBRARY', 'CLOSED', 'APPROVED_PROCEDURE']);
                                $userDept = strtoupper($_SESSION['user']['dept'] ?? '');
                                $currentRole = strtoupper($req['current_role'] ?? '');
                                
                                // Only allow editing if: NOT completed AND current_role matches user's department
                                $isMyTurn = !$isCompleted && $currentRole === $userDept;
                                
                                $viewUrl = $isMyTurn 
                                    ? "?controller=request&action=review&id={$req['id']}" 
                                    : "?controller=audit&action=detail&id={$req['id']}";
                                ?>
                                <a href="<?php echo $viewUrl; ?>" class="btn-view" style="text-decoration:none; background:var(--primary-red); color:white; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
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
                                    if (empty($content) && isset($req['mode']) && $req['mode'] === 'FILE_UPLOAD') {
                                         echo '<span style="color:#3b82f6;">File Upload</span>';
                                    } else {
                                         echo htmlspecialchars(strlen($content) > 30 ? substr($content, 0, 27) . '...' : ($content ?: '-'));
                                    }
                                ?>
                            </td>
                            <td style="padding:10px;">
                                <?php 
                                    $map = function($s) {
                                        $s = strtoupper($s);
                                        if (strpos($s, 'SUBMIT') !== false) return 'SUBMITTED';
                                        if (strpos($s, 'REVIS') !== false) return 'REVISI';
                                        if (strpos($s, 'REJECT') !== false) return 'REJECT';
                                        if (strpos($s, 'SPV') !== false) return 'SPV';
                                        if (strpos($s, 'PIC') !== false) return 'PIC';
                                        if (strpos($s, 'LEGAL') !== false) return 'LEGAL';
                                        if (strpos($s, 'CX') !== false) return 'CX';
                                        if (strpos($s, 'PROCEDURE') !== false) return 'PROCEDURE';
                                        if (strpos($s, 'LIBRARY') !== false || strpos($s, 'CLOSED') !== false) return 'LIBRARY';
                                        return $s;
                                    };
                                ?>
                                <div style="font-weight:600; color:#334155; font-size:12px;">
                                    <?php echo $map($req['status']); ?>
                                </div>
                            </td>
                            <td style="padding:10px; font-weight:600; color:#334155;"><?php echo htmlspecialchars($req['created_by']); ?></td>
                            <td style="padding:10px; color:#64748b; font-size:12px;">
                                <?php 
                                    $actionDate = $req['my_action_date'];
                                    if ($actionDate instanceof DateTime) {
                                        echo ($actionDate instanceof DateTime) ? $actionDate->format('d M, H:i') : date('d M, H:i', strtotime($actionDate));
                                    } else {
                                        echo ($actionDate instanceof DateTime) ? $actionDate->format('d M, H:i') : date('d M, H:i', strtotime($actionDate));
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php include __DIR__ . '/../layouts/pagination.php'; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
