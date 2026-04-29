import { describeMongoDependency } from '../services/mongo.js';

export function handleHealth(env) {
  return {
    status: 'ok',
    service: 'booking-payment-service',
    dependencies: {
      mongodb: describeMongoDependency(env),
      wordpress: {
        status: 'configured',
        endpoint: env.WORDPRESS_CONFIRM_ENDPOINT,
      },
    },
  };
}
