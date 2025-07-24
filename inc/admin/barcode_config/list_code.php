<?php

add_action('wp_ajax_gpt_delete_barcode', 'gpt_delete_barcode');
add_action('admin_post_gpt_export_barcode_excel', 'gpt_export_barcode_with_image');
add_action('admin_post_nopriv_gpt_export_barcode_excel', 'gpt_export_barcode_with_image');

require_once plugin_dir_path(__FILE__) . '../../libs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function gpt_delete_barcode() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

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

function gpt_export_barcode_with_image() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $rows = $wpdb->get_results("SELECT * FROM $table WHERE status = 'unused' ORDER BY id DESC");

    if (empty($rows)) {
        wp_die('Không có dữ liệu để xuất.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $headers = ['Mã định danh', "Token - Tráng bạc", 'Điểm', 'Trạng thái', 'Ngày tạo', 'Link QR', 'Link Barcode'];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->setCellValue("A$rowIndex", $row->barcode);
        $sheet->setCellValue("B$rowIndex", $row->token);
        $sheet->setCellValue("C$rowIndex", $row->point);
        $sheet->setCellValue("D$rowIndex", $row->status);
        $sheet->setCellValue("E$rowIndex", $row->created_at);
        $sheet->setCellValue("F$rowIndex", $row->qr_code_url);
        $sheet->setCellValue("G$rowIndex", $row->barcode_url);
        $rowIndex++;
    }

    // Header Excel để xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="ma_cao_export_links.xlsx"');
    header('Cache-Control: max-age=0');

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
    if (!empty($_GET['status'])) {
        $where .= $wpdb->prepare(" AND status = %s", $_GET['status']);
    }
    if (!empty($_GET['product_id'])) {
        $where .= $wpdb->prepare(" AND product_id = %s", $_GET['product_id']);
    }
    if (!empty($_GET['session'])) {
        $where .= $wpdb->prepare(" AND session = %s", $_GET['session']);
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $where .= $wpdb->prepare(" AND DATE(created_at) BETWEEN %s AND %s", $_GET['from_date'], $_GET['to_date']);
    }

    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
    $total_pages = ceil($total / $per_page);

    ?>
    <h1>Danh sách mã định danh</h1>
    <div class="ux-row" style="margin-bottom: 20px;">
        <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
            <input type="hidden" name="page" value="gpt-config-barcode">
            
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
                        $selected = ($_GET['product_id'] ?? '') == $product->get_id() ? 'selected' : '';
                        echo "<option value='{$product->get_id()}' $selected>{$product->get_name()}</option>";
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
                <button type="submit" class="button primary">Lọc</button>
            </div>
        </form>

    </div>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 15px;">
        <input type="hidden" name="action" value="gpt_export_barcode_excel">
        <button type="submit" class="button button-primary">📥 Xuất File Excel</button>
    </form>
    <div id="gpt-export-loading" style="display:none; margin-top:10px;">
        <div class="spinner is-active" style="margin-bottom: 10px;"></div>
        <strong>Đang xử lý và tạo file Excel, vui lòng chờ...</strong>
    </div>
    <button type="button" id="gpt_delete_selected" class="button alert">
        Xóa các mã đã chọn
        <span id="gpt_delete_loading" style="display:none;">Đang xóa...</span>
    </button>
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
                        <td><?php echo $row->barcode; ?></td>
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
                        <td><img src="<?php echo esc_url($row->barcode_url); ?>" alt="Barcode mã cào" style="height:80px;"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align:center; font-style: italic;">Không có dữ liệu phù hợp với bộ lọc.</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
    <?php
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links([
                'base' => admin_url('admin.php?page=gpt-danh-sach-ma-cao%_%'),
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

            form.addEventListener('submit', function () {
                loading.style.display = 'block';
            });
        });
    </script>

    <script>
        jQuery(document).ready(function($) {
            $('#gpt_check_all').on('change', function() {
                $('.gpt_check_item').prop('checked', $(this).is(':checked'));
            });

            $('#gpt_delete_selected').on('click', function() {
                let selected = [];
                $('.gpt_check_item:checked').each(function() {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert('Vui lòng chọn ít nhất 1 mã để xóa.');
                    return;
                }

                if (!confirm('Bạn có chắc chắn muốn xóa các mã đã chọn?')) {
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
                        alert('Đã xóa thành công!');
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
        });
    </script>
<?php
}