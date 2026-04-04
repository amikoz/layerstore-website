/**
 * LayerStore Meta Pixel (Facebook Pixel)
 * GDPR-konform mit Consent Management
 */

(function(window, document) {
    'use strict';

    // Konfiguration
    const CONFIG = {
        PIXEL_ID: 'XXXXXXXXXXXXXXXX', // Durch echte Pixel ID ersetzen
        DEBUG_MODE: false,  // In Produktion auf false setzen
        AUTO_CONFIG: true,  // Automatic Advanced Matching
        DELAY_LOAD: 2000,   // ms Verzögerung für Performance
    };

    // Consent Status
    let consentGranted = false;

    /**
     * Logging für Debug-Modus
     */
    function log(message, data) {
        if (CONFIG.DEBUG_MODE && window.console) {
            console.log('[MetaPixel]', message, data || '');
        }
    }

    /**
     * Prüft ob Pixel aktiviert werden darf
     */
    function hasConsent() {
        return consentGranted || localStorage.getItem('layerstore_pixel_consent') === 'granted';
    }

    /**
     * Meta Pixel initialisieren
     */
    function initPixel() {
        if (!hasConsent()) {
            log('Pixel nicht initialisiert - kein Consent');
            return;
        }

        // fbq Funktion initialisieren
        window.fbq = window.fbq || function() {
            (window.fbq.q = window.fbq.q || []).push(arguments);
        };

        // Pixel laden
        (function() {
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://connect.facebook.net/en_US/fbevents.js';
            document.head.appendChild(script);
        })();

        // Pixel initialisieren
        fbq('init', CONFIG.PIXEL_ID);

        // Automatic Advanced Matching (optional)
        if (CONFIG.AUTO_CONFIG) {
            fbq('init', CONFIG.PIXEL_ID, getAdvancedMatchingData());
        }

        // PageView tracken
        fbq('track', 'PageView');

        log('Meta Pixel initialisiert', CONFIG.PIXEL_ID);
    }

    /**
     * Advanced Matching Daten (wenn verfügbar)
     */
    function getAdvancedMatchingData() {
        const data = {};
        const userEmail = localStorage.getItem('user_email');
        const userName = localStorage.getItem('user_name');
        const userPhone = localStorage.getItem('user_phone');

        if (userEmail) data.em = userEmail;
        if (userName) data.fn = userName.split(' ')[0]; // First Name
        if (userName) data.ln = userName.split(' ').slice(-1)[0]; // Last Name
        if (userPhone) data.ph = userPhone;

        return Object.keys(data).length > 0 ? data : null;
    }

    /**
     * Standard Event: ViewContent (Produkt ansehen)
     */
    function trackViewContent(product) {
        if (!hasConsent()) return;

        const contentName = product.name || 'Produkt';
        const contentIds = [product.id || product.sku || ''];
        const contentCategory = product.category || 'Dekoration';
        const value = parseFloat(product.price) || 0;
        const currency = product.currency || 'EUR';

        fbq('track', 'ViewContent', {
            content_name: contentName,
            content_ids: contentIds,
            content_type: 'product',
            content_category: contentCategory,
            value: value,
            currency: currency
        });

        log('ViewContent tracked', { contentName, value, currency });
    }

    /**
     * Standard Event: Search (Suche)
     */
    function trackSearch(searchString, contentIds = []) {
        if (!hasConsent()) return;

        fbq('track', 'Search', {
            search_string: searchString,
            content_ids: contentIds
        });

        log('Search tracked', { searchString, resultCount: contentIds.length });
    }

    /**
     * Standard Event: AddToCart (Zum Warenkorb hinzufügen)
     */
    function trackAddToCart(product, quantity = 1) {
        if (!hasConsent()) return;

        const contentName = product.name || 'Produkt';
        const contentIds = [product.id || product.sku || ''];
        const contentCategory = product.category || 'Dekoration';
        const value = (parseFloat(product.price) || 0) * quantity;
        const currency = product.currency || 'EUR';

        fbq('track', 'AddToCart', {
            content_name: contentName,
            content_ids: contentIds,
            content_type: 'product',
            content_category: contentCategory,
            value: value,
            currency: currency
        });

        log('AddToCart tracked', { contentName, value, currency, quantity });
    }

    /**
     * Standard Event: AddToWishlist (Zur Wunschliste hinzufügen)
     */
    function trackAddToWishlist(product) {
        if (!hasConsent()) return;

        const contentName = product.name || 'Produkt';
        const contentIds = [product.id || product.sku || ''];
        const contentCategory = product.category || 'Dekoration';
        const value = parseFloat(product.price) || 0;
        const currency = product.currency || 'EUR';

        fbq('track', 'AddToWishlist', {
            content_name: contentName,
            content_ids: contentIds,
            content_type: 'product',
            content_category: contentCategory,
            value: value,
            currency: currency
        });

        log('AddToWishlist tracked', { contentName, value, currency });
    }

    /**
     * Standard Event: InitiateCheckout (Checkout starten)
     */
    function trackInitiateCheckout(cartData, promoCode = '') {
        if (!hasConsent()) return;

        const contentIds = cartData.items.map(item => item.id || item.sku).filter(Boolean);
        const numItems = cartData.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const value = parseFloat(cartData.total) || 0;
        const currency = cartData.currency || 'EUR';

        fbq('track', 'InitiateCheckout', {
            content_ids: contentIds,
            content_type: 'product',
            num_items: numItems,
            value: value,
            currency: currency
        });

        if (promoCode) {
            log('InitiateCheckout tracked', { value, currency, numItems, promoCode });
        } else {
            log('InitiateCheckout tracked', { value, currency, numItems });
        }
    }

    /**
     * Standard Event: AddPaymentInfo (Zahlungsinformation hinzugefügt)
     */
    function trackAddPaymentInfo(cartData, paymentMethod = '') {
        if (!hasConsent()) return;

        const contentIds = cartData.items.map(item => item.id || item.sku).filter(Boolean);
        const value = parseFloat(cartData.total) || 0;
        const currency = cartData.currency || 'EUR';

        fbq('track', 'AddPaymentInfo', {
            content_ids: contentIds,
            content_type: 'product',
            value: value,
            currency: currency
        });

        log('AddPaymentInfo tracked', { value, currency, paymentMethod });
    }

    /**
     * Standard Event: Purchase (Kauf abgeschlossen)
     */
    function trackPurchase(orderData) {
        if (!hasConsent()) return;

        const contentIds = orderData.items.map(item => item.id || item.sku).filter(Boolean);
        const numItems = orderData.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const value = parseFloat(orderData.value) || 0;
        const currency = orderData.currency || 'EUR';

        fbq('track', 'Purchase', {
            content_ids: contentIds,
            content_type: 'product',
            num_items: numItems,
            value: value,
            currency: currency,
            transaction_id: orderData.transactionId || ''
        });

        log('Purchase tracked', { value, currency, numItems, transactionId: orderData.transactionId });
    }

    /**
     * Standard Event: Lead (Lead generiert)
     */
    function trackLead(contentName = 'Anfrage') {
        if (!hasConsent()) return;

        fbq('track', 'Lead', {
            content_name: contentName
        });

        log('Lead tracked', { contentName });
    }

    /**
     * Standard Event: CompleteRegistration (Registrierung abgeschlossen)
     */
    function trackCompleteRegistration(method = 'Email') {
        if (!hasConsent()) return;

        fbq('track', 'CompleteRegistration', {
            content_name: 'Registrierung',
            status: 'completed',
            method: method
        });

        log('CompleteRegistration tracked', { method });
    }

    /**
     * Standard Event: Contact (Kontakt aufgenommen)
     */
    function trackContact(method = 'WhatsApp') {
        if (!hasConsent()) return;

        fbq('track', 'Contact', {
            content_name: 'Kontakt',
            method: method
        });

        log('Contact tracked', { method });
    }

    /**
     * Standard Event: CustomizeProduct (Produkt angepasst)
     */
    function trackCustomizeProduct(product) {
        if (!hasConsent()) return;

        fbq('track', 'CustomizeProduct', {
            content_name: product.name || 'Produkt',
            content_ids: [product.id || product.sku || '']
        });

        log('CustomizeProduct tracked', product);
    }

    /**
     * Custom Event (benutzerdefiniertes Event)
     */
    function trackCustomEvent(eventName, parameters = {}) {
        if (!hasConsent()) return;

        fbq('trackCustom', eventName, parameters);
        log('Custom event tracked', { eventName, parameters });
    }

    /**
     * Microdata Event (für strukturierte Daten)
     */
    function trackMicrodata() {
        if (!hasConsent()) return;

        // Warten bis DOM geladen ist
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', executeMicrodata);
        } else {
            executeMicrodata();
        }

        function executeMicrodata() {
            const products = document.querySelectorAll('[itemtype*="Product"]');
            products.forEach(product => {
                const name = product.querySelector('[itemprop="name"]')?.textContent;
                const price = product.querySelector('[itemprop="price"]')?.textContent;
                const sku = product.querySelector('[itemprop="sku"]')?.textContent;

                if (name) {
                    trackViewContent({
                        name: name,
                        price: price,
                        id: sku
                    });
                }
            });
        }
    }

    /**
     * Konsens erteilen
     */
    function grantConsent() {
        consentGranted = true;
        localStorage.setItem('layerstore_pixel_consent', 'granted');

        // Pixel nachträglich initialisieren
        if (window.fbq) {
            fbq('consent', 'grant');
            log('Pixel consent granted (fbq already loaded)');
        } else {
            initPixel();
        }
    }

    /**
     * Konsent entziehen
     */
    function denyConsent() {
        consentGranted = false;
        localStorage.removeItem('layerstore_pixel_consent');

        if (window.fbq) {
            fbq('consent', 'revoke');
            log('Pixel consent denied');
        }
    }

    /**
     * Warenkorb-Änderungen tracken
     */
    function trackCartChanges() {
        // Cart add/remove events abhören
        const originalSetItem = localStorage.setItem;
        localStorage.setItem = function(key, value) {
            originalSetItem.call(this, key, value);

            if (key === 'layerstore_cart') {
                try {
                    const cart = JSON.parse(value);
                    if (hasConsent() && cart.length > 0) {
                        trackInitiateCheckout({
                            items: cart,
                            total: cart.reduce((sum, item) => {
                                const price = parseFloat(item.price?.replace(',', '.')?.replace(/[^\d.]/g, '') || 0);
                                return sum + (price * item.quantity);
                            }, 0)
                        });
                    }
                } catch (e) {
                    // Ignore parsing errors
                }
            }
        };
    }

    // API exportieren
    const LayerStoreMetaPixel = {
        init: initPixel,
        viewContent: trackViewContent,
        search: trackSearch,
        addToCart: trackAddToCart,
        addToWishlist: trackAddToWishlist,
        initiateCheckout: trackInitiateCheckout,
        addPaymentInfo: trackAddPaymentInfo,
        purchase: trackPurchase,
        lead: trackLead,
        completeRegistration: trackCompleteRegistration,
        contact: trackContact,
        customizeProduct: trackCustomizeProduct,
        customEvent: trackCustomEvent,
        microdata: trackMicrodata,
        grantConsent: grantConsent,
        denyConsent: denyConsent,
        hasConsent: hasConsent,
        config: CONFIG
    };

    // Global verfügbar machen
    window.LayerStoreMetaPixel = LayerStoreMetaPixel;

    // Pixel beim Laden initialisieren (wenn Consent vorhanden)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (hasConsent()) {
                initPixel();
            }
        });
    } else {
        if (hasConsent()) {
            initPixel();
        }
    }

    // Cart-Tracking aktivieren
    trackCartChanges();

})(window, document);
