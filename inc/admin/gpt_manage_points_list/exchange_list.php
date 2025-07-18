<?php 
function gpt_render_exchange_list_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT;

    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY time DESC");

    echo '<div class="wrap">';
    echo '<h1>Danh sách đổi điểm</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
            <th>SĐT</th>
            <th>Sản phẩm</th>
            <th>Điểm đổi</th>
            <th>Cửa hàng</th>
            <th>Người thực hiện</th>
            <th>Trạng thái</th>
            <th>Thời gian</th>
        </tr></thead>';
    echo '<tbody>';

    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->phone) . '</td>';
            echo '<td>' . esc_html($row->product) . '</td>';
            echo '<td>' . esc_html($row->points) . '</td>';
            echo '<td>' . esc_html($row->store_name) . '</td>';
            echo '<td>' . esc_html($row->user_name) . '</td>';
            echo '<td>' . esc_html($row->status) . '</td>';
            echo '<td>' . esc_html($row->time) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">Chưa có dữ liệu.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}