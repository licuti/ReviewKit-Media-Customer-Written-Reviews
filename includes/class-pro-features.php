<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCWR_Pro_Features
 * Chứa: Blacklist Keywords, Report Review, Import/Export, Email Reminders
 */
class MCWR_Pro_Features {

    public function __construct() {
        // --- Blacklist Keywords ---
        add_filter( 'preprocess_comment', array( $this, 'check_blacklist_keywords' ) );

        // --- Report Review (AJAX) ---
        add_action( 'wp_ajax_mcwr_report_review',        array( $this, 'handle_report_review' ) );
        add_action( 'wp_ajax_nopriv_mcwr_report_review', array( $this, 'handle_report_review' ) );

        // --- Export/Import ---
        add_action( 'admin_post_mcwr_export_reviews', array( $this, 'handle_export_reviews' ) );
        add_action( 'admin_post_mcwr_import_reviews', array( $this, 'handle_import_reviews' ) );

        // --- Email Reminders (Action Scheduler) ---
        add_action( 'woocommerce_order_status_completed', array( $this, 'schedule_reminder_on_complete' ) );
        add_action( 'mcwr_send_single_reminder_action', array( $this, 'send_single_reminder' ), 10, 5 );

        // --- Cleanup Media ---
        add_action( 'wp_delete_comment',  array( $this, 'cleanup_media_on_comment_delete' ) );

    }

    // =========================================================
    // 1. BLACKLIST KEYWORDS
    // =========================================================

    /**
     * Lọc nội dung bình luận, từ chối nếu chứa từ cấm.
     * Hook: preprocess_comment
     */
    public function check_blacklist_keywords( $commentdata ) {
        // Chỉ lọc đối với comment type là 'review' hoặc 'comment'
        if ( ! in_array( $commentdata['comment_type'], array( 'review', 'comment', '' ) ) ) {
            return $commentdata;
        }

        $blacklist_raw = get_option( 'mcwr_blacklist_keywords', '' );
        if ( empty( $blacklist_raw ) ) return $commentdata;

        // Tách từ khóa và làm sạch
        $keywords = explode( ',', $blacklist_raw );
        $keywords = array_map( 'trim', $keywords );
        $keywords = array_filter( $keywords );

        if ( empty( $keywords ) ) return $commentdata;

        $content = mb_strtolower( $commentdata['comment_content'], 'UTF-8' );

        foreach ( $keywords as $keyword ) {
            $keyword_lower = mb_strtolower( $keyword, 'UTF-8' );
            if ( ! empty( $keyword_lower ) && strpos( $content, $keyword_lower ) !== false ) {
                wp_die(
                    sprintf(
                        __( 'Đánh giá của bạn chứa từ ngữ không được phép: "%s". Vui lòng chỉnh sửa và thử lại.', 'my-custom-woo-reviews' ),
                        esc_html( $keyword )
                    ),
                    __( 'Nội dung không hợp lệ', 'my-custom-woo-reviews' ),
                    array( 'response' => 400, 'back_link' => true )
                );
            }
        }
        return $commentdata;
    }

    // =========================================================
    // 2. REPORT REVIEW
    // =========================================================

    /**
     * AJAX handler: Nhận báo cáo vi phạm.
     */
    public function handle_report_review() {
        check_ajax_referer( 'mcwr_ajax_nonce', 'nonce' );
        
        $comment_id = intval( $_POST['comment_id'] ?? 0 );
        $reason     = sanitize_text_field( $_POST['reason'] ?? '' );

        if ( ! $comment_id ) {
            wp_send_json_error( array( 'message' => __( 'Không hợp lệ.', 'my-custom-woo-reviews' ) ) );
        }

        // Tránh báo cáo trùng lặp từ cùng một IP
        $cookie_key = 'mcwr_reported_' . $comment_id;
        if ( isset( $_COOKIE[ $cookie_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Bạn đã báo cáo đánh giá này rồi.', 'my-custom-woo-reviews' ) ) );
        }

        // Lưu số lần báo cáo và lý do mới nhất
        $count = intval( get_comment_meta( $comment_id, 'mcwr_report_count', true ) );
        update_comment_meta( $comment_id, 'mcwr_report_count', $count + 1 );
        update_comment_meta( $comment_id, 'mcwr_report_reason', $reason );

        // Gửi email cho Admin nếu vượt ngưỡng
        $threshold = intval( get_option( 'mcwr_report_threshold', 3 ) );
        if ( ( $count + 1 ) >= $threshold ) {
            $comment     = get_comment( $comment_id );
            $product     = get_post( $comment->comment_post_ID );
            $admin_email = get_option( 'admin_email' );

            $subject = sprintf( __( '[MCWR] Cảnh báo: Đánh giá #%d bị báo cáo %d lần', 'my-custom-woo-reviews' ), $comment_id, $count + 1 );
            $body    = sprintf(
                __( "Đánh giá của \"%s\" trên sản phẩm \"%s\" đã bị báo cáo %d lần.\n\nLý do mới nhất: %s\n\nXem tại: %s", 'my-custom-woo-reviews' ),
                esc_html( $comment->comment_author ),
                esc_html( $product->post_title ?? 'Không xác định' ),
                $count + 1,
                $reason,
                admin_url( 'comment.php?action=editcomment&c=' . $comment_id )
            );

            wp_mail( $admin_email, $subject, $body );
        }

        // Đặt cookie 30 ngày
        setcookie( $cookie_key, '1', time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );

        wp_send_json_success( array( 'message' => __( 'Đã ghi nhận báo cáo của bạn. Cảm ơn!', 'my-custom-woo-reviews' ) ) );
    }

    // =========================================================
    // 3. EXPORT REVIEWS (CSV)
    // =========================================================

    /**
     * Xuất tất cả đánh giá ra file CSV.
     */
    public function handle_export_reviews() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Không có quyền.', 'my-custom-woo-reviews' ) );
        check_admin_referer( 'mcwr_export_nonce' );

        $product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

        $args = array(
            'status'  => 'approve',
            'type'    => 'review',
            'parent'  => 0,
            'number'  => 0, // Lấy tất cả
        );
        if ( $product_id ) $args['post_id'] = $product_id;

        $comments = get_comments( $args );

        // Headers CSV
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="mcwr-reviews-' . date('Y-m-d') . '.csv"' );

        $output = fopen( 'php://output', 'w' );
        // BOM UTF-8 để Excel đọc đúng tiếng Việt
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

        // Tiêu đề cột
        fputcsv( $output, array( 'ID', 'product_id', 'author', 'email', 'rating', 'content', 'date', 'status', 'verified' ) );

        foreach ( $comments as $comment ) {
            $rating   = get_comment_meta( $comment->comment_ID, 'rating', true );
            $verified = get_comment_meta( $comment->comment_ID, 'verified', true ) ? '1' : '0';
            fputcsv( $output, array(
                $comment->comment_ID,
                $comment->comment_post_ID,
                $comment->comment_author,
                $comment->comment_author_email,
                $rating,
                $comment->comment_content,
                $comment->comment_date,
                $comment->comment_approved,
                $verified,
            ) );
        }
        fclose( $output );
        exit;
    }

    // =========================================================
    // 4. IMPORT REVIEWS (CSV)
    // =========================================================

    /**
     * Nhập đánh giá từ file CSV upload.
     * Cột CSV kỳ vọng: product_id, author, email, rating, content, date
     */
    public function handle_import_reviews() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Không có quyền.', 'my-custom-woo-reviews' ) );
        check_admin_referer( 'mcwr_import_nonce' );

        if ( ! isset( $_FILES['mcwr_import_file'] ) || $_FILES['mcwr_import_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'mcwr-settings', 'tab' => 'tools', 'import_error' => 'no_file' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $file = $_FILES['mcwr_import_file']['tmp_name'];
        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'mcwr-settings', 'tab' => 'tools', 'import_error' => 'read_fail' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Bỏ qua dòng tiêu đề
        $header = fgetcsv( $handle );
        $count  = 0;
        $errors = 0;

        $moderation = get_option( 'mcwr_moderation_mode', 0 );

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            // Hỗ trợ cột: product_id, author, email, rating, content, date
            if ( count( $row ) < 5 ) { $errors++; continue; }

            // Nếu CSV có 9 cột (export của plugin) → dùng đúng cột
            // Nếu CSV có ít hơn → sử dụng thứ tự đơn giản
            if ( count( $row ) >= 9 ) {
                // Format export plugin: ID, product_id, author, email, rating, content, date, status, verified
                $post_id  = intval( $row[1] );
                $author   = sanitize_text_field( $row[2] );
                $email    = sanitize_email( $row[3] );
                $rating   = intval( $row[4] );
                $content  = sanitize_textarea_field( $row[5] );
                $date     = sanitize_text_field( $row[6] );
                $verified = intval( $row[8] );
            } else {
                // Format đơn giản: product_id, author, email, rating, content, [date]
                $post_id  = intval( $row[0] );
                $author   = sanitize_text_field( $row[1] );
                $email    = sanitize_email( $row[2] );
                $rating   = intval( $row[3] );
                $content  = sanitize_textarea_field( $row[4] );
                $date     = isset( $row[5] ) ? sanitize_text_field( $row[5] ) : current_time( 'mysql' );
                $verified = 0;
            }

            if ( ! $post_id || ! $rating || empty( $content ) ) { $errors++; continue; }
            if ( $rating < 1 || $rating > 5 ) { $errors++; continue; }

            $comment_id = wp_insert_comment( array(
                'comment_post_ID'      => $post_id,
                'comment_author'       => $author ?: __( 'Khách hàng', 'my-custom-woo-reviews' ),
                'comment_author_email' => $email,
                'comment_content'      => $content,
                'comment_type'         => 'review',
                'comment_approved'     => intval( $moderation ),
                'comment_date'         => $date,
                'comment_date_gmt'     => get_gmt_from_date( $date ),
            ) );

            if ( $comment_id && ! is_wp_error( $comment_id ) ) {
                add_comment_meta( $comment_id, 'rating', $rating );
                if ( $verified ) add_comment_meta( $comment_id, 'verified', 1 );
                $count++;
            } else {
                $errors++;
            }
        }
        fclose( $handle );

        wp_safe_redirect( add_query_arg( array(
            'page'           => 'mcwr-settings',
            'tab'            => 'tools',
            'import_success' => $count,
            'import_errors'  => $errors,
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // =========================================================
    // 5. EMAIL REMINDERS (Action Scheduler)
    // =========================================================

    /**
     * Khi đơn hàng hoàn thành, lên lịch gửi email nhắc đánh giá qua Action Scheduler.
     */
    public function schedule_reminder_on_complete( $order_id ) {
        if ( ! get_option( 'mcwr_reminder_enabled', 0 ) ) return;
        if ( ! function_exists( 'as_schedule_single_action' ) ) return; // Yêu cầu WC Action Scheduler

        $days_delay = intval( get_option( 'mcwr_reminder_days', 3 ) );
        $send_after = time() + ( $days_delay * DAY_IN_SECONDS );

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $customer_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();

        if ( empty( $customer_email ) ) return;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product    = wc_get_product( $product_id );
            if ( ! $product ) continue;

            // Xếp hàng gửi 1 email cho 1 sản phẩm
            $args = array(
                'order_id'       => $order_id,
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                'product_id'     => $product_id,
                'product_name'   => $product->get_name()
            );

            // Kiểm tra xem action này với arguments này đã được xếp lịch chưa
            if ( false === as_next_scheduled_action( 'mcwr_send_single_reminder_action', $args ) ) {
                as_schedule_single_action( $send_after, 'mcwr_send_single_reminder_action', $args );
            }
        }
    }

    /**
     * Hàm callback được Action Scheduler gọi ra để tiến hành gửi email chuẩn xác.
     */
    public function send_single_reminder( $order_id, $customer_name, $customer_email, $product_id, $product_name ) {
        if ( ! get_option( 'mcwr_reminder_enabled', 0 ) ) return;

        $subject_template = get_option( 'mcwr_reminder_subject', __( '[{site_name}] Bạn có hài lòng với đơn hàng #{order_id}?', 'my-custom-woo-reviews' ) );
        $body_template    = get_option( 'mcwr_reminder_body', $this->get_default_reminder_body() );
        $from_name        = get_option( 'mcwr_reminder_from_name', get_bloginfo( 'name' ) );
        $from_email       = get_option( 'mcwr_reminder_from_email', get_option( 'admin_email' ) );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $product_url = get_permalink( $product_id );

        // Thay thế placeholders
        $placeholders = array(
            '{site_name}'    => get_bloginfo( 'name' ),
            '{order_id}'     => $order_id,
            '{customer_name}'=> $customer_name,
            '{product_name}' => $product_name,
            '{product_url}'  => '<a href="' . esc_url( $product_url ) . '">' . esc_html( $product_name ) . '</a>',
            '{review_link}'  => '<a href="' . esc_url( $product_url ) . '#tab-reviews">' . __( 'Để lại đánh giá', 'my-custom-woo-reviews' ) . '</a>',
        );

        $subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject_template );
        $body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body_template );

        wp_mail( $customer_email, $subject, $body, $headers );
    }

    /**
     * Nội dung email mặc định.
     */
    private function get_default_reminder_body() {
        return '
<p>Xin chào <strong>{customer_name}</strong>,</p>
<p>Cảm ơn bạn đã mua <strong>{product_name}</strong> tại cửa hàng của chúng tôi!</p>
<p>Chúng tôi rất muốn biết ý kiến của bạn về sản phẩm này. Hãy dành một vài giây để chia sẻ cảm nhận của bạn — điều đó sẽ giúp ích rất nhiều cho những khách hàng khác.</p>
<p style="text-align:center;margin:25px 0;">
    <a href="{product_url}#tab-reviews" style="background:#ee4d2d;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">⭐ Viết đánh giá ngay</a>
</p>
<p>Trân trọng,<br><strong>{site_name}</strong></p>
';
    }

    /**
     * Tự động xóa file ảnh/video trong Media Library khi review bị xóa vĩnh viễn.
     * Hook: wp_delete_comment
     */
    public function cleanup_media_on_comment_delete( $comment_id ) {
        // Kiểm tra xem Option có được bật không
        if ( ! get_option( 'mcwr_delete_media_with_review', 0 ) ) return;

        // Lấy danh sách ID ảnh và video từ meta
        $image_ids = get_comment_meta( $comment_id, 'review_image_ids', true );
        $video_ids = get_comment_meta( $comment_id, 'review_video_ids', true );

        $all_ids = array();

        if ( ! empty( $image_ids ) ) {
            $all_ids = array_merge( $all_ids, explode( ',', $image_ids ) );
        }
        if ( ! empty( $video_ids ) ) {
            $all_ids = array_merge( $all_ids, explode( ',', $video_ids ) );
        }

        $all_ids = array_unique( array_filter( array_map( 'intval', $all_ids ) ) );

        if ( ! empty( $all_ids ) ) {
            foreach ( $all_ids as $attachment_id ) {
                // Xóa file vật lý và bài đăng attachment
                // Tham số thứ 2 là true để xóa vĩnh viễn không bỏ vào thùng rác
                wp_delete_attachment( $attachment_id, true );
            }
        }
    }
}
