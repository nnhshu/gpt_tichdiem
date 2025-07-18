<?php
function gpt_render_duyet_barcode_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $selected_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
    $where_sql = "WHERE status = 'pending'";
    $params = [];

    if ($selected_date) {
        $where_sql .= " AND DATE(created_at) = %s";
        $params[] = $selected_date;
    }

    $sessions = $wpdb->get_results(
        $wpdb->prepare("
            SELECT session, COUNT(*) as total
            FROM $table
            $where_sql
            GROUP BY session
            ORDER BY MAX(created_at) DESC
        ", ...$params)
    );

    ?>
    
    <div class="wrap">
        <h1>Danh sách phiên cần duyệt</h1>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="gpt-browse-barcodes">
            <label>Lọc theo ngày: 
                <input type="date" name="filter_date" value="<?php echo esc_attr($selected_date); ?>">
            </label>
            <button type="submit" class="button">Lọc</button>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Số lượng mã</th>
                    <th>Ngày tạo gần nhất</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?php echo esc_html($session->session); ?></td>
                    <td><?php echo esc_html($session->total); ?></td>
                    <td>
                        <?php
                        $ngay = $wpdb->get_var($wpdb->prepare("SELECT MAX(created_at) FROM $table WHERE session = %s", $session->session));
                        echo esc_html($ngay);
                        ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=gpt-config-barcode&tab=browse&action=view&session=' . urlencode($session->session)); ?>" class="button">Xem mã</a>
                        <a href="<?php echo admin_url('admin.php?page=gpt-config-barcode&tab=browse&action=approve&session=' . urlencode($session->session)); ?>" class="button button-primary">Duyệt tất cả</a>
                        <a href="' . admin_url('admin.php?page=gpt-config-barcode&tab=browse&action=delete&session=' . urlencode($session)) . '" class="button button-danger" onclick="return confirm(\'Bạn có chắc muốn xoá toàn bộ mã trong phiên này?\')">Xoá</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    if (isset($_GET['action'], $_GET['session']) && $_GET['action'] === 'view') {
        global $wpdb;
        $table = BIZGPT_PLUGIN_WP_BARCODE;
        $session = sanitize_text_field($_GET['session']);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE session = %s ORDER BY id ASC",
            $session
        ));

        if (empty($results)) {
            echo '<div class="notice notice-warning"><p>Không tìm thấy mã trong phiên này.</p></div>';
            return;
        }

        echo '<h2>Chi tiết phiên: ' . esc_html($session) . '</h2>';
        echo '<table class="widefat striped">';
        echo '<thead>
            <tr>
                <th>ID</th>
                <th>Mã cào</th>
                <th>Điểm</th>
                <th>Trạng thái</th>
                <th>Sản phẩm</th>
                <th>Ngày tạo</th>
                <th>Token phủ bạc</th>
                <th>Link QR</th>
                <th>Bar code</th>
            </tr>
        </thead>';
        echo '<tbody>';

        foreach ($results as $row) {

            $custom_prod_id = $row->product_id;
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
                    $product_name = $product->post_title;
                    $product_image = get_the_post_thumbnail_url($product->ID, 'medium');
                } else {
                    echo $custom_prod_id;
                }
            
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->barcode) . '</td>';
            echo '<td>' . esc_html($row->point) . '</td>';
            $status_map = [
                'pending'         => 'Chờ duyệt',
                'unused'    => 'Chưa sử dụng',
                'used'      => 'Đã sử dụng',
            ];

            $status_key = isset($status_map[$row->status]) ? $status_map[$row->status] : 'pending';
            $status_class = 'status-' . $row->status;

            echo '<td><span class="status-label ' . esc_attr($status_class) . '">' . esc_html($status_key) . '</span></td>';
            echo '<td>' . esc_html($product_name) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '<td>' . esc_html($row->token) . '</td>';
            echo '<td><a href="' . esc_url($row->qr_code_url) . '" target="_blank">Xem QR</a></td>';
            echo '<td><a href="' . esc_url($row->barcode) . '" target="_blank">Xem Barcode</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['session'])) {
        $session = sanitize_text_field($_GET['session']);
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET status = 'unused' WHERE session = %s AND status = 'pending'",
            $session
        ));

        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã duyệt ' . intval($updated) . ' mã trong phiên <code>' . esc_html($session) . '</code></p></div>';
    }
}
