<?php
session_start();
echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$userRole = $_SESSION['user']['dept'] ?? 'USER'; // Check what 'dept' actually holds (used as role in controller?)
// Note: In DashboardController I used $_SESSION['user']['role'] ?? 'USER'.
// Let's check if 'role' key actually exists in session!

echo "<h2>Role Logic Check</h2>";
echo "User Role Key (['user']['role']): " . ($_SESSION['user']['role'] ?? 'UNDEFINED') . "<br>";
echo "User Dept Key (['user']['dept']): " . ($_SESSION['user']['dept'] ?? 'UNDEFINED') . "<br>";

$logicRole = $_SESSION['user']['role'] ?? 'USER';
$canManage = ($logicRole !== 'USER');

echo "Can Manage (Logic): " . ($canManage ? 'TRUE' : 'FALSE') . "<br>";
