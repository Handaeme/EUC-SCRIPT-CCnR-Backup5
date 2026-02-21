<?php foreach ($logs as $row):
    // Determine Dynamic Reviewer & Timestamp & Status (Logic preserved for status color)
    $displayStatus = 'SUBMITTED';
    $statusColor = '#666';
    $timestamp = $row['created_date']; // Default
    
    // Logic Hierarchy
    if ($row['status'] === 'DRAFT') {
        $displayStatus = 'DRAFT';
        $statusColor = '#64748b'; // Grey for draft
        $timestamp = $row['created_date'];
    } elseif ($row['status_procedure'] === 'APPROVE_PROCEDURE') {
        // Check if this was a DIRECT publish (Library revision with no SPV/PIC review)
        if (empty($row['status_spv']) && empty($row['status_pic'])) {
            $displayStatus = 'DIRECT';
            $statusColor = '#7c3aed'; // Purple for direct publish
        } else {
            $displayStatus = 'LIBRARY';
            $statusColor = '#16a34a'; // Green for normal flow
        }
        $timestamp = $row['timestamp_procedure'];
    } elseif ($row['status_procedure'] === 'REVISION' || $row['status_procedure'] === 'REJECTED') {
        $displayStatus = 'PROCEDURE ' . $row['status_procedure'];
        $statusColor = '#dc2626';
        $timestamp = $row['timestamp_procedure'];
    } elseif ($row['status_pic'] === 'APPROVE_PIC') {
        // Granular Status for Procedure Phase
        if (($row['raw_status'] ?? $row['status']) === 'PENDING_MAKER_CONFIRMATION') {
            $displayStatus = 'WAITING MAKER CONFIR.';
            $statusColor = '#8b5cf6'; // Purple-ish
        } else {
            $hasLegal = $row['has_legal'] ?? 0;
            $hasCX = $row['has_cx'] ?? 0;
            $hasSyariah = $row['has_syariah'] ?? 0;
            $hasLPP = $row['has_lpp'] ?? 0;

            $docs = [];
            if ($hasLegal > 0) $docs[] = 'LEGAL';
            if ($hasCX > 0) $docs[] = 'CX';
            if ($hasSyariah > 0) $docs[] = 'SYARIAH';
            if ($hasLPP > 0) $docs[] = 'LPP';

            if (count($docs) > 0) {
                // If 4 docs, use abbreviation so it fits
                if (count($docs) === 4) {
                    $displayStatus = 'ALL DOCS UPLOADED';
                } else {
                    $displayStatus = implode(', ', $docs) . ' UPLOADED';
                }
                $statusColor = '#2563eb'; // Blue
            } else {
                $displayStatus = 'WAITING PROCEDURE';
                $statusColor = '#eab308'; // Yellow
            }
        }
        $timestamp = $row['timestamp_pic'];
    } elseif ($row['status_pic'] === 'REVISION' || $row['status_pic'] === 'REJECTED') {
        $displayStatus = 'PIC ' . $row['status_pic'];
        $statusColor = '#dc2626';
        $timestamp = $row['timestamp_pic'];
    } elseif ($row['status_spv'] === 'APPROVE_SPV') {
        $displayStatus = 'WAITING PIC';
        $statusColor = '#eab308';
        $timestamp = $row['timestamp_spv'];
    } elseif ($row['status_spv'] === 'REVISION' || $row['status_spv'] === 'REJECTED') {
        $displayStatus = 'SPV ' . $row['status_spv'];
        $statusColor = '#dc2626';
        $timestamp = $row['timestamp_spv'];
    } else {
        $displayStatus = 'SUBMITTED';
        $statusColor = '#9ca3af';
        // FIX: Use updated_at (if available) to reflect Maker Revisions, fallback to created_at
        $timestamp = !empty($row['updated_at']) ? $row['updated_at'] : $row['created_date'];
    }

    // Content Snippet
    $snippet = '';
    if (($row['mode'] ?? '') === 'FILE_UPLOAD') {
        $filename = $row['script_content'] ?? '';
        if (empty($filename)) {
                $displayFilename = '<span style="color:#9ca3af; font-style:italic;">(No File)</span>';
                $fullFilename = '';
        } else {
            $fullFilename = $filename;
            if (strlen($filename) > 30) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                if (strlen($name) > 25) {
                    $name = substr($name, 0, 25) . '... ';
                }
                $displayFilename = $name . ($ext ? '.' . $ext : '');
            } else {
                $displayFilename = $filename;
            }
        }
        $snippet = '<div style="display:flex; align-items:center; gap:5px; color:#4b5563;" title="' . htmlspecialchars($fullFilename) . '">
                        <i class="fs-4 bi-file-earmark-text"></i> 
                        <span style="font-weight:600;">' . $displayFilename . '</span>
                    </div>';
    } else {
        // Free Input - Truncate
        $rawContent = strip_tags($row['script_content'] ?? '');
        // Fix Spacing: Replace newlines with spaces for table preview
        $rawContent = str_replace(["\r\n", "\r", "\n"], " ", $rawContent);
        $rawContent = preg_replace('/\s+/', ' ', $rawContent); // Clean excess spaces
        $rawContent = trim($rawContent);
        
        if (strlen($rawContent) === 0) {
            $snippet = '<div style="color:#d1d5db; font-style:italic; font-size:11px;">(No Content Preview)</div>';
        } else {
            // Add Icon for consistency with File Upload rows
            $snippet = '<div style="display:flex; align-items:center; gap:5px; color:#4b5563;" title="' . htmlspecialchars($rawContent) . '">
                            <i class="fs-4 bi-file-text" style="font-size:14px;"></i>
                            <div style="font-family:\'Inter\', system-ui, -apple-system, sans-serif; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;">' . htmlspecialchars($rawContent) . '</div>
                        </div>';
        }
    }
    // Format Ticket ID (SC-XXXX)
    $ticketDisplay = $row['ticket_id'];
    if (is_numeric($ticketDisplay)) {
        $ticketDisplay = sprintf("SC-%04d", $ticketDisplay);
    }
?>
<tr style="border-bottom:1px solid #eee; hover:background-color:#f9f9f9;">
    
    <!-- Action Button -->
    <td style="padding:10px;">
        <div style="display:flex; gap:5px; align-items:center;">
            <a href="?controller=audit&action=detail&id=<?php echo $row['id']; ?>" style="display:inline-block; padding:6px 12px; background:#fff; border:1px solid #ef4444; border-radius:6px; color:#ef4444; font-weight:600; text-decoration:none; font-size:11px; transition:all 0.2s; box-shadow:0 1px 2px rgba(239, 68, 68, 0.05);" onmouseover="this.style.background='#ef4444'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='#ef4444';">View</a>
            
            <?php 
            $u = strtolower($_SESSION['user']['userid'] ?? '');
            $r = $_SESSION['user']['dept'] ?? '';
            if ($r === 'ADMIN' || in_array($u, ['admin', 'admin_script'])): 
            ?>
            <a href="?controller=audit&action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus script ini? Script akan disembunyikan dari list (Soft Delete).');" style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:#fee2e2; border:1px solid #f87171; border-radius:6px; color:#b91c1c; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05);" title="Delete Script">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </a>
            <?php endif; ?>
        </div>
    </td>

    <!-- Maker -->
    <td style="padding:10px;">
        <div style="font-weight:600; color:#374151;"><?php echo htmlspecialchars($row['maker']); ?></div>
    </td>

    <!-- Ticket ID -->
    <td style="padding:10px;">
        <a href="?controller=audit&action=detail&id=<?php echo $row['id']; ?>" style="text-decoration:none; display:inline-block; transition:all 0.2s;" onmouseover="this.style.opacity='0.8'; this.style.transform='translateY(-1px)';" onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';" title="View Detail">
            <div style="font-weight:bold; color:var(--primary-red); font-size:13px;"><?php echo htmlspecialchars($ticketDisplay); ?></div>
        </a>
    </td>

    <!-- Script No -->
    <td style="padding:10px;">
        <div style="font-size:11px; color:#1e293b;"><?php echo htmlspecialchars($row['script_number']); ?></div>
    </td>

    <!-- Produk -->
    <td style="padding:10px;">
        <div style="font-size:12px; color:#374151;"><?php echo htmlspecialchars(!empty($row['produk']) ? $row['produk'] : '-'); ?></div>
    </td>

    <!-- Media -->
    <td style="padding:10px;">
        <div style="font-size:12px; color:#374151;"><?php echo htmlspecialchars(!empty($row['media']) ? $row['media'] : '-'); ?></div>
    </td>

    <!-- Kategori -->
    <td style="padding:10px; white-space:nowrap;">
        <div style="font-size:12px; color:#374151;"><?php echo htmlspecialchars(!empty($row['kategori']) ? $row['kategori'] : '-'); ?></div>
    </td>

    <!-- Jenis -->
    <td style="padding:10px; white-space:nowrap;">
        <div style="font-size:12px; color:#374151;"><?php echo htmlspecialchars(!empty($row['jenis']) ? $row['jenis'] : '-'); ?></div>
    </td>

    <!-- Script Content -->
    <td style="padding:10px; max-width:200px;">
        <?php echo $snippet; ?>
    </td>
    
    <!-- Status -->
    <td style="padding:10px;">
        <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-size:10px; font-weight:bold; background-color:<?php echo $statusColor; ?>20; color:<?php echo $statusColor; ?>; border:1px solid <?php echo $statusColor; ?>40;">
            <?php echo $displayStatus; ?>
        </span>
    </td>

    <!-- Last Updated (Full DateTime) -->
    <td style="padding:10px; text-align:right;" data-sort-val="<?php echo ($timestamp instanceof DateTime) ? $timestamp->getTimestamp() : strtotime($timestamp); ?>">
        <div style="font-weight:700; color:#374151; font-size:11px;"><?php echo ($timestamp instanceof DateTime) ? $timestamp->format('d M Y, H:i') : ($timestamp ? date('d M Y, H:i', strtotime($timestamp)) : '-'); ?></div>
    </td>
</tr>
<?php endforeach; ?>
