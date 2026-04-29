export async function handlePaymentWebhook(body, services) {
  return {
    statusCode: 200,
    payload: await services.paymentWebhookWorkflow.handleWebhook(body),
  };
}
