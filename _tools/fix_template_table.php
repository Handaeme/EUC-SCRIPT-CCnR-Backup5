<?php
$config = require 'config/database.php';

$conn = sqlsrv_connect($config['host'], $config['options'] ?? []);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_templates' AND xtype='U')
CREATE TABLE script_templates (
    template_id INT IDENTITY(1,1) PRIMARY KEY,
    title VARCHAR(100),
    filename VARCHAR(255),
    filepath VARCHAR(255),
    uploaded_by VARCHAR(50),
    created_at DATETIME DEFAULT GETDATE()
)";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt) {
    echo "Table script_templates created/checked successfully.";
} else {
    echo "Error creating table: " . print_r(sqlsrv_errors(), true);
}
