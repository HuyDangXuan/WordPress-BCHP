import { createHttpError } from '../lib/errors.js';

function toIsoString(value) {
  return new Date(value).toISOString();
}

export function createSePayWebhookWorkflow({
  store,
  sepayClient,
  callbackClient,
  now = () => new Date().toISOString(),
}) {
  return {
    async handleWebhook(payload, headers = {}) {
      if (! sepayClient.verifyWebhookAuthorization(headers)) {
        throw createHttpError(401, {
          result: 'rejected',
          message: 'Invalid SePay authorization.',
        });
      }

      const event = sepayClient.normalizeWebhookEvent(payload);

      if (! event.paymentCode || ! event.wordpressOrderId) {
        throw createHttpError(400, {
          result: 'rejected',
          message: 'Missing SePay payment_code or order reference.',
        });
      }

      if (event.status !== 'paid') {
        return { success: true };
      }

      const timestamp = toIsoString(now());

      try {
        await store.insertPaymentEvent({
          event_id: event.eventId,
          idempotency_key: event.idempotencyKey,
          payment_code: event.paymentCode,
          provider: event.provider,
          event_type: event.eventType,
          signature_valid: true,
          payload: event.rawPayload,
          received_at: timestamp,
          processed_at: timestamp,
          result: 'processed',
        });
      } catch (error) {
        if (error?.code === 'DUPLICATE_EVENT') {
          return { success: true };
        }

        throw error;
      }

      const payment = await store.getPaymentByCode(event.paymentCode);
      const booking = await store.getBookingByOrderId(event.wordpressOrderId);

      await store.upsertPayment({
        payment_code: event.paymentCode,
        booking_code: payment?.booking_code || booking?.booking_code || `BK-${event.wordpressOrderId}`,
        wordpress_order_id: event.wordpressOrderId,
        gateway: payment?.gateway || event.provider,
        amount: event.amount,
        currency: event.currency,
        status: event.status,
        checkout_url: payment?.checkout_url || '',
        qr_url: payment?.qr_url || '',
        provider_transaction_id: event.providerTransactionId,
        paid_at: event.paidAt || timestamp,
        created_at: payment?.created_at || timestamp,
        updated_at: timestamp,
      });

      await store.upsertBooking({
        ...(booking || {}),
        booking_code: booking?.booking_code || `BK-${event.wordpressOrderId}`,
        wordpress_order_id: event.wordpressOrderId,
        amount: event.amount,
        currency: event.currency,
        payment_status: event.status,
        created_at: booking?.created_at || timestamp,
        updated_at: timestamp,
      });

      await callbackClient.sendPaymentConfirm({
        wordpress_order_id: event.wordpressOrderId,
        payment_code: event.paymentCode,
        amount: event.amount,
        currency: event.currency,
        status: event.status,
        provider: event.provider,
        provider_transaction_id: event.providerTransactionId,
      });

      return { success: true };
    },
  };
}
