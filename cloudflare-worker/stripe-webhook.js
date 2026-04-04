/**
 * Cloudflare Worker für Stripe Webhooks
 * Empängt Stripe Events und sendet E-Mails
 */

// ============================================
// KONFIGURATION
// ============================================

// Stripe Webhook Secret (von Stripe Dashboard)
// TODO: In Cloudflare Workers Secrets als STRIPE_WEBHOOK_SECRET speichern
const STRIPE_WEBHOOK_SECRET = 'whsec_dein_secret_hier';

// E-Mail Service Konfiguration
// Wähle einen: 'mailgun' oder 'sendgrid'
const EMAIL_SERVICE = 'mailgun';

// Mailgun API Key (In Cloudflare als SECRET speichern!)
const MAILGUN_API_KEY = 'YOUR_MAILGUN_API_KEY';
const MAILGUN_DOMAIN = 'mg.layerstore.eu'; // oder deine Domain

// SendGrid API Key (In Cloudflare als SECRET speichern!)
const SENDGRID_API_KEY = 'YOUR_SENDGRID_API_KEY';
const FROM_EMAIL = 'noreply@layerstore.eu';
const TO_EMAIL = 'info@layerstore.eu';

// ============================================
// CLOUDFLARE WORKER
// ============================================

export default {
  async fetch(request, env, ctx) {
    // CORS Preflight
    if (request.method === 'OPTIONS') {
      return new Response(null, {
        headers: {
          'Access-Control-Allow-Origin': '*',
          'Access-Control-Allow-Methods': 'POST, OPTIONS',
          'Access-Control-Allow-Headers': 'Content-Type, Stripe-Signature',
        }
      });
    }

    // Nur POST
    if (request.method !== 'POST') {
      return jsonResponse({ error: 'Method not allowed' }, 405);
    }

    try {
      // Stripe Webhook verifizieren
      const signature = request.headers.get('Stripe-Signature');
      const body = await request.text();

      if (!signature) {
        return jsonResponse({ error: 'No signature' }, 401);
      }

      const event = await verifyStripeSignature(body, signature, env.STRIPE_WEBHOOK_SECRET || STRIPE_WEBHOOK_SECRET);

      if (!event) {
        return jsonResponse({ error: 'Invalid signature' }, 401);
      }

      // Event Log (in Produktion durch echten Logging Service ersetzen)
      console.log('Event received:', event.type);

      // Events verarbeiten
      switch (event.type) {
        case 'checkout.session.completed':
          await handleCheckoutCompleted(event.data.object, env);
          break;

        case 'payment_intent.succeeded':
          await handlePaymentSucceeded(event.data.object, env);
          break;

        case 'payment_intent.payment_failed':
          await handlePaymentFailed(event.data.object, env);
          break;

        default:
          console.log('Unhandled event:', event.type);
      }

      return jsonResponse({ status: 'success', event: event.type });

    } catch (error) {
      console.error('Error:', error);
      return jsonResponse({ error: error.message }, 500);
    }
  }
};

// ============================================
// EVENT HANDLER
// ============================================

async function handleCheckoutCompleted(session, env) {
  const customerEmail = session.customer_details?.email || 'Nicht angegeben';
  const customerName = session.customer_details?.name || 'Kunde';
  const totalAmount = (session.amount_total / 100).toFixed(2) + ' €';
  const orderId = session.id.slice(-8);

  // E-Mail an Shop-Besitzer
  await sendEmail({
    to: env.TO_EMAIL || TO_EMAIL,
    subject: `✅ Neue Bestellung bei LayerStore - #${orderId}`,
    html: shopOrderEmail({
      orderId,
      customerName,
      customerEmail,
      totalAmount,
      sessionId: session.id,
      items: session.metadata?.items || [],
      created: new Date(session.created * 1000).toLocaleString('de-DE')
    }),
    env
  });

  // E-Mail an Kunde (wenn E-Mail vorhanden)
  if (customerEmail && customerEmail !== 'Nicht angegeben') {
    await sendEmail({
      to: customerEmail,
      subject: 'Deine Bestellung bei LayerStore ist erfolgreich! ✅',
      html: customerOrderEmail({
        orderId,
        totalAmount,
        customerName
      }),
      env
    });
  }
}

async function handlePaymentSucceeded(paymentIntent, env) {
  const amount = (paymentIntent.amount / 100).toFixed(2) + ' ' + paymentIntent.currency.toUpperCase();

  await sendEmail({
    to: env.TO_EMAIL || TO_EMAIL,
    subject: '💰 Zahlung erhalten - Stripe',
    html: `
      <h2>Zahlung erfolgreich</h2>
      <p><strong>Amount:</strong> ${amount}</p>
      <p><strong>Payment Intent ID:</strong> ${paymentIntent.id}</p>
      <p><strong>Status:</strong> ${paymentIntent.status}</p>
    `,
    env
  });
}

async function handlePaymentFailed(paymentIntent, env) {
  const amount = (paymentIntent.amount / 100).toFixed(2) + ' ' + paymentIntent.currency.toUpperCase();
  const errorMsg = paymentIntent.last_payment_error?.message || 'Unknown error';

  await sendEmail({
    to: env.TO_EMAIL || TO_EMAIL,
    subject: '❌ Zahlung fehlgeschlagen - Stripe',
    html: `
      <h2>Zahlung fehlgeschlagen</h2>
      <p><strong>Amount:</strong> ${amount}</p>
      <p><strong>Payment Intent ID:</strong> ${paymentIntent.id}</p>
      <p><strong>Error:</strong> ${errorMsg}</p>
    `,
    env
  });
}

// ============================================
// EMAIL TEMPLATES
// ============================================

function shopOrderEmail({ orderId, customerName, customerEmail, totalAmount, sessionId, items, created }) {
  let itemsHtml = '';
  if (items && Array.isArray(items)) {
    itemsHtml = '<h4>Bestellte Artikel:</h4><ul>';
    items.forEach(item => {
      itemsHtml += `<li>${escapeHtml(item.name)} - ${((item.price || 0) / 100).toFixed(2)} € x ${item.quantity || 1}</li>`;
    });
    itemsHtml += '</ul>';
  }

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); color: #F0ECDA; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background: #f9f9f9; }
        .order-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ea580c; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <h1>🎉 Neue Bestellung!</h1>
          <p>Eine neue Bestellung wurde erfolgreich bezahlt.</p>
        </div>
        <div class="content">
          <div class="order-details">
            <h3>👤 Kunde</h3>
            <p><strong>Name:</strong> ${escapeHtml(customerName)}</p>
            <p><strong>Email:</strong> <a href="mailto:${escapeHtml(customerEmail)}">${escapeHtml(customerEmail)}</a></p>
            <h3>💰 Zahlung</h3>
            <p><strong>Gesamtbetrag:</strong> <span style="font-size: 18px; color: #ea580c; font-weight: bold;">${totalAmount}</span></p>
            <p><strong>Bestellnummer:</strong> ${orderId}</p>
            <p><strong>Erstellt:</strong> ${created}</p>
            ${itemsHtml}
            <p style="margin-top: 15px;"><strong>Stripe Session ID:</strong> ${sessionId}</p>
          </div>
        </div>
        <div class="footer">
          <p>Diese E-Mail wurde automatisch vom LayerStore Zahlungssystem generiert.</p>
          <p>© ${new Date().getFullYear()} LayerStore. Alle Rechte vorbehalten.</p>
        </div>
      </div>
    </body>
    </html>
  `;
}

function customerOrderEmail({ orderId, totalAmount, customerName }) {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #232E3D 0%, #3a4a5c 100%); color: #F0ECDA; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background: #f9f9f9; }
        .order-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <h1>Vielen Dank! 🎉</h1>
          <p>Deine Bestellung ist erfolgreich bei uns eingegangen.</p>
        </div>
        <div class="content">
          <p>Hallo ${escapeHtml(customerName)},</p>
          <p>vielen Dank für deine Bestellung bei LayerStore!</p>
          <div class="order-details">
            <h3>Bestellübersicht</h3>
            <p><strong>Bestellnummer:</strong> ${orderId}</p>
            <p><strong>Betrag:</strong> ${totalAmount}</p>
            <p><strong>Zahlungsstatus:</strong> ✅ Bezahlt</p>
          </div>
          <p>Wir werden deine Bestellung schnellstmöglich bearbeiten.</p>
          <p style="margin-top: 20px;">Mit freundlichen Grüßen,<br><strong>Das LayerStore Team</strong></p>
        </div>
        <div class="footer">
          <p>LayerStore - Individuelle 3D-Druck-Kreationen</p>
          <p>© ${new Date().getFullYear()} LayerStore. Alle Rechte vorbehalten.</p>
        </div>
      </div>
    </body>
    </html>
  `;
}

// ============================================
// EMAIL SENDING
// ============================================

async function sendEmail({ to, subject, html, env }) {
  const service = env.EMAIL_SERVICE || EMAIL_SERVICE;
  const fromEmail = env.FROM_EMAIL || FROM_EMAIL;

  if (service === 'mailgun') {
    return sendViaMailgun({ to, subject, html, fromEmail, env });
  } else if (service === 'sendgrid') {
    return sendViaSendGrid({ to, subject, html, fromEmail, env });
  } else {
    throw new Error('Unknown email service: ' + service);
  }
}

async function sendViaMailgun({ to, subject, html, fromEmail, env }) {
  const apiKey = env.MAILGUN_API_KEY || MAILGUN_API_KEY;
  const domain = env.MAILGUN_DOMAIN || MAILGUN_DOMAIN;

  const response = await fetch(`https://api.mailgun.net/v3/${domain}/messages`, {
    method: 'POST',
    headers: {
      'Authorization': 'Basic ' + btoa('api:' + apiKey),
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      from: fromEmail,
      to: to,
      subject: subject,
      html: html
    })
  });

  if (!response.ok) {
    const error = await response.text();
    throw new Error('Mailgun error: ' + error);
  }

  return await response.json();
}

async function sendViaSendGrid({ to, subject, html, fromEmail, env }) {
  const apiKey = env.SENDGRID_API_KEY || SENDGRID_API_KEY;

  const response = await fetch('https://api.sendgrid.com/v3/mail/send', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + apiKey,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      personalizations: [{
        to: [{ email: to }],
        subject: subject
      }],
      from: { email: fromEmail },
      content: [{
        type: 'text/html',
        value: html
      }]
    })
  });

  if (!response.ok) {
    const error = await response.text();
    throw new Error('SendGrid error: ' + error);
  }
}

// ============================================
// STRIPE SIGNATURE VERIFICATION
// ============================================

async function verifyStripeSignature(payload, signature, secret) {
  const elements = signature.split(',');
  let timestamp = null;
  let signatureHash = null;

  for (const element of elements) {
    const [key, value] = element.split('=');
    if (key.trim() === 't') {
      timestamp = value;
    } else if (key.trim() === 'v1') {
      signatureHash = value;
    }
  }

  if (!timestamp || !signatureHash) {
    return null;
  }

  // Check timestamp tolerance (5 minutes)
  const now = Math.floor(Date.now() / 1000);
  if (Math.abs(now - parseInt(timestamp)) > 300) {
    return null;
  }

  // Verify signature
  const signedPayload = timestamp + '.' + payload;
  const expectedSignature = await hmacSha256(signedPayload, secret);

  if (signatureHash !== expectedSignature) {
    return null;
  }

  // Parse and return event
  try {
    return JSON.parse(payload);
  } catch {
    return null;
  }
}

async function hmacSha256(message, secret) {
  const encoder = new TextEncoder();
  const keyData = encoder.encode(secret);
  const messageData = encoder.encode(message);

  const key = await crypto.subtle.importKey(
    'raw',
    keyData,
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign']
  );

  const signature = await crypto.subtle.sign('HMAC', key, messageData);
  const hashArray = Array.from(new Uint8Array(signature));
  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function jsonResponse(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json' }
  });
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}
