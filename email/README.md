# LayerStore Email Service

Modernes E-Mail-System fur LayerStore mit zwei Sendemethoden:

1. **Resend.com API** (empfohlen) - Moderne API mit hoher Deliverability
2. **PHP mail()** (Fallback) - Traditionelle Methode

## Features

### ResendEmailService (Neu)
- **Resend.com API** - Moderne E-Mail-Infrastruktur
- **Retry-Logik** - Automatische Wiederholungsversuche bei Fehlern
- **Exponential Backoff** - Intelligente Wartezeiten
- **Sandbox-Mode** - Testen ohne echte E-Mails
- **HTML + Text** - Multipart E-Mails
- **Anhange** - Dateianhange unterstutzt
- **Tags** - Tracking-Tags fur Analysen
- **CC/BCC** - Kopie-Empfanger

### TemplateRenderer (Bestehend)
- **Table-based Layouts** - Funktioniert in Outlook, Gmail, Apple Mail
- **Inline CSS** - Gmail-kompatibel
- **Plain Text Fallback** - Automatisch generiert
- **Responsive Design** - Fluid Layout, 600px max-width
- **LayerStore Branding** - Offizielle Farben (#232E3D, #ea580c, #FAF9F0)
- **Security First** - Automatisches Escaping
- **MSO Support** - Outlook-spezifische Kommentare

## Directory Structure

```
email/
├── config.php                 # Konfiguration & Environment-Variablen
├── ResendEmailService.php     # Resend.com API Integration
├── TemplateRenderer.php       # Template-Rendering System
├── test.php                   # Test-Skript fur Resend
├── templates/
│   ├── order-notification.html.php    # An Store-Besitzer
│   ├── customer-confirmation.html.php # An Kunden
│   └── payment-failed.html.php        # Zahlungsfehler
├── logs/
│   └── email.log             # E-Mail Logs (auto-created)
└── README.md                 # Diese Datei
```

## Installation

1. `.env` Datei erstellen:
   ```bash
   cp .env.example .env
   ```

2. Resend API Key konfigurieren:
   - Login bei https://resend.com
   - API Key erstellen
   - In `.env` einfugen:
     ```
     RESEND_API_KEY=re_xxxxxxxxxxxxx
     ```

3. Testen:
   ```bash
   php email/test.php
   ```

## Resend API Quick Start

### Einfache E-Mail senden

```php
require_once 'email/ResendEmailService.php';

use LayerStore\Email\ResendEmailService;

$email = new ResendEmailService(
    'empfaenger@example.com',
    'Betreff der E-Mail'
);

$email->html('<h1>Hallo Welt</h1><p>Dies ist eine Test-E-Mail.</p>');
$email->text('Hallo Welt\n\nDies ist eine Test-E-Mail.');

$result = $email->send();

if ($result['success']) {
    echo "E-Mail gesendet! ID: " . $result['id'];
} else {
    echo "Fehler: " . $result['message'];
}
```

### Mit Reply-To und Tags

```php
$email = new ResendEmailService('info@layerstore.eu', 'Neue Bestellung');
$email->html($htmlContent);
$email->replyTo($customerEmail);
$email->tag('order', 'new_order');
$email->tag('source', 'website');
$email->send();
```

### Quick Helper

```php
$result = ResendEmailService::quickSend(
    'to@example.com',
    'Betreff',
    '<h1>HTML</h1>',
    'Text'
);
```

## Konfiguration

Alle Werte konnen uber Environment-Variablen in `.env` gesetzt werden:

| Variable | Default | Beschreibung |
|----------|---------|--------------|
| `RESEND_API_KEY` | - | Resend.com API Key (notwendig) |
| `EMAIL_FROM` | `noreply@layerstore.eu` | Absender-Adresse |
| `EMAIL_FROM_NAME` | `LayerStore` | Absender-Name |
| `EMAIL_REPLY_TO` | `info@layerstore.eu` | Reply-To Adresse |
| `EMAIL_DEFAULT_RECIPIENT` | `info@layerstore.eu` | Standard-Empfanger |
| `EMAIL_SANDBOX` | `false` | Sandbox-Mode (keine echten Mails) |
| `EMAIL_LOG_FILE` | `/tmp/resend_email.log` | Log-Datei Pfad |
| `SITE_URL` | `https://layerstore.eu` | Basis-URL |

### Sandbox-Mode

Fur Tests ohne echte E-Mail-Versande:

```
EMAIL_SANDBOX=true
```

Die E-Mail wird validiert aber nicht wirklich gesendet.

## TemplateRenderer Quick Start

### Basic Usage

```php
require_once 'email/TemplateRenderer.php';

use LayerStore\Email\TemplateRenderer;

$renderer = new TemplateRenderer();

// Send order notification to store owner
$renderer->sendOrderNotification([
    'order_id' => 'LS-2024-001',
    'customer_name' => 'Max Mustermann',
    'customer_email' => 'max@example.com',
    'total_amount' => '49,90 €',
    'created' => '04.04.2026 14:30',
    'items' => [
        ['name' => 'Produkt A', 'price' => 2995, 'quantity' => 1],
        ['name' => 'Produkt B', 'price' => 1995, 'quantity' => 1]
    ],
    'payment_intent' => 'pi_3AbC123...',
    'stripe_url' => 'https://dashboard.stripe.com/payments/pi_3AbC123...'
]);

// Send confirmation to customer
$renderer->sendCustomerConfirmation([
    'customer_name' => 'Max Mustermann',
    'customer_email' => 'max@example.com',
    'order_id' => 'LS-2024-001',
    'total_amount' => '49,90 €'
]);

// Send payment failed notification
$renderer->sendPaymentFailed([
    'amount' => '99,99 €',
    'payment_intent_id' => 'pi_3XyZ789...',
    'error_message' => 'Card declined',
    'error_code' => 'card_declined',
    'customer_email' => 'max@example.com'
]);
```

### Stripe Webhook Integration

```php
// In your webhook handler
function handleCheckoutCompleted(array $session): void
{
    $renderer = new TemplateRenderer();

    $orderId = substr($session['id'], -8);
    $customerEmail = $session['customer_details']['email'] ?? '';
    $totalAmount = TemplateRenderer::formatCurrency($session['amount_total']);

    // Notify store owner
    $renderer->sendOrderNotification([
        'order_id' => $orderId,
        'customer_name' => $session['customer_details']['name'] ?? 'Kunde',
        'customer_email' => $customerEmail,
        'total_amount' => $totalAmount,
        'created' => TemplateRenderer::formatDate($session['created']),
        'items' => json_decode($session['metadata']['items'] ?? '[]', true),
        'payment_intent' => $session['payment_intent'] ?? '',
        'stripe_url' => "https://dashboard.stripe.com/payments/{$session['payment_intent']}"
    ]);

    // Confirm to customer
    if ($customerEmail) {
        $renderer->sendCustomerConfirmation([
            'customer_name' => $session['customer_details']['name'] ?? 'Kunde',
            'customer_email' => $customerEmail,
            'order_id' => $orderId,
            'total_amount' => $totalAmount
        ]);
    }
}
```

### Direct Template Rendering

```php
$renderer = new TemplateRenderer();

$result = $renderer->render('customer-confirmation', [
    'subject' => 'Custom Subject',
    'customer_name' => 'Max Mustermann',
    'order_id' => 'LS-001',
    'total_amount' => '49,90 €'
]);

// $result contains:
// ['html' => '...', 'text' => '...', 'subject' => 'Custom Subject']
```

## Template Variables

### Order Notification (`order-notification`)

| Variable | Type | Description |
|----------|------|-------------|
| `order_id` | string | Order identifier |
| `customer_name` | string | Customer's full name |
| `customer_email` | string | Customer's email address |
| `total_amount` | string | Formatted total (e.g., "49,90 €") |
| `created` | string | Order date/time |
| `items` | array | Array of items with `name`, `price` (in cents), `quantity` |
| `payment_intent` | string | Stripe Payment Intent ID |
| `stripe_url` | string | URL to Stripe dashboard |

### Customer Confirmation (`customer-confirmation`)

| Variable | Type | Description |
|----------|------|-------------|
| `customer_name` | string | Customer's full name |
| `customer_email` | string | Customer's email address (for `send()`) |
| `order_id` | string | Order identifier |
| `total_amount` | string | Formatted total |

### Payment Failed (`payment-failed`)

| Variable | Type | Description |
|----------|------|-------------|
| `amount` | string | Failed payment amount |
| `payment_intent_id` | string | Stripe Payment Intent ID |
| `error_message` | string | Error message from Stripe |
| `error_code` | string | Optional: Stripe error code |
| `customer_email` | string | Optional: Customer email |

## Helper Methods

```php
// Format currency (amount in cents)
TemplateRenderer::formatCurrency(4990);  // "49,90 €"

// Format date/timestamp
TemplateRenderer::formatDate(time());    // "04.04.2026 14:30"

// Escape HTML output
TemplateRenderer::e($userInput);
```

## Configuration

Edit `TemplateRenderer.php` constants to configure:

```php
// Brand Colors
public const COLOR_PRIMARY = '#232E3D';
public const COLOR_ACCENT = '#ea580c';
public const COLOR_BG = '#FAF9F0';

// Email Config
public const FROM_EMAIL = 'noreply@layerstore.eu';
public const FROM_NAME = 'LayerStore';
public const REPLY_TO = 'info@layerstore.eu';
public const STORE_URL = 'https://layerstore.eu';
```

## Testing

```bash
# Test all templates (saves output to test files)
php email/usage-examples.php test

# Preview a template in terminal
php email/usage-examples.php preview customer-confirmation
```

## Email Client Compatibility

Tested and working in:

- Gmail (web + mobile)
- Apple Mail (iOS + macOS)
- Outlook (Windows + Mac + Web)
- Thunderbird
- Windows Mail
- Samsung Mail
- Yahoo Mail
- ProtonMail

## Security

- All output is escaped using `htmlspecialchars()`
- Header injection prevention
- Timestamp verification for Stripe webhooks
- No user-supplied data in email headers (except recipient)

## Logging

Email delivery logs are automatically saved to `email/logs/email.log`:

```
[2026-04-04 14:30:00] SENT: Template=order-notification, To=info@layerstore.eu
[2026-04-04 14:30:01] SENT: Template=customer-confirmation, To=customer@example.com
```

## Customization

To create a new template:

1. Create `templates/your-template.html.php`
2. Use `TemplateRenderer::e()` for escaping
3. Use helper methods for formatting
4. Follow the table-based layout pattern
5. Include inline styles only

## Migration from Old Templates

Replace old email functions in `cart/stripe-webhook.php`:

```php
// OLD:
// sendOrderConfirmationEmail($session);
// sendCustomerConfirmationEmail($session);

// NEW:
require_once __DIR__ . '/../email/TemplateRenderer.php';
$renderer = new LayerStore\Email\TemplateRenderer();
// ... extract data ...
$renderer->sendOrderNotification($data);
$renderer->sendCustomerConfirmation($data);
```

## License

Proprietary - LayerStore © 2026
