<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | LayerStore Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: #FAF9F0;
            min-height: 100vh;
        }

        .admin-header {
            background: #232E3D;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 1.25rem;
            color: #F0ECDA;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .header-nav a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .header-nav a:hover,
        .header-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .metric-title {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 600;
            color: #232F3D;
        }

        .metric-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .metric-change.positive {
            color: #22c55e;
        }

        .metric-change.negative {
            color: #dc2626;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.1rem;
            color: #232F3D;
        }

        .period-selector {
            display: flex;
            gap: 0.5rem;
        }

        .period-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .period-btn:hover {
            border-color: #232F3D;
        }

        .period-btn.active {
            background: #232F3D;
            color: white;
            border-color: #232F3D;
        }

        /* Funnel Chart */
        .funnel-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .funnel-step {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .funnel-label {
            min-width: 120px;
            font-size: 0.9rem;
            color: #333;
        }

        .funnel-bar {
            flex: 1;
            height: 40px;
            background: #f0f0f0;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .funnel-fill {
            height: 100%;
            background: linear-gradient(90deg, #232F3D, #4A5A6A);
            border-radius: 6px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 1rem;
        }

        .funnel-count {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .funnel-rate {
            min-width: 80px;
            text-align: right;
            font-size: 0.85rem;
            color: #666;
        }

        /* Product Table */
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .product-table th {
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .product-table td {
            font-size: 0.9rem;
        }

        .product-table tr:hover {
            background: #f9f9f9;
        }

        /* Event Log */
        .event-log {
            max-height: 400px;
            overflow-y: auto;
        }

        .event-item {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            font-size: 0.85rem;
        }

        .event-item:last-child {
            border-bottom: none;
        }

        .event-type {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .event-type.view { background: #dbeafe; color: #1e40af; }
        .event-type.cart { background: #fef3c7; color: #92400e; }
        .event-type.checkout { background: #e0e7ff; color: #4338ca; }
        .event-type.purchase { background: #dcfce7; color: #166534; }

        .event-time {
            color: #888;
            font-size: 0.75rem;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #232F3D;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Chart Container */
        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Revenue Chart Bars */
        .revenue-bars {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            height: 200px;
            padding-top: 1rem;
        }

        .revenue-bar {
            flex: 1;
            background: linear-gradient(180deg, #232F3D, #4A5A6A);
            border-radius: 4px 4px 0 0;
            min-height: 4px;
            position: relative;
            transition: height 0.3s ease;
        }

        .revenue-bar:hover {
            opacity: 0.8;
        }

        .revenue-bar-tooltip {
            position: absolute;
            bottom: calc(100% + 5px);
            left: 50%;
            transform: translateX(-50%);
            background: #232F3D;
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            display: none;
            z-index: 10;
        }

        .revenue-bar:hover .revenue-bar-tooltip {
            display: block;
        }

        .revenue-labels {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.7rem;
            color: #666;
        }

        .revenue-label {
            flex: 1;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .admin-header {
                flex-direction: column;
                gap: 1rem;
            }

            .funnel-step {
                flex-wrap: wrap;
            }

            .funnel-label {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>LayerStore Analytics</h1>
        <nav class="header-nav">
            <a href="/admin">Admin</a>
            <a href="/admin/analytics.php" class="active">Analytics</a>
        </nav>
    </div>

    <div class="admin-content">
        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Gesamtumsatz (30 Tage)</div>
                <div class="metric-value" id="totalRevenue">-</div>
                <div class="metric-change" id="revenueChange">-</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Bestellungen</div>
                <div class="metric-value" id="totalOrders">-</div>
                <div class="metric-change" id="ordersChange">-</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Durchschnittlicher Bestellwert</div>
                <div class="metric-value" id="avgOrderValue">-</div>
                <div class="metric-change" id="aovChange">-</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Conversion Rate (Checkout)</div>
                <div class="metric-value" id="conversionRate">-</div>
                <div class="metric-change" id="conversionChange">-</div>
            </div>
        </div>

        <!-- Conversion Funnel -->
        <div class="section">
            <div class="section-header">
                <h2>Conversion Funnel</h2>
                <div class="period-selector">
                    <button class="period-btn" data-period="7d">7 Tage</button>
                    <button class="period-btn active" data-period="30d">30 Tage</button>
                    <button class="period-btn" data-period="90d">90 Tage</button>
                </div>
            </div>
            <div class="funnel-container" id="funnelContainer">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="section">
            <div class="section-header">
                <h2>Top Produkte</h2>
                <select id="productMetric" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="views">Nach Aufrufen</option>
                    <option value="add_to_carts">Nach Warenkorb</option>
                    <option value="purchases">Nach Käufen</option>
                </select>
            </div>
            <div id="productsContainer">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="section">
            <div class="section-header">
                <h2>Umsatzverlauf</h2>
            </div>
            <div class="chart-container" id="revenueChart">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="section">
            <div class="section-header">
                <h2>Letzte Events</h2>
                <button class="period-btn" id="refreshEvents">Aktualisieren</button>
            </div>
            <div class="event-log" id="eventLog">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '/analytics/tracking.php';
        let currentPeriod = '30d';

        // Format numbers
        function formatNumber(num) {
            return new Intl.NumberFormat('de-DE').format(num);
        }

        // Format currency
        function formatCurrency(num) {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(num);
        }

        // Load metrics
        async function loadMetrics() {
            try {
                const response = await fetch(`${API_BASE}?action=getMetrics&period=${currentPeriod}`);
                const data = await response.json();

                document.getElementById('totalRevenue').textContent = formatCurrency(data.revenue || 0);
                document.getElementById('totalOrders').textContent = formatNumber(data.orders || 0);
                document.getElementById('avgOrderValue').textContent = formatCurrency(data.aov || 0);
                document.getElementById('conversionRate').textContent = (data.conversion || 0) + '%';
            } catch (error) {
                console.error('Error loading metrics:', error);
            }
        }

        // Load funnel
        async function loadFunnel() {
            const container = document.getElementById('funnelContainer');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

            try {
                const response = await fetch(`${API_BASE}?action=getFunnel&period=${currentPeriod}`);
                const data = await response.json();

                if (!data || !data.funnel) {
                    container.innerHTML = '<div class="empty-state">Keine Daten verfügbar</div>';
                    return;
                }

                const funnel = data.funnel;
                const maxCount = Math.max(funnel.views, funnel.carts, funnel.checkouts, funnel.purchases);

                container.innerHTML = `
                    <div class="funnel-step">
                        <div class="funnel-label">Produktansichten</div>
                        <div class="funnel-bar">
                            <div class="funnel-fill" style="width: ${(funnel.views / maxCount) * 100}%">
                                <span class="funnel-count">${formatNumber(funnel.views)}</span>
                            </div>
                        </div>
                        <div class="funnel-rate">100%</div>
                    </div>
                    <div class="funnel-step">
                        <div class="funnel-label">Zum Warenkorb</div>
                        <div class="funnel-bar">
                            <div class="funnel-fill" style="width: ${(funnel.carts / maxCount) * 100}%">
                                <span class="funnel-count">${formatNumber(funnel.carts)}</span>
                            </div>
                        </div>
                        <div class="funnel-rate">${funnel.cart_rate || 0}%</div>
                    </div>
                    <div class="funnel-step">
                        <div class="funnel-label">Checkout gestartet</div>
                        <div class="funnel-bar">
                            <div class="funnel-fill" style="width: ${(funnel.checkouts / maxCount) * 100}%">
                                <span class="funnel-count">${formatNumber(funnel.checkouts)}</span>
                            </div>
                        </div>
                        <div class="funnel-rate">${funnel.checkout_rate || 0}%</div>
                    </div>
                    <div class="funnel-step">
                        <div class="funnel-label">Käufe</div>
                        <div class="funnel-bar">
                            <div class="funnel-fill" style="width: ${(funnel.purchases / maxCount) * 100}%">
                                <span class="funnel-count">${formatNumber(funnel.purchases)}</span>
                            </div>
                        </div>
                        <div class="funnel-rate">${funnel.purchase_rate || 0}%</div>
                    </div>
                    <div class="funnel-step">
                        <div class="funnel-label">Gesamt-Conversion</div>
                        <div class="funnel-bar">
                            <div class="funnel-fill" style="width: ${(funnel.purchases / funnel.views) * 100}%">
                                <span class="funnel-count">${formatNumber(funnel.purchases)}</span>
                            </div>
                        </div>
                        <div class="funnel-rate">${funnel.overall_rate || 0}%</div>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading funnel:', error);
                container.innerHTML = '<div class="empty-state">Fehler beim Laden der Daten</div>';
            }
        }

        // Load products
        async function loadProducts(metric = 'views') {
            const container = document.getElementById('productsContainer');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

            try {
                const response = await fetch(`${API_BASE}?action=getProducts&metric=${metric}&limit=10`);
                const products = await response.json();

                if (!products || products.length === 0) {
                    container.innerHTML = '<div class="empty-state">Keine Produkte verfügbar</div>';
                    return;
                }

                container.innerHTML = `
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Produkt</th>
                                <th>Aufrufe</th>
                                <th>Warenkorb</th>
                                <th>Käufe</th>
                                <th>Umsatz</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${products.map(p => `
                                <tr>
                                    <td>${p.name || p.id}</td>
                                    <td>${formatNumber(p.views || 0)}</td>
                                    <td>${formatNumber(p.add_to_carts || 0)}</td>
                                    <td>${formatNumber(p.purchases || 0)}</td>
                                    <td>${formatCurrency(p.revenue || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } catch (error) {
                console.error('Error loading products:', error);
                container.innerHTML = '<div class="empty-state">Fehler beim Laden der Produkte</div>';
            }
        }

        // Load revenue chart
        async function loadRevenueChart() {
            const container = document.getElementById('revenueChart');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

            try {
                const response = await fetch(`${API_BASE}?action=getRevenue&period=${currentPeriod}`);
                const data = await response.json();

                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="empty-state">Keine Umsatzdaten verfügbar</div>';
                    return;
                }

                const maxValue = Math.max(...data.map(d => d.total));

                container.innerHTML = `
                    <div class="revenue-bars">
                        ${data.map(d => `
                            <div class="revenue-bar" style="height: ${(d.total / maxValue) * 180}px">
                                <div class="revenue-bar-tooltip">${formatCurrency(d.total)}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="revenue-labels">
                        ${data.map(d => `<div class="revenue-label">${d.date}</div>`).join('')}
                    </div>
                `;
            } catch (error) {
                console.error('Error loading revenue chart:', error);
                container.innerHTML = '<div class="empty-state">Fehler beim Laden der Umsatzdaten</div>';
            }
        }

        // Load events
        async function loadEvents() {
            const container = document.getElementById('eventLog');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

            try {
                const response = await fetch(`${API_BASE}?action=getEvents&limit=50`);
                const events = await response.json();

                if (!events || events.length === 0) {
                    container.innerHTML = '<div class="empty-state">Keine Events verfügbar</div>';
                    return;
                }

                container.innerHTML = events.map(e => {
                    const typeClass = e.name.includes('view') ? 'view' :
                                     e.name.includes('cart') ? 'cart' :
                                     e.name.includes('checkout') ? 'checkout' :
                                     e.name.includes('purchase') ? 'purchase' : '';

                    return `
                        <div class="event-item">
                            <span class="event-type ${typeClass}">${e.name}</span>
                            <span style="margin-left: 1rem; color: #333;">${JSON.stringify(e.data).substring(0, 50)}...</span>
                            <span class="event-time" style="float: right;">${new Date(e.datetime).toLocaleString('de-DE')}</span>
                        </div>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading events:', error);
                container.innerHTML = '<div class="empty-state">Fehler beim Laden der Events</div>';
            }
        }

        // Period selector
        document.querySelectorAll('.period-btn[data-period]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.period-btn[data-period]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;
                loadAll();
            });
        });

        // Product metric selector
        document.getElementById('productMetric').addEventListener('change', (e) => {
            loadProducts(e.target.value);
        });

        // Refresh events
        document.getElementById('refreshEvents').addEventListener('click', loadEvents);

        // Load all data
        function loadAll() {
            loadMetrics();
            loadFunnel();
            loadProducts();
            loadRevenueChart();
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            loadAll();
            loadEvents();
        });
    </script>
</body>
</html>
