<?php

function gpt_location_warnings_page() {
    global $wpdb;
    $warning_table = BIZGPT_PLUGIN_WP_LOCATION_WARNINGS;
    
    if (isset($_POST['action']) && isset($_POST['warning_id'])) {
        $warning_id = intval($_POST['warning_id']);
        
        switch ($_POST['action']) {
            case 'mark_processed':
                $wpdb->update(
                    $warning_table,
                    array('status' => 'da_xu_ly'),
                    array('id' => $warning_id),
                    array('%s'),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>Đã đánh dấu xử lý thành công!</p></div>';
                break;
                
            case 'add_note':
                if (!empty($_POST['note'])) {
                    $wpdb->update(
                        $warning_table,
                        array('note' => sanitize_textarea_field($_POST['note'])),
                        array('id' => $warning_id),
                        array('%s'),
                        array('%d')
                    );
                    echo '<div class="notice notice-success"><p>Đã thêm ghi chú thành công!</p></div>';
                }
                break;
                
            case 'delete':
                $wpdb->delete(
                    $warning_table,
                    array('id' => $warning_id),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>Đã xóa cảnh báo thành công!</p></div>';
                break;
        }
    }
    
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $where_conditions = array();
    $where_values = array();
    
    if ($status_filter !== 'all') {
        $where_conditions[] = "status = %s";
        $where_values[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(barcode LIKE %s OR customer_name LIKE %s OR phone_number LIKE %s)";
        $where_values[] = '%' . $search . '%';
        $where_values[] = '%' . $search . '%';
        $where_values[] = '%' . $search . '%';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $total_query = "SELECT COUNT(*) FROM $warning_table" . $where_clause;
    if (!empty($where_values)) {
        $total_query = $wpdb->prepare($total_query, ...$where_values);
    }
    $total_items = $wpdb->get_var($total_query);
    
    $data_query = "SELECT * FROM $warning_table" . $where_clause . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $query_values = array_merge($where_values, array($per_page, $offset));
    $warnings = $wpdb->get_results($wpdb->prepare($data_query, ...$query_values));
    
    $provinces = [
        'AG' => 'An Giang',
        'BN' => 'Bắc Ninh',
        'CM' => 'Cà Mau',
        'CB' => 'Cao Bằng',
        'DL' => 'Đắk Lắk',
        'DB' => 'Điện Biên',
        'DG' => 'Đồng Nai',
        'DT' => 'Đồng Tháp',
        'GL' => 'Gia Lai',
        'HT' => 'Hà Tĩnh',
        'HY' => 'Hưng Yên',
        'KH' => 'Khánh Hoà',
        'LC' => 'Lai Châu',
        'LD' => 'Lâm Đồng',
        'LS' => 'Lạng Sơn',
        'LA' => 'Lào Cai',
        'NA' => 'Nghệ An',
        'NB' => 'Ninh Bình',
        'PT' => 'Phú Thọ',
        'QG' => 'Quảng Ngãi',
        'QN' => 'Quảng Ninh',
        'QT' => 'Quảng Trị',
        'SL' => 'Sơn La',
        'TN' => 'Tây Ninh',
        'TG' => 'Thái Nguyên',
        'TH' => 'Thanh Hóa',
        'CT' => 'TP. Cần Thơ',
        'DN' => 'TP. Đà Nẵng',
        'HN' => 'TP. Hà Nội',
        'HP' => 'TP. Hải Phòng',
        'SG' => 'TP. Hồ Chí Minh',
        'HUE' => 'TP. Huế',
        'TQ' => 'Tuyên Quang',
        'VL' => 'Vĩnh Long'
    ];
    
    $total_pages = ceil($total_items / $per_page);
    ?>
    
    <div class="bg_wrap">
        <h1 class="wp-heading-inline">Cảnh báo vị trí tích điểm</h1>
        <div class="ux-row" style="margin-bottom: 20px;">
            <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
                <input type="hidden" name="page" value="gpt-warning-list">
                <div class="col large-1">
                    <label>Trạng thái:</label>
                    <select name="status" onchange="this.form.submit();">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>Tất cả trạng thái</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Chưa xử lý</option>
                        <option value="da_xu_ly" <?php selected($status_filter, 'da_xu_ly'); ?>>Đã xử lý</option>
                    </select>
                </div>
                <div class="col large-1">
                    <label for="warning-search-input">Tìm kiếm cảnh báo:</label>
                    <input type="search" id="warning-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Tìm theo mã cào, tên, số điện thoại...">
                </div>
                <div class="col large-1">
                    <input type="submit" id="search-submit" style="min-height: 40px; border-radius: 6px; width: max-content;" class="button button-primary" value="Tìm kiếm">
                </div>
            </form>
        </div>
        <!-- Thống kê -->
        <div class="notice notice-success" style="padding: 10px; margin: 0px;">
            <p><strong>Thống kê:</strong> 
                Tổng cộng: <?php echo $total_items; ?> cảnh báo | 
                Chưa xử lý: <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $warning_table WHERE status = 'pending'"); ?> | 
                Đã xử lý: <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $warning_table WHERE status = 'da_xu_ly'"); ?>
            </p>
        </div>
        
        <!-- Bảng dữ liệu -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 80px;">ID</th>
                    <th scope="col">Mã cào</th>
                    <th scope="col">Khách hàng</th>
                    <th scope="col">SĐT</th>
                    <th scope="col">Tỉnh mong đợi</th>
                    <th scope="col">Tỉnh thực tế</th>
                    <th scope="col">Sản phẩm</th>
                    <th scope="col">Cửa hàng</th>
                    <th scope="col">Thời gian</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warnings)): ?>
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px;">
                        <em>Không có cảnh báo nào.</em>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($warnings as $warning): ?>
                <tr id="warning-<?php echo $warning->id; ?>" class="<?php echo $warning->status === 'pending' ? 'alternate' : ''; ?>">
                    <td><strong><?php echo $warning->id; ?></strong></td>
                    <td><code><?php echo esc_html($warning->barcode); ?></code></td>
                    <td><?php echo esc_html($warning->customer_name); ?></td>
                    <td><?php echo esc_html($warning->phone_number); ?></td>
                    <td>
                        <span class="dashicons dashicons-location" style="color: #dc3232;"></span>
                        <?php echo isset($provinces[$warning->province_expect]) ? $provinces[$warning->province_expect] : $warning->province_expect; ?>
                        <small>(<?php echo $warning->province_expect; ?>)</small>
                    </td>
                    <td>
                        <span class="dashicons dashicons-location-alt" style="color: #0073aa;"></span>
                        <?php echo isset($provinces[$warning->province_actual]) ? $provinces[$warning->province_actual] : $warning->province_actual; ?>
                        <small>(<?php echo $warning->province_actual; ?>)</small>
                    </td>
                    <td><?php echo esc_html($warning->product); ?></td>
                    <td><?php echo esc_html($warning->store); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($warning->created_at)); ?></td>
                    <td>
                        <?php if ($warning->status === 'pending'): ?>
                            <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                            <span style="color: #dc3232;">Chưa xử lý</span>
                        <?php else: ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <span style="color: #46b450;">Đã xử lý</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="button button-small button-primary" onclick="toggleDetails(<?php echo $warning->id; ?>)">
                            Chi tiết
                        </button>
                    </td>
                </tr>
                
                <!-- Row chi tiết (ẩn mặc định) -->
                <tr id="details-<?php echo $warning->id; ?>" style="display: none; background-color: #f9f9f9;">
                    <td colspan="11" style="padding: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div style="text-align: left;">
                                <h4>Thông tin chi tiết:</h4>
                                <p><strong>Địa chỉ đầy đủ:</strong><br><?php echo esc_html($warning->full_address); ?></p>
                                <p><strong>Ghi chú:</strong><br><?php echo esc_html($warning->note ?: 'Chưa có ghi chú'); ?></p>
                            </div>
                            <div style="text-align: left;">
                                <h4>Thao tác:</h4>
                                <?php if ($warning->status === 'pending'): ?>
                                <form method="post" style="margin-bottom: 10px;">
                                    <input type="hidden" name="warning_id" value="<?php echo $warning->id; ?>">
                                    <input type="hidden" name="action" value="mark_processed">
                                    <button type="submit" class="button button-primary" onclick="return confirm('Xác nhận đánh dấu đã xử lý?')">
                                        Đánh dấu đã xử lý
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="post" style="margin-bottom: 10px;">
                                    <input type="hidden" name="warning_id" value="<?php echo $warning->id; ?>">
                                    <input type="hidden" name="action" value="add_note">
                                    <textarea name="note" placeholder="Thêm ghi chú..." style="width: 100%; height: 60px;"><?php echo esc_textarea($warning->note); ?></textarea>
                                    <button type="submit" class="button button-edit">Cập nhật ghi chú</button>
                                </form>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="warning_id" value="<?php echo $warning->id; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="button button-link-delete button-danger" onclick="return confirm('Xác nhận xóa cảnh báo này?')">
                                        Xóa cảnh báo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="alignleft actions">
                <span class="displaying-num"><?php echo $total_items; ?> mục</span>
            </div>
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    $base_url = admin_url('admin.php?page=gpt-warning-list');
                    if (!empty($status_filter) && $status_filter !== 'all') {
                        $base_url .= '&status=' . $status_filter;
                    }
                    if (!empty($search)) {
                        $base_url .= '&search=' . urlencode($search);
                    }
                    
                    if ($current_page > 1): ?>
                        <a class="button" href="<?php echo $base_url; ?>&paged=<?php echo $current_page - 1; ?>">‹ Trước</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text">Trang hiện tại</label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> trong <span class="total-pages"><?php echo $total_pages; ?></span></span>
                    </span>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a class="button" href="<?php echo $base_url; ?>&paged=<?php echo $current_page + 1; ?>">Sau ›</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function toggleDetails(warningId) {
        var detailsRow = document.getElementById('details-' + warningId);
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            detailsRow.style.display = 'table-row';
        } else {
            detailsRow.style.display = 'none';
        }
    }
    
    // Auto submit form khi thay đổi page number
    document.getElementById('current-page-selector').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            var currentPage = parseInt(this.value);
            var totalPages = parseInt(document.querySelector('.total-pages').textContent);
            if (currentPage >= 1 && currentPage <= totalPages) {
                var url = new URL(window.location);
                url.searchParams.set('paged', currentPage);
                window.location.href = url.toString();
            }
        }
    });
    </script>
    
    <style>
    .wp-list-table th, .wp-list-table td {
        padding: 8px 10px;
    }
    .wp-list-table code {
        background: #f1f1f1;
        padding: 2px 6px;
        border-radius: 3px;
        font-weight: bold;
    }
    .alternate {
        background-color: #fff5f5;
    }
    .search-form {
        background: #f1f1f1;
        padding: 10px;
        border-radius: 3px;
    }
    </style>
    
    <?php
}

// Tạo bảng cảnh báo khi activate plugin
function create_location_warning_table_on_activation() {
    create_location_warning_table();
}
register_activation_hook(__FILE__, 'create_location_warning_table_on_activation');

