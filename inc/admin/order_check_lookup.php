<?php

// Order check
function gpt_render_order_check_lookup() {
    ?>
    <style>
        form#order-check-form {
            background: #fff;
            border: 1px solid #eee;
            padding: 24px;
            max-width: 500px;
            margin: 20px auto;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
        }
        form#order-check-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        form#order-check-form input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
            box-sizing: border-box;
        }
        form#order-check-form input[type="text"]:focus {
            border-color: #6c63ff;
            outline: none;
        }
        form#order-check-form button {
            background-color: #173d7c;
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.25s;
            width: 100%;
            margin: 0;
            padding: 8px;
        }
        form#order-check-form button:hover {
            background-color: #5848d1;
        }
        .order-check-result {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 30px auto;
            max-width: 960px;
            font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
            font-size: 15px;
            color: #333;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .order-check-result h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #111827;
        }
        .order-check-result h4 {
            font-size: 17px;
            margin-top: 24px;
            margin-bottom: 10px;
            color: #374151;
        }
        .order-check-result ul {
            padding-left: 20px;
            margin-bottom: 20px;
        }
        .order-check-result ul li {
            margin-bottom: 6px;
            line-height: 1.5;
        }
        .order-check-result img {
            max-width: 120px;
            border-radius: 6px;
            border: 1px solid #ddd;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .order-check-result table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        .order-check-result table th,
        .order-check-result table td {
            padding: 12px;
            border: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }
        .order-check-result table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .order-check-result pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: inherit;
            margin: 0;
        }
    </style>

    <!-- Form nhập mã cào -->
    <form method="get" id="order-check-form">
        <input type="hidden" name="page" value="gpt-tra-cuu-ma-cao" />
        <label for="lookup_macao">Nhập barcode:</label>
        <input type="text" name="lookup_macao" id="lookup_macao" value="<?php echo esc_attr($_GET['lookup_macao'] ?? ''); ?>" required>
        <button type="submit">Tra cứu</button>
    </form>

    <div class="order-check-result">
    <?php
    if (isset($_GET['lookup_macao'])) {
        global $wpdb;

        $lookup_macao = sanitize_text_field($_GET['lookup_macao']);
        $table_macao = BIZGPT_PLUGIN_WP_BARCODE;
        $table_donhang = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;

        $barcode_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_macao WHERE barcode = %s LIMIT 1",
            $lookup_macao
        ));

        if (!$barcode_row || !$barcode_row->order_by_product_id) {
            echo '<p>❌ Không tìm thấy mã cào hoặc chưa được gán đơn hàng.</p>';
        } else {
            $order_row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_donhang WHERE order_id = %d",
                $barcode_row->order_by_product_id
            ));

            if (!$order_row) {
                echo '<p>❌ Không tìm thấy thông tin đơn hàng liên quan.</p>';
            } else {
                $post_id = $order_row->order_id;
                $post = get_post($post_id);

                if (!$post || $post->post_type !== 'order_check') {
                    echo '<p>❌ Không tìm thấy đơn hàng gốc.</p>';
                } else {
                    $meta = get_post_meta($post_id);

                    echo '<h3>Thông tin đơn hàng #' . $post_id . '</h3>';
                    echo '<ul>';
                    echo '<li><strong>Order ID:</strong> ' . esc_html($meta['order_id'][0] ?? '') . '</li>';
                    echo '<li><strong>Người xuất kho:</strong> ' . esc_html($meta['order_export_by'][0] ?? '') . '</li>';
                    echo '<li><strong>Lô:</strong> ' . esc_html($meta['order_batch'][0] ?? '') . '</li>';
                    echo '<li><strong>Ngày:</strong> ' . esc_html($meta['order_date'][0] ?? '') . '</li>';
                    echo '<li><strong>Trạng thái:</strong> ' . esc_html($meta['order_status'][0] ?? 'Mới') . '</li>';
                    echo '</ul>';

                    if (!empty($meta['order_images'][0])) {
                        $images = explode(',', $meta['order_images'][0]);
                        echo '<h4>Ảnh đơn hàng:</h4><div style="display:flex;gap:10px;flex-wrap:wrap">';
                        foreach ($images as $img) {
                            echo '<img src="' . esc_url($img) . '">';
                        }
                        echo '</div>';
                    }

                    $items = get_post_meta($post_id, '_order_check_line_items', true);
                    if (!empty($items)) {
                        echo '<h4>Sản phẩm:</h4>';
                        echo '<table>';
                        echo '<tr><th>Tên SP</th><th>Số lượng</th><th>Mã cào</th><th>Tỉnh</th><th>Kênh</th></tr>';
                        foreach ($items as $item) {
                            $product = wc_get_product($item['product_id']);
                            $title = $product ? $product->get_name() : 'N/A';
                            echo '<tr>';
                            echo '<td>' . esc_html($title) . '</td>';
                            echo '<td>' . intval($item['quantity']) . '</td>';
                            echo '<td><pre>' . esc_html($item['barcode'] ?? '') . '</pre></td>';
                            echo '<td>' . esc_html($item['province'] ?? '') . '</td>';
                            echo '<td>' . esc_html($item['channel'] ?? '') . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }

                    $logs = get_post_meta($post_id, '_inventory_logs', true);
                    if (!empty($logs)) {
                        echo '<h4>Log tồn kho:</h4><ul>';
                        foreach ($logs as $log) {
                            echo '<li>' . esc_html($log) . '</li>';
                        }
                        echo '</ul>';
                    }

                    $status_logs = get_post_meta($post_id, 'order_status_logs', true);
                    if (!empty($status_logs)) {
                        echo '<h4>Log trạng thái:</h4><ul>';
                        foreach ($status_logs as $slog) {
                            echo '<li>' . esc_html($slog['timestamp'] ?? '') . ' – ' . esc_html($slog['status'] ?? '') . '</li>';
                        }
                        echo '</ul>';
                    }
                }
            }
        }
    }
    ?>
    </div>
    <?php
}
