<?php
/**
 * Stripe Payment Intent API
 * Creates a payment intent for custom payment forms
 * Use this for direct Stripe Elements integration (not Checkout)
 *
 * Endpoint: /api/create-payment-intent.php
 * Method: POST
 * Content-Type: application/json
 *
 * Requires Stripe.js on frontend to confirm payment
 */

header('Content-Type: application/json');

// CORS - allow requests from GitHub Pages and layerstore.eu
$allowedOrigins = [
    'https://layerstore.eu',
    'https://www.layerstore.eu',
    'https://layerstore.com',
    'https://amikoz.github.io',
    'http://localhost:3000',
    'http://127.0.0.1:3000'
];

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

// Stripe Publishable Key (for frontend)
$stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_...';

// ========================================================

// Get data from request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Extract amount
$amount = $input['amount'] ?? 0;
if (empty($amount)) {
    // Calculate from items if no direct amount
    $items = $input['items'] ?? [];
    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'No amount or items provided']);
        exit;
    }

    foreach ($items as $item) {
        $price = floatval($item['price'] ?? 0);
        $quantity = intval($item['quantity'] ?? 1);
        $amount += $price * $quantity;
    }
}

// Convert to cents
$amountInCents = round(floatval($amount) * 100);

// Minimum amount (50 cents = 0.50 EUR)
if ($amountInCents < 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Minimum amount is 0.50 EUR']);
    exit;
}

// Currency (default EUR)
$currency = strtolower($input['currency'] ?? 'eur');

// Customer email (optional)
$customerEmail = $input['customerEmail'] ?? null;

// Metadata (optional)
$metadata = $input['metadata'] ?? [];

// Add items to metadata
if (!empty($input['items'])) {
    $metadata['items'] = json_encode($input['items']);
}

// Payment method types (optional, defaults to card)
$paymentMethodTypes = $input['paymentMethodTypes'] ?? ['card'];

// Capture method (automatic or manual)
$captureMethod = $input['captureMethod'] ?? 'automatic';

// ==================== STRIPE API CALL ====================

function createPaymentIntent($amount, $currency, $customerEmail, $metadata, $paymentMethodTypes, $captureMethod, $secretKey) {
    $ch = curl_init();

    $postData = [
        'amount' => $amount,
        'currency' => $currency,
        'payment_method_types' => $paymentMethodTypes,
        'capture_method' => $captureMethod,
        'metadata' => $metadata,
        'description' => $metadata['description'] ?? 'LayerStore Bestellung',
    ];

    // Add customer email if provided
    if ($customerEmail) {
        // First create/get customer
        $customerData = createOrGetCustomer($customerEmail, $secretKey);
        if ($customerData) {
            $postData['customer'] = $customerData['id'];
        }
    }

    // Setup future usage for saved cards
    if (!empty($metadata['setup_future_usage'])) {
        $postData['setup_future_usage'] = $metadata['setup_future_usage'];
    }

    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
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

function createOrGetCustomer($email, $secretKey) {
    $ch = curl_init();

    $postData = [
        'email' => $email,
    ];

    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/customers');
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

    if ($httpCode >= 400) {
        return null;
    }

    return json_decode($response, true);
}

// ==================== EXECUTE ====================

try {
    $paymentIntent = createPaymentIntent(
        $amountInCents,
        $currency,
        $customerEmail,
        $metadata,
        $paymentMethodTypes,
        $captureMethod,
        $stripeSecretKey
    );

    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent['client_secret'],
        'paymentIntentId' => $paymentIntent['id'],
        'amount' => $paymentIntent['amount'],
        'amountFormatted' => number_format($paymentIntent['amount'] / 100, 2, ',', '') . ' €',
        'currency' => strtoupper($paymentIntent['currency']),
        'status' => $paymentIntent['status'],
        'publishableKey' => $stripePublishableKey
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
