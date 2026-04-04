# Stripe Checkout Guide - LayerStore

Complete guide for implementing and managing Stripe Checkout for LayerStore.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Checkout Integration](#checkout-integration)
4. [Promo Codes](#promo-codes)
5. [Shipping Options](#shipping-options)
6. [Upsells](#upsells)
7. [Webhooks](#webhooks)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)

---

## Installation

### 1. Prerequisites

- Stripe Account (https://dashboard.stripe.com)
- PHP 8.0 or higher
- SSL Certificate (required for production)

### 2. File Setup

Place these files in your `/cart/` directory:

```
/cart/
├── create-checkout-session.php    # Checkout API endpoint
├── stripe-webhook.php              # Webhook handler
├── stripe.js                       # Frontend integration
├── stripe-config.php               # Configuration (NOT in git)
└── stripe-config.php.example       # Configuration template
```

### 3. Stripe Keys

Get your keys from Stripe Dashboard > Developers > API Keys:

- **Publishable Key** (pk_...): Used in frontend JavaScript
- **Secret Key** (sk_...): Used in backend PHP
- **Webhook Secret** (whsec_...): Used to verify webhooks

### 4. Configuration File

Create `stripe-config.php`:

```php
<?php
// Stripe Secret Key (NEVER commit to git)
return 'sk_test_your_secret_key_here';
```

For production, use environment variables:

```bash
# In .env file
STRIPE_SECRET_KEY=sk_live_your_production_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

---

## Configuration

### API Endpoint

The checkout session is created via POST request to:

```
POST /cart/create-checkout-session.php
Content-Type: application/json
```

### Request Body

```json
{
  "line_items": [
    {
      "price_data": {
        "currency": "eur",
        "product_data": {
          "name": "Osterkarotte",
          "description": "Handgefertigter 3D Druck",
          "images": ["https://..."]
        },
        "unit_amount": 1300,
        "tax_behavior": "exclusive"
      },
      "quantity": 1
    }
  ],
  "customer_email": "customer@example.com",
  "promo_code": "DISCOUNT10",
  "enable_promo_code": true,
  "collect_phone": true,
  "collect_address": false,
  "shipping_options": [...],
  "success_url": "https://.../success",
  "cancel_url": "https://.../cancel"
}
```

### Response

```json
{
  "success": true,
  "url": "https://checkout.stripe.com/...",
  "sessionId": "cs_test_...",
  "amountTotal": 1300,
  "currency": "eur"
}
```

---

## Checkout Integration

### Basic Implementation

```javascript
// Using the LayerStore Stripe helper
const stripeCheckout = window.layerStoreStripe;

// Prepare items
const items = [
    {
        name: 'Osterkarotte',
        price: 13.00,
        quantity: 1,
        description: 'Handgefertigter 3D Druck',
        images: ['/collections/easter/images/carrot1.jpeg']
    }
];

// Start checkout
try {
    await stripeCheckout.checkout(items, {
        customerEmail: 'customer@example.com',
        collectPhone: true,
        collectAddress: true
    });
} catch (error) {
    console.error('Checkout failed:', error);
}
```

### Advanced Options

```javascript
await stripeCheckout.checkout(items, {
    // Customer data
    customerEmail: 'customer@example.com',
    customerId: 'cus_...', // Existing Stripe customer
    clientReferenceId: 'order_12345',

    // Promo codes
    enablePromoCode: true,
    promoCode: 'EASTER2025',

    // Data collection
    collectPhone: true,
    collectAddress: true,

    // Shipping
    shippingOptions: [
        {
            name: 'Standardversand',
            amount: 495, // 4.95 EUR in cents
            min_unit: 'business_day',
            min_value: 3,
            max_unit: 'business_day',
            max_value: 7
        }
    ],

    // Upsells
    upsellItems: [
        {
            name: 'Geschenkverpackung',
            price: 2.95,
            quantity: 1,
            description: 'Upsell Produkt'
        }
    ],

    // Custom URLs
    successUrl: 'https://layerstore.eu/thank-you?session={CHECKOUT_SESSION_ID}',
    cancelUrl: 'https://layerstore.eu/cart?canceled=true',

    // Tax (requires Stripe Tax)
    enableTax: false,

    // Metadata
    metadata: {
        order_source: 'google',
        campaign: 'spring2025'
    }
});
```

### Buy Now Button

```javascript
// Direct checkout for single product
async function buyNow(productId) {
    const product = {
        id: productId,
        name: 'Osterkarotte',
        price: 13.00,
        images: ['/path/to/image.jpg']
    };

    try {
        await stripeCheckout.buyNow(product, {
            metadata: {
                checkout_type: 'buy_now'
            }
        });
    } catch (error) {
        // Show error to user
        showError(error.message);
    }
}
```

---

## Promo Codes

### Types of Promo Codes

#### 1. Client-Side Promo Codes (Local)

Managed in `stripe.js` and validated on frontend:

```javascript
const PROMO_CODES = {
    'TM26MG': { discount: 0.1, description: 'Trödelmarkt Metro Godorf' },
    'TY26KM': { discount: 0.1, description: 'Thank You card' },
    'LAYER10': { discount: 0.1, description: '10% Rabatt auf alles' },
    'OSTER2025': { discount: 0.15, description: 'Oster-Sonderaktion' }
};
```

#### 2. Stripe Native Promo Codes

Created in Stripe Dashboard:

1. Go to Products > Coupons
2. Create coupon with:
   - Percent or Fixed Amount
   - Duration (Once, Multi-month, Forever)
   - Restrictions (min/max amount, first-time)

3. Create Promotion Code for the coupon
4. Copy the promotion code to use in checkout

### Adding Client-Side Promo Codes

Edit `cart/stripe.js`:

```javascript
validatePromoCode(code) {
    const PROMO_CODES = {
        'YOURCODE': { discount: 0.15, description: 'Your description' }
        // Add more codes here
    };

    const upperCode = code.trim().toUpperCase();
    return PROMO_CODES[upperCode] || null;
}
```

### Using Promo Codes in Checkout

```javascript
// Enable promo code input in Stripe Checkout
await stripeCheckout.checkout(items, {
    enablePromoCode: true, // Shows promo code field in Stripe Checkout
    promoCode: 'PREDEFINED_CODE' // Pre-fill a code
});
```

### Promo Code Best Practices

1. **Code Format**: Use uppercase, short, memorable codes
2. **Expiry**: Set clear expiration dates
3. **Limits**: Restrict usage (once per customer, min/max order)
4. **Testing**: Test codes before going live

---

## Shipping Options

### Defining Shipping Options

```javascript
const shippingOptions = [
    {
        name: 'Standardversand',
        amount: 495, // 4.95 EUR in cents
        min_unit: 'business_day',
        min_value: 3,  // 3 business days
        max_unit: 'business_day',
        max_value: 7   // 7 business days
    },
    {
        name: 'Expressversand',
        amount: 995, // 9.95 EUR in cents
        min_unit: 'business_day',
        min_value: 1,
        max_unit: 'business_day',
        max_value: 2
    },
    {
        name: 'Abholung',
        amount: 0,   // Free
        min_unit: 'business_day',
        min_value: 1,
        max_unit: 'business_day',
        max_value: 3
    }
];
```

### Using Shipping Options

```javascript
await stripeCheckout.checkout(items, {
    shippingOptions: shippingOptions,
    collectAddress: true // Required for shipping
});
```

### Dynamic Shipping

Calculate shipping based on cart:

```javascript
function getShippingOptions(cart) {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    // Free shipping over 50 EUR
    if (total >= 50) {
        return [{
            name: 'Kostenloser Versand',
            amount: 0,
            min_unit: 'business_day',
            min_value: 3,
            max_unit: 'business_day',
            max_value: 7
        }];
    }

    // Standard shipping
    return [
        {
            name: 'Standardversand',
            amount: 495,
            min_unit: 'business_day',
            min_value: 3,
            max_unit: 'business_day',
            max_value: 7
        }
    ];
}
```

---

## Upsells

### Adding Upsell Products

```javascript
await stripeCheckout.checkout(mainItems, {
    upsellItems: [
        {
            name: 'Geschenkverpackung',
            description: 'Schöne Geschenkverpackung mit Karte',
            price: 2.95,
            quantity: 1,
            images: ['/images/gift-wrap.jpg'],
            metadata: {
                type: 'upsell',
                product_id: 'gift-wrap'
            }
        },
        {
            name: 'Gravur',
            description: 'Personalisierte Gravur (+2 Tage)',
            price: 5.00,
            quantity: 1
        }
    ]
});
```

### Conditional Upsells

```javascript
// Add gift wrapping for orders over 25 EUR
function getUpsells(cart) {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const upsells = [];

    if (total >= 25) {
        upsells.push({
            name: 'Geschenkverpackung',
            description: 'Perfekt für Geschenke',
            price: 2.95,
            quantity: 1
        });
    }

    // Always offer engraving
    upsells.push({
        name: 'Namensgravur',
        description: 'Personalisierte Gravur',
        price: 5.00,
        quantity: 1
    });

    return upsells;
}
```

---

## Webhooks

### Setting Up Webhooks

1. **In Stripe Dashboard**:
   - Go to Developers > Webhooks
   - Add endpoint: `https://layerstore.eu/cart/stripe-webhook.php`
   - Select events to listen for

2. **Required Events**:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

3. **Copy Webhook Secret**:
   - Click on endpoint > Signing secret
   - Copy to `.env` or `stripe-config.php`

### Webhook Events

#### checkout.session.completed

Triggered when customer completes checkout. Contains:
- Customer details (name, email, phone)
- Line items
- Shipping address
- Payment status
- Metadata

#### payment_intent.succeeded

Triggered when payment is successfully processed.

#### payment_intent.payment_failed

Triggered when payment fails (insufficient funds, declined).

### Customizing Webhook Emails

Edit `cart/stripe-webhook.php` to customize:

```php
function getOwnerEmailHtml(/* ... */) {
    // Customize email template
    return "<html>...</html>";
}
```

---

## Testing

### Local Testing

Use Stripe CLI for local webhooks:

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to localhost
stripe listen --forward-to localhost:8000/cart/stripe-webhook.php

# Get webhook secret for testing
stripe listen --print-secret
```

### Test Cards

Use Stripe test cards for testing:

| Card Number | Description |
|-------------|-------------|
| 4242 4242 4242 4242 | Success |
| 4000 0025 0000 3155 | Requires 3D Secure |
| 4000 0000 0000 9995 | Declined |
| 4000 0000 0000 0002 | Card declined (insufficient funds) |

### Running Test Suite

```bash
# CLI
php tests/stripe-checkout-test.php

# Browser
# Open https://layerstore.eu/tests/stripe-checkout-test.php
```

### Test Checklist

- [ ] Basic checkout flow
- [ ] Promo code application
- [ ] Shipping selection
- [ ] Upsell addition
- [ ] Payment success
- [ ] Payment cancellation
- [ ] Webhook delivery
- [ ] Email notifications
- [ ] Mobile checkout

---

## Troubleshooting

### Common Issues

#### 1. "Stripe not configured"

**Solution**: Check `stripe-config.php` or environment variable:
```bash
echo $STRIPE_SECRET_KEY
```

#### 2. Webhook signature verification fails

**Solution**: Ensure webhook secret matches in Stripe Dashboard:
```bash
# Check .env
cat .env | grep STRIPE_WEBHOOK_SECRET
```

#### 3. CORS errors

**Solution**: Ensure proper CORS headers in `create-checkout-session.php`:
```php
header('Access-Control-Allow-Origin: *');
```

#### 4. Promo codes not working

**Solution**: For Stripe native codes, ensure coupon is active and promotion code is enabled.

#### 5. Shipping not showing

**Solution**: Ensure `collect_address` is enabled:
```javascript
collectAddress: true  // Required for shipping
```

### Debug Mode

Enable detailed logging:

```php
// In create-checkout-session.php
error_log('Stripe Request: ' . print_r($postData, true));
error_log('Stripe Response: ' . $response);
```

### Getting Help

- Stripe Docs: https://stripe.com/docs/api
- Stripe Status: https://status.stripe.com/
- Support: https://support.stripe.com/

---

## Security Checklist

- [ ] Secret key never exposed in frontend
- [ ] `stripe-config.php` in `.gitignore`
- [ ] Webhook signature verification enabled
- [ ] HTTPS enforced in production
- [ ] Input validation on all parameters
- [ ] Rate limiting on checkout endpoint
- [ ] PCI compliance maintained

---

## Changelog

### v2.0.0 (2025-04-04)
- Added native Stripe promo code support
- Added shipping options
- Added customer data collection (phone, address)
- Added upsell products
- Enhanced error handling
- Added loading states
- Added test suite

### v1.0.0 (2025-03-31)
- Initial release
- Basic checkout functionality
- Webhook integration with email notifications
