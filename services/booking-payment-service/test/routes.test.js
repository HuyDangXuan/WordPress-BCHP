import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import http from 'node:http';
import crypto from 'node:crypto';

import { createServer } from '../src/app.js';

function baseEnv() {
  return {
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
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
  const composeText = await fs.readFile('docker/compose.local.yml', 'utf8');

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
