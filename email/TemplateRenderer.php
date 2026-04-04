<?php
/**
 * LayerStore Email Template Renderer
 *
 * Professional email template system with:
 * - Table-based layouts for maximum client compatibility
 * - Inline CSS (no external stylesheets)
 * - Automatic plain-text generation
 * - Variable interpolation with security escaping
 * - LayerStore branding
 *
 * @author LayerStore
 * @version 1.0.0
 */

declare(strict_types=1);

namespace LayerStore\Email;

class TemplateRenderer
{
    // Brand Colors
    public const COLOR_PRIMARY = '#232E3D';
    public const COLOR_ACCENT = '#ea580c';
    public const COLOR_BG = '#FAF9F0';
    public const COLOR_TEXT = '#333333';
    public const COLOR_TEXT_LIGHT = '#666666';
    public const COLOR_BORDER = '#e5e7eb';
    public const COLOR_SUCCESS = '#10b981';
    public const COLOR_ERROR = '#ef4444';

    // Email Config
    public const MAX_WIDTH = 600;
    public const FROM_EMAIL = 'noreply@layerstore.eu';
    public const FROM_NAME = 'LayerStore';
    public const REPLY_TO = 'info@layerstore.eu';
    public const STORE_URL = 'https://layerstore.eu';

    private string $templatesDir;
    private array $globalVars = [];

    /**
     * Constructor
     *
     * @param string|null $templatesDir Path to templates directory
     */
    public function __construct(?string $templatesDir = null)
    {
        $this->templatesDir = $templatesDir ?? __DIR__ . '/templates';
    }

    /**
     * Set global variables available to all templates
     *
     * @param array $vars Key-value pairs
     * @return self
     */
    public function setGlobalVars(array $vars): self
    {
        $this->globalVars = $vars;
        return $this;
    }

    /**
     * Render an email template
     *
     * @param string $template Template name (without extension)
     * @param array $vars Variables to interpolate
     * @return array ['html' => string, 'text' => string, 'subject' => string]
     * @throws \RuntimeException If template not found
     */
    public function render(string $template, array $vars = []): array
    {
        $templatePath = $this->templatesDir . '/' . $template . '.html.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Merge global and local variables
        $vars = array_merge($this->getDefaults(), $this->globalVars, $vars);

        // Extract variables for template use
        extract($vars, EXTR_SKIP);

        // Capture HTML output
        ob_start();
        require $templatePath;
        $html = ob_get_clean();

        // Generate plain text version
        $text = $this->generatePlainText($html);

        // Extract subject if present
        $subject = $vars['subject'] ?? 'LayerStore Notification';

        return [
            'html' => $html,
            'text' => $text,
            'subject' => $subject
        ];
    }

    /**
     * Render and send email
     *
     * @param string $to Recipient email
     * @param string $template Template name
     * @param array $vars Template variables
     * @param array $options Additional options (priority, reply_to, etc.)
     * @return bool Whether email was sent successfully
     */
    public function send(string $to, string $template, array $vars = [], array $options = []): bool
    {
        $rendered = $this->render($template, $vars);

        $headers = $this->buildHeaders($to, $options);
        $subject = $this->escapeHeader($rendered['subject']);

        // Send multipart email
        $boundary = 'boundary_' . md5(uniqid((string) random_int(1, PHP_INT_MAX)));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = $this->buildMultipartBody($boundary, $rendered);

        $result = mail($to, $subject, $body, implode("\r\n", $headers));

        $this->logEmail($to, $template, $result);

        return $result;
    }

    /**
     * Send order notification to store owner
     *
     * @param array $data Order data
     * @return bool
     */
    public function sendOrderNotification(array $data): bool
    {
        $vars = [
            'subject' => 'Neue Bestellung bei LayerStore - #' . ($data['order_id'] ?? '???'),
            'order_id' => $data['order_id'] ?? '',
            'customer_name' => $data['customer_name'] ?? 'Kunde',
            'customer_email' => $data['customer_email'] ?? '',
            'total_amount' => $data['total_amount'] ?? '',
            'created' => $data['created'] ?? date('d.m.Y H:i'),
            'items' => $data['items'] ?? [],
            'payment_intent' => $data['payment_intent'] ?? '',
            'stripe_url' => $data['stripe_url'] ?? ''
        ];

        $options = [
            'priority' => true // High priority for new orders
        ];

        return $this->send('info@layerstore.eu', 'order-notification', $vars, $options);
    }

    /**
     * Send confirmation email to customer
     *
     * @param array $data Order data
     * @return bool
     */
    public function sendCustomerConfirmation(array $data): bool
    {
        $vars = [
            'subject' => 'Deine Bestellung bei LayerStore ist erfolgreich!',
            'customer_name' => $data['customer_name'] ?? 'Kunde',
            'order_id' => $data['order_id'] ?? '',
            'total_amount' => $data['total_amount'] ?? ''
        ];

        return $this->send($data['customer_email'], 'customer-confirmation', $vars);
    }

    /**
     * Send payment failed notification
     *
     * @param array $data Payment data
     * @return bool
     */
    public function sendPaymentFailed(array $data): bool
    {
        $vars = [
            'subject' => 'Zahlung fehlgeschlagen - LayerStore',
            'amount' => $data['amount'] ?? '',
            'payment_intent_id' => $data['payment_intent_id'] ?? '',
            'error_message' => $data['error_message'] ?? 'Unbekannter Fehler'
        ];

        $options = [
            'priority' => true
        ];

        return $this->send('info@layerstore.eu', 'payment-failed', $vars, $options);
    }

    /**
     * Get default template variables
     *
     * @return array
     */
    private function getDefaults(): array
    {
        return [
            'store_url' => self::STORE_URL,
            'store_name' => 'LayerStore',
            'current_year' => date('Y'),
            'logo_url' => self::STORE_URL . '/logo.png',
            'primary_color' => self::COLOR_PRIMARY,
            'accent_color' => self::COLOR_ACCENT,
            'bg_color' => self::COLOR_BG,
            'max_width' => self::MAX_WIDTH
        ];
    }

    /**
     * Build email headers
     *
     * @param string $to Recipient email
     * @param array $options Additional options
     * @return array
     */
    private function buildHeaders(string $to, array $options): array
    {
        $headers = [
            'MIME-Version: 1.0',
            'Date: ' . date('r'),
            'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . '>',
            'Reply-To: ' . self::REPLY_TO,
            'Return-Path: ' . self::FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion(),
            'X-Sender: ' . self::FROM_EMAIL,
            'X-Priority: ' . ($options['priority'] ?? false ? '1' : '3'),
            'X-MSMail-Priority: ' . ($options['priority'] ?? false ? 'High' : 'Normal')
        ];

        if (!empty($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . $options['reply_to'];
        }

        return $headers;
    }

    /**
     * Build multipart email body
     *
     * @param string $boundary MIME boundary
     * @param array $rendered Rendered template
     * @return string
     */
    private function buildMultipartBody(string $boundary, array $rendered): string
    {
        $body = '';

        // Plain text version
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $rendered['text'] . "\r\n\r\n";

        // HTML version
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $rendered['html'] . "\r\n\r\n";

        $body .= "--{$boundary}--";

        return $body;
    }

    /**
     * Escape header value to prevent header injection
     *
     * @param string $value
     * @return string
     */
    private function escapeHeader(string $value): string
    {
        return preg_replace('/[\r\n]+/', ' ', $value);
    }

    /**
     * Generate plain text from HTML
     *
     * @param string $html
     * @return string
     */
    private function generatePlainText(string $html): string
    {
        // Remove CSS styles
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Remove head, script, style
        $text = preg_replace('/<(head|script|style)[^>]*>.*?<\/\\1>/is', '', $text);

        // Convert tables to structured text
        $text = preg_replace('/<table[^>]*>/i', '', $text);
        $text = preg_replace('/<\/table>/i', '', $text);
        $text = preg_replace('/<tr[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/tr>/i', '', $text);
        $text = preg_replace('/<td[^>]*>/i', "  ", $text);
        $text = preg_replace('/<\/td>/i', "\n", $text);

        // Convert headings
        $text = preg_replace('/<h[1-6][^>]*>/i', "\n\n", $text);
        $text = preg_replace('/<\/h[1-6]>/i', ":\n", $text);

        // Convert paragraphs
        $text = preg_replace('/<p[^>]*>/i', "\n\n", $text);
        $text = preg_replace('/<\/p>/i', "\n", $text);

        // Convert divs
        $text = preg_replace('/<div[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/div>/i', "\n", $text);

        // Convert line breaks
        $text = preg_replace('/<br[^>]*>/i', "\n", $text);

        // Convert bold/strong
        $text = preg_replace('/<(strong|b)[^>]*>/i', '*', $text);
        $text = preg_replace('/<\/(strong|b)>/i', '*', $text);

        // Remove all remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = trim($text);

        // Add header
        $text = "LayerStore\n" . str_repeat('=', 20) . "\n\n" . $text;

        // Add footer
        $text .= "\n\n" . str_repeat('-', 20) . "\n";
        $text .= "© " . date('Y') . " LayerStore. Alle Rechte vorbehalten.\n";
        $text .= self::STORE_URL . "\n";

        return $text;
    }

    /**
     * Log email sending attempt
     *
     * @param string $to Recipient email
     * @param string $template Template name
     * @param bool $success Whether sending was successful
     * @return void
     */
    private function logEmail(string $to, string $template, bool $success): void
    {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/email.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $success ? 'SENT' : 'FAILED';

        $message = "[{$timestamp}] {$status}: Template={$template}, To={$to}\n";

        @file_put_contents($logFile, $message, FILE_APPEND);
    }

    /**
     * Escape HTML output
     *
     * @param string|null $text
     * @return string
     */
    public static function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format currency amount
     *
     * @param float|int $amount Amount in cents
     * @param string $currency Currency code
     * @return string
     */
    public static function formatCurrency(float|int $amount, string $currency = 'EUR'): string
    {
        $amount = (float) $amount / 100;
        return number_format($amount, 2, ',', '.') . ' €';
    }

    /**
     * Format date/time
     *
     * @param int|string $timestamp Unix timestamp or date string
     * @return string
     */
    public static function formatDate(int|string $timestamp): string
    {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        return date('d.m.Y H:i', $timestamp);
    }
}
