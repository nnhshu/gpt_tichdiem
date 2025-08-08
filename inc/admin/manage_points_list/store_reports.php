<?php
function gpt_affiliate_reports_page_callback() {
    global $wpdb;

    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    
    // Lấy danh sách cửa hàng
    $store_query = "SELECT DISTINCT aff_by_store_id FROM $table_logs WHERE aff_by_store_id IS NOT NULL";
    $stores = $wpdb->get_results($store_query);

    $selected_store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : null;
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;
    $user_status = isset($_GET['user_status']) ? sanitize_text_field($_GET['user_status']) : '';
    $transaction_type = isset($_GET['transaction_type']) ? sanitize_text_field($_GET['transaction_type']) : '';

    ob_start();

    ?>
    <div class="wrap">
    <div class="bg_wrap">
    <h1>Báo cáo tích điểm theo cửa hàng</h1>
    <hr>
    <?php
        $new_customers_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT phone_number) FROM $table_logs WHERE u_status = 'new'"
        ));

        $old_customers_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT phone_number) FROM $table_logs WHERE u_status = 'old'"
        ));

        $used_barcode_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_logs WHERE barcode_status = 'used' AND transaction_type = 'tich_diem'"
        ));

        $total_redeemed_points = $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(ABS(point_change)), 0) FROM $table_logs WHERE transaction_type = 'doi_diem'"
        ));

        $top_store_query = "
            SELECT aff_by_store_id, SUM(point_change) AS total_points
            FROM $table_logs
            WHERE aff_by_store_id IS NOT NULL AND transaction_type = 'tich_diem'
            GROUP BY aff_by_store_id
            ORDER BY total_points DESC
            LIMIT 1
        ";
        $top_store = $wpdb->get_row($top_store_query);
    ?>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Số lượng khách hàng mới</h3>
            <p><?php echo $new_customers_count; ?></p>
        </div>
        <div class="card">
            <h3>Số lượng khách hàng cũ</h3>
            <p><?php echo $old_customers_count; ?></p>
        </div>
        <div class="card">
            <h3>Số lượng barcode đã sử dụng</h3>
            <p><?php echo $used_barcode_count; ?></p>
        </div>
        <div class="card">
            <h3>Tổng điểm đã đổi</h3>
            <p><?php echo number_format($total_redeemed_points); ?> điểm</p>
        </div>
        <div class="card">
            <h3>Cửa hàng có điểm tích nhiều nhất</h3>
            <?php
                if ($top_store) {
                    $store_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT store_name FROM $table_store WHERE id = %d",
                        $top_store->aff_by_store_id
                    ));
                    echo "<p>$store_name : {$top_store->total_points} điểm</p>";
                } else {
                    echo "<p>Không có dữ liệu</p>";
                }
            ?>
        </div>
    </div>

    <hr>
    <div class="ux-row" style="margin-bottom: 20px;">
    <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
        <input type="hidden" name="page" value="gpt-affiliate-reports">
        <div class="col large-2">
            <label for="store_id">Chọn cửa hàng:</label>
            <select name="store_id" id="store_id">
                <option value="">Tất cả cửa hàng</option>
                <?php foreach ($stores as $store): ?>
                    <?php
                    $store_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT store_name FROM $table_store WHERE id = %d",
                        $store->aff_by_store_id
                    ));
                    ?>
                    <option value="<?php echo esc_attr($store->aff_by_store_id); ?>" <?php selected($selected_store_id, $store->aff_by_store_id); ?>>
                        <?php echo esc_html($store_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col large-2">
            <label for="user_status">Loại khách hàng:</label>
            <select name="user_status" id="user_status">
                <option value="">Tất cả</option>
                <option value="new" <?php selected($user_status, 'new'); ?>>Khách hàng mới</option>
                <option value="old" <?php selected($user_status, 'old'); ?>>Khách hàng cũ</option>
            </select>
        </div>
        <div class="col large-2">
            <label for="transaction_type">Loại giao dịch:</label>
            <select name="transaction_type" id="transaction_type">
                <option value="">Tất cả</option>
                <option value="tich_diem" <?php selected($transaction_type, 'tich_diem'); ?>>Tích điểm</option>
                <option value="doi_diem" <?php selected($transaction_type, 'doi_diem'); ?>>Đổi điểm</option>
            </select>
        </div>
        <div class="col large-2">
            <label for="start_date">Từ ngày:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>" />
        </div>
        <div class="col large-2">
            <label for="end_date">Đến ngày:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>" />
        </div>
        <div class="col large-1">
            <button type="submit" class="button primary">Xem báo cáo</button>
        </div>
    </form>
    </div>
    <?php
    if (!empty($_GET['store_id']) || !empty($_GET['user_status']) || !empty($_GET['transaction_type']) || (!empty($_GET['start_date']) && !empty($_GET['end_date']))) {
        $export_url = admin_url('admin-ajax.php?action=gpt_export_affiliate_reports');
        if (isset($_GET['store_id'])) $export_url .= '&store_id=' . $_GET['store_id'];
        if (isset($_GET['user_status'])) $export_url .= '&user_status=' . $_GET['user_status'];
        if (isset($_GET['transaction_type'])) $export_url .= '&transaction_type=' . $_GET['transaction_type'];
        if (isset($_GET['start_date'])) $export_url .= '&start_date=' . $_GET['start_date'];
        if (isset($_GET['end_date'])) $export_url .= '&end_date=' . $_GET['end_date'];
        
        echo '<div style="margin: 15px 0; padding: 10px; background: #f0f8f0; border-left: 4px solid #28a745; border-radius: 4px;">';
        echo '<a href="' . esc_url($export_url) . '" class="button" style="background: #28a745; color: white; border-color: #28a745; text-decoration: none;">';
        echo '📊 Xuất Excel</a>';
        echo '<small style="margin-left: 10px; color: #666;">Xuất dữ liệu hiện tại ra file Excel với các bộ lọc đã chọn</small>';
        echo '</div>';
    }
    ?>
    <hr>

    <?php
        $query = "SELECT * FROM $table_logs WHERE aff_by_store_id IS NOT NULL";
        
        if ($selected_store_id) {
            $query .= $wpdb->prepare(" AND aff_by_store_id = %d", $selected_store_id);
        }
        
        if ($user_status) {
            $query .= $wpdb->prepare(" AND u_status = %s", $user_status);
        }
        
        if ($transaction_type) {
            $query .= $wpdb->prepare(" AND transaction_type = %s", $transaction_type);
        }
        
        if ($start_date && $end_date) {
            $query .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $start_date, $end_date);
        }

        $logs = $wpdb->get_results($query);

        if (!empty($logs)) :
    ?>
        <?php if($start_date && $end_date): ?>
        <h3>Ngày <?php echo esc_html($start_date) . " đến " . esc_html($end_date); ?> thống kê</h3>
        <?php endif; ?>
        
        <?php if($user_status): ?>
        <h4>Loại khách hàng: <?php echo $user_status == 'new' ? 'Khách hàng mới' : 'Khách hàng cũ'; ?></h4>
        <?php endif; ?>
        
        <?php if($transaction_type): ?>
        <h4>Loại giao dịch: <?php echo $transaction_type == 'tich_diem' ? 'Tích điểm' : 'Đổi điểm'; ?></h4>
        <?php endif; ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Cửa hàng</th>
                    <th>Số điểm tích</th>
                    <th>Loại khách hàng</th>
                    <th>Loại giao dịch</th>
                    <th>Sản phẩm</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $store_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT store_name FROM $table_store WHERE id = %d",
                        $log->aff_by_store_id
                    ));
                    ?>
                    <tr>
                        <td><?php echo esc_html($log->customer_name); ?></td>
                        <td><?php echo esc_html($log->phone_number); ?></td>
                        <td><?php echo esc_html($store_name); ?></td>
                        <td><?php echo esc_html($log->point_change); ?></td>
                        <td>
                            <?php if ($log->u_status == 'new'): ?>
                                <span class="gpt-badge gpt-badge-success">Khách hàng mới</span>
                            <?php else: ?>
                                <span class="gpt-badge gpt-badge-info">Khách hàng cũ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->transaction_type == 'tich_diem'): ?>
                                <span class="gpt-badge gpt-badge-success">Tích điểm</span>
                            <?php elseif ($log->transaction_type == 'doi_diem'): ?>
                                <span class="gpt-badge gpt-badge-danger">Đổi điểm</span>
                            <?php else: ?>
                                <span class="gpt-badge gpt-badge-secondary">Khác</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log->product_name); ?></td>
                        <td><?php echo esc_html($log->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Không có dữ liệu cho cửa hàng này.</p>
    <?php endif; ?>
    </div>
    </div>
    <?php
    if (empty($stores)) {
        echo '<p>Không có cửa hàng nào được ghi nhận trong hệ thống.</p>';
    }

    $content = ob_get_clean();

    echo $content;
}

function gpt_generate_summary_report() {
    global $wpdb;

    // Bảng chứa thông tin giao dịch
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    // Đặt mặc định cho ngày bắt đầu và ngày kết thúc
    $current_month = date('Y-m');
    $current_quarter = ceil(date('n') / 3);
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $current_month . '-01';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');

    // Nếu người dùng chọn tháng, quý hoặc từ ngày đến ngày
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : null;
    $selected_quarter = isset($_GET['quarter']) ? sanitize_text_field($_GET['quarter']) : null;
    $selected_transaction_type = isset($_GET['summary_transaction_type']) ? sanitize_text_field($_GET['summary_transaction_type']) : 'tich_diem';

    // Form lựa chọn
    echo '<h2>Báo Cáo Tổng Hợp Tích Điểm</h2>';

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="gpt-affiliate-reports">';

    // Tháng
    echo '<label for="month">Chọn tháng:</label>';
    echo '<input type="month" name="month" value="' . esc_attr($selected_month ?: $current_month) . '">';

    // Quý
    echo '<label for="quarter">Chọn quý:</label>';
    echo '<select name="quarter">
            <option value="">Chọn quý</option>
            <option value="1" ' . ($selected_quarter == 1 ? 'selected' : '') . '>Quý 1</option>
            <option value="2" ' . ($selected_quarter == 2 ? 'selected' : '') . '>Quý 2</option>
            <option value="3" ' . ($selected_quarter == 3 ? 'selected' : '') . '>Quý 3</option>
            <option value="4" ' . ($selected_quarter == 4 ? 'selected' : '') . '>Quý 4</option>
        </select>';

    // Từ ngày đến ngày
    echo '<label for="start_date">Từ ngày:</label>';
    echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo '<label for="end_date">Đến ngày:</label>';
    echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    
    // Loại giao dịch
    echo '<label for="summary_transaction_type">Loại giao dịch:</label>';
    echo '<select name="summary_transaction_type">
            <option value="tich_diem" ' . ($selected_transaction_type == 'tich_diem' ? 'selected' : '') . '>Tích điểm</option>
            <option value="doi_diem" ' . ($selected_transaction_type == 'doi_diem' ? 'selected' : '') . '>Đổi điểm</option>
            <option value="all" ' . ($selected_transaction_type == 'all' ? 'selected' : '') . '>Tất cả</option>
        </select>';
    
    echo '<button type="submit" class="button">Xem báo cáo</button>';
    echo '</form>';

    // Tính toán khoảng thời gian
    if ($selected_month) {
        $start_date = $selected_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
    }

    if ($selected_quarter) {
        $start_date = date('Y', strtotime($start_date)) . '-' . (($selected_quarter - 1) * 3 + 1) . '-01';
        $end_date = date('Y', strtotime($start_date)) . '-' . ($selected_quarter * 3) . '-31';
    }

    echo '<h3>Báo cáo khách hàng ' . ($selected_transaction_type == 'tich_diem' ? 'tích điểm' : ($selected_transaction_type == 'doi_diem' ? 'đổi điểm' : 'giao dịch')) . ' từ ' . date('d/m/Y', strtotime($start_date)) . ' đến ' . date('d/m/Y', strtotime($end_date)) . '</h3>';

    // Điều kiện transaction type cho queries
    $transaction_condition = '';
    if ($selected_transaction_type == 'tich_diem') {
        $transaction_condition = "AND transaction_type = 'tich_diem'";
    } elseif ($selected_transaction_type == 'doi_diem') {
        $transaction_condition = "AND transaction_type = 'doi_diem'";
    }
    // Nếu là 'all' thì không thêm điều kiện

    // Query cho khách hàng mới
    $new_users_query = "
        SELECT phone_number, customer_name, SUM(point_change) as total_points, MAX(created_at) as last_transaction
        FROM $table_logs
        WHERE DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN %s AND %s 
        AND u_status = 'new'
        $transaction_condition
        GROUP BY phone_number, customer_name
        ORDER BY total_points DESC
    ";

    $new_users = $wpdb->get_results($wpdb->prepare($new_users_query, $start_date, $end_date));

    // Query cho khách hàng cũ
    $old_users_query = "
        SELECT phone_number, customer_name, SUM(point_change) as total_points, MAX(created_at) as last_transaction
        FROM $table_logs
        WHERE DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN %s AND %s 
        AND u_status = 'old'
        $transaction_condition
        GROUP BY phone_number, customer_name
        ORDER BY total_points DESC
    ";

    $old_users = $wpdb->get_results($wpdb->prepare($old_users_query, $start_date, $end_date));

    // Hiển thị 2 bảng riêng biệt cho user mới và cũ
    echo '<div style="display: flex; gap: 20px; margin-bottom: 30px;">';
    
    // Bảng User Mới
    echo '<div style="flex: 1; background: #f0fff4; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">';
    echo '<h4 style="color: #28a745; margin-top: 0;">🆕 Khách hàng mới (' . count($new_users) . ' người)</h4>';
    
    if ($new_users) {
        $total_new_points = array_sum(array_column($new_users, 'total_points'));
        $points_label = $selected_transaction_type == 'doi_diem' ? 'điểm đã đổi' : ($selected_transaction_type == 'tich_diem' ? 'điểm tích lũy' : 'tổng điểm');
        echo '<p style="background: white; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><strong>Tổng ' . $points_label . ': ' . number_format(abs($total_new_points)) . ' điểm</strong></p>';
        
        echo '<div style="background: white; border-radius: 4px; overflow: hidden;">';
        echo '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
        echo '<thead><tr><th>Tên khách hàng</th><th>Số điện thoại</th><th>' . ucfirst($points_label) . '</th><th>Giao dịch cuối</th></tr></thead>';
        echo '<tbody>';
        foreach ($new_users as $user) {
            echo '<tr>';
            echo "<td>" . esc_html($user->customer_name) . "</td>";
            echo "<td>" . esc_html($user->phone_number) . "</td>";
            echo "<td><strong style='color: #28a745;'>" . number_format(abs($user->total_points)) . "</strong></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($user->last_transaction)) . "</td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<p style="text-align: center; color: #6c757d; font-style: italic;">Không có khách hàng mới trong khoảng thời gian này.</p>';
    }
    echo '</div>';

    // Bảng User Cũ
    echo '<div style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6c757d;">';
    echo '<h4 style="color: #6c757d; margin-top: 0;">👥 Khách hàng cũ (' . count($old_users) . ' người)</h4>';
    
    if ($old_users) {
        $total_old_points = array_sum(array_column($old_users, 'total_points'));
        $points_label = $selected_transaction_type == 'doi_diem' ? 'điểm đã đổi' : ($selected_transaction_type == 'tich_diem' ? 'điểm tích lũy' : 'tổng điểm');
        echo '<p style="background: white; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><strong>Tổng ' . $points_label . ': ' . number_format(abs($total_old_points)) . ' điểm</strong></p>';
        
        echo '<div style="background: white; border-radius: 4px; overflow: hidden;">';
        echo '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
        echo '<thead><tr><th>Tên khách hàng</th><th>Số điện thoại</th><th>' . ucfirst($points_label) . '</th><th>Giao dịch cuối</th></tr></thead>';
        echo '<tbody>';
        foreach ($old_users as $user) {
            echo '<tr>';
            echo "<td>" . esc_html($user->customer_name) . "</td>";
            echo "<td>" . esc_html($user->phone_number) . "</td>";
            echo "<td><strong style='color: #6c757d;'>" . number_format(abs($user->total_points)) . "</strong></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($user->last_transaction)) . "</td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<p style="text-align: center; color: #6c757d; font-style: italic;">Không có khách hàng cũ trong khoảng thời gian này.</p>';
    }
    echo '</div>';
    echo '</div>';

    // Thống kê tổng quan
    echo '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
    echo '<h4>📊 Thống kê tổng quan</h4>';
    echo '<div style="display: flex; gap: 30px;">';
    echo '<div><strong>Tổng khách hàng mới:</strong> ' . count($new_users) . ' người</div>';
    echo '<div><strong>Tổng khách hàng cũ:</strong> ' . count($old_users) . ' người</div>';
    if (isset($total_new_points)) {
        $label = $selected_transaction_type == 'doi_diem' ? 'Điểm đã đổi từ KH mới' : ($selected_transaction_type == 'tich_diem' ? 'Điểm tích từ KH mới' : 'Tổng điểm từ KH mới');
        echo '<div><strong>' . $label . ':</strong> ' . number_format(abs($total_new_points)) . ' điểm</div>';
    }
    if (isset($total_old_points)) {
        $label = $selected_transaction_type == 'doi_diem' ? 'Điểm đã đổi từ KH cũ' : ($selected_transaction_type == 'tich_diem' ? 'Điểm tích từ KH cũ' : 'Tổng điểm từ KH cũ');
        echo '<div><strong>' . $label . ':</strong> ' . number_format(abs($total_old_points)) . ' điểm</div>';
    }
    echo '</div>';
    echo '</div>';

    // Nhật ký theo cửa hàng
    echo '<h3>📍 Nhật ký ' . ($selected_transaction_type == 'tich_diem' ? 'tích điểm' : ($selected_transaction_type == 'doi_diem' ? 'đổi điểm' : 'giao dịch')) . ' của từng cửa hàng:</h3>';

    $store_points_query = "
        SELECT aff_by_store_id, 
               SUM(point_change) as total_points,
               SUM(CASE WHEN u_status = 'new' THEN point_change ELSE 0 END) as new_user_points,
               SUM(CASE WHEN u_status = 'old' THEN point_change ELSE 0 END) as old_user_points,
               COUNT(DISTINCT CASE WHEN u_status = 'new' THEN phone_number END) as new_user_count,
               COUNT(DISTINCT CASE WHEN u_status = 'old' THEN phone_number END) as old_user_count
        FROM $table_logs
        WHERE DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN %s AND %s
        $transaction_condition
        GROUP BY aff_by_store_id
        ORDER BY total_points DESC
    ";
    $store_points = $wpdb->get_results($wpdb->prepare($store_points_query, $start_date, $end_date));

    if ($store_points) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Cửa hàng</th><th>Tổng điểm</th><th>KH mới</th><th>Điểm từ KH mới</th><th>KH cũ</th><th>Điểm từ KH cũ</th></tr></thead>';
        echo '<tbody>';
        foreach ($store_points as $store) {
            $store_name = $wpdb->get_var($wpdb->prepare(
                "SELECT store_name FROM $table_store WHERE id = %d",
                $store->aff_by_store_id
            ));
            echo '<tr>';
            echo "<td><strong>" . esc_html($store_name) . "</strong></td>";
            echo "<td><strong>" . number_format($store->total_points) . "</strong></td>";
            echo "<td>" . $store->new_user_count . " người</td>";
            echo "<td style='color: #28a745;'>" . number_format($store->new_user_points) . " điểm</td>";
            echo "<td>" . $store->old_user_count . " người</td>";
            echo "<td style='color: #6c757d;'>" . number_format($store->old_user_points) . " điểm</td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Không có dữ liệu tích điểm cho cửa hàng nào trong khoảng thời gian này.</p>';
    }
}

// Các function khác giữ nguyên
function gpt_display_store_transaction_log() {
    global $wpdb;
    
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    $query = "SELECT aff_by_store_id, DATE(created_at) as date, SUM(point_change) as total_points
              FROM $table_logs
              WHERE aff_by_store_id IS NOT NULL
              GROUP BY aff_by_store_id, DATE(created_at)
              ORDER BY created_at DESC";
    
    $store_transactions = $wpdb->get_results($query);

    if (empty($store_transactions)) {
        echo "<p>Không có dữ liệu tích điểm cho các cửa hàng.</p>";
        return;
    }

    echo '<h2>Nhật Ký Tích Điểm Theo Cửa Hàng</h2>';

    foreach ($store_transactions as $transaction) {
        $store_name = $wpdb->get_var($wpdb->prepare(
            "SELECT store_name FROM $table_store WHERE id = %d",
            $transaction->aff_by_store_id
        ));

        if ($store_name) {
            echo "<h3>Ngày: " . date("d/m/Y", strtotime($transaction->date)) . "</h3>";
            echo "<p><strong>Cửa hàng: $store_name</strong> - Đã tích được <strong>{$transaction->total_points}</strong> điểm</p>";
        }
    }
}

function gpt_display_customer_report_by_store() {
    global $wpdb;

    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    $query = "
        SELECT 
            aff_by_store_id,
            phone_number,
            SUM(point_change) as total_points
        FROM $table_logs
        WHERE aff_by_store_id IS NOT NULL
        GROUP BY aff_by_store_id, phone_number
        ORDER BY aff_by_store_id, total_points DESC
    ";

    $customer_report = $wpdb->get_results($query);

    if (empty($customer_report)) {
        echo "<p>Không có dữ liệu tích điểm cho các cửa hàng.</p>";
        return;
    }

    echo '<h2>Báo Cáo Khách Hàng Theo Cửa Hàng</h2>';

    foreach ($customer_report as $report) {
        $store_name = $wpdb->get_var($wpdb->prepare(
            "SELECT store_name FROM $table_store WHERE id = %d",
            $report->aff_by_store_id
        ));

        if ($store_name) {
            echo "<h3>Cửa hàng: $store_name</h3>";

            echo "<table class='wp-list-table widefat fixed striped'>
                    <thead>
                        <tr>
                            <th>Số điện thoại</th>
                            <th>Tổng điểm</th>
                        </tr>
                    </thead>
                    <tbody>";

            echo "<tr>";
            echo "<td>{$report->phone_number}</td>";
            echo "<td>{$report->total_points}</td>";
            echo "</tr>";

            echo "</tbody></table>";
        }
    }
}

require_once plugin_dir_path(__FILE__) . '../../libs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

function gpt_export_affiliate_reports_excel() {
    global $wpdb;

    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    
    // Lấy parameters từ request
    $selected_store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : null;
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;
    $user_status = isset($_GET['user_status']) ? sanitize_text_field($_GET['user_status']) : '';
    $transaction_type = isset($_GET['transaction_type']) ? sanitize_text_field($_GET['transaction_type']) : '';

    // Xây dựng query với các filters
    $query = "SELECT l.*, s.store_name FROM $table_logs l 
              LEFT JOIN $table_store s ON l.aff_by_store_id = s.id 
              WHERE l.aff_by_store_id IS NOT NULL";
    
    $query_params = [];
    
    if ($selected_store_id) {
        $query .= " AND l.aff_by_store_id = %d";
        $query_params[] = $selected_store_id;
    }
    
    if ($user_status) {
        $query .= " AND l.u_status = %s";
        $query_params[] = $user_status;
    }
    
    if ($transaction_type) {
        $query .= " AND l.transaction_type = %s";
        $query_params[] = $transaction_type;
    }
    
    if ($start_date && $end_date) {
        $query .= " AND l.created_at BETWEEN %s AND %s";
        $query_params[] = $start_date . ' 00:00:00';
        $query_params[] = $end_date . ' 23:59:59';
    }

    $query .= " ORDER BY l.created_at DESC";

    // Execute query
    if (!empty($query_params)) {
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_params));
    } else {
        $logs = $wpdb->get_results($query);
    }

    if (empty($logs)) {
        wp_die('Không có dữ liệu để xuất Excel.');
    }

    // Tạo spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Đặt tên sheet
    $sheet->setTitle('Báo cáo tích điểm');

    // Thông tin header file
    $filter_info = [];
    if ($selected_store_id) {
        $store_name = $wpdb->get_var($wpdb->prepare(
            "SELECT store_name FROM $table_store WHERE id = %d", $selected_store_id
        ));
        $filter_info[] = "Cửa hàng: " . $store_name;
    }
    if ($user_status) {
        $filter_info[] = "Loại KH: " . ($user_status == 'new' ? 'Khách hàng mới' : 'Khách hàng cũ');
    }
    if ($transaction_type) {
        $filter_info[] = "Loại GD: " . ($transaction_type == 'tich_diem' ? 'Tích điểm' : 'Đổi điểm');
    }
    if ($start_date && $end_date) {
        $filter_info[] = "Từ " . date('d/m/Y', strtotime($start_date)) . " đến " . date('d/m/Y', strtotime($end_date));
    }

    // Title và filter info
    $sheet->setCellValue('A1', 'BÁO CÁO TÍCH ĐIỂM THEO CỬA HÀNG');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    if (!empty($filter_info)) {
        $sheet->setCellValue('A2', 'Bộ lọc: ' . implode(' | ', $filter_info));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    $sheet->setCellValue('A3', 'Xuất ngày: ' . date('d/m/Y H:i'));
    $sheet->mergeCells('A3:I3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Headers bảng dữ liệu
    $headers = [
        'STT',
        'Khách hàng', 
        'Số điện thoại', 
        'Cửa hàng', 
        'Số điểm', 
        'Loại khách hàng',
        'Loại giao dịch', 
        'Sản phẩm', 
        'Thời gian'
    ];
    
    $headerRow = 5;
    $sheet->fromArray($headers, NULL, 'A' . $headerRow);

    // Style cho headers
    $headerRange = 'A' . $headerRow . ':I' . $headerRow;
    $sheet->getStyle($headerRange)->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setRGB('4472C4');
    $sheet->getStyle($headerRange)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Dữ liệu
    $rowIndex = $headerRow + 1;
    $stt = 1;
    
    foreach ($logs as $log) {
        $sheet->setCellValue("A$rowIndex", $stt);
        $sheet->setCellValue("B$rowIndex", $log->customer_name);
        $sheet->setCellValue("C$rowIndex", $log->phone_number);
        $sheet->setCellValue("D$rowIndex", $log->store_name);
        $sheet->setCellValue("E$rowIndex", $log->point_change);
        $sheet->setCellValue("F$rowIndex", $log->u_status == 'new' ? 'Khách hàng mới' : 'Khách hàng cũ');
        $sheet->setCellValue("G$rowIndex", $log->transaction_type == 'tich_diem' ? 'Tích điểm' : 'Đổi điểm');
        $sheet->setCellValue("H$rowIndex", $log->product_name);
        $sheet->setCellValue("I$rowIndex", date('d/m/Y H:i', strtotime($log->created_at)));
        
        // Style cho từng loại
        if ($log->u_status == 'new') {
            $sheet->getStyle("F$rowIndex")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('D4F8D4'); // Xanh lá nhạt
        } else {
            $sheet->getStyle("F$rowIndex")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('F5F5F5'); // Xám nhạt
        }
        
        if ($log->transaction_type == 'tich_diem') {
            $sheet->getStyle("G$rowIndex")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('D4F8D4'); // Xanh lá nhạt
        } else {
            $sheet->getStyle("G$rowIndex")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('FFE6E6'); // Đỏ nhạt
        }

        $rowIndex++;
        $stt++;
    }

    // Thống kê tổng hợp
    $summaryRow = $rowIndex + 2;
    $sheet->setCellValue("A$summaryRow", 'THỐNG KÊ TỔNG HỢP');
    $sheet->mergeCells("A$summaryRow:I$summaryRow");
    $sheet->getStyle("A$summaryRow")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$summaryRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A$summaryRow")->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setRGB('E7E6E6');

    $summaryRow++;
    
    // Tính toán thống kê
    $total_records = count($logs);
    $new_customers = array_filter($logs, function($log) { return $log->u_status == 'new'; });
    $old_customers = array_filter($logs, function($log) { return $log->u_status == 'old'; });
    $tich_diem_transactions = array_filter($logs, function($log) { return $log->transaction_type == 'tich_diem'; });
    $doi_diem_transactions = array_filter($logs, function($log) { return $log->transaction_type == 'doi_diem'; });
    
    $total_points_earned = array_sum(array_column($tich_diem_transactions, 'point_change'));
    $total_points_redeemed = abs(array_sum(array_column($doi_diem_transactions, 'point_change')));

    // Hiển thị thống kê
    $stats = [
        ['Tổng số giao dịch:', $total_records],
        ['Khách hàng mới:', count($new_customers)],
        ['Khách hàng cũ:', count($old_customers)],
        ['Giao dịch tích điểm:', count($tich_diem_transactions)],
        ['Giao dịch đổi điểm:', count($doi_diem_transactions)],
        ['Tổng điểm tích được:', number_format($total_points_earned)],
        ['Tổng điểm đã đổi:', number_format($total_points_redeemed)]
    ];

    foreach ($stats as $stat) {
        $sheet->setCellValue("B$summaryRow", $stat[0]);
        $sheet->setCellValue("C$summaryRow", $stat[1]);
        $sheet->getStyle("B$summaryRow")->getFont()->setBold(true);
        $summaryRow++;
    }

    // Auto-resize columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Border cho toàn bộ bảng dữ liệu
    $dataRange = 'A' . $headerRow . ':I' . ($rowIndex - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);

    // Tạo filename
    $filename = 'bao_cao_tich_diem_cua_hang_' . date('Y_m_d_H_i_s') . '.xlsx';

    // Headers để download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Hook để xử lý export
add_action('wp_ajax_gpt_export_affiliate_reports', 'gpt_export_affiliate_reports_excel');
add_action('wp_ajax_nopriv_gpt_export_affiliate_reports', 'gpt_export_affiliate_reports_excel');

// Function để tạo nút export trong báo cáo
function gpt_add_export_button_to_reports() {
    $current_url = $_SERVER['REQUEST_URI'];
    $export_url = admin_url('admin-ajax.php?action=gpt_export_affiliate_reports');
    
    // Thêm các parameters hiện tại vào export URL
    if (isset($_GET['store_id'])) $export_url .= '&store_id=' . $_GET['store_id'];
    if (isset($_GET['user_status'])) $export_url .= '&user_status=' . $_GET['user_status'];
    if (isset($_GET['transaction_type'])) $export_url .= '&transaction_type=' . $_GET['transaction_type'];
    if (isset($_GET['start_date'])) $export_url .= '&start_date=' . $_GET['start_date'];
    if (isset($_GET['end_date'])) $export_url .= '&end_date=' . $_GET['end_date'];
    
    echo '<div style="margin: 20px 0;">';
    echo '<a href="' . esc_url($export_url) . '" class="button button-primary" style="background: #28a745; border-color: #28a745;">';
    echo '📊 Xuất Excel</a>';
    echo '<small style="margin-left: 10px; color: #666;">Xuất dữ liệu hiện tại ra file Excel</small>';
    echo '</div>';
}
