<?php
/**
 * Order Form Handler for LayerStore
 * Processes order submissions with email notification support
 *
 * Supports two email methods:
 * 1. Resend.com API (modern, recommended) - set RESEND_API_KEY in .env
 * 2. PHP mail() fallback (legacy, less reliable)
 */

declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Autoload email service
require_once __DIR__ . '/email/config.php';
require_once __DIR__ . '/email/ResendEmailService.php';

use LayerStore\Email\ResendEmailService;
use LayerStore\Email\EmailConfig;

// Log file
$logFile = __DIR__ . '/order_log.txt';

/**
 * Log message to file
 */
function logMessage(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== NEW ORDER REQUEST START ===");
logMessage("Request method: " . $_SERVER['REQUEST_METHOD']);
logMessage("Request URI: " . $_SERVER['REQUEST_URI']);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OPTIONS OK']);
    logMessage("OPTIONS request received");
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logMessage("ERROR: Wrong request method");
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

logMessage("POST request received");
logMessage("RAW POST DATA: " . file_get_contents('php://input'));

/**
 * Sanitize and validate input data
 */
function cleanInput(string $data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

$email = cleanInput($_POST['email'] ?? '');
$name = cleanInput($_POST['name'] ?? '');
$whatsapp = cleanInput($_POST['whatsapp'] ?? '');
$orderDetails = cleanInput($_POST['order_details'] ?? '');
$promoCode = cleanInput($_POST['promo_code'] ?? '');

logMessage("Email: '$email'");
logMessage("Name: '$name'");
logMessage("WhatsApp: '$whatsapp'");
logMessage("Promo Code: '$promoCode'");

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("ERROR: Invalid email address");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige E-Mail-Adresse']);
    exit;
}

logMessage("Email validation passed");

// Prepare order data
$orderData = [
    'email' => $email,
    'name' => $name,
    'whatsapp' => $whatsapp,
    'order_details' => $orderDetails,
    'promo_code' => $promoCode,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
];

// Try Resend API first
$emailResult = sendOrderEmailViaResend($orderData);

if ($emailResult['success']) {
    logMessage("SUCCESS: Email sent via Resend");
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Bestellung erfolgreich gesendet',
        'method' => 'resend'
    ]);
} else {
    // Fallback to PHP mail() if Resend fails
    logMessage("Resend failed: " . $emailResult['message'] . ", trying PHP mail() fallback");
    $fallbackResult = sendOrderEmailViaPHP($orderData);

    if ($fallbackResult['success']) {
        logMessage("SUCCESS: Email sent via PHP mail() fallback");
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bestellung erfolgreich gesendet',
            'method' => 'php_mail'
        ]);
    } else {
        logMessage("ERROR: Both email methods failed");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Senden der E-Mail. Bitte versuchen Sie es später erneut.',
            'error' => $emailResult['message']
        ]);
    }
}

logMessage("=== REQUEST END ===\n");

/**
 * Send order email via Resend API
 */
function sendOrderEmailViaResend(array $orderData): array
{
    // Check if Resend is configured
    if (empty(EmailConfig::$resendApiKey) && !EmailConfig::$sandbox) {
        logMessage("Resend not configured, skipping");
        return ['success' => false, 'message' => 'Resend not configured'];
    }

    try {
        $email = new ResendEmailService(
            EmailConfig::$defaultRecipient,
            'Neue Bestellung von LayerStore'
        );

        // Generate HTML content
        $htmlContent = generateOrderEmailHTML($orderData);

        // Generate plain text content
        $textContent = generateOrderEmailText($orderData);

        $email->content($htmlContent, $textContent);
        $email->replyTo($orderData['email']);
        $email->tag('order', 'new_order');
        $email->tag('source', 'website');

        return $email->send();

    } catch (\Throwable $e) {
        logMessage("Resend exception: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Send order email via PHP mail() fallback
 */
function sendOrderEmailViaPHP(array $orderData): array
{
    $recipientEmail = EmailConfig::$defaultRecipient;
    $subject = 'Neue Bestellung von LayerStore';

    // Build plain text message
    $message = generateOrderEmailText($orderData);

    // Email headers
    $headersString = "From: LayerStore <noreply@layerstore.eu>\r\n";
    $headersString .= "Reply-To: {$orderData['email']}\r\n";
    $headersString .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headersString .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headersString .= "MIME-Version: 1.0\r\n";

    try {
        $params = '-f ' . $recipientEmail;
        $success = mail(
            $recipientEmail,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $message,
            $headersString,
            $params
        );

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'PHP mail() returned false'];
        }
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate HTML email content for order notification
 */
function generateOrderEmailHTML(array $orderData): string
{
    $storeUrl = $_ENV['SITE_URL'] ?? 'https://layerstore.eu';
    $primaryColor = '#232E3D';
    $accentColor = '#ea580c';

    $customerInfo = '';
    if (!empty($orderData['name'])) {
        $customerInfo .= '<tr><td style="padding: 8px 0;"><strong>Name:</strong></td><td style="padding: 8px 0;">' . htmlspecialchars($orderData['name']) . '</td></tr>';
    }
    if (!empty($orderData['whatsapp'])) {
        $customerInfo .= '<tr><td style="padding: 8px 0;"><strong>WhatsApp:</strong></td><td style="padding: 8px 0;">' . htmlspecialchars($orderData['whatsapp']) . '</td></tr>';
    }

    $promoSection = '';
    if (!empty($orderData['promo_code'])) {
        $promoSection = '
            <tr>
                <td style="padding: 20px 0;">
                    <div style="background: #fff9e6; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px;">
                        <div style="font-weight: 600; color: #856404;">Verwendeter Promo-Code:</div>
                        <div>' . htmlspecialchars($orderData['promo_code']) . '</div>
                    </div>
                </td>
            </tr>';
    }

    // Format order details as HTML
    $orderDetailsHTML = nl2br(htmlspecialchars($orderData['order_details']));

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Bestellung - LayerStore</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, {$primaryColor} 0%, #3a4a5c 100%); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">Neue Bestellung eingegangen</h1>
        </div>

        <div style="padding: 30px;">
            <div style="margin-bottom: 25px;">
                <div style="font-size: 16px; font-weight: 600; color: {$accentColor}; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #f0f0f0;">
                    Kundeninformationen
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px 0;"><strong>E-Mail:</strong></td><td style="padding: 8px 0;">' . htmlspecialchars($orderData['email']) . '</td></tr>
                    {$customerInfo}
                </table>
            </div>

            <div style="margin-bottom: 25px;">
                <div style="font-size: 16px; font-weight: 600; color: {$accentColor}; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #f0f0f0;">
                    Bestelldetails
                </div>
                <div style="background: #f9f9f9; border-radius: 6px; padding: 15px;">
                    {$orderDetailsHTML}
                </div>
            </div>

            {$promoSection}

            <div style="background: #f0f0f0; padding: 15px; font-size: 12px; color: #666; border-radius: 6px;">
                <strong>Bestellung eingegangen:</strong> ' . date('d.m.Y \u\m H:i') . '<br>
                <strong>IP-Adresse:</strong> ' . htmlspecialchars($orderData['ip']) . '<br>
                <strong>User-Agent:</strong> ' . htmlspecialchars(substr($orderData['user_agent'], 0, 100)) . '
            </div>
        </div>

        <div style="text-align: center; padding: 20px; color: #666; font-size: 14px; border-top: 1px solid #eee;">
            <p style="margin: 0;">Diese E-Mail wurde automatisch vom LayerStore System generiert.</p>
            <p style="margin: 5px 0 0 0;"><a href="{$storeUrl}" style="color: {$accentColor};">layerstore.eu</a></p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Generate plain text email content for order notification
 */
function generateOrderEmailText(array $orderData): string
{
    $line = str_repeat('-', 50);
    $text = "NEUE BESTELLUNG von LayerStore\n{$line}\n\n";
    $text .= "KUNDENINFORMATIONEN\n====================\n";
    $text .= "E-Mail: {$orderData['email']}\n";

    if (!empty($orderData['name'])) {
        $text .= "Name: {$orderData['name']}\n";
    }
    if (!empty($orderData['whatsapp'])) {
        $text .= "WhatsApp: {$orderData['whatsapp']}\n";
    }

    $text .= "\nBESTELLDETAILS\n==============\n\n";
    $text .= $orderData['order_details'];

    if (!empty($orderData['promo_code'])) {
        $text .= "\n\n{$line}\n\nVERWENDETER PROMO-CODE\n====================\n";
        $text .= "Promo-Code: {$orderData['promo_code']}\n";
    }

    $text .= "\n\nMETADATEN\n=========\n";
    $text .= "Zeit: " . date('d.m.Y \u\m H:i') . "\n";
    $text .= "IP: {$orderData['ip']}\n";
    $text .= "User-Agent: " . substr($orderData['user_agent'], 0, 100) . "\n";

    $text .= "\n{$line}\n";
    $text .= "Diese E-Mail wurde automatisch vom LayerStore System generiert.\n";
    $text .= "https://layerstore.eu\n";

    return $text;
}
