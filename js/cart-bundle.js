/**
 * LayerStore Cart Bundle
 * Main entry point for all cart functionality
 *
 * @version 2.0.0
 *
 * Load this file to enable all cart features:
 * - LocalStorage cart management
 * - Mini cart slide-out
 * - Cart UI components
 * - Guest checkout
 */

(function() {
    'use strict';

    // Track loaded modules
    const loadedModules = new Set();
    const pendingCallbacks = [];

    /**
     * Load script dynamically
     */
    function loadScript(src, id) {
        return new Promise((resolve, reject) => {
            // Check if already loaded
            if (document.getElementById(id)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.id = id;
            script.src = src;
            script.async = true;

            script.onload = () => {
                loadedModules.add(id);
                resolve();
            };

            script.onerror = () => {
                reject(new Error(`Failed to load ${src}`));
            };

            document.head.appendChild(script);
        });
    }

    /**
     * Initialize all cart modules
     */
    async function initCart(options = {}) {
        const {
            storage = true,
            minicart = true,
            ui = true,
            checkout = true,
            autoUpdateBadge = true
        } = options;

        const basePath = options.basePath || '/js';

        try {
            // Load storage module first (dependency for others)
            if (storage) {
                await loadScript(`${basePath}/cart-storage.js`, 'cart-storage-module');
            }

            // Load other modules
            const promises = [];

            if (minicart) {
                promises.push(loadScript(`${basePath}/minicart.js`, 'minicart-module'));
            }

            if (ui) {
                promises.push(loadScript(`${basePath}/cart-ui.js`, 'cart-ui-module'));
            }

            if (checkout) {
                promises.push(loadScript(`${basePath}/checkout-guest.js`, 'checkout-module'));
            }

            await Promise.all(promises);

            // Initialize cart badge if enabled
            if (autoUpdateBadge && window.cartStorage) {
                const cart = window.cartStorage.getCart();
                const count = window.cartStorage.getItemCount(cart);

                // Dispatch initial cart update
                window.dispatchEvent(new CustomEvent('cartUpdated', {
                    detail: { items: cart, count }
                }));
            }

            // Execute pending callbacks
            pendingCallbacks.forEach(cb => cb());
            pendingCallbacks.length = 0;

            // Dispatch ready event
            window.dispatchEvent(new CustomEvent('cartReady'));

            return true;
        } catch (error) {
            console.error('Failed to initialize cart:', error);
            return false;
        }
    }

    /**
     * Public API
     */
    window.LayerStoreCart = {
        init: initCart,
        ready: function(callback) {
            if (loadedModules.size >= 3) {
                callback();
            } else {
                pendingCallbacks.push(callback);
            }
        },
        // Convenience methods
        add: function(item) {
            if (window.cartStorage) {
                window.cartStorage.addItem(item);
                window.miniCart?.update();
                if (window.toast) {
                    window.toast.show('Hinzugefügt', `${item.name} wurde zum Warenkorb hinzugefügt`, 'success', 2000);
                }
            }
        },
        remove: function(index) {
            if (window.cartStorage) {
                window.cartStorage.removeItem(index);
                window.miniCart?.update();
            }
        },
        update: function(index, quantity) {
            if (window.cartStorage) {
                window.cartStorage.updateQuantity(index, quantity);
                window.miniCart?.update();
            }
        },
        get: function() {
            return window.cartStorage ? window.cartStorage.getCart() : [];
        },
        clear: function() {
            if (window.cartStorage) {
                window.cartStorage.clearCart();
                window.miniCart?.update();
            }
        },
        count: function() {
            return window.cartStorage ? window.cartStorage.getItemCount() : 0;
        },
        open: function() {
            if (window.miniCart) {
                window.miniCart.open();
            }
        },
        close: function() {
            if (window.miniCart) {
                window.miniCart.close();
            }
        },
        checkout: function() {
            if (window.guestCheckout) {
                window.guestCheckout.open();
            }
        }
    };

    // Auto-initialize if data attribute is present
    if (document.currentScript?.dataset.autoInit !== 'false') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => initCart());
        } else {
            initCart();
        }
    }

})();
