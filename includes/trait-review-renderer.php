<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trait MCWR_Trait_Renderer
 *
 * Chứa toàn bộ logic RENDER HTML:
 * - Layout 2 cột chính
 * - Danh sách review + toolbar lọc
 * - HTML từng review đơn lẻ
 * - Khối thống kê sao (Summary)
 * - Hàm tiện ích: phân trang, màu sắc, thời gian
 *
 * Trait này được dùng bởi MCWR_Frontend. Mọi $this-> call đều hoạt động
 * như thể method nằm trực tiếp trong class gốc.
 */
trait MCWR_Trait_Renderer {

    // ============================================================
    // HELPERS: product_id & asset loading
    // ============================================================

    /**
     * Lấy product_id từ $atts shortcode.
     * Fallback: global $product → get_the_ID()
     */
    private function get_product_id_from_atts( $atts ) {
        if ( ! empty( $atts['product_id'] ) && intval( $atts['product_id'] ) > 0 ) {
            return intval( $atts['product_id'] );
        }
        global $product;
        if ( $product && method_exists( $product, 'get_id' ) ) {
            return $product->get_id();
        }
        return intval( get_the_ID() );
    }

    /**
     * Đảm bảo CSS/JS của plugin được enqueue khi shortcode dùng ngoài trang sản phẩm
     */
    private function ensure_assets_loaded() {
        if ( ! wp_style_is( 'mcwr-style', 'enqueued' ) ) {
            $this->enqueue_assets();
        }
    }

    // ============================================================
    // LAYOUT CHÍNH
    // ============================================================

    /**
     * Render toàn bộ layout 2 cột (dùng chung cho tab WooCommerce & [mcwr_reviews])
     */
    private function render_full_layout( $product_id ) {
        $product          = wc_get_product( $product_id );
        $per_page         = get_option( 'mcwr_per_page', 5 );
        $pagination_style = get_option( 'mcwr_pagination_style', 'numbered_ajax' );
        $require_login    = get_option( 'mcwr_require_login', 0 );
        $can_show_form    = ! ( $require_login && ! is_user_logged_in() );

        ob_start();
        echo '<div class="mcwr-two-col-layout">';

            // ── CỘT TRÁI ──────────────────────────────────────
            echo '<div class="mcwr-col-left">';
                echo $this->render_review_list_html( $product );
            echo '</div>';

            // ── CỘT PHẢI ──────────────────────────────────────
            echo '<div class="mcwr-col-right">';
                echo $this->render_review_summary_html( $product );

                if ( ! $can_show_form ) {
                    echo '<div class="mcwr-notice-warning">';
                    echo sprintf(
                        __( 'Vui lòng %sĐăng nhập%s để gửi đánh giá.', 'my-custom-woo-reviews' ),
                        '<a href="' . wp_login_url( get_permalink() ) . '"><strong>',
                        '</strong></a>'
                    );
                    echo '</div>';
                } else {
                    $current_product_id = $product_id;
                    $template_path = defined( 'MCWR_PLUGIN_DIR' )
                        ? MCWR_PLUGIN_DIR . 'templates/review-form.php'
                        : dirname( __DIR__ ) . '/templates/review-form.php';
                    if ( file_exists( $template_path ) ) {
                        include $template_path;
                    }
                }
            echo '</div>';

        echo '</div>'; // .mcwr-two-col-layout
        return ob_get_clean();
    }

    // ============================================================
    // DANH SÁCH REVIEW
    // ============================================================

    /**
     * Render danh sách đánh giá + toolbar lọc + phân trang
     *
     * @param WC_Product $product
     * @return string HTML
     */
    private function render_review_list_html( $product ) {
        $per_page         = get_option( 'mcwr_per_page', 5 );
        $pagination_style = get_option( 'mcwr_pagination_style', 'numbered_ajax' );

        ob_start();

        $product_id = $product->get_id();
        $uid = 'mcwr-' . $product_id;

        // Toolbar lọc & sắp xếp
        echo '<div class="mcwr-filter-container" data-product-id="' . $product_id . '">';
            echo '<h3>' . __( 'Đánh giá từ khách hàng', 'my-custom-woo-reviews' ) . '</h3>';
            echo '<div class="mcwr-toolbar">';
                echo '<div class="mcwr-filter-group">';
                    echo '<button class="filter-btn active" data-filter="all">'  . __( 'Tất cả', 'my-custom-woo-reviews' )     . '</button>';
                    echo '<button class="filter-btn" data-filter="5">'           . __( '5 Sao', 'my-custom-woo-reviews' )      . '</button>';
                    echo '<button class="filter-btn" data-filter="4">'           . __( '4 Sao', 'my-custom-woo-reviews' )      . '</button>';
                    echo '<button class="filter-btn" data-filter="3">'           . __( '3 Sao', 'my-custom-woo-reviews' )      . '</button>';
                    echo '<button class="filter-btn" data-filter="2">'           . __( '2 Sao', 'my-custom-woo-reviews' )      . '</button>';
                    echo '<button class="filter-btn" data-filter="1">'           . __( '1 Sao', 'my-custom-woo-reviews' )      . '</button>';
                    echo '<button class="filter-btn" data-filter="has_image">'   . __( 'Có hình ảnh', 'my-custom-woo-reviews' )  . '</button>';
                    echo '<button class="filter-btn" data-filter="verified">'    . __( 'Đã mua hàng', 'my-custom-woo-reviews' ) . '</button>';
                echo '</div>';
                echo '<div class="mcwr-sort-group">';
                    echo '<select class="mcwr-sort-dropdown" data-uid="' . esc_attr($uid) . '">';
                        echo '<option value="newest" selected>' . __( 'Mới nhất', 'my-custom-woo-reviews' )    . '</option>';
                        echo '<option value="helpful">'         . __( 'Hữu ích nhất', 'my-custom-woo-reviews' ) . '</option>';
                        echo '<option value="rating_desc">'     . __( 'Đánh giá cao', 'my-custom-woo-reviews' ) . '</option>';
                        echo '<option value="rating_asc">'      . __( 'Đánh giá thấp', 'my-custom-woo-reviews' ) . '</option>';
                        echo '<option value="oldest">'          . __( 'Cũ nhất', 'my-custom-woo-reviews' )     . '</option>';
                    echo '</select>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Danh sách review
        echo '<div class="mcwr-reviews-wrapper" data-product-id="' . $product_id . '">';
            $args = array(
                'post_id' => $product->get_id(),
                'status'  => 'approve',
                'type'    => 'review',
                'parent'  => 0,
                'orderby' => 'comment_date',
                'order'   => 'DESC',
                'number'  => $per_page,
            );
            $comments       = get_comments( $args );
            $count_args     = $args;
            unset( $count_args['number'] );
            $count_args['count'] = true;
            $total_comments = get_comments( $count_args );
            $total_pages    = ceil( $total_comments / $per_page );

            if ( ! $comments ) {
                echo '<p class="mcwr-no-review">' . __( 'Chưa có đánh giá nào.', 'my-custom-woo-reviews' ) . '</p>';
            } else {
                foreach ( $comments as $comment ) {
                    echo $this->get_single_review_html( $comment, $product );
                }
                if ( $pagination_style == 'load_more' ) {
                    if ( $total_comments > $per_page ) {
                        echo '<div class="mcwr-load-more-container">';
                        echo '<button id="mcwr-load-more-btn" class="mcwr-btn" data-page="2">' . __( 'Tải thêm đánh giá', 'my-custom-woo-reviews' ) . '</button>';
                        echo '</div>';
                    }
                } else {
                    echo $this->get_pagination_html( $total_pages, 1 );
                }
            }
        echo '</div>'; // .mcwr-reviews-wrapper

        return ob_get_clean();
    }

    /**
     * Phân trang số (1, 2, 3...) với ellipsis
     *
     * @param int $total_pages
     * @param int $current_page
     * @return string HTML
     */
    private function get_pagination_html( $total_pages, $current_page ) {
        if ( $total_pages <= 1 ) return '';

        $html = '<div class="mcwr-pagination">';

        if ( $current_page > 1 ) {
            $html .= '<button class="page-btn" data-page="' . ($current_page - 1) . '">&laquo;</button>';
        }

        $range = 2;
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            if ( $i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range) ) {
                $active = ($i == $current_page) ? 'active' : '';
                $html .= '<button class="page-btn ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
            } elseif ( $i == $current_page - $range - 1 || $i == $current_page + $range + 1 ) {
                if ($i > 1 && $i < $total_pages) {
                    if ( !str_contains($html, '<span class="dots">...</span>') ) {
                        $html .= '<span class="dots">...</span>';
                    }
                }
            }
        }

        if ( $current_page < $total_pages ) {
            $html .= '<button class="page-btn" data-page="' . ($current_page + 1) . '">&raquo;</button>';
        }

        $html .= '</div>';
        return $html;
    }

    // ============================================================
    // HTML MỘT REVIEW ĐƠN LẺ
    // ============================================================

    /**
     * Sinh HTML hoàn chỉnh cho 1 bình luận/review.
     *
     * @param WP_Comment $comment
     * @param WC_Product $product
     * @return string HTML
     */
    private function get_single_review_html( $comment, $product ) {
        $rating        = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
        $image_ids_str = get_comment_meta( $comment->comment_ID, 'review_image_ids', true );
        $is_verified   = get_comment_meta( $comment->comment_ID, 'verified', true );
        $helpful_count = intval( get_comment_meta( $comment->comment_ID, 'helpful_count', true ) );
        $enable_voting = get_option( 'mcwr_enable_voting', 1 );
        $badge_text    = get_option( 'mcwr_verified_text', __( 'Đã mua hàng', 'my-custom-woo-reviews' ) );
        $badge_color   = get_option( 'mcwr_verified_color', '#27ae60' );

        ob_start();
        ?>
        <div class="review-card-item" itemprop="review" itemscope itemtype="https://schema.org/Review">
            <div class="review-header">
                <div class="header-left">
                    <div class="user-avatar"><?php echo get_avatar( $comment, 40 ); ?></div>
                    <div class="user-info" itemprop="author" itemscope itemtype="https://schema.org/Person">
                        <div class="name-row">
                            <span class="author-name" itemprop="name"><?php echo get_comment_author( $comment->comment_ID ); ?></span>
                            <?php if ( $is_verified ): ?>
                                <span class="verified-badge" style="--badge-color-rgb: <?php echo $this->hex2rgb($badge_color); ?>; color: <?php echo esc_attr($badge_color); ?>;">
                                    <?php echo mcwr_icon( 'verified' ); echo esc_html( $badge_text ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="date-row">
                            <?php
                            $comment_timestamp = strtotime( $comment->comment_date );
                            $full_date         = get_comment_date( 'd/m/Y H:i', $comment->comment_ID );
                            echo '<time itemprop="datePublished" class="mcwr-comment-date" datetime="' . esc_attr( date( 'Y-m-d', $comment_timestamp ) ) . '" title="' . esc_attr( $full_date ) . '">' . $this->mcwr_human_time_diff( $comment_timestamp ) . '</time>';
                            ?>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <?php if ( $rating > 0 ): ?>
                        <div class="star-display" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                            <meta itemprop="ratingValue" content="<?php echo esc_attr($rating); ?>" />
                            <meta itemprop="bestRating" content="5" />
                            <?php echo str_repeat('★', $rating) . str_repeat('<span class="star-empty">★</span>', 5 - $rating); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="review-body" itemprop="reviewBody">
                <?php echo wpautop( get_comment_text( $comment->comment_ID ) ); ?>
            </div>

            <?php
            $image_ids = get_comment_meta( $comment->comment_ID, 'review_image_ids', true );
            $video_ids = get_comment_meta( $comment->comment_ID, 'review_video_ids', true );

            if ( ! empty( $image_ids ) || ! empty( $video_ids ) ): ?>
                <div class="review-gallery">
                    <?php
                    // Render Videos
                    if ( ! empty( $video_ids ) ) {
                        $video_id_array = explode( ',', $video_ids );
                        foreach ( $video_id_array as $v_id ) {
                            $video_url = wp_get_attachment_url( $v_id );
                            if ( $video_url ) {
                                ?>
                                <div class="review-gallery-item video-item" data-fancybox="gallery-<?php echo $comment->comment_ID; ?>" data-src="<?php echo esc_url( $video_url ); ?>">
                                    <div class="video-overlay"><?php echo mcwr_icon( 'play' ); ?></div>
                                    <video src="<?php echo esc_url( $video_url ); ?>" muted></video>
                                </div>
                                <?php
                            }
                        }
                    }

                    // Render Images
                    if ( ! empty( $image_ids ) ) {
                        $image_id_array = explode( ',', $image_ids );
                        foreach ( $image_id_array as $img_id ) {
                            $img_thumb = wp_get_attachment_image_src( $img_id, 'thumbnail' );
                            $img_full  = wp_get_attachment_image_src( $img_id, 'large' );
                            if ( $img_thumb ) {
                                ?>
                                <div
                                    class="review-gallery-item"
                                    data-src="<?php echo esc_url( $img_full[0] ); ?>"
                                    data-thumb="<?php echo esc_url( $img_thumb[0] ); ?>"
                                    data-fancybox="gallery-<?php echo $comment->comment_ID; ?>"
                                    data-caption="<?php echo sprintf( __( 'Đánh giá của %s', 'my-custom-woo-reviews' ), esc_attr(get_comment_author($comment->comment_ID)) ); ?>"
                                >
                                    <img src="<?php echo esc_url( $img_thumb[0] ); ?>">
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="review-actions">
                <?php if ( $enable_voting ): ?>
                <button class="action-btn like-btn" data-comment-id="<?php echo $comment->comment_ID; ?>">
                        <?php echo mcwr_icon( 'thumbs-up' ); ?> <?php _e( 'Hữu ích', 'my-custom-woo-reviews' ); ?> (<span class="count"><?php echo $helpful_count; ?></span>)
                    </button>
                <?php endif; ?>

                <?php if ( current_user_can( 'administrator' ) ): ?>
                <button class="action-btn reply-toggle-btn" data-comment-id="<?php echo $comment->comment_ID; ?>">
                        <?php echo mcwr_icon( 'chat' ); ?> <?php _e( 'Phản hồi', 'my-custom-woo-reviews' ); ?>
                    </button>
                <?php endif; ?>

                <button class="action-btn mcwr-report-btn" data-comment-id="<?php echo $comment->comment_ID; ?>" title="<?php _e( 'Báo cáo đánh giá vi phạm', 'my-custom-woo-reviews' ); ?>">
                    <?php echo mcwr_icon( 'flag' ); ?> <?php _e( 'Báo cáo', 'my-custom-woo-reviews' ); ?>
                </button>
            </div>

            <?php if ( current_user_can( 'administrator' ) ): ?>
                <div class="admin-reply-area mcwr-admin-reply-area" id="reply-form-<?php echo $comment->comment_ID; ?>" style="display:none;">
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                        <input type="hidden" name="action" value="mcwr_admin_reply_submission">
                        <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
                        <input type="hidden" name="parent_id" value="<?php echo $comment->comment_ID; ?>">
                        <?php wp_nonce_field( 'mcwr_admin_reply_action', 'mcwr_admin_reply_nonce' ); ?>
                        <textarea name="admin_reply_content" placeholder="<?php _e( 'Viết phản hồi...', 'my-custom-woo-reviews' ); ?>" required></textarea>
                        <div class="reply-form-actions">
                            <button type="button" class="cancel-btn" onclick="document.getElementById('reply-form-<?php echo $comment->comment_ID; ?>').style.display='none'"><?php _e( 'Hủy', 'my-custom-woo-reviews' ); ?></button>
                            <button type="submit" class="submit-btn"><?php _e( 'Gửi', 'my-custom-woo-reviews' ); ?></button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php
            $replies = get_comments( array('parent' => $comment->comment_ID, 'status' => 'approve', 'order' => 'ASC') );
            if ( $replies ) :
                foreach ( $replies as $reply ) :
            ?>
                <div class="shop-response mcwr-nested-reply">
                    <div class="reply-header">
                        <div class="reply-avatar">
                            <?php echo get_avatar( $reply->comment_author_email, 36 ); ?>
                        </div>
                        <div class="reply-author-info">
                            <span class="reply-author-name"><?php echo esc_html( $reply->comment_author ); ?></span>
                            <span class="reply-badge"><?php echo mcwr_icon( 'admin' ); ?> <?php _e( 'Quản trị viên', 'my-custom-woo-reviews' ); ?></span>
                            <span class="reply-date">&bull; <?php
                                $reply_ts   = strtotime( $reply->comment_date );
                                $reply_full = get_comment_date( 'd/m/Y H:i', $reply->comment_ID );
                                echo '<time class="mcwr-comment-date" datetime="' . esc_attr( date( 'c', $reply_ts ) ) . '" title="' . esc_attr( $reply_full ) . '">' . $this->mcwr_human_time_diff( $reply_ts ) . '</time>';
                            ?></span>
                        </div>
                    </div>
                    <div class="reply-content">
                        <?php echo wpautop( get_comment_text( $reply->comment_ID ) ); ?>
                    </div>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ============================================================
    // THỐNG KÊ SAO (SUMMARY)
    // ============================================================

    /**
     * Lấy số lượng review theo từng sao (cached via transient).
     *
     * @param int $product_id
     * @return array { counts, total, average }
     */
    private function get_rating_counts( $product_id ) {
        $transient_key = 'mcwr_rating_counts_' . $product_id;
        $cached_counts = get_transient( $transient_key );

        if ( false !== $cached_counts ) {
            return $cached_counts;
        }

        global $wpdb;
        $sql = "
            SELECT meta_value as rating, COUNT(*) as count
            FROM {$wpdb->commentmeta} AS cm
            JOIN {$wpdb->comments} AS c ON cm.comment_id = c.comment_ID
            WHERE c.comment_post_ID = %d
            AND c.comment_approved = '1'
            AND c.comment_type = 'review'
            AND cm.meta_key = 'rating'
            GROUP BY cm.meta_value
        ";

        $results = $wpdb->get_results( $wpdb->prepare( $sql, $product_id ) );

        $counts      = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
        $total_count = 0;
        $total_score = 0;

        foreach ( $results as $row ) {
            $rating = intval( $row->rating );
            $count  = intval( $row->count );
            if ( isset( $counts[$rating] ) ) {
                $counts[$rating] = $count;
                $total_count    += $count;
                $total_score    += ( $rating * $count );
            }
        }

        $average    = ($total_count > 0) ? round( $total_score / $total_count, 1 ) : 0;
        $final_data = array(
            'counts'  => $counts,
            'total'   => $total_count,
            'average' => $average,
        );

        set_transient( $transient_key, $final_data, 12 * HOUR_IN_SECONDS );
        return $final_data;
    }

    /**
     * Render khối thống kê sao + Schema.org AggregateRating.
     *
     * @param WC_Product $product
     * @return string HTML
     */
    private function render_review_summary_html( $product ) {
        $stats   = $this->get_rating_counts( $product->get_id() );
        $counts  = $stats['counts'];
        $total   = $stats['total'];
        $average = $stats['average'];

        if ( $total == 0 ) return '';
        $percentage = ($average / 5) * 100;

        ob_start();
        ?>
        <div class="mcwr-summary-box" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
            <meta itemprop="ratingValue" content="<?php echo esc_attr( $average ); ?>" />
            <meta itemprop="reviewCount" content="<?php echo esc_attr( $total ); ?>" />
            <div class="summary-left">
                <div class="average-score">
                    <span class="score-number"><?php echo esc_html( $average ); ?></span>
                    <span class="score-total">/ 5</span>
                </div>
                <div class="average-stars-container" title="<?php echo esc_attr($average); ?> sao">
                    <div class="stars-bg">
                        <?php echo str_repeat( mcwr_icon( 'star' ), 5 ); ?>
                    </div>
                    <div class="stars-fill" style="width: <?php echo esc_attr($percentage); ?>%;">
                        <?php echo str_repeat( mcwr_icon( 'star' ), 5 ); ?>
                    </div>
                </div>
                <div class="total-text"><?php echo sprintf( __( '%d đánh giá', 'my-custom-woo-reviews' ), $total ); ?></div>
            </div>
            <div class="summary-right">
                <?php
                for ( $i = 5; $i >= 1; $i-- ) {
                    $count   = $counts[$i];
                    $percent = ($total > 0) ? ($count / $total) * 100 : 0;
                    ?>
                    <div class="summary-row" onclick="jQuery('.filter-btn[data-filter=<?php echo $i; ?>]').trigger('click');">
                        <span class="row-label"><?php echo sprintf( __( '%d sao', 'my-custom-woo-reviews' ), $i ); ?></span>
                        <div class="row-bar-bg">
                            <div class="row-bar-fill mcwr-progress-bar" style="width: 0;" data-percent="<?php echo esc_attr( $percent ); ?>%"></div>
                        </div>
                        <span class="row-count"><?php echo $count; ?></span>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ============================================================
    // TIỆN ÍCH MÀU SẮC & THỜI GIAN
    // ============================================================

    /**
     * Chuyển mã hex sang chuỗi "R, G, B" (dùng cho CSS custom property).
     *
     * @param string $hex
     * @return string
     */
    private function hex2rgb( $hex ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen($hex) == 3 ) {
            $r = hexdec( substr($hex,0,1) . substr($hex,0,1) );
            $g = hexdec( substr($hex,1,1) . substr($hex,1,1) );
            $b = hexdec( substr($hex,2,1) . substr($hex,2,1) );
        } else {
            $r = hexdec( substr($hex,0,2) );
            $g = hexdec( substr($hex,2,2) );
            $b = hexdec( substr($hex,4,2) );
        }
        return $r . ', ' . $g . ', ' . $b;
    }

    /**
     * Chuyển mã hex sang rgba() (dùng cho inline style với opacity).
     *
     * @param string $hex
     * @param float  $opacity
     * @return string
     */
    private function hex_to_rgba_helper( $hex, $opacity = 1 ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) == 3 ) {
            $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
            $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
            $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
        } else {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        }
        return "rgba($r, $g, $b, $opacity)";
    }

    /**
     * Chuyển timestamp thành thời gian tương đối tiếng Việt.
     * Dưới 7 ngày → tương đối ("3 giờ trước"). Trên 7 ngày → ngày thật ("06/04/2026").
     *
     * @param  int    $timestamp Unix timestamp
     * @return string
     */
    private function mcwr_human_time_diff( $timestamp ) {
        $now  = current_time( 'timestamp' );
        $diff = $now - $timestamp;

        if ( $diff < 60 ) {
            return __( 'Vừa xong', 'my-custom-woo-reviews' );
        }
        if ( $diff < HOUR_IN_SECONDS ) {
            $minutes = round( $diff / MINUTE_IN_SECONDS );
            return sprintf( _n( '%d phút trước', '%d phút trước', $minutes, 'my-custom-woo-reviews' ), $minutes );
        }
        if ( $diff < DAY_IN_SECONDS ) {
            $hours = round( $diff / HOUR_IN_SECONDS );
            return sprintf( _n( '%d giờ trước', '%d giờ trước', $hours, 'my-custom-woo-reviews' ), $hours );
        }
        if ( $diff < ( 7 * DAY_IN_SECONDS ) ) {
            $days = round( $diff / DAY_IN_SECONDS );
            return sprintf( _n( '%d ngày trước', '%d ngày trước', $days, 'my-custom-woo-reviews' ), $days );
        }

        return date_i18n( 'd/m/Y', $timestamp );
    }

} // end trait MCWR_Trait_Renderer
