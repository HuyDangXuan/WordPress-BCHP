import test from 'node:test';
import assert from 'node:assert/strict';

import { loadEnv } from '../src/config/env.js';

test('loadEnv requires all documented service variables', () => {
  assert.throws(() => loadEnv({}), /MONGO_URI/);
});

test('loadEnv accepts the documented ZaloPay service variables', () => {
  const env = loadEnv({
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
    ZALOPAY_APP_ID: '554',
    ZALOPAY_KEY1: '8NdU5pG5R2spGHGhyO99HN1OhD8IQJBn',
    ZALOPAY_KEY2: 'uUfsWgfLkRLzq6W2uNXTCxrfxs51auny',
    ZALOPAY_ENV: 'sandbox',
    ZALOPAY_CALLBACK_URL: 'http://localhost:8787/api/payments/zalopay/callback',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
  });

  assert.equal(env.ZALOPAY_ENV, 'sandbox');
  assert.equal(env.ZALOPAY_CALLBACK_URL, 'http://localhost:8787/api/payments/zalopay/callback');
});

test('loadEnv accepts the documented SePay service variables without legacy ZaloPay keys', () => {
  const env = loadEnv({
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
    SEPAY_API_KEY: 'demo-sepay-api-key',
    SEPAY_API_TOKEN: 'demo-sepay-api-token',
    SEPAY_BANK_CODE: 'Vietcombank',
    SEPAY_ACCOUNT_NUMBER: '0010000000355',
    SEPAY_ACCOUNT_NAME: 'HV Travel Demo',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
  });

  assert.equal(env.SEPAY_API_KEY, 'demo-sepay-api-key');
  assert.equal(env.SEPAY_API_TOKEN, 'demo-sepay-api-token');
  assert.equal(env.SEPAY_BANK_CODE, 'Vietcombank');
  assert.equal(env.SEPAY_ACCOUNT_NUMBER, '0010000000355');
  assert.equal(env.SEPAY_ACCOUNT_NAME, 'HV Travel Demo');
});
