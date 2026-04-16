<?php
/**
 * Template: Review List (Danh sách đánh giá + Filter Toolbar)
 *
 * Hiển thị thanh lọc sao, dropdown sắp xếp và vòng lặp
 * các đánh giá kèm phân trang tương ứng.
 *
 * Variables available:
 * @var int          $product_id       ID sản phẩm hiện tại.
 * @var WP_Comment[] $comments         Mảng comment hiện tại.
 * @var int          $total_comments   Tổng số comment (dùng phân trang).
 * @var int          $total_pages      Tổng số trang.
 * @var int          $per_page         Số review mỗi trang.
 * @var string       $pagination_style Kiểu phân trang: 'numbered_ajax' | 'load_more'.
 * @var WC_Product   $product          Đối tượng sản phẩm.
 *
 * @package ReviewKit
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$uid = 'reviewkit-' . intval( $product_id );
?>

<?php /* --- TOOLBAR: Bộ lọc & Sắp xếp --- */ ?>
<div class="reviewkit-filter-container" data-product-id="<?php echo esc_attr( $product_id ); ?>">
    <h3><?php _e( 'Đánh giá từ khách hàng', 'review-kit' ); ?></h3>

    <div class="reviewkit-toolbar">
        <div class="reviewkit-filter-group">
            <button class="filter-btn active" data-filter="all"><?php _e( 'Tất cả', 'review-kit' ); ?></button>
            <?php for ( $s = 5; $s >= 1; $s-- ) : ?>
                <button class="filter-btn" data-filter="<?php echo intval( $s ); ?>"><?php echo esc_html( sprintf( __( '%d Sao', 'review-kit' ), $s ) ); ?></button>
            <?php endfor; ?>
            <button class="filter-btn" data-filter="has_image"><?php _e( 'Có hình ảnh', 'review-kit' ); ?></button>
            <button class="filter-btn" data-filter="verified"><?php _e( 'Đã mua hàng', 'review-kit' ); ?></button>
        </div>

        <div class="reviewkit-sort-group">
            <select class="reviewkit-sort-dropdown" data-uid="<?php echo esc_attr( $uid ); ?>">
                <option value="newest"      selected><?php _e( 'Mới nhất', 'review-kit' ); ?></option>
                <option value="helpful">             <?php _e( 'Hữu ích nhất', 'review-kit' ); ?></option>
                <option value="rating_desc">         <?php _e( 'Đánh giá cao', 'review-kit' ); ?></option>
                <option value="rating_asc">          <?php _e( 'Đánh giá thấp', 'review-kit' ); ?></option>
                <option value="oldest">              <?php _e( 'Cũ nhất', 'review-kit' ); ?></option>
            </select>
        </div>
    </div>
</div>

<?php /* --- REVIEW ITEMS --- */ ?>
<div class="reviewkit-reviews-wrapper" data-product-id="<?php echo esc_attr( $product_id ); ?>">
    <?php if ( empty( $comments ) ) : ?>
        <p class="reviewkit-no-review"><?php _e( 'Chưa có đánh giá nào.', 'review-kit' ); ?></p>
    <?php else : ?>

        <?php foreach ( $comments as $comment ) : ?>
            <?php
            reviewkit_get_template( 'reviews/review-card.php', array(
                'comment'       => $comment,
                'product'       => $product,
                'rating'        => intval( get_comment_meta( $comment->comment_ID, 'rating', true ) ),
                'is_verified'   => get_comment_meta( $comment->comment_ID, 'verified', true ),
                'helpful_count' => intval( get_comment_meta( $comment->comment_ID, 'helpful_count', true ) ),
                'enable_voting' => get_option( 'reviewkit_enable_voting', 1 ),
                'badge_text'    => get_option( 'reviewkit_verified_text', __( 'Đã mua hàng', 'review-kit' ) ),
                'badge_color'   => get_option( 'reviewkit_verified_color', '#27ae60' ),
            ) );
            ?>
        <?php endforeach; ?>

        <?php /* --- PAGINATION --- */ ?>
        <?php if ( 'load_more' === $pagination_style ) : ?>
            <?php if ( $total_comments > $per_page ) : ?>
                <div class="reviewkit-load-more-container">
                    <button id="reviewkit-load-more-btn" class="reviewkit-btn" data-page="2">
                        <?php _e( 'Tải thêm đánh giá', 'review-kit' ); ?>
                    </button>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <?php reviewkit_get_template( 'reviews/pagination.php', array( 'total_pages' => $total_pages, 'current_page' => 1 ) ); ?>
        <?php endif; ?>

    <?php endif; ?>
</div><?php // .reviewkit-reviews-wrapper ?>
