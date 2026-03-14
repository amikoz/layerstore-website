# 📘 IONOS API für Stripe

## Auf IONOS hochladen

Diese Dateien müssen auf deinen IONOS Webspace in den `api/` Ordner:

```
httpdocs/
└── api/
    └── create-checkout.php
```

## Schritte

1. **IONOS Dateimanager öffnen**
   - https://hosting.ionos.de → Login
   - Webspace-Explorer / Dateimanager

2. **Ordner erstellen**
   - Klicke auf "Neuer Ordner"
   - Name: `api`

3. **Datei hochladen**
   - In den `api/` Ordner wechseln
   - `create-checkout.php` hochladen

## Testen

Öffne im Browser:
```
https://layerstore.eu/api/create-checkout.php
```

Erwartete Antwort:
```json
{"error":"Method not allowed"}
```

## Fertig!

Jetzt kannst du auf GitHub Pages bezahlen:
1. https://amikoz.github.io/layerstore-website/cart_test/
2. Produkte hinzufügen
3. "💳 Jetzt bezahlen" klicken
4. API URL ist bereits korrekt: `https://layerstore.eu/api`
