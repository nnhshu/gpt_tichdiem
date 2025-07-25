<?php
function gpt_analytics_reports_page_callback() {
    global $wpdb;

    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_users = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    // Lấy tham số filter
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    ?>
    <div class="wrap">
        <div class="bg_wrap">
            <h1>📊 Báo cáo phân tích tích điểm</h1>
            <hr>
            
            <!-- Filter Form -->
            <div class="ux-row" style="margin-bottom: 20px;">
                <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
                    <input type="hidden" name="page" value="gpt-analytics-reports">
                    <div class="col large-2">
                        <label for="month">Chọn tháng:</label>
                        <input type="month" name="month" id="month" value="<?php echo esc_attr($selected_month); ?>" />
                    </div>
                    <div class="col large-2">
                        <label for="year">Chọn năm:</label>
                        <select name="year" id="year">
                            <?php for($y = 2020; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col large-1">
                        <button type="submit" class="button primary">Xem báo cáo</button>
                    </div>
                </form>
            </div>
            
            <?php
            // Lấy dữ liệu thống kê
            $stats = gpt_get_analytics_data($selected_month, $selected_year);
            ?>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px;">
                    <h3 style="margin: 0 0 10px 0; color: white;">📈 Tổng điểm tích lũy tháng</h3>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo number_format($stats['total_points_month']); ?></p>
                    <small>Tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?></small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px;">
                    <h3 style="margin: 0 0 10px 0; color: white;">👥 Người dùng mới</h3>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo $stats['new_users_month']; ?></p>
                    <small>Tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?></small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 10px;">
                    <h3 style="margin: 0 0 10px 0; color: white;">🎯 Tổng giao dịch</h3>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo $stats['total_transactions_month']; ?></p>
                    <small>Tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?></small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 10px;">
                    <h3 style="margin: 0 0 10px 0; color: white;">💎 Điểm trung bình/giao dịch</h3>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo $stats['avg_points_per_transaction']; ?></p>
                    <small>Tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?></small>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Biểu đồ theo ngày trong tháng -->
                <div class="chart-container" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>📅 Tích điểm theo ngày (Tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?>)</h3>
                    <canvas id="dailyChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Biểu đồ theo tháng trong năm -->
                <div class="chart-container" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>📊 Tích điểm theo tháng (Năm <?php echo $selected_year; ?>)</h3>
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Biểu đồ người dùng mới -->
            <div class="chart-container" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>👥 Người dùng mới theo tháng (Năm <?php echo $selected_year; ?>)</h3>
                <canvas id="newUsersChart" width="400" height="200"></canvas>
            </div>
            
            <!-- Bảng chi tiết -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>📋 Chi tiết tích điểm theo ngày</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Tổng điểm</th>
                            <th>Số giao dịch</th>
                            <th>Người dùng mới</th>
                            <th>Điểm TB/giao dịch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['daily_details'] as $day_data): ?>
                        <tr>
                            <td><?php echo esc_html($day_data['date']); ?></td>
                            <td><?php echo number_format($day_data['total_points']); ?></td>
                            <td><?php echo $day_data['total_transactions']; ?></td>
                            <td><?php echo $day_data['new_users']; ?></td>
                            <td><?php echo $day_data['avg_points']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dữ liệu biểu đồ
        const dailyData = <?php echo json_encode($stats['chart_data']['daily']); ?>;
        const monthlyData = <?php echo json_encode($stats['chart_data']['monthly']); ?>;
        const newUsersData = <?php echo json_encode($stats['chart_data']['new_users']); ?>;
        
        // Biểu đồ theo ngày
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.labels,
                datasets: [{
                    label: 'Điểm tích lũy',
                    data: dailyData.points,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Biểu đồ theo tháng
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Điểm tích lũy',
                    data: monthlyData.points,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Biểu đồ người dùng mới
        const newUsersCtx = document.getElementById('newUsersChart').getContext('2d');
        new Chart(newUsersCtx, {
            type: 'line',
            data: {
                labels: newUsersData.labels,
                datasets: [{
                    label: 'Người dùng mới',
                    data: newUsersData.users,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    
    <style>
        .dashboard-cards .card h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .chart-container {
            min-height: 300px;
        }
        .chart-container h3 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
    <?php
}

function gpt_get_analytics_data($selected_month, $selected_year) {
    global $wpdb;
    
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;
    $table_users = BIZGPT_PLUGIN_WP_SAVE_USERS;
    
    // Thống kê tháng
    $start_month = $selected_month . '-01';
    $end_month = date('Y-m-t', strtotime($start_month));
    
    // Tổng điểm tích lũy trong tháng
    $total_points_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(point_change), 0) FROM $table_logs 
         WHERE DATE(created_at) BETWEEN %s AND %s AND transaction_type = 'tich_diem'",
        $start_month, $end_month
    ));
    
    // Số người dùng mới trong tháng
    $new_users_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT phone_number) FROM $table_logs 
         WHERE DATE(created_at) BETWEEN %s AND %s AND u_status = 'new'",
        $start_month, $end_month
    ));
    
    // Tổng giao dịch trong tháng
    $total_transactions_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_logs 
         WHERE DATE(created_at) BETWEEN %s AND %s AND transaction_type = 'tich_diem'",
        $start_month, $end_month
    ));
    
    // Điểm trung bình mỗi giao dịch
    $avg_points_per_transaction = $total_transactions_month > 0 ? 
        round($total_points_month / $total_transactions_month, 1) : 0;
    
    // Dữ liệu theo ngày trong tháng
    $daily_query = $wpdb->prepare(
        "SELECT 
            DATE(created_at) as date,
            SUM(CASE WHEN transaction_type = 'tich_diem' THEN point_change ELSE 0 END) as total_points,
            COUNT(CASE WHEN transaction_type = 'tich_diem' THEN 1 END) as total_transactions,
            COUNT(CASE WHEN u_status = 'new' THEN 1 END) as new_users
         FROM $table_logs 
         WHERE DATE(created_at) BETWEEN %s AND %s
         GROUP BY DATE(created_at)
         ORDER BY date",
        $start_month, $end_month
    );
    
    $daily_results = $wpdb->get_results($daily_query);
    
    // Dữ liệu theo tháng trong năm
    $monthly_query = $wpdb->prepare(
        "SELECT 
            MONTH(created_at) as month,
            SUM(CASE WHEN transaction_type = 'tich_diem' THEN point_change ELSE 0 END) as total_points,
            COUNT(CASE WHEN u_status = 'new' THEN 1 END) as new_users
         FROM $table_logs 
         WHERE YEAR(created_at) = %d
         GROUP BY MONTH(created_at)
         ORDER BY month",
        $selected_year
    );
    
    $monthly_results = $wpdb->get_results($monthly_query);
    
    // Chuẩn bị dữ liệu cho biểu đồ
    $chart_data = [
        'daily' => [
            'labels' => [],
            'points' => []
        ],
        'monthly' => [
            'labels' => [],
            'points' => []
        ],
        'new_users' => [
            'labels' => [],
            'users' => []
        ]
    ];
    
    // Dữ liệu ngày
    $daily_details = [];
    foreach ($daily_results as $row) {
        $chart_data['daily']['labels'][] = date('d/m', strtotime($row->date));
        $chart_data['daily']['points'][] = intval($row->total_points);
        
        $avg_points = $row->total_transactions > 0 ? round($row->total_points / $row->total_transactions, 1) : 0;
        $daily_details[] = [
            'date' => date('d/m/Y', strtotime($row->date)),
            'total_points' => intval($row->total_points),
            'total_transactions' => intval($row->total_transactions),
            'new_users' => intval($row->new_users),
            'avg_points' => $avg_points
        ];
    }
    
    // Dữ liệu tháng
    $month_names = [
        1 => 'T1', 2 => 'T2', 3 => 'T3', 4 => 'T4', 5 => 'T5', 6 => 'T6',
        7 => 'T7', 8 => 'T8', 9 => 'T9', 10 => 'T10', 11 => 'T11', 12 => 'T12'
    ];
    
    $monthly_data = array_fill(1, 12, 0);
    $new_users_monthly = array_fill(1, 12, 0);
    
    foreach ($monthly_results as $row) {
        $monthly_data[$row->month] = intval($row->total_points);
        $new_users_monthly[$row->month] = intval($row->new_users);
    }
    
    for ($i = 1; $i <= 12; $i++) {
        $chart_data['monthly']['labels'][] = $month_names[$i];
        $chart_data['monthly']['points'][] = $monthly_data[$i];
        $chart_data['new_users']['labels'][] = $month_names[$i];
        $chart_data['new_users']['users'][] = $new_users_monthly[$i];
    }
    
    return [
        'total_points_month' => intval($total_points_month),
        'new_users_month' => intval($new_users_month),
        'total_transactions_month' => intval($total_transactions_month),
        'avg_points_per_transaction' => $avg_points_per_transaction,
        'daily_details' => $daily_details,
        'chart_data' => $chart_data
    ];
}
