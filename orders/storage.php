<?php
/**
 * Order Storage System for LayerStore
 * SQLite-based order storage with full CRUD operations
 *
 * @package LayerStore\Orders
 */

declare(strict_types=1);

namespace LayerStore\Orders;

/**
 * Order Storage Class
 *
 * Handles all database operations for orders using SQLite
 */
class OrderStorage
{
    private static ?\SQLite3 $db = null;
    private static string $dbPath;
    private static string $dataDir;

    // Valid order statuses
    public const STATUSES = [
        'pending' => 'Ausstehend',
        'processing' => 'In Bearbeitung',
        'shipped' => 'Versendet',
        'delivered' => 'Geliefert',
        'cancelled' => 'Storniert'
    ];

    // Status colors for UI
    public const STATUS_COLORS = [
        'pending' => '#f59e0b',
        'processing' => '#3b82f6',
        'shipped' => '#8b5cf6',
        'delivered' => '#10b981',
        'cancelled' => '#ef4444'
    ];

    /**
     * Initialize the storage system
     */
    public static function init(): void
    {
        self::$dataDir = dirname(__DIR__) . '/data';
        self::$dbPath = self::$dataDir . '/orders.db';

        // Create data directory if it doesn't exist
        if (!is_dir(self::$dataDir)) {
            mkdir(self::$dataDir, 0755, true);
        }

        // Initialize database connection
        self::connect();
        self::createTables();
    }

    /**
     * Get database connection
     */
    private static function connect(): void
    {
        self::$db = new \SQLite3(self::$dbPath);
        self::$db->enableExceptions(true);

        // Set SQLite options for better performance
        self::$db->exec('PRAGMA journal_mode = WAL');
        self::$db->exec('PRAGMA synchronous = NORMAL');
        self::$db->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * Create database tables
     */
    private static function createTables(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id TEXT UNIQUE NOT NULL,
            stripe_session_id TEXT,
            stripe_payment_intent_id TEXT,
            customer_name TEXT,
            customer_email TEXT NOT NULL,
            customer_phone TEXT,
            items TEXT NOT NULL,
            subtotal INTEGER DEFAULT 0,
            tax INTEGER DEFAULT 0,
            shipping INTEGER DEFAULT 0,
            total INTEGER NOT NULL,
            currency TEXT DEFAULT 'EUR',
            status TEXT DEFAULT 'pending',
            shipping_address TEXT,
            billing_address TEXT,
            notes TEXT,
            metadata TEXT,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_orders_order_id ON orders(order_id);
        CREATE INDEX IF NOT EXISTS idx_orders_customer_email ON orders(customer_email);
        CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
        CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);

        CREATE TABLE IF NOT EXISTS order_status_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            old_status TEXT,
            new_status TEXT NOT NULL,
            changed_by TEXT,
            changed_at INTEGER NOT NULL,
            notes TEXT,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_status_history_order_id ON order_status_history(order_id);
        ";

        self::$db->exec($sql);
    }

    /**
     * Get database connection (for external use)
     */
    public static function getDb(): \SQLite3
    {
        if (self::$db === null) {
            self::init();
        }
        return self::$db;
    }

    /**
     * Save a new order
     */
    public static function save(array $orderData): string
    {
        self::init();

        // Generate order ID if not provided
        $orderId = $orderData['order_id'] ?? self::generateOrderId();

        $now = time();

        $stmt = self::$db->prepare("
            INSERT INTO orders (
                order_id, stripe_session_id, stripe_payment_intent_id,
                customer_name, customer_email, customer_phone,
                items, subtotal, tax, shipping, total, currency,
                status, shipping_address, billing_address, notes, metadata,
                created_at, updated_at
            ) VALUES (
                :order_id, :stripe_session_id, :stripe_payment_intent_id,
                :customer_name, :customer_email, :customer_phone,
                :items, :subtotal, :tax, :shipping, :total, :currency,
                :status, :shipping_address, :billing_address, :notes, :metadata,
                :created_at, :updated_at
            )
        ");

        $stmt->bindValue(':order_id', $orderId, SQLITE3_TEXT);
        $stmt->bindValue(':stripe_session_id', $orderData['stripe_session_id'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':stripe_payment_intent_id', $orderData['stripe_payment_intent_id'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':customer_name', $orderData['customer_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':customer_email', $orderData['customer_email'], SQLITE3_TEXT);
        $stmt->bindValue(':customer_phone', $orderData['customer_phone'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':items', json_encode($orderData['items'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(':subtotal', (int)($orderData['subtotal'] ?? 0), SQLITE3_INTEGER);
        $stmt->bindValue(':tax', (int)($orderData['tax'] ?? 0), SQLITE3_INTEGER);
        $stmt->bindValue(':shipping', (int)($orderData['shipping'] ?? 0), SQLITE3_INTEGER);
        $stmt->bindValue(':total', (int)($orderData['total']), SQLITE3_INTEGER);
        $stmt->bindValue(':currency', $orderData['currency'] ?? 'EUR', SQLITE3_TEXT);
        $stmt->bindValue(':status', $orderData['status'] ?? 'pending', SQLITE3_TEXT);
        $stmt->bindValue(':shipping_address', $orderData['shipping_address'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':billing_address', $orderData['billing_address'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $orderData['notes'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($orderData['metadata'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(':created_at', $now, SQLITE3_INTEGER);
        $stmt->bindValue(':updated_at', $now, SQLITE3_INTEGER);

        $result = $stmt->execute();

        // Log initial status
        $internalId = self::$db->lastInsertRowID();
        self::addStatusHistory((int)$internalId, null, $orderData['status'] ?? 'pending');

        return $orderId;
    }

    /**
     * Get order by order ID
     */
    public static function getByOrderId(string $orderId): ?array
    {
        self::init();

        $stmt = self::$db->prepare("SELECT * FROM orders WHERE order_id = :order_id");
        $stmt->bindValue(':order_id', $orderId, SQLITE3_TEXT);
        $result = $stmt->execute();

        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row === false) {
            return null;
        }

        return self::formatOrder($row);
    }

    /**
     * Get order by internal ID
     */
    public static function getById(int $id): ?array
    {
        self::init();

        $stmt = self::$db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row === false) {
            return null;
        }

        return self::formatOrder($row);
    }

    /**
     * Get all orders with optional filtering
     */
    public static function getAll(array $filters = []): array
    {
        self::init();

        $sql = "SELECT * FROM orders WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['customer_email'])) {
            $sql .= " AND customer_email LIKE :email";
            $params[':email'] = "%{$filters['customer_email']}%";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params[':date_from'] = strtotime($filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params[':date_to'] = strtotime($filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $sql .= " AND (order_id LIKE :search OR customer_name LIKE :search OR customer_email LIKE :search)";
            $params[':search'] = $search;
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $offset = (int)($filters['offset'] ?? 0);
            if ($offset > 0) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = self::$db->prepare($sql);

        foreach ($params as $key => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
            $stmt->bindValue($key, $value, $type);
        }

        if (!empty($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], SQLITE3_INTEGER);
            if (!empty($filters['offset'])) {
                $stmt->bindValue(':offset', (int)$filters['offset'], SQLITE3_INTEGER);
            }
        }

        $result = $stmt->execute();

        $orders = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $orders[] = self::formatOrder($row);
        }

        return $orders;
    }

    /**
     * Update order status
     */
    public static function updateStatus(string $orderId, string $newStatus, string $changedBy = 'admin', ?string $notes = null): bool
    {
        self::init();

        $order = self::getByOrderId($orderId);
        if (!$order) {
            return false;
        }

        $oldStatus = $order['status'];

        $stmt = self::$db->prepare("
            UPDATE orders
            SET status = :new_status, updated_at = :updated_at
            WHERE order_id = :order_id
        ");

        $stmt->bindValue(':new_status', $newStatus, SQLITE3_TEXT);
        $stmt->bindValue(':updated_at', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':order_id', $orderId, SQLITE3_TEXT);

        $result = $stmt->execute();

        // Add to status history
        self::addStatusHistory($order['id'], $oldStatus, $newStatus, $changedBy, $notes);

        return $result !== false;
    }

    /**
     * Update order notes
     */
    public static function updateNotes(string $orderId, string $notes): bool
    {
        self::init();

        $stmt = self::$db->prepare("
            UPDATE orders
            SET notes = :notes, updated_at = :updated_at
            WHERE order_id = :order_id
        ");

        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':updated_at', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':order_id', $orderId, SQLITE3_TEXT);

        return $stmt->execute() !== false;
    }

    /**
     * Get order status history
     */
    public static function getStatusHistory(int $orderId): array
    {
        self::init();

        $stmt = self::$db->prepare("
            SELECT * FROM order_status_history
            WHERE order_id = :order_id
            ORDER BY changed_at DESC
        ");

        $stmt->bindValue(':order_id', $orderId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $history = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $history[] = [
                'old_status' => $row['old_status'],
                'new_status' => $row['new_status'],
                'changed_by' => $row['changed_by'],
                'changed_at' => (int)$row['changed_at'],
                'changed_at_formatted' => date('d.m.Y H:i', (int)$row['changed_at']),
                'notes' => $row['notes']
            ];
        }

        return $history;
    }

    /**
     * Get statistics
     */
    public static function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        self::init();

        $dateClause = '';
        $params = [];

        if ($dateFrom) {
            $dateClause .= ' AND created_at >= :date_from';
            $params[':date_from'] = strtotime($dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $dateClause .= ' AND created_at <= :date_to';
            $params[':date_to'] = strtotime($dateTo . ' 23:59:59');
        }

        // Total orders
        $sql = "SELECT COUNT(*) as total FROM orders WHERE 1=1{$dateClause}";
        $stmt = self::$db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_INTEGER);
        }
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $totalOrders = (int)$row['total'];

        // Total revenue
        $sql = "SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'{$dateClause}";
        $stmt = self::$db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_INTEGER);
        }
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $totalRevenue = (int)($row['revenue'] ?? 0);

        // Orders by status
        $sql = "SELECT status, COUNT(*) as count FROM orders WHERE 1=1{$dateClause} GROUP BY status";
        $stmt = self::$db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_INTEGER);
        }
        $result = $stmt->execute();

        $byStatus = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $byStatus[$row['status']] = (int)$row['count'];
        }

        // Fill missing statuses
        foreach (self::STATUSES as $key => $label) {
            if (!isset($byStatus[$key])) {
                $byStatus[$key] = 0;
            }
        }

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => self::formatPrice($totalRevenue),
            'by_status' => $byStatus
        ];
    }

    /**
     * Add status history entry
     */
    private static function addStatusHistory(int $orderId, ?string $oldStatus, string $newStatus, string $changedBy = 'system', ?string $notes = null): void
    {
        $stmt = self::$db->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, changed_at, notes)
            VALUES (:order_id, :old_status, :new_status, :changed_by, :changed_at, :notes)
        ");

        $stmt->bindValue(':order_id', $orderId, SQLITE3_INTEGER);
        $stmt->bindValue(':old_status', $oldStatus, SQLITE3_TEXT);
        $stmt->bindValue(':new_status', $newStatus, SQLITE3_TEXT);
        $stmt->bindValue(':changed_by', $changedBy, SQLITE3_TEXT);
        $stmt->bindValue(':changed_at', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);

        $stmt->execute();
    }

    /**
     * Format order data for API output
     */
    private static function formatOrder(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'order_id' => $row['order_id'],
            'stripe_session_id' => $row['stripe_session_id'],
            'stripe_payment_intent_id' => $row['stripe_payment_intent_id'],
            'customer' => [
                'name' => $row['customer_name'],
                'email' => $row['customer_email'],
                'phone' => $row['customer_phone']
            ],
            'items' => json_decode($row['items'], true) ?? [],
            'amounts' => [
                'subtotal' => (int)$row['subtotal'],
                'tax' => (int)$row['tax'],
                'shipping' => (int)$row['shipping'],
                'total' => (int)$row['total'],
                'currency' => $row['currency']
            ],
            'status' => $row['status'],
            'status_label' => self::STATUSES[$row['status']] ?? $row['status'],
            'status_color' => self::STATUS_COLORS[$row['status']] ?? '#6b7280',
            'addresses' => [
                'shipping' => $row['shipping_address'] ? json_decode($row['shipping_address'], true) : null,
                'billing' => $row['billing_address'] ? json_decode($row['billing_address'], true) : null
            ],
            'notes' => $row['notes'],
            'metadata' => json_decode($row['metadata'], true) ?? [],
            'created_at' => (int)$row['created_at'],
            'created_at_formatted' => date('d.m.Y H:i', (int)$row['created_at']),
            'updated_at' => (int)$row['updated_at'],
            'updated_at_formatted' => date('d.m.Y H:i', (int)$row['updated_at'])
        ];
    }

    /**
     * Format price
     */
    public static function formatPrice(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2, ',', '.') . ' €';
    }

    /**
     * Generate unique order ID
     */
    private static function generateOrderId(): string
    {
        return 'LS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Export orders to CSV
     */
    public static function exportToCsv(array $filters = []): string
    {
        $orders = self::getAll($filters);

        $csv = fopen('php://temp', 'r+');

        // Header
        fputcsv($csv, [
            'Order ID',
            'Datum',
            'Kunde',
            'E-Mail',
            'Artikel',
            'Gesamtbetrag',
            'Status'
        ], ';');

        // Rows
        foreach ($orders as $order) {
            $items = array_map(fn($item) => $item['name'], $order['items']);
            fputcsv($csv, [
                $order['order_id'],
                $order['created_at_formatted'],
                $order['customer']['name'],
                $order['customer']['email'],
                implode(', ', $items),
                self::formatPrice($order['amounts']['total']),
                $order['status_label']
            ], ';');
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }
}
