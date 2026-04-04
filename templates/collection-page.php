<?php
/**
 * Collection Page Template
 *
 * Dynamische Produkt-Liste nach Kollektion mit Filtern
 *
 * Parameter (URL):
 * - category: Kategorie-ID (z.B. 'easter')
 * - sort: Sortierung (name, price, newest)
 * - order: ASC oder DESC
 * - color: Farb-Filter
 * - min_price, max_price: Preis-Filter
 * - search: Suchbegriff
 *
 * @version 1.0.0
 */

// Kategorie aus URL Parameter
$categorySlug = $_GET['category'] ?? 'easter';

// Produktdaten laden
$productsJsonPath = __DIR__ . '/../products/products.json';
$productsData = [];

if (file_exists($productsJsonPath)) {
    $json = file_get_contents($productsJsonPath);
    $productsData = json_decode($json, true);
}

// Kategorie-Informationen finden
$currentCategory = null;
foreach ($productsData['categories'] ?? [] as $cat) {
    if ($cat['id'] === $categorySlug) {
        $currentCategory = $cat;
        break;
    }
}

// Produkte für diese Kategorie
$products = [];
foreach ($productsData['products'] ?? [] as $product) {
    if ($product['category'] === $categorySlug) {
        $products[] = $product;
    }
}

// Farben für Filter
$allColors = $productsData['colors'] ?? [];

// Preis-Bereich
$minPrice = null;
$maxPrice = null;
foreach ($products as $p) {
    $price = $p['price'] ?? 0;
    if ($minPrice === null || $price < $minPrice) $minPrice = $price;
    if ($maxPrice === null || $price > $maxPrice) $maxPrice = $price;
}

// Page-Titel
$pageTitle = $currentCategory['name'] ?? 'Kollektion';
$pageDescription = $currentCategory['description'] ?? 'Entdecken Sie unsere Produkte';

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | LayerStore</title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <link rel="preload" href="/ls-logo-beige.svg" as="image">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #FAF9F0;
            --bg-secondary: #F5F3EB;
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --accent: #232F3D;
            --accent-light: #4A5A6A;
            --border: #e7e5e4;
            --danger: #dc2626;
            --success: #22c55e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #232E3D;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo img {
            height: 24px;
            width: auto;
            padding: 6px 0;
        }

        nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.8rem;
            font-weight: 400;
            transition: color 0.3s ease;
            position: relative;
        }

        nav a:hover {
            color: white;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            padding: 0.3rem 0.5rem;
        }

        .cart-icon svg {
            width: 20px;
            height: 20px;
            fill: #F0ECDA;
        }

        .cart-icon:hover svg {
            fill: white;
        }

        .cart-count {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--accent);
            color: white;
            font-size: 0.65rem;
            font-weight: 600;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            opacity: 0;
        }

        .cart-count.visible {
            opacity: 1;
        }

        /* Hero */
        .collection-hero {
            padding: 120px 2rem 3rem;
            text-align: center;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }

        .collection-emoji {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .collection-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .collection-description {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Layout */
        .collection-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 3rem;
        }

        /* Sidebar Filters */
        .filters-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .filter-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-toggle {
            display: none;
            width: 24px;
            height: 24px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
        }

        /* Checkbox Filters */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.4rem 0;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .checkbox-item label {
            cursor: pointer;
            font-size: 0.9rem;
            color: var(--text-secondary);
            flex: 1;
        }

        .checkbox-item .count {
            font-size: 0.8rem;
            color: var(--text-secondary);
            opacity: 0.7;
        }

        /* Color Filter */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }

        .color-option {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 1px var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.active {
            box-shadow: 0 0 0 2px var(--accent);
        }

        .color-option.active::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.8rem;
            text-shadow: 0 0 2px rgba(0,0,0,0.5);
        }

        /* Price Range */
        .price-range {
            padding: 0.5rem 0;
        }

        .price-inputs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .price-inputs input {
            flex: 1;
            padding: 0.6rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .price-inputs input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .price-range-slider {
            width: 100%;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            position: relative;
            margin: 1rem 0;
        }

        .price-range-slider input {
            width: 100%;
            height: 4px;
            background: transparent;
            -webkit-appearance: none;
            appearance: none;
            position: absolute;
            top: 0;
            left: 0;
        }

        .price-range-slider input::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: var(--accent);
            border-radius: 50%;
            cursor: pointer;
        }

        .price-range-slider input::-moz-range-thumb {
            width: 16px;
            height: 16px;
            background: var(--accent);
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        /* Product Card (inline for this template) */
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .product-card-image {
            width: 100%;
            padding-top: 100%;
            position: relative;
            overflow: hidden;
            background: var(--bg-secondary);
        }

        .product-card-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-card-image img {
            transform: scale(1.08);
        }

        .product-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .product-badge.featured {
            background: var(--accent);
            color: white;
        }

        .product-badge.sold-out {
            background: rgba(0, 0, 0, 0.7);
            color: white;
        }

        .product-card-content {
            padding: 1.25rem;
        }

        .product-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .product-card-description {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .product-card-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent);
        }

        .product-card-add-btn {
            width: 44px;
            height: 44px;
            border: 2px solid var(--border);
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .product-card-add-btn:hover:not(:disabled) {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        .product-card-add-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .product-card-colors {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.75rem;
        }

        .color-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 1px var(--border);
        }

        /* Results Header */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-count {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .results-count strong {
            color: var(--text-primary);
        }

        .sort-select {
            padding: 0.6rem 2rem 0.6rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L2 4h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
        }

        /* Active Filters */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            background: var(--bg-secondary);
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        .active-filter-tag button {
            background: none;
            border: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .active-filter-tag button:hover {
            background: var(--border);
        }

        .clear-filters {
            padding: 0.4rem 0.8rem;
            background: none;
            border: none;
            color: var(--accent);
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: underline;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        /* Mobile Filters Toggle */
        .mobile-filters-toggle {
            display: none;
            width: 100%;
            padding: 1rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            align-items: center;
            justify-content: space-between;
        }

        /* Footer */
        footer {
            background: var(--text-primary);
            color: white;
            padding: 4rem 3rem 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: block;
            margin-bottom: 0.75rem;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .collection-container {
                grid-template-columns: 1fr;
            }

            .filters-sidebar {
                display: none;
            }

            .filters-sidebar.active {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: var(--bg-primary);
                z-index: 2000;
                padding: 2rem;
                overflow-y: auto;
            }

            .mobile-filters-toggle {
                display: flex;
            }

            .filters-close {
                display: block;
                position: absolute;
                top: 1rem;
                right: 1rem;
                background: white;
                border: 1px solid var(--border);
                width: 36px;
                height: 36px;
                border-radius: 50%;
                font-size: 1.2rem;
                cursor: pointer;
            }
        }

        @media (max-width: 768px) {
            header {
                padding: 0.65rem 0.75rem;
            }

            nav a:not(.cart-icon) {
                display: none;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }

            .product-card-content {
                padding: 0.75rem;
            }

            .product-card-title {
                font-size: 0.95rem;
            }

            .product-card-price {
                font-size: 1rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .skeleton {
            background: linear-gradient(90deg, var(--bg-secondary) 25%, var(--bg-primary) 50%, var(--bg-secondary) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <a href="/" class="logo">
            <img src="/ls-logo-beige.svg" alt="LayerStore" width="60" height="24">
        </a>
        <nav>
            <a href="/">Startseite</a>
            <a href="/collections">Kollektionen</a>
            <a href="/contact">Kontakt</a>
            <a href="/cart" class="cart-icon" id="cartIcon" title="Warenkorb">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                <span class="cart-count" id="cartCount">0</span>
            </a>
        </nav>
    </header>

    <!-- Hero -->
    <section class="collection-hero">
        <div class="collection-emoji"><?php echo $currentCategory['emoji'] ?? '🎨'; ?></div>
        <h1 class="collection-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="collection-description"><?php echo htmlspecialchars($pageDescription); ?></p>
    </section>

    <!-- Main Container -->
    <div class="collection-container">
        <!-- Mobile Filters Toggle -->
        <button class="mobile-filters-toggle" id="mobileFiltersToggle">
            <span>🔍 Filter</span>
            <span id="filterCount">(0)</span>
        </button>

        <!-- Sidebar Filters -->
        <aside class="filters-sidebar" id="filtersSidebar">
            <button class="filters-close" id="filtersClose" style="display: none;">×</button>

            <!-- Color Filter -->
            <div class="filter-section">
                <div class="filter-title">
                    Farbe
                    <button class="filter-toggle" aria-label="Toggle">−</button>
                </div>
                <div class="color-grid" id="colorFilters">
                    <?php
                    $usedColors = [];
                    foreach ($products as $p) {
                        foreach ($p['colors'] ?? [] as $c) {
                            $usedColors[$c] = true;
                        }
                    }
                    foreach ($allColors as $color):
                        if (!isset($usedColors[$color['id']])) continue;
                    ?>
                    <div class="color-option"
                         data-color="<?php echo $color['id']; ?>"
                         style="background-color: <?php echo $color['hex']; ?>"
                         title="<?php echo htmlspecialchars($color['name']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Price Filter -->
            <div class="filter-section">
                <div class="filter-title">
                    Preis
                    <button class="filter-toggle" aria-label="Toggle">−</button>
                </div>
                <div class="price-range">
                    <div class="price-inputs">
                        <input type="number" id="minPrice" placeholder="Min" min="0" step="0.5" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                        <input type="number" id="maxPrice" placeholder="Max" min="0" step="0.5" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                    </div>
                    <div class="price-range-slider">
                        <input type="range" id="priceSlider" min="<?php echo $minPrice ?: 0; ?>" max="<?php echo $maxPrice ?: 50; ?>" step="0.5">
                    </div>
                </div>
            </div>

            <!-- Stock Filter -->
            <div class="filter-section">
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="inStockFilter">
                        <span>Nur verfügbar</span>
                    </label>
                </div>
            </div>
        </aside>

        <!-- Products Grid -->
        <main>
            <!-- Results Header -->
            <div class="results-header">
                <div class="results-count">
                    <strong id="resultsCount"><?php echo count($products); ?></strong> Produkte
                </div>
                <select class="sort-select" id="sortSelect">
                    <option value="name-asc">Name: A-Z</option>
                    <option value="name-desc">Name: Z-A</option>
                    <option value="price-asc">Preis: Aufsteigend</option>
                    <option value="price-desc">Preis: Absteigend</option>
                    <option value="newest">Neueste zuerst</option>
                </select>
            </div>

            <!-- Active Filters -->
            <div class="active-filters" id="activeFilters"></div>

            <!-- Products -->
            <div class="products-grid" id="productsGrid">
                <?php foreach ($products as $product):
                    $image = $product['images'][0] ?? '/images/placeholder.jpg';
                    $price = number_format($product['price'], 2, ',', '');
                    $inStock = ($product['stock'] ?? 0) > 0;
                ?>
                <div class="product-card"
                     data-id="<?php echo $product['id']; ?>"
                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                     data-price="<?php echo $product['price']; ?>"
                     data-stock="<?php echo $product['stock'] ?? 0; ?>"
                     data-colors="<?php echo implode(',', $product['colors'] ?? []); ?>">
                    <a href="/products/<?php echo $product['id']; ?>">
                        <div class="product-card-image">
                            <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                            <?php if ($product['featured'] ?? false): ?>
                            <span class="product-badge featured">Beliebt</span>
                            <?php endif; ?>
                            <?php if (!$inStock): ?>
                            <span class="product-badge sold-out">Ausverkauft</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-card-content">
                        <h3 class="product-card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-card-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-card-footer">
                            <span class="product-card-price"><?php echo $price; ?> €</span>
                            <button class="product-card-add-btn"
                                    data-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                    <?php echo $inStock ? '' : 'disabled'; ?>>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($product['colors'])): ?>
                        <div class="product-card-colors">
                            <?php foreach (array_slice($product['colors'], 0, 5) as $color):
                                $colorMap = [
                                    'white' => '#FFFFFF', 'black' => '#000000', 'beige' => '#F5F5DC',
                                    'pink' => '#FFC0CB', 'blue' => '#4A90D9', 'green' => '#4CAF50',
                                    'yellow' => '#FFEB3B', 'orange' => '#FF9800', 'brown' => '#795548'
                                ];
                                $hex = $colorMap[$color] ?? '#CCCCCC';
                            ?>
                            <span class="color-dot" style="background-color: <?php echo $hex; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty State (hidden by default) -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <h3>Keine Produkte gefunden</h3>
                <p>Versuchen Sie, Ihre Filter anzupassen.</p>
                <button class="clear-filters" onclick="resetFilters()">Alle Filter zurücksetzen</button>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>LayerStore</h4>
                <p>Individuelle 3D-Druck-Kreationen für jeden Anlass.</p>
                <p>© 2026 LayerStore. Alle Rechte vorbehalten.</p>
            </div>
            <div class="footer-section">
                <h4>Entdecken</h4>
                <a href="/collections">Kollektionen</a>
                <a href="/collections/easter">🐰 Ostern</a>
                <a href="/cart">Warenkorb</a>
            </div>
            <div class="footer-section">
                <h4>Rechtliches</h4>
                <a href="/datenschutz">🔒 Datenschutz</a>
                <a href="/impressum">ℹ️ Impressum</a>
                <a href="/contact">📧 Kontakt</a>
            </div>
        </div>
    </footer>

    <script>
        // Products data
        const products = <?php echo json_encode($products); ?>;
        const categorySlug = '<?php echo $categorySlug; ?>';

        // Filter state
        let filters = {
            colors: new Set(),
            minPrice: null,
            maxPrice: null,
            inStock: false,
            search: ''
        };

        // DOM Elements
        const productsGrid = document.getElementById('productsGrid');
        const emptyState = document.getElementById('emptyState');
        const resultsCount = document.getElementById('resultsCount');
        const activeFiltersContainer = document.getElementById('activeFilters');
        const sortSelect = document.getElementById('sortSelect');

        // Filter products
        function filterProducts() {
            let filtered = [...products];

            // Color filter
            if (filters.colors.size > 0) {
                filtered = filtered.filter(p =>
                    p.colors?.some(c => filters.colors.has(c))
                );
            }

            // Price filter
            if (filters.minPrice !== null) {
                filtered = filtered.filter(p => p.price >= filters.minPrice);
            }
            if (filters.maxPrice !== null) {
                filtered = filtered.filter(p => p.price <= filters.maxPrice);
            }

            // Stock filter
            if (filters.inStock) {
                filtered = filtered.filter(p => (p.stock ?? 0) > 0);
            }

            // Search filter
            if (filters.search) {
                const search = filters.search.toLowerCase();
                filtered = filtered.filter(p =>
                    p.name.toLowerCase().includes(search) ||
                    p.description.toLowerCase().includes(search)
                );
            }

            return filtered;
        }

        // Sort products
        function sortProducts(products, sortBy) {
            const [field, order] = sortBy.split('-');

            return products.sort((a, b) => {
                let valA = a[field];
                let valB = b[field];

                if (field === 'price') {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                }

                if (order === 'asc') {
                    return valA > valB ? 1 : -1;
                } else {
                    return valA < valB ? 1 : -1;
                }
            });
        }

        // Render products
        function renderProducts() {
            let filtered = filterProducts();
            const sortBy = sortSelect.value;
            if (sortBy !== 'newest') {
                filtered = sortProducts(filtered, sortBy);
            }

            // Update count
            resultsCount.textContent = filtered.length;

            // Show/hide empty state
            if (filtered.length === 0) {
                productsGrid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            productsGrid.style.display = 'grid';
            emptyState.style.display = 'none';

            // Reorder existing cards
            const cards = Array.from(productsGrid.querySelectorAll('.product-card'));
            const cardMap = new Map(cards.map(c => [c.dataset.id, c]));

            productsGrid.innerHTML = '';

            filtered.forEach(product => {
                const card = cardMap.get(product.id);
                if (card) {
                    productsGrid.appendChild(card);
                }
            });

            updateActiveFilters();
        }

        // Update active filters display
        function updateActiveFilters() {
            const filtersHTML = [];

            filters.colors.forEach(color => {
                filtersHTML.push(`
                    <span class="active-filter-tag">
                        Farbe: ${color}
                        <button onclick="toggleColor('${color}')">×</button>
                    </span>
                `);
            });

            if (filters.minPrice) {
                filtersHTML.push(`
                    <span class="active-filter-tag">
                        Min: ${filters.minPrice} €
                        <button onclick="clearMinPrice()">×</button>
                    </span>
                `);
            }

            if (filters.maxPrice) {
                filtersHTML.push(`
                    <span class="active-filter-tag">
                        Max: ${filters.maxPrice} €
                        <button onclick="clearMaxPrice()">×</button>
                    </span>
                `);
            }

            if (filters.inStock) {
                filtersHTML.push(`
                    <span class="active-filter-tag">
                        Nur verfügbar
                        <button onclick="toggleStock()">×</button>
                    </span>
                `);
            }

            if (filtersHTML.length > 0) {
                filtersHTML.push('<button class="clear-filters" onclick="resetFilters()">Alle zurücksetzen</button>');
            }

            activeFiltersContainer.innerHTML = filtersHTML.join('');
            document.getElementById('filterCount').textContent = `(${filters.colors.size + (filters.minPrice ? 1 : 0) + (filters.maxPrice ? 1 : 0) + (filters.inStock ? 1 : 0)})`;
        }

        // Toggle color filter
        function toggleColor(color) {
            if (filters.colors.has(color)) {
                filters.colors.delete(color);
            } else {
                filters.colors.add(color);
            }

            // Update UI
            document.querySelectorAll('.color-option').forEach(el => {
                el.classList.toggle('active', filters.colors.has(el.dataset.color));
            });

            renderProducts();
        }

        // Toggle stock filter
        function toggleStock() {
            filters.inStock = !filters.inStock;
            document.getElementById('inStockFilter').checked = filters.inStock;
            renderProducts();
        }

        // Clear price filters
        function clearMinPrice() {
            filters.minPrice = null;
            document.getElementById('minPrice').value = '';
            renderProducts();
        }

        function clearMaxPrice() {
            filters.maxPrice = null;
            document.getElementById('maxPrice').value = '';
            renderProducts();
        }

        // Reset all filters
        function resetFilters() {
            filters = {
                colors: new Set(),
                minPrice: null,
                maxPrice: null,
                inStock: false,
                search: ''
            };

            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('inStockFilter').checked = false;
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));

            renderProducts();
        }

        // Event listeners
        document.querySelectorAll('.color-option').forEach(el => {
            el.addEventListener('click', () => toggleColor(el.dataset.color));
        });

        document.getElementById('minPrice').addEventListener('input', (e) => {
            filters.minPrice = parseFloat(e.target.value) || null;
            renderProducts();
        });

        document.getElementById('maxPrice').addEventListener('input', (e) => {
            filters.maxPrice = parseFloat(e.target.value) || null;
            renderProducts();
        });

        document.getElementById('inStockFilter').addEventListener('change', toggleStock);

        sortSelect.addEventListener('change', renderProducts);

        // Mobile filters
        const filtersSidebar = document.getElementById('filtersSidebar');
        const mobileToggle = document.getElementById('mobileFiltersToggle');
        const filtersClose = document.getElementById('filtersClose');

        mobileToggle.addEventListener('click', () => {
            filtersSidebar.classList.add('active');
            filtersClose.style.display = 'flex';
        });

        filtersClose.addEventListener('click', () => {
            filtersSidebar.classList.remove('active');
        });

        // Cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('layerstore_cart') || '[]');
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const cartCount = document.getElementById('cartCount');
            cartCount.textContent = count;
            if (count > 0) {
                cartCount.classList.add('visible');
            } else {
                cartCount.classList.remove('visible');
            }
        }

        // Add to cart
        document.querySelectorAll('.product-card-add-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.id;
                const productName = btn.dataset.name;

                const cart = JSON.parse(localStorage.getItem('layerstore_cart') || '[]');
                const existing = cart.find(item => item.id === productId);

                if (existing) {
                    existing.quantity += 1;
                } else {
                    cart.push({
                        id: productId,
                        name: productName,
                        quantity: 1,
                        color: 'standard',
                        option: 'as-photo'
                    });
                }

                localStorage.setItem('layerstore_cart', JSON.stringify(cart));
                updateCartCount();

                // Visual feedback
                btn.classList.add('added');
                setTimeout(() => btn.classList.remove('added'), 1500);
            });
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateCartCount();
            renderProducts();
        });
    </script>
</body>
</html>
