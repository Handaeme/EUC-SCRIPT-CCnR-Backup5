<?php
// _worker_create.php
// Worker script to be called by _simulate_race.php

if ($argc < 3) {
    die("Usage: php _worker_create.php [Title] [UserID]\n");
}

$title = $argv[1];
$userId = $argv[2];

// Mock Session for Init
session_start();
$_SESSION['user'] = ['userid' => $userId, 'role' => 'Maker'];

require_once 'app/init.php';
require_once 'app/models/RequestModel.php';

$model = new App\Models\RequestModel($conn);

$data = [
    'title' => $title,
    'jenis' => 'Konvensional',
    'produk' => 'TestProduct', // Dummy
    'kategori' => 'TestCategory',
    'media' => 'WhatsApp',
    'mode' => 'FREE_INPUT',
    'creator_id' => $userId,
    'selected_spv' => 'SPV01' // Dummy
];

echo "Worker attempting createRequest for '$title'...\n";

// Call Create Request
$result = $model->createRequest($data);

if (isset($result['error'])) {
    echo "[ERROR] " . $result['error'] . "\n";
} else {
    echo "[SUCCESS] Created ID: " . $result['id'] . "\n";
}
