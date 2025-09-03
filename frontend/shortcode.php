<?php

add_shortcode('gpt_lookup_employee_aff', 'gpt_render_lookup_employee_aff');

function gpt_render_lookup_employee_aff() {
    ob_start();
    ?>
<div class="gpt-lookup-form">
    <form id="gpt-lookup-form">
        <h3>🔍 Tra cứu thông tin nhân viên</h3>
        <input type="text" id="tra_cuu_keyword" placeholder="Nhập mã nhân viên ..." required>
        <button type="submit">Tra cứu</button>
    </form>
    <div id="gpt-result"></div>
</div>

<style>
.gpt-lookup-form {
    max-width: 100%;
    margin: 40px auto;
    padding: 24px 32px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

#gpt-lookup-form {
    width: 350px;
    margin: 0 auto;
}

#gpt-lookup-form h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #2d3748;
    font-weight: 600;
    text-align: center;
}

.gpt-lookup-form input[type="text"],
.gpt-lookup-form input[type="number"],
.gpt-lookup-form select {
    width: 100%;
    padding: 10px 14px;
    margin-bottom: 16px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    background-color: #f7fafc;
    font-size: 15px;
    transition: border-color 0.2s ease;
    box-shadow: none;
}

.gpt-lookup-form input:focus,
.gpt-lookup-form select:focus {
    outline: none;
    border-color: #173d7c;
    background-color: #fff;
}

.gpt-lookup-form button {
    width: 100%;
    padding: 8px 12px;
    background-color: #173d7c;
    border: none;
    color: white;
    font-size: 16px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    margin: 0px;
}

.gpt-lookup-form button:hover {
    background-color: #2b6cb0;
}

.gpt-lookup-form .form-group {
    margin-bottom: 20px;
}

.gpt-lookup-form .result-table {
    margin-top: 30px;
    border-collapse: collapse;
    width: 100%;
}

#gpt-result * {
    color: #000;
}

.gpt-lookup-form .result-table th,
.gpt-lookup-form .result-table td {
    border: 1px solid #e2e8f0;
    padding: 10px 14px;
    text-align: left;
    font-size: 14px;
}

.gpt-lookup-form .result-table th {
    background-color: #f1f5f9;
    color: #000;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#gpt-lookup-form').on('submit', function(e) {
        e.preventDefault();
        let keyword = $('#tra_cuu_keyword').val().trim();
        if (!keyword) return;


        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'gpt_tra_cuu_diem_nhan_vien',
            keyword: keyword
        }, function(res) {
            if (res.success) {
                let html = `<h4>👤 Nhân viên: <strong>${res.data.name}</strong></h4>
                                    <p>🎯 Tổng điểm: <strong>${res.data.total_points}</strong></p>`;
                if (res.data.logs.length > 0) {
                    html += `<table class="gpt-log-table">
                                        <thead>
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Họ và tên</th>
                                                <th>Số điện thoại</th>
                                                <th>Mã cào</th>
                                                <th>Sản phẩm</th>
                                                <th>Điểm</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                    res.data.logs.forEach(log => {
                        html += `<tr>
                                            <td>${log.created_at}</td>
                                            <td>${log.customer_name}</td>
                                            <td>${log.phone_number}</td>
                                            <td>${log.barcode}</td>
                                            <td>${log.product}</td>
                                            <td>${log.point_change}</td>
                                        </tr>`;
                    });
                    html += `</tbody></table>`;
                } else {
                    html += `<p>📭 Không có lịch sử tích điểm nào.</p>`;
                }

                $('#gpt-result').html(html);
            } else {
                $('#gpt-result').html('<p style="color:red">❌ Không tìm thấy nhân viên.</p>');
            }
        });
    });
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_lookup_employee_aff_ajax', 'gpt_lookup_employee_aff_ajax');
add_action('wp_ajax_nopriv_gpt_lookup_employee_aff_ajax', 'gpt_lookup_employee_aff_ajax');

function gpt_mask_phone_number($phone) {
    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) < 6) {
        return $phone;
    }

    $prefix = substr($phone, 0, 3);
    $suffix = substr($phone, -3);
    return $prefix . '*****' . $suffix;
}

function gpt_lookup_employee_aff_ajax() {
    global $wpdb;
    $keyword = sanitize_text_field($_POST['keyword']);

    $table_emp  = BIZGPT_PLUGIN_WP_EMPLOYEES;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;

    $emp = $wpdb->get_row($wpdb->prepare(
        "SELECT id, full_name, code FROM $table_emp WHERE code = %s LIMIT 1", $keyword
    ));

    if (!$emp) {
        wp_send_json_error();
    }

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(point_change) FROM $table_logs WHERE aff_by_employee_code = %d AND transaction_type = 'tich_diem'", $emp->id
    ));

    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone_number, barcode, product, point_change, barcode_status, created_at FROM $table_logs 
         WHERE aff_by_employee_code = %d ORDER BY created_at DESC LIMIT 100", $emp->id
    ));

    $formatted_logs = array_map(function($row) {
        return [
            'created_at'       => date('d/m/Y', strtotime($row->created_at)),
            'barcode'     => esc_html($row->barcode),
            'product'   => esc_html($row->product),
            'point_change'       => intval($row->point_change),
            'phone_number' => esc_html(gpt_mask_phone_number($row->phone_number)),
            'customer_name' => esc_html($row->customer_name),
        ];
    }, $logs);

    wp_send_json_success([
        'name'         => $emp->full_name,
        'total_points' => intval($total),
        'logs'         => $formatted_logs,
    ]);
}

// Tra cứu cửa hàng

add_shortcode('gpt_lookup_store_aff', 'gpt_shortcode_lookup_store_aff');
function gpt_shortcode_lookup_store_aff() {
    global $wpdb;
    $store_table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $point_table = BIZGPT_PLUGIN_WP_LOGS;

    $stores = $wpdb->get_results("SELECT id, store_name FROM $store_table ORDER BY store_name ASC");

    ob_start();
    ?>
<div class="gpt-lookup-form">
    <div class="gpt-lookup-form-container">
        <h3 style="text-align: center;">Tra cứu thông tin tích điểm của cửa hàng</h3>
        <div id="lookup-store-form">
            <div class="form-row">
                <label for="store_id">Chọn cửa hàng:</label>
                <select id="store_id_loopup" name="store_id" class="gpt-select2" required>
                    <option value="">-- Chọn cửa hàng --</option>
                    <?php foreach ($stores as $store): ?>
                    <option value="<?= esc_attr($store->id) ?>">
                        <?= esc_html($store->store_name) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label for="store_phone">Số điện thoại cửa hàng:</label>
                <input type="tel" id="store_phone" name="store_phone" placeholder="Nhập số điện thoại cửa hàng" required>
            </div>
            
            <div class="form-row">
                <label for="captcha">Nhập vào kết quả của phép tính: <span id="captcha-question"></span></label>
                <div class="captcha-wrapper">
                    <input type="number" id="store_captcha_answer" name="store_captcha_answer" placeholder="Nhập kết quả" required>
                    <button type="button" id="refresh-captcha" class="refresh-btn">🔄</button>
                </div>
            </div>
            
            <button type="submit" class="button button-primary" id="lookup-submit-btn">Tra cứu</button>
        </div>
    </div>
    <div id="gpt-loading-wrapper" style="display:none;">
        <div class="gpt-loading-center">
            <div class="lds-dual-ring"></div>
            <p class="loading-text">Hệ thống đang xử lý dữ liệu. Vui lòng chờ ...</p>
        </div>
    </div>
    <div id="gpt_store_result"></div>
</div>
<style>
    .form-row {
        margin-bottom: 15px;
        width: 100%;
    }

    .form-row .select2-container{
        margin-bottom: 0px;
    }

    .form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-row input, 
    .form-row select {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #cccccc;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s, box-shadow 0.3s;
        min-height: 50px;
        margin-bottom: 0px;
        box-shadow: none;
    }

    .captcha-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .captcha-wrapper span {
        font-weight: bold;
        font-size: 16px;
        color: #333;
        min-width: 80px;
    }

    .captcha-wrapper input {
        width: 95%;
        margin: 0;
    }

    .refresh-btn {
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 14px;
        margin: 0px;
    }

    .refresh-btn:hover {
        background: #e0e0e0;
    }

    .error-message {
        color: #d63638;
        font-size: 14px;
        margin-top: 5px;
    }

    .gpt-loading-center {
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-top: 16px;
    }

    .loading-text {
        margin-bottom: 0px;
        color: #000;
    }

    .lds-dual-ring {
        display: inline-block;
        width: 40px;
        height: 40px;
    }

    .lds-dual-ring:after {
        content: " ";
        display: block;
        width: 32px;
        height: 32px;
        margin: 4px;
        border-radius: 50%;
        border: 4px solid var(--fs-color-primary);
        border-color: var(--fs-color-primary) transparent var(--fs-color-primary) transparent;
        animation: lds-dual-ring 1.2s linear infinite;
    }

    @keyframes lds-dual-ring {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
jQuery(document).ready(function($) {
    let captchaAnswer = 0;
    
    // Tạo captcha mới
    function generateCaptcha() {
        let num1 = Math.floor(Math.random() * 20) + 1;
        let num2 = Math.floor(Math.random() * 20) + 1;
        captchaAnswer = num1 + num2;
        $('#captcha-question').text(num1 + ' + ' + num2 + ' = ?');
        $('#store_captcha_answer').val('');
    }
    
    // Khởi tạo
    $('#gpt_store_result').hide();
    $('#lookup-store-form #store_id_loopup').select2({
        placeholder: 'Chọn cửa hàng...',
        minimumInputLength: 1
    });
    generateCaptcha();
    
    // Refresh captcha
    $(document).on('click', '#refresh-captcha', function() {
        generateCaptcha();
    });

    // Xử lý form submit
    $(document).on('click', '#lookup-store-form .button', function(e) {
        e.preventDefault();
        
        // Xóa các thông báo lỗi cũ
        $('.error-message').remove();
        
        let store_id = $('#lookup-store-form #store_id_loopup').val();
        let store_phone = $('#store_phone').val().trim();
        let user_answer = parseInt($('#store_captcha_answer').val());
        
        let hasError = false;
        
        // Validate form
        if (!store_id) {
            $('#store_id_loopup').after('<div class="error-message">Vui lòng chọn cửa hàng</div>');
            hasError = true;
        }
        
        if (!store_phone) {
            $('#store_phone').after('<div class="error-message">Vui lòng nhập số điện thoại cửa hàng</div>');
            hasError = true;
        } else if (!/^[0-9]{10,11}$/.test(store_phone)) {
            $('#store_phone').after('<div class="error-message">Số điện thoại không hợp lệ (10-11 số)</div>');
            hasError = true;
        }
        
        if (isNaN(user_answer) || user_answer !== captchaAnswer) {
            $('#store_captcha_answer').after('<div class="error-message">Mã xác thực không đúng</div>');
            hasError = true;
        }
        
        if (hasError) {
            return;
        }

        // Gửi request
        $('#gpt_store_result').hide().html('');
        $('#gpt-loading-wrapper').show();
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'gpt_get_store_point_aff',
            store_id: store_id,
            store_phone: store_phone
        }, function(res) {
            $('#gpt-loading-wrapper').hide();
            if (res.success) {
                let html = `<h4>Cửa hàng: <strong>${res.data.name}</strong></h4>
                                    <p>📞 Số điện thoại: <strong>${store_phone}</strong></p>
                                    <p>🎯 Tổng điểm: <strong>${res.data.total_points}</strong></p>`;
                if (res.data.logs.length > 0) {
                    html += `<table class="gpt-log-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Họ và tên</th>
                                    <th>Số điện thoại</th>
                                    <th>Mã định danh</th>
                                    <th>Sản phẩm</th>
                                    <th>Điểm</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    res.data.logs.forEach(log => {
                        html += `<tr>
                                <td>${log.created_at}</td>
                                <td>${log.customer_name}</td>
                                <td>${log.phone_number}</td>
                                <td>${log.barcode}</td>
                                <td>${log.product}</td>
                                <td>${log.point_change}</td>
                            </tr>`;
                    });
                    html += `</tbody></table>`;
                } else {
                    html += `<p>📭 Không có lịch sử tích điểm nào.</p>`;
                }
                $('#gpt_store_result').show();
                $('#gpt_store_result').html(html);
                
                // Tạo captcha mới sau khi thành công
                generateCaptcha();
            } else {
                $('#gpt_store_result').html(
                    '<p style="color:red">❌ ' + (res.data.message || 'Không tìm thấy thông tin cửa hàng.') + '</p>');
                $('#gpt_store_result').show();
                
                // Tạo captcha mới sau khi thất bại
                generateCaptcha();
            }
        }).fail(function() {
            $('#gpt-loading-wrapper').hide();
            $('#gpt_store_result').html(
                '<p style="color:red">❌ Có lỗi xảy ra. Vui lòng thử lại.</p>');
            $('#gpt_store_result').show();
            generateCaptcha();
        });
    });
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_get_store_point_aff', 'gpt_get_store_point_aff');
add_action('wp_ajax_nopriv_gpt_get_store_point_aff', 'gpt_get_store_point_aff');

function gpt_get_store_point_aff() {
    global $wpdb;

    $store_id = intval($_POST['store_id']);
    $store_phone = sanitize_text_field($_POST['store_phone']);
    
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;

    // Validate input
    if (empty($store_id)) {
        wp_send_json_error(['message' => 'Vui lòng chọn cửa hàng.']);
        return;
    }

    if (empty($store_phone)) {
        wp_send_json_error(['message' => 'Vui lòng nhập số điện thoại cửa hàng.']);
        return;
    }

    // Validate phone format
    if (!preg_match('/^[0-9]{10,11}$/', $store_phone)) {
        wp_send_json_error(['message' => 'Số điện thoại không hợp lệ (phải có 10-11 chữ số).']);
        return;
    }

    // Get store info
    $store = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $store_id));
    if (!$store) {
        wp_send_json_error(['message' => 'Không tìm thấy cửa hàng.']);
        return;
    }

    // Verify store phone (assuming store table has phone_number field)
    // If you want to verify store phone matches database, uncomment below:
    /*
    if (isset($store->phone_number) && $store->phone_number !== $store_phone) {
        wp_send_json_error(['message' => 'Số điện thoại cửa hàng không đúng.']);
        return;
    }
    */

    // Get total points for this store
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(point_change), 0) FROM $table_logs WHERE aff_by_store_id = %d AND transaction_type = 'tich_diem'", 
        $store->id
    ));

    // Get transaction logs for this store
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone_number, barcode, product_name as product, point_change, barcode_status, created_at 
         FROM $table_logs 
         WHERE aff_by_store_id = %d AND transaction_type = 'tich_diem'
         ORDER BY created_at DESC 
         LIMIT 100", 
        $store->id
    ));

    // Format logs data
    $formatted_logs = array_map(function($row) {
        return [
            'created_at'    => date('d/m/Y H:i', strtotime($row->created_at)),
            'barcode'       => esc_html($row->barcode ?? ''),
            'product'       => esc_html($row->product ?? ''),
            'point_change'  => intval($row->point_change),
            'phone_number'  => esc_html(gpt_mask_phone_number($row->phone_number)),
            'customer_name' => esc_html($row->customer_name ?? ''),
        ];
    }, $logs);

    wp_send_json_success([
        'name'         => esc_html($store->store_name),
        'phone'        => esc_html($store_phone),
        'total_points' => intval($total),
        'logs'         => $formatted_logs,
    ]);

    wp_die();
}

// Tra cứu tích điểm

add_shortcode('gpt_lookup_point_of_user', 'gpt_lookup_point_of_user_shortcode');

function gpt_lookup_point_of_user_shortcode() {
    ob_start();
    ?>
    <?php  wp_enqueue_style('gpt-form-style-look', plugin_dir_url(__FILE__) . 'lookup.css'); ?>
    <div class="gpt_lookup_point_wrap">
        <div class="bizgpt_form_content">
            <h3 style="text-align: center;">Tra cứu thông tin tích điểm</h3>
            <form id="gpt_lookup_point_form" class="space-y-3">
                <input type="text" id="lookup_phone" placeholder="Nhập số điện thoại" required>

                <div style="margin-top: 10px;">
                    <label>
                        Nhập vào kết quả của phép tính:
                        <span id="captcha_question"></span>
                    </label>
                    <input type="text" id="captcha_answer" placeholder="Nhập kết quả" required>
                </div>

                <button type="submit" class="btn-gradient">Tra cứu</button>
            </form>
        </div>
        <div id="search_result"></div>
    </div>

    <script>
        jQuery(document).ready(function($) {

            function generateCaptcha() {
                let num1 = Math.floor(Math.random() * 10) + 1;
                let num2 = Math.floor(Math.random() * 10) + 1;
                $('#captcha_question').text(`${num1} + ${num2} = ?`);
                return num1 + num2;
            }

            let captcha_result = generateCaptcha();

            let currentPageTich = 1;
            let currentPageDoi = 1;
            let currentPageAffiliate = 1; // Thêm biến cho affiliate
            let perPage = 5;

            $('#gpt_lookup_point_form').on('submit', function(e) {
                e.preventDefault();

                let phone = $('#lookup_phone').val().trim();
                let captcha = $('#captcha_answer').val().trim();

                if (phone === '') {
                    alert('Vui lòng nhập số điện thoại!');
                    return;
                }

                if (!isValidVietnamPhone(phone)) {
                    alert('Số điện thoại không hợp lệ. Vui lòng nhập đúng số điện thoại Việt Nam.');
                    return;
                }

                if (parseInt(captcha) !== captcha_result) {
                    alert('Mã captcha không đúng!');
                    return;
                }

                $('#search_result').html('<p>🔄 Đang tra cứu, vui lòng đợi...</p>');

                fetchHistory(phone, currentPageTich, currentPageDoi, currentPageAffiliate);
            });

            function fetchHistory(phone, pageTich, pageDoi, pageAffiliate = 1) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                $.post(ajaxurl, {
                    action: 'gpt_lookup_point_ajax',
                    phone: phone,
                    page_tich: pageTich,
                    page_doi: pageDoi,
                    page_affiliate: pageAffiliate, // Thêm tham số affiliate
                    per_page: perPage
                }, function(response) {
                    if (response.success) {
                        let data = response.data;
                        renderResult(phone, data);

                        captcha_result = generateCaptcha();
                        $('#captcha_answer').val('');
                    } else {
                        $('#search_result').html(`<p style="color:red;">${response.data}</p>`);
                        captcha_result = generateCaptcha();
                        $('#captcha_answer').val('');
                    }
                });
            }

            function renderResult(phone, data) {
                let html = `
                        <div class="flex align-middle box_info">
                            <div><span>📱 Số điện thoại:</span> <strong>${phone}</strong></div>
                            <div><span>✅ Tổng điểm của bạn:</span> <strong>${data.tong_diem} điểm</strong></div>
                            <div><span>💰 Điểm tích lũy từ sản phẩm:</span> <strong>${data.diem_tich} điểm</strong></div>
                            <div><span>🤝 Điểm Affiliate:</span> <strong>${data.diem_affiliate || 0} điểm</strong></div>
                            <div><span>🔁 Đã đổi:</span> <strong>${data.diem_doi} điểm</strong></div>
                            <div><span>⭐ Điểm còn lại:</span> <strong>${data.diem_con_lai} điểm</strong></div>
                        </div>
                        
                        <!-- Tab Navigation -->
                        <div class="tab-container" style="margin-top: 20px;">
                            <div class="tab-nav" style="display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px;">
                                <button class="tab-btn active" data-tab="tich" style="padding: 10px 20px; margin-right: 5px; border: none; background: #007cba; color: white; cursor: pointer;">
                                    📈 Lịch sử Tích điểm
                                </button>
                                <button class="tab-btn" data-tab="doi" style="padding: 10px 20px; margin-right: 5px; border: none; background: #f1f1f1; color: #333; cursor: pointer;">
                                    🎁 Lịch sử Đổi điểm
                                </button>
                                <button class="tab-btn" data-tab="affiliate" style="padding: 10px 20px; border: none; background: #f1f1f1; color: #333; cursor: pointer;">
                                    🤝 Lịch sử Affiliate
                                </button>
                            </div>
                            
                            <!-- Tab Contents -->
                            <div class="tab-content">
                                <!-- Tab Tích điểm -->
                                <div id="tab-tich" class="tab-pane active" style="display: block;">
                                    <h4>📈 Lịch sử Tích điểm:</h4>
                                    ${renderTable(data.lich_su_tich, 'tich')}
                                    <div id="pagination_tich" style="margin-top: 10px; text-align: center;">
                                        ${data.total_pages_tich > 1 ? renderPagination(data.total_pages_tich, data.current_page_tich, 'tich') : ''}
                                    </div>
                                </div>

                                <!-- Tab Đổi điểm -->
                                <div id="tab-doi" class="tab-pane" style="display: none;">
                                    <h4>🎁 Lịch sử Đổi điểm:</h4>
                                    ${renderTable(data.lich_su_doi, 'doi')}
                                    <div id="pagination_doi" style="margin-top: 10px; text-align: center;">
                                        ${data.total_pages_doi > 1 ? renderPagination(data.total_pages_doi, data.current_page_doi, 'doi') : ''}
                                    </div>
                                </div>

                                <!-- Tab Affiliate -->
                                <div id="tab-affiliate" class="tab-pane" style="display: none;">
                                    <h4>🤝 Lịch sử Affiliate:</h4>
                                    ${renderAffiliateTable(data.lich_su_affiliate || [])}
                                    <div id="pagination_affiliate" style="margin-top: 10px; text-align: center;">
                                        ${data.total_pages_affiliate > 1 ? renderPagination(data.total_pages_affiliate, data.current_page_affiliate, 'affiliate') : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                $('#search_result').html(html);

                // Tab switching functionality
                $('.tab-btn').on('click', function() {
                    let tabId = $(this).data('tab');
                    
                    // Update button styles
                    $('.tab-btn').removeClass('active').css({
                        'background': '#f1f1f1',
                        'color': '#333'
                    });
                    $(this).addClass('active').css({
                        'background': '#007cba',
                        'color': 'white'
                    });
                    
                    // Show/hide tab content
                    $('.tab-pane').hide();
                    $(`#tab-${tabId}`).show();
                });

                // Pagination functionality
                $('.pagination-link').on('click', function() {
                    let type = $(this).data('type');
                    let page = parseInt($(this).data('page'));
                    
                    if (type === 'tich') {
                        currentPageTich = page;
                    } else if (type === 'doi') {
                        currentPageDoi = page;
                    } else if (type === 'affiliate') {
                        currentPageAffiliate = page;
                    }
                    
                    fetchHistory(phone, currentPageTich, currentPageDoi, currentPageAffiliate);
                });
            }

            function renderTable(list, type) {
                if (list.length === 0) {
                    return '<p>Không có dữ liệu.</p>';
                }

                return `
                    <div class="overflow-x-auto">
                        <table class="table bordered" style="width:100%; border-collapse: collapse;" border="1">
                            <thead>
                                <tr>
                                    <th style="padding: 8px;">Loại giao dịch</th>
                                    <th style="padding: 8px;">Số điểm</th>
                                    <th style="padding: 8px;">Sản phẩm</th>
                                    <th style="padding: 8px;">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${list.map(item => `
                                    <tr>
                                        <td style="padding: 8px;">${item.loai_giao_dich}</td>
                                        <td style="padding: 8px; color: ${type === 'doi' ? 'red' : 'green'};">
                                            ${type === 'doi' ? '-' : '+'}${item.so_diem}
                                        </td>
                                        <td style="padding: 8px;">${item.product}</td>
                                        <td style="padding: 8px;">${item.created_at}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            function renderAffiliateTable(list) {
                if (list.length === 0) {
                    return '<p>Bạn chưa có hoạt động affiliate nào.</p>';
                }

                return `
                    <div class="overflow-x-auto">
                        <table class="table bordered" style="width:100%; border-collapse: collapse;" border="1">
                            <thead>
                                <tr>
                                    <th style="padding: 8px;">Loại giao dịch</th>
                                    <th style="padding: 8px;">Số điểm</th>
                                    <th style="padding: 8px;">Mô tả</th>
                                    <th style="padding: 8px;">Tên người giới thiệu</th>
                                    <th style="padding: 8px;">Lần đầu</th>
                                    <th style="padding: 8px;">Lần cuối</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${list.map(item => `
                                    <tr>
                                        <td style="padding: 8px; color: #28a745;">
                                            <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                                ${item.loai_giao_dich}
                                            </span>
                                        </td>
                                        <td style="padding: 8px; color: #28a745; font-weight: bold;">
                                            +${item.so_diem}
                                        </td>
                                        <td style="padding: 8px;">${item.product}</td>
                                        <td style="padding: 8px; font-weight: bold;">
                                            ${item.ten_nguoi_gioi_thieu || 'N/A'}
                                        </td>
                                        <td style="padding: 8px; font-size: 12px;">
                                            ${item.lan_dau_gioi_thieu || 'N/A'}
                                        </td>
                                        <td style="padding: 8px; font-size: 12px;">
                                            ${item.lan_cuoi_gioi_thieu || 'N/A'}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            function renderPagination(totalPages, currentPage, type) {
                let html = '';
                for (let i = 1; i <= totalPages; i++) {
                    if (i === currentPage) {
                        html += `<span style="margin: 0 5px; font-weight: bold; background: #007cba; color: white; padding: 5px 10px; border-radius: 3px;">${i}</span>`;
                    } else {
                        html += `<a href="javascript:void(0);" class="pagination-link" data-page="${i}" data-type="${type}" style="margin: 0 5px; padding: 5px 10px; text-decoration: none; border: 1px solid #ddd; border-radius: 3px;">${i}</a>`;
                    }
                }
                return html;
            }

            function isValidVietnamPhone(phone) {
                let regex = /^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-5]|9[0-9])[0-9]{7}$/;
                return regex.test(phone);
            }
        });
    </script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_lookup_point_ajax', 'gpt_lookup_point_ajax_callback');
add_action('wp_ajax_nopriv_gpt_lookup_point_ajax', 'gpt_lookup_point_ajax_callback');

function gpt_lookup_point_ajax_callback() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_LOGS;
    $exchange_table = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT;
    $user_table = BIZGPT_PLUGIN_WP_SAVE_USERS;
    $referral_table = BIZGPT_PLUGIN_WP_AFFILIATE_STATS; 

    $phone = sanitize_text_field($_POST['phone']);

    $page_tich = isset($_POST['page_tich']) ? intval($_POST['page_tich']) : 1;
    $page_doi = isset($_POST['page_doi']) ? intval($_POST['page_doi']) : 1;
    $page_affiliate = isset($_POST['page_affiliate']) ? intval($_POST['page_affiliate']) : 1; // Thêm page cho affiliate
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;

    $offset_tich = ($page_tich - 1) * $per_page;
    $offset_doi = ($page_doi - 1) * $per_page;
    $offset_affiliate = ($page_affiliate - 1) * $per_page; // Offset cho affiliate

    if (empty($phone)) {
        wp_send_json_error('Vui lòng nhập số điện thoại!');
    }

    $user_points = $wpdb->get_row($wpdb->prepare(
        "SELECT total_points, redeemed_points FROM $user_table WHERE phone_number = %s",
        $phone
    ));

    if (!$user_points) {
        wp_send_json_error('Không tìm thấy dữ liệu tích điểm cho số điện thoại này!');
    }

    $diem_tich = intval($user_points->total_points);
    $diem_doi = intval($user_points->redeemed_points);
    
    // Tính tổng điểm affiliate
    $diem_affiliate = $wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(total_points_earned), 0) FROM $referral_table WHERE referrer_phone = %s",
        $phone
    ));
    
    $tong_diem = $diem_tich + $diem_affiliate;
    $diem_con_lai = ($diem_tich + $diem_affiliate) - $diem_doi;

    // Lấy lịch sử tích điểm từ bảng logs
    $total_tich = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE phone_number = %s AND transaction_type = 'tich_diem'", $phone));
    $total_pages_tich = ceil($total_tich / $per_page);

    $lich_su_tich = $wpdb->get_results($wpdb->prepare(
        "SELECT point_change, product_name, created_at FROM $table WHERE phone_number = %s AND transaction_type = 'tich_diem' ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $phone, $per_page, $offset_tich
    ));

    $history_point_detail = [];
    foreach ($lich_su_tich as $log) {
        $history_point_detail[] = [
            'so_diem' => intval($log->point_change),
            'loai_giao_dich' => 'Tích điểm',
            'product' => $log->product_name,
            'created_at' => date('d/m/Y H:i', strtotime($log->created_at))
        ];
    }

    // Lấy lịch sử đổi điểm từ bảng wp_gpt_exchange_gifts
    $total_doi = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $exchange_table WHERE phone = %s", $phone));
    $total_pages_doi = ceil($total_doi / $per_page);

    $lich_su_doi = $wpdb->get_results($wpdb->prepare(
        "SELECT points, product, time FROM $exchange_table WHERE phone = %s ORDER BY time DESC LIMIT %d OFFSET %d",
        $phone, $per_page, $offset_doi
    ));

    $lich_su_doi_chi_tiet = [];
    foreach ($lich_su_doi as $log) {
        $lich_su_doi_chi_tiet[] = [
            'so_diem' => intval($log->points),
            'loai_giao_dich' => 'Đổi điểm',
            'product' => $log->product,
            'created_at' => date('d/m/Y H:i', strtotime($log->time))
        ];
    }

    // Lấy lịch sử tích điểm affiliate
    $total_affiliate = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $referral_table WHERE referrer_phone = %s", $phone));
    $total_pages_affiliate = ceil($total_affiliate / $per_page);

    $lich_su_affiliate = $wpdb->get_results($wpdb->prepare(
        "SELECT referrer_name, total_referrals, total_points_earned, last_referral_date, first_referral_date 
         FROM $referral_table 
         WHERE referrer_phone = %s 
         ORDER BY last_referral_date DESC 
         LIMIT %d OFFSET %d",
        $phone, $per_page, $offset_affiliate
    ));

    $lich_su_affiliate_chi_tiet = [];
    foreach ($lich_su_affiliate as $log) {
        $lich_su_affiliate_chi_tiet[] = [
            'so_diem' => intval($log->total_points_earned),
            'loai_giao_dich' => 'Tích điểm Affiliate',
            'product' => "Giới thiệu {$log->total_referrals} khách hàng",
            'ten_nguoi_gioi_thieu' => $log->referrer_name,
            'lan_dau_gioi_thieu' => $log->first_referral_date ? date('d/m/Y H:i', strtotime($log->first_referral_date)) : '',
            'lan_cuoi_gioi_thieu' => $log->last_referral_date ? date('d/m/Y H:i', strtotime($log->last_referral_date)) : '',
            'created_at' => $log->last_referral_date ? date('d/m/Y H:i', strtotime($log->last_referral_date)) : ''
        ];
    }

    // Tạo lịch sử tổng hợp (tùy chọn)
    $lich_su_tong_hop = array_merge($history_point_detail, $lich_su_doi_chi_tiet, $lich_su_affiliate_chi_tiet);
    
    // Sắp xếp theo thời gian (mới nhất trước)
    usort($lich_su_tong_hop, function($a, $b) {
        return strtotime(str_replace('/', '-', $b['created_at'])) - strtotime(str_replace('/', '-', $a['created_at']));
    });

    wp_send_json_success([
        'diem_tich' => intval($diem_tich),
        'diem_doi' => abs(intval($diem_doi)),
        'diem_affiliate' => intval($diem_affiliate),
        'tong_diem' => intval($tong_diem),
        'diem_con_lai' => max($diem_con_lai, 0),
        'lich_su_tich' => $history_point_detail,
        'lich_su_doi' => $lich_su_doi_chi_tiet,
        'lich_su_affiliate' => $lich_su_affiliate_chi_tiet,
        'lich_su_tong_hop' => $lich_su_tong_hop,
        'total_pages_tich' => $total_pages_tich,
        'current_page_tich' => $page_tich,
        'total_pages_doi' => $total_pages_doi,
        'current_page_doi' => $page_doi,
        'total_pages_affiliate' => $total_pages_affiliate,
        'current_page_affiliate' => $page_affiliate
    ]);
}