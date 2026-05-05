const REQUIRED_KEYS = [
  'MONGO_URI',
  'PAYOS_CLIENT_ID',
  'PAYOS_API_KEY',
  'PAYOS_CHECKSUM_KEY',
  'ZALOPAY_APP_ID',
  'ZALOPAY_KEY1',
  'ZALOPAY_KEY2',
  'ZALOPAY_ENV',
  'ZALOPAY_CALLBACK_URL',
  'PAYMENT_SYNC_SECRET',
  'WORDPRESS_CONFIRM_ENDPOINT',
];

export function loadEnv(source = process.env) {
  for (const key of REQUIRED_KEYS) {
    if (!source[key]) {
      throw new Error(`Missing required environment variable: ${key}`);
    }
  }

  return Object.fromEntries(REQUIRED_KEYS.map((key) => [key, source[key]]));
}

export { REQUIRED_KEYS };
