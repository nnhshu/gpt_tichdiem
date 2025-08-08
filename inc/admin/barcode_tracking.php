<?php
// AJAX handlers
add_action('wp_ajax_gpt_track_barcode', 'gpt_track_barcode_ajax');
add_action('wp_ajax_nopriv_gpt_track_barcode', 'gpt_track_barcode_ajax');

function gpt_track_barcode_ajax() {
    $barcode = sanitize_text_field($_POST['barcode'] ?? '');
    
    if (empty($barcode)) {
        wp_send_json_error(['message' => 'Vui lòng nhập mã barcode']);
        return;
    }
    
    $tracking_data = gpt_get_barcode_tracking_data($barcode);
    
    if (!$tracking_data) {
        wp_send_json_error(['message' => 'Không tìm thấy thông tin cho mã: ' . $barcode]);
        return;
    }
    
    wp_send_json_success($tracking_data);
}

function gpt_get_barcode_tracking_data($barcode) {
    global $wpdb;
    
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    $box_table = BIZGPT_PLUGIN_WP_BOX_MANAGER;
    $logs_table = BIZGPT_PLUGIN_WP_LOGS;
    $store_table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $users_table = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    // Tìm mã trong bảng barcode
    $barcode_info = $wpdb->get_row($wpdb->prepare(
        "SELECT *, 'individual' as type FROM $barcode_table WHERE barcode = %s",
        $barcode
    ));
    
    if (!$barcode_info) {
        return false;
    }
    
    // Tìm thùng chứa mã này
    $box_info = null;
    $import_order_info = null;
    
    $box_containing_barcode = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $box_table WHERE FIND_IN_SET(%s, REPLACE(list_barcode, ' ', '')) > 0",
        $barcode
    ));
    
    if ($box_containing_barcode) {
        $box_info = $box_containing_barcode;
        
        // Lấy thông tin import order từ post_type: import_check
        if (!empty($box_info->order_id)) {
            $import_order_post = get_post($box_info->order_id);
            if ($import_order_post && $import_order_post->post_type === 'import_check') {
                $import_order_info = [
                    'title' => $import_order_post->post_title,
                    'link' => get_edit_post_link($import_order_post->ID),
                    'date' => $import_order_post->post_date,
                    'status' => $import_order_post->post_status
                ];
            }
        }
    }
    
    // Lấy tất cả logs của mã này
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT l.*, s.store_name, s.province, s.channel, s.address 
         FROM $logs_table l 
         LEFT JOIN $store_table s ON l.aff_by_store_id = s.id 
         WHERE l.barcode = %s 
         ORDER BY l.created_at DESC",
        $barcode
    ));
    
    // Lấy thông tin tất cả user đã tích mã này
    $users_info = [];
    if (!empty($logs)) {
        $phone_numbers = array_unique(array_column($logs, 'phone_number'));
        if (!empty($phone_numbers)) {
            $phone_placeholders = implode(',', array_fill(0, count($phone_numbers), '%s'));
            
            $users_data = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $users_table WHERE phone_number IN ($phone_placeholders)",
                ...$phone_numbers
            ));
            
            // Convert to associative array for easier lookup
            foreach ($users_data as $user) {
                $users_info[$user->phone_number] = $user;
            }
        }
    }
    
    // Tính toán thống kê
    $total_earned_points = 0;
    $total_redeemed_points = 0;
    $first_use_date = null;
    $last_use_date = null;
    $unique_users = [];
    $unique_stores = [];
    
    foreach ($logs as $log) {
        if ($log->transaction_type == 'tich_diem') {
            $total_earned_points += $log->point_change;
        } elseif ($log->transaction_type == 'doi_diem') {
            $total_redeemed_points += abs($log->point_change);
        }
        
        if (!$first_use_date || $log->created_at < $first_use_date) {
            $first_use_date = $log->created_at;
        }
        if (!$last_use_date || $log->created_at > $last_use_date) {
            $last_use_date = $log->created_at;
        }
        
        // Đếm unique users và stores
        if (!empty($log->phone_number)) {
            $unique_users[$log->phone_number] = true;
        }
        if (!empty($log->aff_by_store_id)) {
            $unique_stores[$log->aff_by_store_id] = $log->store_name;
        }
    }
    
    return [
        'barcode_info' => $barcode_info,
        'box_info' => $box_info,
        'import_order_info' => $import_order_info,
        'logs' => $logs,
        'users_info' => $users_info,
        'statistics' => [
            'total_earned_points' => $total_earned_points,
            'total_redeemed_points' => $total_redeemed_points,
            'total_transactions' => count($logs),
            'unique_users_count' => count($unique_users),
            'unique_stores_count' => count($unique_stores),
            'unique_stores' => $unique_stores,
            'first_use_date' => $first_use_date,
            'last_use_date' => $last_use_date
        ]
    ];
}

function gpt_barcode_tracking_page() {
    ?>
    <div class="wrap">
        <h1>🔍 Truy xuất nguồn gốc mã barcode</h1>
        <p class="description">Nhập mã barcode để truy xuất thông tin chi tiết về nguồn gốc, lịch sử sử dụng và thông tin người dùng.</p>
        
        <!-- Search Form -->
        <div class="search-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #007cba;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 0px;">
                <label for="barcode_input" style="font-weight: bold; font-size: 16px; color: #007cba;">
                    📦 Nhập mã barcode:
                </label>
                <input type="text" 
                       id="barcode_input" 
                       placeholder="Nhập mã barcode cần truy xuất..." 
                       style="flex: 1; max-width: 400px; border-radius: 6px;">
                <button type="button" id="track_barcode_btn" class="button button-primary button-large" style="height: 40px; border-radius: 6px;">
                    🔍 Truy xuất
                </button>
            </div>
            
            <div id="search_loading" style="display: none; text-align: center; padding: 10px;">
                <div class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></div>
                <span style="color: #666; font-style: italic;">Đang tìm kiếm thông tin...</span>
            </div>
        </div>
        
        <!-- Results Section -->
        <div id="tracking_results" style="display: none;">
            
            <!-- Barcode Information Card -->
            <div class="info-card" style="background: white; border: 1px solid #e1e1e1; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
                <div class="card-header" style="background: #007cba; color: white; padding: 15px 20px; font-weight: bold; font-size: 16px;">
                    📋 Thông tin mã barcode
                </div>
                <div class="card-body" style="padding: 20px;" id="barcode_info_content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Box & Import Order Information Card -->
            <div class="info-card" id="box_info_card" style="background: white; border: 1px solid #e1e1e1; border-radius: 8px; margin-bottom: 20px; overflow: hidden; display: none;">
                <div class="card-header" style="background: #17a2b8; color: white; padding: 15px 20px; font-weight: bold; font-size: 16px;">
                    📦 Thông tin thùng & đơn nhập
                </div>
                <div class="card-body" style="padding: 20px;" id="box_info_content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Statistics Card -->
            <div class="info-card" style="background: white; border: 1px solid #e1e1e1; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
                <div class="card-header" style="background: #28a745; color: white; padding: 15px 20px; font-weight: bold; font-size: 16px;">
                    📊 Thống kê sử dụng
                </div>
                <div class="card-body" style="padding: 20px;" id="statistics_content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Users Information Card -->
            <div class="info-card" id="users_info_card" style="background: white; border: 1px solid #e1e1e1; border-radius: 8px; margin-bottom: 20px; overflow: hidden; display: none;">
                <div class="card-header" style="background: #6f42c1; color: white; padding: 15px 20px; font-weight: bold; font-size: 16px;">
                    👥 Danh sách người dùng đã tích điểm
                </div>
                <div class="card-body" style="padding: 0;" id="users_info_content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Transaction History Card -->
            <div class="info-card" id="transaction_history_card" style="background: white; border: 1px solid #e1e1e1; border-radius: 8px; margin-bottom: 20px; overflow: hidden; display: none;">
                <div class="card-header" style="background: #fd7e14; color: white; padding: 15px 20px; font-weight: bold; font-size: 16px;">
                    📈 Danh sách chi tiết logs giao dịch
                </div>
                <div class="card-body" style="padding: 0;" id="transaction_history_content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
        </div>
        
        <!-- Error Message -->
        <div id="error_message" style="display: none; background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545; margin-bottom: 20px;">
            <strong>❌ Lỗi:</strong> <span id="error_text"></span>
        </div>
        
    </div>
    
    <style>
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .info-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #007cba;
    }
    
    .info-item strong {
        display: block;
        color: #495057;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .info-item .value {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        margin-right: 8px;
        margin-bottom: 5px;
    }
    
    .status-unused { background: #d4edda; color: #155724; }
    .status-used { background: #f8d7da; color: #721c24; }
    .status-pending { background: #fff3cd; color: #856404; }
    
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .transaction-table th,
    .transaction-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    
    .transaction-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    
    .transaction-table tr:hover {
        background: #f8f9fa;
    }
    
    .transaction-type-earn { color: #28a745; font-weight: bold; }
    .transaction-type-redeem { color: #dc3545; font-weight: bold; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#barcode_input').on('keypress', function(e) {
            if (e.which === 13) {
                $('#track_barcode_btn').click();
            }
        });
        
        $('#track_barcode_btn').on('click', function() {
            const barcode = $('#barcode_input').val().trim();
            
            if (!barcode) {
                alert('Vui lòng nhập mã barcode!');
                $('#barcode_input').focus();
                return;
            }
            
            // Hide previous results and errors
            $('#tracking_results, #error_message').hide();
            $('#users_info_card, #transaction_history_card, #box_info_card').hide();
            $('#search_loading').show();
            
            $.post(ajaxurl, {
                action: 'gpt_track_barcode',
                barcode: barcode
            }, function(response) {
                $('#search_loading').hide();
                
                if (response.success) {
                    displayTrackingResults(response.data);
                } else {
                    showError(response.data.message);
                }
            }).fail(function() {
                $('#search_loading').hide();
                showError('Lỗi kết nối server. Vui lòng thử lại.');
            });
        });
        
        function displayTrackingResults(data) {
            const barcode = data.barcode_info;
            const stats = data.statistics;
            const users = data.users_info;
            const logs = data.logs;
            const boxInfo = data.box_info;
            const importOrderInfo = data.import_order_info;
            
            // Display barcode information
            let barcodeHtml = `
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Mã barcode</strong>
                        <div class="value">${barcode.barcode}</div>
                    </div>
                    <div class="info-item">
                        <strong>Loại mã</strong>
                        <div class="value">📱 Mã đơn lẻ</div>
                    </div>
                    <div class="info-item">
                        <strong>Trạng thái</strong>
                        <div class="value">
                            <span class="status-badge status-${barcode.status}">
                                ${getStatusText(barcode.status)}
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <strong>Ngày tạo</strong>
                        <div class="value">${formatDateTime(barcode.created_at)}</div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Điểm</strong>
                        <div class="value">${barcode.point || 0} điểm</div>
                    </div>
                    <div class="info-item">
                        <strong>Sản phẩm ID</strong>
                        <div class="value">${barcode.product_id || 'N/A'}</div>
                    </div>
                    <div class="info-item">
                        <strong>Phiên</strong>
                        <div class="value">${barcode.session || 'N/A'}</div>
                    </div>
                    <div class="info-item">
                        <strong>Token</strong>
                        <div class="value" style="font-family: monospace;">${barcode.token || 'N/A'}</div>
                    </div>
                </div>
            `;
            
            $('#barcode_info_content').html(barcodeHtml);
            
            // Display box and import order information
            if (boxInfo) {
                let boxHtml = `
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Mã thùng (Box)</strong>
                            <div class="value">${boxInfo.barcode}</div>
                        </div>
                        <div class="info-item">
                            <strong>Trạng thái thùng</strong>
                            <div class="value">
                                <span class="status-badge status-${boxInfo.status}">
                                    ${getStatusText(boxInfo.status)}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <strong>Ngày tạo thùng</strong>
                            <div class="value">${formatDateTime(boxInfo.created_at)}</div>
                        </div>
                        <div class="info-item">
                            <strong>Order ID</strong>
                            <div class="value">${boxInfo.order_id || 'N/A'}</div>
                        </div>
                    </div>
                `;
                
                if (importOrderInfo) {
                    boxHtml += `
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Tên đơn nhập</strong>
                                <div class="value">${importOrderInfo.title}</div>
                            </div>
                            <div class="info-item">
                                <strong>Ngày tạo đơn</strong>
                                <div class="value">${formatDateTime(importOrderInfo.date)}</div>
                            </div>
                            <div class="info-item">
                                <strong>Trạng thái đơn</strong>
                                <div class="value">${importOrderInfo.status}</div>
                            </div>
                            <div class="info-item">
                                <strong>Link đơn nhập</strong>
                                <div class="value">
                                    <a href="${importOrderInfo.link}" target="_blank" class="button button-small">
                                        📋 Xem đơn nhập
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                $('#box_info_content').html(boxHtml);
                $('#box_info_card').show();
            }
            
            // Display statistics
            let statsHtml = `
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Tổng giao dịch</strong>
                        <div class="value">${stats.total_transactions}</div>
                    </div>
                    <div class="info-item">
                        <strong>Số người dùng đã tích</strong>
                        <div class="value">${stats.unique_users_count} người</div>
                    </div>
                    <div class="info-item">
                        <strong>Số cửa hàng liên quan</strong>
                        <div class="value">${stats.unique_stores_count} cửa hàng</div>
                    </div>
                    <div class="info-item">
                        <strong>Điểm đã tích</strong>
                        <div class="value transaction-type-earn">+${stats.total_earned_points}</div>
                    </div>
                    <div class="info-item">
                        <strong>Điểm đã đổi</strong>
                        <div class="value transaction-type-redeem">-${stats.total_redeemed_points}</div>
                    </div>
                    <div class="info-item">
                        <strong>Lần sử dụng đầu</strong>
                        <div class="value">${stats.first_use_date ? formatDateTime(stats.first_use_date) : 'Chưa sử dụng'}</div>
                    </div>
                </div>
            `;
            
            if (stats.unique_stores_count > 0) {
                statsHtml += `
                    <div style="margin-top: 20px;">
                        <strong>Danh sách cửa hàng liên quan:</strong>
                        <div style="margin-top: 10px;">
                `;
                
                Object.values(stats.unique_stores).forEach(function(store) {
                    statsHtml += `<span class="status-badge" style="background: #e3f2fd; color: #1976d2;">${store}</span>`;
                });
                
                statsHtml += `
                        </div>
                    </div>
                `;
            }
            
            $('#statistics_content').html(statsHtml);
            
            // Display users information
            if (Object.keys(users).length > 0) {
                let usersHtml = `
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Họ và tên</th>
                                <th>Số điện thoại</th>
                                <th>Tổng điểm hiện có</th>
                                <th>Điểm đã đổi</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                Object.values(users).forEach(function(user) {
                    const statusClass = user.user_status === 'active' ? 'transaction-type-earn' : 'transaction-type-redeem';
                    usersHtml += `
                        <tr>
                            <td><strong>${user.full_name || 'N/A'}</strong></td>
                            <td>${user.phone_number}</td>
                            <td>${user.total_points || 0} điểm</td>
                            <td>${user.redeemed_points || 0} điểm</td>
                            <td><span class="${statusClass}">${user.user_status || 'N/A'}</span></td>
                            <td>${formatDateTime(user.created_at)}</td>
                        </tr>
                    `;
                });
                
                usersHtml += '</tbody></table>';
                $('#users_info_content').html(usersHtml);
                $('#users_info_card').show();
            }
            
            // Display transaction history
            if (logs.length > 0) {
                let historyHtml = `
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Thời gian</th>
                                <th>Người tích điểm</th>
                                <th>SĐT</th>
                                <th>Loại GD</th>
                                <th>Điểm</th>
                                <th>Sản phẩm</th>
                                <th>Cửa hàng</th>
                                <th>Tỉnh/Thành</th>
                                <th>Kênh</th>
                                <th>Địa chỉ</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                logs.forEach(function(log, index) {
                    const transactionType = log.transaction_type === 'tich_diem' ? 
                        '<span class="transaction-type-earn">Tích điểm</span>' : 
                        '<span class="transaction-type-redeem">Đổi điểm</span>';
                    
                    const userName = users[log.phone_number] ? users[log.phone_number].full_name : log.customer_name;
                    
                    historyHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${formatDateTime(log.created_at)}</td>
                            <td><strong>${userName || 'N/A'}</strong></td>
                            <td>${log.phone_number}</td>
                            <td>${transactionType}</td>
                            <td><strong>${log.point_change > 0 ? '+' : ''}${log.point_change}</strong></td>
                            <td>${log.product_name || log.product || 'N/A'}</td>
                            <td>${log.store_name || 'N/A'}</td>
                            <td>${log.province || 'N/A'}</td>
                            <td>${log.channel || 'N/A'}</td>
                            <td>${log.address || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                historyHtml += '</tbody></table>';
                $('#transaction_history_content').html(historyHtml);
                $('#transaction_history_card').show();
            }
            
            $('#tracking_results').show();
        }
        
        function showError(message) {
            $('#error_text').text(message);
            $('#error_message').show();
        }
        
        function getStatusText(status) {
            switch(status) {
                case 'unused': return 'Chưa sử dụng';
                case 'used': return 'Đã sử dụng';
                case 'pending': return 'Chờ duyệt';
                default: return status;
            }
        }
        
        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN');
        }
        
        // Auto focus on input
        $('#barcode_input').focus();
    });
    </script>
    <?php
}
?>