<?php
// $current_product_id được truyền vào từ hàm render của class-frontend.php
// Fallback về get_the_ID() nếu không được set (tương thích ngược)
if ( ! isset( $current_product_id ) || ! $current_product_id ) {
    $current_product_id = get_the_ID();
}
?>
<div class="mcwr-review-form">
    <h3><?php _e( 'Gửi đánh giá sản phẩm', 'review-kit' ); ?></h3>
    
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data" id="mcwr_form">
        <input type="hidden" name="action" value="submit_custom_review">
        <input type="hidden" name="product_id" value="<?php echo intval( $current_product_id ); ?>">
        <?php wp_nonce_field( 'submit_review', 'mcwr_nonce' ); ?>

        <div class="mcwr-form-group">
            <label class="mcwr-label"><?php _e( 'Đánh giá của bạn:', 'review-kit' ); ?></label>
            <div class="mcwr-star-rating">
                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="<?php esc_attr_e( 'Tuyệt vời', 'review-kit' ); ?>">★</label>
                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="<?php esc_attr_e( 'Tốt', 'review-kit' ); ?>">★</label>
                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="<?php esc_attr_e( 'Bình thường', 'review-kit' ); ?>">★</label>
                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="<?php esc_attr_e( 'Tệ', 'review-kit' ); ?>">★</label>
                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="<?php esc_attr_e( 'Rất tệ', 'review-kit' ); ?>">★</label>
            </div>
        </div>

        <div class="mcwr-form-group">
            <input type="text" name="author" placeholder="<?php esc_attr_e( 'Tên của bạn', 'review-kit' ); ?>" required>
        </div>
        
        <div class="mcwr-form-group">
            <input type="email" name="email" placeholder="<?php esc_attr_e( 'Email của bạn', 'review-kit' ); ?>" required>
        </div>

        <div class="mcwr-form-group">
            <textarea name="comment" placeholder="<?php esc_attr_e( 'Viết cảm nhận chi tiết...', 'review-kit' ); ?>" required></textarea>
        </div>

        <div class="mcwr-form-group">
            <label class="mcwr-label"><?php _e( 'Hình ảnh & Video thực tế (Tối đa 5 tệp):', 'review-kit' ); ?></label>
            
            <div id="mcwr_dropzone" class="mcwr-dropzone">
                <p><?php _e( 'Kéo thả tệp vào đây hoặc click để chọn', 'review-kit' ); ?></p>
                <span><?php _e( '(Hỗ trợ JPG, PNG, MP4, WebM)', 'review-kit' ); ?></span>
                <input type="file" name="review_media[]" id="mcwr_file_input" accept="image/*,video/*" multiple hidden>
            </div>

            <div id="mcwr_preview_list" class="mcwr-preview-list">
                </div>
        </div>

        <button type="submit" class="mcwr-submit-btn"><?php _e( 'Gửi đánh giá', 'review-kit' ); ?></button>
    </form>
</div>