<?php
/**
 * LayerStore Product API
 *
 * Endpunkte:
 * - GET /products/api.php               - Alle Produkte
 * - GET /products/api.php?id=xyz        - Einzelnes Produkt
 * - GET /products/api.php?category=xyz  - Filter nach Kategorie
 * - GET /products/api.php?featured=1    - Nur Featured Produkte
 * - GET /products/api.php?search=xyz    - Suche
 * - GET /products/api.php?categories    - Alle Kategorien
 * - GET /products/api.php?colors        - Alle Farben
 *
 * @version 1.0.0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS für CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// JSON-Datei laden
function loadProductsData() {
    $jsonPath = __DIR__ . '/products.json';

    if (!file_exists($jsonPath)) {
        http_response_code(404);
        return ['error' => 'Produktdatenbank nicht gefunden'];
    }

    $json = file_get_contents($jsonPath);
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        return ['error' => 'Fehler beim Lesen der Produktdatenbank'];
    }

    return $data;
}

// Einzelnes Produkt finden
function findProduct($products, $id) {
    foreach ($products as $product) {
        if ($product['id'] === $id) {
            return $product;
        }
    }
    return null;
}

// Produkte filtern
function filterProducts($products, $filters) {
    $filtered = $products;

    // Kategorie-Filter
    if (!empty($filters['category'])) {
        $filtered = array_filter($filtered, function($p) use ($filters) {
            return $p['category'] === $filters['category'];
        });
    }

    // Featured-Filter
    if (!empty($filters['featured'])) {
        $filtered = array_filter($filtered, function($p) {
            return !empty($p['featured']);
        });
    }

    // Such-Filter
    if (!empty($filters['search'])) {
        $search = strtolower($filters['search']);
        $filtered = array_filter($filtered, function($p) use ($search) {
            $name = strtolower($p['name']);
            $desc = strtolower($p['description']);
            $tags = isset($p['tags']) ? implode(' ', $p['tags']) : '';
            return strpos($name, $search) !== false ||
                   strpos($desc, $search) !== false ||
                   strpos($tags, $search) !== false;
        });
    }

    // Farb-Filter
    if (!empty($filters['color'])) {
        $filtered = array_filter($filtered, function($p) use ($filters) {
            return in_array($filters['color'], $p['colors'] ?? []);
        });
    }

    // Preis-Filter (min/max)
    if (!empty($filters['min_price'])) {
        $min = (float) $filters['min_price'];
        $filtered = array_filter($filtered, function($p) use ($min) {
            return $p['price'] >= $min;
        });
    }

    if (!empty($filters['max_price'])) {
        $max = (float) $filters['max_price'];
        $filtered = array_filter($filtered, function($p) use ($max) {
            return $p['price'] <= $max;
        });
    }

    // Stock-Filter (nur verfügbare)
    if (!empty($filters['in_stock'])) {
        $filtered = array_filter($filtered, function($p) {
            return ($p['stock'] ?? 0) > 0;
        });
    }

    // Neu indizieren
    return array_values($filtered);
}

// Produkte sortieren
function sortProducts(&$products, $sortBy = 'name', $order = 'asc') {
    usort($products, function($a, $b) use ($sortBy, $order) {
        $valA = $a[$sortBy] ?? '';
        $valB = $b[$sortBy] ?? '';

        if ($sortBy === 'price') {
            $valA = (float) $valA;
            $valB = (float) $valB;
        }

        if ($order === 'desc') {
            return $valB <=> $valA;
        }
        return $valA <=> $valB;
    });
}

// Haupt-Logik
$data = loadProductsData();

if (isset($data['error'])) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Parameter auswerten
$action = $_GET['action'] ?? 'products';

switch ($action) {
    case 'product':
        // Einzelnes Produkt
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Produkt-ID fehlt'], JSON_PRETTY_PRINT);
            exit();
        }

        $product = findProduct($data['products'], $id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produkt nicht gefunden'], JSON_PRETTY_PRINT);
            exit();
        }

        echo json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'products':
        // Produkte mit Filter
        $filters = [
            'category' => $_GET['category'] ?? '',
            'featured' => $_GET['featured'] ?? '',
            'search' => $_GET['search'] ?? '',
            'color' => $_GET['color'] ?? '',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'in_stock' => $_GET['in_stock'] ?? ''
        ];

        $products = filterProducts($data['products'], $filters);

        // Sortierung
        $sortBy = $_GET['sort_by'] ?? 'name';
        $order = $_GET['order'] ?? 'asc';
        sortProducts($products, $sortBy, $order);

        // Pagination
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;

        $total = count($products);
        $products = array_slice($products, $offset, $limit);

        echo json_encode([
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'categories':
        // Alle Kategorien
        echo json_encode($data['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'category':
        // Einzelne Kategorie mit Produkten
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Kategorie-ID fehlt'], JSON_PRETTY_PRINT);
            exit();
        }

        $category = null;
        foreach ($data['categories'] as $cat) {
            if ($cat['id'] === $id) {
                $category = $cat;
                break;
            }
        }

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Kategorie nicht gefunden'], JSON_PRETTY_PRINT);
            exit();
        }

        $category['products'] = array_values(array_filter($data['products'], function($p) use ($id) {
            return $p['category'] === $id;
        }));

        echo json_encode($category, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'colors':
        // Alle Farben
        echo json_encode($data['colors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'sizes':
        // Alle Größen
        echo json_encode($data['sizes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'meta':
        // Metadaten
        echo json_encode($data['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Action', 'valid_actions' => [
            'products', 'product', 'categories', 'category', 'colors', 'sizes', 'meta'
        ]], JSON_PRETTY_PRINT);
        break;
}
