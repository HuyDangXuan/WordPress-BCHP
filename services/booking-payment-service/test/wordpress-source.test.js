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
  repoPath('wordpress/wp-content/plugins/op-travel-sepay'),
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

async function readSourceIfPresent(relativePath) {
  try {
    return await fs.readFile(repoPath(relativePath), 'utf8');
  } catch (error) {
    if (error && error.code === 'ENOENT') {
      return '';
    }

    throw error;
  }
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
  const setup = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/setup.php'),
    'utf8',
  );

  assert.match(css, /--op-sand/);
  assert.match(css, /--op-cream/);
  assert.match(css, /--op-ink/);
  assert.match(css, /--op-gold/);
  assert.match(css, /--op-sea/);
  assert.match(css, /--op-font-primary:\s*"Be Vietnam Pro",\s*sans-serif/);
  assert.match(css, /body\s*{[^}]*font-family:\s*var\(--op-font-primary\)/);
  assert.match(css, /h1,[\s\S]*?h6\s*{[^}]*font-family:\s*var\(--op-font-primary\)/);
  assert.match(css, /\.op-brand__mark\s*{[^}]*font-family:\s*var\(--op-font-primary\)/);
  assert.doesNotMatch(css, /Cormorant Garamond|Manrope/);
  assert.match(setup, /family=Be\+Vietnam\+Pro:wght@400;500;600;700;800/);
  assert.doesNotMatch(setup, /Cormorant\+Garamond|Manrope/);
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

test('plugin source configures WooCommerce to use VND with dong symbol formatting', async () => {
  const cmsSetup = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
    'utf8',
  );

  assert.match(cmsSetup, /woocommerce_currency_symbol/);
  assert.match(cmsSetup, /filter_woocommerce_currency_symbol/);
  assert.match(cmsSetup, /update_option\('woocommerce_currency',\s*'VND'\)/);
  assert.match(cmsSetup, /update_option\('woocommerce_currency_pos',\s*'right'\)/);
  assert.match(cmsSetup, /update_option\('woocommerce_price_thousand_sep',\s*'\.'\)/);
  assert.match(cmsSetup, /update_option\('woocommerce_price_decimal_sep',\s*','\)/);
  assert.match(cmsSetup, /update_option\('woocommerce_price_num_decimals',\s*'0'\)/);
  assert.match(cmsSetup, /return\s*'đ'/);
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

test('demo seeder provides enough tour records for storefront skeleton testing', async () => {
  const demoSeeder = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoSeeder.php'),
    'utf8',
  );
  const productSlugs = [...demoSeeder.matchAll(/'slug'\s*=>\s*'([^']+)'/g)]
    .map((match) => match[1])
    .filter((slug) => [
      'premium-hoang-hon-phu-quoc',
      'di-san-da-nang-hoi-an',
      'cao-nguyen-da-ha-giang',
      'con-dao-blue-retreat',
      'sapa-cloud-hike',
      'ninh-binh-heritage-escape',
      'da-lat-slow-living',
      'mekong-private-cruise',
      'quy-nhon-coastal-hideaway',
      'hue-imperial-food-trail',
      'cat-ba-lan-ha-adventure',
      'moc-chau-tea-valley',
      'sepay-test-tour',
    ].includes(slug));

  assert.equal(new Set(productSlugs).size, 13);
  assert.match(demoSeeder, /return\s+array_merge\(\s*\[/);
  assert.doesNotMatch(demoSeeder, /return\s+\[[\s\S]*?\],\s*self::get_additional_demo_products\(\);/);
  assert.match(demoSeeder, /Con Dao Blue Retreat/);
  assert.match(demoSeeder, /Sapa Cloud Hike/);
  assert.match(demoSeeder, /Ninh Binh Heritage Escape/);
  assert.match(demoSeeder, /Moc Chau Tea Valley/);
  assert.match(demoSeeder, /SePay Test Tour 2K/);
  assert.match(demoSeeder, /'price'\s*=>\s*'2000'/);
});

test('plugin source contains booking service sync and payment meta markers', async () => {
  const corePluginEntry = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/op-travel-core.php'),
    'utf8',
  );
  const coreBootstrap = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php'),
    'utf8',
  );
  const paymentPluginEntry = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/op-travel-sepay.php',
  );
  const paymentBootstrap = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/Bootstrap.php',
  );
  const env = await fs.readFile(
    repoPath('env/wordpress.env.example'),
    'utf8',
  );
  const orderMeta = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Support/OrderMeta.php'),
    'utf8',
  );
  const qrHooks = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/SePayPaymentQrHooks.php',
  );
  const syncFile = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/BookingServiceSync.php',
  );

  assert.doesNotMatch(corePluginEntry, /BookingServiceSync|DemoPaymentQrHooks|ZaloPayQrGateway/);
  assert.doesNotMatch(coreBootstrap, /BookingServiceSync|DemoPaymentQrHooks|ZaloPayQrGateway/);
  assert.match(paymentPluginEntry, /OP Travel SePay|BookingServiceSync|SePayPaymentQrHooks|SePayQrGateway/);
  assert.match(paymentBootstrap, /BookingServiceSync::boot/);
  assert.match(paymentBootstrap, /SePayPaymentQrHooks::boot/);
  assert.match(paymentBootstrap, /SePayQrGateway::boot/);
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
  assert.match(syncFile, /payment_diagnostics/);
  assert.match(syncFile, /SePay diagnostics/);
  assert.match(qrHooks, /PAYMENT_QR_URL/);
  assert.match(qrHooks, /PAYMENT_CHECKOUT_URL/);
  assert.match(qrHooks, /fallback/i);
});

test('plugin source registers the SePay QR WooCommerce gateway in the dedicated payment plugin', async () => {
  const pluginEntry = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/op-travel-sepay.php',
  );
  const bootstrap = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/Bootstrap.php',
  );
  const gateway = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/Payment/SePayQrGateway.php',
  );
  const gatewayMethod = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/Payment/SePayQrGatewayMethod.php',
  );
  const qrHooks = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/SePayPaymentQrHooks.php',
  );

  assert.match(pluginEntry, /SePayQrGateway/);
  assert.match(bootstrap, /SePayQrGateway::boot/);
  assert.match(gateway, /SePayQrGatewayMethod.php/);
  assert.doesNotMatch(gateway, /function ensure_gateway_class[\s\S]*class SePayQrGatewayMethod/);
  assert.match(gatewayMethod, /WC_Payment_Gateway/);
  assert.match(gatewayMethod, /op_travel_sepay_qr/);
  assert.match(gatewayMethod, /SePay QR/);
  assert.match(gatewayMethod, /process_payment/);
  assert.match(gatewayMethod, /set_payment_method/);
  assert.match(gatewayMethod, /get_checkout_order_received_url/);
  assert.match(qrHooks, /SePay QR/);
  assert.match(qrHooks, /PAYMENT_CHECKOUT_URL/);
  assert.match(qrHooks, /PAYMENT_QR_URL/);
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
  const frontPage = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/front-page.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const setup = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/setup.php'),
    'utf8',
  );

  assert.match(frontPage, /'posts_per_page'\s*=>\s*6/);
  assert.doesNotMatch(frontPage, /'posts_per_page'\s*=>\s*3/);
  assert.match(css, /\.home\s+\.op-tour-card__media/);
  assert.match(css, /\.op-tour-card__media\s*{[^}]*display:\s*block/);
  assert.match(css, /aspect-ratio:\s*1\.45/);
  assert.match(css, /min-height:\s*0/);
  assert.match(setup, /filemtime\(\$theme_css_path\)/);
});

test('theme source defines reusable skeleton primitives and motion safeguards', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );
  const cmsSetup = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
    'utf8',
  );
  const demoSeeder = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoSeeder.php'),
    'utf8',
  );
  const archive = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php'),
    'utf8',
  );
  const contentProduct = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php'),
    'utf8',
  );
  const cart = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php'),
    'utf8',
  );
  const checkout = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php'),
    'utf8',
  );
  const thankyou = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php'),
    'utf8',
  );

  assert.match(css, /\.op-skeleton\s*{/);
  assert.match(css, /\.op-skeleton-line\s*{/);
  assert.match(css, /\.op-skeleton-media\s*{/);
  assert.match(css, /\.op-skeleton-card\s*{/);
  assert.match(css, /\.op-is-loading\s*{/);
  assert.match(css, /\.op-button--loading\s*{/);
  assert.match(css, /\.op-has-loaded/);
  assert.match(css, /@keyframes\s+op-skeleton-shimmer/);
  assert.match(css, /@keyframes\s+op-skeleton-pulse/);
  assert.match(css, /@media\s*\(prefers-reduced-motion:\s*reduce\)/);

  assert.match(js, /function\s+markOpLoading/);
  assert.match(js, /function\s+initOpSkeletonImages/);
  assert.match(js, /function\s+initOpLoadingForms/);
  assert.match(js, /function\s+initWooCommerceLoadingStates/);
  assert.match(js, /update_checkout/);
  assert.match(js, /updated_checkout/);
  assert.match(js, /checkout_error/);
  assert.match(js, /wc_fragments_refreshed/);
  assert.match(js, /pageshow/);

  assert.match(archive, /data-op-loading-form/);
  assert.match(archive, /data-op-skeleton-target="\.op-tour-grid"/);
  assert.match(contentProduct, /op-skeleton-media/);
  assert.match(cart, /data-op-loading-link/);
  assert.match(checkout, /data-op-loading-form/);
  assert.match(checkout, /data-op-skeleton-target="#order_review"/);
  assert.match(thankyou, /op-thankyou-panel[\s\S]*data-op-skeleton-target="payment-state"/);
});

test('tour archive renders theme pagination that preserves filter state', async () => {
  const archive = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/archive-product.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(archive, /op-tour-pagination/);
  assert.match(archive, /paginate_links/);
  assert.match(archive, /'add_args'\s*=>\s*\$pagination_args/);
  assert.match(archive, /destination/);
  assert.match(archive, /tour_style/);
  assert.match(archive, /data-op-loading-link/);
  assert.doesNotMatch(archive, /woocommerce_pagination\(\)/);

  assert.match(css, /\.op-tour-pagination\s*{/);
  assert.match(css, /\.op-tour-pagination\s+\.page-numbers\s*{/);
  assert.match(css, /\.op-tour-pagination\s+\.page-numbers\.current\s*{/);
  assert.match(css, /\.op-tour-pagination__summary\s*{/);
});

test('theme provides WooCommerce-native login and register UI with header auth actions', async () => {
  const header = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/header.php'),
    'utf8',
  );
  const authTemplate = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/form-login.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );
  const cmsSetup = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
    'utf8',
  );
  const demoSeeder = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/DemoSeeder.php'),
    'utf8',
  );

  assert.match(header, /op-header-auth/);
  assert.match(header, /wc_get_page_permalink\('myaccount'\)/);
  assert.match(header, /is_user_logged_in\(\)/);
  assert.match(header, /wp_logout_url/);
  assert.match(header, /op-header-auth__register/);

  assert.match(authTemplate, /op-auth-shell/);
  assert.match(authTemplate, /op-auth-panel--login/);
  assert.match(authTemplate, /op-auth-panel--register/);
  assert.match(authTemplate, /woocommerce-form-login/);
  assert.match(authTemplate, /woocommerce-login-nonce/);
  assert.match(authTemplate, /woocommerce_enable_myaccount_registration/);
  assert.match(authTemplate, /op_travel_registration_action/);
  assert.match(authTemplate, /value="send_otp"/);
  assert.match(authTemplate, /value="verify_otp"/);
  assert.match(authTemplate, /value="resend_otp"/);
  assert.match(authTemplate, /op_travel_registration_send_otp/);
  assert.match(authTemplate, /op_travel_registration_verify_otp/);
  assert.match(authTemplate, /op_travel_registration_resend_otp/);
  assert.match(authTemplate, /name="otp_token"/);
  assert.match(authTemplate, /name="otp_code"/);
  assert.match(authTemplate, /id="reg_password"/);
  assert.match(authTemplate, /id="reg_password_confirm"/);
  assert.match(authTemplate, /op-auth-resend-form/);
  assert.match(authTemplate, /data-op-loading-form/);
  assert.match(authTemplate, /do_action\(\s*'woocommerce_login_form'/);
  assert.match(authTemplate, /name="redirect"/);
  assert.doesNotMatch(authTemplate, /name="register"/);
  assert.doesNotMatch(authTemplate, /woocommerce-form-register/);
  assert.doesNotMatch(authTemplate, /woocommerce-register-nonce/);

  assert.match(css, /\.op-header-auth\s*{/);
  assert.match(css, /\.op-auth-shell\s*{/);
  assert.match(css, /\.op-auth-panel\s*{/);
  assert.match(css, /\.op-auth-panel--register\s*{/);
  assert.match(css, /\.op-auth-form\s+\.form-row\s*{/);
  assert.match(css, /\.op-auth-panel\.op-is-loading/);
  assert.match(css, /button:not\([^)]*\.show-password-input/);
  assert.match(css, /\.op-auth-form\s+\.show-password-input\s*{/);
  assert.match(css, /\.op-auth-form\s+\.show-password-input::before\s*{/);
  assert.match(css, /\.op-auth-form\s+\.show-password-input\.display-password::before\s*{/);
  assert.match(css, /\.op-auth-otp-step\s*{/);
  assert.match(css, /\.op-auth-password-grid\s*{/);
  assert.match(css, /\.op-auth-resend-form\s*{/);

  assert.match(js, /function\s+initOpAuthPanelFocus/);
  assert.match(js, /function\s+initOpPasswordToggles/);
  assert.match(js, /querySelectorAll\('\.op-auth-form input\[type="password"\]'\)/);
  assert.match(js, /show-password-input/);
  assert.match(js, /params\.get\('op_auth'\)\s*===\s*'register'/);
  assert.match(js, /#op-register/);

  assert.match(cmsSetup, /woocommerce_enable_myaccount_registration/);
  assert.match(demoSeeder, /configure_woocommerce_pages/);
});

test('theme provides logged-in account shell, filtered travel-first navigation, and profile dropdown state', async () => {
  const header = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/header.php'),
    'utf8',
  );
  const pageTemplate = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/page.php'),
    'utf8',
  );
  const wooHelpers = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/woocommerce.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );
  const accountShell = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/my-account.php',
  );
  const accountNav = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/navigation.php',
  );
  const accountDashboard = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/dashboard.php',
  );
  const accountOrders = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/orders.php',
  );
  const accountViewOrder = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/view-order.php',
  );
  const accountAddress = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/my-address.php',
  );
  const accountEditAddress = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/form-edit-address.php',
  );
  const accountEditProfile = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/form-edit-account.php',
  );

  assert.match(header, /op-header-profile/);
  assert.match(header, /op-header-profile__trigger/);
  assert.match(header, /op-header-profile__menu/);
  assert.match(header, /op-header-profile__meta/);
  assert.match(header, /op_travel_get_account_user_summary/);

  assert.match(pageTemplate, /is_account_page\(\)/);
  assert.match(pageTemplate, /op-page-content--account/);

  assert.match(wooHelpers, /woocommerce_account_menu_items/);
  assert.match(wooHelpers, /woocommerce_account_menu_item_classes/);
  assert.match(wooHelpers, /template_redirect/);
  assert.match(wooHelpers, /payment-methods/);
  assert.match(wooHelpers, /downloads/);
  assert.match(wooHelpers, /op_travel_get_account_user_summary/);
  assert.match(wooHelpers, /op_travel_get_recent_account_orders/);
  assert.match(wooHelpers, /op_travel_build_account_order_card/);

  assert.match(accountShell, /op-account-shell/);
  assert.match(accountShell, /op-account-layout/);
  assert.match(accountNav, /op-account-nav/);
  assert.match(accountNav, /Booking của tôi/);
  assert.match(accountDashboard, /op-account-dashboard/);
  assert.match(accountDashboard, /op-account-hero/);
  assert.match(accountDashboard, /op-account-summary/);
  assert.match(accountOrders, /op-account-orders/);
  assert.match(accountOrders, /op-account-order-card/);
  assert.match(accountViewOrder, /op-account-order-detail/);
  assert.match(accountViewOrder, /_op_travel_booking_data|op_travel_get_order_booking_snapshots/);
  assert.match(accountAddress, /op-account-addresses/);
  assert.match(accountEditAddress, /op-account-form/);
  assert.match(accountEditProfile, /op-account-form/);

  assert.match(css, /\.op-header-profile\s*{/);
  assert.match(css, /\.op-header-profile__menu\s*{/);
  assert.match(css, /\.op-header-profile__menu\[hidden\]\s*{/);
  assert.match(css, /\.op-account-shell\s*{/);
  assert.match(css, /\.op-account-nav\s*{/);
  assert.match(css, /\.op-account-order-card\s*{/);
  assert.match(css, /\.op-account-form\s*{/);
  assert.match(css, /\.woocommerce-notices-wrapper\s+\.woocommerce-message/);

  assert.match(js, /function\s+initOpAccountMenu/);
  assert.match(js, /op-header-profile/);
  assert.match(js, /keydown/);
  assert.match(js, /Escape/);
});

test('contact page uses a dedicated travel-first template around the seeded shortcode form', async () => {
  const contactTemplate = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/page-lien-he.php',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const cmsSetup = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CmsSetup.php'),
    'utf8',
  );

  assert.match(contactTemplate, /op-contact-page/);
  assert.match(contactTemplate, /op-contact-hero/);
  assert.match(contactTemplate, /op-contact-grid/);
  assert.match(contactTemplate, /op-contact-aside/);
  assert.match(contactTemplate, /op-travel-contact-form/);
  assert.match(contactTemplate, /home_url\('\/tours\/'\)/);

  assert.match(css, /\.op-contact-page\s*{/);
  assert.match(css, /\.op-contact-hero\s*{/);
  assert.match(css, /\.op-contact-grid\s*{/);
  assert.match(css, /\.op-contact-aside\s*{/);
  assert.match(css, /\.op-travel-contact-form\s*{/);
  assert.match(css, /\.op-travel-contact-form\s+\.op-field\s*{/);

  assert.match(cmsSetup, /\[op_travel_contact_form\]/);
});

test('plugin handles OTP registration email flow through WordPress mail', async () => {
  const pluginEntry = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/op-travel-core.php'),
    'utf8',
  );
  const bootstrap = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php'),
    'utf8',
  );
  const otp = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CustomerRegistrationOtp.php'),
    'utf8',
  );
  const env = await fs.readFile(
    repoPath('env/wordpress.env.example'),
    'utf8',
  );
  const authFlow = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CustomerAuthFlow.php'),
    'utf8',
  );

  assert.match(pluginEntry, /CustomerRegistrationOtp\.php/);
  assert.match(pluginEntry, /CustomerAuthFlow\.php/);
  assert.match(bootstrap, /CustomerRegistrationOtp::boot/);
  assert.match(bootstrap, /CustomerAuthFlow::boot/);
  assert.match(authFlow, /final class CustomerAuthFlow/);
  assert.match(authFlow, /add_filter\('woocommerce_login_redirect'/);
  assert.match(authFlow, /wp_validate_redirect/);
  assert.match(authFlow, /wc_get_page_permalink\('myaccount'\)/);
  assert.match(otp, /final class CustomerRegistrationOtp/);
  assert.match(otp, /add_action\('wp_loaded'/);
  assert.match(otp, /add_action\('phpmailer_init'/);
  assert.match(otp, /add_filter\('wp_mail_from'/);
  assert.match(otp, /add_filter\('wp_mail_from_name'/);
  assert.match(otp, /public static function mail_from/);
  assert.match(otp, /public static function mail_from_name/);
  assert.match(otp, /Env::get\('SMTP_HOST'/);
  assert.match(otp, /Env::get\('SMTP_PORT'/);
  assert.match(otp, /Env::get\('SMTP_USER'/);
  assert.match(otp, /Env::get\('SMTP_PASS'/);
  assert.match(otp, /private static function smtp_password/);
  assert.match(otp, /strtolower\(\$host\)\s*===\s*'smtp\.gmail\.com'/);
  assert.match(otp, /preg_replace\('\/\\s\+\/'/);
  assert.match(otp, /wp_mail/);
  assert.match(otp, /wp_verify_nonce/);
  assert.match(otp, /op_travel_registration_send_otp/);
  assert.match(otp, /op_travel_registration_verify_otp/);
  assert.match(otp, /op_travel_registration_resend_otp/);
  assert.match(otp, /handle_resend_otp/);
  assert.match(otp, /send_otp_email/);
  assert.match(otp, /op_travel_registration_otp_/);
  assert.match(otp, /op_travel_registration_otp_rate_/);
  assert.match(otp, /set_transient/);
  assert.match(otp, /get_transient/);
  assert.match(otp, /delete_transient/);
  assert.match(otp, /MAX_ATTEMPTS\s*=\s*5/);
  assert.match(otp, /OTP_TTL_SECONDS\s*=\s*600/);
  assert.match(otp, /RATE_LIMIT_SECONDS\s*=\s*60/);
  assert.match(otp, /wc_create_new_customer|wp_create_user/);
  assert.match(otp, /wp_set_auth_cookie/);
  assert.match(otp, /wp_set_current_user/);
  assert.match(otp, /wp_safe_redirect/);
  assert.match(env, /SMTP_HOST/);
  assert.match(env, /SMTP_PORT/);
  assert.match(env, /SMTP_USER/);
  assert.match(env, /SMTP_PASS/);
});

test('login flow replaces the guest cart with the account cart instead of merging both carts', async () => {
  const authFlow = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/CustomerAuthFlow.php'),
    'utf8',
  );

  assert.match(authFlow, /add_action\('wp_login'/);
  assert.match(authFlow, /woocommerce_load_cart_from_session/);
  assert.match(authFlow, /_woocommerce_load_saved_cart_after_login/);
  assert.match(authFlow, /_woocommerce_persistent_cart_/);
  assert.match(authFlow, /WC\(\)->session->set\('cart'/);
  assert.match(authFlow, /WC\(\)->session->set\('cart_totals', null\)/);
});

test('tour media skeleton renders immediately with YouTube-style contrast', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );
  const contentProduct = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/content-product.php'),
    'utf8',
  );
  const singleProduct = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/single-product.php'),
    'utf8',
  );
  const cart = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php'),
    'utf8',
  );

  assert.match(css, /--op-skeleton-base:\s*#e5e5e5/);
  assert.match(css, /--op-skeleton-mid:\s*#f2f2f2/);
  assert.match(css, /--op-skeleton-shine:\s*rgba\(255,\s*255,\s*255,\s*0\.72\)/);
  assert.match(css, /\.op-tour-card\.op-is-loading\s+\.op-tour-card__content::before/);
  assert.match(css, /linear-gradient\(#e5e5e5\s+20px,\s*transparent\s+0\)/);
  assert.match(css, /\.op-skeleton-media\.op-is-loading\s+img[\s\S]*opacity:\s*0/);
  assert.match(js, /const card = wrapper\.closest\('\.op-tour-card, \.op-cart-tour-card'\)/);
  assert.match(js, /card\.classList\.remove\(loadingClass\)/);

  assert.match(contentProduct, /op-tour-card[\s\S]*op-is-loading/);
  assert.match(contentProduct, /op-tour-card__media op-skeleton-media op-is-loading/);
  assert.match(singleProduct, /woocommerce-product-gallery op-skeleton-media op-is-loading/);
  assert.match(cart, /op-cart-tour-card[\s\S]*op-is-loading/);
  assert.match(cart, /op-cart-tour-card__media op-skeleton-media op-is-loading/);
});

test('tour media skeleton stays visible briefly even when images are cached', async () => {
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );

  assert.match(js, /const\s+minimumSkeletonDuration\s*=\s*650/);
  assert.match(js, /function\s+runAfterMinimumSkeletonDuration/);
  assert.match(js, /Date\.now\(\)/);
  assert.match(js, /window\.setTimeout/);
  assert.match(js, /img\.complete\s*&&\s*img\.naturalWidth\s*>\s*0[\s\S]*runAfterMinimumSkeletonDuration/);
  assert.match(js, /img\.addEventListener\('load',\s*\(\)\s*=>\s*runAfterMinimumSkeletonDuration/);
  assert.match(js, /img\.addEventListener\('error',\s*\(\)\s*=>\s*runAfterMinimumSkeletonDuration/);
});

test('loading states do not disable WooCommerce submit buttons before the add-to-cart payload is serialized', async () => {
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );

  assert.match(js, /function\s+shouldDisableControl/);
  assert.match(js, /button\[type="submit"\]/);
  assert.match(js, /input\[type="submit"\]/);
  assert.match(js, /single_add_to_cart_button/);
  assert.match(js, /if\s*\(shouldDisableControl\(control\)\)\s*{\s*control\.disabled = isLoading;/);
});

test('single tour booking panel aligns form controls vertically', async () => {
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
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
  assert.match(js, /function\s+initOpBookingForms/);
  assert.match(js, /querySelector\('select\[name=\"op_departure_date\"\]'\)/);
  assert.match(js, /option\.value\s*!==\s*''/);
  assert.match(js, /form\.addEventListener\('submit'/);
});

test('single tour booking submissions redirect to the cart after a successful hold', async () => {
  const woo = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/inc/woocommerce.php'),
    'utf8',
  );

  assert.match(woo, /woocommerce_add_to_cart_redirect/);
  assert.match(woo, /function op_travel_redirect_booking_add_to_cart/);
  assert.match(woo, /op_travel_booking_nonce/);
  assert.match(woo, /wc_get_cart_url\(\)/);
});

test('booking hooks preselect the nearest departure date so single-tour holds do not dead-end on reload', async () => {
  const bookingHooks = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php'),
    'utf8',
  );

  assert.match(bookingHooks, /\$selected_departure_date\s*=\s*isset\(\$_POST\['op_departure_date'\]\)/);
  assert.match(bookingHooks, /\$selected_departure_date\s*===\s*''\s*&&\s*! empty\(\$available_departure_dates\)/);
  assert.match(bookingHooks, /\$selected_departure_date\s*=\s*\$available_departure_dates\[0\]/);
  assert.match(bookingHooks, /selected\(\$selected_departure_date,\s*\$departure_date\)/);
});

test('booking holds do not hard-fail on stale nonce from long-lived product tabs', async () => {
  const bookingHooks = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/BookingHooks.php'),
    'utf8',
  );

  assert.match(bookingHooks, /wp_nonce_field\('op_travel_booking', 'op_travel_booking_nonce'\)/);
  assert.doesNotMatch(bookingHooks, /wp_verify_nonce/);
  assert.doesNotMatch(bookingHooks, /Phiên giữ chỗ không hợp lệ/);
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
  assert.match(cart, /type="radio"/);
  assert.match(cart, /name="op_travel_selected_cart_item"/);
  assert.match(cart, /op-cart-selection/);
  assert.match(cart, /name="op_travel_checkout_selected_booking"/);
  assert.match(cart, /type="submit"\s+name="op_travel_checkout_selected_booking"/);
  assert.doesNotMatch(cart, /Cart totals|Subtotal|Proceed to checkout/);
  assert.doesNotMatch(cart, /woocommerce_cart_totals|woocommerce_proceed_to_checkout/);
  assert.doesNotMatch(cart, /Ã|Ä|áº|á»|Â|â†/);

  assert.match(css, /\.woocommerce-cart-form\s+\.op-cart-tour-card\s*{/);
  assert.match(css, /\.op-cart-booking-grid\s*{/);
  assert.match(css, /\.op-cart-summary-row\s*{/);
  assert.match(css, /\.op-cart-selection\s*{/);
  assert.match(css, /\.op-cart-radio\s*{/);
});

test('cart summary updates immediately when the selected booking radio changes', async () => {
  const cart = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart.php'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );

  assert.match(cart, /data-op-cart-radio/);
  assert.match(cart, /data-op-cart-total-html/);
  assert.match(cart, /data-op-cart-tour-name/);
  assert.match(cart, /data-op-cart-selected-total/);
  assert.match(cart, /data-op-cart-selected-tour/);

  assert.match(js, /function\s+initOpCartSelection/);
  assert.match(js, /querySelectorAll\('\[data-op-cart-radio\]'\)/);
  assert.match(js, /data-op-cart-selected-total/);
  assert.match(js, /data-op-cart-selected-tour/);
  assert.match(js, /innerHTML\s*=/);
  assert.match(js, /addEventListener\('change'/);
});

test('cart page provides a guided empty state when checkout redirects back without any booking', async () => {
  const cartEmpty = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/cart/cart-empty.php'),
    'utf8',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(cartEmpty, /op-cart-empty/);
  assert.match(cartEmpty, /op-cart-empty__layout/);
  assert.match(cartEmpty, /Trang thanh toán chỉ mở khi bạn đã giữ chỗ ít nhất một tour/);
  assert.match(cartEmpty, /Quay lại shortlist tour/);
  assert.match(cartEmpty, /Đi tiếp như thế nào/);
  assert.match(css, /\.op-cart-empty\s*{/);
  assert.match(css, /\.op-cart-empty__layout\s*{/);
  assert.match(css, /\.op-cart-empty__steps\s*{/);
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
  assert.match(checkout, /op-checkout-selection-note/);
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
  assert.match(css, /\.op-checkout-selection-note\s*{/);
  assert.match(css, /\.op-checkout-review-table\s*{/);
  assert.match(css, /\.op-checkout-review-table\s+\.wc-item-meta\s+li\s*{[^}]*grid-template-columns:\s*140px\s+minmax\(0,\s*1fr\)/);
  assert.match(css, /\.op-checkout-review-table\s+\.variation\s*{[^}]*grid-template-columns:\s*140px\s+minmax\(0,\s*1fr\)/);
  assert.match(css, /\.op-checkout-review-table\s+\.product-total\s*{[^}]*text-align:\s*right/);
});

test('plugin keeps a single selected booking in checkout while preserving the remaining cart items for later', async () => {
  const pluginEntry = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/op-travel-core.php'),
    'utf8',
  );
  const bootstrap = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Bootstrap.php'),
    'utf8',
  );
  const selectionFlow = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-core/includes/CheckoutSelectionFlow.php',
  );

  assert.match(pluginEntry, /CheckoutSelectionFlow\.php/);
  assert.match(bootstrap, /CheckoutSelectionFlow::boot/);
  assert.match(selectionFlow, /op_travel_selected_checkout_cart_item_key/);
  assert.match(selectionFlow, /op_travel_checkout_backup_cart_items/);
  assert.match(selectionFlow, /is_cart\(\)/);
  assert.match(selectionFlow, /is_checkout\(\)/);
  assert.match(selectionFlow, /woocommerce_thankyou/);
  assert.match(selectionFlow, /wp_safe_redirect\(wc_get_checkout_url\(\)\)/);
  assert.match(selectionFlow, /update_user_meta/);
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

test('payment QR block is localized and exposes a payment status check flow on order pages', async () => {
  const paymentConfirm = await fs.readFile(
    repoPath('wordpress/wp-content/plugins/op-travel-core/includes/Rest/PaymentConfirmController.php'),
    'utf8',
  );
  const qrHooks = await readSourceIfPresent(
    'wordpress/wp-content/plugins/op-travel-sepay/includes/SePayPaymentQrHooks.php',
  );
  const thankyou = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php'),
    'utf8',
  );
  const viewOrder = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/woocommerce/myaccount/view-order.php'),
    'utf8',
  );
  const js = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/js/theme.js'),
    'utf8',
  );

  assert.match(paymentConfirm, /payment-status/);
  assert.match(paymentConfirm, /'methods'\s*=>\s*'GET'/);
  assert.match(paymentConfirm, /order_key/);
  assert.match(paymentConfirm, /payment_status/);
  assert.match(paymentConfirm, /state_label/);
  assert.match(paymentConfirm, /state_message/);
  assert.match(paymentConfirm, /BOOKING_SERVICE_ENDPOINT/);
  assert.match(paymentConfirm, /wp_remote_get/);
  assert.match(paymentConfirm, /feedback_message/);

  assert.match(qrHooks, /data-op-payment-status-check/);
  assert.match(qrHooks, /data-op-payment-status-feedback/);
  assert.match(qrHooks, /data-op-payment-state-detail/);
  assert.match(qrHooks, /data-op-order-key/);
  assert.match(qrHooks, /provider_label/);
  assert.doesNotMatch(qrHooks, /ZaloPay payment data is available|Open ZaloPay payment link|Fallback QR is shown|Provider/);

  assert.match(thankyou, /data-op-payment-state-pill/);
  assert.match(thankyou, /data-op-payment-state-text/);
  assert.match(thankyou, /data-op-payment-state-message/);
  assert.match(viewOrder, /data-op-payment-state-text/);
  assert.match(viewOrder, /op-payment-assets/);

  assert.match(js, /function\s+initOpPaymentStatusCheck/);
  assert.match(js, /data-op-payment-status-check/);
  assert.match(js, /data-op-payment-state-pill/);
  assert.match(js, /data-op-payment-state-text/);
  assert.match(js, /data-op-payment-state-message/);
  assert.match(js, /data-op-payment-state-detail/);
  assert.match(js, /feedback_message/);
  assert.match(js, /fetch\(/);
});

test('pay-for-order flow uses a dedicated travel-first payment shell instead of the default WooCommerce table', async () => {
  const pageTemplate = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/page.php'),
    'utf8',
  );
  const formPay = await readSourceIfPresent(
    'wordpress/wp-content/themes/op-travel-shop/woocommerce/checkout/form-pay.php',
  );
  const css = await fs.readFile(
    repoPath('wordpress/wp-content/themes/op-travel-shop/assets/css/theme.css'),
    'utf8',
  );

  assert.match(pageTemplate, /is_checkout\(\)/);
  assert.match(formPay, /op-pay-order-layout/);
  assert.match(formPay, /op-pay-order-summary/);
  assert.match(formPay, /op-pay-order-payment/);
  assert.match(formPay, /data-op-loading-form/);
  assert.match(formPay, /woocommerce_pay_order_before_payment/);
  assert.match(formPay, /Phương thức thanh toán/);
  assert.match(formPay, /Số tiền cần xử lý/);
  assert.match(formPay, /Chi tiết booking đang chờ thanh toán/);
  assert.doesNotMatch(formPay, /shop_table|esc_html_e\(\s*'Product'|esc_html_e\(\s*'Qty'|esc_html_e\(\s*'Totals'/);

  assert.match(css, /\.op-pay-order-layout\s*{/);
  assert.match(css, /\.op-pay-order-summary,\s*[\r\n]+\s*\.op-pay-order-payment\s*{/);
  assert.match(css, /\.op-pay-order-card\s*{/);
  assert.match(css, /\.op-pay-order-payment\s*{/);
  assert.match(css, /\.op-pay-order-total\s*{/);
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
