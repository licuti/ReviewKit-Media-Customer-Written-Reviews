<?php
/**
 * Uninstall Plugin: ReviewKit: Media & Customer Written Reviews
 *
 * File này chạy khi người dùng chọn "Xóa" plugin trong WordPress Admin.
 * Sẽ xóa toàn bộ options của plugin khỏi database.
 *
 * LƯU Ý: Không xóa dữ liệu commentmeta (review_image_ids, rating...)
 * vì đây là dữ liệu người dùng quan trọng, không nên xóa mà không có
 * sự đồng ý rõ ràng. Chỉ xóa khi admin chủ động bật tùy chọn.
 */

// Chặn truy cập trực tiếp — bắt buộc theo WP.org standards
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Danh sách tất cả options của plugin cần xóa
$reviewkit_options = array(
    'reviewkit_moderation_mode',
    'reviewkit_enable_voting',
    'reviewkit_require_login',
    'reviewkit_enable_uploads',
    'reviewkit_max_images',
    'reviewkit_max_file_size',
    'reviewkit_enable_video_upload',
    'reviewkit_max_video_size',
    'reviewkit_allowed_video_types',
    'reviewkit_per_page',
    'reviewkit_pagination_style',
    'reviewkit_primary_color',
    'reviewkit_stars_color',
    'reviewkit_border_color',
    'reviewkit_verified_text',
    'reviewkit_verified_color',
    'reviewkit_lightbox_layout',
    'reviewkit_lightbox_toolbar',
    'reviewkit_lightbox_theme',
    'reviewkit_blacklist_keywords',
    'reviewkit_reminder_enabled',
    'reviewkit_reminder_delay',
    'reviewkit_reminder_subject',
    'reviewkit_reminder_body',
    'reviewkit_delete_media_with_review',
    'reviewkit_installed_version',
);

foreach ( $reviewkit_options as $option_key ) {
    delete_option( $option_key );
}

// Nếu là multisite — xóa trên tất cả các site con
if ( is_multisite() ) {
    $sites = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        foreach ( $reviewkit_options as $option_key ) {
            delete_option( $option_key );
        }
        restore_current_blog();
    }
}
