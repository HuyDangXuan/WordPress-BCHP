# Phase 5 - Plugin nghiệp vụ OP Travel Core

## Mục tiêu phase
Mô tả vai trò của plugin `OP Travel Core` trong việc biến WooCommerce thành một hệ thống bán tour du lịch, bao gồm taxonomy, post type, metadata tour, booking fields, QR demo và các hook nghiệp vụ.

## Đầu vào
- Plugin `wp-content/plugins/op-travel-core/`
- Các file PHP trong `includes/`
- Domain model và test bootstrap hiện có

## Đầu ra
- Một bức tranh rõ ràng về vai trò của plugin riêng
- Danh sách các thành phần nghiệp vụ đã có và cần mở rộng
- Nội dung phù hợp cho phần “lập trình tùy biến chức năng (plugin)”

## Ý nghĩa với BCCĐ
Đây là phần quan trọng nhất để chứng minh năng lực lập trình. Nếu theme trả lời câu hỏi “website trông như thế nào”, thì plugin trả lời câu hỏi “website hoạt động như thế nào”. Chính plugin là nơi nghiệp vụ du lịch được hiện thực hóa.

## Nguyên tắc plugin bắt buộc
- Business logic phải sống trong `op-travel-core`, không đẩy sang theme.
- Dữ liệu booking phải đi xuyên suốt `single -> cart -> checkout -> order`, không được chỉ sống ở session tạm.
- Metadata tour phải phục vụ cả admin và frontend.
- Endpoint hoặc integration mới phải bám đúng contract chung như `POST /api/bookings` và `POST /wp-json/op-travel/v1/payment-confirm`.
- QR demo là một concern riêng; không trộn lẫn nó với xác thực payment thật nếu chưa có boundary rõ.

## Mục tiêu plugin
- Mở rộng WooCommerce để sản phẩm mang ý nghĩa “tour”
- Tạo taxonomy và post type phù hợp với ngành du lịch
- Thêm metadata chuyên biệt cho tour
- Thu thập dữ liệu booking trước khi vào giỏ hàng
- Lưu dữ liệu booking đi xuyên suốt cart, checkout, order
- Hiển thị QR demo theo order
- Làm nền để mở rộng sang payOS, MongoDB và báo cáo sau này

## Cấu trúc plugin
### File entrypoint
- `op-travel-core.php`: nạp toàn bộ class và gọi `Bootstrap::boot()`

### Nhóm bootstrap và cấu hình
- `includes/Bootstrap.php`
- `includes/CmsSetup.php`

### Nhóm dữ liệu tour
- `includes/ProductMeta.php`
- `includes/Domain/TourProductData.php`

### Nhóm booking
- `includes/BookingHooks.php`
- `includes/Domain/BookingRequest.php`

### Nhóm thanh toán QR demo
- `includes/DemoPaymentQrHooks.php`
- `includes/Domain/DemoPaymentQrData.php`

### Nhóm tiện ích khác
- `includes/DemoSeeder.php`
- `includes/SubmissionPolisher.php`

## Ma trận trách nhiệm module
| Module | Trách nhiệm chính | Không nên gánh |
| --- | --- | --- |
| `Bootstrap.php` | Nạp module và ghép hệ thống | Business rule chi tiết của booking/payment |
| `CmsSetup.php` | Taxonomy, CPT, page seed, CMS defaults, contact form | Render storefront theme |
| `ProductMeta.php` | Admin input và persistence metadata tour | Validate payment hoặc webhook |
| `BookingHooks.php` | Render/validate booking fields, persist cart/order meta | Theme layout hoặc MongoDB trực tiếp |
| `DemoPaymentQrHooks.php` | Hiển thị QR/panel demo theo order | Xác nhận thanh toán thật từ webhook |
| `Domain/*` | Chuẩn hóa shape dữ liệu tour, booking, QR | Logic UI hoặc storage ngoài phạm vi module |

## CmsSetup
`CmsSetup.php` đang đảm nhiệm các việc rất quan trọng:

- Đăng ký taxonomy `destination`
- Đăng ký taxonomy `tour_style`
- Đăng ký custom post type `promotion`
- Đăng ký custom post type `testimonial`
- Đăng ký shortcode `op_travel_contact_form`
- Xử lý submit form liên hệ
- Seed các page chuẩn của website
- Áp dụng cấu hình WooCommerce mặc định
- Đổi rewrite của product sang slug `/tours/`

Đây là phần rất tốt để trình bày vì cho thấy plugin không chỉ thêm một field, mà đã can thiệp có hệ thống vào CMS.

## ProductMeta
`ProductMeta.php` cho phép quản trị viên nhập metadata riêng cho tour trong admin product:

- `tour_code`
- `duration_text`
- `departure_city`
- `available_departure_dates`
- `highlights`
- `itinerary`
- `includes`
- `excludes`
- `meeting_point`
- `gallery_ids`

Ý nghĩa:
- Tour không còn là product với tên và giá đơn thuần.
- Quản trị viên có thể nhập đầy đủ dữ liệu hành trình.
- Frontend dùng lại metadata này để hiển thị chi tiết tour và phục vụ booking.

## BookingHooks
`BookingHooks.php` là trung tâm của luồng đặt tour:

- Render booking fields trước nút add to cart
- Validate dữ liệu booking
- Lưu payload booking vào cart item data
- Hiển thị metadata booking trong giỏ hàng
- Ghi metadata booking vào order item khi checkout

Các field đang có:
- `departure_date`
- `adult_count`
- `child_count`
- `customer_note`

Đây là minh chứng trực tiếp cho “lập trình tùy biến chức năng”.

## DemoPaymentQrHooks
`DemoPaymentQrHooks.php` đang đảm nhiệm phần minh họa thanh toán:

- Hook vào `woocommerce_thankyou`
- Hook vào `woocommerce_view_order`
- Dựng panel QR cho order
- Dùng `DemoPaymentQrData` để xây dựng dữ liệu như:
  - số tiền
  - nội dung chuyển khoản
  - tài khoản nhận
  - ảnh QR
  - link VietQR

Phần này cực kỳ phù hợp để demo vì sinh viên có thể cho hội đồng thấy QR theo từng order cụ thể.

## Taxonomy và post type
### Taxonomy
- `destination`: quản lý điểm đến
- `tour_style`: quản lý loại tour

### Custom post type
- `promotion`: quản lý khuyến mãi
- `testimonial`: quản lý đánh giá khách hàng

Nhờ vậy, website có mô hình nội dung riêng chứ không phụ thuộc hoàn toàn vào post/page mặc định của WordPress.

## Meta tour
Các metadata của tour giúp tạo ra sự khác biệt giữa “tour” và “sản phẩm thường”:

- Có mã tour để vận hành và đối chiếu
- Có thời lượng và nơi khởi hành
- Có danh sách ngày khởi hành
- Có lịch trình cụ thể
- Có danh sách bao gồm và không bao gồm
- Có điểm hẹn
- Có gallery riêng

Đây là phần nên được đưa vào slide bằng screenshot admin product tab `Thông tin tour`.

## Luồng booking
Luồng booking hiện tại có thể mô tả như sau:

1. Khách mở trang chi tiết tour
2. Hệ thống hiển thị form chọn ngày khởi hành, số người và ghi chú
3. Plugin validate dữ liệu
4. Dữ liệu hợp lệ được đưa vào cart item
5. Cart hiển thị lại metadata booking
6. Checkout tạo order với metadata tương ứng
7. Thank-you page tiếp tục dùng order để hiển thị QR hoặc trạng thái thanh toán

## Lưu dữ liệu vào order
Điểm kỹ thuật quan trọng là plugin không chỉ lưu vào session tạm:

- Dữ liệu booking được đưa vào `cart item data`
- Sau đó đi vào `order item meta`
- Nghĩa là thông tin booking theo được tới cấp độ order

Điều này giúp:
- Nhân viên điều hành đọc được đơn
- Có thể đồng bộ sang MongoDB
- Có thể dùng lại cho email và báo cáo

## Điểm nối ra ngoài plugin
### Đồng bộ booking sang service
- Trigger phù hợp là sau khi đã có order hoặc snapshot booking đủ dữ liệu.
- Contract outbound cần bám `POST /api/bookings`.
- Payload phải giữ các trường như `wordpress_order_id`, `tour_code`, `departure_date`, `adult_count`, `child_count`, `amount`, `payment_status`.

### Nhận xác nhận thanh toán từ service
- Plugin cần là nơi nhận `POST /wp-json/op-travel/v1/payment-confirm`.
- Request phải được bảo vệ bằng `PAYMENT_SYNC_SECRET`.
- Khi nhận callback hợp lệ, plugin mới cập nhật trạng thái order/payment meta và để theme render trạng thái cuối.

## Thông báo người dùng
Plugin đang tạo thông báo cho người dùng ở nhiều mức:

- Báo lỗi validate khi thiếu ngày khởi hành hoặc dữ liệu sai
- Báo lỗi nonce khi phiên giữ chỗ hết hạn
- Hiển thị trạng thái gửi form liên hệ
- Hiển thị panel QR trên thank-you page

## Khả năng mở rộng
Plugin hiện có cấu trúc đủ tốt để mở rộng thêm:

- REST endpoint nhận xác nhận thanh toán từ service
- Đồng bộ booking sang MongoDB
- Dashboard admin về doanh thu, trạng thái thanh toán
- Thêm rule hết hạn giữ chỗ
- Thêm số lượng chỗ trống theo ngày khởi hành

## Minh chứng trong source code
- `wp-content/plugins/op-travel-core/op-travel-core.php`
- `wp-content/plugins/op-travel-core/includes/Bootstrap.php`
- `wp-content/plugins/op-travel-core/includes/CmsSetup.php`
- `wp-content/plugins/op-travel-core/includes/ProductMeta.php`
- `wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- `wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- `wp-content/plugins/op-travel-core/includes/Domain/DemoPaymentQrData.php`

## Checklist plugin nghiệp vụ
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Bootstrap plugin | Có điểm khởi tạo rõ ràng | Đã có |
| Taxonomy du lịch | `destination`, `tour_style` | Đã có |
| Post type phụ trợ | `promotion`, `testimonial` | Đã có |
| Metadata tour | Tab `Thông tin tour` trong admin | Đã có |
| Booking fields | Ngày đi, số khách, ghi chú | Đã có |
| Validate booking | Chặn dữ liệu sai trước add to cart | Đã có |
| Persist vào cart/order | Theo suốt workflow | Đã có |
| QR demo | Hiển thị theo order | Đã có |
| Booking outbound sync | Gọi `POST /api/bookings` đúng contract | Cần bổ sung |
| Payment confirm endpoint | Nhận `POST /wp-json/op-travel/v1/payment-confirm` với `PAYMENT_SYNC_SECRET` | Cần bổ sung |
| Mongo sync | Gửi dữ liệu ra service business thay vì chạm Mongo trực tiếp | Cần bổ sung |
| Test plugin | Có unit/integration test cho booking flow và callback | Cần bổ sung |

## Những gì đã có
- Một plugin riêng thay vì nhồi code vào theme
- Cấu trúc file tương đối rõ ràng
- Các phần cốt lõi cho du lịch đã hiện diện
- Booking flow hoạt động xuyên suốt WooCommerce
- QR demo đã có nền tảng tốt

## Những gì cần bổ sung để hoàn thiện đồ án
- Tạo endpoint `POST /wp-json/op-travel/v1/payment-confirm`
- Tạo lớp gửi `POST /api/bookings` sang service
- Tạo admin page xem lịch sử thanh toán và đồng bộ
- Tạo report mini từ MongoDB hoặc cache tổng hợp
- Tạo unit test hoặc integration test cho các phần quan trọng

## Cách trình bày khi bảo vệ
- Giới thiệu plugin riêng `OP Travel Core` là phần trái tim của nghiệp vụ.
- Mở `CmsSetup.php` để nói về taxonomy, post type và seed page.
- Mở `ProductMeta.php` để chỉ tab `Thông tin tour`.
- Mở `BookingHooks.php` để giải thích luồng booking fields.
- Mở cart và order để cho thấy metadata đã đi xuyên suốt hệ thống.
- Mở `DemoPaymentQrHooks.php` để giới thiệu QR theo order.
- Chốt rằng plugin này là nền tảng để tích hợp payOS và MongoDB.

## Kết luận phase
Phase 5 chứng minh HV-Travel có phần lập trình plugin đủ chiều sâu để đáp ứng yêu cầu BCCĐ. Từ đây, phase tiếp theo sẽ tập trung vào nhánh nghiệp vụ nhạy cảm nhất với người dùng và hội đồng: thanh toán trực tuyến, quét mã QR và thông báo thanh toán thành công.
