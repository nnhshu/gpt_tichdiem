<?php

function gpt_render_lot_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_PRODUCT_LOT;

    // Xử lý thêm lô mới
    if (isset($_POST['add_lot'])) {
        $lot_name = sanitize_text_field($_POST['lot_name']);
        $product_id = sanitize_text_field($_POST['product_id']);
        
        // Kiểm tra lot_name đã tồn tại chưa
        $existing_lot = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE lot_name = %s",
            $lot_name
        ));
        
        if ($existing_lot > 0) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Tên lô đã tồn tại. Vui lòng chọn tên khác.</p></div>';
        } else {
            $result = $wpdb->insert($table, [
                'lot_name'   => $lot_name,
                'product_id' => $product_id,
            ]);
            
            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Đã thêm lô sản phẩm mới.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Có lỗi xảy ra khi thêm lô.</p></div>';
            }
        }
    }

    // Xử lý xóa lô
    if (isset($_GET['delete_id'])) {
        $result = $wpdb->delete($table, ['id' => intval($_GET['delete_id'])]);
        
        if ($result !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xoá lô sản phẩm.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Có lỗi xảy ra khi xóa lô.</p></div>';
        }
    }

    // Xử lý cập nhật lô
    if (isset($_POST['edit_lot_id'])) {
        $lot_name = sanitize_text_field($_POST['lot_name']);
        $product_id = sanitize_text_field($_POST['product_id']);
        $edit_lot_id = intval($_POST['edit_lot_id']);
        
        // Kiểm tra lot_name đã tồn tại chưa (trừ record hiện tại)
        $existing_lot = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE lot_name = %s AND id != %d",
            $lot_name, $edit_lot_id
        ));
        
        if ($existing_lot > 0) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Tên lô đã tồn tại. Vui lòng chọn tên khác.</p></div>';
        } else {
            $result = $wpdb->update($table, [
                'lot_name'   => $lot_name,
                'product_id' => $product_id,
            ], ['id' => $edit_lot_id]);

            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật lô sản phẩm.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Có lỗi xảy ra khi cập nhật lô.</p></div>';
            }
        }
    }

    // Phân trang
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    
    // Lấy danh sách lô với thông tin sản phẩm
    $lots = $wpdb->get_results($wpdb->prepare(
        "SELECT l.*, p.post_title as product_name 
         FROM $table l 
         LEFT JOIN {$wpdb->posts} p ON l.product_id = p.ID 
         ORDER BY l.created_at DESC 
         LIMIT %d OFFSET %d",
        $per_page, $offset
    ));

    $total_pages = ceil($total_items / $per_page);

    // Lấy dữ liệu để edit
    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $edit_data = $wpdb->get_row("SELECT * FROM $table WHERE id = " . intval($_GET['edit_id']));
    }

    ?>
    <div class="wrap">
        <h1>Quản lý lô sản phẩm</h1>
        
        <div class="gpt-admin-flex-layout">
            <!-- Form Section -->
            <div class="form-section">
                <h3><?= $edit_data ? 'Chỉnh sửa lô sản phẩm' : 'Thêm lô sản phẩm mới' ?></h3>
                <hr>
                <form method="post">
                    <div class="form-group">
                        <label for="lot_name">Tên lô: <span style="color: red;">*</span></label>
                        <input type="text" name="lot_name" id="lot_name" placeholder="Nhập tên lô (phải là duy nhất)" class="regular-text" required value="<?= esc_attr($edit_data->lot_name ?? '') ?>">
                        <p class="description">Tên lô phải là duy nhất trong hệ thống.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_id">Sản phẩm:</label>
                        <select name="product_id" id="product_id" class="gpt-select2" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php
                            $products = get_posts([
                                'post_type' => 'product',
                                'numberposts' => -1,
                                'post_status' => 'publish'
                            ]);
                            
                            foreach ($products as $product) {
                                $custom_prod_id = get_post_meta($product->ID, 'custom_prod_id', true);
                                $option_value = !empty($custom_prod_id) ? $custom_prod_id : $product->ID;
                                $selected = (isset($edit_data) && $edit_data->product_id == $option_value) ? 'selected' : '';
                                echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($product->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_lot_id" value="<?= $edit_data->id ?>">
                        <button type="submit" class="button button-primary primary">Lưu thay đổi</button>
                        <a href="?page=gpt-lot-manager" class="button">Huỷ</a>
                    <?php else: ?>
                        <button type="submit" name="add_lot" class="button button-primary primary" style="width: 100%;">Lưu thông tin</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Section -->
            <div class="gpt-table-container">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên lô</th>
                            <th>Sản phẩm</th>
                            <!-- <th>Ngày tạo</th> -->
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($lots): ?>
                            <?php foreach ($lots as $lot): ?>
                                <tr>
                                    <td><?= esc_html($lot->id) ?></td>
                                    <td><?= esc_html($lot->lot_name) ?></td>
                                    <td><?= esc_html(get_product_name_by_custom_id($lot->product_id)) ?></td>
                                    <!-- <td><?= esc_html(date('d/m/Y H:i', strtotime($lot->created_at))) ?></td> -->
                                    <td class="btn-actions">
                                        <a href="?page=gpt-lot-manager&edit_id=<?= $lot->id ?>" class="button">Sửa</a>
                                        <a href="?page=gpt-lot-manager&delete_id=<?= $lot->id ?>" class="button button-danger" onclick="return confirm('Bạn chắc chắn muốn xoá lô này?')">Xoá</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #999; padding: 40px;">Chưa có dữ liệu lô sản phẩm</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php
                // Hiển thị phân trang
                if ($total_pages > 1) {
                    echo '<div class="gpt-pagination">';
                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => '«',
                        'next_text' => '»',
                        'total'     => $total_pages,
                        'current'   => $paged
                    ]);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

function get_product_name_by_custom_id($custom_prod_id) {
    if (empty($custom_prod_id)) {
        return false;
    }

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
        echo $products[0]->post_title;
    } else {
        echo $custom_prod_id;
    }
}