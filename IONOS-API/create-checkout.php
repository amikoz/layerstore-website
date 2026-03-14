<?php
/**
 * Stripe Checkout Session API
 * Создаёт checkout session и возвращает URL для редиректа
 *
 * Endpoint: /api/create-checkout.php
 * Method: POST
 * Content-Type: application/json
 */

header('Content-Type: application/json');

// CORS - разрешить запросы с GitHub Pages и layerstore.eu
$allowedOrigins = [
    'https://layerstore.eu',
    'https://www.layerstore.eu',
    'https://amikoz.github.io',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// OPTIONS request для CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ==================== CONFIGURATION ====================

// Stripe Secret Key (TEST MODE)
// Erhalte diesen Key von: https://dashboard.stripe.com/test/apikeys
$stripeSecretKey = 'sk_test_...'; // Hier deinen Secret Key einfügen

// Базовые URL
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $protocol = 'https';
} else {
    $protocol = 'http';
}
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host;

// Default URLs
$defaultSuccessUrl = $baseUrl . '/cart_test/stripe-test.html?success=true';
$defaultCancelUrl = $baseUrl . '/cart_test/stripe-test.html?canceled=true';

// ========================================================

// Получаем данные из тела запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Проверяем items
$items = $input['items'] ?? [];
if (empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'No items provided']);
    exit;
}

// Формируем line_items для Stripe
$lineItems = [];
$totalAmount = 0;

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
            ],
            'unit_amount' => $amountInCents,
        ],
        'quantity' => $quantity,
    ];

    $totalAmount += $amountInCents * $quantity;
}

// Success/Cancel URLs
$successUrl = $input['successUrl'] ?? $defaultSuccessUrl;
$cancelUrl = $input['cancelUrl'] ?? $defaultCancelUrl;

// Customer email (если есть)
$customerEmail = $input['customerEmail'] ?? null;

// ==================== STRIPE API CALL ====================

function createStripeCheckoutSession($lineItems, $successUrl, $cancelUrl, $customerEmail, $secretKey) {
    $ch = curl_init();

    $postData = [
        'payment_method_types' => ['card', 'sofort', 'paypal'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'locale' => 'de',
    ];

    if ($customerEmail) {
        $postData['customer_email'] = $customerEmail;
    }

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
        throw new Exception($errorMessage);
    }

    return $decoded;
}

// ==================== EXECUTE ====================

try {
    $session = createStripeCheckoutSession($lineItems, $successUrl, $cancelUrl, $customerEmail, $stripeSecretKey);

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
