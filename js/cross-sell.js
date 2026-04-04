/**
 * LayerStore Cross-Selling Module
 * Displays similar products and upsells in the cart
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==================== PRODUCT DATABASE ====================
    const products = {
        osterkarotte: {
            name: 'Osterkarotte',
            price: '13,00 €',
            category: 'ostern',
            tags: ['gemüse', 'ostern', 'hasen'],
            images: ['/collections/easter/images/carrot1.jpeg'],
            url: '/collections/easter#osterkarotte'
        },
        hasenkorb: {
            name: 'Hasenkorb',
            price: '13,00 €',
            category: 'ostern',
            tags: ['hase', 'korb', 'ostern'],
            images: ['/collections/easter/images/bunnybasketbeige.jpeg'],
            url: '/collections/easter#hasenkorb'
        },
        osterei: {
            name: 'Osterei mit Beinen',
            price: '2,00 €',
            category: 'ostern',
            tags: ['ei', 'ostern', 'niedlich'],
            images: ['/collections/easter/images/legseggkinder.jpeg'],
            url: '/collections/easter#osterei'
        },
        gestricktesei: {
            name: 'Gestricktes Ei',
            price: '9,00 €',
            category: 'ostern',
            tags: ['ei', 'gestrickt', 'ostern'],
            images: ['/collections/easter/images/knittedeggmintgreen.jpeg'],
            url: '/collections/easter#gestricktesei'
        },
        kinderhalter: {
            name: 'Hasen-Kinderhalter',
            price: '4,00 €',
            category: 'ostern',
            tags: ['hase', 'halter', 'kind'],
            images: ['/collections/easter/images/bunnykinderholder.jpeg'],
            url: '/collections/easter#kinderhalter'
        },
        hasemmitkorb: {
            name: 'Hase mit Korb',
            price: '5,00 €',
            category: 'ostern',
            tags: ['hase', 'korb', 'ostern'],
            images: ['/collections/easter/images/bunnywithbasket.jpeg'],
            url: '/collections/easter#hasemmitkorb'
        },
        kinderrahmen: {
            name: 'Hasen-Kinderrahmen',
            price: '2,00 €',
            category: 'ostern',
            tags: ['hase', 'rahmen', 'kind'],
            images: ['/collections/easter/images/kinderrahmen.jpg'],
            url: '/collections/easter#kinderrahmen'
        },
        hasedekochoco: {
            name: 'Hase Deko 15cm',
            price: '12,00 €',
            category: 'deko',
            tags: ['hase', 'deko', 'schoko'],
            images: ['/collections/easter/images/fuzzybunnydekochoco1.jpeg'],
            url: '/collections/easter#hasedekochoco'
        },
        hasedekowhite: {
            name: 'Hase Deko 11cm',
            price: '8,00 €',
            category: 'deko',
            tags: ['hase', 'deko', 'weiss'],
            images: ['/collections/easter/images/fuzzybunnydekowhite.jpeg'],
            url: '/collections/easter#hasedekowhite'
        },
        skandibig: {
            name: 'Osterhase "Skandi Linien" 12,5cm',
            price: '10,00 €',
            category: 'ostern',
            tags: ['hase', 'skandi', 'modern'],
            images: ['/collections/easter/images/OsterhaseSkandiBig.jpeg'],
            url: '/collections/easter#skandibig'
        },
        skandismall: {
            name: 'Osterhase "Skandi Linien" 11cm',
            price: '8,00 €',
            category: 'ostern',
            tags: ['hase', 'skandi', 'modern'],
            images: ['/collections/easter/images/OsterhaseSkandi.jpeg'],
            url: '/collections/easter#skandismall'
        }
    };

    // ==================== RECOMMENDATION ENGINE ====================
    class RecommendationEngine {
        constructor() {
            this.products = products;
        }

        /**
         * Find similar products based on tags and category
         */
        findSimilar(productId, limit = 4) {
            const currentProduct = this.products[productId];
            if (!currentProduct) return [];

            const scored = [];

            Object.entries(this.products).forEach(([id, product]) => {
                if (id === productId) return;

                let score = 0;

                // Same category
                if (product.category === currentProduct.category) {
                    score += 3;
                }

                // Matching tags
                const commonTags = product.tags.filter(tag =>
                    currentProduct.tags.includes(tag)
                );
                score += commonTags.length * 2;

                // Price similarity
                const priceDiff = Math.abs(
                    parseFloat(product.price) - parseFloat(currentProduct.price)
                );
                if (priceDiff < 5) score += 1;

                scored.push({ id, score });
            });

            // Sort by score and return top results
            return scored
                .sort((a, b) => b.score - a.score)
                .slice(0, limit)
                .map(item => ({ ...this.products[item.id], id: item.id }));
        }

        /**
         * Get products frequently bought together
         */
        getFrequentlyBoughtTogether(productIds) {
            const bundles = {
                'ostern-basket': ['osterkarotte', 'osterei', 'gestricktesei'],
                'bunny-set': ['hasenkorb', 'hasemmitkorb', 'kinderhalter'],
                'deko-set': ['hasedekochoco', 'hasedekowhite', 'skandibig']
            };

            // Check if any bundle matches
            for (const [bundleName, bundleProducts] of Object.entries(bundles)) {
                const matches = bundleProducts.filter(id => productIds.includes(id));
                if (matches.length >= 1) {
                    // Return products from bundle that aren't in cart
                    return bundleProducts
                        .filter(id => !productIds.includes(id))
                        .slice(0, 2)
                        .map(id => ({ ...this.products[id], id }));
                }
            }

            return [];
        }

        /**
         * Get "don't forget" items
         */
        getDontForgetItems(productIds) {
            const allTags = productIds
                .map(id => this.products[id]?.tags || [])
                .flat();

            // Count tag frequency
            const tagCounts = {};
            allTags.forEach(tag => {
                tagCounts[tag] = (tagCounts[tag] || 0) + 1;
            });

            // Find products with matching tags but not in cart
            const suggestions = [];

            Object.entries(this.products).forEach(([id, product]) => {
                if (productIds.includes(id)) return;

                const relevance = product.tags.reduce((sum, tag) =>
                    sum + (tagCounts[tag] || 0), 0
                );

                if (relevance > 0) {
                    suggestions.push({ ...product, id, relevance });
                }
            });

            return suggestions
                .sort((a, b) => b.relevance - a.relevance)
                .slice(0, 3);
        }
    }

    // ==================== CROSS-SELL RENDERER ====================
    class CrossSellRenderer {
        constructor(container) {
            this.container = container;
            this.engine = new RecommendationEngine();
        }

        renderSimilarProducts(cartItems) {
            const productIds = cartItems.map(item => item.id);

            if (productIds.length === 0) return;

            // Get similar products for each cart item
            const similarProducts = new Map();

            productIds.forEach(productId => {
                const similar = this.engine.findSimilar(productId, 2);
                similar.forEach(product => {
                    if (!similarProducts.has(product.id)) {
                        similarProducts.set(product.id, product);
                    }
                });
            });

            const products = Array.from(similarProducts.values()).slice(0, 4);

            if (products.length === 0) return;

            this.renderSection('Ähnliche Produkte', products, 'similar');
        }

        renderFrequentlyBoughtTogether(cartItems) {
            const productIds = cartItems.map(item => item.id);
            const products = this.engine.getFrequentlyBoughtTogether(productIds);

            if (products.length === 0) return;

            this.renderSection('Passend dazu', products, 'bundle');
        }

        renderDontForget(cartItems) {
            const productIds = cartItems.map(item => item.id);
            const products = this.engine.getDontForgetItems(productIds);

            if (products.length === 0) return;

            this.renderSection('Vergiss nicht...', products, 'dont-forget');
        }

        renderSection(title, products, type) {
            const section = document.createElement('div');
            section.className = `cross-sell-section cross-sell-${type}`;

            section.innerHTML = `
                <div class="cross-sell-header">
                    <h3 class="cross-sell-title">${title}</h3>
                    ${type === 'dont-forget' ? '<span class="cross-sell-subtitle">Diese Artikel passen perfekt zu Ihrer Auswahl</span>' : ''}
                </div>
                <div class="cross-sell-products">
                    ${products.map(product => this.renderProductCard(product)).join('')}
                </div>
            `;

            this.container.appendChild(section);
            this.attachEventListeners(section);
        }

        renderProductCard(product) {
            return `
                <div class="cross-sell-product" data-product-id="${product.id}">
                    <a href="${product.url}" class="cross-sell-image-link">
                        <img src="${product.images[0]}" alt="${product.name}" class="cross-sell-image" loading="lazy">
                    </a>
                    <div class="cross-sell-info">
                        <h4 class="cross-sell-name">
                            <a href="${product.url}">${product.name}</a>
                        </h4>
                        <div class="cross-sell-price">${product.price}</div>
                        <button class="cross-sell-add-btn" data-product-id="${product.id}">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0a1 1 0 011 1v6h6a1 1 0 110 2H9v6a1 1 0 11-2 0V9H1a1 1 0 110-2h6V1a1 1 0 011-1z"/>
                            </svg>
                            Hinzufügen
                        </button>
                    </div>
                </div>
            `;
        }

        attachEventListeners(section) {
            section.querySelectorAll('.cross-sell-add-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.dataset.productId;

                    if (window.cartStorage && window.LayerStoreCart) {
                        // Add to cart with default options
                        window.LayerStoreCart.add({
                            id: productId,
                            name: this.engine.products[productId].name,
                            price: this.engine.products[productId].price,
                            color: 'standard',
                            option: 'as-photo',
                            quantity: 1
                        });

                        // Update button state
                        btn.innerHTML = `
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>
                            </svg>
                            Hinzugefügt
                        `;
                        btn.classList.add('added');

                        setTimeout(() => {
                            btn.innerHTML = `
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a1 1 0 011 1v6h6a1 1 0 110 2H9v6a1 1 0 11-2 0V9H1a1 1 0 110-2h6V1a1 1 0 011-1z"/>
                                </svg>
                                Hinzufügen
                            `;
                            btn.classList.remove('added');
                        }, 2000);
                    }
                });
            });
        }
    }

    // ==================== INJECT STYLES ====================
    function injectStyles() {
        const styleId = 'cross-sell-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            .cross-sell-section {
                margin-top: 3rem;
                padding: 2rem;
                background: #fafafa;
                border-radius: 12px;
            }

            .cross-sell-header {
                margin-bottom: 1.5rem;
            }

            .cross-sell-title {
                font-family: 'Quicksand', sans-serif;
                font-size: 1.5rem;
                font-weight: 500;
                color: var(--accent, #232F3D);
                margin: 0 0 0.25rem 0;
            }

            .cross-sell-subtitle {
                font-size: 0.9rem;
                color: var(--text-secondary, #666666);
            }

            .cross-sell-products {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 1.5rem;
            }

            .cross-sell-product {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                transition: box-shadow 0.2s ease, transform 0.2s ease;
            }

            .cross-sell-product:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            .cross-sell-image-link {
                display: block;
                text-decoration: none;
            }

            .cross-sell-image {
                width: 100%;
                aspect-ratio: 1;
                object-fit: cover;
                background: var(--bg-secondary, #F5F3EB);
            }

            .cross-sell-info {
                padding: 1rem;
            }

            .cross-sell-name {
                font-size: 0.95rem;
                font-weight: 500;
                margin: 0 0 0.5rem 0;
                line-height: 1.3;
            }

            .cross-sell-name a {
                color: var(--accent, #232F3D);
                text-decoration: none;
                transition: color 0.2s ease;
            }

            .cross-sell-name a:hover {
                color: var(--accent-light, #4A5A6A);
            }

            .cross-sell-price {
                font-weight: 600;
                color: var(--accent, #232F3D);
                margin-bottom: 0.75rem;
            }

            .cross-sell-add-btn {
                width: 100%;
                padding: 0.5rem 0.75rem;
                background: var(--accent, #232F3D);
                color: white;
                border: none;
                border-radius: 4px;
                font-size: 0.85rem;
                font-weight: 500;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.2s ease;
            }

            .cross-sell-add-btn:hover {
                background: var(--accent-light, #4A5A6A);
            }

            .cross-sell-add-btn.added {
                background: #10b981;
            }

            @media (max-width: 1024px) {
                .cross-sell-products {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            @media (max-width: 768px) {
                .cross-sell-products {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 1rem;
                }

                .cross-sell-section {
                    padding: 1.5rem;
                }
            }

            @media (max-width: 480px) {
                .cross-sell-products {
                    grid-template-columns: 1fr;
                }
            }
        `;

        document.head.appendChild(style);
    }

    // ==================== AUTO-INITIALIZE ====================
    function initCrossSell() {
        injectStyles();

        const container = document.getElementById('crossSellContainer');
        if (!container) return;

        // Wait for cart to be ready
        const init = () => {
            if (!window.cartStorage) {
                setTimeout(init, 100);
                return;
            }

            const cart = window.cartStorage.getCart();
            const renderer = new CrossSellRenderer(container);

            // Render different sections based on cart content
            renderer.renderDontForget(cart);
            renderer.renderFrequentlyBoughtTogether(cart);
            renderer.renderSimilarProducts(cart);
        };

        if (window.cartStorage) {
            init();
        } else {
            window.addEventListener('cartReady', init);
        }
    }

    // ==================== EXPORT ====================
    window.CrossSell = {
        RecommendationEngine,
        CrossSellRenderer,
        init: initCrossSell
    };

    // Auto-initialize on cart page
    if (document.getElementById('crossSellContainer')) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCrossSell);
        } else {
            initCrossSell();
        }
    }

})();
