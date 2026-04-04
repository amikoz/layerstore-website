/**
 * LayerStore Analytics Manager
 * Google Analytics 4 Integration
 * GDPR-konform mit IP-Anonymisierung und Consent Management
 */

(function(window, document) {
    'use strict';

    // Konfiguration
    const CONFIG = {
        GA4_MEASUREMENT_ID: 'G-XXXXXXXXXX', // Durch echte ID ersetzen
        GA4_TRACKING_ID: 'UA-XXXXXXXXX-X',  // Fallback für Universal Analytics
        DEBUG_MODE: false,  // In Produktion auf false setzen
        ANONYMIZE_IP: true,  // IP-Adressen anonymisieren (DSGVO)
        SEND_PAGE_VIEW: true,
        TRANSPORT_TYPE: 'beacon', // beacon, xhr, image
        SESSION_TIMEOUT: 30, // Minuten
        COOKIE_EXPIRY: 400, // Tage (max 400 für GA4)
        COOKIE_DOMAIN: 'auto',
        COOKIE_FLAGS: 'samesite=none;secure'
    };

    // Consent Status
    let consentGranted = false;

    /**
     * Logging für Debug-Modus
     */
    function log(message, data) {
        if (CONFIG.DEBUG_MODE && window.console) {
            console.log('[Analytics]', message, data || '');
        }
    }

    /**
     * Prüft ob Analytics aktiviert werden darf
     */
    function hasConsent() {
        return consentGranted || localStorage.getItem('layerstore_analytics_consent') === 'granted';
    }

    /**
     * GA4 initialisieren
     */
    function initGA4() {
        if (!hasConsent()) {
            log('GA4 nicht initialisiert - kein Consent');
            return;
        }

        // gtag.js laden
        (function(id, src) {
            const script = document.createElement('script');
            script.async = true;
            script.src = src;
            document.head.appendChild(script);
        })(CONFIG.GA4_MEASUREMENT_ID, 'https://www.googletagmanager.com/gtag/js?id=' + CONFIG.GA4_MEASUREMENT_ID);

        // gtag Funktion initialisieren
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function() {
            dataLayer.push(arguments);
        };

        // GA4 konfigurieren
        gtag('js', new Date());

        gtag('config', CONFIG.GA4_MEASUREMENT_ID, {
            send_page_view: CONFIG.SEND_PAGE_VIEW,
            transport_type: CONFIG.TRANSPORT_TYPE,
            anonymize_ip: CONFIG.ANONYMIZE_IP,
            cookie_domain: CONFIG.COOKIE_DOMAIN,
            cookie_expires: CONFIG.COOKIE_EXPIRY * 86400, // Sekunden
            cookie_flags: CONFIG.COOKIE_FLAGS,
            page_title: document.title,
            page_location: window.location.href,
            custom_map: {
                'custom_parameter_1': 'product_category',
                'custom_parameter_2': 'product_color',
                'custom_parameter_3': 'collection_type'
            }
        });

        log('GA4 initialisiert', CONFIG.GA4_MEASUREMENT_ID);
    }

    /**
     * Pageview Tracking
     */
    function trackPageView(title, url) {
        if (!hasConsent()) return;

        const pageInfo = {
            page_title: title || document.title,
            page_location: url || window.location.href,
            page_path: url ? new URL(url).pathname : window.location.pathname
        };

        gtag('event', 'page_view', pageInfo);
        log('Pageview tracked', pageInfo);
    }

    /**
     * Produkte im E-Commerce Format
     */
    function formatProduct(product) {
        return {
            item_id: product.id,
            item_name: product.name,
            affiliation: 'LayerStore',
            coupon: product.promoCode || '',
            currency: 'EUR',
            discount: product.discount || 0,
            index: product.index || 0,
            item_brand: 'LayerStore',
            item_category: product.category || 'Dekoration',
            item_category2: product.subCategory || '',
            item_category3: product.collection || '',
            item_category4: product.season || '',
            item_category5: product.material || 'PLA',
            item_list_id: product.listId || '',
            item_list_name: product.listName || '',
            item_variant: product.variant || '',
            location_id: product.locationId || '',
            price: parseFloat(product.price) || 0,
            quantity: product.quantity || 1
        };
    }

    /**
     * E-Commerce Event: view_item (Produkt ansehen)
     */
    function trackViewItem(product) {
        if (!hasConsent()) return;

        const item = formatProduct(product);

        gtag('event', 'view_item', {
            currency: 'EUR',
            value: item.price * item.quantity,
            items: [item]
        });

        log('view_item tracked', item);
    }

    /**
     * E-Commerce Event: add_to_cart (Zum Warenkorb hinzufügen)
     */
    function trackAddToCart(product, quantity = 1) {
        if (!hasConsent()) return;

        const item = formatProduct({
            ...product,
            quantity: quantity
        });

        gtag('event', 'add_to_cart', {
            currency: 'EUR',
            value: item.price * quantity,
            items: [item]
        });

        log('add_to_cart tracked', item);
    }

    /**
     * E-Commerce Event: add_to_wishlist (Zur Wunschliste hinzufügen)
     */
    function trackAddToWishlist(product) {
        if (!hasConsent()) return;

        const item = formatProduct(product);

        gtag('event', 'add_to_wishlist', {
            currency: 'EUR',
            value: item.price,
            items: [item]
        });

        log('add_to_wishlist tracked', item);
    }

    /**
     * E-Commerce Event: remove_from_cart (Aus Warenkorb entfernen)
     */
    function trackRemoveFromCart(product, quantity = 1) {
        if (!hasConsent()) return;

        const item = formatProduct({
            ...product,
            quantity: quantity
        });

        gtag('event', 'remove_from_cart', {
            currency: 'EUR',
            value: item.price * quantity,
            items: [item]
        });

        log('remove_from_cart tracked', item);
    }

    /**
     * E-Commerce Event: view_cart (Warenkorb ansehen)
     */
    function trackViewCart(cartItems) {
        if (!hasConsent()) return;

        const items = cartItems.map((item, index) => formatProduct({
            ...item,
            index: index
        }));

        const totalValue = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        gtag('event', 'view_cart', {
            currency: 'EUR',
            value: totalValue,
            items: items
        });

        log('view_cart tracked', { totalValue, itemCount: items.length });
    }

    /**
     * E-Commerce Event: begin_checkout (Checkout starten)
     */
    function trackBeginCheckout(cartItems, coupon = '') {
        if (!hasConsent()) return;

        const items = cartItems.map((item, index) => formatProduct({
            ...item,
            index: index
        }));

        const totalValue = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        gtag('event', 'begin_checkout', {
            currency: 'EUR',
            value: totalValue,
            coupon: coupon,
            items: items
        });

        log('begin_checkout tracked', { totalValue, coupon });
    }

    /**
     * E-Commerce Event: add_shipping_info (Versandinfo)
     */
    function trackAddShippingInfo(cartItems, shippingTier, coupon = '') {
        if (!hasConsent()) return;

        const items = cartItems.map((item, index) => formatProduct({
            ...item,
            index: index
        }));

        const totalValue = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        gtag('event', 'add_shipping_info', {
            currency: 'EUR',
            value: totalValue,
            coupon: coupon,
            shipping_tier: shippingTier,
            items: items
        });

        log('add_shipping_info tracked', { shippingTier, totalValue });
    }

    /**
     * E-Commerce Event: add_payment_info (Zahlungsinformation)
     */
    function trackAddPaymentInfo(cartItems, paymentType, coupon = '') {
        if (!hasConsent()) return;

        const items = cartItems.map((item, index) => formatProduct({
            ...item,
            index: index
        }));

        const totalValue = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        gtag('event', 'add_payment_info', {
            currency: 'EUR',
            value: totalValue,
            coupon: coupon,
            payment_type: paymentType,
            items: items
        });

        log('add_payment_info tracked', { paymentType, totalValue });
    }

    /**
     * E-Commerce Event: purchase (Kauf abgeschlossen)
     */
    function trackPurchase(orderData) {
        if (!hasConsent()) return;

        const items = orderData.items.map((item, index) => formatProduct({
            ...item,
            index: index
        }));

        gtag('event', 'purchase', {
            transaction_id: orderData.transactionId,
            affiliation: 'LayerStore',
            value: orderData.value,
            currency: 'EUR',
            tax: orderData.tax || 0,
            shipping: orderData.shipping || 0,
            coupon: orderData.coupon || '',
            items: items
        });

        log('purchase tracked', orderData);
    }

    /**
     * E-Commerce Event: refund (Rückerstattung)
     */
    function trackRefund(transactionId, refundAmount = 0) {
        if (!hasConsent()) return;

        const eventData = {
            transaction_id: transactionId,
            currency: 'EUR'
        };

        if (refundAmount > 0) {
            eventData.value = refundAmount;
        }

        gtag('event', 'refund', eventData);
        log('refund tracked', { transactionId, refundAmount });
    }

    /**
     * Custom Event Tracking
     */
    function trackEvent(eventName, parameters) {
        if (!hasConsent()) return;

        gtag('event', eventName, {
            ...parameters,
            custom_map: {
                'custom_parameter_1': 'value'
            }
        });

        log('Custom event tracked', { eventName, parameters });
    }

    /**
     * Suchbegriffe tracken
     */
    function trackSearch(searchTerm, resultsCount = 0) {
        if (!hasConsent()) return;

        gtag('event', 'search', {
            search_term: searchTerm,
            results_count: resultsCount
        });

        log('search tracked', { searchTerm, resultsCount });
    }

    /**
     * Share Event tracken
     */
    function trackShare(contentType, itemId) {
        if (!hasConsent()) return;

        gtag('event', 'share', {
            content_type: contentType,
            item_id: itemId
        });

        log('share tracked', { contentType, itemId });
    }

    /**
     * Sign Up Event tracken
     */
    function trackSignUp(method) {
        if (!hasConsent()) return;

        gtag('event', 'sign_up', {
            method: method
        });

        log('sign_up tracked', { method });
    }

    /**
     * Login Event tracken
     */
    function trackLogin(method) {
        if (!hasConsent()) return;

        gtag('event', 'login', {
            method: method
        });

        log('login tracked', { method });
    }

    /**
     * Konsens erteilen
     */
    function grantConsent() {
        consentGranted = true;
        localStorage.setItem('layerstore_analytics_consent', 'granted');

        if (window.gtag) {
            gtag('consent', 'update', {
                analytics_storage: 'granted',
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted'
            });
        }

        log('Analytics consent granted');
        initGA4();
    }

    /**
     * Konsent entziehen
     */
    function denyConsent() {
        consentGranted = false;
        localStorage.removeItem('layerstore_analytics_consent');

        if (window.gtag) {
            gtag('consent', 'update', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied'
            });
        }

        log('Analytics consent denied');
    }

    /**
     * Default Konsent-Einstellung (bevor Consent erteilt wird)
     */
    function setDefaultConsent() {
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }

        gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            wait_for_update: 500
        });

        log('Default consent set to denied');
    }

    /**
     * Pageview-Änderungen bei SPA-Navigation tracken
     */
    function trackPageChanges() {
        let lastUrl = window.location.href;

        // MutationObserver für history.pushState/replaceState
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;

        history.pushState = function() {
            originalPushState.apply(this, arguments);
            handleUrlChange();
        };

        history.replaceState = function() {
            originalReplaceState.apply(this, arguments);
            handleUrlChange();
        };

        window.addEventListener('popstate', handleUrlChange);

        function handleUrlChange() {
            const newUrl = window.location.href;
            if (newUrl !== lastUrl) {
                lastUrl = newUrl;
                trackPageView();
            }
        }
    }

    // API exportieren
    const LayerStoreAnalytics = {
        init: initGA4,
        pageview: trackPageView,
        viewItem: trackViewItem,
        addToCart: trackAddToCart,
        removeFromCart: trackRemoveFromCart,
        viewCart: trackViewCart,
        beginCheckout: trackBeginCheckout,
        addShippingInfo: trackAddShippingInfo,
        addPaymentInfo: trackAddPaymentInfo,
        purchase: trackPurchase,
        refund: trackRefund,
        event: trackEvent,
        search: trackSearch,
        share: trackShare,
        signUp: trackSignUp,
        login: trackLogin,
        grantConsent: grantConsent,
        denyConsent: denyConsent,
        setDefaultConsent: setDefaultConsent,
        hasConsent: hasConsent,
        config: CONFIG
    };

    // Global verfügbar machen
    window.LayerStoreAnalytics = LayerStoreAnalytics;

    // Default Konsent beim Laden setzen
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setDefaultConsent();
            if (hasConsent()) {
                initGA4();
            }
        });
    } else {
        setDefaultConsent();
        if (hasConsent()) {
            initGA4();
        }
    }

    // URL-Tracking für Single-Page-Applications
    trackPageChanges();

})(window, document);
