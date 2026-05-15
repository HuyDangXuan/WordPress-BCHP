import { createHttpError } from '../lib/errors.js';

function extractSecret(headers = {}) {
  const authorization = String(headers.authorization || headers.Authorization || '');

  if (authorization.toLowerCase().startsWith('bearer ')) {
    return authorization.slice(7).trim();
  }

  return String(headers['x-payment-sync-secret'] || headers['X-Payment-Sync-Secret'] || '');
}

export async function handlePaymentStatus(searchParams, headers, env, services) {
  const providedSecret = extractSecret(headers);

  if (! env.PAYMENT_SYNC_SECRET || ! providedSecret || env.PAYMENT_SYNC_SECRET !== providedSecret) {
    throw createHttpError(403, {
      status: 'forbidden',
      message: 'Invalid payment sync secret.',
    });
  }

  return {
    statusCode: 200,
    payload: await services.paymentStatusWorkflow.getPaymentStatus({
      wordpressOrderId: searchParams.get('order_id'),
      paymentCode: searchParams.get('payment_code'),
    }),
  };
}
