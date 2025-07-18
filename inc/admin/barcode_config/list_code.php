<?php

add_action('wp_ajax_gpt_delete_macao', 'gpt_delete_macao');
function gpt_delete_macao() {
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

function gpt_export_macao_excel() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $where = '1=1';
    if (!empty($_POST['status'])) {
        $where .= $wpdb->prepare(" AND status = %s", $_POST['status']);
    }
    if (!empty($_POST['branch'])) {
        $where .= $wpdb->prepare(" AND branch LIKE %s", '%' . $_POST['branch'] . '%');
    }

    $results = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY id DESC");

    if (empty($results)) {
        wp_die('Không có dữ liệu để xuất.');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=danh_sach_barcode_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Mã cào', 'Điểm', 'Trạng thái', 'Sản phẩm', 'Ngày tạo'));

    foreach ($results as $row) {
        fputcsv($output, array(
            $row->id,
            $row->barcode,
            $row->point,
            $row->status,
            $row->branch,
            $row->product,
            $row->created_at
        ));
    }

    fclose($output);
    exit;
}

function gpt_macao_list_page() {
    if (isset($_POST['gpt_export_excel'])) {
        gpt_export_macao_excel();
        exit;
    }

    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 100;
    $offset = ($paged - 1) * $per_page;

    $where = '1=1';
    if (!empty($_GET['status'])) {
        $where .= $wpdb->prepare(" AND status = %s", $_GET['status']);
    }
    if (!empty($_GET['branch'])) {
        $where .= $wpdb->prepare(" AND branch LIKE %s", '%' . $_GET['branch'] . '%');
    }

    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
    $total_pages = ceil($total / $per_page);

    ?>
    <style>
        :root{
            --primary-color-admin: #164eaf;
        }
        .list_code_wrap{
            margin-top: 30px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .list_code_wrap h1 {
            margin-top: 0;
            font-size: 20px;
            color: var(--primary-color-admin);
        }

        .ux-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid var(--primary-color-admin);
            border-bottom: 1px solid var(--primary-color-admin);
            padding: 16px 0px;
        }

        .ux-row .col {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ux-row select,
        .ux-row input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .ux-row button {
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: var(--primary-color-admin) !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_export_excel{
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: #22c55e !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_delete_selected{
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: #ef4444 !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_macao_table th,
        #gpt_macao_table td {
            padding: 12px 15px;
            text-align: left;
        }

        #gpt_macao_table tr:hover {
            background: #f9f9f9;
        }

        .gpt-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #fff;
        }

        .gpt-badge-success {
            background-color: #22c55e;
        }

        .gpt-badge-warning {
            background-color: #EB5B00;
        }

        .gpt-badge-danger {
            background-color: #ef4444;
        }

        .tablenav-pages {
            margin-top: 20px;
        }

        .updates-table td input, 
        .widefat tfoot td input, 
        .widefat th input, 
        .widefat thead td input {
            margin: 0 !important;
        }

    </style>
    <div class="list_code_wrap">
        <h1>Danh sách mã cào</h1>
        <form method="post" id="gpt_export_form" style="display:none;">
            <input type="hidden" name="gpt_export_excel" value="1">
            <input type="hidden" name="status" value="<?php echo esc_attr($_GET['status'] ?? ''); ?>">
            <input type="hidden" name="branch" value="<?php echo esc_attr($_GET['branch'] ?? ''); ?>">
        </form>

        <div class="ux-row" style="margin-bottom: 20px;">
            <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
                <input type="hidden" name="page" value="gpt-danh-sach-ma-cao">
                <div class="col large-4">
                    <label>Lọc theo trạng thái:</label>
                    <select name="status" class="form-select" style="margin-bottom: 10px;">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php selected($_GET['status'] ?? '', 'pending'); ?>>Chờ duyệt</option>
                        <option value="unused" <?php selected($_GET['status'] ?? '', 'unused'); ?>>Chưa sử dụng</option>
                        <option value="used" <?php selected($_GET['status'] ?? '', 'used'); ?>>Đã sử dụng</option>
                    </select>
                    <button type="submit" class="button primary">Lọc</button>
                </div>
            </form>
        </div>

        <button type="button" id="gpt_delete_selected" class="button alert">
            Xóa các mã đã chọn
            <span id="gpt_delete_loading" style="display:none; margin-left: 10px;">Đang xóa...</span>
        </button>
        <button type="button" id="gpt_export_excel" class="button primary" style="margin-left: 10px;">
            Xuất Excel
        </button>
        <button id="export_table_excel" class="button">Xuất Excel kèm hình ảnh</button>
        <button id="btn_export_pdf" class="button">Xuất PDF</button>
        <div class="table-wrapper">
            <table class="wp-list-table widefat fixed striped" id="gpt_macao_table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="gpt_check_all"></th>
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
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><input type="checkbox" class="gpt_check_item" value="<?php echo $row->id; ?>"></td>
                        <td><?php echo $row->id; ?></td>
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
                                    $product = $products[0];
                                    $product_name = $product->post_title;
                                    $product_image = get_the_post_thumbnail_url($product->ID, 'medium');

                                    echo $product_name;
                                } else {
                                    echo $custom_prod_id;
                                }
                            ?>
                        </td>
                        <td><?php echo $row->created_at; ?></td>
                        <td><?php echo $row->token; ?></td>
                        <td><?php echo '<img src="' . esc_url($row->qr_code_url) . '" alt="QR mã cào" style="height:100px;">'; ?></td>
                        <td><?php echo '<img src="' . esc_url($row->barcode_url) . '" alt="Barcode mã cào" style="height:30px;">'; ?></td>
                    </tr>
                    <?php endforeach; ?>
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
        </div>
        <div id="export_pdf_area">
        <style>
            .export-page {
                page-break-after: always;
                padding-bottom: 30px;
            }
            .export-page:last-child {
                page-break-after: auto;
            }

            table.export-table {
                width: 100%;
                border-collapse: collapse;
                font-family: Arial, sans-serif;
                font-size: 13px;
            }
            table.export-table th, table.export-table td {
                border: 1px solid #ccc;
                padding: 6px 10px;
                text-align: center;
                vertical-align: middle;
            }
            table.export-table img {
                height: 80px;
                width: auto;
                object-fit: contain;
            }
        </style>

        <?php
        $chunks = array_chunk($results, 9);
        foreach ($chunks as $rows): ?>
            <div class="export-page">
                <table class="export-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Điểm</th>
                            <th>Mã cào</th>
                            <th>Token</th>
                            <th>QR</th>
                            <th>Barcode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo $row->id; ?></td>
                            <td><?php echo $row->point; ?></td>
                            <td><?php echo $row->barcode; ?></td>
                            <td><?php echo $row->token; ?></td>
                            <td><img src="<?php echo esc_url($row->qr_code_url); ?>" /></td>
                            <td><img src="<?php echo esc_url($row->barcode_url); ?>" /></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    document.getElementById("btn_export_pdf").addEventListener("click", function () {
        const element = document.getElementById("export_pdf_area");

        // Load tất cả ảnh trước khi xuất
        const images = element.querySelectorAll("img");
        const imagePromises = [];

        images.forEach(img => {
            if (!img.complete) {
                imagePromises.push(new Promise(resolve => {
                    img.onload = resolve;
                    img.onerror = resolve;
                }));
            }
        });

        Promise.all(imagePromises).then(() => {
            const opt = {
                margin:       0.5,
                filename:     'barcode_' + new Date().toISOString().slice(0,10) + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        });
    });
    </script>

    <script>
        document.getElementById('export_table_excel').addEventListener('click', function () {
            let tableHTML = document.getElementById('gpt_macao_table').outerHTML;
            let html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="UTF-8">
                </head>
                <body>
                    ${tableHTML}
                </body>
                </html>
            `;

            let blob = new Blob(['\ufeff' + html], {
                type: 'application/vnd.ms-excel'
            });

            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'barcode_kem_anh_' + new Date().toISOString().slice(0,10) + '.xls';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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

            // Hiển thị loading
            $('#gpt_delete_loading').show();
            $('#gpt_delete_selected').prop('disabled', true);

            $.post(ajaxurl, {
                action: 'gpt_delete_macao',
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