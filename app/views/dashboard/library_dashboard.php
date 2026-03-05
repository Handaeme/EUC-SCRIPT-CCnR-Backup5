<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$ds = $dashStats ?? [];
$total_all      = $ds['total_all'] ?? 0;
$total_active   = $ds['total_active'] ?? 0;
$total_inactive = $ds['total_inactive'] ?? 0;
$pending        = $ds['pending_approvals'] ?? 0;
$this_month     = $ds['added_this_month'] ?? 0;
$recent_scripts = $ds['recent_scripts'] ?? [];
$recent_audit   = $ds['recent_audit'] ?? [];
$monthly_trend  = $ds['monthly_trend'] ?? [];
$by_kategori    = $ds['by_kategori'] ?? [];

// Prepare chart data
$jenis_labels  = json_encode(array_keys($ds['by_jenis'] ?? []));
$jenis_data    = json_encode(array_values($ds['by_jenis'] ?? []));
$produk_labels = json_encode(array_keys($ds['by_produk'] ?? []));
$produk_data   = json_encode(array_values($ds['by_produk'] ?? []));
$media_labels  = json_encode(array_keys($ds['by_media'] ?? []));
$media_data    = json_encode(array_values($ds['by_media'] ?? []));
$trend_labels  = json_encode(array_map(fn($t) => $t['label'], $monthly_trend));
$trend_data    = json_encode(array_map(fn($t) => $t['count'], $monthly_trend));

// Action badge helper
function actionBadge($action) {
    $map = [
        'CREATED'           => ['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>'Submitted'],
        'APPROVE_SPV'       => ['bg'=>'#d1fae5','color'=>'#065f46','label'=>'SPV Approved'],
        'APPROVED_SPV'      => ['bg'=>'#d1fae5','color'=>'#065f46','label'=>'SPV Approved'],
        'APPROVE_PIC'       => ['bg'=>'#dcfce7','color'=>'#14532d','label'=>'PIC Approved'],
        'APPROVED_PIC'      => ['bg'=>'#dcfce7','color'=>'#14532d','label'=>'PIC Approved'],
        'APPROVE_PROCEDURE' => ['bg'=>'#f0fdf4','color'=>'#166534','label'=>'Finalized'],
        'REJECTED'          => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Rejected'],
        'REVISION'          => ['bg'=>'#fef9c3','color'=>'#92400e','label'=>'Revision'],
    ];
    $b = $map[$action] ?? ['bg'=>'#f1f5f9','color'=>'#475569','label'=>$action];
    return "<span style='background:{$b['bg']};color:{$b['color']};padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;'>{$b['label']}</span>";
}
?>
<!-- Chart.js Local -->
<script src="public/js/chart.umd.min.js"></script>

<!-- Responsive Dashboard Styles -->
<style>
.dash-row2 {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 8px;
}
.dash-row2 > div {
    min-width: 280px;
    flex-shrink: 0;
    scroll-snap-align: start;
    flex: 1; /* Allow them to grow and fill empty space on large screens if desired, but prioritize scroll if not fitting */
}
.dash-row2 > div:first-child {
    min-width: 400px;
    flex: 2; /* Trend chart takes more space if available */
}
/* subtle scrollbar */
.dash-row2::-webkit-scrollbar { height: 6px; }
.dash-row2::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.dash-row2::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.dash-row3 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
.dash-table-wrap { overflow-x:auto; }

@media (max-width: 768px) {
    .dash-row3 { grid-template-columns: 1fr; }
    .dash-row2 > div:first-child { min-width: 300px; }
    .dash-row2 > div { min-width: 240px; }
    .dash-table-wrap table { font-size: 12px; }
}
</style>

<div class="main">
    <!-- ── PAGE HEADER ── -->
    <div style="margin-bottom:24px;">
        <h2 style="color:var(--primary-red);margin:0 0 4px 0;">Library Dashboard</h2>
        <p style="color:#94a3b8;margin:0;font-size:14px;">Ringkasan data script library secara keseluruhan.</p>
    </div>

    <!-- ══════════════════════════════════════════
         ROW 1 — KPI CARDS
    ══════════════════════════════════════════ -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">

        <!-- Total All -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="width:46px;height:46px;background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:800;color:#1e293b;line-height:1;"><?php echo number_format($total_all); ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:600;margin-top:2px;">Total Scripts</div>
            </div>
        </div>

        <!-- Active -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="width:46px;height:46px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:800;color:#1e293b;line-height:1;"><?php echo number_format($total_active); ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:600;margin-top:2px;">Active</div>
            </div>
        </div>

        <!-- Inactive -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="width:46px;height:46px;background:linear-gradient(135deg,#94a3b8,#64748b);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:800;color:#1e293b;line-height:1;"><?php echo number_format($total_inactive); ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:600;margin-top:2px;">Inactive</div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <a href="?controller=audit&action=index" style="text-decoration:none;">
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(245,158,11,0.15)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.04)'">
            <div style="width:46px;height:46px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:800;color:#1e293b;line-height:1;"><?php echo number_format($pending); ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:600;margin-top:2px;">Pending Approval</div>
            </div>
        </div>
        </a>

        <!-- Added This Month -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="width:46px;height:46px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:800;color:#1e293b;line-height:1;"><?php echo number_format($this_month); ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:600;margin-top:2px;">Baru Bulan Ini</div>
            </div>
        </div>

    </div>

    <?php
    // Shared colour palette (mirrors JS)
    $palette = ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'];
    function donutLegend($data, $paletteOffset, $palette, $param) {
        if (empty($data)) return;
        $total = array_sum($data);
        $i = $paletteOffset;
        foreach ($data as $name => $cnt) {
            $color = $palette[$i % count($palette)];
            $pct   = $total ? round($cnt / $total * 100) : 0;
            echo "<div style='display:flex;align-items:center;justify-content:space-between;gap:8px;padding:4px 0;border-bottom:1px solid #f8fafc;'>";
            echo "  <div style='display:flex;align-items:center;gap:6px;min-width:0;'>";
            echo "    <span style='width:10px;height:10px;border-radius:50%;background:{$color};flex-shrink:0;'></span>";
            echo "    <a href='?controller=dashboard&action=library&{$param}=" . urlencode($name) . "' style='font-size:12px;color:#374151;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;text-decoration:none;' title='" . htmlspecialchars($name) . "'>" . htmlspecialchars($name) . "</a>";
            echo "  </div>";
            echo "  <span style='font-size:14px;font-weight:700;color:{$color};white-space:nowrap;'>{$cnt} <span style='color:#94a3b8;font-weight:400;font-size:11px;'>({$pct}%)</span></span>";
            echo "</div>";
            $i++;
        }
    }
    ?>

    <!-- ══════════════════════════════════════════
         ROW 2 — CHARTS with integrated legends
    ══════════════════════════════════════════ -->
    <div class="dash-row2">

        <!-- Line: Monthly Trend -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span style="width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;"></span>
                Tren Publikasi (6 Bulan Terakhir)
            </div>
            <div style="height:180px;flex-shrink:0;">
                <canvas id="chartTrend"></canvas>
            </div>
            <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;column-gap:24px;row-gap:8px;flex-grow:1;align-content:start;">
                <?php 
                if (!empty($monthly_trend)) {
                    foreach (array_reverse($monthly_trend) as $m) {
                        // We reverse it so the most recent month shows first in the list
                        echo "<div style='display:flex;align-items:center;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f8fafc;'>";
                        echo "  <span style='font-size:12px;color:#64748b;'>{$m['label']}</span>";
                        echo "  <span style='font-size:14px;font-weight:700;color:#ef4444;'>{$m['count']}</span>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- Donut: By Jenis -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;background:#6366f1;border-radius:50%;display:inline-block;"></span>
                By Jenis
            </div>
            <div style="height:180px;display:flex;align-items:center;justify-content:center;">
                <canvas id="chartJenis"></canvas>
            </div>
            <div style="margin-top:12px;">
                <?php donutLegend($ds['by_jenis'] ?? [], 0, $palette, 'jenis'); ?>
            </div>
        </div>

        <!-- Donut: By Produk -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block;"></span>
                By Produk
            </div>
            <div style="height:180px;display:flex;align-items:center;justify-content:center;">
                <canvas id="chartProduk"></canvas>
            </div>
            <div style="margin-top:12px;">
                <?php donutLegend($ds['by_produk'] ?? [], 2, $palette, 'produk'); ?>
            </div>
        </div>

        <!-- Donut: By Kategori -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block;"></span>
                By Kategori
            </div>
            <div style="height:180px;display:flex;align-items:center;justify-content:center;">
                <canvas id="chartKategori"></canvas>
            </div>
            <div style="margin-top:12px;">
                <?php donutLegend($by_kategori, 4, $palette, 'kategori'); ?>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════
         ROW 3 — By Media + Recent Activity
    ══════════════════════════════════════════ -->
    <div class="dash-row3">

        <!-- Horizontal Bar: By Media -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;background:#3b82f6;border-radius:50%;display:inline-block;"></span>
                By Media Channel
                <a href="?controller=dashboard&action=library" style="margin-left:auto;font-size:11px;color:#3b82f6;text-decoration:none;font-weight:600;">Lihat Library →</a>
            </div>
            <div style="height:150px;">
                <canvas id="chartMedia"></canvas>
            </div>
            <div style="margin-top:12px;">
                <?php donutLegend($ds['by_media'] ?? [], 0, $palette, 'media'); ?>
            </div>
        </div>

        <!-- Recent Audit Activity -->
        <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block;"></span>
                Aktivitas Audit Terkini
                <a href="?controller=audit&action=index" style="margin-left:auto;font-size:11px;color:#f59e0b;text-decoration:none;font-weight:600;">Lihat Semua →</a>
            </div>
            <?php if (empty($recent_audit)): ?>
                <p style="color:#94a3b8;font-size:13px;text-align:center;padding:30px 0;">Belum ada aktivitas.</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($recent_audit as $audit): ?>
                <a href="?controller=audit&action=detail&id=<?php echo urlencode($audit['request_id']); ?>" style="text-decoration:none;">
                <div style="padding:10px 12px;background:#f9fafb;border-radius:8px;border:1px solid #f1f5f9;transition:background .15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f9fafb'">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <?php echo actionBadge($audit['action']); ?>
                        <span style="font-size:11px;color:#94a3b8;margin-left:auto;"><?php echo substr($audit['ts'] ?? '', 0, 16); ?></span>
                    </div>
                    <div style="font-size:12px;color:#374151;font-weight:600;"><?php echo htmlspecialchars($audit['script_number'] ?? '—'); ?></div>
                    <div style="font-size:11px;color:#94a3b8;"><?php echo htmlspecialchars($audit['user_id'] ?? ''); ?> (<?php echo htmlspecialchars($audit['user_role'] ?? ''); ?>)</div>
                </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ══════════════════════════════════════════
         ROW 4 — RECENTLY PUBLISHED SCRIPTS
    ══════════════════════════════════════════ -->
    <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);margin-bottom:24px;">
        <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <span style="width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block;"></span>
            Script Terbaru Diterbitkan (Active)
            <a href="?controller=dashboard&action=library" style="margin-left:auto;font-size:11px;color:#10b981;text-decoration:none;font-weight:600;">Lihat Library →</a>
        </div>
        <?php if (empty($recent_scripts)): ?>
            <p style="color:#94a3b8;font-size:13px;text-align:center;padding:20px 0;">Tidak ada script aktif.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Script Number</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Jenis</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Produk</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Media</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Published</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_scripts as $sc): ?>
                <tr style="border-bottom:1px solid #f1f5f9;transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding:10px 12px;font-weight:600;color:#1e293b;"><?php echo htmlspecialchars($sc['script_number'] ?? '—'); ?></td>
                    <td style="padding:10px 12px;color:#64748b;"><?php echo htmlspecialchars($sc['jenis'] ?? '—'); ?></td>
                    <td style="padding:10px 12px;color:#64748b;"><?php echo htmlspecialchars($sc['produk'] ?? '—'); ?></td>
                    <td style="padding:10px 12px;color:#64748b;"><?php echo htmlspecialchars($sc['media'] ?? '—'); ?></td>
                    <td style="padding:10px 12px;color:#94a3b8;font-size:12px;"><?php echo htmlspecialchars($sc['pub_date'] ?? '—'); ?></td>
                    <td style="padding:10px 12px;">
                        <a href="?controller=library&action=detail&id=<?php echo urlencode($sc['request_id']); ?>"
                           style="display:inline-flex;align-items:center;gap:4px;background:#10b981;color:white;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;">
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- end .main -->

<script>
// ── Colour Palette ──
const palette = ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'];

// ── Donut: Jenis ──
new Chart(document.getElementById('chartJenis'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $jenis_labels; ?>,
        datasets: [{ data: <?php echo $jenis_data; ?>, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0)*100)}%)` } } } }
});

// ── Donut: Produk ──
new Chart(document.getElementById('chartProduk'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $produk_labels; ?>,
        datasets: [{ data: <?php echo $produk_data; ?>, backgroundColor: palette.slice(2), borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0)*100)}%)` } } } }
});

// ── Donut: Kategori ──
<?php $kategori_labels = json_encode(array_keys($by_kategori)); $kategori_data = json_encode(array_values($by_kategori)); ?>
new Chart(document.getElementById('chartKategori'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $kategori_labels; ?>,
        datasets: [{ data: <?php echo $kategori_data; ?>, backgroundColor: palette.slice(4), borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0)*100)}%)` } } } }
});

// ── Line: Monthly Trend ──
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: <?php echo $trend_labels; ?>,
        datasets: [{
            label: 'Scripts Diterbitkan',
            data: <?php echo $trend_data; ?>,
            fill: true, backgroundColor: 'rgba(239,68,68,0.08)', borderColor: '#ef4444',
            borderWidth: 2.5, pointBackgroundColor: '#ef4444', pointRadius: 4, tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grace: '20%', ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// ── Horizontal Bar: Media ──
new Chart(document.getElementById('chartMedia'), {
    type: 'bar',
    data: {
        labels: <?php echo $media_labels; ?>,
        datasets: [{ label: 'Scripts', data: <?php echo $media_data; ?>, backgroundColor: palette, borderRadius: 6, borderSkipped: false }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { size: 12 } }, grid: { display: false } }
        }
    }
});
</script>

