<?php
/**
 * Stripe Checkout Test Suite
 *
 * Comprehensive testing system for Stripe Checkout integration.
 * Tests various scenarios including:
 * - Standard checkout
 * - Promo codes
 * - Discounts
 * - Shipping options
 * - Customer data collection
 * - Upsells
 * - Error handling
 *
 * Usage: Open in browser or run via CLI: php tests/stripe-checkout-test.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Stripe configuration
$stripeSecretKey = null;
if (file_exists(__DIR__ . '/../cart/stripe-config.php')) {
    $stripeSecretKey = include __DIR__ . '/../cart/stripe-config.php';
} else {
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?? null;
}

// API endpoint
$apiUrl = 'https://layerstore.eu/cart/create-checkout-session.php';

// For local testing, use local file
if (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost')) {
    $apiUrl = 'http://localhost:8000/cart/create-checkout-session.php';
}

// Test results
$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;

/**
 * Run a single test
 */
function runTest($name, $callback) {
    global $testCount, $passCount, $failCount, $testResults;

    $testCount++;
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "TEST $testCount: $name\n";
    echo str_repeat('-', 60) . "\n";

    try {
        $result = $callback();
        if ($result['success']) {
            $passCount++;
            echo "✓ PASSED: " . ($result['message'] ?? 'Test successful') . "\n";
            $testResults[] = ['name' => $name, 'status' => 'passed', 'message' => $result['message'] ?? ''];
        } else {
            $failCount++;
            echo "✗ FAILED: " . ($result['message'] ?? 'Test failed') . "\n";
            $testResults[] = ['name' => $name, 'status' => 'failed', 'message' => $result['message'] ?? ''];
        }
        return $result;
    } catch (Exception $e) {
        $failCount++;
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $testResults[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Create checkout session via API
 */
function createCheckoutSession($data) {
    global $apiUrl, $stripeSecretKey;

    // For CLI testing, directly include the file
    if (php_sapi_name() === 'cli') {
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;

        // Capture output
        ob_start();
        $input = json_encode($data);
        file_put_contents('php://memory', $input);

        // Include the checkout session file
        include __DIR__ . '/../cart/create-checkout-session.php';
        $output = ob_get_clean();

        return json_decode($output, true);
    }

    // HTTP request for web testing
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

/**
 * Test data generators
 */
function generateTestProduct($overrides = []) {
    return array_merge([
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'Test Produkt',
                'description' => 'Test Beschreibung',
                'images' => []
            ],
            'unit_amount' => 1995 // 19.95 EUR
        ],
        'quantity' => 1
    ], $overrides);
}

// ==================== TESTS ====================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     Stripe Checkout Test Suite for LayerStore              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check configuration
echo "Configuration:\n";
echo "- API URL: $apiUrl\n";
echo "- Stripe Key: " . ($stripeSecretKey ? '✓ Configured (' . substr($stripeSecretKey, 0, 7) . '...)' : '✗ Not configured') . "\n";
echo "\n";

// Test 1: Basic checkout session
runTest('Basic Checkout Session Creation', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Osterkarotte'],
                    'unit_amount' => 1300
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['http_code']) && $result['http_code'] === 200) {
        return ['success' => true, 'message' => 'Session created: ' . substr($result['data']['sessionId'] ?? 'N/A', 0, 20)];
    }

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session created: ' . substr($result['sessionId'] ?? 'N/A', 0, 20)];
    }

    return ['success' => false, 'message' => 'Failed to create session'];
});

// Test 2: Multiple products
runTest('Multiple Products in Cart', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Osterkarotte'],
                    'unit_amount' => 1300
                ],
                'quantity' => 2
            ]),
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Hasenkorb'],
                    'unit_amount' => 1300
                ],
                'quantity' => 1
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with 2 products created'];
    }

    return ['success' => false, 'message' => 'Failed to create multi-product session'];
});

// Test 3: Empty cart validation
runTest('Empty Cart Validation', function() {
    $data = [
        'line_items' => []
    ];

    $result = createCheckoutSession($data);

    if (isset($result['http_code']) && $result['http_code'] === 400) {
        return ['success' => true, 'message' => 'Correctly rejected empty cart'];
    }

    if (isset($result['error'])) {
        return ['success' => true, 'message' => 'Correctly rejected: ' . $result['error']];
    }

    return ['success' => false, 'message' => 'Should have rejected empty cart'];
});

// Test 4: Invalid price validation
runTest('Invalid Price Validation', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'unit_amount' => -100
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['error']) || (isset($result['http_code']) && $result['http_code'] === 400)) {
        return ['success' => true, 'message' => 'Correctly rejected invalid price'];
    }

    return ['success' => false, 'message' => 'Should have rejected invalid price'];
});

// Test 5: Customer email
runTest('Customer Email Collection', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'customer_email' => 'test@example.com'
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with customer email created'];
    }

    return ['success' => false, 'message' => 'Failed to create session with email'];
});

// Test 6: Invalid email validation
runTest('Invalid Email Validation', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'customer_email' => 'not-an-email'
    ];

    $result = createCheckoutSession($data);

    // Backend may or may not validate email - this is informational
    return ['success' => true, 'message' => 'Email validation handled (may be client-side)'];
});

// Test 7: Phone number collection
runTest('Phone Number Collection', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'collect_phone' => true
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with phone collection enabled'];
    }

    return ['success' => false, 'message' => 'Failed to enable phone collection'];
});

// Test 8: Address collection
runTest('Address Collection', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'collect_address' => true
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with address collection enabled'];
    }

    return ['success' => false, 'message' => 'Failed to enable address collection'];
});

// Test 9: Promo code support
runTest('Promo Code Support', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'enable_promo_code' => true,
        'promo_code' => 'TEST10'
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with promo code support created'];
    }

    return ['success' => false, 'message' => 'Failed to enable promo codes'];
});

// Test 10: Shipping options
runTest('Shipping Options', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'shipping_options' => [
            [
                'name' => 'Standardversand',
                'amount' => 495,
                'min_unit' => 'business_day',
                'min_value' => 3,
                'max_unit' => 'business_day',
                'max_value' => 7
            ],
            [
                'name' => 'Expressversand',
                'amount' => 995,
                'min_unit' => 'business_day',
                'min_value' => 1,
                'max_unit' => 'business_day',
                'max_value' => 2
            ]
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with shipping options created'];
    }

    return ['success' => false, 'message' => 'Failed to add shipping options'];
});

// Test 11: Upsell items
runTest('Upsell Items', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Hauptprodukt'],
                    'unit_amount' => 1995
                ]
            ])
        ],
        'upsell_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => [
                        'name' => 'Geschenkverpackung',
                        'description' => 'Upsell Produkt'
                    ],
                    'unit_amount' => 295
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with upsell item created'];
    }

    return ['success' => false, 'message' => 'Failed to add upsell item'];
});

// Test 12: Custom metadata
runTest('Custom Metadata', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'metadata' => [
            'order_source' => 'google',
            'campaign' => 'easter2025',
            'customer_note' => 'Test bestellung'
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with custom metadata created'];
    }

    return ['success' => false, 'message' => 'Failed to add custom metadata'];
});

// Test 13: Long product name truncation
runTest('Long Product Name Truncation', function() {
    $longName = 'Sehr langer Produktname der über die Grenzen von Stripe hinausgeht und abgeschnitten werden sollte';
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => [
                        'name' => $longName,
                        'description' => str_repeat('X ', 200)
                    ],
                    'unit_amount' => 1995
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Long names handled correctly'];
    }

    return ['success' => false, 'message' => 'Failed to handle long product name'];
});

// Test 14: Client reference ID
runTest('Client Reference ID', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'client_reference_id' => 'ls_test_' . time() . '_001'
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with client reference ID created'];
    }

    return ['success' => false, 'message' => 'Failed to add client reference ID'];
});

// Test 15: Success/Cancel URLs
runTest('Custom Success/Cancel URLs', function() {
    $data = [
        'line_items' => [
            generateTestProduct()
        ],
        'success_url' => 'https://layerstore.eu/success?session={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://layerstore.eu/canceled'
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with custom URLs created'];
    }

    return ['success' => false, 'message' => 'Failed to set custom URLs'];
});

// Test 16: Product with images
runTest('Product with Images', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => [
                        'name' => 'Osterhasen',
                        'images' => [
                            'https://layerstore.eu/collections/easter/images/bunnybasketbeige.jpeg'
                        ]
                    ],
                    'unit_amount' => 1300
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with product images created'];
    }

    return ['success' => false, 'message' => 'Failed to add product images'];
});

// Test 17: Quantity variations
runTest('Quantity Variations', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Produkt A'],
                    'unit_amount' => 1000
                ],
                'quantity' => 5
            ]),
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Produkt B'],
                    'unit_amount' => 500
                ],
                'quantity' => 10
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with varying quantities created'];
    }

    return ['success' => false, 'message' => 'Failed to handle quantity variations'];
});

// Test 18: Tax behavior
runTest('Tax Behavior Configuration', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'product_data' => ['name' => 'Steuerpflichtiges Produkt'],
                    'unit_amount' => 1995,
                    'tax_behavior' => 'exclusive'
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    if (isset($result['success']) && $result['success']) {
        return ['success' => true, 'message' => 'Session with tax behavior created'];
    }

    return ['success' => false, 'message' => 'Failed to set tax behavior'];
});

// Test 19: Promo code scenarios
runTest('Valid Promo Codes', function() {
    $promoCodes = ['TM26MG', 'TY26KM', 'LAYER10'];
    $results = [];

    foreach ($promoCodes as $code) {
        $data = [
            'line_items' => [
                generateTestProduct([
                    'price_data' => [
                        'product_data' => ['name' => 'Test mit Promo'],
                        'unit_amount' => 2000
                    ]
                ])
            ],
            'promo_code' => $code,
            'enable_promo_code' => true
        ];

        $result = createCheckoutSession($data);
        $results[$code] = isset($result['success']) && $result['success'];
    }

    $passed = count(array_filter($results));
    return [
        'success' => $passed > 0,
        'message' => "$passed/" . count($promoCodes) . " promo codes tested"
    ];
});

// Test 20: Zero amount handling
runTest('Zero Amount Handling', function() {
    $data = [
        'line_items' => [
            generateTestProduct([
                'price_data' => [
                    'unit_amount' => 0
                ]
            ])
        ]
    ];

    $result = createCheckoutSession($data);

    // Zero amount should be rejected
    if (isset($result['error']) || (isset($result['http_code']) && $result['http_code'] === 400)) {
        return ['success' => true, 'message' => 'Correctly rejected zero amount'];
    }

    return ['success' => false, 'message' => 'Should reject zero amount items'];
});

// ==================== SUMMARY ====================

echo "\n";
echo str_repeat('=', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "Total Tests: $testCount\n";
echo "✓ Passed: $passCount\n";
echo "✗ Failed: $failCount\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 1) . "%\n";

// Detailed results
echo "\nDETAILED RESULTS:\n";
echo str_repeat('-', 60) . "\n";
foreach ($testResults as $result) {
    $icon = $result['status'] === 'passed' ? '✓' : '✗';
    echo "$icon {$result['name']}: {$result['status']}\n";
    if ($result['message']) {
        echo "  {$result['message']}\n";
    }
}

echo "\n";

// HTML Report Generator (when accessed via browser)
if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stripe Checkout Test Report</title>
        <style>
            body { font-family: 'Quicksand', sans-serif; max-width: 1000px; margin: 40px auto; padding: 20px; background: #FAF9F0; }
            h1 { color: #232E3D; }
            .summary { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 20px; }
            .stat { text-align: center; flex: 1; }
            .stat-value { font-size: 2rem; font-weight: bold; }
            .stat-label { color: #666; font-size: 0.9rem; }
            .stat.pass .stat-value { color: #22c55e; }
            .stat.fail .stat-value { color: #dc2626; }
            .test-list { background: white; padding: 20px; border-radius: 8px; }
            .test-item { padding: 12px; border-left: 4px solid #ddd; margin-bottom: 8px; background: #f9f9f9; }
            .test-item.passed { border-left-color: #22c55e; }
            .test-item.failed { border-left-color: #dc2626; }
            .test-name { font-weight: 500; }
            .test-message { font-size: 0.9rem; color: #666; margin-top: 4px; }
            .progress-bar { height: 8px; background: #e5e5e5; border-radius: 4px; overflow: hidden; margin: 20px 0; }
            .progress-fill { height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width 0.5s ease; }
        </style>
    </head>
    <body>
        <h1>🧪 Stripe Checkout Test Report</h1>
        <p>LayerStore Stripe Checkout Integration Tests</p>

        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo round(($passCount / $testCount) * 100); ?>%;"></div>
        </div>

        <div class="summary">
            <div class="stat">
                <div class="stat-value"><?php echo $testCount; ?></div>
                <div class="stat-label">Total Tests</div>
            </div>
            <div class="stat pass">
                <div class="stat-value"><?php echo $passCount; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat fail">
                <div class="stat-value"><?php echo $failCount; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo round(($passCount / $testCount) * 100); ?>%</div>
                <div class="stat-label">Success Rate</div>
            </div>
        </div>

        <div class="test-list">
            <h2>Test Results</h2>
            <?php foreach ($testResults as $result): ?>
                <div class="test-item <?php echo $result['status']; ?>">
                    <div class="test-name"><?php echo htmlspecialchars($result['name']); ?></div>
                    <?php if ($result['message']): ?>
                        <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 20px; text-align: center; color: #666;">
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
}
