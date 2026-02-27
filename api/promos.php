<?php
// Simple promo codes API
// Returns all promo codes as JSON
header('Content-Type: application/json; charset=utf-8');

$promoFile = __DIR__ . '/data/promo-codes.json';

// Create data directory if not exists
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Default promo codes if file doesn't exist
if (!file_exists($promoFile)) {
    $default = [
        'OSTER2025' => [
            'discount' => 0.15,
            'description' => 'Oster-Sonderangebot'
        ],
        'SOMMER20' => [
            'discount' => 0.20,
            'description' => 'Sommer-Angebot'
        ]
    ];
    file_put_contents($promoFile, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode($default);
    exit;
}

echo file_get_contents($promoFile);
