import { createHttpError } from './errors.js';

export function extractPaymentSyncSecret(headers = {}) {
  const authorization = String(headers.authorization || headers.Authorization || '');

  if (authorization.toLowerCase().startsWith('bearer ')) {
    return authorization.slice(7).trim();
  }

  return String(headers['x-payment-sync-secret'] || headers['X-Payment-Sync-Secret'] || '');
}

export function assertValidPaymentSyncSecret(headers, env) {
  const providedSecret = extractPaymentSyncSecret(headers);

  if (! env.PAYMENT_SYNC_SECRET || ! providedSecret || env.PAYMENT_SYNC_SECRET !== providedSecret) {
    throw createHttpError(403, {
      status: 'forbidden',
      message: 'Invalid payment sync secret.',
    });
  }
}
