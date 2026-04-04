/**
 * LayerStore E-Commerce Tracking Helper
 * Vereinfacht das Tracking von E-Commerce Events
 */

(function(window, document) {
    'use strict';

    /**
     * Produkt-Objekt für Tracking vorbereiten
     */
    function prepareProduct(product, options = {}) {
        return {
            id: product.id || product.sku || '',
            name: product.name || product.title || '',
            price: parseFloat(product.price) || 0,
            category: product.category || 'Dekoration',
            subCategory: product.subCategory || '',
            collection: product.collection || '',
            variant: options.variant || product.variant || product.color || '',
            quantity: options.quantity || product.quantity || 1,
            listId: options.listId || '',
            listName: options.listName || '',
            position: options.position || 0
        };
    }

    /**
     * Warenkorb-Items vorbereiten
     */
    function prepareCartItems(cart) {
        return cart.map((item, index) => prepareProduct(item, {
            position: index,
            variant: item.color || item.variant || ''
        }));
    }

    /**
     * Produktaufruf tracken
     */
    function trackProductView(product, options = {}) {
        const preparedProduct = prepareProduct(product, options);

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.viewItem(preparedProduct);
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.viewContent(preparedProduct);
        }

        // Server-seitig tracken
        sendToServer('product_view', {
            product_id: preparedProduct.id,
            product_name: preparedProduct.name,
            price: preparedProduct.price,
            category: preparedProduct.category
        });
    }

    /**
     * Produktpreise tracken
     */
    function trackPriceClick(product, price, options = {}) {
        const preparedProduct = prepareProduct({ ...product, price: price }, options);

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.event('view_pricing', {
                item_id: preparedProduct.id,
                item_name: preparedProduct.name,
                price: preparedProduct.price
            });
        }
    }

    /**
     * Farbe auswählen tracken
     */
    function trackColorSelect(product, color) {
        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.event('select_color', {
                item_id: product.id,
                item_name: product.name,
                color_selected: color
            });
        }
    }

    /**
     * Größe auswählen tracken
     */
    function trackSizeSelect(product, size) {
        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.event('select_size', {
                item_id: product.id,
                item_name: product.name,
                size_selected: size
            });
        }
    }

    /**
     * Zum Warenkorb hinzufügen tracken
     */
    function trackAddToCart(product, quantity = 1, options = {}) {
        const preparedProduct = prepareProduct(product, { ...options, quantity });

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.addToCart(preparedProduct, quantity);
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.addToCart(preparedProduct, quantity);
        }

        // Server-seitig tracken
        sendToServer('add_to_cart', {
            product_id: preparedProduct.id,
            product_name: preparedProduct.name,
            price: preparedProduct.price,
            quantity: quantity
        });
    }

    /**
     * Aus Warenkorb entfernen tracken
     */
    function trackRemoveFromCart(product, quantity = 1) {
        const preparedProduct = prepareProduct(product, { quantity });

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.removeFromCart(preparedProduct, quantity);
        }
    }

    /**
     * Warenkorb ansehen tracken
     */
    function trackViewCart(cart) {
        const items = prepareCartItems(cart);
        const total = cart.reduce((sum, item) => {
            const price = parseFloat(item.price?.replace(',', '.')?.replace(/[^\d.]/g, '') || 0);
            return sum + (price * item.quantity);
        }, 0);

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.viewCart(items);
        }

        // Server-seitig tracken
        sendToServer('view_cart', {
            items_count: items.length,
            total_value: total
        });
    }

    /**
     * Checkout starten tracken
     */
    function trackBeginCheckout(cart, promoCode = '') {
        const items = prepareCartItems(cart);
        const total = cart.reduce((sum, item) => {
            const price = parseFloat(item.price?.replace(',', '.')?.replace(/[^\d.]/g, '') || 0);
            return sum + (price * item.quantity);
        }, 0);

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.beginCheckout(items, promoCode);
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.initiateCheckout({
                items: items,
                total: total
            }, promoCode);
        }

        // Server-seitig tracken
        sendToServer('begin_checkout', {
            items_count: items.length,
            total_value: total,
            promo_code: promoCode
        });
    }

    /**
     * Kauf abschließen tracken
     */
    function trackPurchase(orderData) {
        const items = prepareCartItems(orderData.items || []);

        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.purchase({
                transactionId: orderData.transactionId || orderData.id,
                value: orderData.value || orderData.total,
                tax: orderData.tax || 0,
                shipping: orderData.shipping || 0,
                coupon: orderData.coupon || orderData.promoCode || '',
                items: items
            });
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.purchase({
                transactionId: orderData.transactionId || orderData.id,
                value: orderData.value || orderData.total,
                items: items
            });
        }

        // Server-seitig tracken
        sendToServer('purchase', {
            transaction_id: orderData.transactionId || orderData.id,
            value: orderData.value || orderData.total,
            items: items,
            coupon: orderData.coupon || orderData.promoCode || ''
        });
    }

    /**
     * Promo-Code anwenden tracken
     */
    function trackPromoCodeApply(code) {
        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.event('apply_promo_code', {
                promo_code: code
            });
        }
    }

    /**
     * Suche tracken
     */
    function trackSearch(searchTerm, resultCount = 0) {
        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.search(searchTerm, resultCount);
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.search(searchTerm, []);
        }
    }

    /**
     * Produkt teilen tracken
     */
    function trackShare(product, platform) {
        if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.share('product', product.id || product.sku);
        }

        if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.customEvent('ShareProduct', {
                content_name: product.name,
                platform: platform
            });
        }
    }

    /**
     * Server-seitig senden (via fetch)
     */
    function sendToServer(eventType, data) {
        fetch('/analytics/tracking.php?action=ecommerce', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: eventType,
                data: data
            })
        }).catch(err => {
            // Silent fail - Analytics ist nicht kritisch
            if (console && console.debug) {
                console.debug('Analytics send failed:', err);
            }
        });
    }

    /**
     * Checkout-Flow automatisch tracken
     */
    function autoTrackCheckout() {
        // Cart-Änderungen überwachen
        const originalSetItem = localStorage.setItem;
        localStorage.setItem = function(key, value) {
            originalSetItem.call(this, key, value);

            if (key === 'layerstore_cart') {
                try {
                    const cart = JSON.parse(value);
                    if (cart.length > 0) {
                        trackViewCart(cart);
                    }
                } catch (e) {
                    // Ignore
                }
            }
        };
    }

    /**
     * Pageview automatisch tracken bei Navigation
     */
    function autoTrackPageView() {
        // Router-Änderungen erkennen
        let lastUrl = window.location.href;

        const observer = new MutationObserver(() => {
            const currentUrl = window.location.href;
            if (currentUrl !== lastUrl) {
                lastUrl = currentUrl;

                if (window.LayerStoreAnalytics) {
                    window.LayerStoreAnalytics.pageview();
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // API exportieren
    const LayerStoreEcommerce = {
        productView: trackProductView,
        priceClick: trackPriceClick,
        colorSelect: trackColorSelect,
        sizeSelect: trackSizeSelect,
        addToCart: trackAddToCart,
        removeFromCart: trackRemoveFromCart,
        viewCart: trackViewCart,
        beginCheckout: trackBeginCheckout,
        purchase: trackPurchase,
        promoCodeApply: trackPromoCodeApply,
        search: trackSearch,
        share: trackShare,
        autoTrack: autoTrackCheckout,
        init: function() {
            autoTrackCheckout();
            autoTrackPageView();
        }
    };

    window.LayerStoreEcommerce = LayerStoreEcommerce;

    // Automatisch initialisieren
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', LayerStoreEcommerce.init);
    } else {
        LayerStoreEcommerce.init();
    }

})(window, document);
