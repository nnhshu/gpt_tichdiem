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

        $used_barcode_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_logs WHERE barcode_status = 'used'"
        ));

        $top_store_query = "
            SELECT aff_by_store_id, SUM(point_change) AS total_points
            FROM $table_logs
            WHERE aff_by_store_id IS NOT NULL
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
            <h3>Số lượng barcode đã được sử dụng</h3>
            <p><?php echo $used_barcode_count; ?></p>
        </div>
        <div class="card">
            <h3>Cửa hàng có số điểm tích được nhiều nhất</h3>
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
                    <option value="<?php echo esc_attr($store->aff_by_store_id); ?>">
                        <?php echo esc_html($store_name); ?>
                    </option>
                <?php endforeach; ?>
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
    <hr>

    <?php
        $query = "SELECT * FROM $table_logs WHERE aff_by_store_id IS NOT NULL";
        
        if ($selected_store_id) {
            $query .= $wpdb->prepare(" AND aff_by_store_id = %d", $selected_store_id);
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
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Cửa hàng</th>
                    <th>Số điểm tích</th>
                    <th>Lần đầu tích điểm</th>
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
                                <span>✅</span>
                            <?php else: ?>
                                <span>❌</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->transaction_type == 'tich_diem'): ?>
                                <span class="gpt-badge gpt-badge-success">Tích điểm</span>
                            <?php else: ?>
                                <span class="gpt-badge gpt-badge-danger">Đổi điểm</span>
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


function gpt_display_store_transaction_log() {
    global $wpdb;
    
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    // Truy vấn nhật ký của tất cả các cửa hàng
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

    // Hiển thị nhật ký tích điểm theo cửa hàng
    echo '<h2>Nhật Ký Tích Điểm Theo Cửa Hàng</h2>';

    // Duyệt qua từng cửa hàng và hiển thị nhật ký tích điểm theo ngày
    foreach ($store_transactions as $transaction) {
        // Lấy tên cửa hàng từ store table
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

    // Bảng logs chứa thông tin giao dịch tích điểm
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    // Truy vấn các cửa hàng và tổng điểm tích lũy theo số điện thoại cho từng cửa hàng
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

    // Duyệt qua từng cửa hàng và hiển thị thông tin khách hàng tích điểm
    foreach ($customer_report as $report) {
        // Lấy tên cửa hàng từ bảng store
        $store_name = $wpdb->get_var($wpdb->prepare(
            "SELECT store_name FROM $table_store WHERE id = %d",
            $report->aff_by_store_id
        ));

        if ($store_name) {
            echo "<h3>Cửa hàng: $store_name</h3>";

            // Hiển thị thông tin khách hàng
            echo "<table class='wp-list-table widefat fixed striped'>
                    <thead>
                        <tr>
                            <th>Số điện thoại</th>
                            <th>Tổng điểm</th>
                        </tr>
                    </thead>
                    <tbody>";

            // Hiển thị từng khách hàng và tổng điểm của họ
            echo "<tr>";
            echo "<td>{$report->phone_number}</td>";
            echo "<td>{$report->total_points}</td>";
            echo "</tr>";

            echo "</tbody></table>";
        }
    }
}

function gpt_generate_summary_report() {
    global $wpdb;

    // Bảng chứa thông tin giao dịch
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_store = BIZGPT_PLUGIN_WP_STORE_LIST;

    // Đặt mặc định cho ngày bắt đầu và ngày kết thúc
    $current_month = date('Y-m'); // Lấy tháng hiện tại
    $current_quarter = ceil(date('n') / 3); // Lấy quý hiện tại
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $current_month . '-01';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');

    // Nếu người dùng chọn tháng, quý hoặc từ ngày đến ngày
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : null;
    $selected_quarter = isset($_GET['quarter']) ? sanitize_text_field($_GET['quarter']) : null;

    // Form lựa chọn tháng, quý hoặc từ ngày đến ngày
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
    echo '<button type="submit" class="button">Xem báo cáo</button>';
    echo '</form>';

    // Truy vấn tổng hợp cho các ngày/tháng/quý được chọn
    if ($selected_month) {
        $start_date = $selected_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));  // Tính đến ngày cuối của tháng
    }

    if ($selected_quarter) {
        $start_date = date('Y', strtotime($start_date)) . '-' . (($selected_quarter - 1) * 3 + 1) . '-01';
        $end_date = date('Y', strtotime($start_date)) . '-' . ($selected_quarter * 3) . '-31'; // Lấy ngày cuối của quý
    }

    // Điều chỉnh truy vấn để lọc theo ngày/tháng/quý
    $query = "
        SELECT DISTINCT phone_number, SUM(point_change) as total_points
        FROM $table_logs
        WHERE DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN %s AND %s
        GROUP BY phone_number
    ";

    $logs = $wpdb->get_results($wpdb->prepare($query, $start_date, $end_date));

    // Hiển thị báo cáo
    echo '<h3>Báo cáo khách hàng tích điểm từ ' . date('d/m/Y', strtotime($start_date)) . ' đến ' . date('d/m/Y', strtotime($end_date)) . '</h3>';

    if ($logs) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Khách hàng</th><th>Tổng điểm tích lũy</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo '<tr>';
            echo "<td>{$log->phone_number}</td>";
            echo "<td>{$log->total_points}</td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Không có dữ liệu cho khoảng thời gian này.</p>';
    }

    // 3. Nhật ký tích điểm của từng cửa hàng
    echo '<h3>Nhật ký tích điểm của từng cửa hàng:</h3>';

    // Truy vấn nhật ký tích điểm của cửa hàng
    $store_points_query = "
        SELECT aff_by_store_id, SUM(point_change) as total_points
        FROM $table_logs
        WHERE DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN %s AND %s
        GROUP BY aff_by_store_id
        ORDER BY total_points DESC
    ";
    $store_points = $wpdb->get_results($wpdb->prepare($store_points_query, $start_date, $end_date));

    if ($store_points) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Cửa hàng</th><th>Tổng điểm tích lũy</th></tr></thead>';
        echo '<tbody>';
        foreach ($store_points as $store) {
            $store_name = $wpdb->get_var($wpdb->prepare(
                "SELECT store_name FROM $table_store WHERE id = %d",
                $store->aff_by_store_id
            ));
            echo '<tr>';
            echo "<td>{$store_name}</td>";
            echo "<td>{$store->total_points}</td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Không có dữ liệu tích điểm cho cửa hàng nào trong khoảng thời gian này.</p>';
    }
}
