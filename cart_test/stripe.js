/**
 * Stripe Checkout Helper
 * Handles Stripe payment integration for LayerStore cart
 *
 * @version 1.0.0
 */

(function() {
    'use strict';

    // ==================== CONFIGURATION ====================
    const CONFIG = {
        apiUrl: '/cart/create-checkout-session.php',
        stripePublishableKey: 'pk_test_51T58RB0tkT4gFwpoiGlKX7VQOirsh5m9VhtehVv0c6DpmWj6uLsGC9xMY7HcFB1up0M3mnDB53KgIwsf2MNIAVsJ002LeLQnzM', // Test mode publishable key
        currency: 'eur',
        locale: 'de'
    };

    // ==================== STRIPE CHECKOUT CLASS ====================
    class StripeCheckout {
        constructor(config = {}) {
            this.config = { ...CONFIG, ...config };
            this.stripe = null;

            // Initialize Stripe if publishable key is provided
            if (this.config.stripePublishableKey && this.config.stripePublishableKey !== 'pk_test_...') {
                this.stripe = Stripe(this.config.stripePublishableKey);
            }
        }

        /**
         * Start Stripe checkout process
         * @param {Array} items - Cart items in Stripe format
         * @param {Object} options - Additional options
         * @returns {Promise<string>} Checkout URL
         */
        async startCheckout(items, options = {}) {
            const {
                customerEmail = null,
                successUrl = null,
                cancelUrl = null,
                metadata = {}
            } = options;

            // Build line items for Stripe
            const lineItems = items.map(item => {
                // Handle price format (convert "13,00 €" to number)
                let price = item.price || 0;

                if (typeof price === 'string') {
                    price = parseFloat(price.replace(',', '.').replace(/[^\d.]/g, ''));
                }

                // Convert to cents
                const amountInCents = Math.round(price * 100);

                return {
                    price_data: {
                        currency: this.config.currency,
                        product_data: {
                            name: item.name || 'Produkt',
                            description: item.description || '',
                            images: item.images || []
                        },
                        unit_amount: amountInCents
                    },
                    quantity: item.quantity || 1
                };
            });

            // Build URLs
            const baseUrl = window.location.origin + '/cart';
            const finalSuccessUrl = successUrl || baseUrl + '?session_id={CHECKOUT_SESSION_ID}';
            const finalCancelUrl = cancelUrl || baseUrl + '?canceled=true';

            // Prepare request payload
            const payload = {
                line_items: lineItems,
                success_url: finalSuccessUrl,
                cancel_url: finalCancelUrl,
                customer_email: customerEmail,
                metadata: metadata
            };

            try {
                // Call backend API
                const response = await fetch(this.config.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Stripe API Fehler');
                }

                return data;

            } catch (error) {
                console.error('Stripe checkout error:', error);
                throw error;
            }
        }

        /**
         * Redirect to Stripe checkout
         * @param {Array} items - Cart items
         * @param {Object} options - Additional options
         */
        async redirectToCheckout(items, options = {}) {
            try {
                const result = await this.startCheckout(items, options);

                if (result.url) {
                    window.location.href = result.url;
                } else {
                    throw new Error('Keine Checkout URL erhalten');
                }
            } catch (error) {
                throw error;
            }
        }

        /**
         * Check payment status from URL parameters
         * @returns {Object} Status object
         */
        checkPaymentStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session_id');
            const canceled = urlParams.get('canceled');

            return {
                isSuccess: !!sessionId,
                isCanceled: canceled === 'true',
                sessionId: sessionId
            };
        }

        /**
         * Clean URL parameters
         */
        cleanUrl() {
            const url = new URL(window.location);
            url.searchParams.delete('session_id');
            url.searchParams.delete('canceled');
            window.history.replaceState({}, document.title, url.toString());
        }
    }

    // ==================== CART INTEGRATION ====================
    class CartStripeIntegration {
        constructor() {
            this.checkout = new StripeCheckout();
        }

        /**
         * Prepare cart items for Stripe
         * @param {Array} cart - Cart from localStorage
         * @returns {Array} Stripe formatted items
         */
        prepareCartItems(cart) {
            return cart
                .filter(item => item.price && item.option !== 'individuell')
                .map(item => {
                    let price = 0;

                    if (item.price && typeof item.price === 'string') {
                        price = parseFloat(item.price.replace(',', '.').replace(' €', ''));
                    }

                    // Apply marble discount (+20%)
                    if (item.isMarble) {
                        price = price * 1.2;
                    }

                    // Build description
                    const descriptionParts = [];

                    if (item.color && item.color !== 'standard') {
                        const colorName = this.getColorName(item.color);
                        descriptionParts.push(colorName);
                    }

                    if (item.isMarble) {
                        descriptionParts.push('Marmor');
                    }

                    if (item.option && item.option !== 'as-photo') {
                        descriptionParts.push('Individuelle Größe');
                    }

                    return {
                        name: item.name || 'Produkt',
                        description: descriptionParts.join(', ') || '',
                        price: price,
                        quantity: item.quantity || 1,
                        images: item.image ? [item.image] : []
                    };
                });
        }

        /**
         * Get color name from code
         * @param {string} colorCode - Color code
         * @returns {string} Color name
         */
        getColorName(colorCode) {
            const colorNames = {
                'black': 'Schwarz',
                'chestnut-brown-marble': 'Kastanienbraun Marmor',
                'light-green': 'Hellgrün',
                'mint-green': 'Minzgrün',
                'new-pink': 'Neu Pink',
                'olive-green': 'Olivgrün',
                'peach': 'Pfirsich',
                'pink': 'Pink',
                'purple': 'Lila',
                'red': 'Rot',
                'sakura-pink': 'Sakura Pink',
                'sky-blue': 'Himmelblau',
                'white': 'Weiß',
                'yellow': 'Gelb',
                beige: 'Beige',
                orange: 'Orange',
                lemonyellow: 'Lemon Yellow',
                lightgreen: 'Light Green',
                mintgreen: 'Mint Green',
                olivegreen: 'Olive Green',
                skyblue: 'Sky Blue',
                blue: 'Blue',
                deeppink: 'Deep Pink',
                newpink: 'New Pink'
            };

            return colorNames[colorCode] || colorCode;
        }

        /**
         * Calculate total discount from promo code
         * @param {number} subtotal - Subtotal before discount
         * @param {string|null} promoCode - Applied promo code
         * @returns {number} Discount amount
         */
        calculateDiscount(subtotal, promoCode) {
            const PROMO_CODES = {
                'TM26MG': 0.1,
                'TY26KM': 0.1
            };

            if (!promoCode || !PROMO_CODES[promoCode]) {
                return 0;
            }

            return subtotal * PROMO_CODES[promoCode];
        }

        /**
         * Start checkout process
         * @param {Array} cart - Cart items
         * @param {Object} options - Options
         */
        async checkout(cart, options = {}) {
            const items = this.prepareCartItems(cart);

            if (items.length === 0) {
                throw new Error('Warenkorb ist leer oder enthält nur Artikel mit individuellem Preis');
            }

            return await this.checkout.redirectToCheckout(items, options);
        }
    }

    // ==================== EXPORT ====================
    // Expose to global scope
    window.LayerStoreStripe = {
        Checkout: StripeCheckout,
        CartIntegration: CartStripeIntegration,
        createIntegration: function() {
            return new CartStripeIntegration();
        }
    };

    // Auto-initialize for convenience
    window.layerStoreStripe = window.LayerStoreStripe.createIntegration();

})();
