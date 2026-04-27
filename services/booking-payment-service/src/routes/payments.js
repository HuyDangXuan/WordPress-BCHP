import { buildPaymentConfirmRequest } from '../services/callback-wordpress.js';

export function handlePaymentWebhook(body, env) {
  const callback = buildPaymentConfirmRequest(body, env);

  return {
    result: 'accepted',
    payment_status: body.status || 'pending',
    callback,
  };
}
