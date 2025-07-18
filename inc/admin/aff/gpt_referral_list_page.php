<?php

function gpt_referral_list_page() {
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
        SELECT id, customer_name, phone_number, referrer_name, phone_referrer, time, store
        FROM $table
        $where
        ORDER BY time DESC
        LIMIT 100
    ");

    echo '<div class="wrap"><h1>Người được giới thiệu</h1>';
    echo '<form method="get" style="margin-bottom:20px;">
        <input type="hidden" name="page" value="gpt-nguoi-duoc-gioi-thieu" />
        Ngày: <input type="date" name="date" value="' . esc_attr($_GET['date'] ?? '') . '" />
        Người giới thiệu: <input type="text" name="ref" value="' . esc_attr($_GET['ref'] ?? '') . '" />
        <input type="submit" class="button" value="Lọc" />
    </form>';

    echo '<table class="widefat fixed striped"><thead><tr>
        <th>STT</th><th>Khách hàng</th><th>SĐT</th><th>Người giới thiệu</th><th>Chi nhánh</th><th>Thời gian</th>
    </tr></thead><tbody>';

    $i = 1;
    foreach ($results as $row) {
        echo '<tr>
            <td>' . $i++ . '</td>
            <td>' . esc_html($row->customer_name) . '</td>
            <td>' . esc_html($row->phone_number) . '</td>
            <td>' . esc_html($row->referrer_name . ' (' . $row->phone_referrer . ')') . '</td>
            <td>' . esc_html($row->store) . '</td>
            <td>' . esc_html($row->time) . '</td>
        </tr>';
    }

    echo '</tbody></table></div>';
}

