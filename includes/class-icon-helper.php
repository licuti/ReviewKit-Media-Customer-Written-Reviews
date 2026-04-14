<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCWR Icon Helper
 *
 * Cung cấp hệ thống icon có thể override thông qua filter `mcwr_icons`.
 * Mặc định dùng WordPress Dashicons (được enqueue tự động trên frontend).
 *
 * ── Cách dùng trong code plugin ─────────────────────────────────────────
 *
 *   echo mcwr_icon( 'thumbs-up' );   // <i class="dashicons dashicons-thumbs-up"></i>
 *   echo mcwr_icon( 'star' );        // <i class="dashicons dashicons-star-filled"></i>
 *
 * ── Developer: Override toàn bộ icon set ────────────────────────────────
 *
 *   // Ví dụ: chuyển sang Font Awesome 6
 *   add_filter( 'mcwr_icons', function( array $icons ): array {
 *       $icons['thumbs-up'] = '<i class="fa-regular fa-thumbs-up"></i>';
 *       $icons['flag']      = '<i class="fa-regular fa-flag"></i>';
 *       $icons['star']      = '<i class="fa-solid fa-star"></i>';
 *       $icons['play']      = '<i class="fa-solid fa-play"></i>';
 *       $icons['verified']  = '<i class="fa-solid fa-circle-check"></i>';
 *       $icons['admin']     = '<i class="fa-solid fa-user-shield"></i>';
 *       $icons['chat']      = '<i class="fa-regular fa-comment"></i>';
 *       return $icons;
 *   } );
 *
 * ── Developer: Override một icon đơn lẻ ─────────────────────────────────
 *
 *   add_filter( 'mcwr_icons', function( array $icons ): array {
 *       $icons['thumbs-up'] = '<svg ...>...</svg>';  // SVG tùy chỉnh
 *       return $icons;
 *   } );
 *
 * ── Developer: Override HTML theo từng icon name ─────────────────────────
 *
 *   add_filter( 'mcwr_icon_html', function( string $html, string $name ): string {
 *       if ( $name === 'star' ) {
 *           return '⭐'; // Chuyển sang emoji
 *       }
 *       return $html;
 *   }, 10, 2 );
 *
 * @package ReviewKit
 * @since   1.2.0
 */

/**
 * Trả về HTML icon theo tên.
 *
 * @param  string $name  Tên icon. Xem danh sách trong mcwr_get_icons().
 * @param  string $class CSS class bổ sung (tùy chọn).
 * @return string        HTML của icon. Trả về chuỗi rỗng nếu không tìm thấy.
 */
function mcwr_icon( string $name, string $class = '' ): string {
    $icons = mcwr_get_icons();
    $html  = $icons[ $name ] ?? '';

    if ( '' === $html ) {
        return '';
    }

    // Cho phép override HTML cuối cùng theo từng tên icon.
    $html = apply_filters( 'mcwr_icon_html', $html, $name );

    // Thêm class bổ sung nếu có (inject vào thẻ đầu tiên).
    if ( $class && false !== strpos( $html, '<' ) ) {
        $class = esc_attr( $class );
        // Inject vào attribute class của thẻ đầu tiên.
        $html  = preg_replace( '/class="([^"]*)"/', 'class="$1 ' . $class . '"', $html, 1 );
    }

    return $html;
}

/**
 * Trả về toàn bộ danh sách icon sau khi đã áp dụng filter `mcwr_icons`.
 *
 * Danh sách mặc định dùng WordPress Dashicons.
 * Developer có thể override một phần hoặc toàn bộ qua filter này.
 *
 * @return array<string, string>  [ 'icon-name' => '<html>' ]
 */
function mcwr_get_icons(): array {
    /**
     * Filter toàn bộ icon set của ReviewKit.
     *
     * @since 1.2.0
     * @param array<string, string> $icons Mảng [ tên-icon => HTML-icon ].
     *
     * @example Chuyển sang Font Awesome 6:
     *   add_filter( 'mcwr_icons', function( $icons ) {
     *       $icons['thumbs-up'] = '<i class="fa-regular fa-thumbs-up"></i>';
     *       return $icons;
     *   } );
     */
    return apply_filters( 'mcwr_icons', array(
        // Hành động trong review card
        'thumbs-up'    => '<i class="dashicons dashicons-thumbs-up" aria-hidden="true"></i>',
        'flag'         => '<i class="dashicons dashicons-flag" aria-hidden="true"></i>',
        'chat'         => '<i class="dashicons dashicons-format-chat" aria-hidden="true"></i>',

        // Media
        'play'         => '<i class="dashicons dashicons-controls-play" aria-hidden="true"></i>',
        'video'        => '<i class="dashicons dashicons-video-alt3" aria-hidden="true"></i>',

        // Badge & trạng thái
        'verified'     => '<i class="dashicons dashicons-yes" aria-hidden="true"></i>',
        'admin'        => '<i class="dashicons dashicons-admin-users" aria-hidden="true"></i>',

        // Đánh giá sao
        'star'         => '<i class="dashicons dashicons-star-filled" aria-hidden="true"></i>',
        'star-empty'   => '<i class="dashicons dashicons-star-empty" aria-hidden="true"></i>',
    ) );
}
