<?php
/**
 * Plugin Name: BIZGPT AI TEAM - Tích điểm mã cào sản phẩm
 * Description: Quản lý mã cào và tích điểm sản phẩm.
 * Version: 1.0
 * Author: BIZGPT AI TEAM
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
define('BIZGPT_PLUGIN_DB_VERSION', '1.15');
define ('BIZGPT_PLUGIN_WP_BARCODE', $wpdb->prefix . 'gpt_barcode');
define ('BIZGPT_PLUGIN_WP_LOGS', $wpdb->prefix . 'gpt_logs');
// define ('BIZGPT_PLUGIN_WP_CUSTOMERS', $wpdb->prefix . 'gpt_customers');
define ('BIZGPT_PLUGIN_WP_LOCATION_WARNINGS', $wpdb->prefix . 'gpt_location_warnings');
define ('BIZGPT_PLUGIN_WP_AFFILIATE_STATS', $wpdb->prefix . 'gpt_affiliate_stats');
define ('BIZGPT_PLUGIN_WP_AFFILIATE_LOGS', $wpdb->prefix . 'gpt_affiliate_logs');
define ('BIZGPT_PLUGIN_WP_SAVE_USERS', $wpdb->prefix . 'gpt_users');
define ('BIZGPT_PLUGIN_WP_RANKINGS', $wpdb->prefix . 'gpt_rankings');
define ('BIZGPT_PLUGIN_WP_ORDER_PRODUCTS', $wpdb->prefix . 'gpt_product_orders');
define ('BIZGPT_PLUGIN_WP_ORDER_PRODUCTS_SELL_OUT', $wpdb->prefix . 'gpt_products_sold');
define ('BIZGPT_PLUGIN_WP_CHANNELS', $wpdb->prefix . 'gpt_channels');
define ('BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT', $wpdb->prefix . 'gpt_exchange_gifts');
define ('BIZGPT_PLUGIN_WP_STORE_LIST', $wpdb->prefix . 'gpt_stores');
define ('BIZGPT_PLUGIN_WP_EMPLOYEES', $wpdb->prefix . 'gpt_employees');
define('BIZGPT_PLUGIN_WP_BOX_MANAGER', $wpdb->prefix . 'gpt_box_manager');
define('BIZGPT_PLUGIN_WP_DISTRIBUTORS', $wpdb->prefix . 'gpt_distributors');
define('BIZGPT_PLUGIN_WP_REFUND_ORDER', $wpdb->prefix . 'gpt_refund_order');
define('BIZGPT_PLUGIN_WP_PRODUCT_LOT', $wpdb->prefix . 'gpt_lot_of_products');

// Admin
include plugin_dir_path(__FILE__) . 'inc/admin/index.php';
include plugin_dir_path(__FILE__) . 'inc/database/sql.php';
//Role
include plugin_dir_path(__FILE__) . 'user_role.php';
// Front end
include plugin_dir_path(__FILE__) . 'inc/post_type/order_check.php';
include plugin_dir_path(__FILE__) . 'inc/post_type/import_check.php';
include plugin_dir_path(__FILE__) . 'inc/post_type/refund_check.php';

include plugin_dir_path(__FILE__) . 'frontend/view_form.php';
include plugin_dir_path(__FILE__) . 'frontend/ranking.php';
include plugin_dir_path(__FILE__) . 'frontend/index.php';
include plugin_dir_path(__FILE__) . './user_points.php';
include plugin_dir_path(__FILE__) . 'frontend/shortcode.php';

// $status_map = [
//     'pending'         => 'Chờ duyệt',
//     'unused'    => 'Chưa sử dụng',
//     'used'      => 'Đã sử dụng',
// ];

add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'gpt') !== false) { 
        wp_enqueue_style('tabler-icons','https://unpkg.com/@tabler/icons/iconfont/tabler-icons.min.css',[],'latest');
        wp_enqueue_style('gpt-admin-style', plugin_dir_url(__FILE__) . 'assets/css/gpt-admin.css');
    }
});

function load_select2_assets() {
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
}
add_action('wp_enqueue_scripts', 'load_select2_assets');

function getClientIdFromUrlPage() {
    if(isset($_GET['client_id'])) {
        return $_GET['client_id'];
    }
    return null;
}

function get_status_display_text($status) {
    $status_map = [
        'pending' => 'Chờ duyệt',
        'completed' => 'Hoàn thành'
    ];
    return isset($status_map[$status]) ? $status_map[$status] : 'Chờ duyệt';
}

function get_or_create_user_points($phone_number, $user_data = []) {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    // Kiểm tra user đã tồn tại chưa
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE phone_number = %s", $phone_number
    ));
    
    if ($user) {
        return $user;
    }
    
    // Tạo user mới
    $default_data = [
        'phone_number' => $phone_number,
        'full_name' => '',
        'email' => '',
        'address' => '',
        'province' => '',
        'district' => '',
        'ward' => '',
        'total_points' => 0,
        'earned_points' => 0,
        'redeemed_points' => 0,
        'affiliate_points' => 0,
        'total_transactions' => 0,
        'total_referrals' => 0,
        'first_transaction_date' => current_time('mysql'),
        'last_transaction_date' => current_time('mysql'),
        'last_activity_date' => current_time('mysql'),
        'user_status' => 'active'
    ];
    
    $insert_data = array_merge($default_data, $user_data);
    
    $result = $wpdb->insert($table_name, $insert_data);
    
    if ($result) {
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE phone_number = %s", $phone_number
        ));
    }
    
    return false;
}

/**
 * Cập nhật điểm cho user
 */
function update_user_points($phone_number, $points_change, $transaction_type = 'earned', $user_info = []) {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    // Lấy hoặc tạo user
    $user = get_or_create_user_points($phone_number, $user_info);
    
    if (!$user) {
        return false;
    }
    
    $update_data = [
        'last_activity_date' => current_time('mysql')
    ];
    
    // Xử lý theo loại giao dịch
    switch ($transaction_type) {
        case 'earned':
            $update_data['total_points'] = $user->total_points + $points_change;
            $update_data['earned_points'] = $user->earned_points + $points_change;
            $update_data['total_transactions'] = $user->total_transactions + 1;
            $update_data['last_transaction_date'] = current_time('mysql');
            
            // Cập nhật first_transaction_date nếu là giao dịch đầu tiên
            if ($user->total_transactions == 0) {
                $update_data['first_transaction_date'] = current_time('mysql');
            }
            break;
            
        case 'redeemed':
            // Kiểm tra đủ điểm để đổi không
            if ($user->total_points < $points_change) {
                return ['success' => false, 'message' => 'Không đủ điểm để thực hiện giao dịch'];
            }
            $update_data['total_points'] = $user->total_points - $points_change;
            $update_data['redeemed_points'] = $user->redeemed_points + $points_change;
            break;
            
        case 'affiliate':
            $update_data['total_points'] = $user->total_points + $points_change;
            $update_data['affiliate_points'] = $user->affiliate_points + $points_change;
            $update_data['total_referrals'] = $user->total_referrals + 1;
            break;
            
        case 'adjustment':
            // Điều chỉnh điểm (có thể âm hoặc dương)
            $update_data['total_points'] = max(0, $user->total_points + $points_change);
            break;
    }
    
    // Cập nhật thông tin cá nhân nếu có
    if (!empty($user_info['full_name'])) {
        $update_data['full_name'] = $user_info['full_name'];
    }
    if (!empty($user_info['address'])) {
        $update_data['address'] = $user_info['address'];
    }
    if (!empty($user_info['province'])) {
        $update_data['province'] = $user_info['province'];
    }
    if (!empty($user_info['district'])) {
        $update_data['district'] = $user_info['district'];
    }
    if (!empty($user_info['ward'])) {
        $update_data['ward'] = $user_info['ward'];
    }
    if (!empty($user_info['email'])) {
        $update_data['email'] = $user_info['email'];
    }
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['phone_number' => $phone_number],
        array_fill(0, count($update_data), '%s'),
        ['%s']
    );
    
    if ($result !== false) {
        // Lấy thông tin user sau khi cập nhật
        $updated_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE phone_number = %s", $phone_number
        ));
        
        return [
            'success' => true,
            'user' => $updated_user,
            'message' => 'Cập nhật điểm thành công'
        ];
    }
    
    return ['success' => false, 'message' => 'Lỗi khi cập nhật điểm'];
}

/**
 * Lấy thông tin điểm của user
 */
function get_user_points_info($phone_number) {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE phone_number = %s", $phone_number
    ));
    
    if ($user) {
        return [
            'exists' => true,
            'user' => $user,
            'total_points' => $user->total_points,
            'earned_points' => $user->earned_points,
            'redeemed_points' => $user->redeemed_points,
            'affiliate_points' => $user->affiliate_points,
            'total_transactions' => $user->total_transactions,
            'total_referrals' => $user->total_referrals,
            'member_since' => $user->created_at,
            'last_activity' => $user->last_activity_date
        ];
    }
    
    return [
        'exists' => false,
        'total_points' => 0,
        'earned_points' => 0,
        'redeemed_points' => 0,
        'affiliate_points' => 0,
        'total_transactions' => 0,
        'total_referrals' => 0
    ];
}

/**
 * Kiểm tra user có tồn tại cho affiliate
 */
function check_user_exists_for_affiliate($phone_number) {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT phone_number, full_name, total_points, total_transactions, total_referrals, created_at 
         FROM $table_name WHERE phone_number = %s", 
        $phone_number
    ));
    
    if ($user) {
        return [
            'exists' => true,
            'name' => $user->full_name ?: 'Người dùng',
            'total_points' => $user->total_points,
            'total_transactions' => $user->total_transactions,
            'total_referrals' => $user->total_referrals,
            'member_since' => $user->created_at,
            'is_verified' => $user->total_transactions > 0
        ];
    }
    
    return [
        'exists' => false,
        'name' => '',
        'total_points' => 0,
        'total_transactions' => 0,
        'total_referrals' => 0,
        'is_verified' => false
    ];
}

// Hàm xử lý affiliate reward
function process_affiliate_reward($referrer_phone, $referrer_name, $customer_points, $customer_name, $customer_phone, $clientID, $code, $session, $store_name, $location, $province, $ward, $address, $product_name) {
    // Kiểm tra điều kiện
    if (empty($referrer_phone) || !get_option('affiliate_enabled', 0)) {
        return 0;
    }
    
    // Kiểm tra điểm tối thiểu
    $min_points_required = get_option('affiliate_min_points_required', 1);
    if ($customer_points < $min_points_required) {
        return 0;
    }
    
    // Kiểm tra không tự giới thiệu
    if ($referrer_phone === $customer_phone) {
        return 0;
    }
    
    // Tính điểm thưởng
    $affiliate_points = get_option('affiliate_points_per_referral', 10);
    
    // Lưu giao dịch affiliate vào bảng logs và user points
    $affiliate_log_data = array(
        'user_id' => 0,
        'client_id' => $clientID,
        'barcode' => $code . '_AFFILIATE',
        'session_code' => $session,
        'barcode_status' => 'used',
        'ten_khach_hang' => $referrer_name ?: 'Người giới thiệu',
        'so_dien_thoai' => $referrer_phone,
        'point_change' => $affiliate_points,
        'product' => "Điểm thưởng giới thiệu: $customer_name",
        'store' => $store_name,
        'point_location' => $location,
        'address' => "Thưởng affiliate từ: $address",
        'province' => $province,
        'xa_phuong' => $ward,
        'transaction_type' => 'affiliate_reward',
        'product_name' => 'Affiliate Reward',
        'phone_referrer' => $customer_phone,
        'referrer_name' => $customer_name,
        'is_affiliate_reward' => 1
    );
    
    // Lưu vào database (sẽ tự động cập nhật cả logs và user points)
    $result = enhanced_bizgpt_insert_point_log($affiliate_log_data);
    
    if ($result) {
        // Cập nhật thống kê affiliate
        update_affiliate_stats($referrer_phone, $referrer_name, $affiliate_points);
        
        // Gửi thông báo
        // send_affiliate_notification($referrer_phone, $referrer_name, $affiliate_points, $customer_name);
        
        return $affiliate_points;
    }
    
    return 0;
}

// Cập nhật thống kê affiliate
function update_affiliate_stats($referrer_phone, $referrer_name, $points_earned) {
    global $wpdb;
    $stats_table = BIZGPT_PLUGIN_WP_AFFILIATE_STATS;
    
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $stats_table WHERE referrer_phone = %s", $referrer_phone
    ));
    
    if ($existing) {
        // Cập nhật thống kê hiện có
        $wpdb->update(
            $stats_table,
            [
                'referrer_name' => $referrer_name ?: $existing->referrer_name,
                'total_referrals' => $existing->total_referrals + 1,
                'total_points_earned' => $existing->total_points_earned + $points_earned,
                'last_referral_date' => current_time('mysql')
            ],
            ['referrer_phone' => $referrer_phone],
            ['%s', '%d', '%d', '%s'],
            ['%s']
        );
    } else {
        // Tạo mới
        $wpdb->insert(
            $stats_table,
            [
                'referrer_phone' => $referrer_phone,
                'referrer_name' => $referrer_name,
                'total_referrals' => 1,
                'total_points_earned' => $points_earned,
                'first_referral_date' => current_time('mysql'),
                'last_referral_date' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s']
        );
    }
}

// Gửi thông báo affiliate
function send_affiliate_notification($referrer_phone, $referrer_name, $points_earned, $customer_name) {
    // Lấy tổng điểm hiện tại của người giới thiệu
    $total_points = bizgpt_get_current_points($referrer_phone);
    
    // Lấy template tin nhắn
    $message_template = get_option('affiliate_notification_message', '🎉 Chúc mừng! Bạn vừa nhận được {points} điểm từ việc giới thiệu {customer_name} tích điểm. Tổng điểm hiện tại: {total_points}');
    
    // Replace placeholders
    $message = str_replace(
        ['{points}', '{customer_name}', '{total_points}'],
        [$points_earned, $customer_name, $total_points],
        $message_template
    );
    
    // Gửi tin nhắn (tích hợp với hệ thống chatbot hiện có)
    $json_content = json_encode([
        "messages" => [
            ["text" => $message]
        ]
    ]);
    
    // Uncomment để gửi thực tế
    // send_mess_bizgpt($json_content, $referrer_phone);
    
    // Log để debug
    error_log("Affiliate notification sent to $referrer_phone: $message");
}

function gpt_handle_barcode_tracking() {
    if (!is_page('tich-diem-ma-cao')) return;
    if (empty($_GET['barcode'])) return;

    $barcode = sanitize_text_field($_GET['barcode']);
    $ip = gpt_get_user_ip();
    $ip_key = 'gpt_barcode_' . md5($ip);

    if (get_transient($ip_key)) {
        return;
    }

    $barcode_status = gpt_check_barcode_status($barcode);
    
    if ($barcode_status == 'used') {
        set_transient($ip_key, '', 5 * MINUTE_IN_SECONDS);
        $current_barcode = gpt_get_saved_barcode();
    } else {
        set_transient($ip_key, $barcode, 5 * MINUTE_IN_SECONDS);
    }
}

add_action('template_redirect', 'gpt_handle_barcode_tracking');

function gpt_get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function gpt_check_barcode_status($barcode) {
    global $wpdb;
    
    $table_name = BIZGPT_PLUGIN_WP_BARCODE;
    
    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$table_name} WHERE barcode = %s",
        $barcode
    ));
    
    return $status ? $status : 'not_found';
}

function gpt_get_saved_barcode() {
    $ip = gpt_get_user_ip();
    $ip_key = 'gpt_barcode_' . md5($ip);
    
    $saved_barcode = get_transient($ip_key);
    
    if ($saved_barcode === '') {
        return false;
    }
    
    return $saved_barcode;
}

function gpt_clear_barcode_transient() {
    $ip = gpt_get_user_ip();
    $ip_key = 'gpt_barcode_' . md5($ip);
    
    delete_transient($ip_key);
}


