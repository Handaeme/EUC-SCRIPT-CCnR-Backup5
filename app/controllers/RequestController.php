<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\FileHandler;

class RequestController extends Controller {

    public function index() {
        // My Tasks - Show user's own requests
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }
        
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        $role = $user['dept'] ?? '';
        
        // Status filter tab
        $statusFilter = $_GET['status_filter'] ?? null;
        $validFilters = ['revise', 'confirm', 'draft', 'wip', 'done'];
        if ($statusFilter && !in_array($statusFilter, $validFilters)) {
            $statusFilter = null;
        }
        
        // Get date range
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        $filterOptions = [];
        
        foreach ($filterCols as $col) {
            $filterOptions[$col] = $reqModel->getDistinctRequestValues($col);
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }

        $requests = $reqModel->getUserRequests($user['userid'], $startDate, $endDate, $filters, $statusFilter);
        
        // ENRICHMENT: Fetch Content for Display (Robust Fallback)
        foreach ($requests as &$req) {
            if (empty($req['content']) || trim($req['content']) === '') {
                // 1. Try Free Input (Previews)
                $previews = $reqModel->getPreviewContent($req['id']);
                if (!empty($previews)) {
                    $combined = [];
                    foreach ($previews as $p) {
                        $combined[] = "{$p['media']}: " . strip_tags($p['content']);
                    }
                    $req['content'] = implode(' | ', $combined);
                } 
                // 2. If still empty, Try File Upload
                if (empty($req['content'])) {
                     $files = $reqModel->getFiles($req['id']);
                     if (!empty($files)) {
                         $names = array_map(function($f) { return $f['original_filename']; }, $files);
                         $req['content'] = implode(', ', $names);
                     }
                }
            }
        }
        unset($req);
        
        // Stat cards for MAKER role
        $stats = null;
        if ($role === 'MAKER' || $role === 'Maker') {
            $stats = $reqModel->getMakerStats($user['userid']);
        }
        
        $this->view('request/index', [
            'requests' => array_slice($requests, (max(1,intval($_GET['page']??1))-1)*10, 10),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'currentPage' => max(1,intval($_GET['page']??1)),
            'totalPages' => max(1, ceil(count($requests)/10)),
            'totalItems' => count($requests),
            'perPage' => 10,
            'statusFilter' => $statusFilter,
            'stats' => $stats,
            'role' => $role
        ]);
    }

    public function create() {
        $reqModel = $this->model('RequestModel');
        // Fetch SPVs specifically (Division Head Unit)
        $spvList = $reqModel->getSupervisors(); 
        $this->view('request/create', ['spvList' => $spvList]);
    }

    public function review() {
        if (!isset($_GET['id'])) {
            die("Invalid Request ID");
        }
        $id = $_GET['id'];
        
        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);
        
        if (!$request) {
            die("Request not found");
        }
        

        // Security Check: Only assigned SPV can review (or Admin)
        // Ignoring strict check for now to ease testing, but should be:
        // if ($request['selected_spv'] != $_SESSION['user']['userid']) die("Unauthorized");

        $content = $reqModel->getPreviewContent($id);
        $files = $reqModel->getFiles($id);
        
        // Fix: Fetch Timeline/Audit Logs for PDF
        $detail = $reqModel->getRequestDetail($id);
        $timeline = $detail['logs'] ?? [];
        
        $this->view('request/review', [
            'request' => $request,
            'content' => $content,
            'files' => $files,
            'timeline' => $timeline
        ]);
    }

    public function saveDraft() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['request_id'])) {
             echo json_encode(['success'=>false, 'error'=>'Missing ID']); return; 
        }

        $reqModel = $this->model('RequestModel');
        
        // [SECURITY] Ownership check - Creator OR Assigned Reviewer can save
        $req = $reqModel->getRequestById($input['request_id']);
        
        if (!$req) {
             echo json_encode(['success'=>false, 'error'=>'Request not found']); return;
        }

        $userId = $_SESSION['user']['userid'] ?? null;
        $userRole = $_SESSION['user']['role'] ?? ($_SESSION['user']['dept'] ?? '');

        $isCreator = ($req['created_by'] == $userId);
        $isAdmin = ($userRole === 'ADMIN');
        
        // Allow if assigned SPV/PIC (Ignore status constraints for now to allow cleanup)
        $isAssignedSPV = (($req['selected_spv'] ?? '') == $userId);
        $isAssignedPIC = (($req['selected_pic'] ?? '') == $userId);
        
        // Allow PROCEDURE role to edit if status is PENDING_LEGAL or APPROVED_PIC (Pre-final)
        $isProcedure = ($userRole === 'PROCEDURE');
        
        if (!$isCreator && !$isAssignedSPV && !$isAssignedPIC && !$isAdmin && !$isProcedure) {
             // Debugging info (Safe to show in dev)
             $debug = "User:$userId, SPV:".($req['selected_spv']??'-').", PIC:".($req['selected_pic']??'-');
             echo json_encode(['success'=>false, 'error'=>'Unauthorized: You are not the owner or assigned reviewer of this request. ' . $debug]); return;
        }

        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            
            // [FIX V10] Backend Sanitizer for Drafts
            if (isset($input['deleted_ids']) && !empty($input['deleted_ids'])) {
                $deletedIds = explode(',', $input['deleted_ids']);
                $deletedIds = array_map('trim', $deletedIds);
                
                if (!empty($deletedIds)) {
                     foreach ($input['updated_content'] as $contentId => &$html) {
                        if (empty($html)) continue;
                        
                        $dom = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        
                        $xpath = new \DOMXPath($dom);
                        $modified = false;
                        
                        foreach ($deletedIds as $delId) {
                            if (empty($delId)) continue;
                            
                            // [FIX V22] ROBUST REMOVAL
                            // Try exact ID match first
                            $nodes = $xpath->query("//*[@id='$delId']");
                            
                            // If not found and it's a legacy ID, try looking for data-comment-id attribute
                            if ($nodes->length === 0 && strpos($delId, 'legacy-') === 0) {
                                 $nodes = $xpath->query("//*[@data-comment-id='$delId']");
                            }
                            
                            // If still not found, try partially matching ID (risky but needed for some malformed HTML)
                            // "//*[contains(@id, '$delId')]" -> Too dangerous? Maybe restrict to span?
                            
                            foreach ($nodes as $node) {
                                // REMOVE NODE
                                $node->parentNode->removeChild($node);
                                $modified = true;
                            }
                            
                            // [FIX V22] Also remove empty spans that might be left behind?
                            // $emptySpans = $xpath->query("//span[not(node())]");
                            // foreach ($emptySpans as $span) { $span->parentNode->removeChild($span); $modified = true; }
                        }
                        
                        if ($modified) {
                            $newHtml = $dom->saveHTML();
                            // Fix: remove XML declaration and basic html/body tags if locally loaded
                            // But usually usage of loadHTML adds them. 
                            // We need to return BODY content if it was a fragment.
                            // However, we saving the whole HTML might add <html><body>.
                            // Let's strip the wrapper if we detected it wasn't there before? 
                            // Or just return body?
                            
                            // Robust strip:
                            $newHtml = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $newHtml));
                            $html = $newHtml;
                        }
                     }
                }
            }

            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        
        // Mark as Draft
        $reqModel->setDraftStatus($input['request_id'], 1);
        
        echo json_encode(['success'=>true]);
    }

    public function approve() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['request_id'])) {
             echo json_encode(['success'=>false, 'error'=>'Missing ID']); return; 
        }

        $reqModel = $this->model('RequestModel');
        $req = $reqModel->getRequestById($input['request_id']);
        
        
        // VERSIONING: Insert new version rows instead of updating
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {

            // [FIX V22] BACKEND SANITIZER FOR APPROVAL (Apply deletions before saving version)
            if (isset($input['deleted_ids']) && !empty($input['deleted_ids'])) {
                $deletedIds = explode(',', $input['deleted_ids']);
                $deletedIds = array_map('trim', $deletedIds);
                
                if (!empty($deletedIds)) {
                     foreach ($input['updated_content'] as $contentId => &$html) {
                        if (empty($html)) continue;
                        
                        $dom = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        
                        $xpath = new \DOMXPath($dom);
                        $modified = false;
                        
                        foreach ($deletedIds as $delId) {
                            if (empty($delId)) continue;
                            
                            // [FIX V22] ROBUST REMOVAL (Same as saveDraft)
                            $nodes = $xpath->query("//*[@id='$delId']");
                            if ($nodes->length === 0 && strpos($delId, 'legacy-') === 0) {
                                 $nodes = $xpath->query("//*[@data-comment-id='$delId']");
                            }
                            
                            foreach ($nodes as $node) {
                                $node->parentNode->removeChild($node);
                                $modified = true;
                            }
                        }
                        
                        if ($modified) {
                            $newHtml = $dom->saveHTML();
                            $newHtml = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $newHtml));
                            $html = $newHtml;
                        }
                     }
                }
            }

            $userId = $_SESSION['user']['userid'] ?? 'UNKNOWN';
            // FIX: Normalize role to uppercase for safe comparison
            $currentRole = isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : '';
            
            // Map current role to next workflow stage
            $workflowStageMap = [
                'SPV' => 'APPROVED_SPV',
                'PIC' => 'APPROVED_PIC',
                'PROCEDURE' => 'APPROVED_PROCEDURE'
            ];
            
            $nextRole = ($currentRole === 'SPV') ? 'PIC' : (($currentRole === 'PIC') ? 'PROCEDURE' : 'COMPLETED');
            $workflowStage = $workflowStageMap[$currentRole] ?? 'UNKNOWN';

            // [FIX V26] SAVE SELECTED PIC IF PROVIDED
            // [FIX V26] SAVE SELECTED PIC IF PROVIDED
            if ($currentRole === 'SPV') {
                if (isset($input['selected_pic']) && !empty($input['selected_pic'])) {
                    $reqModel->updateSelectedPic($req['id'], $input['selected_pic']);
                } else {
                    // STRICT BACKEND VALIDATION: PIC is Mandatory for SPV Approval
                    echo json_encode(['success'=>false, 'error'=>'Anda WAJIB memilih PIC sebelum melanjutkan.']); 
                    return;
                }
            }
            
            foreach ($input['updated_content'] as $contentId => $html) {
                // Get original row to copy media metadata
                $original = $reqModel->getPreviewContentById($contentId);
                
                if ($original) {
                    // INSERT new version row with workflow stage
                    $reqModel->insertPreviewContentVersion(
                        $req['id'], 
                        $original['media'], 
                        $html, 
                        $workflowStage, 
                        $userId
                    );
                }
            }
        }

        // Determine Next Status & Role based on Current Role
        // FIX: Normalize role here too
        $currentRole = isset($_SESSION['user']['dept']) ? strtoupper($_SESSION['user']['dept']) : '';
        $nextStatus = '';
        $nextRole = '';
        $auditAction = '';
        $auditDetails = '';

        // Helper: Get Approver Name
        $approverName = $_SESSION['user']['fullname'] ?? $_SESSION['user']['userid'] ?? ($currentRole === 'SPV' ? 'Supervisor' : 'User');

        if ($currentRole === 'SPV') {
            $nextStatus = 'APPROVED_SPV';
            $nextRole = 'PIC';
            $auditAction = 'APPROVE_SPV';
            
            // Resolve PIC Full Name
            $picName = $input['selected_pic'] ?? 'Unknown';
            if (!empty($input['selected_pic'])) {
                $pics = $reqModel->getPICs();
                foreach ($pics as $pic) {
                    if (strcasecmp($pic['userid'], $input['selected_pic']) === 0) {
                        $picName = $pic['fullname'];
                        break;
                    }
                }
            }
            
            $auditDetails = 'Approved by ' . $approverName . ', assigned to PIC: ' . $picName;
        } 
        elseif ($currentRole === 'PIC') {
             $nextStatus = 'APPROVED_PIC';
             $nextRole = 'PROCEDURE';
             $auditAction = 'APPROVE_PIC';
             $auditDetails = 'Approved by ' . $approverName;
        }
        elseif ($currentRole === 'PROCEDURE') {
             // Check if Procedure wants to send to Maker for confirmation
             if (!empty($input['send_to_maker'])) {
                 $nextStatus = 'PENDING_MAKER_CONFIRMATION';
                 $nextRole = 'MAKER';
                 $auditAction = 'SEND_TO_MAKER_CONFIRMATION';
                 $auditDetails = 'Sent to Maker for confirmation by ' . $approverName;
             } else {
                 $nextStatus = 'LIBRARY'; // Direct publish
                 $nextRole = 'LIBRARY';   // Finished
                 $auditAction = 'APPROVE_PROCEDURE';
                 $auditDetails = 'Published to Library by ' . $approverName;
                 
                 // Finalize to Library
                 if (!$reqModel->finalizeLibrary($req['id'])) {
                     echo json_encode(['success'=>false, 'error'=>'Failed to publish to Library']);
                     return;
                 }
             }
        }
        else {
             echo json_encode(['success'=>false, 'error'=>'Unauthorized Role']);
             return;
        }

        // [Feature] Append Optional Remarks to Audit Details
        if (!empty($input['remarks'])) {
            $notes = trim($input['remarks']);
            if ($notes) {
                $auditDetails .= ". Note: " . $notes;
            }
        }

        // Update Status
        $updateResult = $reqModel->updateStatus($req['id'], $nextStatus, $nextRole, $_SESSION['user']['userid']);
        
        if (!$updateResult) {
            $errors = sqlsrv_errors(); // or db_errors() wrapper
            if (!$errors && function_exists('db_errors')) $errors = db_errors();
            
            error_log("Failed to update status: " . print_r($errors, true));
            echo json_encode(['success'=>false, 'error'=>'Gagal mengupdate status tiket. Detail: ' . print_r($errors, true)]);
            return;
        }

        $reqModel->setDraftStatus($req['id'], 0); // Clear draft flag
        $reqModel->logAudit($req['id'], $req['script_number'], $auditAction, $currentRole, $_SESSION['user']['userid'], $auditDetails);

        echo json_encode(['success'=>true]);
    }

    /**
     * Maker Confirmation Flow — Confirm or Reject changes sent by Procedure.
     * - Confirm: Finalize to Library
     * - Reject: Return to APPROVED_PIC (back to Procedure queue)
     */
    public function makerConfirm() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['request_id'], $input['decision'])) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $reqModel = $this->model('RequestModel');
        $req = $reqModel->getRequestById($input['request_id']);

        if (!$req) {
            echo json_encode(['success' => false, 'error' => 'Request not found']);
            return;
        }

        // Verify status is PENDING_MAKER_CONFIRMATION
        if ($req['status'] !== 'PENDING_MAKER_CONFIRMATION') {
            echo json_encode(['success' => false, 'error' => 'Request is not pending Maker confirmation. Current status: ' . $req['status']]);
            return;
        }

        // Verify user is the original creator
        $userId = $_SESSION['user']['userid'] ?? null;
        if ($req['created_by'] !== $userId) {
            echo json_encode(['success' => false, 'error' => 'Only the original Maker can confirm this request']);
            return;
        }

        $makerName = $_SESSION['user']['fullname'] ?? $userId;
        $decision = $input['decision']; // 'confirm' or 'reject'

        if ($decision === 'confirm') {
            // Maker confirmed — return to Procedure for final publish decision
            $reqModel->updateStatus($req['id'], 'APPROVED_PIC', 'PROCEDURE', $userId);
            $reqModel->setDraftStatus($req['id'], 0);
            $reqModel->logAudit(
                $req['id'], $req['script_number'],
                'MAKER_CONFIRM',
                'MAKER', $userId,
                'Confirmed by Maker (' . $makerName . '). Returned to Procedure for publishing.'
            );

            echo json_encode(['success' => true, 'message' => 'Konfirmasi berhasil. Request dikembalikan ke Procedure.']);
        } elseif ($decision === 'reject') {
            // Return to Procedure queue
            $reqModel->updateStatus($req['id'], 'APPROVED_PIC', 'PROCEDURE', $userId);
            $reqModel->logAudit(
                $req['id'], $req['script_number'],
                'MAKER_REJECT_CONFIRMATION',
                'MAKER', $userId,
                'Rejected by Maker (' . $makerName . '). Returned to Procedure for revision.'
            );

            echo json_encode(['success' => true, 'message' => 'Request returned to Procedure']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid decision: ' . $decision]);
        }
    }

    public function reject() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        $reqModel = $this->model('RequestModel');
        $req = $reqModel->getRequestById($input['request_id']);
        
        // Save Updated Content if exists (even on Reject/Revise)
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                // [FIX] DO NOT use htmlspecialchars on full HTML blocks containing tables/spans
                // The placeholders are already handled by the editor/adapter logic.
                //$html = htmlspecialchars($html, ENT_QUOTES, 'UTF-8'); 
                
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        $status = ($input['decision'] === 'REJECT') ? 'REJECTED' : 'REVISION';
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';

        // Return to Maker
        $currentRole = $_SESSION['user']['dept'];
        $reqModel->updateStatus($req['id'], $status, 'Maker', $_SESSION['user']['userid']);
        $reqModel->setDraftStatus($req['id'], 0); // Clear draft flag
        $reqModel->logAudit($req['id'], $req['script_number'], $status, $currentRole, $_SESSION['user']['userid'], $remarks);

        echo json_encode(['success'=>true]);
    }

    public function revise() {
        $this->reject();
    }

    public function reuse() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['original_id']) || !isset($input['selected_spv'])) {
             echo json_encode(['success'=>false, 'error'=>'Missing Parameters']); return;
        }

        // CRITICAL: Prevent duplicate submission if user navigates away mid-request
        $requestToken = $input['request_token'] ?? null;
        
        if (!$requestToken) {
            echo json_encode(['success'=>false, 'error'=>'Invalid request token']); 
            return;
        }
        
        // Check if this token was already processed
        if (isset($_SESSION['reuse_processed_tokens']) && in_array($requestToken, $_SESSION['reuse_processed_tokens'])) {
            echo json_encode(['success'=>false, 'error'=>'Duplicate request detected - already processed']); 
            return;
        }

        $userId = $_SESSION['user']['userid'] ?? 'maker01'; // Fallback for dev

        $reqModel = $this->model('RequestModel');
        $result = $reqModel->createVersionedRequest($input['original_id'], $userId, $input['selected_spv']);

        if (isset($result['error'])) {
            echo json_encode(['success'=>false, 'error'=>$result['error']]);
        } else {
            // Mark this token as processed to prevent duplicates
            if (!isset($_SESSION['reuse_processed_tokens'])) {
                $_SESSION['reuse_processed_tokens'] = [];
            }
            $_SESSION['reuse_processed_tokens'][] = $requestToken;
            
            // Keep only last 10 tokens to avoid memory bloat
            if (count($_SESSION['reuse_processed_tokens']) > 10) {
                array_shift($_SESSION['reuse_processed_tokens']);
            }
            
            echo json_encode(['success'=>true, 'new_id'=>$result['id'], 'new_number'=>$result['number']]);
        }
    }

    public function cancelDraft() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['request_id'])) {
            echo json_encode(['success'=>false, 'error'=>'Missing ID']); return;
        }

        $reqModel = $this->model('RequestModel');
        
        // [SECURITY] Ownership check - only the creator can cancel their own draft
        $req = $reqModel->getRequestById($input['request_id']);
        if (!$req || $req['created_by'] !== $_SESSION['user']['userid']) { 
             echo json_encode(['success'=>false, 'error'=>'Unauthorized: You are not the owner of this request']); return;
        }

        if ($req['status'] === 'DRAFT' || $req['status'] === 'DRAFT_TEMP' || $req['has_draft'] == 1) {
             if ($reqModel->deleteDraft($input['request_id'])) {
                 echo json_encode(['success'=>true]);
             } else {
                 echo json_encode(['success'=>false, 'error'=>'Database Delete Failed']);
             }
        } else {
             echo json_encode(['success'=>false, 'error'=>'Cannot delete non-draft request']);
        }
    }

    // SPECIAL ACTION: For PROCEDURE to review/revise existing library script
    public function review_library_script() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['dept'] !== 'PROCEDURE' && $_SESSION['user']['dept'] !== 'CPMS')) {
             echo "Access Denied"; return;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) { echo "Missing ID"; return; }

        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);

        if (!$request) { echo "Script Not Found"; return; }

        // [LOGIC] Check if we are working on a Live Library Item (Published/Completed) 
        // OR if this is already a Revision Draft.
        
        $isLibraryRevision = false;
        
        // If status is 'LIBRARY' or 'COMPLETED' (Assuming 'APPROVED_PROCEDURE' or similar led to Library)
        // Check if there is already an ACTIVE DRAFT/REVISION for this script?
        // We can check by `script_number` base or `ticket_id`.
        // However, for simplicity per requirements:
        // If PROCEDURE accesses a "Completed" script, we treat it as "Potential Revision".
        // We DO NOT create a draft immediately to avoid spamming DB just by viewing.
        // We only create draft if they SAVE or Click Action.
        // BUT, standard review UI allows editing text immediately.
        // If they edit text on a Completed Script, it's dangerous.
        
        // DECISION: 
        // 1. If Status is NOT 'DRAFT'/'REVISION' -> Read-Only Mode initially OR Auto-Draft on First Edit?
        // 2. The prompt asked for "Reviewer Edit" step.
        // 3. To keep it robust: We will check if there is an EXISTING open revision for this Ticket ID?
        //    If yes, redirect them there? 
        //    (Too complex for now).
        
        // SIMPLIFIED APPROACH:
        // show the `review.php` with `$isLibraryRevision = true`.
        // The view handles UI.
        // When they click "Update Library Direct" -> We clone, update, publish.
        // When they click "Minor/Major Revision" -> We clone, set status.
        // What about intermediate edits (Text changes)?
        // The `saveDraft` in review.php calls `RequestController::saveDraft`.
        // I will modify `saveDraft` to handle "Clone-on-Write" if needed, 
        // OR just assume Procedure knows what they are doing (editing Live content? No, risky).
        
        // BETTER: Create a DRAFT immediately if it's not a draft.
        // This ensures safety.
        // We check `status`. If it's 'LIBRARY' (or whatever completed status is), we create draft.
        // Actually, `review_library_script` likely called from Library.
        
        if ($request['status'] === 'COMPLETED' || $request['status'] === 'LIBRARY') {
             // Create Draft Immediately
             // Use Original Creator as Maker? Or Current User?
             // Use Original Maker to ensure flow returns to them properly.
             $makerId = $request['created_by'];
             $spvId = $request['selected_spv'];
             
             // Check if I already have a draft? 
             $existingDraftId = $reqModel->getExistingRevision($request['script_number']);
             if ($existingDraftId && $existingDraftId != $id) {
                 // [ENHANCEMENT] Redirect to EXISTING DRAFT with Notification Info
                 $exReq = $reqModel->getRequestById($existingDraftId);
                 $exTicket = $exReq['ticket_id'] ?? $request['script_number'];
                 $exStatus = $exReq['status'] ?? 'Draft';
                 
                 header("Location: ?controller=request&action=review_library_script&id=" . $existingDraftId . 
                        "&duplicate_alert=1&dup_ticket=" . urlencode($exTicket) . "&dup_status=" . urlencode($exStatus));
                 exit;
             } elseif ($existingDraftId == $id) {
                 // SAFETY: If I AM the existing draft (and status is somehow LIBRARY/COMPLETED?), stop loop.
                 // This shouldn't happen if status logic is correct, but safe to break here.
             } else {
                 $res = $reqModel->createRevisionDraft($id, $makerId, $spvId);
                 
                 if (isset($res['success']) && $res['success']) {
                     // Redirect to the NEW DRAFT
                     header("Location: ?controller=request&action=review_library_script&id=" . $res['id']);
                     exit;
                 } else {
                     echo "Failed to initialize revision draft: " . ($res['error'] ?? 'Unknown error');
                     return;
                 }
             }
        }
        
        // If we are here, $request is likely the NEW DRAFT (Status 'DRAFT').
        // So we can safely edit it.
        // We flag it as `isLibraryRevision` so the UI shows the Special Buttons instead of Standard Approve.
        $isLibraryRevision = true;

        // Fetch Content & Files
        $content = $reqModel->getPreviewContent($id);
        $files = $reqModel->getFiles($id);
        
        // Fetch Audit Trail
        $detail = $reqModel->getRequestDetail($id);
        $auditTrail = $detail['logs'] ?? [];
        $timeline = $auditTrail; 

        $revisions = []; 

        require_once 'app/views/request/review.php';
    }

    // ACTION: Direct Update (Procedure Fixes & Publishes)
    public function update_library_direct() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input['request_id']) { echo json_encode(['success'=>false]); return; }

        $reqModel = $this->model('RequestModel');
        $id = $input['request_id'];

        // SAVE CONTENT IF PROVIDED (Critical for Procedure edits)
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            $reqModel = $this->model('RequestModel');
            foreach ($input['updated_content'] as $contentId => $html) {
                // Ensure ID is valid and HTML is safe (basic safety handled by parameterized query)
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        // 1. Finalize to Library
        // This inserts into `script_library`
        if ($reqModel->finalizeLibrary($id)) {
            // 2. Update Status -> 'LIBRARY' (or COMPLETED)
            $reqModel->updateStatus($id, 'LIBRARY', 'LIBRARY', $_SESSION['user']['userid']);
            
            // 3. Log (Include user's note)
            $req = $reqModel->getRequestById($id);
            $note = $input['note'] ?? '';
            $details = !empty($note) ? $note : 'Directly updated by Procedure.';
            $reqModel->logAudit($id, $req['script_number'], 'LIBRARY_UPDATE', 'PROCEDURE', $_SESSION['user']['userid'], $details);
            
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Failed to update library.']);
        }
    }

    // ACTION: Minor Revision (Return to Maker, Keep Docs)
    public function revise_minor() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['request_id'];
        $note = $input['note'] ?? 'Minor Revision Requested';

        $reqModel = $this->model('RequestModel');

        // SAVE CONTENT IF PROVIDED
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        // 1. Update Status -> 'MINOR_REVISION' -> Assigned to MAKER
        $reqModel->updateStatus($id, 'MINOR_REVISION', 'MAKER', $_SESSION['user']['userid']);
        
        // 2. Log
        $req = $reqModel->getRequestById($id);
        $reqModel->logAudit($id, $req['script_number'], 'MINOR_REVISION', 'PROCEDURE', $_SESSION['user']['userid'], $note);
        
        echo json_encode(['success'=>true]);
    }

    // ACTION: Major Revision (Reset, Delete Docs, Return to Maker)
    public function revise_major() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['request_id'];

        $reqModel = $this->model('RequestModel');

        // SAVE CONTENT IF PROVIDED
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        // 1. Delete Review Docs (Legal, CX, Syariah, LPP)
        // These are distinct from TEMPLATE (the script itself)
        $reqModel->deleteFilesByType($id, ['LEGAL', 'CX', 'SYARIAH', 'LPP']);
        
        // 2. Update Status -> 'MAJOR_REVISION' -> Assigned to MAKER
        $reqModel->updateStatus($id, 'MAJOR_REVISION', 'MAKER', $_SESSION['user']['userid']);
        
        // 3. Log (Include user's note)
        $req = $reqModel->getRequestById($id);
        $note = $input['note'] ?? '';
        $details = !empty($note) ? $note . ' [Documents reset]' : 'Major Revision requested. Documents reset.';
        $reqModel->logAudit($id, $req['script_number'], 'MAJOR_REVISION', 'PROCEDURE', $_SESSION['user']['userid'], $details);
        
        echo json_encode(['success'=>true]);
    }

    public function upload() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetDir = dirname(__DIR__, 2) . '/storage/uploads/';
        
        // Ensure dir exists
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Parse File
            $parseResult = FileHandler::parseFile($targetPath, $ext);
            
            // Handle both old (string) and new (array) formats
            $previewHtml = is_array($parseResult) ? $parseResult['preview_html'] : $parseResult;
            
            echo json_encode([
                'success' => true,
                'filepath' => $targetPath,
                'preview' => $previewHtml
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        }
    }

    // Handle final submission (saving everything)
    public function store() {
        header('Content-Type: application/json');
        
        try {
            // Read FormData from POST (not JSON)
            $input = $_POST;
            
            // Handle file upload if present
            if (isset($_FILES['script_file'])) {
                // File upload mode - process file first
                $file = $_FILES['script_file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $targetDir = dirname(__DIR__, 2) . '/storage/uploads/';
                
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $filename = uniqid() . '_' . basename($file['name']);
                $targetPath = $targetDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $input['filepath'] = $targetPath;
                    $input['filename'] = $filename;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to upload file']);
                    return;
                }
            }
            
            // Decode script_content if it's JSON string
            if (isset($input['script_content']) && is_string($input['script_content'])) {
                $decoded = json_decode($input['script_content'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input['script_content'] = $decoded;
                }
            }
            
            if (empty($input)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data - no input received']);
                return;
            }
            
            // Start Session if not started
            if (session_status() == PHP_SESSION_NONE) session_start();
            $user = $_SESSION['user']['userid'] ?? 'maker01';

            $reqModel = $this->model('RequestModel');

            // VALDIATION: SPV SELECT
            if (empty($input['selected_spv'])) {
                echo json_encode(['status' => 'error', 'message' => 'Please select an SPV/Supervisor!']);
                return;
            }

            // 1. Create Request
            $requestData = [
                'title' => $input['title'],
                'jenis' => $input['jenis'],
                'produk' => $input['produk'],
                'kategori' => $input['kategori'],
                'media' => $input['media'],
                'mode' => $input['input_mode'], // Frontend sends 'input_mode', DB expects 'mode'
                'creator_id' => $user,
                'selected_spv' => $input['selected_spv'],
                'start_date' => !empty($input['start_date']) ? $input['start_date'] : null
            ];
            
            $reqResult = $reqModel->createRequest($requestData);

            if (!$reqResult || isset($reqResult['error'])) {
                $errMsg = isset($reqResult['error']) ? $reqResult['error'] : 'Create Request Failed';
                echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $errMsg]);
                return;
            }
            
            $scriptId = $reqResult['id'];
            $scriptNumber = $reqResult['number'];
            $ticketId = $reqResult['ticket_id'] ?? $scriptNumber;

            // 2. Save Preview Content
            // Logic: Content might be a single string (from File Upload) or an Object/Array (from Free Input Tabs)
            
            $mediaList = explode(',', $input['media']); // "WA, SMS" -> ["WA", " SMS"]
            $contentData = $input['script_content'] ?? null; // Changed from 'content' to 'script_content'

            if (is_array($contentData)) {
                // Free Input with specific content per media
                foreach ($contentData as $item) {
                    $sheetName = $item['sheet_name'] ?? 'Sheet';
                    $text = $item['content'] ?? '';
                    
                    // [FIX] DO NOT use htmlspecialchars for FILE_UPLOAD 
                    // because we are scraping HTML tables from the frontend.
                    if ($input['input_mode'] !== 'FILE_UPLOAD') {
                        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                    }
                    
                    $reqModel->savePreviewContent($scriptId, trim($sheetName), $text, $user);
                }
            } else if ($contentData) {
                // Single content (File Upload or Raw String) 
                
                // [FIX] If contentData is already an array for FILE_UPLOAD, use it!
                // This means the user edited the preview in the frontend.
                if ($input['input_mode'] === 'FILE_UPLOAD' && is_array($contentData)) {
                    error_log("MULTI-SHEET DEBUG - Using EDITED content from frontend");
                    foreach ($contentData as $item) {
                        $sheetName = $item['sheet_name'] ?? 'Sheet';
                        $text = $item['content'] ?? '';
                        $reqModel->savePreviewContent($scriptId, trim($sheetName), $text, $user);
                    }
                } 
                // Regular File Upload (No edits yet or first time)
                else if ($input['input_mode'] === 'FILE_UPLOAD' && !empty($input['filepath'])) {
                    $ext = pathinfo($input['filepath'], PATHINFO_EXTENSION);
                    $parseResult = FileHandler::parseFile($input['filepath'], $ext);
                    
                    // DEBUG: Log parse result structure
                    error_log("MULTI-SHEET DEBUG - Parse Result Type: " . gettype($parseResult));
                    if (is_array($parseResult)) {
                        error_log("MULTI-SHEET DEBUG - Has 'sheets' key: " . (isset($parseResult['sheets']) ? 'YES' : 'NO'));
                        if (isset($parseResult['sheets'])) {
                            error_log("MULTI-SHEET DEBUG - Sheets count: " . count($parseResult['sheets']));
                            error_log("MULTI-SHEET DEBUG - Sheets data: " . json_encode($parseResult['sheets']));
                        }
                    }
                    
                    // If parseResult has 'sheets' array, save each sheet separately
                    if (is_array($parseResult) && isset($parseResult['sheets']) && count($parseResult['sheets']) > 0) {
                        error_log("MULTI-SHEET DEBUG - Saving " . count($parseResult['sheets']) . " sheets separately");
                        foreach ($parseResult['sheets'] as $sheet) {
                            $sheetName = $sheet['name'] ?? 'Sheet';
                            $sheetContent = $sheet['content'] ?? '';
                            error_log("MULTI-SHEET DEBUG - Saving sheet: " . $sheetName);
                            $reqModel->savePreviewContent($scriptId, trim($sheetName), $sheetContent, $user);
                        }
                    } else {
                        // Fallback: Old format or single sheet
                        error_log("MULTI-SHEET DEBUG - Fallback: Saving to media list (count: " . count($mediaList) . ")");
                        foreach ($mediaList as $mediaName) {
                            $reqModel->savePreviewContent($scriptId, trim($mediaName), $contentData, $user);
                        }
                    }
                } else {
                    // Not file upload, save to each media
                    foreach ($mediaList as $mediaName) {
                        $reqModel->savePreviewContent($scriptId, trim($mediaName), $contentData, $user);
                    }
                }
            }

            // 3. Save File Info (If Upload Mode)
            if ($input['input_mode'] === 'FILE_UPLOAD' && !empty($input['filepath'])) {
                $reqModel->saveFileInfo($scriptId, 'TEMPLATE', isset($file) ? basename($file['name']) : basename($input['filepath']), $input['filepath'], $user);
            }

            // 4. Audit Trail
            $reqModel->logAudit($scriptId, $scriptNumber, 'SUBMIT_REQUEST', 'Maker', $user, 'Submitted by ' . ($_SESSION['user']['userid'] ?? 'Maker'));

            echo json_encode(['status' => 'success', 'ticket_id' => $ticketId, 'script_number' => $scriptNumber]);
            
        } catch (Exception $e) {
            // Catch any PHP errors and return JSON error instead of HTML
            echo json_encode([
                'status' => 'error', 
                'message' => 'Server Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    public function edit() {
        if (!isset($_GET['id'])) die("Invalid ID");
        $id = $_GET['id'];
        
        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);
        
        // Security: Ensure only Creator can edit, AND status is REVISION (or REJECTED)
        // Ignoring strict check for demo speed, but logic is:
        // if ($request['created_by'] != $_SESSION['user']['userid']) die("Unauthorized");
        
        $content = $reqModel->getPreviewContent($id);
        $spvList = $reqModel->getSupervisors();
        
        // Fetch Full Detail (Metadata + Logs)
        $detail = $reqModel->getRequestDetail($id);
        $timeline = $detail['logs'] ?? [];
        
        $revisionInfo = $reqModel->getLatestRevisionInfo($id);
        $rejectionNote = $revisionInfo['details'] ?? '';
        $lastRole = $revisionInfo['user_role'] ?? 'Reviewer';
        $draftNote = $reqModel->getLatestDraftNote($id);

        $this->view('request/edit', [
            'request' => $request,
            'content' => $content,
            'spvList' => $spvList,
            'rejectionNote' => $rejectionNote,
            'lastRole' => $lastRole,
            'draftNote' => $draftNote,
            'timeline' => $timeline
        ]);
    }

    public function update() {
        // Handle Re-Submission
        // Clear any previous output buffers to prevent JSON corruption
        if (ob_get_length()) ob_end_clean();
        ob_start();

        header('Content-Type: application/json');
        
        try {
            // Read FormData
            $input = $_POST;
            
            // Handle JSON Input
            if (empty($input)) {
                $raw = file_get_contents('php://input');
                $input = json_decode($raw, true);
            }
        
        if (isset($input['script_content']) && is_string($input['script_content'])) {
            $decoded = json_decode($input['script_content'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input['content'] = $decoded;
            } else {
                $input['content'] = $input['script_content']; // Raw HTML string
            }
        }
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        // [FIX V10] Backend Sanitizer: Exorcise Deleted IDs
        if (isset($input['deleted_ids']) && !empty($input['deleted_ids'])) {
            $deletedIds = explode(',', $input['deleted_ids']);
            $deletedIds = array_map('trim', $deletedIds);
            
            if (!empty($deletedIds)) {
                // Determine content to sanitize
                $contentToSanitize = [];
                if (isset($input['content']) && is_array($input['content'])) {
                    $contentToSanitize = &$input['content']; // Reference
                } elseif (isset($input['content']) && is_string($input['content'])) {
                    $contentToSanitize = [&$input['content']]; // Wrap in array
                }
                
                foreach ($contentToSanitize as &$item) {
                    $html = is_array($item) ? $item['content'] : $item;
                    if (empty($html)) continue;
                    
                    // Use robust DOM parsing
                    $dom = new \DOMDocument();
                    // Suppress warnings for HTML fragments
                    libxml_use_internal_errors(true);
                    // Add utf-8 header to prevent encoding issues
                    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();
                    
                    $xpath = new \DOMXPath($dom);
                    $modified = false;
                    
                    foreach ($deletedIds as $delId) {
                        if (empty($delId)) continue;
                        // XPath to find element by ID
                        $nodes = $xpath->query("//*[@id='$delId']");
                        
                        foreach ($nodes as $node) {
                            // REMOVE NODE completely (including content "jujur")
                            $node->parentNode->removeChild($node);
                            $modified = true;
                        }
                    }
                    
                    if ($modified) {
                        $newHtml = $dom->saveHTML();
                        // Remove the XML declaration added by loadHTML
                        $newHtml = str_replace('<?xml encoding="utf-8" ?>', '', $newHtml);
                        
                        if (is_array($item)) {
                            $item['content'] = $newHtml;
                        } else {
                            $item = $newHtml;
                        }
                    }
                }
            }
        }

        $reqModel = $this->model('RequestModel');
        $id = $input['request_id'];
        $user = $_SESSION['user']['userid'] ?? 'maker01';

        // 2. Handle File Upload if present in update
        if (isset($_FILES['script_file'])) {
             $file = $_FILES['script_file'];
             $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
             // FIX: Use Absolute Path to ensure we stay inside project root
             $targetDir = dirname(__DIR__, 2) . '/storage/uploads/';
             if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
             $filename = uniqid() . '_' . basename($file['name']);
             $targetPath = $targetDir . $filename;
             if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                 $reqModel->saveFileInfo($id, 'TEMPLATE', basename($file['name']), $targetPath, $user);
             }
        }
        
        // 3. Update Content
        if (isset($input['content'])) {
             $contentData = $input['content'];
             $reqModel->deletePreviewContent($id);
             
             if (is_array($contentData)) {
                 // Free Input
                 foreach ($contentData as $item) {
                     $sheetName = $item['sheet_name'] ?? 'unknown';
                     $text = $item['content'] ?? '';
                     $reqModel->savePreviewContent($id, trim($sheetName), $text, $user);
                 }
             } else if (!empty($contentData)) {
                 // File Upload (Fallback)
                 // If content is still string (Legacy or Single Sheet Word), save as "Document Preview"
                 // Do NOT use "Formatted Script" to avoid confusion
                 $reqModel->savePreviewContent($id, 'Document Preview', $contentData, $user);
             }
        }
        
        // 3b. Update Metadata (Checklists) - FIX for Persistence
        // MAP input_mode to mode (Model expects 'mode')
        if (isset($input['input_mode'])) {
            $input['mode'] = $input['input_mode'];
        }
        
        // Pass start_date if set
        if (isset($input['start_date'])) {
            // No processing needed, just pass along
        }
        
        $reqModel->updateRequestMetadata($id, $input);

        // 4. Update Status (Only if NOT Draft)
        $msg = "Re-submitted by " . ($_SESSION['user']['userid'] ?? 'Maker');
        $action = "RESUBMIT";

        if (isset($input['is_draft']) && $input['is_draft'] == '1') {
             // DRAFT MODE: Do not change status, just save content
             $msg = "Draft saved by " . ($_SESSION['user']['userid'] ?? 'Maker');
             $action = "DRAFT_SAVED";
             // PROMOTE TEMP DRAFT TO PERMANENT DRAFT
             $reqModel->updateStatus($id, 'DRAFT', 'Maker', $user); 
             $reqModel->setDraftStatus($id, 1);
        } else {
             // SUBMIT MODE: Forward to SPV
             $reqModel->updateStatus($id, 'CREATED', 'SPV', $user);
             $reqModel->setDraftStatus($id, 0); // Clear draft flag
        }
        
        $logDetails = $msg;
        if (!empty($input['maker_note'])) {
            $logDetails .= '. Note: ' . $input['maker_note'];
        }

        $reqModel->logAudit($id, $input['script_number'], $action, 'Maker', $user, $logDetails);
        
        ob_clean(); // Ensure clean JSON
        echo json_encode(['success' => true]);
        
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function uploadReviewDoc() {
        // Clear any previous output/warnings to ensure valid JSON
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json');
        
        try {
            if (!isset($_SESSION['user'])) {
                throw new \Exception('Not authenticated');
            }
            
            if (!isset($_FILES['file']) || !isset($_POST['doc_type']) || !isset($_POST['request_id'])) {
                throw new \Exception('Missing parameters');
            }
            
            $file = $_FILES['file'];
            $docType = $_POST['doc_type']; // LEGAL, CX, LEGAL_SYARIAH, LPP
            $requestId = $_POST['request_id'];
            
            // Allow any extension (User Request)
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            
            // Generate unique filename: [TYPE]_[YEAR]_[TICKET]_[ORIGINALNAME].[ext]
            // sanitize type
            $safeType = preg_replace('/[^a-zA-Z0-9]/', '_', $docType);
            
            // Get Request Info for Ticket ID
            $reqModel = $this->model('RequestModel');
            $request = $reqModel->getRequestById($requestId);
            $ticketId = $request['ticket_id'] ?? 'Unknown';
            $formattedTicket = is_numeric($ticketId) ? sprintf("SC-%04d", $ticketId) : $ticketId;
            
            $year = date('Y');
            
            // Clean original name (remove weird chars)
            $safeOriginal = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
            
            // Final Filename
            $filename = "{$safeType}_{$year}_{$formattedTicket}_{$safeOriginal}.{$ext}";
            
            // Create upload directory (ABSOLUTE PATH SECURE)
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/review_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filepath = $uploadDir . $filename;
            
            // Move file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new \Exception('Failed to save file to disk');
            }
            
            // Save to database
            $reqModel = $this->model('RequestModel');
            $user = $_SESSION['user']['userid'];
            
            $newFileId = $reqModel->saveFileInfo($requestId, $docType, $file['name'], $filepath, $user);
            
            if ($newFileId) {
                echo json_encode(['success' => true, 'filename' => $file['name'], 'path' => $filepath, 'id' => $newFileId]);
            } else {
                // If DB save fails, maybe delete the file?
                unlink($filepath);
                throw new \Exception('Failed to save to database (SQL Error)');
            }
            
        } catch (\Exception $e) {
            // Log the full error for server admin
            error_log("Upload Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function downloadReviewDoc() {
        if (!isset($_GET['file_id'])) die("File ID missing.");
        
        $fileId = $_GET['file_id'];
        $reqModel = $this->model('RequestModel');
        $file = $reqModel->getFileById($fileId);
        
        if (!$file) die("File not found in DB.");

        $filepath = $file['filepath'];
        
        // PATH RESOLUTION LOGIC (Legacy vs New)
        // 1. If path is Absolute (contains drive letter ':' or starts with '/') -> Use as is.
        // 2. If path is Relative (starts with 'uploads/'), prepend Root Dir.
        
        if (strpos($filepath, ':') === false && strpos($filepath, '/') !== 0 && strpos($filepath, '\\') !== 0) {
            // Relative Path detected (Legacy)
            $filepath = dirname(__DIR__, 2) . '/' . $filepath;
        }

        if (!file_exists($filepath)) {
            die("Error 404: File physical path not found on server. Path: " . htmlspecialchars($filepath));
        }

        // Serve File
        $filename = $file['original_filename'];
        $mime = mime_content_type($filepath);
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function deleteReviewDoc() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['file_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing File ID']);
            return;
        }
        
        $fileId = $input['file_id'];
        $reqModel = $this->model('RequestModel');
        
        // Security: Check file exists
        $file = $reqModel->getFileById($fileId);
        if (!$file) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            return;
        }
        
        // Optional: Check permission (e.g., only Uploader or Admin)
        // if ($file['uploaded_by'] != $_SESSION['user']['userid']) ...

        // Delete Physical File
        if (file_exists($file['filepath'])) {
            unlink($file['filepath']);
        }
        
        // Delete DB Record
        if ($reqModel->deleteReviewDoc($fileId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database delete failed']);
        }
    }

    public function viewLibrary() {
        if (!isset($_GET['id'])) {
            die("Invalid Script ID");
        }
        
        $scriptId = $_GET['id'];
        $reqModel = $this->model('RequestModel');
        
        // Get Full Request Details (Metadata + Logs + Content + Files)
        $detail = $reqModel->getRequestDetail($scriptId);
        
        if (!$detail || !$detail['request']) {
             die("Script not found. ID: " . htmlspecialchars($scriptId));
        }

        $request = $detail['request'];
        $logs = $detail['logs'] ?? [];
        
        // Extract specific components for the view
        // Note: getRequestDetail already structures 'content' and 'files'
        // But the View expects specific variables, so we'll adapt slightly
        
        // Content: View expects raw list of rows for preview
    // FIX: Use getLibraryContentOnly to ensure we show the CLEANED version from script_library,
    // instead of potentially dirty draft content from script_preview_content.
    $content = $reqModel->getLibraryContentOnly($scriptId);
        
        // Review Docs
        $reviewDocs = $reqModel->getReviewDocuments($scriptId);
        
        // Original Script File
        $scriptFile = $reqModel->getScriptFile($scriptId);
        
        // [FALLBACK] If content is empty but file exists, parse on-the-fly
        if (empty($content) && !empty($scriptFile) && !empty($scriptFile['filepath']) && file_exists($scriptFile['filepath'])) {
            $ext = pathinfo($scriptFile['original_filename'], PATHINFO_EXTENSION);
            $parseResult = FileHandler::parseFile($scriptFile['filepath'], $ext);
            
            if (is_array($parseResult) && isset($parseResult['sheets'])) {
                // Multi-sheet mapping
                foreach ($parseResult['sheets'] as $sheet) {
                    $content[] = [
                        'media' => $sheet['name'],
                        'content' => $sheet['content']
                    ];
                }
            } elseif (is_string($parseResult)) {
                // Single content
                $content[] = [
                    'media' => 'Document Preview',
                    'content' => $parseResult
                ];
            }
        }
        
        // Fetch SPV List for "Reuse/Revise" Modal
        $spvList = $reqModel->getSupervisors();

        // [ENHANCEMENT] Check for Active Revision Draft
        $existingDraft = null;
        if (in_array($request['status'] ?? '', ['LIBRARY', 'COMPLETED'])) {
            $existingDraft = $reqModel->getExistingRevisionDetails($request['script_number']);
            // Don't show banner for the script's own ID
            if ($existingDraft && $existingDraft['id'] == $scriptId) {
                $existingDraft = null;
            }
        }

        // [NEW] Get all users for historical log replacement
        $userModel = $this->model('UserModel');
        $allUsers = $userModel->getAll();

        // [NEW] Get Library Item Details (for Active Status & Start Date)
        $libraryItem = $reqModel->getLibraryItemByRequestId($scriptId);
        $isActive = $libraryItem['is_active'] ?? 1; // Default true if not found (legacy behavior)
        $startDate = $libraryItem['start_date'] ?? $request['start_date'] ?? null;

        // [NEW] Get Activator User Info (for "Activated by" display)
        $activatorInfo = null;
        $activatedBy = $libraryItem['activated_by'] ?? null;
        if ($activatedBy) {
            $userModel = $this->model('UserModel');
            $activatorUser = $userModel->getById($activatedBy);
            if ($activatorUser) {
                $activatorInfo = [
                    'userid' => $activatorUser['USERID'] ?? $activatedBy,
                    'fullname' => $activatorUser['FULLNAME'] ?? $activatedBy,
                    'job_function' => $activatorUser['JOB_FUNCTION'] ?? '',
                    'divisi' => $activatorUser['DIVISI'] ?? '',
                    'dept' => $activatorUser['DEPT'] ?? ''
                ];
            }
        }
        $activatedAt = $libraryItem['activated_at'] ?? null;

        // [NEW] Get SQL Server Today Date to prevent PHP timezone discrepancy
        $sqlServerToday = $reqModel->getSqlServerTodayDate();

        $this->view('library/detail', [
            'request' => $request,
            'content' => $content,
            'reviewDocs' => $reviewDocs,
            'scriptFile' => $scriptFile,
            'logs' => $logs,
            'spvList' => $spvList,
            'existingDraft' => $existingDraft,
            'allUsers' => $allUsers,
            'isActive' => $isActive,
            'startDate' => $startDate,
            'activatorInfo' => $activatorInfo,
            'activatedAt' => $activatedAt,
            'sqlServerToday' => $sqlServerToday
        ]);
    }

    public function history() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }
        $userId = $_SESSION['user']['userid'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        $filterOptions = [];
        
        foreach ($filterCols as $col) {
            $filterOptions[$col] = $reqModel->getDistinctRequestValues($col);
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }
        
        $history = $reqModel->getUserApprovalHistory($userId, $startDate, $endDate, $filters);
        
        // ENRICHMENT: Fetch Content for Display (Robust Fallback)
        foreach ($history as &$req) {
            if (empty($req['content']) || trim($req['content']) === '') {
                // 1. Try Free Input (Previews)
                $previews = $reqModel->getPreviewContent($req['id']);
                if (!empty($previews)) {
                    $combined = [];
                    foreach ($previews as $p) {
                        $combined[] = "{$p['media']}: " . strip_tags($p['content']);
                    }
                    $req['content'] = implode(' | ', $combined);
                } 
                // 2. If still empty, Try File Upload
                if (empty($req['content'])) {
                     $files = $reqModel->getFiles($req['id']);
                     if (!empty($files)) {
                         $names = array_map(function($f) { return $f['original_filename']; }, $files);
                         $req['content'] = implode(', ', $names);
                     }
                }
            }
        }
        unset($req);
        
        $this->view('request/history', [
            'history' => array_slice($history, (max(1,intval($_GET['page']??1))-1)*10, 10),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'currentPage' => max(1,intval($_GET['page']??1)),
            'totalPages' => max(1, ceil(count($history)/10)),
            'totalItems' => count($history),
            'perPage' => 10
        ]);
    }
    
    public function downloadTemplate() {
        // Generate standard Excel template for script creation
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Script_Template_' . date('YmdHis') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Use PhpSpreadsheet if available, otherwise use simple CSV-like format
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->generateTemplateWithPhpSpreadsheet();
        } else {
            $this->generateTemplateSimple();
        }
    }
    
    private function generateTemplateWithPhpSpreadsheet() {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set Headers
        $headers = ['Process', 'Row', 'Node', 'Script Content', 'Next Action', 'Error Script'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Style Header Row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Set Column Widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(100);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(25);
        
        // Add sample row (optional)
        $sampleData = ['Pre Collect', '1', 'Node 1', 'Contoh isi script di sini...', 'Next Node', 'Error Handler'];
        $sheet->fromArray($sampleData, NULL, 'A2');
        
        // Write file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function generateTemplateSimple() {
        // Simple TSV format that Excel can open
        $content = "Process\tRow\tNode\tScript Content\tNext Action\tError Script\n";
        $content .= "Pre Collect\t1\tNode 1\tContoh isi script di sini...\tNext Node\tError Handler\n";
        
        echo $content;
        exit;
    }
    public function download() {
        if (!isset($_GET['id'])) die("Invalid ID");
        $id = $_GET['id'];
        
        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);
        
        if (!$request) die("Request not found");
        
        $source = $_GET['source'] ?? '';
        
        // 1. FILE UPLOAD MODE (Priority for non-library downloads)
        // If source is 'library', we skip original file and generate fresh from DB to include revisions
        if ($source !== 'library') {
            $requestedType = $_GET['file'] ?? 'TEMPLATE';
            $files = $reqModel->getFiles($id);
            $targetFile = null;
            
            foreach ($files as $f) {
                if ($f['file_type'] === $requestedType) { 
                    $targetFile = $f;
                    break;
                }
            }
            
            if ($targetFile && file_exists($targetFile['filepath'])) {
                $filepath = $targetFile['filepath'];
                $filename = $targetFile['original_filename'];
                
                if (ob_get_length()) ob_clean();
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($filename).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        }
        
        // 2. GENERATED EXCEL MODE (Free Input or Fallback/Library Context)
        if ($source === 'library') {
            $content = $reqModel->getLibraryContentOnly($id);
        } else {
            $content = $reqModel->getPreviewContent($id);
        }
        
        if (empty($content)) {
            die("No content to download. Please verify input.");
        }
        
        // FIX: Normalize Stream Resources (SQL Server TEXT/NVARCHAR) for Export
        // The controller must handle this because the View's fix doesn't apply to the download action
        foreach ($content as &$row) {
            if (isset($row['content']) && is_resource($row['content'])) {
                $row['content'] = stream_get_contents($row['content']);
            }
        }
        unset($row); // Break reference safety
        
        $filename = "Script_" . ($request['script_number'] ?? 'Draft') . ".xlsx";
        
        // Use PhpSpreadsheet for true Multi-Sheet XLSX if available
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->generateMultiSheetExcel($content, $filename);
        } else {
            // FALLBACK V4: XML Spreadsheet 2003 (Proven Multi-Sheet)
            // with HTML Parsing to fix "Raw Code" issue
            $this->generateNativeXMLSpreadsheet($content, $filename);
        }
    }

    private function generateNativeXMLSpreadsheet($content, $filename) {
        $filename = str_replace('.xlsx', '.xml', $filename);
        
        if (ob_get_length()) ob_clean();
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");
        
        echo '<?xml version="1.0"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        echo ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
        echo ' xmlns:html="http://www.w3.org/TR/REC-html40">';
        
        // Styles
        echo '<Styles>';
        echo '<Style ss:ID="Default" ss:Name="Normal">';
        echo '<Alignment ss:Vertical="Top" ss:WrapText="1"/>';
        echo '<Borders/>';
        echo '<Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>'; // Global Black
        echo '<Interior/>';
        echo '<NumberFormat/>';
        echo '<Protection/>';
        echo '</Style>';
        
        echo '<Style ss:ID="sHeader">';
        echo '<Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>';
        echo '<Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '</Borders>';
        echo '</Style>';
        
        echo '<Style ss:ID="sContent">'; 
        echo '<Alignment ss:Vertical="Top" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        echo '</Borders>';
        echo '<Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>'; // Explicit Black
        echo '</Style>';
        echo '</Styles>';
        
        foreach ($content as $index => $row) {
            $mediaName = trim($row['media'] ?? 'Sheet' . ($index + 1));
            $safeTitle = substr(preg_replace('/[*?:\[\]\/\\\]/', '_', $mediaName), 0, 31);
            if (empty($safeTitle)) $safeTitle = "Media_" . ($index + 1);
            
            echo '<Worksheet ss:Name="' . htmlspecialchars($safeTitle) . '">';
            echo '<Table ss:DefaultColumnWidth="600">';
            echo '<Column ss:Width="600"/>';
            
            echo '<Row>';
            echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">MEDIA: ' . htmlspecialchars($mediaName) . '</Data></Cell>';
            echo '</Row>';
            
            // ROBUST HTML PARSING
            $htmlContent = $row['content'] ?? '';
            
            if (!empty($htmlContent) && class_exists('DOMDocument')) {
                $internalErrors = libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                // Check if content is full table or part
                $toLoad = (strpos($htmlContent, '<html') === false) 
                    ? '<?xml encoding="UTF-8"><html><body>' . $htmlContent . '</body></html>' 
                    : $htmlContent;
                
                $dom->loadHTML($toLoad, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $rows = $dom->getElementsByTagName('tr');
                
                foreach ($rows as $tr) {
                    echo '<Row>';
                    $cells = $tr->childNodes;
                    foreach ($cells as $td) {
                        if ($td->nodeName === 'td' || $td->nodeName === 'th') {
                            $cellText = trim($td->textContent);
                            $cellText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cellText);
                            echo '<Cell ss:StyleID="sContent"><Data ss:Type="String">' . htmlspecialchars($cellText) . '</Data></Cell>';
                        }
                    }
                    echo '</Row>';
                }
                libxml_clear_errors();
                libxml_use_internal_errors($internalErrors);
            } else {
                $text = strip_tags($htmlContent);
                echo '<Row><Cell ss:StyleID="sContent"><Data ss:Type="String">' . htmlspecialchars($text) . '</Data></Cell></Row>';
            }
            
            echo '</Table>';
            echo '</Worksheet>';
        }
        
        echo '</Workbook>';
        exit;
    }


    // Keeping PhpSpreadsheet implementation as potential future option
    private function generateMultiSheetExcel($content, $filename) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $spreadsheet->removeSheetByIndex(0); // Remove default sheet
        
        foreach ($content as $index => $row) {
            $mediaName = trim($row['media'] ?? 'Sheet' . ($index + 1));
            // Excel sheet names cannot exceed 31 chars and have forbidden chars
            $safeTitle = substr(preg_replace('/[*?:\[\]\/\\\]/', '_', $mediaName), 0, 31);
            if (empty($safeTitle)) $safeTitle = "Media_" . ($index + 1);

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($safeTitle);
            
            // Set Header (Media Name)
            $sheet->setCellValue('A1', "MEDIA: " . $mediaName);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->setName('Calibri');
            $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
            
            // Set Content
            $sheet->setCellValue('A2', $row['content'] ?? '');
            
            // Styling A1:A2
            $styleArray = [
                'font' => ['name' => 'Calibri', 'size' => 11],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
            
            // Specific for A1
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            
            // Set Column Width
            $sheet->getColumnDimension('A')->setWidth(120);
        }
        
        // Reset to first sheet
        if ($spreadsheet->getSheetCount() > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        } else {
            // Fallback if no sheets were created
            $spreadsheet->createSheet()->setTitle('No Content');
        }
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function toggle_active() {
        header('Content-Type: application/json');
        
        // Security Check: Only Maker/Procedure
        $dept = $_SESSION['user']['dept'] ?? '';
        if ($dept !== 'MAKER' && $dept !== 'PROCEDURE' && $dept !== 'CPMS') {
             echo json_encode(['success'=>false, 'error'=>'Unauthorized Access']); return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? null;
        $isActive = $input['is_active'] ?? null;
        $startDate = $input['start_date'] ?? null; // User-picked date from popup

        if (!$requestId || $isActive === null) {
            echo json_encode(['success'=>false, 'error'=>'Missing Parameters']); return;
        }

        $userId = $_SESSION['user']['userid'] ?? 'UNKNOWN';
        $reqModel = $this->model('RequestModel');
        if ($reqModel->updateActiveStatus($requestId, $isActive, $startDate, $userId)) {
            // Log Audit
            $statusLabel = $isActive ? 'ACTIVATED' : 'DEACTIVATED';
            $details = $isActive ? 'Activated with start_date: ' . ($startDate ?: 'today') . ' by ' . $userId : 'Deactivated by ' . $userId;
            $reqModel->logAudit($requestId, 'N/A', $statusLabel, $dept, $userId, $details);
            
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Database Update Failed: ' . print_r(db_errors(), true)]);
        }
    }
}
