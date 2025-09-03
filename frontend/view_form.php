<?php 

add_action('wp_ajax_check_macao_ajax', 'check_macao_ajax_callback');

add_action('wp_ajax_gpt_check_referrer', 'gpt_check_referrer_ajax');
add_action('wp_ajax_nopriv_gpt_check_referrer', 'gpt_check_referrer_ajax');
add_action('wp_ajax_nopriv_check_macao_ajax', 'check_macao_ajax_callback');

add_action('wp_ajax_gpt_get_stores_by_code_token', 'gpt_get_stores_by_code_token');
add_action('wp_ajax_gpt_get_employees_by_store', 'gpt_get_employees_by_store');

add_action('wp_ajax_get_current_address', 'bizgpt_ajax_get_current_address');
add_action('wp_ajax_nopriv_get_current_address', 'bizgpt_ajax_get_current_address');


add_shortcode('gpt_form_accumulate_code','gpt_form_accumulate_code');

function check_macao_ajax_callback() {
    global $wpdb;
    
    $table = BIZGPT_PLUGIN_WP_BARCODE;
    $code = sanitize_text_field($_POST['code']);
    $affiliate_percent_per_referral = get_option('affiliate_percent_per_referral');
    
    $code_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE LOWER(barcode) = LOWER(%s)",
        $code
    ));
    
    if ($code_info) {
        $custom_prod_id = $code_info->product_id;
        
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => 'custom_prod_id',
                    'value' => $custom_prod_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ];
        
        $products = get_posts($args);
        
        if (!empty($products)) {
            $product = $products[0];
            $product_id = $product->ID;
            $product_name = $product->post_title;
            $product_image = get_the_post_thumbnail_url($product->ID, 'medium');
            $product_price = wc_get_price_to_display(wc_get_product($product_id));
            
            $points = intval($code_info->point);
            $affiliate_percent = floatval($affiliate_percent_per_referral);
            
            if ($affiliate_percent == 0) {
                $affiliate_points = $points;
            } else {
                $affiliate_points = ($affiliate_percent / 100) * $points;
            }
            
            $product_info = [
                'name' => $product_name,
                'custom_prod_id' => $custom_prod_id,
                'price' => wc_price($product_price),
                'image' => $product_image ? $product_image : wc_placeholder_img_src(),
                'points' => $points,
                'affiliate_points' => round($affiliate_points, 2)
            ];
            
            wp_send_json_success([
                'channel' => $code_info->channel,
                'province' => $code_info->province,
                'product' => $product_info,
                'show_store_section' => in_array($code_info->channel, ['G', 'S', 'T']),
                'show_referrer_section' => !in_array($code_info->channel, ['G', 'S', 'T']),
                'affiliate_percent' => $affiliate_percent,
            ]);
            
        } else {
            wp_send_json_error('Không tìm thấy sản phẩm tương ứng.');
        }
    } else {
        wp_send_json_error('Mã cào không đúng!');
    }
}

function enhanced_bizgpt_insert_point_log($data = array()) {
    global $wpdb;

    $logs_table = BIZGPT_PLUGIN_WP_LOGS;
    $log_result = $wpdb->insert($logs_table, array(
        'user_id' => isset($data['user_id']) ? intval($data['user_id']) : 0,
        'client_id' => sanitize_text_field($data['client_id']),
        'barcode' => sanitize_text_field($data['barcode']),
        'session_code' => intval($data['session_code']),
        'barcode_status' => sanitize_text_field($data['barcode_status']),
        'customer_name' => sanitize_text_field($data['customer_name']),
        'phone_number' => sanitize_text_field($data['phone_number']),
        'point_change' => intval($data['point_change']),
        'product' => sanitize_text_field($data['product']),
        'store' => sanitize_text_field($data['store']),
        'point_location' => sanitize_text_field($data['point_location']),
        'address' => sanitize_text_field($data['address']),
        'province' => sanitize_text_field($data['province']),
        'ward' => sanitize_text_field($data['ward']),
        'transaction_type' => sanitize_text_field($data['transaction_type']),
        'product_name' => sanitize_text_field($data['product_name']),
        'phone_referrer' => isset($data['phone_referrer']) ? sanitize_text_field($data['phone_referrer']) : '',
        'referrer_name' => isset($data['referrer_name']) ? sanitize_text_field($data['referrer_name']) : '',
        'aff_by_store_id' => isset($data['aff_by_store_id']) ? sanitize_text_field($data['aff_by_store_id']) : '',
        'aff_by_employee_code' => isset($data['aff_by_employee_code']) ? sanitize_text_field($data['aff_by_employee_code']) : '',
        'is_affiliate_reward' => isset($data['is_affiliate_reward']) ? intval($data['is_affiliate_reward']) : 0,
        'user_province_from_ip' => isset($data['user_province_from_ip']) ? intval($data['user_province_from_ip']) : '',
        'u_status' => sanitize_text_field($data['u_status']) ? sanitize_text_field($data['u_status']) : '',
        'note_status' => sanitize_text_field($data['note_status']) ? sanitize_text_field($data['note_status']) : '',
        'created_at' => current_time('mysql')
    ));
    if ($log_result) {
        // Cập nhật bảng user points
        $phone_number = $data['phone_number'];
        $points = $data['point_change'];
        $transaction_type = $data['transaction_type'] ?? 'earned';
        
        // Thông tin user để cập nhật
        $user_info = [
            'full_name' => $data['customer_name'] ?? '',
            'address' => $data['address'] ?? '',
            'province' => $data['province'] ?? '',
            'district' => isset($data['user_district']) ? $data['user_district'] : '',
            'ward' => $data['ward'] ?? ''
        ];
        
        // Xác định loại giao dịch cho user points
        $point_type = 'earned';
        if (isset($data['is_affiliate_reward']) && $data['is_affiliate_reward']) {
            $point_type = 'affiliate';
        } elseif ($transaction_type === 'redeem' || $transaction_type === 'doi_diem') {
            $point_type = 'redeemed';
        }
        
        // Cập nhật user points
        update_user_points($phone_number, $points, $point_type, $user_info);
    }
    
    return $log_result;
}

function send_location_warning_to_admin($code, $username, $phone, $expected_province, $actual_province, $full_address, $product_name, $store_name, $location_mismatch, $location_warning) {
    global $wpdb;
    
    // Tạo bảng cảnh báo nếu chưa có
    $warning_table = BIZGPT_PLUGIN_WP_LOCATION_WARNINGS;
    
    // Lưu cảnh báo vào database
    $wpdb->insert(
        $warning_table,
        array(
            'barcode' => $code,
            'customer_name' => $username,
            'phone_number' => $phone,
            'province_expect' => $expected_province,
            'province_actual' => $actual_province,
            'full_address' => $full_address,
            'product' => $product_name,
            'store' => $store_name,
            'created_at' => current_time('mysql'),
            'status' => 'pending'
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Gửi email cho admin (tùy chọn)
    $admin_email = get_option('admin_email');
    $subject = "[CẢNH BÁO] Tích điểm sai vị trí - Mã: $code";
    $message = "
    🚨 CẢNH BÁO TÍCH ĐIỂM SAI VỊ TRÍ 🚨
    
    📝 Mã cào: $code
    👤 Khách hàng: $username
    📱 Số điện thoại: $phone
    🏪 Cửa hàng: $store_name
    🎁 Sản phẩm: $product_name
    
    📍 Vị trí mong đợi: $expected_province
    📍 Vị trí thực tế: $actual_province
    🏠 Địa chỉ đầy đủ: $full_address
    
    ⏰ Thời gian: " . current_time('Y-m-d H:i:s') . "
    
    Vui lòng kiểm tra và xử lý.
    ";

    $response_warning = array(
        'message' => $message,
        'status' => 200,
        'location_warning' => $location_mismatch ? $location_warning : null
    );
    // echo json_encode($response);
    
}

function get_location_warnings($status = 'all', $limit = 50) {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_LOCATION_WARNINGS;
    
    $where_clause = '';
    if ($status !== 'all') {
        $where_clause = $wpdb->prepare(" WHERE status = %s", $status);
    }
    
    $sql = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT $limit";
    
    return $wpdb->get_results($sql);
}

function bizgpt_get_current_points($phone) {
    global $wpdb;

    $logs_table = BIZGPT_PLUGIN_WP_LOGS;

    $total_points = $wpdb->get_var($wpdb->prepare("SELECT IFNULL(SUM(point_change), 0) FROM $logs_table WHERE phone_number = %s", $phone));

    return intval($total_points);
}

function bizgpt_get_current_address($phone) {
    global $wpdb;

    $users_table = BIZGPT_PLUGIN_WP_SAVE_USERS;
    $address = $wpdb->get_var($wpdb->prepare("SELECT address FROM $users_table WHERE phone_number = %s", $phone));
    return $address;
}

function bizgpt_ajax_get_current_address() {
    $phone = sanitize_text_field($_POST['phone'] ?? '');

    if (!$phone) {
        wp_send_json_error(['message' => 'Vui lòng điền vào số điện thoại']);
    }

    $address = bizgpt_get_current_address($phone);

    wp_send_json_success(['address' => $address]);
}

function gpt_form_accumulate_code() {

    $client_id = getClientIdFromUrlPage();
    global $wpdb;
    $table_store_locations = $wpdb->prefix . 'store_locations';

    $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_store_locations");

    $store_records = $wpdb->get_results("SELECT * FROM $table_store_locations");
    $store_records_json = json_encode($store_records);
    $selected_store = isset($_GET['store_name']) ? sanitize_text_field($_GET['store_name']) : '';

    ?>
    <?php if (!isset($client_id) || empty($client_id)): ?>
    <?php
        $notice = get_option('gpt_error_notice_editor');
        $messenger_link = get_option('gpt_messenger_link', '');
        $logo_image_url = get_option('gpt_logo_image_url', '');
        $messenger_icon_url = get_option('gpt_messenger_icon_url', '');
        $display_image_url = get_option('gpt_display_image_url', '');
        ob_start();
    ?>
        <?php  wp_enqueue_style('gpt-form-style', plugin_dir_url(__FILE__) . 'form.css'); ?>        
        <div class="div-chuyen-huong-mes">
            <div class="content">
                <img src="<?php echo esc_url($logo_image_url); ?>"
                    class="logo" alt="Logo Website" />
                <div class="message" id="messageDb">
                    <?php 
                    $success_message = get_option('gpt_error_notice_editor', '✨ Bạn đang ở bước cuối cùng!<br><br>Nhấn để bắt đầu tích điểm và nhận ưu đãi đổi quà 🎁');
                    echo wp_kses_post($success_message);
                    ?>
                </div>
                <button class="btn" onclick="goToMessenger()" id="giftBtn">
                    <img src="<?php echo esc_url($messenger_icon_url); ?>"
                        alt="Messenger" />
                    Nhấn để tích điểm
                </button>
                <img src="<?php echo esc_url($display_image_url); ?>"
                    alt="Cow surprise" class="cow-image" />
            </div>
        </div>
        <script>
            function goToMessenger() {
                window.location.href = `<?php echo esc_js($messenger_link); ?>`;
            }
        </script>
        <?php
        return ob_get_clean();
    ?>

    <?php else: ?>
    <?php

        $response_message = "";
        $response_success = "";
        $response_success_1 = "";
        $response = "";
        $logo_image_url = get_option('gpt_logo_image_url', '');
        $affiliate_enabled = get_option('affiliate_enabled', 0);
        $affiliate_points = get_option('affiliate_points_per_referral', 10);
        $min_points_required = get_option('affiliate_min_points_required', 1);
        $title_message = get_option('gpt_success_notice_editor', '');
        $gpt_agree_terms_editor = get_option('gpt_agree_terms_editor', '');
        // $barcode_from_url = isset($_GET['barcode']) ? sanitize_text_field($_GET['barcode']) : '';

        $current_barcode = gpt_get_saved_barcode();

        if(isset($_GET['code']) && isset($_GET['token'])){
            global $wpdb;
            $table_name = BIZGPT_PLUGIN_WP_LOGS;
            $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
            $affiliate_log_table = BIZGPT_PLUGIN_WP_AFFILIATE_LOGS;
            $store_table = BIZGPT_PLUGIN_WP_STORE_LIST;
            $employees_table = BIZGPT_PLUGIN_WP_EMPLOYEES;

            $note_status = "";

            // Lấy dữ liệu từ form
            $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            $phone_number = isset($_GET['phone_number']) ? sanitize_text_field($_GET['phone_number']) : '';
            $username = isset($_GET['username']) ? sanitize_text_field($_GET['username']) : '';
            $address = isset($_GET['address']) ? sanitize_text_field($_GET['address']) : '';
            $user_current_location = isset($_GET['user_current_location']) ? sanitize_text_field($_GET['user_current_location']) : '';
            $user_current_ward = isset($_GET['user_current_ward']) ? sanitize_text_field($_GET['user_current_ward']) : '';
            $user_current_province = isset($_GET['user_current_province']) ? sanitize_text_field($_GET['user_current_province']) : '';
            // $store_name = isset($_GET['store_name']) ? sanitize_text_field($_GET['store_name']) : '';
            $clientID = isset($_GET['client_id']) ? sanitize_text_field($_GET['client_id']) : '';
            // Lấy thông tin địa chỉ chi tiết mới
            $user_house_number = isset($_GET['user_house_number']) ? sanitize_text_field($_GET['user_house_number']) : '';
            $user_road = isset($_GET['user_road']) ? sanitize_text_field($_GET['user_road']) : '';
            $user_district = isset($_GET['user_district']) ? sanitize_text_field($_GET['user_district']) : '';
            $user_postcode = isset($_GET['user_postcode']) ? sanitize_text_field($_GET['user_postcode']) : '';
            $user_full_address = isset($_GET['user_full_address']) ? sanitize_text_field($_GET['user_full_address']) : '';
            $user_detail_address = isset($_GET['user_detail_address']) ? sanitize_text_field($_GET['user_detail_address']) : '';
            $location_timestamp = isset($_GET['location_timestamp']) ? sanitize_text_field($_GET['location_timestamp']) : '';
            $user_location = isset($_GET['user_location']) ? sanitize_text_field($_GET['user_location']) : '';
            // Lấy thông tin vị trí user thông qua địa chỉ ip
            $userProvinceFromIP = isset($_GET['userProvinceFromI']) ? sanitize_text_field($_GET['userProvinceFromI']) : '';
            // Check type affiliate
            $aff_check_type = isset($_GET['aff_check_type']) ? sanitize_text_field($_GET['aff_check_type']) : '';
            $referrer_phone = "";
            $referrer_name = "";
            $aff_by_store_id = "";
            // $aff_by_employee_code = "";
            $store_name_aff = "";
            // $employee_name_aff = "";

            if($aff_check_type == "affiliate"){
                $referrer_phone = isset($_GET['referrer_phone']) ? sanitize_text_field($_GET['referrer_phone']) : '';
                $referrer_name = isset($_GET['referrer_name']) ? sanitize_text_field($_GET['referrer_name']) : '';
            } else if($aff_check_type == "employee"){
                $aff_by_store_id = isset($_GET['store_id']) ? sanitize_text_field($_GET['store_id']) : '';
                $store_name_aff = isset($_GET['store_name_aff']) ? sanitize_text_field($_GET['store_name_aff']) : '';
                // $aff_by_employee_code = isset($_GET['employee_id']) ? sanitize_text_field($_GET['employee_id']) : '';
                // $employee_name_aff = isset($_GET['employee_name']) ? sanitize_text_field($_GET['employee_name']) : '';
            }
            // $used_by_other_phone = $wpdb->get_var("SELECT phone_number FROM $table_name WHERE barcode = '$code'");
            $check_used_in_logs = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE barcode = %s",
                $code
            ));

            $check_used_in_barcode = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $barcode_table WHERE barcode = %s AND status = 'used'",
                $code
            ));
            // $code_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $barcode_table WHERE LOWER(barcode) = LOWER(%s)",$code));
            $code_info = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM $barcode_table 
                WHERE LOWER(barcode) = LOWER(%s) AND token = %s
            ", $code, $token));

            // ★★★ VALIDATION NGƯỜI GIỚI THIỆU ★★★
            if ($affiliate_enabled && !empty($referrer_phone)) {
                // Validate format số điện thoại
                if (!preg_match('/^[0-9+\-\s()]{8,15}$/', $referrer_phone)) {
                    $response = 'Số điện thoại người giới thiệu không hợp lệ. Vui lòng kiểm tra lại.';
                }
                
                if ($referrer_phone === $phone_number) {
                    $response = 'Bạn không thể tự giới thiệu chính mình. Vui lòng nhập số điện thoại khác.';
                }
                
                // Kiểm tra người giới thiệu có tồn tại không
                $referrer_info = check_user_exists_for_affiliate($referrer_phone);

                if (!$referrer_info['exists']) {
                    $response = "Số điện thoại người giới thiệu ($referrer_phone) chưa từng tham gia chương trình. Vui lòng kiểm tra lại hoặc để trống nếu không có người giới thiệu.";
                } else{
                    $referrer_name = $referrer_info['name'];
                    $response_success_1 = "Thông tin người giới thiệu: $referrer_name";
                }
                
                // Nếu có tên người giới thiệu nhưng không khớp với database
                if (!empty($referrer_name) && !empty($referrer_info['name']) && 
                    strtolower(trim($referrer_name)) !== strtolower(trim($referrer_info['name']))) {
                    $response = "Tên người giới thiệu không khớp. Tên đúng là: " . $referrer_info['name'] . ". Vui lòng kiểm tra lại.";
                }
                
                // Cập nhật tên người giới thiệu từ database nếu có
                if (empty($referrer_name) && !empty($referrer_info['name'])) {
                    $referrer_name = $referrer_info['name'];
                }
            }

            if ($check_used_in_logs > 0 || $check_used_in_barcode > 0) {
                $response = 'Mã '.$code.' đã được sử dụng. Vui lòng thử lại mã khác. Chúng tôi có thể giúp được gì cho bạn không?';
                $json_content = '
                    "messages": [
                        {"text": "'.$response.'"}
                    ]
                    ';
                    // send_mess_bizgpt($json_content, $clientID);
            } else {
                if (!$code_info) {
                    $response = 'Mã ' . $code . ' hoặc Token không hợp lệ. Vui lòng kiểm tra lại.';
                } else {
                    // KIỂM TRA VỊ TRÍ
                    $location_mismatch = false;
                    $location_warning = "";
                    
                    $provinces = [
                        'An Giang' => 'AG', 'Bắc Ninh' => 'BN', 'Cà Mau' => 'CM', 'Cao Bằng' => 'CB',
                        'Đắk Lắk' => 'DL', 'Điện Biên' => 'DB', 'Đồng Nai' => 'DG', 'Đồng Tháp' => 'DT',
                        'Gia Lai' => 'GL', 'Hà Tĩnh' => 'HT', 'Hưng Yên' => 'HY', 'Khánh Hoà' => 'KH',
                        'Lai Châu' => 'LC', 'Lâm Đồng' => 'LD', 'Lạng Sơn' => 'LS', 'Lào Cai' => 'LA',
                        'Nghệ An' => 'NA', 'Ninh Bình' => 'NB', 'Phú Thọ' => 'PT', 'Quảng Ngãi' => 'QG',
                        'Quảng Ninh' => 'QN', 'Quảng Trị' => 'QT', 'Sơn La' => 'SL', 'Tây Ninh' => 'TN',
                        'Thái Nguyên' => 'TG', 'Thanh Hóa' => 'TH', 'TP. Cần Thơ' => 'CT', 'TP. Đà Nẵng' => 'DN',
                        'TP. Hà Nội' => 'HN', 'TP. Hải Phòng' => 'HP', 'TP. Hồ Chí Minh' => 'SG', 'TP. Huế' => 'HUE',
                        'Tuyên Quang' => 'TQ', 'Vĩnh Long' => 'VL'
                    ];
                    
                    // Lấy mã tỉnh từ mã cào (giả sử lưu trong trường province)
                    $code_province = trim($code_info->province);
                    
                    // Tìm mã tỉnh từ tên tỉnh của user
                    $user_province_name = trim($user_current_province);
                    $user_province_code = '';
                    
                    // Tìm mã tỉnh tương ứng với tên tỉnh của user
                    foreach ($provinces as $province_name => $province_code) {
                        // So sánh không phân biệt hoa thường và loại bỏ dấu cách thừa
                        if (stripos($user_province_name, $province_name) !== false || 
                            stripos($province_name, $user_province_name) !== false) {
                            $user_province_code = $province_code;
                            break;
                        }
                    }
                    
                    // Nếu không tìm thấy trong danh sách, thử so sánh trực tiếp
                    if (empty($user_province_code)) {
                        // Kiểm tra xem user_province_name có phải là mã tỉnh không
                        if (in_array(strtoupper($user_province_name), $provinces)) {
                            $user_province_code = strtoupper($user_province_name);
                        }
                    }
                    
                    // So sánh mã tỉnh
                    if (!empty($code_province) && !empty($user_province_code)) {
                        if (strtoupper($code_province) !== strtoupper($user_province_code)) {
                            $location_mismatch = true;
                            
                            // Tìm tên tỉnh từ mã để hiển thị
                            $code_province_name = array_search(strtoupper($code_province), $provinces);
                            $user_province_display = $user_province_name;
                            
                            $location_warning = "⚠️ CẢNH BÁO VỊ TRÍ: Mã cào dành cho {$code_province_name} ({$code_province}) nhưng người dùng đang ở {$user_province_display} ({$user_province_code})";
                        }
                    } else {
                        // Nếu không xác định được mã tỉnh, ghi log để kiểm tra
                        error_log("Cannot determine province code - Code province: $code_province, User province: $user_province_name");
                    }
                    
                    // Tìm sản phẩm
                    $custom_prod_id = $code_info->product_id;
                    $args = [
                        'post_type' => 'product',
                        'meta_query' => [
                            [
                                'key' => 'custom_prod_id',
                                'value' => $custom_prod_id,
                                'compare' => '='
                            ]
                        ],
                        'posts_per_page' => 1
                    ];

                    $products = get_posts($args);
                    $product_name = !empty($products) ? $products[0]->post_title : "";
                    
                    $session = intval($code_info->session);
                    $status = 'used';
                    
                    $is_first_time = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name WHERE phone_number = %s AND transaction_type = 'tich_diem'",
                            $phone_number
                        )
                    ) > 0 ? false : true;;

                    $original_points = intval($code_info->point);
                    $bonus_points = 0;

                    if ($is_first_time) {
                        $bonus_percent = floatval(get_option('affiliate_percent_per_new_user', 0));

                        if ($bonus_percent > 0) {
                            $bonus_points = $original_points * ($bonus_percent / 100);
                        }

                        if (!empty($referrer_phone)) {
                            $note_status = "Bạn được giới thiệu bởi $referrer_phone và nhận thưởng affiliate!";
                            $user_status = 'aff_new';
                            // Ghi log & cập nhật stats
                            gpt_log_affiliate_reward(
                                'referral',
                                $referrer_phone,
                                $phone_number,
                                $bonus_points,
                                '',
                                $note_status
                            );

                        } 
                        // elseif (!empty($aff_by_employee_code) && !empty($aff_by_store_id)) {
                        //     $store = $wpdb->get_results($wpdb->prepare(
                        //         "SELECT id, store_name as name FROM $store_table WHERE id = %d",
                        //         $aff_by_store_id
                        //     ));
                        //     $note_status = "Nhân viên $employee_name_aff - #$aff_by_employee_code tại cửa hàng $store_name_aff - #$aff_by_store_id đã giới thiệu người mới!";
                            
                        //     gpt_log_affiliate_reward(
                        //         'employee',
                        //         $aff_by_employee_code,
                        //         $phone_number,
                        //         $bonus_points,
                        //         $aff_by_store_id,
                        //         $note_status
                        //     );

                        // } 
                        elseif (!empty($aff_by_store_id)) {
                            $note_status = "🎉 Người dùng được giới thiệu bởi cửa hàng $store_name_aff - $aff_by_store_id!";
                            $user_status = 'new';
                            gpt_log_affiliate_reward(
                                'store_only',
                                '',
                                $phone_number,
                                $bonus_points,
                                $aff_by_store_id,
                                $note_status
                            );
                        }
                    }

                    $points = $original_points + $bonus_points;
                    $main_transaction_data = array(
                        'user_id' => 0,
                        'client_id' => $clientID,
                        'barcode' => $code,
                        'session_code' => $session,
                        'barcode_status' => $status,
                        'customer_name' => $username,
                        'phone_number' => $phone_number,
                        'point_change' => $points,
                        'product' => $custom_prod_id,
                        'store' => !empty($store_name_aff) ? $store_name_aff : NULL,
                        'point_location' => $user_current_location,
                        'address' => $address,
                        'province' => !empty($user_current_province) ? $user_current_province : NULL,
                        'ward' => !empty($user_current_ward) ? $user_current_ward : NULL,
                        'transaction_type' => 'tich_diem',
                        'product_name' => $product_name,
                        'user_district' => $user_district,
                        'user_house_number' => $user_house_number,
                        'user_road' => $user_road,
                        'user_postcode' => $user_postcode,
                        'user_full_address' => $user_full_address,
                        'user_detail_address' => $user_detail_address,
                        'phone_referrer' => !empty($referrer_phone) ? $referrer_phone : NULL,
                        'referrer_name' => !empty($referrer_name) ? $referrer_name : NULL,
                        'is_affiliate_reward' => 0,
                        'u_status' => !empty($user_status) ? $user_status : NULL,
                        'note_status' => $bonus_points > 0 ? " (+$bonus_points điểm thưởng ref)" : "",
                        'aff_by_store_id' => !empty($aff_by_store_id) ? $aff_by_store_id : NULL,
                        'aff_by_employee_code' => "",
                        'user_province_from_ip' => $userProvinceFromIP
                    );

                    // Lưu giao dịch chính (sẽ tự động cập nhật user points)
                    $main_result = enhanced_bizgpt_insert_point_log($main_transaction_data);
                    error_log("Error saving transaction data: " . print_r($main_transaction_data, true));
                    if ($main_result) {
                        // Cập nhật trạng thái mã cào
                        $wpdb->update(
                            $barcode_table,
                            array('status' => 'used'),
                            array('barcode' => $code),
                            array('%s'),
                            array('%s')
                        );
                    }

                    // THÔNG BÁO CHO ADMIN NẾU CÓ VẤN ĐỀ VỊ TRÍ
                    if ($location_mismatch) {
                        send_location_warning_to_admin($code, $username, $phone_number, $code_info->province, $user_current_province, $user_full_address, $product_name, $store_name, $location_mismatch, $location_warning);
                    }

                    $total_points = bizgpt_get_current_points($phone_number);
                    $response_success = 'Bạn đã tích điểm thành công. Bạn hiện đang có: '.$total_points.' điểm';
                    
                    $location_note = $location_mismatch ? "\n⚠️ Lưu ý: Vị trí tích điểm khác với khu vực dự kiến của mã." : "";
                    
                    $response_data = [
                        'message_intro' => 'Chúc mừng bạn đã tích điểm thành công.',
                        'store_name'    => $store_name,
                        'phone_number'  => $phone_number,
                        'product_name'  => $product_name,
                        'address'       => $address,
                        'code'          => $code,
                        'total_points'  => $total_points,
                        'location_note' => $location_note,
                        'support_phone' => '1900636605',
                    ];
                    
                    $json_content = '
                        "messages": [
                        {"text": "Chúc mừng bạn đã tích '.$points.' điểm thành công.\n☀️ Thông tin khách hàng :\n🏠 Tên shop : '.$store_name.'\n📱Số điện thoại : '.$phone_number.'\n🎡 Tên sản phẩm tích điểm : '.$product_name.'\n🎯 Địa chỉ : '.$address.'\n📝 Mã Cào  : '.$code.'\n🌟 Số điểm của bạn là : '.$total_points.'\n📍Để đổi điểm, bạn nhắn: Đổi điểm\n📍Để tích điểm, bạn nhắn: Tích điểm\n📍Để kiểm tra điểm, bạn nhắn: Kiểm tra điểm\n✨Mọi thắc mắc bạn vui lòng liên hệ theo SĐT: 1900636605\n----------\n💐 Cảm ơn bạn đã tham gia chương trình."}
                        ]
                        ';
                    // send_mess_bizgpt($json_content, $clientID);
                }
            }
        }
        ob_start();
    ?>
        <?php  wp_enqueue_style('gpt-form-style', plugin_dir_url(__FILE__) . 'form.css'); ?>
        <div class="biz_form_tichdiem">
            <h2>NHẬP THÔNG TIN TÍCH ĐIỂM MÃ CÀO</h2>
            <form id="biz_form_tichdiem" method="get">
                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                <input type="hidden" id="user_location" name="user_location" value="">
                <input type="hidden" id="user_current_location" name="user_current_location" value="">
                <input type="hidden" id="userProvinceFromI" name="userProvinceFromI" value="">
                <input type="hidden" name="aff_check_type" id="aff_check_type" value="">
                <input type="hidden" name="store_name_aff" id="store_name_aff">
                <!-- <input type="hidden" name="employee_name" id="employee_name"> -->
                <div class="form-group">
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        value="<?php echo isset($_GET['code']) ? sanitize_text_field($_GET['code']) : $current_barcode; ?>" 
                        placeholder="Mã định danh sản phẩm"
                        required
                    >    
                </div>
                <div id="product_info"></div>
                <div class="form-group">
                    <input type="text" id="token" name="token" value="<?php echo isset($_GET['token']) ? sanitize_text_field($_GET['token']) : ''; ?>" placeholder="Token lớp tráng bạc trên tem" required>
                </div>
                <div class="form-group">
                    <input type="text" id="username" name="username" value="<?php echo isset($_GET['username']) ? sanitize_text_field($_GET['username']) : ''; ?>" placeholder="Họ và tên" required>
                </div>
                <div class="form-group">
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo isset($_GET['phone_number']) ? sanitize_text_field($_GET['phone_number']) : ''; ?>" placeholder="Số điện thoại của bạn" required>
                </div>
                <div class="form-group">
                    <input type="text" id="address" name="address" value="<?php echo isset($_GET['address']) ? sanitize_text_field($_GET['address']) : ''; ?>" placeholder="Địa chỉ của bạn" required>
                </div>
                <hr>
                <div class="gpt-toggle-wrapper" id="buyed_store">
                    <label class="gpt-toggle-label">Bạn mua tại cửa hàng nào</label>
                    <label class="gpt-toggle-switch">
                        <input type="checkbox" id="is_employee">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="employee-fields" style="display:none; margin-top: 15px;">
                    <div class="form-group">
                        <label>Cửa hàng</label>
                        <select name="store_id" id="store_id" style="width: 100%;"></select>
                    </div>
                    <!-- <div class="form-group">
                        <label>Nhân viên</label>
                        <select name="employee_id" id="employee_id" style="width: 100%;"></select>
                    </div> -->
                </div>
                <?php if ($affiliate_enabled): ?>
                    <div class="gpt-toggle-wrapper" id="buyed_referrer">
                        <label class="gpt-toggle-label">Bạn có người giới thiệu</label>
                        <label class="gpt-toggle-switch">
                            <input type="checkbox" id="has_referrer">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="referrer-fields" style="display:none; margin-top: 15px;">
                        <div class="form-section affiliate-section">
                            <h3>🤝 Có người giới thiệu không?</h3>
                            <div class="affiliate-intro">
                                <div class="affiliate-benefit">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="referrer_phone">Số điện thoại người giới thiệu</label>
                                <input type="tel" 
                                    id="referrer_phone" 
                                    name="referrer_phone" 
                                    placeholder="Nhập số điện thoại người giới thiệu (không bắt buộc)" 
                                    value="<?php echo isset($_GET['referrer_phone']) ? sanitize_text_field($_GET['referrer_phone']) : ''; ?>"
                                >
                                <div id="referrer_info"></div>
                                <?php if($response_success_1) : ?>
                                    <div class="message-success">
                                        <span><?php echo $response_success_1; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div id="affiliate-preview"></div>
                        </div>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="form-group btn-group">
                    <button type="submit" class="btn-gradient" id="btn_tichdiem">TÍCH ĐIỂM NGAY</button>
                </div>
                <?php if($response) : ?>
                    <div class="error-message">
                        <span><?php echo $response; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($response_data)) : ?>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        const data = <?php echo json_encode($response_data, JSON_UNESCAPED_UNICODE); ?>;

                        // Tự dựng HTML đẹp hơn
                        const htmlContent = `
                            <p>☀️ <b>${data.message_intro}</b></p>
                            <p>🏠 <b>Tên shop:</b> ${data.store_name}</p>
                            <p>📱 <b>Số điện thoại:</b> ${data.phone_number}</p>
                            <p>🎡 <b>Tên sản phẩm tích điểm:</b> ${data.product_name}</p>
                            <p>🎯 <b>Địa chỉ:</b> ${data.address}</p>
                            <p>📝 <b>Mã Cào:</b> ${data.code}</p>
                            <p>🌟 <b>Số điểm của bạn:</b> ${data.total_points}</p>
                            <hr>
                            <p>✨ Mọi thắc mắc vui lòng liên hệ: ${data.support_phone}</p>
                            <p>${data.location_note}</p>
                            <p>💐 Cảm ơn bạn đã tham gia chương trình.</p>
                        `;

                        Swal.fire({
                            title: `<?php echo $title_message; ?>`,
                            html: htmlContent,
                            imageUrl: '<?php echo esc_url($logo_image_url); ?>',
                            imageWidth: 128,
                            imageHeight: 128,
                            showCloseButton: true,
                            showDenyButton: true,
                            denyButtonText: 'Quay lại Trang chủ',
                            confirmButtonText: 'Tiện ích khác',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/tien-ich';
                            } else if (result.isDenied || result.dismiss === Swal.DismissReason.timer) {
                                window.location.href = '/';
                            }
                        });
                    </script>
                <?php endif; ?>
            </form>
        </div>             
        <script>
            jQuery(document).ready(function($) {
                let typingTimer;
                let doneTypingInterval = 500;
                let timer;
                let lastPhone = '';

                let referrerName = document.getElementById('referrer_name');

                $('#referrer_info').hide();

                let storeData = <?php echo $store_records_json; ?>;

                const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                const codeInput = $('#code');
                const initialCode = codeInput.val().trim();
                
                                
                function toggleExclusive(activeId, otherId, typeValue) {
                    $('#' + activeId).trigger('change', function() {
                        if ($(this).is(':checked')) {
                            $('#' + otherId).prop('checked', false).trigger('change');
                            $('#aff_check_type').val(typeValue);
                        } else {
                            $('#aff_check_type').val('');
                        }
                    });
                }

                $('#buyed_store').hide();
                $('#buyed_referrer').hide();

                $('#is_employee').on('change', function () {
                    $('.employee-fields').toggle(this.checked);
                });

                $('#has_referrer').on('change', function () {
                    $('.referrer-fields').toggle(this.checked);
                });

                // $('.gpt-select2').select2();
                // $('#store_id, #employee_id').select2({ placeholder: 'Vui lòng chọn', allowClear: true });

                $('#code, #token').on('change blur', function() {
                    let code = $('#code').val().trim();
                    let token = $('#token').val().trim();
                    
                    if (code && token) {
                        $.post(ajaxurl, {
                            action: 'gpt_get_stores_by_code_token',
                            code: code,
                            token: token
                        }, function(res) {
                            if (res.success) {
                                let stores = res.data;
                                console.log(stores)
                                $('#store_id').html('');
                                $('#store_id').append(`<option value="">$--- Vui lòng chọn cửa hàng ---</option>`);
                                $.each(stores, function(i, store) {
                                    $('#store_id').append(`<option value="${store.id}">${store.name}</option>`);
                                });
                                $('.employee-fields').toggle(this.checked);
                                $('#store_id').trigger('change');
                                $('#store_id').select2({ placeholder: 'Vui lòng chọn', allowClear: true });
                            }
                        });
                    }
                });

                $('#store_id').on('change', function() {
                    let store_id = $(this).val();
                    let store_name = $('#store_id option:selected').text();
                    $('#store_name_aff').val(store_name);
                    // if (store_id) {
                    //     $.post(ajaxurl, {
                    //         action: 'gpt_get_employees_by_store',
                    //         store_id: store_id
                    //     }, function(res) {
                    //         if (res.success) {
                    //             $('#employee_id').html('');
                    //             $.each(res.data, function(i, emp) {
                    //                 $('#employee_id').append(`<option value="${emp.code}">${emp.name} - #${emp.code}</option>`);
                    //             });
                    //             $('#employee_id').trigger('change');
                    //         }
                    //     });
                    // }
                });

                // $('#employee_id').on('change', function() {
                //     let employee_id = $(this).val();
                //     let employee_name = $('#employee_id option:selected').text().split(' - #')[0];

                //     console.log("Employee ID:", employee_id);
                //     console.log("Employee Name:", employee_name);

                //     $('#employee_name').val(employee_name);
                // });

                if ($('#is_employee').is(':checked')) {
                    $('#aff_check_type').val('employee');
                    $('.employee-fields').show();
                } else if ($('#has_referrer').is(':checked')) {
                    $('#aff_check_type').val('affiliate');
                    $('.referrer-fields').show();
                }

                if (initialCode !== '') {
                    $('#product_info').html('<p class="text-gray-500 mt-2">Đang kiểm tra mã...</p>');

                    typingTimer = setTimeout(function () {
                        $.post(ajaxurl, {
                            action: 'check_macao_ajax',
                            code: initialCode
                        }, function (response) {
                            if (response.success) {
                                let productInfo = response.data.product;
                                $('#product_info').html(`
                                    <div class="prod_item">
                                        <div class="prod_item_left"><img src="${productInfo.image}" alt="${productInfo.name}"></div>
                                        <div class="prod_item_right">
                                            <h4 class="">Thông tin sản phẩm:</h4>
                                            <p><strong>${productInfo.name}</strong></p>
                                            <h5 class="">Giá niêm yết:</h5>
                                            <p><strong>${productInfo.price}</strong></p>
                                        </div>
                                    </div>
                                `);
                                $('.affiliate-benefit').html(`🎁 Ưu đãi đặc biệt: Nếu có người giới thiệu, họ sẽ nhận <strong>${productInfo.affiliate_points} điểm thưởng</strong> khi bạn tích điểm thành công!`);
                            } else {
                                $('#product_info').html(`<p class="text-red-500 mt-2">${response.data}</p>`);
                            }
                        });
                    }, doneTypingInterval);
                }
                // $('#store_name').select2({
                //     placeholder: 'TÊN CỬA HÀNG MUA HÀNG',
                //     allowClear: true,
                //     width: '100%',
                //     language: {
                //         noResults: function() {
                //             return "Không tìm thấy kết quả";
                //         },
                //         searching: function() {
                //             return "Đang tìm kiếm...";
                //         },
                //         loadingMore: function() {
                //             return "Đang tải thêm...";
                //         }
                //     }
                // });
                fillLocationDataToForm();
                function getAddressFromCoordinates(lat, lng) {
                    var url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`;
                    
                    jQuery.get(url, function(data) {
                        if (data && data.display_name) {
                            const address = data.display_name;
                            const house_number = data.address.house_number || '';
                            const road = data.address.road || '';
                            const ward = data.address.quarter || '';
                            const district = data.address.suburb || '';
                            const city = data.address.city || '';
                            const postcode = data.address.postcode || '';
                            const full_address = data.display_name;

                            console.log('Tỉnh: ' + city);
                            console.log('Phường/Xã: ' + ward);
                            console.log('Địa chỉ chi tiết: ' + house_number + ' ' + road);
                            console.log('Địa chỉ đầy đủ: ' + full_address);

                            $('#user_current_location').val(address);
                            $('#user_current_ward').val(ward);
                            $('#user_current_province').val(city);

                            var userLocationData = {
                                coordinates: {
                                    latitude: lat,
                                    longitude: lng
                                },
                                address: {
                                    house_number: house_number,
                                    road: road,
                                    ward: ward,
                                    district: district,
                                    city: city,
                                    postcode: postcode,
                                    full_address: full_address,
                                    display_name: address
                                },
                                timestamp: new Date().getTime()
                            };

                            localStorage.setItem('userLocationData', JSON.stringify(userLocationData));
                            console.log('Đã lưu thông tin vị trí và địa chỉ vào localStorage');
                            
                            localStorage.setItem('userProvince', city);
                            localStorage.setItem('userWard', ward);
                            localStorage.setItem('userFullAddress', full_address);
                            localStorage.setItem('userDetailAddress', house_number + ' ' + road);
                            
                        } else {
                            console.log('Không tìm thấy địa chỉ.');
                        }
                    }).fail(function(error) {
                        console.log('Lỗi khi lấy địa chỉ:', error);
                    });
                }

                function fillLocationDataToForm() {
                    // Lấy dữ liệu từ localStorage
                    var locationData = getUserLocationData();
                    
                    if (locationData) {
                        updateFormWithNewLocation(locationData);
                        // Điền thông tin GPS
                        $('#user_location').val(locationData.coordinates.latitude + ',' + locationData.coordinates.longitude);
                        
                        // Điền thông tin địa chỉ
                        $('#user_current_location').val(locationData.address.display_name);
                        
                        console.log('Đã điền thông tin địa chỉ vào form');
                    } else {
                        $('#userProvinceFromI').val(localStorage.getItem('userProvinceFromI') || '')
                        console.log('Không có dữ liệu địa chỉ trong localStorage');
                    }
                }

                // Hàm cập nhật thông tin vào form sau khi lấy được địa chỉ mới
                function updateFormWithNewLocation(locationData) {
                    // Cập nhật các trường hidden
                    $('#user_location').val(locationData.coordinates.latitude + ',' + locationData.coordinates.longitude);
                    $('#user_current_location').val(locationData.address.display_name);
                    
                    // Cập nhật trường address hiển thị
                    if ($('#address').val() === '') {
                        $('#address').val(locationData.address.full_address);
                    }
                }

                // Hàm để lấy toàn bộ thông tin vị trí từ localStorage
                function getUserLocationData() {
                    var savedData = localStorage.getItem('userLocationData');
                    if (savedData) {
                        return JSON.parse(savedData);
                    }
                    return null;
                }

                // Hàm để lấy từng thông tin cụ thể từ localStorage
                function getUserProvince() {
                    return localStorage.getItem('userProvince') || '';
                }

                function getUserWard() {
                    return localStorage.getItem('userWard') || '';
                }

                function getUserFullAddress() {
                    return localStorage.getItem('userFullAddress') || '';
                }

                function getUserDetailAddress() {
                    return localStorage.getItem('userDetailAddress') || '';
                }

                function getLocationClientFromI(){
                    $.get('http://ip-api.com/json/', function(data) {
                        const location = data.city;
                        localStorage.setItem('userProvinceFromI', location);
                    });
                }

                // Hàm để xóa toàn bộ thông tin vị trí
                function clearUserLocationData() {
                    localStorage.removeItem('userLocationData');
                    localStorage.removeItem('userProvince');
                    localStorage.removeItem('userWard');
                    localStorage.removeItem('userFullAddress');
                    localStorage.removeItem('userDetailAddress');
                    console.log('Đã xóa toàn bộ thông tin vị trí khỏi localStorage');
                }
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var latitude = position.coords.latitude;
                        var longitude = position.coords.longitude;
                        $('#user_location').val(latitude + ',' + longitude);
                        console.log('Vị trí GPS: ' + latitude + ',' + longitude);
                    
                        getAddressFromCoordinates(latitude, longitude);
                    }, function(error) {
                        console.log('Lỗi lấy vị trí: ', error.message);
                        getLocationClientFromI();
                    });
                } else {
                    getLocationClientFromI();
                    console.log('Trình duyệt không hỗ trợ Geolocation');
                }

                function getUserLocationFromStorage() {
                    var savedLocation = localStorage.getItem('userLocation');
                    if (savedLocation) {
                        return JSON.parse(savedLocation);
                    }
                    return null;
                }

                function clearUserLocation() {
                    localStorage.removeItem('userLocation');
                    console.log('Đã xóa vị trí khỏi localStorage');
                }

                $('#phone_number').on('change blur', function() {
                    let phone = $(this).val().trim();

                    if (phone) {
                        $.post( ajaxurl, {
                            action: 'get_current_address',
                            phone: phone
                        }, function(res) {
                            if (res.success) {
                                console.log("Address:", res.data.address);
                                $('#address').val(res.data.address);
                            } else {
                                console.error("Error:", res.data.message);
                                $('#address').val('');
                            }
                        });
                    }
                });

                $('#code').on('keyup', function() {
                    clearTimeout(typingTimer);
                    let code = $(this).val().trim();
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    if (code !== '') {
                        $('#product_info').html('<p class="text-gray-500 mt-2">Đang kiểm tra mã...</p>');

                        typingTimer = setTimeout(function() {
                            $.post(ajaxurl, {
                                action: 'check_macao_ajax',
                                code: code
                            }, function(response) {
                                if (response.success) {
                                    let productInfo = response.data.product;
                                    $('#product_info').html(`
                                        <div class="prod_item">
                                            <div class="prod_item_left"><img src="${productInfo.image}" alt="${productInfo.name}"></div>
                                            <div class="prod_item_right"><h4 class="">Thông tin sản phẩm:</h4><p><strong>${productInfo.name}</strong></p><h5 class="">Giá niêm yết:</h5><p><strong>${productInfo.price}</strong></p></div>
                                        </div>
                                    `);
                                    if(response.data.show_store_section == true){
                                        // toggleExclusive('is_employee', 'has_referrer', 'employee');
                                        $('#buyed_store').show();
                                        $('#is_employee').prop('checked', true);
                                        $('.employee-fields').show();
                                        $('#buyed_referrer').hide();
                                        $('#aff_check_type').val('employee');
                                    } else{
                                        $('#buyed_store').hide();
                                        $('#has_referrer').prop('checked', true);
                                        $('.referrer-fields').show();
                                        $('#buyed_referrer').show();
                                         $('#aff_check_type').val('affiliate');
                                        // toggleExclusive('has_referrer', 'is_employee', 'affiliate');
                                    }
                                } else {
                                    $('#product_info').html(`<p class="text-red-500 mt-2">${response.data}</p>`);
                                }
                            });
                        }, doneTypingInterval);
                    } else {
                        $('#product_info').html('');
                    }
                });

                $('#referrer_phone').on('input', function () {
                    const phone = $(this).val().trim();
                    const resultBox = $('#referrer_info');
                    const userPhone = $('#phone_number').val().trim();
                    const referrer_phone = $('#referrer_phone').val().trim();
                    clearTimeout(timer);
                    resultBox.text('');
                    $('#referrer_info').show();

                    if (phone.length < 9) return;

                    if (referrer_phone && userPhone === referrer_phone) {
                        showAffiliateStatus('❌ Không thể tự giới thiệu chính mình', 'error');
                        return;
                    } else{
                        timer = setTimeout(function () {
                            if (phone === lastPhone) return;
                            lastPhone = phone;

                            resultBox.text('Đang kiểm tra...').css('color', 'black');

                            $.ajax({
                                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                                method: 'GET',
                                data: {
                                    action: 'gpt_check_referrer',
                                    phone: phone
                                },
                                success: function(res) {
                                    if (res.success) {
                                        resultBox.text('Người giới thiệu: ' + res.data.name);
                                    } else {
                                        resultBox.text('Không tìm thấy người giới thiệu').css('color', 'red');
                                    }
                                },
                                error: function() {
                                    resultBox.text('Lỗi khi kiểm tra').css('color', 'red');
                                }
                            });
                        }, 500);
                    }
                });

                $('#btn_tichdiem').on('click', function(e) {
                    e.preventDefault();
                    
                    // Kiểm tra các trường bắt buộc
                    const code = $('#code').val().trim();
                    const token = $('#token').val().trim();
                    const username = $('#username').val().trim();
                    const phone_number = $('#phone_number').val().trim();
                    const address = $('#address').val().trim();
                    
                    // Validate các trường bắt buộc
                    if (!code) {
                        Swal.fire({
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng nhập mã định danh sản phẩm',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (!token) {
                        Swal.fire({
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng nhập token lớp tráng bạc trên tem',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (!username) {
                        Swal.fire({
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng nhập họ và tên',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (!phone_number) {
                        Swal.fire({
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng nhập số điện thoại',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    // Validate số điện thoại
                    if (!/^[0-9+\-\s()]{8,15}$/.test(phone_number)) {
                        Swal.fire({
                            title: 'Số điện thoại không hợp lệ',
                            text: 'Vui lòng nhập số điện thoại hợp lệ',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (!address) {
                        Swal.fire({
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng nhập địa chỉ',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    // Kiểm tra người giới thiệu nếu có
                    const referrer_phone = $('#referrer_phone') ? $('#referrer_phone').val() : null;
                    if (referrer_phone !== null) {
                        if (phone_number === referrer_phone) {
                            Swal.fire({
                                title: 'Lỗi người giới thiệu',
                                text: 'Không thể tự giới thiệu chính mình',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                        
                        // Kiểm tra người giới thiệu có tồn tại không
                        const referrerInfo = $('#referrer_info').text();
                        if (referrerInfo.includes('Không tìm thấy')) {
                            Swal.fire({
                                title: 'Người giới thiệu không tồn tại',
                                text: 'Số điện thoại người giới thiệu không tồn tại trong hệ thống',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                    }
                    
                    Swal.fire({
                        title: 'Xác nhận tích điểm',
                        html: `<?php echo $gpt_agree_terms_editor; ?>`,
                        icon: 'question',
                        showCloseButton: true,
                        showCancelButton: true,
                        cancelButtonText: 'Hủy bỏ',
                        confirmButtonText: 'Đồng ý và tích điểm',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $("#biz_form_tichdiem").off('submit').submit();
                        }
                    });
                });

                function showAffiliateStatus(message, type) {
                    $('#affiliate-preview').html(message);
                    $('#affiliate-preview').css('display','block');
                }
            });

        </script>
    <?php
        return ob_get_clean();
        endif;
    ?>
    <?php 
}

function gpt_check_referrer_ajax() {
    global $wpdb;

    $phone = sanitize_text_field($_GET['phone'] ?? '');
    if (empty($phone)) {
        wp_send_json_error(['message' => 'Thiếu số điện thoại']);
    }

    $table = BIZGPT_PLUGIN_WP_SAVE_USERS;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT full_name FROM $table WHERE phone_number = %d LIMIT 1", $phone
    ));

    if ($row) {
        wp_send_json_success(['name' => $row->full_name]);
    } else {
        wp_send_json_error(['message' => 'Không tìm thấy']);
    }
}

function gpt_get_stores_by_code_token() {
    global $wpdb;

    $code = sanitize_text_field($_POST['code']);
    $token = sanitize_text_field($_POST['token']);

    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    $channels_table = BIZGPT_PLUGIN_WP_CHANNELS;
    $store_table   = BIZGPT_PLUGIN_WP_STORE_LIST;

    $channel_code = $wpdb->get_var($wpdb->prepare(
        "SELECT channel FROM $barcode_table WHERE barcode = %s AND token = %s",
        $code, $token
    ));

    if (!$channel_code) {
        wp_send_json_error(['message' => 'Không tìm thấy mã kênh từ mã cào.']);
    }

    $channel_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $channels_table WHERE channel_code = %s",
        $channel_code
    ));

    if (!$channel_id) {
        wp_send_json_error(['message' => 'Không tìm thấy kênh phù hợp.']);
    }

    $stores = $wpdb->get_results($wpdb->prepare(
        "SELECT id, store_name as name FROM $store_table WHERE channel_id = %d",
        $channel_id
    ));

    wp_send_json_success($stores);
}

function gpt_get_employees_by_store() {
    global $wpdb;

    $store_id = intval($_POST['store_id']);
    $employee_table = BIZGPT_PLUGIN_WP_EMPLOYEES;

    $employees = $wpdb->get_results($wpdb->prepare(
        "SELECT id, full_name as name, code FROM $employee_table WHERE store_id = %d",
        $store_id
    ));

    wp_send_json_success($employees);
}


function gpt_log_affiliate_reward($type, $referrer, $referred_phone, $points, $store_id = '', $note = '', $source = '') {
    global $wpdb;

    $log_table = BIZGPT_PLUGIN_WP_AFFILIATE_LOGS;
    $stats_table = BIZGPT_PLUGIN_WP_AFFILIATE_STATS;

    $referrer_phone = ($type === 'referral') ? $referrer : '';

    // Insert vào bảng log
    $wpdb->insert($log_table, [
        'type' => $type,
        'referrer' => $referrer,
        'referrer_phone' => $referrer_phone,
        'referred_phone' => $referred_phone,
        'points_rewarded' => $points,
        'note' => $note,
        'source' => $source,
        'store_id' => $store_id,
        'created_at' => current_time('mysql'),
    ]);

    // Nếu là referral thì cập nhật bảng stats
    if ($type === 'referral' && !empty($referrer_phone)) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $stats_table WHERE referrer_phone = %s LIMIT 1", $referrer_phone
        ));

        if ($existing) {
            $wpdb->update($stats_table, [
                'total_referrals' => $existing->total_referrals + 1,
                'total_points_earned' => $existing->total_points_earned + $points,
                'last_referral_date' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ], ['referrer_phone' => $referrer_phone]);
        } else {
            $wpdb->insert($stats_table, [
                'referrer_phone' => $referrer_phone,
                'referrer_name' => '',
                'total_referrals' => 1,
                'total_points_earned' => $points,
                'first_referral_date' => current_time('mysql'),
                'last_referral_date' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }
    }
}



