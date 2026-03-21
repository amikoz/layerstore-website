<?php
/**
 * Stripe Checkout Session API
 * Creates a checkout session and returns URL for redirect
 *
 * Endpoint: /api/create-checkout-session.php
 * Method: POST
 * Content-Type: application/json
 */

header('Content-Type: application/json');

// CORS - allow requests from all origins for IONOS hosting
$allowedOrigins = [
    'https://layerstore.eu',
    'https://www.layerstore.eu',
    'https://layerstore.com',
    'https://amikoz.github.io',
    'http://localhost:3000',
    'http://localhost:8000',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000'
];

// Allow all for development (remove in production if needed)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
if ($origin === '*' || in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . ($origin === '*' ? '*' : $origin));
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// OPTIONS request for CORS preflight
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

// ==================== CONFIGURATION ====================

// Stripe Secret Key (TEST MODE)
// Get this key from: https://dashboard.stripe.com/test/apikeys
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_...';

// Base URLs
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $protocol = 'https';
} else {
    $protocol = 'http';
}
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host;

// Default URLs
$defaultSuccessUrl = $baseUrl . '/cart_test/?session_id={CHECKOUT_SESSION_ID}';
$defaultCancelUrl = $baseUrl . '/cart_test/?canceled=true';

// ========================================================

// Get data from request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Accept line_items directly (Stripe format) or items array
$lineItems = $input['line_items'] ?? [];
$totalAmount = 0;

// If line_items is empty, try items array (legacy format)
if (empty($lineItems)) {
    $items = $input['items'] ?? [];
    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'No items provided']);
        exit;
    }
    // Convert items to line_items format
    foreach ($items as $item) {
        $price = floatval($item['price'] ?? 0);
        $quantity = intval($item['quantity'] ?? 1);
        $amountInCents = round($price * 100);

        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $item['name'] ?? 'Produkt',
                    'description' => $item['description'] ?? '',
                    'images' => $item['images'] ?? [],
                ],
                'unit_amount' => $amountInCents,
            ],
            'quantity' => $quantity,
        ];

        $totalAmount += $amountInCents * $quantity;
    }
} else {
    // Calculate total from provided line_items
    foreach ($lineItems as $item) {
        $amount = intval($item['price_data']['unit_amount'] ?? 0);
        $quantity = intval($item['quantity'] ?? 1);
        $totalAmount += $amount * $quantity;
    }
}

// Success/Cancel URLs (support both naming conventions)
$successUrl = $input['success_url'] ?? $input['successUrl'] ?? $defaultSuccessUrl;
$cancelUrl = $input['cancel_url'] ?? $input['cancelUrl'] ?? $defaultCancelUrl;

// Customer email (optional)
$customerEmail = $input['customerEmail'] ?? null;

// Customer creation (optional)
$createCustomer = $input['createCustomer'] ?? false;

// Metadata (optional)
$metadata = $input['metadata'] ?? [];

// ==================== STRIPE API CALL ====================

function createStripeCheckoutSession($lineItems, $successUrl, $cancelUrl, $customerEmail, $createCustomer, $metadata, $secretKey) {
    $ch = curl_init();

    $postData = [
        'payment_method_types' => ['card', 'sofort', 'paypal', 'sepa_debit'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'locale' => 'de',
        'metadata' => $metadata,
    ];

    if ($customerEmail) {
        $postData['customer_email'] = $customerEmail;
    }

    if ($createCustomer && $customerEmail) {
        $postData['customer_creation'] = 'always';
    }

    // Add expriation time (30 minutes)
    $postData['expires_at'] = time() + 1800;

    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        $errorMessage = $decoded['error']['message'] ?? 'Stripe API error';
        $errorType = $decoded['error']['type'] ?? 'api_error';
        throw new Exception($errorMessage . ' (' . $errorType . ')');
    }

    return $decoded;
}

// ==================== EXECUTE ====================

try {
    $session = createStripeCheckoutSession($lineItems, $successUrl, $cancelUrl, $customerEmail, $createCustomer, $metadata, $stripeSecretKey);

    echo json_encode([
        'success' => true,
        'url' => $session['url'],
        'sessionId' => $session['id'],
        'totalAmount' => $totalAmount,
        'totalFormatted' => number_format($totalAmount / 100, 2, ',', '') . ' €'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
