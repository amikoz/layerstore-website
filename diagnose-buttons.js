const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    slowMo: 500
  });

  const page = await browser.newPage();

  // Console logging abfangen
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    console.log(`[BROWSER ${type.toUpperCase()}]`, text);
  });

  // JavaScript Errors abfangen
  page.on('pageerror', error => {
    console.error('[BROWSER ERROR]', error.message);
  });

  console.log('Navigiere zu http://127.0.0.1:8888 ...');
  await page.goto('http://127.0.0.1:8888', {
    waitUntil: 'networkidle',
    timeout: 10000
  });

  console.log('\n=== DIAGNOSE 1: Console Prüfung ===');
  await page.waitForTimeout(2000);

  console.log('\n=== DIAGNOSE 2: window.products Prüfung ===');
  const productsCheck = await page.evaluate(() => {
    return {
      productsExists: typeof window.products !== 'undefined',
      productsKeys: window.products ? Object.keys(window.products) : [],
      productsCount: window.products ? Object.keys(window.products).length : 0
    };
  });
  console.log('window.products:', JSON.stringify(productsCheck, null, 2));

  console.log('\n=== DIAGNOSE 3: addToCart Prüfung ===');
  const addToCartCheck = await page.evaluate(() => {
    try {
      return {
        addToCartType: typeof addToCart,
        addToCartExists: typeof addToCart !== 'undefined',
        addToCartString: typeof addToCart !== 'undefined' ? String(addToCart).substring(0, 100) : 'NOT FOUND'
      };
    } catch (e) {
      return {
        addToCartType: 'ERROR',
        addToCartExists: false,
        error: e.message
      };
    }
  });
  console.log('addToCart:', JSON.stringify(addToCartCheck, null, 2));

  console.log('\n=== DIAGNOSE 4: Button-Status Prüfung ===');
  const buttonCheck = await page.evaluate(() => {
    const buttons = document.querySelectorAll('.add-to-cart-btn');
    return {
      totalButtons: buttons.length,
      disabledButtons: Array.from(buttons).filter(b => b.disabled).length,
      visibleButtons: Array.from(buttons).filter(b => {
        const rect = b.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
      }).length,
      sampleButtonHTML: buttons[0] ? buttons[0].outerHTML : 'NO BUTTONS'
    };
  });
  console.log('Buttons:', JSON.stringify(buttonCheck, null, 2));

  console.log('\n=== DIAGNOSE 5: Z-Index Prüfung ===');
  const zIndexCheck = await page.evaluate(() => {
    const button = document.querySelector('.add-to-cart-btn');
    if (!button) return { error: 'No button found' };

    const rect = button.getBoundingClientRect();
    const elementAtPosition = document.elementFromPoint(
      rect.left + rect.width / 2,
      rect.top + rect.height / 2
    );

    return {
      buttonZIndex: window.getComputedStyle(button).zIndex,
      buttonPosition: window.getComputedStyle(button).position,
      elementAtPosition: elementAtPosition ? elementAtPosition.tagName : 'null',
      elementAtPositionClass: elementAtPosition ? elementAtPosition.className : 'null',
      isButtonCovered: elementAtPosition !== button,
      buttonRect: rect
    };
  });
  console.log('Z-Index:', JSON.stringify(zIndexCheck, null, 2));

  console.log('\n=== DIAGNOSE 6: Button Klick Test ===');
  try {
    const firstButton = page.locator('.add-to-cart-btn').first();
    await firstButton.click();
    console.log('Button Klick erfolgreich!');

    await page.waitForTimeout(1000);

    // Prüfe ob Warenkorb sich geändert hat
    const cartCount = await page.evaluate(() => {
      const cartBadge = document.querySelector('.cart-count');
      return cartBadge ? cartBadge.textContent : 'NO BADGE';
    });
    console.log('Warenkorb Count:', cartCount);

  } catch (error) {
    console.error('Button Klick FEHLER:', error.message);
  }

  console.log('\n=== DIAGNOSE 7: Screenshot ===');
  await page.screenshot({
    path: '/Users/afdnrw/Projects/layerstore-website/diagnose-screenshot.png',
    fullPage: true
  });
  console.log('Screenshot gespeichert: /Users/afdnrw/Projects/layerstore-website/diagnose-screenshot.png');

  console.log('\nBrowser bleibt offen für manuelle Prüfung...');
  console.log('Drücke STRG+C zum Beenden.');

  // Halte Browser offen
  await new Promise(() => {});
})();
