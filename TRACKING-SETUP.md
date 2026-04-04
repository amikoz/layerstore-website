# Analytics & Conversion Tracking Setup Guide

LayerStore verwendet ein umfassendes Tracking-System mit Google Analytics 4, Meta Pixel und server-seitigem Tracking.

## Inhaltsverzeichnis

1. [Schnellstart](#schnellstart)
2. [Google Analytics 4](#google-analytics-4)
3. [Meta Pixel (Facebook)]#meta-pixel-facebook)
4. [Custom Analytics](#custom-analytics)
5. [Conversion Funnels](#conversion-funnels)
6. [E-Commerce Metrics](#e-commerce-metrics)
7. [Privacy/GDPR](#privacygdpr)
8. [API-Referenz](#api-referenz)

---

## Schnellstart

### 1. Skripte einbinden

Füge in allen HTML-Dateien (im `<head>` oder vor `</body>`) folgende Skripte ein:

```html
<!-- Analytics -->
<script src="/js/analytics.js" defer></script>
<script src="/js/meta-pixel.js" defer></script>
<script src="/js/cookie-consent.js" defer></script>
```

### 2. Konfiguration anpassen

**js/analytics.js:**
```javascript
const CONFIG = {
    GA4_MEASUREMENT_ID: 'G-XXXXXXXXXX', // Deine GA4 Measurement ID
    // ...
};
```

**js/meta-pixel.js:**
```javascript
const CONFIG = {
    PIXEL_ID: 'XXXXXXXXXXXXXXXX', // Deine Meta Pixel ID
    // ...
};
```

---

## Google Analytics 4

### Einrichtung

1. Gehe zu [Google Analytics](https://analytics.google.com/)
2. Erstelle ein neues Konto und eine Property (GA4)
3. Kopiere die Measurement ID (`G-XXXXXXXXXX`)
4. Trage sie in `js/analytics.js` ein

### Events

| Event | Beschreibung | Parameter |
|-------|-------------|-----------|
| `page_view` | Seitenaufruf | page_title, page_location |
| `view_item` | Produkt ansehen | item_id, item_name, price |
| `add_to_cart` | Zum Warenkorb hinzufügen | items, value |
| `view_cart` | Warenkorb ansehen | items, value |
| `begin_checkout` | Checkout starten | items, coupon |
| `purchase` | Kauf abgeschlossen | transaction_id, value, items |
| `search` | Suche | search_term |
| `share` | Teilen | content_type, item_id |

### Beispiel: Produktansicht tracken

```javascript
window.LayerStoreAnalytics.viewItem({
    id: 'osterkarotte',
    name: 'Osterkarotte',
    price: 13.00,
    category: 'Dekoration',
    subCategory: 'Ostern',
    variant: 'pink'
});
```

### Beispiel: Kauf tracken

```javascript
window.LayerStoreAnalytics.purchase({
    transactionId: 'order_12345',
    value: 45.90,
    tax: 7.34,
    shipping: 5.90,
    coupon: 'OSTER2025',
    items: [
        {
            id: 'osterkarotte',
            name: 'Osterkarotte',
            price: 13.00,
            quantity: 2
        }
    ]
});
```

---

## Meta Pixel (Facebook)

### Einrichtung

1. Gehe zu [Meta Business Suite](https://business.facebook.com/)
2. Erstelle einen neuen Pixel
3. Kopiere die Pixel ID
4. Trage sie in `js/meta-pixel.js` ein

### Standard Events

| Event | Beschreibung | Parameter |
|-------|-------------|-----------|
| `PageView` | Seitenaufruf | automatisch |
| `ViewContent` | Produkt ansehen | content_ids, value |
| `AddToCart` | Zum Warenkorb hinzufügen | content_ids, value |
| `InitiateCheckout` | Checkout starten | content_ids, num_items |
| `AddPaymentInfo` | Zahlungsinformation | content_ids |
| `Purchase` | Kauf abgeschlossen | content_ids, value |

### Beispiel: Checkout starten

```javascript
window.LayerStoreMetaPixel.initiateCheckout({
    items: cartItems,
    total: 45.90,
    promoCode: 'OSTER2025'
});
```

---

## Custom Analytics

### Server-Side Tracking

Die Datei `analytics/tracking.php` speichert Events serverseitig für zusätzliche Analysen.

```php
<?php
require_once 'analytics/tracking.php';

// Event tracken
trackEvent('custom_button_click', [
    'button_id' => 'cta-buy',
    'page' => '/collections/easter'
]);

// E-Commerce Event tracken
trackEcommerceEvent('product_view', [
    'product_id' => 'osterkarotte',
    'product_name' => 'Osterkarotte',
    'price' => 13.00
]);
?>
```

### API Endpunkte

| Endpunkt | Methode | Beschreibung |
|----------|---------|-------------|
| `/analytics/tracking.php?action=track` | POST | Event tracken |
| `/analytics/tracking.php?action=ecommerce` | POST | E-Commerce Event |
| `/analytics/tracking.php?action=getMetrics` | GET | KPIs abrufen |
| `/analytics/tracking.php?action=getFunnel` | GET | Conversion Funnel |

---

## Conversion Funnels

### Geführte Customer Journey

```
Produktansicht → Warenkorb → Checkout → Kauf
     ↓             ↓           ↓        ↓
   100%         ~15%        ~80%     ~60%
```

### Funnel Tracking

```javascript
// 1. Produktansicht
window.LayerStoreAnalytics.viewItem(product);

// 2. Zum Warenkorb
window.LayerStoreAnalytics.addToCart(product, quantity);

// 3. Warenkorb ansehen
window.LayerStoreAnalytics.viewCart(cartItems);

// 4. Checkout starten
window.LayerStoreAnalytics.beginCheckout(cartItems, promoCode);

// 5. Kauf
window.LayerStoreAnalytics.purchase(orderData);
```

### Conversion Rates berechnen

```php
<?php
require_once 'analytics/tracking.php';

$conversion = getConversionRate('30d');
/*
Return:
[
    'cart_rate' => 15.5,        // views → carts
    'checkout_rate' => 80.2,    // carts → checkout
    'purchase_rate' => 65.3,    // checkout → purchase
    'overall_rate' => 8.1       // views → purchases
]
*/
?>
```

---

## E-Commerce Metrics

### AOV (Average Order Value)

```javascript
// Server-seitig
const aov = getAverageOrderValue('30d');

// Erklärung: Durchschnittlicher Bestellwert
// Berechnung: Gesamtumsatz / Anzahl Bestellungen
```

### LTV (Lifetime Value) - Einfach

```javascript
// Basierend auf wiederkehrenden Kunden
function getSimpleLTV(customerId) {
    const orders = getCustomerOrders(customerId);
    const totalSpent = orders.reduce((sum, o) => sum + o.value, 0);
    return totalSpent;
}
```

### Cart Abandonment Rate

```php
<?php
$abandonmentRate = getCartAbandonmentRate('30d');
// Gibt die Prozentzahl der abgebrochenen Warenkörbe zurück
?>
```

### Top Produkte

```php
<?php
$topProducts = getTopProducts(10, 'purchases');
// Gibt die Top 10 Produkte nach Käufen zurück
?>
```

---

## Privacy/GDPR

### IP-Anonymisierung

In `js/analytics.js` ist die IP-Anonymisierung aktiviert:

```javascript
gtag('config', CONFIG.GA4_MEASUREMENT_ID, {
    anonymize_ip: true,  // IPs werden anonymisiert
    // ...
});
```

### Cookie Banner

Das Cookie Banner ist GDPR-konform mit folgenden Features:

- **Granulare Kontrolle**: Erforderlich, Analytics, Marketing
- **Opt-out Option**: Jederzeit änderbar
- **Kein Tracking ohne Consent**
- **LocalStorage für Consent**

### Consent Management

```javascript
// Prüfen ob Consent vorliegt
if (window.LayerStoreCookieConsent.hasConsent('analytics')) {
    // Analytics aktivieren
}

// Alle akzeptieren
window.LayerStoreCookieConsent.acceptAll();

// Nur erforderliche
window.LayerStoreCookieConsent.acceptEssential();

// Einstellungen ändern
window.LayerStoreCookieConsent.changeConsent();
```

---

## API-Referenz

### LayerStoreAnalytics

| Methode | Parameter | Beschreibung |
|---------|-----------|-------------|
| `init()` | - | Analytics initialisieren |
| `pageView(title, url)` | title, url | Seitenaufruf tracken |
| `viewItem(product)` | product object | Produktansicht |
| `addToCart(product, qty)` | product, quantity | Zum Warenkorb |
| `viewCart(cartItems)` | items array | Warenkorb ansehen |
| `beginCheckout(items, coupon)` | items, coupon | Checkout starten |
| `purchase(orderData)` | order object | Kauf tracken |
| `search(term, count)` | term, count | Suche tracken |
| `event(name, params)` | name, parameters | Custom Event |

### LayerStoreMetaPixel

| Methode | Parameter | Beschreibung |
|---------|-----------|-------------|
| `init()` | - | Pixel initialisieren |
| `viewContent(product)` | product object | Produkt ansehen |
| `addToCart(product, qty)` | product, quantity | Zum Warenkorb |
| `initiateCheckout(cart, code)` | cart, promoCode | Checkout starten |
| `purchase(orderData)` | order object | Kauf tracken |
| `lead(contentName)` | name | Lead generieren |
| `contact(method)` | method | Kontakt tracken |

### LayerStoreCookieConsent

| Methode | Parameter | Beschreibung |
|---------|-----------|-------------|
| `acceptAll()` | - | Alle Cookies akzeptieren |
| `acceptEssential()` | - | Nur erforderliche |
| `changeConsent()` | - | Einstellungen öffnen |
| `hasConsent(category)` | category | Prüft Consent |

---

## Dashboard

Das Analytics Dashboard ist unter `/admin/analytics.php` erreichbar.

### Features

- **KPI Übersicht**: Umsatz, Bestellungen, AOV, Conversion Rate
- **Conversion Funnel**: Visuelle Darstellung der Customer Journey
- **Top Produkte**: Nach verschiedenen Metriken sortiert
- **Umsatzverlauf**: Zeitlicher Verlauf der Umsätze
- **Event Log**: Liste der letzten Events

---

## Testing

### GA4 DebugView

1. Öffre [Google Analytics DebugView](https://analytics.google.com/debugview)
2. Aktiviere den Debug-Modus:

```javascript
// In js/analytics.js
CONFIG.DEBUG_MODE = true;
```

### Meta Pixel Helper

Installiere die [Meta Pixel Helper Extension](https://chrome.google.com/webstore/detail/meta-pixel-helper/) für Chrome.

### Server-Side Logging

```php
<?php
// Logging aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Events prüfen
$events = getEvents(['date_from' => '2024-01-01']);
print_r($events);
?>
```

---

## Dateien

| Datei | Beschreibung |
|-------|-------------|
| `js/analytics.js` | Google Analytics 4 Integration |
| `js/meta-pixel.js` | Meta Pixel Integration |
| `js/cookie-consent.js` | Cookie Consent Manager |
| `analytics/tracking.php` | Server-Side Tracking |
| `analytics/events.json` | Event Speicher |
| `analytics/funnel.json` | Conversion Funnel Daten |
| `analytics/products.json` | Produkt Statistiken |
| `analytics/revenue.json` | Umsatz Daten |
| `admin/analytics.php` | Analytics Dashboard |
| `includes/cookie-banner.html` | Cookie Banner HTML |

---

## Support

Bei Problemen oder Fragen kontaktiere:
- GitHub Issues: [layerstore-website Issues](https://github.com/layerstore/website/issues)
- Email: tech@layerstore.eu

---

*Version 1.0.0 - 2026*
