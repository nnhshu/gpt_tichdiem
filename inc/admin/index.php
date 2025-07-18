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
// Channel
include plugin_dir_path(__FILE__) . './channels/index.php';
// Ranking
include plugin_dir_path(__FILE__) . './ranking/index.php';
// Tích & đổi điểm
include plugin_dir_path(__FILE__) . './gpt_manage_points_list/accumulate_list.php';
include plugin_dir_path(__FILE__) . './gpt_manage_points_list/exchange_list.php';
include plugin_dir_path(__FILE__) . './gpt_manage_points_list/warning.php';
// cửa hàng & nhân viên
include plugin_dir_path(__FILE__) . './gpt_manage_employee_store/store.php';
include plugin_dir_path(__FILE__) . './gpt_manage_employee_store/employee.php';


add_action('admin_menu', function () {
    add_menu_page('GPT Mã Cào Sản Phẩm', 'GPT Mã Cào Sản Phẩm', 'edit_posts', 'gpt-macao', '__return_null', 'dashicons-tickets', 6);

    if (current_user_can('manage_options')) {
        // Cấu hình tab
        add_submenu_page(
            'gpt-macao',
            'Cấu hình hệ thống',
            'Cấu hình hệ thống',
            'manage_options',
            'gpt-config',
            'gpt_render_config_tabs_page'
        );
        add_submenu_page(
            'gpt-macao',
            'Quản lý mã',
            'Quản lý mã',
            'manage_options',
            'gpt-config-barcode',
            'gpt_render_config_barcode_page'
        );
        add_submenu_page(
            'gpt-macao',
            'DS cửa hàng & nhân viên',
            'DS cửa hàng & nhân viên',
            'manage_options',
            'gpt-store-employee',
            'gpt_render_store_employee_page'
        );
        add_submenu_page(
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
        );

        

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

    if (current_user_can('edit_posts')) {
        add_submenu_page('gpt-macao', 'Tra cứu mã cào', 'Tra cứu mã cào', 'edit_posts', 'gpt-tra-cuu-ma-cao', 'gpt_render_order_check_lookup');
    }
});

// Quản lí cửa hàng & nhân viên
function gpt_render_store_employee_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'store';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-store-employee&tab=store" class="nav-tab ' . ($active_tab === 'store' ? 'nav-tab-active' : '') . '">Cửa hàng</a>';
    echo '<a href="?page=gpt-store-employee&tab=employee" class="nav-tab ' . ($active_tab === 'employee' ? 'nav-tab-active' : '') . '">Nhân viên</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        case 'employee':
            gpt_render_employee_tab();
            break;
        case 'store':
        default:
            gpt_render_store_tab();
            break;
    }

    echo '</div></div>';
}

// Quản lí barcode
function gpt_render_config_barcode_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'barcode';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';

    echo '<a href="?page=gpt-config-barcode&tab=barcode" class="nav-tab ' . ($active_tab === 'barcode' ? 'nav-tab-active' : '') . '">Danh sách mã</a>';
    echo '<a href="?page=gpt-config-barcode&tab=browse" class="nav-tab ' . ($active_tab === 'browse' ? 'nav-tab-active' : '') . '">Duyệt mã</a>';

    echo '</h1>';
    echo '<div class="tab-content">';

    switch ($active_tab) {
        case 'browse':
            gpt_render_duyet_barcode_page();
            break;
        case 'barcode':
        default:
            gpt_macao_list_page();
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
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'cau-hinh-chung';

    echo '<div class="wrap">';
    echo '<h1 class="nav-tab-wrapper">';
    
    echo '<a href="?page=gpt-config&tab=cau-hinh-chung" class="nav-tab ' . ($active_tab == 'cau-hinh-chung' ? 'nav-tab-active' : '') . '">Cấu hình chung</a>';
    echo '<a href="?page=gpt-config&tab=thong-bao" class="nav-tab ' . ($active_tab == 'thong-bao' ? 'nav-tab-active' : '') . '">Cấu hình thông báo</a>';
    echo '<a href="?page=gpt-config&tab=kenh-ban-hang" class="nav-tab ' . ($active_tab == 'kenh-ban-hang' ? 'nav-tab-active' : '') . '">Cấu hình kênh BH</a>';

    echo '</h1>';

    echo '<div class="tab-content">';
    switch ($active_tab) {
        case 'thong-bao':
            gpt_notice_config_page();
            break;
        case 'kenh-ban-hang':
            gpt_render_sales_channels_page();
            break;
        default:
            gpt_config_page();
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
        $new_content = wp_kses_post($_POST['gpt_error_notice_editor'] ?? '');
        update_option('gpt_error_notice_editor', $new_content);
        echo '<div class="updated notice is-dismissible"><p>Đã lưu thông báo lỗi.</p></div>';
        $content = $new_content;
    } else {
        $content = get_option('gpt_error_notice_editor', '');
    }

    echo '<div class="wrap"><h1>Cấu hình thông báo lỗi</h1>';
    echo '<form method="post">';
    wp_editor($content, 'gpt_error_notice_editor', [
        'textarea_name' => 'gpt_error_notice_editor',
        'media_buttons' => false,
        'textarea_rows' => 6,
        'teeny' => true,
    ]);
    submit_button('Lưu thông báo');
    echo '</form></div>';
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

