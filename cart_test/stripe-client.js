/**
 * Stripe Client-Side Checkout
 * Kein Backend nötig - verwendet Stripe.js direkt
 *
 * HINWEIS: Für Produktion sollte ein Backend verwendet werden!
 * Diese Lösung ist für Tests und kleine Projekte geeignet.
 */

// ==================== CONFIG ====================
// Stripe Publishable Key (Test)
const STRIPE_PUBLISHABLE_KEY = 'pk_test_...'; // Hier deinen Key einfügen

// ==================== CHECKOUT ====================

/**
 * Startet Stripe Checkout ohne Backend
 * Verwendet Stripe.js für direkte Integration
 */
async function stripeCheckoutClient(items, successUrl, cancelUrl) {
    // Stripe.js laden wenn noch nicht vorhanden
    if (!window.Stripe) {
        await loadStripeJS();
    }

    const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);

    // Option 1: Redirect zu Stripe Checkout (wenn Session ID existiert)
    // Option 2: Stripe Elements für eigene Payment Form

    // Für diese Lösung verwenden wir Payment Links
    // Die müssen im Stripe Dashboard erstellt werden

    throw new Error('Bitte Stripe Publishable Key konfigurieren');
}

/**
 * Lädt Stripe.js dynamisch
 */
function loadStripeJS() {
    return new Promise((resolve, reject) => {
        if (window.Stripe) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

/**
 * Erstellt einen Payment Link basierend auf dem Warenkorb
 * Verwendet预设 Produkte oder erstellt einen dynamischen Link
 */
async function createPaymentLink(items) {
    // Berechne Gesamtbetrag
    const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    // Stripe Payment Link Format
    // Kann im Dashboard erstellt werden oder dynamisch generiert werden

    // Für Demo-Zwecke: Einfach Redirect mit Parametern
    // In Produktion: Backend für Session Creation verwenden

    return null;
}

// ==================== EXPORT ====================
window.stripeCheckoutClient = stripeCheckoutClient;
