<?php
/**
 * Admin API for LayerStore
 * Handles authentication and promo code management
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration
define('ADMIN_USERNAME', 'amikoz');
define('ADMIN_PASSWORD', 'amikoz');
define('PROMO_FILE', __DIR__ . '/promo-codes.json');
define('TOKEN_SECRET', 'layerstore_secret_key_2024');

// Helper functions
function sendResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function getPromoCodes() {
    if (!file_exists(PROMO_FILE)) {
        // Default promo codes
        $default = [
            'OSTER2025' => [
                'discount' => 0.15,
                'description' => 'Oster-Sonderangebot'
            ],
            'FRÜHLING10' => [
                'discount' => 0.10,
                'description' => 'Frühlingsangebot'
            ]
        ];
        file_put_contents(PROMO_FILE, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    return json_decode(file_get_contents(PROMO_FILE), true) ?? [];
}

function savePromoCodes($promos) {
    file_put_contents(PROMO_FILE, json_encode($promos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 2) return false;

    $payload = base64_decode($parts[0]);
    $signature = $parts[1];

    $expectedSignature = hash_hmac('sha256', $payload, TOKEN_SECRET);

    return hash_equals($expectedSignature, $signature);
}

function generateToken($username) {
    $payload = base64_encode(json_encode([
        'username' => $username,
        'exp' => time() + (8 * 60 * 60) // 8 hours
    ]));
    $signature = hash_hmac('sha256', $payload, TOKEN_SECRET);
    return $payload . '.' . $signature;
}

// Get headers
$headers = getallheaders();
$authToken = $headers['X-Auth-Token'] ?? '';

$action = $_GET['action'] ?? '';

// Login
if ($action === 'login') {
    // Get from POST (FormData) or JSON body
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // If empty, try JSON
    if (empty($username)) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
    }

    // Debug logging
    $log = date('Y-m-d H:i:s') . " | Login | User: '$username' | Pass: '$password'\n";
    @file_put_contents(__DIR__ . '/debug.log', $log, FILE_APPEND);

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $token = generateToken($username);
        sendResponse(true, ['token' => $token], 'Login successful');
    } else {
        sendResponse(false, [], 'Invalid credentials');
    }
}

// All other actions require authentication
if (!verifyToken($authToken)) {
    sendResponse(false, [], 'Unauthorized');
}

// List promo codes
if ($action === 'listPromos') {
    sendResponse(true, ['promos' => getPromoCodes()]);
}

// Add promo code
if ($action === 'addPromo') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = strtoupper($input['code'] ?? '');
    $discount = floatval($input['discount'] ?? 0);
    $description = $input['description'] ?? '';

    if (empty($code)) {
        sendResponse(false, [], 'Code is required');
    }

    if ($discount <= 0 || $discount > 1) {
        sendResponse(false, [], 'Invalid discount');
    }

    $promos = getPromoCodes();
    $promos[$code] = [
        'discount' => $discount,
        'description' => $description
    ];
    savePromoCodes($promos);

    sendResponse(true, [], 'Promo code added');
}

// Delete promo code
if ($action === 'deletePromo') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';

    if (empty($code)) {
        sendResponse(false, [], 'Code is required');
    }

    $promos = getPromoCodes();
    unset($promos[$code]);
    savePromoCodes($promos);

    sendResponse(true, [], 'Promo code deleted');
}

sendResponse(false, [], 'Invalid action');
