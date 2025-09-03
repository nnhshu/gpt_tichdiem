<?php

add_action('wp_ajax_gpt_delete_barcode', 'gpt_delete_barcode');
add_action('wp_ajax_gpt_delete_all_barcode', 'gpt_delete_all_barcode');
add_action('admin_post_gpt_export_barcode_excel', 'gpt_export_barcode_with_image');
add_action('admin_post_nopriv_gpt_export_barcode_excel', 'gpt_export_barcode_with_image');

require_once plugin_dir_path(__FILE__) . '../../libs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function gpt_delete_all_barcode() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    // Xoá toàn bộ dữ liệu
    $deleted = $wpdb->query("DELETE FROM $table");

    if ($deleted !== false) {
        wp_send_json(['status' => 'success', 'message' => 'Đã xoá toàn bộ dữ liệu.']);
    } else {
        wp_send_json(['status' => 'error', 'message' => 'Không thể xóa dữ liệu.']);
    }
}


function gpt_export_barcode_with_image() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    // Xây dựng điều kiện WHERE dựa trên các tham số
    $where_conditions = ["status = 'unused'"]; // Mặc định chỉ xuất unused
    $where_params = [];
    
    // Lọc theo sản phẩm
    if (!empty($_POST['export_product_id'])) {
        $where_conditions[] = "product_id = %s";
        $where_params[] = sanitize_text_field($_POST['export_product_id']);
    }
    
    // Lọc theo phiên
    if (!empty($_POST['export_session'])) {
        $where_conditions[] = "session = %s";
        $where_params[] = sanitize_text_field($_POST['export_session']);
    }
    
    // Tạo câu query
    $where_clause = implode(' AND ', $where_conditions);
    $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY id DESC";
    
    if (!empty($where_params)) {
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$where_params));
    } else {
        $rows = $wpdb->get_results($sql);
    }

    if (empty($rows)) {
        wp_die('Không có dữ liệu để xuất với các bộ lọc đã chọn.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Thiết lập tên sheet
    $sheet_name = 'Ma_Cao_Export';
    if (!empty($_POST['export_product_id'])) {
        $product_name = '';
        $products = wc_get_products(['limit' => -1]);
        foreach ($products as $product) {
            $custom_product_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
            if ($custom_product_id == $_POST['export_product_id']) {
                $product_name = sanitize_title($product->get_name());
                break;
            }
        }
        if ($product_name) {
            $sheet_name = substr($product_name, 0, 20);
        }
    }
    $sheet->setTitle($sheet_name);

    // Header với style
    $headers = [
        'STT',
        'Mã định danh', 
        'Token - Trắng bạc', 
        'Điểm', 
        'Trạng thái', 
        'Sản phẩm',
        'Phiên',
        'Ngày tạo', 
        'Link QR', 
        'Link Barcode'
    ];
    
    $sheet->fromArray($headers, NULL, 'A1');
    
    // Style cho header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // Thiết lập độ rộng cột
    $sheet->getColumnDimension('A')->setWidth(5);   // STT
    $sheet->getColumnDimension('B')->setWidth(20);  // Mã định danh
    $sheet->getColumnDimension('C')->setWidth(25);  // Token
    $sheet->getColumnDimension('D')->setWidth(8);   // Điểm
    $sheet->getColumnDimension('E')->setWidth(15);  // Trạng thái
    $sheet->getColumnDimension('F')->setWidth(25);  // Sản phẩm
    $sheet->getColumnDimension('G')->setWidth(10);  // Phiên
    $sheet->getColumnDimension('H')->setWidth(18);  // Ngày tạo
    $sheet->getColumnDimension('I')->setWidth(40);  // Link QR
    $sheet->getColumnDimension('J')->setWidth(40);  // Link Barcode

    // Thêm dữ liệu
    $rowIndex = 2;
    $stt = 1;
    
    foreach ($rows as $row) {
        // Tìm tên sản phẩm
        $product_name = '';
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
            $product_name = $products[0]->post_title;
        } else {
            $product_name = $custom_prod_id;
        }
        
        // Chuyển đổi trạng thái
        $status_text = '';
        switch($row->status) {
            case 'unused': $status_text = 'Chưa sử dụng'; break;
            case 'used': $status_text = 'Đã sử dụng'; break;
            case 'pending': $status_text = 'Chờ duyệt'; break;
            default: $status_text = $row->status;
        }
        
        $sheet->setCellValue("A$rowIndex", $stt);
        $sheet->setCellValue("B$rowIndex", $row->barcode);
        $sheet->setCellValue("C$rowIndex", $row->token);
        $sheet->setCellValue("D$rowIndex", $row->point);
        $sheet->setCellValue("E$rowIndex", $status_text);
        $sheet->setCellValue("F$rowIndex", $product_name);
        $sheet->setCellValue("G$rowIndex", $row->session);
        $sheet->setCellValue("H$rowIndex", $row->created_at);
        $sheet->setCellValue("I$rowIndex", $row->qr_code_url);
        $sheet->setCellValue("J$rowIndex", $row->barcode_url);
        
        // Style cho dòng dữ liệu
        $dataStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ];
        
        // Màu nền xen kẽ
        if ($rowIndex % 2 == 0) {
            $dataStyle['fill'] = [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA']
            ];
        }
        
        $sheet->getStyle("A$rowIndex:J$rowIndex")->applyFromArray($dataStyle);
        
        $rowIndex++;
        $stt++;
    }

    // Tạo tên file
    $filename = 'ma_cao_export_' . date('Y-m-d_H-i-s');
    
    // Thêm thông tin bộ lọc vào tên file
    $filter_parts = [];
    if (!empty($_POST['export_product_id'])) {
        $filter_parts[] = 'product_' . $_POST['export_product_id'];
    }
    if (!empty($_POST['export_session'])) {
        $filter_parts[] = 'session_' . $_POST['export_session'];
    }
    
    if (!empty($filter_parts)) {
        $filename .= '_' . implode('_', $filter_parts);
    }
    
    $filename .= '.xlsx';

    // Header Excel để xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Hàm hỗ trợ tải ảnh từ URL về tạm để nhúng
function download_image_temp($url) {
    $temp_file = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($temp_file, file_get_contents($url));
    return $temp_file;
}

function gpt_barcode_list_page() {
    if (isset($_POST['gpt_export_excel'])) {
        gpt_export_barcode_excel();
        exit;
    }

    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    $where = '1=1';
    $search_params = [];
    
    // Thêm search theo barcode
    if (!empty($_GET['search_barcode'])) {
        $search_barcode = sanitize_text_field($_GET['search_barcode']);
        $where .= " AND barcode LIKE %s";
        $search_params[] = '%' . $wpdb->esc_like($search_barcode) . '%';
    }
    
    if (!empty($_GET['status'])) {
        $where .= " AND status = %s";
        $search_params[] = $_GET['status'];
    }
    if (!empty($_GET['product_id'])) {
        $where .= " AND product_id = %s";
        $search_params[] = $_GET['product_id'];
    }
    if (!empty($_GET['session'])) {
        $where .= " AND session = %s";
        $search_params[] = $_GET['session'];
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $where .= " AND DATE(created_at) BETWEEN %s AND %s";
        $search_params[] = $_GET['from_date'];
        $search_params[] = $_GET['to_date'];
    }

    // Xây dựng query với parameters
    $query = "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
    $count_query = "SELECT COUNT(*) FROM $table WHERE $where";
    
    $all_params = array_merge($search_params, [$per_page, $offset]);
    
    if (!empty($search_params)) {
        $results = $wpdb->get_results($wpdb->prepare($query, ...$all_params));
        $total = $wpdb->get_var($wpdb->prepare($count_query, ...$search_params));
    } else {
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        $total = $wpdb->get_var($count_query);
    }
    
    $total_pages = ceil($total / $per_page);

    ?>
    <style>
        .ux-row{
            align-items: flex-end;
        }
    </style>
    <h1>Danh sách mã định danh</h1>
    <div class="excel-export-section" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #495057; border-bottom: 2px solid #28a745; padding-bottom: 10px;">
            📥 Xuất File Excel
        </h3>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="gpt-excel-export-form">
            <input type="hidden" name="action" value="gpt_export_barcode_excel">
            
            <div class="export-filters row form-row ux-row">
                <div class="col large-2">
                    <label for="export_product_id">
                        Chọn sản phẩm:
                    </label>
                    <select name="export_product_id" id="export_product_id" class="regular-text" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da;">
                        <option value="">🔄 Tất cả sản phẩm</option>
                        <?php
                        $products = wc_get_products(['limit' => -1]);
                        foreach ($products as $product) {
                            $custom_product_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
                            if ($custom_product_id) {
                                echo "<option value='{$custom_product_id}' data-product-name='{$product->get_name()}'>{$product->get_name()}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col large-2">
                    <label for="export_session">
                        Chọn phiên:
                        <span id="session-loading" style="display: none; font-size: 12px; color: #007cba;">(Đang tải...)</span>
                    </label>
                    <select name="export_session" id="export_session" class="regular-text" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da;">
                        <option value="">🔄 Tất cả phiên</option>
                        <?php
                        $all_sessions = $wpdb->get_col("SELECT DISTINCT session FROM $table ORDER BY session DESC");
                        foreach ($all_sessions as $session) {
                            echo "<option value='{$session}'>Phiên {$session}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col large-2">
                    <button type="submit" class="button button-primary" style="background: #28a745; border-color: #28a745; height: 40px; padding: 0 20px; font-weight: 600; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);">
                        📥 Xuất Excel
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Loading indicator -->
        <div id="gpt-export-loading" style="display: none; margin-top: 15px; text-align: center; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
            <div class="spinner is-active" style="margin-bottom: 10px; float: none;"></div>
            <div style="color: #856404;">
                <strong>🔄 Đang xử lý và tạo file Excel...</strong><br>
                <small>Vui lòng chờ trong giây lát, không đóng trang web.</small>
            </div>
        </div>
    </div>
    
    <!-- Search Box nổi bật -->
    <div class="search-box" style="background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
        <form method="get">
            <input type="hidden" name="page" value="gpt-config-barcode">
            <input type="hidden" name="tab" value="barcode">
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
            
            <label for="search_barcode" style="font-weight: bold; color: #0073aa; margin-bottom: 12px;">Tìm mã định danh:</label>
            <input type="text" 
                   id="search_barcode" 
                   name="search_barcode" 
                   value="<?php echo esc_attr($_GET['search_barcode'] ?? ''); ?>" 
                   placeholder="Nhập mã barcode cần tìm..."
                   style="flex: 1; max-width: 300px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="button button-primary">Tìm kiếm</button>
            
            <?php if (!empty($_GET['search_barcode'])): ?>
                <a href="<?php echo admin_url('admin.php?page=gpt-config-barcode&tab=barcode'); ?>" class="button">Xóa tìm kiếm</a>
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
            <input type="hidden" name="tab" value="barcode">
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

            <!-- Sản phẩm -->
            <div class="col large-3">
                <label>Sản phẩm:</label>
                <select name="product_id" class="gpt-select2">
                    <option value="">Tất cả</option>
                    <?php
                    $products = wc_get_products(['limit' => -1]);
                    foreach ($products as $product) {
                        $custom_product_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
                        $selected = ($_GET['product_id'] ?? '') == $custom_product_id ? 'selected' : '';
                        if ($custom_product_id) {
                            echo "<option value='{$custom_product_id}' $selected>{$product->get_name()}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Phiên -->
            <div class="col large-2">
                <label>Phiên:</label>
                <select name="session" class="gpt-select2">
                    <option value="">Tất cả</option>
                    <?php
                    $sessions = $wpdb->get_col("SELECT DISTINCT session FROM $table ORDER BY session DESC");
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
                <button type="submit" class="button button-primary primary">Lọc</button>
            </div>
        </form>
    </div>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center;">
        
        <button type="button" id="gpt_delete_selected" class="button button-danger alert">
            🗑️ Xóa các mã đã chọn
            <span id="gpt_delete_loading" style="display:none;">Đang xóa...</span>
        </button>
        
        <div style="margin-left: auto; color: #000; font-size: 14px;">
            <strong>Tổng: <?php echo number_format($total); ?> mã</strong>
        </div>
        <button type="button" id="gpt_delete_all" class="button button-danger alert">
            🗑️ Xoá toàn bộ mã
            <span id="gpt_delete_loading" style="display:none;">Đang xóa...</span>
        </button>
    </div>
    
    <div class="table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="gpt_barcode_table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="gpt_check_all"></th>
                    <!-- <th>ID</th> -->
                    <th>Mã định danh</th>
                    <th>Điểm</th>
                    <th>Trạng thái</th>
                    <th>Sản phẩm</th>
                    <th>Ngày tạo</th>
                    <th>Token phủ bạc</th>
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
                        <td><?php echo $row->point; ?></td>
                        <td>
                            <?php if ($row->status == 'unused') : ?>
                                <span class="gpt-badge gpt-badge-success">Chưa sử dụng</span>
                            <?php elseif($row->status == 'pending') : ?>
                                <span class="gpt-badge gpt-badge-warning">Đang chờ duyệt</span>
                            <?php else: ?>
                                <span class="gpt-badge gpt-badge-danger">Đã sử dụng</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
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
                                    echo $products[0]->post_title;
                                } else {
                                    echo $custom_prod_id;
                                }
                            ?>
                        </td>
                        <td><?php echo $row->created_at; ?></td>
                        <td><?php echo $row->token; ?></td>
                        <td><img src="<?php echo esc_url($row->qr_code_url); ?>" alt="QR mã cào" style="height:100px;"></td>
                        <td><img src="<?php echo esc_url($row->barcode_url); ?>" alt="Barcode mã cào" style="height:65px;"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align:center; font-style: italic;">
                            <?php 
                            if (!empty($_GET['search_barcode'])) {
                                echo 'Không tìm thấy mã barcode "' . esc_html($_GET['search_barcode']) . '"';
                            } else {
                                echo 'Không có dữ liệu phù hợp với bộ lọc.';
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
            $base_url = admin_url('admin.php?page=gpt-config-barcode');
            $url_params = [];
            foreach (['search_barcode', 'status', 'product_id', 'session', 'from_date', 'to_date'] as $param) {
                if (!empty($_GET[$param])) {
                    $url_params[] = $param . '=' . urlencode($_GET[$param]);
                }
            }
            $base_url .= '&' . implode('&', $url_params);
            
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
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('gpt-export-form');
            const loading = document.getElementById('gpt-export-loading');

            if (form) {
                form.addEventListener('submit', function () {
                    loading.style.display = 'block';
                });
            }
        });
    </script>

    <script>
        jQuery(document).ready(function($) {
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
                    alert('Vui lòng chọn ít nhất 1 mã để xóa.');
                    return;
                }

                if (!confirm('Bạn có chắc chắn muốn xóa ' + selected.length + ' mã đã chọn?')) {
                    return;
                }

                $('#gpt_delete_loading').show();
                $('#gpt_delete_selected').prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'gpt_delete_barcode',
                    ids: selected
                }, function(response) {
                    $('#gpt_delete_loading').hide();
                    $('#gpt_delete_selected').prop('disabled', false);

                    if (response.status === 'success') {
                        alert('Đã xóa thành công ' + selected.length + ' mã!');
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

            $('#gpt_delete_all').on('click', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ mã?')) {
                    return;
                }

                $('#gpt_delete_loading').show();
                $('#gpt_delete_all').prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'gpt_delete_all_barcode'
                }, function(response) {
                    $('#gpt_delete_loading').hide();
                    $('#gpt_delete_all').prop('disabled', false);

                    if (response.status === 'success') {
                        alert('Đã xóa thành công toàn bộ mã!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                }).fail(function() {
                    $('#gpt_delete_loading').hide();
                    $('#gpt_delete_all').prop('disabled', false);
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
    <script>
        jQuery(document).ready(function($) {
            // Cập nhật thông tin tóm tắt khi thay đổi bộ lọc
            function updateExportInfo() {
                let productText = $('#export_product_id option:selected').text();
                let sessionText = $('#export_session option:selected').text();
                
                let info = '📋 <strong>Sẽ xuất:</strong> ';
                let filters = [];
                
                if ($('#export_product_id').val()) {
                    filters.push('Sản phẩm: ' + productText);
                }
                if ($('#export_session').val()) {
                    filters.push('Phiên: ' + sessionText);
                }
                
                if (filters.length > 0) {
                    info += filters.join(' | ');
                } else {
                    info = '💡 <strong>Chọn bộ lọc phía trên để xuất dữ liệu theo yêu cầu</strong>';
                }
                
                $('#export-info').html(info);
            }
            
            // Hàm load phiên theo sản phẩm
            function loadSessionsByProduct(productId) {
                if (!productId) {
                    // Reset về tất cả phiên
                    $('#export_session').html('<option value="">🔄 Tất cả phiên</option><?php foreach ($all_sessions as $session) { echo "<option value=\"{$session}\">Phiên {$session}</option>"; } ?>');
                    updateExportInfo();
                    return;
                }
                
                $('#session-loading').show();
                $('#export_session').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gpt_get_sessions_by_product',
                        product_id: productId,
                        security: '<?php echo wp_create_nonce("gpt_get_sessions_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value="">🔄 Tất cả phiên (của sản phẩm này)</option>';
                            
                            if (response.data.sessions && response.data.sessions.length > 0) {
                                response.data.sessions.forEach(function(session) {
                                    options += '<option value="' + session + '">Phiên ' + session + ' (' + response.data.counts[session] + ' mã)</option>';
                                });
                            } else {
                                options += '<option value="" disabled>Không có phiên nào cho sản phẩm này</option>';
                            }
                            
                            $('#export_session').html(options);
                        } else {
                            alert('Lỗi khi tải phiên: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Lỗi kết nối server khi tải phiên');
                    },
                    complete: function() {
                        $('#session-loading').hide();
                        $('#export_session').prop('disabled', false);
                        updateExportInfo();
                    }
                });
            }
            
            // Lắng nghe thay đổi sản phẩm
            $('#export_product_id').on('change', function() {
                let productId = $(this).val();
                loadSessionsByProduct(productId);
            });
            
            // Lắng nghe thay đổi phiên
            $('#export_session').on('change', updateExportInfo);
            
            // Xử lý submit form xuất Excel
            $('#gpt-excel-export-form').on('submit', function(e) {
                // Hiển thị loading
                $('#gpt-export-loading').show();
                
                // Disable submit button
                $(this).find('button[type="submit"]').prop('disabled', true).html('⏳ Đang xuất...');
                setTimeout(function() {
                    $('#gpt-export-loading').hide();
                    $('#gpt-excel-export-form button[type="submit"]').prop('disabled', false).html('📥 Xuất Excel');
                }, 5000);
                
            });
            
            // Hide loading khi trang load lại (trường hợp lỗi)
            setTimeout(function() {
                $('#gpt-export-loading').hide();
                $('#gpt-excel-export-form button[type="submit"]').prop('disabled', false).html('📥 Xuất Excel');
            }, 1000);
        });
        </script>
<?php
}


add_action('wp_ajax_gpt_get_sessions_by_product', 'gpt_get_sessions_by_product');

function gpt_get_sessions_by_product() {
    // Kiểm tra nonce bảo mật
    if (!wp_verify_nonce($_POST['security'], 'gpt_get_sessions_nonce')) {
        wp_send_json_error('Nonce verification failed');
        return;
    }
    
    if (empty($_POST['product_id'])) {
        wp_send_json_error('Product ID is required');
        return;
    }
    
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;
    $product_id = sanitize_text_field($_POST['product_id']);
    
    try {
        // Lấy tất cả phiên và số lượng mã của sản phẩm được chọn
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT session, COUNT(*) as count 
             FROM $table 
             WHERE product_id = %s 
             GROUP BY session 
             ORDER BY session DESC",
            $product_id
        ));
        
        if ($wpdb->last_error) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        $sessions = [];
        $counts = [];
        
        foreach ($results as $result) {
            $sessions[] = $result->session;
            $counts[$result->session] = $result->count;
        }
        
        wp_send_json_success([
            'sessions' => $sessions,
            'counts' => $counts,
            'total_sessions' => count($sessions),
            'product_id' => $product_id
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error('Exception: ' . $e->getMessage());
    }
}