<?php
/**
 * Email Configuration for LayerStore
 * Supports Resend.com API integration
 *
 * @package LayerStore\Email
 */

declare(strict_types=1);

namespace LayerStore\Email;

/**
 * Email Configuration Class
 */
class EmailConfig
{
    /**
     * Resend API Key (from environment variable)
     */
    public static string $resendApiKey;

    /**
     * From email address
     */
    public static string $fromEmail = 'noreply@layerstore.eu';

    /**
     * From name
     */
    public static string $fromName = 'LayerStore';

    /**
     * Reply-to email address
     */
    public static string $replyToEmail = 'info@layerstore.eu';

    /**
     * Sandbox mode - when true, emails are validated but not sent
     */
    public static bool $sandbox = false;

    /**
     * Default recipient for order notifications
     */
    public static string $defaultRecipient = 'info@layerstore.eu';

    /**
     * Log file path
     */
    public static string $logFile = '/tmp/resend_email.log';

    /**
     * Retry configuration
     */
    public static int $maxRetries = 3;
    public static int $retryDelay = 1000; // milliseconds
    public static array $retryStatusCodes = [429, 500, 502, 503, 504];

    /**
     * Resend API endpoint
     */
    public const RESEND_API_URL = 'https://api.resend.com/emails';

    /**
     * Load .env file from project root
     */
    private static function loadEnv(): void
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=VALUE
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Initialize configuration from environment
     */
    public static function init(): void
    {
        // Load .env file first
        self::loadEnv();

        // Load from environment variables
        self::$resendApiKey = $_ENV['RESEND_API_KEY'] ?? '';

        // Parse RESEND_FROM_EMAIL - supports both formats:
        // "email@example.com" or "Name <email@example.com>"
        $fromEmail = $_ENV['RESEND_FROM_EMAIL'] ?? self::$fromEmail;
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $fromEmail, $matches)) {
            // Format: "Name <email@example.com>"
            self::$fromName = trim($matches[1]);
            self::$fromEmail = trim($matches[2]);
        } else {
            // Format: "email@example.com" (simple email)
            self::$fromEmail = $fromEmail;
        }

        self::$fromName = $_ENV['RESEND_FROM_NAME'] ?? self::$fromName;
        self::$replyToEmail = $_ENV['RESEND_TO_EMAIL'] ?? self::$replyToEmail;
        self::$defaultRecipient = $_ENV['RESEND_TO_EMAIL'] ?? self::$defaultRecipient;
        self::$sandbox = filter_var($_ENV['RESEND_SANDBOX_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        self::$logFile = $_ENV['EMAIL_LOG_FILE'] ?? self::$logFile;

        // Validate required configuration
        if (empty(self::$resendApiKey) && !self::$sandbox) {
            self::log('WARNING: RESEND_API_KEY not set in environment');
        }
    }

    /**
     * Log a message to the log file
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;

        // Try to write to log file
        try {
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Fallback to error log if file write fails
            error_log("ResendEmail log failed: " . $e->getMessage());
        }
    }

    /**
     * Check if configuration is valid
     */
    public static function isValid(): bool
    {
        return !empty(self::$resendApiKey) || self::$sandbox;
    }

    /**
     * Get the full from address (Name <email>)
     */
    public static function getFromAddress(): string
    {
        if (empty(self::$fromName)) {
            return self::$fromEmail;
        }
        return sprintf('%s <%s>', self::$fromName, self::$fromEmail);
    }

    /**
     * Validate email address format
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Auto-initialize configuration
EmailConfig::init();
