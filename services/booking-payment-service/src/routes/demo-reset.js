import { assertValidPaymentSyncSecret } from '../lib/payment-sync-secret.js';

export async function handleDemoReset(headers, env, services) {
  assertValidPaymentSyncSecret(headers, env);

  return {
    statusCode: 200,
    payload: {
      status: 'ok',
      cleared: await services.store.clearDemoData(),
    },
  };
}
