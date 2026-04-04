<?php
/**
 * Admin Orders Dashboard for LayerStore
 *
 * @package LayerStore\Admin
 */

declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../orders/storage.php';

use LayerStore\Orders\OrderStorage;

// Initialize storage
OrderStorage::init();

// Get current filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Clean empty filters
$filters = array_filter($filters, fn($v) => $v !== '');

// Get orders
$orders = OrderStorage::getAll($filters);

// Get statistics
$stats = OrderStorage::getStatistics();

// Check for export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo OrderStorage::exportToCsv($filters);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellungen - LayerStore Admin</title>
    <style>
        :root {
            --primary: #232E3D;
            --primary-light: #3a4a5c;
            --accent: #ea580c;
            --accent-light: #f97316;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 { font-size: 1.5rem; font-weight: 600; }
        .header-nav { display: flex; gap: 1rem; }
        .header-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .header-nav a:hover, .header-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.info { border-left-color: var(--info); }

        .stat-label { font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text-dark); margin: 0.5rem 0; }
        .stat-sub { font-size: 0.875rem; color: var(--text-muted); }

        /* Filters */
        .filters {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }

        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-muted); }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-light); }

        .btn-secondary { background: var(--bg-white); color: var(--text-dark); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--bg-light); border-color: var(--text-muted); }

        .btn-icon { padding: 0.5rem; }

        /* Table */
        .table-container {
            background: var(--bg-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title { font-size: 1.125rem; font-weight: 600; }
        .table-count { font-size: 0.875rem; color: var(--text-muted); }

        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--bg-light); }
        th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }
        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
        }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover { background: var(--bg-light); }
        tbody tr.has-details { background: #fef3c7; }

        /* Status Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.processing { background: #dbeafe; color: #1e40af; }
        .badge.shipped { background: #ede9fe; color: #5b21b6; }
        .badge.delivered { background: #d1fae5; color: #065f46; }
        .badge.cancelled { background: #fee2e2; color: #991b1b; }

        /* Order Row */
        .order-id { font-family: monospace; font-weight: 600; color: var(--primary); }
        .customer-name { font-weight: 500; }
        .customer-email { font-size: 0.875rem; color: var(--text-muted); }
        .order-total { font-weight: 600; }
        .order-date { font-size: 0.875rem; color: var(--text-muted); }

        /* Actions */
        .action-buttons { display: flex; gap: 0.5rem; }
        .action-buttons .btn { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }

        /* Order Details Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active { display: flex; }

        .modal {
            background: var(--bg-white);
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title { font-size: 1.25rem; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }

        .modal-body { padding: 1.5rem; }
        .modal-section { margin-bottom: 1.5rem; }
        .modal-section h3 { font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--primary); }

        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .detail-item label { display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-item span { font-weight: 500; }

        .items-list { border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .item-row { display: flex; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 500; }
        .item-qty { color: var(--text-muted); font-size: 0.875rem; }
        .item-price { font-weight: 600; }

        .status-history { margin-top: 1rem; }
        .history-item { display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
        .history-item:last-child { border-bottom: none; }
        .history-time { font-size: 0.8125rem; color: var(--text-muted); min-width: 100px; }
        .history-content { flex: 1; }

        .status-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; }
        .status-actions .btn { flex: 1; min-width: 120px; justify-content: center; }
        .btn-pending { background: var(--warning); color: white; }
        .btn-processing { background: var(--info); color: white; }
        .btn-shipped { background: var(--purple); color: white; }
        .btn-delivered { background: var(--success); color: white; }
        .btn-cancelled { background: var(--danger); color: white; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-light: #0f172a;
                --bg-white: #1e293b;
                --text-dark: #f1f5f9;
                --text-muted: #94a3b8;
                --border: #334155;
            }
            .modal { background: #1e293b; }
            tbody tr:hover { background: #334155; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header { padding: 1rem; }
            .filters { flex-direction: column; }
            .filter-group { min-width: 100%; }
            .detail-grid { grid-template-columns: 1fr; }
            .table-container { overflow-x: auto; }
            table { min-width: 600px; }
            .action-buttons { flex-wrap: wrap; }
            .status-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>LayerStore Admin</h1>
        <nav class="header-nav">
            <a href="/">Startseite</a>
            <a href="/admin/" class="active">Bestellungen</a>
        </nav>
    </header>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Gesamtumsatz</div>
                <div class="stat-value"><?php echo $stats['total_revenue_formatted']; ?></div>
                <div class="stat-sub"><?php echo $stats['total_orders']; ?> Bestellungen</div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Abgeschlossen</div>
                <div class="stat-value"><?php echo $stats['by_status']['delivered']; ?></div>
                <div class="stat-sub">Gelieferte Bestellungen</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">In Bearbeitung</div>
                <div class="stat-value"><?php echo $stats['by_status']['processing']; ?></div>
                <div class="stat-sub">Offene Bestellungen</div>
            </div>
            <div class="stat-card info">
                <div class="stat-label">Ausstehend</div>
                <div class="stat-value"><?php echo $stats['by_status']['pending']; ?></div>
                <div class="stat-sub">Warten auf Bearbeitung</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filters" method="GET" action="">
            <div class="filter-group">
                <label for="search">Suche</label>
                <input type="text" id="search" name="search" placeholder="Bestellnummer, Kunde..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Alle Status</option>
                    <?php foreach (OrderStorage::STATUSES as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (($_GET['status'] ?? '') === $key ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="date_from">Von</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label for="date_to">Bis</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
            </div>
            <div class="filter-group" style="flex: 0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Filtern</button>
            </div>
            <div class="filter-group" style="flex: 0;">
                <label>&nbsp;</label>
                <a href="?" class="btn btn-secondary">Reset</a>
            </div>
            <div class="filter-group" style="flex: 0;">
                <label>&nbsp;</label>
                <a href="?<?php echo http_build_query(array_filter($filters, fn($v) => $v !== '') + ['export' => 'csv']); ?>" class="btn btn-secondary">
                    Export CSV
                </a>
            </div>
        </form>

        <!-- Orders Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <span class="table-title">Bestellungen</span>
                    <span class="table-count">(<?php echo count($orders); ?>)</span>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <p>Keine Bestellungen gefunden.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Bestellnummer</th>
                            <th>Kunde</th>
                            <th>Artikel</th>
                            <th>Betrag</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <td><span class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></span></td>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer']['name'] ?: 'N/A'); ?></div>
                                    <div class="customer-email"><?php echo htmlspecialchars($order['customer']['email']); ?></div>
                                </td>
                                <td>
                                    <?php
                                    $itemNames = array_slice(array_map(fn($i) => $i['name'], $order['items']), 0, 2);
                                    echo htmlspecialchars(implode(', ', $itemNames));
                                    if (count($order['items']) > 2) {
                                        echo ' +' . (count($order['items']) - 2);
                                    }
                                    ?>
                                </td>
                                <td><span class="order-total"><?php echo OrderStorage::formatPrice($order['amounts']['total']); ?></span></td>
                                <td><span class="order-date"><?php echo htmlspecialchars($order['created_at_formatted']); ?></span></td>
                                <td><span class="badge <?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status_label']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-secondary btn-icon" onclick="viewOrder('<?php echo htmlspecialchars($order['order_id']); ?>')" title="Details">
                                            👁
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Bestellnummer</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        const API_URL = '/orders/api.php';

        // View order details
        async function viewOrder(orderId) {
            const modal = document.getElementById('orderModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Laden...';
            modalBody.innerHTML = '<div style="text-align:center;padding:2rem;">⏳ Laden...</div>';
            modal.classList.add('active');

            try {
                const response = await fetch(`${API_URL}?id=${encodeURIComponent(orderId)}`);
                if (!response.ok) throw new Error('Failed to load order');

                const order = await response.json();
                renderOrderDetails(order);
            } catch (error) {
                modalTitle.textContent = 'Fehler';
                modalBody.innerHTML = '<div style="text-align:center;padding:2rem;color:#ef4444;">❌ Konnte Bestellung nicht laden.</div>';
            }
        }

        // Render order details
        function renderOrderDetails(order) {
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = order.order_id;

            // Items list
            const itemsHtml = order.items.map(item => `
                <div class="item-row">
                    <div>
                        <div class="item-name">${escapeHtml(item.name)}</div>
                        <div class="item-qty">Menge: ${item.quantity || 1}</div>
                    </div>
                    <div class="item-price">${formatPrice(item.price * (item.quantity || 1))}</div>
                </div>
            `).join('');

            // Status history
            const historyHtml = order.status_history ? order.status_history.map(h => `
                <div class="history-item">
                    <div class="history-time">${h.changed_at_formatted}</div>
                    <div class="history-content">
                        <strong>${getStatusLabel(h.new_status)}</strong>
                        ${h.old_status ? `<span style="color:#64748b;">(von ${getStatusLabel(h.old_status)})</span>` : ''}
                        ${h.notes ? `<div style="font-size:0.875rem;color:#64748b;margin-top:0.25rem;">${escapeHtml(h.notes)}</div>` : ''}
                    </div>
                </div>
            `).join('') : '<p style="color:#64748b;">Kein Verlauf verfügbar.</p>';

            modalBody.innerHTML = `
                <div class="modal-section">
                    <h3>Kunde</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name</label>
                            <span>${escapeHtml(order.customer.name || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <label>E-Mail</label>
                            <span><a href="mailto:${escapeHtml(order.customer.email)}">${escapeHtml(order.customer.email)}</a></span>
                        </div>
                        ${order.customer.phone ? `
                            <div class="detail-item">
                                <label>Telefon</label>
                                <span>${escapeHtml(order.customer.phone)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <div class="modal-section">
                    <h3>Artikel</h3>
                    <div class="items-list">
                        ${itemsHtml}
                        <div class="item-row" style="background:#f8fafc;font-weight:600;">
                            <span>Gesamtbetrag</span>
                            <span>${formatPrice(order.amounts.total)}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <h3>Status ändern</h3>
                    <div class="status-actions">
                        <button class="btn btn-pending" onclick="updateStatus('${order.order_id}', 'pending')">Ausstehend</button>
                        <button class="btn btn-processing" onclick="updateStatus('${order.order_id}', 'processing')">In Bearbeitung</button>
                        <button class="btn btn-shipped" onclick="updateStatus('${order.order_id}', 'shipped')">Versendet</button>
                        <button class="btn btn-delivered" onclick="updateStatus('${order.order_id}', 'delivered')">Geliefert</button>
                        <button class="btn btn-cancelled" onclick="updateStatus('${order.order_id}', 'cancelled')">Stornieren</button>
                    </div>
                </div>

                <div class="modal-section">
                    <h3>Verlauf</h3>
                    <div class="status-history">
                        ${historyHtml}
                    </div>
                </div>
            `;
        }

        // Update order status
        async function updateStatus(orderId, newStatus) {
            if (!confirm(`Status zu "${getStatusLabel(newStatus)}" ändern?`)) return;

            try {
                const response = await fetch(`${API_URL}?id=${encodeURIComponent(orderId)}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });

                if (!response.ok) throw new Error('Failed to update status');

                // Refresh order details
                viewOrder(orderId);

                // Reload page after short delay to update table
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                alert('Konnte Status nicht aktualisieren.');
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        // Close on overlay click
        document.getElementById('orderModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Helpers
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function formatPrice(cents) {
            return (cents / 100).toFixed(2).replace('.', ',') + ' €';
        }

        function getStatusLabel(status) {
            const labels = {
                'pending': 'Ausstehend',
                'processing': 'In Bearbeitung',
                'shipped': 'Versendet',
                'delivered': 'Geliefert',
                'cancelled': 'Storniert'
            };
            return labels[status] || status;
        }
    </script>
</body>
</html>
