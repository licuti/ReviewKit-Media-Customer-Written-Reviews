<?php
/**
 * Plugin Name:       ReviewKit: Media & Customer Written Reviews
 * Plugin URI:        https://github.com/your-username/my-custom-woo-reviews
 * Description:       Upgrade your WooCommerce store with rich media reviews: photo & video uploads, verified buyer badges, star statistics, and a powerful admin toolkit.
 * Version:           1.1.0
 * Author:            Linh Nguyen
 * Author URI:        https://yoursite.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-custom-woo-reviews
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
define( 'MCWR_VERSION',     '1.1.0' );
define( 'MCWR_PLUGIN_FILE', __FILE__ );
define( 'MCWR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'MCWR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ============================================================
// THÊM LINK "Settings" TRONG DANH SÁCH PLUGIN
// ============================================================
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    function( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=mcwr-settings' ) ),
            __( 'Settings', 'my-custom-woo-reviews' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }
);

// ============================================================
// LOAD INCLUDES
// ============================================================
require_once MCWR_PLUGIN_DIR . 'includes/class-icon-helper.php';   // Icon system (load trước traits)
require_once MCWR_PLUGIN_DIR . 'includes/trait-review-renderer.php';
require_once MCWR_PLUGIN_DIR . 'includes/trait-ajax-handlers.php';
require_once MCWR_PLUGIN_DIR . 'includes/class-frontend.php';
require_once MCWR_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once MCWR_PLUGIN_DIR . 'includes/class-pro-features.php';
require_once MCWR_PLUGIN_DIR . 'includes/class-admin-pro.php';
require_once MCWR_PLUGIN_DIR . 'includes/class-admin-review-editor.php';

// ============================================================
// ACTIVATION / DEACTIVATION
// ============================================================
register_activation_hook( __FILE__, 'mcwr_activate' );
register_deactivation_hook( __FILE__, 'mcwr_deactivate' );

/**
 * Khi kich hoat plugin: ghi default options neu chua ton tai.
 */
function mcwr_activate() {
    $defaults = array(
        'mcwr_moderation_mode'          => '1',
        'mcwr_enable_voting'            => '1',
        'mcwr_require_login'            => '0',
        'mcwr_enable_uploads'           => '1',
        'mcwr_max_images'               => '5',
        'mcwr_max_file_size'            => '5',
        'mcwr_enable_video_upload'      => '0',
        'mcwr_max_video_size'           => '10',
        'mcwr_allowed_video_types'      => 'mp4,webm,mov',
        'mcwr_per_page'                 => '5',
        'mcwr_pagination_style'         => 'numbered_ajax',
        'mcwr_primary_color'            => '#ee4d2d',
        'mcwr_stars_color'              => '#f59e0b',
        'mcwr_border_color'             => '#e2e8f0',
        'mcwr_lightbox_layout'          => 'modern',
        'mcwr_lightbox_theme'           => 'dark',
        'mcwr_lightbox_toolbar'         => '1',
        'mcwr_delete_media_with_review' => '0',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    update_option( 'mcwr_installed_version', MCWR_VERSION );
}

/**
 * Khi tat plugin: khong xoa data (dung uninstall.php neu muon xoa).
 */
function mcwr_deactivate() {
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
        'my-custom-woo-reviews',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
} );

// ============================================================
// INIT CLASSES
// ============================================================
function mcwr_init() {
    new MCWR_Frontend();
    new MCWR_Pro_Features();
}

function mcwr_admin_init() {
    new MCWR_Admin_Settings();
    new MCWR_Admin_Pro();
    new MCWR_Admin_Review_Editor();
}

add_action( 'plugins_loaded', 'mcwr_init' );
add_action( 'plugins_loaded', 'mcwr_admin_init' );