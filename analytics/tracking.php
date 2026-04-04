<?php
/**
 * LayerStore Server-Side Analytics Tracking
 * Speichert Analytics-Events serverseitig für zusätzliche Kontrolle
 *
 * @author LayerStore
 * @version 1.0.0
 */

// Konfiguration
define('ANALYTICS_DIR', __DIR__);
define('EVENTS_FILE', ANALYTICS_DIR . '/events.json');
define('AGGREGATED_FILE', ANALYTICS_DIR . '/aggregated.json');
define('MAX_EVENTS', 10000); // Maximale Anzahl Events vor Rotation
define('RETENTION_DAYS', 90); // Aufbewahrungsdauer in Tagen

/**
 * Event speichern
 */
function trackEvent($eventName, $data = []) {
    $event = [
        'id' => generateEventId(),
        'name' => $eventName,
        'data' => $data,
        'timestamp' => time(),
        'datetime' => date('c'),
        'ip_hash' => hashIp(getClientIp()),
        'session_id' => getSessionId(),
        'user_agent' => getUserAgent(),
        'referrer' => getReferrer(),
        'page_url' => getCurrentUrl()
    ];

    // Events laden
    $events = loadEvents();

    // Event hinzufügen
    $events[] = $event;

    // Events speichern (mit Rotation)
    saveEvents($events);

    // Aggregierte Daten aktualisieren
    updateAggregatedData($event);

    return $event['id'];
}

/**
 * E-Commerce Event tracken
 */
function trackEcommerceEvent($eventType, $data) {
    // Standard-Tracking
    trackEvent('ecommerce_' . $eventType, $data);

    // E-Commerce spezifische Logik
    switch ($eventType) {
        case 'product_view':
            updateProductStats($data['product_id'], 'views');
            break;

        case 'add_to_cart':
            updateProductStats($data['product_id'], 'add_to_carts');
            updateFunnel('view_to_cart', $data);
            break;

        case 'begin_checkout':
            updateFunnel('cart_to_checkout', $data);
            break;

        case 'purchase':
            updateProductStats($data['product_id'], 'purchases');
            updateFunnel('checkout_to_purchase', $data);
            updateRevenue($data);
            break;
    }
}

/**
 * Conversion Funnel tracken
 */
function updateFunnel($step, $data) {
    $funnelFile = ANALYTICS_DIR . '/funnel.json';
    $funnel = file_exists($funnelFile) ? json_decode(file_get_contents($funnelFile), true) : [];

    $date = date('Y-m-d');
    $sessionId = getSessionId();

    if (!isset($funnel[$date])) {
        $funnel[$date] = [
            'views' => 0,
            'add_to_carts' => 0,
            'checkouts' => 0,
            'purchases' => 0,
            'sessions' => []
        ];
    }

    switch ($step) {
        case 'view_to_cart':
            $funnel[$date]['add_to_carts']++;
            if (!in_array($sessionId, $funnel[$date]['sessions'])) {
                $funnel[$date]['sessions'][] = $sessionId;
            }
            break;

        case 'cart_to_checkout':
            $funnel[$date]['checkouts']++;
            break;

        case 'checkout_to_purchase':
            $funnel[$date]['purchases']++;
            break;
    }

    file_put_contents($funnelFile, json_encode($funnel, JSON_PRETTY_PRINT));
}

/**
 * Produkt-Statistiken aktualisieren
 */
function updateProductStats($productId, $metric) {
    $productsFile = ANALYTICS_DIR . '/products.json';
    $products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) : [];

    if (!isset($products[$productId])) {
        $products[$productId] = [
            'id' => $productId,
            'views' => 0,
            'add_to_carts' => 0,
            'purchases' => 0,
            'revenue' => 0
        ];
    }

    $products[$productId][$metric]++;

    file_put_contents($productsFile, json_encode($products, JSON_PRETTY_PRINT));
}

/**
 * Umsatz aktualisieren
 */
function updateRevenue($data) {
    $revenueFile = ANALYTICS_DIR . '/revenue.json';
    $revenueData = file_exists($revenueFile) ? json_decode(file_get_contents($revenueFile), true) : [];

    $date = date('Y-m-d');
    $amount = floatval($data['amount'] ?? 0);

    if (!isset($revenueData[$date])) {
        $revenueData[$date] = [
            'total' => 0,
            'orders' => 0,
            'avg_order_value' => 0
        ];
    }

    $revenueData[$date]['total'] += $amount;
    $revenueData[$date]['orders']++;
    $revenueData[$date]['avg_order_value'] = $revenueData[$date]['total'] / $revenueData[$date]['orders'];

    file_put_contents($revenueFile, json_encode($revenueData, JSON_PRETTY_PRINT));
}

/**
 * Aggregierte Daten aktualisieren
 */
function updateAggregatedData($event) {
    $aggregated = file_exists(AGGREGATED_FILE) ? json_decode(file_get_contents(AGGREGATED_FILE), true) : [];

    $date = date('Y-m-d');
    $hour = date('H');

    if (!isset($aggregated[$date])) {
        $aggregated[$date] = [
            'events_total' => 0,
            'unique_sessions' => [],
            'events_by_hour' => array_fill(0, 24, 0),
            'events_by_type' => []
        ];
    }

    $aggregated[$date]['events_total']++;
    $aggregated[$date]['events_by_hour'][$hour]++;

    $eventType = $event['name'];
    if (!isset($aggregated[$date]['events_by_type'][$eventType])) {
        $aggregated[$date]['events_by_type'][$eventType] = 0;
    }
    $aggregated[$date]['events_by_type'][$eventType]++;

    $sessionId = $event['session_id'];
    if (!in_array($sessionId, $aggregated[$date]['unique_sessions'])) {
        $aggregated[$date]['unique_sessions'][] = $sessionId;
    }

    file_put_contents(AGGREGATED_FILE, json_encode($aggregated, JSON_PRETTY_PRINT));
}

/**
 * Events laden
 */
function loadEvents() {
    if (!file_exists(EVENTS_FILE)) {
        return [];
    }

    $content = file_get_contents(EVENTS_FILE);
    $events = json_decode($content, true);

    return is_array($events) ? $events : [];
}

/**
 * Events speichern
 */
function saveEvents($events) {
    // Rotation bei zu vielen Events
    if (count($events) > MAX_EVENTS) {
        $events = array_slice($events, -MAX_EVENTS);
    }

    // Alte Events aufräumen
    $cutoffTime = time() - (RETENTION_DAYS * 86400);
    $events = array_filter($events, function($event) use ($cutoffTime) {
        return $event['timestamp'] > $cutoffTime;
    });

    // Events speichern
    file_put_contents(EVENTS_FILE, json_encode(array_values($events), JSON_PRETTY_PRINT));

    // Index erstellen
    rebuildIndex($events);
}

/**
 * Index für schnelle Abfragen erstellen
 */
function rebuildIndex($events) {
    $index = [
        'by_date' => [],
        'by_type' => [],
        'by_session' => []
    ];

    foreach ($events as $event) {
        $date = date('Y-m-d', $event['timestamp']);
        $type = $event['name'];
        $session = $event['session_id'];

        if (!isset($index['by_date'][$date])) {
            $index['by_date'][$date] = [];
        }
        $index['by_date'][$date][] = $event['id'];

        if (!isset($index['by_type'][$type])) {
            $index['by_type'][$type] = [];
        }
        $index['by_type'][$type][] = $event['id'];

        if (!isset($index['by_session'][$session])) {
            $index['by_session'][$session] = [];
        }
        $index['by_session'][$session][] = $event['id'];
    }

    file_put_contents(ANALYTICS_DIR . '/index.json', json_encode($index, JSON_PRETTY_PRINT));
}

/**
 * Event-ID generieren
 */
function generateEventId() {
    return uniqid('evt_', true);
}

/**
 * IP-Adresse hashen (DSGVO-konform)
 */
function hashIp($ip) {
    return hash('sha256', $ip . 'layerstore_salt');
}

/**
 * Client IP ermitteln
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Session ID ermitteln oder erstellen
 */
function getSessionId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return session_id();
}

/**
 * User Agent ermitteln
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

/**
 * Referrer ermitteln
 */
function getReferrer() {
    return $_SERVER['HTTP_REFERER'] ?? '';
}

/**
 * Aktuelle URL ermitteln
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
}

/**
 * Events abrufen (für Dashboard)
 */
function getEvents($filters = []) {
    $events = loadEvents();

    // Filter anwenden
    if (!empty($filters['date_from'])) {
        $from = strtotime($filters['date_from']);
        $events = array_filter($events, function($e) use ($from) {
            return $e['timestamp'] >= $from;
        });
    }

    if (!empty($filters['date_to'])) {
        $to = strtotime($filters['date_to'] . ' 23:59:59');
        $events = array_filter($events, function($e) use ($to) {
            return $e['timestamp'] <= $to;
        });
    }

    if (!empty($filters['type'])) {
        $events = array_filter($events, function($e) use ($filters) {
            return $e['name'] === $filters['type'];
        });
    }

    if (!empty($filters['session_id'])) {
        $events = array_filter($events, function($e) use ($filters) {
            return $e['session_id'] === $filters['session_id'];
        });
    }

    return array_values($events);
}

/**
 * Conversion Rate berechnen
 */
function getConversionRate($period = '7d') {
    $funnelFile = ANALYTICS_DIR . '/funnel.json';

    if (!file_exists($funnelFile)) {
        return [
            'cart_rate' => 0,
            'checkout_rate' => 0,
            'purchase_rate' => 0
        ];
    }

    $funnel = json_decode(file_get_contents($funnelFile), true);
    $startDate = getDatePeriodStart($period);

    $totalViews = 0;
    $totalCarts = 0;
    $totalCheckouts = 0;
    $totalPurchases = 0;

    foreach ($funnel as $date => $data) {
        if (strtotime($date) >= $startDate) {
            $totalViews += count($data['sessions'] ?? []);
            $totalCarts += $data['add_to_carts'] ?? 0;
            $totalCheckouts += $data['checkouts'] ?? 0;
            $totalPurchases += $data['purchases'] ?? 0;
        }
    }

    return [
        'cart_rate' => $totalViews > 0 ? round(($totalCarts / $totalViews) * 100, 2) : 0,
        'checkout_rate' => $totalCarts > 0 ? round(($totalCheckouts / $totalCarts) * 100, 2) : 0,
        'purchase_rate' => $totalCheckouts > 0 ? round(($totalPurchases / $totalCheckouts) * 100, 2) : 0,
        'overall_rate' => $totalViews > 0 ? round(($totalPurchases / $totalViews) * 100, 2) : 0,
        'funnel' => [
            'views' => $totalViews,
            'carts' => $totalCarts,
            'checkouts' => $totalCheckouts,
            'purchases' => $totalPurchases
        ]
    ];
}

/**
 * Durchschnittlicher Bestellwert (AOV)
 */
function getAverageOrderValue($period = '30d') {
    $revenueFile = ANALYTICS_DIR . '/revenue.json';

    if (!file_exists($revenueFile)) {
        return 0;
    }

    $revenueData = json_decode(file_get_contents($revenueFile), true);
    $startDate = getDatePeriodStart($period);

    $totalRevenue = 0;
    $totalOrders = 0;

    foreach ($revenueData as $date => $data) {
        if (strtotime($date) >= $startDate) {
            $totalRevenue += $data['total'] ?? 0;
            $totalOrders += $data['orders'] ?? 0;
        }
    }

    return $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
}

/**
 * Top Produkte ermitteln
 */
function getTopProducts($limit = 10, $metric = 'views') {
    $productsFile = ANALYTICS_DIR . '/products.json';

    if (!file_exists($productsFile)) {
        return [];
    }

    $products = json_decode(file_get_contents($productsFile), true);

    // Sortieren nach gewünschter Metrik
    uasort($products, function($a, $b) use ($metric) {
        return ($b[$metric] ?? 0) <=> ($a[$metric] ?? 0);
    });

    return array_slice($products, 0, $limit);
}

/**
 * Cart Abandonment Rate
 */
function getCartAbandonmentRate($period = '30d') {
    $funnelFile = ANALYTICS_DIR . '/funnel.json';

    if (!file_exists($funnelFile)) {
        return 0;
    }

    $funnel = json_decode(file_get_contents($funnelFile), true);
    $startDate = getDatePeriodStart($period);

    $totalCarts = 0;
    $totalPurchases = 0;

    foreach ($funnel as $date => $data) {
        if (strtotime($date) >= $startDate) {
            $totalCarts += $data['add_to_carts'] ?? 0;
            $totalPurchases += $data['purchases'] ?? 0;
        }
    }

    // Abandonment Rate = (Carts - Purchases) / Carts
    return $totalCarts > 0 ? round((($totalCarts - $totalPurchases) / $totalCarts) * 100, 2) : 0;
}

/**
 * Zeitraum Start ermitteln
 */
function getDatePeriodStart($period) {
    $now = time();

    switch ($period) {
        case '1d':
            return $now - 86400;
        case '7d':
            return $now - (7 * 86400);
        case '30d':
            return $now - (30 * 86400);
        case '90d':
            return $now - (90 * 86400);
        default:
            return $now - (7 * 86400);
    }
}

/**
 * API Endpunkt für Tracking (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'track':
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = trackEvent($input['event'] ?? 'unknown', $input['data'] ?? []);
            echo json_encode(['success' => true, 'event_id' => $eventId]);
            break;

        case 'ecommerce':
            $input = json_decode(file_get_contents('php://input'), true);
            trackEcommerceEvent($input['type'] ?? 'unknown', $input['data'] ?? []);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
