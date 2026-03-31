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

// Sanitize and collect product data
$sanitizedItems = [];
$productsForMetadata = [];

foreach ($lineItems as $item) {
    if (!isset($item['price_data']['unit_amount']) || $item['price_data']['unit_amount'] <= 0) {
        continue;
    }

    // Extract product name and description
    $productName = $item['price_data']['product_data']['name'] ?? 'LayerStore Produkt';
    $productDescription = $item['price_data']['product_data']['description'] ?? '';
    $unitAmount = intval($item['price_data']['unit_amount']);
    $quantity = $item['quantity'] ?? 1;

    $sanitizedItem = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => substr($productName, 0, 500), // Stripe max length
                'description' => substr($productDescription, 0, 500)
            ],
            'unit_amount' => $unitAmount
        ],
        'quantity' => $quantity
    ];

    // Add image if available
    if (isset($item['price_data']['product_data']['images']) && !empty($item['price_data']['product_data']['images'])) {
        $sanitizedItem['price_data']['product_data']['images'] = $item['price_data']['product_data']['images'];
    }

    $sanitizedItems[] = $sanitizedItem;

    // Collect for metadata (size limit is 500 chars for each metadata value)
    $productsForMetadata[] = [
        'name' => $productName,
        'price' => $unitAmount,
        'quantity' => $quantity
    ];
}

if (empty($sanitizedItems)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid items']);
    exit;
}

// Prepare metadata (Stripe has limits on metadata size)
$metadata = [
    'created_at' => date('Y-m-d H:i:s'),
    'source' => 'layerstore.eu'
];

// Add items as JSON (truncated if needed)
$itemsJson = json_encode($productsForMetadata);
if (strlen($itemsJson) > 500) {
    // If too long, just store count and total
    $metadata['items_count'] = count($productsForMetadata);
    $total = 0;
    foreach ($productsForMetadata as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    $metadata['total_amount'] = $total;
} else {
    $metadata['items'] = $itemsJson;
}

// Add customer email if provided
if (isset($input['customer_email']) && filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
    $metadata['customer_email'] = $input['customer_email'];
    $postData['customer_email'] = $input['customer_email'];
}

$postData = [
    'payment_method_types' => ['card'],
    'line_items' => $sanitizedItems,
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'metadata' => $metadata
];

// Add phone number collection if enabled
if (isset($input['collect_phone']) && $input['collect_phone']) {
    $postData['phone_number_collection'] = [
        'enabled' => true
    ];
}

$ch = curl_init();
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
// curl_close() is deprecated in PHP 8.5+, not needed anymore

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
