# Phase 2 - Cấu hình môi trường và hệ thống

## Mục tiêu phase
Trình bày toàn bộ phần “sinh viên cấu hình”: từ môi trường phát triển, cài WordPress, WooCommerce, theme, plugin đến cấu hình trang, email, thanh toán và chuẩn bị Docker local.

## Đầu vào
- Source code WordPress hiện tại
- `wp-config.php`
- Theme `op-travel-shop`
- Plugin `op-travel-core`
- Plugin `BCK`

## Đầu ra
- Môi trường cài đặt nhất quán
- Danh sách bước cấu hình rõ ràng, có thể demo theo từng màn hình
- Checklist để tránh sót cấu hình khi demo hoặc deploy

## Ý nghĩa với BCCĐ
Đây là phần chứng minh sinh viên không chỉ biết code mà còn biết dựng hệ thống hoàn chỉnh. Ở buổi bảo vệ, phần cấu hình giúp hội đồng thấy được tư duy vận hành: cấu hình database, permalink, page mapping, plugin, SMTP, payment và chuẩn bị môi trường container.

## Yêu cầu máy phát triển
- Hệ điều hành: Windows, Linux hoặc macOS
- PHP: `8.1+`
- MySQL: `8.x` hoặc MariaDB tương thích
- Web server: Apache hoặc Nginx
- Node.js: chỉ cần nếu mở rộng service riêng hoặc build asset sau này
- Docker Desktop: dùng cho local container
- Git: dùng cho quản lý phiên bản

## Nguyên tắc cấu hình bắt buộc
- Chỉ kết luận theme hoặc plugin lỗi sau khi `WordPress` và `WooCommerce` đã chạy ổn định.
- `op-travel-shop` là lớp giao diện; `op-travel-core` là lớp business logic, không được đảo vai trò khi giải thích.
- Rewrite tour phải đi theo slug `/tours/`; nếu route sai, phải xử lý ở permalink hoặc `CmsSetup` trước.
- `payOS` là hướng thanh toán chính; `BCK` và QR demo chỉ là fallback/minh họa ở giai đoạn đầu.
- Target Docker local chuẩn vẫn phải là 4 service: `wordpress`, `mysql`, `mongodb`, `booking-payment-service`.

## Thứ tự dựng môi trường chuẩn
1. Chuẩn bị nền tảng local: PHP, MySQL/MariaDB, web server và Docker Desktop nếu cần local stack.
2. Cấu hình `wp-config.php` để WordPress kết nối MySQL thành công.
3. Hoàn tất cài đặt WordPress cơ bản và đăng nhập được vào trang quản trị.
4. Cài và kích hoạt `WooCommerce`, xác nhận product/cart/checkout/order hoạt động.
5. Kích hoạt theme `op-travel-shop`, sau đó kiểm tra asset và WooCommerce template override.
6. Kích hoạt `OP Travel Core`, `BCK`, rồi rà lại page seed, taxonomy và slug `/tours/`.
7. Cấu hình permalink, homepage/blog page, shop/cart/checkout/account mapping.
8. Cấu hình SMTP, payment env target và chuẩn bị local Docker topology nếu cần.
9. Chạy smoke test tối thiểu trước khi chuyển sang phase giao diện, plugin hoặc payment thật.

## Cài đặt WordPress
1. Clone source về máy.
2. Chuẩn bị web server và MySQL.
3. Tạo database WordPress, ví dụ `wordpress`.
4. Cập nhật `wp-config.php` với:
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_HOST`
5. Mở website local để hoàn tất bước cài đặt cơ bản nếu chưa có dữ liệu.

### Điểm đáng chú ý trong repo
- `wp-config.php` hiện khai báo:
  - `DB_NAME=wordpress`
  - `DB_USER=root`
  - `DB_PASSWORD=''`
  - `DB_HOST=127.0.0.1`
- `WP_DEBUG` đang được lấy từ biến môi trường.
- `WP_DEBUG_LOG=true` nên log sẽ đi vào `wp-content/debug.log`.

## Ma trận cấu hình theo lớp
| Lớp cấu hình | Việc phải chốt | Dấu hiệu pass | Không nên làm ở phase 2 |
| --- | --- | --- | --- |
| `WordPress core` | DB connection, admin login, `WP_DEBUG`, `WP_DEBUG_LOG` | Vào được admin và không lỗi kết nối DB | Đưa logic booking/payment vào core |
| `WooCommerce` | Product, cart, checkout, account pages | Có đủ trang và luồng mua cơ bản chạy được | Xem WooCommerce như shop mặc định không có custom tour flow |
| `Theme op-travel-shop` | Kích hoạt theme, asset, override WooCommerce | Archive/single/cart/checkout hiển thị đúng skin | Nhét business logic vào `functions.php` |
| `Plugin OP Travel Core` | `CmsSetup`, taxonomy, page seed, slug `/tours/` | Page mapping và metadata tour hoạt động | Xử lý payment webhook trực tiếp bằng template |
| `Payment + service target` | Env `payOS`, `PAYMENT_SYNC_SECRET`, callback endpoint | Có thể mô tả rõ đường đi từ checkout tới payment confirm | Coi browser return là xác nhận thanh toán cuối cùng |
| `Docker local` | Topology 4 service, volume, env tách lớp | Có kế hoạch dựng local giống production thu gọn | Gom tất cả vào một container all-in-one |

## Cài đặt WooCommerce
- Cài và kích hoạt `WooCommerce`.
- Chạy wizard cơ bản hoặc để plugin `OP Travel Core` seed page hỗ trợ một phần.
- Kiểm tra các page:
  - `Tours`
  - `Giỏ hàng`
  - `Thanh toán`
  - `Tài khoản`

## Kích hoạt theme/plugin
### Theme
- Kích hoạt theme `op-travel-shop`.
- Theme này đã nạp các file:
  - `inc/setup.php`
  - `inc/template-tags.php`
  - `inc/workflow.php`
  - `inc/woocommerce.php`

### Plugin
- Kích hoạt `OP Travel Core`
- Kích hoạt `WooCommerce`
- Kích hoạt `BCK`
- Cài thêm:
  - `WP Mail SMTP`
  - `UpdraftPlus`
  - `Wordfence`

## Cấu hình permalink
- Vào `Settings > Permalinks`
- Chọn `Post name`
- Kiểm tra rewrite cho product đã được plugin đổi slug thành `/tours/`
- Kiểm tra các route:
  - `/tours/`
  - `/gio-hang/`
  - `/thanh-toan/`
  - `/tai-khoan/`
  - `/lien-he/`

## Cấu hình trang chủ / shop / cart / checkout / account
Plugin `CmsSetup` đang có logic seed page và map page tự động:

- Trang chủ: `trang-chu`
- Blog: `blog`
- Liên hệ: `lien-he`
- Shop: `tours`
- Cart: `gio-hang`
- Checkout: `thanh-toan`
- Account: `tai-khoan`

Trong thực tế demo, cần vào đúng từng khu vực để kiểm tra lại mapping:

- `Settings > Reading`: kiểm tra `trang-chu` và `blog`
- `WooCommerce > Settings > Advanced`: kiểm tra `gio-hang`, `thanh-toan`, `tai-khoan`
- `WooCommerce > Settings > Products`: kiểm tra page shop nếu cần
- Nếu page seed xong nhưng route chưa đúng, save lại permalink trước khi kết luận page mapping lỗi

## Biến môi trường tối thiểu
### WordPress
- `WORDPRESS_DB_HOST`
- `WORDPRESS_DB_NAME`
- `WORDPRESS_DB_USER`
- `WORDPRESS_DB_PASSWORD`
- `WP_DEBUG`

### Booking/payment service
- `MONGO_URI`
- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `PAYMENT_SYNC_SECRET`
- `WORDPRESS_CONFIRM_ENDPOINT`

### Email
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`

Lưu ý vận hành:

- `PAYMENT_SYNC_SECRET` và `WORDPRESS_CONFIRM_ENDPOINT` thuộc service nghiệp vụ, không phải cấu hình hiển thị của theme.
- Local và production phải tách secret; không hard-code key thật vào source.
- Nếu chưa tích hợp `payOS` hoàn chỉnh ở phase 2, vẫn phải khóa đúng tên env để phase 6 không phải đổi contract.

## Cấu hình Docker local
Theo bộ tài liệu hiện tại, Docker assets vẫn là hạng mục cần bổ sung. Tuy vậy, topology local cần được chốt ngay từ phase 2 để tránh mô tả sai kiến trúc:

- 1 container `wordpress`
- 1 container `mysql`
- 1 container `mongodb`
- 1 container `booking-payment-service`

### Mục tiêu của Docker local
- Giúp dựng môi trường demo chỉ bằng một lệnh
- Không phụ thuộc máy cá nhân cài tay từng thành phần
- Dễ chuyển lên Render

### Vai trò từng service trong local stack
| Service | Nhiệm vụ | Dữ liệu cần giữ | Ghi chú |
| --- | --- | --- | --- |
| `wordpress` | Render storefront, admin, WooCommerce, theme/plugin | `wp-content/uploads` nếu lưu local | Không nên chứa secret payment ngoài phần cần gọi service |
| `mysql` | Lưu data WordPress/WooCommerce | DB volume | Là DB lõi của website |
| `mongodb` | Lưu booking/payment/report document | DB volume | Không thay thế MySQL |
| `booking-payment-service` | Nhận webhook, đồng bộ payment, gọi callback về WordPress | log/service state nếu có | Dùng `PAYMENT_SYNC_SECRET` và `WORDPRESS_CONFIRM_ENDPOINT` |

## Cấu hình email SMTP
- Cài `WP Mail SMTP`
- Cấu hình qua SMTP server thật hoặc sandbox
- Kiểm tra email:
  - xác nhận liên hệ
  - xác nhận đơn hàng
  - thông báo quản trị viên

## Cấu hình thanh toán
### Hiện trạng
- Theme đang ưu tiên gateway `mpay_up_vnpay` rồi mới đến `bacs`
- Plugin `DemoPaymentQrHooks` hiển thị QR nếu payment method khớp gateway demo
- Plugin `BCK` đã có trong repo

### Định hướng đồ án
- `payOS` là hướng thanh toán online chính
- `BCK` là phương án dự phòng / so sánh
- `Bank transfer` vẫn được giữ để minh họa fallback
- Browser `returnUrl` chỉ phục vụ trải nghiệm quay lại trang đơn; webhook/callback mới là nguồn xác nhận thanh toán đáng tin cậy
- Callback nội bộ phải quay về `POST /wp-json/op-travel/v1/payment-confirm` với `PAYMENT_SYNC_SECRET`
- Phase 2 chỉ chốt cấu hình và target tích hợp; lifecycle payment hoàn chỉnh sẽ được triển khai ở phase 6

## Smoke test sau cấu hình
1. Mở trang chủ và kiểm tra theme `op-travel-shop` đã lên đúng skin.
2. Mở `/tours/` và xác nhận archive sản phẩm hiển thị bình thường.
3. Mở một single tour và kiểm tra booking fields xuất hiện.
4. Thêm tour vào giỏ, kiểm tra metadata đi vào cart.
5. Mở `/gio-hang/` và `/thanh-toan/`, xác nhận page mapping không lệch.
6. Kiểm tra ít nhất một phương thức thanh toán fallback hoạt động ở mức hiển thị.
7. Gửi thử email từ `WP Mail SMTP` nếu đã cấu hình SMTP.
8. Nếu mô phỏng local stack, xác nhận đủ 4 service thay vì chỉ `wordpress + mysql`.

## Các lỗi thường gặp và cách xử lý
### Lỗi không thấy trang `/tours/`
- Vào `Settings > Permalinks` và save lại
- Tắt bật lại plugin `OP Travel Core`

### Lỗi không hiện booking fields
- Kiểm tra `WooCommerce` đã active
- Kiểm tra product đang dùng template WooCommerce mặc định của theme

### Lỗi QR không hiển thị
- Kiểm tra order dùng đúng gateway hỗ trợ
- Kiểm tra `DemoPaymentQrHooks.php`
- Kiểm tra BCK hoặc gateway demo đã được cấu hình

### Lỗi callback payment không quay về WordPress
- Kiểm tra `WORDPRESS_CONFIRM_ENDPOINT`
- Kiểm tra `PAYMENT_SYNC_SECRET`
- Kiểm tra service payment có gọi đúng `POST /wp-json/op-travel/v1/payment-confirm`

### Lỗi email không gửi
- Kiểm tra cấu hình `WP Mail SMTP`
- Kiểm tra cổng mạng và tài khoản SMTP

### Lỗi page mapping sai
- Kiểm tra `woocommerce_shop_page_id`, `woocommerce_cart_page_id`, `woocommerce_checkout_page_id`, `woocommerce_myaccount_page_id`

### Lỗi local Docker thiếu thành phần
- Kiểm tra lại đủ 4 service: `wordpress`, `mysql`, `mongodb`, `booking-payment-service`
- Không rút gọn thành mô hình chỉ có `wordpress + mysql` nếu vẫn muốn giữ đúng câu chuyện kiến trúc đã chốt ở phase 1

## Minh chứng trong source code
- `wp-config.php`
- `wp-content/plugins/op-travel-core/includes/CmsSetup.php`
- `wp-content/themes/op-travel-shop/functions.php`
- `wp-content/themes/op-travel-shop/inc/setup.php`
- `wp-content/themes/op-travel-shop/inc/woocommerce.php`

## Checklist cấu hình
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| WordPress | Cấu hình DB, debug, đường dẫn cài đặt | Đã có một phần trong repo |
| WooCommerce | Cài và bật để có product/cart/checkout/order | Đã có trong repo |
| OP Travel Core | Kích hoạt plugin nghiệp vụ | Đã có trong repo |
| OP Travel Shop | Kích hoạt theme tùy biến | Đã có trong repo |
| Permalink | Kiểm tra rewrite `/tours/` | Cần xác nhận trên môi trường chạy |
| Pages | Kiểm tra shop/cart/checkout/account | Được seed tự động, cần rà lại |
| SMTP | Cấu hình gửi mail | Cần bổ sung |
| UpdraftPlus | Cấu hình backup cơ bản | Cần bổ sung |
| Wordfence | Cấu hình bảo mật cơ bản | Cần bổ sung |
| payOS | Cấu hình thanh toán thật | Cần bổ sung |
| BCK | Cấu hình phương án dự phòng | Đã có plugin, cần cấu hình |
| Payment callback | Chốt `PAYMENT_SYNC_SECRET` và `WORDPRESS_CONFIRM_ENDPOINT` | Cần bổ sung |
| Docker local | Soạn topology 4 service và `docker-compose` | Cần bổ sung |

## Những gì đã có
- `wp-config.php` rõ ràng, dễ map sang env
- Plugin đã seed page và thiết lập WooCommerce mặc định
- Theme và plugin đã tách riêng, thuận lợi cho cấu hình
- WooCommerce và BCK đã xuất hiện trong repo

## Những gì cần bổ sung để hoàn thiện đồ án
- Tạo bộ file Docker local theo topology `wordpress + mysql + mongodb + booking-payment-service`
- Tích hợp `WP Mail SMTP`
- Hoàn thiện cấu hình backup với `UpdraftPlus` và security baseline với `Wordfence`
- Tạo tài liệu cấu hình `payOS` và callback về WordPress
- Tách biến môi trường cho production rõ ràng
- Soạn script kiểm tra sau cấu hình

## Cách trình bày khi bảo vệ
- Mở màn bằng sơ đồ local environment: WordPress, MySQL, plugin, theme.
- Mở `wp-config.php` và giải thích phần DB cùng debug.
- Cho thấy plugin `OP Travel Core` tự seed page giúp rút ngắn thao tác cấu hình.
- Vào WooCommerce để kiểm tra các trang shop, cart, checkout, account.
- Vào phần theme để giải thích đây là custom skin riêng.
- Nói về `BCK` là gateway có sẵn trong repo.
- Nói tiếp về `payOS` sẽ là lớp thanh toán chính trong phiên bản hoàn thiện.
- Chốt rằng Docker local sẽ giúp dựng nhanh toàn bộ stack.

## Kết luận phase
Phase 2 chứng minh hệ thống HV-Travel có thể được dựng và cấu hình một cách có phương pháp, không phụ thuộc vào thao tác thủ công rời rạc. Sau khi chốt được phần môi trường, phase tiếp theo sẽ tập trung vào quản lý phiên bản để giải thích quá trình phát triển dự án theo hướng bài bản hơn.
