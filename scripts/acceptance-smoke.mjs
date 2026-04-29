const WORDPRESS_BASE_URL = (process.env.WORDPRESS_BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const SERVICE_BASE_URL = (process.env.SERVICE_BASE_URL || 'http://localhost:8787').replace(/\/$/, '');

const mojibakePattern =
  /[\u00a1\u00a2\u00a3\u00a9\u00aa\u00ac\u00af\u00b0\u00b4\u00b5\u00ba\u00bb\u00c2\u00c3\u00c4\u00c5\u00c6\u0192\u4e00-\u9fff\ufffd]|\u00e2[\u0080-\u00bf\u20ac\u201a-\u201e\u2018-\u201d\u2020-\u2022\u2122]/;

const checks = [];

function record(name, ok, detail = '') {
  checks.push({ name, ok, detail });
  const status = ok ? 'ok' : 'not ok';
  console.log(`${status} - ${name}${detail ? ` (${detail})` : ''}`);
}

async function fetchText(name, url, options = {}) {
  try {
    const response = await fetch(url, options);
    const text = await response.text();
    return { response, text };
  } catch (error) {
    record(name, false, error.message);
    return null;
  }
}

function assertNoMojibake(name, text) {
  const hasMojibake = mojibakePattern.test(text);
  record(`${name} has no mojibake artifacts`, ! hasMojibake, hasMojibake ? 'mojibake=yes' : 'mojibake=no');
}

async function checkHtml(name, path) {
  const result = await fetchText(name, `${WORDPRESS_BASE_URL}${path}`);

  if (! result) {
    return;
  }

  record(name, result.response.ok, `status=${result.response.status}`);

  if (result.response.ok) {
    assertNoMojibake(name, result.text);
  }
}

async function checkServiceHealth() {
  const result = await fetchText('service health', `${SERVICE_BASE_URL}/health`);

  if (! result) {
    return;
  }

  let body = null;
  try {
    body = JSON.parse(result.text);
  } catch (error) {
    record('service health returns JSON', false, error.message);
  }

  record('service health', result.response.ok && body?.status === 'ok', `status=${result.response.status}`);
}

async function checkPaymentConfirmRoute() {
  const result = await fetchText('payment-confirm route', `${WORDPRESS_BASE_URL}/wp-json/op-travel/v1/payment-confirm`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: '{}',
  });

  if (! result) {
    return;
  }

  const routeExists = result.response.status !== 404 && result.response.status < 500;
  record('payment-confirm route exists', routeExists, `status=${result.response.status}`);
}

await checkServiceHealth();
await checkHtml('wordpress homepage', '/');
await checkHtml('tour archive', '/tours/');
await checkHtml('seeded single tour', '/tours/premium-hoang-hon-phu-quoc/');
await checkPaymentConfirmRoute();

const failed = checks.filter((check) => ! check.ok);

if (failed.length > 0) {
  console.error(`acceptance smoke failed: ${failed.length} check(s) failed`);
  process.exit(1);
}

console.log(`acceptance smoke passed: ${checks.length} checks`);
