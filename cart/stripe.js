/**
 * Stripe Checkout Helper v2.0
 * Handles Stripe payment integration for LayerStore cart
 *
 * @version 2.0.0
 * @features
 * - Promo Code Support (native Stripe)
 * - Shipping Options
 * - Loading States
 * - Enhanced Error Handling
 * - Buy Now Functionality
 * - Customer Data Collection
 */

(function() {
    'use strict';

    // ==================== CONFIGURATION ====================
    const CONFIG = {
        apiUrl: '/cart/create-checkout-session.php',
        stripePublishableKey: 'pk_test_51T58RB0tkT4gFwpoiGlKX7VQOirsh5m9VhtehVv0c6DpmWj6uLsGC9xMY7HcFB1up0M3mnDB53KgIwsf2MNIAVsJ002LeLQnzM',
        currency: 'eur',
        locale: 'de',
        timeout: 30000, // 30 seconds
        defaultShipping: [
            {
                name: 'Standardversand',
                amount: 495, // 4.95 EUR in cents
                min_unit: 'business_day',
                min_value: 3,
                max_unit: 'business_day',
                max_value: 7
            },
            {
                name: 'Expressversand',
                amount: 995, // 9.95 EUR in cents
                min_unit: 'business_day',
                min_value: 1,
                max_unit: 'business_day',
                max_value: 3
            }
        ]
    };

    // ==================== ERROR TYPES ====================
    const ERROR_TYPES = {
        NETWORK: 'network_error',
        API: 'api_error',
        VALIDATION: 'validation_error',
        TIMEOUT: 'timeout_error',
        CART_EMPTY: 'cart_empty',
        STRIPE_LOAD: 'stripe_load_error'
    };

    // ==================== STRIPE CHECKOUT CLASS ====================
    class StripeCheckout {
        constructor(config = {}) {
            this.config = { ...CONFIG, ...config };
            this.stripe = null;
            this.isLoading = false;
            this.abortController = null;

            // Initialize Stripe if publishable key is provided
            if (this.config.stripePublishableKey && this.config.stripePublishableKey !== 'pk_test_...') {
                try {
                    this.stripe = Stripe(this.config.stripePublishableKey, {
                        locale: this.config.locale
                    });
                } catch (error) {
                    console.error('Failed to initialize Stripe:', error);
                    this.handleInitializationError(error);
                }
            }
        }

        /**
         * Handle Stripe initialization error
         */
        handleInitializationError(error) {
            // Dispatch custom event for UI to handle
            window.dispatchEvent(new CustomEvent('stripe:init-error', {
                detail: { error, message: 'Stripe konnte nicht geladen werden. Bitte Seite neu laden.' }
            }));
        }

        /**
         * Generate unique client reference ID
         */
        generateClientReferenceId() {
            return 'ls_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        /**
         * Validate email address
         */
        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        /**
         * Sanitize input to prevent XSS
         */
        sanitize(input) {
            const div = document.createElement('div');
            div.textContent = input;
            return div.innerHTML;
        }

        /**
         * Build line items for Stripe
         */
        buildLineItems(items, options = {}) {
            return items.map(item => {
                // Handle price format (convert "13,00 €" to number)
                let price = item.price || 0;

                if (typeof price === 'string') {
                    price = parseFloat(price.replace(',', '.').replace(/[^\d.]/g, ''));
                }

                // Convert to cents
                const amountInCents = Math.round(price * 100);

                // Build product data
                const productData = {
                    name: this.sanitize(item.name || 'Produkt'),
                    description: this.sanitize(item.description || ''),
                    metadata: {
                        product_id: item.id || 'unknown',
                        category: item.category || 'general'
                    }
                };

                // Add images if available
                if (item.images && item.images.length > 0) {
                    productData.images = item.images;
                } else if (item.image) {
                    productData.images = [item.image];
                }

                return {
                    price_data: {
                        currency: this.config.currency,
                        product_data: productData,
                        unit_amount: amountInCents,
                        tax_behavior: 'exclusive'
                    },
                    quantity: item.quantity || 1
                };
            });
        }

        /**
         * Start Stripe checkout process
         * @param {Array} items - Cart items in Stripe format
         * @param {Object} options - Additional options
         * @returns {Promise<Object>} Checkout session data
         */
        async startCheckout(items, options = {}) {
            // Prevent multiple concurrent requests
            if (this.isLoading) {
                throw this.createError('Ein Checkout läuft bereits. Bitte warten...', ERROR_TYPES.VALIDATION);
            }

            // Validate items
            if (!items || items.length === 0) {
                throw this.createError('Der Warenkorb ist leer.', ERROR_TYPES.CART_EMPTY);
            }

            // Set loading state
            this.isLoading = true;
            this.abortController = new AbortController();

            const {
                customerEmail = null,
                promoCode = null,
                enablePromoCode = true,
                successUrl = null,
                cancelUrl = null,
                collectPhone = true,
                collectAddress = false,
                shippingOptions = this.config.defaultShipping,
                upsellItems = [],
                metadata = {},
                clientReferenceId = this.generateClientReferenceId(),
                enableTax = false
            } = options;

            // Validate email if provided
            if (customerEmail && !this.validateEmail(customerEmail)) {
                this.isLoading = false;
                throw this.createError('Bitte geben Sie eine gültige E-Mail-Adresse ein.', ERROR_TYPES.VALIDATION);
            }

            // Build line items
            const lineItems = this.buildLineItems(items);

            // Build URLs
            const baseUrl = window.location.origin + '/cart';
            const finalSuccessUrl = successUrl || baseUrl + '?success=true&session_id={CHECKOUT_SESSION_ID}';
            const finalCancelUrl = cancelUrl || baseUrl + '?canceled=true';

            // Prepare request payload
            const payload = {
                line_items: lineItems,
                success_url: finalSuccessUrl,
                cancel_url: finalCancelUrl,
                promo_code: promoCode,
                enable_promo_code: enablePromoCode,
                customer_email: customerEmail,
                collect_phone: collectPhone,
                collect_address: collectAddress,
                shipping_options: shippingOptions,
                upsell_items: this.buildLineItems(upsellItems),
                metadata: {
                    ...metadata,
                    client_timestamp: new Date().toISOString(),
                    page_url: window.location.href
                },
                client_reference_id: clientReferenceId,
                enable_tax: enableTax
            };

            try {
                // Create timeout promise
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Timeout')), this.config.timeout);
                });

                // Call backend API with timeout
                const response = await Promise.race([
                    fetch(this.config.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload),
                        signal: this.abortController.signal
                    }),
                    timeoutPromise
                ]);

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw this.createError(
                        data.error || 'Ein Fehler ist aufgetreten.',
                        data.code || ERROR_TYPES.API,
                        data.details
                    );
                }

                return data;

            } catch (error) {
                if (error.name === 'AbortError') {
                    throw this.createError('Anfrage abgebrochen.', ERROR_TYPES.VALIDATION);
                }

                if (error.message === 'Timeout') {
                    throw this.createError(
                        'Die Anfrage hat zu lange gedauert. Bitte versuchen Sie es erneut.',
                        ERROR_TYPES.TIMEOUT
                    );
                }

                if (error instanceof TypeError && error.message.includes('fetch')) {
                    throw this.createError(
                        'Keine Internetverbindung. Bitte überprüfen Sie Ihre Verbindung.',
                        ERROR_TYPES.NETWORK
                    );
                }

                // Re-throw custom errors
                throw error;

            } finally {
                this.isLoading = false;
                this.abortController = null;
            }
        }

        /**
         * Create a standardized error object
         */
        createError(message, type = ERROR_TYPES.API, details = null) {
            const error = new Error(message);
            error.type = type;
            error.details = details;
            error.timestamp = new Date().toISOString();
            return error;
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
                    // Store session info for post-checkout validation
                    sessionStorage.setItem('stripe_session_id', result.sessionId);
                    sessionStorage.setItem('stripe_session_start', Date.now().toString());

                    window.location.href = result.url;
                } else {
                    throw this.createError('Keine Checkout URL erhalten.', ERROR_TYPES.API);
                }
            } catch (error) {
                // Dispatch error event for UI handling
                window.dispatchEvent(new CustomEvent('stripe:checkout-error', {
                    detail: {
                        error,
                        message: error.message,
                        type: error.type || ERROR_TYPES.API
                    }
                }));
                throw error;
            }
        }

        /**
         * Abort current checkout request
         */
        abortCheckout() {
            if (this.abortController) {
                this.abortController.abort();
                this.abortController = null;
                this.isLoading = false;
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
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.toString());
        }

        /**
         * Get loading state
         */
        getLoadingState() {
            return this.isLoading;
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
                        id: item.id || 'unknown',
                        name: item.name || 'Produkt',
                        description: descriptionParts.join(', ') || '',
                        price: price,
                        quantity: item.quantity || 1,
                        images: item.image ? [item.image] : [],
                        category: '3d-print',
                        metadata: {
                            color: item.color || 'standard',
                            isMarble: item.isMarble ? 'true' : 'false',
                            option: item.option || 'as-photo'
                        }
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
                'TY26KM': 0.1,
                'LAYER10': 0.1,
                'OSTER2025': 0.15
            };

            if (!promoCode || !PROMO_CODES[promoCode]) {
                return 0;
            }

            return subtotal * PROMO_CODES[promoCode];
        }

        /**
         * Validate promo code
         * @param {string} code - Promo code to validate
         * @returns {Object|null} Promo code details or null
         */
        validatePromoCode(code) {
            const PROMO_CODES = {
                'TM26MG': { discount: 0.1, description: 'Trödelmarkt Metro Godorf' },
                'TY26KM': { discount: 0.1, description: 'Thank You card' },
                'LAYER10': { discount: 0.1, description: '10% Rabatt auf alles' },
                'OSTER2025': { discount: 0.15, description: 'Oster-Sonderaktion' },
                'WELCOME5': { discount: 0.05, description: 'Willkommen-Rabatt' }
            };

            const upperCode = code.trim().toUpperCase();
            return PROMO_CODES[upperCode] || null;
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

        /**
         * Quick "Buy Now" for single product
         * @param {Object} product - Product to buy
         * @param {Object} options - Additional options
         */
        async buyNow(product, options = {}) {
            const items = [{
                id: product.id,
                name: product.name,
                description: product.description || '',
                price: product.price,
                quantity: 1,
                images: product.images || [],
                category: '3d-print'
            }];

            return await this.checkout.redirectToCheckout(items, {
                ...options,
                metadata: {
                    ...options.metadata,
                    checkout_type: 'buy_now'
                }
            });
        }
    }

    // ==================== UI HELPERS ====================
    class StripeUIHelper {
        constructor(checkoutInstance) {
            this.checkout = checkoutInstance;
        }

        /**
         * Show loading state
         */
        showLoading(message = 'Zahlung wird vorbereitet...') {
            const existing = document.getElementById('stripe-loading-overlay');
            if (existing) existing.remove();

            const overlay = document.createElement('div');
            overlay.id = 'stripe-loading-overlay';
            overlay.innerHTML = `
                <div class="stripe-loading-content">
                    <div class="stripe-spinner"></div>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;

            // Add styles
            const style = document.createElement('style');
            style.textContent = `
                #stripe-loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(35, 46, 61, 0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                }
                .stripe-loading-content {
                    text-align: center;
                    color: white;
                }
                .stripe-spinner {
                    width: 50px;
                    height: 50px;
                    margin: 0 auto 20px;
                    border: 4px solid rgba(255,255,255,0.3);
                    border-top-color: #F0ECDA;
                    border-radius: 50%;
                    animation: stripe-spin 0.8s linear infinite;
                }
                @keyframes stripe-spin {
                    to { transform: rotate(360deg); }
                }
                .stripe-loading-content p {
                    font-family: 'Quicksand', sans-serif;
                    font-size: 1.1rem;
                }
            `;

            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }

        /**
         * Hide loading state
         */
        hideLoading() {
            const overlay = document.getElementById('stripe-loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
            }
        }

        /**
         * Show error message
         */
        showError(message, type = 'error') {
            const existing = document.getElementById('stripe-error-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'stripe-error-toast';
            toast.className = `stripe-toast stripe-toast-${type}`;
            toast.innerHTML = `
                <span class="stripe-toast-icon">${type === 'error' ? '✕' : '⚠'}</span>
                <span class="stripe-toast-message">${this.escapeHtml(message)}</span>
            `;

            // Add styles
            if (!document.getElementById('stripe-toast-styles')) {
                const style = document.createElement('style');
                style.id = 'stripe-toast-styles';
                style.textContent = `
                    .stripe-toast {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        border-left: 4px solid #dc2626;
                        border-radius: 8px;
                        padding: 1rem 1.5rem;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                        z-index: 10001;
                        display: flex;
                        align-items: center;
                        gap: 1rem;
                        transform: translateX(400px);
                        opacity: 0;
                        transition: all 0.3s ease;
                        max-width: 400px;
                    }
                    .stripe-toast.show {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    .stripe-toast-icon {
                        font-size: 1.5rem;
                        color: #dc2626;
                    }
                    .stripe-toast-message {
                        font-family: 'Quicksand', sans-serif;
                        font-size: 0.95rem;
                        color: #1a1a1a;
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => toast.classList.add('show'));

            // Auto hide after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // ==================== EXPORT ====================
    // Expose to global scope
    window.LayerStoreStripe = {
        Checkout: StripeCheckout,
        CartIntegration: CartStripeIntegration,
        UIHelper: StripeUIHelper,
        ERROR_TYPES: ERROR_TYPES,
        createIntegration: function() {
            return new CartStripeIntegration();
        },
        createCheckout: function(config) {
            return new StripeCheckout(config);
        }
    };

    // Auto-initialize for convenience
    window.layerStoreStripe = window.LayerStoreStripe.createIntegration();

    // Set up global event listeners for error handling
    document.addEventListener('stripe:checkout-error', (event) => {
        const { message, type } = event.detail;
        console.error('Stripe checkout error:', type, message);

        // Show user-friendly error
        const helper = new StripeUIHelper(window.layerStoreStripe.checkout);
        helper.showError(message);

        // Hide loading if visible
        helper.hideLoading();
    });

    document.addEventListener('stripe:init-error', (event) => {
        console.error('Stripe initialization error:', event.detail);
        const helper = new StripeUIHelper(window.layerStoreStripe.checkout);
        helper.showError('Stripe konnte nicht geladen werden. Bitte Seite neu laden.', 'warning');
    });

})();
