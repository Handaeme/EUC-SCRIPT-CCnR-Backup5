<?php
// _tools/test_filter_split.php
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
require_once __DIR__ . '/../app/models/RequestModel.php';

// Prepare Environment (if needed by RequestModel internal logic or db_connect)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    App\Helpers\EnvLoader::load($envPath);
}

// Instantiate Model
$model = new App\Models\RequestModel();

echo "Testing getDistinctRequestValues('produk')...\n";
$products = $model->getDistinctRequestValues('produk');

echo "Result:\n";
print_r($products);

echo "\nTesting getDistinctRequestValues('media')...\n";
$media = $model->getDistinctRequestValues('media');
print_r($media);

echo "\nTest logic check:\n";
// Manually check if any result has comma
$hasComma = false;
foreach ($products as $p) {
    if (strpos($p, ',') !== false) {
        $hasComma = true;
        echo "[FAIL] Found comma in product: '$p'\n";
    }
}

if (!$hasComma) {
    echo "[PASS] No commas found in products (Splitting works!)\n";
}
?>
