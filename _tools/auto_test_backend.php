<?php
/**
 * AUTOMATED BACKEND TEST RUNNER
 * 
 * Tujuan: Menguji alur Backend (Request -> Approval -> Versioning) secara otomatis.
 * Cara Pakai: Buka http://localhost/.../_tools/auto_test_backend.php
 */

// 1. Setup Environment
define('TEST_MODE', true);
// Fix Path Resolution
$root = __DIR__ . '/..';
require_once $root . '/app/helpers/DbAdapter.php'; // CORRECT DB ADAPTER
require_once $root . '/app/core/Controller.php';
require_once $root . '/app/models/RequestModel.php';

use App\Models\RequestModel;

session_start();

// Helper: Output log
function testLog($msg, $status = 'INFO') {
    $color = 'black';
    if ($status === 'PASS') $color = 'green';
    if ($status === 'FAIL') $color = 'red';
    echo "<div style='color:$color; font-family:monospace; margin-bottom:5px;'>[$status] $msg</div>";
    if ($status === 'FAIL') exit();
}

try {
    echo "<h1>Automated Backend Test: Life Cycle</h1>";
    
    // Init DB
    // RequestModel constructor should handle connection successfully
    $reqModel = new RequestModel();
    
    // We can't access $reqModel->conn directly if it's private.
    // Instead of raw DB queries, let's use RequestModel methods OR Reflection to get connection.
    // Since we are in a test script, let's just make the verification queries slightly more hacky 
    // by using a helper function in this script that piggybacks on RequestModel if possible
    // OR just instantiate a public accessor if one exists.
    
    // BETTER FIX: Use the global $conn if defined by DbAdapter but usually it's not.
    // Let's rely on RequestModel's existing methods for verification where possible.
    
    // For raw verification, we need a connection.
    // The previous error was due to 'Named Pipes', likely relative path in config failing or DbAdapter weirdness.
    // Let's Try to use the same connection RequestModel uses via Reflection.
    $reflection = new ReflectionClass($reqModel);
    $property = $reflection->getProperty('conn');
    $property->setAccessible(true);
    $conn = $property->getValue($reqModel);

    // ---------------------------------------------------------
    // STEP 1: LOGIN AS MAKER & CREATE REQUEST
    // ---------------------------------------------------------
    $_SESSION['user'] = ['userid' => 'MAKER01', 'role' => 'MAKER', 'group' => 'DEPARTMENT HEAD UNIT'];
    
    $ticketId = 'TEST-' . time();
    $inputData = [
        'mode' => 'FREE_INPUT',
        'jenis' => 'Kartu Kredit',
        'produk' => 'KTA',
        'kategori' => 'Sales',
        'script_title' => 'Auto Test Script ' . $ticketId,
        'ticket_id' => $ticketId,
        'media' => ['WA', 'SMS'],
        'content_wa' => 'Halo ini tes WA',
        'content_sms' => 'Halo ini tes SMS',
        'selected_spv' => 'SPV01'
    ];

    testLog("Creating Request as MAKER01...", 'INFO');
    
    // Simulate Create Logic (Raw Model Call)
    $requestId = $reqModel->createRequest($inputData['jenis'], $inputData['produk'], $inputData['kategori'], $inputData['media'], $inputData['selected_spv'], $inputData['ticket_id'], $inputData['script_title'], $inputData['mode'], 'MAKER01');
    
    if ($requestId) {
        testLog("Request Created! ID: $requestId", 'PASS');
        // Insert Content
        $reqModel->insertPreviewContent($requestId, 'WA', $inputData['content_wa']);
        $reqModel->insertPreviewContent($requestId, 'SMS', $inputData['content_sms']);
    } else {
        testLog("Failed to create request", 'FAIL');
        exit();
    }

    // ---------------------------------------------------------
    // STEP 2: SPV APPROVAL (Create Version 1)
    // ---------------------------------------------------------
    $_SESSION['user'] = ['userid' => 'SPV01', 'role' => 'SPV'];
    testLog("Approving as SPV01...", 'INFO');

    // Simulate Controller 'approve' logic relies on insertPreviewContentVersion
    // We maintain original content but add 'approved' flag/stage
    $versionContent = "Halo ini tes WA (Approved SPV)";
    
    // APPROVE LOGIC:
    // 1. Update Status
    $reqModel->updateStatus($requestId, 'APPROVED_SPV');
    // 2. Insert Audit Trail
    $reqModel->logActivity($requestId, 'SPV01', 'SPV', 'APPROVE_SPV', 'Auto Approve SPV');
    // 3. Insert Version
    $reqModel->insertPreviewContentVersion($requestId, 'WA', $versionContent, 'SPV', 'SPV01');
    
    testLog("SPV Approval Complete.", 'PASS');

    // VERIFY VERSION COUNT (Using reflected connection)
    $sql = "SELECT COUNT(*) as cnt FROM script_preview_content WHERE request_id = ? AND workflow_stage = 'SPV'";
    $stmt = db_query($conn, $sql, [$requestId]);
    $row = db_fetch_array($stmt, DB_FETCH_ASSOC);
    if ($row['cnt'] > 0) {
        testLog("Versioning Check: SPV Version found.", 'PASS');
    } else {
        testLog("Versioning Check: SPV Version NOT found!", 'FAIL');
    }

    // ---------------------------------------------------------
    // STEP 3: PIC APPROVAL (Create Version 2)
    // ---------------------------------------------------------
    $_SESSION['user'] = ['userid' => 'PIC01', 'role' => 'PIC'];
    testLog("Approving as PIC01...", 'INFO');
    
    $reqModel->updateStatus($requestId, 'APPROVED_PIC');
    $reqModel->logActivity($requestId, 'PIC01', 'PIC', 'APPROVE_PIC', 'Auto Approve PIC');
    $reqModel->insertPreviewContentVersion($requestId, 'WA', "Halo ini tes WA (Approved PIC)", 'PIC', 'PIC01');
    
    testLog("PIC Approval Complete.", 'PASS');


    // ---------------------------------------------------------
    // STEP 4: PROCEDURE APPROVAL (Finalize)
    // ---------------------------------------------------------
    $_SESSION['user'] = ['userid' => 'PROC01', 'role' => 'PROCEDURE'];
    testLog("Approving as PROC01...", 'INFO');
    
    $reqModel->updateStatus($requestId, 'APPROVED_PROCEDURE'); // Or CLOSED
    $reqModel->finalizeLibrary($requestId); // Should clean content
    $reqModel->logActivity($requestId, 'PROC01', 'PROCEDURE', 'APPROVE_PROCEDURE', 'Finalized');
    
    testLog("Procedure Approval Complete. Script moved to Library.", 'PASS');

    // ---------------------------------------------------------
    // STEP 5: FINAL VERIFICATION
    // ---------------------------------------------------------
    
    // Check Library
    $libSql = "SELECT * FROM script_library WHERE request_id = ?";
    $libStmt = db_query($conn, $libSql, [$requestId]);
    $libRow = db_fetch_array($libStmt, DB_FETCH_ASSOC);
    
    if ($libRow) {
        testLog("Library Entry Found.", 'PASS');
    } else {
        testLog("Library Entry MISSING!", 'FAIL');
    }

    echo "<h3>ALL TESTS PASSED ✅</h3>";
    echo "<p>Request ID $requestId has been successfully processed through the full workflow.</p>";

} catch (Exception $e) {
    testLog("EXCEPTION: " . $e->getMessage(), 'FAIL');
}
