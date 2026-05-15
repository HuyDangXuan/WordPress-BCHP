import test from 'node:test';
import assert from 'node:assert/strict';

import { createSePayClient } from '../src/services/sepay.js';

function baseEnv(overrides = {}) {
  return {
    SEPAY_API_KEY: 'demo-sepay-api-key',
    SEPAY_BANK_CODE: 'Vietcombank',
    SEPAY_ACCOUNT_NUMBER: '0010000000355',
    SEPAY_ACCOUNT_NAME: 'HV Travel Demo',
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

test('createPaymentLink returns a SePay VietQR image with the booking payment code embedded', async () => {
  const client = createSePayClient(baseEnv());

  const paymentLink = await client.createPaymentLink(bookingPayload());

  assert.equal(paymentLink.provider, 'sepay');
  assert.equal(paymentLink.checkout_url, '');
  assert.equal(paymentLink.provider_transaction_id, '');
  assert.match(paymentLink.qr_url, /^https:\/\/qr\.sepay\.vn\/img\?/);
  assert.match(paymentLink.qr_url, /acc=0010000000355/);
  assert.match(paymentLink.qr_url, /bank=Vietcombank/);
  assert.match(paymentLink.qr_url, /amount=12990000/);
  assert.match(decodeURIComponent(paymentLink.qr_url), /des=PMT1024/);
});

test('verifyWebhookAuthorization accepts the documented SePay Apikey header format', () => {
  const client = createSePayClient(baseEnv());

  assert.equal(client.verifyWebhookAuthorization({
    authorization: 'Apikey demo-sepay-api-key',
  }), true);

  assert.equal(client.verifyWebhookAuthorization({
    authorization: 'Bearer demo-sepay-api-key',
  }), false);
});

test('normalizeWebhookEvent maps SePay credit IPN payloads to a paid booking event', () => {
  const client = createSePayClient(baseEnv());

  const event = client.normalizeWebhookEvent({
    gateway: 'Vietcombank',
    transaction_date: '2026-05-15 11:08:33',
    account_number: '0010000000355',
    bank_account_xid: 'ba_demo_001',
    va: null,
    payment_code: 'PMT-1024',
    content: 'PMT-1024 chuyen tien',
    transfer_type: 'credit',
    amount: 12990000,
    reference_code: 'FT24012345678',
    accumulated: 0,
    transaction_id: 'txn_1024',
  });

  assert.equal(event.paymentCode, 'PMT-1024');
  assert.equal(event.provider, 'sepay');
  assert.equal(event.wordpressOrderId, 1024);
  assert.equal(event.status, 'paid');
  assert.equal(event.providerTransactionId, 'txn_1024');
  assert.equal(event.eventType, 'payment.paid');
});

test('normalizeWebhookEvent accepts compact SePay transfer content without dash and maps it back to the internal payment code', () => {
  const client = createSePayClient(baseEnv());

  const event = client.normalizeWebhookEvent({
    gateway: 'Vietcombank',
    transaction_date: '2026-05-15 11:08:33',
    account_number: '0010000000355',
    bank_account_xid: 'ba_demo_001',
    va: null,
    payment_code: '',
    content: 'PMT1024 chuyen tien',
    transfer_type: 'credit',
    amount: 12990000,
    reference_code: 'FT24012345678',
    accumulated: 0,
    transaction_id: 'txn_1024_compact',
  });

  assert.equal(event.paymentCode, 'PMT-1024');
  assert.equal(event.wordpressOrderId, 1024);
  assert.equal(event.status, 'paid');
  assert.equal(event.eventType, 'payment.paid');
});

test('findIncomingTransferByPaymentCode queries SePay transactions API and returns a normalized paid event', async () => {
  const requests = [];
  const client = createSePayClient(baseEnv({
    SEPAY_API_TOKEN: 'sepay-live-token-123',
  }), async (url, options = {}) => {
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
  });

  const event = await client.findIncomingTransferByPaymentCode({
    paymentCode: 'PMT-74',
    amount: 2000,
  });

  assert.equal(requests.length, 1);
  const requestUrl = new URL(requests[0].url);
  assert.equal(requestUrl.origin, 'https://userapi.sepay.vn');
  assert.equal(requestUrl.pathname, '/v2/transactions');
  assert.equal(requestUrl.searchParams.get('transaction_content'), 'PMT74');
  assert.equal(requestUrl.searchParams.get('transfer_type'), 'in');
  assert.equal(requestUrl.searchParams.get('amount_in_min'), '2000');
  assert.equal(requestUrl.searchParams.get('amount_in_max'), '2000');
  assert.equal(requestUrl.searchParams.get('transaction_date_sort'), 'desc');
  assert.equal(requestUrl.searchParams.get('per_page'), '20');
  assert.equal(requests[0].options.headers.Authorization, 'Bearer sepay-live-token-123');

  assert.equal(event.paymentCode, 'PMT-74');
  assert.equal(event.wordpressOrderId, 74);
  assert.equal(event.status, 'paid');
  assert.equal(event.provider, 'sepay');
  assert.equal(event.providerTransactionId, 'FT26135009096257');
});
