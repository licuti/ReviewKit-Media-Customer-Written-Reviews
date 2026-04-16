<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ReviewKit_Admin_Settings {

    private $active_tab;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    // 1. TẠO MENU ADMIN
    public function add_admin_menu() {
        add_menu_page(
            __( 'ReviewKit Settings', 'review-kit' ), // Page Title
            __( 'ReviewKit', 'review-kit' ),           // Menu Title
            'manage_options',   // Capability
            'reviewkit-settings',    // Menu Slug
            array( $this, 'render_settings_page' ), // Callback
            'dashicons-star-half', // Icon
            58 // Position (Sau Woo Products)
        );
    }

    // 2. ENQUEUE ASSETS (Cho Color Picker + Admin CSS)
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_reviewkit-settings' !== $hook ) return;

        // Load Color Picker của WordPress
        wp_enqueue_style( 'wp-color-picker' );

        // Admin Settings CSS riêng
        wp_enqueue_style(
            'reviewkit-admin-settings-css',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-settings.css',
            array(),
            defined( 'ReviewKit_VERSION' ) ? ReviewKit_VERSION : '1.1.0'
        );

        wp_enqueue_script( 'reviewkit-admin-settings-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-settings.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
    }

    // 3. ĐĂNG KÝ CÁC TÙY CHỌN (SETTINGS API)
    public function register_settings() {
        
        // --- TAB 1: CÀI ĐẶT CHUNG (GENERAL) ---
        register_setting( 'reviewkit_general_group', 'reviewkit_moderation_mode' );
        register_setting( 'reviewkit_general_group', 'reviewkit_enable_voting' );
        register_setting( 'reviewkit_general_group', 'reviewkit_require_login' );
        
        // --- MÀU SẮC (COLORS) ---
        register_setting( 'reviewkit_display_group', 'reviewkit_primary_color' );
        register_setting( 'reviewkit_display_group', 'reviewkit_stars_color' );
        register_setting( 'reviewkit_display_group', 'reviewkit_border_color' );

        add_settings_section( 'reviewkit_general_section', __( 'Cấu hình cơ bản', 'review-kit' ), null, 'reviewkit_general_page' );

        add_settings_field( 'reviewkit_moderation_mode', __( 'Chế độ duyệt bài', 'review-kit' ), array( $this, 'render_select_moderation' ), 'reviewkit_general_page', 'reviewkit_general_section' );
        add_settings_field( 'reviewkit_enable_voting', __( 'Tính năng Hữu ích (Like)', 'review-kit' ), array( $this, 'render_checkbox' ), 'reviewkit_general_page', 'reviewkit_general_section', array( 'id' => 'reviewkit_enable_voting' ) );
        add_settings_field(  'reviewkit_require_login' , __( 'Yêu cầu đăng nhập', 'review-kit' ), array( $this, 'render_checkbox' ), 'reviewkit_general_page', 'reviewkit_general_section', array( 
                'id' => 'reviewkit_require_login',
                'default' => 0 // Mặc định là TẮT (cho phép khách gửi review)
        ) 
);


        // --- TAB 2: GIỚI HẠN UPLOAD (LIMITS) ---
        register_setting( 'reviewkit_limits_group', 'reviewkit_enable_uploads' );
        register_setting( 'reviewkit_limits_group', 'reviewkit_max_images' );
        register_setting( 'reviewkit_limits_group', 'reviewkit_max_file_size' );
        
        // --- MỚI: VIDEO SETTINGS ---
        register_setting( 'reviewkit_limits_group', 'reviewkit_enable_video_upload' );
        register_setting( 'reviewkit_limits_group', 'reviewkit_max_video_size' );
        register_setting( 'reviewkit_limits_group', 'reviewkit_allowed_video_types' );

        add_settings_section( 'reviewkit_limits_section', __( 'Cấu hình hình ảnh và Video', 'review-kit' ), null, 'reviewkit_limits_page' );

        add_settings_field( 'reviewkit_enable_uploads', __( 'Cho phép Upload ảnh', 'review-kit' ), array( $this, 'render_checkbox' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_enable_uploads' ) );
        add_settings_field( 'reviewkit_max_images', __( 'Số ảnh tối đa', 'review-kit' ), array( $this, 'render_number' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_max_images', 'desc' => __( 'ảnh/review', 'review-kit' ) ) );
        add_settings_field( 'reviewkit_max_file_size', __( 'Dung lượng ảnh tối đa', 'review-kit' ), array( $this, 'render_number' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_max_file_size', 'desc' => 'MB' ) );

        add_settings_field( 'reviewkit_enable_video_upload', __( 'Cho phép Upload Video', 'review-kit' ), array( $this, 'render_checkbox' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_enable_video_upload', 'default' => 0 ) );
        add_settings_field( 'reviewkit_max_video_size', __( 'Dung lượng Video tối đa', 'review-kit' ), array( $this, 'render_number' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_max_video_size', 'default' => 10, 'desc' => 'MB' ) );
        add_settings_field( 'reviewkit_allowed_video_types', __( 'Định dạng Video cho phép', 'review-kit' ), array( $this, 'render_text' ), 'reviewkit_limits_page', 'reviewkit_limits_section', array( 'id' => 'reviewkit_allowed_video_types', 'default' => 'mp4,webm,mov', 'desc' => __( 'Phân cách bằng dấu phẩy (vd: mp4,webm,mov)', 'review-kit' ) ) );


        // --- TAB 3: HIỂN THỊ (DISPLAY) ---
        register_setting( 'reviewkit_display_group', 'reviewkit_verified_text' );
        register_setting( 'reviewkit_display_group', 'reviewkit_verified_color' );

        add_settings_section( 'reviewkit_display_section', __( 'Giao diện', 'review-kit' ), null, 'reviewkit_display_page' );

        add_settings_field( 'reviewkit_verified_text', __( 'Chữ Badge xác thực', 'review-kit' ), array( $this, 'render_text' ), 'reviewkit_display_page', 'reviewkit_display_section', array( 'id' => 'reviewkit_verified_text' ) );
        add_settings_field( 'reviewkit_verified_color', __( 'Màu Badge xác thực', 'review-kit' ), array( $this, 'render_color_picker' ), 'reviewkit_display_page', 'reviewkit_display_section', array( 'id' => 'reviewkit_verified_color' ) );


        register_setting( 'reviewkit_display_group', 'reviewkit_per_page' );
        register_setting( 'reviewkit_display_group', 'reviewkit_pagination_style' ); // Thêm key mới


        // Đăng ký Lightbox (MỚI THÊM) - NẾU THIẾU DÒNG NÀY SẼ KHÔNG LƯU ĐƯỢC
        register_setting( 'reviewkit_display_group', 'reviewkit_lightbox_layout' );
        register_setting( 'reviewkit_display_group', 'reviewkit_lightbox_toolbar' );
        register_setting( 'reviewkit_display_group', 'reviewkit_lightbox_theme' );

        add_settings_section( 'reviewkit_display_section', __( 'Cấu hình Hiển thị', 'review-kit' ), null, 'reviewkit_display_page' );

        // 1. Số đánh giá mỗi trang
        add_settings_field( 
            'reviewkit_per_page', 
            __( 'Số đánh giá mỗi trang', 'review-kit' ), 
            array( $this, 'render_number' ), 
            'reviewkit_display_page', 
            'reviewkit_display_section', 
            array( 'id' => 'reviewkit_per_page', 'default' => 5, 'desc' => __( 'Số lượng đánh giá hiển thị trong 1 lần tải.', 'review-kit' ) ) 
        );
        
        // 2. Kiểu Phân Trang
        add_settings_field( 
            'reviewkit_pagination_style', 
            __( 'Kiểu Phân Trang', 'review-kit' ), 
            array( $this, 'render_select' ), 
            'reviewkit_display_page', 
            'reviewkit_display_section', 
            array( 
                'id' => 'reviewkit_pagination_style', 
                'default' => 'numbered_ajax',
                'options' => array(
                    'numbered_ajax' => __( 'Phân trang số (1, 2, 3...)', 'review-kit' ),
                    'load_more'     => __( 'Tải thêm (Load More Button)', 'review-kit' )
                ),
                'desc' => __( 'Chọn kiểu hiển thị cho đánh giá.', 'review-kit' )
            ) 
        );


        // ======
        add_settings_section( 'reviewkit_lightbox_section', __( 'Cấu hình Lightbox (Popup)', 'review-kit' ), null, 'reviewkit_display_page' );

        // 2. Vị trí Thumbnails (Layout)
        add_settings_field( 
            'reviewkit_lightbox_layout', 
            __( 'Kiểu hiển thị Thumbnails', 'review-kit' ), 
            array( $this, 'render_select' ), 
            'reviewkit_display_page', 
            'reviewkit_lightbox_section', 
            array( 
                'id' => 'reviewkit_lightbox_layout',
                'default' => 'modern',
                'options' => array(
                    'modern'     => 'Modern (' . __( 'Mặc định', 'review-kit' ) . ')',
                    'classic'    => __( 'Classic', 'review-kit' ),
                    'scrollable' => __( 'Scrollable', 'review-kit' ),
                    'vertical'   => __( 'Vertical Thumbnails', 'review-kit' ),
                    ''           => __( 'Không hiển thị Thumbnails', 'review-kit' )
                ),
                'desc' => __( 'Chọn "Dọc" cho màn hình rộng, "Ngang" cho phong cách cổ điển.', 'review-kit' )
            ) 
        );

        // ====== MÀU SẮC ======
        add_settings_section( 'reviewkit_color_section', __( 'Màu sắc giao diện', 'review-kit' ), null, 'reviewkit_display_page' );

        add_settings_field( 
            'reviewkit_primary_color', 
            __( 'Màu chủ đạo (Nút, Badge)', 'review-kit' ), 
            array( $this, 'render_color_picker' ), 
            'reviewkit_display_page', 
            'reviewkit_color_section', 
            array( 'id' => 'reviewkit_primary_color', 'default' => '#ee4d2d' ) 
        );

        add_settings_field( 
            'reviewkit_stars_color', 
            __( 'Màu ngôi sao & Thanh biểu đồ', 'review-kit' ), 
            array( $this, 'render_color_picker' ), 
            'reviewkit_display_page', 
            'reviewkit_color_section', 
            array( 'id' => 'reviewkit_stars_color', 'default' => '#f59e0b' ) 
        );

        add_settings_field( 
            'reviewkit_border_color', 
            __( 'Màu đường viền card', 'review-kit' ), 
            array( $this, 'render_color_picker' ), 
            'reviewkit_display_page', 
            'reviewkit_color_section', 
            array( 'id' => 'reviewkit_border_color', 'default' => '#e2e8f0' ) 
        );

        add_settings_field( 
            'reviewkit_lightbox_theme', 
            __( 'Giao diện (Theme)', 'review-kit' ), 
            array( $this, 'render_select' ), 
            'reviewkit_display_page', 
            'reviewkit_lightbox_section', 
            array( 
                'id' => 'reviewkit_lightbox_theme', 
                'default' => 'dark',
                'options' => array(
                    'dark'  => __( 'Dark theme (Tối)', 'review-kit' ),
                    'light' => __( 'Light theme (Sáng)', 'review-kit' ),
                    'auto'  => __( 'Auto (Theo thiết bị)', 'review-kit' )
                ),
                'desc' => __( 'Chọn màu nền cho Popup ảnh.', 'review-kit' )
            ) 
        );

        // 3. Bật/Tắt Toolbar
        add_settings_field( 
            'reviewkit_lightbox_toolbar', 
            __( 'Hiển thị thanh công cụ (Toolbar)', 'review-kit' ), 
            array( $this, 'render_checkbox' ), 
            'reviewkit_display_page', 
            'reviewkit_lightbox_section', 
            array( 'id' => 'reviewkit_lightbox_toolbar', 'default' => 1 ) 
        );


    }

    // --- 4. CALLBACKS HIỂN THỊ HTML INPUT ---

    // Input Checkbox
    public function render_checkbox( $args ) {
        // Lấy giá trị mặc định nếu có truyền vào, nếu không thì là 0
        $default_val = isset($args['default']) ? $args['default'] : 0;
        
        // Lấy giá trị từ DB, nếu không có thì dùng mặc định
        $option = get_option( $args['id'], $default_val );
        
        echo '<input type="checkbox" name="' . $args['id'] . '" value="1" ' . checked( 1, $option, false ) . ' /> ' . __( 'Cho phép', 'review-kit' );
    }

    // Input Number
    public function render_number( $args ) {
        $default = isset($args['default']) ? $args['default'] : 0;
        $option = get_option( $args['id'], $default );
        echo '<input type="number" name="' . $args['id'] . '" value="' . esc_attr( $option ) . '" class="small-text" /> <span class="description">' . ( isset($args['desc']) ? $args['desc'] : '' ) . '</span>';
    }

    // Input Text
    public function render_text( $args ) {
        $default = isset($args['default']) ? $args['default'] : '';
        $option = get_option( $args['id'], $default );
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr( $option ) . '" class="regular-text" /> <p class="description">' . ( isset($args['desc']) ? $args['desc'] : '' ) . '</p>';
    }

    // Select: Moderation
    public function render_select_moderation() {
        $option = get_option( 'reviewkit_moderation_mode' );
        ?>
        <select name="reviewkit_moderation_mode">
            <option value="0" <?php selected( $option, '0' ); ?>><?php _e( 'Chờ duyệt (Pending)', 'review-kit' ); ?></option>
            <option value="1" <?php selected( $option, '1' ); ?>><?php _e( 'Tự động đăng (Approved)', 'review-kit' ); ?></option>
        </select>
        <p class="description"><?php _e( 'Chọn "Chờ duyệt" để kiểm tra nội dung trước khi hiển thị.', 'review-kit' ); ?></p>
        <?php
    }

    // Color Picker
    public function render_color_picker( $args ) {
        $default = isset($args['default']) ? $args['default'] : '#ee4d2d';
        $option = get_option( $args['id'], $default );
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr( $option ) . '" class="reviewkit-color-field" />';
    }


    // --- 5. RENDER TRANG ADMIN CHÍNH ---
    public function render_settings_page() {
        $this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

        // Map tab → [ title, icon, description ]
        $tab_meta = array(
            'general'    => array(
                'title' => __( 'Cài đặt chung', 'review-kit' ),
                'icon'  => 'dashicons-admin-settings',
                'desc'  => __( 'Cấu hình chế độ duyệt bài, bình chọn hữu ích và yêu cầu đăng nhập.', 'review-kit' ),
            ),
            'limits'     => array(
                'title' => __( 'Giới hạn & Upload', 'review-kit' ),
                'icon'  => 'dashicons-upload',
                'desc'  => __( 'Cấu hình số lượng, dung lượng tối đa cho ảnh và video khách hàng upload.', 'review-kit' ),
            ),
            'display'    => array(
                'title' => __( 'Hiển thị', 'review-kit' ),
                'icon'  => 'dashicons-visibility',
                'desc'  => __( 'Tuỳ chỉnh giao diện: màu sắc, badge xác thực, phân trang, lightbox popup.', 'review-kit' ),
            ),
            'shortcodes' => array(
                'title' => __( 'Mã ngắn', 'review-kit' ),
                'icon'  => 'dashicons-editor-code',
                'desc'  => __( 'Chèn các mã ngắn vào trang, bài viết hoặc widget để hiển thị khu vực đánh giá.', 'review-kit' ),
            ),
            'tools'      => array(
                'title' => __( 'Công cụ Pro', 'review-kit' ),
                'icon'  => 'dashicons-hammer',
                'desc'  => __( 'Các công cụ nâng cao: nhắc nhở email, blacklist, xóa file media khi xóa đánh giá.', 'review-kit' ),
            ),
        );

        $current = isset( $tab_meta[ $this->active_tab ] ) ? $tab_meta[ $this->active_tab ] : $tab_meta['general'];
        ?>
        <div class="wrap reviewkit-settings-wrap">
            <h1><?php _e( 'ReviewKit: Media & Customer Written Reviews', 'review-kit' ); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tab_meta as $slug => $meta ) : ?>
                <a href="?page=reviewkit-settings&tab=<?php echo esc_attr( $slug ); ?>"
                   class="nav-tab <?php echo $this->active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
                    <?php echo esc_html( $meta['title'] ); ?>
                </a>
                <?php endforeach; ?>
            </h2>

            <!-- Banner header mô tả tab hiện tại -->
            <div class="reviewkit-page-header">
                <h2>
                    <span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>"></span>
                    <?php echo esc_html( $current['title'] ); ?>
                </h2>
                <p><?php echo esc_html( $current['desc'] ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                if ( $this->active_tab === 'general' ) {
                    settings_fields( 'reviewkit_general_group' );
                    do_settings_sections( 'reviewkit_general_page' );
                } elseif ( $this->active_tab === 'limits' ) {
                    settings_fields( 'reviewkit_limits_group' );
                    do_settings_sections( 'reviewkit_limits_page' );
                } elseif ( $this->active_tab === 'display' ) {
                    settings_fields( 'reviewkit_display_group' );
                    do_settings_sections( 'reviewkit_display_page' );
                }

                if ( ! in_array( $this->active_tab, array( 'tools', 'shortcodes' ), true ) ) {
                    submit_button();
                }
                ?>
            </form>

            <?php if ( $this->active_tab === 'tools' ) : ?>
                <?php do_action( 'reviewkit_admin_tab_content_tools' ); ?>
            <?php endif; ?>

            <?php if ( $this->active_tab === 'shortcodes' ) : ?>
                <?php $this->render_shortcodes_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }


    public function render_select( $args ) {
        $option = get_option( $args['id'], $args['default'] ); // Lấy giá trị mặc định

        echo '<select name="' . $args['id'] . '">';
        foreach ( $args['options'] as $value => $label ) {
            echo '<option value="' . esc_attr($value) . '" ' . selected( $option, $value, false ) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';

        if ( isset($args['desc']) ) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }


    // --- TAB MÃ NGẮN (SHORTCODES) ---
    public function render_shortcodes_tab() {
        // Lấy một sản phẩm bất kỳ để làm ví dụ product_id
        $example_id = 0;
        $products = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) );
        if ( ! empty( $products ) ) {
            $example_id = $products[0]->get_id();
        }
        $pid = $example_id ?: '123';
        // Lưu ý: KHÔNG dùng <style> inline — tất cả style nằm trong admin-settings.css
        ?>
        <div class="reviewkit-sc-grid">

                <!-- Shortcode 1: Full Layout -->
                <div class="reviewkit-sc-card">
                    <div class="reviewkit-sc-card-header">
                        <h3><span class="dashicons dashicons-layout"></span> Toàn bộ giao diện đánh giá</h3>
                    </div>
                    <div class="reviewkit-sc-card-body">
                        <span class="reviewkit-sc-badge"><?php _e( 'Khuyên dùng', 'review-kit' ); ?></span>
                        <p><?php _e( 'Hiển thị đầy đủ 2 cột: <strong>Danh sách + bộ lọc</strong> ở trái và <strong>Thống kê + Form</strong> ở phải. Giống hệt tab Đánh giá mặc định của WooCommerce.', 'review-kit' ); ?></p>
                        <div class="reviewkit-sc-code-block">
                            <code>[reviewkit_reviews product_id="<?php echo $pid; ?>"]</code>
                            <button class="reviewkit-sc-copy-btn" onclick="reviewkitCopy(this)" data-code="[reviewkit_reviews product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'review-kit' ); ?></button>
                        </div>
                        <div class="reviewkit-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID của sản phẩm (tìm trong URL trang sửa sản phẩm). Nếu bỏ trống, tự động lấy sản phẩm hiện tại.', 'review-kit' ); ?></td></tr></table>
                        </div>
                        <div class="reviewkit-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'review-kit' ); ?></strong> <?php _e( 'Trang sản phẩm (Single Product) không cần điền <code>product_id</code> — plugin tự nhận diện.', 'review-kit' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 2: Form -->
                <div class="reviewkit-sc-card">
                    <div class="reviewkit-sc-card-header">
                        <h3><span class="dashicons dashicons-edit"></span> <?php _e( 'Chỉ Form gửi đánh giá', 'review-kit' ); ?></h3>
                    </div>
                    <div class="reviewkit-sc-card-body">
                        <p><?php _e( 'Hiển thị riêng form nhập sao, tên, email, nội dung và upload ảnh/video. Phù hợp để nhúng vào sidebar, landing page hoặc popup.', 'review-kit' ); ?></p>
                        <div class="reviewkit-sc-code-block">
                            <code>[reviewkit_review_form product_id="<?php echo $pid; ?>"]</code>
                            <button class="reviewkit-sc-copy-btn" onclick="reviewkitCopy(this)" data-code="[reviewkit_review_form product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'review-kit' ); ?></button>
                        </div>
                        <div class="reviewkit-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm cần gửi đánh giá (bắt buộc nếu dùng ngoài trang sản phẩm).', 'review-kit' ); ?></td></tr></table>
                        </div>
                        <div class="reviewkit-sc-tip reviewkit-admin-tip--warning"><strong><span class="dashicons dashicons-warning"></span> <?php _e( 'Lưu ý:', 'review-kit' ); ?></strong> <?php _e( 'Cài đặt <em>"Yêu cầu đăng nhập"</em> vẫn được áp dụng — khách chưa đăng nhập sẽ thấy thông báo.', 'review-kit' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 3: List -->
                <div class="reviewkit-sc-card">
                    <div class="reviewkit-sc-card-header">
                        <h3><span class="dashicons dashicons-list-view"></span> <?php _e( 'Chỉ Danh sách đánh giá', 'review-kit' ); ?></h3>
                    </div>
                    <div class="reviewkit-sc-card-body">
                        <p><?php _e( 'Hiển thị danh sách các bình luận kèm <strong>thanh lọc theo sao</strong>, lọc theo ảnh/đã mua và <strong>sắp xếp</strong> (mới nhất, hữu ích, điểm cao...).', 'review-kit' ); ?></p>
                        <div class="reviewkit-sc-code-block">
                            <code>[reviewkit_review_list product_id="<?php echo $pid; ?>"]</code>
                            <button class="reviewkit-sc-copy-btn" onclick="reviewkitCopy(this)" data-code="[reviewkit_review_list product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'review-kit' ); ?></button>
                        </div>
                        <div class="reviewkit-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm muốn lấy danh sách đánh giá.', 'review-kit' ); ?></td></tr></table>
                        </div>
                        <div class="reviewkit-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'review-kit' ); ?></strong> <?php printf( __( 'Ghép cặp với %s để tạo layout tuỳ chỉnh theo thiết kế riêng.', 'review-kit' ), '<code>[reviewkit_review_summary]</code>' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 4: Summary -->
                <div class="reviewkit-sc-card">
                    <div class="reviewkit-sc-card-header">
                        <h3><span class="dashicons dashicons-chart-bar"></span> <?php _e( 'Chỉ Thống kê đánh giá', 'review-kit' ); ?></h3>
                    </div>
                    <div class="reviewkit-sc-card-body">
                        <p><?php _e( 'Hiển thị <strong>điểm trung bình</strong> (Ví dụ: 4.8 / 5) cùng biểu đồ thanh tiến trình cho từng mức sao từ 5★ đến 1★.', 'review-kit' ); ?></p>
                        <div class="reviewkit-sc-code-block">
                            <code>[reviewkit_review_summary product_id="<?php echo $pid; ?>"]</code>
                            <button class="reviewkit-sc-copy-btn" onclick="reviewkitCopy(this)" data-code="[reviewkit_review_summary product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'review-kit' ); ?></button>
                        </div>
                        <div class="reviewkit-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm muốn xem thống kê đánh giá.', 'review-kit' ); ?></td></tr></table>
                        </div>
                        <div class="reviewkit-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'review-kit' ); ?></strong> <?php _e( 'Phù hợp để nhúng vào trang mô tả sản phẩm hoặc bảng so sánh sản phẩm.', 'review-kit' ); ?></div>
                    </div>
                </div>

            </div><!-- /.reviewkit-sc-grid -->

            <!-- Hướng dẫn tìm Product ID -->
            <div class="reviewkit-find-id-box">
                <h3><span class="dashicons dashicons-search"></span> <?php _e( 'Cách tìm Product ID', 'review-kit' ); ?></h3>
                <p><?php _e( 'Product ID là số hiển thị trong URL khi bạn chỉnh sửa sản phẩm trong WooCommerce:', 'review-kit' ); ?></p>
                <div class="reviewkit-sc-code-block">
                    <code>https://yoursite.com/wp-admin/post.php?post=<span class="reviewkit-code-highlight">123</span>&action=edit</code>
                </div>
                <p><?php _e( 'Bạn cũng có thể xem cột <strong>ID</strong> trong danh sách sản phẩm WooCommerce (bật cột ID qua <em>Screen Options</em> ở góc trên phải).', 'review-kit' ); ?></p>
                <?php if ( $example_id ) : ?>
                <div class="reviewkit-sc-tip reviewkit-admin-tip--success"><strong><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'Sản phẩm mẫu của bạn:', 'review-kit' ); ?></strong> <?php printf( __( 'Product ID = <strong>%d</strong> — đã được điền sẵn trong các ví dụ phía trên.', 'review-kit' ), $example_id ); ?></div>
                <?php endif; ?>
            </div>


        <script>
        function reviewkitCopy(btn) {
            var code = btn.getAttribute('data-code').replace(/&quot;/g, '"');
            navigator.clipboard.writeText(code).then(function() {
                btn.textContent = '✓ Đã copy!';
                setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
            });
        }
        </script>
        <?php
    }

}