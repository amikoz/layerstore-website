<?php
/**
 * Resend Email Service for LayerStore
 * Production-ready email service with retry logic and error handling
 *
 * @package LayerStore\Email
 */

declare(strict_types=1);

namespace LayerStore\Email;

require_once __DIR__ . '/config.php';

use Closure;

/**
 * Resend Email Service Class
 *
 * Features:
 * - Resend API integration with cURL
 * - HTML and plain text support
 * - Template rendering
 * - Automatic retry with exponential backoff
 * - Comprehensive error handling
 * - Structured logging
 */
class ResendEmailService
{
    private array $errors = [];
    private array $attachments = [];
    private array $tags = [];
    private ?string $htmlContent = null;
    private ?string $textContent = null;

    /**
     * Email data
     */
    private string $to;
    private string $subject;
    private ?string $replyTo = null;
    private array $cc = [];
    private array $bcc = [];

    /**
     * Constructor
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     */
    public function __construct(string $to, string $subject)
    {
        if (!EmailConfig::validateEmail($to)) {
            throw new \InvalidArgumentException("Invalid recipient email: $to");
        }

        $this->to = $to;
        $this->subject = $subject;

        EmailConfig::log("Email instance created - To: $to, Subject: $subject");
    }

    /**
     * Set HTML content
     *
     * @param string $html HTML email content
     * @return self
     */
    public function html(string $html): self
    {
        $this->htmlContent = $html;
        return $this;
    }

    /**
     * Set plain text content
     *
     * @param string $text Plain text email content
     * @return self
     */
    public function text(string $text): self
    {
        $this->textContent = $text;
        return $this;
    }

    /**
     * Set both HTML and text content
     *
     * @param string $html HTML content
     * @param string $text Plain text content
     * @return self
     */
    public function content(string $html, string $text): self
    {
        $this->htmlContent = $html;
        $this->textContent = $text;
        return $this;
    }

    /**
     * Render a template with data
     *
     * @param string $template Template name or file path
     * @param array $data Data to inject into template
     * @return string Rendered content
     */
    public function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = $this->resolveTemplatePath($template);

        if (!file_exists($templatePath)) {
            EmailConfig::log("Template not found: $templatePath", 'ERROR');
            throw new \RuntimeException("Template not found: $template");
        }

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            include $templatePath;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            EmailConfig::log("Template render error: " . $e->getMessage(), 'ERROR');
            throw new \RuntimeException("Template render failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve template path
     */
    private function resolveTemplatePath(string $template): string
    {
        // If it's an absolute path, use it directly
        if (str_starts_with($template, '/') || str_starts_with($template, '.')) {
            return $template;
        }

        // Look in templates directory
        $templateDir = __DIR__ . '/templates';
        $path = $templateDir . '/' . ltrim($template, '/');

        // Try with .php extension if not present
        if (!pathinfo($path, PATHINFO_EXTENSION)) {
            $path .= '.php';
        }

        return $path;
    }

    /**
     * Set reply-to address
     *
     * @param string $email Reply-to email address
     * @return self
     */
    public function replyTo(string $email): self
    {
        if (!EmailConfig::validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid reply-to email: $email");
        }
        $this->replyTo = $email;
        return $this;
    }

    /**
     * Add CC recipient
     *
     * @param string $email CC email address
     * @return self
     */
    public function addCc(string $email): self
    {
        if (!EmailConfig::validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid CC email: $email");
        }
        $this->cc[] = $email;
        return $this;
    }

    /**
     * Add BCC recipient
     *
     * @param string $email BCC email address
     * @return self
     */
    public function addBcc(string $email): self
    {
        if (!EmailConfig::validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid BCC email: $email");
        }
        $this->bcc[] = $email;
        return $this;
    }

    /**
     * Add an attachment
     *
     * @param string $content Attachment content (base64 encoded)
     * @param string $filename Filename for the attachment
     * @param string $contentType MIME type
     * @return self
     */
    public function attach(string $content, string $filename, string $contentType = 'application/octet-stream'): self
    {
        $this->attachments[] = [
            'content' => $content,
            'filename' => $filename,
            'type' => $contentType,
        ];
        return $this;
    }

    /**
     * Attach a file from path
     *
     * @param string $filePath Path to the file
     * @param string|null $filename Optional custom filename
     * @return self
     */
    public function attachFile(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }

        $content = base64_encode(file_get_contents($filePath));
        $filename = $filename ?? basename($filePath);

        return $this->attach($content, $filename, mime_content_type($filePath) ?: 'application/octet-stream');
    }

    /**
     * Add a tag for tracking
     *
     * @param string $name Tag name
     * @param string $value Tag value
     * @return self
     */
    public function tag(string $name, string $value): self
    {
        $this->tags[] = ['name' => $name, 'value' => $value];
        return $this;
    }

    /**
     * Send the email
     *
     * @return array Response data with 'success' boolean and 'message' string
     */
    public function send(): array
    {
        // Validate content
        if (empty($this->htmlContent) && empty($this->textContent)) {
            EmailConfig::log("Email send failed: No content set", 'ERROR');
            return [
                'success' => false,
                'message' => 'Email content is required (HTML or text)',
                'error_id' => 'no_content'
            ];
        }

        // Check sandbox mode
        if (EmailConfig::$sandbox) {
            EmailConfig::log("SANDBOX MODE: Email would be sent to: $this->to", 'INFO');
            return [
                'success' => true,
                'message' => 'Email simulated in sandbox mode',
                'sandbox' => true
            ];
        }

        // Check API key
        if (empty(EmailConfig::$resendApiKey)) {
            EmailConfig::log("Email send failed: No API key configured", 'ERROR');
            return [
                'success' => false,
                'message' => 'Resend API key not configured',
                'error_id' => 'no_api_key'
            ];
        }

        // Build request payload
        $payload = $this->buildPayload();

        // Send with retry logic
        return $this->sendWithRetry($payload);
    }

    /**
     * Build the API request payload
     */
    private function buildPayload(): array
    {
        $payload = [
            'from' => EmailConfig::getFromAddress(),
            'to' => [$this->to],
            'subject' => $this->subject,
        ];

        // Add content
        if (!empty($this->htmlContent)) {
            $payload['html'] = $this->htmlContent;
        }
        if (!empty($this->textContent)) {
            $payload['text'] = $this->textContent;
        }

        // Add reply-to
        if (!empty($this->replyTo)) {
            $payload['reply_to'] = [$this->replyTo];
        }

        // Add CC
        if (!empty($this->cc)) {
            $payload['cc'] = $this->cc;
        }

        // Add BCC
        if (!empty($this->bcc)) {
            $payload['bcc'] = $this->bcc;
        }

        // Add attachments
        if (!empty($this->attachments)) {
            $payload['attachments'] = $this->attachments;
        }

        // Add tags
        if (!empty($this->tags)) {
            $payload['tags'] = $this->tags;
        }

        return $payload;
    }

    /**
     * Send with retry logic using exponential backoff
     */
    private function sendWithRetry(array $payload): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < EmailConfig::$maxRetries) {
            $attempt++;

            try {
                EmailConfig::log("Sending email attempt $attempt/$this->to", 'INFO');

                $response = $this->makeApiRequest($payload);

                if ($response['success']) {
                    EmailConfig::log("Email sent successfully to: $this->to, ID: " . ($response['id'] ?? 'unknown'), 'INFO');
                    return $response;
                }

                // Check if we should retry
                if ($response['should_retry'] ?? false) {
                    $lastError = $response;
                    $delay = EmailConfig::$retryDelay * (2 ** ($attempt - 1)); // Exponential backoff
                    EmailConfig::log("Retryable error, waiting {$delay}ms before attempt " . ($attempt + 1), 'WARNING');
                    usleep($delay * 1000);
                    continue;
                }

                // Non-retryable error
                return $response;

            } catch (\Throwable $e) {
                $lastError = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_id' => 'exception'
                ];
                EmailConfig::log("Exception during send: " . $e->getMessage(), 'ERROR');

                if ($attempt < EmailConfig::$maxRetries) {
                    $delay = EmailConfig::$retryDelay * (2 ** ($attempt - 1));
                    usleep($delay * 1000);
                }
            }
        }

        // All retries exhausted
        EmailConfig::log("All retries exhausted for: $this->to", 'ERROR');
        return [
            'success' => false,
            'message' => 'Failed after ' . EmailConfig::$maxRetries . ' attempts: ' . ($lastError['message'] ?? 'Unknown error'),
            'error_id' => 'max_retries_exceeded',
            'attempts' => $attempt
        ];
    }

    /**
     * Make the actual API request using cURL
     */
    private function makeApiRequest(array $payload): array
    {
        $ch = curl_init(EmailConfig::RESEND_API_URL);

        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        $jsonPayload = json_encode($payload);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . EmailConfig::$resendApiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        curl_close($ch);

        // Log request details
        EmailConfig::log("API Request - URL: " . EmailConfig::RESEND_API_URL . ", HTTP Code: $httpCode", 'DEBUG');

        // Check for cURL errors
        if ($curlErrno !== 0) {
            EmailConfig::log("cURL error: [$curlErrno] $curlError", 'ERROR');
            return [
                'success' => false,
                'message' => "Connection error: $curlError",
                'error_id' => 'curl_error',
                'should_retry' => in_array($curlErrno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT])
            ];
        }

        // Parse response
        $responseData = json_decode($responseBody, true);
        $isJson = json_last_error() === JSON_ERROR_NONE;

        // Success (200 OK or 202 Accepted)
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'id' => $isJson ? ($responseData['id'] ?? null) : null,
                'data' => $responseData
            ];
        }

        // Handle error responses
        $errorMessage = $isJson ? ($responseData['message'] ?? 'Unknown error') : $responseBody;
        $errorCode = $isJson ? ($responseData['errorCode'] ?? null) : null;

        EmailConfig::log("API error - HTTP $httpCode: $errorMessage", 'ERROR');

        return [
            'success' => false,
            'message' => $errorMessage,
            'error_code' => $errorCode,
            'http_code' => $httpCode,
            'should_retry' => in_array($httpCode, EmailConfig::$retryStatusCodes)
        ];
    }

    /**
     * Get all errors that occurred
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Static helper to send a simple email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $content HTML content
     * @param string|null $textContent Plain text content (optional)
     * @return array
     */
    public static function quickSend(string $to, string $subject, string $content, ?string $textContent = null): array
    {
        $email = new self($to, $subject);
        $email->html($content);
        if ($textContent !== null) {
            $email->text($textContent);
        }
        return $email->send();
    }
}

/**
 * Helper function for quick email sending
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $html HTML content
 * @param string|null $text Plain text content (optional)
 * @return array
 */
function send_email(string $to, string $subject, string $html, ?string $text = null): array
{
    return ResendEmailService::quickSend($to, $subject, $html, $text);
}
