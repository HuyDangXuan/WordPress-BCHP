import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import http from 'node:http';

import { createServer } from '../src/app.js';

async function request(server, { method, path, body }) {
  const address = server.address();
  const payload = body ? JSON.stringify(body) : null;

  return await new Promise((resolve, reject) => {
    const req = http.request(
      {
        hostname: '127.0.0.1',
        port: address.port,
        path,
        method,
        headers: payload
          ? {
              'Content-Type': 'application/json',
              'Content-Length': Buffer.byteLength(payload),
            }
          : undefined,
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
  const server = createServer({
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  t.after(() => server.close());

  const response = await request(server, { method: 'GET', path: '/health' });

  assert.equal(response.statusCode, 200);
  assert.equal(response.body.status, 'ok');
});

test('POST /api/bookings returns pending booking snapshot response', async (t) => {
  const server = createServer({
    MONGO_URI: 'mongodb://mongodb:27017/hv_travel',
    PAYOS_CLIENT_ID: 'demo-client-id',
    PAYOS_API_KEY: 'demo-api-key',
    PAYOS_CHECKSUM_KEY: 'demo-checksum-key',
    PAYMENT_SYNC_SECRET: 'change-me',
    WORDPRESS_CONFIRM_ENDPOINT: 'http://wordpress/wp-json/op-travel/v1/payment-confirm',
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

  assert.equal(response.statusCode, 202);
  assert.equal(response.body.payment_status, 'pending');
});
