/**
 * LayerStore Guest Checkout Module
 * Handles guest checkout without registration
 *
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==================== GUEST CHECKOUT CLASS ====================
    class GuestCheckout {
        constructor(options = {}) {
            this.config = {
                modalId: 'checkoutModal',
                formId: 'checkoutForm',
                requireEmail: true,
                requireAddress: false,
                privacyRequired: true,
                ...options
            };

            this.modal = null;
            this.form = null;
            this.formData = {
                email: '',
                name: '',
                phone: '',
                address: {
                    street: '',
                    postalCode: '',
                    city: '',
                    country: 'Deutschland'
                },
                privacyConsent: false,
                newsletter: false
            };

            this.init();
        }

        init() {
            this.modal = document.getElementById(this.config.modalId);
            this.form = document.getElementById(this.config.formId);

            if (!this.modal) {
                console.warn('Checkout modal not found');
                return;
            }

            this.attachEventListeners();
            this.loadSavedData();
        }

        attachEventListeners() {
            // Close button
            const closeBtn = this.modal.querySelector('.checkout-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }

            // Backdrop click
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.close();
                }
            });

            // Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen()) {
                    this.close();
                }
            });

            // Form inputs
            if (this.form) {
                this.form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.addEventListener('change', (e) => this.saveFormData(e.target));
                    input.addEventListener('blur', (e) => this.validateField(e.target));
                });
            }

            // Privacy consent
            const privacyCheckbox = document.getElementById('privacyConsent');
            if (privacyCheckbox) {
                privacyCheckbox.addEventListener('change', (e) => {
                    this.formData.privacyConsent = e.target.checked;
                    this.updateButtonStates();
                });
            }
        }

        open() {
            this.modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Focus first input
            setTimeout(() => {
                const firstInput = this.form?.querySelector('input:not([type="checkbox"])');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }

        close() {
            this.modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        isOpen() {
            return this.modal.classList.contains('active');
        }

        saveFormData(input) {
            const name = input.name;
            const value = input.type === 'checkbox' ? input.checked : input.value;

            if (name.startsWith('address.')) {
                const field = name.replace('address.', '');
                this.formData.address[field] = value;
            } else {
                this.formData[name] = value;
            }

            // Save to localStorage for convenience
            this.saveToStorage();
        }

        loadSavedData() {
            const saved = localStorage.getItem('layerstore_checkout_data');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.formData = { ...this.formData, ...data };

                    // Populate form fields
                    if (this.form) {
                        Object.keys(this.formData).forEach(key => {
                            if (key === 'address') {
                                Object.keys(this.formData.address).forEach(field => {
                                    const input = this.form.querySelector(`[name="address.${field}"]`);
                                    if (input) {
                                        input.value = this.formData.address[field];
                                    }
                                });
                            } else {
                                const input = this.form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    if (input.type === 'checkbox') {
                                        input.checked = this.formData[key];
                                    } else {
                                        input.value = this.formData[key];
                                    }
                                }
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error loading checkout data:', e);
                }
            }
        }

        saveToStorage() {
            localStorage.setItem('layerstore_checkout_data', JSON.stringify(this.formData));
        }

        clearStorage() {
            localStorage.removeItem('layerstore_checkout_data');
        }

        validateField(input) {
            let isValid = true;
            let errorMessage = '';

            // Remove existing error
            input.classList.remove('error');
            const existingError = input.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            // Required validation
            if (input.hasAttribute('required') && !input.value.trim()) {
                isValid = false;
                errorMessage = 'Dies ist ein Pflichtfeld';
            }

            // Email validation
            if (input.type === 'email' && input.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    isValid = false;
                    errorMessage = 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
                }
            }

            // Phone validation (optional but should be valid if provided)
            if (input.type === 'tel' && input.value) {
                const phoneRegex = /^[\d\s+()-]{6,}$/;
                if (!phoneRegex.test(input.value)) {
                    isValid = false;
                    errorMessage = 'Bitte geben Sie eine gültige Telefonnummer ein';
                }
            }

            // Show error if invalid
            if (!isValid) {
                input.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message visible';
                errorDiv.textContent = errorMessage;
                input.parentNode.appendChild(errorDiv);
            }

            return isValid;
        }

        validateForm() {
            if (!this.form) return true;

            let isValid = true;
            const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            // Check privacy consent
            if (this.config.privacyRequired && !this.formData.privacyConsent) {
                if (window.toast) {
                    window.toast.show(
                        'Datenschutz',
                        'Bitte stimmen Sie der Datenschutzerklärung zu',
                        'error'
                    );
                }
                isValid = false;
            }

            return isValid;
        }

        updateButtonStates() {
            const buttons = this.modal.querySelectorAll('.checkout-submit-btn, .btn-whatsapp, .btn-email');
            const disabled = this.config.privacyRequired && !this.formData.privacyConsent;

            buttons.forEach(btn => {
                btn.disabled = disabled;
            });
        }

        getFormData() {
            return { ...this.formData };
        }

        getOrderSummary(cart) {
            let summary = '=== BESTELLUNG ===\n\n';

            cart.forEach(item => {
                summary += `${item.quantity}x ${item.name}`;
                if (item.color && item.color !== 'standard') {
                    summary += `\n   Farbe: ${item.color}`;
                }
                if (item.isMarble) {
                    summary += ' (Marmor)';
                }
                if (item.option && item.option !== 'as-photo') {
                    summary += '\n   Größe: Individuell';
                }
                if (item.price && item.option !== 'individuell') {
                    summary += ` - ${item.price}`;
                }
                summary += '\n';
            });

            // Add customer info
            if (this.formData.name) {
                summary += `\n=== KUNDE ===\nName: ${this.formData.name}\n`;
            }
            if (this.formData.email) {
                summary += `E-Mail: ${this.formData.email}\n`;
            }
            if (this.formData.phone) {
                summary += `Telefon: ${this.formData.phone}\n`;
            }

            summary += `\nDatum: ${new Date().toLocaleDateString('de-DE')}\n`;
            summary += `Zeit: ${new Date().toLocaleTimeString('de-DE')}\n`;

            return summary;
        }

        prepareWhatsAppMessage(cart) {
            const summary = this.getOrderSummary(cart);

            let message = '🎨 *Neue Bestellung von LayerStore*\n\n';
            message += summary;

            return encodeURIComponent(message);
        }

        prepareEmailBody(cart) {
            const summary = this.getOrderSummary(cart);
            return summary;
        }

        submitWhatsApp(phoneNumber = '4915259821293') {
            if (!this.validateForm()) return false;

            const cart = window.cartStorage ? window.cartStorage.getCart() : [];
            if (cart.length === 0) {
                if (window.toast) {
                    window.toast.show('Fehler', 'Ihr Warenkorb ist leer', 'error');
                }
                return false;
            }

            const message = this.prepareWhatsAppMessage(cart);
            const url = `https://wa.me/${phoneNumber}?text=${message}`;

            window.open(url, '_blank');
            this.onSubmitSuccess();

            return true;
        }

        submitEmail(recipient = 'info@layerstore.eu') {
            if (!this.validateForm()) return false;

            const cart = window.cartStorage ? window.cartStorage.getCart() : [];
            if (cart.length === 0) {
                if (window.toast) {
                    window.toast.show('Fehler', 'Ihr Warenkorb ist leer', 'error');
                }
                return false;
            }

            const subject = encodeURIComponent('Neue Bestellung von LayerStore');
            const body = encodeURIComponent(this.prepareEmailBody(cart));
            const url = `mailto:${recipient}?subject=${subject}&body=${body}`;

            window.location.href = url;
            this.onSubmitSuccess();

            return true;
        }

        onSubmitSuccess() {
            // Clear cart
            if (window.cartStorage) {
                window.cartStorage.clearCart();
            }

            // Clear promo code
            if (window.cartStorage) {
                window.cartStorage.setPromoCode(null);
            }

            // Keep customer data for convenience
            // this.clearStorage();

            // Close modal
            this.close();

            // Show success message
            if (window.toast) {
                window.toast.show(
                    'Bestellung gesendet!',
                    'Vielen Dank! Wir melden uns schnellstmöglich bei Ihnen.',
                    'success'
                );
            }

            // Dispatch event
            window.dispatchEvent(new CustomEvent('checkoutComplete'));
        }
    }

    // ==================== CHECKOUT FORM ENHANCEMENT ====================
    class CheckoutFormEnhancer {
        constructor(formElement) {
            this.form = formElement;
            this.init();
        }

        init() {
            this.addFloatingLabels();
            this.addInputValidation();
            this.addAutoComplete();
        }

        addFloatingLabels() {
            const inputs = this.form.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea, select');

            inputs.forEach(input => {
                const wrapper = input.parentNode;
                const label = wrapper.querySelector('label');

                if (label && !wrapper.classList.contains('floating-label')) {
                    wrapper.classList.add('floating-label');

                    // Add focus classes
                    input.addEventListener('focus', () => {
                        wrapper.classList.add('focused');
                    });

                    input.addEventListener('blur', () => {
                        wrapper.classList.remove('focused');
                        if (input.value) {
                            wrapper.classList.add('has-value');
                        } else {
                            wrapper.classList.remove('has-value');
                        }
                    });

                    // Check initial value
                    if (input.value) {
                        wrapper.classList.add('has-value');
                    }
                }
            });
        }

        addInputValidation() {
            const inputs = this.form.querySelectorAll('input, textarea, select');

            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value) {
                        input.classList.add('touched');
                    }
                });
            });
        }

        addAutoComplete() {
            const form = this.form;
            form.setAttribute('autocomplete', 'on');

            const autoCompleteMapping = {
                'customerName': 'name',
                'customerEmail': 'email',
                'customerPhone': 'tel',
                'addressStreet': 'street-address',
                'addressPostalCode': 'postal-code',
                'addressCity': 'address-level2',
                'addressCountry': 'country-name'
            };

            Object.entries(autoCompleteMapping).forEach(([name, autoComplete]) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.setAttribute('autocomplete', autoComplete);
                }
            });
        }
    }

    // ==================== INJECT STYLES ====================
    function injectStyles() {
        const styleId = 'guest-checkout-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Floating Labels */
            .form-group.floating-label {
                position: relative;
            }

            .form-group.floating-label label {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                transition: all 0.2s ease;
                pointer-events: none;
                background: transparent;
                padding: 0 0.25rem;
                margin: 0;
            }

            .form-group.floating-label input:focus + label,
            .form-group.floating-label input.touched + label,
            .form-group.floating-label.has-value label {
                top: 0;
                transform: translateY(-50%);
                font-size: 0.75rem;
                background: white;
                color: var(--accent, #232F3D);
            }

            .form-group.floating-label input {
                padding: 1rem;
            }

            /* Input Validation Styles */
            .form-group input.error,
            .form-group select.error,
            .form-group textarea.error {
                border-color: #dc2626;
            }

            .form-group input.touched:not(:placeholder-shown):valid,
            .form-group select.touched:valid {
                border-color: #10b981;
            }

            /* Error Messages */
            .error-message {
                color: #dc2626;
                font-size: 0.85rem;
                margin-top: 0.5rem;
                display: none;
            }

            .error-message.visible {
                display: block;
            }

            /* Checkout Buttons */
            .checkout-submit-btn:disabled,
            .btn-whatsapp:disabled,
            .btn-email:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                pointer-events: none;
            }
        `;

        document.head.appendChild(style);
    }

    // ==================== EXPORT ====================
    window.GuestCheckout = GuestCheckout;
    window.CheckoutFormEnhancer = CheckoutFormEnhancer;

    // Auto-inject styles
    injectStyles();

    // Auto-initialize
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize guest checkout
        window.guestCheckout = new GuestCheckout();

        // Enhance checkout forms
        const checkoutForms = document.querySelectorAll('#checkoutForm, .checkout-form');
        checkoutForms.forEach(form => {
            new CheckoutFormEnhancer(form);
        });
    });

})();
