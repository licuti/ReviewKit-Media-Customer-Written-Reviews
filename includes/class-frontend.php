<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCWR_Frontend — Orchestrator chính
 *
 * Chịu trách nhiệm:
 * 1. Đăng ký tất cả WordPress hooks.
 * 2. Enqueue CSS/JS frontend.
 * 3. Khai báo shortcodes.
 * 4. Điểm vào của Tab WooCommerce.
 *
 * Logic render được tách sang:  @see MCWR_Trait_Renderer
 * Logic AJAX handlers tách sang: @see MCWR_Trait_Ajax_Handlers
 */
class MCWR_Frontend {

    use MCWR_Trait_Renderer;
    use MCWR_Trait_Ajax_Handlers;

    public function __construct() {
        // ── Tab WooCommerce & Assets ───────────────────────────
        add_filter( 'woocommerce_product_tabs', array( $this, 'customize_review_tab' ) );
        add_action( 'wp_enqueue_scripts',       array( $this, 'enqueue_assets' ) );

        // ── Form Submit & Admin Reply ──────────────────────────
        add_action( 'admin_post_submit_custom_review',       array( $this, 'handle_review_submission' ) );
        add_action( 'admin_post_nopriv_submit_custom_review', array( $this, 'handle_review_submission' ) );
        add_action( 'admin_post_mcwr_admin_reply_submission', array( $this, 'handle_admin_reply_submission' ) );

        // ── AJAX Filter & Vote ─────────────────────────────────
        add_action( 'wp_ajax_mcwr_filter_reviews',        array( $this, 'handle_filter_reviews' ) );
        add_action( 'wp_ajax_nopriv_mcwr_filter_reviews', array( $this, 'handle_filter_reviews' ) );
        add_action( 'wp_ajax_mcwr_vote_review',           array( $this, 'handle_vote_review' ) );
        add_action( 'wp_ajax_nopriv_mcwr_vote_review',    array( $this, 'handle_vote_review' ) );

        // ── Shortcodes ─────────────────────────────────────────
        add_shortcode( 'mcwr_reviews',        array( $this, 'shortcode_reviews' ) );
        add_shortcode( 'mcwr_review_form',    array( $this, 'shortcode_review_form' ) );
        add_shortcode( 'mcwr_review_list',    array( $this, 'shortcode_review_list' ) );
        add_shortcode( 'mcwr_review_summary', array( $this, 'shortcode_review_summary' ) );

        // ── Cache Management ───────────────────────────────────
        add_action( 'edit_comment',              array( $this, 'clear_ratings_cache' ) );
        add_action( 'deleted_comment',           array( $this, 'clear_ratings_cache' ) );
        add_action( 'transition_comment_status', array( $this, 'clear_ratings_cache_status' ), 10, 3 );
    }

    // ============================================================
    // CACHE MANAGEMENT
    // ============================================================

    /**
     * Xóa cache thống kê sao khi review thay đổi nội dung hoặc bị xóa.
     *
     * @param int $comment_id
     */
    public function clear_ratings_cache( $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( $comment && $comment->comment_type === 'review' ) {
            delete_transient( 'mcwr_rating_counts_' . $comment->comment_post_ID );
        }
    }

    /**
     * Xóa cache khi trạng thái review thay đổi (duyệt / từ chối).
     *
     * @param string     $new_status
     * @param string     $old_status
     * @param WP_Comment $comment
     */
    public function clear_ratings_cache_status( $new_status, $old_status, $comment ) {
        if ( $comment->comment_type === 'review' ) {
            delete_transient( 'mcwr_rating_counts_' . $comment->comment_post_ID );
        }
    }

    // ============================================================
    // WooCommerce TAB CALLBACK
    // ============================================================

    /**
     * Thay callback mặc định của tab "Reviews" WooCommerce bằng render của plugin.
     *
     * @param array $tabs
     * @return array
     */
    public function customize_review_tab( $tabs ) {
        $tabs['reviews']['title']    = __( 'Đánh giá từ khách hàng', 'review-kit' );
        $tabs['reviews']['callback'] = array( $this, 'render_review_content' );
        return $tabs;
    }

    /**
     * Điểm vào của Tab WooCommerce (callback trực tiếp của WC).
     */
    public function render_review_content() {
        global $product;
        if ( ! $product ) $product = wc_get_product( get_the_ID() );
        if ( ! $product ) return;

        // Thông báo đánh giá đang chờ duyệt
        if ( isset( $_GET['review_pending'] ) && $_GET['review_pending'] == '1' ) {
            echo '<div class="mcwr-notice-success">';
            echo __( 'Cảm ơn bạn! Đánh giá của bạn đã được gửi thành công và đang chờ quản trị viên xét duyệt.', 'review-kit' );
            echo '</div>';
        }

        echo $this->render_full_layout( $product->get_id() );
    }

    // ============================================================
    // SHORTCODES
    // ============================================================

    /**
     * [mcwr_reviews product_id="123"]
     * Toàn bộ layout 2 cột (danh sách + thống kê + form).
     *
     * @param array $atts
     * @return string HTML
     */
    public function shortcode_reviews( $atts ) {
        $atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'mcwr_reviews' );
        $product_id = $this->get_product_id_from_atts( $atts );
        if ( ! $product_id ) return '<p class="mcwr-error">' . __( 'Vui lòng cung cấp product_id hợp lệ.', 'review-kit' ) . '</p>';

        $this->ensure_assets_loaded();

        $product = wc_get_product( $product_id );
        if ( ! $product ) return '';

        return $this->render_full_layout( $product->get_id() );
    }

    /**
     * [mcwr_review_form product_id="123"]
     * Chỉ hiển thị form gửi đánh giá.
     *
     * @param array $atts
     * @return string HTML
     */
    public function shortcode_review_form( $atts ) {
        $atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'mcwr_review_form' );
        $product_id = $this->get_product_id_from_atts( $atts );
        if ( ! $product_id ) return '<p class="mcwr-error">' . __( 'Vui lòng cung cấp product_id hợp lệ.', 'review-kit' ) . '</p>';

        $this->ensure_assets_loaded();

        $current_product_id = $product_id;
        $template_path = defined( 'MCWR_PLUGIN_DIR' )
            ? MCWR_PLUGIN_DIR . 'templates/review-form.php'
            : dirname( __DIR__ ) . '/templates/review-form.php';

        ob_start();
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
        return ob_get_clean();
    }

    /**
     * [mcwr_review_list product_id="123"]
     * Chỉ danh sách đánh giá + toolbar lọc.
     *
     * @param array $atts
     * @return string HTML
     */
    public function shortcode_review_list( $atts ) {
        $atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'mcwr_review_list' );
        $product_id = $this->get_product_id_from_atts( $atts );
        if ( ! $product_id ) return '<p class="mcwr-error">' . __( 'Vui lòng cung cấp product_id hợp lệ.', 'review-kit' ) . '</p>';

        $this->ensure_assets_loaded();

        $product = wc_get_product( $product_id );
        if ( ! $product ) return '';

        return $this->render_review_list_html( $product );
    }

    /**
     * [mcwr_review_summary product_id="123"]
     * Chỉ khối thống kê điểm số & progress bars.
     *
     * @param array $atts
     * @return string HTML
     */
    public function shortcode_review_summary( $atts ) {
        $atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'mcwr_review_summary' );
        $product_id = $this->get_product_id_from_atts( $atts );
        if ( ! $product_id ) return '<p class="mcwr-error">' . __( 'Vui lòng cung cấp product_id hợp lệ.', 'review-kit' ) . '</p>';

        $this->ensure_assets_loaded();

        $product = wc_get_product( $product_id );
        if ( ! $product ) return '';

        return $this->render_review_summary_html( $product );
    }

    // ============================================================
    // ENQUEUE ASSETS
    // ============================================================

    /**
     * Enqueue CSS & JS frontend kèm localize script.
     * Hook: wp_enqueue_scripts
     */
    public function enqueue_assets() {
        $plugin_url = defined( 'MCWR_PLUGIN_URL' ) ? MCWR_PLUGIN_URL : plugin_dir_url( dirname( __FILE__ ) );
        $version    = defined( 'MCWR_VERSION' )    ? MCWR_VERSION    : '1.1.0';

        // Dashicons — cần thiết cho icon system (mcwr_icon helper).
        // WordPress chỉ tự load dashicons cho admin; frontend cần enqueue thủ công.
        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style(  'mcwr-fancybox-style',  $plugin_url . 'assets/libs/fancybox/fancybox.css',     array(),                           '4.0' );
        wp_enqueue_script( 'mcwr-fancybox-script', $plugin_url . 'assets/libs/fancybox/fancybox.umd.js', array( 'jquery' ),                  '4.0', true );
        wp_enqueue_style(  'mcwr-style',            $plugin_url . 'assets/css/style.css',                  array( 'mcwr-fancybox-style' ),     $version );
        wp_enqueue_script( 'mcwr-script',           $plugin_url . 'assets/js/script.js',                  array( 'jquery', 'mcwr-fancybox-script' ), $version, true );

        // ── Custom Color Tokens ────────────────────────────────
        $primary_color = get_option('mcwr_primary_color', '#ee4d2d');
        $stars_color   = get_option('mcwr_stars_color',   '#f59e0b');
        $border_color  = get_option('mcwr_border_color',  '#e2e8f0');
        $primary_lt    = $this->hex_to_rgba_helper( $primary_color, 0.08 );

        $custom_css = ":root {
            --mcwr-primary: {$primary_color};
            --mcwr-primary-dk: {$primary_color};
            --mcwr-primary-lt: {$primary_lt};
            --mcwr-accent: {$stars_color};
            --mcwr-border: {$border_color};
        }";
        wp_add_inline_style( 'mcwr-style', $custom_css );

        // ── Localize Script ────────────────────────────────────
        wp_localize_script( 'mcwr-script', 'mcwr_vars', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'mcwr_ajax_nonce' ),
            'product_id'    => get_the_ID(),
            'max_files'     => intval( get_option( 'mcwr_max_images', 5 ) ),
            'max_size_mb'   => intval( get_option( 'mcwr_max_file_size', 2 ) ),
            'allow_upload'  => get_option( 'mcwr_enable_uploads', 0 ) ? 'yes' : 'no',
            'enable_video'  => get_option( 'mcwr_enable_video_upload', 0 ) ? 'yes' : 'no',
            'max_video_mb'  => intval( get_option( 'mcwr_max_video_size', 10 ) ),
            'allowed_video' => get_option( 'mcwr_allowed_video_types', 'mp4,webm,mov' ),
            'lb_layout'     => get_option('mcwr_lightbox_layout', 'modern'),
            'lb_theme'      => get_option('mcwr_lightbox_theme',  'dark'),
            'lb_toolbar'    => get_option('mcwr_lightbox_toolbar', 1) ? 'yes' : 'no',
            'i18n'          => array(
                'upload_locked'      => __( 'Tính năng upload đang tạm khóa.', 'review-kit' ),
                'max_files_reached'  => __( 'Đã đủ số lượng tệp tối đa (%d tệp).', 'review-kit' ),
                'extra_images_limit' => __( 'Chỉ lấy thêm %d tệp.', 'review-kit' ),
                'file_too_large'     => __( 'Tệp "%s" quá lớn. Tối đa %dMB.', 'review-kit' ),
                'connection_error'   => __( 'Lỗi kết nối Server.', 'review-kit' ),
                'voted_already'      => __( 'Bạn đã bình chọn rồi.', 'review-kit' ),
                'helpful'            => __( 'Hữu ích', 'review-kit' ),
                'loading'            => __( 'Đang tải...', 'review-kit' ),
                'load_more'          => __( 'Tải thêm đánh giá', 'review-kit' ),
                'no_results'         => __( 'Không tìm thấy kết quả.', 'review-kit' ),
            ),
        ) );
    }

} // end class MCWR_Frontend