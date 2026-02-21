<?php
require_once 'app/core/Controller.php';
require_once 'app/core/Database.php';
require_once 'app/models/RequestModel.php';

// Mock DB connection inside Model (or just use raw model if possible)
// Since Model extends nothing that requires complex setup, I can instantiate it.
// Wait, Controller base class might be needed or just Database config.
// RequestModel constructor handles DB connection.

$model = new App\Models\RequestModel();
$spvs = $model->getSupervisors();

echo "<h1>Testing getSupervisors()</h1>";
echo "<pre>";
print_r($spvs);
echo "</pre>";

if (empty($spvs)) {
    echo "NO SPVS FOUND. Check Database group_name vs Query.";
} else {
    echo "SUCCESS: " . count($spvs) . " SPV(s) found.";
}
