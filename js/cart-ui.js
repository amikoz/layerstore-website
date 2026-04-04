/**
 * LayerStore Cart UI Module
 * Handles all UI interactions for the cart page
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==================== TOAST NOTIFICATIONS ====================
    class ToastManager {
        constructor() {
            this.container = null;
            this.init();
        }

        init() {
            this.container = document.getElementById('toast');
            if (!this.container) {
                this.createContainer();
            }
        }

        createContainer() {
            this.container = document.createElement('div');
            this.container.id = 'toast';
            this.container.className = 'toast';
            document.body.appendChild(this.container);
        }

        show(title, message, type = 'success', duration = 4000) {
            const toast = this.container || this.createContainer();

            toast.innerHTML = `
                <div class="toast-icon">${this.getIcon(type)}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Schließen">&times;</button>
            `;

            toast.className = `toast toast-${type} show`;

            // Close button
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => this.hide());

            // Auto hide
            setTimeout(() => this.hide(), duration);

            // Log for debugging
            console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
        }

        hide() {
            const toast = this.container;
            if (toast) {
                toast.classList.remove('show');
            }
        }

        getIcon(type) {
            const icons = {
                success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
                error: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
                warning: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
            };
            return icons[type] || icons.info;
        }
    }

    // ==================== LOADING SPINNER ====================
    class LoadingSpinner {
        constructor() {
            this.activeSpinners = 0;
        }

        show(container, message = 'Laden...') {
            this.activeSpinners++;

            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner-overlay';
            spinner.innerHTML = `
                <div class="loading-spinner">
                    <svg class="spinner-svg" viewBox="0 0 50 50">
                        <circle class="spinner-path" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
                    </svg>
                    ${message ? `<div class="loading-message">${message}</div>` : ''}
                </div>
            `;

            if (typeof container === 'string') {
                container = document.querySelector(container);
            }

            if (container) {
                container.appendChild(spinner);
                container.style.position = 'relative';
            }

            return spinner;
        }

        hide(spinner) {
            if (spinner && spinner.parentNode) {
                spinner.parentNode.removeChild(spinner);
            }
            this.activeSpinners--;
        }

        showInline(button, originalText) {
            button.disabled = true;
            button.dataset.originalText = originalText;
            button.innerHTML = `
                <svg class="inline-spinner" width="16" height="16" viewBox="0 0 50 50">
                    <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4"></circle>
                </svg>
                ${button.dataset.loadingText || 'Laden...'}
            `;
        }

        hideInline(button) {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'OK';
        }
    }

    // ==================== CART BADGE ====================
    class CartBadge {
        constructor() {
            this.badge = document.getElementById('cartCount');
            this.init();
        }

        init() {
            if (!this.badge) {
                console.warn('Cart badge element not found');
                return;
            }

            // Listen for cart updates
            window.addEventListener('cartUpdated', (e) => {
                this.update(e.detail.count);
            });
        }

        update(count) {
            if (!this.badge) return;

            this.badge.textContent = count;

            if (count > 0) {
                this.badge.classList.add('visible');
                this.animate();
            } else {
                this.badge.classList.remove('visible');
            }
        }

        animate() {
            this.badge.style.transform = 'scale(1.3)';
            setTimeout(() => {
                this.badge.style.transform = 'scale(1)';
            }, 200);
        }
    }

    // ==================== QUANTITY SELECTOR ====================
    class QuantitySelector {
        constructor(container) {
            this.container = container;
            this.input = container.querySelector('input[type="number"]');
            this.decreaseBtn = container.querySelector('[data-action="decrease"]');
            this.increaseBtn = container.querySelector('[data-action="increase"]');
            this.min = parseInt(this.input.min) || 1;
            this.max = parseInt(this.input.max) || 99;

            this.init();
        }

        init() {
            this.decreaseBtn.addEventListener('click', () => this.decrease());
            this.increaseBtn.addEventListener('click', () => this.increase());
            this.input.addEventListener('change', () => this.validate());
        }

        decrease() {
            const current = parseInt(this.input.value) || this.min;
            const newValue = Math.max(this.min, current - 1);
            this.input.value = newValue;
            this.triggerChange();
        }

        increase() {
            const current = parseInt(this.input.value) || this.min;
            const newValue = Math.min(this.max, current + 1);
            this.input.value = newValue;
            this.triggerChange();
        }

        validate() {
            let value = parseInt(this.input.value) || this.min;
            value = Math.max(this.min, Math.min(this.max, value));
            this.input.value = value;
        }

        triggerChange() {
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        getValue() {
            return parseInt(this.input.value) || this.min;
        }

        setValue(value) {
            this.input.value = Math.max(this.min, Math.min(this.max, value));
        }
    }

    // ==================== EMPTY STATE ====================
    class EmptyState {
        constructor(container) {
            this.container = container;
        }

        render(options = {}) {
            const {
                title = 'Ihr Warenkorb ist leer',
                message = 'Entdecken Sie unsere wunderschönen Kollektionen!',
                ctaText = 'Jetzt entdecken',
                ctaLink = '/collections',
                illustration = 'cart'
            } = options;

            this.container.innerHTML = `
                <div class="empty-cart">
                    ${this.getSvg(illustration)}
                    <h2>${title}</h2>
                    <p>${message}</p>
                    <a href="${ctaLink}" class="btn-primary">${ctaText}</a>
                </div>
            `;
        }

        getSvg(type) {
            const svgs = {
                cart: `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>`,
                heart: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                </svg>`
            };
            return svgs[type] || svgs.cart;
        }
    }

    // ==================== CART ANIMATIONS ====================
    class CartAnimations {
        static flyToCart(element, target) {
            const rect = element.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();

            const flyElement = element.cloneNode(true);
            flyElement.style.position = 'fixed';
            flyElement.style.left = rect.left + 'px';
            flyElement.style.top = rect.top + 'px';
            flyElement.style.width = rect.width + 'px';
            flyElement.style.height = rect.height + 'px';
            flyElement.style.transition = 'all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1)';
            flyElement.style.zIndex = '10000';
            flyElement.style.opacity = '0.8';
            flyElement.style.pointerEvents = 'none';

            document.body.appendChild(flyElement);

            requestAnimationFrame(() => {
                flyElement.style.left = targetRect.left + 'px';
                flyElement.style.top = targetRect.top + 'px';
                flyElement.style.width = '20px';
                flyElement.style.height = '20px';
                flyElement.style.opacity = '0.3';
            });

            setTimeout(() => {
                flyElement.remove();
            }, 600);
        }

        static shake(element) {
            element.style.animation = 'shake 0.5s ease';
            setTimeout(() => {
                element.style.animation = '';
            }, 500);
        }
    }

    // ==================== INJECT STYLES ====================
    function injectStyles() {
        const styleId = 'cart-ui-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Toast Notifications */
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1.25rem 1.5rem;
                min-width: 320px;
                max-width: 480px;
                transform: translateX(120%);
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .toast.show {
                transform: translateX(0);
            }

            .toast-success {
                border-left: 4px solid #10b981;
            }

            .toast-error {
                border-left: 4px solid #ef4444;
            }

            .toast-warning {
                border-left: 4px solid #f59e0b;
            }

            .toast-info {
                border-left: 4px solid #3b82f6;
            }

            .toast-icon {
                flex-shrink: 0;
                color: inherit;
            }

            .toast-success .toast-icon { color: #10b981; }
            .toast-error .toast-icon { color: #ef4444; }
            .toast-warning .toast-icon { color: #f59e0b; }
            .toast-info .toast-icon { color: #3b82f6; }

            .toast-content {
                flex: 1;
            }

            .toast-title {
                font-weight: 600;
                font-size: 1rem;
                margin-bottom: 0.25rem;
                color: var(--text-primary, #1a1a1a);
            }

            .toast-message {
                font-size: 0.9rem;
                color: var(--text-secondary, #666666);
            }

            .toast-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: var(--text-secondary, #666666);
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Loading Spinner Overlay */
            .loading-spinner-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100;
                border-radius: inherit;
            }

            .loading-spinner {
                text-align: center;
            }

            .spinner-svg {
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
            }

            .spinner-path {
                stroke: #232F3D;
                stroke-dasharray: 90, 150;
                stroke-dashoffset: 0;
                stroke-linecap: round;
                animation: dash 1.5s ease-in-out infinite;
            }

            @keyframes spin {
                100% { transform: rotate(360deg); }
            }

            @keyframes dash {
                0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
                50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
                100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
            }

            .loading-message {
                margin-top: 1rem;
                color: var(--text-secondary, #666666);
                font-size: 0.9rem;
            }

            /* Inline Spinner */
            .inline-spinner {
                animation: spin 1s linear infinite;
            }

            /* Cart Badge Animation */
            .cart-count {
                transition: transform 0.2s ease;
            }

            /* Shake Animation */
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }

            /* Responsive Toast */
            @media (max-width: 480px) {
                .toast {
                    left: 10px;
                    right: 10px;
                    min-width: auto;
                    max-width: none;
                }
            }
        `;

        document.head.appendChild(style);
    }

    // ==================== EXPORT ====================
    window.CartUI = {
        ToastManager,
        LoadingSpinner,
        CartBadge,
        QuantitySelector,
        EmptyState,
        CartAnimations
    };

    // Auto-inject styles
    injectStyles();

    // Auto-initialize components
    document.addEventListener('DOMContentLoaded', () => {
        window.toast = new ToastManager();
        window.cartBadge = new CartBadge();
        window.loadingSpinner = new LoadingSpinner();
    });

})();
