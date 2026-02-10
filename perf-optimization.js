// Performance Optimization Script
(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        optimizeImages();
        setupLazyLoading();
        optimizeFontLoading();
    }

    // Optimize all images
    function optimizeImages() {
        const images = document.querySelectorAll('img');

        images.forEach((img, index) => {
            // Skip if already has loading attribute
            if (img.hasAttribute('loading')) return;

            // Add lazy loading for images below the fold
            if (index > 2) {
                img.setAttribute('loading', 'lazy');
            }

            // Add async decoding for non-critical images
            if (index > 0) {
                img.setAttribute('decoding', 'async');
            }

            // Set fetch priority
            if (index < 2) {
                img.setAttribute('fetchpriority', 'high');
            } else if (index > 5) {
                img.setAttribute('fetchpriority', 'low');
            }

            // Add error handling
            img.addEventListener('error', function() {
                this.style.display = 'none';
                console.warn('Image failed to load:', this.src);
            });
        });
    }

    // Setup Intersection Observer for better lazy loading
    function setupLazyLoading() {
        if (!('IntersectionObserver' in window)) return;

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Optimize font loading
    function optimizeFontLoading() {
        // Ensure fonts are loaded quickly
        if (document.fonts) {
            document.fonts.ready.then(() => {
                document.documentElement.classList.add('fonts-loaded');
            });
        }
    }

    // Debounce function for resize events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Log performance metrics
    window.addEventListener('load', () => {
        setTimeout(() => {
            if (window.performance && performance.getEntriesByType) {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    console.log('Performance Metrics:', {
                        loadTime: Math.round(perfData.loadEventEnd - perfData.fetchStart),
                        domReady: Math.round(perfData.domContentLoadedEventEnd - perfData.fetchStart),
                        firstPaint: perfData.responseStart - perfData.fetchStart
                    });
                }
            }
        }, 0);
    });
})();
