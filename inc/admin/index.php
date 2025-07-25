<?php
include plugin_dir_path(__FILE__) . './config_page.php';
include plugin_dir_path(__FILE__) . './order_check_lookup.php';
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


add_action('admin_menu', function () {
    add_menu_page('Cấu hình tem công nghệ', 'Cấu hình tem công nghệ', 'edit_posts', 'gpt-macao', '__return_null', 'dashicons-tickets', 5);
    add_menu_page('Quản lý xuất nhập tồn tem công nghệ', 'Quản lý xuất nhập tồn tem công nghệ', 'edit_posts', 'gpt-manager-tem', '__return_null', 'dashicons-tickets', 5);
    add_menu_page('Báo cáo tem công nghệ', 'Báo cáo tem công nghệ', 'edit_posts', 'gpt-analytics-reports', '__return_null', 'dashicons-tickets', 5);

    if (current_user_can('manage_options')) {
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
            'Cấu hình kênh bán',
            'Cấu hình kênh bán',
            'manage_options',
            'gpt-store-employee',
            'gpt_render_store_employee_page'
        );
        /*add_submenu_page(
            'gpt-macao',
            'DS tích & đổi điểm',
            'DS tích & đổi điểm',
            'manage_options',
            'gpt-list-points-report',
            'gpt_render_config_list_points_page'
        );
        add_submenu_page(
            'gpt-macao',
            'Quản lí Affiliate',
            'Quản lí Affiliate',
            'manage_options',
            'gpt-affiliate-report',
            'gpt_render_config_affiliate_page'
        );*/

        

        // add_submenu_page('gpt-macao', 'Cấu hình chung', 'Cấu hình chung', 'manage_options', 'gpt-macao', 'gpt_config_page');
        // add_submenu_page('gpt-macao', 'Cấu hình thông báo', 'Cấu hình thông báo', 'manage_options', 'gpt-cau-hinh-thong-bao', 'gpt_notice_config_page');
        // add_submenu_page('gpt-macao', 'Cấu hình kênh BH', 'Cấu hình kênh BH', 'manage_options', 'gpt-sales-channels', 'gpt_render_sales_channels_page');
        // add_submenu_page(
        //     'gpt-macao',
        //     'Duyệt mã cào',
        //     'Duyệt mã cào',
        //     'manage_options',
        //     'gpt-browse-barcodes',
        //     'gpt_render_duyet_barcode_page'
        // );
        // add_submenu_page('gpt-macao', 'DS mã cào', 'DS mã cào', 'manage_options', 'gpt-danh-sach-ma-cao', 'gpt_macao_list_page');
        // add_submenu_page('gpt-macao', 'DSKH tích điểm', 'DSKH tích điểm', 'manage_options', 'gpt-khach-hang', 'gpt_customer_list_page');
        // add_submenu_page('gpt-macao', 'DSKH đổi điểm', 'DSKH đổi điểm', 'manage_options', 'gpt-doi-diem-list', 'gpt_render_exchange_list_page');
        // add_submenu_page('gpt-macao', 'DS cảnh báo', 'DS cảnh báo', 'manage_options', 'gpt-canh-bao-vi-tri', 'gpt_location_warnings_page');
        // add_submenu_page('gpt-macao', 'DS người được giới thiệu', 'Người được giới thiệu', 'manage_options', 'gpt-nguoi-duoc-gioi-thieu', 'gpt_referral_list_page');
        // add_submenu_page('gpt-macao', 'DS người giới thiệu thành công', 'Giới thiệu thành công', 'manage_options', 'gpt-nguoi-gioi-thieu-thanh-cong', 'gpt_successful_referrer_page');
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
        

        // add_submenu_page('gpt-macao', 'Cấu hình chung', 'Cấu hình chung', 'manage_options', 'gpt-macao', 'gpt_config_page');
        // add_submenu_page('gpt-macao', 'Cấu hình thông báo', 'Cấu hình thông báo', 'manage_options', 'gpt-cau-hinh-thong-bao', 'gpt_notice_config_page');
        // add_submenu_page('gpt-macao', 'Cấu hình kênh BH', 'Cấu hình kênh BH', 'manage_options', 'gpt-sales-channels', 'gpt_render_sales_channels_page');
        // add_submenu_page(
        //     'gpt-macao',
        //     'Duyệt mã cào',
        //     'Duyệt mã cào',
        //     'manage_options',
        //     'gpt-browse-barcodes',
        //     'gpt_render_duyet_barcode_page'
        // );
        // add_submenu_page('gpt-macao', 'DS mã cào', 'DS mã cào', 'manage_options', 'gpt-danh-sach-ma-cao', 'gpt_macao_list_page');
        // add_submenu_page('gpt-macao', 'DSKH tích điểm', 'DSKH tích điểm', 'manage_options', 'gpt-khach-hang', 'gpt_customer_list_page');
        // add_submenu_page('gpt-macao', 'DSKH đổi điểm', 'DSKH đổi điểm', 'manage_options', 'gpt-doi-diem-list', 'gpt_render_exchange_list_page');
        // add_submenu_page('gpt-macao', 'DS cảnh báo', 'DS cảnh báo', 'manage_options', 'gpt-canh-bao-vi-tri', 'gpt_location_warnings_page');
        // add_submenu_page('gpt-macao', 'DS người được giới thiệu', 'Người được giới thiệu', 'manage_options', 'gpt-nguoi-duoc-gioi-thieu', 'gpt_referral_list_page');
        // add_submenu_page('gpt-macao', 'DS người giới thiệu thành công', 'Giới thiệu thành công', 'manage_options', 'gpt-nguoi-gioi-thieu-thanh-cong', 'gpt_successful_referrer_page');
    }   

});

// Quản lí cửa hàng & nhân viên
function gpt_render_store_employee_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'channels';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-store-employee&tab=channels" class="nav-tab ' . ($active_tab === 'channels' ? 'nav-tab-active' : '') . '">Kênh bán hàng</a>';
    echo '<a href="?page=gpt-store-employee&tab=distributor" class="nav-tab ' . ($active_tab === 'distributor' ? 'nav-tab-active' : '') . '">Nhà phân phối</a>';
    echo '<a href="?page=gpt-store-employee&tab=store" class="nav-tab ' . ($active_tab === 'store' ? 'nav-tab-active' : '') . '">Cửa hàng</a>';
    // echo '<a href="?page=gpt-store-employee&tab=employee" class="nav-tab ' . ($active_tab === 'employee' ? 'nav-tab-active' : '') . '">Nhân viên</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        // case 'employee':
        //     gpt_render_employee_tab();
        //     break;
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

    echo '</div></div>';
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
            gpt_setting_identifier_page();
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
        if (!empty($_FILES['gpt_logo_image']['name'])) {
            $uploaded_logo = wp_handle_upload($_FILES['gpt_logo_image'], ['test_form' => false]);
            if (!isset($uploaded_logo['error'])) {
                update_option('gpt_logo_image_url', $uploaded_logo['url']);
            }
        }
        
        if (!empty($_FILES['gpt_messenger_icon']['name'])) {
            $uploaded_messenger = wp_handle_upload($_FILES['gpt_messenger_icon'], ['test_form' => false]);
            if (!isset($uploaded_messenger['error'])) {
                update_option('gpt_messenger_icon_url', $uploaded_messenger['url']);
            }
        }
        
        if (!empty($_FILES['gpt_display_image']['name'])) {
            $uploaded_display = wp_handle_upload($_FILES['gpt_display_image'], ['test_form' => false]);
            if (!isset($uploaded_display['error'])) {
                update_option('gpt_display_image_url', $uploaded_display['url']);
            }
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Thành công!</strong> Đã lưu cấu hình thông báo.</p>';
            echo '</div>';
        });
    }

    $notice_content = get_option('gpt_error_notice_editor', '');
    $messenger_link = get_option('gpt_messenger_link', 'https://m.me/700792956451509?ref=.f.2dfe2f2acdbb4fa281d6c5bd018478f0');
    $logo_image_url = get_option('gpt_logo_image_url', 'https://bimbosan.superhub.vn/wp-content/uploads/sites/1108/2025/07/Bimbosan_Logo_no-Claim-1024x267.png');
    $messenger_icon_url = get_option('gpt_messenger_icon_url', 'https://bimbosan.superhub.vn/wp-content/uploads/sites/1108/2025/07/logo-messenger.png');
    $display_image_url = get_option('gpt_display_image_url', 'https://bimbosan.superhub.vn/wp-content/uploads/sites/1108/2025/06/67b49d34db548cf82c4c01e5_cows.png');

    
    ?>
    <div class="wrap">
        <h1>Cấu hình thông báo</h1>
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
            
            <form method="post" action="">
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
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gpt_error_notice_editor"><strong>Nội dung thông báo:</strong></label>
                    <?php
                    wp_editor($notice_content, 'gpt_error_notice_editor', [
                        'textarea_name' => 'gpt_error_notice_editor',
                        'media_buttons' => false,
                        'textarea_rows' => 8,
                        'tinymce' => true,
                        'quicktags' => true
                    ]);
                    ?>
                    <p class="description">Cấu hình nội dung thông báo sẽ hiển thị cho người dùng</p>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gpt_logo_image"><strong>Logo Website:</strong></label>
                    <input type="file" name="gpt_logo_image" accept="image/*">
                    <?php 
                         if ($logo_image_url) {
                            echo '<br><img src="' . esc_url($logo_image_url) . '" style="max-width: 200px; margin-top: 10px;" alt="Logo hiện tại">';
                            echo '<p class="description">Logo hiện tại</p>';
                        }
                    ?>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gpt_messenger_icon"><strong>Icon Messenger:</strong></label>
                    <input type="file" name="gpt_messenger_icon" accept="image/*">
                    <?php 
                        if ($messenger_icon_url) {
                            echo '<br><img src="' . esc_url($messenger_icon_url) . '" style="max-width: 100px; margin-top: 10px;" alt="Icon messenger hiện tại">';
                            echo '<p class="description">Icon messenger hiện tại</p>';
                        }
                    ?>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gpt_display_image"><strong>Ảnh Hiển Thị:</strong></label>
                    <input type="file" name="gpt_display_image" accept="image/*">
                    <?php 
                        if ($display_image_url) {
                            echo '<br><img src="' . esc_url($display_image_url) . '" style="max-width: 200px; margin-top: 10px;" alt="Ảnh hiển thị hiện tại">';
                            echo '<p class="description">Ảnh hiển thị hiện tại</p>';
                        }
                    ?>
                </div>
                <div class="submit">
                    <input type="submit" name="submit_notice_config" class="button-primary" value="💾 Lưu cấu hình thông báo">
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .gpt_form_wrap {
        max-width: 800px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .form-group input[type="url"] {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .form-group input[type="url"]:focus {
        border-color: #007cba;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
    }
    
    .description {
        color: #666;
        font-style: italic;
        margin-top: 5px;
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

