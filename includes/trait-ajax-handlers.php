<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trait MCWR_Trait_Ajax_Handlers
 *
 * Chứa toàn bộ logic xử lý dữ liệu đầu vào:
 * - Xác minh người mua hàng
 * - Lọc & sắp xếp review qua AJAX
 * - Submit form đánh giá mới (POST + upload file)
 * - Admin phản hồi bình luận
 * - Vote "Hữu ích" (Like)
 *
 * Trait này được dùng bởi MCWR_Frontend.
 */
trait MCWR_Trait_Ajax_Handlers {

    // ============================================================
    // HELPER: XÁC MINH NGƯỜI MUA HÀNG
    // ============================================================

    /**
     * Kiểm tra khách hàng đã mua sản phẩm này chưa.
     *
     * @param string $email
     * @param int    $user_id
     * @param int    $product_id
     * @return bool
     */
    private function is_verified_buyer( $email, $user_id, $product_id ) {
        $is_verified = false;
        if ( function_exists('wc_customer_bought_product') ) {
            $is_verified = wc_customer_bought_product( $email, $user_id, $product_id );
        }

        // Fallback: quét thủ công theo Email & Đơn hàng
        if ( ! $is_verified ) {
            $customer_orders = wc_get_orders( array(
                'limit'    => -1,
                'customer' => $email,
                'status'   => array( 'completed', 'processing' ),
            ) );

            if ( $customer_orders ) {
                foreach ( $customer_orders as $order ) {
                    $items = $order->get_items();
                    foreach ( $items as $item ) {
                        if ( $item->get_product_id() == $product_id || $item->get_variation_id() == $product_id ) {
                            $is_verified = true;
                            break 2;
                        }
                    }
                }
            }
        }
        return $is_verified;
    }

    // ============================================================
    // AJAX FILTER & SORT
    // ============================================================

    /**
     * Xử lý AJAX lọc/sắp xếp danh sách review.
     * Hook: wp_ajax_mcwr_filter_reviews / wp_ajax_nopriv_mcwr_filter_reviews
     */
    public function handle_filter_reviews() {
        $product_id  = intval( $_POST['product_id'] );
        $filter_type = sanitize_text_field( $_POST['filter_type'] );
        $sort_type   = sanitize_text_field( $_POST['sort_type'] );

        $page             = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page         = get_option('mcwr_per_page', 5);
        $pagination_style = get_option('mcwr_pagination_style', 'numbered_ajax');
        $offset           = ($page - 1) * $per_page;

        // Không thể vừa lọc theo sao, vừa sort theo sao — fallback về newest
        if ( is_numeric( $filter_type ) && in_array($sort_type, ['rating_desc', 'rating_asc']) ) {
            $sort_type = 'newest';
        }

        $args = array(
            'post_id' => $product_id,
            'status'  => 'approve',
            'type'    => 'review',
            'parent'  => 0,
        );

        $meta_query = array();
        if ( is_numeric( $filter_type ) ) {
            $meta_query[] = array('key' => 'rating', 'value' => intval( $filter_type ), 'compare' => '=');
        } elseif ( $filter_type == 'has_image' ) {
            $meta_query[] = array('key' => 'review_image_ids', 'value' => '', 'compare' => '!=');
        } elseif ( $filter_type == 'verified' ) {
            $meta_query[] = array('key' => 'verified', 'value' => '1', 'compare' => '=');
        }
        if ( ! empty( $meta_query ) ) $args['meta_query'] = $meta_query;

        $count_args          = $args;
        $count_args['count'] = true;
        $total_comments      = get_comments( $count_args );
        $total_pages         = ceil( $total_comments / $per_page );

        if ( $sort_type == 'rating_desc' ) {
            $args['meta_key'] = 'rating'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC';
        } elseif ( $sort_type == 'rating_asc' ) {
            $args['meta_key'] = 'rating'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC';
        } elseif ( $sort_type == 'oldest' ) {
            $args['orderby'] = 'comment_date'; $args['order'] = 'ASC';
        } elseif ( $sort_type == 'newest' ) {
            $args['orderby'] = 'comment_date'; $args['order'] = 'DESC';
        }

        if ( $sort_type != 'helpful' ) {
            $args['number'] = $per_page;
            $args['offset'] = $offset;
        }

        $comments = get_comments( $args );

        // Sort theo "helpful" trong PHP sau khi lấy tất cả
        if ( $sort_type == 'helpful' && ! empty( $comments ) ) {
            usort( $comments, function( $a, $b ) {
                $count_a = intval( get_comment_meta( $a->comment_ID, 'helpful_count', true ) );
                $count_b = intval( get_comment_meta( $b->comment_ID, 'helpful_count', true ) );
                if ( $count_a == $count_b ) return 0;
                return ( $count_a > $count_b ) ? -1 : 1;
            });
            $comments = array_slice( $comments, $offset, $per_page );
        }

        $product = wc_get_product( $product_id );
        ob_start();

        if ( ! $comments ) {
            echo '<p class="mcwr-no-review">' . __( 'Không tìm thấy đánh giá nào phù hợp.', 'review-kit' ) . '</p>';
        } else {
            foreach ( $comments as $comment ) {
                echo $this->get_single_review_html( $comment, $product );
            }

            if ( $pagination_style == 'load_more' ) {
                if ( count($comments) == $per_page && ($offset + $per_page < $total_comments) ) {
                    $next_page = $page + 1;
                    echo '<div class="mcwr-load-more-container">';
                    echo '<button id="mcwr-load-more-btn" class="mcwr-btn mcwr-load-more-btn" data-page="' . $next_page . '">' . __( 'Tải thêm đánh giá', 'review-kit' ) . '</button>';
                    echo '</div>';
                }
            } else {
                echo $this->get_pagination_html( $total_pages, $page );
            }
        }

        $html_content = ob_get_clean();

        wp_send_json_success( array(
            'html'            => $html_content,
            'final_sort_type' => $sort_type,
        ) );
    }

    // ============================================================
    // SUBMIT REVIEW FORM
    // ============================================================

    /**
     * Xử lý form gửi đánh giá mới (POST + upload ảnh/video).
     * Hook: admin_post_submit_custom_review / admin_post_nopriv_submit_custom_review
     */
    public function handle_review_submission() {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        if ( ! isset( $_POST['mcwr_nonce'] ) || ! wp_verify_nonce( $_POST['mcwr_nonce'], 'submit_review' ) ) {
            wp_die( __( 'Lỗi bảo mật (Nonce Error). Vui lòng tải lại trang.', 'review-kit' ) );
        }

        $product_id = intval( $_POST['product_id'] );
        $rating     = intval( $_POST['rating'] );
        $author     = sanitize_text_field( $_POST['author'] );
        $email      = sanitize_email( $_POST['email'] );
        $content    = sanitize_textarea_field( $_POST['comment'] );

        if ( ! $product_id ) wp_die( __( 'Lỗi: Không tìm thấy ID sản phẩm.', 'review-kit' ) );
        if ( ! $rating )     wp_die( __( 'Lỗi: Chưa chọn số sao.', 'review-kit' ) );
        if ( empty($email) ) wp_die( __( 'Lỗi: Email trống.', 'review-kit' ) );

        $require_login = get_option('mcwr_require_login', 0);
        if ( $require_login && ! is_user_logged_in() ) {
            wp_die(
                __( 'Bạn cần phải đăng nhập để gửi đánh giá.', 'review-kit' ),
                __( 'Lỗi: Yêu cầu Đăng nhập', 'review-kit' ),
                array('response' => 403)
            );
        }

        $user_id          = get_current_user_id();
        $moderation_mode  = get_option( 'mcwr_moderation_mode', 0 );
        $comment_approved = current_user_can('administrator') ? 1 : intval( $moderation_mode );

        $comment_data = array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $author,
            'comment_author_email' => $email,
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_approved'     => $comment_approved,
            'user_id'              => $user_id,
        );

        $comment_id = wp_insert_comment( $comment_data );

        if ( ! $comment_id ) {
            wp_die( __( 'Lỗi: Không thể lưu đánh giá của bạn. Vui lòng thử lại.', 'review-kit' ) );
        }

        add_comment_meta( $comment_id, 'rating', $rating );

        if ( $this->is_verified_buyer( $email, $user_id, $product_id ) ) {
            add_comment_meta( $comment_id, 'verified', 1 );
        }

        $uploaded_image_ids = array();
        $uploaded_video_ids = array();

        if ( isset( $_FILES['review_media'] ) && is_array( $_FILES['review_media']['name'] ) ) {
            $files = $_FILES['review_media'];
            foreach ( $files['name'] as $key => $value ) {
                if ( $files['error'][$key] === UPLOAD_ERR_OK ) {
                    $file_type = $files['type'][$key];
                    $file_unit = array(
                        'name'     => $files['name'][$key],
                        'type'     => $file_type,
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key],
                    );

                    $_FILES['upload_file_temp'] = $file_unit;
                    $attachment_id = media_handle_upload( 'upload_file_temp', 0 );

                    if ( ! is_wp_error( $attachment_id ) ) {
                        if ( strpos( $file_type, 'video' ) !== false ) {
                            $uploaded_video_ids[] = $attachment_id;
                        } else {
                            // Auto Resize Server Side để tiết kiệm dung lượng hosting
                            $file_path = get_attached_file( $attachment_id );
                            if ( $file_path ) {
                                $image_editor = wp_get_image_editor( $file_path );
                                if ( ! is_wp_error( $image_editor ) ) {
                                    $image_editor->resize( 1200, 1200, false );
                                    $image_editor->set_quality( 80 );
                                    $image_editor->save( $file_path );
                                }
                            }
                            $uploaded_image_ids[] = $attachment_id;
                        }
                    }
                    unset( $_FILES['upload_file_temp'] );
                }
            }

            if ( ! empty( $uploaded_image_ids ) ) {
                add_comment_meta( $comment_id, 'review_image_ids', implode( ',', $uploaded_image_ids ) );
            }
            if ( ! empty( $uploaded_video_ids ) ) {
                add_comment_meta( $comment_id, 'review_video_ids', implode( ',', $uploaded_video_ids ) );
            }
        }

        // Refresh review count của WooCommerce
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $data_store = $product->get_data_store();
            if ( method_exists( $data_store, 'refresh_review_count' ) ) {
                $data_store->refresh_review_count( $product );
            }
        }
        delete_transient( 'mcwr_rating_counts_' . $product_id );

        $redirect_url = get_permalink( $product_id ) . '#reviews';
        if ( $comment_approved == 0 ) {
            $redirect_url = get_permalink( $product_id ) . '?review_pending=1#reviews';
        }

        wp_redirect( $redirect_url );
        exit;
    }

    // ============================================================
    // ADMIN REPLY
    // ============================================================

    /**
     * Xử lý form Admin phản hồi một đánh giá.
     * Hook: admin_post_mcwr_admin_reply_submission
     */
    public function handle_admin_reply_submission() {
        if ( ! current_user_can( 'administrator' ) ) {
            wp_die( __( 'Bạn không có quyền thực hiện tác vụ này.', 'review-kit' ) );
        }

        if ( ! isset( $_POST['mcwr_admin_reply_nonce'] ) || ! wp_verify_nonce( $_POST['mcwr_admin_reply_nonce'], 'mcwr_admin_reply_action' ) ) {
            wp_die( __( 'Lỗi bảo mật.', 'review-kit' ) );
        }

        $product_id = intval( $_POST['product_id'] );
        $parent_id  = intval( $_POST['parent_id'] );
        $content    = sanitize_textarea_field( $_POST['admin_reply_content'] );
        $user       = wp_get_current_user();

        if ( empty( $content ) ) wp_die( __( 'Nội dung trống.', 'review-kit' ) );

        $comment_data = array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_parent'       => $parent_id,
            'user_id'              => $user->ID,
            'comment_approved'     => 1,
        );

        $comment_id = wp_insert_comment( $comment_data );

        if ( $comment_id ) {
            wp_redirect( get_permalink( $product_id ) . '#reviews' );
            exit;
        } else {
            wp_die( __( 'Lỗi khi lưu phản hồi.', 'review-kit' ) );
        }
    }

    // ============================================================
    // VOTE HỮU ÍCH
    // ============================================================

    /**
     * Xử lý AJAX vote "Hữu ích" cho một đánh giá.
     * Hook: wp_ajax_mcwr_vote_review / wp_ajax_nopriv_mcwr_vote_review
     */
    public function handle_vote_review() {
        check_ajax_referer( 'mcwr_ajax_nonce', 'nonce' );
        $comment_id = intval( $_POST['comment_id'] );

        $cookie_name = 'mcwr_voted_' . $comment_id;
        if ( isset( $_COOKIE[$cookie_name] ) ) {
            wp_send_json_error( array( 'message' => __( 'Bạn đã bình chọn rồi.', 'review-kit' ) ) );
        }

        $current_likes = intval( get_comment_meta( $comment_id, 'helpful_count', true ) );
        $new_likes     = $current_likes + 1;
        update_comment_meta( $comment_id, 'helpful_count', $new_likes );

        setcookie( $cookie_name, '1', time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN );
        wp_send_json_success( array( 'new_count' => $new_likes ) );
    }

} // end trait MCWR_Trait_Ajax_Handlers
