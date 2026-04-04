<?php
/**
 * Order API for LayerStore
 * REST API for order management
 *
 * @package LayerStore\Orders
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/storage.php';

use LayerStore\Orders\OrderStorage;

// Simple authentication (in production, use proper JWT/session)
$authToken = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = $_ENV['ORDERS_API_KEY'] ?? 'change-me-in-production';

if ($authToken !== 'Bearer ' . $apiKey && $_ENV['APP_ENV'] ?? 'prod' !== 'dev') {
    // For development, allow without auth if APP_ENV=dev
    if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'dev') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

/**
 * Send JSON response
 */
function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get request method
 */
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Handle GET requests
 */
if ($method === 'GET') {
    $orderId = $_GET['id'] ?? null;

    // Get single order
    if ($orderId) {
        $order = OrderStorage::getByOrderId($orderId);

        if (!$order) {
            sendResponse(['error' => 'Order not found'], 404);
        }

        // Include status history
        $order['status_history'] = OrderStorage::getStatusHistory($order['id']);

        sendResponse($order);
    }

    // Get statistics
    if (isset($_GET['stats'])) {
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $stats = OrderStorage::getStatistics($dateFrom, $dateTo);
        sendResponse($stats);
    }

    // Get all orders with filters
    $filters = [
        'status' => $_GET['status'] ?? null,
        'customer_email' => $_GET['email'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'search' => $_GET['search'] ?? null,
        'limit' => $_GET['limit'] ?? null,
        'offset' => $_GET['offset'] ?? null
    ];

    $orders = OrderStorage::getAll($filters);

    // Remove null filters
    $filters = array_filter($filters, fn($v) => $v !== null);

    sendResponse([
        'orders' => $orders,
        'count' => count($orders),
        'filters' => $filters
    ]);
}

/**
 * Handle POST requests - Create new order
 */
if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        sendResponse(['error' => 'Invalid JSON'], 400);
    }

    // Validate required fields
    if (empty($data['customer_email'])) {
        sendResponse(['error' => 'customer_email is required'], 400);
    }

    if (!isset($data['total']) || $data['total'] < 0) {
        sendResponse(['error' => 'total is required and must be >= 0'], 400);
    }

    try {
        $orderId = OrderStorage::save($data);
        $order = OrderStorage::getByOrderId($orderId);

        sendResponse([
            'success' => true,
            'order' => $order
        ], 201);
    } catch (\Exception $e) {
        sendResponse([
            'error' => 'Failed to create order',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Handle PUT requests - Update order
 */
if ($method === 'PUT') {
    $orderId = $_GET['id'] ?? null;

    if (!$orderId) {
        sendResponse(['error' => 'Order ID is required'], 400);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        sendResponse(['error' => 'Invalid JSON'], 400);
    }

    try {
        // Update status
        if (isset($data['status'])) {
            if (!isset(OrderStorage::STATUSES[$data['status']])) {
                sendResponse(['error' => 'Invalid status'], 400);
            }

            $success = OrderStorage::updateStatus(
                $orderId,
                $data['status'],
                $data['changed_by'] ?? 'admin',
                $data['notes'] ?? null
            );

            if (!$success) {
                sendResponse(['error' => 'Order not found'], 404);
            }
        }

        // Update notes
        if (isset($data['notes'])) {
            OrderStorage::updateNotes($orderId, $data['notes']);
        }

        $order = OrderStorage::getByOrderId($orderId);
        $order['status_history'] = OrderStorage::getStatusHistory($order['id']);

        sendResponse([
            'success' => true,
            'order' => $order
        ]);
    } catch (\Exception $e) {
        sendResponse([
            'error' => 'Failed to update order',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Handle DELETE requests
 */
if ($method === 'DELETE') {
    $orderId = $_GET['id'] ?? null;

    if (!$orderId) {
        sendResponse(['error' => 'Order ID is required'], 400);
    }

    // Note: Soft delete would be better in production
    sendResponse(['error' => 'Delete not implemented - use status=cancelled instead'], 405);
}

/**
 * Export to CSV
 */
if ($method === 'GET' && isset($_GET['export'])) {
    $filters = [
        'status' => $_GET['status'] ?? null,
        'customer_email' => $_GET['email'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'search' => $_GET['search'] ?? null
    ];

    $csv = OrderStorage::exportToCsv($filters);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo $csv;
    exit;
}

// Method not allowed
sendResponse(['error' => 'Method not allowed'], 405);
