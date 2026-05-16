const DEFAULT_QR_BASE_URL = 'https://qr.sepay.vn/img';
const DEFAULT_TRANSACTION_API_BASE_URL = 'https://userapi.sepay.vn/v2';
const PAYMENT_CODE_PATTERN = /PMT[\s\-_]?(\d+)/i;

function isPlaceholderValue(value) {
  const normalized = String(value || '').trim().toLowerCase();

  return normalized === ''
    || normalized === 'change-me'
    || normalized.startsWith('demo-');
}

function normalizeWebhookDate(value) {
  const raw = String(value || '').trim();

  if (raw === '') {
    return null;
  }

  const candidate = raw.includes('T')
    ? raw
    : raw.replace(' ', 'T');
  const timestamp = new Date(candidate);

  return Number.isNaN(timestamp.getTime()) ? null : timestamp.toISOString();
}

function extractPaymentCode(payload) {
  const explicitCode = normalizePaymentCode(payload?.payment_code);

  if (explicitCode !== '') {
    return explicitCode;
  }

  const content = String(payload?.content || '');
  return normalizePaymentCode(content);
}

function extractWordPressOrderId(paymentCode) {
  const matchedOrderId = String(paymentCode || '').match(/(\d+)$/);
  return matchedOrderId ? Number(matchedOrderId[1]) : 0;
}

function normalizePaymentCode(value) {
  const raw = String(value || '').trim().toUpperCase();

  if (raw === '') {
    return '';
  }

  const matchedCode = raw.match(PAYMENT_CODE_PATTERN);

  if (!matchedCode || !matchedCode[1]) {
    return '';
  }

  return `PMT-${matchedCode[1]}`;
}

function buildTransferReference(paymentCode, wordpressOrderId) {
  const normalizedCode = normalizePaymentCode(paymentCode) || `PMT-${wordpressOrderId}`;
  return normalizedCode.replace(/[^A-Z0-9]/g, '');
}

function buildQrUrl(env, booking) {
  const query = new URLSearchParams({
    acc: String(env.SEPAY_ACCOUNT_NUMBER || ''),
    bank: String(env.SEPAY_BANK_CODE || ''),
    amount: String(Math.round(Number(booking.amount || 0))),
    des: buildTransferReference(booking.payment_code, booking.wordpress_order_id),
  });

  return `${env.SEPAY_QR_BASE_URL || DEFAULT_QR_BASE_URL}?${query.toString()}`;
}

function normalizeTransactionAmount(transaction) {
  return Math.round(Number(transaction?.amount_in || 0));
}

function normalizeTransactionStatus(transaction) {
  const transferType = String(transaction?.transfer_type || '').trim().toLowerCase();
  return transferType === 'in' || transferType === 'credit'
    ? 'paid'
    : 'failed';
}

function buildTransactionsApiUrl(env, { paymentCode, amount }) {
  const baseUrl = String(env.SEPAY_API_BASE_URL || DEFAULT_TRANSACTION_API_BASE_URL).replace(/\/+$/, '');
  const url = new URL(`${baseUrl}/transactions`);
  const compactPaymentCode = buildTransferReference(paymentCode, extractWordPressOrderId(paymentCode));

  url.searchParams.set('transaction_content', compactPaymentCode);
  url.searchParams.set('transfer_type', 'in');
  url.searchParams.set('transaction_date_sort', 'desc');
  url.searchParams.set('per_page', '20');
  url.searchParams.set('timestamp_format', 'iso8601');

  if (Number(amount || 0) > 0) {
    const normalizedAmount = String(Math.round(Number(amount || 0)));
    url.searchParams.set('amount_in_min', normalizedAmount);
    url.searchParams.set('amount_in_max', normalizedAmount);
  }

  return url;
}

function normalizeTransactionLookupResult(transaction, paymentCode) {
  const resolvedPaymentCode = normalizePaymentCode(
    transaction?.code
    || transaction?.transaction_content
    || paymentCode
  );
  const providerTransactionId = String(transaction?.reference_number || transaction?.id || resolvedPaymentCode);

  return {
    eventId: `sepay-api:${transaction?.id || providerTransactionId}`,
    idempotencyKey: `sepay-api:${transaction?.id || providerTransactionId}`,
    paymentCode: resolvedPaymentCode,
    provider: 'sepay',
    providerTransactionId,
    amount: normalizeTransactionAmount(transaction),
    currency: 'VND',
    wordpressOrderId: extractWordPressOrderId(resolvedPaymentCode),
    status: normalizeTransactionStatus(transaction),
    paidAt: normalizeWebhookDate(transaction?.transaction_date),
    rawPayload: transaction,
    eventType: normalizeTransactionStatus(transaction) === 'paid'
      ? 'payment.paid'
      : 'payment.failed',
    statusSource: 'sepay-api',
  };
}

function matchesTransaction(env, transaction, { paymentCode, amount }) {
  const normalizedPaymentCode = normalizePaymentCode(paymentCode);
  const transactionPaymentCode = normalizePaymentCode(
    transaction?.code
    || transaction?.transaction_content
  );
  const transferType = String(transaction?.transfer_type || '').trim().toLowerCase();

  if (transferType !== 'in' && transferType !== 'credit') {
    return false;
  }

  if (transactionPaymentCode === '' || transactionPaymentCode !== normalizedPaymentCode) {
    return false;
  }

  if (Number(amount || 0) > 0 && normalizeTransactionAmount(transaction) !== Math.round(Number(amount || 0))) {
    return false;
  }

  if (String(env.SEPAY_ACCOUNT_NUMBER || '').trim() !== '') {
    return String(transaction?.account_number || '').trim() === String(env.SEPAY_ACCOUNT_NUMBER).trim();
  }

  return true;
}

export function createSePayClient(env, fetchImpl = globalThis.fetch) {
  return {
    async createPaymentLink(booking) {
      if (
        isPlaceholderValue(env.SEPAY_BANK_CODE)
        || isPlaceholderValue(env.SEPAY_ACCOUNT_NUMBER)
      ) {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      return {
        provider: 'sepay',
        checkout_url: '',
        qr_url: buildQrUrl(env, booking),
        provider_transaction_id: '',
      };
    },

    canLookupTransactions() {
      return ! isPlaceholderValue(env.SEPAY_API_TOKEN);
    },

    async findIncomingTransferByPaymentCode({ paymentCode, amount }) {
      if (isPlaceholderValue(env.SEPAY_API_TOKEN)) {
        return null;
      }

      const response = await fetchImpl(buildTransactionsApiUrl(env, { paymentCode, amount }), {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${env.SEPAY_API_TOKEN}`,
        },
      });

      if (! response.ok) {
        const error = new Error(`SePay transactions lookup failed with status ${response.status}`);
        error.statusCode = response.status;
        throw error;
      }

      const payload = await response.json().catch(() => ({ status: 'error', data: [] }));
      const transactions = Array.isArray(payload?.data) ? payload.data : [];
      const matchedTransaction = transactions.find((transaction) => matchesTransaction(env, transaction, {
        paymentCode,
        amount,
      }));

      return matchedTransaction
        ? normalizeTransactionLookupResult(matchedTransaction, paymentCode)
        : null;
    },

    verifyWebhookAuthorization(headers = {}) {
      const authorization = headers.authorization ?? headers.Authorization ?? '';
      const expected = `Apikey ${env.SEPAY_API_KEY || ''}`;

      return String(authorization).trim() !== '' && String(authorization).trim() === expected;
    },

    normalizeWebhookEvent(payload) {
      const paymentCode = extractPaymentCode(payload);
      const wordpressOrderId = extractWordPressOrderId(paymentCode);
      const providerTransactionId = String(payload?.transaction_id || payload?.reference_code || paymentCode);
      const eventId = String(payload?.transaction_id || `sepay:${paymentCode}:${payload?.reference_code || 'na'}`);

      return {
        eventId,
        idempotencyKey: eventId,
        paymentCode,
        provider: 'sepay',
        providerTransactionId,
        amount: Math.round(Number(payload?.amount || 0)),
        currency: 'VND',
        wordpressOrderId,
        status: String(payload?.transfer_type || '').trim().toLowerCase() === 'credit'
          ? 'paid'
          : 'failed',
        paidAt: normalizeWebhookDate(payload?.transaction_date),
        rawPayload: payload,
        eventType: String(payload?.transfer_type || '').trim().toLowerCase() === 'credit'
          ? 'payment.paid'
          : 'payment.failed',
      };
    },
  };
}
