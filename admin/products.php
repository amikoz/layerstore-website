<?php
/**
 * LayerStore Admin - Product Management
 *
 * Features:
 * - List all products
 * - Add new product
 * - Edit existing product
 * - Delete product
 * - Image upload
 *
 * @version 1.0.0
 * @requires login
 */

session_start();

// Simple auth check (replace with proper auth)
$adminPassword = getenv('ADMIN_PASSWORD') ?? 'layerstore2025';
$isLoggedIn = $_SESSION['admin_logged_in'] ?? false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = true;
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: products.php');
    exit();
}

// File paths
$productsJsonPath = __DIR__ . '/../products/products.json';
$imagesDir = __DIR__ . '/../collections/easter/images';

// Ensure images directory exists
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// Load products
function loadProducts() {
    global $productsJsonPath;
    if (!file_exists($productsJsonPath)) {
        return ['products' => [], 'categories' => [], 'colors' => []];
    }
    $json = file_get_contents($productsJsonPath);
    return json_decode($json, true) ?? [];
}

// Save products
function saveProducts($data) {
    global $productsJsonPath;
    $data['meta']['lastUpdated'] = date('c');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($productsJsonPath, $json);
}

// Handle form submissions
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = loadProducts();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
        case 'edit':
            $productId = $_POST['id'] ?? uniqid('prod_');

            $product = [
                'id' => $productId,
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => (float)$_POST['price'],
                'images' => array_filter(explode("\n", $_POST['images'] ?? '')),
                'category' => $_POST['category'],
                'colors' => array_filter(explode(',', $_POST['colors'] ?? '')),
                'sizes' => array_filter(explode(',', $_POST['sizes'] ?? '')),
                'stock' => (int)$_POST['stock'],
                'featured' => !empty($_POST['featured']),
                'tags' => array_filter(explode(',', $_POST['tags'] ?? '')),
                'customizable' => !empty($_POST['customizable'])
            ];

            // Update or add
            $found = false;
            foreach ($data['products'] as $i => $p) {
                if ($p['id'] === $productId) {
                    $data['products'][$i] = $product;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['products'][] = $product;
            }

            saveProducts($data);
            $successMessage = $action === 'add' ? 'Produkt hinzugefügt!' : 'Produkt aktualisiert!';
            break;

        case 'delete':
            $deleteId = $_POST['id'] ?? '';
            $data['products'] = array_filter($data['products'], fn($p) => $p['id'] !== $deleteId);
            saveProducts($data);
            $successMessage = 'Produkt gelöscht!';
            break;

        case 'upload':
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . $ext;
                $targetPath = $imagesDir . '/' . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $uploadedImage = '/collections/easter/images/' . $filename;
                    $successMessage = 'Bild hochgeladen: ' . $filename;
                } else {
                    $errorMessage = 'Fehler beim Hochladen.';
                }
            }
            break;
    }

    // Redirect to prevent form resubmission
    if (isset($successMessage) || isset($errorMessage)) {
        header('Location: products.php?' . http_build_query(array_filter([
            'success' => $successMessage ?? null,
            'error' => $errorMessage ?? null,
            'edit' => $_POST['id'] ?? null
        ])));
        exit();
    }
}

// Get current data
$productsData = loadProducts();
$products = $productsData['products'] ?? [];
$categories = $productsData['categories'] ?? [];
$colors = $productsData['colors'] ?? [];

// Edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    foreach ($products as $p) {
        if ($p['id'] === $_GET['edit']) {
            $editProduct = $p;
            break;
        }
    }
}

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkte verwalten | LayerStore Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        /* Login Screen */
        .login-screen {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #232E3D 0%, #4A5A6A 100%);
        }

        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-box h1 {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            color: #232E3D;
        }

        /* Header */
        .admin-header {
            background: #232E3D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .admin-header nav {
            display: flex;
            gap: 1.5rem;
        }

        .admin-header nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .admin-header nav a:hover {
            color: white;
        }

        .btn-logout {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Main Content */
        .admin-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .message.error {
            background: #fecaca;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .actions-bar h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background: #232E3D;
            color: white;
        }

        .btn-primary:hover {
            background: #4A5A6A;
        }

        .btn-secondary {
            background: white;
            color: #1d1d1f;
            border: 1px solid #e5e5e7;
        }

        .btn-secondary:hover {
            background: #f5f5f7;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        /* Products Table */
        .products-table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #e5e5e7;
        }

        .products-table th {
            background: #f5f5f7;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6e6e73;
        }

        .products-table tr:hover {
            background: #fafafa;
        }

        .product-name {
            font-weight: 500;
            color: #1d1d1f;
        }

        .product-id {
            font-size: 0.75rem;
            color: #6e6e73;
        }

        .product-image {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            background: #f5f5f7;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .badge-featured {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-customizable {
            background: #dbeafe;
            color: #1e40af;
        }

        .stock-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .stock-indicator.in-stock {
            background: #22c55e;
        }

        .stock-indicator.low-stock {
            background: #eab308;
        }

        .stock-indicator.out-of-stock {
            background: #dc2626;
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        /* Form Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 2rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e5e7;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: #f5f5f7;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            color: #6e6e73;
        }

        .modal-close:hover {
            background: #e5e5e7;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: #1d1d1f;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid #e5e5e7;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #232E3D;
            box-shadow: 0 0 0 3px rgba(35, 46, 61, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-help {
            font-size: 0.75rem;
            color: #6e6e73;
            margin-top: 0.25rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #232E3D;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e5e7;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Image Upload */
        .upload-area {
            border: 2px dashed #e5e5e7;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: #232E3D;
            background: #f5f5f7;
        }

        .upload-area input {
            display: none;
        }

        /* Login Form */
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e5e7;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #232E3D;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: #4A5A6A;
        }

        .login-error {
            background: #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-content {
                padding: 0 1rem;
            }

            .products-table {
                display: block;
                overflow-x: auto;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- Login Screen -->
    <div class="login-screen">
        <div class="login-box">
            <h1>LayerStore Admin</h1>
            <?php if (isset($loginError)): ?>
            <div class="login-error">Falsches Passwort</div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Passwort" required autofocus>
                </div>
                <button type="submit" class="btn-login">Anmelden</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Admin Interface -->
    <div class="admin-header">
        <h1>🛍️ LayerStore Admin</h1>
        <nav>
            <a href="/">Website</a>
            <a href="/collections">Kollektionen</a>
            <a href="/cart">Warenkorb</a>
            <a href="?action=logout" class="btn-logout">Abmelden</a>
        </nav>
    </div>

    <div class="admin-content">
        <?php if (isset($_GET['success'])): ?>
        <div class="message success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <h2>Produkte (<?php echo count($products); ?>)</h2>
            <div style="display: flex; gap: 0.75rem;">
                <button class="btn btn-secondary" onclick="openUploadModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Bild hochladen
                </button>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Produkt hinzufügen
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="products-table-wrapper">
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Bild</th>
                        <th>Produkt</th>
                        <th>ID</th>
                        <th>Kategorie</th>
                        <th>Preis</th>
                        <th>Lager</th>
                        <th style="width: 120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['images'][0])): ?>
                            <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="" class="product-image">
                            <?php else: ?>
                            <div class="product-image"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <?php if (!empty($product['featured'])): ?>
                            <span class="badge badge-featured">Beliebt</span>
                            <?php endif; ?>
                            <?php if (!empty($product['customizable'])): ?>
                            <span class="badge badge-customizable">Personalisierbar</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="product-id"><?php echo htmlspecialchars($product['id']); ?></span></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo number_format($product['price'], 2, ',', ''); ?> €</td>
                        <td>
                            <span class="stock-indicator <?php
                                $stock = $product['stock'] ?? 0;
                                echo $stock > 20 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                            ?>"></span>
                            <?php echo $stock; ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn btn-sm btn-secondary" onclick="openEditModal('<?php echo $product['id']; ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo $product['id']; ?>', '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Form Modal -->
    <div class="modal-overlay" id="productModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Produkt hinzufügen</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="productForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="productName">Produktname *</label>
                        <input type="text" id="productName" name="name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="productPrice">Preis (€) *</label>
                            <input type="number" id="productPrice" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="productStock">Lagerbestand</label>
                            <input type="number" id="productStock" name="stock" min="0" value="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="productCategory">Kategorie *</label>
                        <select id="productCategory" name="category" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="productDescription">Beschreibung</label>
                        <textarea id="productDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="productImages">Bilder (eine pro Zeile)</label>
                        <textarea id="productImages" name="images" rows="3" placeholder="/collections/easter/images/image1.jpg"></textarea>
                        <div class="form-help">Relativer Pfad vom Root-Verzeichnis</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="productColors">Farben (kommagetrennt)</label>
                            <input type="text" id="productColors" name="colors" placeholder="white,black,pink">
                        </div>
                        <div class="form-group">
                            <label for="productSizes">Größen (kommagetrennt)</label>
                            <input type="text" id="productSizes" name="sizes" placeholder="small,medium,large">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="productTags">Tags (kommagetrennt)</label>
                        <input type="text" id="productTags" name="tags" placeholder="ostern,deko,geschenk">
                    </div>

                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" id="productFeatured" name="featured">
                            <label for="productFeatured">Beliebtes Produkt</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="productCustomizable" name="customizable">
                            <label for="productCustomizable">Personalisierbar</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Bild hochladen</h2>
                <button class="modal-close" onclick="closeUploadModal()">×</button>
            </div>
            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="modal-body">
                    <div class="upload-area" id="uploadArea">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6e6e73" stroke-width="1.5" style="margin-bottom: 1rem;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <p>Klicken Sie hier oder ziehen Sie ein Bild hierher</p>
                        <input type="file" id="imageInput" name="image" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Hochladen</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Produkt löschen?</h2>
                <button class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie <strong id="deleteProductName"></strong> wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Abbrechen</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteProductId">
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Product data for editing
        const products = <?php echo json_encode($products); ?>;

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Produkt hinzufügen';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.getElementById('productForm').reset();
            document.getElementById('productModal').classList.add('active');
        }

        function openEditModal(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;

            document.getElementById('modalTitle').textContent = 'Produkt bearbeiten';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name || '';
            document.getElementById('productPrice').value = product.price || '';
            document.getElementById('productStock').value = product.stock || 0;
            document.getElementById('productCategory').value = product.category || '';
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productImages').value = (product.images || []).join('\n');
            document.getElementById('productColors').value = (product.colors || []).join(',');
            document.getElementById('productSizes').value = (product.sizes || []).join(',');
            document.getElementById('productTags').value = (product.tags || []).join(',');
            document.getElementById('productFeatured').checked = !!product.featured;
            document.getElementById('productCustomizable').checked = !!product.customizable;

            document.getElementById('productModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }

        function confirmDelete(productId, productName) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteProductName').textContent = productName;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Upload area interactions
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('imageInput');

        uploadArea.addEventListener('click', () => imageInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
            }
        });

        // Close modals on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });

        // Close modals on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
