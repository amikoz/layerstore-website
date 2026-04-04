<?php
/**
 * Payment Failed Email Template
 * Sent to store owner when a payment fails
 *
 * Variables:
 * - subject: Email subject
 * - amount: Payment amount formatted
 * - payment_intent_id: Stripe Payment Intent ID
 * - error_message: Error message from Stripe
 * - error_code: Error code (optional)
 * - customer_email: Customer email (optional)
 * - store_name: Store name
 * - primary_color: Primary brand color
 * - accent_color: Accent brand color
 * - max_width: Email max width
 * - current_year: Current year
 */
?>
<!DOCTYPE html>
<html lang="de" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <title><?= \LayerStore\Email\TemplateRenderer::e($subject ?? 'Zahlung fehlgeschlagen') ?></title>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f4f4f7; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333333;">
    <!-- Preheader Text (hidden) -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Zahlung fehlgeschlagen | Betrag: <?= \LayerStore\Email\TemplateRenderer::e($amount ?? '') ?> | <?= \LayerStore\Email\TemplateRenderer::e($error_message ?? '') ?>
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <!-- Email Body -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f7;">
        <tr>
            <td style="padding: 40px 20px;">
                <!-- Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: <?= \LayerStore\Email\TemplateRenderer::e($max_width ?? 600) ?>px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 0; background: linear-gradient(135deg, #991b1b 0%, #b91c1c 100%); text-align: center;">
                            <!--[if mso]>
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="<?= $max_width ?? 600 ?>">
                            <tr><td style="padding: 40px 30px;">
                            <![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: <?= \LayerStore\Email\TemplateRenderer::e($max_width ?? 600) ?>px; margin: 0 auto;">
                                <tr>
                                    <td style="padding: 40px 30px; text-align: center;">
                                        <!-- Error Icon -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 15px;">
                                            <tr>
                                                <td style="text-align: center;">
                                                    <div style="width: 60px; height: 60px; background-color: #ffffff; border-radius: 50%; display: inline-block; line-height: 60px; text-align: center; font-size: 36px; color: #dc2626;">
                                                        &#9888;
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 700; color: #ffffff; text-align: center;">
                                            Zahlung fehlgeschlagen
</h1>
                                        <p style="margin: 0; font-size: 16px; color: #fecaca; text-align: center;">
                                            Eine Zahlung konnte nicht verarbeitet werden.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]>
                            </td></tr></table>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 0; background-color: #ffffff;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 30px;">
                                        <!-- Alert Box -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FEF2F2; border: 1px solid #fecaca; border-radius: 6px; border-left: 4px solid #dc2626;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 0; font-size: 14px; color: #991b1b; line-height: 1.6;">
                                                                <strong style="font-size: 15px;">Fehler:</strong><br>
                                                                <?= \LayerStore\Email\TemplateRenderer::e($error_message ?? 'Unbekannter Fehler') ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Payment Details -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <h3 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600; color: #333333;">
                                                        Zahlungsdetails
                                                    </h3>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #666666; width: 140px;">
                                                                <strong>Betrag:</strong>
                                                            </td>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 16px; font-weight: 600; color: #dc2626;">
                                                                <?= \LayerStore\Email\TemplateRenderer::e($amount ?? '') ?>
                                                            </td>
                                                        </tr>
                                                        <?php if (!empty($customer_email)): ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #666666;">
                                                                <strong>Kunde:</strong>
                                                            </td>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px;">
                                                                <a href="mailto:<?= \LayerStore\Email\TemplateRenderer::e($customer_email) ?>" style="color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; text-decoration: none;">
                                                                    <?= \LayerStore\Email\TemplateRenderer::e($customer_email) ?>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($error_code)): ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #666666;">
                                                                <strong>Fehlercode:</strong>
                                                            </td>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #333333;">
                                                                <code style="background-color: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 13px;">
                                                                    <?= \LayerStore\Email\TemplateRenderer::e($error_code) ?>
                                                                </code>
                                                            </td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #666666;">
                                                                <strong>Payment Intent:</strong>
                                                            </td>
                                                            <td style="padding: 8px 0; font-size: 13px; color: #666666; word-break: break-all;">
                                                                <?= \LayerStore\Email\TemplateRenderer::e($payment_intent_id ?? '') ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Next Steps -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0 0 10px 0;">
                                                    <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #333333;">
                                                        Empfohlene Maßnahmen
                                                    </h3>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #333333; line-height: 1.6;">
                                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #666666; border-radius: 50%; margin-right: 10px;">&nbsp;</span>
                                                                Prüfe das Stripe Dashboard für Details
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #333333; line-height: 1.6;">
                                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #666666; border-radius: 50%; margin-right: 10px;">&nbsp;</span>
                                                                Kontaktiere den Kunden, falls erforderlich
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #333333; line-height: 1.6;">
                                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #666666; border-radius: 50%; margin-right: 10px;">&nbsp;</span>
                                                                Biete alternative Zahlungsoptionen an
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Timestamp -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0; font-size: 12px; color: #999999;">
                                                    Gemeldet: <?= date('d.m.Y H:i') ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; background-color: #ffffff; text-align: center;">
                            <!--[if mso]>
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="100%">
                            <tr><td style="text-align: center;">
                            <![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="display: inline-block; margin: 0;">
                                <tr>
                                    <td style="border-radius: 5px; background-color: #dc2626; box-shadow: 0 2px 5px rgba(220, 38, 38, 0.3);">
                                        <a href="https://dashboard.stripe.com/payments/<?= \LayerStore\Email\TemplateRenderer::e($payment_intent_id ?? '') ?>" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 5px;">
                                            In Stripe ansehen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]>
                            </td></tr></table>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center; padding: 10px 0;">
                                        <p style="margin: 0 0 10px 0; font-size: 13px; color: #666666; line-height: 1.5;">
                                            Diese E-Mail wurde automatisch vom <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?> Zahlungssystem generiert.
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #999999;">
                                            &copy; <?= \LayerStore\Email\TemplateRenderer::e($current_year ?? date('Y')) ?> <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?>. Alle Rechte vorbehalten.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!-- End Container -->
            </td>
        </tr>
    </table>
</body>
</html>
