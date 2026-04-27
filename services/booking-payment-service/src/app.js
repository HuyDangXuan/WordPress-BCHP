import http from 'node:http';
import { pathToFileURL } from 'node:url';

import { loadEnv } from './config/env.js';
import { getRequestUrl } from './lib/http.js';
import { readJsonBody } from './lib/json.js';
import { sendJson } from './lib/response.js';
import { handleCreateBooking } from './routes/bookings.js';
import { handleHealth } from './routes/health.js';
import { handlePaymentWebhook } from './routes/payments.js';
import { handleRevenueReport } from './routes/reports.js';

export function createServer(envSource = process.env) {
  const env = loadEnv(envSource);

  return http.createServer(async (request, response) => {
    const url = getRequestUrl(request);

    try {
      if (request.method === 'GET' && url.pathname === '/health') {
        sendJson(response, 200, handleHealth(env));
        return;
      }

      if (request.method === 'POST' && url.pathname === '/api/bookings') {
        const body = await readJsonBody(request);
        sendJson(response, 202, handleCreateBooking(body));
        return;
      }

      if (request.method === 'POST' && url.pathname === '/api/payments/payos/webhook') {
        const body = await readJsonBody(request);
        sendJson(response, 202, handlePaymentWebhook(body, env));
        return;
      }

      if (request.method === 'GET' && url.pathname === '/api/reports/revenue') {
        sendJson(response, 200, handleRevenueReport(url.searchParams));
        return;
      }

      sendJson(response, 404, {
        status: 'not_found',
        path: url.pathname,
      });
    } catch (error) {
      sendJson(response, 400, {
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
