# Phase 11 - Kịch bản thuyết trình và phân công trình bày

## Mục tiêu phase
Chuẩn bị lời nói trực tiếp cho buổi bảo vệ BCCĐ, bảo đảm nội dung ngắn gọn, đúng trọng tâm, dễ nói và bám sát các phase kỹ thuật đã xây dựng.

## Đầu vào
- Toàn bộ bộ tài liệu `Phases/`
- Website demo
- Source code theme/plugin

## Đầu ra
- Kịch bản nói hoàn chỉnh
- Gợi ý phân công trình bày theo nhiều người hoặc một người
- Danh sách câu hỏi phản biện thường gặp

## Ý nghĩa với BCCĐ
Một đồ án kỹ thuật mạnh nhưng nói rời rạc vẫn dễ bị mất điểm. File này giúp chuyển các phần kỹ thuật thành một kịch bản nói rõ ràng, có mở đầu, thân bài, demo, kết luận và phần trả lời phản biện.

## Nguyên tắc trình bày bắt buộc
- Đi theo journey khách hàng trước, không nhảy quá sớm vào admin hoặc code.
- Mọi phần trả lời phản biện phải bám đúng boundary đã chốt: `WordPress/WooCommerce + MySQL` cho core, service + `MongoDB` cho business.
- `payOS` là hướng thanh toán chính; `BCK` và QR demo chỉ là fallback/minh họa.
- Nếu live demo lỗi, chuyển sang order mẫu, screenshot hoặc sơ đồ ngay, không dừng quá lâu ở bước khắc phục.

## Mở đầu 30 giây
“Nhóm em thực hiện đề tài website bán vé tour du lịch HV-Travel. Hệ thống được xây dựng trên WordPress và WooCommerce, có theme riêng, plugin nghiệp vụ riêng, hỗ trợ quy trình đặt tour, thanh toán QR và định hướng tích hợp MongoDB, Docker, Render để có thể chạy trực tuyến thực tế. Mục tiêu của đề tài là tạo ra một website du lịch vừa dễ quản trị, vừa có luồng mua tour rõ ràng và có khả năng mở rộng.”

## Giới thiệu đề tài
“Lý do nhóm em chọn đề tài này là vì du lịch là một bài toán rất gần với thương mại điện tử dịch vụ. Khác với bán sản phẩm vật lý, một tour du lịch cần có ngày khởi hành, số khách, ghi chú vận hành và trạng thái thanh toán rõ ràng. Vì vậy, đề tài này phù hợp để thể hiện cả phần giao diện lẫn phần lập trình plugin.”

## Giới thiệu kiến trúc
“Về kiến trúc, hệ thống được chia làm ba lớp chính. Lớp thứ nhất là WordPress và WooCommerce để quản trị nội dung, tour, giỏ hàng, checkout và đơn hàng. Lớp thứ hai là plugin nghiệp vụ OP Travel Core để xử lý taxonomy, metadata tour, booking fields và QR thanh toán. Lớp thứ ba là service nghiệp vụ kết nối MongoDB để lưu booking, payment, event log và báo cáo. Website được định hướng đóng gói bằng Docker và triển khai trên Render.”

## Demo cấu hình
“Ở phần cấu hình, nhóm em cài đặt WordPress, WooCommerce, theme OP Travel Shop và plugin OP Travel Core. Plugin này còn seed sẵn các trang như trang chủ, tours, giỏ hàng, thanh toán, tài khoản và liên hệ. Trong `wp-config.php`, phần cấu hình database và debug được tách rõ để thuận tiện cho local và production.”

## Demo version control
“Về quản lý phiên bản, nhóm em dùng Git để quản lý toàn bộ source code. Hướng phát triển được tổ chức theo các nhánh `main`, `develop`, `feature/*`, `release/*`, `hotfix/*`. Cách làm này giúp mỗi chức năng như booking, payment hoặc deploy được tách riêng, dễ theo dõi và dễ quay lui khi cần.”

## Demo skin
“Về giao diện, nhóm em không dùng WooCommerce mặc định mà xây dựng theme riêng tên là OP Travel Shop. Điểm nhấn của theme là toàn bộ hành trình mua tour được tổ chức thành 4 bước: chọn tour, xác nhận giữ chỗ, thanh toán và hoàn tất. Từ trang chủ, archive, single tour đến checkout và thank-you page đều được thiết kế lại để phù hợp với hành vi mua tour.”

## Demo plugin
“Về plugin tùy biến, OP Travel Core là phần trung tâm của nghiệp vụ. Plugin đăng ký taxonomy điểm đến, loại tour, post type khuyến mãi và testimonial. Ngoài ra plugin còn thêm tab `Thông tin tour` trong sản phẩm để nhập mã tour, thời lượng, nơi khởi hành, lịch trình, ngày khởi hành và các nội dung bao gồm, không bao gồm. Khi khách đặt tour, plugin thu thập thông tin ngày đi, số lượng khách và ghi chú, sau đó đưa dữ liệu này xuyên suốt cart, checkout và order.”

## Demo thanh toán QR
“Ở phần thanh toán, hệ thống định hướng dùng payOS làm phương án online chính. Trong phiên bản hiện tại, nhóm em đã có nền QR demo theo từng đơn hàng bằng `DemoPaymentQrHooks`. Sau khi khách tạo đơn, hệ thống hiển thị QR, số tiền, nội dung chuyển khoản và tài khoản nhận tương ứng với order đó. BCK được giữ như một phương án dự phòng hoặc minh họa so sánh.”

## Demo trạng thái thành công
“Điểm nhấn của bài demo là thank-you page. Khi đơn thành công, trang này hiển thị mã đơn, ngày đặt, tổng tiền, phương thức thanh toán, trạng thái đơn và QR tương ứng. Nếu thanh toán thất bại, trang sẽ hiện thông báo khác và cho phép thanh toán lại. Điều này giúp quy trình mua tour trở nên rõ ràng và trực quan.”

## Demo deploy online
“Về triển khai trực tuyến, nhóm em định hướng dùng Docker và Render. Hệ thống sẽ được tách thành nhiều service: WordPress web service, MySQL private service, MongoDB private service và booking/payment API service. Chỉ có WordPress là public, còn database và service nghiệp vụ đi qua private network. Cách triển khai này phù hợp với tư duy vận hành thực tế hơn so với việc gom tất cả vào một máy duy nhất.”

## Kết luận
“Tóm lại, HV-Travel là một đề tài kết hợp giữa thương mại điện tử, hệ thống thông tin và tùy biến phần mềm trên nền WordPress. Điểm mạnh của đồ án là có giao diện riêng, có plugin nghiệp vụ riêng, có quy trình đặt tour rõ ràng, có thanh toán QR, có định hướng tích hợp MongoDB và có khả năng triển khai online bằng Docker và Render.”

## Kịch bản 5 phút gợi ý
1. Mở đầu 30 giây.
2. Giới thiệu kiến trúc tổng thể trong 45-60 giây.
3. Demo nhanh journey khách hàng: archive -> single -> cart -> checkout -> thank-you.
4. Nhấn vào 3 điểm kỹ thuật: theme riêng, plugin booking, payment/QR.
5. Kết luận 30 giây bằng MongoDB + Docker/Render.

## Kịch bản 10 phút gợi ý
1. Mở đầu và lý do chọn đề tài.
2. Giới thiệu kiến trúc tổng thể.
3. Demo phần cấu hình và workflow Git ngắn gọn.
4. Demo theme `op-travel-shop` theo 4 bước.
5. Demo plugin `op-travel-core` với booking fields và metadata tour.
6. Demo thank-you page, QR/payment state và nói về webhook.
7. Giải thích MongoDB service và deploy Render.
8. Kết luận và mở sang phần phản biện.

## Phân công trình bày nếu có 3 thành viên
### Thành viên 1
“Em trình bày phần giới thiệu đề tài, kiến trúc tổng thể, lý do chọn WordPress và WooCommerce, đồng thời giới thiệu tổng quan về MongoDB, Docker và Render.”

### Thành viên 2
“Em trình bày phần cấu hình hệ thống, quản lý phiên bản, tùy biến skin theme và demo giao diện trang chủ, archive tour, single tour, cart và checkout.”

### Thành viên 3
“Em trình bày phần plugin OP Travel Core, booking flow, QR thanh toán, thank-you page, MongoDB sync, deploy online và phần kết luận.”

## Phân công trình bày nếu chỉ có 1 người
“Nếu chỉ có một người trình bày, nên đi theo thứ tự: giới thiệu đề tài, kiến trúc, cấu hình, theme, plugin, thanh toán, MongoDB, deploy, kết luận. Khi demo, nên thao tác theo đúng journey khách hàng để mạch kể tự nhiên hơn.”

## Câu hỏi phản biện thường gặp
### Câu hỏi 1
“Vì sao chọn WordPress mà không viết từ đầu?”

Trả lời:
“Vì bài toán của đề tài cần cả quản trị nội dung lẫn thương mại điện tử. WordPress và WooCommerce cung cấp nền tảng rất mạnh cho phần đó, còn nghiệp vụ tour được nhóm em tùy biến bằng plugin riêng. Cách làm này tiết kiệm thời gian nền tảng và tập trung công sức vào phần nghiệp vụ.”

### Câu hỏi 2
“Vì sao nói dùng MongoDB nhưng WordPress vẫn dùng MySQL?”

Trả lời:
“WordPress core vận hành chuẩn trên MySQL. Nhóm em dùng MongoDB cho dữ liệu nghiệp vụ mở rộng như booking, payment event, contact lead và report thông qua service trung gian. Đây là kiến trúc đúng kỹ thuật và dễ mở rộng hơn.”

### Câu hỏi 3
“Nếu cổng thanh toán lỗi thì sao?”

Trả lời:
“Nhóm em có phương án dự phòng bằng BCK hoặc QR demo theo order. Ngoài ra, phần trạng thái đơn và thank-you page vẫn được tổ chức đầy đủ để minh họa trọn luồng nghiệp vụ.”

### Câu hỏi 4
“Plugin tùy biến của nhóm có gì khác so với cài plugin có sẵn?”

Trả lời:
“Plugin OP Travel Core xử lý đúng nghiệp vụ tour của đề tài: taxonomy điểm đến, metadata tour, booking fields, lưu dữ liệu booking vào order và QR theo order. Đây là phần đặc thù mà plugin có sẵn khó đáp ứng đồng bộ.”

### Câu hỏi 5
“Vì sao cần Docker và Render?”

Trả lời:
“Docker giúp chuẩn hóa môi trường chạy, còn Render giúp hệ thống có thể triển khai online thực tế, có domain, HTTPS, auto deploy và private service cho database.”

## Phương án fallback khi live demo lỗi
- Nếu site online chậm: chuyển sang order mẫu hoặc site local.
- Nếu `payOS` sandbox lỗi: dùng `BCK` hoặc QR demo và nói rõ đây là fallback.
- Nếu webhook không về kịp: mở sẵn order `paid` đã chuẩn bị.
- Nếu SMTP lỗi: bỏ demo mail, tiếp tục bằng thank-you state và order detail.
- Nếu hội đồng hỏi sâu khi demo lỗi: quay lại sơ đồ kiến trúc và contract để giữ mạch kể.

## Minh chứng trong source code
- `wp-content/themes/op-travel-shop/`
- `wp-content/plugins/op-travel-core/`
- `wp-content/plugins/op-travel-core/includes/BookingHooks.php`
- `wp-content/plugins/op-travel-core/includes/DemoPaymentQrHooks.php`
- `Phases/`

## Những gì đã có
- Bộ khung nói bám sát đúng kiến trúc và source code hiện tại
- Có thể dùng trực tiếp để tập thuyết trình
- Có sẵn câu trả lời cho các phản biện phổ biến

## Những gì cần bổ sung để hoàn thiện đồ án
- Luyện nói theo thời gian thực tế
- Gắn ảnh màn hình hoặc slide đi kèm từng đoạn
- Chuẩn bị sẵn thứ tự mở tab trình duyệt và file code
- Chuẩn bị sẵn order `pending` và `paid`, cùng video/screenshot fallback

## Cách trình bày khi bảo vệ
- Nói ngắn, câu rõ, không đọc quá nhanh.
- Vừa nói vừa thao tác theo journey khách hàng.
- Nếu hội đồng hỏi sâu, quay về sơ đồ kiến trúc và plugin.
- Nếu demo lỗi, chuyển ngay sang ảnh hoặc order mẫu rồi tiếp tục giải thích.

## Kết luận phase
Phase 11 là điểm nối cuối cùng giữa kỹ thuật và trình bày. Khi đã có script rõ ràng, nhóm có thể biến một hệ thống nhiều thành phần như HV-Travel thành một câu chuyện mạch lạc, dễ hiểu và thuyết phục trước hội đồng.
