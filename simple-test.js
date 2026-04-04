const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    slowMo: 1000
  });

  const page = await browser.newPage();

  console.log('Navigiere zu http://127.0.0.1:8888/collections/easter/ ...');
  await page.goto('http://127.0.0.1:8888/collections/easter/', {
    waitUntil: 'networkidle',
    timeout: 10000
  });

  console.log('Warte 2 Sekunden...');
  await page.waitForTimeout(2000);

  console.log('Prüfe addToCart Funktion...');
  const addToCartExists = await page.evaluate(() => {
    return typeof addToCart !== 'undefined';
  });
  console.log('addToCart existiert:', addToCartExists);

  console.log('Klicke auf ersten "In den Warenkorb" Button...');
  try {
    await page.locator('.add-to-cart-btn').first().click();
    console.log('Button Klick erfolgreich!');

    await page.waitForTimeout(2000);

    const cartCount = await page.evaluate(() => {
      const cartBadge = document.querySelector('.cart-count');
      return cartBadge ? cartBadge.textContent : 'NO BADGE';
    });
    console.log('Warenkorb Count:', cartCount);

  } catch (error) {
    console.error('Button Klick FEHLER:', error.message);
  }

  console.log('Screenshot speichern...');
  await page.screenshot({
    path: '/Users/afdnrw/Projects/layerstore-website/button-test-result.png'
  });

  console.log('Drücke Enter zum Schließen...');
  await page.waitForTimeout(10000);
  await browser.close();
})();
