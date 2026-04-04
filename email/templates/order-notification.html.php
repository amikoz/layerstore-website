<?php
/**
 * Order Notification Email Template
 * Sent to store owner when a new order is received
 *
 * Variables:
 * - subject: Email subject
 * - order_id: Order identifier
 * - customer_name: Customer's name
 * - customer_email: Customer's email
 * - total_amount: Total amount formatted
 * - created: Order creation date/time
 * - items: Array of order items (optional)
 * - payment_intent: Stripe Payment Intent ID
 * - stripe_url: URL to view payment in Stripe Dashboard
 * - store_url: Store URL
 * - store_name: Store name
 * - logo_url: URL to logo image
 * - primary_color: Primary brand color
 * - accent_color: Accent brand color
 * - max_width: Email max width
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
    <
![endif]-->
    <title><?= \LayerStore\Email\TemplateRenderer::e($subject ?? 'Neue Bestellung'
) ?></title>
    <!--[if mso]>
    <style type="text/css">
        table { border-collapse: collapse; }
        .button-table { border-collapse: separate !important; }
    </style>
    <
![endif]-->
</head>
<body style="margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f4f4f7; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333333;">
    <!-- Preheader Text (hidden) -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Neue Bestellung bei <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?> - Betrag: <?= \LayerStore\Email\TemplateRenderer::e($total_amount ?? '') ?>
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <!-- Email Body -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f7;">
        <tr>
            <td style="padding: 40px 20px;">
                <!-- Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: <?= \LayerStore\Email\TemplateRenderer::e($max_width ?? 600) ?>px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 0; background: linear-gradient(135deg, <?= \LayerStore\Email\TemplateRenderer::e($primary_color ?? '#232E3D') ?> 0%, #3a4a5c 100%); text-align: center;">
                            <!--[if mso]>
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="<?= $max_width ?? 600 ?>">
                            <tr><td style="padding: 40px 30px;">
                            <
![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: <?= \LayerStore\Email\TemplateRenderer::e($max_width ?? 600) ?>px; margin: 0 auto;">
                                <tr>
                                    <td style="padding: 40px 30px; text-align: center;">
                                        <!-- Logo Area -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="text-align: center;">
                                                    <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 700; color: #ffffff; text-align: center;">
                                                        Neue Bestellung!
</h1>
                                                    <p style="margin: 0; font-size: 16px; color: #F0ECDA; text-align: center;">
                                                        Eine neue Bestellung wurde erfolgreich bezahlt.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
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
                                        <!-- Order Info Box -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFF9F0; border-radius: 6px; border-left: 4px solid <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <strong style="color: #333333; font-size: 14px;">Bestellnummer:</strong>
                                                                <span style="color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; font-weight: 700; font-size: 16px;">
                                                                    <?= \LayerStore\Email\TemplateRenderer::e($order_id ?? '') ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 0;">
                                                                <strong style="color: #333333; font-size: 14px;">Erstellt:</strong>
                                                                <span style="color: #666666; font-size: 14px;">
                                                                    <?= \LayerStore\Email\TemplateRenderer::e($created ?? '') ?>
                                                                </span>
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

                                        <!-- Customer Details -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <h3 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600; color: #333333;">
                                                        Kunde
                                                    </h3>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #666666; width: 80px;">
                                                                <strong>Name:</strong>
                                                            </td>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #333333;">
                                                                <?= \LayerStore\Email\TemplateRenderer::e($customer_name ?? '') ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #666666; width: 80px;">
                                                                <strong>E-Mail:</strong>
                                                            </td>
                                                            <td style="padding: 5px 0; font-size: 14px;">
                                                                <a href="mailto:<?= \LayerStore\Email\TemplateRenderer::e($customer_email ?? '') ?>" style="color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; text-decoration: none;">
                                                                    <?= \LayerStore\Email\TemplateRenderer::e($customer_email ?? '') ?>
                                                                </a>
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
                                                        Zahlung
                                                    </h3>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #666666; width: 120px;">
                                                                <strong>Gesamtbetrag:</strong>
                                                            </td>
                                                            <td style="padding: 5px 0; font-size: 22px; font-weight: 700; color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>;">
                                                                <?= \LayerStore\Email\TemplateRenderer::e($total_amount ?? '') ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #666666;">
                                                                <strong>Status:</strong>
                                                            </td>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #10b981; font-weight: 600;">
                                                                Bezahlt
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #666666;">
                                                                <strong>Zahlungsart:</strong>
                                                            </td>
                                                            <td style="padding: 5px 0; font-size: 14px; color: #333333;">
                                                                Kartenzahlung (Stripe)
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <?php if (!empty($items) && is_array($items)): ?>
                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Order Items -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <h3 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600; color: #333333;">
                                                        Bestellte Artikel
                                                    </h3>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #333333;">
                                                                <?= \LayerStore\Email\TemplateRenderer::e($item['name'] ?? 'Produkt') ?>
                                                            </td>
                                                            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #666666; text-align: right;">
                                                                <?= \LayerStore\Email\TemplateRenderer::formatCurrency($item['price'] ?? 0) ?> x <?= (int)($item['quantity'] ?? 1) ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <?php endif; ?>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Technical Details -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0; font-size: 12px; color: #999999;">
                                                    <strong>Stripe Session ID:</strong> <?= \LayerStore\Email\TemplateRenderer::e($payment_intent ?? '') ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <?php if (!empty($stripe_url)): ?>
                    <tr>
                        <td style="padding: 0 30px 30px 30px; background-color: #ffffff; text-align: center;">
                            <!--[if mso]>
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="100%">
                            <tr><td style="text-align: center;">
                            <
![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="display: inline-block; margin: 0;">
                                <tr>
                                    <td style="border-radius: 5px; background-color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; box-shadow: 0 2px 5px rgba(234, 88, 12, 0.3);">
                                        <a href="<?= \LayerStore\Email\TemplateRenderer::e($stripe_url) ?>" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 5px;">
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
                    <?php endif; ?>

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
