# Phase 3 - Quản lý phiên bản và quy trình Git

## Mục tiêu phase
Mô tả cách quản lý source code của dự án HV-Travel bằng Git, từ workflow mục tiêu trên source repo đến mô hình nhánh, quy ước commit, release, hotfix và rollback phục vụ đồ án.

## Đầu vào
- Source repo HV-Travel khi được đưa vào Git
- Workspace tài liệu phase hiện tại
- Nhu cầu phát triển theme, plugin, payment, tài liệu và deploy

## Đầu ra
- Mô hình nhánh thống nhất
- Quy ước commit, tag và release rõ ràng
- Câu chuyện version control đủ chặt để trình bày trong BCCĐ

## Ý nghĩa với BCCĐ
Phần quản lý phiên bản cho thấy sinh viên không làm dự án theo kiểu chỉnh trực tiếp trên host, mà có quy trình phát triển có kiểm soát, tách nhánh, commit theo chức năng, gắn tag theo mốc demo và có khả năng rollback khi cần.

## Phạm vi áp dụng của phase
- Workspace hiện tại là bộ tài liệu phase, không chứa metadata Git thực tế như thư mục `.git/`.
- Vì vậy phase này mô tả workflow Git mục tiêu cần áp dụng cho source repo WordPress/HV-Travel, thay vì khẳng định trạng thái Git của workspace tài liệu hiện tại.
- Khi demo trên repo mã nguồn thật, mọi minh chứng như branch, tag và `git log` phải lấy từ source repo đó.

## Khởi tạo repo
- Source repo HV-Travel nên được khởi tạo thành một Git repository riêng cho mã nguồn WordPress và các thành phần liên quan.
- Theme `op-travel-shop`, plugin `op-travel-core`, plugin `BCK`, Docker assets và tài liệu cần được quản lý cùng một workflow version control rõ ràng.
- Nếu team đang tách riêng repo tài liệu và repo source code, repo mã nguồn vẫn là nơi chốt branch, tag, release và rollback.

## Nguyên tắc workflow bắt buộc
- `main` là nhánh ổn định, dùng cho demo chính thức và deploy.
- `develop` là nhánh tích hợp, nơi gom feature đã verify local.
- Không làm việc trực tiếp trên `main`.
- Không gộp nhiều subsystem không liên quan vào một commit duy nhất.
- Commit message phải mang ngữ nghĩa kỹ thuật rõ như `feat`, `fix`, `docs`, `refactor`.
- Mỗi mốc demo hoặc bàn giao phải có tag để khóa đúng phiên bản trình bày.

## Ý nghĩa các commit hiện có
Trong nhiều đồ án, giai đoạn đầu thường xuất hiện các commit kiểu:

1. `first commit`
2. `second commit`
3. `update`

Nhận xét:
- Các tên commit như vậy không cho biết đã thay đổi theme, plugin, payment hay deploy.
- Khi cần rollback hoặc giải thích quá trình phát triển, lịch sử commit mơ hồ sẽ rất khó dùng để thuyết trình.
- Vì vậy từ phase này trở đi, lịch sử commit phải chuyển sang dạng mô tả theo chức năng và subsystem.

## Mô hình nhánh đề xuất
### Nhánh chính
- `main`: nhánh ổn định, luôn sẵn sàng để demo hoặc deploy
- `develop`: nhánh tích hợp chung trước khi release

### Nhánh công việc
- `feature/*`: phát triển tính năng mới
- `release/*`: gom các thay đổi chuẩn bị demo/bảo vệ
- `hotfix/*`: sửa lỗi gấp trên phiên bản đã release

## Quy ước đặt tên branch
- `feature/theme-checkout-workflow`
- `feature/plugin-booking-metadata`
- `feature/payment-payos-integration`
- `feature/mongodb-sync-service`
- `feature/render-docker-topology`
- `feature/docs-bccd-phase-update`
- `release/demo-v1`
- `hotfix/fix-payment-callback`

## Ma trận branch theo loại thay đổi
| Loại thay đổi | Branch phù hợp | Ví dụ | Lý do tách riêng |
| --- | --- | --- | --- |
| Theme/storefront | `feature/*` | `feature/theme-tour-archive-redesign` | Tránh trộn UI với business logic plugin |
| Plugin nghiệp vụ | `feature/*` | `feature/plugin-booking-order-meta` | Dễ theo dõi thay đổi booking flow |
| Payment | `feature/*` | `feature/payment-payos-integration` | Luồng payment có rủi ro riêng, cần review tách biệt |
| MongoDB/service | `feature/*` | `feature/mongodb-sync-service` | Giữ boundary ngoài WordPress rõ ràng |
| Deploy/Docker | `feature/*` | `feature/render-docker-topology` | Tránh lẫn hạ tầng với code theme/plugin |
| Tài liệu | `feature/*` hoặc `docs/*` nếu team muốn | `feature/docs-bccd-phase-update` | Dễ chứng minh tiến độ báo cáo song song với code |
| Chuẩn bị demo | `release/*` | `release/demo-v1` | Khóa phạm vi thay đổi trước ngày bảo vệ |
| Vá lỗi gấp | `hotfix/*` | `hotfix/fix-payment-callback` | Sửa nhanh trên nhánh ổn định mà không chờ feature khác |

## Quy ước commit
Nên dùng commit message theo hướng ngắn, rõ chức năng:

- `feat: add booking fields for tour product`
- `feat: customize WooCommerce thank-you workflow`
- `feat: add QR payment panel for demo orders`
- `feat: add MongoDB booking sync endpoint`
- `docs: update phase-based BCCD documentation`
- `fix: validate departure date before add to cart`
- `fix: verify payment callback signature`
- `refactor: separate workflow helpers from theme bootstrap`

## Quy trình làm feature
1. Tạo nhánh từ `develop`.
2. Phát triển một nhóm thay đổi rõ ràng, ví dụ chỉ theme hoặc chỉ plugin.
3. Commit theo từng đơn vị chức năng.
4. Verify local trước khi merge.
5. Mở review nội bộ nếu team có người phụ trách từng subsystem.
6. Merge về `develop`.
7. Sau khi đủ tính năng, tạo `release/*`.
8. Chỉ merge về `main` khi nhánh release đã ổn định.

## Quy trình release demo
1. Từ `develop`, tạo `release/demo-v1`.
2. Chỉ cho phép fix nhẹ, không thêm tính năng lớn.
3. Kiểm tra:
   - UI trang chủ
   - Booking fields
   - Checkout
   - Thank-you page
   - QR/payment flow
   - Tài liệu BCCĐ
4. Gắn tag release.
5. Merge sang `main`.
6. Deploy từ `main`.

## Tiêu chí chặn trước khi cắt release
- Theme `op-travel-shop` phải hiển thị đúng homepage, archive, single, cart, checkout và thank-you.
- Plugin `op-travel-core` phải giữ được booking fields và cart/order metadata.
- Payment flow ở mức demo phải có ít nhất một nhánh fallback chạy được nếu `payOS` chưa hoàn tất.
- Bộ tài liệu phase phải khớp với chức năng đang có trong source repo.
- Nếu có Docker/deploy assets, chúng phải tương thích với nhánh release chuẩn bị dùng để demo.

## Gắn tag
Nên dùng các tag rõ mốc:

- `v0.1-theme-plugin-base`
- `v0.2-booking-flow`
- `v0.3-qr-demo`
- `v0.4-mongodb-sync`
- `v0.5-render-staging`
- `v1.0-bccd-demo`

## Liên hệ giữa commit và chức năng
- Nhóm commit `theme`: liên quan `op-travel-shop`
- Nhóm commit `plugin`: liên quan `op-travel-core`
- Nhóm commit `payment`: tích hợp `payOS`, `BCK`, webhook, QR
- Nhóm commit `deploy`: Docker, Render, env
- Nhóm commit `docs`: bộ tài liệu phase, script demo và ghi chú bảo vệ

## Quy trình hotfix và rollback
### Hotfix
1. Tạo nhánh `hotfix/*` từ `main`.
2. Chỉ sửa đúng lỗi khẩn, ví dụ callback payment hoặc route checkout.
3. Test nhanh trên local hoặc staging.
4. Merge lại vào `main`.
5. Đồng bộ ngược về `develop` để tránh mất bản vá ở các nhánh sau.

### Rollback
1. Xác định tag hoặc commit ổn định gần nhất, ví dụ `v0.3-qr-demo`.
2. Redeploy lại đúng tag đó hoặc tạo nhánh rollback từ mốc ổn định.
3. Chỉ sau khi dịch vụ ổn định mới tiếp tục điều tra lỗi trên nhánh riêng.
4. Khi trình bày với giảng viên, cần nhấn mạnh rollback dựa trên version/tag chứ không sửa tay trực tiếp trên host.

## Ánh xạ workflow Git với các phase kỹ thuật
| Phase | Loại branch thường gặp | Ví dụ |
| --- | --- | --- |
| `Phase 4` | `feature/theme-*` | `feature/theme-checkout-workflow` |
| `Phase 5` | `feature/plugin-*` | `feature/plugin-booking-metadata` |
| `Phase 6` | `feature/payment-*` | `feature/payment-payos-integration` |
| `Phase 7` | `feature/mongodb-*` | `feature/mongodb-sync-service` |
| `Phase 8` | `feature/deploy-*` hoặc `release/*` | `feature/render-docker-topology` |
| `Phase 9` | `release/*` và `hotfix/*` | `release/demo-v1`, `hotfix/fix-payment-callback` |

## Minh chứng trong source code
- Trong workspace tài liệu hiện tại không có `.git/`; minh chứng Git thật phải lấy từ source repo HV-Travel
- `git branch -a`
- `git log -10 --oneline`
- `git tag --list`
- `wp-content/themes/op-travel-shop/`
- `wp-content/plugins/op-travel-core/`
- `Phases/`

## Checklist quản lý phiên bản
| Hạng mục | Mô tả | Trạng thái |
| --- | --- | --- |
| Repo Git | Source repo WordPress phải được version hóa | Cần xác nhận trên source repo |
| Lịch sử commit | Commit phải phản ánh subsystem và chức năng | Cần áp dụng |
| Nhánh `main` | Nhánh ổn định để demo/deploy | Cần áp dụng |
| Nhánh `develop` | Nhánh tích hợp chung | Cần áp dụng |
| `feature/*` | Tách riêng từng tính năng | Cần áp dụng |
| `release/*` | Chuẩn bị bản demo | Cần áp dụng |
| `hotfix/*` | Sửa lỗi khẩn | Cần áp dụng |
| Tag version | Đánh dấu các mốc | Cần áp dụng |
| Commit message chuẩn | Tên commit thể hiện chức năng | Cần áp dụng |
| Rollback theo tag | Có mốc ổn định để quay lui | Cần áp dụng |

## Những gì đã có
- Bộ tài liệu đã chốt được branch model mục tiêu cho HV-Travel
- Các subsystem chính đã rõ để có thể tách branch theo `theme`, `plugin`, `payment`, `deploy`, `docs`
- Mốc release demo và rollback đã có thể mô tả nhất quán với các phase kỹ thuật

## Những gì cần bổ sung để hoàn thiện đồ án
- Áp dụng mô hình nhánh chính thức trên source repo WordPress thật
- Viết commit message rõ ngữ nghĩa theo subsystem
- Gắn tag cho từng mốc demo như `v0.3-qr-demo`, `v1.0-bccd-demo`
- Gắn quy trình deploy với nhánh `main` và nhịp release
- Tách tài liệu và code thành các đợt commit dễ theo dõi
- Chuẩn hóa hotfix và rollback theo tag thay vì sửa tay trên host

## Cách trình bày khi bảo vệ
- Nói ngắn gọn: nhóm dùng Git để quản lý mọi thay đổi của đồ án.
- Nếu đang mở source repo thật, show `git log --oneline` và `git tag` để chứng minh tiến trình theo phiên bản.
- Giải thích vì sao cần `main`, `develop`, `feature/*`.
- Nêu ví dụ một tính năng như booking fields sẽ phát triển trên một nhánh riêng.
- Nêu ví dụ release demo trước ngày bảo vệ.
- Nói về tag `v1.0-bccd-demo` để chốt bản trình diễn.
- Chốt rằng quản lý phiên bản giúp dự án an toàn, dễ phối hợp, dễ deploy và dễ rollback.

## Kết luận phase
Sau khi cấu hình được hệ thống và chốt quy trình Git rõ ràng, dự án đã có nền tảng phát triển bài bản hơn: biết tách nhánh theo subsystem, biết khóa bản demo bằng tag và biết quay lui nếu có sự cố. Phase tiếp theo sẽ đi vào phần tùy biến skin, nơi HV-Travel thể hiện rõ dấu ấn giao diện và trải nghiệm riêng thay vì sử dụng theme hoặc WooCommerce mặc định.
