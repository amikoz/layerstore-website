<?php
/**
 * Stripe Checkout Session API - Production Ready
 *
 * Load Stripe key from config file (not in git)
 */

header('Content-Type: application/json');

// CORS for production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load Stripe configuration
if (file_exists(__DIR__ . '/stripe-config.php')) {
    $stripeSecretKey = include __DIR__ . '/stripe-config.php';
} else {
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?? null;
}

if (!$stripeSecretKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe not configured']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$lineItems = $input['line_items'] ?? [];

if (empty($lineItems)) {
    http_response_code(400);
    echo json_encode(['error' => 'No items provided']);
    exit;
}

$successUrl = $input['success_url'] ?? 'https://layerstore.eu/cart/?success=true&session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $input['cancel_url'] ?? 'https://layerstore.eu/cart/?canceled=true';

// Create Stripe session
$ch = curl_init();

$sanitizedItems = [];
foreach ($lineItems as $item) {
    if (!isset($item['price_data']['unit_amount']) || $item['price_data']['unit_amount'] <= 0) {
        continue;
    }

    $sanitizedItems[] = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'LayerStore Produkt'
            ],
            'unit_amount' => intval($item['price_data']['unit_amount'])
        ],
        'quantity' => 1
    ];
}

if (empty($sanitizedItems)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid items']);
    exit;
}

$postData = [
    'payment_method_types' => ['card'],
    'line_items' => $sanitizedItems,
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl
];

curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

if ($httpCode >= 400) {
    $errorMessage = $decoded['error']['message'] ?? 'Stripe API error';
    http_response_code(500);
    echo json_encode(['error' => $errorMessage]);
    exit;
}

echo json_encode([
    'success' => true,
    'url' => $decoded['url'],
    'sessionId' => $decoded['id']
]);
