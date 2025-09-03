<?php
include plugin_dir_path(__FILE__) . './config_page.php';
include plugin_dir_path(__FILE__) . './order_check_lookup.php';
include plugin_dir_path(__FILE__) . './barcode_tracking.php';

// Affiliate
include plugin_dir_path(__FILE__) . './aff/gpt_referral_list_page.php';
include plugin_dir_path(__FILE__) . './aff/gpt_successful_referrer_page.php';
include plugin_dir_path(__FILE__) . './aff/gpt_marketing_config_page.php';
// Barcode
include plugin_dir_path(__FILE__) . './barcode_config/index.php';
include plugin_dir_path(__FILE__) . './barcode_config/list_code.php';
include plugin_dir_path(__FILE__) . './barcode_config/box_list_code.php';
// Ranking
include plugin_dir_path(__FILE__) . './ranking/index.php';
// Tích & đổi điểm
include plugin_dir_path(__FILE__) . './manage_points_list/accumulate_list.php';
include plugin_dir_path(__FILE__) . './manage_points_list/exchange_list.php';
include plugin_dir_path(__FILE__) . './manage_points_list/warning.php';
include plugin_dir_path(__FILE__) . './manage_points_list/store_reports.php';
include plugin_dir_path(__FILE__) . './manage_points_list/analytics_reports.php';
// cửa hàng & nhân viên
include plugin_dir_path(__FILE__) . './manage_employee_store/store.php';
include plugin_dir_path(__FILE__) . './manage_employee_store/employee.php';
include plugin_dir_path(__FILE__) . './manage_employee_store/distributors.php';
include plugin_dir_path(__FILE__) . './manage_employee_store/sale_channels.php';
// Settings
include plugin_dir_path(__FILE__) . './settings/setting_affiliate.php';
include plugin_dir_path(__FILE__) . './settings/setting_identifier.php';
include plugin_dir_path(__FILE__) . './settings/setting_lot.php';
include plugin_dir_path(__FILE__) . './settings/cron.php';

add_action('admin_menu', function () {
   
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    if (current_user_can('manage_options')) {
        add_menu_page('Quản lý xuất nhập tồn tem công nghệ', 'Quản lý xuất nhập tồn tem công nghệ', 'edit_posts', 'gpt-manager-tem', '__return_null', 'dashicons-tickets', 5);
        add_menu_page('Cấu hình tem công nghệ', 'Cấu hình tem công nghệ', 'edit_posts', 'gpt-macao', '__return_null', 'dashicons-tickets', 5);
        add_menu_page('Báo cáo tem công nghệ', 'Báo cáo tem công nghệ', 'edit_posts', 'gpt-analytics-reports', '__return_null', 'dashicons-tickets', 5);
        // Cấu hình tab
        add_submenu_page(
            'gpt-macao',
            'Cấu hình chung',
            'Cấu hình chung',
            'manage_options',
            'gpt-config',
            'gpt_render_config_tabs_page',
            0
        );
        add_submenu_page(
            'gpt-macao',
            'Tạo mã định danh',
            'Tạo mã định danh',
            'manage_options',
            'gpt-setting-identifier',
            'gpt_setting_identifier_page',
            1
        );
        add_submenu_page(
            'gpt-macao',
            'Danh sách mã',
            'Danh sách mã',
            'manage_options',
            'gpt-config-barcode',
            'gpt_render_config_barcode_page',
            2
        );
        add_submenu_page(
            'gpt-macao',
            'Cấu hình Lô của sản phẩm',
            'Cấu hình Lô của sản phẩm',
            'manage_options',
            'gpt-lot-manager',
            'gpt_render_lot_page',
            3
        );
        add_submenu_page(
            'gpt-macao',
            'Truy xuất mã',
            'Truy xuất mã', 
            'manage_options',
            'gpt-barcode-tracking',
            'gpt_barcode_tracking_page',
            4
        );
        add_submenu_page(
            'gpt-macao',
            'Cấu hình kênh bán',
            'Cấu hình kênh bán',
            'manage_options',
            'gpt-store-employee',
            'gpt_render_store_employee_page',
            5
        );
         add_submenu_page(
            'gpt-macao',
            'Hướng dẫn chung',
            'Hướng dẫn chung',
            'manage_options',
            'gpt-instructions',
            'gpt_render_instructions_page',
            6
        );
    }

    if ( in_array( 'quan_ly_kho', $user_roles ) ) {
        add_menu_page('Quản lý xuất nhập tồn tem công nghệ', 'Quản lý xuất nhập tồn tem công nghệ', 'edit_posts', 'gpt-manager-tem', '__return_null', 'dashicons-tickets', 5);
        add_menu_page('Cấu hình tem công nghệ', 'Cấu hình tem công nghệ', 'edit_posts', 'gpt-macao', '__return_null', 'dashicons-tickets', 5);
        add_menu_page('Báo cáo tem công nghệ', 'Báo cáo tem công nghệ', 'edit_posts', 'gpt-analytics-reports', '__return_null', 'dashicons-tickets', 5);
        add_submenu_page(
            'gpt-macao',
            'Cấu hình kênh bán',
            'Cấu hình kênh bán',
            'quan_ly_kho',
            'gpt-store-employee',
            'gpt_render_store_employee_page'
        );
        add_submenu_page(
            'gpt-macao',
            'Cấu hình Lô của sản phẩm',
            'Cấu hình Lô của sản phẩm',
            'quan_ly_kho',
            'gpt-lot-manager',
            'gpt_render_lot_page'
        );
        add_submenu_page(
            'gpt-analytics-reports',
            'Báo cáo tổng hợp',
            'Báo cáo tổng hợp',
            'quan_ly_kho',
            'gpt_analytics_reports_page',
            'gpt_analytics_reports_page_callback'
        );
        add_submenu_page(
            'gpt-analytics-reports',
            'DSKH tích điểm',
            'DSKH tích điểm',
            'quan_ly_kho',
            'gpt-customer-list',
            'gpt_customer_list_page'
        );
    }

    if ( in_array( 'nhan_vien_kho', $user_roles ) ) {
        add_menu_page('Quản lý xuất nhập tồn tem công nghệ', 'Quản lý xuất nhập tồn tem công nghệ', 'edit_posts', 'gpt-manager-tem', '__return_null', 'dashicons-tickets', 5);
    }

    if (current_user_can('manage_options')) {
        // Cấu hình tab
        add_submenu_page(
            'gpt-analytics-reports',
            'Báo cáo tổng hợp',
            'Báo cáo tổng hợp',
            'manage_options',
            'gpt_analytics_reports_page',
            'gpt_analytics_reports_page_callback'
        );
        add_submenu_page(
            'gpt-analytics-reports',
            'DSKH tích điểm',
            'DSKH tích điểm',
            'manage_options',
            'gpt-customer-list',
            'gpt_customer_list_page'
        );
        add_submenu_page(
            'gpt-analytics-reports',
            'DSKH đổi điểm',
            'DSKH đổi điểm',
            'manage_options',
            'gpt-exchange-list',
            'gpt_render_exchange_list_page'
        );
        add_submenu_page(
            'gpt-analytics-reports',
            'DS cảnh báo sai vị trí',
            'DS cảnh báo sai vị trí',
            'manage_options',
            'gpt-warning-list',
            'gpt_location_warnings_page'
        );
            add_submenu_page(
            'gpt-analytics-reports',
            'Báo cáo tích điểm theo cửa hàng',
            'Báo cáo tích điểm theo cửa hàng',
            'manage_options',
            'gpt-affiliate-reports',
            'gpt_affiliate_reports_page_callback'
        );
        add_submenu_page('gpt-analytics-reports', 'DS người được giới thiệu', 'Người được giới thiệu', 'manage_options', 'gpt-referred-person', 'gpt_referral_list_page');
        add_submenu_page('gpt-analytics-reports', 'DS người giới thiệu thành công', 'Giới thiệu thành công', 'manage_options', 'gpt-successful-referrer', 'gpt_successful_referrer_page');
        
    }   

});

// Quản lí cửa hàng & nhân viên
function gpt_render_store_employee_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'channels';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-store-employee&tab=channels" class="nav-tab ' . ($active_tab === 'channels' ? 'nav-tab-active' : '') . '">Kênh bán hàng</a>';
    echo '<a href="?page=gpt-store-employee&tab=distributor" class="nav-tab ' . ($active_tab === 'distributor' ? 'nav-tab-active' : '') . '">Nhà phân phối / Chi nhánh</a>';
    echo '<a href="?page=gpt-store-employee&tab=store" class="nav-tab ' . ($active_tab === 'store' ? 'nav-tab-active' : '') . '">Cửa hàng</a>';
    echo '<a href="?page=gpt-store-employee&tab=employee" class="nav-tab ' . ($active_tab === 'employee' ? 'nav-tab-active' : '') . '">Nhân viên</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        case 'employee':
            gpt_render_employee_tab();
            break;
        case 'distributor':
            gpt_render_distributors_tab();
            break;
        case 'store':
            gpt_render_store_tab();
            break;
        default:
            gpt_render_sales_channels_page();
            break;
    }

    echo '</div></div>';
}

// Quản lí barcode
function gpt_render_config_barcode_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'barcode';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-config-barcode&tab=box_barcode" class="nav-tab ' . ($active_tab === 'box_barcode' ? 'nav-tab-active' : '') . '">Danh sách mã định danh thùng</a>';
    echo '<a href="?page=gpt-config-barcode&tab=barcode" class="nav-tab ' . ($active_tab === 'barcode' ? 'nav-tab-active' : '') . '">Danh sách mã định danh sản phẩm</a>';
    echo '<a href="?page=gpt-config-barcode&tab=browse" class="nav-tab ' . ($active_tab === 'browse' ? 'nav-tab-active' : '') . '">Duyệt mã định danh sản phẩm</a>';

    echo '</h1>';
    echo '<div class="tab-content">';
    echo '<div class="bg-grey">';
    switch ($active_tab) {
        case 'browse':
            gpt_render_duyet_barcode_page();
            break;
        case 'box_barcode':
            gpt_box_barcode_list_page();
            break;
        default:
            gpt_barcode_list_page();
            break;
    }

    echo '</div></div></div>';
}

// Quản lí tích & đổi điểm
function gpt_render_config_list_points_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'accumulate_points';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-list-points-report&tab=accumulate_points" class="nav-tab ' . ($active_tab === 'accumulate_points' ? 'nav-tab-active' : '') . '">Danh sách khách hàng tích điểm</a>';
    echo '<a href="?page=gpt-list-points-report&tab=change_points" class="nav-tab ' . ($active_tab === 'change_points' ? 'nav-tab-active' : '') . '">Danh sách khách hàng đổi điểm</a>';
    echo '<a href="?page=gpt-list-points-report&tab=warning_points" class="nav-tab ' . ($active_tab === 'warning_points' ? 'nav-tab-active' : '') . '">Danh sách khách cảnh báo</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        case 'warning_points':
            gpt_location_warnings_page();
            break;
        case 'change_points':
            gpt_render_exchange_list_page();
            break;
        case 'accumulate_points':
        default:
            gpt_customer_list_page();
            break;
    }

    echo '</div></div>';
}

// Quản lí Affiliate

function gpt_render_config_affiliate_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'affiliate_user';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-affiliate-report&tab=affiliate_user" class="nav-tab ' . ($active_tab === 'affiliate_user' ? 'nav-tab-active' : '') . '">Danh sách người được giới thiệu</a>';
    echo '<a href="?page=gpt-affiliate-report&tab=affiliate_success" class="nav-tab ' . ($active_tab === 'affiliate_success' ? 'nav-tab-active' : '') . '">Danh sách giới thiệu thành công</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        case 'affiliate_success':
            gpt_successful_referrer_page();
            break;
        case 'affiliate_user':
        default:
            gpt_referral_list_page();
            break;
    }

    echo '</div></div>';
}

// Quản lí cấu hình chung
function gpt_render_config_tabs_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';
    
    echo '<a href="?page=gpt-config&tab=notice" class="nav-tab ' . ($active_tab == 'notice' ? 'nav-tab-active' : '') . '">Cấu hình thông báo</a>';
    echo '<a href="?page=gpt-config&tab=products_gift" class="nav-tab ' . ($active_tab == 'products_gift' ? 'nav-tab-active' : '') . '">Cấu hình sản phẩm đổi quà</a>';
    echo '<a href="?page=gpt-config&tab=affiliate" class="nav-tab ' . ($active_tab == 'affiliate' ? 'nav-tab-active' : '') . '">Cấu hình affiliate</a>';

    echo '</h1>';

    echo '<div class="tab-content">';
    switch ($active_tab) {
        case 'notice':
            gpt_notice_config_page();
            break;
        case 'affiliate':
            gpt_affiliate_setting_page();
            break;
        case 'products_gift':
            gpt_setting_products_gift_page();
            break; 
        default:
            gpt_notice_config_page();
            break;
    }
    echo '</div></div>';
}

function gpt_get_store_name($store_id) {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $name = $wpdb->get_var($wpdb->prepare("SELECT store_name FROM $table WHERE id = %d", $store_id));
    return $name ?: '—';
}

function gpt_get_distributor_name($distributor_id) {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_DISTRIBUTORS;
    $name = $wpdb->get_var($wpdb->prepare("SELECT title FROM $table WHERE id = %d", $distributor_id));
    return $name ?: '—';
}

function gpt_get_channel_name($channel_id) {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;
    $name = $wpdb->get_var($wpdb->prepare("SELECT title FROM $table WHERE id = %d", $channel_id));
    return $name ?: '—';
}

// Quản lí thông báo

add_action('admin_init', 'gpt_register_notice_settings');

function gpt_notice_config_page() {
    if (isset($_POST['submit_notice_config']) && wp_verify_nonce($_POST['notice_config_nonce'], 'save_notice_config')) {
        
        if (isset($_POST['messenger_link'])) {
            $messenger_link = sanitize_url($_POST['messenger_link']);
            update_option('gpt_messenger_link', $messenger_link);
        }
        
        if (isset($_POST['gpt_error_notice_editor'])) {
            $notice_content = wp_kses_post($_POST['gpt_error_notice_editor']);
            update_option('gpt_error_notice_editor', $notice_content);
        }

        // Thêm xử lý cho thông báo tích điểm thành công
        if (isset($_POST['gpt_success_notice_editor'])) {
            $success_notice_content = wp_kses_post($_POST['gpt_success_notice_editor']);
            update_option('gpt_success_notice_editor', $success_notice_content);
        }

        if (isset($_POST['gpt_agree_terms_editor'])) {
            $success_notice_content = wp_kses_post($_POST['gpt_agree_terms_editor']);
            update_option('gpt_agree_terms_editor', $success_notice_content);
        }

        if (!empty($_FILES['gpt_logo_image']['name'])) {
            $uploaded_logo = wp_handle_upload($_FILES['gpt_logo_image'], ['test_form' => false]);
            if (!isset($uploaded_logo['error'])) {
                update_option('gpt_logo_image_url', esc_url_raw($uploaded_logo['url']));
            }
        }
        
        if (!empty($_FILES['gpt_messenger_icon']['name'])) {
            $uploaded_messenger = wp_handle_upload($_FILES['gpt_messenger_icon'], ['test_form' => false]);
            if (!isset($uploaded_messenger['error'])) {
                update_option('gpt_messenger_icon_url', esc_url_raw($uploaded_messenger['url']));
            }
        }
        
        if (!empty($_FILES['gpt_display_image']['name'])) {
            $uploaded_display = wp_handle_upload($_FILES['gpt_display_image'], ['test_form' => false]);
            if (!isset($uploaded_display['error'])) {
                update_option('gpt_display_image_url', esc_url_raw($uploaded_display['url']));
            }
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Thành công!</strong> Đã lưu cấu hình thông báo.</p>';
            echo '</div>';
        });
    }

    $notice_content          = get_option('gpt_error_notice_editor', '');
    $success_notice_content  = get_option('gpt_success_notice_editor', '');
    $gpt_agree_terms_editor  = get_option('gpt_agree_terms_editor', '');
    $messenger_link          = get_option('gpt_messenger_link', '');
    $logo_image_url          = get_option('gpt_logo_image_url', '');
    $messenger_icon_url      = get_option('gpt_messenger_icon_url', '');
    $display_image_url       = get_option('gpt_display_image_url', '');

    ?>
    <div class="bg-grey">
        <h1>Cấu hình thông báo</h1>
        <hr>
        <div class="gpt-tich-diem-form">
            <div class="messenger-status" style="padding: 10px; border-left: 4px solid #0073aa; background: #fff; margin-bottom: 20px;">
                <strong>Link Messenger hiện tại:</strong>
                <?php if (!empty($messenger_link)): ?>
                    <br><a href="<?php echo esc_url($messenger_link); ?>" target="_blank" style="color: #00a32a; font-weight: bold;">
                        <?php echo esc_url($messenger_link); ?>
                    </a>
                <?php else: ?>
                    <span style="color: #d63638; font-weight: bold;">Chưa có link Messenger</span>
                <?php endif; ?>
            </div>
            
            <!-- *** PHẢI THÊM enctype="multipart/form-data" ĐỂ UPLOAD FILE *** -->
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('save_notice_config', 'notice_config_nonce'); ?>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="messenger_link"><strong>Link Messenger:</strong></label>
                    <input type="url" 
                            id="messenger_link" 
                            name="messenger_link" 
                            value="<?php echo esc_attr($messenger_link); ?>" 
                            placeholder="https://m.me/your-page-name" 
                            class="large-text"
                            style="width: 100%;">
                    <p class="description">Nhập link Messenger của bạn (ví dụ: https://m.me/your-page-name)</p>
                </div>
                
                <!-- Thông báo lỗi -->
                <div class="form-group notice-section" style="margin-bottom: 20px; padding: 15px; background: #f0fff4; border-left: 4px solid #28a745; border-radius: 4px;">
                    <label for="gpt_error_notice_editor"><strong>📢 Thông báo hiển thị trang tích điểm:</strong></label>
                    <?php
                    wp_editor($notice_content, 'gpt_error_notice_editor', [
                        'textarea_name' => 'gpt_error_notice_editor',
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                        'tinymce' => true,
                        'quicktags' => true
                    ]);
                    ?>
                    <p class="description">Nội dung thông báo hiển thị khi chưa có client_id</p>
                    <div style="background: #e7f3ff; padding: 10px; border-radius: 4px; margin-top: 10px;">
                        <strong>💡 Biến có thể sử dụng:</strong>
                        <ul style="margin: 5px 0 0 20px;">
                            <li><code>{customer_name}</code> - Tên khách hàng</li>
                            <li><code>{points}</code> - Số điểm vừa tích</li>
                            <li><code>{product_name}</code> - Tên sản phẩm</li>
                            <li><code>{total_points}</code> - Tổng điểm hiện có</li>
                            <li><code>{store_name}</code> - Tên cửa hàng</li>
                        </ul>
                    </div>
                </div>

                <!-- Thông báo tích điểm thành công -->
                <div class="form-group notice-section" style="margin-bottom: 20px; padding: 15px; background: #f0fff4; border-left: 4px solid #28a745; border-radius: 4px;">
                    <label for="gpt_success_notice_editor"><strong>🎉 Tiêu đề thông báo tích điểm thành công:</strong></label>
                    <?php
                    wp_editor($success_notice_content, 'gpt_success_notice_editor', [
                        'textarea_name' => 'gpt_success_notice_editor',
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                        'tinymce' => true,
                        'quicktags' => true
                    ]);
                    ?>                    
                </div>
                <hr>
                <div class="form-group notice-section" style="margin-bottom: 20px; padding: 15px; background: #f0fff4; border-left: 4px solid #28a745; border-radius: 4px;">
                    <label for="gpt_agree_terms_editor"><strong>🎉 Thông báo đồng ý điều khoản khi tích điểm:</strong></label>
                    <?php
                    wp_editor($gpt_agree_terms_editor, 'gpt_agree_terms_editor', [
                        'textarea_name' => 'gpt_agree_terms_editor',
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                        'tinymce' => true,
                        'quicktags' => true
                    ]);
                    ?>                    
                </div>
                <hr>
                <!-- Upload files section -->
                <div class="upload-section" style="margin-top: 30px;">
                    <h3 style="margin-top: 0; color: #333;">Cấu hình hình ảnh hiển thị form tích điểm</h3>
                    <div class="form-wrap">
                        <div class="form-group">
                            <label for="gpt_logo_image"><strong>Logo website:</strong></label>
                            <input type="file" name="gpt_logo_image" accept="image/*">
                            <?php 
                                if ($logo_image_url) {
                                    echo '<br><img src="' . esc_url($logo_image_url) . '" style="max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;" alt="Logo hiện tại">';
                                    echo '<p class="description">Logo hiện tại</p>';
                                }
                            ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="gpt_messenger_icon"><strong>Icon messenger:</strong></label>
                            <input type="file" name="gpt_messenger_icon" accept="image/*">
                            <?php 
                                if ($messenger_icon_url) {
                                    echo '<br><img src="' . esc_url($messenger_icon_url) . '" style="max-width: 100px; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;" alt="Icon messenger hiện tại">';
                                    echo '<p class="description">Icon messenger hiện tại</p>';
                                }
                            ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="gpt_display_image"><strong>Ảnh hiển thị:</strong></label>
                            <input type="file" name="gpt_display_image" accept="image/*">
                            <?php 
                                if ($display_image_url) {
                                    echo '<br><img src="' . esc_url($display_image_url) . '" style="max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;" alt="Ảnh hiển thị hiện tại">';
                                    echo '<p class="description">Ảnh hiển thị hiện tại</p>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="submit">
                    <input type="submit" name="submit_notice_config" class="button-primary" value="Lưu cấu hình thông báo">
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .gpt_form_wrap {
        max-width: 800px;
    }

    .form-wrap {
        display: flex;
        gap: 24px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input[type="url"] {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .form-group input[type="url"]:focus {
        border-color: #007cba;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
    }
    
    .form-group input[type="file"] {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        width: 100%;
        max-width: 400px;
    }
    
    .description {
        color: #666;
        font-style: italic;
        margin-top: 5px;
        font-size: 13px;
    }
    
    .notice-section {
        transition: all 0.3s ease;
    }
    
    .notice-section:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .upload-section h3 {
        border-bottom: 1px solid #e1e1e1;
        padding-bottom: 10px;
    }
    
    code {
        background: #f1f1f1;
        padding: 2px 4px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        color: #d63384;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#messenger_link').on('input', function() {
            const link = $(this).val();
            const previewDiv = $('.messenger-status');
            
            if (link.trim() !== '') {
                previewDiv.html(
                    '<strong>Link Messenger hiện tại:</strong><br>' +
                    '<a href="' + link + '" target="_blank" style="color: #00a32a; font-weight: bold;">' + 
                    link + 
                    '</a>'
                );
            } else {
                previewDiv.html(
                    '<strong>Link Messenger hiện tại:</strong> ' +
                    '<span style="color: #d63638; font-weight: bold;">Chưa có link Messenger</span>'
                );
            }
        });
        
        // Preview cho success message
        $('#gpt_success_notice_editor').on('input', function() {
            // Có thể thêm preview real-time nếu cần
        });
    });
    </script>
    <?php
}

function gpt_register_notice_settings() {
    register_setting('gpt_notice_settings', 'gpt_error_notice_editor');

    add_settings_section(
        'gpt_notice_main',
        'Thông báo khi thiếu client_id',
        null,
        'gpt_notice_settings_page'
    );

    add_settings_field(
        'gpt_error_notice_editor',
        'Nội dung hiển thị',
        'gpt_render_error_notice_editor',
        'gpt_notice_settings_page',
        'gpt_notice_main'
    );
}

function gpt_render_error_notice_editor() {
    $content = get_option('gpt_error_notice_editor', '');
    wp_editor($content, 'gpt_error_notice_editor', [
        'textarea_name' => 'gpt_error_notice_editor',
        'media_buttons' => false,
        'textarea_rows' => 6,
        'teeny' => true,
    ]);
}

add_action('admin_footer', function () {
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('.gpt-select2').select2({
                width: '100%',
                placeholder: 'Chọn...',
                allowClear: true
            });
        });
    </script>
    <?php
});

function gpt_render_instructions_page() {
    ?>
    <div class="gpt-instructions-container">
        <div class="gpt-content">
            <section class="gpt-section">
                <h2>Hướng dẫn sử dụng shortcode hiển thị</h2>
                <div class="examples-container">
                    <div class="example-item">
                        <h4>1. Shortcode hiển thị form tích điểm</h4>
                        <div class="prompt-example">
                            [gpt_form_accumulate_code]
                        </div>
                    </div>
                    <div class="example-item">
                        <h4>2. Shortcode tra cứu điểm của người dùng</h4>
                        <div class="prompt-example">
                            [gpt_lookup_point_of_user]
                        </div>
                    </div>
                    <div class="example-item">
                        <h4>3. Shortcode tra cứu điểm của cửa hàng</h4>
                        <div class="prompt-example">
                            [gpt_lookup_store_aff]
                        </div>
                    </div>
                    <div class="example-item">
                        <h4>4. Shortcode hiển thị bảng xếp hạng</h4>
                        <div class="prompt-example">
                            [gpt_user_ranking_dashboard]
                        </div>
                    </div>
                    <div class="example-item">
                        <h4>5. Shortcode hiển thị danh sách sản phẩm đổi quà</h4>
                        <div class="prompt-example">
                            [list_of_products_to_redeem_gifts]
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <?php
}



