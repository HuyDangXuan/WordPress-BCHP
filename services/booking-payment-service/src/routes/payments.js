export async function handlePaymentWebhook(body, services) {
  return {
    statusCode: 200,
    payload: await services.paymentWebhookWorkflow.handleWebhook(body),
  };
}

export async function handleSePayWebhook(body, headers, services) {
  return {
    statusCode: 200,
    payload: await services.sepayWebhookWorkflow.handleWebhook(body, headers),
  };
}

export async function handleZaloPayCallback(body, services) {
  return {
    statusCode: 200,
    payload: await services.zalopayCallbackWorkflow.handleCallback(body),
  };
}
