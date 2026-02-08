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

logMessage("=== New order request ===");

// Configuration
$recipientEmail = 'info@layerstore.eu';
$subject = 'Neue Bestellung von LayerStore';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("ERROR: Wrong request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

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

logMessage("Email: $email");
logMessage("Name: " . ($name ?: 'not provided'));
logMessage("WhatsApp: " . ($whatsapp ?: 'not provided'));

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("ERROR: Invalid email: $email");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige E-Mail-Adresse']);
    exit;
}

// Build email message
$message = "
=== NEUE BESTELLUNG von LayerStore ===

Kundeninformationen:
-------------------
E-Mail: $email
" . ($name ? "Name: $name\n" : "") .
($whatsapp ? "WhatsApp: $whatsapp\n" : "") . "

$orderDetails

Gesendet am: " . date('d.m.Y') . " um " . date('H:i') . "
IP Adresse: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "
";

logMessage("Message prepared, length: " . strlen($message));

// Email headers - Ionos compatible format
$headersString = "From: LayerStore <noreply@layerstore.eu>\r\n";
$headersString .= "Reply-To: $email\r\n";
$headersString .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headersString .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headersString .= "MIME-Version: 1.0\r\n";

logMessage("Headers: " . $headersString);

// Send email
try {
    logMessage("Attempting to send email to $recipientEmail");

    $success = mail($recipientEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headersString);

    logMessage("Mail function returned: " . ($success ? 'TRUE' : 'FALSE'));

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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

logMessage("=== End of request ===\n");
?>
