import { assertValidPaymentSyncSecret } from '../lib/payment-sync-secret.js';

export async function handlePaymentStatus(searchParams, headers, env, services) {
  assertValidPaymentSyncSecret(headers, env);

  return {
    statusCode: 200,
    payload: await services.paymentStatusWorkflow.getPaymentStatus({
      wordpressOrderId: searchParams.get('order_id'),
      paymentCode: searchParams.get('payment_code'),
    }),
  };
}
