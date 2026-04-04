// ============================================================================
// 🔍 BUTTON DEBUGGER - LayerStore Ostern-Seite
// ============================================================================
// Kopiere dieses komplette Script in die Browser-Konsole auf:
// http://127.0.0.1:8888
// ============================================================================

(function() {
    console.clear();
    console.log('%c=== 🔍 BUTTON DEBUGGING START ===', 'color: #00ff00; font-size: 16px; font-weight: bold');

    const results = [];
    const errors = [];

    // Helper-Funktionen
    function logTest(name, status, message) {
        const statusSymbol = status === 'success' ? '✅' : status === 'error' ? '❌' : '⚠️';
        const statusColor = status === 'success' ? '#00ff00' : status === 'error' ? '#ff4444' : '#ffaa00';
        console.log(`%c${statusSymbol} ${name}`, `color: ${statusColor}`);
        if (message) {
            console.log(`   ${message}`);
        }
        results.push({ name, status, message });
    }

    function analyzeButton(btn, index) {
        const rect = btn.getBoundingClientRect();
        const styles = window.getComputedStyle(btn);
        const center = {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2
        };

        // Prüfe welches Element an der Position des Buttons ist
        const elementAtCenter = document.elementFromPoint(center.x, center.y);

        return {
            index,
            text: btn.textContent.trim(),
            dataProduct: btn.getAttribute('data-product'),
            onclick: btn.getAttribute('onclick'),
            visible: rect.width > 0 && rect.height > 0,
            inViewport: rect.top >= 0 && rect.left >= 0 &&
                       rect.bottom <= window.innerHeight &&
                       rect.right <= window.innerWidth,
            position: {
                top: rect.top,
                left: rect.left,
                width: rect.width,
                height: rect.height,
                centerX: center.x,
                centerY: center.y
            },
            styles: {
                display: styles.display,
                visibility: styles.visibility,
                opacity: styles.opacity,
                pointerEvents: styles.pointerEvents,
                zIndex: styles.zIndex
            },
            clickable: elementAtCenter === btn,
            elementAtCenter: elementAtCenter === btn ?
                'Button selbst' :
                `${elementAtCenter.tagName}${elementAtCenter.className ? '.' + elementAtCenter.className : ''}${elementAtCenter.id ? '#' + elementAtCenter.id : ''}`
        };
    }

    // ============================================================================
    // TEST 1: window.products Prüfung
    // ============================================================================
    console.log('\n%c📦 TEST 1: window.products Objekt', 'color: #00ffff; font-weight: bold');

    if (typeof window.products === 'undefined') {
        logTest('window.products existiert', 'error', 'window.products ist nicht definiert!');
        errors.push('window.products ist nicht definiert');
    } else {
        const productKeys = Object.keys(window.products);
        logTest('window.products existiert', 'success', `${productKeys.length} Produkte gefunden`);

        console.log('   Produkte:', productKeys);

        // Prüfe ob die Produktschlüssel aus den Buttons existieren
        const cartButtons = document.querySelectorAll('.add-to-cart-btn');
        const missingProducts = [];

        cartButtons.forEach(btn => {
            const productId = btn.getAttribute('data-product');
            if (!window.products[productId]) {
                missingProducts.push(productId);
            }
        });

        if (missingProducts.length > 0) {
            logTest('Produkt-Mapping', 'error', `Fehlende Produkte: ${missingProducts.join(', ')}`);
            errors.push(`Fehlende Produkte in window.products: ${missingProducts.join(', ')}`);
        } else {
            logTest('Produkt-Mapping', 'success', 'Alle Produktschlüssel existieren');
        }
    }

    // ============================================================================
    // TEST 2: Button-Existenz
    // ============================================================================
    console.log('\n%c🔘 TEST 2: Button-Existenz', 'color: #00ffff; font-weight: bold');

    const cartButtons = document.querySelectorAll('.add-to-cart-btn');
    const customizeButtons = document.querySelectorAll('.customize-btn');

    logTest('"In den Warenkorb" Buttons', cartButtons.length > 0 ? 'success' : 'error',
           `${cartButtons.length} Buttons gefunden`);
    logTest('"Anpassen" Buttons', customizeButtons.length > 0 ? 'success' : 'error',
           `${customizeButtons.length} Buttons gefunden`);

    // ============================================================================
    // TEST 3: Ersten "In den Warenkorb" Button analysieren
    // ============================================================================
    console.log('\n%c🔍 TEST 3: Erster "In den Warenkorb" Button Analyse', 'color: #00ffff; font-weight: bold');

    if (cartButtons.length > 0) {
        const firstButton = cartButtons[0];
        const analysis = analyzeButton(firstButton, 0);

        console.log('Button-Analyse:', analysis);

        if (!analysis.visible) {
            logTest('Button-Sichtbarkeit', 'error', 'Button ist nicht sichtbar (width/height = 0)');
            errors.push('Button ist nicht sichtbar');
        } else {
            logTest('Button-Sichtbarkeit', 'success', `Button ist sichtbar (${analysis.position.width}x${analysis.position.height}px)`);
        }

        if (!analysis.inViewport) {
            logTest('Button im Viewport', 'warning', 'Button ist nicht im sichtbaren Bereich');
        } else {
            logTest('Button im Viewport', 'success', 'Button ist im sichtbaren Bereich');
        }

        if (!analysis.clickable) {
            logTest('Button Klickbarkeit', 'error',
                   `Button wird überdeckt von: ${analysis.elementAtCenter}`);
            errors.push(`Button wird überdeckt von: ${analysis.elementAtCenter}`);
        } else {
            logTest('Button Klickbarkeit', 'success', 'Button ist klickbar (nicht überdeckt)');
        }

        if (analysis.styles.pointerEvents === 'none') {
            logTest('Pointer Events', 'error', 'pointer-events: none blockiert Klicks');
            errors.push('Button hat pointer-events: none');
        } else {
            logTest('Pointer Events', 'success', `pointer-events: ${analysis.styles.pointerEvents}`);
        }

        if (analysis.styles.opacity === '0') {
            logTest('Opacity', 'warning', 'Button hat opacity: 0 (unsichtbar aber klickbar)');
        } else {
            logTest('Opacity', 'success', `opacity: ${analysis.styles.opacity}`);
        }

    } else {
        logTest('Button-Analyse', 'error', 'Keine Buttons vorhanden zum Analysieren');
    }

    // ============================================================================
    // TEST 4: Auf überlagernde Elemente prüfen
    // ============================================================================
    console.log('\n%c🎯 TEST 4: Überlagernde Elemente', 'color: #00ffff; font-weight: bold');

    const overlays = [];
    const allElements = document.querySelectorAll('*');

    allElements.forEach(el => {
        const styles = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        const zIndex = parseInt(styles.zIndex);

        // Prüfe auf Elemente mit hohem z-index die überlagern könnten
        if (zIndex > 100 && rect.width > 100 && rect.height > 100) {
            overlays.push({
                tag: el.tagName,
                id: el.id,
                className: el.className,
                zIndex: zIndex,
                position: styles.position,
                display: styles.display,
                rect: {
                    top: Math.round(rect.top),
                    left: Math.round(rect.left),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            });
        }
    });

    if (overlays.length > 0) {
        logTest('Overlay-Elemente', 'warning', `${overlays.length} Elemente mit hohem z-index gefunden`);
        console.log('Overlays:', overlays);
    } else {
        logTest('Overlay-Elemente', 'success', 'Keine überlagernden Elemente gefunden');
    }

    // ============================================================================
    // TEST 5: JavaScript-Fehler abfangen
    // ============================================================================
    console.log('\n%c⚠️  TEST 5: JavaScript-Fehler Monitoring', 'color: #00ffff; font-weight: bold');

    let errorCaught = false;
    const errorHandler = (e) => {
        console.error('🚨 JavaScript Error:', e.message);
        console.error('   Stack:', e.stack);
        errorCaught = true;
        errors.push(`JavaScript Error: ${e.message}`);
    };

    window.addEventListener('error', errorHandler);

    // ============================================================================
    // TEST 6: Manuellen Klick simulieren
    // ============================================================================
    console.log('\n%c🖱️  TEST 6: Klick-Simulation', 'color: #00ffff; font-weight: bold');

    if (cartButtons.length > 0) {
        const firstButton = cartButtons[0];

        console.log('Simuliere Klick auf Button...');
        console.log('Button-Text vor Klick:', firstButton.textContent);

        try {
            // Prüfe ob onclick Handler existiert
            if (typeof firstButton.onclick === 'function') {
                logTest('onclick Handler', 'success', 'onclick Handler ist definiert');
            } else {
                logTest('onclick Handler', 'warning', 'Kein onclick Handler gefunden (onClick Attribut wird evaluiert)');
            }

            // Klick ausführen
            firstButton.click();

            logTest('Klick-Ausführung', 'success', 'Klick wurde ausgeführt (ohne Exception)');

            // Prüfe auf Änderungen nach dem Klick
            setTimeout(() => {
                console.log('Button-Text nach Klick:', firstButton.textContent);
                console.log('Button-Klassen nach Klick:', firstButton.className);

                // Prüfe auf Benachrichtigung
                const notification = document.querySelector('.cart-notification');
                if (notification) {
                    const isVisible = notification.offsetWidth > 0 && notification.offsetHeight > 0;
                    if (isVisible) {
                        logTest('Warenkorb-Benachrichtigung', 'success', 'Benachrichtigung wurde angezeigt');
                    } else {
                        logTest('Warenkorb-Benachrichtigung', 'warning', 'Benachrichtigung existiert aber ist nicht sichtbar');
                    }
                } else {
                    logTest('Warenkorb-Benachrichtigung', 'error', 'Keine Benachrichtigung im DOM gefunden');
                }

                finalSummary();
            }, 500);

        } catch (error) {
            logTest('Klick-Ausführung', 'error', `Fehler beim Klick: ${error.message}`);
            errors.push(`Fehler beim Klick: ${error.message}`);
            finalSummary();
        }
    } else {
        finalSummary();
    }

    function finalSummary() {
        console.log('\n%c=== 📊 ZUSAMMENFASSUNG ===', 'color: #00ff00; font-size: 16px; font-weight: bold');

        const successCount = results.filter(r => r.status === 'success').length;
        const errorCount = results.filter(r => r.status === 'error').length;
        const warningCount = results.filter(r => r.status === 'warning').length;

        console.log(`✅ Erfolgreich: ${successCount}`);
        console.log(`❌ Fehler: ${errorCount}`);
        console.log(`⚠️  Warnungen: ${warningCount}`);

        if (errors.length > 0) {
            console.log('\n%c❌ GEFUNDENE FEHLER:', 'color: #ff4444; font-weight: bold');
            errors.forEach((err, i) => {
                console.log(`   ${i + 1}. ${err}`);
            });
        } else {
            console.log('\n%c✅ Keine kritischen Fehler gefunden!', 'color: #00ff00; font-weight: bold');
        }

        console.log('\n%c💡 EMPFEHLUNG:', 'color: #00ffff; font-weight: bold');

        if (errors.some(e => e.includes('window.products'))) {
            console.log('   → Prüfe ob window.products korrekt geladen wird');
            console.log('   → Prüfe die Network Tab ob die Produktdaten geladen werden');
        }

        if (errors.some(e => e.includes('überdeckt'))) {
            console.log('   → Prüfe welches Element die Buttons überdeckt');
            console.log('   → Passe z-index Werte an');
        }

        if (errors.length === 0) {
            console.log('   → Die Buttons sollten funktionieren');
            console.log('   → Falls nicht, prüfe die Network Tab auf fehlende Ressourcen');
        }

        console.log('\n%c=== 🔍 DEBUGGING ENDE ===', 'color: #00ff00; font-size: 16px; font-weight: bold');
    }

})();
