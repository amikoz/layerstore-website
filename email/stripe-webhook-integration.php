<?php
/**
 * Stripe Webhook Handler - Updated with Email Template System
 *
 * This is the NEW version of cart/stripe-webhook.php that uses the
 * professional email template system.
 *
 * Replace the contents of cart/stripe-webhook.php with this file.
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

// Load email template renderer
require_once __DIR__ . '/../email/TemplateRenderer.php';

use LayerStore\Email\TemplateRenderer;

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
        // Optional: send additional notification for payment_intent events
        break;

    case 'payment_intent.payment_failed':
        $paymentIntent = $event['data']['object'];
        handlePaymentFailed($paymentIntent);
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
 * Handle checkout.session.completed event
 */
function handleCheckoutCompleted(array $session): void
{
    $renderer = new TemplateRenderer();

    // Extract customer information
    $customerEmail = $session['customer_details']['email'] ?? '';
    $customerName = $session['customer_details']['name'] ?? 'Kunde';

    // Generate order ID from session ID
    $orderId = 'LS-' . date('Y') . '-' . substr($session['id'], -8);

    // Format amounts
    $totalAmount = TemplateRenderer::formatCurrency($session['amount_total'] ?? 0);
    $createdDate = TemplateRenderer::formatDate($session['created'] ?? time());

    // Parse items from metadata
    $items = [];
    if (isset($session['metadata']['items'])) {
        $parsedItems = json_decode($session['metadata']['items'], true);
        if (is_array($parsedItems)) {
            $items = $parsedItems;
        }
    }

    // Prepare Stripe URL
    $stripeUrl = '';
    if (!empty($session['payment_intent'])) {
        $stripeUrl = 'https://dashboard.stripe.com/payments/' . $session['payment_intent'];
    }

    // Send order notification to store owner
    $ownerResult = $renderer->sendOrderNotification([
        'order_id' => $orderId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'total_amount' => $totalAmount,
        'created' => $createdDate,
        'items' => $items,
        'payment_intent' => $session['payment_intent'] ?? '',
        'stripe_url' => $stripeUrl
    ]);

    // Send confirmation to customer
    if ($customerEmail) {
        $customerResult = $renderer->sendCustomerConfirmation([
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'order_id' => $orderId,
            'total_amount' => $totalAmount
        ]);

        // Log customer email result
        $logMessage = date('Y-m-d H:i:s') . ' - Customer email ' . ($customerResult ? 'SENT' : 'FAILED') .
                     " to {$customerEmail} for order {$orderId}\n";
        file_put_contents(__DIR__ . '/webhook.log', $logMessage, FILE_APPEND);
    }

    // Log owner notification result
    $logMessage = date('Y-m-d H:i:s') . ' - Owner notification ' . ($ownerResult ? 'SENT' : 'FAILED') .
                 " for order {$orderId} (Amount: {$totalAmount})\n";
    file_put_contents(__DIR__ . '/webhook.log', $logMessage, FILE_APPEND);
}

/**
 * Handle payment_intent.payment_failed event
 */
function handlePaymentFailed(array $paymentIntent): void
{
    $renderer = new TemplateRenderer();

    $amount = TemplateRenderer::formatCurrency($paymentIntent['amount'] ?? 0);
    $errorMsg = $paymentIntent['last_payment_error']['message'] ?? 'Unknown error';
    $errorCode = $paymentIntent['last_payment_error']['code'] ?? '';

    $renderer->sendPaymentFailed([
        'amount' => $amount,
        'payment_intent_id' => $paymentIntent['id'] ?? '',
        'error_message' => $errorMsg,
        'error_code' => $errorCode,
        'customer_email' => $paymentIntent['metadata']['customer_email'] ?? null
    ]);
}
