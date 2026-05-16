const REQUIRED_KEYS = [
  'MONGO_URI',
  'PAYOS_CLIENT_ID',
  'PAYOS_API_KEY',
  'PAYOS_CHECKSUM_KEY',
  'PAYMENT_SYNC_SECRET',
  'WORDPRESS_CONFIRM_ENDPOINT',
];

const OPTIONAL_KEYS = [
  'SEPAY_API_KEY',
  'SEPAY_API_TOKEN',
  'SEPAY_API_BASE_URL',
  'SEPAY_BANK_CODE',
  'SEPAY_ACCOUNT_NUMBER',
  'SEPAY_ACCOUNT_NAME',
  'SEPAY_QR_BASE_URL',
  'ZALOPAY_APP_ID',
  'ZALOPAY_KEY1',
  'ZALOPAY_KEY2',
  'ZALOPAY_ENV',
  'ZALOPAY_CALLBACK_URL',
  'ZALOPAY_PREFERRED_PAYMENT_METHOD',
  'ZALOPAY_CREATE_ORDER_URL',
  'PAYOS_API_BASE_URL',
];

export function loadEnv(source = process.env) {
  for (const key of REQUIRED_KEYS) {
    if (!source[key]) {
      throw new Error(`Missing required environment variable: ${key}`);
    }
  }

  return Object.fromEntries(
    [...REQUIRED_KEYS, ...OPTIONAL_KEYS].map((key) => [key, source[key]]),
  );
}

export { OPTIONAL_KEYS, REQUIRED_KEYS };
