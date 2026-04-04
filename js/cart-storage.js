/**
 * LayerStore Cart Storage Module
 * Handles all localStorage operations for the shopping cart
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==================== CONFIGURATION ====================
    const CONFIG = {
        STORAGE_KEY: 'layerstore_cart',
        PROMO_CODE_KEY: 'layerstore_promo_code',
        COOKIE_CONSENT_KEY: 'layerstore_cookie_consent',
        MAX_CART_AGE: 30 * 24 * 60 * 60 * 1000, // 30 days in milliseconds
    };

    // ==================== CART STORAGE CLASS ====================
    class CartStorage {
        constructor(config = {}) {
            this.config = { ...CONFIG, ...config };
        }

        /**
         * Get cart from localStorage
         * @returns {Array} Cart items
         */
        getCart() {
            try {
                const cartData = localStorage.getItem(this.config.STORAGE_KEY);
                if (!cartData) return [];

                const cart = JSON.parse(cartData);

                // Validate cart age
                if (cart.timestamp && Date.now() - cart.timestamp > this.config.MAX_CART_AGE) {
                    this.clearCart();
                    return [];
                }

                return cart.items || [];
            } catch (error) {
                console.error('Error reading cart from storage:', error);
                return [];
            }
        }

        /**
         * Save cart to localStorage
         * @param {Array} items - Cart items to save
         */
        saveCart(items) {
            try {
                const cartData = {
                    items: items,
                    timestamp: Date.now(),
                    version: '2.0.0'
                };
                localStorage.setItem(this.config.STORAGE_KEY, JSON.stringify(cartData));

                // Dispatch custom event for other components
                window.dispatchEvent(new CustomEvent('cartUpdated', {
                    detail: { items, count: this.getItemCount(items) }
                }));
            } catch (error) {
                console.error('Error saving cart to storage:', error);
            }
        }

        /**
         * Add item to cart
         * @param {Object} item - Item to add
         * @returns {Array} Updated cart
         */
        addItem(item) {
            const cart = this.getCart();
            const existingIndex = this.findItemIndex(cart, item);

            if (existingIndex !== -1) {
                // Item exists - update quantity
                cart[existingIndex].quantity += (item.quantity || 1);
            } else {
                // New item - add to cart
                cart.push({
                    ...item,
                    quantity: item.quantity || 1,
                    addedAt: Date.now()
                });
            }

            this.saveCart(cart);
            return cart;
        }

        /**
         * Update item quantity
         * @param {number} index - Item index
         * @param {number} quantity - New quantity
         * @returns {Array} Updated cart
         */
        updateQuantity(index, quantity) {
            const cart = this.getCart();

            if (index >= 0 && index < cart.length) {
                if (quantity <= 0) {
                    cart.splice(index, 1);
                } else {
                    cart[index].quantity = quantity;
                }
                this.saveCart(cart);
            }

            return cart;
        }

        /**
         * Remove item from cart
         * @param {number} index - Item index
         * @returns {Array} Updated cart
         */
        removeItem(index) {
            const cart = this.getCart();

            if (index >= 0 && index < cart.length) {
                cart.splice(index, 1);
                this.saveCart(cart);
            }

            return cart;
        }

        /**
         * Clear entire cart
         */
        clearCart() {
            localStorage.removeItem(this.config.STORAGE_KEY);
            window.dispatchEvent(new CustomEvent('cartCleared'));
        }

        /**
         * Get total item count
         * @param {Array} cart - Cart items (optional, uses stored cart if not provided)
         * @returns {number} Total count
         */
        getItemCount(cart = null) {
            const items = cart || this.getCart();
            return items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        }

        /**
         * Find existing item index
         * @param {Array} cart - Cart items
         * @param {Object} item - Item to find
         * @returns {number} Item index or -1
         */
        findItemIndex(cart, item) {
            return cart.findIndex(cartItem => {
                return cartItem.id === item.id &&
                       cartItem.color === item.color &&
                       cartItem.option === item.option &&
                       cartItem.sizeDescription === item.sizeDescription;
            });
        }

        /**
         * Get applied promo code
         * @returns {string|null} Promo code or null
         */
        getPromoCode() {
            return localStorage.getItem(this.config.PROMO_CODE_KEY) || null;
        }

        /**
         * Set promo code
         * @param {string} code - Promo code to set
         */
        setPromoCode(code) {
            if (code) {
                localStorage.setItem(this.config.PROMO_CODE_KEY, code);
            } else {
                localStorage.removeItem(this.config.PROMO_CODE_KEY);
            }
        }

        /**
         * Check cookie consent
         * @returns {boolean} Consent status
         */
        hasCookieConsent() {
            return localStorage.getItem(this.config.COOKIE_CONSENT_KEY) === 'accepted';
        }

        /**
         * Set cookie consent
         */
        acceptCookieConsent() {
            localStorage.setItem(this.config.COOKIE_CONSENT_KEY, 'accepted');
            window.dispatchEvent(new CustomEvent('cookieConsentAccepted'));
        }

        /**
         * Calculate cart total
         * @param {Array} cart - Cart items (optional)
         * @param {number} discount - Discount amount (optional)
         * @returns {Object} Total calculation
         */
        calculateTotal(cart = null, discount = 0) {
            const items = cart || this.getCart();
            const subtotal = items.reduce((sum, item) => {
                if (!item.price || item.option === 'individuell') return sum;
                const priceNum = parseFloat(item.price.replace(',', '.').replace(/[^\d.]/g, '')) || 0;
                return sum + (priceNum * (item.quantity || 0));
            }, 0);

            const itemCount = this.getItemCount(items);

            return {
                subtotal: subtotal,
                discount: discount,
                total: Math.max(0, subtotal - discount),
                itemCount: itemCount
            };
        }
    }

    // ==================== EXPORT ====================
    window.LayerStoreCartStorage = CartStorage;

    // Create singleton instance
    window.cartStorage = new CartStorage();

})();
