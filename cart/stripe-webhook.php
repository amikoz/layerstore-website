<?php
/**
 * Stripe Webhook Handler - Email Notifications
 *
 * Receives webhook events from Stripe and sends email notifications
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

// Webhook signing secret (you'll get this from Stripe Dashboard)
// For now, we'll skip signature verification in test mode
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?? '';

// Get raw POST data
$input = file_get_contents('php://input');
$event = json_decode($input, true);

if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Log webhook event for debugging
file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . ' - Event: ' . $event['type'] . PHP_EOL, FILE_APPEND);

// Handle successful checkout session
if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'];

    // Only process successful payments
    if ($session['payment_status'] === 'paid') {
        sendOrderConfirmationEmail($session);
    }
}

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success']);

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($session) {
    $toEmail = 'info@layerstore.eu';
    $subject = 'Neue Bestellung bei LayerStore - #' . $session['id'];

    // Extract customer information
    $customerEmail = $session['customer_details']['email'] ?? 'Nicht angegeben';
    $customerName = $session['customer_details']['name'] ?? 'Kunde';
    $totalAmount = ($session['amount_total'] / 100) . ' ' . strtoupper($session['currency']);

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
                <h2>LayerStore - Neue Bestellung</h2>
            </div>
            <div class='content'>
                <h3>Bestellung erfolgreich!</h3>
                <p>Eine neue Bestellung wurde erfolgreich bezahlt.</p>

                <div class='order-details'>
                    <h4>BestellDetails:</h4>
                    <p><strong>Bestellnummer:</strong> {$session['id']}</p>
                    <p><strong>Kunde:</strong> {$customerName}</p>
                    <p><strong>Email:</strong> {$customerEmail}</p>
                    <p><strong>Gesamtbetrag:</strong> {$totalAmount}</p>
                    <p><strong>Zahlungsstatus:</strong> Bezahlt</p>
                    <p><strong>Erstellt am:</strong> " . date('d.m.Y H:i', $session['created']) . "</p>
                </div>

                <p><strong>Weitere Details:</strong></p>
                <ul>
                    <li>Zahlungsmethode: Kartenzahlung</li>
                    <li>Stripe Session ID: {$session['id']}</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Diese E-Mail wurde automatisch vom LayerStore Zahlungssystem generiert.</p>
                <p>Bitte antworten Sie nicht auf diese E-Mail.</p>
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
        file_put_contents(__DIR__ . '/email.log', date('Y-m-d H:i:s') . ' - Email sent to ' . $toEmail . ' for order ' . $session['id'] . PHP_EOL, FILE_APPEND);
    } else {
        // Log failed email
        file_put_contents(__DIR__ . '/email.log', date('Y-m-d H:i:s') . ' - Failed to send email for order ' . $session['id'] . PHP_EOL, FILE_APPEND);
    }
}
