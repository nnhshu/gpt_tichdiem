<?php

function gpt_successful_referrer_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'gpt_logs';

    // Lọc theo ngày hoặc người giới thiệu
    $where = "WHERE (phone_referrer IS NOT NULL OR referrer_name IS NOT NULL)";
    if (!empty($_GET['date'])) {
        $date = sanitize_text_field($_GET['date']);
        $where .= " AND DATE(time) = '$date'";
    }
    if (!empty($_GET['ref'])) {
        $ref = esc_sql($_GET['ref']);
        $where .= " AND (referrer_name LIKE '%$ref%' OR phone_referrer LIKE '%$ref%')";
    }

    $results = $wpdb->get_results("
        SELECT referrer_name, phone_referrer,
               COUNT(*) as so_luot,
               SUM(point_change) as total_point,
               MAX(time) as lan_gan_nhat
        FROM $table
        $where
        GROUP BY phone_referrer, referrer_name
        ORDER BY so_luot DESC
        LIMIT 100
    ");

    echo '<div class="wrap"><h1>Người giới thiệu thành công</h1>';
    echo '<form method="get" style="margin-bottom:20px;">
        <input type="hidden" name="page" value="gpt-nguoi-gioi-thieu-thanh-cong" />
        Ngày: <input type="date" name="date" value="' . esc_attr($_GET['date'] ?? '') . '" />
        Người giới thiệu: <input type="text" name="ref" value="' . esc_attr($_GET['ref'] ?? '') . '" />
        <input type="submit" class="button" value="Lọc" />
    </form>';

    echo '<table class="widefat fixed striped"><thead><tr>
        <th>STT</th><th>Người giới thiệu</th><th>SĐT</th><th>Lượt</th><th>Tổng điểm</th><th>Gần nhất</th>
    </tr></thead><tbody>';

    $i = 1;
    foreach ($results as $row) {
        echo '<tr>
            <td>' . $i++ . '</td>
            <td>' . esc_html($row->referrer_name) . '</td>
            <td>' . esc_html($row->phone_referrer) . '</td>
            <td>' . intval($row->so_luot) . '</td>
            <td>' . intval($row->total_point) . '</td>
            <td>' . esc_html($row->lan_gan_nhat) . '</td>
        </tr>';
    }

    echo '</tbody></table></div>';
}
