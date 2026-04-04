<?php
/**
 * Stripe Checkout Session API - Production Ready v2.0
 *
 * Features:
 * - Promo Code Support (native Stripe discounts)
 * - Shipping Options
 * - Customer Data Collection (phone, address)
 * - Enhanced Metadata
 * - Tax Calculation Support
 * - Upsell Products
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

// Extract options
$customerEmail = $input['customer_email'] ?? null;
$promoCode = $input['promo_code'] ?? null;
$enablePromoCode = $input['enable_promo_code'] ?? true;
$collectPhone = $input['collect_phone'] ?? true;
$collectAddress = $input['collect_address'] ?? false;
$shippingOptions = $input['shipping_options'] ?? null;
$upsellItems = $input['upsell_items'] ?? [];
$metadata = $input['metadata'] ?? [];
$clientReferenceId = $input['client_reference_id'] ?? null;
$customerId = $input['customer_id'] ?? null;

// Sanitize and collect product data
$sanitizedItems = [];
$productsForMetadata = [];
$totalAmount = 0;

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
                'description' => substr($productDescription, 0, 500),
                'metadata' => [
                    'type' => 'main_product'
                ]
            ],
            'unit_amount' => $unitAmount,
            'tax_behavior' => 'exclusive' // Add tax calculation support
        ],
        'quantity' => $quantity
    ];

    // Add image if available
    if (isset($item['price_data']['product_data']['images']) && !empty($item['price_data']['product_data']['images'])) {
        $sanitizedItem['price_data']['product_data']['images'] = $item['price_data']['product_data']['images'];
    }

    // Add product-specific metadata
    if (isset($item['price_data']['product_data']['metadata'])) {
        $sanitizedItem['price_data']['product_data']['metadata'] = array_merge(
            $sanitizedItem['price_data']['product_data']['metadata'],
            $item['price_data']['product_data']['metadata']
        );
    }

    $sanitizedItems[] = $sanitizedItem;

    // Collect for metadata
    $productsForMetadata[] = [
        'name' => $productName,
        'price' => $unitAmount,
        'quantity' => $quantity,
        'type' => 'main'
    ];

    $totalAmount += $unitAmount * $quantity;
}

// Add upsell items
foreach ($upsellItems as $item) {
    if (!isset($item['price_data']['unit_amount']) || $item['price_data']['unit_amount'] <= 0) {
        continue;
    }

    $productName = $item['price_data']['product_data']['name'] ?? 'LayerStore Zusatzprodukt';
    $unitAmount = intval($item['price_data']['unit_amount']);
    $quantity = $item['quantity'] ?? 1;

    $sanitizedItem = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => substr($productName, 0, 500),
                'description' => $item['price_data']['product_data']['description'] ?? '',
                'metadata' => [
                    'type' => 'upsell'
                ]
            ],
            'unit_amount' => $unitAmount,
            'tax_behavior' => 'exclusive'
        ],
        'quantity' => $quantity
    ];

    if (isset($item['price_data']['product_data']['images']) && !empty($item['price_data']['product_data']['images'])) {
        $sanitizedItem['price_data']['product_data']['images'] = $item['price_data']['product_data']['images'];
    }

    $sanitizedItems[] = $sanitizedItem;

    $productsForMetadata[] = [
        'name' => $productName,
        'price' => $unitAmount,
        'quantity' => $quantity,
        'type' => 'upsell'
    ];

    $totalAmount += $unitAmount * $quantity;
}

if (empty($sanitizedItems)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid items']);
    exit;
}

// Build session metadata
$sessionMetadata = array_merge([
    'created_at' => date('Y-m-d H:i:s'),
    'source' => 'layerstore.eu',
    'total_amount' => $totalAmount,
    'items_count' => count($productsForMetadata)
], $metadata);

// Add items as JSON (truncated if needed)
$itemsJson = json_encode($productsForMetadata);
if (strlen($itemsJson) <= 500) {
    $sessionMetadata['items'] = $itemsJson;
} else {
    $sessionMetadata['items_summary'] = substr($itemsJson, 0, 497) . '...';
}

// Add customer email to metadata if provided
if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    $sessionMetadata['customer_email'] = $customerEmail;
}

// Add promo code to metadata if provided
if ($promoCode) {
    $sessionMetadata['promo_code'] = $promoCode;
}

// Prepare checkout session data
$postData = [
    'payment_method_types' => ['card', 'sofort', 'paypal'], // Support multiple payment methods
    'line_items' => $sanitizedItems,
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'metadata' => $sessionMetadata,
    'expires_at' => time() + (4 * 60 * 60), // Session expires in 4 hours
    'billing_address_collection' => $collectAddress ? 'required' : 'auto',
    'customer_email' => $customerEmail
];

// Add customer creation/update
if ($customerId) {
    $postData['customer'] = $customerId;
    $postData['customer_update'] = [
        'name' => 'auto',
        'address' => 'auto'
    ];
}

// Add phone number collection
if ($collectPhone) {
    $postData['phone_number_collection'] = [
        'enabled' => true
    ];
}

// Add shipping options
if ($shippingOptions && is_array($shippingOptions)) {
    $postData['shipping_options'] = [];

    foreach ($shippingOptions as $option) {
        $shippingOption = [
            'shipping_rate_data' => [
                'type' => 'fixed_amount',
                'fixed_amount' => [
                    'amount' => intval($option['amount'] ?? 0),
                    'currency' => 'eur',
                    'tax_behavior' => 'exclusive'
                ],
                'display_name' => substr($option['name'] ?? 'Standard Versand', 50),
                'delivery_estimate' => [
                    'minimum' => [
                        'unit' => $option['min_unit'] ?? 'business_day',
                        'value' => intval($option['min_value'] ?? 3)
                    ],
                    'maximum' => [
                        'unit' => $option['max_unit'] ?? 'business_day',
                        'value' => intval($option['max_value'] ?? 7)
                    ]
                ]
            ]
        ];

        // Add tax code if provided
        if (isset($option['tax_code'])) {
            $shippingOption['shipping_rate_data']['tax_code'] = $option['tax_code'];
        }

        $postData['shipping_options'][] = $shippingOption;
    }
}

// Add promo code support (native Stripe discounts)
if ($enablePromoCode) {
    $postData['allow_promotion_codes'] = true;
}

// Add client reference ID for order tracking
if ($clientReferenceId) {
    $postData['client_reference_id'] = substr($clientReferenceId, 250);
}

// Add tax calculation (optional - requires Stripe Tax)
if ($input['enable_tax'] ?? false) {
    $postData['automatic_tax'] = [
        'enabled' => true
    ];
}

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2023-10-16' // Use specific API version
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// curl_close() is deprecated in PHP 8.5+, not needed anymore

$decoded = json_decode($response, true);

if ($httpCode >= 400 || !isset($decoded['id'])) {
    $errorMessage = $decoded['error']['message'] ?? 'Stripe API error';
    $errorCode = $decoded['error']['code'] ?? 'unknown_error';
    http_response_code(500);
    echo json_encode([
        'error' => $errorMessage,
        'code' => $errorCode,
        'details' => $decoded['error'] ?? []
    ]);
    exit;
}

// Success response
echo json_encode([
    'success' => true,
    'url' => $decoded['url'],
    'sessionId' => $decoded['id'],
    'customerEmail' => $decoded['customer_details']['email'] ?? $customerEmail,
    'amountTotal' => $decoded['amount_total'] ?? 0,
    'currency' => $decoded['currency'] ?? 'eur',
    'paymentStatus' => $decoded['payment_status'] ?? null
]);
