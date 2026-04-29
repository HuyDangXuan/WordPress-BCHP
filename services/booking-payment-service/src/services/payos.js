import crypto from 'node:crypto';

const API_BASE_URL = 'https://api-merchant.payos.vn';
const ALLOWED_STATUSES = new Set(['pending', 'paid', 'failed', 'expired', 'cancelled']);

function isPlaceholderCredential(value) {
  const normalized = String(value || '').trim().toLowerCase();

  return normalized === ''
    || normalized === 'change-me'
    || normalized.startsWith('demo-');
}

function createHmacSignature(data, checksumKey) {
  return crypto.createHmac('sha256', checksumKey).update(data).digest('hex');
}

function buildDataSignaturePayload(data) {
  return Object.keys(data)
    .sort()
    .map((key) => `${key}=${data[key] ?? ''}`)
    .join('&');
}

function buildRequestSignaturePayload(payload) {
  const signatureSource = {
    amount: payload.amount,
    cancelUrl: payload.cancelUrl,
    description: payload.description,
    orderCode: payload.orderCode,
    returnUrl: payload.returnUrl,
  };

  return buildDataSignaturePayload(signatureSource);
}

function normalizeWebhookStatus(explicitStatus, payload) {
  const normalizedExplicit = String(explicitStatus || '').trim().toLowerCase();
  if (ALLOWED_STATUSES.has(normalizedExplicit)) {
    return normalizedExplicit;
  }

  const providerStatus = String(payload?.data?.status || '').trim().toUpperCase();
  if (providerStatus === 'PAID') {
    return 'paid';
  }
  if (providerStatus === 'PENDING') {
    return 'pending';
  }
  if (providerStatus === 'CANCELLED') {
    return 'cancelled';
  }
  if (providerStatus === 'EXPIRED') {
    return 'expired';
  }
  if (providerStatus === 'FAILED') {
    return 'failed';
  }

  if (payload?.success === true && String(payload?.code || '') === '00') {
    return 'paid';
  }

  const description = `${payload?.desc || ''} ${payload?.data?.desc || ''}`.toLowerCase();
  if (description.includes('cancel')) {
    return 'cancelled';
  }
  if (description.includes('expire')) {
    return 'expired';
  }

  return 'failed';
}

function normalizeWebhookEvent(payload) {
  const data = payload?.data || {};
  const wordpressOrderId = Number(data.orderCode || payload.wordpress_order_id || 0);
  const status = normalizeWebhookStatus(payload.status, payload);
  const providerTransactionId = String(data.paymentLinkId || payload.provider_transaction_id || data.reference || '');
  const paymentCode = String(payload.payment_code || data.paymentCode || `PMT-${wordpressOrderId}`);
  const eventId = String(payload.event_id || data.reference || `${paymentCode}:${providerTransactionId || 'na'}:${status}`);

  return {
    eventId,
    idempotencyKey: eventId,
    paymentCode,
    provider: 'payos',
    providerTransactionId,
    amount: Math.round(Number(data.amount || payload.amount || 0)),
    currency: String(data.currency || payload.currency || 'VND'),
    wordpressOrderId,
    status,
    paidAt: data.transactionDateTime || payload.paid_at || null,
    rawPayload: payload,
    eventType: `payment.${status}`,
  };
}

export function createPayOSClient(env, fetchImpl = globalThis.fetch) {
  const apiBaseUrl = env.PAYOS_API_BASE_URL || API_BASE_URL;

  return {
    async createPaymentLink(booking) {
      if (
        isPlaceholderCredential(env.PAYOS_CLIENT_ID)
        || isPlaceholderCredential(env.PAYOS_API_KEY)
        || isPlaceholderCredential(env.PAYOS_CHECKSUM_KEY)
      ) {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      const payload = {
        orderCode: Number(booking.wordpress_order_id),
        amount: Math.round(Number(booking.amount)),
        description: `HVTRAVEL${booking.wordpress_order_id}`.slice(0, 25),
        buyerName: booking.customer_name,
        buyerEmail: booking.customer_email,
        buyerPhone: booking.customer_phone,
        items: [
          {
            name: String(booking.tour_name).slice(0, 25),
            quantity: 1,
            price: Math.round(Number(booking.amount)),
            unit: 'tour',
          },
        ],
        cancelUrl: booking.cancel_url,
        returnUrl: booking.return_url,
      };

      payload.signature = createHmacSignature(
        buildRequestSignaturePayload(payload),
        env.PAYOS_CHECKSUM_KEY,
      );

      const response = await fetchImpl(`${apiBaseUrl}/v2/payment-requests`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'x-client-id': env.PAYOS_CLIENT_ID,
          'x-api-key': env.PAYOS_API_KEY,
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json().catch(() => null);

      if (! response.ok || result?.code !== '00') {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      const qrCode = result?.data?.qrCode || '';

      return {
        provider: 'payos',
        checkout_url: result?.data?.checkoutUrl || '',
        qr_url: qrCode
          ? `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(qrCode)}`
          : '',
        provider_transaction_id: result?.data?.paymentLinkId || '',
      };
    },

    verifyWebhookSignature(payload) {
      if (! payload?.signature || ! payload?.data) {
        return false;
      }

      const signaturePayload = buildDataSignaturePayload(payload.data);
      const expectedSignature = createHmacSignature(signaturePayload, env.PAYOS_CHECKSUM_KEY);
      const expectedBuffer = Buffer.from(String(expectedSignature));
      const providedBuffer = Buffer.from(String(payload.signature));

      if (expectedBuffer.length !== providedBuffer.length) {
        return false;
      }

      return crypto.timingSafeEqual(expectedBuffer, providedBuffer);
    },

    normalizeWebhookEvent,
  };
}
