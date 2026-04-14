<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MCWR_Admin_Settings {

    private $active_tab;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    // 1. TẠO MENU ADMIN
    public function add_admin_menu() {
        add_menu_page(
            __( 'ReviewKit Settings', 'my-custom-woo-reviews' ), // Page Title
            __( 'ReviewKit', 'my-custom-woo-reviews' ),           // Menu Title
            'manage_options',   // Capability
            'mcwr-settings',    // Menu Slug
            array( $this, 'render_settings_page' ), // Callback
            'dashicons-star-half', // Icon
            58 // Position (Sau Woo Products)
        );
    }

    // 2. ENQUEUE ASSETS (Cho Color Picker + Admin CSS)
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_mcwr-settings' !== $hook ) return;

        // Load Color Picker của WordPress
        wp_enqueue_style( 'wp-color-picker' );

        // Admin Settings CSS riêng
        wp_enqueue_style(
            'mcwr-admin-settings-css',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-settings.css',
            array(),
            defined( 'MCWR_VERSION' ) ? MCWR_VERSION : '1.1.0'
        );

        wp_enqueue_script( 'mcwr-admin-settings-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-settings.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
    }

    // 3. ĐĂNG KÝ CÁC TÙY CHỌN (SETTINGS API)
    public function register_settings() {
        
        // --- TAB 1: CÀI ĐẶT CHUNG (GENERAL) ---
        register_setting( 'mcwr_general_group', 'mcwr_moderation_mode' );
        register_setting( 'mcwr_general_group', 'mcwr_enable_voting' );
        register_setting( 'mcwr_general_group', 'mcwr_require_login' );
        
        // --- MÀU SẮC (COLORS) ---
        register_setting( 'mcwr_display_group', 'mcwr_primary_color' );
        register_setting( 'mcwr_display_group', 'mcwr_stars_color' );
        register_setting( 'mcwr_display_group', 'mcwr_border_color' );

        add_settings_section( 'mcwr_general_section', __( 'Cấu hình cơ bản', 'my-custom-woo-reviews' ), null, 'mcwr_general_page' );

        add_settings_field( 'mcwr_moderation_mode', __( 'Chế độ duyệt bài', 'my-custom-woo-reviews' ), array( $this, 'render_select_moderation' ), 'mcwr_general_page', 'mcwr_general_section' );
        add_settings_field( 'mcwr_enable_voting', __( 'Tính năng Hữu ích (Like)', 'my-custom-woo-reviews' ), array( $this, 'render_checkbox' ), 'mcwr_general_page', 'mcwr_general_section', array( 'id' => 'mcwr_enable_voting' ) );
        add_settings_field(  'mcwr_require_login' , __( 'Yêu cầu đăng nhập', 'my-custom-woo-reviews' ), array( $this, 'render_checkbox' ), 'mcwr_general_page', 'mcwr_general_section', array( 
                'id' => 'mcwr_require_login',
                'default' => 0 // Mặc định là TẮT (cho phép khách gửi review)
        ) 
);


        // --- TAB 2: GIỚI HẠN UPLOAD (LIMITS) ---
        register_setting( 'mcwr_limits_group', 'mcwr_enable_uploads' );
        register_setting( 'mcwr_limits_group', 'mcwr_max_images' );
        register_setting( 'mcwr_limits_group', 'mcwr_max_file_size' );
        
        // --- MỚI: VIDEO SETTINGS ---
        register_setting( 'mcwr_limits_group', 'mcwr_enable_video_upload' );
        register_setting( 'mcwr_limits_group', 'mcwr_max_video_size' );
        register_setting( 'mcwr_limits_group', 'mcwr_allowed_video_types' );

        add_settings_section( 'mcwr_limits_section', __( 'Cấu hình hình ảnh và Video', 'my-custom-woo-reviews' ), null, 'mcwr_limits_page' );

        add_settings_field( 'mcwr_enable_uploads', __( 'Cho phép Upload ảnh', 'my-custom-woo-reviews' ), array( $this, 'render_checkbox' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_enable_uploads' ) );
        add_settings_field( 'mcwr_max_images', __( 'Số ảnh tối đa', 'my-custom-woo-reviews' ), array( $this, 'render_number' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_max_images', 'desc' => __( 'ảnh/review', 'my-custom-woo-reviews' ) ) );
        add_settings_field( 'mcwr_max_file_size', __( 'Dung lượng ảnh tối đa', 'my-custom-woo-reviews' ), array( $this, 'render_number' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_max_file_size', 'desc' => 'MB' ) );

        add_settings_field( 'mcwr_enable_video_upload', __( 'Cho phép Upload Video', 'my-custom-woo-reviews' ), array( $this, 'render_checkbox' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_enable_video_upload', 'default' => 0 ) );
        add_settings_field( 'mcwr_max_video_size', __( 'Dung lượng Video tối đa', 'my-custom-woo-reviews' ), array( $this, 'render_number' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_max_video_size', 'default' => 10, 'desc' => 'MB' ) );
        add_settings_field( 'mcwr_allowed_video_types', __( 'Định dạng Video cho phép', 'my-custom-woo-reviews' ), array( $this, 'render_text' ), 'mcwr_limits_page', 'mcwr_limits_section', array( 'id' => 'mcwr_allowed_video_types', 'default' => 'mp4,webm,mov', 'desc' => __( 'Phân cách bằng dấu phẩy (vd: mp4,webm,mov)', 'my-custom-woo-reviews' ) ) );


        // --- TAB 3: HIỂN THỊ (DISPLAY) ---
        register_setting( 'mcwr_display_group', 'mcwr_verified_text' );
        register_setting( 'mcwr_display_group', 'mcwr_verified_color' );

        add_settings_section( 'mcwr_display_section', __( 'Giao diện', 'my-custom-woo-reviews' ), null, 'mcwr_display_page' );

        add_settings_field( 'mcwr_verified_text', __( 'Chữ Badge xác thực', 'my-custom-woo-reviews' ), array( $this, 'render_text' ), 'mcwr_display_page', 'mcwr_display_section', array( 'id' => 'mcwr_verified_text' ) );
        add_settings_field( 'mcwr_verified_color', __( 'Màu Badge xác thực', 'my-custom-woo-reviews' ), array( $this, 'render_color_picker' ), 'mcwr_display_page', 'mcwr_display_section', array( 'id' => 'mcwr_verified_color' ) );


        register_setting( 'mcwr_display_group', 'mcwr_per_page' );
        register_setting( 'mcwr_display_group', 'mcwr_pagination_style' ); // Thêm key mới


        // Đăng ký Lightbox (MỚI THÊM) - NẾU THIẾU DÒNG NÀY SẼ KHÔNG LƯU ĐƯỢC
        register_setting( 'mcwr_display_group', 'mcwr_lightbox_layout' );
        register_setting( 'mcwr_display_group', 'mcwr_lightbox_toolbar' );
        register_setting( 'mcwr_display_group', 'mcwr_lightbox_theme' );

        add_settings_section( 'mcwr_display_section', __( 'Cấu hình Hiển thị', 'my-custom-woo-reviews' ), null, 'mcwr_display_page' );

        // 1. Số đánh giá mỗi trang
        add_settings_field( 
            'mcwr_per_page', 
            __( 'Số đánh giá mỗi trang', 'my-custom-woo-reviews' ), 
            array( $this, 'render_number' ), 
            'mcwr_display_page', 
            'mcwr_display_section', 
            array( 'id' => 'mcwr_per_page', 'default' => 5, 'desc' => __( 'Số lượng đánh giá hiển thị trong 1 lần tải.', 'my-custom-woo-reviews' ) ) 
        );
        
        // 2. Kiểu Phân Trang
        add_settings_field( 
            'mcwr_pagination_style', 
            __( 'Kiểu Phân Trang', 'my-custom-woo-reviews' ), 
            array( $this, 'render_select' ), 
            'mcwr_display_page', 
            'mcwr_display_section', 
            array( 
                'id' => 'mcwr_pagination_style', 
                'default' => 'numbered_ajax',
                'options' => array(
                    'numbered_ajax' => __( 'Phân trang số (1, 2, 3...)', 'my-custom-woo-reviews' ),
                    'load_more'     => __( 'Tải thêm (Load More Button)', 'my-custom-woo-reviews' )
                ),
                'desc' => __( 'Chọn kiểu hiển thị cho đánh giá.', 'my-custom-woo-reviews' )
            ) 
        );


        // ======
        add_settings_section( 'mcwr_lightbox_section', __( 'Cấu hình Lightbox (Popup)', 'my-custom-woo-reviews' ), null, 'mcwr_display_page' );

        // 2. Vị trí Thumbnails (Layout)
        add_settings_field( 
            'mcwr_lightbox_layout', 
            __( 'Kiểu hiển thị Thumbnails', 'my-custom-woo-reviews' ), 
            array( $this, 'render_select' ), 
            'mcwr_display_page', 
            'mcwr_lightbox_section', 
            array( 
                'id' => 'mcwr_lightbox_layout',
                'default' => 'modern',
                'options' => array(
                    'modern'     => 'Modern (' . __( 'Mặc định', 'my-custom-woo-reviews' ) . ')',
                    'classic'    => __( 'Classic', 'my-custom-woo-reviews' ),
                    'scrollable' => __( 'Scrollable', 'my-custom-woo-reviews' ),
                    'vertical'   => __( 'Vertical Thumbnails', 'my-custom-woo-reviews' ),
                    ''           => __( 'Không hiển thị Thumbnails', 'my-custom-woo-reviews' )
                ),
                'desc' => __( 'Chọn "Dọc" cho màn hình rộng, "Ngang" cho phong cách cổ điển.', 'my-custom-woo-reviews' )
            ) 
        );

        // ====== MÀU SẮC ======
        add_settings_section( 'mcwr_color_section', __( 'Màu sắc giao diện', 'my-custom-woo-reviews' ), null, 'mcwr_display_page' );

        add_settings_field( 
            'mcwr_primary_color', 
            __( 'Màu chủ đạo (Nút, Badge)', 'my-custom-woo-reviews' ), 
            array( $this, 'render_color_picker' ), 
            'mcwr_display_page', 
            'mcwr_color_section', 
            array( 'id' => 'mcwr_primary_color', 'default' => '#ee4d2d' ) 
        );

        add_settings_field( 
            'mcwr_stars_color', 
            __( 'Màu ngôi sao & Thanh biểu đồ', 'my-custom-woo-reviews' ), 
            array( $this, 'render_color_picker' ), 
            'mcwr_display_page', 
            'mcwr_color_section', 
            array( 'id' => 'mcwr_stars_color', 'default' => '#f59e0b' ) 
        );

        add_settings_field( 
            'mcwr_border_color', 
            __( 'Màu đường viền card', 'my-custom-woo-reviews' ), 
            array( $this, 'render_color_picker' ), 
            'mcwr_display_page', 
            'mcwr_color_section', 
            array( 'id' => 'mcwr_border_color', 'default' => '#e2e8f0' ) 
        );

        add_settings_field( 
            'mcwr_lightbox_theme', 
            __( 'Giao diện (Theme)', 'my-custom-woo-reviews' ), 
            array( $this, 'render_select' ), 
            'mcwr_display_page', 
            'mcwr_lightbox_section', 
            array( 
                'id' => 'mcwr_lightbox_theme', 
                'default' => 'dark',
                'options' => array(
                    'dark'  => __( 'Dark theme (Tối)', 'my-custom-woo-reviews' ),
                    'light' => __( 'Light theme (Sáng)', 'my-custom-woo-reviews' ),
                    'auto'  => __( 'Auto (Theo thiết bị)', 'my-custom-woo-reviews' )
                ),
                'desc' => __( 'Chọn màu nền cho Popup ảnh.', 'my-custom-woo-reviews' )
            ) 
        );

        // 3. Bật/Tắt Toolbar
        add_settings_field( 
            'mcwr_lightbox_toolbar', 
            __( 'Hiển thị thanh công cụ (Toolbar)', 'my-custom-woo-reviews' ), 
            array( $this, 'render_checkbox' ), 
            'mcwr_display_page', 
            'mcwr_lightbox_section', 
            array( 'id' => 'mcwr_lightbox_toolbar', 'default' => 1 ) 
        );


    }

    // --- 4. CALLBACKS HIỂN THỊ HTML INPUT ---

    // Input Checkbox
    public function render_checkbox( $args ) {
        // Lấy giá trị mặc định nếu có truyền vào, nếu không thì là 0
        $default_val = isset($args['default']) ? $args['default'] : 0;
        
        // Lấy giá trị từ DB, nếu không có thì dùng mặc định
        $option = get_option( $args['id'], $default_val );
        
        echo '<input type="checkbox" name="' . $args['id'] . '" value="1" ' . checked( 1, $option, false ) . ' /> ' . __( 'Cho phép', 'my-custom-woo-reviews' );
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
        $option = get_option( 'mcwr_moderation_mode' );
        ?>
        <select name="mcwr_moderation_mode">
            <option value="0" <?php selected( $option, '0' ); ?>><?php _e( 'Chờ duyệt (Pending)', 'my-custom-woo-reviews' ); ?></option>
            <option value="1" <?php selected( $option, '1' ); ?>><?php _e( 'Tự động đăng (Approved)', 'my-custom-woo-reviews' ); ?></option>
        </select>
        <p class="description"><?php _e( 'Chọn "Chờ duyệt" để kiểm tra nội dung trước khi hiển thị.', 'my-custom-woo-reviews' ); ?></p>
        <?php
    }

    // Color Picker
    public function render_color_picker( $args ) {
        $default = isset($args['default']) ? $args['default'] : '#ee4d2d';
        $option = get_option( $args['id'], $default );
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr( $option ) . '" class="mcwr-color-field" />';
    }


    // --- 5. RENDER TRANG ADMIN CHÍNH ---
    public function render_settings_page() {
        $this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

        // Map tab → [ title, icon, description ]
        $tab_meta = array(
            'general'    => array(
                'title' => __( 'Cài đặt chung', 'my-custom-woo-reviews' ),
                'icon'  => 'dashicons-admin-settings',
                'desc'  => __( 'Cấu hình chế độ duyệt bài, bình chọn hữu ích và yêu cầu đăng nhập.', 'my-custom-woo-reviews' ),
            ),
            'limits'     => array(
                'title' => __( 'Giới hạn & Upload', 'my-custom-woo-reviews' ),
                'icon'  => 'dashicons-upload',
                'desc'  => __( 'Cấu hình số lượng, dung lượng tối đa cho ảnh và video khách hàng upload.', 'my-custom-woo-reviews' ),
            ),
            'display'    => array(
                'title' => __( 'Hiển thị', 'my-custom-woo-reviews' ),
                'icon'  => 'dashicons-visibility',
                'desc'  => __( 'Tuỳ chỉnh giao diện: màu sắc, badge xác thực, phân trang, lightbox popup.', 'my-custom-woo-reviews' ),
            ),
            'shortcodes' => array(
                'title' => __( 'Mã ngắn', 'my-custom-woo-reviews' ),
                'icon'  => 'dashicons-editor-code',
                'desc'  => __( 'Chèn các mã ngắn vào trang, bài viết hoặc widget để hiển thị khu vực đánh giá.', 'my-custom-woo-reviews' ),
            ),
            'tools'      => array(
                'title' => __( 'Công cụ Pro', 'my-custom-woo-reviews' ),
                'icon'  => 'dashicons-hammer',
                'desc'  => __( 'Các công cụ nâng cao: nhắc nhở email, blacklist, xóa file media khi xóa đánh giá.', 'my-custom-woo-reviews' ),
            ),
        );

        $current = isset( $tab_meta[ $this->active_tab ] ) ? $tab_meta[ $this->active_tab ] : $tab_meta['general'];
        ?>
        <div class="wrap mcwr-settings-wrap">
            <h1><?php _e( 'ReviewKit: Media & Customer Written Reviews', 'my-custom-woo-reviews' ); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tab_meta as $slug => $meta ) : ?>
                <a href="?page=mcwr-settings&tab=<?php echo esc_attr( $slug ); ?>"
                   class="nav-tab <?php echo $this->active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
                    <?php echo esc_html( $meta['title'] ); ?>
                </a>
                <?php endforeach; ?>
            </h2>

            <!-- Banner header mô tả tab hiện tại -->
            <div class="mcwr-page-header">
                <h2>
                    <span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>"></span>
                    <?php echo esc_html( $current['title'] ); ?>
                </h2>
                <p><?php echo esc_html( $current['desc'] ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                if ( $this->active_tab === 'general' ) {
                    settings_fields( 'mcwr_general_group' );
                    do_settings_sections( 'mcwr_general_page' );
                } elseif ( $this->active_tab === 'limits' ) {
                    settings_fields( 'mcwr_limits_group' );
                    do_settings_sections( 'mcwr_limits_page' );
                } elseif ( $this->active_tab === 'display' ) {
                    settings_fields( 'mcwr_display_group' );
                    do_settings_sections( 'mcwr_display_page' );
                }

                if ( ! in_array( $this->active_tab, array( 'tools', 'shortcodes' ), true ) ) {
                    submit_button();
                }
                ?>
            </form>

            <?php if ( $this->active_tab === 'tools' ) : ?>
                <?php do_action( 'mcwr_admin_tab_content_tools' ); ?>
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
        <div class="mcwr-sc-grid">

                <!-- Shortcode 1: Full Layout -->
                <div class="mcwr-sc-card">
                    <div class="mcwr-sc-card-header">
                        <h3><span class="dashicons dashicons-layout"></span> Toàn bộ giao diện đánh giá</h3>
                    </div>
                    <div class="mcwr-sc-card-body">
                        <span class="mcwr-sc-badge"><?php _e( 'Khuyên dùng', 'my-custom-woo-reviews' ); ?></span>
                        <p><?php _e( 'Hiển thị đầy đủ 2 cột: <strong>Danh sách + bộ lọc</strong> ở trái và <strong>Thống kê + Form</strong> ở phải. Giống hệt tab Đánh giá mặc định của WooCommerce.', 'my-custom-woo-reviews' ); ?></p>
                        <div class="mcwr-sc-code-block">
                            <code>[mcwr_reviews product_id="<?php echo $pid; ?>"]</code>
                            <button class="mcwr-sc-copy-btn" onclick="mcwrCopy(this)" data-code="[mcwr_reviews product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'my-custom-woo-reviews' ); ?></button>
                        </div>
                        <div class="mcwr-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID của sản phẩm (tìm trong URL trang sửa sản phẩm). Nếu bỏ trống, tự động lấy sản phẩm hiện tại.', 'my-custom-woo-reviews' ); ?></td></tr></table>
                        </div>
                        <div class="mcwr-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'my-custom-woo-reviews' ); ?></strong> <?php _e( 'Trang sản phẩm (Single Product) không cần điền <code>product_id</code> — plugin tự nhận diện.', 'my-custom-woo-reviews' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 2: Form -->
                <div class="mcwr-sc-card">
                    <div class="mcwr-sc-card-header">
                        <h3><span class="dashicons dashicons-edit"></span> <?php _e( 'Chỉ Form gửi đánh giá', 'my-custom-woo-reviews' ); ?></h3>
                    </div>
                    <div class="mcwr-sc-card-body">
                        <p><?php _e( 'Hiển thị riêng form nhập sao, tên, email, nội dung và upload ảnh/video. Phù hợp để nhúng vào sidebar, landing page hoặc popup.', 'my-custom-woo-reviews' ); ?></p>
                        <div class="mcwr-sc-code-block">
                            <code>[mcwr_review_form product_id="<?php echo $pid; ?>"]</code>
                            <button class="mcwr-sc-copy-btn" onclick="mcwrCopy(this)" data-code="[mcwr_review_form product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'my-custom-woo-reviews' ); ?></button>
                        </div>
                        <div class="mcwr-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm cần gửi đánh giá (bắt buộc nếu dùng ngoài trang sản phẩm).', 'my-custom-woo-reviews' ); ?></td></tr></table>
                        </div>
                        <div class="mcwr-sc-tip mcwr-admin-tip--warning"><strong><span class="dashicons dashicons-warning"></span> <?php _e( 'Lưu ý:', 'my-custom-woo-reviews' ); ?></strong> <?php _e( 'Cài đặt <em>"Yêu cầu đăng nhập"</em> vẫn được áp dụng — khách chưa đăng nhập sẽ thấy thông báo.', 'my-custom-woo-reviews' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 3: List -->
                <div class="mcwr-sc-card">
                    <div class="mcwr-sc-card-header">
                        <h3><span class="dashicons dashicons-list-view"></span> <?php _e( 'Chỉ Danh sách đánh giá', 'my-custom-woo-reviews' ); ?></h3>
                    </div>
                    <div class="mcwr-sc-card-body">
                        <p><?php _e( 'Hiển thị danh sách các bình luận kèm <strong>thanh lọc theo sao</strong>, lọc theo ảnh/đã mua và <strong>sắp xếp</strong> (mới nhất, hữu ích, điểm cao...).', 'my-custom-woo-reviews' ); ?></p>
                        <div class="mcwr-sc-code-block">
                            <code>[mcwr_review_list product_id="<?php echo $pid; ?>"]</code>
                            <button class="mcwr-sc-copy-btn" onclick="mcwrCopy(this)" data-code="[mcwr_review_list product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'my-custom-woo-reviews' ); ?></button>
                        </div>
                        <div class="mcwr-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm muốn lấy danh sách đánh giá.', 'my-custom-woo-reviews' ); ?></td></tr></table>
                        </div>
                        <div class="mcwr-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'my-custom-woo-reviews' ); ?></strong> <?php printf( __( 'Ghép cặp với %s để tạo layout tuỳ chỉnh theo thiết kế riêng.', 'my-custom-woo-reviews' ), '<code>[mcwr_review_summary]</code>' ); ?></div>
                    </div>
                </div>

                <!-- Shortcode 4: Summary -->
                <div class="mcwr-sc-card">
                    <div class="mcwr-sc-card-header">
                        <h3><span class="dashicons dashicons-chart-bar"></span> <?php _e( 'Chỉ Thống kê đánh giá', 'my-custom-woo-reviews' ); ?></h3>
                    </div>
                    <div class="mcwr-sc-card-body">
                        <p><?php _e( 'Hiển thị <strong>điểm trung bình</strong> (Ví dụ: 4.8 / 5) cùng biểu đồ thanh tiến trình cho từng mức sao từ 5★ đến 1★.', 'my-custom-woo-reviews' ); ?></p>
                        <div class="mcwr-sc-code-block">
                            <code>[mcwr_review_summary product_id="<?php echo $pid; ?>"]</code>
                            <button class="mcwr-sc-copy-btn" onclick="mcwrCopy(this)" data-code="[mcwr_review_summary product_id=&quot;<?php echo $pid; ?>&quot;]"><?php _e( 'Copy', 'my-custom-woo-reviews' ); ?></button>
                        </div>
                        <div class="mcwr-sc-params">
                            <table><tr><td>product_id</td><td><?php _e( 'ID sản phẩm muốn xem thống kê đánh giá.', 'my-custom-woo-reviews' ); ?></td></tr></table>
                        </div>
                        <div class="mcwr-sc-tip"><strong><span class="dashicons dashicons-lightbulb"></span> <?php _e( 'Mẹo:', 'my-custom-woo-reviews' ); ?></strong> <?php _e( 'Phù hợp để nhúng vào trang mô tả sản phẩm hoặc bảng so sánh sản phẩm.', 'my-custom-woo-reviews' ); ?></div>
                    </div>
                </div>

            </div><!-- /.mcwr-sc-grid -->

            <!-- Hướng dẫn tìm Product ID -->
            <div class="mcwr-find-id-box">
                <h3><span class="dashicons dashicons-search"></span> <?php _e( 'Cách tìm Product ID', 'my-custom-woo-reviews' ); ?></h3>
                <p><?php _e( 'Product ID là số hiển thị trong URL khi bạn chỉnh sửa sản phẩm trong WooCommerce:', 'my-custom-woo-reviews' ); ?></p>
                <div class="mcwr-sc-code-block">
                    <code>https://yoursite.com/wp-admin/post.php?post=<span class="mcwr-code-highlight">123</span>&action=edit</code>
                </div>
                <p><?php _e( 'Bạn cũng có thể xem cột <strong>ID</strong> trong danh sách sản phẩm WooCommerce (bật cột ID qua <em>Screen Options</em> ở góc trên phải).', 'my-custom-woo-reviews' ); ?></p>
                <?php if ( $example_id ) : ?>
                <div class="mcwr-sc-tip mcwr-admin-tip--success"><strong><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'Sản phẩm mẫu của bạn:', 'my-custom-woo-reviews' ); ?></strong> <?php printf( __( 'Product ID = <strong>%d</strong> — đã được điền sẵn trong các ví dụ phía trên.', 'my-custom-woo-reviews' ), $example_id ); ?></div>
                <?php endif; ?>
            </div>


        <script>
        function mcwrCopy(btn) {
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