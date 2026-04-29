import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

const WORDPRESS_SOURCE_ROOTS = [
  'wordpress/wp-content/plugins/op-travel-core',
  'wordpress/wp-content/themes/op-travel-shop',
];

const WORDPRESS_SOURCE_EXTENSIONS = new Set(['.php', '.js', '.css']);

const MOJIBAKE_PATTERN = /[\u00a1\u00a2\u00a3\u00a9\u00aa\u00ac\u00af\u00b0\u00b4\u00b5\u00ba\u00bb\u00c2\u00c3\u00c4\u00c5\u00c6\u0192\u4e00-\u9fff\ufffd]|\u00e2[\u0080-\u00bf\u20ac\u201a-\u201e\u2018-\u201d\u2020-\u2022\u2122]/;

async function collectWordPressSourceFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const path = `${directory}/${entry.name}`;

    if (entry.isDirectory()) {
      files.push(...await collectWordPressSourceFiles(path));
      continue;
    }

    const extension = path.slice(path.lastIndexOf('.'));
    if (WORDPRESS_SOURCE_EXTENSIONS.has(extension)) {
      files.push(path);
    }
  }

  return files;
}

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

test('plugin source defines the full tour metadata schema for phase 5', async () => {
  const productMeta = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/ProductMeta.php',
    'utf8',
  );

  assert.match(productMeta, /_tour_code/);
  assert.match(productMeta, /_duration_text/);
  assert.match(productMeta, /_departure_city/);
  assert.match(productMeta, /_available_departure_dates/);
  assert.match(productMeta, /_tour_highlights/);
  assert.match(productMeta, /_tour_itinerary/);
  assert.match(productMeta, /_tour_includes/);
  assert.match(productMeta, /_tour_excludes/);
  assert.match(productMeta, /_meeting_point/);
  assert.match(productMeta, /_gallery_ids/);
});

test('plugin source contains admin seeder and booking review markers', async () => {
  const bookingHooks = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php',
    'utf8',
  );
  const demoSeeder = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/DemoSeeder.php',
    'utf8',
  );

  assert.match(bookingHooks, /available_departure_dates/);
  assert.match(bookingHooks, /woocommerce_admin_order_data_after_order_details/);
  assert.match(bookingHooks, /tour_code/);
  assert.match(bookingHooks, /tour_name/);
  assert.match(bookingHooks, /payment_status/);
  assert.match(demoSeeder, /add_management_page/);
  assert.match(demoSeeder, /wp_update_term/);
  assert.match(demoSeeder, /Seed Demo Data/);
  assert.match(demoSeeder, /destination/);
  assert.match(demoSeeder, /tour_style/);
  assert.match(demoSeeder, /promotion/);
  assert.match(demoSeeder, /testimonial/);
});

test('plugin source contains booking service sync and payment meta markers', async () => {
  const pluginEntry = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/op-travel-core.php',
    'utf8',
  );
  const bootstrap = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php',
    'utf8',
  );
  const env = await fs.readFile(
    'env/wordpress.env.example',
    'utf8',
  );
  const orderMeta = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/Support/OrderMeta.php',
    'utf8',
  );
  const demoQrHooks = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php',
    'utf8',
  );
  const syncFile = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/BookingServiceSync.php',
    'utf8',
  );

  assert.match(pluginEntry, /BookingServiceSync/);
  assert.match(bootstrap, /BookingServiceSync/);
  assert.match(env, /BOOKING_SERVICE_ENDPOINT/);
  assert.match(orderMeta, /PAYMENT_CHECKOUT_URL/);
  assert.match(orderMeta, /PAYMENT_QR_URL/);
  assert.match(orderMeta, /BOOKING_CODE/);
  assert.match(orderMeta, /PAYMENT_PROVIDER/);
  assert.match(syncFile, /woocommerce_checkout_order_processed/);
  assert.match(syncFile, /BOOKING_SERVICE_ENDPOINT/);
  assert.match(syncFile, /booking_code/);
  assert.match(syncFile, /payment_code/);
  assert.match(syncFile, /payment_qr_url/);
  assert.match(syncFile, /payment_checkout_url/);
  assert.match(demoQrHooks, /PAYMENT_QR_URL/);
  assert.match(demoQrHooks, /PAYMENT_CHECKOUT_URL/);
  assert.match(demoQrHooks, /fallback/i);
});

test('theme source reads real tour data and all payment states', async () => {
  const singleProduct = await fs.readFile(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php',
    'utf8',
  );
  const thankyou = await fs.readFile(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php',
    'utf8',
  );

  assert.match(singleProduct, /_tour_code/);
  assert.match(singleProduct, /_tour_highlights/);
  assert.match(singleProduct, /_tour_itinerary/);
  assert.match(singleProduct, /_tour_includes/);
  assert.match(singleProduct, /_tour_excludes/);
  assert.match(singleProduct, /_available_departure_dates/);
  assert.match(singleProduct, /wc_get_product\(get_the_ID\(\)\)/);
  assert.match(thankyou, /pending/);
  assert.match(thankyou, /paid/);
  assert.match(thankyou, /failed/);
  assert.match(thankyou, /expired/);
  assert.match(thankyou, /cancelled/);
});

test('theme renders page content and seeded WooCommerce pages include shortcodes', async () => {
  const pageTemplate = await fs.readFile(
    'wordpress/wp-content/themes/op-travel-shop/page.php',
    'utf8',
  );
  const cmsSetup = await fs.readFile(
    'wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php',
    'utf8',
  );

  assert.match(pageTemplate, /the_content/);
  assert.match(cmsSetup, /\[woocommerce_cart\]/);
  assert.match(cmsSetup, /\[woocommerce_checkout\]/);
  assert.match(cmsSetup, /\[woocommerce_my_account\]/);
});

test('maintained WordPress source does not contain mojibake artifacts', async () => {
  const files = (await Promise.all(
    WORDPRESS_SOURCE_ROOTS.map((root) => collectWordPressSourceFiles(root)),
  )).flat();
  const matches = [];

  for (const file of files) {
    const text = await fs.readFile(file, 'utf8');
    text.split(/\r?\n/).forEach((line, index) => {
      if (MOJIBAKE_PATTERN.test(line)) {
        matches.push(`${file}:${index + 1}: ${line.trim()}`);
      }
    });
  }

  assert.deepEqual(matches, []);
});
