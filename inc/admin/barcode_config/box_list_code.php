<?php

add_action('wp_ajax_gpt_delete_box_barcode', 'gpt_delete_box_barcode');
add_action('admin_post_gpt_export_box_barcode_excel', 'gpt_export_box_barcode_with_image');
add_action('admin_post_nopriv_gpt_export_barcode_excel', 'gpt_export_box_barcode_with_image');

require_once plugin_dir_path(__FILE__) . '../../libs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function gpt_delete_box_barcode() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        wp_send_json(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    }

    $ids = array_map('intval', $_POST['ids']);
    $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $sql = "DELETE FROM $table WHERE id IN ($id_placeholders)";
    $query = $wpdb->prepare($sql, ...$ids);
    $deleted = $wpdb->query($query);

    if ($deleted !== false) {
        wp_send_json(['status' => 'success']);
    } else {
        wp_send_json(['status' => 'error', 'message' => 'Không thể xóa dữ liệu.']);
    }
}

function gpt_export_box_barcode_with_image() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    // Xây dựng điều kiện WHERE dựa trên các filter được gửi
    $where_conditions = ["status = 'unused'"];
    $params = [];

    // Kiểm tra các filter từ POST data
    if (!empty($_POST['export_session'])) {
        $where_conditions[] = "session = %s";
        $params[] = sanitize_text_field($_POST['export_session']);
    }

    if (!empty($_POST['export_search_barcode'])) {
        $where_conditions[] = "barcode LIKE %s";
        $params[] = '%' . $wpdb->esc_like(sanitize_text_field($_POST['export_search_barcode'])) . '%';
    }

    if (!empty($_POST['export_status'])) {
        // Ghi đè điều kiện status mặc định
        $where_conditions[0] = "status = %s";
        $params = array_merge([sanitize_text_field($_POST['export_status'])], array_slice($params, 0));
    }

    if (!empty($_POST['export_from_date']) && !empty($_POST['export_to_date'])) {
        $where_conditions[] = "DATE(created_at) BETWEEN %s AND %s";
        $params[] = sanitize_text_field($_POST['export_from_date']);
        $params[] = sanitize_text_field($_POST['export_to_date']);
    }

    // Tạo query
    $where_clause = implode(' AND ', $where_conditions);
    $query = "SELECT * FROM $table WHERE $where_clause ORDER BY id DESC";

    if (!empty($params)) {
        $rows = $wpdb->get_results($wpdb->prepare($query, ...$params));
    } else {
        $rows = $wpdb->get_results($query);
    }

    if (empty($rows)) {
        wp_die('Không có dữ liệu để xuất với các điều kiện đã chọn.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $headers = ['Mã định danh', "Token - Tráng bạc", 'Điểm', 'Trạng thái', 'Phiên', 'Ngày tạo', 'Link QR', 'Link Barcode'];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->setCellValue("A$rowIndex", $row->barcode);
        $sheet->setCellValue("B$rowIndex", $row->token ?? '');
        $sheet->setCellValue("C$rowIndex", $row->point ?? '');
        $sheet->setCellValue("D$rowIndex", $row->status);
        $sheet->setCellValue("E$rowIndex", $row->session);
        $sheet->setCellValue("F$rowIndex", $row->created_at);
        $sheet->setCellValue("G$rowIndex", $row->qr_code_url);
        $sheet->setCellValue("H$rowIndex", $row->barcode_url);
        $rowIndex++;
    }

    // Tạo tên file với thông tin filter
    $filename = 'box_barcode_export';
    if (!empty($_POST['export_session'])) {
        $filename .= '_session_' . sanitize_file_name($_POST['export_session']);
    }
    if (!empty($_POST['export_status'])) {
        $filename .= '_' . sanitize_file_name($_POST['export_status']);
    }
    $filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Header Excel để xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function gpt_box_barcode_list_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    // Xây dựng điều kiện WHERE
    $where_conditions = [];
    $search_params = [];
    
    // Thêm search theo barcode
    if (!empty($_GET['search_barcode'])) {
        $search_barcode = sanitize_text_field($_GET['search_barcode']);
        $where_conditions[] = "barcode LIKE %s";
        $search_params[] = '%' . $wpdb->esc_like($search_barcode) . '%';
    }
    
    if (!empty($_GET['status'])) {
        $where_conditions[] = "status = %s";
        $search_params[] = $_GET['status'];
    }

    if (!empty($_GET['session'])) {
        $where_conditions[] = "session = %s";
        $search_params[] = $_GET['session'];
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $where_conditions[] = "DATE(created_at) BETWEEN %s AND %s";
        $search_params[] = $_GET['from_date'];
        $search_params[] = $_GET['to_date'];
    }

    // Tạo WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Xây dựng query với parameters
    $query = "SELECT * FROM $table $where_clause ORDER BY id DESC LIMIT %d OFFSET %d";
    $count_query = "SELECT COUNT(*) FROM $table $where_clause";
    
    // Thêm LIMIT và OFFSET vào parameters
    $all_params = array_merge($search_params, [$per_page, $offset]);
    
    if (!empty($search_params)) {
        $results = $wpdb->get_results($wpdb->prepare($query, ...$all_params));
        $total = $wpdb->get_var($wpdb->prepare($count_query, ...$search_params));
    } else {
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        $total = $wpdb->get_var($count_query);
    }
    
    $total_pages = ceil($total / $per_page);

    // Lấy danh sách sessions cho dropdown
    $sessions = $wpdb->get_col("SELECT DISTINCT session FROM $table ORDER BY session DESC");

    ?>
    <h1>Danh sách mã định danh thùng hàng</h1>
    
    <!-- Search Box nổi bật -->
    <div class="search-box" style="background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
        <form method="get">
            <input type="hidden" name="page" value="gpt-config-barcode">
            <input type="hidden" name="tab" value="box_barcode">
            
            <!-- Preserve other filters -->
            <?php if (!empty($_GET['status'])): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($_GET['status']); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['product_id'])): ?>
                <input type="hidden" name="product_id" value="<?php echo esc_attr($_GET['product_id']); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['session'])): ?>
                <input type="hidden" name="session" value="<?php echo esc_attr($_GET['session']); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['from_date'])): ?>
                <input type="hidden" name="from_date" value="<?php echo esc_attr($_GET['from_date']); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['to_date'])): ?>
                <input type="hidden" name="to_date" value="<?php echo esc_attr($_GET['to_date']); ?>">
            <?php endif; ?>
            
            <label for="search_barcode" style="font-weight: bold; color: #0073aa; display: block; margin-bottom: 12px;">Tìm mã định danh thùng hàng:</label>
            <input type="text" 
                   id="search_barcode" 
                   name="search_barcode" 
                   value="<?php echo esc_attr($_GET['search_barcode'] ?? ''); ?>" 
                   placeholder="Nhập mã barcode cần tìm..."
                   style="flex: 1; max-width: 300px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="button button-primary">Tìm kiếm</button>
            
            <?php if (!empty($_GET['search_barcode'])): ?>
                <a href="<?php echo admin_url('admin.php?page=gpt-config-barcode&tab=box_barcode'); ?>" class="button">Xóa tìm kiếm</a>
            <?php endif; ?>
        </form>
        
        <?php if (!empty($_GET['search_barcode'])): ?>
            <div style="margin-top: 10px; padding: 8px 12px; background: #fff; border-radius: 4px; font-size: 14px;">
                <strong>Đang tìm kiếm:</strong> "<?php echo esc_html($_GET['search_barcode']); ?>" 
                <em>(<?php echo $total; ?> kết quả)</em>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Advanced Filters -->
    <div class="ux-row" style="margin-bottom: 20px;">
        <div class="filter-toggle" style="margin-bottom: 15px;">
            <button type="button" id="toggle-filters" class="button" style="font-size: 14px; color: #fff;">
                ⚙️ <?php echo (!empty($_GET['status']) || !empty($_GET['product_id']) || !empty($_GET['session']) || !empty($_GET['from_date'])) ? 'Ẩn bộ lọc nâng cao' : 'Hiện bộ lọc nâng cao'; ?>
            </button>
        </div>
        
        <form method="get" class="row form-row advanced-filters" 
              style="align-items: flex-end; width: 100%; <?php echo (!empty($_GET['status']) || !empty($_GET['product_id']) || !empty($_GET['session']) || !empty($_GET['from_date'])) ? '' : 'display: none;'; ?>">
            <input type="hidden" name="page" value="gpt-config-barcode">
            <input type="hidden" name="tab" value="box_barcode">
            
            <!-- Preserve search -->
            <?php if (!empty($_GET['search_barcode'])): ?>
                <input type="hidden" name="search_barcode" value="<?php echo esc_attr($_GET['search_barcode']); ?>">
            <?php endif; ?>
            
            <!-- Trạng thái -->
            <div class="col large-2">
                <label>Trạng thái:</label>
                <select name="status" class="gpt-select2">
                    <option value="">Tất cả</option>
                    <option value="pending" <?php selected($_GET['status'] ?? '', 'pending'); ?>>Chờ duyệt</option>
                    <option value="unused" <?php selected($_GET['status'] ?? '', 'unused'); ?>>Chưa sử dụng</option>
                    <option value="used" <?php selected($_GET['status'] ?? '', 'used'); ?>>Đã sử dụng</option>
                </select>
            </div>
            
            <!-- Phiên -->
            <div class="col large-2">
                <label>Phiên:</label>
                <select name="session" class="gpt-select2">
                    <option value="">Tất cả</option>
                    <?php
                    foreach ($sessions as $session) {
                        $selected = ($_GET['session'] ?? '') == $session ? 'selected' : '';
                        echo "<option value='{$session}' $selected>{$session}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Khoảng ngày -->
            <div class="col large-2">
                <label>Từ ngày:</label>
                <input type="date" name="from_date" value="<?php echo esc_attr($_GET['from_date'] ?? ''); ?>" class="regular-text">
            </div>
            <div class="col large-2">
                <label>Đến ngày:</label>
                <input type="date" name="to_date" value="<?php echo esc_attr($_GET['to_date'] ?? ''); ?>" class="regular-text">
            </div>

            <div class="col large-1">
                <button type="submit" class="button button-primary">Lọc</button>
            </div>
        </form>
    </div>
    
    <!-- Export Form với Modal -->
    <div id="export-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">📥 Xuất File Excel</h3>
                <span id="close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="export-form">
                <input type="hidden" name="action" value="gpt_export_box_barcode_excel">
                
                <!-- Chọn phiên -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Chọn phiên:</label>
                    <select name="export_session" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">🔄 Tất cả phiên</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo esc_attr($session); ?>" <?php selected($_GET['session'] ?? '', $session); ?>>
                                📦 <?php echo esc_html($session); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Chọn trạng thái -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Chọn trạng thái:</label>
                    <select name="export_status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">🔄 Tất cả trạng thái</option>
                        <option value="pending" <?php selected($_GET['status'] ?? '', 'pending'); ?>>⏳ Chờ duyệt</option>
                        <option value="unused" <?php selected($_GET['status'] ?? '', 'unused'); ?>>✅ Chưa sử dụng</option>
                        <option value="used" <?php selected($_GET['status'] ?? '', 'used'); ?>>❌ Đã sử dụng</option>
                    </select>
                </div>
                
                <!-- Tìm kiếm barcode -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tìm kiếm mã barcode:</label>
                    <input type="text" name="export_search_barcode" value="<?php echo esc_attr($_GET['search_barcode'] ?? ''); ?>" 
                           placeholder="Để trống để xuất tất cả" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <!-- Khoảng thời gian -->
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Từ ngày:</label>
                        <input type="date" name="export_from_date" value="<?php echo esc_attr($_GET['from_date'] ?? ''); ?>" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Đến ngày:</label>
                        <input type="date" name="export_to_date" value="<?php echo esc_attr($_GET['to_date'] ?? ''); ?>" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" id="cancel-export" class="button" style="margin-right: 10px;">Hủy</button>
                    <button type="submit" class="button button-primary">📥 Xuất Excel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center;">
        <button type="button" id="show-export-modal" class="button button-primary">📥 Xuất File Excel</button>
        
        <button type="button" id="gpt_delete_selected" class="button button-danger alert">
            🗑️ Xóa các mã đã chọn
            <span id="gpt_delete_loading" style="display:none;">Đang xóa...</span>
        </button>
        
        <div style="margin-left: auto; color: #000; font-size: 14px;">
            <strong>Tổng: <?php echo number_format($total); ?> mã định danh thùng</strong>
        </div>
    </div>
    
    <div id="gpt-export-loading" style="display:none; margin-top:10px;">
        <div class="spinner is-active" style="margin-bottom: 10px;"></div>
        <strong>Đang xử lý và tạo file Excel, vui lòng chờ...</strong>
    </div>
    
    <div class="table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="gpt_barcode_table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="gpt_check_all"></th>
                    <th>Mã định danh</th>
                    <th>Phiên</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Link QR</th>
                    <th>Bar code</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><input type="checkbox" class="gpt_check_item" value="<?php echo $row->id; ?>"></td>
                        <td>
                            <?php 
                            $barcode = $row->barcode;
                            $search_term = $_GET['search_barcode'] ?? '';
                            
                            // Highlight search term
                            if (!empty($search_term)) {
                                $barcode = str_ireplace($search_term, '<mark style="background: yellow; padding: 2px;">' . $search_term . '</mark>', $barcode);
                            }
                            echo $barcode;
                            ?>
                        </td>
                        <td>
                            <span style="background: #e8f4fd; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                <?php echo esc_html($row->session); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row->status == 'unused') : ?>
                                <span class="gpt-badge gpt-badge-success">Chưa sử dụng</span>
                            <?php elseif($row->status == 'pending') : ?>
                                <span class="gpt-badge gpt-badge-warning">Đang chờ duyệt</span>
                            <?php else: ?>
                                <span class="gpt-badge gpt-badge-danger">Đã sử dụng</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row->created_at)); ?></td>
                        <td><img src="<?php echo esc_url($row->qr_code_url); ?>" alt="QR mã Box" style="height:100px; border: 1px solid #ddd; border-radius: 4px;"></td>
                        <td><img src="<?php echo esc_url($row->barcode_url); ?>" alt="Barcode mã Box" style="height:80px; border: 1px solid #ddd; border-radius: 4px;"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align:center; font-style: italic; padding: 20px;">
                            <?php 
                            if (!empty($_GET['search_barcode'])) {
                                echo '🔍 Không tìm thấy mã Box barcode "' . esc_html($_GET['search_barcode']) . '"';
                            } else {
                                echo '📦 Không có dữ liệu Box barcode phù hợp với bộ lọc.';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            
            // Build pagination URL with all current parameters
            $base_url = admin_url('admin.php?page=gpt-config-barcode&tab=box_barcode');
            $url_params = [];
            foreach (['search_barcode', 'status', 'product_id', 'session', 'from_date', 'to_date'] as $param) {
                if (!empty($_GET[$param])) {
                    $url_params[] = $param . '=' . urlencode($_GET[$param]);
                }
            }
            if (!empty($url_params)) {
                $base_url .= '&' . implode('&', $url_params);
            }
            
            echo paginate_links([
                'base' => $base_url . '%_%',
                'format' => '&paged=%#%',
                'current' => $paged,
                'total' => $total_pages
            ]);
            echo '</div></div>';
        }
    ?>
    
    <script>
        jQuery(document).ready(function($) {
            // Export Modal functionality
            $('#show-export-modal').on('click', function() {
                $('#export-modal').show();
            });
            
            $('#close-modal, #cancel-export').on('click', function() {
                $('#export-modal').hide();
            });
            
            // Close modal when clicking outside
            $('#export-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Export form submission
            $('#export-form').on('submit', function() {
                $('#export-modal').hide();
                $('#gpt-export-loading').show();
                setTimeout(function() {
                    $('#gpt-export-loading').hide();
                }, 2000);
            });
            
            // Toggle advanced filters
            $('#toggle-filters').on('click', function() {
                $('.advanced-filters').toggle();
                let isVisible = $('.advanced-filters').is(':visible');
                $(this).html(isVisible ? '⚙️ Ẩn bộ lọc nâng cao' : '⚙️ Hiện bộ lọc nâng cao');
            });
            
            // Auto-focus search input
            $('#search_barcode').focus();
            
            // Check all functionality
            $('#gpt_check_all').on('change', function() {
                $('.gpt_check_item').prop('checked', $(this).is(':checked'));
            });

            // Delete selected functionality
            $('#gpt_delete_selected').on('click', function() {
                let selected = [];
                $('.gpt_check_item:checked').each(function() {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert('Vui lòng chọn ít nhất 1 mã Box để xóa.');
                    return;
                }

                if (!confirm('Bạn có chắc chắn muốn xóa ' + selected.length + ' mã Box đã chọn?')) {
                    return;
                }

                $('#gpt_delete_loading').show();
                $('#gpt_delete_selected').prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'gpt_delete_box_barcode',
                    ids: selected
                }, function(response) {
                    $('#gpt_delete_loading').hide();
                    $('#gpt_delete_selected').prop('disabled', false);

                    if (response.status === 'success') {
                        alert('Đã xóa thành công ' + selected.length + ' mã Box!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                }).fail(function() {
                    $('#gpt_delete_loading').hide();
                    $('#gpt_delete_selected').prop('disabled', false);
                    alert('Lỗi kết nối server.');
                });
            });
            
            // Enter key for search
            $('#search_barcode').on('keypress', function(e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        });
    </script>
<?php
}