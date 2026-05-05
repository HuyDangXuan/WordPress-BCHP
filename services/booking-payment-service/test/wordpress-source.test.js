import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

function repoPath(relativePath) {
  return path.join(REPO_ROOT, relativePath);
}

const WORDPRESS_SOURCE_ROOTS = [
  repoPath('wordpress/wp-content/plugins/op-travel-core'),
  repoPath('wordpress/wp-content/themes/op-travel-shop'),
];

const WORDPRESS_SOURCE_EXTENSIONS = new Set(['.php', '.js', '.css']);

const MOJIBAKE_PATTERN = /[\u00a1\u00a2\u00a3\u00a9\u00aa\u00ac\u00af\u00b0\u00b4\u00b5\u00ba\u00bb\u00c2\u00c3\u00c4\u00c5\u00c6\u0192\u4e00-\u9fff\ufffd]|\u00e2[\u0080-\u00bf\u20ac\u201a-\u201e\u2018-\u201d\u2020-\u2022\u2122]/;

async function collectWordPressSourceFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const entryPath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      files.push(...await collectWordPressSourceFiles(entryPath));
      continue;
    }

    const extension = path.extname(entryPath);
    if (WORDPRESS_SOURCE_EXTENSIONS.has(extension)) {
      files.push(entryPath);
    }
  }

  return files;
}

test('theme source contains premium palette, typography, and workflow markers', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const workflow = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/workflow.php'),
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
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
    'utf8',
  );
  const paymentConfirm = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Rest/PaymentConfirmController.php'),
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
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/ProductMeta.php'),
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
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php'),
    'utf8',
  );
  const demoSeeder = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoSeeder.php'),
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
    repoPath('wordpress/wp-content/plugins/op-travel-core/op-travel-core.php'),
    'utf8',
  );
  const bootstrap = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php'),
    'utf8',
  );
  const env = await fs.readFile(
    repoPath('env/wordpress.env.example'),
    'utf8',
  );
  const orderMeta = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Support/OrderMeta.php'),
    'utf8',
  );
  const demoQrHooks = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php'),
    'utf8',
  );
  const syncFile = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/BookingServiceSync.php'),
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

test('plugin source registers the ZaloPay QR WooCommerce gateway', async () => {
  const pluginEntry = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/op-travel-core.php'),
    'utf8',
  );
  const bootstrap = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php'),
    'utf8',
  );
  const gateway = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Payment/ZaloPayQrGateway.php'),
    'utf8',
  );
  const gatewayMethod = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Payment/ZaloPayQrGatewayMethod.php'),
    'utf8',
  );
  const demoQrHooks = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php'),
    'utf8',
  );

  assert.match(pluginEntry, /ZaloPayQrGateway/);
  assert.match(bootstrap, /ZaloPayQrGateway::boot/);
  assert.match(gateway, /ZaloPayQrGatewayMethod.php/);
  assert.doesNotMatch(gateway, /function ensure_gateway_class[\s\S]*class ZaloPayQrGatewayMethod/);
  assert.match(gatewayMethod, /WC_Payment_Gateway/);
  assert.match(gatewayMethod, /op_travel_zalopay_qr/);
  assert.match(gatewayMethod, /ZaloPay QR/);
  assert.match(gatewayMethod, /process_payment/);
  assert.match(gatewayMethod, /set_payment_method/);
  assert.match(gatewayMethod, /get_checkout_order_received_url/);
  assert.match(demoQrHooks, /ZaloPay QR/);
  assert.match(demoQrHooks, /PAYMENT_CHECKOUT_URL/);
  assert.match(demoQrHooks, /PAYMENT_QR_URL/);
});

test('theme source reads real tour data and all payment states', async () => {
  const singleProduct = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php'),
    'utf8',
  );
  const thankyou = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php'),
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

test('featured tour cards map Vietnamese display fields with fallbacks', async () => {
  const contentProduct = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php'),
    'utf8',
  );

  assert.match(contentProduct, /_tour_code/);
  assert.match(contentProduct, /_duration_text/);
  assert.match(contentProduct, /_departure_city/);
  assert.match(contentProduct, /_available_departure_dates/);
  assert.match(contentProduct, /Điểm đến nổi bật/);
  assert.match(contentProduct, /Tour chọn lọc/);
  assert.match(contentProduct, /Mã tour đang cập nhật/);
  assert.match(contentProduct, /Lịch trình đang cập nhật/);
  assert.match(contentProduct, /Khởi hành linh hoạt/);
  assert.match(contentProduct, /Gần nhất/);
  assert.match(contentProduct, /Xem chi tiết tour/);
  assert.doesNotMatch(contentProduct, /Ã|Ä|áº|á»|Â|â†/);
});

test('home shortlist tour cards keep mapped content visible above the fold', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const setup = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/setup.php'),
    'utf8',
  );

  assert.match(css, /\.home\s+\.op-tour-card__media/);
  assert.match(css, /\.op-tour-card__media\s*{[^}]*display:\s*block/);
  assert.match(css, /aspect-ratio:\s*1\.45/);
  assert.match(css, /min-height:\s*0/);
  assert.match(setup, /filemtime\(\$theme_css_path\)/);
});

test('single tour booking panel aligns form controls vertically', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(css, /\.op-booking-panel\s+form\.cart\s*{[^}]*display:\s*grid/);
  assert.match(css, /\.op-booking-panel\s+\.op-booking-fields\s*{[^}]*display:\s*grid/);
  assert.match(css, /\.op-booking-panel\s+\.form-row\s*{[^}]*display:\s*grid/);
  assert.match(css, /\.op-booking-panel\s+\.form-row\s+label\s*{[^}]*display:\s*block/);
  assert.match(css, /\.op-booking-panel\s+\.op-booking-fields\s+(select|input|textarea)/);
  assert.match(css, /width:\s*100%/);
  assert.match(css, /\.op-booking-panel\s+\.quantity\s*{[^}]*display:\s*none/);
  assert.match(css, /\.op-booking-panel\s+\.single_add_to_cart_button\s*{[^}]*width:\s*100%/);
});

test('cart confirmation step is localized and uses aligned booking summary layout', async () => {
  const cart = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(cart, /Xác nhận giữ chỗ/);
  assert.match(cart, /Tổng giữ chỗ/);
  assert.match(cart, /Tạm tính/);
  assert.match(cart, /Tổng cộng/);
  assert.match(cart, /Tiếp tục thanh toán/);
  assert.match(cart, /Chờ thanh toán/);
  assert.match(cart, /op-cart-tour-card/);
  assert.match(cart, /op-cart-summary-row/);
  assert.doesNotMatch(cart, /Cart totals|Subtotal|Proceed to checkout/);
  assert.doesNotMatch(cart, /woocommerce_cart_totals|woocommerce_proceed_to_checkout/);
  assert.doesNotMatch(cart, /Ã|Ä|áº|á»|Â|â†/);

  assert.match(css, /\.woocommerce-cart-form\s+\.op-cart-tour-card\s*{/);
  assert.match(css, /\.op-cart-booking-grid\s*{/);
  assert.match(css, /\.op-cart-summary-row\s*{/);
});

test('checkout payment step is one column with localized booking summary', async () => {
  const checkout = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(checkout, /Bước 3 · Thanh toán/);
  assert.match(checkout, /Thông tin khách/);
  assert.match(checkout, /Tóm tắt booking/);
  assert.match(checkout, /Tóm tắt đơn và thanh toán/);
  assert.match(checkout, /Khởi hành/);
  assert.match(checkout, /op-checkout-stack/);
  assert.match(checkout, /op-checkout-customer-panel/);
  assert.match(checkout, /op-checkout-summary-panel/);
  assert.match(checkout, /'Billing details' => __\('Thông tin khách'/);
  assert.match(checkout, /'Product' => __\('Tour'/);
  assert.match(checkout, /'Subtotal' => __\('Tạm tính'/);
  assert.match(checkout, /'Your personal data will be used[\s\S]*Thông tin cá nhân của bạn/);
  assert.match(checkout, /woocommerce_gateway_description/);
  assert.doesNotMatch(checkout, /aria-label="[^"]*Checkout|esc_attr__\('Checkout'/);
  assert.doesNotMatch(checkout, /Ã|Ä|áº|á»|Â|â†/);

  assert.match(css, /\.op-checkout-stack\s*{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)/);
  assert.match(css, /\.op-checkout-customer-panel\s+\.form-row\s*{[^}]*display:\s*grid/);
  assert.match(css, /\.op-checkout-customer-panel\s+\.form-row\s+label\s*{[^}]*display:\s*block/);
  assert.match(css, /\.op-checkout-customer-panel\s+\.form-row\s+(input|select|textarea)/);
  assert.match(css, /\.op-checkout-summary-panel\s*{/);
  assert.match(css, /\.op-checkout-review-table\s*{/);
  assert.match(css, /\.op-checkout-review-table\s+\.wc-item-meta\s+li\s*{[^}]*grid-template-columns:\s*140px\s+minmax\(0,\s*1fr\)/);
  assert.match(css, /\.op-checkout-review-table\s+\.variation\s*{[^}]*grid-template-columns:\s*140px\s+minmax\(0,\s*1fr\)/);
  assert.match(css, /\.op-checkout-review-table\s+\.product-total\s*{[^}]*text-align:\s*right/);
});

test('thank you step renders localized custom completion summary without default Woo details', async () => {
  const thankyou = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(thankyou, /Bước 4 · Hoàn tất/);
  assert.match(thankyou, /op-thankyou-panel/);
  assert.match(thankyou, /op-thankyou-booking-card/);
  assert.match(thankyou, /op-thankyou-facts/);
  assert.match(thankyou, /Chờ thanh toán/);
  assert.match(thankyou, /Mã tour/);
  assert.match(thankyou, /Ngày khởi hành/);
  assert.match(thankyou, /Tổng thanh toán/);
  assert.doesNotMatch(thankyou, /do_action\('woocommerce_thankyou'/);
  assert.doesNotMatch(thankyou, /echo esc_html\(\$booking\['payment_status'\]\)|Order details|Billing address/);
  assert.doesNotMatch(thankyou, /Ã|Ä|áº|á»|Â|â†/);

  assert.match(css, /\.op-thankyou-panel\s*{/);
  assert.match(css, /\.op-thankyou-facts,\s*[\r\n]+\s*\.op-thankyou-detail-grid\s*{/);
  assert.match(css, /\.op-thankyou-booking-card\s*{/);
  assert.match(css, /\.op-thankyou-detail-grid\s*{/);
});

test('theme renders page content and seeded WooCommerce pages include shortcodes', async () => {
  const pageTemplate = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/page.php'),
    'utf8',
  );
  const cmsSetup = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
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
