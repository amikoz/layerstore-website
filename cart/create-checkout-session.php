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
// curl_close() is deprecated in PHP 8.5+, not needed anymore

$decoded = json_decode($response, true);

if ($httpCode >= 400) {
    $errorMessage = $decoded['error']['message'] ?? 'Stripe API error';
    http_response_code(500);
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Send immediate email notification about the checkout session
sendCheckoutNotificationEmail($decoded, $sanitizedItems);

echo json_encode([
    'success' => true,
    'url' => $decoded['url'],
    'sessionId' => $decoded['id']
]);

/**
 * Send email notification when checkout session is created
 */
function sendCheckoutNotificationEmail($sessionData, $items) {
    $toEmail = 'info@layerstore.eu';
    $subject = 'Neue Checkout Session gestartet - LayerStore';

    // Calculate total amount
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += $item['price_data']['unit_amount'] * $item['quantity'];
    }
    $totalInEur = number_format($totalAmount / 100, 2, ',', '.') . ' €';

    // Build email content
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #232E3D; color: #F0ECDA; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .order-details { background: white; padding: 15px; margin: 15px 0; border: 1px solid #ddd; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>LayerStore - Neue Zahlung gestartet</h2>
            </div>
            <div class='content'>
                <h3>Eine neue Zahlung wurde initiiert!</h3>
                <p>Ein Kunde hat den Checkout-Prozess gestartet.</p>

                <div class='order-details'>
                    <h4>Details:</h4>
                    <p><strong>Session ID:</strong> {$sessionData['id']}</p>
                    <p><strong>Gesamtbetrag:</strong> {$totalInEur}</p>
                    <p><strong>Anzahl Artikel:</strong> " . count($items) . "</p>
                    <p><strong>Checkout URL:</strong> <a href='{$sessionData['url']}'>Zur Zahlung</a></p>
                    <p><strong>Erstellt am:</strong> " . date('d.m.Y H:i') . "</p>
                </div>

                <p><strong>Hinweis:</strong></p>
                <ul>
                    <li>Diese E-Mail zeigt an, dass ein Kunde die Zahlungsseite aufgerufen hat</li>
                    <li>Die Zahlung ist noch nicht abgeschlossen</li>
                    <li>Wenn der Kunde bezahlt, erhältst du eine weitere Bestätigung</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Diese E-Mail wurde automatisch vom LayerStore Zahlungssystem generiert.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Send email
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: LayerStore <noreply@layerstore.eu>',
        'Reply-To: info@layerstore.eu',
        'X-Mailer: PHP/' . phpversion()
    ];

    if (mail($toEmail, $subject, $message, implode("\r\n", $headers))) {
        // Log successful email
        file_put_contents(__DIR__ . '/email.log', date('Y-m-d H:i:s') . ' - Checkout notification email sent for session ' . $sessionData['id'] . PHP_EOL, FILE_APPEND);
    } else {
        // Log failed email
        file_put_contents(__DIR__ . '/email.log', date('Y-m-d H:i:s') . ' - Failed to send checkout notification email for session ' . $sessionData['id'] . PHP_EOL, FILE_APPEND);
    }
}
