<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class hiển thị Meta Box thư viện ảnh/video trong trang sửa Comment.
 */
class MCWR_Admin_Review_Editor {

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'add_meta_boxes_comment', array( $this, 'register_review_metabox' ) );

            // Phase 7: Media Column in List
            add_filter( 'manage_edit-comments_columns',  array( $this, 'add_media_column' ) );
            // WooCommerce Reviews page support (WC 6.7+)
            add_filter( 'woocommerce_product_reviews_table_columns', array( $this, 'add_media_column' ) );
            add_action( 'woocommerce_product_reviews_table_column_mcwr_media', array( $this, 'render_wc_media_column' ) );

            add_action( 'manage_comments_custom_column', array( $this, 'render_media_column' ), 10, 2 );
            add_action( 'admin_head', array( $this, 'add_column_styles' ) );
        }
    }

    /**
     * Đăng ký Metabox chi tiết Media cho Đánh giá
     */
    public function register_review_metabox() {
        add_meta_box(
            'mcwr_media_gallery',
            'MCWR: Thư viện Đánh giá',
            array( $this, 'render_review_metabox' ),
            'comment',
            'normal',
            'high'
        );
    }

    /**
     * Render giao diện Metabox
     */
    public function render_review_metabox( $comment ) {
        $image_ids = get_comment_meta( $comment->comment_ID, 'review_image_ids', true );
        $video_ids = get_comment_meta( $comment->comment_ID, 'review_video_ids', true );
        $likes     = intval( get_comment_meta( $comment->comment_ID, 'helpful_count', true ) );
        $reports   = intval( get_comment_meta( $comment->comment_ID, 'mcwr_report_count', true ) );

        ?>
        <style>
            .mcwr-admin-gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
            .mcwr-admin-media-item { width: 100px; height: 100px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; position: relative; background: #f9f9f9; }
            .mcwr-admin-media-item img { width: 100%; height: 100%; object-fit: cover; }
            .mcwr-admin-media-item .video-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; font-size: 10px; text-align: center; padding: 2px 0; }
            .mcwr-meta-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
            .mcwr-meta-summary div strong { color: #333; }
        </style>

        <div class="mcwr-admin-gallery-container">
            <strong><span class="dashicons dashicons-images-alt2"></span> Ảnh & Video của khách hàng:</strong>
            
            <?php if ( empty($image_ids) && empty($video_ids) ) : ?>
                <p style="color: #888; font-style: italic;">Đánh giá này không đính kèm file media.</p>
            <?php else : ?>
                <div class="mcwr-admin-gallery">
                    <?php 
                    // Render Ảnh
                    if ( ! empty( $image_ids ) ) {
                        $ids_arr = explode( ',', $image_ids );
                        foreach ( $ids_arr as $id ) {
                            $url = wp_get_attachment_thumb_url( $id );
                            if ( $url ) {
                                echo '<div class="mcwr-admin-media-item"><a href="' . wp_get_attachment_url($id) . '" target="_blank" title="Xem ảnh gốc"><img src="' . esc_url($url) . '" /></a></div>';
                            } else {
                                echo '<div class="mcwr-admin-media-item" style="display:flex;align-items:center;justify-content:center;color:#ccc;font-size:11px;">[Lỗi ID: '.$id.']</div>';
                            }
                        }
                    }

                    // Render Video
                    if ( ! empty( $video_ids ) ) {
                        $vids_arr = explode( ',', $video_ids );
                        foreach ( $vids_arr as $v_id ) {
                            $video_url = wp_get_attachment_url( $v_id );
                            if ( $video_url ) {
                                echo '<div class="mcwr-admin-media-item"><a href="' . esc_url($video_url) . '" target="_blank" title="Xem Video"><img src="' . includes_url('images/media/video.png') . '" style="padding:20px;" /><div class="video-label">VIDEO</div></a></div>';
                            }
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="mcwr-meta-summary">
                <div>
                    <span class="dashicons dashicons-thumbs-up"></span> Số lượt thích: <strong><?php echo $likes; ?></strong>
                </div>
                <div>
                    <span class="dashicons dashicons-warning"></span> Số lần bị báo cáo: <strong style="color:<?php echo $reports > 0 ? '#d63638' : 'inherit'; ?>;"><?php echo $reports; ?></strong>
                </div>
            </div>

            <p class="description" style="margin-top:15px; background:#fff8e5; padding:8px; border-left:4px solid #ffba00;">
                <span class="dashicons dashicons-info" style="font-size:16px;"></span> Bạn có thể quản lý File trong <strong>Thư viện Media</strong> bằng cách tìm theo các ID: 
                <code><?php echo esc_html( trim( $image_ids . ',' . $video_ids, ',' ) ); ?></code>
            </p>
        </div>
        <?php
    }

    /**
     * Thêm cột Media vào header bảng
     */
    public function add_media_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[$key] = $value;
            if ( $key === 'comment' ) { // Chèn sau cột nội dung bình luận
                $new_columns['mcwr_media'] = '<span class="dashicons dashicons-images-alt2"></span> Media';
            }
        }
        return $new_columns;
    }

    /**
     * Render nội dung cho cột Media
     */
    public function render_media_column( $column, $comment_id ) {
        if ( $column !== 'mcwr_media' ) return;

        $image_ids = get_comment_meta( $comment_id, 'review_image_ids', true );
        $video_ids = get_comment_meta( $comment_id, 'review_video_ids', true );

        if ( empty($image_ids) && empty($video_ids) ) {
            echo '<span style="color:#ccc;">—</span>';
            return;
        }

        echo '<div class="mcwr-admin-column-media">';
        
        // Render Ảnh
        if ( ! empty( $image_ids ) ) {
            $ids = explode( ',', $image_ids );
            $count = count($ids);
            foreach ( array_slice($ids, 0, 2) as $id ) {
                $img = wp_get_attachment_image_src( $id, array(50, 50) );
                if ( $img ) {
                    echo '<img src="' . esc_url( $img[0] ) . '" class="mcwr-col-thumb" />';
                }
            }
            if ( $count > 2 ) {
                echo '<span class="mcwr-more-badge">+' . ($count - 2) . '</span>';
            }
        }

        // Render Video Icon
        if ( ! empty( $video_ids ) ) {
            $v_ids = explode( ',', $video_ids );
            echo '<span class="mcwr-video-badge" title="Có ' . count($v_ids) . ' video"><span class="dashicons dashicons-video-alt3"></span></span>';
        }

        echo '</div>';
    }

    /**
     * Wrapper cho WooCommerce Product Reviews Table
     */
    public function render_wc_media_column( $item ) {
        if ( isset( $item->comment_ID ) ) {
            $this->render_media_column( 'mcwr_media', $item->comment_ID );
        }
    }

    /**
     * Thêm CSS để tối ưu hiển thị cột
     */
    public function add_column_styles() {
        $screen = get_current_screen();
        $allowed_screens = array( 'edit-comments', 'product_page_product-reviews' );
        
        if ( ! in_array( $screen->id, $allowed_screens ) ) return;
        ?>
        <style>
            .column-mcwr_media { width: 110px; }
            .mcwr-admin-column-media { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; padding: 5px 0; }
            .mcwr-col-thumb { width: 34px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; }
            .mcwr-more-badge { font-size: 10px; background: #72777c; color: #fff; padding: 2px 4px; border-radius: 3px; line-height: 1; font-weight: 600; }
            .mcwr-video-badge { color: #ee4d2d; transform: scale(0.9); display: inline-flex; align-items: center; }
            
            /* Responsive fix cho bảng WC */
            .fixed .column-mcwr_media { width: 110px; }
        </style>
        <?php
    }
}
