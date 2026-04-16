<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Class cấu hình tab "Tools" và tích hợp các settings Pro vào Admin.
 */
class ReviewKit_Admin_Pro
{

    public function __construct()
    {
        // Thêm tab "Tools" vào admin settings
        add_action('reviewkit_admin_tabs', array($this, 'add_tools_tab'));

        // Đăng ký settings mới
        add_action('admin_init', array($this, 'register_pro_settings'));

        // Render tab tools
        add_action('reviewkit_admin_tab_content_tools', array($this, 'render_tools_tab'));
    }

    public function register_pro_settings()
    {
        // --- Blacklist ---
        register_setting('reviewkit_pro_group', 'reviewkit_blacklist_keywords');

        // --- Report Review ---
        register_setting('reviewkit_pro_group', 'reviewkit_report_threshold');

        // --- Email Reminders ---
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_enabled');
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_days');
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_subject');
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_body');
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_from_name');
        register_setting('reviewkit_pro_group', 'reviewkit_reminder_from_email');

        // --- Cleanup ---
        register_setting('reviewkit_pro_group', 'reviewkit_delete_media_with_review');
    }

    public function add_tools_tab()
    {
        // Được gọi từ render_settings_page của ReviewKit_Admin_Settings
    }

    public function render_tools_tab()
    {
        $active_sub = isset($_GET['sub']) ? sanitize_key($_GET['sub']) : 'blacklist';
        $base_url = admin_url('admin.php?page=reviewkit-settings&tab=tools');

        // Import/Export thông báo
        if (isset($_GET['import_success'])) {
            printf('<div class="notice notice-success is-dismissible"><p>✅ Đã nhập thành công <strong>%d</strong> đánh giá. (Lỗi: %d)</p></div>', intval($_GET['import_success']), intval($_GET['import_errors'] ?? 0));
        }
        if (isset($_GET['import_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Lỗi nhập file. Vui lòng kiểm tra lại định dạng CSV.</p></div>';
        }
        ?>
        <style>
            .reviewkit-sub-tabs {
                display: flex;
                gap: 8px;
                margin-top: 10px;
                margin-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }

            .reviewkit-sub-tabs a {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                text-decoration: none;
                border-radius: 4px 4px 0 0;
                background: #f0f0f0;
                color: #333;
                font-weight: 500;
                transition: all 0.2s;
            }

            .reviewkit-sub-tabs a .dashicons {
                font-size: 17px;
                width: 17px;
                height: 17px;
                color: #666;
            }

            .reviewkit-sub-tabs a.active {
                background: #ee4d2d;
                color: #fff;
            }

            .reviewkit-sub-tabs a.active .dashicons {
                color: #fff;
            }

            .reviewkit-pro-box {
                background: #fff;
                padding: 20px 24px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .reviewkit-pro-box h3 {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 0;
                font-size: 16px;
                color: #1d2327;
            }

            .reviewkit-pro-box h3 .dashicons {
                color: #ee4d2d;
            }

            .reviewkit-pro-box p.desc {
                color: #666;
                font-size: 13px;
                margin-top: 4px;
            }

            .reviewkit-pro-box .form-table {
                margin-top: 0;
            }

            .reviewkit-btn-export {
                display: inline-block;
                padding: 8px 18px;
                background: #2271b1;
                color: #fff;
                border-radius: 4px;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
            }

            .reviewkit-btn-export:hover {
                background: #135e96;
                color: #fff;
            }

            .reviewkit-btn-export .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
        </style>

        <div class="reviewkit-sub-tabs">
            <a href="<?php echo esc_url($base_url . '&sub=analytics'); ?>"
                class="<?php echo $active_sub === 'analytics' ? 'active' : ''; ?>"><span
                    class="dashicons dashicons-chart-area"></span> Thống kê</a>
            <a href="<?php echo esc_url($base_url . '&sub=blacklist'); ?>"
                class="<?php echo $active_sub === 'blacklist' ? 'active' : ''; ?>"><span
                    class="dashicons dashicons-shield"></span> Từ khóa cấm</a>
            <a href="<?php echo esc_url($base_url . '&sub=report'); ?>"
                class="<?php echo $active_sub === 'report' ? 'active' : ''; ?>"><span
                    class="dashicons dashicons-flag"></span> Báo cáo vi phạm</a>
            <a href="<?php echo esc_url($base_url . '&sub=reminder'); ?>"
                class="<?php echo $active_sub === 'reminder' ? 'active' : ''; ?>"><span
                    class="dashicons dashicons-email-alt"></span> Nhắc đánh giá</a>
            <a href="<?php echo esc_url($base_url . '&sub=import'); ?>"
                class="<?php echo $active_sub === 'import' ? 'active' : ''; ?>"><span
                    class="dashicons dashicons-database-import"></span> Nhập/Xuất CSV</a>
        </div>

        <?php if ($active_sub === 'analytics'): ?>
            <?php $this->render_analytics_tab(); ?>

        <?php elseif ($active_sub === 'blacklist'): ?>
            <div class="reviewkit-pro-box">
                <h3><span class="dashicons dashicons-shield"></span> Danh sách từ khóa cấm (Blacklist)</h3>
                <p class="desc">Các đánh giá chứa những từ này sẽ bị từ chối tự động. Phân cách bằng dấu phẩy.</p>
                <form method="post" action="options.php">
                    <?php settings_fields('reviewkit_pro_group'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="reviewkit_blacklist_keywords">Từ khóa cấm</label></th>
                            <td>
                                <textarea name="reviewkit_blacklist_keywords" id="reviewkit_blacklist_keywords" rows="5"
                                    style="width:100%;max-width:500px;"><?php echo esc_textarea(get_option('reviewkit_blacklist_keywords', '')); ?></textarea>
                                <p class="description">Ví dụ: <code>shopee, lazada, đối thủ, spam, xxx</code></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Lưu từ khóa'); ?>
                </form>
            </div>

        <?php elseif ($active_sub === 'report'): ?>
            <div class="reviewkit-pro-box">
                <h3><span class="dashicons dashicons-flag"></span> Cấu hình Báo cáo vi phạm</h3>
                <p class="desc">Admin sẽ nhận email cảnh báo khi một đánh giá bị báo cáo đủ số lần quy định.</p>
                <form method="post" action="options.php">
                    <?php settings_fields('reviewkit_pro_group'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="reviewkit_report_threshold">Ngưỡng cảnh báo</label></th>
                            <td>
                                <input type="number" name="reviewkit_report_threshold" id="reviewkit_report_threshold"
                                    value="<?php echo esc_attr(get_option('reviewkit_report_threshold', 3)); ?>" min="1"
                                    class="small-text" />
                                <span class="description"> lần báo cáo</span>
                                <p class="description">Khi đánh giá bị báo cáo đủ số lần này, một email cảnh báo sẽ được gửi tới địa
                                    chỉ quản trị.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Lưu cấu hình'); ?>
                </form>
            </div>

            <div class="reviewkit-pro-box">
                <h3>Xem danh sách bị báo cáo nhiều nhất</h3>
                <?php $this->render_reported_reviews_table(); ?>
            </div>

        <?php elseif ($active_sub === 'reminder'): ?>
            <div class="reviewkit-pro-box">
                <h3><span class="dashicons dashicons-email-alt"></span> Email nhắc nhở đánh giá</h3>
                <p class="desc">Tự động gửi email sau N ngày khi đơn hàng chuyển sang "Hoàn thành".</p>
                <form method="post" action="options.php">
                    <?php settings_fields('reviewkit_pro_group'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="reviewkit_reminder_enabled">Bật tính năng</label></th>
                            <td><input type="checkbox" name="reviewkit_reminder_enabled" value="1" <?php checked(1, get_option('reviewkit_reminder_enabled', 0)); ?> /> Cho phép gửi email nhắc nhở</td>
                        </tr>
                        <tr>
                            <th><label for="reviewkit_reminder_days">Gửi sau</label></th>
                            <td>
                                <input type="number" name="reviewkit_reminder_days" id="reviewkit_reminder_days"
                                    value="<?php echo esc_attr(get_option('reviewkit_reminder_days', 3)); ?>" min="1"
                                    class="small-text" />
                                <span class="description"> ngày kể từ khi đơn hàng hoàn thành</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reviewkit_reminder_from_name">Tên người gửi</label></th>
                            <td><input type="text" name="reviewkit_reminder_from_name" id="reviewkit_reminder_from_name"
                                    value="<?php echo esc_attr(get_option('reviewkit_reminder_from_name', get_bloginfo('name'))); ?>"
                                    class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="reviewkit_reminder_from_email">Email gửi đi</label></th>
                            <td><input type="email" name="reviewkit_reminder_from_email" id="reviewkit_reminder_from_email"
                                    value="<?php echo esc_attr(get_option('reviewkit_reminder_from_email', get_option('admin_email'))); ?>"
                                    class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="reviewkit_reminder_subject">Tiêu đề Email</label></th>
                            <td>
                                <input type="text" name="reviewkit_reminder_subject" id="reviewkit_reminder_subject"
                                    value="<?php echo esc_attr(get_option('reviewkit_reminder_subject', '[{site_name}] Bạn có hài lòng với đơn hàng #{order_id}?')); ?>"
                                    class="large-text" />
                                <p class="description">Biến: <code>{site_name}</code>, <code>{order_id}</code>,
                                    <code>{customer_name}</code>, <code>{product_name}</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reviewkit_reminder_body">Nội dung Email (HTML)</label></th>
                            <td>
                                <textarea name="reviewkit_reminder_body" id="reviewkit_reminder_body" rows="12"
                                    style="width:100%;max-width:600px;font-family:monospace;"><?php echo esc_textarea(get_option('reviewkit_reminder_body', '')); ?></textarea>
                                <p class="description">Biến: <code>{customer_name}</code>, <code>{product_name}</code>,
                                    <code>{product_url}</code>, <code>{review_link}</code>, <code>{site_name}</code>,
                                    <code>{order_id}</code></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Lưu cấu hình Email'); ?>
                </form>
                <hr>
                <h4><span class="dashicons dashicons-clock"></span> Trạng thái hàng đợi</h4>
                <?php
                $queue = get_option('reviewkit_reminder_queue', []);
                $pending_count = count(array_filter($queue, fn($i) => !$i['sent']));
                $sent_count = count(array_filter($queue, fn($i) => $i['sent']));
                ?>
                <p><span class="dashicons dashicons-email"></span> Đang chờ gửi: <strong><?php echo $pending_count; ?></strong>
                    email &nbsp;|&nbsp; <span class="dashicons dashicons-yes" style="color:#2ea2cc;"></span> Đã gửi:
                    <strong><?php echo $sent_count; ?></strong> email</p>
                <p class="description">Cron job chạy mỗi ngày 1 lần để xử lý hàng đợi.</p>
            </div>

        <?php elseif ($active_sub === 'import'): ?>
            <div class="reviewkit-pro-box">
                <h3><span class="dashicons dashicons-database-export"></span> Xuất đánh giá ra CSV</h3>
                <p class="desc">Tải xuống toàn bộ đánh giá dưới dạng file CSV (UTF-8 BOM tương thích Excel).</p>
                <?php $export_url = wp_nonce_url(admin_url('admin-post.php?action=reviewkit_export_reviews'), 'reviewkit_export_nonce'); ?>
                <a href="<?php echo esc_url($export_url); ?>" class="reviewkit-btn-export"><span
                        class="dashicons dashicons-download"></span> Xuất tất cả đánh giá</a>
                &nbsp;&nbsp;
                <a href="<?php echo esc_url($export_url . '&product_id=' . (isset($_GET['pid']) ? intval($_GET['pid']) : 0)); ?>"
                    class="reviewkit-btn-export" style="background:#555;">Xuất theo sản phẩm (nhập Product ID vào URL: &pid=123)</a>
            </div>

            <div class="reviewkit-pro-box">
                <h3><span class="dashicons dashicons-database-import"></span> Nhập đánh giá từ CSV</h3>
                <p class="desc">File CSV phải có các cột theo thứ tự:
                    <code>product_id, author, email, rating (1-5), content, date (YYYY-MM-DD HH:MM:SS)</code></p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="reviewkit_import_reviews" />
                    <?php wp_nonce_field('reviewkit_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="reviewkit_import_file">Chọn file CSV</label></th>
                            <td>
                                <input type="file" name="reviewkit_import_file" id="reviewkit_import_file" accept=".csv" required />
                                <p class="description">Dung lượng tối đa theo cấu hình của server (thường là 8MB - 32MB)</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Bắt đầu nhập', 'review-kit'), 'primary', 'submit', false, array('class' => 'button-primary')); ?>
                    <p class="description" style="color:#d63638;margin-top:10px; display:flex; align-items:center; gap:5px;"><span
                            class="dashicons dashicons-warning" style="font-size:16px;width:16px;height:16px;"></span> ⚠️ Lưu ý:
                        Hành động này không thể hoàn tác. Hãy sao lưu database trước khi nhập.</p>
                </form>

                <hr style="margin-top:20px;">
                <h4><span class="dashicons dashicons-media-document"></span>File CSV mẫu</h4>
                <pre style="background:#f0f0f0;padding:12px;border-radius:4px;font-size:12px;">product_id,author,email,rating,content,date
            123,Nguyễn Văn A,a@example.com,5,"Sản phẩm rất tốt, giao hàng nhanh!",2024-01-15 10:30:00
            123,Trần Thị B,b@example.com,4,"Chất lượng ổn, sẽ mua lại.",2024-01-16 14:00:00</pre>
            </div>
        <?php endif; ?>

        <div class="reviewkit-pro-box" style="border-top: 3px solid #ee4d2d; margin-top: 30px;">
            <h3><span class="dashicons dashicons-trash"></span> Cấu hình Dọn dẹp hệ thống</h3>
            <p class="desc">Các thiết lập tối ưu dung lượng và quản lý dữ liệu Media.</p>
            <form method="post" action="options.php">
                <?php settings_fields('reviewkit_pro_group'); ?>
                <table class="form-table">
                    <tr>
                        <th style="width:200px;"><label for="reviewkit_delete_media_with_review">Xóa File vật lý</label></th>
                        <td>
                            <input type="checkbox" name="reviewkit_delete_media_with_review" id="reviewkit_delete_media_with_review"
                                value="1" <?php checked(1, get_option('reviewkit_delete_media_with_review', 0)); ?> />
                            <strong>Tự động xóa vĩnh viễn Ảnh/Video trong Thư viện Media khi xóa đánh giá.</strong>
                            <p class="description">Mặc định WordPress sẽ giữ lại file trong kho Media ngay cả khi bình luận bị
                                xóa. Bật tính năng này nếu bạn muốn dọn dẹp triệt để để tiết kiệm dung lượng hosting.</p>
                            <p class="description" style="color:#d63638;"><span class="dashicons dashicons-warning"
                                    style="font-size:14px;"></span> ⚠️ Lưu ý: Khi đánh giá bị xóa vĩnh viễn (xóa trong thùng
                                rác), các file đính kèm cũng sẽ bị xóa và không thể khôi phục.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Cập nhật thiết lập dọn dẹp'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render tab Thống kê (Analytics)
     */
    private function render_analytics_tab()
    {
        $data = $this->get_analytics_data();

        // Enqueue Chart.js (CDN)
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        ?>
        <style>
            .reviewkit-analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .analytics-card {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                border: 1px solid #e0e0e0;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                text-align: center;
            }

            .analytics-card .label {
                display: block;
                font-size: 14px;
                color: #666;
                margin-bottom: 10px;
                font-weight: 500;
            }

            .analytics-card .value {
                display: block;
                font-size: 28px;
                font-weight: 700;
                color: #ee4d2d;
            }

            .analytics-card .sub-label {
                display: block;
                font-size: 12px;
                color: #999;
                margin-top: 5px;
            }

            .reviewkit-charts-row {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }

            @media (max-width: 900px) {
                .reviewkit-charts-row {
                    grid-template-columns: 1fr;
                }
            }

            .chart-container {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                border: 1px solid #e0e0e0;
            }

            .chart-container h4 {
                margin-top: 0;
                margin-bottom: 20px;
                font-size: 15px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .chart-wrapper {
                position: relative;
                height: 320px;
                width: 100%;
            }

            .top-products-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            .top-products-table th,
            .top-products-table td {
                padding: 12px;
                border-bottom: 1px solid #f0f0f0;
                text-align: left;
            }

            .top-products-table th {
                font-weight: 600;
                color: #555;
                font-size: 13px;
            }

            .top-products-table td {
                font-size: 13px;
            }

            .rank-number {
                display: inline-block;
                width: 22px;
                height: 22px;
                line-height: 22px;
                text-align: center;
                background: #f0f0f0;
                border-radius: 50%;
                font-size: 11px;
                font-weight: bold;
                margin-right: 8px;
            }
        </style>

        <div class="reviewkit-analytics-grid">
            <div class="analytics-card">
                <span class="label">Tổng đánh giá</span>
                <span class="value"><?php echo number_format($data['total_reviews']); ?></span>
                <span class="sub-label">Tất cả thời gian</span>
            </div>
            <div class="analytics-card">
                <span class="label">Điểm trung bình</span>
                <span class="value"><?php echo number_format($data['avg_rating'], 1); ?> <span
                        style="font-size:18px;">★</span></span>
                <span class="sub-label">Dựa trên <?php echo $data['total_reviews']; ?> lượt</span>
            </div>
            <div class="analytics-card">
                <span class="label">Tỷ lệ hài lòng</span>
                <span class="value"><?php echo $data['satisfaction_rate']; ?>%</span>
                <span class="sub-label">Đánh giá 4 & 5 sao</span>
            </div>
            <div class="analytics-card">
                <span class="label">Ảnh & Video</span>
                <span class="value"><?php echo $data['total_media']; ?></span>
                <span class="sub-label">Tệp đính kèm được up</span>
            </div>
        </div>

        <div class="reviewkit-charts-row">
            <div class="chart-container">
                <h4><span class="dashicons dashicons-chart-line"></span> Xu hướng đánh giá (6 tháng qua)</h4>
                <div class="chart-wrapper">
                    <canvas id="reviewTrendChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <h4><span class="dashicons dashicons-chart-pie"></span> Phân bổ số sao</h4>
                <div class="chart-wrapper">
                    <canvas id="ratingDistChart"></canvas>
                </div>
            </div>
        </div>

        <div class="reviewkit-pro-box">
            <h3><span class="dashicons dashicons-star-filled"></span> Top 5 Sản phẩm được đánh giá tốt nhất</h3>
            <table class="top-products-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số đánh giá</th>
                        <th>Điểm trung bình</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['top_products'])): ?>
                        <tr>
                            <td colspan="3">Chưa có đủ dữ liệu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['top_products'] as $index => $p): ?>
                            <tr>
                                <td>
                                    <span class="rank-number"><?php echo $index + 1; ?></span>
                                    <a href="<?php echo get_permalink($p->comment_post_ID); ?>" target="_blank"
                                        style="text-decoration:none;">
                                        <?php echo get_the_title($p->comment_post_ID); ?>
                                    </a>
                                </td>
                                <td><?php echo $p->review_count; ?> lượt</td>
                                <td><strong style="color:#ee4d2d;"><?php echo round($p->avg_rate, 1); ?> ★</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // 1. Line Chart: Trend
                const trendCtx = document.getElementById('reviewTrendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($data['trend']['labels']); ?>,
                        datasets: [{
                            label: 'Số đánh giá',
                            data: <?php echo json_encode($data['trend']['values']); ?>,
                            borderColor: '#ee4d2d',
                            backgroundColor: 'rgba(238, 77, 45, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#ee4d2d'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });

                // 2. Doughnut Chart: Distribution
                const distCtx = document.getElementById('ratingDistChart').getContext('2d');
                new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['5 sao', '4 sao', '3 sao', '2 sao', '1 sao'],
                        datasets: [{
                            data: <?php echo json_encode(array_values($data['dist'])); ?>,
                            backgroundColor: ['#27ae60', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } } }
                        }
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Lấy dữ liệu analytics từ Database
     */
    private function get_analytics_data()
    {
        global $wpdb;

        // 1. Tổng quan
        $total_reviews = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'review' AND comment_approved = '1'");
        $avg_rating = $wpdb->get_var("SELECT AVG(meta_value) FROM {$wpdb->commentmeta} WHERE meta_key = 'rating' AND comment_id IN (SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type = 'review' AND comment_approved = '1')");

        // 2. Phân bổ sao
        $dist_rows = $wpdb->get_results("SELECT meta_value as rating, COUNT(*) as count FROM {$wpdb->commentmeta} WHERE meta_key = 'rating' GROUP BY meta_value");
        $dist = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0];
        $satisfaction_count = 0;
        foreach ($dist_rows as $row) {
            $r = (string) intval($row->rating);
            if (isset($dist[$r])) {
                $dist[$r] = intval($row->count);
                if ($r === '5' || $r === '4')
                    $satisfaction_count += intval($row->count);
            }
        }
        $satisfaction_rate = ($total_reviews > 0) ? round(($satisfaction_count / $total_reviews) * 100) : 0;

        // 3. Ảnh & Video
        $total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE meta_key = 'review_image_ids' AND meta_value != ''");
        $total_videos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE meta_key = 'review_video_ids' AND meta_value != ''");

        // 4. Xu hướng 6 tháng
        $trend_labels = [];
        $trend_values = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'review' AND comment_approved = '1' AND comment_date LIKE %s",
                $month . '%'
            ));
            $trend_labels[] = date('m/Y', strtotime("-$i months"));
            $trend_values[] = intval($count);
        }

        // 5. Top sản phẩm
        $top_products = $wpdb->get_results(
            "SELECT comment_post_ID, COUNT(*) as review_count, AVG(CAST(meta_value AS DECIMAL(3,2))) as avg_rate 
             FROM {$wpdb->comments} as c
             JOIN {$wpdb->commentmeta} as m ON c.comment_ID = m.comment_id
             WHERE c.comment_type = 'review' AND c.comment_approved = '1' AND m.meta_key = 'rating'
             GROUP BY comment_post_ID
             HAVING review_count >= 1
             ORDER BY avg_rate DESC, review_count DESC
             LIMIT 5"
        );

        return [
            'total_reviews' => intval($total_reviews),
            'avg_rating' => floatval($avg_rating),
            'satisfaction_rate' => $satisfaction_rate,
            'total_media' => intval($total_images + $total_videos),
            'dist' => array_reverse($dist, true), // 5, 4, 3, 2, 1
            'trend' => ['labels' => $trend_labels, 'values' => $trend_values],
            'top_products' => $top_products
        ];
    }

    /**
     * Hiển thị bảng các đánh giá bị báo cáo nhiều nhất.
     */
    private function render_reported_reviews_table()
    {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT cm.comment_id, cm.meta_value as report_count, c.comment_author, c.comment_post_ID, c.comment_content
             FROM {$wpdb->commentmeta} as cm
             JOIN {$wpdb->comments} as c ON cm.comment_id = c.comment_ID
             WHERE cm.meta_key = 'reviewkit_report_count' AND CAST(cm.meta_value AS UNSIGNED) > 0
             ORDER BY CAST(cm.meta_value AS UNSIGNED) DESC LIMIT 20"
        );

        if (empty($results)) {
            echo '<p style="color:#888;">Chưa có đánh giá nào bị báo cáo.</p>';
            return;
        }
        ?>
        <table class="widefat fixed striped" style="margin-top:10px;">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:120px;">Lần báo cáo</th>
                    <th style="width:150px;">Tác giả</th>
                    <th>Nội dung (trích dẫn)</th>
                    <th style="width:140px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo intval($row->comment_id); ?></td>
                        <td><span style="color:#c00;font-weight:bold;"><?php echo intval($row->report_count); ?> lần</span></td>
                        <td><?php echo esc_html($row->comment_author); ?></td>
                        <td><?php echo esc_html(mb_substr($row->comment_content, 0, 100)); ?>...</td>
                        <td>
                            <a href="<?php echo admin_url('comment.php?action=editcomment&c=' . intval($row->comment_id)); ?>"
                                class="button button-small">Xem & Duyệt</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
