# Stripe Webhook Setup für LayerStore

## 🎯 Ziel
Wenn ein Kunde bei LayerStore bestellt, erhält:
- **Shop-Besitzer** (info@layerstore.eu): E-Mail mit Bestelldetails
- **Kunde**: Bestätigungs-E-Mail

## 📋 Voraussetzungen

1. ✅ Resend API konfiguriert (.env vorhanden)
2. ✅ Domain verifiziert (layerstore.eu)
3. ⏳ Stripe Webhook eingerichtet (nächste Schritte)

---

## 🔧 Stripe Webhook einrichten

### 1. Stripe Dashboard öffnen
Gehe zu: https://dashboard.stripe.com/webhooks

### 2. Neuen Webhook erstellen
Klicke auf **"+ Add endpoint"**

### 3. Webhook URL eintragen
```
https://layerstore.eu/cart/stripe-webhook.php
```

> **Hinweis:** Wenn die Website noch nicht live ist, kannst du zum Testen:
> - `stripe listen --forward-to localhost:8000/cart/stripe-webhook.php` nutzen
> - Oder einen Tunnel wie ngrok/bore verwenden

### 4. Events auswählen
Wähle diese Events aus:

| Event | Beschreibung |
|-------|--------------|
| `checkout.session.completed` | Checkout erfolgreich abgeschlossen |
| `payment_intent.succeeded` | Zahlung erfolgreich |
| `payment_intent.payment_failed` | Zahlung fehlgeschlagen |

### 5. Webhook Secret kopieren
Nach dem Erstellen zeigt Stripe den **Signing Secret**:
```
whsec_abc123def456...
```
Kopiere dieses Secret!

### 6. Secret in .env eintragen
Öffne `.env` und trage das Secret ein:
```bash
STRIPE_WEBHOOK_SECRET=whsec_abc123def456...
```

### 7. Stripe Secret Key
Falls noch nicht vorhanden, auch den Secret Key eintragen:
```bash
STRIPE_SECRET_KEY=sk_test_...  # oder sk_live_... für Production
```

---

## 🧪 Testen

### Methode 1: Stripe CLI (empfohlen)
```bash
# Stripe CLI installieren
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Webhook an localhost forwarden
stripe listen --forward-to localhost:8000/cart/stripe-webhook.php

# In einem anderen Terminal: Test-Event senden
stripe trigger checkout.session.completed
```

### Methode 2: Stripe Dashboard
1. Gehe zu: https://dashboard.stripe.com/webhooks
2. Klicke auf deinen Webhook
3. Tab "Sending test"
4. Wähle `checkout.session.completed`
5. Klicke "Send test webhook"

### Methode 3: Echte Test-Zahlung
1. Gehe zu: https://buy.stripe.com/test_...
2. Führe eine Test-Zahlung durch
3. Prüfe Logs: `tail -f email.log`

---

## 📁 Dateien

| Datei | Zweck |
|-------|-------|
| `cart/stripe-webhook.php` | Webhook Handler (empfängt Stripe Events) |
| `email/ResendEmailService.php` | E-Mail Versand über Resend API |
| `email/config.php` | Konfiguration |
| `.env` | API Keys (NIEMALS committen!) |

---

## 🚀 Production Deployment

### Auf Server hochladen
```bash
# Alle Dateien hochladen (außer .env!)
scp -r cart/ email/ user@server:/path/to/layerstore-website/

# .env separat auf den Server kopieren
scp .env user@server:/path/to/layerstore-website/.env

# .env Datei auf dem Server schützen (nicht lesbar über Web!)
chmod 600 .env
```

### Live Stripe Webhook URL
```
https://layerstore.eu/cart/stripe-webhook.php
```

---

## 🔍 Troubleshooting

### E-Mail kommt nicht an?
1. **Logs prüfen:** `tail -f email.log`
2. **Resend Dashboard:** https://resend.com/dashboard/logs
3. **Stripe Logs:** https://dashboard.stripe.com/webhooks

### Webhook Error "Invalid signature"?
- `STRIPE_WEBHOOK_SECRET` in .env prüfen
- Secret muss exakt aus Stripe kopiert werden

### PHP Fehler?
- `error_log` auf Server prüfen
- PHP-Version: >= 8.0 benötigt

---

## ✅ Checkliste

- [ ] Stripe Webhook Endpoint erstellt
- [ ] Webhook URL korrekt eingetragen
- [ ] Events ausgewählt (checkout.session.completed, etc.)
- [ ] Webhook Secret in .env eingetragen
- [ ] Test-Event erfolgreich gesendet
- [ ] E-Mails erhalten (Shop & Kunde)
- [ ] Auf Server deployed

---

## 📞 Support

- **Stripe Docs:** https://stripe.com/docs/webhooks
- **Resend Docs:** https://resend.com/docs
- **Logs prüfen:** `tail -f email.log`
