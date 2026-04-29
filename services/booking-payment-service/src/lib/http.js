export function getRequestUrl(request) {
  return new URL(request.url, `http://${request.headers.host || 'localhost'}`);
}
