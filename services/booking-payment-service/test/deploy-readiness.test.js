import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

async function read(path) {
  return await fs.readFile(path, 'utf8');
}

test('render blueprint defines the documented four-service topology', async () => {
  const blueprint = await read('render.yaml');

  assert.match(blueprint, /name:\s*wordpress/);
  assert.match(blueprint, /name:\s*booking-payment-service/);
  assert.match(blueprint, /name:\s*mysql/);
  assert.match(blueprint, /name:\s*mongodb/);

  assert.match(blueprint, /name:\s*wordpress[\s\S]*?type:\s*web[\s\S]*?runtime:\s*docker/);
  assert.match(blueprint, /name:\s*booking-payment-service[\s\S]*?type:\s*pserv[\s\S]*?runtime:\s*docker/);
  assert.match(blueprint, /name:\s*mysql[\s\S]*?type:\s*pserv[\s\S]*?runtime:\s*image[\s\S]*?mysql:8\.0/);
  assert.match(blueprint, /name:\s*mongodb[\s\S]*?type:\s*pserv[\s\S]*?runtime:\s*image[\s\S]*?mongo:7\.0/);
});

test('render blueprint keeps service env and persistent disks separated', async () => {
  const blueprint = await read('render.yaml');

  assert.match(blueprint, /WORDPRESS_DB_HOST/);
  assert.match(blueprint, /WORDPRESS_DB_NAME/);
  assert.match(blueprint, /WORDPRESS_DB_USER/);
  assert.match(blueprint, /WORDPRESS_DB_PASSWORD/);
  assert.match(blueprint, /PAYMENT_SYNC_SECRET/);
  assert.match(blueprint, /BOOKING_SERVICE_ENDPOINT/);
  assert.match(blueprint, /MONGO_URI/);
  assert.match(blueprint, /PAYOS_CLIENT_ID/);
  assert.match(blueprint, /PAYOS_API_KEY/);
  assert.match(blueprint, /PAYOS_CHECKSUM_KEY/);
  assert.match(blueprint, /WORDPRESS_CONFIRM_ENDPOINT/);

  assert.match(blueprint, /mountPath:\s*\/var\/www\/html\/wp-content\/uploads/);
  assert.match(blueprint, /mountPath:\s*\/var\/lib\/mysql/);
  assert.match(blueprint, /mountPath:\s*\/data\/db/);
});

test('production Docker and build context include WordPress app source without local-only artifacts', async () => {
  const dockerfile = await read('docker/wordpress/Dockerfile');
  const dockerignore = await read('.dockerignore');

  assert.match(dockerfile, /COPY\s+wordpress\/wp-content\/themes\/op-travel-shop/);
  assert.match(dockerfile, /COPY\s+wordpress\/wp-content\/plugins\/op-travel-core/);

  assert.match(dockerignore, /^\.git$/m);
  assert.match(dockerignore, /^node_modules\/$/m);
  assert.match(dockerignore, /^services\/booking-payment-service\/node_modules\/$/m);
  assert.match(dockerignore, /^wordpress\/wp-content\/uploads\/$/m);
  assert.match(dockerignore, /^\*\.log$/m);
});

test('deploy and demo runbooks cover Render operations and local acceptance', async () => {
  const renderRunbook = await read('docs/deploy/render-runbook.md');
  const demoRunbook = await read('docs/demo/local-e2e-acceptance.md');
  const readme = await read('README.md');

  assert.match(renderRunbook, /WordPress/);
  assert.match(renderRunbook, /booking-payment-service/);
  assert.match(renderRunbook, /persistent disk/i);
  assert.match(renderRunbook, /backup/i);
  assert.match(renderRunbook, /restore/i);
  assert.match(renderRunbook, /rollback/i);
  assert.match(renderRunbook, /post-deploy smoke/i);

  assert.match(demoRunbook, /pending/);
  assert.match(demoRunbook, /paid/);
  assert.match(demoRunbook, /webhook/i);
  assert.match(demoRunbook, /duplicate/i);
  assert.match(demoRunbook, /revenue/i);
  assert.match(demoRunbook, /fallback QR/i);

  assert.match(readme, /Render-ready/i);
  assert.match(readme, /docs\/deploy\/render-runbook\.md/);
  assert.match(readme, /docs\/demo\/local-e2e-acceptance\.md/);
});

test('acceptance smoke script checks local endpoints and mojibake artifacts', async () => {
  const smokeScript = await read('scripts/acceptance-smoke.mjs');

  assert.match(smokeScript, /WORDPRESS_BASE_URL/);
  assert.match(smokeScript, /SERVICE_BASE_URL/);
  assert.match(smokeScript, /localhost:8080/);
  assert.match(smokeScript, /localhost:8787/);
  assert.match(smokeScript, /\/health/);
  assert.match(smokeScript, /\/tours\//);
  assert.match(smokeScript, /premium-hoang-hon-phu-quoc/);
  assert.match(smokeScript, /payment-confirm/);
  assert.match(smokeScript, /mojibake/i);
});
