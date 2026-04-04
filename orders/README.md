# Order Management System - LayerStore

Das Order Management System für LayerStore verwaltet alle Bestellungen, die über Stripe eingehen.

## Funktionsumfang

- **Automatische Bestellungsspeicherung** via Stripe Webhook
- **Admin Dashboard** zur Übersicht aller Bestellungen
- **Status-Management** (pending, processing, shipped, delivered, cancelled)
- **Filterung** nach Status, Datum und Suche
- **CSV-Export** für Excel/Buchhaltung
- **Statistiken** (Umsatz, Anzahl, Status-Verteilung)
- **REST API** für externe Integrationen

## Dateistruktur

```
layerstore-website/
├── orders/
│   ├── storage.php      # SQLite-basierter Speicher
│   ├── api.php          # REST API
│   └── README.md        # Diese Datei
├── admin/
│   └── orders.php       # Admin Dashboard
├── data/
│   └── orders.db        # SQLite Datenbank (auto-created)
└── cart/
    └── stripe-webhook.php # Aktualisiert mit Order-Speicherung
```

## Installation

1. **Umgebungsvariablen in `.env` setzen:**

```bash
# Stripe Konfiguration
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Orders API Key
ORDERS_API_KEY=dein-sicherer-api-key-hier
```

2. **Berechtigungen setzen:**

```bash
mkdir -p data
chmod 755 data
```

3. **Stripe Webhook konfigurieren:**

```
URL: https://layerstore.eu/cart/stripe-webhook.php
Events: checkout.session.completed, payment_intent.succeeded
```

## Nutzung

### Admin Dashboard

Öffne: `https://layerstore.eu/admin/orders.php`

**Features:**
- Bestellliste mit allen Details
- Filter nach Status, Datum, Suche
- Status ändern mit einem Klick
- Bestelldetails im Modal
- CSV-Export für Buchhaltung

### REST API

**Alle Bestellungen abrufen:**
```
GET /orders/api.php
Authorization: Bearer dein-api-key
```

**Einzelne Bestellung:**
```
GET /orders/api.php?id=LS-2024-ABC123
```

**Status ändern:**
```
PUT /orders/api.php?id=LS-2024-ABC123
Content-Type: application/json
Authorization: Bearer dein-api-key

{
  "status": "shipped",
  "notes": "Versendet mit DHL"
}
```

**Neue Bestellung erstellen:**
```
POST /orders/api.php
Content-Type: application/json

{
  "customer_email": "kunde@example.com",
  "customer_name": "Max Mustermann",
  "items": [
    {"name": "Produkt A", "price": 1999, "quantity": 1}
  ],
  "total": 1999
}
```

**Statistiken:**
```
GET /orders/api.php?stats=1&date_from=2024-01-01&date_to=2024-12-31
```

**CSV-Export:**
```
GET /orders/api.php?export=csv&status=delivered
```

## Status-System

| Status | Beschreibung | Farbe |
|--------|-------------|-------|
| `pending` | Ausstehend - Neu eingegangen | Orange |
| `processing` | In Bearbeitung | Blau |
| `shipped` | Versendet | Lila |
| `delivered` | Geliefert - Abgeschlossen | Grün |
| `cancelled` | Storniert | Rot |

## Datenbank-Schema

```sql
orders (
  id INTEGER PRIMARY KEY,
  order_id TEXT UNIQUE,
  stripe_session_id TEXT,
  customer_email TEXT,
  customer_name TEXT,
  items JSON,
  total INTEGER,  -- in Cents
  status TEXT,
  created_at INTEGER,
  updated_at INTEGER
)

order_status_history (
  id INTEGER PRIMARY KEY,
  order_id INTEGER,
  old_status TEXT,
  new_status TEXT,
  changed_at INTEGER,
  notes TEXT
)
```

## Sicherheit

- **API Authentication**: Bearer Token in `.env`
- **Webhook Signature Verification**: Stripe Signaturprüfung
- **Database Protection**: `.htaccess` verhindert direkten Datenbankzugriff
- **Input Validation**: Alle Eingaben werden validiert

## Branding

Das Dashboard nutzt die LayerStore Farben:
- **Primary**: #232E3D (Dunkles Blau)
- **Accent**: #ea580c (Orange)

## Support

Bei Problemen oder Fragen:
- E-Mail: info@layerstore.eu
- Stripe Dashboard: https://dashboard.stripe.com
