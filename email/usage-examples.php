<?php
/**
 * Email Template Usage Examples
 *
 * This file demonstrates how to use the LayerStore Email Template Renderer
 * in various scenarios (Stripe webhooks, order confirmations, etc.).
 *
 * @author LayerStore
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/TemplateRenderer.php';

use LayerStore\Email\TemplateRenderer;

// ============================================
// EXAMPLE 1: Order Notification (Stripe Webhook)
// ============================================

function handleCheckoutCompleted(array $session): void
{
    $renderer = new TemplateRenderer();

    // Extract data from Stripe session
    $orderId = substr($session['id'], -8);
    $customerEmail = $session['customer_details']['email'] ?? '';
    $customerName = $session['customer_details']['name'] ?? 'Kunde';
    $totalAmount = TemplateRenderer::formatCurrency($session['amount_total'] ?? 0);
    $created = TemplateRenderer::formatDate($session['created'] ?? time());

    // Parse items from metadata
    $items = [];
    if (isset($session['metadata']['items'])) {
        $items = json_decode($session['metadata']['items'], true) ?: [];
    }

    // Send notification to store owner
    $renderer->sendOrderNotification([
        'order_id' => $orderId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'total_amount' => $totalAmount,
        'created' => $created,
        'items' => $items,
        'payment_intent' => $session['payment_intent'] ?? '',
        'stripe_url' => "https://dashboard.stripe.com/payments/{$session['payment_intent']}"
    ]);

    // Send confirmation to customer
    if ($customerEmail) {
        $renderer->sendCustomerConfirmation([
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'order_id' => $orderId,
            'total_amount' => $totalAmount
        ]);
    }
}

// ============================================
// EXAMPLE 2: Payment Failed Notification
// ============================================

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

// ============================================
// EXAMPLE 3: Direct Template Rendering
// ============================================

function renderCustomEmail(): array
{
    $renderer = new TemplateRenderer();

    // Set global variables for all templates
    $renderer->setGlobalVars([
        'store_name' => 'LayerStore',
        'store_url' => 'https://layerstore.eu'
    ]);

    // Render template with custom variables
    return $renderer->render('customer-confirmation', [
        'subject' => 'Deine Bestellung ist erfolgreich!',
        'customer_name' => 'Max Mustermann',
        'order_id' => 'LS-2024-001',
        'total_amount' => '49,90 €'
    ]);
}

// ============================================
// EXAMPLE 4: Sending Raw Email (Advanced)
// ============================================

function sendCustomEmail(string $to, string $template, array $data): bool
{
    $renderer = new TemplateRenderer();

    $data['subject'] = $data['subject'] ?? 'Notification from LayerStore';

    $options = [
        'priority' => $data['urgent'] ?? false,
        'reply_to' => $data['reply_to'] ?? null
    ];

    return $renderer->send($to, $template, $data, $options);
}

// ============================================
// EXAMPLE 5: Integration with Stripe Webhook Handler
// ============================================

/**
 * Process Stripe webhook events
 * Call this from your webhook endpoint
 */
function processStripeWebhook(array $event): void
{
    $renderer = new TemplateRenderer();

    switch ($event['type']) {
        case 'checkout.session.completed':
            $session = $event['data']['object'];
            if ($session['payment_status'] === 'paid') {
                handleCheckoutCompleted($session);
            }
            break;

        case 'payment_intent.succeeded':
            // Optional: Additional notification for payment_intent events
            $paymentIntent = $event['data']['object'];
            // Can add custom handling here
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event['data']['object'];
            handlePaymentFailed($paymentIntent);
            break;

        default:
            // Log unhandled events if needed
            break;
    }
}

// ============================================
// EXAMPLE 6: Testing Templates
// ============================================

/**
 * Test email templates (for development)
 */
function testTemplates(): void
{
    $renderer = new TemplateRenderer();

    // Test order notification
    $orderResult = $renderer->render('order-notification', [
        'subject' => 'Test: Neue Bestellung',
        'order_id' => 'TEST-001',
        'customer_name' => 'Test Kunde',
        'customer_email' => 'test@example.com',
        'total_amount' => '99,99 €',
        'created' => date('d.m.Y H:i'),
        'items' => [
            ['name' => 'Produkt A', 'price' => 4999, 'quantity' => 2],
            ['name' => 'Produkt B', 'price' => 1999, 'quantity' => 1]
        ],
        'payment_intent' => 'pi_test_123456',
        'stripe_url' => 'https://dashboard.stripe.com/test'
    ]);

    // Save test output
    file_put_contents(__DIR__ . '/test-order-notification.html', $orderResult['html']);
    file_put_contents(__DIR__ . '/test-order-notification.txt', $orderResult['text']);

    // Test customer confirmation
    $customerResult = $renderer->render('customer-confirmation', [
        'subject' => 'Test: Bestellbestätigung',
        'customer_name' => 'Max Mustermann',
        'order_id' => 'TEST-001',
        'total_amount' => '99,99 €'
    ]);

    file_put_contents(__DIR__ . '/test-customer-confirmation.html', $customerResult['html']);
    file_put_contents(__DIR__ . '/test-customer-confirmation.txt', $customerResult['text']);

    // Test payment failed
    $failedResult = $renderer->render('payment-failed', [
        'subject' => 'Test: Zahlung fehlgeschlagen',
        'amount' => '149,99 €',
        'payment_intent_id' => 'pi_failed_123456',
        'error_message' => 'Your card was declined.',
        'error_code' => 'card_declined',
        'customer_email' => 'customer@example.com'
    ]);

    file_put_contents(__DIR__ . '/test-payment-failed.html', $failedResult['html']);
    file_put_contents(__DIR__ . '/test-payment-failed.txt', $failedResult['text']);

    echo "Templates tested! Output saved to test files.\n";
}

// ============================================
// EXAMPLE 7: Cloudflare Worker Compatibility
// ============================================

/**
 * Generate email HTML for Cloudflare Worker
 * Call this from your worker's email sending logic
 */
function generateOrderEmailHtml(array $data): string
{
    $renderer = new TemplateRenderer();

    $result = $renderer->render('order-notification', [
        'order_id' => $data['orderId'] ?? '',
        'customer_name' => $data['customerName'] ?? '',
        'customer_email' => $data['customerEmail'] ?? '',
        'total_amount' => $data['totalAmount'] ?? '',
        'created' => $data['created'] ?? '',
        'items' => $data['items'] ?? [],
        'payment_intent' => $data['sessionId'] ?? '',
        'stripe_url' => $data['stripeUrl'] ?? ''
    ]);

    return $result['html'];
}

function generateCustomerEmailHtml(array $data): string
{
    $renderer = new TemplateRenderer();

    $result = $renderer->render('customer-confirmation', [
        'customer_name' => $data['customerName'] ?? '',
        'order_id' => $data['orderId'] ?? '',
        'total_amount' => $data['totalAmount'] ?? ''
    ]);

    return $result['html'];
}

// ============================================
// CLI Usage
// ============================================

if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $command = $argv[1];

    switch ($command) {
        case 'test':
            testTemplates();
            break;

        case 'preview':
            $template = $argv[2] ?? 'customer-confirmation';
            $renderer = new TemplateRenderer();
            $result = $renderer->render($template, [
                'customer_name' => 'Max Mustermann',
                'order_id' => 'DEMO-001',
                'total_amount' => '49,90 €',
                'created' => date('d.m.Y H:i'),
                'customer_email' => 'demo@example.com',
                'items' => [
                    ['name' => 'Demo Produkt', 'price' => 4990, 'quantity' => 1]
                ]
            ]);
            echo $result['html'];
            break;

        default:
            echo "Usage:\n";
            echo "  php usage-examples.php test          - Test all templates\n";
            echo "  php usage-examples.php preview <tpl> - Preview template\n";
            echo "\nAvailable templates:\n";
            echo "  - order-notification\n";
            echo "  - customer-confirmation\n";
            echo "  - payment-failed\n";
    }
}
