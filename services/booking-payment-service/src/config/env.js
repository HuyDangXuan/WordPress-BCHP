const REQUIRED_KEYS = [
  'MONGO_URI',
  'PAYOS_CLIENT_ID',
  'PAYOS_API_KEY',
  'PAYOS_CHECKSUM_KEY',
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
