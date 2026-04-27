import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

test('theme source contains premium palette, typography, and workflow markers', async () => {
  const css = await fs.readFile(
    'wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css',
    'utf8',
  );
  const workflow = await fs.readFile(
    'wordpress/wp-content/themes/op-travel-shop/inc/workflow.php',
    'utf8',
  );

  assert.match(css, /--op-sand/);
  assert.match(css, /--op-cream/);
  assert.match(css, /--op-ink/);
  assert.match(css, /--op-gold/);
  assert.match(css, /--op-sea/);
  assert.match(css, /Cormorant Garamond/);
  assert.match(css, /Manrope/);
  assert.match(workflow, /Chọn tour/);
  assert.match(workflow, /Xác nhận giữ chỗ/);
  assert.match(workflow, /Thanh toán/);
  assert.match(workflow, /Hoàn tất/);
});

test('plugin source contains taxonomy and payment confirm contract markers', async () => {
  const cmsSetup = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php',
    'utf8',
  );
  const paymentConfirm = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/Rest/PaymentConfirmController.php',
    'utf8',
  );

  assert.match(cmsSetup, /destination/);
  assert.match(cmsSetup, /tour_style/);
  assert.match(cmsSetup, /promotion/);
  assert.match(cmsSetup, /testimonial/);
  assert.match(paymentConfirm, /payment-confirm/);
  assert.match(paymentConfirm, /PAYMENT_SYNC_SECRET/);
  assert.match(paymentConfirm, /pending/);
  assert.match(paymentConfirm, /paid/);
});
