<?php
/**
 * Analytics Konfiguration
 * Trage hier deine Google Analytics 4 und Meta Pixel IDs ein
 */

return [
    'ga4' => [
        'measurement_id' => 'G-XXXXXXXXXX', // GA4 Measurement ID
        'tracking_id' => 'UA-XXXXXXXXX-X',  // Optional: Universal Analytics
        'enabled' => true,
        'anonymize_ip' => true,
        'cookie_domain' => 'auto',
    ],

    'meta_pixel' => [
        'pixel_id' => 'XXXXXXXXXXXXXXXX', // Meta Pixel ID
        'enabled' => true,
    ],

    'server_tracking' => [
        'enabled' => true,
        'events_file' => __DIR__ . '/events.json',
        'retention_days' => 90,
        'max_events' => 10000,
    ],

    'consent' => [
        'version' => '1.0',
        'auto_show_banner' => true,
        'show_after_days' => 365,
    ],

    'ecommerce' => [
        'currency' => 'EUR',
        'tax_rate' => 0.19, // 19% MwSt
        'shipping_cost' => 5.90,
    ],

    'funnel' => [
        'enabled' => true,
        'steps' => ['view', 'add_to_cart', 'checkout', 'purchase'],
    ],

    'reports' => [
        'aov_enabled' => true, // Average Order Value
        'ltv_enabled' => true, // Lifetime Value
        'abandonment_enabled' => true, // Cart Abandonment
        'top_products_enabled' => true,
    ],
];
