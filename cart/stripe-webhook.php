<?php
/**
 * Stripe Webhook Handler - Email Notifications
 *
 * Receives webhook events from Stripe and sends email notifications
 * for successful payments.
 */

header('Content-Type: application/json');

// CORS for production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Stripe-Signature');

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

// Webhook signing secret from Stripe Dashboard
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?? '';

// Get raw POST data
$input = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify webhook signature if secret is configured
if ($webhookSecret && $sigHeader) {
    $timestamp = '';
    $signature = '';

    // Split signature
    $parts = explode(',', $sigHeader);
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, 't=') === 0) {
            $timestamp = substr($part, 2);
        } elseif (strpos($part, 'v1=') === 0) {
            $signature = substr($part, 3);
        }
    }

    // Check timestamp tolerance (5 minutes)
    if (abs(time() - $timestamp) > 300) {
        http_response_code(400);
        echo json_encode(['error' => 'Timestamp too old']);
        exit;
    }

    // Verify signature
    $signedPayload = $timestamp . '.' . $input;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

    if (!hash_equals($signature, $expectedSignature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

$event = json_decode($input, true);

if (!$event || !isset($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Log webhook event for debugging
file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . ' - Event: ' . $event['type'] . PHP_EOL, FILE_APPEND);

// Handle different event types
switch ($event['type']) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];
        if ($session['payment_status'] === 'paid') {
            sendOrderConfirmationEmail($session);
            sendCustomerConfirmationEmail($session);
        }
        break;

    case 'payment_intent.succeeded':
        $paymentIntent = $event['data']['object'];
        sendPaymentSuccessEmail($paymentIntent);
        break;

    case 'payment_intent.payment_failed':
        $paymentIntent = $event['data']['object'];
        sendPaymentFailedEmail($paymentIntent);
        break;

    default:
        // Log unhandled events
        file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . ' - Unhandled event: ' . $event['type'] . PHP_EOL, FILE_APPEND);
        break;
}

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success', 'event' => $event['type']]);

/**
 * Send order confirmation email to store owner
 */
function sendOrderConfirmationEmail($session) {
    $toEmail = 'info@layerstore.eu';
    $subject = '✅ Neue Bestellung bei LayerStore - #' . substr($session['id'], -8);

    // Extract customer information
    $customerEmail = $session['customer_details']['email'] ?? 'Nicht angegeben';
    $customerName = $session['customer_details']['name'] ?? 'Kunde';
    $totalAmount = number_format($session['amount_total'] / 100, 2, ',', '.') . ' €';
    $createdDate = date('d.m.Y H:i', $session['created']);

    // Get line items from metadata or session
    $itemsInfo = '';
    if (isset($session['metadata']['items'])) {
        $items = json_decode($session['metadata']['items'], true);
        if ($items) {
            $itemsInfo = '<h4>Bestellte Artikel:</h4><ul>';
            foreach ($items as $item) {
                $itemsInfo .= '<li>' . htmlspecialchars($item['name'] ?? 'Produkt') . ' - ' .
                             number_format($item['price'] / 100, 2, ',', '.') . ' € x ' .
                             ($item['quantity'] ?? 1) . '</li>';
            }
            $itemsInfo .= '</ul>';
        }
    }

    // Build email content
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); color: #F0ECDA; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f9f9f9; }
            .order-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ea580c; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .highlight { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; }
            .btn { display: inline-block; padding: 12px 24px; background: #ea580c; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Neue Bestellung!</h1>
                <p>Eine neue Bestellung wurde erfolgreich bezahlt.</p>
            </div>
            <div class='content'>
                <div class='highlight'>
                    <strong>Bestellnummer:</strong> " . htmlspecialchars(substr($session['id'], -8)) . "<br>
                    <strong>Erstellt:</strong> $createdDate
                </div>

                <div class='order-details'>
                    <h3>👤 Kunde</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($customerName) . "</p>
                    <p><strong>Email:</strong> <a href='mailto:$customerEmail'>" . htmlspecialchars($customerEmail) . "</a></p>

                    <h3>💰 Zahlung</h3>
                    <p><strong>Gesamtbetrag:</strong> <span style='font-size: 18px; color: #ea580c; font-weight: bold;'>$totalAmount</span></p>
                    <p><strong>Status:</strong> ✅ Bezahlt</p>
                    <p><strong>Zahlungsart:</strong> Kartenzahlung (Stripe)</p>

                    $itemsInfo

                    <p style='margin-top: 15px;'><strong>Stripe Session ID:</strong> " . htmlspecialchars($session['id']) . "</p>
                </div>

                <p style='text-align: center;'>
                    <a href='https://dashboard.stripe.com/payments/" . $session['payment_intent'] . "' class='btn' target='_blank'>
                        In Stripe ansehen
                    </a>
                </p>
            </div>
            <div class='footer'>
                <p>Diese E-Mail wurde automatisch vom LayerStore Zahlungssystem generiert.</p>
                <p>© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.</p>
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
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1', // High priority
        'X-MSMail-Priority: High'
    ];

    $result = mail($toEmail, $subject, $message, implode("\r\n", $headers));

    // Log email result
    $logMessage = date('Y-m-d H:i:s') . ' - Order email ' . ($result ? 'SENT' : 'FAILED') .
                 " to $toEmail for order " . $session['id'] . " (Amount: $totalAmount)\n";
    file_put_contents(__DIR__ . '/email.log', $logMessage, FILE_APPEND);

    return $result;
}

/**
 * Send confirmation email to customer
 */
function sendCustomerConfirmationEmail($session) {
    $customerEmail = $session['customer_details']['email'] ?? null;
    $customerName = $session['customer_details']['name'] ?? 'Kunde';

    if (!$customerEmail) {
        return false;
    }

    $subject = 'Deine Bestellung bei LayerStore ist erfolgreich! ✅';
    $totalAmount = number_format($session['amount_total'] / 100, 2, ',', '.') . ' €';
    $orderId = substr($session['id'], -8);

    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); color: #F0ECDA; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f9f9f9; }
            .order-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Vielen Dank! 🎉</h1>
                <p>Deine Bestellung ist erfolgreich bei uns eingegangen.</p>
            </div>
            <div class='content'>
                <p>Hallo " . htmlspecialchars($customerName) . ",</p>
                <p>vielen Dank für deine Bestellung bei LayerStore!</p>

                <div class='order-details'>
                    <h3>Bestellübersicht</h3>
                    <p><strong>Bestellnummer:</strong> $orderId</p>
                    <p><strong>Betrag:</strong> $totalAmount</p>
                    <p><strong>Zahlungsstatus:</strong> ✅ Bezahlt</p>
                </div>

                <p>Wir werden deine Bestellung schnellstmöglich bearbeiten und dich per E-Mail über den Versand informieren.</p>

                <p>Falls du Fragen hast, antworte einfach auf diese E-Mail.</p>

                <p style='margin-top: 20px;'>Mit freundlichen Grüßen,<br><strong>Das LayerStore Team</strong></p>
            </div>
            <div class='footer'>
                <p>LayerStore - Individuelle 3D-Druck-Kreationen</p>
                <p>© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: LayerStore <noreply@layerstore.eu>',
        'Reply-To: info@layerstore.eu',
        'X-Mailer: PHP/' . phpversion()
    ];

    $result = mail($customerEmail, $subject, $message, implode("\r\n", $headers));

    $logMessage = date('Y-m-d H:i:s') . ' - Customer email ' . ($result ? 'SENT' : 'FAILED') .
                 " to $customerEmail for order $orderId\n";
    file_put_contents(__DIR__ . '/email.log', $logMessage, FILE_APPEND);

    return $result;
}

/**
 * Send payment success email (for payment_intent events)
 */
function sendPaymentSuccessEmail($paymentIntent) {
    $toEmail = 'info@layerstore.eu';
    $subject = '💰 Zahlung erhalten - Stripe Payment Intent';
    $amount = number_format($paymentIntent['amount'] / 100, 2, ',', '.') . ' ' . strtoupper($paymentIntent['currency']);

    $message = "
    <html>
    <head></head>
    <body>
        <h2>Zahlung erfolgreich</h2>
        <p><strong>Amount:</strong> $amount</p>
        <p><strong>Payment Intent ID:</strong> " . htmlspecialchars($paymentIntent['id']) . "</p>
        <p><strong>Status:</strong> " . htmlspecialchars($paymentIntent['status']) . "</p>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: LayerStore <noreply@layerstore.eu>'
    ];

    mail($toEmail, $subject, $message, implode("\r\n", $headers));
}

/**
 * Send payment failed email
 */
function sendPaymentFailedEmail($paymentIntent) {
    $toEmail = 'info@layerstore.eu';
    $subject = '❌ Zahlung fehlgeschlagen - Stripe';
    $amount = number_format($paymentIntent['amount'] / 100, 2, ',', '.') . ' ' . strtoupper($paymentIntent['currency']);

    $message = "
    <html>
    <head></head>
    <body>
        <h2>Zahlung fehlgeschlagen</h2>
        <p><strong>Amount:</strong> $amount</p>
        <p><strong>Payment Intent ID:</strong> " . htmlspecialchars($paymentIntent['id']) . "</p>
        <p><strong>Error:</strong> " . htmlspecialchars($paymentIntent['last_payment_error']['message'] ?? 'Unknown') . "</p>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: LayerStore <noreply@layerstore.eu>'
    ];

    mail($toEmail, $subject, $message, implode("\r\n", $headers));
}
