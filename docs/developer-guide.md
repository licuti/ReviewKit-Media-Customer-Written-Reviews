# ReviewKit — Developer Guide

> **Plugin:** ReviewKit: Media & Customer Written Reviews  
> **Version:** 1.2.0+  
> **Text Domain:** `review-kit`

Plugin cung cấp hệ thống **Action Hooks**, **Filter Hooks**, và **Icon API**
để developer có thể mở rộng mà không cần chỉnh sửa code gốc.

---

## Mục lục

1. [Icon System](#1-icon-system)
2. [Filter Hooks](#2-filter-hooks)
3. [Action Hooks](#3-action-hooks)
4. [Shortcodes](#4-shortcodes)
5. [CSS Customization](#5-css-customization)

---

## 1. Icon System

### `mcwr_icons` — Override toàn bộ icon set

Tất cả icon trong frontend được quản lý qua hàm `mcwr_icon( $name )`.
Mặc định dùng **WordPress Dashicons**. Bạn có thể thay thế toàn bộ hoặc từng icon
bằng filter `mcwr_icons`.

**Danh sách tên icon:**

| Tên (`$name`)  | Vị trí hiển thị              | Dashicon mặc định            |
|----------------|------------------------------|------------------------------|
| `thumbs-up`    | Nút "Hữu ích"                | `dashicons-thumbs-up`        |
| `flag`         | Nút "Báo cáo"                | `dashicons-flag`             |
| `chat`         | Nút "Phản hồi" (admin)       | `dashicons-format-chat`      |
| `play`         | Overlay trên video thumbnail | `dashicons-controls-play`    |
| `video`        | Fallback khi không có thumb  | `dashicons-video-alt3`       |
| `verified`     | Badge "Đã mua hàng"          | `dashicons-yes`              |
| `admin`        | Badge "Quản trị viên"        | `dashicons-admin-users`      |
| `star`         | Sao trong bảng thống kê      | `dashicons-star-filled`      |
| `star-empty`   | Sao rỗng (dự phòng)          | `dashicons-star-empty`       |

---

### Ví dụ 1: Chuyển sang Font Awesome 6

```php
add_filter( 'mcwr_icons', function( array $icons ): array {
    $icons['thumbs-up'] = '<i class="fa-regular fa-thumbs-up" aria-hidden="true"></i>';
    $icons['flag']      = '<i class="fa-regular fa-flag" aria-hidden="true"></i>';
    $icons['chat']      = '<i class="fa-regular fa-comment" aria-hidden="true"></i>';
    $icons['play']      = '<i class="fa-solid fa-play" aria-hidden="true"></i>';
    $icons['video']     = '<i class="fa-solid fa-film" aria-hidden="true"></i>';
    $icons['verified']  = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>';
    $icons['admin']     = '<i class="fa-solid fa-user-shield" aria-hidden="true"></i>';
    $icons['star']      = '<i class="fa-solid fa-star" aria-hidden="true"></i>';
    return $icons;
} );
```

### Ví dụ 2: Dùng SVG tùy chỉnh cho một icon

```php
add_filter( 'mcwr_icons', function( array $icons ): array {
    $icons['verified'] = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
        viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
    </svg>';
    return $icons;
} );
```

### Ví dụ 3: Dùng Emoji

```php
add_filter( 'mcwr_icons', function( array $icons ): array {
    $icons['thumbs-up'] = '<span aria-hidden="true">👍</span>';
    $icons['star']      = '<span aria-hidden="true">⭐</span>';
    $icons['flag']      = '<span aria-hidden="true">🚩</span>';
    return $icons;
} );
```

### Ví dụ 4: Override HTML cuối cùng của một icon cụ thể

```php
// Filter mcwr_icon_html chạy sau khi đã lấy HTML từ mcwr_icons.
add_filter( 'mcwr_icon_html', function( string $html, string $name ): string {
    if ( 'thumbs-up' === $name ) {
        return '<span class="my-custom-icon my-thumbs-up"></span>';
    }
    return $html;
}, 10, 2 );
```

---

## 2. Filter Hooks

### `mcwr_icons`
Xem [Icon System](#1-icon-system) ở trên.

---

### `mcwr_icon_html`
Override HTML của một icon sau tất cả các bước xử lý.

```php
apply_filters( 'mcwr_icon_html', string $html, string $name )
```

| Tham số  | Kiểu     | Mô tả                        |
|----------|----------|------------------------------|
| `$html`  | `string` | HTML icon hiện tại           |
| `$name`  | `string` | Tên icon (vd: `'thumbs-up'`) |

---

## 3. Action Hooks

> 🔧 **Sắp ra mắt trong phiên bản tiếp theo:**
> `mcwr_before_review_card`, `mcwr_after_review_card`,
> `mcwr_before_review_form`, `mcwr_after_review_form`

---

## 4. Shortcodes

| Shortcode                                        | Mô tả                              |
|--------------------------------------------------|------------------------------------|
| `[mcwr_reviews product_id="123"]`               | Toàn bộ layout 2 cột               |
| `[mcwr_review_form product_id="123"]`           | Chỉ form gửi đánh giá              |
| `[mcwr_review_list product_id="123"]`           | Chỉ danh sách + bộ lọc             |
| `[mcwr_review_summary product_id="123"]`        | Chỉ thống kê sao                   |

> **Tip:** Khi dùng trong trang sản phẩm WooCommerce, `product_id` tự động được nhận diện.

---

## 5. CSS Customization

Toàn bộ màu sắc được định nghĩa qua **CSS Custom Properties** trên `:root`.
Bạn có thể override trong theme hoặc bộ CSS riêng.

```css
:root {
    --mcwr-primary:    #ee4d2d;  /* Màu chủ đạo (nút, button, badge) */
    --mcwr-primary-lt: rgba(238,77,45,0.08); /* Màu nhạt của primary */
    --mcwr-accent:     #f59e0b;  /* Màu sao đánh giá */
    --mcwr-border:     #e2e8f0;  /* Màu viền card */
}
```

> **Tip:** Bạn có thể cấu hình màu sắc trong **ReviewKit → Cài đặt → Hiển thị**
> mà không cần chỉnh sửa CSS.

---

## Ghi chú kỹ thuật

- Plugin tự động `wp_enqueue_style('dashicons')` trên frontend.
  Nếu bạn override `mcwr_icons` sang icon set khác, bạn cần tự enqueue font/CSS tương ứng.
- Tất cả output của `mcwr_icon()` đều được escape an toàn trong context HTML.
