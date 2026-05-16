import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import http from 'node:http';
import crypto from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { createServer } from '../src/app.js';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

async function readRepoText(relativePath) {
  return await fs.readFile(path.join(REPO_ROOT, relativePath), 'utf8');
}

function baseEnv() {
  return {
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
    SEPAY_API_KEY: 'demo-sepay-api-key',
    SEPAY_BANK_CODE: 'Vietcombank',
    SEPAY_ACCOUNT_NUMBER: '0010000000355',
    SEPAY_ACCOUNT_NAME: 'HV Travel Demo',
    ZALOPAY_APP_ID: '554',
    ZALOPAY_KEY1: '8NdU5pG5R2spGHGhyO99HN1OhD8IQJBn',
    ZALOPAY_KEY2: 'uUfsWgfLkRLzq6W2uNXTCxrfxs51auny',
    ZALOPAY_ENV: 'sandbox',
    ZALOPAY_CALLBACK_URL: 'http://localhost:8787/api/payments/zalopay/callback',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
  };
}

function createMemoryStore() {
  const bookings = new Map();
  const payments = new Map();
  const paymentEvents = new Map();

  return {
    async upsertBooking(booking) {
      bookings.set(String(booking.wordpress_order_id), { ...booking });
      return { ...booking };
    },
    async getBookingByOrderId(wordpressOrderId) {
      return bookings.get(String(wordpressOrderId)) ?? null;
    },
    async listBookings() {
      return Array.from(bookings.values()).map((booking) => ({ ...booking }));
    },
    async upsertPayment(payment) {
      payments.set(String(payment.payment_code), { ...payment });
      return { ...payment };
    },
    async getPaymentByCode(paymentCode) {
      return payments.get(String(paymentCode)) ?? null;
    },
    async getPaymentByOrderId(wordpressOrderId) {
      return Array.from(payments.values()).find(
        (payment) => Number(payment.wordpress_order_id) === Number(wordpressOrderId),
      ) ?? null;
    },
    async insertPaymentEvent(paymentEvent) {
      const id = String(paymentEvent.idempotency_key);
      if (paymentEvents.has(id)) {
        const duplicateError = new Error('Duplicate payment event');
        duplicateError.code = 'DUPLICATE_EVENT';
        throw duplicateError;
      }

      paymentEvents.set(id, { ...paymentEvent });

      return { ...paymentEvent };
    },
    async listPaymentEvents() {
      return Array.from(paymentEvents.values()).map((paymentEvent) => ({ ...paymentEvent }));
    },
  };
}

function createPayOSSignedWebhook({
  orderCode = 1024,
  amount = 12990000,
  description = 'HVTRAVEL1024',
  accountNumber = '123456789',
  reference = 'FT123456789',
  transactionDateTime = '2026-04-27T15:30:00Z',
  paymentLinkId = 'plink_1024',
  code = '00',
  desc = 'success',
  status = 'PAID',
  key = 'demo-checksum-key',
} = {}) {
  const data = {
    orderCode,
    amount,
    description,
    accountNumber,
    reference,
    transactionDateTime,
    paymentLinkId,
    code,
    desc,
    status,
  };

  const sortedKeys = Object.keys(data).sort();
  const signaturePayload = sortedKeys
    .map((property) => `${property}=${data[property] ?? ''}`)
    .join('&');

  const signature = crypto
    .createHmac('sha256', key)
    .update(signaturePayload)
    .digest('hex');

  return {
    code: '00',
    desc: 'success',
    success: true,
    signature,
    data,
  };
}

function createZaloPaySignedCallback({
  appTransId = '260504_1024',
  amount = 12990000,
  appId = 554,
  appTime = 1777860000000,
  appUser = 'a@example.com',
  zpTransId = 250504000001,
  serverTime = 1777860005000,
  key = 'uUfsWgfLkRLzq6W2uNXTCxrfxs51auny',
  wordpressOrderId = 1024,
  paymentCode = 'PMT-1024',
} = {}) {
  const data = JSON.stringify({
    app_id: appId,
    app_trans_id: appTransId,
    app_time: appTime,
    app_user: appUser,
    amount,
    embed_data: JSON.stringify({
      wordpress_order_id: wordpressOrderId,
      payment_code: paymentCode,
    }),
    item: '[]',
    zp_trans_id: zpTransId,
    server_time: serverTime,
    channel: 38,
  });

  const mac = crypto
    .createHmac('sha256', key)
    .update(data)
    .digest('hex');

  return {
    data,
    mac,
    type: 1,
  };
}

function createSePayWebhook({
  gateway = 'Vietcombank',
  transactionDate = '2026-05-15 11:08:33',
  accountNumber = '0010000000355',
  bankAccountXid = 'ba_demo_001',
  paymentCode = 'PMT-1024',
  content = 'PMT-1024 chuyen tien',
  transferType = 'credit',
  amount = 12990000,
  referenceCode = 'FT24012345678',
  transactionId = 'txn_1024',
} = {}) {
  return {
    gateway,
    transaction_date: transactionDate,
    account_number: accountNumber,
    bank_account_xid: bankAccountXid,
    va: null,
    payment_code: paymentCode,
    content,
    transfer_type: transferType,
    amount,
    reference_code: referenceCode,
    accumulated: 0,
    transaction_id: transactionId,
  };
}

async function request(server, { method, path, body, headers }) {
  const address = server.address();
  const payload = body ? JSON.stringify(body) : null;
  const requestHeaders = {
    ...(headers ?? {}),
  };

  if (payload) {
    requestHeaders['Content-Type'] = 'application/json';
    requestHeaders['Content-Length'] = Buffer.byteLength(payload);
  }

  return await new Promise((resolve, reject) => {
    const req = http.request(
      {
        hostname: '127.0.0.1',
        port: address.port,
        path,
        method,
        headers: Object.keys(requestHeaders).length > 0 ? requestHeaders : undefined,
      },
      (res) => {
        let raw = '';

        res.on('data', (chunk) => {
          raw += chunk;
        });

        res.on('end', () => {
          resolve({
            statusCode: res.statusCode,
            body: raw ? JSON.parse(raw) : null,
          });
        });
      },
    );

    req.on('error', reject);

    if (payload) {
      req.write(payload);
    }

    req.end();
  });
}

test('compose file defines the documented four-service local stack', async () => {
  const composeText = await readRepoText('docker/compose.local.yml');

  assert.match(composeText, /wordpress:/);
  assert.match(composeText, /mysql:/);
  assert.match(composeText, /mongodb:/);
  assert.match(composeText, /booking-payment-service:/);
});

test('GET /health returns ok payload', async (t) => {
  const server = createServer(baseEnv());

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, { method: 'GET', path: '/health' });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.status, 'ok');
});

test('POST /api/bookings rejects payloads missing required booking fields', async (t) => {
  const server = createServer(baseEnv(), {
    store: createMemoryStore(),
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/bookings',
    body: {
      wordpress_order_id: 1024,
      payment_status: 'pending',
    },
  });

  assert.equal(response.statusCode, 400);
  assert.match(response.body.message, /tour_code|customer_email|departure_date/);
});

test('POST /api/bookings creates pending booking and payment records with provider urls when adapter returns them', async (t) => {
  const store = createMemoryStore();
  const server = createServer(baseEnv(), {
    store,
    payosClient: {
      async createPaymentLink(payload) {
        return {
          provider: 'payos',
          payment_code: `PMT-${payload.wordpress_order_id}`,
          checkout_url: `https://pay.payos.vn/checkout/${payload.wordpress_order_id}`,
          qr_url: `https://pay.payos.vn/qr/${payload.wordpress_order_id}`,
          provider_transaction_id: `plink_${payload.wordpress_order_id}`,
        };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/bookings',
    body: {
      wordpress_order_id: 1024,
      wordpress_order_key: 'wc_order_demo',
      product_id: 88,
      tour_code: 'DL-HUE-3N2D',
      tour_name: 'Hue - Da Nang - Hoi An 3N2D',
      departure_date: '2026-05-20',
      adult_count: 2,
      child_count: 1,
      customer_note: 'An chay',
      customer_name: 'Nguyen Van A',
      customer_email: 'a@example.com',
      customer_phone: '0900000000',
      amount: 12990000,
      currency: 'VND',
      payment_status: 'pending',
      return_url: 'http://localhost:8080/checkout/order-received/1024',
      cancel_url: 'http://localhost:8080/thanh-toan/',
    },
  });

  assert.equal(response.statusCode, 201);
  assert.equal(response.body.booking_code, 'BK-1024');
  assert.equal(response.body.payment_code, 'PMT-1024');
  assert.equal(response.body.payment_status, 'pending');
  assert.equal(response.body.provider, 'payos');
  assert.match(response.body.checkout_url, /payos/);
  assert.match(response.body.qr_url, /payos/);

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  assert.equal(storedBooking.booking_code, 'BK-1024');
  assert.equal(storedBooking.payment_status, 'pending');
  assert.equal(storedPayment.status, 'pending');
  assert.equal(storedPayment.booking_code, 'BK-1024');
});

test('POST /api/bookings returns provider diagnostics when payment creation falls back', async (t) => {
  const store = createMemoryStore();
  const server = createServer(baseEnv(), {
    store,
    payosClient: {
      async createPaymentLink() {
        return {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
          diagnostics: {
            provider: 'zalopay',
            return_code: -2,
            return_message: 'Invalid mac',
            sub_return_code: -201,
            sub_return_message: 'mac not equal',
          },
        };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/bookings',
    body: {
      wordpress_order_id: 1024,
      wordpress_order_key: 'wc_order_demo',
      product_id: 88,
      tour_code: 'DL-HUE-3N2D',
      tour_name: 'Hue - Da Nang - Hoi An 3N2D',
      departure_date: '2026-05-20',
      adult_count: 2,
      customer_name: 'Nguyen Van A',
      customer_email: 'a@example.com',
      customer_phone: '0900000000',
      amount: 12990000,
      currency: 'VND',
      payment_status: 'pending',
      return_url: 'http://localhost:8080/checkout/order-received/1024',
      cancel_url: 'http://localhost:8080/thanh-toan/',
    },
  });

  assert.equal(response.statusCode, 201);
  assert.equal(response.body.provider, 'fallback');
  assert.deepEqual(response.body.payment_diagnostics, {
    provider: 'zalopay',
    return_code: -2,
    return_message: 'Invalid mac',
    sub_return_code: -201,
    sub_return_message: 'mac not equal',
  });
});

test('POST /api/payments/payos/webhook rejects invalid signatures', async (t) => {
  const store = createMemoryStore();
  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'payos',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const webhook = createPayOSSignedWebhook();
  webhook.signature = 'tampered';

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/payos/webhook',
    body: webhook,
  });

  assert.equal(response.statusCode, 400);
  assert.equal(response.body.result, 'rejected');
});

test('POST /api/payments/payos/webhook records event, updates booking and payment, and calls back WordPress once', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    wordpress_order_key: 'wc_order_demo',
    product_id: 88,
    tour_code: 'DL-HUE-3N2D',
    tour_name: 'Hue - Da Nang - Hoi An 3N2D',
    departure_date: '2026-05-20',
    adult_count: 2,
    child_count: 1,
    customer_note: 'An chay',
    customer_name: 'Nguyen Van A',
    customer_email: 'a@example.com',
    customer_phone: '0900000000',
    amount: 12990000,
    currency: 'VND',
    payment_status: 'pending',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'payos',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: 'https://pay.payos.vn/checkout/1024',
    qr_url: 'https://pay.payos.vn/qr/1024',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const webhook = createPayOSSignedWebhook();
  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/payos/webhook',
    body: webhook,
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.result, 'processed');
  assert.equal(response.body.payment_status, 'paid');

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'paid');
  assert.equal(storedPayment.status, 'paid');
  assert.equal(paymentEvents.length, 1);
  assert.equal(callbacks.length, 1);
  assert.equal(callbacks[0].wordpress_order_id, 1024);
  assert.equal(callbacks[0].status, 'paid');
  assert.equal(callbacks[0].payment_code, 'PMT-1024');
});

test('POST /api/payments/payos/webhook is idempotent for duplicate events', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'payos',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const webhook = createPayOSSignedWebhook();

  const firstResponse = await request(server, {
    method: 'POST',
    path: '/api/payments/payos/webhook',
    body: webhook,
  });
  const secondResponse = await request(server, {
    method: 'POST',
    path: '/api/payments/payos/webhook',
    body: webhook,
  });

  assert.equal(firstResponse.body.result, 'processed');
  assert.equal(secondResponse.statusCode, 200);
  assert.equal(secondResponse.body.result, 'duplicate');
  assert.equal(callbacks.length, 1);
});

test('POST /api/payments/zalopay/callback records a paid callback and returns ZaloPay success code', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    wordpress_order_key: 'wc_order_demo',
    product_id: 88,
    tour_code: 'DL-HUE-3N2D',
    tour_name: 'Hue - Da Nang - Hoi An 3N2D',
    departure_date: '2026-05-20',
    adult_count: 2,
    child_count: 1,
    customer_note: 'An chay',
    customer_name: 'Nguyen Van A',
    customer_email: 'a@example.com',
    customer_phone: '0900000000',
    amount: 12990000,
    currency: 'VND',
    payment_status: 'pending',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'zalopay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: 'https://qcgateway.zalopay.vn/openinapp?order=demo',
    qr_url: 'https://api.qrserver.com/v1/create-qr-code/?data=demo',
    provider_transaction_id: 'AC-ZALOPAY-TOKEN',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/zalopay/callback',
    body: createZaloPaySignedCallback(),
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.return_code, 1);
  assert.equal(response.body.return_message, 'success');

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'paid');
  assert.equal(storedPayment.status, 'paid');
  assert.equal(storedPayment.gateway, 'zalopay');
  assert.equal(storedPayment.provider_transaction_id, '250504000001');
  assert.equal(paymentEvents.length, 1);
  assert.equal(paymentEvents[0].provider, 'zalopay');
  assert.equal(callbacks.length, 1);
  assert.equal(callbacks[0].wordpress_order_id, 1024);
  assert.equal(callbacks[0].status, 'paid');
  assert.equal(callbacks[0].provider, 'zalopay');
});

test('POST /api/payments/zalopay/callback is idempotent for duplicate callbacks', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'zalopay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const callback = createZaloPaySignedCallback();

  const firstResponse = await request(server, {
    method: 'POST',
    path: '/api/payments/zalopay/callback',
    body: callback,
  });
  const secondResponse = await request(server, {
    method: 'POST',
    path: '/api/payments/zalopay/callback',
    body: callback,
  });

  assert.equal(firstResponse.body.return_code, 1);
  assert.equal(secondResponse.statusCode, 200);
  assert.equal(secondResponse.body.return_code, 2);
  assert.equal(secondResponse.body.return_message, 'duplicate');
  assert.equal(callbacks.length, 1);
});

test('POST /api/payments/zalopay/callback rejects invalid MAC without updating payment state', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'zalopay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const callback = createZaloPaySignedCallback();
  callback.mac = 'tampered';

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/zalopay/callback',
    body: callback,
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.return_code, -1);
  assert.equal(response.body.return_message, 'mac not equal');

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'pending');
  assert.equal(storedPayment.status, 'pending');
  assert.equal(paymentEvents.length, 0);
  assert.equal(callbacks.length, 0);
});

test('POST /api/payments/sepay/webhook records a paid SePay IPN and acknowledges success', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'sepay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: 'https://qr.sepay.vn/img?acc=0010000000355&bank=Vietcombank&amount=12990000&des=PMT1024',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/sepay/webhook',
    headers: {
      Authorization: 'Apikey demo-sepay-api-key',
    },
    body: createSePayWebhook(),
  });

  assert.equal(response.statusCode, 200);
  assert.deepEqual(response.body, { success: true });

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'paid');
  assert.equal(storedPayment.status, 'paid');
  assert.equal(storedPayment.gateway, 'sepay');
  assert.equal(storedPayment.provider_transaction_id, 'txn_1024');
  assert.equal(paymentEvents.length, 1);
  assert.equal(paymentEvents[0].provider, 'sepay');
  assert.equal(callbacks.length, 1);
  assert.equal(callbacks[0].provider, 'sepay');
});

test('POST /api/payments/sepay/webhook accepts compact transfer content without dash in the payment reference', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'sepay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: 'https://qr.sepay.vn/img?acc=0010000000355&bank=Vietcombank&amount=12990000&des=PMT1024',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/sepay/webhook',
    headers: {
      Authorization: 'Apikey demo-sepay-api-key',
    },
    body: createSePayWebhook({
      paymentCode: '',
      content: 'PMT1024 chuyen tien',
      transactionId: 'txn_1024_compact',
    }),
  });

  assert.equal(response.statusCode, 200);
  assert.deepEqual(response.body, { success: true });

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'paid');
  assert.equal(storedPayment.status, 'paid');
  assert.equal(storedPayment.provider_transaction_id, 'txn_1024_compact');
  assert.equal(paymentEvents.length, 1);
  assert.equal(paymentEvents[0].payment_code, 'PMT-1024');
  assert.equal(callbacks.length, 1);
  assert.equal(callbacks[0].payment_code, 'PMT-1024');
});

test('POST /api/payments/sepay/webhook rejects invalid authorization without updating payment state', async (t) => {
  const callbacks = [];
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'sepay',
    amount: 12990000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
    callbackClient: {
      async sendPaymentConfirm(payload) {
        callbacks.push(payload);
        return { status: 'ok' };
      },
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'POST',
    path: '/api/payments/sepay/webhook',
    headers: {
      Authorization: 'Bearer wrong-value',
    },
    body: createSePayWebhook(),
  });

  assert.equal(response.statusCode, 401);
  assert.equal(response.body.result, 'rejected');

  const storedBooking = await store.getBookingByOrderId(1024);
  const storedPayment = await store.getPaymentByCode('PMT-1024');
  const paymentEvents = await store.listPaymentEvents();

  assert.equal(storedBooking.payment_status, 'pending');
  assert.equal(storedPayment.status, 'pending');
  assert.equal(paymentEvents.length, 0);
  assert.equal(callbacks.length, 0);
});

test('GET /api/payments/status returns the freshest stored payment state for an order', async (t) => {
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-1024',
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    gateway: 'sepay',
    amount: 12990000,
    currency: 'VND',
    status: 'paid',
    checkout_url: 'https://pay.sepay.vn/checkout/1024',
    qr_url: 'https://qr.sepay.vn/img?acc=0010000000355&bank=Vietcombank&amount=12990000&des=PMT1024',
    provider_transaction_id: 'txn_1024',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-05-15T11:08:33.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'GET',
    path: '/api/payments/status?order_id=1024',
    headers: {
      Authorization: 'Bearer change-me',
    },
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.status, 'ok');
  assert.equal(response.body.wordpress_order_id, 1024);
  assert.equal(response.body.payment_code, 'PMT-1024');
  assert.equal(response.body.payment_status, 'paid');
  assert.equal(response.body.provider, 'sepay');
  assert.equal(response.body.provider_transaction_id, 'txn_1024');
});

test('GET /api/payments/status rejects requests without the payment sync secret', async (t) => {
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1024',
    wordpress_order_id: 1024,
    payment_status: 'pending',
    amount: 12990000,
    currency: 'VND',
    created_at: '2026-04-27T15:00:00.000Z',
    updated_at: '2026-04-27T15:00:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'GET',
    path: '/api/payments/status?order_id=1024',
  });

  assert.equal(response.statusCode, 403);
  assert.equal(response.body.status, 'forbidden');
});

test('GET /api/payments/status reconciles a pending SePay payment from the SePay transactions API', async (t) => {
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-74',
    wordpress_order_id: 74,
    payment_status: 'pending',
    amount: 2000,
    currency: 'VND',
    created_at: '2026-05-15T11:47:51.907Z',
    updated_at: '2026-05-15T11:47:51.907Z',
  });
  await store.upsertPayment({
    payment_code: 'PMT-74',
    booking_code: 'BK-74',
    wordpress_order_id: 74,
    gateway: 'sepay',
    amount: 2000,
    currency: 'VND',
    status: 'pending',
    checkout_url: '',
    qr_url: 'https://qr.sepay.vn/img?acc=0010000000355&bank=Vietcombank&amount=2000&des=PMT74',
    provider_transaction_id: '',
    created_at: '2026-05-15T11:47:51.907Z',
    updated_at: '2026-05-15T11:47:51.907Z',
  });

  const requests = [];
  const server = createServer({
    ...baseEnv(),
    SEPAY_API_TOKEN: 'sepay-live-token-123',
  }, {
    store,
    fetchImpl: async (url, options = {}) => {
      requests.push({ url: String(url), options });

      return {
        ok: true,
        status: 200,
        async json() {
          return {
            status: 'success',
            data: [
              {
                id: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                transaction_date: '2026-05-15 18:48:00',
                account_number: '0010000000355',
                transfer_type: 'in',
                amount_in: 2000,
                amount_out: 0,
                transaction_content: 'PMT74',
                reference_number: 'FT26135009096257',
                webhook_success: 0,
                bank_brand_name: 'MBBank',
              },
            ],
          };
        },
      };
    },
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'GET',
    path: '/api/payments/status?order_id=74',
    headers: {
      Authorization: 'Bearer change-me',
    },
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.status, 'ok');
  assert.equal(response.body.payment_status, 'paid');
  assert.equal(response.body.payment_code, 'PMT-74');
  assert.equal(response.body.provider, 'sepay');
  assert.equal(response.body.provider_transaction_id, 'FT26135009096257');
  assert.equal(response.body.status_source, 'sepay-api');
  assert.equal(requests.length, 1);
  assert.match(requests[0].url, /transaction_content=PMT74/);

  const storedBooking = await store.getBookingByOrderId(74);
  const storedPayment = await store.getPaymentByCode('PMT-74');
  assert.equal(storedBooking.payment_status, 'paid');
  assert.equal(storedPayment.status, 'paid');
  assert.equal(storedPayment.provider_transaction_id, 'FT26135009096257');
});

test('GET /api/reports/revenue only sums paid bookings within range', async (t) => {
  const store = createMemoryStore();

  await store.upsertBooking({
    booking_code: 'BK-1001',
    wordpress_order_id: 1001,
    amount: 5000000,
    currency: 'VND',
    payment_status: 'paid',
    created_at: '2026-04-01T10:00:00.000Z',
    updated_at: '2026-04-01T10:30:00.000Z',
  });
  await store.upsertBooking({
    booking_code: 'BK-1002',
    wordpress_order_id: 1002,
    amount: 4000000,
    currency: 'VND',
    payment_status: 'pending',
    created_at: '2026-04-02T10:00:00.000Z',
    updated_at: '2026-04-02T10:15:00.000Z',
  });
  await store.upsertBooking({
    booking_code: 'BK-1003',
    wordpress_order_id: 1003,
    amount: 2500000,
    currency: 'VND',
    payment_status: 'paid',
    created_at: '2026-05-03T10:00:00.000Z',
    updated_at: '2026-05-03T10:20:00.000Z',
  });

  const server = createServer(baseEnv(), {
    store,
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, {
    method: 'GET',
    path: '/api/reports/revenue?from=2026-04-01&to=2026-04-30',
  });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.revenue_total, 5000000);
  assert.equal(response.body.paid_bookings, 1);
  assert.equal(response.body.total_bookings, 2);
});
