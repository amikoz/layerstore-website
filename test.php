<?php
/**
 * Test script to verify PHP is working on Ionos
 */

// Set headers
header('Content-Type: application/json; charset=utf-8');

// Test response
echo json_encode([
    'success' => true,
    'message' => 'PHP работает корректно!',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'test' => 'order.php готов к использованию'
]);
?>
