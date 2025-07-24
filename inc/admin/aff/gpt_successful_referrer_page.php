<?php

function gpt_successful_referrer_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'gpt_logs';

    $where = "WHERE (phone_referrer IS NOT NULL OR referrer_name IS NOT NULL)";

    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = sanitize_text_field($_GET['start_date']);
        $end_date = sanitize_text_field($_GET['end_date']);
        $where .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    } elseif (!empty($_GET['date'])) {
        $date = sanitize_text_field($_GET['date']);
        $where .= " AND DATE(created_at) = '$date'";
    }

    if (!empty($_GET['phone_number'])) {
        $phone_number = esc_sql($_GET['phone_number']);
        $where .= " AND phone_referrer LIKE '%$phone_number%'";
    }

    $results = $wpdb->get_results("
        SELECT referrer_name, phone_referrer,
               COUNT(*) as so_luot,
               SUM(point_change) as total_point,
               MAX(created_at) as lan_gan_nhat
        FROM $table
        $where
        GROUP BY phone_referrer, referrer_name
        ORDER BY so_luot DESC
        LIMIT 100
    ");

    ?>
    <div class="bg_wrap">
        <h1>Danh sách giới thiệu thành công</h1>
        <hr>
        <div class="ux-row">
            <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
                <input type="hidden" name="page" value="gpt-successful-referrer" />
                <div class="col large-2">
                    <label for="start_date">Từ ngày:</label>
                    <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>" />
                </div>
                <div class="col large-2">
                    <label for="end_date">Đến ngày:</label>
                    <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>" />
                </div>
                <div class="col large-2">
                    <label for="phone_number">Số điện thoại người giới thiệu:</label>
                    <input type="text" name="phone_number" value="<?php echo esc_attr($_GET['phone_number'] ?? ''); ?>" />
                </div>
                <div class="col large-1">
                    <div class="d-flex gap-1">
                        <input type="submit" class="button button-primary" value="Lọc" />
                        <a href="admin.php?page=gpt-successful-referrer" class="button button-danger">Reset Bộ Lọc</a>
                    </div>
                </div>
            </form>
        </div>
        <hr>
        <table class="widefat fixed striped" style="margin-top: 24px;">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Người giới thiệu</th>
                    <th>SĐT</th>
                    <th>Lượt</th>
                    <th>Tổng điểm</th>
                    <th>Gần nhất</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . $i++ . '</td>';
                    echo '<td>' . esc_html($row->referrer_name) . '</td>';
                    echo '<td>' . esc_html($row->phone_referrer) . '</td>';
                    echo '<td>' . intval($row->so_luot) . '</td>';
                    echo '<td>' . intval($row->total_point) . '</td>';
                    echo '<td>' . esc_html($row->lan_gan_nhat) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php gpt_top_10_referrers(); ?>
    <?php
}

function gpt_top_10_referrers() {
    global $wpdb;
    $table = $wpdb->prefix . 'gpt_logs';

    // Lấy dữ liệu Top 10 người giới thiệu
    $results = $wpdb->get_results("
        SELECT referrer_name, phone_referrer,
               COUNT(*) as so_luot,
               SUM(point_change) as total_point,
               MAX(created_at) as lan_gan_nhat
        FROM $table
        GROUP BY phone_referrer, referrer_name
        ORDER BY so_luot DESC
        LIMIT 10
    ");

    ?>
    <div class="bg_wrap top10" style="margin-top: 32px;">
        <h1>Bảng xếp hạng Top 10 người giới thiệu</h1>
        <hr>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Thứ hạng</th>
                    <th>Người giới thiệu</th>
                    <th>SĐT</th>
                    <th>Lượt</th>
                    <th>Tổng điểm</th>
                    <th>Gần nhất</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . $rank++ . '</td>';
                    echo '<td>' . esc_html($row->referrer_name) . '</td>';
                    echo '<td>' . esc_html($row->phone_referrer) . '</td>';
                    echo '<td>' . intval($row->so_luot) . '</td>';
                    echo '<td>' . intval($row->total_point) . '</td>';
                    echo '<td>' . esc_html($row->lan_gan_nhat) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

