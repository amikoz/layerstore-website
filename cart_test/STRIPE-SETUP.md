# Stripe Webhook Setup für LayerStore

## Übersicht

Das LayerStore verwendet Stripe Webhooks, um E-Mail-Benachrichtigungen zu senden, wenn:
- ✅ Eine Bestellung erfolgreich bezahlt wurde (`checkout.session.completed`)
- ✅ Eine Zahlung erfolgreich war (`payment_intent.succeeded`)
- ❌ Eine Zahlung fehlgeschlagen ist (`payment_intent.payment_failed`)

## Schritt 1: Stripe Webhook konfigurieren

### 1. Stripe Dashboard öffnen

Gehe zu: https://dashboard.stripe.com/webhooks

### 2. Neuen Webhook erstellen

1. Klicke auf **"+ Add endpoint"**
2. **Webhook URL:** `https://layerstore.eu/cart/stripe-webhook.php`
3. **Events zu hören:** Wähle folgende Events:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

4. Klicke auf **"Add endpoint"**

### 3. Webhook Secret kopieren

Nach dem Erstellen erhältst du ein **Webhook Secret** (whsec_...):
1. Klicke auf den neuen Webhook-Endpunkt
2. Scrolle zu **"Signing secret"**
3. Klicke auf **"Reveal"** und kopiere das Secret

## Schritt 2: Webhook Secret konfigurieren

### Option A: Umgebungsvariable (empfohlen)

Füge die Umgebungsvariable zur Server-Konfiguration hinzu:

```bash
# In deiner .htaccess oder Server-Konfiguration
SetEnv STRIPE_WEBHOOK_SECRET whsec_dein_secret_hier
```

### Option B: In stripe-config.php

Erstelle/aktualisiere `cart/stripe-config.php`:

```php
<?php
return 'whsec_dein_webhook_secret_hier';
```

## Schritt 3: E-Mail-Empfänger konfigurieren

Die E-Mails werden an folgende Adresse gesendet:
- **Standard:** `info@layerstore.eu`

Um die E-Mail zu ändern, editiere in `stripe-webhook.php`:
- Zeile 119: `$toEmail = 'deine@email.de';`
- Zeile 173: `$toEmail = 'deine@email.de';`

## Schritt 4: Testen

### Test mit Stripe CLI

```bash
# Stripe CLI installieren
stripe login

# Webhook an localhost weiterleiten (für Tests)
stripe listen --forward-to localhost:8888/cart/stripe-webhook.php

# Test-Event senden
stripe trigger checkout.session.completed
```

### Test im Dashboard

1. Gehe zu **Webhooks** im Stripe Dashboard
2. Klicke auf deinen Webhook-Endpunkt
3. Klicke auf **"Send test webhook"**
4. Wähle `checkout.session.completed`
5. Prüfe die Logs in `cart/email.log` und `cart/webhook.log`

## Schritt 5: Log-Dateien überwachen

```bash
# Webhook-Events ansehen
tail -f cart/webhook.log

# E-Mail-Status ansehen
tail -f cart/email.log
```

## E-Mail-Vorlagen

### Für Shop-Betreiber (info@layerstore.eu)
- **Betreff:** `✅ Neue Bestellung bei LayerStore - #XXXXXXXX`
- **Enthält:** Kunde, Betrag, Artikel, Stripe-Link

### Für Kunden
- **Betreff:** `Deine Bestellung bei LayerStore ist erfolgreich! ✅`
- **Enthält:** Bestellnummer, Betrag, Danke-Nachricht

## Fehlersuche

### Keine E-Mails erhalten?

1. **PHP mail() prüfen:**
   ```bash
   php -r "mail('test@example.com', 'Test', 'Test');"
   ```

2. **Log-Dateien prüfen:**
   ```bash
   cat cart/email.log
   cat cart/webhook.log
   ```

3. **Webhook-Status im Stripe Dashboard:**
   - Zeigt der Webhook "Success" oder "Failed"?
   - Welchen HTTP-Code返回 der Server?

### Webhook返回 400 oder 500?

- **Signatur-Problem:** Überprüfe das Webhook Secret
- **PHP-Fehler:** Prüfe die PHP-Error-Logs
- **Datei-Rechte:** Prüfe ob der Webserver in `cart/` schreiben darf

## Features

| Event | Aktion |
|-------|--------|
| `checkout.session.completed` | E-Mail an Shop + Kunde |
| `payment_intent.succeeded` | E-Mail an Shop |
| `payment_intent.payment_failed` | E-Mail an Shop (Fehler) |

## Sicherheit

- ✅ Webhook-Signatur-Verifikation (Stripe-Secret)
- ✅ Timestamp-Toleranz-Prüfung (5 Minuten)
- ✅ CORS-Konfiguration
- ✅ Nur POST-Anfragen akzeptiert
