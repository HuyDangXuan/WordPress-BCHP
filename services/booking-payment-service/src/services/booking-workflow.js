import { createHttpError } from '../lib/errors.js';

const ALLOWED_STATUSES = new Set(['pending', 'paid', 'failed', 'expired', 'cancelled']);
const REQUIRED_FIELDS = [
  'wordpress_order_id',
  'wordpress_order_key',
  'product_id',
  'tour_code',
  'tour_name',
  'departure_date',
  'adult_count',
  'customer_name',
  'customer_email',
  'customer_phone',
  'amount',
  'currency',
  'return_url',
  'cancel_url',
];

function normalizeStatus(value) {
  const normalized = String(value || 'pending').trim().toLowerCase();
  return ALLOWED_STATUSES.has(normalized) ? normalized : 'pending';
}

function toIsoString(value) {
  return new Date(value).toISOString();
}

function normalizeAmount(value) {
  return Math.round(Number(value || 0));
}

function collectMissingFields(rawBooking) {
  return REQUIRED_FIELDS.filter((field) => {
    const value = rawBooking[field];

    if (value === undefined || value === null) {
      return true;
    }

    if (typeof value === 'string' && value.trim() === '') {
      return true;
    }

    return false;
  });
}

function normalizeBooking(rawBooking, timestamp, existingBooking) {
  const wordpressOrderId = Number(rawBooking.wordpress_order_id);
  const bookingCode = `BK-${wordpressOrderId}`;

  return {
    booking_code: bookingCode,
    wordpress_order_id: wordpressOrderId,
    wordpress_order_key: String(rawBooking.wordpress_order_key),
    product_id: Number(rawBooking.product_id),
    tour_code: String(rawBooking.tour_code),
    tour_name: String(rawBooking.tour_name),
    departure_date: String(rawBooking.departure_date),
    adult_count: Number(rawBooking.adult_count),
    child_count: Number(rawBooking.child_count || 0),
    customer_note: String(rawBooking.customer_note || ''),
    customer_name: String(rawBooking.customer_name),
    customer_email: String(rawBooking.customer_email),
    customer_phone: String(rawBooking.customer_phone),
    amount: normalizeAmount(rawBooking.amount),
    currency: String(rawBooking.currency || 'VND'),
    payment_status: normalizeStatus(rawBooking.payment_status),
    created_at: existingBooking?.created_at || timestamp,
    updated_at: timestamp,
  };
}

export function createBookingWorkflow({
  store,
  paymentClient,
  payosClient,
  now = () => new Date().toISOString(),
}) {
  const providerClient = paymentClient ?? payosClient;

  return {
    async createBooking(rawBooking) {
      const missingFields = collectMissingFields(rawBooking);

      if (missingFields.length > 0) {
        throw createHttpError(400, {
          result: 'rejected',
          message: `Missing required booking fields: ${missingFields.join(', ')}`,
        });
      }

      if (Number(rawBooking.adult_count) < 1) {
        throw createHttpError(400, {
          result: 'rejected',
          message: 'adult_count must be at least 1.',
        });
      }

      const timestamp = toIsoString(now());
      const existingBooking = await store.getBookingByOrderId(Number(rawBooking.wordpress_order_id));
      const booking = normalizeBooking(rawBooking, timestamp, existingBooking);
      const paymentCode = `PMT-${booking.wordpress_order_id}`;

      let paymentLink = {
        provider: 'fallback',
        checkout_url: '',
        qr_url: '',
        provider_transaction_id: '',
      };

      try {
        paymentLink = await providerClient.createPaymentLink({
          ...booking,
          payment_code: paymentCode,
          return_url: String(rawBooking.return_url),
          cancel_url: String(rawBooking.cancel_url),
        });
      } catch (error) {
        paymentLink = {
          provider: 'fallback',
          checkout_url: '',
          qr_url: '',
          provider_transaction_id: '',
        };
      }

      const payment = {
        payment_code: paymentCode,
        booking_code: booking.booking_code,
        wordpress_order_id: booking.wordpress_order_id,
        gateway: paymentLink.provider || 'fallback',
        amount: booking.amount,
        currency: booking.currency,
        status: 'pending',
        checkout_url: paymentLink.checkout_url || '',
        qr_url: paymentLink.qr_url || '',
        provider_transaction_id: paymentLink.provider_transaction_id || '',
        created_at: timestamp,
        updated_at: timestamp,
      };

      await store.upsertBooking(booking);
      await store.upsertPayment(payment);

      return {
        booking_code: booking.booking_code,
        payment_code: payment.payment_code,
        payment_status: booking.payment_status,
        checkout_url: payment.checkout_url,
        qr_url: payment.qr_url,
        provider: payment.gateway,
      };
    },
  };
}
