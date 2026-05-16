export function buildPaymentConfirmRequest(event, env) {
  return {
    url: env.WORDPRESS_CONFIRM_ENDPOINT,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${env.PAYMENT_SYNC_SECRET}`,
    },
    body: {
      wordpress_order_id: event.wordpress_order_id ?? null,
      payment_code: event.payment_code ?? 'PMT-DEMO-0001',
      amount: event.amount ?? 0,
      currency: event.currency ?? 'VND',
      status: event.status ?? 'pending',
      provider: event.provider ?? 'sepay',
      provider_transaction_id: event.provider_transaction_id ?? 'SEPAY-DEMO-0001',
    },
  };
}

export function createWordPressCallbackClient(env, fetchImpl = globalThis.fetch) {
  return {
    async sendPaymentConfirm(event) {
      const request = buildPaymentConfirmRequest(event, env);
      const response = await fetchImpl(request.url, {
        method: request.method,
        headers: request.headers,
        body: JSON.stringify(request.body),
      });

      if (! response.ok) {
        throw new Error(`WordPress payment confirm failed with status ${response.status}`);
      }

      return await response.json().catch(() => ({ status: 'ok' }));
    },
  };
}
