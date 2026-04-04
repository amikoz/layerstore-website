/**
 * LayerStore Mini Cart Module
 * Slide-out cart overlay with quick view
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==================== PRODUCT DATA ====================
    const products = {
        osterkarotte: {
            name: 'Osterkarotte',
            price: '13,00 €',
            images: ['/collections/easter/images/carrot1.jpeg']
        },
        hasenkorb: {
            name: 'Hasenkorb',
            price: '13,00 €',
            images: ['/collections/easter/images/bunnybasketbeige.jpeg']
        },
        osterei: {
            name: 'Osterei mit Beinen',
            price: '2,00 €',
            images: ['/collections/easter/images/legseggkinder.jpeg']
        },
        gestricktesei: {
            name: 'Gestricktes Ei',
            price: '9,00 €',
            images: ['/collections/easter/images/knittedeggmintgreen.jpeg']
        },
        kinderhalter: {
            name: 'Hasen-Kinderhalter',
            price: '4,00 €',
            images: ['/collections/easter/images/bunnykinderholder.jpeg']
        },
        hasemmitkorb: {
            name: 'Hase mit Korb',
            price: '5,00 €',
            images: ['/collections/easter/images/bunnywithbasket.jpeg']
        },
        kinderrahmen: {
            name: 'Hasen-Kinderrahmen',
            price: '2,00 €',
            images: ['/collections/easter/images/kinderrahmen.jpg']
        },
        hasedekochoco: {
            name: 'Hase Deko 15cm',
            price: '12,00 €',
            images: ['/collections/easter/images/fuzzybunnydekochoco1.jpeg']
        },
        hasedekowhite: {
            name: 'Hase Deko 11cm',
            price: '8,00 €',
            images: ['/collections/easter/images/fuzzybunnydekowhite.jpeg']
        },
        skandibig: {
            name: 'Osterhase "Skandi Linien" 12,5cm',
            price: '10,00 €',
            images: ['/collections/easter/images/OsterhaseSkandiBig.jpeg']
        },
        skandismall: {
            name: 'Osterhase "Skandi Linien" 11cm',
            price: '8,00 €',
            images: ['/collections/easter/images/OsterhaseSkandi.jpeg']
        }
    };

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
        'yellow': 'Gelb'
    };

    // ==================== MINI CART CLASS ====================
    class MiniCart {
        constructor() {
            this.isOpen = false;
            this.element = null;
            this.overlay = null;
            this.cartItems = [];
            this.maxDisplayItems = 3; // Number of items to show in mini cart
        }

        /**
         * Initialize mini cart
         */
        init() {
            this.createMarkup();
            this.attachEventListeners();
            this.update();

            // Listen for cart updates
            window.addEventListener('cartUpdated', (e) => {
                this.update();
            });
        }

        /**
         * Create mini cart markup
         */
        createMarkup() {
            // Create overlay
            this.overlay = document.createElement('div');
            this.overlay.className = 'minicart-overlay';
            this.overlay.innerHTML = `
                <div class="minicart-backdrop"></div>
                <div class="minicart-panel">
                    <div class="minicart-header">
                        <h2 class="minicart-title">Ihr Warenkorb</h2>
                        <button class="minicart-close" aria-label="Schließen">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="minicart-content">
                        <div class="minicart-items" id="minicartItems"></div>
                        <div class="minicart-empty" id="minicartEmpty" style="display: none;">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                            <p>Ihr Warenkorb ist leer</p>
                        </div>
                    </div>
                    <div class="minicart-footer" id="minicartFooter">
                        <div class="minicart-subtotal">
                            <span>Zwischensumme:</span>
                            <span id="minicartSubtotal">0,00 €</span>
                        </div>
                        <a href="/cart" class="minicart-checkout-btn">
                            Zur Kasse
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"/>
                            </svg>
                        </a>
                        <button class="minicart-continue-btn">Weiter einkaufen</button>
                    </div>
                </div>
            `;

            document.body.appendChild(this.overlay);

            // Add styles
            this.injectStyles();
        }

        /**
         * Inject CSS styles
         */
        injectStyles() {
            const styleId = 'minicart-styles';
            if (document.getElementById(styleId)) return;

            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                :root {
                    --minicart-width: 420px;
                    --minicart-bg: #ffffff;
                    --minicart-border: #e7e5e4;
                    --minicart-text: #1a1a1a;
                    --minicart-text-secondary: #666666;
                    --minicart-accent: #232F3D;
                }

                .minicart-overlay {
                    position: fixed;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    z-index: 9999;
                    pointer-events: none;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.3s ease, visibility 0.3s ease;
                }

                .minicart-overlay.open {
                    pointer-events: auto;
                    opacity: 1;
                    visibility: visible;
                }

                .minicart-backdrop {
                    position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    background: rgba(0, 0, 0, 0.5);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }

                .minicart-overlay.open .minicart-backdrop {
                    opacity: 1;
                }

                .minicart-panel {
                    position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    width: var(--minicart-width);
                    max-width: 100vw;
                    background: var(--minicart-bg);
                    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
                    display: flex;
                    flex-direction: column;
                    transform: translateX(100%);
                    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .minicart-overlay.open .minicart-panel {
                    transform: translateX(0);
                }

                .minicart-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 1.25rem 1.5rem;
                    border-bottom: 1px solid var(--minicart-border);
                }

                .minicart-title {
                    font-family: 'Quicksand', sans-serif;
                    font-size: 1.5rem;
                    font-weight: 500;
                    color: var(--minicart-accent);
                    margin: 0;
                }

                .minicart-close {
                    background: none;
                    border: none;
                    padding: 0.5rem;
                    cursor: pointer;
                    color: var(--minicart-text-secondary);
                    transition: color 0.2s ease, transform 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .minicart-close:hover {
                    color: var(--minicart-accent);
                    transform: rotate(90deg);
                }

                .minicart-content {
                    flex: 1;
                    overflow-y: auto;
                    padding: 1rem 1.5rem;
                }

                .minicart-items {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                }

                .minicart-item {
                    display: flex;
                    gap: 1rem;
                    padding: 0.75rem;
                    border: 1px solid var(--minicart-border);
                    border-radius: 8px;
                    transition: box-shadow 0.2s ease;
                }

                .minicart-item:hover {
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                }

                .minicart-item-image {
                    width: 80px;
                    height: 80px;
                    object-fit: cover;
                    border-radius: 6px;
                    background: var(--bg-secondary, #F5F3EB);
                    flex-shrink: 0;
                }

                .minicart-item-details {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-width: 0;
                }

                .minicart-item-name {
                    font-family: 'Quicksand', sans-serif;
                    font-size: 0.95rem;
                    font-weight: 500;
                    color: var(--minicart-accent);
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .minicart-item-variant {
                    font-size: 0.8rem;
                    color: var(--minicart-text-secondary);
                    margin-top: 0.25rem;
                }

                .minicart-item-bottom {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .minicart-item-price {
                    font-weight: 600;
                    color: var(--minicart-accent);
                }

                .minicart-item-quantity {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .minicart-qty-btn {
                    width: 24px;
                    height: 24px;
                    border: 1px solid var(--minicart-border);
                    background: white;
                    color: var(--minicart-text);
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s ease;
                }

                .minicart-qty-btn:hover {
                    border-color: var(--minicart-accent);
                    color: var(--minicart-accent);
                }

                .minicart-qty-value {
                    font-size: 0.9rem;
                    font-weight: 600;
                    min-width: 20px;
                    text-align: center;
                }

                .minicart-item-remove {
                    background: none;
                    border: none;
                    color: #dc2626;
                    font-size: 0.75rem;
                    cursor: pointer;
                    padding: 0.25rem 0.5rem;
                    transition: opacity 0.2s ease;
                }

                .minicart-item-remove:hover {
                    text-decoration: underline;
                    opacity: 0.8;
                }

                .minicart-empty {
                    text-align: center;
                    padding: 3rem 1rem;
                    color: var(--minicart-text-secondary);
                }

                .minicart-empty svg {
                    color: var(--minicart-border);
                    margin-bottom: 1rem;
                }

                .minicart-empty p {
                    font-size: 1rem;
                    margin: 0;
                }

                .minicart-footer {
                    padding: 1.5rem;
                    border-top: 1px solid var(--minicart-border);
                    background: #fafafa;
                }

                .minicart-subtotal {
                    display: flex;
                    justify-content: space-between;
                    font-size: 1rem;
                    font-weight: 500;
                    margin-bottom: 1rem;
                }

                .minicart-checkout-btn {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    width: 100%;
                    padding: 1rem;
                    background: var(--minicart-accent);
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }

                .minicart-checkout-btn:hover {
                    background: var(--minicart-accent);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(35, 47, 61, 0.2);
                }

                .minicart-continue-btn {
                    width: 100%;
                    padding: 0.75rem;
                    background: none;
                    border: none;
                    color: var(--minicart-text-secondary);
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: color 0.2s ease;
                }

                .minicart-continue-btn:hover {
                    color: var(--minicart-accent);
                }

                .minicart-view-all {
                    text-align: center;
                    padding: 1rem;
                    border-top: 1px solid var(--minicart-border);
                }

                .minicart-view-all a {
                    color: var(--minicart-accent);
                    text-decoration: none;
                    font-size: 0.9rem;
                    transition: opacity 0.2s ease;
                }

                .minicart-view-all a:hover {
                    opacity: 0.8;
                }

                @media (max-width: 480px) {
                    :root {
                        --minicart-width: 100%;
                    }

                    .minicart-item-image {
                        width: 60px;
                        height: 60px;
                    }
                }
            `;

            document.head.appendChild(style);
        }

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Close on backdrop click
            this.overlay.querySelector('.minicart-backdrop').addEventListener('click', () => {
                this.close();
            });

            // Close on close button
            this.overlay.querySelector('.minicart-close').addEventListener('click', () => {
                this.close();
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });

            // Continue shopping button
            this.overlay.querySelector('.minicart-continue-btn').addEventListener('click', () => {
                this.close();
            });
        }

        /**
         * Open mini cart
         */
        open() {
            this.isOpen = true;
            this.overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close mini cart
         */
        close() {
            this.isOpen = false;
            this.overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        /**
         * Toggle mini cart
         */
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        /**
         * Update mini cart content
         */
        update() {
            if (!window.cartStorage) return;

            const cart = window.cartStorage.getCart();
            const total = window.cartStorage.calculateTotal();

            this.renderItems(cart);
            this.renderFooter(total);
        }

        /**
         * Render cart items
         */
        renderItems(cart) {
            const itemsContainer = this.getElementById('minicartItems');
            const emptyContainer = this.getElementById('minicartEmpty');
            const footerContainer = this.getElementById('minicartFooter');

            if (cart.length === 0) {
                itemsContainer.style.display = 'none';
                emptyContainer.style.display = 'block';
                footerContainer.style.display = 'none';
                return;
            }

            itemsContainer.style.display = 'flex';
            emptyContainer.style.display = 'none';
            footerContainer.style.display = 'block';

            // Display items (limited)
            const displayItems = cart.slice(0, this.maxDisplayItems);
            const hasMore = cart.length > this.maxDisplayItems;

            itemsContainer.innerHTML = displayItems.map((item, index) => {
                const imagePath = this.getImagePath(item);
                const variantText = this.getVariantText(item);
                const totalPrice = this.getItemTotal(item);

                return `
                    <div class="minicart-item" data-index="${index}">
                        <img src="${imagePath}" alt="${item.name}" class="minicart-item-image" loading="lazy">
                        <div class="minicart-item-details">
                            <div>
                                <div class="minicart-item-name">${item.name}</div>
                                ${variantText ? `<div class="minicart-item-variant">${variantText}</div>` : ''}
                            </div>
                            <div class="minicart-item-bottom">
                                <div class="minicart-item-quantity">
                                    <button class="minicart-qty-btn" data-action="decrease" data-index="${index}">−</button>
                                    <span class="minicart-qty-value">${item.quantity}</span>
                                    <button class="minicart-qty-btn" data-action="increase" data-index="${index}">+</button>
                                </div>
                                <span class="minicart-item-price">${totalPrice}</span>
                            </div>
                        </div>
                        <button class="minicart-item-remove" data-index="${index}">Entfernen</button>
                    </div>
                `;
            }).join('');

            // Show "view all" link if more items
            if (hasMore) {
                const remaining = cart.length - this.maxDisplayItems;
                itemsContainer.innerHTML += `
                    <div class="minicart-view-all">
                        <a href="/cart">+ ${remaining} weitere Artikel anzeigen</a>
                    </div>
                `;
            }

            // Attach item event listeners
            this.attachItemListeners();
        }

        /**
         * Attach event listeners to cart items
         */
        attachItemListeners() {
            const container = this.getElementById('minicartItems');

            // Quantity buttons
            container.querySelectorAll('.minicart-qty-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.target.dataset.index);
                    const action = e.target.dataset.action;
                    const cart = window.cartStorage.getCart();
                    const item = cart[index];

                    if (action === 'increase') {
                        window.cartStorage.updateQuantity(index, item.quantity + 1);
                    } else {
                        window.cartStorage.updateQuantity(index, item.quantity - 1);
                    }

                    this.update();
                });
            });

            // Remove buttons
            container.querySelectorAll('.minicart-item-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.target.dataset.index);
                    window.cartStorage.removeItem(index);
                    this.update();
                });
            });
        }

        /**
         * Render footer with subtotal
         */
        renderFooter(total) {
            const subtotalEl = this.getElementById('minicartSubtotal');
            subtotalEl.textContent = this.formatPrice(total.total);
        }

        /**
         * Get image path for item
         */
        getImagePath(item) {
            if (item.image) {
                return item.image.startsWith('http') || item.image.startsWith('/')
                    ? item.image
                    : `/collections/easter/images/${item.image}`;
            }
            const product = products[item.id];
            if (product && product.images && product.images[0]) {
                return `/collections/easter/images/${product.images[0]}`;
            }
            return '/collections/easter/images/placeholder.jpg';
        }

        /**
         * Get variant text for item
         */
        getVariantText(item) {
            const parts = [];

            if (item.color && item.color !== 'standard') {
                const colorName = colorNames[item.color] || item.color;
                parts.push(colorName);
            }

            if (item.isMarble) {
                parts.push('Marmor');
            }

            if (item.option && item.option !== 'as-photo') {
                parts.push('Individuelle Größe');
            }

            return parts.join(', ');
        }

        /**
         * Get item total price
         */
        getItemTotal(item) {
            if (!item.price || item.option === 'individuell') {
                return 'Auf Anfrage';
            }

            const priceNum = parseFloat(item.price.replace(',', '.').replace(/[^\d.]/g, '')) || 0;
            const total = priceNum * item.quantity;
            return this.formatPrice(total);
        }

        /**
         * Format price
         */
        formatPrice(amount) {
            return amount.toFixed(2).replace('.', ',') + ' €';
        }

        /**
         * Helper to get element by ID from mini cart
         */
        getElementById(id) {
            return this.overlay.querySelector(`#${id}`);
        }
    }

    // ==================== EXPORT ====================
    window.MiniCart = MiniCart;

    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.miniCart = new MiniCart();
            window.miniCart.init();
        });
    } else {
        window.miniCart = new MiniCart();
        window.miniCart.init();
    }

})();
