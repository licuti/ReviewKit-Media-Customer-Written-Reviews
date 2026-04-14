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
$mcwr_options = array(
    'mcwr_moderation_mode',
    'mcwr_enable_voting',
    'mcwr_require_login',
    'mcwr_enable_uploads',
    'mcwr_max_images',
    'mcwr_max_file_size',
    'mcwr_enable_video_upload',
    'mcwr_max_video_size',
    'mcwr_allowed_video_types',
    'mcwr_per_page',
    'mcwr_pagination_style',
    'mcwr_primary_color',
    'mcwr_stars_color',
    'mcwr_border_color',
    'mcwr_verified_text',
    'mcwr_verified_color',
    'mcwr_lightbox_layout',
    'mcwr_lightbox_toolbar',
    'mcwr_lightbox_theme',
    'mcwr_blacklist_keywords',
    'mcwr_reminder_enabled',
    'mcwr_reminder_delay',
    'mcwr_reminder_subject',
    'mcwr_reminder_body',
    'mcwr_delete_media_with_review',
    'mcwr_installed_version',
);

foreach ( $mcwr_options as $option_key ) {
    delete_option( $option_key );
}

// Nếu là multisite — xóa trên tất cả các site con
if ( is_multisite() ) {
    $sites = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        foreach ( $mcwr_options as $option_key ) {
            delete_option( $option_key );
        }
        restore_current_blog();
    }
}
