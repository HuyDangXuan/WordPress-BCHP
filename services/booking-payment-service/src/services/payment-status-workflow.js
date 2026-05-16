import { createHttpError } from '../lib/errors.js';

const ALLOWED_STATUSES = new Set(['pending', 'paid', 'failed', 'expired', 'cancelled']);

function normalizeStatus(value) {
  const normalized = String(value || 'pending').trim().toLowerCase();
  return ALLOWED_STATUSES.has(normalized) ? normalized : 'pending';
}

function normalizeAmount(value) {
  const amount = Number(value || 0);
  return Number.isFinite(amount) ? Math.round(amount) : 0;
}

function toIsoString(value) {
  return new Date(value).toISOString();
}

export function createPaymentStatusWorkflow({
  store,
  sepayClient,
  now = () => new Date().toISOString(),
}) {
  return {
    async getPaymentStatus({ wordpressOrderId, paymentCode }) {
      const resolvedOrderId = Number(wordpressOrderId || 0);
      const resolvedPaymentCode = String(paymentCode || '').trim();

      if (! resolvedOrderId && resolvedPaymentCode === '') {
        throw createHttpError(400, {
          status: 'invalid_request',
          message: 'order_id or payment_code is required.',
        });
      }

      let payment = resolvedPaymentCode !== ''
        ? await store.getPaymentByCode(resolvedPaymentCode)
        : null;

      if (! payment && resolvedOrderId && typeof store.getPaymentByOrderId === 'function') {
        payment = await store.getPaymentByOrderId(resolvedOrderId);
      }

      const paymentOrderId = Number(payment?.wordpress_order_id || 0);
      const finalOrderId = resolvedOrderId || paymentOrderId;
      const booking = finalOrderId
        ? await store.getBookingByOrderId(finalOrderId)
        : null;

      if (! payment && ! booking) {
        throw createHttpError(404, {
          status: 'not_found',
          message: 'Payment record not found.',
        });
      }

      const provider = String(payment?.gateway || '');
      const currentStatus = normalizeStatus(payment?.status || booking?.payment_status);

      if (
        provider === 'sepay'
        && currentStatus === 'pending'
        && typeof sepayClient?.canLookupTransactions === 'function'
        && sepayClient.canLookupTransactions()
      ) {
        const reconciledEvent = await sepayClient.findIncomingTransferByPaymentCode({
          paymentCode: String(payment?.payment_code || resolvedPaymentCode || ''),
          amount: normalizeAmount(payment?.amount ?? booking?.amount),
        }).catch(() => null);

        if (reconciledEvent && reconciledEvent.status === 'paid') {
          const timestamp = toIsoString(now());

          payment = await store.upsertPayment({
            ...(payment || {}),
            payment_code: reconciledEvent.paymentCode,
            booking_code: payment?.booking_code || booking?.booking_code || `BK-${reconciledEvent.wordpressOrderId}`,
            wordpress_order_id: reconciledEvent.wordpressOrderId,
            gateway: provider || reconciledEvent.provider,
            amount: reconciledEvent.amount,
            currency: reconciledEvent.currency,
            status: reconciledEvent.status,
            checkout_url: payment?.checkout_url || '',
            qr_url: payment?.qr_url || '',
            provider_transaction_id: reconciledEvent.providerTransactionId,
            paid_at: reconciledEvent.paidAt || timestamp,
            created_at: payment?.created_at || timestamp,
            updated_at: timestamp,
          });

          if (booking) {
            await store.upsertBooking({
              ...booking,
              booking_code: booking.booking_code || payment?.booking_code || `BK-${reconciledEvent.wordpressOrderId}`,
              wordpress_order_id: reconciledEvent.wordpressOrderId,
              amount: reconciledEvent.amount,
              currency: reconciledEvent.currency,
              payment_status: reconciledEvent.status,
              created_at: booking.created_at || timestamp,
              updated_at: timestamp,
            });
          }

          return {
            status: 'ok',
            wordpress_order_id: reconciledEvent.wordpressOrderId,
            booking_code: String(payment?.booking_code || booking?.booking_code || ''),
            payment_code: reconciledEvent.paymentCode,
            payment_status: reconciledEvent.status,
            provider: reconciledEvent.provider,
            provider_transaction_id: reconciledEvent.providerTransactionId,
            amount: reconciledEvent.amount,
            currency: reconciledEvent.currency,
            checkout_url: String(payment?.checkout_url || ''),
            qr_url: String(payment?.qr_url || ''),
            updated_at: payment?.updated_at || timestamp,
            status_source: reconciledEvent.statusSource || 'payment',
          };
        }
      }

      return {
        status: 'ok',
        wordpress_order_id: finalOrderId || Number(booking?.wordpress_order_id || 0),
        booking_code: String(payment?.booking_code || booking?.booking_code || ''),
        payment_code: String(payment?.payment_code || resolvedPaymentCode || ''),
        payment_status: normalizeStatus(payment?.status || booking?.payment_status),
        provider: String(payment?.gateway || ''),
        provider_transaction_id: String(payment?.provider_transaction_id || ''),
        amount: normalizeAmount(payment?.amount ?? booking?.amount),
        currency: String(payment?.currency || booking?.currency || 'VND'),
        checkout_url: String(payment?.checkout_url || ''),
        qr_url: String(payment?.qr_url || ''),
        updated_at: payment?.updated_at || booking?.updated_at || null,
        status_source: payment ? 'payment' : 'booking',
      };
    },
  };
}
