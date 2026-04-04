# 🔍 Debugging-Anleitung: Ostern-Buttons

## Problem
Die Buttons "In den Warenkorb" und "Anpassen" auf http://127.0.0.1:8888 funktionieren nicht.

## Lösungswege

### Methode 1: Browser-Konsole (Empfohlen)

1. Öffne http://127.0.0.1:8888 in deinem Browser
2. Öffne die Entwickler-Tools:
   - **Mac**: `Cmd + Option + I`
   - **Windows/Linux**: `F12` oder `Ctrl + Shift + I`
3. Gehe zum Tab "Konsole"
4. Kopiere das komplette Script aus `debug-console-script.js`
5. Füge es in die Konsole ein und drücke `Enter`

Das Script wird automatisch folgende Tests durchführen:
- ✅ Prüfen ob `window.products` geladen ist
- ✅ Prüfen ob alle Buttons existieren
- ✅ Prüfen ob Buttons sichtbar sind
- ✅ Prüfen ob Buttons überdeckt werden
- ✅ Prüfen ob Buttons klickbar sind
- ✅ Einen Klick simulieren
- ✅ Prüfen ob die Benachrichtigung erscheint

### Methode 2: HTML-Debugging-Tool

1. Öffne `debug-easter-buttons.html` in deinem Browser
2. Folge den Anweisungen auf der Seite

### Methode 3: Manuelles Debugging

Wenn du manuell debuggen willst, öffne die Konsole auf http://127.0.0.1:8888 und führe nacheinander aus:

```javascript
// 1. Prüfe window.products
console.log('Products:', window.products);

// 2. Prüfe Buttons
console.log('Cart Buttons:', document.querySelectorAll('.add-to-cart-btn'));
console.log('Customize Buttons:', document.querySelectorAll('.customize-btn'));

// 3. Prüfe ersten Button
const btn = document.querySelector('.add-to-cart-btn');
console.log('Button:', btn);
console.log('Visible:', btn.offsetWidth > 0 && btn.offsetHeight > 0);

// 4. Prüfe was den Button überdeckt
const rect = btn.getBoundingClientRect();
const elem = document.elementFromPoint(rect.left + rect.width/2, rect.top + rect.height/2);
console.log('Element at button:', elem);

// 5. Teste Klick
btn.click();
```

## Häufige Probleme und Lösungen

### Problem 1: `window.products` ist undefined

**Ursache:** Die Produktdaten werden nicht geladen.

**Lösung:**
- Prüfe die Network Tab ob die Daten geladen werden
- Prüfe ob es JavaScript-Fehler gibt
- Prüfe ob das Script mit den Produktdaten ausgeführt wird

### Problem 2: Buttons werden überdeckt

**Ursache:** Ein Element mit höherem z-index überdeckt die Buttons.

**Lösung:**
- Passe den z-index der Buttons an
- Entferne oder verstecke das überlagernde Element
- Passe die Positionierung an

### Problem 3: Buttons sind nicht sichtbar

**Ursache:** CSS-Problem (display: none, opacity: 0, etc.)

**Lösung:**
- Prüfe die CSS-Regeln für `.add-to-cart-btn` und `.customize-btn`
- Prüfe ob responsive Styles die Buttons verstecken

### Problem 4: Klick wird nicht registriert

**Ursache:** onclick Handler ist nicht definiert oder fehlerhaft.

**Lösung:**
- Prüfe ob die Funktionen `addToCart()` und `openCustomizeModal()` existieren
- Prüfe ob JavaScript-Fehler in der Konsole auftreten

## Dateien

- `/Users/afdnrw/Projects/layerstore-website/collections/easter/index.html` - Die Ostern-Seite
- `/Users/afdnrw/Projects/layerstore-website/debug-console-script.js` - Automatisches Debugging-Script
- `/Users/afdnrw/Projects/layerstore-website/debug-easter-buttons.html` - HTML-Debugging-Tool

## Nächste Schritte

1. Führe das Debugging-Script aus
2. Notiere die Ergebnisse und Fehler
3. Melde die Ergebnisse mir zur weiteren Analyse
