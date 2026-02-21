<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$isMaker = (($role ?? '') === 'MAKER' || ($role ?? '') === 'Maker');
$pendingCount = $stats['pending'] ?? 0;
$wipCount = $stats['wip'] ?? 0;
$completedCount = $stats['completed'] ?? 0;
$confirmCount = $stats['confirmation'] ?? 0;
$currentFilter = $statusFilter ?? '';
?>

<div class="main" style="background: #f8fafc; padding: 15px 15px 60px 15px;">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">My Tasks</h2>
            <p style="color:var(--text-secondary); margin:4px 0 0 0; font-size:13px;">All your script requests.</p>
        </div>
    </div>

    <?php if ($isMaker && $stats): ?>
    <!-- Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        
        <!-- Revise -->
        <a href="?controller=request&action=index&status_filter=revise" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentFilter === 'revise' ? ' box-shadow: 0 0 0 2px #f59e0b;' : ''; ?>">
            <div style="background: #fef3c7; padding: 6px; border-radius: 8px; color: #d97706;">
                <i class="fi fi-rr-time-forward" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Revise</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $pendingCount; ?></div>
            </div>
        </a>

        <!-- WIP -->
        <a href="?controller=request&action=index&status_filter=wip" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentFilter === 'wip' ? ' box-shadow: 0 0 0 2px #3b82f6;' : ''; ?>">
            <div style="background: #eff6ff; padding: 6px; border-radius: 8px; color: #2563eb;">
                <i class="fi fi-rr-settings" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">WIP</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $wipCount; ?></div>
            </div>
        </a>

        <!-- Done -->
        <a href="?controller=request&action=index&status_filter=done" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentFilter === 'done' ? ' box-shadow: 0 0 0 2px #10b981;' : ''; ?>">
            <div style="background: #ecfdf5; padding: 6px; border-radius: 8px; color: #059669;">
                <i class="fi fi-rr-check" style="font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Done</div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $completedCount; ?></div>
            </div>
        </a>

        <!-- Konfirmasi -->
        <?php if ($confirmCount > 0): ?>
        <a href="?controller=request&action=index&status_filter=confirm" style="text-decoration:none; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #8b5cf6; background: white; display: flex; align-items: center; gap: 12px; transition: all 0.2s;<?php echo $currentFilter === 'confirm' ? ' box-shadow: 0 0 0 2px #8b5cf6;' : ''; ?>">
            <div style="background: #ede9fe; padding: 6px; border-radius: 8px; color: #7c3aed;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Konfirmasi</div>
                <div style="font-size: 20px; font-weight: 700; color: #8b5cf6;"><?php echo $confirmCount; ?></div>
            </div>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        
        <?php if ($isMaker): ?>
        <!-- Status Filter Tabs -->
        <div style="display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 15px;">
            <?php
            $tabs = [
                '' => ['label' => 'All', 'count' => null],
                'revise' => ['label' => 'Revisi', 'count' => $pendingCount, 'bg' => '#fef3c7', 'color' => '#b45309'],
                'confirm' => ['label' => 'Perlu Konfirmasi', 'count' => $confirmCount, 'bg' => '#ede9fe', 'color' => '#7c3aed'],
                'draft' => ['label' => 'Draft', 'count' => null, 'bg' => '#f3f4f6', 'color' => '#4b5563'],
                'wip' => ['label' => 'WIP', 'count' => $wipCount, 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
                'done' => ['label' => 'Done', 'count' => $completedCount, 'bg' => '#d1fae5', 'color' => '#065f46'],
            ];
            foreach ($tabs as $filterVal => $tab):
                $isActive = ($currentFilter === $filterVal);
                $activeStyle = $isActive 
                    ? 'color: var(--primary-red); border-bottom: 2px solid var(--primary-red); font-weight: 700;' 
                    : 'color: #94a3b8; border-bottom: 2px solid transparent; font-weight: 600;';
            ?>
            <a href="?controller=request&action=index<?php echo $filterVal ? '&status_filter=' . $filterVal : ''; ?>" 
               style="padding: 10px 16px; text-decoration: none; font-size: 13px; <?php echo $activeStyle; ?> margin-bottom: -2px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;">
                <?php echo $tab['label']; ?>
                <?php if ($tab['count'] !== null && $tab['count'] > 0): ?>
                    <span style="background: <?php echo $tab['bg']; ?>; color: <?php echo $tab['color']; ?>; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700;"><?php echo $tab['count']; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">My Script Requests</h4>
        <?php endif; ?>
        
        <!-- Advanced Filter Bar -->
        <div class="filter-bar" style="margin-bottom:20px; background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <form method="GET">
                <input type="hidden" name="controller" value="request">
                <input type="hidden" name="action" value="index">
                <?php if (!empty($currentFilter)): ?>
                <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($currentFilter); ?>">
                <?php endif; ?>                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <!-- Quick Search (Moved to Left) -->
                        <div style="display:inline-block; vertical-align:middle; position:relative;">
                            <input type="text" id="searchInput" oninput="handleSearchInput(this)" autocomplete="off" placeholder="Quick Search..." style="padding:8px 30px 8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:250px;">
                            <span id="clearSearchBtn" onclick="clearSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; display:none; font-size:16px; font-weight:bold;">&times;</span>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="background:var(--primary-red); color:white; padding:8px 20px; border-radius:6px; font-weight:600; border:none; cursor:pointer;">Apply Filters</button>
                        
                        <?php if(!empty($_GET)): ?>
                             <a href="?controller=request&action=index" style="margin-left:5px; color:#64748b; font-size:13px; text-decoration:none;">Reset</a>
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
                    
                     <a href="?controller=request&action=index" style="font-size:12px; color:#ef4444; text-decoration:underline; margin-left:5px;">Clear All</a>
                </div>
                <?php endif; ?>

            </form>
        </div>

        <?php if (empty($requests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>You haven't created any requests yet.</p>
                <a href="?controller=request&action=create" class="btn btn-primary" style="margin-top:15px; display:inline-block;">Create New Request</a>
            </div>
        <?php else: ?>
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
                    <?php foreach ($requests as $index => $req): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                         <td style="padding: 10px; vertical-align: middle;">
                            <?php if (in_array($req['status'] ?? '', ['REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION', 'DRAFT'])): ?>
                                <a href="?controller=request&action=edit&id=<?php echo $req['id']; ?>" style="display:inline-block; width:70px; text-align:center; padding:5px 0; background: #facc15; color: #1e293b; text-decoration: none; border-radius: 6px; font-size: 11px; font-weight: 600; border:1px solid #eab308;">
                                    <?php echo ($req['status'] === 'DRAFT') ? 'Edit Draft' : 'Revise'; ?>
                                </a>
                            <?php elseif (($req['status'] ?? '') === 'PENDING_MAKER_CONFIRMATION'): ?>
                                <a href="index.php?controller=request&action=review&id=<?php echo $req['id']; ?>" style="display:inline-block; width:60px; text-align:center; padding:5px 0; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; color:#3b82f6; font-weight:700; text-decoration:none; font-size:10px;" title="Lihat Detail & Evidence">
                                    View
                                </a>
                            <?php else: ?>
                                <a href="index.php?controller=audit&action=detail&id=<?php echo $req['id']; ?>" style="display:inline-block; width:60px; text-align:center; padding:5px 0; background:#fff; border:1px solid #ef4444; border-radius:6px; color:#ef4444; font-weight:600; text-decoration:none; font-size:11px; transition:all 0.2s; box-shadow:0 1px 2px rgba(239, 68, 68, 0.05);" onmouseover="this.style.background='#ef4444'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='#ef4444';">
                                    View
                                </a>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                            <?php 
                                $tId = $req['ticket_id'] ?? 'Pending';
                                echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId;
                            ?>
                        </td>
                        <td style="padding: 10px; vertical-align: middle; color: #334155;">
                           <?php echo htmlspecialchars($req['script_number']); ?>
                        </td>
                        <td style="padding: 10px; vertical-align: middle; color: #334155;"><?php echo htmlspecialchars($req['produk']); ?></td>
                        <td style="padding: 10px; vertical-align: middle; color: #334155;"><?php echo htmlspecialchars($req['media']); ?></td>
                        <td style="padding: 10px; vertical-align: middle; color: #334155;"><?php echo htmlspecialchars($req['kategori']); ?></td>
                        <td style="padding: 10px; vertical-align: middle; color: #334155;"><?php echo htmlspecialchars($req['jenis']); ?></td>
                         <td style="padding: 10px; vertical-align: middle; color: #64748b; font-size: 12px;">
                            <?php 
                                $content = strip_tags($req['content'] ?? '');
                                if (empty($content) && isset($req['input_mode']) && $req['input_mode'] === 'FILE_UPLOAD') {
                                     echo '<span style="color:#3b82f6;">File Upload</span>';
                                } else {
                                     echo htmlspecialchars(strlen($content) > 50 ? substr($content, 0, 47) . '...' : ($content ?: '-'));
                                }
                            ?>
                        </td>
                        <td style="padding: 10px; vertical-align: middle; color: #64748b;">
                            <?php 
                            if ($req['created_at'] instanceof DateTime) {
                                echo ($req['created_at'] instanceof DateTime) ? $req['created_at']->format('d M Y') : date('d M Y', strtotime($req['created_at']));
                            } else {
                                echo ($req['created_at'] instanceof DateTime) ? $req['created_at']->format('d M Y') : date('d M Y', strtotime($req['created_at']));
                            }
                            ?>
                        </td>
                        <td style="padding: 10px; vertical-align: middle;">
                            <?php 
                            $statusColor = '#fef3c7'; // default yellow
                            $statusTextColor = '#b45309';
                            $statusLabel = $req['status'];
                            if ($req['status'] === 'LIBRARY' || $req['status'] === 'CLOSED') {
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php include __DIR__ . '/../layouts/pagination.php'; ?>
        <?php endif; ?>
    </div>
</div>

<script>
let currentDateSortOrder = 'desc'; // Default: newest first

function sortTableByDate(columnIndex) {
    const table = document.getElementById('dataTable');
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

<?php require_once 'app/views/layouts/footer.php'; ?>
