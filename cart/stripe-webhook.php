<?php
/**
 * Stripe Webhook Handler - Resend API Integration
 *
 * Receives webhook events from Stripe and sends email notifications
 * via Resend API for reliable delivery.
 * Also saves orders to the OrderStorage system.
 */

header('Content-Type: application/json');

// CORS for production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Stripe-Signature');

// Load dependencies
require_once __DIR__ . '/../email/config.php';
require_once __DIR__ . '/../email/ResendEmailService.php';
require_once __DIR__ . '/../orders/storage.php';

use LayerStore\Email\ResendEmailService;
use LayerStore\Email\EmailConfig;
use LayerStore\Orders\OrderStorage;

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

// Initialize email config
EmailConfig::init();

// Initialize order storage
OrderStorage::init();

// Load Stripe configuration
if (file_exists(__DIR__ . '/stripe-config.php')) {
    $stripeSecretKey = include __DIR__ . '/stripe-config.php';
} else {
    $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
}

if (!$stripeSecretKey) {
    EmailConfig::log('Stripe not configured - missing STRIPE_SECRET_KEY', 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Stripe not configured']);
    exit;
}

// Webhook signing secret from environment
$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

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
    if (abs(time() - (int)$timestamp) > 300) {
        EmailConfig::log('Webhook timestamp too old', 'WARNING');
        http_response_code(400);
        echo json_encode(['error' => 'Timestamp too old']);
        exit;
    }

    // Verify signature
    $signedPayload = $timestamp . '.' . $input;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

    if (!hash_equals($signature, $expectedSignature)) {
        EmailConfig::log('Invalid webhook signature', 'WARNING');
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

// Log webhook event
EmailConfig::log("Stripe webhook received: {$event['type']}", 'INFO');

// Handle different event types
switch ($event['type']) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];
        if ($session['payment_status'] === 'paid') {
            handleCheckoutCompleted($session);
        }
        break;

    case 'payment_intent.succeeded':
        $paymentIntent = $event['data']['object'];
        handlePaymentSucceeded($paymentIntent);
        break;

    case 'payment_intent.payment_failed':
        $paymentIntent = $event['data']['object'];
        handlePaymentFailed($paymentIntent);
        break;

    case 'invoice.paid':
        $invoice = $event['data']['object'];
        handleInvoicePaid($invoice);
        break;

    default:
        EmailConfig::log("Unhandled webhook event: {$event['type']}", 'INFO');
        break;
}

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success', 'event' => $event['type']]);

/**
 * Handle checkout.session.completed event
 */
function handleCheckoutCompleted(array $session): void
{
    $customerEmail = $session['customer_details']['email'] ?? '';
    $customerName = $session['customer_details']['name'] ?? 'Kunde';

    // Generate order ID
    $orderId = 'LS-' . date('Y') . '-' . strtoupper(substr($session['id'], -8));

    // Parse items from metadata
    $items = [];
    if (isset($session['metadata']['items'])) {
        $parsedItems = json_decode($session['metadata']['items'], true);
        if (is_array($parsedItems)) {
            $items = $parsedItems;
        }
    }

    // Save order to database
    try {
        $orderData = [
            'order_id' => $orderId,
            'stripe_session_id' => $session['id'],
            'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $session['customer_details']['phone'] ?? null,
            'items' => $items,
            'subtotal' => (int)($session['amount_subtotal'] ?? 0),
            'tax' => (int)($session['total_details']['amount_tax'] ?? 0),
            'shipping' => (int)(($session['total_details']['amount_shipping'] ?? 0) + ($session['shipping_cost']['amount_total'] ?? 0)),
            'total' => (int)($session['amount_total'] ?? 0),
            'currency' => strtoupper($session['currency'] ?? 'eur'),
            'status' => 'pending',
            'shipping_address' => isset($session['shipping']) ? [
                'name' => $session['shipping']['name'] ?? '',
                'address' => $session['shipping']['address'] ?? []
            ] : null,
            'billing_address' => isset($session['customer_details']['address']) ? [
                'name' => $customerName,
                'address' => $session['customer_details']['address']
            ] : null,
            'metadata' => $session['metadata'] ?? []
        ];

        $savedOrderId = OrderStorage::save($orderData);
        EmailConfig::log("Order saved to database: {$savedOrderId}", 'INFO');
    } catch (\Exception $e) {
        EmailConfig::log("Failed to save order: " . $e->getMessage(), 'ERROR');
    }

    // Format amount
    $totalAmount = number_format(($session['amount_total'] ?? 0) / 100, 2, ',', '.') . ' €';
    $createdDate = date('d.m.Y H:i', $session['created']);

    // Build items HTML for email
    $itemsHtml = '';
    if (!empty($items)) {
        $itemsHtml = '<h4 style="margin: 15px 0 10px; color: #232E3D;">Bestellte Artikel:</h4><ul style="margin: 0; padding-left: 20px;">';
        foreach ($items as $item) {
            $price = number_format(($item['price'] ?? 0) / 100, 2, ',', '.') . ' €';
            $itemsHtml .= '<li style="margin: 5px 0;">' . htmlspecialchars($item['name'] ?? 'Produkt') . ' - ' . $price . ' x ' . ($item['quantity'] ?? 1) . '</li>';
        }
        $itemsHtml .= '</ul>';
    }

    // Stripe URL
    $stripeUrl = !empty($session['payment_intent'])
        ? 'https://dashboard.stripe.com/payments/' . $session['payment_intent']
        : '';

    // ===== Send email to store owner =====
    $ownerHtml = getOwnerEmailHtml($orderId, $customerName, $customerEmail, $totalAmount, $createdDate, $itemsHtml, $stripeUrl, $session['id']);
    $ownerText = getOwnerEmailText($orderId, $customerName, $customerEmail, $totalAmount, $createdDate, $items, $session['id']);

    $ownerEmail = new ResendEmailService(EmailConfig::$defaultRecipient, "✅ Neue Bestellung bei LayerStore - #{$orderId}");
    $ownerEmail->content($ownerHtml, $ownerText);
    $ownerResult = $ownerEmail->send();

    EmailConfig::log("Owner notification " . ($ownerResult['success'] ? 'SENT' : 'FAILED') . " for order {$orderId}", $ownerResult['success'] ? 'INFO' : 'ERROR');

    // ===== Send confirmation to customer =====
    if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $customerHtml = getCustomerEmailHtml($customerName, $orderId, $totalAmount);
        $customerText = getCustomerEmailText($customerName, $orderId, $totalAmount);

        $customerEmailObj = new ResendEmailService($customerEmail, 'Deine Bestellung bei LayerStore ist erfolgreich! ✅');
        $customerEmailObj->content($customerHtml, $customerText);
        $customerResult = $customerEmailObj->send();

        EmailConfig::log("Customer confirmation " . ($customerResult['success'] ? 'SENT' : 'FAILED') . " to {$customerEmail}", $customerResult['success'] ? 'INFO' : 'ERROR');
    }
}

/**
 * Handle payment_intent.succeeded event
 */
function handlePaymentSucceeded(array $paymentIntent): void
{
    $amount = number_format(($paymentIntent['amount'] ?? 0) / 100, 2, ',', '.') . ' ' . strtoupper($paymentIntent['currency'] ?? 'eur');

    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #10b981;'>💰 Zahlung erfolgreich</h2>
        <p><strong>Betrag:</strong> {$amount}</p>
        <p><strong>Payment Intent ID:</strong> {$paymentIntent['id']}</p>
        <p><strong>Status:</strong> {$paymentIntent['status']}</p>
    </div>";

    $text = "Zahlung erfolgreich\n\nBetrag: {$amount}\nPayment Intent ID: {$paymentIntent['id']}\nStatus: {$paymentIntent['status']}";

    $email = new ResendEmailService(EmailConfig::$defaultRecipient, '💰 Zahlung erhalten - Stripe');
    $email->content($html, $text);
    $email->send();
}

/**
 * Handle payment_intent.payment_failed event
 */
function handlePaymentFailed(array $paymentIntent): void
{
    $amount = number_format(($paymentIntent['amount'] ?? 0) / 100, 2, ',', '.') . ' ' . strtoupper($paymentIntent['currency'] ?? 'eur');
    $errorMsg = $paymentIntent['last_payment_error']['message'] ?? 'Unknown error';

    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #ef4444;'>❌ Zahlung fehlgeschlagen</h2>
        <p><strong>Betrag:</strong> {$amount}</p>
        <p><strong>Payment Intent ID:</strong> {$paymentIntent['id']}</p>
        <p><strong>Fehler:</strong> " . htmlspecialchars($errorMsg) . "</p>
    </div>";

    $text = "Zahlung fehlgeschlagen\n\nBetrag: {$amount}\nPayment Intent ID: {$paymentIntent['id']}\nFehler: {$errorMsg}";

    $email = new ResendEmailService(EmailConfig::$defaultRecipient, '❌ Zahlung fehlgeschlagen - Stripe');
    $email->content($html, $text);
    $email->send();
}

/**
 * Handle invoice.paid event (for subscriptions)
 */
function handleInvoicePaid(array $invoice): void
{
    $amount = number_format(($invoice['amount_paid'] ?? 0) / 100, 2, ',', '.') . ' €';
    $customerEmail = $invoice['customer_email'] ?? '';

    EmailConfig::log("Invoice paid: {$amount} from {$customerEmail}", 'INFO');
}

// ============================================================
// EMAIL TEMPLATES
// ============================================================

function getOwnerEmailHtml(string $orderId, string $customerName, string $customerEmail, string $totalAmount, string $createdDate, string $itemsHtml, string $stripeUrl, string $sessionId): string
{
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Arial, sans-serif; background-color: #f5f5f5;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5;'>
        <tr>
            <td style='padding: 40px 20px;'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); padding: 40px 30px; text-align: center; color: #F0ECDA;'>
                            <h1 style='margin: 0; font-size: 28px;'>🎉 Neue Bestellung!</h1>
                            <p style='margin: 10px 0 0; font-size: 16px; opacity: 0.9;'>Eine neue Bestellung wurde erfolgreich bezahlt.</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style='padding: 30px;'>
                            <!-- Highlight Box -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #fff3cd; border-radius: 8px; margin-bottom: 20px;'>
                                <tr>
                                    <td style='padding: 15px 20px;'>
                                        <p style='margin: 0; color: #856404;'><strong>Bestellnummer:</strong> {$orderId}</p>
                                        <p style='margin: 5px 0 0; color: #856404;'><strong>Erstellt:</strong> {$createdDate}</p>
                                    </td>
                                </tr>
                            </table>
                            <!-- Customer Details -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='border-left: 4px solid #ea580c; background-color: #f9f9f9; border-radius: 8px; margin-bottom: 20px;'>
                                <tr>
                                    <td style='padding: 20px;'>
                                        <h3 style='margin: 0 0 15px; color: #232E3D; font-size: 18px;'>👤 Kunde</h3>
                                        <p style='margin: 5px 0;'><strong>Name:</strong> " . htmlspecialchars($customerName) . "</p>
                                        <p style='margin: 5px 0;'><strong>E-Mail:</strong> <a href='mailto:{$customerEmail}' style='color: #ea580c;'>{$customerEmail}</a></p>
                                        <h3 style='margin: 20px 0 15px; color: #232E3D; font-size: 18px;'>💰 Zahlung</h3>
                                        <p style='margin: 5px 0;'><strong>Gesamtbetrag:</strong> <span style='font-size: 20px; color: #ea580c; font-weight: bold;'>{$totalAmount}</span></p>
                                        <p style='margin: 5px 0;'><strong>Status:</strong> ✅ Bezahlt</p>
                                        <p style='margin: 5px 0;'><strong>Zahlungsart:</strong> Kartenzahlung (Stripe)</p>
                                        {$itemsHtml}
                                        <p style='margin: 15px 0 0; font-size: 12px; color: #666;'><strong>Stripe Session ID:</strong> {$sessionId}</p>
                                    </td>
                                </tr>
                            </table>
                            <!-- CTA Button -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td style='text-align: center; padding: 10px 0;'>
                                        <a href='{$stripeUrl}' style='display: inline-block; padding: 14px 28px; background-color: #ea580c; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;'>In Stripe ansehen</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #232E3D; padding: 20px; text-align: center;'>
                            <p style='margin: 0; color: rgba(255,255,255,0.7); font-size: 12px;'>Diese E-Mail wurde automatisch vom LayerStore Zahlungssystem generiert.</p>
                            <p style='margin: 5px 0 0; color: rgba(255,255,255,0.7); font-size: 12px;'>© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
}

function getOwnerEmailText(string $orderId, string $customerName, string $customerEmail, string $totalAmount, string $createdDate, array $items, string $sessionId): string
{
    $text = "NEUE BESTELLUNG bei LayerStore\n";
    $text .= "=" . str_repeat("=", 40) . "\n\n";
    $text .= "Bestellnummer: {$orderId}\n";
    $text .= "Erstellt: {$createdDate}\n\n";
    $text .= "KUNDE\n";
    $text .= "-" . str_repeat("-", 20) . "\n";
    $text .= "Name: {$customerName}\n";
    $text .= "E-Mail: {$customerEmail}\n\n";
    $text .= "ZAHLUNG\n";
    $text .= "-" . str_repeat("-", 20) . "\n";
    $text .= "Gesamtbetrag: {$totalAmount}\n";
    $text .= "Status: Bezahlt\n\n";

    if (!empty($items)) {
        $text .= "ARTIKEL\n";
        $text .= "-" . str_repeat("-", 20) . "\n";
        foreach ($items as $item) {
            $price = number_format(($item['price'] ?? 0) / 100, 2, ',', '.') . ' €';
            $text .= "- {$item['name']} - {$price} x " . ($item['quantity'] ?? 1) . "\n";
        }
        $text .= "\n";
    }

    $text .= "Stripe Session ID: {$sessionId}\n";
    $text .= "\n© " . date('Y') . " LayerStore\n";

    return $text;
}

function getCustomerEmailHtml(string $customerName, string $orderId, string $totalAmount): string
{
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Arial, sans-serif; background-color: #f5f5f5;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5;'>
        <tr>
            <td style='padding: 40px 20px;'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); padding: 40px 30px; text-align: center; color: #F0ECDA;'>
                            <h1 style='margin: 0; font-size: 28px;'>Vielen Dank! 🎉</h1>
                            <p style='margin: 10px 0 0; font-size: 16px; opacity: 0.9;'>Deine Bestellung ist erfolgreich bei uns eingegangen.</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style='padding: 30px;'>
                            <p style='margin: 0 0 20px; font-size: 16px; color: #333;'>Hallo " . htmlspecialchars($customerName) . ",</p>
                            <p style='margin: 0 0 20px; color: #333;'>vielen Dank für deine Bestellung bei LayerStore!</p>
                            <!-- Order Details -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color: #f9f9f9; border-radius: 8px; margin-bottom: 20px;'>
                                <tr>
                                    <td style='padding: 20px;'>
                                        <h3 style='margin: 0 0 15px; color: #232E3D; font-size: 18px;'>Bestellübersicht</h3>
                                        <p style='margin: 5px 0;'><strong>Bestellnummer:</strong> {$orderId}</p>
                                        <p style='margin: 5px 0;'><strong>Betrag:</strong> {$totalAmount}</p>
                                        <p style='margin: 5px 0;'><strong>Zahlungsstatus:</strong> ✅ Bezahlt</p>
                                    </td>
                                </tr>
                            </table>
                            <p style='margin: 0 0 10px; color: #333;'>Wir werden deine Bestellung schnellstmöglich bearbeiten und dich per E-Mail über den Versand informieren.</p>
                            <p style='margin: 0 0 20px; color: #333;'>Falls du Fragen hast, antworte einfach auf diese E-Mail.</p>
                            <p style='margin: 20px 0 0; color: #333;'>Mit freundlichen Grüßen,<br><strong>Das LayerStore Team</strong></p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #232E3D; padding: 20px; text-align: center;'>
                            <p style='margin: 0; color: rgba(255,255,255,0.7); font-size: 12px;'>LayerStore - Individuelle 3D-Druck-Kreationen</p>
                            <p style='margin: 5px 0 0; color: rgba(255,255,255,0.7); font-size: 12px;'>© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
}

function getCustomerEmailText(string $customerName, string $orderId, string $totalAmount): string
{
    return "LayerStore - Bestellbestätigung\n\n" .
           "Hallo {$customerName},\n\n" .
           "vielen Dank für deine Bestellung bei LayerStore!\n\n" .
           "Bestellübersicht\n" .
           "-" . str_repeat("-", 20) . "\n" .
           "Bestellnummer: {$orderId}\n" .
           "Betrag: {$totalAmount}\n" .
           "Zahlungsstatus: Bezahlt\n\n" .
           "Wir werden deine Bestellung schnellstmöglich bearbeiten und dich per E-Mail über den Versand informieren.\n\n" .
           "Falls du Fragen hast, antworte einfach auf diese E-Mail.\n\n" .
           "Mit freundlichen Grüßen,\n" .
           "Das LayerStore Team\n\n" .
           "© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.\n" .
           "https://layerstore.eu\n";
}
