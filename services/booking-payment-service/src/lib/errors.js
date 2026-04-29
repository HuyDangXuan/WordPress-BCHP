export function createHttpError(statusCode, payload) {
  const error = new Error(payload?.message || 'Request failed');
  error.statusCode = statusCode;
  error.payload = payload;
  return error;
}
