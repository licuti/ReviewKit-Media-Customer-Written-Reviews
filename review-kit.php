<?php
/**
 * Plugin Name:       ReviewKit: Media & Customer Written Reviews
 * Plugin URI:        https://github.com/your-username/review-kit
 * Description:       Upgrade your WooCommerce store with rich media reviews: photo & video uploads, verified buyer badges, star statistics, and a powerful admin toolkit.
 * Version:           1.1.0
 * Author:            Linh Nguyen
 * Author URI:        https://yoursite.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       review-kit
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.5
 * WC requires at least: 7.0
 * WC tested up to:   8.9
 */


if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// CONSTANTS
// ============================================================
define( 'ReviewKit_VERSION',     '1.1.0' );
define( 'ReviewKit_PLUGIN_FILE', __FILE__ );
define( 'ReviewKit_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ReviewKit_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ============================================================
// THÊM LINK "Settings" TRONG DANH SÁCH PLUGIN
// ============================================================
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    function( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=reviewkit-settings' ) ),
            __( 'Settings', 'review-kit' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }
);

// ============================================================
// LOAD INCLUDES
// ============================================================
require_once ReviewKit_PLUGIN_DIR . 'includes/class-icon-helper.php';   // Icon system (load trước traits)
require_once ReviewKit_PLUGIN_DIR . 'includes/trait-review-renderer.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/trait-ajax-handlers.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/class-frontend.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/class-pro-features.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/class-admin-pro.php';
require_once ReviewKit_PLUGIN_DIR . 'includes/class-admin-review-editor.php';

// ============================================================
// ACTIVATION / DEACTIVATION
// ============================================================
register_activation_hook( __FILE__, 'reviewkit_activate' );
register_deactivation_hook( __FILE__, 'reviewkit_deactivate' );

/**
 * Khi kich hoat plugin: ghi default options neu chua ton tai.
 */
function reviewkit_activate() {
    $defaults = array(
        'reviewkit_moderation_mode'          => '1',
        'reviewkit_enable_voting'            => '1',
        'reviewkit_require_login'            => '0',
        'reviewkit_enable_uploads'           => '1',
        'reviewkit_max_images'               => '5',
        'reviewkit_max_file_size'            => '5',
        'reviewkit_enable_video_upload'      => '0',
        'reviewkit_max_video_size'           => '10',
        'reviewkit_allowed_video_types'      => 'mp4,webm,mov',
        'reviewkit_per_page'                 => '5',
        'reviewkit_pagination_style'         => 'numbered_ajax',
        'reviewkit_primary_color'            => '#ee4d2d',
        'reviewkit_stars_color'              => '#f59e0b',
        'reviewkit_border_color'             => '#e2e8f0',
        'reviewkit_lightbox_layout'          => 'modern',
        'reviewkit_lightbox_theme'           => 'dark',
        'reviewkit_lightbox_toolbar'         => '1',
        'reviewkit_delete_media_with_review' => '0',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    update_option( 'reviewkit_installed_version', ReviewKit_VERSION );
}

/**
 * Khi tat plugin: khong xoa data (dung uninstall.php neu muon xoa).
 */
function reviewkit_deactivate() {
    // Intentionally empty
}

// ============================================================
// WooCommerce HPOS Compatibility Declaration
// ============================================================
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// ============================================================
// LOAD TEXT DOMAIN
// ============================================================
add_action( 'init', function () {
    load_plugin_textdomain(
        'review-kit',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
} );

// ============================================================
// INIT CLASSES
// ============================================================
function reviewkit_init() {
    new ReviewKit_Frontend();
    new ReviewKit_Pro_Features();
}

function reviewkit_admin_init() {
    new ReviewKit_Admin_Settings();
    new ReviewKit_Admin_Pro();
    new ReviewKit_Admin_Review_Editor();
}

add_action( 'plugins_loaded', 'reviewkit_init' );
add_action( 'plugins_loaded', 'reviewkit_admin_init' );