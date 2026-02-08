<?php
/**
 * Order Form Handler for LayerStore
 * Processes order submissions and sends email notification
 */

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

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
IP Adresse: " . $_SERVER['REMOTE_ADDR'] . "
";

// Email headers
$headers = [
    'From' => 'LayerStore <noreply@layerstore.eu>',
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8'
];

// Convert headers array to string
$headersString = '';
foreach ($headers as $key => $value) {
    $headersString .= "$key: $value\r\n";
}

// Send email
try {
    $success = mail($recipientEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headersString);

    if ($success) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bestellung erfolgreich gesendet'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Senden der E-Mail'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
