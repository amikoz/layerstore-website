import { chromium } from 'playwright';

async function debugEasterButtons() {
    console.log('🔍 Starte Debugging der Ostern-Seite Buttons...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000 // Verlangsamung für bessere Beobachtung
    });

    const context = await browser.newContext();
    const page = await context.newPage();

    // Konsolen-Logs abfangen
    const consoleMessages = [];
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        consoleMessages.push({ type, text });
        console.log(`🖥️  [${type.toUpperCase()}] ${text}`);
    });

    // JavaScript-Fehler abfangen
    page.on('pageerror', error => {
        console.error('❌ JAVASCRIPT ERROR:', error.message);
        console.error('   Stack:', error.stack);
    });

    try {
        console.log('1. Öffne http://127.0.0.1:8888 ...\n');
        await page.goto('http://127.0.0.1:8888', { waitUntil: 'networkidle' });

        // Warte auf das Laden der Seite
        await page.waitForTimeout(2000);

        console.log('\n2. Prüfe window.products Objekt...');
        const productsData = await page.evaluate(() => {
            return {
                exists: typeof window.products !== 'undefined',
                keys: window.products ? Object.keys(window.products) : [],
                sampleProduct: window.products ? window.products['carrot'] : null
            };
        });

        console.log('   window.products existiert:', productsData.exists);
        console.log('   Anzahl Produkte:', productsData.keys.length);
        console.log('   Produkt-Keys:', productsData.keys);
        console.log('   Beispiel "carrot":', JSON.stringify(productsData.sampleProduct, null, 2));

        console.log('\n3. Prüfe Button-Elemente...');
        const buttonInfo = await page.evaluate(() => {
            const cartButtons = document.querySelectorAll('.add-to-cart-btn');
            const customizeButtons = document.querySelectorAll('.customize-btn');

            const getInfo = (btn, index) => {
                const rect = btn.getBoundingClientRect();
                const styles = window.getComputedStyle(btn);
                return {
                    index,
                    text: btn.textContent.trim(),
                    dataProduct: btn.getAttribute('data-product'),
                    onclick: btn.getAttribute('onclick'),
                    visible: rect.width > 0 && rect.height > 0,
                    position: {
                        top: rect.top,
                        left: rect.left,
                        width: rect.width,
                        height: rect.height
                    },
                    styles: {
                        display: styles.display,
                        visibility: styles.visibility,
                        opacity: styles.opacity,
                        pointerEvents: styles.pointerEvents,
                        zIndex: styles.zIndex
                    }
                };
            };

            return {
                cartButtons: Array.from(cartButtons).map(getInfo),
                customizeButtons: Array.from(customizeButtons).map(getInfo)
            };
        });

        console.log('   "In den Warenkorb" Buttons:', buttonInfo.cartButtons.length);
        console.log('   "Anpassen" Buttons:', buttonInfo.customizeButtons.length);

        console.log('\n4. Prüfe auf überlagernde Elemente (z-index overlays)...');
        const overlays = await page.evaluate(() => {
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
                        class: el.className,
                        zIndex: zIndex,
                        position: styles.position,
                        display: styles.display,
                        rect: {
                            top: rect.top,
                            left: rect.left,
                            width: rect.width,
                            height: rect.height
                        }
                    });
                }
            });

            return overlays;
        });

        if (overlays.length > 0) {
            console.log('   ⚠️  Gefundene Overlay-Elemente:');
            overlays.forEach(ov => {
                console.log(`      ${ov.tag}${ov.id ? '#' + ov.id : ''}.${ov.class} - z-index: ${ov.zIndex}`);
            });
        } else {
            console.log('   ✅ Keine überlagernden Elemente gefunden');
        }

        console.log('\n5. Teste Klick auf ersten "In den Warenkorb" Button...');
        const firstCartButton = page.locator('.add-to-cart-btn').first();

        // Prüfe ob Button klickbar ist
        const isClickable = await firstCartButton.isEnabled();
        console.log('   Button ist klickbar:', isClickable);

        if (isClickable) {
            // Klick ausführen
            await firstCartButton.click();
            console.log('   ✅ Klick ausgeführt');

            // Warte und prüfe auf Reaktion
            await page.waitForTimeout(1000);

            // Prüfe Benachrichtigung
            const notification = await page.locator('.cart-notification').isVisible();
            console.log('   Benachrichtigung angezeigt:', notification);

            // Prüfe Button-Status nach Klick
            const buttonStatus = await page.evaluate(() => {
                const btn = document.querySelector('.add-to-cart-btn');
                return {
                    text: btn.textContent,
                    class: btn.className,
                    added: btn.classList.contains('added')
                };
            });
            console.log('   Button Status nach Klick:', buttonStatus);
        } else {
            console.log('   ❌ Button ist nicht klickbar!');
        }

        console.log('\n6. Teste Klick auf "Anpassen" Button...');
        const firstCustomizeButton = page.locator('.customize-btn').first();
        const isCustomizeClickable = await firstCustomizeButton.isEnabled();
        console.log('   "Anpassen" Button ist klickbar:', isCustomizeClickable);

        if (isCustomizeClickable) {
            await firstCustomizeButton.click();
            console.log('   ✅ Klick auf "Anpassen" ausgeführt');

            await page.waitForTimeout(1000);

            // Prüfe ob Modal geöffnet
            const modalVisible = await page.locator('.customize-modal').isVisible();
            console.log('   Customizing Modal geöffnet:', modalVisible);
        } else {
            console.log('   ❌ "Anpassen" Button ist nicht klickbar!');
        }

        console.log('\n7. Zusammenfassung der Konsolen-Fehler:');
        const errors = consoleMessages.filter(m => m.type === 'error');
        if (errors.length > 0) {
            console.log(`   ⚠️  ${errors.length} Fehler gefunden:`);
            errors.forEach(err => console.log(`      - ${err.text}`));
        } else {
            console.log('   ✅ Keine JavaScript-Fehler');
        }

        console.log('\n8. Finaler Screenshot...');
        await page.screenshot({ path: '/Users/afdnrw/Projects/layerstore-website/debug-screenshot.png', fullPage: true });
        console.log('   ✅ Screenshot gespeichert: debug-screenshot.png');

    } catch (error) {
        console.error('\n❌ FEHLER während des Debuggings:', error.message);
        console.error('Stack:', error.stack);
    } finally {
        console.log('\n⏳ Warte 5 Sekunden vor dem Schließen...');
        await page.waitForTimeout(5000);
        await browser.close();
        console.log('✅ Debugging abgeschlossen');
    }
}

debugEasterButtons().catch(console.error);
