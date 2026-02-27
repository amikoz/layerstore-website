<?php
/**
 * Promo Codes API
 * Returns available promo codes to frontend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$promoFile = __DIR__ . '/admin/promo-codes.json';

if (file_exists($promoFile)) {
    echo file_get_contents($promoFile);
} else {
    // Default fallback if file doesn't exist
    echo json_encode([
        'OSTER2025' => [
            'discount' => 0.15,
            'description' => 'Oster-Sonderangebot'
        ]
    ]);
}
