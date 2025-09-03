<?php

// Nhân viên
function gpt_render_employee_tab() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EMPLOYEES;

    // Xử lý thêm nhân viên mới
    if (isset($_POST['add_employee'])) {
        $code = sanitize_text_field($_POST['code']);
        
        // Kiểm tra mã nhân viên đã tồn tại chưa
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE code = %s",
            $code
        ));
        
        if ($existing > 0) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Mã nhân viên "' . esc_html($code) . '" đã tồn tại. Vui lòng sử dụng mã khác!</p></div>';
        } else {
            $wpdb->insert($table, [
                'code'       => $code,
                'full_name'  => sanitize_text_field($_POST['full_name']),
                'position'   => sanitize_text_field($_POST['position']),
                'image_url'  => esc_url_raw($_POST['image_url']),
            ]);
            echo '<div class="notice notice-success is-dismissible"><p>✅ Đã thêm nhân viên mới.</p></div>';
        }
    }

    // Xử lý xóa nhân viên
    if (isset($_GET['delete_id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete_id'])]);
        echo '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xoá nhân viên.</p></div>';
    }

    // Xử lý cập nhật nhân viên
    if (isset($_POST['edit_employee_id'])) {
        $edit_id = intval($_POST['edit_employee_id']);
        $code = sanitize_text_field($_POST['code']);
        
        // Kiểm tra mã nhân viên có bị trùng với nhân viên khác không
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE code = %s AND id != %d",
            $code,
            $edit_id
        ));
        
        if ($existing > 0) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Mã nhân viên "' . esc_html($code) . '" đã được sử dụng bởi nhân viên khác!</p></div>';
        } else {
            $wpdb->update($table, [
                'code'       => $code,
                'full_name'  => sanitize_text_field($_POST['full_name']),
                'position'   => sanitize_text_field($_POST['position']),
                'image_url'  => esc_url_raw($_POST['image_url']),
            ], ['id' => $edit_id]);

            echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật nhân viên.</p></div>';
        }
    }

    $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset   = ($paged - 1) * $per_page;
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $employees = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));

    $total_pages = ceil($total_items / $per_page);

    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $edit_data = $wpdb->get_row("SELECT * FROM $table WHERE id = " . intval($_GET['edit_id']));
    }

    ?>
    <div class="gpt-admin-flex-layout">
        <div class="form-section">
            <h3><?= $edit_data ? 'Chỉnh sửa nhân viên' : 'Thêm nhân viên mới' ?></h3>
            <hr>
            <form method="post" enctype="multipart/form-data" id="employee-form">
                <div class="form-group">
                    <label for="code">Mã nhân viên: <span style="color: red;">*</span></label>
                    <input type="text" name="code" id="code" placeholder="Mã nhân viên (VD: NV001)" class="regular-text" required value="<?= esc_attr($edit_data->code ?? '') ?>">
                    <span id="code-error" style="color: red; display: none; font-size: 13px;"></span>
                    <p class="description">Mã nhân viên phải là duy nhất trong hệ thống</p>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Họ và tên: <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" id="full_name" placeholder="Họ tên nhân viên" class="regular-text" required value="<?= esc_attr($edit_data->full_name ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="position">Vị trí: <span style="color: red;">*</span></label>
                    <select name="position" id="position" class="gpt-select2" required>
                        <option value="">-- Chọn vị trí --</option>
                        <option value="asm" <?= isset($edit_data) && $edit_data->position == 'asm' ? 'selected' : '' ?>>ASM</option>
                        <option value="pg" <?= isset($edit_data) && $edit_data->position == 'pg' ? 'selected' : '' ?>>PG</option>
                        <option value="sale" <?= isset($edit_data) && $edit_data->position == 'sale' ? 'selected' : '' ?>>Sale</option>
                    </select>
                </div>
        
                <div class="form-group">
                    <label for="image_url">Ảnh đại diện:</label>
                    <div class="gpt-media-uploader">
                        <input type="text" name="image_url" id="image_url" class="regular-text" placeholder="Chưa chọn ảnh" value="<?= esc_url($edit_data->image_url ?? '') ?>" readonly>
                        <button type="button" class="button gpt-select-image" data-target="#image_url" style="margin-top:10px;">Chọn ảnh</button>
                        <div class="gpt-preview" style="margin-top:10px;">
                            <?php if (!empty($edit_data->image_url)): ?>
                                <img src="<?= esc_url($edit_data->image_url) ?>" style="max-width: 150px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($edit_data): ?>
                    <input type="hidden" name="edit_employee_id" value="<?= $edit_data->id ?>">
                    <button type="submit" class="button button-primary">Lưu thay đổi</button>
                    <a href="?page=gpt-store-employee&tab=employee" class="button button-danger">Huỷ</a>
                <?php else: ?>
                    <button type="submit" name="add_employee" class="button button-primary" style="width: 100%;">Lưu thông tin</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="gpt-table-container">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Vị trí</th>
                        <th>Ảnh</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?= esc_html($emp->id) ?></td>
                            <td><strong><?= esc_html($emp->code) ?></strong></td>
                            <td><?= esc_html($emp->full_name) ?></td>
                            <td>
                                <?php 
                                $position_labels = [
                                    'asm' => 'ASM',
                                    'pg' => 'PG', 
                                    'sale' => 'Sale'
                                ];
                                echo esc_html($position_labels[$emp->position] ?? $emp->position);
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($emp->image_url)): ?>
                                    <img src="<?= esc_url($emp->image_url) ?>" width="60" style="object-fit: cover;">
                                <?php else: ?>
                                    <span style="color: #999;">Chưa có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc_html($emp->created_at) ?></td>
                            <td class="btn-actions">
                                <a href="?page=gpt-store-employee&tab=employee&edit_id=<?= $emp->id ?>" class="button">Sửa</a>
                                <a href="?page=gpt-store-employee&tab=employee&delete_id=<?= $emp->id ?>" class="button button-danger" onclick="return confirm('Bạn chắc chắn muốn xoá nhân viên này?')">Xoá</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
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
    <?php
}

// AJAX handler để kiểm tra mã nhân viên
add_action('wp_ajax_check_employee_code', 'ajax_check_employee_code');
function ajax_check_employee_code() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EMPLOYEES;
    
    $code = sanitize_text_field($_POST['code']);
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    
    if (empty($code)) {
        wp_send_json_error('Mã nhân viên không được để trống');
        return;
    }
    
    // Kiểm tra xem mã có tồn tại không (loại trừ nhân viên hiện tại nếu đang edit)
    if ($employee_id > 0) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE code = %s AND id != %d",
            $code,
            $employee_id
        ));
    } else {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE code = %s",
            $code
        ));
    }
    
    if ($existing > 0) {
        wp_send_json_error('Mã nhân viên này đã tồn tại!');
    } else {
        wp_send_json_success('Mã nhân viên hợp lệ');
    }
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
});

add_action('admin_footer', function () {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Khởi tạo Select2
        $('#position').select2({
            width: '100%',
            placeholder: '-- Chọn vị trí --'
        });
        
        // Real-time validation cho mã nhân viên
        var typingTimer;
        var doneTypingInterval = 500; // 500ms delay
        
        $('#code').on('keyup', function() {
            clearTimeout(typingTimer);
            var code = $(this).val().trim();
            
            if (code === '') {
                $('#code-error').hide();
                return;
            }
            
            typingTimer = setTimeout(function() {
                checkEmployeeCode(code);
            }, doneTypingInterval);
        });
        
        $('#code').on('keydown', function() {
            clearTimeout(typingTimer);
        });
        
        // Hàm kiểm tra mã nhân viên qua AJAX
        function checkEmployeeCode(code) {
            var employeeId = $('input[name="edit_employee_id"]').val() || 0;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_employee_code',
                    code: code,
                    employee_id: employeeId
                },
                success: function(response) {
                    if (response.success) {
                        $('#code-error').hide();
                        $('#code').css('border-color', '#8cc152');
                    } else {
                        $('#code-error').text(response.data).show();
                        $('#code').css('border-color', '#e74c3c');
                    }
                }
            });
        }
        
        // Validate form trước khi submit
        $('#employee-form').on('submit', function(e) {
            var code = $('#code').val().trim();
            var errorVisible = $('#code-error').is(':visible');
            
            if (errorVisible) {
                e.preventDefault();
                alert('Vui lòng kiểm tra lại mã nhân viên!');
                $('#code').focus();
                return false;
            }
            
            if (code === '') {
                e.preventDefault();
                alert('Mã nhân viên không được để trống!');
                $('#code').focus();
                return false;
            }
        });
        
        // Reset border color khi focus
        $('#code').on('focus', function() {
            $(this).css('border-color', '');
        });
    });
    </script>
    
    <style>
        #code-error {
            margin-top: 5px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        
        .form-group .description {
            color: #666;
            font-size: 13px;
            font-style: italic;
            margin-top: 5px;
        }
        
        input[type="text"]:focus {
            outline: none;
            box-shadow: 0 0 0 1px #0073aa;
        }
    </style>
    <?php
});

// Tạo hoặc cập nhật bảng với unique constraint cho cột code
function update_employees_table_unique_code() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EMPLOYEES;
    
    // Kiểm tra xem index đã tồn tại chưa
    $index_exists = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'unique_code'");
    
    if (empty($index_exists)) {
        // Thêm unique index cho cột code
        $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY unique_code (code)");
    }
}

// Hook để chạy khi plugin được kích hoạt hoặc cập nhật
register_activation_hook(__FILE__, 'update_employees_table_unique_code');
add_action('admin_init', 'update_employees_table_unique_code');