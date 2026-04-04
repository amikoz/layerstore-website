<?php
/**
 * Customer Confirmation Email Template
 * Sent to customer after successful payment
 *
 * Variables:
 * - subject: Email subject
 * - customer_name: Customer's name
 * - order_id: Order identifier
 * - total_amount: Total amount formatted
 * - store_url: Store URL
 * - store_name: Store name
 * - logo_url: URL to logo image
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
    <
![endif]-->
    <title><?= \LayerStore\Email\TemplateRenderer::e($subject ?? 'Deine Bestellung ist erfolgreich!') ?></title>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f4f4f7; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333333;">
    <!-- Preheader Text (hidden) -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Vielen Dank fur deine Bestellung! Bestellung <?= \LayerStore\Email\TemplateRenderer::e($order_id ?? '') ?> | Betrag: <?= \LayerStore\Email\TemplateRenderer::e($total_amount ?? '') ?>
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
                        <td style="padding: 0; background: linear-gradient(135deg, <?= \LayerStore\Email\TemplateRenderer::e($primary_color ?? '#232E3D') ?> 0%, #3a4a5c 100%); text-align: center;">
                            <!--[if mso]>
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" width="<?= $max_width ?? 600 ?>">
                            <tr><td style="padding: 40px 30px;">
                            <![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: <?= \LayerStore\Email\TemplateRenderer::e($max_width ?? 600) ?>px; margin: 0 auto;">
                                <tr>
                                    <td style="padding: 40px 30px; text-align: center;">
                                        <!-- Success Icon -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 15px;">
                                            <tr>
                                                <td style="text-align: center;">
                                                    <div style="width: 60px; height: 60px; background-color: #10b981; border-radius: 50%; display: inline-block; line-height: 60px; text-align: center; font-size: 30px; color: #ffffff;">
                                                        &#10003;
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 700; color: #ffffff; text-align: center;">
                                            Vielen Dank!
</h1>
                                        <p style="margin: 0; font-size: 16px; color: #F0ECDA; text-align: center;">
                                            Deine Bestellung ist erfolgreich bei uns eingegangen.
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
                                        <!-- Greeting -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0 0 20px 0;">
                                                    <p style="margin: 0; font-size: 16px; color: #333333;">
                                                        Hallo <strong><?= \LayerStore\Email\TemplateRenderer::e($customer_name ?? '') ?></strong>,
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0 0 25px 0;">
                                                    <p style="margin: 0; font-size: 16px; color: #333333; line-height: 1.7;">
                                                        vielen Dank für deine Bestellung bei <strong><?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?></strong>!
                                                        Wir haben deine Zahlung erfolgreich erhalten und werden deine Bestellung schnellstmöglich bearbeiten.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Order Summary Box -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: linear-gradient(135deg, #FFF9F0 0%, #ffffff 100%); border: 1px solid #e5e7eb; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 0;">
                                                    <!-- Box Header -->
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #fafafa; border-bottom: 1px solid #e5e7eb; border-radius: 8px 8px 0 0;">
                                                        <tr>
                                                            <td style="padding: 15px 20px;">
                                                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #333333;">
                                                                    Bestellübersicht
                                                                </h3>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!-- Box Content -->
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 20px;">
                                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                                    <tr>
                                                                        <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #666666; width: 50%;">
                                                                            <strong>Bestellnummer:</strong>
                                                                        </td>
                                                                        <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333333; text-align: right;">
                                                                            <?= \LayerStore\Email\TemplateRenderer::e($order_id ?? '') ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #666666;">
                                                                            <strong>Betrag:</strong>
                                                                        </td>
                                                                        <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 18px; font-weight: 700; color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; text-align: right;">
                                                                            <?= \LayerStore\Email\TemplateRenderer::e($total_amount ?? '') ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="padding: 8px 0; font-size: 14px; color: #666666;">
                                                                            <strong>Zahlungsstatus:</strong>
                                                                        </td>
                                                                        <td style="padding: 8px 0; font-size: 14px; color: #10b981; font-weight: 600; text-align: right;">
                                                                            &#10003; Bezahlt
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="30" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- What's Next Section -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0 0 10px 0;">
                                                    <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #333333;">
                                                        Was passiert als nächstes?
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
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; color: #ffffff; border-radius: 50%; text-align: center; line-height: 20px; font-size: 12px; font-weight: 700; margin-right: 10px;">1</span>
                                                                Wir bereiten deine Bestellung vor
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #333333; line-height: 1.6;">
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; color: #ffffff; border-radius: 50%; text-align: center; line-height: 20px; font-size: 12px; font-weight: 700; margin-right: 10px;">2</span>
                                                                Du erhältst eine Versandbestätigung per E-Mail
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 8px 0; font-size: 14px; color: #333333; line-height: 1.6;">
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; color: #ffffff; border-radius: 50%; text-align: center; line-height: 20px; font-size: 12px; font-weight: 700; margin-right: 10px;">3</span>
                                                                Deine Bestellung wird zugestellt
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

                                        <!-- Contact Info -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0; font-size: 14px; color: #666666; line-height: 1.6;">
                                                    Falls du Fragen zu deiner Bestellung hast, antworte einfach auf diese E-Mail.
                                                    Wir sind gerne für dich da!
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Spacer -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td height="25" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                        </table>

                                        <!-- Closing -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 0; font-size: 14px; color: #333333;">
                                                    Mit freundlichen Grüßen,<br>
                                                    <strong style="color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>;">Das <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?> Team</strong>
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
                                    <td style="border-radius: 5px; background-color: <?= \LayerStore\Email\TemplateRenderer::e($accent_color ?? '#ea580c') ?>; box-shadow: 0 2px 5px rgba(234, 88, 12, 0.3);">
                                        <a href="<?= \LayerStore\Email\TemplateRenderer::e($store_url ?? 'https://layerstore.eu') ?>" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 5px;">
                                            Shop besuchen
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
                                            <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?> - Individuelle 3D-Druck-Kreationen
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #999999;">
                                            &copy; <?= \LayerStore\Email\TemplateRenderer::e($current_year ?? date('Y')) ?> <?= \LayerStore\Email\TemplateRenderer::e($store_name ?? 'LayerStore') ?>. Alle Rechte vorbehalten.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; padding: 10px 0 0 0;">
                                        <a href="<?= \LayerStore\Email\TemplateRenderer::e($store_url ?? 'https://layerstore.eu') ?>" style="color: #666666; text-decoration: none; font-size: 12px;">
                                            <?= \LayerStore\Email\TemplateRenderer::e($store_url ?? 'https://layerstore.eu') ?>
                                        </a>
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
