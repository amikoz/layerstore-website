# Warenkorb UX - LayerStore

## Übersicht

Verbessertes Warenkorbsystem für LayerStore mit LocalStorage-Persistenz, Mini Cart Overlay und Gast-Checkout ohne Registrierung.

## Features

### 1. LocalStorage Warenkorb

Der Warenkorb wird im Browser gespeichert und bleibt über Seitenaufrufe hinweg erhalten.

**Funktionen:**
- Cart persistieren über Sessions hinweg (30 Tage)
- Add/Remove/Update Quantity
- Cart Badge im Header mit Animation
- Automatische Bereinigung alter Carts

**Module:** `/js/cart-storage.js`

```javascript
// Warenkorb holen
const cart = window.cartStorage.getCart();

// Artikel hinzufügen
window.cartStorage.addItem({
    id: 'osterkarotte',
    name: 'Osterkarotte',
    price: '13,00 €',
    color: 'pink',
    quantity: 1
});

// Artikel entfernen
window.cartStorage.removeItem(index);

// Menge aktualisieren
window.cartStorage.updateQuantity(index, 5);
```

### 2. Cart Badge im Header

Animiertes Badge mit Artikelanzahl im Header.

```html
<div class="cart-icon" id="cartIcon">
    <svg>...</svg>
    <span class="cart-count" id="cartCount">0</span>
</div>
```

### 3. Mini Cart / Slide-out Cart

Overlay-Warenkorb beim Klick auf Cart Icon.

**Features:**
- Slide-out Animation von rechts
- Quick View der Produkte
- Direktes Ändern der Mengen
- Quick-Add für ähnliche Produkte
- Mobile optimiert

**Module:** `/js/minicart.js`

```javascript
// Mini Cart öffnen
window.miniCart.open();

// Mini Cart schließen
window.miniCart.close();

// Mini Cart aktualisieren
window.miniCart.update();
```

### 4. Gast-Checkout

Keine Registrierung erforderlich - Bestellung mit nur einer E-Mail oder über WhatsApp.

**Features:**
- Optional: Name, E-Mail, Telefon
- Datenschutz-Einwilligung
- WhatsApp-Integration
- E-Mail-Integration
- Daten werden für nächsten Besuch gespeichert

**Module:** `/js/checkout-guest.js`

```javascript
// Checkout öffnen
window.guestCheckout.open();

// Über WhatsApp senden
window.guestCheckout.submitWhatsApp('4915259821293');

// Über E-Mail senden
window.guestCheckout.submitEmail('info@layerstore.eu');
```

### 5. UX Improvements

#### Toast Notifications
```javascript
window.toast.show('Erfolg', 'Artikel hinzugefügt', 'success');
```

**Typen:** `success`, `error`, `warning`, `info`

#### Loading Spinner
```javascript
const spinner = window.loadingSpinner.show(container, 'Laden...');
window.loadingSpinner.hide(spinner);
```

#### Empty State
Angepasster Empty State mit CTA zur Kollektion.

### 6. Cross-Selling

**Ähnliche Produkte**
- Basierend auf Tags und Kategorie
- Wird im Warenkorb angezeigt

**Vergiss nicht...**
- Produkte die zur aktuellen Auswahl passen

**Häufig zusammen gekauft**
- Vorkonfigurierte Bundles

**Module:** `/js/cross-sell.js`

## Integration

### Alle Module laden

```html
<!-- Load Cart Modules -->
<script src="/js/cart-storage.js"></script>
<script src="/js/cart-ui.js"></script>
<script src="/js/checkout-guest.js"></script>
<script src="/js/cross-sell.js"></script>
```

### Bundle verwenden (empfohlen)

```html
<script src="/js/cart-bundle.js" data-auto-init="true"></script>
```

### Artikel zum Warenkorb hinzufügen

```javascript
// Auf Produktseiten
document.querySelector('.add-to-cart-btn').addEventListener('click', () => {
    window.LayerStoreCart.add({
        id: 'product-id',
        name: 'Produktname',
        price: '19,99 €',
        color: selectedColor,
        option: 'as-photo',
        quantity: 1,
        image: '/path/to/image.jpg'
    });
});
```

## API Reference

### CartStorage

```javascript
// Methoden
window.cartStorage.getCart()                    // Cart als Array
window.cartStorage.saveCart(items)              // Cart speichern
window.cartStorage.addItem(item)                // Artikel hinzufügen
window.cartStorage.updateQuantity(index, qty)   // Menge ändern
window.cartStorage.removeItem(index)            // Artikel entfernen
window.cartStorage.clearCart()                  // Warenkorb leeren
window.cartStorage.getItemCount(cart)           // Gesamtanzahl
window.cartStorage.calculateTotal(cart)         // Berechnung
```

### LayerStoreCart (Global)

```javascript
window.LayerStoreCart.add(item)     // Zum Warenkorb hinzufügen
window.LayerStoreCart.remove(index) // Entfernen
window.LayerStoreCart.update(index, qty) // Menge ändern
window.LayerStoreCart.get()         // Cart holen
window.LayerStoreCart.clear()       // Leeren
window.LayerStoreCart.count()       // Anzahl
window.LayerStoreCart.open()        // Mini Cart öffnen
window.LayerStoreCart.close()       // Mini Cart schließen
window.LayerStoreCart.checkout()    // Checkout starten
```

## Dateistruktur

```
/js/
├── cart-storage.js       # LocalStorage Management
├── cart-ui.js            # UI Komponenten (Toast, Spinner, Badge)
├── minicart.js           # Slide-out Cart Overlay
├── checkout-guest.js     # Gast-Checkout Logik
├── cross-sell.js         # Cross-Selling Engine
└── cart-bundle.js        # EntryPoint (lädt alle Module)

/cart/
├── index.html            # Warenkorb-Seite
├── index-v2.html         # Neue Version mit allen Features
├── cart.js               # Legacy (kann entfernt werden)
└── stripe.js             # Stripe Integration
```

## Events

```javascript
// Cart aktualisiert
window.addEventListener('cartUpdated', (e) => {
    console.log('Cart count:', e.detail.count);
});

// Cart geleert
window.addEventListener('cartCleared', () => {
    // Cart wurde geleert
});

// Checkout abgeschlossen
window.addEventListener('checkoutComplete', () => {
    // Bestellung gesendet
});

// Modules ready
window.addEventListener('cartReady', () => {
    // Alle Module geladen
});
```

## Promo-Codes

Promo-Codes werden in `checkout-guest.js` konfiguriert:

```javascript
const PROMO_CODES = {
    'TM26MG': 0.1,  // 10% Rabatt
    'TY26KM': 0.1   // 10% Rabatt
};
```

## Cookie Consent

Nur erforderlicher LocalStorage wird verwendet. Optionaler Cookie Banner:

```javascript
window.cartStorage.hasCookieConsent()    // Status prüfen
window.cartStorage.acceptCookieConsent() // Akzeptieren
```

## Mobile Optimierung

- Responsive Design für alle Screen-Größen
- Touch-optimierte Buttons
- Slide-out Cart auf Mobile Full-Screen
- Optimierte Ladezeiten

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile Browser (iOS Safari, Chrome Mobile)

## Datenschutz

- Keine Tracking-Cookies
- LocalStorage nur für Warenkorb
- Cookie Consent Banner
- GDPR-konform
- Daten bleiben auf dem Gerät des Nutzers

## Upgrade Guide

### Von Legacy zu neuem System

1. Neue Module in `index.html` einbinden
2. `cart.js` kann entfernt werden
3. Globale Funktionen prüfen:
   - `getCart()` → `window.cartStorage.getCart()`
   - `saveCart()` → `window.cartStorage.saveCart()`

### Minimale Integration

```html
<script src="/js/cart-bundle.js"></script>

<script>
document.querySelector('.add-to-cart').addEventListener('click', () => {
    window.LayerStoreCart.add({...});
});
</script>
```

## Testing

### Warenkorb testen

```javascript
// Console
window.LayerStoreCart.add({id: 'test', name: 'Test', price: '10,00 €', quantity: 1});
window.LayerStoreCart.get();
window.LayerStoreCart.count();
```

### LocalStorage prüfen

```javascript
// DevTools Console
localStorage.getItem('layerstore_cart')
JSON.parse(localStorage.getItem('layerstore_cart'))
```

## Bekannte Issues

- Safari Private Mode: LocalStorage kann throwen - try/catch implementiert
- Browser ohne LocalStorage: Fallback zu Memory

## Roadmap

- [ ] Stripe Checkout Integration (erstellt, muss aktiviert werden)
- [ ] Versandkosten-Berechnung
- [ ] Mehrere Versandadressen
- [ ] Wunschliste
- [ ] Produktvergleiche
- [ ] Lagerbestands-Anzeige

## Support

Bei Problemen oder Fragen:
- GitHub Issues
- E-Mail: tech@layerstore.eu

---

**Version:** 2.0.0
**Letztes Update:** April 2026
**Status:** Production Ready
