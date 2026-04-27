# Phase 4 - Tùy biến skin theme OP Travel Shop

## Mục tiêu phase
Phân tích và trình bày phần tùy biến giao diện của dự án HV-Travel, tập trung vào theme `op-travel-shop` và cách theme này biến WooCommerce thành một trải nghiệm đặt tour chuyên biệt.

## Đầu vào
- Theme `wp-content/themes/op-travel-shop/`
- Các file override WooCommerce
- Tệp style và JavaScript của theme

## Đầu ra
- Tài liệu mô tả rõ cấu trúc theme
- Câu chuyện “tùy biến skin” đủ mạnh để trình bày ở buổi bảo vệ
- Danh sách các màn hình quan trọng cần demo

## Ý nghĩa với BCCĐ
Đây là phần thể hiện rõ kỹ năng frontend trong WordPress: không dùng giao diện mặc định của WooCommerce, mà tổ chức lại toàn bộ journey thành một website bán tour có nhịp điệu, ngôn ngữ thị giác và quy trình mua riêng.

## Nguyên tắc theme bắt buộc
- Theme chỉ sở hữu storefront, WooCommerce template override, CTA flow và responsive behavior.
- Business logic như validate booking, persistence dữ liệu, payment confirm hay đồng bộ service không được nằm trong theme.
- Journey 4 bước phải được giữ nhất quán ở mọi màn hình: chọn tour, xác nhận giữ chỗ, thanh toán, hoàn tất.
- Archive phải gắn chặt với taxonomy `destination` và `tour_style`, không biến thành product grid chung chung.
- Nếu cần dữ liệu mới trên giao diện, phải kiểm tra plugin `op-travel-core` đã cấp metadata/taxonomy tương ứng trước khi sửa theme.

## Phân tích theme hiện tại
Theme `op-travel-shop` không chỉ đổi màu sắc mà đã định hình một workflow mua tour xuyên suốt:

- Trang chủ dẫn khách vào bước chọn tour
- Archive tour đóng vai trò shortlist
- Single tour là nơi chốt ngày khởi hành và số lượng khách
- Cart là bước xác nhận giữ chỗ
- Checkout là bước hoàn tất booking
- Thank-you page là bước hiển thị xác nhận và QR

Điểm mạnh lớn nhất của theme là không xem WooCommerce như một shop hàng hóa thông thường, mà tổ chức lại ngôn ngữ giao diện để phù hợp với sản phẩm “tour du lịch”.

## Ma trận màn hình theo journey 4 bước
| Bước | Màn hình chính | Vai trò UX | Dữ liệu phụ thuộc |
| --- | --- | --- | --- |
| Bước 1 | `front-page.php`, `archive-product.php`, `content-product.php` | Gợi cảm hứng, shortlist, chọn tour | taxonomy `destination`, `tour_style`, metadata tour |
| Bước 2 | `single-product.php`, `cart/cart.php` | Chốt ngày đi, số khách và xác nhận giữ chỗ | booking fields, tour metadata, cart item meta |
| Bước 3 | `checkout/form-checkout.php`, `checkout/payment.php` | Thu thông tin khách và chọn payment method | order review, gateway messaging, checkout state |
| Bước 4 | `checkout/thankyou.php`, `order/order-details.php` | Hiển thị trạng thái cuối, QR/panel thanh toán, CTA tiếp theo | order data, payment state, QR/demo panel |

## Cấu trúc file theme
### File nền
- `style.css`: metadata theme
- `functions.php`: điểm nạp các module trong `inc/`

### Thư mục `inc/`
- `setup.php`: theme support, menu, enqueue asset, body class
- `template-tags.php`: helper lấy ảnh, metadata, term, query
- `workflow.php`: blueprint cho 4 bước của hành trình đặt tour
- `woocommerce.php`: filter WooCommerce, text button, sort gateway, product archive filter

### Thư mục `assets/`
- `assets/css/theme.css`: file skin chính
- `assets/js/theme.js`: hành vi frontend
- `assets/images/*`: ảnh fallback và minh họa

### Override WooCommerce
- `woocommerce/archive-product.php`
- `woocommerce/content-product.php`
- `woocommerce/single-product.php`
- `woocommerce/cart/cart.php`
- `woocommerce/cart/cart-totals.php`
- `woocommerce/cart/proceed-to-checkout-button.php`
- `woocommerce/checkout/form-checkout.php`
- `woocommerce/checkout/payment.php`
- `woocommerce/checkout/payment-method.php`
- `woocommerce/checkout/order-received.php`
- `woocommerce/checkout/thankyou.php`
- `woocommerce/myaccount/*`
- `woocommerce/order/order-details.php`

## Trang chủ
Trang chủ trong `front-page.php` đã được xây dựng như một landing page định hướng hành vi:

- Hero lớn với CTA rõ ràng
- Hiển thị số lượng tour, điểm đến, workflow 4 bước
- Block trust signal
- Danh sách tour nổi bật
- Danh mục điểm đến
- Khuyến mãi, testimonial, journal

Thông điệp trung tâm của trang chủ là: khách không bị đẩy vào checkout đột ngột, mà được dẫn qua một “luxury buying journey”.

## Danh sách tour
`woocommerce/archive-product.php` đã custom phần archive theo hướng:

- Có hero giải thích đây là bước 1: chọn tour
- Có bộ lọc theo `destination`
- Có bộ lọc theo `tour_style`
- Có card tour định hướng shortlist chứ không chỉ hiển thị tên và giá

Đây là minh chứng mạnh cho việc theme đang gắn chặt với taxonomy do plugin tạo ra.

## Chi tiết tour
`woocommerce/single-product.php` là một trong những file quan trọng nhất:

- Hiển thị gallery riêng
- Hiển thị metadata tour như thời lượng, nơi khởi hành, ngày gần nhất
- Hiển thị khối trust cho booking
- Gọi `woocommerce_template_single_add_to_cart()` để hiện booking fields
- Hiển thị preview của bước thanh toán và QR demo

Single product hiện đã đóng vai trò trang “đặt tour”, không còn là product detail page chung chung.

## Giỏ hàng
`woocommerce/cart/cart.php` biến cart thành bước 2:

- Hiển thị tiêu đề “Xác nhận giữ chỗ”
- Hiển thị metadata booking đi theo từng line item
- Cho khách xác nhận thông tin trước khi sang checkout
- Nhấn mạnh cart là bước xác nhận chứ không phải chỉ là danh sách sản phẩm

## Thanh toán
`woocommerce/checkout/form-checkout.php` biến checkout thành bước 3:

- Sắp lại layout theo dạng workflow shell
- Nhấn mạnh thông tin khách hàng và hóa đơn
- Có block assurance để giải thích dữ liệu booking vẫn được giữ
- Tóm tắt đơn hàng trước khi xác nhận

## Trang cảm ơn
`woocommerce/checkout/thankyou.php` là điểm nổi bật khi bảo vệ:

- Có trạng thái thành công và thất bại riêng
- Hiển thị mã đơn, ngày đặt, tổng tiền, phương thức thanh toán, trạng thái
- Tạo không gian để QR demo xuất hiện theo order
- Có CTA xem đơn, quay lại tours, gửi yêu cầu tư vấn

Đây là nơi câu chuyện “thanh toán thành công” được nhìn thấy rõ nhất.

## Responsive mobile
Từ `theme.css` có thể thấy theme đang dùng:

- CSS variable cho palette
- Layout grid và width giới hạn
- Button, section shell, hero shell có cấu trúc gọn
- Các block workflow được thiết kế để co xuống màn hình nhỏ

Khi bảo vệ, cần nhấn mạnh:
- Người dùng di động có thể vẫn đi trọn luồng từ chọn tour đến thanh toán
- Giao diện không phụ thuộc desktop

## Màu sắc và nhận diện
Theme đang dùng bộ màu và typography có chủ ý:

- `--op-sand`
- `--op-cream`
- `--op-ink`
- `--op-slate`
- `--op-gold`
- `--op-sea`
- Font `Cormorant Garamond` cho heading
- Font `Manrope` cho body

Điều này giúp website khác biệt với WordPress/WooCommerce mặc định và mang đúng tinh thần travel premium.

## Các override WooCommerce
Trong `inc/woocommerce.php`, theme hiện đang:

- Tắt toàn bộ default WooCommerce styles
- Tùy chỉnh số sản phẩm trên trang archive
- Sắp xếp ưu tiên payment gateway: `mpay_up_vnpay` trước `bacs`
- Đổi text nút add to cart, checkout, return to shop
- Lọc archive theo taxonomy `destination` và `tour_style`

Đây là điểm rất đáng nói vì nó cho thấy theme không chỉ đổi giao diện mà còn tham gia định hình hành vi sử dụng.

## Ranh giới theme và plugin
- Theme render metadata tour và booking panel, nhưng plugin mới là nơi tạo và giữ dữ liệu đó.
- Theme có thể đổi text CTA hoặc thứ tự hiển thị gateway, nhưng không tự xác nhận trạng thái thanh toán.
- Thank-you page có thể kể câu chuyện `pending/paid/failed/expired/cancelled`, nhưng nguồn trạng thái phải đi từ order và payment flow đã được cập nhật đúng ở plugin/service.
- Nếu hội đồng hỏi “logic nằm ở đâu”, câu trả lời chuẩn là: theme kể chuyện giao diện, plugin xử lý nghiệp vụ, service xử lý webhook và MongoDB.

## Minh chứng trong source code
- `wp-content/themes/op-travel-shop/style.css`
- `wp-content/themes/op-travel-shop/assets/css/theme.css`
- `wp-content/themes/op-travel-shop/front-page.php`
- `wp-content/themes/op-travel-shop/inc/setup.php`
- `wp-content/themes/op-travel-shop/inc/workflow.php`
- `wp-content/themes/op-travel-shop/inc/woocommerce.php`
- `wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`
- `wp-content/themes/op-travel-shop/woocommerce/single-product.php`

## Checklist tùy biến skin
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Theme metadata | Khai báo theme riêng | Đã có |
| CSS variables | Bộ màu và nhận diện riêng | Đã có |
| Typography | Font riêng cho heading và body | Đã có |
| Front page | Landing page du lịch có workflow | Đã có |
| Archive product | Bộ lọc và shortlist tour | Đã có |
| Single product | Trang đặt tour chuyên biệt | Đã có |
| Cart | Bước xác nhận giữ chỗ | Đã có |
| Checkout | Workflow shell cho thanh toán | Đã có |
| Thank-you page | Trang hoàn tất có chỗ cho QR | Đã có |
| Taxonomy filters | Bộ lọc `destination` và `tour_style` đi đúng narrative du lịch | Đã có |
| CTA messaging | Text nút và trạng thái đúng từng bước | Cần rà lại theo payment thật |
| Mobile/responsive | Cần kiểm thử kỹ trên thiết bị thật | Cần xác nhận thêm |

## Những gì đã có
- Theme riêng có định vị thẩm mỹ rõ
- Journey 4 bước đã được phản ánh từ `workflow.php`
- Override WooCommerce ở nhiều màn hình quan trọng
- Thank-you page và checkout page đã được custom sâu

## Những gì cần bổ sung để hoàn thiện đồ án
- Chụp screenshot từng màn hình để đưa vào báo cáo
- Tối ưu thêm responsive cho mobile nếu cần
- Đồng bộ thông điệp thanh toán thật với `payOS` và trạng thái order thực
- Rà lại thank-you page để narrative `pending/paid/failed/expired/cancelled` khớp payment flow ở phase 6
- Chỉ bổ sung giao diện back-office nếu thực sự có admin report; không đưa business logic admin vào theme chỉ để “đủ màn hình”

## Cách trình bày khi bảo vệ
- Mở bằng việc nói đây là theme custom hoàn toàn cho website bán tour.
- Trình bày 4 bước workflow trong `workflow.php`.
- Mở trang chủ và chỉ ra cách theme dẫn khách vào bước 1.
- Mở archive tour và giải thích bộ lọc theo điểm đến, loại tour.
- Mở single tour và nhấn vào panel booking.
- Mở cart để chỉ ra metadata booking không bị mất.
- Mở checkout để cho thấy giao diện được tổ chức lại so với WooCommerce mặc định.
- Mở thank-you page và nói đây là nơi tích hợp thông báo thành công và QR.

## Kết luận phase
Phase 4 cho thấy HV-Travel có một “skin” thực sự, chứ không chỉ đổi chút CSS bề mặt. Theme đã được thiết kế để phục vụ trọn hành trình mua tour, tạo nền rất tốt cho phase tiếp theo là plugin nghiệp vụ, nơi toàn bộ logic booking và metadata chuyên ngành được triển khai.
