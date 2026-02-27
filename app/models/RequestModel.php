<?php
namespace App\Models;

class RequestModel {
    private $conn;

    public function __construct() {
        // Fix Path: Go up 2 levels from app/models -> root, then into config
        $configFile = __DIR__ . '/../../config/database.php';
        
        if (!file_exists($configFile)) {
            die("Database config not found at: " . $configFile);
        }
        $config = require $configFile;
        $this->conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        if (!$this->conn) {
            die(print_r(db_errors(), true));
        }
    }
    public function getUsersByRole($role) {
        // Query both columns because setup.php might have swapped them
        // Schema Adapt: User table uses 'username', not 'userid'. No 'fullname' column mentioned in error.
        $sql = "SELECT USERID as userid, FULLNAME as fullname FROM tbluser WHERE JOB_FUNCTION = ? OR [GROUP] = ?";
        $stmt = db_query($this->conn, $sql, [$role, $role]);
        $users = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // NEW: Specific fetch for Division Head (SPV) using Simplified Role
    public function getSupervisors() {
        // Schema Adapt: Use 'username'
        // FIX: Case-insensitive check
        // [UPDATED] Use JOB_FUNCTION = 'DIVISION HEAD' instead of DEPT
        $sql = "SELECT USERID as userid, FULLNAME as fullname FROM tbluser WHERE JOB_FUNCTION = 'DIVISION HEAD'";
        $stmt = db_query($this->conn, $sql);
        $users = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // NEW: Fetch PICs (Person in Charge) for SPV selection
    public function getPICs() {
        // FIX: Case-insensitive check and TRIM spaces (e.g., 'PIC ')
        $sql = "SELECT USERID as userid, FULLNAME as fullname FROM tbluser WHERE TRIM(UPPER(DEPT)) = 'PIC' OR UPPER(DEPT) LIKE '%PIC%' ORDER BY FULLNAME ASC";
        $stmt = db_query($this->conn, $sql);
        $users = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // NEW: Fetch Pending Requests for a Specific User/Role

    public function getPendingRequests($userid, $role, $startDate = null, $endDate = null, $filters = []) {
        $sql = "";
        $params = [];
        $where = "";

        // Define base conditions based on role
        if ($role === 'SPV') {
            // ADMIN OVERRIDE: If user is admin/admin_script, see ALL pending SPV requests
            if (in_array(strtolower($userid), ['admin', 'admin_script'])) {
                 $where = "WHERE status = 'CREATED'";
            } else {
                 $where = "WHERE selected_spv = ? AND status = 'CREATED'";
                 $params[] = $userid;
            }
         } elseif ($role === 'PIC') {
            // FIX: Support selected_pic with backward compatibility
            // New requests: Check selected_pic
            // Old requests (selected_pic IS NULL): Show to all PICs
            $where = "WHERE status = 'APPROVED_SPV' AND (selected_pic = ? OR selected_pic IS NULL)";
            $params[] = $userid;
        } elseif ($role === 'PROCEDURE') {
            // FIX: Visibility for DRAFT_TEMP (Ghost Tickets) so Procedure users can see their own active revisions
            $where = "WHERE (status = 'APPROVED_PIC' OR (status = 'DRAFT_TEMP' AND created_by = ?))";
            $params[] = $userid;
        } elseif ($role === 'MAKER') {
            $where = "WHERE created_by = ? AND status IN ('REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION', 'DRAFT', 'PENDING_MAKER_CONFIRMATION')";
            $params[] = $userid;
        }

        // Apply filters and order if base condition exists
        if ($where) {
            // EXCLUDE DELETED (Soft Delete Check)
            $where .= " AND is_deleted = 0";

            // Date Filter
            if ($startDate && $endDate) {
                // Determine which date column to use based on role
                $col = ($role === 'MAKER' || $role === 'SPV') ? 'created_at' : 'updated_at';
                
                $where .= " AND CAST($col AS DATE) >= ? AND CAST($col AS DATE) <= ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            // Advanced Filters (Multi-Select)
            $filterableColumns = ['jenis', 'produk', 'kategori'];
            foreach ($filterableColumns as $col) {
                if (!empty($filters[$col]) && is_array($filters[$col])) {
                    if (in_array($col, ['produk', 'kategori'])) {
                        $colClauses = [];
                        foreach ($filters[$col] as $val) {
                            $colClauses[] = "$col LIKE ?";
                            $params[] = '%' . $val . '%';
                        }
                        if (!empty($colClauses)) {
                            $where .= " AND (" . implode(' OR ', $colClauses) . ")";
                        }
                    } else {
                        $placeholders = implode(',', array_fill(0, count($filters[$col]), '?'));
                        $where .= " AND $col IN ($placeholders)";
                        $params = array_merge($params, $filters[$col]);
                    }
                }
            }

            // Media Filter (Special handling for LIKE due to comma-separated values)
            if (!empty($filters['media']) && is_array($filters['media'])) {
                $mediaClauses = [];
                foreach ($filters['media'] as $media) {
                    $mediaClauses[] = "media LIKE ?";
                    $params[] = '%' . $media . '%';
                }
                if (!empty($mediaClauses)) {
                    $where .= " AND (" . implode(' OR ', $mediaClauses) . ")";
                }
            }

            // Order By (Preserve existing logic)
            $orderBy = "ORDER BY updated_at DESC";
            if ($role === 'SPV') {
                $orderBy = "ORDER BY created_at DESC";
            }

            $sql = "SELECT * FROM script_request $where $orderBy";
            
            $stmt = db_query($this->conn, $sql, $params);
            $requests = [];
            if ($stmt) {
                while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                    $requests[] = $row;
                }
            }
            return $requests;
        }
        return [];
    }

    public function getMakerStats($userId) {
        $sql = "SELECT 
                    SUM(CASE WHEN status IN ('REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION', 'DRAFT') THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status IN ('CREATED', 'APPROVED_SPV', 'APPROVED_PIC', 'APPROVED_PROCEDURE') THEN 1 ELSE 0 END) as wip,
                    SUM(CASE WHEN status IN ('CLOSED', 'LIBRARY') THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'PENDING_MAKER_CONFIRMATION' THEN 1 ELSE 0 END) as confirmation
                FROM script_request 
                WHERE created_by = ?";
        
        $stmt = db_query($this->conn, $sql, [$userId]);
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            return $row;
        }
        return ['pending' => 0, 'wip' => 0, 'completed' => 0, 'confirmation' => 0];
    }

    public function getRequestById($id) {
        $sql = "SELECT r.*, u.[GROUP] as group_name, u.USERID as maker_userid, u.FULLNAME as maker_name,
                       spv.FULLNAME as selected_spv_name,
                       pic.FULLNAME as selected_pic_name 
                FROM script_request r
                OUTER APPLY (SELECT TOP 1 u2.[GROUP], u2.USERID, u2.FULLNAME FROM tbluser u2 WHERE u2.USERID = r.created_by) u
                OUTER APPLY (SELECT TOP 1 s2.FULLNAME FROM tbluser s2 WHERE s2.USERID = r.selected_spv) spv
                OUTER APPLY (SELECT TOP 1 p2.FULLNAME FROM tbluser p2 WHERE p2.USERID = r.selected_pic) pic
                WHERE r.id = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row;
        }
        return null;
    }

    public function getPreviewContent($requestId) {
        // 1. Check Draft / Active Review Content
        // VERSIONING FIX: Get only LATEST version per media for review mode
        $sql = "WITH LatestVersions AS (
                    SELECT *,
                           ROW_NUMBER() OVER (PARTITION BY media ORDER BY created_at DESC, id DESC) as rn
                    FROM script_preview_content
                    WHERE request_id = ?
                )
                SELECT id, request_id, media, content, workflow_stage, created_by, created_at
                FROM LatestVersions
                WHERE rn = 1
                ORDER BY id ASC";
        
        $stmt = db_query($this->conn, $sql, [$requestId]);
        $rows = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        
        // 2. If empty, Check Library (Approved/Published Scripts)
        if (empty($rows)) {
            $sqlLib = "SELECT * FROM script_library WHERE request_id = ? ORDER BY id ASC";
            $stmtLib = db_query($this->conn, $sqlLib, [$requestId]);
            if ($stmtLib) {
                while ($row = db_fetch_array($stmtLib, DB_FETCH_ASSOC)) {
                    $rows[] = $row;
                }
            }
        }
        
        // 3. If still empty, Check Legacy Request Columns
        if (empty($rows)) {
            $sqlReq = "SELECT content, script_content, media FROM script_request WHERE id = ?";
            $stmtReq = db_query($this->conn, $sqlReq, [$requestId]);
            if ($stmtReq && ($req = db_fetch_array($stmtReq, DB_FETCH_ASSOC))) {
                
                if (!empty($req['content'])) {
                    $rows[] = ['media' => $req['media'] ?? 'Content', 'content' => $req['content']];
                } elseif (!empty($req['script_content'])) {
                    $rows[] = ['media' => $req['media'] ?? 'Content', 'content' => $req['script_content']];
                }
            }
        }
        
        return $rows;
    }

    /**
     * Get Content strictly from Script Library
     * Used for Library Detail View to ensure we show the Final/Cleaned version
     */
    public function getLibraryContentOnly($requestId) {
        $rows = [];
        $sqlLib = "SELECT * FROM script_library WHERE request_id = ? ORDER BY id ASC";
        $stmtLib = db_query($this->conn, $sqlLib, [$requestId]);
        if ($stmtLib) {
            while ($row = db_fetch_array($stmtLib, DB_FETCH_ASSOC)) {
                // [FIX] Clean review marks at display time (handles old data with dirty HTML)
                if (!empty($row['content'])) {
                    $row['content'] = $this->cleanReviewMarks($row['content']);
                }
                $rows[] = $row;
            }
        }
        return $rows;
    }
    public function createRequest($data) {
        // [FIX] ATOMIC LOCK: Prevent race condition double-submit
        // Use file lock per user to serialize requests
        $lockFile = sys_get_temp_dir() . '/req_lock_' . ($data['creator_id'] ?? 'unknown') . '.lock';
        $fp = fopen($lockFile, 'w+');
        
        // precise locking: Wait for lock (Blocking)
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return ['error' => 'System busy (Lock failed). Please try again.'];
        }

        try {
            // [FIX] DEDUP GUARD: Check for double submit (Same Title + Creator within 60s)
            // Now inside the lock, this check is 100% reliable
            $dedupSql = "SELECT TOP 1 id FROM script_request WHERE title = ? AND created_by = ? AND DATEDIFF(SECOND, created_at, GETDATE()) < 60";
            $dedupStmt = db_query($this->conn, $dedupSql, [$data['title'], $data['creator_id']]);
            if ($dedupStmt && $dedupRow = db_fetch_array($dedupStmt, DB_FETCH_ASSOC)) {
                 // Return specific error for double submit
                 return ['error' => 'Double submit detected. Please wait...'];
            }

            // 1. Generate Ticket ID (SC-XXXX)
            // Get the last ticket_id
            $lastSql = "SELECT TOP 1 ticket_id FROM script_request WHERE ticket_id LIKE 'SC-%' ORDER BY id DESC";
            $lastStmt = db_query($this->conn, $lastSql);
            
            $nextNumber = 1;
            if ($lastStmt && $lastRow = db_fetch_array($lastStmt, DB_FETCH_ASSOC)) {
                // Extract number from "SC-XXXX"
                $parts = explode('-', $lastRow['ticket_id'] ?? '');
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $nextNumber = intval($parts[1]) + 1;
                }
            }
            
            $ticketId = sprintf("SC-%04d", $nextNumber);
            $ticketNumber = $ticketId; // Store string directly
            
            // 2. Generate Script Number (KONV/SYR-MEDIA-DD/MM/YY-0001-01)
            $jenisCode = ($data['jenis'] === 'Konvensional') ? 'KONV' : 'SYR';
            
            // Media code mapping
            $mediaMapping = [
                'WhatsApp' => 'WA',
                'Robocoll' => 'RC',
                'Surat' => 'SR',
                'Email' => 'EM',
                'VB' => 'VB',
                'Chatbot' => 'CB',
                'SMS' => 'SM',
                'Others' => 'OT'
            ];
            
            // Abbreviate all selected media
            $mediaParts = array_map('trim', explode(',', $data['media']));
            $abbreviations = [];
            foreach ($mediaParts as $part) {
                $abbreviations[] = isset($mediaMapping[$part]) ? $mediaMapping[$part] : 'OT';
            }
            $mediaCode = implode('/', array_unique($abbreviations));
            
            $dateCode = date('d/m/y'); // DD/MM/YY
            
            // [SYSTEM FIX] Counter logic (per media combination)
            // Use TOP 1 ... ORDER BY id DESC instead of COUNT(*) to prevent collisions after deletions
            $counterSql = "SELECT TOP 1 script_number FROM script_request WHERE script_number LIKE ? ORDER BY id DESC";
            $pattern = $jenisCode . '-' . $mediaCode . '-%';
            $counterStmt = db_query($this->conn, $counterSql, [$pattern]);
            
            $nextCounter = 1;
            if ($counterStmt && $counterRow = db_fetch_array($counterStmt, DB_FETCH_ASSOC)) {
                $lastSn = $counterRow['script_number'];
                $snParts = explode('-', $lastSn);
                // Format: JENIS-MEDIA-DATE-COUNTER-VERSION (Counter is index 3)
                if (count($snParts) >= 4 && is_numeric($snParts[3])) {
                    $nextCounter = intval($snParts[3]) + 1;
                }
            }
            
            $counter = sprintf("%04d", $nextCounter);
            $version = 1; // Default version
            
            $scriptNumber = sprintf("%s-%s-%s-%s-%02d", $jenisCode, $mediaCode, $dateCode, $counter, $version);

            // 3. Insert into script_request
            $sql = "INSERT INTO script_request (
                ticket_id, script_number, title, jenis, produk, kategori, media, mode, 
                status, current_role, version, created_by, selected_spv, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'CREATED', 'Supervisor', ?, ?, ?, GETDATE()); SELECT SCOPE_IDENTITY() as id";

            // Title (Use provided or auto-generated)
            $title = !empty($data['title']) ? $data['title'] : ("Script Request " . $data['jenis'] . " - " . $data['media']);
            
            $params = [
                $ticketNumber, // Insert the integer Ticket Number
                $scriptNumber,
                $title,
                $data['jenis'],
                $data['produk'],
                $data['kategori'],
                $data['media'],
                $data['mode'],
                $version,
                $data['creator_id'],
                $data['selected_spv']
            ];

            $stmt = db_query($this->conn, $sql, $params);
            if ($stmt === false) {
                 $errors = db_errors();
                 $msg = "SQL Error: ";
                 if ($errors != null) {
                     foreach ($errors as $error) {
                         $msg .= $error['message'] . " ";
                     }
                 }
                 return ['error' => $msg];
            }

            db_next_result($stmt);
            $row = db_fetch_array($stmt, DB_FETCH_ASSOC);
            
            return ['id' => $row['id'], 'number' => $scriptNumber, 'ticket_id' => $ticketId];

        } finally {
            // [FIX] Always release lock
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function createRevisionDraft($originalId, $userId, $selectedSpv = null) {
        // Reuse logic of createVersionedRequest but strictly for Revision Cycle
        // Returns ['success' => true, 'id' => ..., 'number' => ...]
        return $this->createVersionedRequest($originalId, $userId, $selectedSpv);
    }

    public function getExistingRevision($baseScriptNumber) {
        // Extract Base (Everything before the last dash, assuming format CODE-NO-VERSION)
        // Actually script_number format is irregular in many systems.
        // But usually Revision appends '-XX'.
        // Let's assume standard format OR use the `ticket_id` relation if available.
        // Better: Search by `title` pattern OR `script_number` pattern?
        // Safest: Search for ANY script that is "derived" from this?
        // Logic: 
        // 1. Get all versions. 
        // 2. Filter for active ones.
        
        // Simpler: Just check if there is ANY active request with similar numbering?
        // regex: $baseScriptNumber . '-%' ?
        // If $baseScriptNumber is 'DOC-001-01', next is 'DOC-001-02'.
        // Base is 'DOC-001'.
        
        $parts = explode('-', $baseScriptNumber);
        if (count($parts) > 1) {
            array_pop($parts); // Remove Version
            $base = implode('-', $parts);
            $pattern = $base . '-%';
            
            $sql = "SELECT TOP 1 id FROM script_request 
                    WHERE script_number LIKE ? 
                    AND status NOT IN ('LIBRARY', 'COMPLETED', 'REJECTED', 'APPROVED_PROCEDURE', 'CANCELLED', 'DRAFT_TEMP', 'DELETED')
                    ORDER BY id DESC";
            
            $stmt = db_query($this->conn, $sql, [$pattern]);
            if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
                return $row['id'];
            }
        }
        return null;
    }

    public function getExistingRevisionDetails($baseScriptNumber) {
        $parts = explode('-', $baseScriptNumber);
        if (count($parts) > 1) {
            array_pop($parts);
            $base = implode('-', $parts);
            $pattern = $base . '-%';
            
            $sql = "SELECT TOP 1 id, ticket_id, status, script_number 
                    FROM script_request 
                    WHERE script_number LIKE ? 
                    AND status NOT IN ('LIBRARY', 'COMPLETED', 'REJECTED', 'APPROVED_PROCEDURE', 'CANCELLED')
                    ORDER BY id DESC";
            
            $stmt = db_query($this->conn, $sql, [$pattern]);
            if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Check if a newer version of this script exists (for version guard)
     * @param string $scriptNumber The current script number (e.g. KONV-RC-12/02/26-0037-06)
     * @return array|null Returns ['script_number'=>..., 'status'=>..., 'ticket_id'=>...] if newer exists, null otherwise
     */
    public function checkNewerVersionExists($scriptNumber) {
        $parts = explode('-', $scriptNumber);
        if (count($parts) < 5) return null;
        
        // Extract base and current version
        $lastIdx = count($parts) - 1;
        $currentVer = $parts[$lastIdx];
        if (!is_numeric($currentVer)) return null;
        
        $baseNumber = implode('-', array_slice($parts, 0, $lastIdx));
        
        // Find ANY version of this base that is numerically higher
        $sql = "SELECT TOP 1 id, script_number, status, ticket_id, current_role 
                FROM script_request 
                WHERE script_number LIKE ? 
                AND script_number != ?
                AND status NOT IN ('DRAFT_TEMP', 'CANCELLED', 'DELETED')
                ORDER BY script_number DESC";
        
        $stmt = db_query($this->conn, $sql, [$baseNumber . '-%', $scriptNumber]);
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            // Extract the version number from the found script
            $foundParts = explode('-', $row['script_number']);
            $foundVer = end($foundParts);
            if (is_numeric($foundVer) && intval($foundVer) > intval($currentVer)) {
                return $row;
            }
        }
        return null;
    }

    public function createVersionedRequest($originalId, $creatorId, $newSpvId) {
        // 1. Fetch Original Data
        $original = $this->getRequestById($originalId);
        if (!$original) return ['error' => 'Original script not found'];

        // 2. Generate NEW Ticket ID (Unique for every request)
        $lastSql = "SELECT TOP 1 ticket_id FROM script_request WHERE ticket_id LIKE 'SC-%' ORDER BY id DESC";
        $lastStmt = db_query($this->conn, $lastSql);
        $nextTicketNum = 1;
        if ($lastStmt && $lastRow = db_fetch_array($lastStmt, DB_FETCH_ASSOC)) {
            $parts = explode('-', $lastRow['ticket_id'] ?? '');
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextTicketNum = intval($parts[1]) + 1;
            }
        }
        $newTicketId = sprintf("SC-%04d", $nextTicketNum);

        // 3. Generate NEW Script Number (Version Increment)
        // Format: [BASE]-[VERSION] (e.g. KONV-WA-20/01/26-0005-01)
        $oldNumber = $original['script_number'];
        $parts = explode('-', $oldNumber);
        
        $newVersion = 1;
        $baseNumber = $oldNumber;

        // Check format: usually last part is version 2 digits
        // Reconstruct base: everything except the last part
        if (count($parts) >= 5) {
             // Assume standard format: CODE-MEDIA-DATE-CTR-VER
             $lastIdx = count($parts) - 1;
             $baseNumber = implode('-', array_slice($parts, 0, $lastIdx));
             
             // Find Max Version of this Base in DB to ensure safety
             $verSql = "SELECT TOP 1 script_number FROM script_request WHERE script_number LIKE ? ORDER BY script_number DESC";
             $verStmt = db_query($this->conn, $verSql, [$baseNumber . '%']);
             
             if ($verStmt && $verRow = db_fetch_array($verStmt, DB_FETCH_ASSOC)) {
                 $latestNum = $verRow['script_number'];
                 $latestParts = explode('-', $latestNum);
                 $latestVer = end($latestParts);
                 if (is_numeric($latestVer)) {
                     $newVersion = intval($latestVer) + 1;
                 }
             }
        }
        
        // Final New Number
        $newScriptNumber = $baseNumber . '-' . sprintf("%02d", $newVersion);

        // 3.5. CLEANUP: Delete any existing "DRAFT_TEMP" for this specific version (Self-Cleaning)
        $checkGarbage = "SELECT id FROM script_request WHERE script_number = ? AND created_by = ? AND status = 'DRAFT_TEMP'";
        $garbageStmt = db_query($this->conn, $checkGarbage, [$newScriptNumber, $creatorId]);
        if ($garbageStmt && $garbageRow = db_fetch_array($garbageStmt, DB_FETCH_ASSOC)) {
            $garbageId = $garbageRow['id'];
            // Cascade delete dependencies
            db_query($this->conn, "DELETE FROM script_preview_content WHERE request_id = ?", [$garbageId]);
            db_query($this->conn, "DELETE FROM script_files WHERE request_id = ?", [$garbageId]);
            db_query($this->conn, "DELETE FROM script_audit_trail WHERE request_id = ?", [$garbageId]);
            db_query($this->conn, "DELETE FROM script_request WHERE id = ?", [$garbageId]);
        }

        // 4. Duplicate Request Record
        $sql = "INSERT INTO script_request (
                    ticket_id, script_number, title, jenis, produk, kategori, media, mode, 
                    status, current_role, version, created_by, selected_spv, created_at, updated_at, start_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT_TEMP', 'Maker', ?, ?, ?, GETDATE(), GETDATE(), ?); 
                SELECT SCOPE_IDENTITY() as id";
        
        $title = $original['title'] . " (Rev $newVersion)";
        
        $params = [
            $newTicketId,
            $newScriptNumber,
            $title,
            $original['jenis'],
            $original['produk'],
            $original['kategori'],
            $original['media'],
            $original['mode'],
            $newVersion,
            $creatorId,
            $newSpvId, // Use NEW SPV selection
            $original['start_date'] // Inherit start_date from original
        ];

        $stmt = db_query($this->conn, $sql, $params);
        if ($stmt === false) {
             return ['error' => 'Failed to create revision record'];
        }
        db_next_result($stmt);
        $row = db_fetch_array($stmt, DB_FETCH_ASSOC);
        $newId = $row['id'];

        // 5. Duplicate Preview Content 
    // FIX: If source is LIBRARY/COMPLETED, fetch CLEAN content from Library instead of dirty Draft
    $contentList = [];
    if (in_array($original['status'], ['LIBRARY', 'COMPLETED', 'APPROVED_PROCEDURE'])) {
        $contentList = $this->getLibraryContentOnly($originalId);
        
        // Fallback: If Library empty (rare), try Preview
        if (empty($contentList)) {
            $contentList = $this->getPreviewContent($originalId);
        }
    } else {
        // Normal Revision (Draft -> Draft)
        $contentList = $this->getPreviewContent($originalId);
    }

    foreach ($contentList as $c) {
        $this->savePreviewContent($newId, $c['media'], $c['content'], $creatorId);
    }    

        // 6. Duplicate Template File (If exists)
        $tmplFile = $this->getScriptFile($originalId);
        if ($tmplFile) {
            // Copy physical file if needed? Or just point to same path?
            // Safer to point to same path OR copy. Ideally we don't duplicate physical files to save space 
            // unless user edits it later. 
            // For now: Just insert DB record pointing to same file. 
            // NOTE: If user edits, 'saveFileInfo' will create new file.
            $this->saveFileInfo($newId, 'TEMPLATE', $tmplFile['original_filename'], $tmplFile['filepath'], $creatorId);
        }
        
        // 7. Log Audit
        $this->logAudit($newId, $newScriptNumber, 'DRAFT_INIT', 'Maker', $creatorId, "Draft Revision $newVersion initialized from $oldNumber");

        return ['success' => true, 'id' => $newId, 'number' => $newScriptNumber];
    }

    public function savePreviewContent($scriptId, $media, $content, $user) {
        $sql = "INSERT INTO script_preview_content (request_id, media, content, updated_by, updated_at) VALUES (?, ?, ?, ?, GETDATE())";
        $params = [$scriptId, $media, $content, $user];
        return db_query($this->conn, $sql, $params);
    }

    public function saveFileInfo($scriptId, $type, $originalName, $path, $user) {
        $sql = "INSERT INTO script_files (request_id, file_type, original_filename, filepath, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, GETDATE()); SELECT SCOPE_IDENTITY() AS id";
        $params = [$scriptId, $type, $originalName, $path, $user];
        $stmt = db_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return false;
        }

        db_next_result($stmt); // Move to the SELECT result
        $row = db_fetch_array($stmt, DB_FETCH_ASSOC);
        
        return $row['id'] ?? false;
    }

    // ... (existing code)

    public function deleteReviewDoc($fileId) {
        $sql = "DELETE FROM script_files WHERE id = ?";
        return db_query($this->conn, $sql, [$fileId]);
    }

    public function deleteRequest($id, $userId) {
        // Soft Delete: Mark is_deleted = 1
        $sql = "UPDATE script_request SET is_deleted = 1, updated_at = GETDATE() WHERE id = ?";
        $res = db_query($this->conn, $sql, [$id]);
        
        if ($res) {
            // Log Audit
            $req = $this->getRequestById($id);
            if ($req) {
                 $this->logAudit($id, $req['script_number'], 'DELETED', 'ADMIN', $userId, 'Request soft-deleted by Admin');
            }
            return true;
        }
        return false;
    }
    
    // ...
    
    // Update getLibraryItems similarly


    public function getFileById($fileId) {
        $sql = "SELECT * FROM script_files WHERE id = ?";
        $stmt = db_query($this->conn, $sql, [$fileId]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row;
        }
        return null;
    }
    public function updatePreviewContent($id, $content) {
        $sql = "UPDATE script_preview_content SET content = ? WHERE id = ?";
        return db_query($this->conn, $sql, [$content, $id]);
    }

    /**
     * Insert new version of preview content (for versioning system)
     */
    public function insertPreviewContentVersion($requestId, $media, $content, $workflowStage, $createdBy) {
        $sql = "INSERT INTO script_preview_content 
                (request_id, media, content, workflow_stage, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, GETDATE())";
        return db_query($this->conn, $sql, [$requestId, $media, $content, $workflowStage, $createdBy]);
    }

    /**
     * Get preview content by ID
     */
    public function getPreviewContentById($id) {
        $sql = "SELECT * FROM script_preview_content WHERE id = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        return db_fetch_array($stmt, DB_FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $nextRole, $user) {
        // Schema Fix: Table script_request has no 'updated_by' column. 
        // We only update status, current_role, and updated_at.
        $sql = "UPDATE script_request SET status = ?, current_role = ?, updated_at = GETDATE() WHERE id = ?";
        $stmt = db_query($this->conn, $sql, [$status, $nextRole, $id]);
        return $stmt;
    }
    
    // NEW: Update selected PIC for SPV approval
    public function updateSelectedPic($id, $selectedPic) {
        $sql = "UPDATE script_request SET selected_pic = ? WHERE id = ?";
        return db_query($this->conn, $sql, [$selectedPic, $id]);
    }

    public function setDraftStatus($id, $hasDraft) {
        $sql = "UPDATE script_request SET has_draft = ?, updated_at = GETDATE() WHERE id = ?";
        return db_query($this->conn, $sql, [$hasDraft, $id]);
    }

    public function updateRequestMetadata($id, $data) {
        $allowed = ['title', 'jenis', 'produk', 'kategori', 'media', 'mode', 'selected_spv', 'start_date'];
        $updates = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) return true;
        
        $sql = "UPDATE script_request SET " . implode(', ', $updates) . ", updated_at = GETDATE() WHERE id = ?";
        $params[] = $id;
        
        return db_query($this->conn, $sql, $params);
    }
    
    public function getRejectionReason($requestId) {
        $sql = "SELECT TOP 1 details FROM script_audit_trail WHERE request_id = ? AND action IN ('REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION') ORDER BY created_at DESC";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row['details'];
        }
        return '';
    }

    public function getLatestRevisionInfo($requestId) {
        $sql = "SELECT TOP 1 details, user_role FROM script_audit_trail 
                WHERE request_id = ? AND action IN ('REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION') 
                ORDER BY created_at DESC";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row;
        }
        return null;
    }

    public function getLatestDraftNote($requestId) {
        $sql = "SELECT TOP 1 details FROM script_audit_trail WHERE request_id = ? AND action = 'DRAFT_SAVED' ORDER BY created_at DESC";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            // Details format: "Draft saved by Maker. Note: [Content]"
            // We need to extract the content.
            $raw = $row['details'];
            if (strpos($raw, 'Note: ') !== false) {
                return trim(substr($raw, strpos($raw, 'Note: ') + 6));
            }
        }
        return '';
    }

    public function getFiles($requestId) {
        $sql = "SELECT * FROM script_files WHERE request_id = ?";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        $rows = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function logAudit($requestId, $scriptNumber, $action, $role, $user, $details) {
        // [FIX] DEDUP GUARD: Prevent duplicate audit entries from double submissions
        // Skip if same request + action + user was logged within last 30 seconds
        $dedupSql = "SELECT TOP 1 id FROM script_audit_trail 
                     WHERE request_id = ? AND action = ? AND user_id = ? 
                     AND DATEDIFF(SECOND, created_at, GETDATE()) < 30";
        $dedupStmt = db_query($this->conn, $dedupSql, [$requestId, $action, $user]);
        if ($dedupStmt && ($dedupRow = db_fetch_array($dedupStmt, DB_FETCH_ASSOC))) {
            // Duplicate detected — skip
            error_log("[DEDUP] Skipping duplicate audit: request=$requestId action=$action user=$user (existing id={$dedupRow['id']})");
            return true;
        }

        $sql = "INSERT INTO script_audit_trail (request_id, script_number, action, user_role, user_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?, GETDATE())";
        return db_query($this->conn, $sql, [$requestId, $scriptNumber, $action, $role, $user, $details]);
    }

    public function finalizeLibrary($requestId) {
        // 1. Get Request Info
        $req = $this->getRequestById($requestId);
        if (!$req) return false;

        // 2. Get Content
        $contentList = $this->getPreviewContent($requestId);

        // 3. Insert specific rows into Library
    // FIX: Always insert ALL rows. 
    // Previously FILE_UPLOAD was limited to 1 row, causing Multi-Sheet Excel to lose sheets.
    // If exact duplicates occur (e.g. same PDF for WA & SMS), we accept them to ensure data safety.

    // [AUTO-DEACTIVATE] Deactivate ALL older versions of this script in the Library
    $scriptNum = $req['script_number'];
    $parts = explode('-', $scriptNum);
    if (count($parts) >= 5) {
        $baseNumber = implode('-', array_slice($parts, 0, count($parts) - 1));
        $deactivateSql = "UPDATE script_library SET is_active = 0 WHERE script_number LIKE ? AND script_number != ?";
        db_query($this->conn, $deactivateSql, [$baseNumber . '-%', $scriptNum]);
    }

    foreach ($contentList as $c) {
        // CLEAN CONTENT: Remove all revision marks before publishing to Library
        $cleanContent = $this->cleanReviewMarks($c['content']);
        
        // Default to INACTIVE (0) so Maker must activate it manually
        $sql = "INSERT INTO script_library (request_id, script_number, media, content, version, created_at, is_active, start_date) VALUES (?, ?, ?, ?, ?, GETDATE(), 0, ?)";
        $params = [$requestId, $req['script_number'], $c['media'], $cleanContent, $req['version'], $req['start_date']];
        if (!db_query($this->conn, $sql, $params)) {
            return false;
        }
    }
        return true;
    }

    /**
     * Clean review marks from HTML content
     * Removes red revision spans and yellow highlight spans for clean Library display
     * @param string $html HTML content with potential review marks
     * @return string Cleaned HTML content
     */
    private function cleanReviewMarks($html) {
        if (empty($html)) return $html;
        
        // === STEP 1: REMOVE DELETED/STRIKETHROUGH TEXT (completely remove content) ===
        // Deletion spans (class "deletion-span")
        $html = preg_replace('/<span[^>]*class="[^"]*deletion-span[^"]*"[^>]*>.*?<\/span>/is', '', $html);
        // <del>, <s>, <strike> tags
        $html = preg_replace('/<del>(.*?)<\/del>/is', '', $html);
        $html = preg_replace('/<s>(.*?)<\/s>/is', '', $html);
        $html = preg_replace('/<strike>(.*?)<\/strike>/is', '', $html);
        // Elements with line-through style
        $html = preg_replace('/<[^>]*style="[^"]*text-decoration:\s*line-through[^"]*"[^>]*>.*?<\/[^>]*>/is', '', $html);
        
        // === STEP 2: KEEP RED TEXT CONTENT BUT REMOVE RED STYLING (clean for Library) ===
        // revision-span and inline-comment spans → unwrap to plain text (no red color)
        $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
        $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
        // Red spans with inline style → unwrap to plain text
        $html = preg_replace('/<span[^>]*style="[^"]*color:\s*(red|#ff0000|#ef4444|rgb\(\s*255,\s*0,\s*0\s*\))[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
        
        // === STEP 3: REMOVE YELLOW HIGHLIGHT (unwrap, keep text) ===
        $html = preg_replace('/<span[^>]*style="[^"]*background(-color)?:\s*#fef08a[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
        
        // === STEP 4: CLEANUP ===
        // Remove data-comment attributes
        $html = preg_replace('/\sdata-comment-[a-z]+="[^"]*"/i', '', $html);
        $html = preg_replace('/\sid="rev-[0-9]+"/i', '', $html);
        // Remove empty spans
        $html = preg_replace('/<span>(.*?)<\/span>/is', '$1', $html);
        $html = preg_replace('/<span\s*>(.*?)<\/span>/is', '$1', $html);
        
        return $html;
    }

    public function getLibraryItems($startDate = null, $endDate = null, $sortOrder = 'DESC', $filters = [], $showInactive = false, $sortBy = 'created_at', $dateType = 'created_at', $search = null) {
        $whereClauses = [];
        $params = [];
        
        // VISIBILITY FILTER:
        // If $showInactive is FALSE (e.g. Agent view), show ONLY active scripts AND those with valid start date.
        // If TRUE (e.g. Maker/Admin view), show ALL.
        if (!$showInactive) {
            $whereClauses[] = "(l.is_active = 1 AND (l.start_date IS NULL OR l.start_date <= CAST(GETDATE() AS DATE)))";
        }
        
        // Date Filter (on Library Creation/Publication Date) - Keep this as Published Date filter
        if ($startDate && $endDate) {
            $filterCol = in_array($dateType, ['created_at', 'start_date']) ? $dateType : 'created_at';
            $whereClauses[] = "CAST(l.$filterCol AS DATE) >= ? AND CAST(l.$filterCol AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Advanced Filters (Multi-Select)
        // Filters expected structure: ['jenis' => ['Konvensional', 'Syariah'], 'produk' => [...], ...]
        $filterableColumns = ['jenis', 'produk', 'kategori'];
        foreach ($filterableColumns as $col) {
            if (!empty($filters[$col]) && is_array($filters[$col])) {
                if (in_array($col, ['produk', 'kategori'])) {
                     $colClauses = [];
                     foreach ($filters[$col] as $val) {
                         $colClauses[] = "r.$col LIKE ?";
                         $params[] = '%' . $val . '%';
                     }
                     if (!empty($colClauses)) {
                         $whereClauses[] = "(" . implode(' OR ', $colClauses) . ")";
                     }
                } else {
                    $placeholders = implode(',', array_fill(0, count($filters[$col]), '?'));
                    $whereClauses[] = "r.$col IN ($placeholders)";
                    $params = array_merge($params, $filters[$col]);
                }
            }
        }

        // Media Filter (Special handling for LIKE due to comma-separated values, BUT better to use LIKE for robustness if distinct values are simple)
        // Actually, if we use checkboxes, we want rows containing ANY of the selected media.
        // Since media is stored as "WA, Email", specific exact match IN clause won't work well if unchecked.
        // However, user asked for "checklist" of media.
        // If I select "WA", I expect scripts with "WA" or "WA, Email".
        if (!empty($filters['media']) && is_array($filters['media'])) {
            $mediaClauses = [];
            foreach ($filters['media'] as $media) {
                $mediaClauses[] = "r.media LIKE ?";
                $params[] = '%' . $media . '%';
            }
            if (!empty($mediaClauses)) {
                $whereClauses[] = "(" . implode(' OR ', $mediaClauses) . ")";
            }
        }
        
        if (!empty($filters['media']) && is_array($filters['media'])) {
            $mediaClauses = [];
            foreach ($filters['media'] as $media) {
                $mediaClauses[] = "r.media LIKE ?";
                $params[] = '%' . $media . '%';
            }
            if (!empty($mediaClauses)) {
                $whereClauses[] = "(" . implode(' OR ', $mediaClauses) . ")";
            }
        }

        // Global Search Filter
        if (!empty($search)) {
            $kw = '%' . $search . '%';
            $whereClauses[] = "(r.script_number LIKE ? OR r.title LIKE ? OR r.ticket_id LIKE ? OR r.produk LIKE ? OR r.media LIKE ? OR r.jenis LIKE ?)";
            // Add params for each ? placeholder
            $params[] = $kw; // script_number
            $params[] = $kw; // title
            $params[] = $kw; // ticket_id
            $params[] = $kw; // produk
            $params[] = $kw; // media
            $params[] = $kw; // jenis
        }
        
        $whereSql = "";
        if (!empty($whereClauses)) {
            $whereSql = "WHERE " . implode(' AND ', $whereClauses);
        }
        
        // Validation for Sort Order
        $sort = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        // Validate Sort Field
        $allowedSorts = ['created_at', 'request_created_at', 'start_date', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        
        $sortColumn = ($sortBy === 'request_created_at') ? "r.created_at" : "l." . $sortBy;
        
        // JOIN Query to get everything in one go
        $sql = "SELECT l.*, r.ticket_id, r.title, r.mode, r.jenis, r.produk, r.kategori, r.media as request_media, r.created_at as request_created_at 
                FROM script_library l
                JOIN script_request r ON l.request_id = r.id
                $whereSql
                AND r.is_deleted = 0 
                ORDER BY $sortColumn $sort";
        
        $stmt = db_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            error_log("SQL Error in getLibraryItems: " . print_r(db_errors(), true));
            return [];
        }
        
        $rows = [];
        while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        
        error_log("Library items count: " . count($rows));
        return $rows;
    }

    public function getDistinctRequestValues($column) {
        // Validation to prevent SQL Injection
        $allowedColumns = ['jenis', 'produk', 'kategori', 'media', 'status'];
        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        // Get distinct values from ALL requests using WHERE condition (e.g. only non-null)
        // Note: For Dashboard, we might want to filter this by Role/Status too, but generally showing all available options is acceptable UX.
        $sql = "SELECT DISTINCT $column 
                FROM script_request 
                WHERE $column IS NOT NULL AND $column != '' AND status != 'DRAFT_TEMP'
                ORDER BY $column ASC";
                
        $stmt = db_query($this->conn, $sql);
        $values = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                if (in_array($column, ['media', 'produk', 'kategori'])) {
                    $parts = preg_split('/[,;]\s*/', $row[$column]);
                    foreach ($parts as $part) {
                        $p = trim($part);
                        if ($p && !in_array($p, $values)) {
                            $values[] = $p;
                        }
                    }
                } else {
                     $values[] = $row[$column];
                }
            }
        }
        
        if (in_array($column, ['media', 'produk', 'kategori'])) {
            sort($values);
        }
        
        return $values;
    }

    public function getDistinctLibraryValues($column) {
        // Validation to prevent SQL Injection
        $allowedColumns = ['jenis', 'produk', 'kategori', 'media'];
        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        // Get distinct values only for items that are actually in the library
        $sql = "SELECT DISTINCT r.$column 
                FROM script_request r
                JOIN script_library l ON r.id = l.request_id
                WHERE r.$column IS NOT NULL AND r.$column != ''
                ORDER BY r.$column ASC";
                
        $stmt = db_query($this->conn, $sql);
        $values = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                // Special handling for Media if it contains commas? 
                // For now, let's assume the distinct query returns the full strings like "WA, Email".
                // If we want individual checklist items, we might need to post-process.
                // But typically filters match the exact data structure or we parse it.
                // Given the requirement "checklist per media", if data is "WA, SMS", we ideally want "WA" and "SMS" as options.
                // But simpler first step: Distinct values as they appear.
                // BETTER: Split them if column is media, produk, or kategori.
                if (in_array($column, ['media', 'produk', 'kategori'])) {
                    $parts = preg_split('/[,;]\s*/', $row[$column]);
                    foreach ($parts as $part) {
                        $p = trim($part);
                        if ($p && !in_array($p, $values)) {
                            $values[] = $p;
                        }
                    }
                } else {
                     $values[] = $row[$column];
                }
            }
        }
        
        if (in_array($column, ['media', 'produk', 'kategori'])) {
            sort($values);
        }
        
        return $values;
    }
    
    // UPDATED WRAPPER
    // UPDATED WRAPPER
    public function getLibraryItemsWithContent($startDate = null, $endDate = null, $sortOrder = 'DESC', $filters = [], $showInactive = false, $sortBy = 'created_at', $dateType = 'created_at', $search = null) {
        $items = $this->getLibraryItems($startDate, $endDate, $sortOrder, $filters, $showInactive, $sortBy, $dateType, $search);
        
        // Deduplication Logic: Group by Request ID
        $uniqueScripts = [];

        foreach ($items as $item) {
            $reqId = $item['request_id'];
            
            // Prepare Content for this row (Library Content)
            $mediaLabel = $item['media'] ?? 'Part';
            $rowContent = strip_tags($item['content'] ?? '');
            $rowContent = trim(preg_replace('/\s+/', ' ', $rowContent));
            
            $formattedContent = "";
            if (!empty($rowContent)) {
                // requested format: "Media : Content"
                $formattedContent = "$mediaLabel : " . $rowContent;
            }

            // If we already have this request, APPEND content (but NOT for File Upload — just filename)
            if (isset($uniqueScripts[$reqId])) {
                // [FIX] Skip appending for FILE_UPLOAD — show filename only
                if (($uniqueScripts[$reqId]['mode'] ?? '') === 'FILE_UPLOAD') {
                    continue;
                }
                if (!empty($formattedContent)) {
                     // Check if not duplicate text (sanity check)
                     if (strpos($uniqueScripts[$reqId]['content_aggregated'] ?? '', $formattedContent) === false) {
                         // Use standard newline, will be converted to <br> in Controller
                         $uniqueScripts[$reqId]['content_aggregated'] .= "\n\n" . $formattedContent;
                     }
                }
                continue; 
            }

            // --- FIRST TIME SEEING THIS REQUEST ---
            
            // 1. Initialize Aggregated Content
            $item['content_aggregated'] = $formattedContent;

            // 2. File Upload Filename Logic
            if (($item['mode'] ?? '') === 'FILE_UPLOAD') {
                 // Try to get TEMPLATE file first
                 $sql3 = "SELECT TOP 1 original_filename FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE'";
                 $stmt3 = db_query($this->conn, $sql3, [$item['request_id']]);
                 if ($stmt3 && $row3 = db_fetch_array($stmt3, DB_FETCH_ASSOC)) {
                     $item['filename'] = $row3['original_filename'];
                 } else {
                     $item['filename'] = 'Attached File';
                 }
                 // Override content with Filename for File Uploads
                 $item['content_aggregated'] = $item['filename'];
            }
            
            // 3. Fallback Preview (if Library table was empty/migrated legacy?)
            if (empty($item['content_aggregated']) && ($item['mode'] ?? '') !== 'FILE_UPLOAD') {
                 // Try fetching from preview table
                 $sql = "SELECT TOP 1 content FROM script_preview_content WHERE request_id = ? ORDER BY id DESC";
                 $stmt = db_query($this->conn, $sql, [$item['request_id']]);
                 if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                     $item['content_aggregated'] = strip_tags($row['content']);
                 }
            }

            // Add to unique list
            $uniqueScripts[$reqId] = $item;
        }
        
        // Return indexed array of unique items
        return array_values($uniqueScripts);
    }


    public function getAllAuditLogs($startDate = null, $endDate = null) {
        // Get all audit logs without JOIN (simpler, more reliable)
        $where = "";
        $params = [];
        if ($startDate && $endDate) {
            $where = "WHERE CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        $sql = "SELECT * FROM script_audit_trail $where ORDER BY created_at DESC";
        $stmt = db_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getUserRequests($userId, $startDate = null, $endDate = null, $filters = [], $statusFilter = null) {
        // Get all requests created by specific user
        $where = "WHERE created_by = ? AND is_deleted = 0";
        $params = [$userId];

        // Date range filter
        if ($startDate && $endDate) {
            $where .= " AND CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Status filter
        if ($statusFilter === 'revise') {
            $where .= " AND status IN ('REVISION', 'REJECTED', 'MINOR_REVISION', 'MAJOR_REVISION')";
        } elseif ($statusFilter === 'confirm') {
            $where .= " AND status = 'PENDING_MAKER_CONFIRMATION'";
        } elseif ($statusFilter === 'draft') {
            $where .= " AND status = 'DRAFT'";
        } elseif ($statusFilter === 'wip') {
            $where .= " AND status IN ('CREATED', 'APPROVED_SPV', 'APPROVED_PIC', 'APPROVED_PROCEDURE')";
        } elseif ($statusFilter === 'done') {
            $where .= " AND status IN ('CLOSED', 'LIBRARY')";
        }

        // Advanced Filters (Multi-Select)
        $filterableColumns = ['jenis', 'produk', 'kategori'];
        foreach ($filterableColumns as $col) {
            if (!empty($filters[$col]) && is_array($filters[$col])) {
                if (in_array($col, ['produk', 'kategori'])) {
                    $colClauses = [];
                    foreach ($filters[$col] as $val) {
                        $colClauses[] = "$col LIKE ?";
                        $params[] = '%' . $val . '%';
                    }
                    if (!empty($colClauses)) {
                        $where .= " AND (" . implode(' OR ', $colClauses) . ")";
                    }
                } else {
                    $placeholders = implode(',', array_fill(0, count($filters[$col]), '?'));
                    $where .= " AND $col IN ($placeholders)";
                    $params = array_merge($params, $filters[$col]);
                }
            }
        }

        // Media Filter (LIKE due to comma-separated)
        if (!empty($filters['media']) && is_array($filters['media'])) {
            $mediaClauses = [];
            foreach ($filters['media'] as $media) {
                $mediaClauses[] = "media LIKE ?";
                $params[] = '%' . $media . '%';
            }
            if (!empty($mediaClauses)) {
                $where .= " AND (" . implode(' OR ', $mediaClauses) . ")";
            }
        }

        $sql = "SELECT * FROM script_request $where ORDER BY created_at DESC";
        $stmt = db_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getApprovalStats($userId, $role) {
        // Count pending requests for this role
        $pendingCount = 0;
        $pendingWhere = "";
        $pendingParams = [];
        
        if ($role === 'SPV') {
            if (in_array(strtolower($userId), ['admin', 'admin_script'])) {
                $pendingWhere = "WHERE status = 'CREATED' AND is_deleted = 0";
            } else {
                $pendingWhere = "WHERE selected_spv = ? AND status = 'CREATED' AND is_deleted = 0";
                $pendingParams[] = $userId;
            }
        } elseif ($role === 'PIC') {
            $pendingWhere = "WHERE status = 'APPROVED_SPV' AND (selected_pic = ? OR selected_pic IS NULL) AND is_deleted = 0";
            $pendingParams[] = $userId;
        } elseif ($role === 'PROCEDURE') {
            $pendingWhere = "WHERE (status = 'APPROVED_PIC' OR (status = 'DRAFT_TEMP' AND created_by = ?)) AND is_deleted = 0";
            $pendingParams[] = $userId;
        }
        
        if ($pendingWhere) {
            $sql = "SELECT COUNT(*) as cnt FROM script_request $pendingWhere";
            $stmt = db_query($this->conn, $sql, $pendingParams);
            if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $pendingCount = (int)$row['cnt'];
            }
        }
        
        // Count history items
        $historySql = "SELECT COUNT(DISTINCT a.request_id) as cnt FROM script_audit_trail a WHERE a.user_id = ?";
        $historyStmt = db_query($this->conn, $historySql, [$userId]);
        $historyCount = 0;
        if ($historyStmt && $row = db_fetch_array($historyStmt, DB_FETCH_ASSOC)) {
            $historyCount = (int)$row['cnt'];
        }
        
        return [
            'pending' => $pendingCount,
            'history' => $historyCount
        ];
    }

    public function getScriptFile($requestId) {
        $sql = "SELECT TOP 1 * FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE' ORDER BY id DESC";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        if ($stmt && ($row = db_fetch_array($stmt, DB_FETCH_ASSOC))) {
            return $row;
        }
        return null;
    }

    public function getReviewDocuments($requestId) {
        // [FIX] Get review documents for the ENTIRE script family (Inheritance for Revisions)
        // 1. First, get the script number for this Request ID
        $sqlRequest = "SELECT script_number FROM script_request WHERE id = ?";
        $stmtRequest = db_query($this->conn, $sqlRequest, [$requestId]);
        if (!$stmtRequest || !($reqRow = db_fetch_array($stmtRequest, DB_FETCH_ASSOC))) {
            return []; // Request not found
        }
        
        $scriptNumber = $reqRow['script_number'];
        $baseExact = $scriptNumber;
        
        // 2. Determine the Base Script Number (Strip version suffix like -01, -02 if present)
        // Format: ...-XXXX (Serial 4 digit) | ...-XXXX-VV (Version 2 digit)
        if (preg_match('/-(\d{2})$/', $scriptNumber)) {
            $baseExact = preg_replace('/-(\d{2})$/', '', $scriptNumber);
        }
        $basePattern = $baseExact . '-%';

        // 3. Query all files belonging to this request OR any request in its family
        $sql = "SELECT f.*
                FROM script_files f
                INNER JOIN script_request r ON f.request_id = r.id
                WHERE (r.script_number = ? OR r.script_number LIKE ?)
                  AND f.file_type IN ('LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP')
                ORDER BY f.id DESC";
                  
        $stmt = db_query($this->conn, $sql, [$baseExact, $basePattern]);
        $rows = [];
        if ($stmt) {
            // Deduplicate by original_filename to prevent showing the same file twice
            // if it was somehow re-attached or duplicated in history
            $seenFiles = [];
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $key = $row['file_type'] . '_' . $row['original_filename'];
                if (!isset($seenFiles[$key])) {
                    $rows[] = $row;
                    $seenFiles[$key] = true;
                }
            }
        }
        return $rows;
    }

    public function getUserApprovalHistory($userId, $startDate = null, $endDate = null, $filters = []) {
        $where = "WHERE a.user_id = ?";
        $params = [$userId];
        
        if ($startDate && $endDate) {
            $where .= " AND CAST(a.created_at AS DATE) >= ? AND CAST(a.created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Apply filters (Multi-Select)
        $filterableColumns = ['jenis', 'produk', 'kategori'];
        foreach ($filterableColumns as $col) {
            if (!empty($filters[$col]) && is_array($filters[$col])) {
                if (in_array($col, ['produk', 'kategori'])) {
                     $colClauses = [];
                     foreach ($filters[$col] as $val) {
                         $colClauses[] = "r.$col LIKE ?";
                         $params[] = '%' . $val . '%';
                     }
                     if (!empty($colClauses)) {
                         $where .= " AND (" . implode(' OR ', $colClauses) . ")";
                     }
                } else {
                    $placeholders = implode(',', array_fill(0, count($filters[$col]), '?'));
                    $where .= " AND r.$col IN ($placeholders)";
                    $params = array_merge($params, $filters[$col]);
                }
            }
        }

        // Media Filter (Special handling for LIKE due to comma-separated values)
        if (!empty($filters['media']) && is_array($filters['media'])) {
            $mediaClauses = [];
            foreach ($filters['media'] as $media) {
                $mediaClauses[] = "r.media LIKE ?";
                $params[] = '%' . $media . '%';
            }
            if (!empty($mediaClauses)) {
                $where .= " AND (" . implode(' OR ', $mediaClauses) . ")";
            }
        }

        $sql = "SELECT a.action as my_last_action, a.created_at as my_action_date, a.details as my_note, r.*
                FROM script_audit_trail a
                JOIN script_request r ON a.request_id = r.id
                $where
                ORDER BY a.created_at DESC";
        
        $stmt = db_query($this->conn, $sql, $params);
        $rows = [];
        $seenRequests = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                // PHP Dedup: Only show the latest action for each request
                if (!in_array($row['id'], $seenRequests)) {
                    $rows[] = $row;
                    $seenRequests[] = $row['id'];
                }
            }
        }
        return $rows;
    }

    public function getAuditExportData($startDate = null, $endDate = null, $sortColumn = 'created_at', $sortOrder = 'DESC', $filters = [], $search = null) {
        // Get all requests with aggregated audit data, excluding DRAFT_TEMP
        $where = "WHERE 1=1 AND r.is_deleted = 0 AND r.status != 'DRAFT_TEMP'";
        $params = [];
        
        // Search Filter (Global)
        if (!empty($search)) {
            $kw = '%' . $search . '%';
            $where .= " AND (r.script_number LIKE ? OR r.ticket_id LIKE ? OR r.created_by LIKE ? OR r.media LIKE ? OR r.produk LIKE ?)";
            $params[] = $kw; $params[] = $kw; $params[] = $kw; $params[] = $kw; $params[] = $kw;
        }

        if ($startDate && $endDate) {
            $where .= " AND CAST(r.created_at AS DATE) >= ? AND CAST(r.created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Advanced Filters (Multi-Select)
        $filterableColumns = ['jenis', 'produk', 'kategori', 'status'];
        foreach ($filterableColumns as $col) {
            if (!empty($filters[$col]) && is_array($filters[$col])) {
                if (in_array($col, ['produk', 'kategori'])) {
                     $colClauses = [];
                     foreach ($filters[$col] as $val) {
                         $colClauses[] = "r.$col LIKE ?";
                         $params[] = '%' . $val . '%';
                     }
                     if (!empty($colClauses)) {
                         $where .= " AND (" . implode(' OR ', $colClauses) . ")";
                     }
                } else {
                    $placeholders = implode(',', array_fill(0, count($filters[$col]), '?'));
                    $where .= " AND r.$col IN ($placeholders)";
                    $params = array_merge($params, $filters[$col]);
                }
            }
        }

        // Media Filter
        if (!empty($filters['media']) && is_array($filters['media'])) {
            $mediaClauses = [];
            foreach ($filters['media'] as $media) {
                $mediaClauses[] = "r.media LIKE ?";
                $params[] = '%' . $media . '%';
            }
            if (!empty($mediaClauses)) {
                $where .= " AND (" . implode(' OR ', $mediaClauses) . ")";
            }
        }
        
        // Whitelist Columns
        $validColumns = ['created_at', 'updated_at'];
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = 'created_at';
        }
        
        // Whitelist Order
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT r.*, 
                u_maker.FULLNAME as maker_fullname,
                (SELECT COUNT(*) FROM script_files f WHERE f.request_id = r.id AND f.file_type = 'LEGAL') as has_legal,
                (SELECT COUNT(*) FROM script_files f WHERE f.request_id = r.id AND f.file_type = 'CX') as has_cx,
                (SELECT COUNT(*) FROM script_files f WHERE f.request_id = r.id AND f.file_type = 'SYARIAH') as has_syariah,
                (SELECT COUNT(*) FROM script_files f WHERE f.request_id = r.id AND f.file_type = 'LPP') as has_lpp
                FROM script_request r
                LEFT JOIN tbluser u_maker ON u_maker.USERID = r.created_by
                $where ORDER BY r.$sortColumn $sortOrder";
        $stmt = db_query($this->conn, $sql, $params);
        
        // Cache for USERID -> FULLNAME lookup
        $userCache = [];
        $resolveFullname = function($userId) use (&$userCache) {
            if (empty($userId)) return '';
            if (isset($userCache[$userId])) return $userCache[$userId];
            $sql = "SELECT FULLNAME FROM tbluser WHERE USERID = ?";
            $stmt = db_query($this->conn, $sql, [$userId]);
            if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $row = array_change_key_case($row, CASE_UPPER); // Normalize column keys
                $userCache[$userId] = $row['FULLNAME'] ?? $userId;
            } else {
                $userCache[$userId] = $userId; // fallback to USERID
            }
            return $userCache[$userId];
        };

        $exportData = [];
        if ($stmt) {
            while ($req = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                if ($req['status'] === 'DRAFT_TEMP') continue; // Skip transient drafts

                $requestId = $req['id'];
                
                // Get audit logs for this request
                $auditSql = "SELECT * FROM script_audit_trail WHERE request_id = ? ORDER BY created_at ASC";
                $auditStmt = db_query($this->conn, $auditSql, [$requestId]);
                
                $maker = $req['maker_fullname'] ?? $req['created_by'];
                $spv = $spvStatus = $spvTimestamp = '';
                $pic = $picStatus = $picTimestamp = '';
                $procedure = $procStatus = $procTimestamp = '';
                
                if ($auditStmt) {
                    while ($audit = db_fetch_array($auditStmt, DB_FETCH_ASSOC)) {
                        $action = $audit['action'];
                        $role = $audit['user_role'];
                        $userId = $audit['user_id'];
                        if (is_object($audit['created_at']) && method_exists($audit['created_at'], 'format')) {
                            $timestamp = $audit['created_at']->format('Y-m-d H:i:s');
                        } else {
                            $timestamp = (string)$audit['created_at'];
                        }
                        
                        if ($role === 'SPV' && in_array($action, ['APPROVE_SPV', 'REJECTED', 'REVISION'])) {
                            $spv = $resolveFullname($userId);
                            $spvStatus = $action;
                            $spvTimestamp = $timestamp;
                        } elseif ($role === 'PIC' && in_array($action, ['APPROVE_PIC', 'REJECTED', 'REVISION'])) {
                            $pic = $resolveFullname($userId);
                            $picStatus = $action;
                            $picTimestamp = $timestamp;
                        } elseif ($role === 'PROCEDURE' && in_array($action, ['APPROVE_PROCEDURE', 'REJECTED', 'REVISION'])) {
                            $procedure = $resolveFullname($userId);
                            $procStatus = $action;
                            $procTimestamp = $timestamp;
                        }
                    }
                }
                
                // Get Content (Robust Logic: Check File First)
            $fileSql = "SELECT original_filename FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE'";
            $fileStmt = db_query($this->conn, $fileSql, [$requestId]);
            $isLegacyFileUpload = false;
            $scriptContent = '';
            
            if ($fileStmt && $fileRow = db_fetch_array($fileStmt, DB_FETCH_ASSOC)) {
                // FILE FOUND: Force Mode to FILE_UPLOAD (even if DB says 'INPUT TEXT')
                $req['mode'] = 'FILE_UPLOAD'; 
                
                $rawName = $fileRow['original_filename'];
                // Clean filename: Remove uniqid prefix (e.g. 698016cd4d82f_File.xlsx OR 1700000000_File.xlsx)
                // Expanded regex to cover simple timestamps (10 digits) + uniqid (13+) + entropy (23+)
                $scriptContent = preg_replace('/^([a-f0-9]{10,})_/i', '', $rawName);
                
                $isLegacyFileUpload = true;
            } else {
                // Free input: format as "WA:\n[content]\n\nSMS:\n[content]"
                // [FIX] Fetch only the LATEST version per media channel (highest ID = most recent)
                $contentSql = "SELECT spc.* FROM script_preview_content spc
                    INNER JOIN (
                        SELECT media, MAX(id) as max_id
                        FROM script_preview_content
                        WHERE request_id = ?
                        GROUP BY media
                    ) latest ON spc.id = latest.max_id";
                $contentStmt = db_query($this->conn, $contentSql, [$requestId]);
                
                $contentParts = [];
                if ($contentStmt) {
                    while ($content = db_fetch_array($contentStmt, DB_FETCH_ASSOC)) {
                        $media = $content['media'];
                        
                        // Robust Cleaning: Decode -> Newlines -> Strip
                        $text = html_entity_decode($content['content']); // Decode &lt;p&gt; etc
                        
                        // [FIX] Remove revision markup: strikethrough/deletion spans entirely
                        $text = preg_replace('/<span[^>]*class="[^"]*deletion-span[^"]*"[^>]*>.*?<\/span>/is', '', $text);
                        // [FIX] Remove revision span wrappers but keep their text content
                        $text = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $text);
                        // [FIX] Remove any remaining red-colored spans (inline style)
                        $text = preg_replace('/<span[^>]*style="[^"]*color:\s*red[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $text);
                        // [FIX] Remove strikethrough text
                        $text = preg_replace('/<s>(.*?)<\/s>/is', '', $text);
                        $text = preg_replace('/<del>(.*?)<\/del>/is', '', $text);
                        
                        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
                        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text); 
                        $text = preg_replace('/<\/p>/i', "\n", $text);
                        $text = preg_replace('/<\/div>/i', "\n", $text);
                        
                        $text = strip_tags($text); 
                        $text = trim($text);      
                        
                        $contentParts[] = "$media:\n$text";
                    }
                }
                $scriptContent = implode("\n\n", $contentParts);
            }
            
            // Clean up potentially messy legacy content if forced to File Upload
            if ($isLegacyFileUpload && empty($scriptContent)) {
                 $scriptContent = 'File Attached'; 
            }
                
                // Get files for Legal/CX review (placeholder, you'll add upload logic later)
                $legalReview = '';
                $cxReview = '';
                $legalSyariah = '';
                $lpp = '';
                
                // Fetch document status with upload timestamps
                $docsSql = "SELECT file_type, uploaded_at FROM script_files WHERE request_id = ? AND file_type IN ('LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP')";
                $docsStmt = db_query($this->conn, $docsSql, [$requestId]);
                if ($docsStmt) {
                    while ($doc = db_fetch_array($docsStmt, DB_FETCH_ASSOC)) {
                        $uploadTs = '';
                        if (!empty($doc['uploaded_at'])) {
                            if ($doc['uploaded_at'] instanceof \DateTime) {
                                $uploadTs = $doc['uploaded_at']->format('Y-m-d H:i:s');
                            } else {
                                $uploadTs = (string)$doc['uploaded_at'];
                            }
                        }
                        $docLabel = $uploadTs ? 'Uploaded (' . $uploadTs . ')' : 'Uploaded';
                        if ($doc['file_type'] === 'LEGAL') $legalReview = $docLabel;
                        if ($doc['file_type'] === 'CX') $cxReview = $docLabel;
                        if ($doc['file_type'] === 'LEGAL_SYARIAH') $legalSyariah = $docLabel;
                        if ($doc['file_type'] === 'LPP') $lpp = $docLabel;
                    }
                }
                
                // Determine overall status
                $overallStatus = 'SUBMITTED';
                if ($req['status'] === 'DRAFT' || $req['status'] === 'DRAFT_TEMP') {
                    $overallStatus = ($req['status'] === 'DRAFT_TEMP') ? 'DRAFT_TEMP' : 'DRAFT';
                } elseif ($spvStatus || $picStatus || $procStatus) {
                    $overallStatus = 'WIP';
                }
                if ($procStatus === 'APPROVE_PROCEDURE') {
                    $overallStatus = 'CLOSED';
                }
                
                $exportData[] = [
                    'id' => $requestId, // Add id for detail links
                    'ticket_id' => $req['ticket_id'] ?? $requestId, // Use real Ticket ID
                    'script_number' => $req['script_number'],
                    'mode' => $req['mode'], // Add mode for view logic
                    'jenis' => $req['jenis'],
                    'produk' => $req['produk'],
                    'kategori' => $req['kategori'],
                    'status' => $overallStatus,
                    'raw_status' => $req['status'], // Raw DB status for PENDING_MAKER_CONFIRMATION check
                    'media' => $req['media'],
                    'script_content' => $scriptContent,
                    'created_date' => $req['created_at'] ? ($req['created_at'] instanceof DateTime ? $req['created_at']->format('Y-m-d H:i:s') : $req['created_at']) : '',
                    'updated_at' => $req['updated_at'] ? ($req['updated_at'] instanceof DateTime ? $req['updated_at']->format('Y-m-d H:i:s') : $req['updated_at']) : '',
                    'maker' => $maker,
                    'spv' => $spv,
                    'status_spv' => $spvStatus,
                    'timestamp_spv' => $spvTimestamp,
                    'pic' => $pic,
                    'status_pic' => $picStatus,
                    'timestamp_pic' => $picTimestamp,
                    'procedure' => $procedure,
                    'status_procedure' => $procStatus,
                    'timestamp_procedure' => $procTimestamp,
                    'legal_review' => $legalReview,
                    'cx_review' => $cxReview,
                    'legal_syariah' => $legalSyariah,
                    'lpp' => $lpp,
                    // Pass through document counts for granular Procedure status
                    'has_legal' => $req['has_legal'] ?? 0,
                    'has_cx' => $req['has_cx'] ?? 0,
                    'has_syariah' => $req['has_syariah'] ?? 0,
                    'has_lpp' => $req['has_lpp'] ?? 0
                ];
            }
        }
        
        return $exportData;
    }

    public function getRequestDetail($id) {
        // 1. Get Request Metadata
        $request = $this->getRequestById($id);
        if (!$request) return null;

        // 2. Get Audit Logs (Joined with User Info for Group Name)
        // [FIX] Use Regex to safely identify Version Suffix (-01, -02) vs Serial Number (-0030)
        // Format: ...-XXXX (Serial 4 digit) | ...-XXXX-VV (Version 2 digit)
        
        $scriptNumber = $request['script_number'] ?? '';
        $basePattern = $scriptNumber . '-%'; // Default match children if any
        $baseExact = $scriptNumber;
        
        // precise regex: ends with hyphen and 2 digits
        if (preg_match('/-(\d{2})$/', $scriptNumber)) {
            // It's a Child (Versioned) -> Strip suffix to get Parent
            $baseExact = preg_replace('/-(\d{2})$/', '', $scriptNumber);
            $basePattern = $baseExact . '-%';
        } 
        // Else: It's likely Parent (Serial) -> Use as is.

        // Query: Match EXACT Parent OR Match ANY Child
        $sql = "SELECT a.*, u.[GROUP] as group_name, u.FULLNAME as full_name, u.JOB_FUNCTION as job_function,
                       r.ticket_id as req_ticket_id, r.script_number as req_script_number
                FROM script_audit_trail a 
                OUTER APPLY (SELECT TOP 1 u2.[GROUP], u2.FULLNAME, u2.JOB_FUNCTION FROM tbluser u2 WHERE u2.USERID = a.user_id) u
                LEFT JOIN script_request r ON a.request_id = r.id
                WHERE (r.script_number = ? OR r.script_number LIKE ?)
                ORDER BY a.created_at ASC";
        
        $stmt = db_query($this->conn, $sql, [$baseExact, $basePattern]);
        $logs = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $logs[] = $row;
            }
        }

        // 3. Get Documents
        $sql = "SELECT * FROM script_files WHERE request_id = ?";
        $stmt = db_query($this->conn, $sql, [$id]);
        $files = [];
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $files[$row['file_type']] = $row;
            }
        }

        // 4. Get Script Content with Version History
        $content = [];
        
        if (($request['mode'] ?? '') === 'FILE_UPLOAD') {
            // File Upload Mode
            $content['type'] = 'file';
            
            if (isset($files['TEMPLATE'])) {
                $content['filename'] = $files['TEMPLATE']['original_filename'];
                $content['path'] = $files['TEMPLATE']['filepath'];
            }
            
            // Fetch ALL versions with metadata (ordered chronologically)
            $sql = "SELECT 
                        v.id,
                        v.media,
                        v.content,
                        v.workflow_stage,
                        v.created_by,
                        u.DEPT as creator_dept,
                        u.FULLNAME as creator_name,
                        CONVERT(varchar, v.created_at, 120) as formatted_date,
                        v.created_at
                    FROM script_preview_content v
                    OUTER APPLY (SELECT TOP 1 u2.DEPT, u2.FULLNAME FROM tbluser u2 WHERE u2.USERID = v.created_by) u
                    WHERE v.request_id IN (SELECT id FROM script_request WHERE script_number = ? OR script_number LIKE ?) 
                    ORDER BY v.created_at ASC, v.id ASC";
            
                // Match exact (Parent) OR pattern (Children) to get FULL content history
                $stmt = db_query($this->conn, $sql, [$baseExact, $basePattern]);
                
                $allVersions = [];
                if ($stmt) {
                    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                        $allVersions[] = $row; // Store raw rows
                    }
                }
            
            // GROUPING LOGIC: Group rows by timestamp (Tolerance: Same Minute)
            // Needed because Multi-Media Uploads create multiple rows (Email, Whatsapp)
            $groupedVersions = [];
            foreach ($allVersions as $ver) {
                $ts = $ver['formatted_date'] ?? ($ver['created_at'] instanceof DateTime ? $ver['created_at']->format('Y-m-d H:i:s') : $ver['created_at']);
                $key = substr($ts, 0, 16); // Minute Grouping
                $key .= '_' . $ver['workflow_stage']; 
                
                if (!isset($groupedVersions[$key])) {
                     $groupedVersions[$key] = [
                         'meta' => $ver,
                         'sheets' => []
                     ];
                }
                $groupedVersions[$key]['sheets'][] = $ver;
            }

            // Synthesize Final Versions (Tabbed)
            $finalVersions = [];
            $vCounter = 1;

            foreach ($groupedVersions as $group) {
                $meta = $group['meta'];
                $sheets = $group['sheets'];
                
                // Construct HTML for this version
                $uniqueId = time() . rand(1000, 9999);
                $html = '<div class="sheet-container" id="container-' . $uniqueId . '">';
                
                // [FIX V2] CROSS-SHEET CONTAMINATION FIX
                // Check if content already contains tabs (Corrupt/Formatted Data)
                // Only use "pre-built tabs" shortcut if ALL sheets have IDENTICAL content
                // (meaning they were saved with the old duplication bug).
                // If sheets have different content, render them normally with generated tabs.
                $hasPrebuiltTabs = false;
                if (!empty($sheets) && isset($sheets[0]['content']) && strpos($sheets[0]['content'], 'sheet-tabs-nav') !== false) {
                    // Check if ALL sheets are identical (contamination symptom)
                    $allIdentical = true;
                    $firstHash = md5($sheets[0]['content']);
                    foreach ($sheets as $s) {
                        if (md5($s['content']) !== $firstHash) {
                            $allIdentical = false;
                            break;
                        }
                    }
                    
                    if ($allIdentical) {
                        // All sheets have same content = OLD BUG contamination
                        // Use first sheet only (it has the full combined HTML with internal tabs)
                        $hasPrebuiltTabs = true;
                    }
                    // If sheets are different, fall through to standard tab generation
                }

                if ($hasPrebuiltTabs) {
                     // Deduplicated: Use First Row Only (all were identical)
                     $html .= $sheets[0]['content'];
                } else {
                    // Standard Logic: Generate Tabs for each sheet/media
                    
                    // TABS HEADER
                    $html .= '<div class="sheet-tabs-nav" contenteditable="false" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">';
                    foreach ($sheets as $idx => $sheet) {
                        $activeClass = ($idx === 0) ? 'active' : '';
                        $sheetName = htmlspecialchars($sheet['media']); // e.g. "EMAIL"
                        $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                        $btnId = 'btn-' . $sheetId;
                        
                        // Icon logic
                        $icon = '<i class="bi-file-text"></i>';
                        if (stripos($sheetName, 'WHATSAPP') !== false) $icon = '<i class="bi-whatsapp"></i>';
                        elseif (stripos($sheetName, 'EMAIL') !== false) $icon = '<i class="bi-envelope"></i>';
                        
                        $html .= "<button type='button' id='$btnId' class='btn-sheet $activeClass' onclick=\"changeSheet('$sheetId')\" style='display:flex; align-items:center; gap:6px;'>$icon $sheetName</button>";
                    }
                    $html .= '</div>';
                    
                    // CONTENT PANES
                    foreach ($sheets as $idx => $sheet) {
                        $displayStyle = ($idx === 0) ? 'block' : 'none';
                        $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                        
                        // For File Upload, content IS HTML string (from FileHandler)
                        // We must output it as is.
                        $contentSafe = $sheet['content']; 
                        
                        $html .= "<div id='$sheetId' class='sheet-pane' data-sheet-name='" . htmlspecialchars($sheet['media']) . "' contenteditable='false' style='display:$displayStyle;'>";
                        $html .= $contentSafe;
                        $html .= "</div>";
                    }
                }
                $html .= '</div>';

                $finalVersions[] = [
                    'id' => $meta['id'],
                    'version_number' => $vCounter++,
                    'workflow_stage' => $meta['workflow_stage'],
                    'created_by' => $meta['created_by'],
                    'user_dept' => $meta['creator_dept'],
                    'user_full_name' => $meta['creator_name'],
                    'formatted_date' => $meta['formatted_date'],
                    'content' => $html
                ];
            }
            
            $content['versions'] = $finalVersions;
            
            // Keep backward compatibility: Also provide latest version in old format
            if (!empty($versions)) {
                $latestVersion = end($versions);
                $content['html_preview'] = $latestVersion['content'];
            }
            
        } else {
            // Free Input Mode - Get ALL versions grouped by media
            // [FIX] Use COALESCE to pick up BOTH legacy rows (updated_by/updated_at)
            // AND new versioned rows from approval (created_by/created_at)
            $sql = "SELECT 
                        v.id,
                        v.media,
                        v.content,
                        v.workflow_stage,
                        COALESCE(v.created_by, v.updated_by) as version_user,
                        u.DEPT as user_dept,
                        u.FULLNAME as user_full_name,
                        CONVERT(varchar, COALESCE(v.created_at, v.updated_at), 120) as formatted_date,
                        COALESCE(v.created_at, v.updated_at) as sort_date
                    FROM script_preview_content v
                    OUTER APPLY (SELECT TOP 1 u2.DEPT, u2.FULLNAME FROM tbluser u2 WHERE u2.USERID = COALESCE(v.created_by, v.updated_by)) u
                    WHERE v.request_id IN (SELECT id FROM script_request WHERE script_number = ? OR script_number LIKE ?)
                    ORDER BY COALESCE(v.created_at, v.updated_at) ASC, v.id ASC";
            
            $stmt = db_query($this->conn, $sql, [$baseExact, $basePattern]);
            $allVersions = [];
            
            if ($stmt === false) {
                // Query failed - log error for debugging
                $errors = db_errors();
                error_log("Free Input Query Failed for request_id=$id: " . print_r($errors, true));
            } elseif ($stmt) {
                while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                    $allVersions[] = $row;
                }
            }
            
            // GROUPING LOGIC: Group rows by timestamp + workflow_stage
            // This treats multiple sheets saved close together as ONE version
            $groupedVersions = [];
            foreach ($allVersions as $ver) {
                // Get timestamp string
                $ts = $ver['formatted_date'] ?? '';
                
                // Truncate to Minute (First 16 chars: "YYYY-MM-DD HH:MM")
                $key = substr($ts, 0, 16);
                
                // Also group by workflow_stage if available (e.g. APPROVED_SPV vs APPROVED_PIC)
                $stage = $ver['workflow_stage'] ?? 'DRAFT';
                $key .= '_' . $stage;
                
                if (!isset($groupedVersions[$key])) {
                     $groupedVersions[$key] = [
                         'meta' => $ver,
                         'sheets' => []
                     ];
                }
                $groupedVersions[$key]['sheets'][] = $ver;
            }

            // Synthesize Final Versions Array with HTML Content (Tabs)
            $finalVersions = [];
            $vCounter = 1;

            foreach ($groupedVersions as $group) {
                $meta = $group['meta'];
                $sheets = $group['sheets'];
                
                // Construct HTML for this version (Tabbed Interface)
                $uniqueId = time() . rand(1000, 9999);
                $html = '<div class="sheet-container" id="container-' . $uniqueId . '">';
                
                // TABS HEADER
                $html .= '<div class="sheet-tabs-nav" contenteditable="false" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">';
                foreach ($sheets as $idx => $sheet) {
                    $activeClass = ($idx === 0) ? 'active' : '';
                    $sheetName = htmlspecialchars($sheet['media']);
                    $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                    $btnId = 'btn-' . $sheetId;
                    
                    // Icon logic
                    $icon = '<i class="bi-file-text"></i>';
                    if (stripos($sheetName, 'WHATSAPP') !== false) $icon = '<i class="bi-whatsapp"></i>';
                    elseif (stripos($sheetName, 'EMAIL') !== false) $icon = '<i class="bi-envelope"></i>';
                    
                    $html .= "<button type='button' id='$btnId' class='btn-sheet $activeClass' onclick=\"changeSheet('$sheetId')\" style='display:flex; align-items:center; gap:6px;'>$icon $sheetName</button>";
                }
                $html .= '</div>';
                
                // CONTENT PANES
                foreach ($sheets as $idx => $sheet) {
                    $displayStyle = ($idx === 0) ? 'block' : 'none';
                    $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                    $contentSafe = trim($sheet['content']); // Already HTML or text
                    
                    // Preserve whitespace for text
                    $html .= "<div id='$sheetId' class='sheet-pane' data-sheet-name='" . htmlspecialchars($sheet['media']) . "' contenteditable='false' style='display:$displayStyle; padding:20px; font-family:\"Inter\", sans-serif; white-space:pre-line;'>";
                    $html .= $contentSafe;
                    $html .= "</div>";
                }
                $html .= '</div>';

                // Add to list
                $finalVersions[] = [
                    'id' => $meta['id'],
                    'version_number' => $vCounter++,
                    'workflow_stage' => $meta['workflow_stage'] ?? null,
                    'created_by' => $meta['version_user'],
                    'user_dept' => $meta['user_dept'],
                    'user_full_name' => $meta['user_full_name'],
                    'formatted_date' => $meta['formatted_date'],
                    'content' => $html
                ];
            }

            $content['type'] = 'text';
            $content['versions'] = $finalVersions;
            $content['all_versions'] = $allVersions; // Keep raw for reference if needed
            
            // Keep backward compatibility: Also provide latest version per media in old format
            $latestByMedia = [];
            foreach ($allVersions as $version) {
                $media = $version['media'];
                // Keep only the latest for each media (since ordered by created_at ASC, last one wins)
                $latestByMedia[$media] = $version;
            }
            
            $content['data'] = array_values($latestByMedia);
            
            // FALLBACK: If no preview content exists, check multiple sources for legacy data
            if (empty($content['data'])) {
                // 1. Check script_library (for published/approved scripts)
                $sqlLib = "SELECT media, content 
                           FROM script_library 
                           WHERE request_id = ? 
                           ORDER BY created_at DESC";
                $stmtLib = db_query($this->conn, $sqlLib, [$id]);
                $libraryContent = [];
                
                if ($stmtLib) {
                    while ($row = db_fetch_array($stmtLib, DB_FETCH_ASSOC)) {
                        $libraryContent[] = $row;
                    }
                }
                
                if (!empty($libraryContent)) {
                    $content['data'] = $libraryContent;
                } else {
                    // 2. Check legacy columns in script_request (for very old data)
                    // If request has 'content' or 'script_content' column
                    $legacyContent = [];
                    
                    if (isset($request['content']) && !empty($request['content'])) {
                        $legacyContent[] = [
                            'media' => $request['media'] ?? 'Legacy',
                            'content' => $request['content']
                        ];
                    } elseif (isset($request['script_content']) && !empty($request['script_content'])) {
                        $legacyContent[] = [
                            'media' => $request['media'] ?? 'Legacy',
                            'content' => $request['script_content']
                        ];
                    }
                    
                    $content['data'] = $legacyContent;
                }
            }
        }

        return [
            'request' => $request,
            'logs' => $logs,
            'files' => $files,
            'content' => $content
        ];
    }

    public function deletePreviewContent($scriptId) {
        $sql = "DELETE FROM script_preview_content WHERE request_id = ?";
        return db_query($this->conn, $sql, [$scriptId]);
    }
    public function deleteDraft($requestId) {
        // [DATA INTEGRITY] First, cleanup orphan records to prevent database bloating
        db_query($this->conn, "DELETE FROM script_preview_content WHERE request_id = ?", [$requestId]);
        db_query($this->conn, "DELETE FROM script_files WHERE request_id = ?", [$requestId]);
        db_query($this->conn, "DELETE FROM script_audit_trail WHERE request_id = ?", [$requestId]);
        
        // Finally delete the main record
        $sql = "DELETE FROM script_request WHERE id = ? AND (status IN ('DRAFT', 'DRAFT_TEMP') OR has_draft = 1)";
        return db_query($this->conn, $sql, [$requestId]);
    }
    
    public function getLibraryScripts() {
        // Get all published/approved scripts for library browser
        $sql = "SELECT 
                    id, 
                    script_number, 
                    title, 
                    jenis, 
                    produk, 
                    kategori, 
                    media,
                    created_at,
                    updated_at
                FROM script_request 
                WHERE status = 'APPROVED' 
                  AND ticket_id IS NOT NULL
                ORDER BY updated_at DESC";
        
        $stmt = db_query($this->conn, $sql);
        $items = [];
        
        if ($stmt) {
            while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
                $items[] = $row;
            }
        }
        
        return $items;
    }

    public function toggleScriptActivation($requestId, $isActive, $userId, $startDate = null) {
        // Toggle is_active for ALL library items belonging to this Request ID
        // (Since a request can have multiple rows for different media)
        
        $status = $isActive ? 1 : 0;
        
        $sql = "";
        $params = [];
        
        if ($status === 1) {
            // Activate — also set start_date
            if ($startDate) {
                $sql = "UPDATE script_library 
                        SET is_active = 1, 
                            start_date = ?,
                            activated_at = GETDATE(), 
                            activated_by = ? 
                        WHERE request_id = ?";
                $params = [$startDate, $userId, $requestId];
            } else {
                $sql = "UPDATE script_library 
                        SET is_active = 1, 
                            start_date = GETDATE(),
                            activated_at = GETDATE(), 
                            activated_by = ? 
                        WHERE request_id = ?";
                $params = [$userId, $requestId];
            }
        } else {
            // Deactivate (preserve activation history)
            $sql = "UPDATE script_library 
                    SET is_active = 0 
                    WHERE request_id = ?";
            $params = [$requestId];
        }
        
        return db_query($this->conn, $sql, $params);
    }
    public function updateActiveStatus($requestId, $isActive, $startDate = null, $userId = null) {
        // Check if library entry exists
        $checkSql = "SELECT TOP 1 1 FROM script_library WHERE request_id = ?";
        $check = db_query($this->conn, $checkSql, [$requestId]);
        
        $exists = false;
        if ($check && db_fetch_array($check)) {
            $exists = true;
        }

        if (!$exists) {
            $this->finalizeLibrary($requestId);
        }

        $params = [$isActive];
        
        if ($isActive == 1) {
            // Activating: save start_date, activated_at, activated_by
            $sql = "UPDATE script_library SET is_active = ?, 
                    start_date = ?, 
                    activated_at = GETDATE(), 
                    activated_by = ? 
                    WHERE request_id = ?";
            $params[] = $startDate ?: date('Y-m-d'); // fallback to today
            $params[] = $userId ?: ($_SESSION['user']['userid'] ?? 'UNKNOWN');
            $params[] = $requestId;
        } else {
            // Deactivating: keep activation history, only flip status
            $sql = "UPDATE script_library SET is_active = ? WHERE request_id = ?";
            $params[] = $requestId;
        }

        return db_query($this->conn, $sql, $params);
    }


    public function getLibraryItemByRequestId($requestId) {
        $sql = "SELECT * FROM script_library WHERE request_id = ?";
        $stmt = db_query($this->conn, $sql, [$requestId]);
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            return $row;
        }
        return null;
    }

    public function getSqlServerTodayDate() {
        $sql = "SELECT CAST(GETDATE() AS DATE) as today_date";
        $stmt = db_query($this->conn, $sql);
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            return $row['today_date'];
        }
        return date('Y-m-d'); // fallback
    }
}
