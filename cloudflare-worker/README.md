# Cloudflare Worker - Stripe Webhook mit E-Mail

Ein serverless Stripe Webhook Handler auf Cloudflare Workers mit E-Mail-Versand über Mailgun oder SendGrid.

## 🚀 Vorteile

- ✅ **Kein Server nötig** - Läuft auf Cloudflare Infrastruktur
- ✅ **Kostenlos** - Bis zu 100.000 Requests/Tag
- ✅ **Schnell** - Weltweite CDN-Verteilung
- ✅ **Skalierbar** - Automatische Skalierung

## 📋 Voraussetzungen

1. **Cloudflare Account** (kostenlos)
2. **Wrangler CLI** installiert:
   ```bash
   npm install -g wrangler
   ```
3. **E-Mail Service** Account:
   - [Mailgun](https://www.mailgun.com/) (Kostenlos: 1000 E-Mails/Monat)
   - ODER [SendGrid](https://sendgrid.com/) (Kostenlos: 100 E-Mails/Tag)

## 🔧 Setup

### 1. Cloudflare Worker deployen

```bash
cd ~/Projects/layerstore-website/cloudflare-worker

# Login bei Cloudflare
wrangler login

# Secrets konfigurieren (wird verschlüsselt gespeichert)
wrangler secret put STRIPE_WEBHOOK_SECRET
wrangler secret put MAILGUN_API_KEY
wrangler secret put SENDGRID_API_KEY
wrangler secret put FROM_EMAIL
wrangler secret put TO_EMAIL
```

### 2. Worker deployen

```bash
wrangler deploy
```

Du erhältst eine URL wie:
```
https://layerstore-stripe-webhook.YOUR_ACCOUNT.workers.dev
```

### 3. Stripe Webhook konfigurieren

1. Öffne: https://dashboard.stripe.com/webhooks
2. **"+ Add endpoint"**
3. **Endpoint URL:** Deine Cloudflare Worker URL
4. **Events:**
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
5. **Webhook Secret** kopieren

### 4. Secret in Worker eintragen

```bash
wrangler secret put STRIPE_WEBHOOK_SECRET
# Füge das whsec_... Secret ein
```

### 5. Neu deployen

```bash
wrangler deploy
```

## 📧 E-Mail Service einrichten

### Mailgun (empfohlen)

1. Registriere: https://www.mailgun.com/
2. API Key kopieren
3. Verifizierte Domain hinzufügen (oder mg. Subdomain nutzen)

```bash
wrangler secret put MAILGUN_API_KEY
wrangler secret put MAILGUN_DOMAIN
```

### SendGrid

1. Registriere: https://sendgrid.com/
2. API Key kopieren
3. Sender Email verifizieren

```bash
wrangler secret put SENDGRID_API_KEY
```

## 🎯 Custom Domain (optional)

```bash
# In wrangler.toml entkommentieren:
# routes = [
#   { pattern = "webhook.layerstore.eu/*", zone_name = "layerstore.eu" }
# ]

# DNS Eintrag bei Cloudflare hinzufügen:
# webhook.layerstore.eu → CNAME → YOUR_WORKER.workers.dev
```

## 🧪 Testen

```bash
# Test-Event senden
stripe trigger checkout.session.completed

# Logs ansehen
wrangler tail
```

## 📊 Monitoring

In Cloudflare Dashboard:
- Workers → LayerStore Webhook
- Requests, Errors, Latency
- Real-time Logs

## 🔄 Vergleich: PHP vs Cloudflare Worker

| Feature | PHP (Server) | Cloudflare Worker |
|---------|---------------|-------------------|
| Hosting | Benötigt Server | Serverless |
| Skalierung | Manuell | Automatisch |
| Kosten | Serverkosten | Kostenlos (bis 100k/day) |
| Wartung | Updates nötig | Keine Wartung |
| Verfügbarkeit | Server downtime | 99.99%+ uptime |
| PHP mail() | Benötigt Mail-Server | API (Mailgun/SendGrid) |

## 📝 Beispiel Secrets

```bash
# Stripe
STRIPE_WEBHOOK_SECRET = whsec_abc123...

# Mailgun
MAILGUN_API_KEY = key-abc123...
MAILGUN_DOMAIN = mg.layerstore.eu

# SendGrid (alternative)
SENDGRID_API_KEY = SG.abc123...

# E-Mails
FROM_EMAIL = noreply@layerstore.eu
TO_EMAIL = info@layerstore.eu
```

## 🔒 Sicherheit

- ✅ Stripe Signature Verification
- ✅ Timestamp Tolerance Check (5 Min)
- ✅ Secrets verschlüsselt in Cloudflare
- ✅ HTTPS强制
- ✅ Rate Limiting (durch Cloudflare)
