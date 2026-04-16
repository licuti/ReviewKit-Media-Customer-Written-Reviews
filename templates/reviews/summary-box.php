<?php
/**
 * Template: Summary Box (Bảng thống kê đánh giá)
 *
 * Hiển thị điểm trung bình, các ngôi sao tổng hợp và
 * thanh progress bar phân bổ theo từng mức sao (5→1).
 * Tích hợp Schema.org AggregateRating.
 *
 * Variables available:
 * @var float  $average    Điểm trung bình (vd: 4.5).
 * @var int    $total      Tổng số đánh giá.
 * @var array  $counts     Mảng số lượng theo sao: [5=>x, 4=>y, ...].
 * @var float  $percentage Phần trăm thanh fill của sao trung bình.
 *
 * @package ReviewKit
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="reviewkit-summary-box" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
    <meta itemprop="ratingValue" content="<?php echo esc_attr( $average ); ?>" />
    <meta itemprop="reviewCount" content="<?php echo esc_attr( $total ); ?>" />

    <?php /* --- Cột trái: Điểm tổng & Sao trung bình --- */ ?>
    <div class="summary-left">
        <div class="average-score">
            <span class="score-number"><?php echo esc_html( $average ); ?></span>
            <span class="score-total">/ 5</span>
        </div>

        <div class="average-stars-container" title="<?php echo esc_attr( $average ); ?> sao">
            <div class="stars-bg">
                <?php echo str_repeat( reviewkit_icon( 'star' ), 5 ); ?>
            </div>
            <div class="stars-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;">
                <?php echo str_repeat( reviewkit_icon( 'star' ), 5 ); ?>
            </div>
        </div>

        <div class="total-text">
            <?php echo esc_html( sprintf( _n( '%d đánh giá', '%d đánh giá', $total, 'review-kit' ), $total ) ); ?>
        </div>
    </div>

    <?php /* --- Cột phải: Progress bars từng sao --- */ ?>
    <div class="summary-right">
        <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
            <?php
            $count   = isset( $counts[ $i ] ) ? intval( $counts[ $i ] ) : 0;
            $percent = ( $total > 0 ) ? round( ( $count / $total ) * 100, 1 ) : 0;
            ?>
            <div class="summary-row" onclick="jQuery('.filter-btn[data-filter=<?php echo intval( $i ); ?>]').trigger('click');">
                <span class="row-label"><?php echo esc_html( sprintf( __( '%d sao', 'review-kit' ), $i ) ); ?></span>
                <div class="row-bar-bg">
                    <div class="row-bar-fill reviewkit-progress-bar" style="width: 0;" data-percent="<?php echo esc_attr( $percent ); ?>%"></div>
                </div>
                <span class="row-count"><?php echo intval( $count ); ?></span>
            </div>
        <?php endfor; ?>
    </div>

</div><?php // .reviewkit-summary-box ?>
