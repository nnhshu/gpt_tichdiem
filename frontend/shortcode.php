<?php

add_shortcode('gpt_lookup_employee_aff', 'gpt_render_lookup_employee_aff');

function gpt_render_lookup_employee_aff() {
    ob_start();
    ?>
<div class="gpt-lookup-form">
    <form id="gpt-lookup-form">
        <h3>🔍 Tra cứu thông tin nhân viên</h3>
        <input type="text" id="tra_cuu_keyword" placeholder="Nhập mã nhân viên ..." required>
        <button type="submit">Tra cứu</button>
    </form>
    <div id="gpt-result"></div>
</div>

<style>
.gpt-lookup-form {
    max-width: 100%;
    margin: 40px auto;
    padding: 24px 32px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

#gpt-lookup-form {
    width: 350px;
    margin: 0 auto;
}

#gpt-lookup-form h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #2d3748;
    font-weight: 600;
    text-align: center;
}

.gpt-lookup-form input[type="text"],
.gpt-lookup-form input[type="number"],
.gpt-lookup-form select {
    width: 100%;
    padding: 10px 14px;
    margin-bottom: 16px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    background-color: #f7fafc;
    font-size: 15px;
    transition: border-color 0.2s ease;
    box-shadow: none;
}

.gpt-lookup-form input:focus,
.gpt-lookup-form select:focus {
    outline: none;
    border-color: #173d7c;
    background-color: #fff;
}

.gpt-lookup-form button {
    width: 100%;
    padding: 8px 12px;
    background-color: #173d7c;
    border: none;
    color: white;
    font-size: 16px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    margin: 0px;
}

.gpt-lookup-form button:hover {
    background-color: #2b6cb0;
}

.gpt-lookup-form .form-group {
    margin-bottom: 20px;
}

.gpt-lookup-form .result-table {
    margin-top: 30px;
    border-collapse: collapse;
    width: 100%;
}

#gpt-result * {
    color: #000;
}

.gpt-lookup-form .result-table th,
.gpt-lookup-form .result-table td {
    border: 1px solid #e2e8f0;
    padding: 10px 14px;
    text-align: left;
    font-size: 14px;
}

.gpt-lookup-form .result-table th {
    background-color: #f1f5f9;
    color: #000;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#gpt-lookup-form').on('submit', function(e) {
        e.preventDefault();
        let keyword = $('#tra_cuu_keyword').val().trim();
        if (!keyword) return;


        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'gpt_tra_cuu_diem_nhan_vien',
            keyword: keyword
        }, function(res) {
            if (res.success) {
                let html = `<h4>👤 Nhân viên: <strong>${res.data.name}</strong></h4>
                                    <p>🎯 Tổng điểm: <strong>${res.data.total_points}</strong></p>`;
                if (res.data.logs.length > 0) {
                    html += `<table class="gpt-log-table">
                                        <thead>
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Họ và tên</th>
                                                <th>Số điện thoại</th>
                                                <th>Mã cào</th>
                                                <th>Sản phẩm</th>
                                                <th>Điểm</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                    res.data.logs.forEach(log => {
                        html += `<tr>
                                            <td>${log.created_at}</td>
                                            <td>${log.customer_name}</td>
                                            <td>${log.phone_number}</td>
                                            <td>${log.barcode}</td>
                                            <td>${log.product}</td>
                                            <td>${log.point_change}</td>
                                        </tr>`;
                    });
                    html += `</tbody></table>`;
                } else {
                    html += `<p>📭 Không có lịch sử tích điểm nào.</p>`;
                }

                $('#gpt-result').html(html);
            } else {
                $('#gpt-result').html('<p style="color:red">❌ Không tìm thấy nhân viên.</p>');
            }
        });
    });
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_lookup_employee_aff_ajax', 'gpt_lookup_employee_aff_ajax');
add_action('wp_ajax_nopriv_gpt_lookup_employee_aff_ajax', 'gpt_lookup_employee_aff_ajax');

function gpt_mask_phone_number($phone) {
    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) < 6) {
        return $phone;
    }

    $prefix = substr($phone, 0, 3);
    $suffix = substr($phone, -3);
    return $prefix . '*****' . $suffix;
}

function gpt_lookup_employee_aff_ajax() {
    global $wpdb;
    $keyword = sanitize_text_field($_POST['keyword']);

    $table_emp  = BIZGPT_PLUGIN_WP_EMPLOYEES;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;

    $emp = $wpdb->get_row($wpdb->prepare(
        "SELECT id, full_name, code FROM $table_emp WHERE code = %s LIMIT 1", $keyword
    ));

    if (!$emp) {
        wp_send_json_error();
    }

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(point_change) FROM $table_logs WHERE aff_by_employee_code = %d AND transaction_type = 'tich_diem'", $emp->id
    ));

    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone_number, barcode, product, point_change, barcode_status, created_at FROM $table_logs 
         WHERE aff_by_employee_code = %d ORDER BY created_at DESC LIMIT 100", $emp->id
    ));

    $formatted_logs = array_map(function($row) {
        return [
            'created_at'       => date('d/m/Y', strtotime($row->created_at)),
            'barcode'     => esc_html($row->barcode),
            'product'   => esc_html($row->product),
            'point_change'       => intval($row->point_change),
            'phone_number' => esc_html(gpt_mask_phone_number($row->phone_number)),
            'customer_name' => esc_html($row->customer_name),
        ];
    }, $logs);

    wp_send_json_success([
        'name'         => $emp->full_name,
        'total_points' => intval($total),
        'logs'         => $formatted_logs,
    ]);
}

// Tra cứu cửa hàng

add_shortcode('gpt_lookup_store_aff', 'gpt_shortcode_lookup_store_aff');
function gpt_shortcode_lookup_store_aff() {
    global $wpdb;
    $store_table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $point_table = BIZGPT_PLUGIN_WP_LOGS;

    $stores = $wpdb->get_results("SELECT id, store_name FROM $store_table ORDER BY store_name ASC");

    ob_start();
    ?>
<div class="gpt-lookup-form">
    <div class="gpt-lookup-form-container">
        <h3 style="text-align: center;">Tra cứu thông tin tích điểm của cửa hàng</h3>
        <div id="lookup-store-form">
            <label for="store_id">Chọn cửa hàng:</label>
            <select id="store_id" name="store_id" class="gpt-select2" required>
                <option value="">-- Chọn cửa hàng --</option>
                <?php foreach ($stores as $store): ?>
                <option value="<?= esc_attr($store->id) ?>">
                    <?= esc_html($store->store_name) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary">Tra cứu</button>
        </div>
    </div>
    <div id="gpt-loading-wrapper" style="display:none;">
        <div class="gpt-loading-center">
            <div class="lds-dual-ring"></div>
            <p class="loading-text">Hệ thống đang xử lý dữ liệu. Vui lòng chờ ...</p>
        </div>
    </div>
    <div id="gpt_store_result"></div>
</div>
<style>

.gpt-loading-center {
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 16px;
}
.loading-text {
    margin-bottom: 0px;
    color: #000;
}
.lds-dual-ring {
    display: inline-block;
    width: 40px;
    height: 40px;
}

.lds-dual-ring:after {
    content: " ";
    display: block;
    width: 32px;
    height: 32px;
    margin: 4px;
    border-radius: 50%;
    border: 4px solid var(--fs-color-primary);
    border-color: var(--fs-color-primary) transparent var(--fs-color-primary) transparent;
    animation: lds-dual-ring 1.2s linear infinite;
}

@keyframes lds-dual-ring {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
<script>
jQuery(document).ready(function($) {

    $('#gpt_store_result').hide();
    $('#lookup-store-form #store_id').select2({
        placeholder: 'Chọn cửa hàng...',
        minimumInputLength: 1
    });

    $('#lookup-store-form button').on('click', function(e) {

        e.preventDefault();
        let store_id = $('#lookup-store-form #store_id').val();

        if (store_id) {
            $('#gpt_store_result').hide().html('');
            $('#gpt-loading-wrapper').show();
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'gpt_get_store_point_aff',
                store_id: store_id
            }, function(res) {
                $('#gpt-loading-wrapper').hide();
                if (res.success) {
                    let html = `<h4>Cửa hàng: <strong>${res.data.name}</strong></h4>
                                        <p>🎯 Tổng điểm: <strong>${res.data.total_points}</strong></p>`;
                    if (res.data.logs.length > 0) {
                        html += `<table class="gpt-log-table">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Họ và tên</th>
                                        <th>Số điện thoại</th>
                                        <th>Mã định danh</th>
                                        <th>Sản phẩm</th>
                                        <th>Điểm</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        res.data.logs.forEach(log => {
                            html += `<tr>
                                    <td>${log.created_at}</td>
                                    <td>${log.customer_name}</td>
                                    <td>${log.phone_number}</td>
                                    <td>${log.barcode}</td>
                                    <td>${log.product}</td>
                                    <td>${log.point_change}</td>
                                </tr>`;
                        });
                        html += `</tbody></table>`;
                    } else {
                        html += `<p>📭 Không có lịch sử tích điểm nào.</p>`;
                    }
                    $('#gpt_store_result').show();
                    $('#gpt_store_result').html(html);
                } else {
                    $('#gpt_store_result').html(
                        '<p style="color:red">❌ Không tìm thấy thông tin cửa hàng.</p>');
                }
            });
        }
    });
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_get_store_point_aff', 'gpt_get_store_point_aff');
add_action('wp_ajax_nopriv_gpt_get_store_point_aff', 'gpt_get_store_point_aff');

function gpt_get_store_point_aff() {
    global $wpdb;

    $store_id = intval($_POST['store_id']);
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;


    $store = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $store_id));
    if (!$store) {
        wp_send_json_error('Không tìm thấy cửa hàng.');
    }

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(point_change) FROM $table_logs WHERE aff_by_store_id = %d AND transaction_type = 'tich_diem'", $store->id
    ));

    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone_number, barcode, product, point_change, barcode_status, created_at FROM $table_logs 
        WHERE aff_by_store_id = %d ORDER BY created_at DESC LIMIT 100", $store->id
    ));

    $formatted_logs = array_map(function($row) {
        return [
            'created_at'       => date('d/m/Y', strtotime($row->created_at)),
            'barcode'     => esc_html($row->barcode),
            'product'   => esc_html($row->product),
            'point_change'       => intval($row->point_change),
            'phone_number' => esc_html(gpt_mask_phone_number($row->phone_number)),
            'customer_name' => esc_html($row->customer_name),
        ];
    }, $logs);

    wp_send_json_success([
        'name'         => $store->store_name,
        'total_points' => intval($total),
        'logs'         => $formatted_logs,
    ]);
}

// Tra cứu tích điểm

add_shortcode('gpt_lookup_point_of_user', 'gpt_lookup_point_of_user_shortcode');

function gpt_lookup_point_of_user_shortcode() {
    ob_start();
    ?>
<?php  wp_enqueue_style('gpt-form-style-look', plugin_dir_url(__FILE__) . 'lookup.css'); ?>
<div class="gpt_lookup_point_wrap">
    <div class="bizgpt_form_content">
        <h3 style="text-align: center;">Tra cứu thông tin tích điểm</h3>
        <form id="gpt_lookup_point_form" class="space-y-3">
            <input type="text" id="lookup_phone" placeholder="Nhập số điện thoại" required>

            <div style="margin-top: 10px;">
                <label>
                    Nhập vào kết quả của phép tính:
                    <span id="captcha_question"></span>
                </label>
                <input type="text" id="captcha_answer" placeholder="Nhập kết quả" required>
            </div>

            <button type="submit" class="btn-gradient">Tra cứu</button>
        </form>
    </div>
    <div id="search_result"></div>
</div>

<script>
jQuery(document).ready(function($) {

    function generateCaptcha() {
        let num1 = Math.floor(Math.random() * 10) + 1;
        let num2 = Math.floor(Math.random() * 10) + 1;
        $('#captcha_question').text(`${num1} + ${num2} = ?`);
        return num1 + num2;
    }

    let captcha_result = generateCaptcha();

    let currentPageTich = 1;
    let currentPageDoi = 1;
    let perPage = 5;

    $('#gpt_lookup_point_form').on('submit', function(e) {
        e.preventDefault();

        let phone = $('#lookup_phone').val().trim();
        let captcha = $('#captcha_answer').val().trim();

        if (phone === '') {
            alert('Vui lòng nhập số điện thoại!');
            return;
        }

        if (!isValidVietnamPhone(phone)) {
            alert('Số điện thoại không hợp lệ. Vui lòng nhập đúng số điện thoại Việt Nam.');
            return;
        }

        if (parseInt(captcha) !== captcha_result) {
            alert('Mã captcha không đúng!');
            return;
        }

        $('#search_result').html('<p>🔄 Đang tra cứu, vui lòng đợi...</p>');

        fetchHistory(phone, currentPageTich, currentPageDoi);
    });

    function fetchHistory(phone, page) {
        let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        $.post(ajaxurl, {
            action: 'gpt_lookup_point_ajax',
            phone: phone,
            page: page,
            per_page: perPage
        }, function(response) {
            if (response.success) {
                let data = response.data;
                renderResult(phone, data);

                captcha_result = generateCaptcha();
                $('#captcha_answer').val('');

            } else {
                $('#search_result').html(`<p style="color:red;">${response.data}</p>`);
                captcha_result = generateCaptcha();
                $('#captcha_answer').val('');
            }
        });
    }

    function fetchHistory(phone, pageTich, pageDoi) {
        let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        $.post(ajaxurl, {
            action: 'gpt_lookup_point_ajax',
            phone: phone,
            page_tich: pageTich,
            page_doi: pageDoi,
            per_page: perPage
        }, function(response) {
            if (response.success) {
                let data = response.data;
                renderResult(phone, data);

                captcha_result = generateCaptcha();
                $('#captcha_answer').val('');
            } else {
                $('#search_result').html(`<p style="color:red;">${response.data}</p>`);
                captcha_result = generateCaptcha();
                $('#captcha_answer').val('');
            }
        });
    }

    function renderResult(phone, data) {
        let html = `
                <div class="flex align-middle box_info">
                    <div><span>📱 Số điện thoại:</span> <strong>${phone}</strong></div>
                    <div><span>✅ Đã tích:</span> <strong>${data.diem_tich} điểm</strong></div>
                    <div><span>🔁 Đã đổi:</span> <strong>${data.diem_doi} điểm</strong></div>
                    <div><span>⭐ Điểm hiện có:</span> <strong>${data.diem_con_lai} điểm</strong></div>
                </div>
                <div style="margin-top: 20px; text-align: left;">
                    <h4>Lịch sử Tích điểm:</h4>
                    ${renderTable(data.lich_su_tich)}
                    <div id="pagination_tich" style="margin-top: 10px; text-align: center;">
                        ${data.total_pages_tich > 1 ? renderPagination(data.total_pages_tich, data.current_page_tich, 'tich') : ''}
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: left;">
                    <h4>Lịch sử Đổi điểm:</h4>
                    ${renderTable(data.lich_su_doi)}
                    <div id="pagination_doi" style="margin-top: 10px; text-align: center;">
                        ${data.total_pages_doi > 1 ? renderPagination(data.total_pages_doi, data.current_page_doi, 'doi') : ''}
                    </div>
                </div>
            `;

        $('#search_result').html(html);

        $('.pagination-link').on('click', function() {
            let type = $(this).data('type');
            let page = parseInt($(this).data('page'));
            if (type === 'tich') {
                currentPageTich = page;
            } else if (type === 'doi') {
                currentPageDoi = page;
            }
            fetchHistory(phone, currentPageTich, currentPageDoi);
        });
    }

    function renderTable(list) {
        if (list.length === 0) {
            return '<p>Không có dữ liệu.</p>';
        }

        return `
                        <div class="overflow-x-auto">
                            <table class="table bordered" style="width:100%; border-collapse: collapse;" border="1">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px;">Loại giao dịch</th>
                                        <th style="padding: 8px;">Số điểm</th>
                                        <th style="padding: 8px;">Sản phẩm</th>
                                        <th style="padding: 8px;">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${list.map(item => `
                                        <tr>
                                            <td style="padding: 8px;">${item.loai_giao_dich}</td>
                                            <td style="padding: 8px;">${item.so_diem}</td>
                                            <td style="padding: 8px;">${item.product}</td>
                                            <td style="padding: 8px;">${item.created_at}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
    }

    function renderPagination(totalPages, currentPage, type) {
        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                html += `<span style="margin: 0 5px; font-weight: bold;">${i}</span>`;
            } else {
                html +=
                    `<a href="javascript:void(0);" class="pagination-link" data-page="${i}" data-type="${type}" style="margin: 0 5px;">${i}</a>`;
            }
        }
        return html;
    }

    function isValidVietnamPhone(phone) {
        let regex = /^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-5]|9[0-9])[0-9]{7}$/;
        return regex.test(phone);
    }
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gpt_lookup_point_ajax', 'gpt_lookup_point_ajax_callback');
add_action('wp_ajax_nopriv_gpt_lookup_point_ajax', 'gpt_lookup_point_ajax_callback');

function gpt_lookup_point_ajax_callback() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_LOGS;

    $phone = sanitize_text_field($_POST['phone']);

    $page_tich = isset($_POST['page_tich']) ? intval($_POST['page_tich']) : 1;
    $page_doi = isset($_POST['page_doi']) ? intval($_POST['page_doi']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;

    $offset_tich = ($page_tich - 1) * $per_page;
    $offset_doi = ($page_doi - 1) * $per_page;

    if (empty($phone)) {
        wp_send_json_error('Vui lòng nhập số điện thoại!');
    }

    $diem_tich = $wpdb->get_var($wpdb->prepare("SELECT IFNULL(SUM(point_change), 0) FROM $table WHERE phone_number = %s AND transaction_type = 'tich_diem'", $phone));
    $diem_doi = $wpdb->get_var($wpdb->prepare("SELECT IFNULL(SUM(point_change), 0) FROM $table WHERE phone_number = %s AND transaction_type = 'doi_diem'", $phone));

    if ($diem_tich == 0 && $diem_doi == 0) {
        wp_send_json_error('Không tìm thấy dữ liệu tích điểm cho số điện thoại này!');
    }

    $diem_con_lai = intval($diem_tich) - abs(intval($diem_doi));

    // Lấy lịch sử tích điểm
    $total_tich = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE phone_number = %s AND transaction_type = 'tich_diem'", $phone));
    $total_pages_tich = ceil($total_tich / $per_page);

    $lich_su_tich = $wpdb->get_results($wpdb->prepare(
        "SELECT point_change, product, created_at FROM $table WHERE phone_number = %s AND transaction_type = 'tich_diem' ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $phone, $per_page, $offset_tich
    ));

    $history_point_detail = [];
    foreach ($lich_su_tich as $log) {
        $history_point_detail[] = [
            'so_diem' => intval($log->point_change),
            'loai_giao_dich' => 'Tích điểm',
            'product' => $log->product,
            'created_at' => date('d/m/Y H:i', strtotime($log->created_at))
        ];
    }

    // Lấy lịch sử đổi điểm
    $total_doi = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE phone_number = %s AND transaction_type = 'doi_diem'", $phone));
    $total_pages_doi = ceil($total_doi / $per_page);

    $lich_su_doi = $wpdb->get_results($wpdb->prepare(
        "SELECT point_change, product, created_at FROM $table WHERE phone_number = %s AND transaction_type = 'doi_diem' ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $phone, $per_page, $offset_doi
    ));

    $lich_su_doi_chi_tiet = [];
    foreach ($lich_su_doi as $log) {
        $lich_su_doi_chi_tiet[] = [
            'so_diem' => intval($log->point_change),
            'loai_giao_dich' => 'Đổi điểm',
            'product' => $log->product,
            'created_at' => date('d/m/Y H:i', strtotime($log->created_at))
        ];
    }

    wp_send_json_success([
        'diem_tich' => intval($diem_tich),
        'diem_doi' => abs(intval($diem_doi)),
        'diem_con_lai' => max($diem_con_lai, 0),
        'lich_su_tich' => $history_point_detail,
        'lich_su_doi' => $lich_su_doi_chi_tiet,
        'total_pages_tich' => $total_pages_tich,
        'current_page_tich' => $page_tich,
        'total_pages_doi' => $total_pages_doi,
        'current_page_doi' => $page_doi
    ]);
}