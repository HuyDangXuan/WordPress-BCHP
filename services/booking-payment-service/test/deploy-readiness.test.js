import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

async function read(relativePath) {
  return await fs.readFile(path.join(REPO_ROOT, relativePath), 'utf8');
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
  assert.match(blueprint, /SEPAY_API_KEY/);
  assert.match(blueprint, /SEPAY_API_TOKEN/);
  assert.match(blueprint, /SEPAY_BANK_CODE/);
  assert.match(blueprint, /SEPAY_ACCOUNT_NUMBER/);
  assert.match(blueprint, /SEPAY_ACCOUNT_NAME/);
  assert.match(blueprint, /WORDPRESS_CONFIRM_ENDPOINT/);

  assert.match(blueprint, /mountPath:\s*\/var\/www\/html\/wp-content\/uploads/);
  assert.match(blueprint, /mountPath:\s*\/var\/lib\/mysql/);
  assert.match(blueprint, /mountPath:\s*\/data\/db/);
});

test('free Render demo blueprint deploys only the booking service on the free plan', async () => {
  const blueprint = await read('render.free-demo.yaml');

  assert.match(blueprint, /name:\s*booking-payment-service-free/);
  assert.match(blueprint, /type:\s*web/);
  assert.match(blueprint, /runtime:\s*docker/);
  assert.match(blueprint, /plan:\s*free/);
  assert.match(blueprint, /dockerfilePath:\s*docker\/service\/Dockerfile/);
  assert.match(blueprint, /healthCheckPath:\s*\/health/);
  assert.match(blueprint, /MONGO_URI/);
  assert.match(blueprint, /PAYOS_CLIENT_ID/);
  assert.match(blueprint, /PAYOS_API_KEY/);
  assert.match(blueprint, /PAYOS_CHECKSUM_KEY/);
  assert.match(blueprint, /SEPAY_API_KEY/);
  assert.match(blueprint, /SEPAY_API_TOKEN/);
  assert.match(blueprint, /SEPAY_BANK_CODE/);
  assert.match(blueprint, /SEPAY_ACCOUNT_NUMBER/);
  assert.match(blueprint, /SEPAY_ACCOUNT_NAME/);
  assert.match(blueprint, /PAYMENT_SYNC_SECRET/);
  assert.match(blueprint, /WORDPRESS_CONFIRM_ENDPOINT/);

  assert.doesNotMatch(blueprint, /name:\s*wordpress/);
  assert.doesNotMatch(blueprint, /name:\s*mysql/);
  assert.doesNotMatch(blueprint, /name:\s*mongodb/);
  assert.doesNotMatch(blueprint, /type:\s*pserv/);
  assert.doesNotMatch(blueprint, /mountPath:/);
});

test('local Docker compose includes an optional Cloudflare tunnel service for custom webhook domains', async () => {
  const compose = await read('docker/compose.local.yml');
  const tunnelEnv = await read('env/tunnel.env.example');

  assert.match(compose, /cloudflared:/);
  assert.match(compose, /image:\s*cloudflare\/cloudflared:/);
  assert.match(compose, /profiles:\s*\n\s*-\s*public-webhook/);
  assert.match(compose, /command:\s*tunnel --no-autoupdate run/);
  assert.match(compose, /env_file:\s*\n\s*-\s*\.\.\/env\/tunnel\.env/);
  assert.match(compose, /booking-payment-service:[\s\S]*cloudflared:/);
  assert.match(compose, /cloudflared:[\s\S]*depends_on:[\s\S]*booking-payment-service:/);
  assert.match(tunnelEnv, /^TUNNEL_TOKEN=change-me$/m);
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
  const freeDemoRunbook = await read('docs/deploy/render-free-demo.md');
  const demoRunbook = await read('docs/demo/local-e2e-acceptance.md');
  const readme = await read('README.md');

  assert.match(renderRunbook, /WordPress/);
  assert.match(renderRunbook, /booking-payment-service/);
  assert.match(renderRunbook, /persistent disk/i);
  assert.match(renderRunbook, /backup/i);
  assert.match(renderRunbook, /restore/i);
  assert.match(renderRunbook, /rollback/i);
  assert.match(renderRunbook, /post-deploy smoke/i);

  assert.match(freeDemoRunbook, /render\.free-demo\.yaml/);
  assert.match(freeDemoRunbook, /Free Web Service/i);
  assert.match(freeDemoRunbook, /MongoDB Atlas/i);
  assert.match(freeDemoRunbook, /WordPress.*local/i);
  assert.match(freeDemoRunbook, /not.*full-stack/i);

  assert.match(demoRunbook, /pending/);
  assert.match(demoRunbook, /paid/);
  assert.match(demoRunbook, /webhook/i);
  assert.match(demoRunbook, /duplicate/i);
  assert.match(demoRunbook, /revenue/i);
  assert.match(demoRunbook, /fallback QR/i);
  assert.match(demoRunbook, /cloudflared/i);
  assert.match(demoRunbook, /api\/payments\/sepay\/webhook/);

  assert.match(readme, /Render-ready/i);
  assert.match(readme, /render\.free-demo\.yaml/);
  assert.match(readme, /docs\/deploy\/render-free-demo\.md/);
  assert.match(readme, /docs\/deploy\/render-runbook\.md/);
  assert.match(readme, /docs\/demo\/local-e2e-acceptance\.md/);
  assert.match(readme, /public-webhook/i);
  assert.match(readme, /TUNNEL_TOKEN/);
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
