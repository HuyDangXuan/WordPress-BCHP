# Phase 9 - Kiểm thử, nghiệm thu và kịch bản demo

## Mục tiêu phase
Xây dựng bộ kiểm thử và các kịch bản demo cho HV-Travel để đảm bảo hệ thống có thể trình diễn trơn tru trong buổi bảo vệ.

## Đầu vào
- Theme đã tùy biến
- Plugin nghiệp vụ đã có booking flow
- Luồng thanh toán QR/payOS đã được thiết kế
- Kế hoạch deploy online trên Render

## Đầu ra
- Danh sách test case rõ ràng
- Kịch bản demo 5 phút và 10 phút
- Kế hoạch xử lý rủi ro trong buổi bảo vệ

## Ý nghĩa với BCCĐ
Một đồ án mạnh không chỉ có code, mà còn có kiểm thử và demo được chuẩn bị kỹ. Phase này giúp biến toàn bộ kết quả kỹ thuật thành một màn trình diễn mạch lạc, giảm tối đa rủi ro khi thao tác trực tiếp trước hội đồng.

## Nguyên tắc demo bắt buộc
- Demo mặc định phải đi theo journey khách hàng trước, không mở admin quá sớm.
- Luôn chuẩn bị ít nhất 2-3 tour mẫu, 1 order `pending`, 1 order `paid`.
- Nếu `payOS` sandbox lỗi, phải có phương án fallback bằng `BCK`, QR demo hoặc screenshot/video.
- Không mô tả MongoDB như database core của WordPress.
- Mọi trạng thái payment trình bày trước hội đồng phải bám đúng vocabulary `pending`, `paid`, `failed`, `expired`, `cancelled`.

## Dữ liệu demo tối thiểu
| Hạng mục | Số lượng tối thiểu | Mục đích |
| --- | --- | --- |
| Tour mẫu | 2-3 tour | Lọc archive, mở single, so sánh nội dung |
| Order `pending` | 1 | Demo luồng chờ thanh toán |
| Order `paid` | 1 | Demo thank-you thành công và QR/payment state |
| Account admin | 1 | Dùng khi cần mở product metadata hoặc order |
| Ảnh/video fallback | 1 bộ | Dự phòng khi mạng hoặc sandbox lỗi |

## Test chức năng
### Mục tiêu
Xác nhận tất cả chức năng chính của website hoạt động đúng theo nghiệp vụ.

### Phạm vi
- hiển thị trang
- hiển thị danh sách tour
- metadata tour
- booking fields
- cart
- checkout
- thank-you page
- contact form

## Test UI
### Cần kiểm tra
- Màu sắc, font, layout đúng theme
- Hero, archive, single tour hiển thị đúng
- Cart/checkout/thank-you không vỡ giao diện
- Responsive trên mobile và tablet

## Test booking
### Các trường hợp cần kiểm tra
- Có ngày khởi hành hợp lệ
- Không chọn ngày khởi hành
- Người lớn nhỏ hơn 1
- Trẻ em bằng 0
- Có ghi chú khách hàng
- Metadata booking có xuất hiện ở cart
- Metadata booking có đi vào order

## Test thanh toán
### Với QR demo / BCK
- Tạo đơn và hiển thị QR đúng order
- Nội dung chuyển khoản đúng định dạng
- Tài khoản nhận hiển thị đầy đủ

### Với payOS
- Tạo link thanh toán
- Điều hướng `returnUrl`
- Điều hướng `cancelUrl`
- Nhận webhook
- Order đổi trạng thái đúng

## Test webhook
### Mục tiêu
Đảm bảo callback thanh toán không chỉ “đến”, mà còn được xử lý đúng.

### Cần kiểm tra
- chữ ký hợp lệ
- số tiền khớp
- order tồn tại
- event không bị xử lý trùng
- order đã `paid` thì callback lặp lại không làm sai trạng thái

## Test MongoDB sync
### Mục tiêu
Đảm bảo dữ liệu nghiệp vụ được ghi xuống MongoDB đầy đủ.

### Cần kiểm tra
- tạo booking ghi vào `bookings`
- tạo payment ghi vào `payments`
- webhook ghi vào `payment_events`
- contact form ghi vào `contacts` nếu tích hợp
- API report trả dữ liệu doanh thu

## Test deploy lại không mất dữ liệu
### Mục tiêu
Đảm bảo hệ thống production có thể redeploy mà không mất:
- đơn hàng WordPress
- collection MongoDB
- uploads media

### Cần kiểm tra
- restart web service
- redeploy image mới
- truy cập lại đơn hàng cũ
- truy cập lại QR/order detail

## Danh sách test case
| Mã test | Nội dung | Kết quả mong đợi | Ưu tiên |
| --- | --- | --- | --- |
| TC01 | Mở trang chủ | Hero, CTA, tour nổi bật hiển thị đúng | Cao |
| TC02 | Mở archive tour | Bộ lọc `destination` và `tour_style` hoạt động | Cao |
| TC03 | Mở single tour | Metadata tour hiển thị đủ | Cao |
| TC04 | Chọn ngày khởi hành hợp lệ | Add to cart thành công | Cao |
| TC05 | Không chọn ngày khởi hành | Hệ thống báo lỗi | Cao |
| TC06 | Cart hiển thị metadata booking | Đúng ngày đi, số khách, ghi chú | Cao |
| TC07 | Checkout tạo order | Order tạo thành công | Cao |
| TC08 | QR hiển thị sau checkout | QR đúng theo order | Cao |
| TC09 | Webhook thành công | Order đổi `paid` | Cao |
| TC10 | Webhook trùng | Không xử lý lặp | Cao |
| TC11 | Mở thank-you page | Hiển thị trạng thái, mã đơn, tổng tiền | Cao |
| TC12 | Contact form gửi thành công | Báo `success` và gửi mail | Trung bình |
| TC13 | Mở trên mobile | Giao diện không vỡ | Trung bình |
| TC14 | Redeploy production | Dữ liệu vẫn còn | Cao |

## Kết quả mong đợi
- Khách có thể đi trọn luồng từ chọn tour đến hoàn tất đơn.
- Dữ liệu booking không mất giữa single product, cart, checkout và order.
- Trạng thái thanh toán được cập nhật rõ ràng.
- MongoDB ghi nhận đầy đủ dữ liệu nghiệp vụ chính.
- Website online vẫn ổn định sau redeploy.

## Tiêu chí pass trước buổi bảo vệ
- Homepage, archive, single, cart, checkout, thank-you đều mở được.
- Có ít nhất một luồng thanh toán hoặc fallback được minh họa trọn vẹn.
- Order `paid` hiển thị đúng trạng thái, mã đơn và CTA.
- Nếu đã tích hợp service, webhook và MongoDB phải có bằng chứng log hoặc dữ liệu mẫu.
- Team biết rõ khi nào chuyển sang phương án fallback thay vì cố live demo quá lâu.

## Kịch bản demo 5 phút
1. Mở trang chủ và giới thiệu đề tài.
2. Vào trang `Tours`, lọc một tour.
3. Mở chi tiết tour, chọn ngày đi và số khách.
4. Thêm vào giỏ hàng, cho thấy metadata booking.
5. Sang checkout, xác nhận phương thức thanh toán.
6. Mở thank-you page và chỉ QR/trạng thái đơn.
7. Kết luận nhanh về plugin, theme và thanh toán.

## Kịch bản demo 10 phút
1. Giới thiệu kiến trúc tổng thể: WordPress, WooCommerce, plugin, MongoDB, Docker, Render.
2. Mở admin product và chỉ tab `Thông tin tour`.
3. Mở archive tour và giải thích taxonomy.
4. Mở single tour, điền booking fields.
5. Mở cart và giải thích bước giữ chỗ.
6. Mở checkout và chỉ payment method.
7. Mở thank-you page và giải thích QR theo order.
8. Nói về webhook, MongoDB và report service.
9. Nói về deploy online bằng Docker trên Render.
10. Chốt bằng lợi ích thực tiễn của đề tài.

## Rủi ro khi bảo vệ
- Mạng internet không ổn định
- Sandbox cổng thanh toán không phản hồi
- QR không tải do API ngoài chậm
- Email không gửi vì SMTP bị chặn
- Site online bị cold start hoặc restart đúng lúc demo

## Phương án xử lý nếu cổng thanh toán/sandbox lỗi
- Chuẩn bị sẵn một order đã `paid`
- Chuẩn bị sẵn route thank-you mẫu
- Chuyển sang `BCK` hoặc QR demo của hệ thống
- Có screenshot/video ngắn phòng trường hợp mạng lỗi
- Nếu cần, trình bày webhook và MongoDB bằng sơ đồ thay cho live callback

## Ma trận rủi ro và fallback
| Rủi ro | Dấu hiệu | Fallback nên dùng |
| --- | --- | --- |
| Mạng chậm hoặc mất mạng | Site hoặc gateway tải lâu | Chuyển sang ảnh/video hoặc site local |
| `payOS` sandbox lỗi | Không tạo link, webhook không về | Dùng `BCK` hoặc QR demo theo order |
| SMTP lỗi | Mail không gửi | Bỏ demo mail, tiếp tục bằng order state |
| Cold start / restart | Site online phản hồi chậm | Dùng order mẫu, nói trên sơ đồ trước |
| Webhook không về kịp | Order chưa đổi `paid` | Mở order `paid` đã chuẩn bị sẵn |

## Minh chứng trong source code
- `wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- `wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- `wp-content/themes/op-travel-shop/woocommerce/cart/cart.php`
- `wp-content/themes/op-travel-shop/woocommerce/checkout/form-checkout.php`
- `wp-content/themes/op-travel-shop/woocommerce/checkout/thankyou.php`

## Checklist kiểm thử và nghiệm thu
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| UI homepage | Kiểm tra layout và CTA | Cần chạy trên môi trường thật |
| UI archive | Kiểm tra lọc taxonomy | Cần chạy trên môi trường thật |
| UI single tour | Kiểm tra panel booking | Cần chạy trên môi trường thật |
| Cart flow | Metadata booking hiển thị đúng | Cần chạy trên môi trường thật |
| Checkout flow | Tạo order và hiển thị payment | Cần chạy trên môi trường thật |
| Thank-you flow | Thông báo trạng thái và QR | Cần chạy trên môi trường thật |
| Webhook | Callback cập nhật order | Cần bổ sung service |
| Mongo sync | Ghi collections nghiệp vụ | Cần bổ sung service |
| Deploy persistence | Redeploy không mất dữ liệu | Cần kiểm thử production |
| Demo backup plan | Có kịch bản fallback | Cần chuẩn bị trước bảo vệ |
| Order mẫu | Có ít nhất 1 `pending`, 1 `paid` | Cần chuẩn bị trước bảo vệ |
| Screenshot/video | Có fallback khi live demo lỗi | Cần chuẩn bị trước bảo vệ |

## Những gì đã có
- Luồng frontend cho booking và thank-you đã khá rõ
- QR demo đã giúp việc trình diễn dễ hơn
- Theme và plugin đã đủ để xây kịch bản demo thực tế

## Những gì cần bổ sung để hoàn thiện đồ án
- Chạy test end-to-end trên môi trường thật
- Tạo dữ liệu demo chuẩn
- Chuẩn bị ít nhất 2 order mẫu
- Chuẩn bị ảnh/video fallback
- Hoàn thiện webhook và Mongo sync nếu chưa xong

## Cách trình bày khi bảo vệ
- Cho hội đồng thấy nhóm đã có checklist kiểm thử, không làm demo cảm tính.
- Nêu ngắn gọn từng nhóm test.
- Trình bày kịch bản 5 phút nếu thời gian ngắn.
- Chuyển sang kịch bản 10 phút nếu được hỏi sâu.
- Nói trước cả phương án fallback khi sandbox lỗi để thể hiện tư duy thực tế.

## Kết luận phase
Phase 9 giúp chuyển toàn bộ kiến trúc và code thành một sản phẩm có thể nghiệm thu. Khi đã có test case, demo script và phương án dự phòng, buổi bảo vệ sẽ bớt phụ thuộc vào may rủi và tập trung hơn vào giá trị kỹ thuật của đồ án. Phase tiếp theo sẽ gom các chi tiết kỹ thuật thành phụ lục để người đọc tra cứu nhanh.
