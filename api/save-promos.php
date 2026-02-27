<?php
// Save promo codes (admin only)
header('Content-Type: application/json; charset=utf-8');

$promoFile = __DIR__ . '/data/promo-codes.json';

// Simple password protection
$password = $_POST['password'] ?? '';
$adminPassword = 'amikoz'; // Change this to a secure password!

if ($password !== $adminPassword) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get promo codes from POST body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['promos'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Save to file
file_put_contents($promoFile, json_encode($data['promos'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
