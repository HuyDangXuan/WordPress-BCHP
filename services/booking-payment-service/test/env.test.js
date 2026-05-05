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
    ZALOPAY_APP_ID: 'demo-app-id',
    ZALOPAY_KEY1: 'demo-key1',
    ZALOPAY_KEY2: 'demo-key2',
    ZALOPAY_ENV: 'sandbox',
    ZALOPAY_CALLBACK_URL: 'http://localhost:8787/api/payments/zalopay/callback',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
  });

  assert.equal(env.ZALOPAY_ENV, 'sandbox');
  assert.equal(env.ZALOPAY_CALLBACK_URL, 'http://localhost:8787/api/payments/zalopay/callback');
});
