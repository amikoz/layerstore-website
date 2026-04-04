/**
 * LayerStore Cookie Consent Manager
 * GDPR-konform mit granularer Kontrolle
 */

(function(window, document) {
    'use strict';

    const CONFIG = {
        CONSENT_VERSION: '1.0',
        CONSENT_KEY: 'layerstore_consent_v1',
        CONSENT_TIMESTAMP: 'layerstore_consent_timestamp',
        SHOW_AFTER_DAYS: 365, // Banner erneut zeigen nach X Tagen

        // Konsent-Kategorien
        CATEGORIES: {
            ESSENTIAL: {
                id: 'essential',
                name: 'Erforderlich',
                description: 'Diese Cookies sind für den Betrieb der Website erforderlich. Dazu gehören LocalStorage für den Warenkorb und Session-Cookies.',
                required: true,
                cookies: ['layerstore_cart', 'layerstore_session']
            },
            ANALYTICS: {
                id: 'analytics',
                name: 'Analytics',
                description: 'Hilft uns zu verstehen, wie Besucher unsere Website nutzen. Wir verwenden Google Analytics 4.',
                required: false,
                cookies: ['_ga', '_gid', '_gat']
            },
            MARKETING: {
                id: 'marketing',
                name: 'Marketing',
                description: 'Wird verwendet, um personalisierte Werbung anzuzeigen. Wir verwenden Meta Pixel.',
                required: false,
                cookies: ['_fbp', 'fr']
            }
        }
    };

    let currentConsent = null;
    let consentCallback = null;

    /**
     * Initialen Konsens laden
     */
    function loadConsent() {
        const stored = localStorage.getItem(CONFIG.CONSENT_KEY);

        if (stored) {
            try {
                const consent = JSON.parse(stored);

                // Prüfen ob Konsent noch gültig ist
                const timestamp = localStorage.getItem(CONFIG.CONSENT_TIMESTAMP);
                const consentDate = timestamp ? new Date(timestamp) : null;
                const now = new Date();

                if (consentDate) {
                    const daysSinceConsent = Math.floor((now - consentDate) / (1000 * 60 * 60 * 24));

                    // Konsent ist abgelaufen
                    if (daysSinceConsent > CONFIG.SHOW_AFTER_DAYS) {
                        clearConsent();
                        return null;
                    }
                }

                currentConsent = consent;
                return consent;
            } catch (e) {
                console.error('Error parsing consent:', e);
            }
        }

        return null;
    }

    /**
     * Konsent speichern
     */
    function saveConsent(consent) {
        currentConsent = consent;
        localStorage.setItem(CONFIG.CONSENT_KEY, JSON.stringify(consent));
        localStorage.setItem(CONFIG.CONSENT_TIMESTAMP, new Date().toISOString());

        // Konsent an Analytics übermitteln
        applyConsent();
    }

    /**
     * Konsent anwenden
     */
    function applyConsent() {
        if (!currentConsent) return;

        // Analytics aktivieren
        if (currentConsent.analytics && window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.grantConsent();
        } else if (window.LayerStoreAnalytics) {
            window.LayerStoreAnalytics.denyConsent();
        }

        // Meta Pixel aktivieren
        if (currentConsent.marketing && window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.grantConsent();
        } else if (window.LayerStoreMetaPixel) {
            window.LayerStoreMetaPixel.denyConsent();
        }

        // Callback aufrufen
        if (consentCallback) {
            consentCallback(currentConsent);
        }
    }

    /**
     * Konsent löschen
     */
    function clearConsent() {
        localStorage.removeItem(CONFIG.CONSENT_KEY);
        localStorage.removeItem(CONFIG.CONSENT_TIMESTAMP);
        currentConsent = null;
    }

    /**
     * Prüfen ob Banner gezeigt werden soll
     */
    function shouldShowBanner() {
        return !loadConsent();
    }

    /**
     * Konsent erteilen (alle)
     */
    function acceptAll() {
        saveConsent({
            version: CONFIG.CONSENT_VERSION,
            essential: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString()
        });

        hideBanner();
    }

    /**
     * Nur erforderliche akzeptieren
     */
    function acceptEssential() {
        saveConsent({
            version: CONFIG.CONSENT_VERSION,
            essential: true,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString()
        });

        hideBanner();
    }

    /**
     * Benutzerdefinierten Konsens speichern
     */
    function saveCustomConsent(settings) {
        saveConsent({
            version: CONFIG.CONSENT_VERSION,
            essential: true, // Immer aktiv
            analytics: settings.analytics || false,
            marketing: settings.marketing || false,
            timestamp: new Date().toISOString()
        });

        hideBanner();
    }

    /**
     * Banner anzeigen
     */
    function showBanner() {
        const banner = document.getElementById('cookieBanner');
        if (banner) {
            banner.classList.add('show');
            document.body.style.paddingBottom = banner.offsetHeight + 'px';
        }
    }

    /**
     * Banner verstecken
     */
    function hideBanner() {
        const banner = document.getElementById('cookieBanner');
        if (banner) {
            banner.classList.remove('show');
            document.body.style.paddingBottom = '0';
        }

        // Modal verstecken
        const modal = document.getElementById('cookieSettingsModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    /**
     * Konsent ändern
     */
    function changeConsent() {
        // Einstellungs-Modal anzeigen
        showSettingsModal();
    }

    /**
     * Einstellungs-Modal anzeigen
     */
    function showSettingsModal() {
        let modal = document.getElementById('cookieSettingsModal');

        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'cookieSettingsModal';
            modal.className = 'cookie-settings-modal';
            document.body.appendChild(modal);
        }

        const consent = loadConsent() || {
            analytics: false,
            marketing: false
        };

        modal.innerHTML = `
            <div class="cookie-settings-content">
                <div class="cookie-settings-header">
                    <h2>Cookie-Einstellungen</h2>
                    <button class="cookie-settings-close" onclick="LayerStoreCookieConsent.closeSettings()">&times;</button>
                </div>
                <div class="cookie-settings-body">
                    ${generateSettingsHTML(consent)}
                </div>
                <div class="cookie-settings-footer">
                    <button class="cookie-btn-secondary" onclick="LayerStoreCookieConsent.closeSettings()">Abbrechen</button>
                    <button class="cookie-btn-primary" onclick="LayerStoreCookieConsent.saveSettings()">Speichern</button>
                </div>
            </div>
        `;

        modal.classList.add('show');
    }

    /**
     * HTML für Einstellungen generieren
     */
    function generateSettingsHTML(consent) {
        return Object.values(CONFIG.CATEGORIES).map(cat => `
            <div class="cookie-setting-item">
                <div class="cookie-setting-info">
                    <h3>${cat.name}</h3>
                    <p>${cat.description}</p>
                </div>
                <label class="cookie-toggle ${cat.required ? 'disabled' : ''}">
                    <input type="checkbox"
                           data-category="${cat.id}"
                           ${cat.required || consent[cat.id] ? 'checked' : ''}
                           ${cat.required ? 'disabled' : ''}>
                    <span class="slider"></span>
                </label>
            </div>
        `).join('');
    }

    /**
     * Einstellungen speichern
     */
    function saveSettings() {
        const checkboxes = document.querySelectorAll('#cookieSettingsModal input[type="checkbox"]');
        const settings = {};

        checkboxes.forEach(cb => {
            settings[cb.dataset.category] = cb.checked;
        });

        saveCustomConsent(settings);
        closeSettings();
    }

    /**
     * Einstellungen schließen
     */
    function closeSettings() {
        const modal = document.getElementById('cookieSettingsModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    /**
     * Callback registrieren
     */
    function onConsentChange(callback) {
        consentCallback = callback;
    }

    /**
     * Aktuellen Konsens abrufen
     */
    function getConsent() {
        return currentConsent || loadConsent();
    }

    /**
     * Prüfen ob Kategorie zugestimmt wurde
     */
    function hasConsent(category) {
        const consent = getConsent();
        return consent && consent[category] === true;
    }

    // API exportieren
    const LayerStoreCookieConsent = {
        init: function() {
            if (shouldShowBanner()) {
                setTimeout(showBanner, 500);
            } else {
                applyConsent();
            }
        },
        acceptAll: acceptAll,
        acceptEssential: acceptEssential,
        changeConsent: changeConsent,
        hasConsent: hasConsent,
        getConsent: getConsent,
        onConsentChange: onConsentChange,
        saveSettings: saveSettings,
        closeSettings: closeSettings
    };

    window.LayerStoreCookieConsent = LayerStoreCookieConsent;

    // Automatisch initialisieren
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', LayerStoreCookieConsent.init);
    } else {
        LayerStoreCookieConsent.init();
    }

})(window, document);
