<?php
/**
 * Order Form Handler for LayerStore
 * Processes order submissions and sends email notification
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log file
$logFile = __DIR__ . '/order_log.txt';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== NEW ORDER REQUEST START ===");
logMessage("Request method: " . $_SERVER['REQUEST_METHOD']);
logMessage("Request URI: " . $_SERVER['REQUEST_URI']);
logMessage("HTTP_HOST: " . $_SERVER['HTTP_HOST']);

// Configuration
$recipientEmail = 'info@layerstore.eu';
$subject = 'Neue Bestellung von LayerStore';

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

// Log raw POST data
logMessage("RAW POST DATA: " . file_get_contents('php://input'));
logMessage("POST array: " . print_r($_POST, true));

// Sanitize and validate input data
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
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
logMessage("Order details length: " . strlen($orderDetails) . " chars");

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("ERROR: Invalid email address");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige E-Mail-Adresse']);
    exit;
}

logMessage("Email validation passed");

// Build email message
$message = "
=== NEUE BESTELLUNG von LayerStore ===

Kundeninformationen:
-------------------
E-Mail: $email
" . ($name ? "Name: $name\n" : "") .
($whatsapp ? "WhatsApp: $whatsapp\n" : "") . "

$orderDetails
" . ($promoCode ? "\n=== VERWENDETER PROMO-CODE ===\nPromo-Code: $promoCode\n" : "") . "

Gesendet am: " . date('d.m.Y') . " um " . date('H:i') . "
IP Adresse: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "
User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "
";

logMessage("Message prepared, length: " . strlen($message) . " bytes");

// Email headers
$headersString = "From: LayerStore <noreply@layerstore.eu>\r\n";
$headersString .= "Reply-To: $email\r\n";
$headersString .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headersString .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headersString .= "MIME-Version: 1.0\r\n";

logMessage("Headers prepared");

// Send email
try {
    logMessage("Attempting to send email to: $recipientEmail");

    // Additional mail params for some hosting providers
    $params = '-f ' . $recipientEmail;

    $success = mail($recipientEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headersString, $params);

    logMessage("Mail function returned: " . ($success ? 'TRUE (success)' : 'FALSE (failed)'));

    if ($success) {
        logMessage("SUCCESS: Email sent successfully");
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bestellung erfolgreich gesendet'
        ]);
    } else {
        logMessage("ERROR: Mail function returned false");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Senden der E-Mail (mail function failed)'
        ]);
    }
} catch (Exception $e) {
    logMessage("EXCEPTION: " . $e->getMessage());
    logMessage("Exception trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

logMessage("=== REQUEST END ===\n");
?>
