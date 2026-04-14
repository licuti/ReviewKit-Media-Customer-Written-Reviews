=== ReviewKit: Media & Customer Written Reviews ===
Contributors:         linhnguyen
Tags:                 woocommerce, reviews, ratings, product reviews, media upload
Requires at least:    6.0
Tested up to:         6.5
Requires PHP:         7.4
Stable tag:           1.1.0
License:              GPL-2.0-or-later
License URI:          https://www.gnu.org/licenses/gpl-2.0.html

Nâng cấp hệ thống đánh giá WooCommerce với upload ảnh/video, bộ lọc sao, thống kê và công cụ quản trị toàn diện.

== Description ==

**ReviewKit: Media & Customer Written Reviews (MCWR)** is a WordPress plugin that takes your WooCommerce review system to the next level.

= Tính năng nổi bật =

* **Upload đa phương tiện**: Khách hàng có thể đính kèm ảnh và video trong đánh giá.
* **Bộ lọc thông minh**: Lọc đánh giá theo số sao, có hình ảnh, hoặc đã mua hàng (Verified Buyer).
* **Hữu ích & Báo cáo**: Nút "Hữu ích" tăng độ tin cậy, hệ thống báo lỗi vi phạm giúp moderation hiệu quả.
* **Dashboard Thống kê**: Biểu đồ trực quan thống kê lượt review theo ngày và phân bổ sao.
* **Schema.org**: Tự động xuất AggregateRating và Review JSON-LD để tối ưu SEO.
* **Từ khóa cấm**: Lọc tự động các bình luận spam hoặc ngôn từ không phù hợp.
* **Nhập/Xuất CSV**: Hỗ trợ import hàng nghìn đánh giá từ các nền tảng khác.
* **Email Reminder**: Tự động nhắc nhở khách đánh giá sau khi đơn hàng hoàn thành.
* **Shortcodes linh hoạt**: Nhúng phần đánh giá vào bất cứ trang nào với 4 shortcodes chuyên biệt.
* **Quản trị Media**: Thumbnail ảnh/video hiển thị ngay trong danh sách bình luận admin.

= Shortcodes hỗ trợ =

* `[mcwr_reviews product_id="123"]` — Toàn bộ giao diện đánh giá.
* `[mcwr_review_form product_id="123"]` — Chỉ Form gửi đánh giá.
* `[mcwr_review_list product_id="123"]` — Chỉ Danh sách đánh giá.
* `[mcwr_review_summary product_id="123"]` — Chỉ Thống kê sao.

= Yêu cầu hệ thống =

* WordPress 6.0 trở lên
* WooCommerce 7.0 trở lên
* PHP 7.4 trở lên

== Installation ==

1. Tải và giải nén file plugin.
2. Upload thư mục `my-custom-woo-reviews` vào `/wp-content/plugins/`.
3. Kích hoạt plugin trong menu **Plugins** của WordPress Admin.
4. Go to **ReviewKit** in the admin menu to configure.

== Frequently Asked Questions ==

= Plugin có tương thích với theme của tôi không? =

Có. Plugin dùng CSS độc lập và không ghi đè style của theme. Có thể tùy chỉnh màu sắc trong trang Settings.

= Ảnh upload được lưu ở đâu? =

Ảnh và video được upload vào WordPress Media Library, cùng thư mục `/uploads/` tiêu chuẩn.

= Có thể dùng shortcode ngoài trang sản phẩm không? =

Có. Tất cả shortcodes đều nhận tham số `product_id` để nhúng vào bất cứ trang/bài viết nào.

== Changelog ==

= 1.1.0 =
* Thêm Action Scheduler cho hệ thống Email Reminder.
* Tích hợp Schema.org JSON-LD cho SEO.
* Thêm Dashboard Analytics với Chart.js.
* Thêm cột Media trong danh sách Phản hồi admin.
* Bổ sung tính năng tự động xóa Media khi xóa Review.
* Tối ưu hiệu năng với Transient Cache.

= 1.0.0 =
* Phiên bản đầu tiên.
* Hỗ trợ upload ảnh, video, đánh giá sao, bộ lọc AJAX.

== Upgrade Notice ==

= 1.1.0 =
Khuyến nghị cập nhật. Bổ sung nhiều tính năng quan trọng và tối ưu hiệu năng đáng kể.

== Screenshots ==

1. Giao diện đánh giá ngoài Frontend với bộ lọc sao và hình ảnh.
2. Trang Admin Dashboard Thống kê với Chart.js.
3. Cột Media trong danh sách Phản hồi.
4. Cài đặt màu sắc và cấu hình upload.
