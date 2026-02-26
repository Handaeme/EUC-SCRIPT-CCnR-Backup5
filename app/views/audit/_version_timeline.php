<?php 
/**
 * Version Timeline Component for Audit Trail
 * Displays "Single View" with Version Navigation
 */

// Check if we have versions array (new format)
if (isset($content['versions']) && !empty($content['versions'])):
    $totalVersions = count($content['versions']);
    $activeIndex = $totalVersions - 1; // Default to Latest
?>
    <!-- Version Navigation Bar -->
    <div style="margin-bottom:15px; background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <span style="font-size:12px; font-weight:600; color:#64748b;">Select Version:</span>
        <div style="display:flex; gap:5px; flex-wrap:wrap;">
            <?php foreach ($content['versions'] as $idx => $version): 
                $vNum = $version['version_number'] ?? ($idx + 1);
                $isLatest = ($idx === $totalVersions - 1);
                $isActive = ($idx === $activeIndex);
                
                // IMPROVED: Map workflow_stage to readable labels
                $workflowStage = $version['workflow_stage'] ?? 'UNKNOWN';
                $createdBy = $version['created_by'] ?? null;
                $versionLabel = 'v' . $vNum;
                
                // FORCE MAKER LABEL for first version or empty stage
                if ($idx === 0 || $workflowStage === 'SUBMIT' || empty($workflowStage) || $workflowStage === 'NULL' || $workflowStage === 'UNKNOWN') {
                    $versionLabel = 'MAKER';
                    // Fallback to Request Creator if version creator is missing (Legacy Data)
                    if (empty($createdBy) || $createdBy === 'NULL' || $createdBy === 'System') {
                         // FIX: Use parent scope variables directly (removed 'global')
                         if (isset($req['created_by'])) {
                             $createdBy = $req['created_by'];
                         } elseif (isset($data['request']['created_by'])) {
                             $createdBy = $data['request']['created_by'];
                         }
                    }
                } elseif ($workflowStage === 'APPROVED_SPV') {
                    $versionLabel = 'SPV';
                } elseif ($workflowStage === 'APPROVED_PIC') {
                    $versionLabel = 'PIC';
                } elseif ($workflowStage === 'APPROVED_PROCEDURE') {
                    $versionLabel = 'PROCEDURE';
                }
                
                // Add DEPT and FULL NAME to label
                $userDept = $version['user_dept'] ?? '';
                $userFullName = $version['user_full_name'] ?? '';
                
                if ($userDept && $userFullName) {
                    $versionLabel = htmlspecialchars($userDept) . ' (' . htmlspecialchars($userFullName) . ')';
                } elseif ($createdBy && $createdBy !== 'NULL' && $createdBy !== 'System') {
                    $versionLabel .= ' (' . htmlspecialchars($createdBy) . ')';
                }
                
                // Color Logic
                $btnClass = $isActive ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50';
                $border = $isActive ? 'border:1px solid #2563eb' : 'border:1px solid #cbd5e1';
                $bg = $isActive ? 'background:#2563eb; color:white' : 'background:white; color:#475569';
                if ($isLatest && !$isActive) {
                    $border = 'border:1px solid #2563eb';
                    $bg = 'background:#eff6ff; color:#1e40af';
                }
            ?>
            <button 
                onclick="switchVersion(<?= $idx ?>)"
                id="ver-btn-<?= $idx ?>"
                style="padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; <?= $border ?>; <?= $bg ?>;">
                <?= $versionLabel ?>
                <?php if($isLatest) echo '<span style="font-size:10px; opacity:0.8; margin-left:3px;">(Latest)</span>'; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Version Content Panes -->
    <div class="version-viewer" style="position:relative; min-height:400px;">
        <?php foreach ($content['versions'] as $idx => $version): 
            $isLatest = ($idx === $totalVersions - 1);
            $display = $isLatest ? 'block' : 'none';
            
            $workflowStage = $version['workflow_stage'] ?? 'UNKNOWN';
            $createdBy = $version['created_by'] ?? 'System';
            $formattedDate = $version['formatted_date'] ?? date('Y-m-d H:i:s');
        ?>
        
        <div id="ver-pane-<?= $idx ?>" class="version-pane" style="display:<?= $display ?>;">
            
            <!-- Metadata Header for this Version -->
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px 8px 0 0; padding:12px 15px; display:flex; justify-content:space-between; align-items:center; border-bottom:none;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <span style="font-size:14px; font-weight:700; color:#1e293b;">Version <?php echo $version['version_number'] ?? ($idx + 1); ?></span>
                    <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase;">
                        <?php 
                        $stageLabel = $workflowStage;
                        if (empty($stageLabel) || $stageLabel === 'UNKNOWN' || $stageLabel === 'NULL') $stageLabel = 'SUBMIT';
                        echo htmlspecialchars($stageLabel); 
                        ?>
                    </span>
                </div>
                <div style="text-align:right; font-size:11px; color:#64748b;">
                    <div><i class="bi-person-fill"></i> 
                        <?php 
                        $userDept = $version['user_dept'] ?? '';
                        $userFullName = $version['user_full_name'] ?? '';
                        echo htmlspecialchars($userDept . ($userFullName ? " ($userFullName)" : " ($createdBy)")); 
                        ?>
                    </div>
                    <div><i class="bi-clock"></i> <?php echo htmlspecialchars($formattedDate); ?></div>
                </div>
            </div>

            <!-- Content Area -->
            <div style="background:white; border:1px solid #e2e8f0; border-radius:0 0 8px 8px; overflow:hidden;">
               <div class="version-content-wrapper" style="padding:20px; overflow-x:auto;">
                   <!-- Wrapper for Excel/Table Content -->
                   <div class="audit-content-display" style="font-family:'Inter', system-ui, -apple-system, sans-serif; font-size:13px;">
                       <?php 
                       $vContent = $version['content'];
                       // FIX: Deduplicate sheet panes if pre-built tabs are present
                       if (strpos($vContent, 'sheet-tabs-nav') !== false) {
                           $vDom = new \DOMDocument();
                           libxml_use_internal_errors(true);
                           $vDom->loadHTML('<?xml encoding="utf-8" ?><div id="__vwrap__">' . $vContent . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                           libxml_clear_errors();
                           $vXpath = new \DOMXPath($vDom);
                           
                           // Remove duplicate nav containers (keep only first)
                           $vNavs = $vXpath->query("//*[contains(@class, 'sheet-tabs-nav')]");
                           if ($vNavs->length > 0) {
                               $firstNav = $vNavs->item(0);
                               for ($ni = $vNavs->length - 1; $ni > 0; $ni--) {
                                   $dupNav = $vNavs->item($ni);
                                   $dupNav->parentNode->removeChild($dupNav);
                               }
                               
                               // Deduplicate buttons within first nav
                               $vBtns = $vXpath->query(".//*[contains(@class, 'btn-sheet')]", $firstNav);
                               $vSeen = []; $vKeepIds = []; $vRemBtns = [];
                               foreach ($vBtns as $vBtn) {
                                   $vName = trim($vBtn->textContent);
                                   preg_match("/['\"]([^'\"]+)['\"]/", $vBtn->getAttribute('onclick'), $vM);
                                   $vTid = $vM[1] ?? '';
                                   if (in_array($vName, $vSeen)) {
                                       $vRemBtns[] = $vBtn;
                                       if ($vTid) { $vPane = $vDom->getElementById($vTid); if ($vPane) $vPane->parentNode->removeChild($vPane); }
                                   } else {
                                       $vSeen[] = $vName;
                                       if ($vTid) $vKeepIds[] = $vTid;
                                   }
                               }
                               foreach ($vRemBtns as $vRb) { $vRb->parentNode->removeChild($vRb); }
                               
                               // Remove orphaned panes
                               $vAllPanes = $vXpath->query("//*[contains(@class, 'sheet-pane') or contains(@class, 'media-pane')]");
                               foreach ($vAllPanes as $vAp) {
                                   $vApId = $vAp->getAttribute('id');
                                   if ($vApId && !in_array($vApId, $vKeepIds)) { $vAp->parentNode->removeChild($vAp); }
                               }
                           }
                           
                           $vClean = $vDom->saveHTML();
                           $vClean = str_replace('<?xml encoding="utf-8" ?>', '', $vClean);
                           $vClean = preg_replace('/^<div id="__vwrap__">(.*)<\/div>$/s', '$1', trim($vClean));
                           echo $vClean;
                       } else {
                           echo $vContent;
                       }
                       ?>
                   </div>
               </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- CSS for Excel Table Styling -->
    <style>
        .audit-content-display .excel-preview {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .audit-content-display .excel-preview td,
        .audit-content-display .excel-preview th {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            min-width: 50px;
            text-align: left;
            vertical-align: top;
        }
        .audit-content-display .excel-preview thead th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        .audit-content-display .excel-preview tbody tr:hover {
            background: #f9fafb;
        }
        
        /* Sheet Tabs Styling */
        .audit-content-display .sheet-tabs-nav {
            background: #f8fafc;
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 6px;
            overflow-x: auto;
            margin-bottom: 15px;
        }
        .audit-content-display .btn-sheet {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
            cursor: pointer;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .audit-content-display .btn-sheet:hover {
            background: #f1f5f9;
            color: #334155;
        }
        .audit-content-display .btn-sheet.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
    </style>


    <!-- Review Notes Sidebar Container (Hidden initially, moved by JS) -->
    <div id="audit-review-sidebar" class="card" style="display:none; margin-bottom:24px; box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee; overflow:hidden;">
        <h4 style="margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">Review Notes</h4>
        <div id="audit-comment-list" style="max-height:400px; overflow-y:auto; padding-right:5px;"></div>
    </div>

    <!-- Initialize -->
    <script>
    // Initial Render
    document.addEventListener('DOMContentLoaded', function() {
        switchVersion(<?= $activeIndex ?>);
    });

    function switchVersion(idx) {
        // 1. Toggle Panes
        document.querySelectorAll('.version-pane').forEach(el => el.style.display = 'none');
        document.getElementById('ver-pane-' + idx).style.display = 'block';
        
        // 2. Toggle Buttons
        document.querySelectorAll('[id^="ver-btn-"]').forEach(btn => {
            btn.style.background = 'white';
            btn.style.color = '#475569';
            btn.style.borderColor = '#cbd5e1';
            btn.classList.remove('shadow-md');
            // Keeping Latest border blueish if not active
            if (btn.innerText.includes('(Latest)') && btn.id !== 'ver-btn-' + idx) {
                btn.style.background = '#eff6ff';
                btn.style.color = '#1e40af';
                btn.style.borderColor = '#2563eb';
            }
        });
        
        const activeBtn = document.getElementById('ver-btn-' + idx);
        if(activeBtn) {
            activeBtn.style.background = '#2563eb';
            activeBtn.style.color = 'white';
            activeBtn.style.borderColor = '#2563eb';
            activeBtn.classList.add('shadow-md');
        }
        
        // 3. Render Review Notes for this Version
        if (typeof renderAuditNotes === 'function') {
            renderAuditNotes(idx);
        }
    }

    function renderAuditNotes(versionIdx) {
        const pane = document.getElementById('ver-pane-' + versionIdx);
        const list = document.getElementById('audit-comment-list');
        const sidebar = document.getElementById('audit-review-sidebar');
        
        if (!pane || !list) return;
        
        list.innerHTML = ''; // Clear previous notes
        
        // RELOCATE SIDEBAR to panel-column, ABOVE the Timeline card
        const timelineHeader = Array.from(document.querySelectorAll('h4')).find(h => h.textContent.trim() === 'Timeline');
        const timelineCard = timelineHeader ? timelineHeader.closest('.card') : null;
        
        if (timelineCard && sidebar.parentElement !== timelineCard.parentElement) {
            timelineCard.parentElement.insertBefore(sidebar, timelineCard);
        }

        // ============================================================
        // PHASE 1: Collect ALL entries from ALL tabs in this version
        // ============================================================
        const allEntries = [];
        const processedIds = new Set();
        const processedElements = new Set();
        
        // Helper: Get tab name from sheet-pane
        function getTabNameLocal(sheetPane) {
            if (!sheetPane) return 'General';
            const sheetId = sheetPane.id || '';
            
            // Find corresponding button
            let btn = pane.querySelector(`button[onclick*="'${sheetId}'"]`);
            if (!btn) btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
            
            if (btn) {
                const clone = btn.cloneNode(true);
                clone.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
                return clone.textContent.trim() || sheetId;
            }
            return sheetId || 'General';
        }
        
        // Helper: Escape HTML
        function escHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
        
        // A) Inline Comments (span[data-comment-id])
        pane.querySelectorAll('span[data-comment-id]').forEach(span => {
            if (processedElements.has(span)) return;
            
            const id = span.getAttribute('data-comment-id');
            if (!id || processedIds.has(id)) return;
            processedIds.add(id);
            
            processedElements.add(span);
            span.querySelectorAll('*').forEach(child => processedElements.add(child));
            
            const parentSheet = span.closest('.sheet-pane');
            const tabName = getTabNameLocal(parentSheet);
            
            const text = span.getAttribute('data-comment-text') || span.textContent.substring(0, 80);
            if (!text || text.trim() === '') return;
            
            allEntries.push({
                id: id,
                type: 'comment',
                text: text,
                user: span.getAttribute('data-comment-user') || 'Reviewer',
                dept: span.getAttribute('data-comment-dept') || '',
                job: span.getAttribute('data-comment-job') || '',
                time: span.getAttribute('data-comment-time') || '',
                timestamp: parseInt(id.replace('c', '').replace('rev-', '')) || 0,
                element: span,
                tabName: tabName
            });
        });
        
        // B) Revision Spans (auto-red / deleted / changed)
        // Broad selectors to catch all formats
        const revSelector = '.revision-span, .inline-comment, span[style*="text-decoration: line-through"], span[style*="text-decoration:line-through"], span[style*="color: red"], span[style*="color:red"], span[style*="color:#ef4444"], span[style*="color:#dc2626"], s, strike, del';
        let revIdx = 0;
        
        pane.querySelectorAll(revSelector).forEach(span => {
            if (processedElements.has(span)) return;
            
            // Skip if already captured as an inline comment
            if (span.closest('[data-comment-id]')) return;
            
            // Skip if it has data-comment-id and was already processed
            const cId = span.getAttribute('data-comment-id');
            if (cId && processedIds.has(cId)) return;
            
            const style = span.style ? span.style.cssText || '' : '';
            const tagName = span.tagName.toLowerCase();
            
            const isStrikethrough = style.includes('line-through') || ['s', 'strike', 'del'].includes(tagName);
            const isRedText = style.includes('red') || style.includes('#ef4444') || style.includes('#dc2626') || span.classList.contains('revision-span');
            
            if (!isStrikethrough && !isRedText) return;
            
            const origText = span.textContent.trim();
            if (!origText || origText.length < 1) return;
            
            // Skip hidden elements
            if (span.style && span.style.display === 'none') return;
            
            processedElements.add(span);
            span.querySelectorAll('*').forEach(child => processedElements.add(child));
            
            const parentSheet = span.closest('.sheet-pane');
            const tabName = getTabNameLocal(parentSheet);
            
            const entryType = isStrikethrough ? 'deleted' : 'changed';
            const uniqueKey = `rev_${tabName}_${revIdx++}`;
            processedIds.add(uniqueKey);
            
            allEntries.push({
                id: uniqueKey,
                type: entryType,
                text: origText.length > 60 ? origText.substring(0, 60) + '...' : origText,
                user: span.getAttribute('data-comment-user') || 'Reviewer',
                dept: '',
                job: '',
                time: span.getAttribute('data-comment-time') || '',
                timestamp: Date.now() - (1000 * revIdx),
                element: span,
                tabName: tabName
            });
        });

        // ============================================================
        // PHASE 2: Group by Tab and Render
        // ============================================================
        if (allEntries.length === 0) {
            sidebar.style.display = 'none';
            return;
        }
        
        sidebar.style.display = 'block';
        
        const grouped = {};
        allEntries.forEach(entry => {
            const key = entry.tabName || 'General';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(entry);
        });
        
        Object.values(grouped).forEach(group => {
            group.sort((a, b) => b.timestamp - a.timestamp);
        });
        
        const tabNames = Object.keys(grouped);
        const showTabHeaders = tabNames.length > 1 || (tabNames.length === 1 && tabNames[0] !== 'General');
        
        tabNames.forEach(tabKey => {
            const entries = grouped[tabKey];
            
            // Tab Header
            if (showTabHeaders) {
                const header = document.createElement('div');
                header.style.cssText = 'font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; padding:8px 0 4px 0; margin-top:8px; border-bottom:1px solid #f1f5f9; margin-bottom:8px;';
                header.textContent = '📄 ' + tabKey;
                list.appendChild(header);
            }
            
            entries.forEach(c => {
                // Badge
                let badgeHtml = '';
                let avatarBg, avatarColor, avatarBorder, textStyle;
                
                if (c.type === 'deleted') {
                    badgeHtml = '<span style="font-size:9px; font-weight:700; background:#fef2f2; color:#dc2626; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Deleted</span>';
                    avatarBg = '#fef2f2'; avatarColor = '#dc2626'; avatarBorder = '#fecaca';
                    textStyle = 'text-decoration:line-through; color:#991b1b;';
                } else if (c.type === 'changed') {
                    badgeHtml = '<span style="font-size:9px; font-weight:700; background:#fffbeb; color:#d97706; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Changed</span>';
                    avatarBg = '#fffbeb'; avatarColor = '#d97706'; avatarBorder = '#fde68a';
                    textStyle = 'color:#92400e;';
                } else {
                    badgeHtml = '<span style="font-size:9px; font-weight:700; background:#eff6ff; color:#3b82f6; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Comment</span>';
                    avatarBg = '#eff6ff'; avatarColor = '#3b82f6'; avatarBorder = '#dbeafe';
                    textStyle = 'color:#334155;';
                }
                
                const card = document.createElement('div');
                card.style.cssText = 'background:white; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:10px; cursor:pointer; transition:all 0.15s;';
                
                card.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:26px; height:26px; background:${avatarBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; color:${avatarColor}; font-weight:bold; border:1px solid ${avatarBorder};">
                                ${c.user.charAt(0).toUpperCase()}
                            </div>
                            <span style="font-size:12px; font-weight:600; color:#1e293b;">${escHtml(c.user)}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            ${badgeHtml}
                            ${c.time ? '<span style="font-size:10px; color:#94a3b8;">' + escHtml(c.time) + '</span>' : ''}
                        </div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:6px; padding:8px 10px; font-size:12px; line-height:1.5; ${textStyle}">
                        ${escHtml(c.text)}
                    </div>
                `;
                
                // Hover Effect
                card.addEventListener('mouseenter', () => {
                    card.style.borderColor = avatarColor;
                    card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
                    c.element.style.outline = '2px solid ' + avatarColor;
                    c.element.style.background = avatarBg;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.borderColor = '#e2e8f0';
                    card.style.boxShadow = 'none';
                    c.element.style.outline = '';
                    c.element.style.background = '';
                });
                
                // Click to Navigate & Highlight
                card.addEventListener('click', () => {
                    const parentSheet = c.element.closest('.sheet-pane');
                    if (parentSheet && parentSheet.style.display === 'none') {
                        const sheetId = parentSheet.id;
                        // Try multiple button lookups
                        let tabBtn = pane.querySelector(`button[onclick*="'${sheetId}'"]`);
                        if (!tabBtn) tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
                        if (!tabBtn) tabBtn = document.getElementById('btn-' + sheetId);
                        if (tabBtn) tabBtn.click();
                        
                        setTimeout(() => {
                            c.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            c.element.style.transition = 'background 0.5s';
                            c.element.style.background = '#fde68a';
                            setTimeout(() => { c.element.style.background = ''; }, 1500);
                        }, 150);
                        return;
                    }
                    
                    c.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    c.element.style.transition = 'background 0.5s';
                    c.element.style.background = '#fde68a';
                    setTimeout(() => { c.element.style.background = ''; }, 1500);
                });
                
                list.appendChild(card);
            });
        });
    }
    </script>

<?php 
// Fallback to single preview if versions array doesn't exist (backward compatibility)
elseif (isset($content['html_preview']) && !empty($content['html_preview'])): 
?>
     <div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; max-width:100%; overflow:hidden;">
        <div style="background:#f9fafb; padding:10px 15px; border-bottom:1px solid #eee; font-weight:600; color:#374151; font-size:13px; display:flex; justify-content:space-between; border-radius: 8px 8px 0 0;">
            <span>Stored Preview</span>
            <span style="font-size:11px; color:#6b7280; font-weight:normal;">Single version mode</span>
        </div>
        
        <div class="split-container" style="width:100%; max-width:100%; overflow:hidden;">
            <div id="audit-editor-container" style="width:100%; max-width:100%; padding:20px; min-height:400px; overflow-x:auto; font-family:'Inter', sans-serif; border-radius: 0 0 8px 8px; box-sizing:border-box;">
                <?php echo $content['html_preview']; ?>
            </div>
        </div>
    </div>

<?php 
// Final fallback to raw file parsing
elseif (isset($content['path']) && file_exists($content['path'])):
    if (!class_exists('App\\Helpers\\FileHandler')) {
        require_once 'app/helpers/FileHandler.php';
    }
    
    $ext = pathinfo($content['filename'], PATHINFO_EXTENSION);
    echo '<div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; max-width:100%; overflow:hidden;">';
    echo '<div style="background:#f9fafb; padding:10px 15px; border-bottom:1px solid #eee; font-weight:600; color:#374151; font-size:13px; border-radius: 8px 8px 0 0;">File Preview (Raw)</div>';
    echo '<div style="padding:0; overflow-x:auto; width:100%; max-width:100%; display:block; font-family:\'Inter\', system-ui, -apple-system, sans-serif; border-radius: 0 0 8px 8px; box-sizing:border-box;">'; 
    $parsed = App\Helpers\FileHandler::parseFile($content['path'], $ext);
    echo is_array($parsed) ? $parsed['preview_html'] : $parsed;
    echo '</div></div>';
else:
    echo '<div style="color:#9ca3af; font-style:italic; text-align:center; padding:40px;">(No Preview Available)</div>';
endif;
?>
