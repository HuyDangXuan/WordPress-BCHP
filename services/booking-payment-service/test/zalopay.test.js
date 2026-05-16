import test from 'node:test';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';

import { createZaloPayClient } from '../src/services/zalopay.js';

function baseEnv(overrides = {}) {
  return {
    ZALOPAY_APP_ID: '554',
    ZALOPAY_KEY1: '8NdU5pG5R2spGHGhyO99HN1OhD8IQJBn',
    ZALOPAY_KEY2: 'uUfsWgfLkRLzq6W2uNXTCxrfxs51auny',
    ZALOPAY_ENV: 'sandbox',
    ZALOPAY_CALLBACK_URL: 'http://localhost:8787/api/payments/zalopay/callback',
    ...overrides,
  };
}

function bookingPayload(overrides = {}) {
  return {
    wordpress_order_id: 1024,
    payment_code: 'PMT-1024',
    amount: 12990000,
    currency: 'VND',
    tour_name: 'Hue - Da Nang - Hoi An 3N2D',
    customer_name: 'Nguyen Van A',
    customer_email: 'a@example.com',
    customer_phone: '0900000000',
    return_url: 'http://localhost:8080/checkout/order-received/1024',
    cancel_url: 'http://localhost:8080/thanh-toan/',
    ...overrides,
  };
}

test('createPaymentLink posts a signed sandbox ZaloPay create-order request', async () => {
  const requests = [];
  const client = createZaloPayClient(
    baseEnv(),
    async (url, options) => {
      requests.push({ url, options });
      return {
        ok: true,
        async json() {
          return {
            return_code: 1,
            order_url: 'https://qcgateway.zalopay.vn/openinapp?order=demo',
            qr_code: '000201010212demo',
            zp_trans_token: 'AC-ZALOPAY-TOKEN',
            order_token: 'AC-ZALOPAY-TOKEN',
          };
        },
      };
    },
    { now: () => new Date('2026-05-04T08:00:00.000Z') },
  );

  const paymentLink = await client.createPaymentLink(bookingPayload());

  assert.equal(requests.length, 1);
  assert.equal(requests[0].url, 'https://sb-openapi.zalopay.vn/v2/create');
  assert.equal(requests[0].options.method, 'POST');
  assert.equal(requests[0].options.headers['Content-Type'], 'application/json');

  const body = JSON.parse(requests[0].options.body);
  const expectedMacInput = [
    body.app_id,
    body.app_trans_id,
    body.app_user,
    body.amount,
    body.app_time,
    body.embed_data,
    body.item,
  ].join('|');
  const expectedMac = crypto
    .createHmac('sha256', '8NdU5pG5R2spGHGhyO99HN1OhD8IQJBn')
    .update(expectedMacInput)
    .digest('hex');

  assert.equal(body.app_id, 554);
  assert.match(body.app_trans_id, /^260504_1024$/);
  assert.equal(body.app_user, 'a@example.com');
  assert.equal(body.amount, 12990000);
  assert.equal(body.bank_code, '');
  assert.equal(body.callback_url, 'http://localhost:8787/api/payments/zalopay/callback');
  assert.equal(body.mac, expectedMac);

  const embedData = JSON.parse(body.embed_data);
  assert.equal(embedData.wordpress_order_id, 1024);
  assert.equal(embedData.payment_code, 'PMT-1024');
  assert.equal(embedData.redirecturl, 'http://localhost:8080/checkout/order-received/1024');
  assert.deepEqual(embedData.preferred_payment_method, ['zalopay_wallet']);

  assert.equal(paymentLink.provider, 'zalopay');
  assert.equal(paymentLink.checkout_url, 'https://qcgateway.zalopay.vn/openinapp?order=demo');
  assert.match(paymentLink.qr_url, /^https:\/\/api\.qrserver\.com/);
  assert.match(decodeURIComponent(paymentLink.qr_url), /000201010212demo/);
  assert.equal(paymentLink.provider_transaction_id, 'AC-ZALOPAY-TOKEN');
});

test('createPaymentLink returns fallback with ZaloPay diagnostics when create-order fails', async () => {
  const client = createZaloPayClient(
    baseEnv(),
    async () => ({
      ok: true,
      async json() {
        return {
          return_code: -2,
          return_message: 'Invalid mac',
          sub_return_code: -201,
          sub_return_message: 'mac not equal',
        };
      },
    }),
  );

  const paymentLink = await client.createPaymentLink(bookingPayload());

  assert.equal(paymentLink.provider, 'fallback');
  assert.equal(paymentLink.checkout_url, '');
  assert.equal(paymentLink.qr_url, '');
  assert.deepEqual(paymentLink.diagnostics, {
    provider: 'zalopay',
    return_code: -2,
    return_message: 'Invalid mac',
    sub_return_code: -201,
    sub_return_message: 'mac not equal',
  });
});

test('createPaymentLink returns fallback provider when ZaloPay credentials are placeholders', async () => {
  const client = createZaloPayClient(baseEnv({
    ZALOPAY_APP_ID: 'demo-app-id',
    ZALOPAY_KEY1: 'demo-key1',
    ZALOPAY_KEY2: 'demo-key2',
  }), async () => {
    throw new Error('fetch should not be called with placeholder credentials');
  });

  const paymentLink = await client.createPaymentLink(bookingPayload());

  assert.deepEqual(paymentLink, {
    provider: 'fallback',
    checkout_url: '',
    qr_url: '',
    provider_transaction_id: '',
  });
});
