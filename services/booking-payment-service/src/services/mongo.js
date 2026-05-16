import { MongoClient } from 'mongodb';

const DEFAULT_DATABASE = 'hv_travel';

function getDatabaseName(mongoUri) {
  const withoutQuery = String(mongoUri).split('?')[0];
  const database = withoutQuery.split('/').pop();

  return database || DEFAULT_DATABASE;
}

export function describeMongoDependency(env) {
  return {
    status: 'configured',
    database: getDatabaseName(env.MONGO_URI),
  };
}

export function createMongoStore(env, options = {}) {
  const clientFactory = options.clientFactory ?? (() => new MongoClient(env.MONGO_URI));
  const databaseName = options.databaseName ?? getDatabaseName(env.MONGO_URI);
  let databasePromise;

  async function getDatabase() {
    if (! databasePromise) {
      databasePromise = (async () => {
        const client = clientFactory();
        await client.connect();
        return client.db(databaseName);
      })();
    }

    return await databasePromise;
  }

  async function getCollection(name) {
    const database = await getDatabase();
    return database.collection(name);
  }

  return {
    async upsertBooking(booking) {
      const collection = await getCollection('bookings');

      await collection.updateOne(
        { wordpress_order_id: booking.wordpress_order_id },
        { $set: booking },
        { upsert: true },
      );

      return { ...booking };
    },

    async getBookingByOrderId(wordpressOrderId) {
      const collection = await getCollection('bookings');
      return await collection.findOne({ wordpress_order_id: wordpressOrderId });
    },

    async listBookings() {
      const collection = await getCollection('bookings');
      return await collection.find({}).toArray();
    },

    async upsertPayment(payment) {
      const collection = await getCollection('payments');

      await collection.updateOne(
        { payment_code: payment.payment_code },
        { $set: payment },
        { upsert: true },
      );

      return { ...payment };
    },

    async getPaymentByCode(paymentCode) {
      const collection = await getCollection('payments');
      return await collection.findOne({ payment_code: paymentCode });
    },

    async getPaymentByOrderId(wordpressOrderId) {
      const collection = await getCollection('payments');
      return await collection.findOne({ wordpress_order_id: wordpressOrderId });
    },

    async insertPaymentEvent(paymentEvent) {
      const collection = await getCollection('payment_events');

      try {
        await collection.insertOne({
          _id: paymentEvent.idempotency_key,
          ...paymentEvent,
        });
      } catch (error) {
        if (error?.code === 11000) {
          const duplicateError = new Error('Duplicate payment event');
          duplicateError.code = 'DUPLICATE_EVENT';
          throw duplicateError;
        }

        throw error;
      }

      return { ...paymentEvent };
    },

    async listPaymentEvents() {
      const collection = await getCollection('payment_events');
      return await collection.find({}).toArray();
    },

    async clearDemoData() {
      const [bookingsResult, paymentsResult, paymentEventsResult] = await Promise.all([
        (await getCollection('bookings')).deleteMany({}),
        (await getCollection('payments')).deleteMany({}),
        (await getCollection('payment_events')).deleteMany({}),
      ]);

      return {
        bookings: bookingsResult.deletedCount ?? 0,
        payments: paymentsResult.deletedCount ?? 0,
        payment_events: paymentEventsResult.deletedCount ?? 0,
      };
    },
  };
}
