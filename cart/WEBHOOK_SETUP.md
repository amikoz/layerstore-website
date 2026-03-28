# Stripe Webhook Setup für LayerStore

## Überblick
Dieser Guide zeigt dir, wie du Stripe Webhooks einrichtest, um automatische E-Mail-Benachrichtigungen bei erfolgreichen Bestellungen zu erhalten.

## Voraussetzungen
- Stripe Konto (Test oder Live)
- Zugriff auf Stripe Dashboard
- Öffenlicher Server mit HTTPS (ngrok für Tests)

## Schritt 1: Webhook-Endpunkt in Stripe erstellen

### Für Test-Modus:
1. Gehe zu [Stripe Dashboard](https://dashboard.stripe.com/test/webhooks)
2. Klicke auf "Add endpoint"
3. URL eintragen: `https://ed15-134-19-37-143.ngrok-free.app/stripe-webhook.php`
4. Events auswählen: `checkout.session.completed`
5. Klicke auf "Add endpoint"

### Für Produktions-Modus:
1. Gehe zu [Stripe Dashboard (Live)](https://dashboard.stripe.com/webhooks)
2. Klicke auf "Add endpoint"
3. URL eintragen: `https://layerstore.eu/cart/stripe-webhook.php`
4. Events auswählen: `checkout.session.completed`
5. Klicke auf "Add endpoint"

## Schritt 2: Webhook Secret konfigurieren

1. Nach dem Erstellen des Endpunkts, klicke auf den Endpoint
2. Scrolle nach unten zu "Signing secret"
3. Klicke auf "Reveal" um das Secret zu sehen
4. Kopiere das Secret (beginnt mit `whsec_...`)

### Secret in PHP Konfiguration speichern:

**Option A: Umgebungsvariable**
```bash
export STRIPE_WEBHOOK_SECRET="whsec_dein_secret_hier"
```

**Option B: In .env Datei**
```bash
STRIPE_WEBHOOK_SECRET=whsec_dein_secret_hier
```

## Schritt 3: Webhook-Handler testen

### Test-Webhook senden:
1. Gehe zu deinem Webhook-Endpunkt im Stripe Dashboard
2. Klicke auf "Send test webhook"
3. Wähle "checkout.session.completed" Event
4. Klicke auf "Send test webhook"

### Logs überprüfen:
```bash
# Webhook Logs ansehen
tail -f /Users/afdnrw/Library/Mobile\ Documents/com~apple~CloudDocs/layerstore/layerstore\ website/cart/webhook.log

# Email Logs ansehen
tail -f /Users/afdnrw/Library/Mobile\ Documents/com~apple~CloudDocs/layerstore/layerstore\ website/cart/email.log
```

## Schritt 4: E-Mail-Versand konfigurieren

Der Webhook-Handler verwendet die PHP `mail()` Funktion. Stelle sicher, dass:

1. **PHP Mail konfiguriert ist:**
   ```bash
   # php.ini überprüfen
   php -i | grep sendmail
   ```

2. **Postfix/SMTP läuft:**
   ```bash
   # Postfix Status prüfen
   sudo postfix status
   ```

3. **Falls Postfix nicht läuft:**
   ```bash
   # Postfix starten
   sudo postfix start

   # oder Postfix konfigurieren
   sudo postfix enable
   ```

## Schritt 5: Kompletten Test durchführen

1. **Testkauf durchführen:**
   - Gehe zu `http://127.0.0.1:8080/collections/schriftzuege/`
   - Füge ein Produkt zum Warenkorb hinzu
   - Klicke "Jetzt bezahlen"
   - Schließe die Testzahlung ab

2. **Bestätigung prüfen:**
   - Check deine E-Mail (info@layerstore.eu)
   - Überprüfe die Logs

## Schritt 6: Produktions-Setup

### Für Live-Modus:
1. **ngrok URL durch Live URL ersetzen:**
   - In Stripe Dashboard: Webhook Endpoint bearbeiten
   - URL ändern zu: `https://layerstore.eu/cart/stripe-webhook.php`

2. **Test-Keys durch Live-Keys ersetzen:**
   - `stripe-config.php` aktualisieren
   - `.env` Datei aktualisieren

3. **E-Mail-Adresse prüfen:**
   - Aktuelle Ziel-Adresse: `info@layerstore.eu`
   - In `stripe-webhook.php` Zeile 96 ändern

## Fehlerbehebung

### Keine E-Mail erhalten?
1. **Logs prüfen:**
   ```bash
   tail -20 /Users/afdnrw/Library/Mobile\ Documents/com~apple~CloudDocs/layerstore/layerstore\ website/cart/email.log
   ```

2. **PHP Mail prüfen:**
   ```php
   <?php
   // Test mail
   mail('info@layerstore.eu', 'Test', 'Test Nachricht');
   ?>
   ```

3. **Spam-Ordner prüfen:**
   - Manchmal landen E-Mails im Spam

### Webhook wird nicht empfangen?
1. **ngrok läuft?**
   ```bash
   lsof -i :8082
   ```

2. **URL erreichbar?**
   ```bash
   curl https://ed15-134-19-37-143.ngrok-free.app/stripe-webhook.php
   ```

3. **Stripe Dashboard Logs:**
   - Prüfe ob Fehler im Stripe Dashboard angezeigt werden

## Wichtige Dateien

- **`stripe-webhook.php`** - Webhook Handler (empfängt Stripe Events)
- **`create-checkout-session.php`** - Erstellt Stripe Checkout Sessions
- **`stripe-config.php`** - Stripe API Keys
- **`.env`** - Umgebungsvariablen
- **`webhook.log`** - Webhook Event Logs
- **`email.log`** - E-Mail Versand Logs

## Sicherheitshinweise

⚠️ **WICHTIG:**
- Log-Dateien enthalten sensible Informationen
- `.gitignore` schützt diese Dateien vor Git-Commits
- Webhook Secrets niemals teilen oder committen
- In Produktion HTTPS verwenden (obligatorisch)

## Nächste Schritte

1. ✅ Webhook-Endpunkt in Stripe erstellen
2. ✅ E-Mail-Versand testen
3. ✅ Kompletten Testkauf durchführen
4. ✅ Auf Produktion vorbereiten

Bei Problemen siehe Fehlerbehebung oder kontaktiere den Support.
