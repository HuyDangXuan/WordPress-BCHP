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
      provider: event.provider ?? 'payos',
      provider_transaction_id: event.provider_transaction_id ?? 'PAYOS-DEMO-0001',
    },
  };
}
