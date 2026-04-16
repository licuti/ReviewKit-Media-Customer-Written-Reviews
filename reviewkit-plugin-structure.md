---
plugin: "ReviewKit: Media & Customer Written Reviews (MCWR)"
version: "1.1.0"
text_domain: "review-kit"
---

# 0. TL;DR
- Purpose: Nâng cấp hệ thống đánh giá mặc định của WooCommerce với hình ảnh, video, thống kê chuyên nghiệp và công cụ quản trị.
- Entry file: `review-kit.php`
- Main modules:
  - `ReviewKit_Frontend`: Xử lý hiển thị Tab/Shortcode, AJAX Submit, Bộ lọc, Like/Report.
  - `ReviewKit_Admin_Settings`: Cấu hình plugin (General, Limits, Display, Shortcodes).
  - `ReviewKit_Admin_Pro`: Dashboard Analytics, Quản lý từ khóa, Nhập/Xuất CSV, Cấu hình dọn dẹp.
  - `ReviewKit_Admin_Review_Editor`: Meta Box chi tiết Media và cột Media trong danh sách Review.
  - `ReviewKit_Pro_Features`: Action Scheduler cho Email, Logic xóa file vật lý.
- Data stores: 
  - `wp_options`: Lưu các settings (prefix `reviewkit_`).
  - `wp_commentmeta`: Lưu rating, IDs media, lượt thích, báo cáo vi phạm.
  - `transients`: Cache thống kê sao (`reviewkit_rating_counts_{product_id}`).
- Public interfaces: 
  - Shortcodes: `[reviewkit_reviews]`, `[reviewkit_review_form]`, `[reviewkit_review_list]`, `[reviewkit_review_summary]`.
  - AJAX: `reviewkit_filter_reviews`, `reviewkit_vote_review`, `reviewkit_report_review`, `reviewkit_submit_review`.

---

# 1. OVERVIEW
- Plugin giải quyết vấn đề gì: Khắc phục sự đơn điệu của Review WooCommerce mặc định. Giúp tăng tỷ lệ chuyển đổi bằng feedback thực tế (hình ảnh/video) và quản lý đánh giá spam hiệu quả.
- User/admin sẽ thấy gì: 
  - User: Giao diện review hiện đại, bộ lọc sao, nút hữu ích/báo cáo, popup lightbox xem ảnh/video.
  - Admin: Dashboard biểu đồ thống kê, công cụ lọc từ khóa cấm, khả năng quản lý media trực tiếp từ danh sách bình luận.

---

# 2. FEATURES
## Core
- Multi-media Review: Upload nhiều ảnh & video cho mỗi đánh giá.
- Smart Calculation: Tính toán và cache tỉ lệ % sao, điểm trung bình.
- SEO Schema: Tích hợp JSON-LD AggregateRating và Individual Reviews.

## Admin
- Settings page: Giao diện tab tùy chỉnh màu sắc, giới hạn upload.
- Analytics: Biểu đồ Chart.js thống kê tăng trưởng review và phân bổ sao.
- Media Cleanup: Tùy chọn tự động xóa file vật lý khi xóa review vĩnh viễn.

## Frontend
- AJAX Filtering: Lọc đánh giá theo số sao, có media, hoặc trạng thái đã mua hàng mà không load lại trang.
- Voting System: Nút "Hữu ích" giúp tăng độ tin cậy của cộng đồng.
- Lightbox: Tích hợp Fancybox để trải nghiệm xem media mượt mà.

---

# 3. ARCHITECTURE
## File structure
- `review-kit.php`: Khởi tạo và nạp module.
- `includes/`: Chứa các Class logic thực thi.
- `assets/`: 
  - `css/`: `frontend.css`, `admin-pro.css`.
  - `js/`: `frontend.js`, `admin-pro.js`.
- `templates/`: Phân tách logic render (upcoming refactor).

## Modules
- `ReviewKit_Frontend` → Điều phối toàn bộ trải nghiệm người dùng cuối.
- `ReviewKit_Admin_Pro` → Cung cấp các công cụ quản trị nâng cao (Pro tools).
- `ReviewKit_Pro_Features` → Xử lý các tác vụ nền (Action Scheduler) và tối ưu dữ liệu.

---

# 4. ENTRY POINT
- File chính: `review-kit.php`
- Load flow:
  1. Define constants: `ReviewKit_VERSION`, `ReviewKit_PLUGIN_FILE`, `ReviewKit_PLUGIN_DIR`, `ReviewKit_PLUGIN_URL`.
  2. `require_once` toàn bộ file class qua `ReviewKit_PLUGIN_DIR`.
  3. `register_activation_hook` → `reviewkit_activate()` ghi default options.
  4. `register_deactivation_hook` → `reviewkit_deactivate()` (empty, data giữ lại).
  5. `before_woocommerce_init` → Khai báo HPOS compatibility.
  6. `init` → `load_plugin_textdomain()` từ `/languages/`.
  7. `plugins_loaded` → Init `ReviewKit_Frontend`, `ReviewKit_Pro_Features`, Admin classes.
- Files bổ sung (WP.org compliance):
  - `uninstall.php` → Xóa toàn bộ `reviewkit_*` options khi người dùng Delete plugin.
  - `readme.txt` → Metadata cho WordPress.org plugin directory.
  - `languages/review-kit.pot` → Template dịch thuật.

---

# 5. HOOKS
## Listen (add_action / add_filter)
- `woocommerce_product_tabs` → Tích hợp giao diện review vào tab sản phẩm.
- `wp_ajax_reviewkit_submit_review` → Xử lý form submit bằng AJAX.
- `transition_comment_status` → Xóa cache thống kê khi review được duyệt.
- `manage_comments_custom_column` → Hiển thị thumbnail media trong danh sách admin.
- `wp_delete_comment` → Logic dọn dẹp file vật lý.

## Emit (do_action / apply_filters)
- `reviewkit_admin_tab_content_tools` → Cho phép mở rộng nội dung tab Tools.
- `reviewkit_before_render_review_list` (planned) → Cho phép can thiệp trước khi hiện danh sách.

---

# 6. DATA LAYER
## Options
- `reviewkit_primary_color` → string → Màu chủ đạo giao diện.
- `reviewkit_delete_media_with_review` → boolean → Bật/tắt dọn dẹp media.
- `reviewkit_blacklist_keywords` → string → Danh sách từ khóa ngăn chặn spam.

## Meta
- `rating` → int → Số sao đánh giá (1-5).
- `review_image_ids` → string (csv) → Danh sách ID attachment ảnh.
- `review_video_ids` → string (csv) → Danh sách ID attachment video.
- `helpful_count` → int → Số lượt thích.

---

# 7. PUBLIC INTERFACES
## Shortcodes
- `[reviewkit_reviews]` → `product_id` → Toàn bộ giao diện review.
- `[reviewkit_review_summary]` → `product_id` → Chỉ hiện bảng thống kê sao.

## AJAX
- `reviewkit_vote_review` → `comment_id` → Tăng số lượt hữu ích.
- `reviewkit_filter_reviews` → `product_id, star, has_media` → Trả về HTML danh sách đã lọc.

---

# 8. DATA FLOW
## Flow 1 (Submit Review)
- Validate: Kiểm tra email, từ khóa cấm, nonce.
- Sanitize: Dùng `wp_kses_post`, `sanitize_text_field`.
- Media Process: `wp_handle_upload` -> `wp_get_image_editor` (resize) -> `wp_insert_attachment`.
- Save: `wp_insert_comment` -> `update_comment_meta`.
- Cache: `delete_transient` cho product liên quan.
- Return: JSON kèm thông báo thành công.

## Flow 2 (Render Summary)
- Query: SQL trực tiếp tính `COUNT` theo từng mức sao.
- Cache: Lưu kết quả vào transient 24h.
- Render: Tính % tỉ lệ bar -> xuất HTML kèm JSON-LD Schema.

---

# 9. SECURITY
- Capability: `manage_options` cho cấu hình, `edit_posts` cho media.
- Nonce: `reviewkit_ajax_nonce` (frontend), `reviewkit_pro_nonce` (admin).
- Sanitization / Escaping: Luôn dùng `esc_attr`, `esc_html`, `wp_kses` khi output ra trình duyệt.

---

# 10. NOTES FOR AI
- Prefix: `reviewkit_` hoặc `REVIEWKIT_`.
- Không tạo thêm option/meta ngoài các key đã quy định ở Data Layer.
- Tận dụng `wp_commentmeta` thay vì tạo bảng riêng để tối ưu WP Query.
- Tất cả các thao tác dữ liệu lớn (xóa file, gửi mail) phải dùng Action Scheduler.
