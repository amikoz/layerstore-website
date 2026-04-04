<?php
/**
 * Email Service Test Script
 * Run this to verify your Resend integration is working
 *
 * Usage: php email/test.php [test-email@example.com]
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ResendEmailService.php';

use LayerStore\Email\ResendEmailService;
use LayerStore\Email\EmailConfig;

// Color output for CLI
function colorOutput(string $text, string $color = 'white'): void
{
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];
    echo $colors[$color] ?? '' . $text . $colors['reset'] . PHP_EOL;
}

// Header
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo colorOutput('  LayerStore Email Service Test', 'blue');
echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;

// Check configuration
echo "Configuration Check:" . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

echo "  Resend API Key: ";
if (!empty(EmailConfig::$resendApiKey)) {
    echo colorOutput('✓ Set (' . substr(EmailConfig::$resendApiKey, 0, 10) . '...)', 'green');
} else {
    echo colorOutput('✗ Not set', 'red');
}

echo "  From Email: " . EmailConfig::$fromEmail . PHP_EOL;
echo "  From Name: " . EmailConfig::$fromName . PHP_EOL;
echo "  Sandbox Mode: " . (EmailConfig::$sandbox ? colorOutput('ENABLED', 'yellow') : 'Disabled') . PHP_EOL;
echo "  Default Recipient: " . EmailConfig::$defaultRecipient . PHP_EOL;
echo PHP_EOL;

// Test email address
$testEmail = $argv[1] ?? EmailConfig::$defaultRecipient;

echo "Sending Test Email to: $testEmail" . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    $email = new ResendEmailService(
        $testEmail,
        'LayerStore Email Service Test'
    );

    // Simple HTML test
    $htmlContent = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: sans-serif; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            .success { color: #10b981; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="success">✓ Email Service Working!</h1>
            <p>This is a test email from LayerStore.</p>
            <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Configuration:</strong></p>
            <ul>
                <li>From: ' . htmlspecialchars(EmailConfig::getFromAddress()) . '</li>
                <li>Sandbox: ' . (EmailConfig::$sandbox ? 'Yes' : 'No') . '</li>
            </ul>
        </div>
    </body>
    </html>';

    $textContent = "LayerStore Email Service Test\n\n" .
                  "This is a test email from LayerStore.\n\n" .
                  "Time: " . date('Y-m-d H:i:s') . "\n" .
                  "From: " . EmailConfig::getFromAddress() . "\n";

    $email->content($htmlContent, $textContent);
    $email->tag('test', 'email_test');

    $result = $email->send();

    echo PHP_EOL;
    if ($result['success']) {
        echo colorOutput('✓ SUCCESS: Email sent!', 'green');
        echo "  Message: " . $result['message'] . PHP_EOL;
        if (!empty($result['id'])) {
            echo "  Email ID: " . $result['id'] . PHP_EOL;
        }
        if (!empty($result['sandbox'])) {
            echo "  " . colorOutput('(Sandbox mode - no real email sent)', 'yellow') . PHP_EOL;
        }
    } else {
        echo colorOutput('✗ FAILED: Email not sent', 'red');
        echo "  Error: " . $result['message'] . PHP_EOL;
        if (!empty($result['error_id'])) {
            echo "  Error ID: " . $result['error_id'] . PHP_EOL;
        }
        if (!empty($result['http_code'])) {
            echo "  HTTP Code: " . $result['http_code'] . PHP_EOL;
        }
    }

} catch (\Throwable $e) {
    echo PHP_EOL . colorOutput('✗ EXCEPTION: ' . $e->getMessage(), 'red') . PHP_EOL;
}

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL . PHP_EOL;

// Show log location
echo "Log file: " . EmailConfig::$logFile . PHP_EOL;
echo PHP_EOL;
