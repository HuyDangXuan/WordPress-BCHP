import http from 'node:http';
import { pathToFileURL } from 'node:url';

import { loadEnv } from './config/env.js';
import { getRequestUrl } from './lib/http.js';
import { readJsonBody } from './lib/json.js';
import { sendJson } from './lib/response.js';
import { handleCreateBooking } from './routes/bookings.js';
import { handleHealth } from './routes/health.js';
import { handlePaymentWebhook, handleZaloPayCallback } from './routes/payments.js';
import { handleRevenueReport } from './routes/reports.js';
import { createBookingWorkflow } from './services/booking-workflow.js';
import { createMongoStore } from './services/mongo.js';
import { createWordPressCallbackClient } from './services/callback-wordpress.js';
import { createPayOSClient } from './services/payos.js';
import { createZaloPayClient } from './services/zalopay.js';
import { createPaymentWebhookWorkflow } from './services/payment-webhook-workflow.js';
import { createZaloPayCallbackWorkflow } from './services/zalopay-callback-workflow.js';
import { createRevenueReportWorkflow } from './services/revenue-report-workflow.js';

function createRuntimeServices(env, overrides = {}) {
  const store = overrides.store ?? createMongoStore(env);
  const payosClient = overrides.payosClient ?? createPayOSClient(env, overrides.fetchImpl);
  const zalopayClient = overrides.zalopayClient ?? createZaloPayClient(env, overrides.fetchImpl);
  const paymentClient = overrides.paymentClient ?? overrides.zalopayClient ?? overrides.payosClient ?? zalopayClient;
  const callbackClient = overrides.callbackClient ?? createWordPressCallbackClient(env, overrides.fetchImpl);

  return {
    store,
    payosClient,
    zalopayClient,
    callbackClient,
    bookingWorkflow: overrides.bookingWorkflow ?? createBookingWorkflow({
      store,
      paymentClient,
      now: overrides.now,
    }),
    paymentWebhookWorkflow: overrides.paymentWebhookWorkflow ?? createPaymentWebhookWorkflow({
      store,
      payosClient,
      callbackClient,
      now: overrides.now,
    }),
    zalopayCallbackWorkflow: overrides.zalopayCallbackWorkflow ?? createZaloPayCallbackWorkflow({
      store,
      zalopayClient,
      callbackClient,
      now: overrides.now,
    }),
    revenueReportWorkflow: overrides.revenueReportWorkflow ?? createRevenueReportWorkflow({
      store,
    }),
  };
}

export function createServer(envSource = process.env, overrides = {}) {
  const env = loadEnv(envSource);
  const services = createRuntimeServices(env, overrides);

  return http.createServer(async (request, response) => {
    const url = getRequestUrl(request);

    try {
      if (request.method === 'GET' && url.pathname === '/health') {
        sendJson(response, 200, handleHealth(env));
        return;
      }

      if (request.method === 'POST' && url.pathname === '/api/bookings') {
        const body = await readJsonBody(request);
        const result = await handleCreateBooking(body, services);
        sendJson(response, result.statusCode, result.payload);
        return;
      }

      if (request.method === 'POST' && url.pathname === '/api/payments/payos/webhook') {
        const body = await readJsonBody(request);
        const result = await handlePaymentWebhook(body, services);
        sendJson(response, result.statusCode, result.payload);
        return;
      }

      if (request.method === 'POST' && url.pathname === '/api/payments/zalopay/callback') {
        const body = await readJsonBody(request);
        const result = await handleZaloPayCallback(body, services);
        sendJson(response, result.statusCode, result.payload);
        return;
      }

      if (request.method === 'GET' && url.pathname === '/api/reports/revenue') {
        const result = await handleRevenueReport(url.searchParams, services);
        sendJson(response, result.statusCode, result.payload);
        return;
      }

      sendJson(response, 404, {
        status: 'not_found',
        path: url.pathname,
      });
    } catch (error) {
      sendJson(response, error.statusCode || 400, error.payload || {
        status: 'error',
        message: error.message,
      });
    }
  });
}

const isDirectRun =
  process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href;

if (isDirectRun) {
  const server = createServer(process.env);
  const port = Number(process.env.PORT || 8787);

  server.listen(port, '0.0.0.0', () => {
    console.log(`booking-payment-service listening on ${port}`);
  });
}
