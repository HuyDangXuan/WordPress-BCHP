import crypto from 'node:crypto';

const SANDBOX_CREATE_URL = 'https://sb-openapi.zalopay.vn/v2/create';
const PRODUCTION_CREATE_URL = 'https://openapi.zalopay.vn/v2/create';

function isPlaceholderCredential(value) {
  const normalized = String(value || '').trim().toLowerCase();

  return normalized === ''
    || normalized === 'change-me'
    || normalized.startsWith('demo-');
}

function createHmacSignature(data, key) {
  return crypto.createHmac('sha256', key).update(data).digest('hex');
}

function timingSafeEqualString(expected, provided) {
  const expectedBuffer = Buffer.from(String(expected));
  const providedBuffer = Buffer.from(String(provided));

  if (expectedBuffer.length !== providedBuffer.length) {
    return false;
  }

  return crypto.timingSafeEqual(expectedBuffer, providedBuffer);
}

function formatVietnamDatePrefix(date) {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: '2-digit',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date);

  const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

  return `${values.year}${values.month}${values.day}`;
}

function buildAppTransId(booking, now) {
  return `${formatVietnamDatePrefix(now)}_${booking.wordpress_order_id}`;
}

function buildCreateOrderMacPayload(payload) {
  return [
    payload.app_id,
    payload.app_trans_id,
    payload.app_user,
    payload.amount,
    payload.app_time,
    payload.embed_data,
    payload.item,
  ].join('|');
}

function getCreateOrderUrl(env) {
  const explicitUrl = env.ZALOPAY_CREATE_ORDER_URL;
  if (explicitUrl) {
    return explicitUrl;
  }

  return String(env.ZALOPAY_ENV || 'sandbox').toLowerCase() === 'production'
    ? PRODUCTION_CREATE_URL
    : SANDBOX_CREATE_URL;
}

function buildQrImageUrl(orderUrl) {
  if (! orderUrl) {
    return '';
  }

  return `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(orderUrl)}`;
}

function parseJsonString(value, fallback) {
  if (typeof value !== 'string' || value.trim() === '') {
    return fallback;
  }

  try {
    return JSON.parse(value);
  } catch (error) {
    return fallback;
  }
}

function toIsoFromMilliseconds(value) {
  const timestamp = Number(value || 0);

  if (! Number.isFinite(timestamp) || timestamp <= 0) {
    return null;
  }

  return new Date(timestamp).toISOString();
}

function normalizeCallbackEvent(payload) {
  const dataString = String(payload?.data || '');
  const data = parseJsonString(dataString, {});
  const embedData = parseJsonString(data.embed_data, {});
  const appTransId = String(data.app_trans_id || '');
  const orderIdFromTransId = Number(appTransId.split('_').pop() || 0);
  const wordpressOrderId = Number(embedData.wordpress_order_id || data.wordpress_order_id || orderIdFromTransId);
  const paymentCode = String(embedData.payment_code || data.payment_code || `PMT-${wordpressOrderId}`);
  const providerTransactionId = String(data.zp_trans_id || data.zp_trans_token || appTransId);
  const eventId = String(payload.event_id || `zalopay:${appTransId}:${providerTransactionId}`);

  return {
    eventId,
    idempotencyKey: eventId,
    paymentCode,
    provider: 'zalopay',
    providerTransactionId,
    amount: Math.round(Number(data.amount || payload.amount || 0)),
    currency: String(data.currency || payload.currency || 'VND'),
    wordpressOrderId,
    status: 'paid',
    paidAt: toIsoFromMilliseconds(data.server_time) || toIsoFromMilliseconds(data.app_time),
    rawPayload: payload,
    eventType: 'payment.paid',
  };
}

export function createZaloPayClient(env, fetchImpl = globalThis.fetch, options = {}) {
  const now = options.now ?? (() => new Date());

  return {
    async createPaymentLink(booking) {
      if (
        isPlaceholderCredential(env.ZALOPAY_APP_ID)
        || isPlaceholderCredential(env.ZALOPAY_KEY1)
        || isPlaceholderCredential(env.ZALOPAY_KEY2)
      ) {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      const timestamp = now();
      const amount = Math.round(Number(booking.amount));
      const appTransId = buildAppTransId(booking, timestamp);
      const embedData = {
        wordpress_order_id: Number(booking.wordpress_order_id),
        payment_code: String(booking.payment_code || `PMT-${booking.wordpress_order_id}`),
        redirecturl: String(booking.return_url || ''),
        cancel_url: String(booking.cancel_url || ''),
      };
      const item = JSON.stringify([
        {
          itemid: String(booking.wordpress_order_id),
          itemname: String(booking.tour_name || 'HV Travel tour').slice(0, 120),
          itemprice: amount,
          itemquantity: 1,
        },
      ]);
      const payload = {
        app_id: Number(env.ZALOPAY_APP_ID),
        app_trans_id: appTransId,
        app_user: String(booking.customer_email || booking.customer_phone || booking.customer_name || 'HVTravel'),
        app_time: timestamp.getTime(),
        amount,
        item,
        description: `HV Travel - Thanh toan don hang #${booking.wordpress_order_id}`.slice(0, 256),
        embed_data: JSON.stringify(embedData),
        bank_code: 'zalopayapp',
        callback_url: env.ZALOPAY_CALLBACK_URL,
        currency: String(booking.currency || 'VND'),
        phone: String(booking.customer_phone || ''),
        email: String(booking.customer_email || ''),
      };

      payload.mac = createHmacSignature(buildCreateOrderMacPayload(payload), env.ZALOPAY_KEY1);

      const response = await fetchImpl(getCreateOrderUrl(env), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json().catch(() => null);

      if (! response.ok || result?.return_code !== 1) {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      const orderUrl = result.order_url || result.orderurl || '';

      return {
        provider: 'zalopay',
        checkout_url: orderUrl,
        qr_url: buildQrImageUrl(orderUrl),
        provider_transaction_id: String(result.zp_trans_token || result.order_token || result.zp_trans_id || ''),
      };
    },

    verifyCallbackMac(payload) {
      if (! payload?.data || ! payload?.mac) {
        return false;
      }

      const expectedMac = createHmacSignature(String(payload.data), env.ZALOPAY_KEY2);

      return timingSafeEqualString(expectedMac, payload.mac);
    },

    normalizeCallbackEvent,
  };
}
