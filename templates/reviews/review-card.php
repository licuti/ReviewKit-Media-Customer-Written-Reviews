<?php
/**
 * Template: Single Review Card
 *
 * Giao diện một review đơn lẻ gồm: avatar, tên, badge, ngày,
 * số sao, nội dung, gallery ảnh/video, và các nút action.
 *
 * Variables available:
 * @var WP_Comment $comment     Đối tượng comment hiện tại.
 * @var WC_Product $product     Đối tượng sản phẩm liên quan.
 * @var int        $rating      Số sao (1-5).
 * @var bool       $is_verified Trạng thái đã mua hàng.
 * @var int        $helpful_count Số lượt hữu ích.
 * @var bool       $enable_voting Bật/tắt tính năng bình chọn.
 * @var string     $badge_text  Nội dung chữ trên badge xác thực.
 * @var string     $badge_color Màu của badge xác thực.
 *
 * @package ReviewKit
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="review-card-item" itemprop="review" itemscope itemtype="https://schema.org/Review">

    <?php /* --- HEADER: Avatar, Tên, Badge, Ngày, Sao --- */ ?>
    <div class="review-header">

        <div class="header-left">
            <div class="user-avatar"><?php echo get_avatar( $comment, 40 ); ?></div>

            <div class="user-info" itemprop="author" itemscope itemtype="https://schema.org/Person">
                <div class="name-row">
                    <span class="author-name" itemprop="name"><?php echo esc_html( get_comment_author( $comment->comment_ID ) ); ?></span>
                    <?php if ( $is_verified ) : ?>
                        <span class="verified-badge" style="--badge-color-rgb: <?php echo esc_attr( reviewkit_hex2rgb( $badge_color ) ); ?>; color: <?php echo esc_attr( $badge_color ); ?>;">
                            <?php echo reviewkit_icon( 'verified' ); echo esc_html( $badge_text ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="date-row">
                    <?php
                    $comment_timestamp = strtotime( $comment->comment_date );
                    $full_date         = get_comment_date( 'd/m/Y H:i', $comment->comment_ID );
                    echo '<time itemprop="datePublished" class="reviewkit-comment-date" datetime="' . esc_attr( date( 'Y-m-d', $comment_timestamp ) ) . '" title="' . esc_attr( $full_date ) . '">' . esc_html( reviewkit_human_time_diff( $comment_timestamp ) ) . '</time>';
                    ?>
                </div>
            </div>
        </div>

        <div class="header-right">
            <?php if ( $rating > 0 ) : ?>
                <div class="star-display" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                    <meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>" />
                    <meta itemprop="bestRating"  content="5" />
                    <?php echo str_repeat( '★', $rating ) . str_repeat( '<span class="star-empty">★</span>', 5 - $rating ); ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php /* --- BODY: Nội dung review --- */ ?>
    <div class="review-body" itemprop="reviewBody">
        <?php echo wp_kses_post( wpautop( get_comment_text( $comment->comment_ID ) ) ); ?>
    </div>

    <?php /* --- GALLERY: Ảnh & Video --- */ ?>
    <?php
    $image_ids = get_comment_meta( $comment->comment_ID, 'review_image_ids', true );
    $video_ids = get_comment_meta( $comment->comment_ID, 'review_video_ids', true );

    if ( ! empty( $image_ids ) || ! empty( $video_ids ) ) :
    ?>
        <div class="review-gallery">
            <?php
            // Render Videos
            if ( ! empty( $video_ids ) ) {
                foreach ( explode( ',', $video_ids ) as $v_id ) {
                    $video_url = wp_get_attachment_url( absint( $v_id ) );
                    if ( $video_url ) {
                        echo '<div class="review-gallery-item video-item" data-fancybox="gallery-' . esc_attr( $comment->comment_ID ) . '" data-src="' . esc_url( $video_url ) . '">';
                        echo '<div class="video-overlay">' . reviewkit_icon( 'play' ) . '</div>';
                        echo '<video src="' . esc_url( $video_url ) . '" muted></video>';
                        echo '</div>';
                    }
                }
            }

            // Render Images
            if ( ! empty( $image_ids ) ) {
                foreach ( explode( ',', $image_ids ) as $img_id ) {
                    $img_thumb = wp_get_attachment_image_src( absint( $img_id ), 'thumbnail' );
                    $img_full  = wp_get_attachment_image_src( absint( $img_id ), 'large' );
                    if ( $img_thumb ) {
                        echo '<div class="review-gallery-item"'
                            . ' data-src="' . esc_url( $img_full[0] ) . '"'
                            . ' data-thumb="' . esc_url( $img_thumb[0] ) . '"'
                            . ' data-fancybox="gallery-' . esc_attr( $comment->comment_ID ) . '"'
                            . ' data-caption="' . esc_attr( sprintf( __( 'Đánh giá của %s', 'review-kit' ), get_comment_author( $comment->comment_ID ) ) ) . '"'
                            . '>';
                        echo '<img src="' . esc_url( $img_thumb[0] ) . '" alt="' . esc_attr( sprintf( __( 'Đánh giá từ %s', 'review-kit' ), get_comment_author( $comment->comment_ID ) ) ) . '">';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
    <?php endif; ?>

    <?php /* --- ACTIONS: Hữu ích, Phản hồi, Báo cáo --- */ ?>
    <div class="review-actions">
        <?php if ( $enable_voting ) : ?>
            <button class="action-btn like-btn" data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>">
                <?php echo reviewkit_icon( 'thumbs-up' ); ?>
                <?php _e( 'Hữu ích', 'review-kit' ); ?> (<span class="count"><?php echo intval( $helpful_count ); ?></span>)
            </button>
        <?php endif; ?>

        <?php if ( current_user_can( 'administrator' ) ) : ?>
            <button class="action-btn reply-toggle-btn" data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>">
                <?php echo reviewkit_icon( 'chat' ); ?>
                <?php _e( 'Phản hồi', 'review-kit' ); ?>
            </button>
        <?php endif; ?>

        <button class="action-btn reviewkit-report-btn" data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>" title="<?php esc_attr_e( 'Báo cáo đánh giá vi phạm', 'review-kit' ); ?>">
            <?php echo reviewkit_icon( 'flag' ); ?>
            <?php _e( 'Báo cáo', 'review-kit' ); ?>
        </button>
    </div>

    <?php /* --- ADMIN REPLY FORM --- */ ?>
    <?php if ( current_user_can( 'administrator' ) ) : ?>
        <div class="admin-reply-area reviewkit-admin-reply-area" id="reply-form-<?php echo esc_attr( $comment->comment_ID ); ?>" style="display:none;">
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action"     value="reviewkit_admin_reply_submission">
                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
                <input type="hidden" name="parent_id"  value="<?php echo esc_attr( $comment->comment_ID ); ?>">
                <?php wp_nonce_field( 'reviewkit_admin_reply_action', 'reviewkit_admin_reply_nonce' ); ?>
                <textarea name="admin_reply_content" placeholder="<?php esc_attr_e( 'Viết phản hồi...', 'review-kit' ); ?>" required></textarea>
                <div class="reply-form-actions">
                    <button type="button" class="cancel-btn" onclick="document.getElementById('reply-form-<?php echo esc_attr( $comment->comment_ID ); ?>').style.display='none'"><?php _e( 'Hủy', 'review-kit' ); ?></button>
                    <button type="submit" class="submit-btn"><?php _e( 'Gửi', 'review-kit' ); ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php /* --- NESTED REPLIES --- */ ?>
    <?php
    $replies = get_comments( array(
        'parent' => $comment->comment_ID,
        'status' => 'approve',
        'order'  => 'ASC',
    ) );
    foreach ( $replies as $reply ) :
    ?>
        <div class="shop-response reviewkit-nested-reply">
            <div class="reply-header">
                <div class="reply-avatar"><?php echo get_avatar( $reply->comment_author_email, 36 ); ?></div>
                <div class="reply-author-info">
                    <span class="reply-author-name"><?php echo esc_html( $reply->comment_author ); ?></span>
                    <span class="reply-badge"><?php echo reviewkit_icon( 'admin' ); ?> <?php _e( 'Quản trị viên', 'review-kit' ); ?></span>
                    <span class="reply-date">&bull; <?php
                        $reply_ts   = strtotime( $reply->comment_date );
                        $reply_full = get_comment_date( 'd/m/Y H:i', $reply->comment_ID );
                        echo '<time class="reviewkit-comment-date" datetime="' . esc_attr( date( 'c', $reply_ts ) ) . '" title="' . esc_attr( $reply_full ) . '">' . esc_html( reviewkit_human_time_diff( $reply_ts ) ) . '</time>';
                    ?></span>
                </div>
            </div>
            <div class="reply-content"><?php echo wp_kses_post( wpautop( get_comment_text( $reply->comment_ID ) ) ); ?></div>
        </div>
    <?php endforeach; ?>

</div><?php // .review-card-item ?>
