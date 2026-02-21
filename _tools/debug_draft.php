<?php
require_once 'app/init.php';
$model = new App\Models\RequestModel();

echo "Debuging Request ID 10 (SC-0010):\n";
$req = $model->getRequestById(10);

if (!$req) {
    echo "Request not found.\n";
} else {
    echo "ID: " . $req['id'] . "\n";
    echo "Ticket: " . $req['ticket_id'] . "\n";
    echo "Status: " . $req['status'] . "\n";
    
    echo "Has Draft Key Exists? " . (array_key_exists('has_draft', $req) ? "YES" : "NO") . "\n";
    echo "Has Draft Value: ";
    var_dump($req['has_draft']);
}
