<?php

function gpt_render_store_tab() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_channel = BIZGPT_PLUGIN_WP_CHANNELS;
    $table_employees = BIZGPT_PLUGIN_WP_EMPLOYEES;
    $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;

    $channels = $wpdb->get_results("SELECT id, title FROM $table_channel ORDER BY title ASC");
    $employees = $wpdb->get_results("SELECT * FROM $table_employees");

    // Xử lý tìm kiếm
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    
    // Query với JOIN để lấy đầy đủ thông tin
    $where_clause = '';
    if (!empty($search)) {
        $where_clause = $wpdb->prepare(" WHERE s.store_name LIKE %s ", '%' . $wpdb->esc_like($search) . '%');
    }
    
    $stores = $wpdb->get_results("
        SELECT 
            s.*,
            c.title as channel_name,
            d.title as distributor_name,
            e.full_name as employee_name,
            e.position as employee_position,
            e.code as employee_code
        FROM $table s
        LEFT JOIN $table_channel c ON s.channel_id = c.id
        LEFT JOIN $table_distributors d ON s.distributor_id = d.id
        LEFT JOIN $table_employees e ON s.employee_id = e.id
        $where_clause
        ORDER BY s.created_at DESC
    ");

    if (isset($_POST['add_store'])) {
        $wpdb->insert($table, [
            'store_name' => sanitize_text_field($_POST['store_name']),
            'address'    => sanitize_text_field($_POST['address']),
            'phone_number' => sanitize_text_field($_POST['phone_number']),
            'image_url'  => esc_url_raw($_POST['image_url']),
            'channel_id' => intval($_POST['channel_id']),
            'employee_id' => intval($_POST['employee_id']),
            'distributor_id' => intval($_POST['distributor_id']),
            
        ]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã thêm cửa hàng mới.</p></div>';
        // Reload page to refresh data
        echo '<script>window.location.href = window.location.href;</script>';
    }

    if (isset($_GET['delete_id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete_id'])]);
        echo '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xoá cửa hàng.</p></div>';
    }

    if (isset($_POST['edit_store_id'])) {
        $wpdb->update($table, [
            'store_name' => sanitize_text_field($_POST['store_name']),
            'address'    => sanitize_text_field($_POST['address']),
            'phone_number' => sanitize_text_field($_POST['phone_number']),
            'image_url'  => esc_url_raw($_POST['image_url']),
            'channel_id' => intval($_POST['channel_id']),
            'employee_id' => intval($_POST['employee_id']),
            'distributor_id' => intval($_POST['distributor_id']),
        ], ['id' => intval($_POST['edit_store_id'])]);

        echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật cửa hàng.</p></div>';
        // Reload page to refresh data
        echo '<script>window.location.href = "?page=gpt-store-employee&tab=store";</script>';
    }

    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $edit_id = intval($_GET['edit_id']);
        $edit_data = $wpdb->get_row("SELECT * FROM $table WHERE id = $edit_id");
    }

    ?>
    <div class="gpt-admin-flex-layout">
        <div class="form-section">
            <h3><?= $edit_data ? 'Chỉnh sửa cửa hàng' : 'Thêm cửa hàng mới' ?></h3>
            <hr>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="store_name">Tên cửa hàng: <span style="color: red;">*</span></label>
                    <input type="text" name="store_name" id="store_name" placeholder="Tên cửa hàng" class="regular-text" required
                    value="<?= esc_attr($edit_data->store_name ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Số điện thoại: <span style="color: red;">*</span></label>
                    <input type="text" name="phone_number" id="phone_number" placeholder="Số điện thoại" class="regular-text" required
                    value="<?= esc_attr($edit_data->phone_number ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Địa chỉ: <span style="color: red;">*</span></label>
                    <input type="text" name="address" id="address" placeholder="Địa chỉ" class="regular-text" required
                    value="<?= esc_attr($edit_data->address ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="channel_id">Kênh bán hàng: <span style="color: red;">*</span></label>
                    <select name="channel_id" id="channel_id" class="gpt-select2" required>
                        <option value="">-- Chọn kênh bán hàng --</option>
                        <?php foreach ($channels as $channel): ?>
                            <option value="<?= esc_attr($channel->id) ?>"
                                <?= isset($edit_data) && $edit_data->channel_id == $channel->id ? 'selected' : '' ?>>
                                <?= esc_html($channel->title) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="distributor_id">Nhà phân phối:</label>
                    <select name="distributor_id" id="distributor_id" class="gpt-select2">
                        <option value="">-- Chọn nhà phân phối --</option>
                        <?php if ($edit_data && $edit_data->channel_id): ?>
                            <?php
                                $selected_distributor = isset($edit_data->distributor_id) ? $edit_data->distributor_id : '';
                                $distributors = $wpdb->get_results($wpdb->prepare("SELECT id, title FROM $table_distributors WHERE channel_id = %d ORDER BY title ASC", $edit_data->channel_id));
                                foreach ($distributors as $distributor):
                                    $selected = $distributor->id == $selected_distributor ? 'selected' : '';
                                    echo '<option value="' . esc_attr($distributor->id) . '" ' . $selected . '>' . esc_html($distributor->title) . '</option>';
                                endforeach;
                            ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="employee_id">Nhân viên phụ trách:</label>
                    <select name="employee_id" id="employee_id" class="gpt-select2">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php
                             $selected_employee = isset($edit_data->employee_id) ? $edit_data->employee_id : '';
                            foreach ($employees as $employee):
                                $selected = $employee->id == $selected_employee ? 'selected' : '';
                                $position_labels = [
                                    'asm' => 'ASM',
                                    'pg' => 'PG', 
                                    'sale' => 'Sale'
                                ];
                                $position = $position_labels[$employee->position] ?? $employee->position;
                                echo '<option value="' . esc_attr($employee->id) . '" ' . $selected . '>'.'['. esc_html($position). '] - '. esc_html($employee->full_name) . ' (' . esc_html($employee->code) . ')</option>';
                            endforeach;
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Ảnh đại diện:</label>
                    <div class="gpt-media-uploader">
                        <input type="text" name="image_url" id="image_url" class="regular-text" placeholder="Chưa chọn ảnh" readonly value="<?= esc_attr($edit_data->image_url ?? '') ?>">
                        <button type="button" class="button gpt-select-image" style="margin-top:16px;">Chọn ảnh</button>
                        <div class="gpt-preview" style="margin-top:10px;">
                            <?php if (!empty($edit_data->image_url)): ?>
                                <img src="<?= esc_url($edit_data->image_url) ?>" style="max-width: 150px; height: auto;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($edit_data): ?>
                <input type="hidden" name="edit_store_id" value="<?= $edit_data->id ?>">
                <button type="submit" class="button button-primary">Lưu thay đổi</button>
                <a href="?page=gpt-store-employee&tab=store" class="button button-danger">Huỷ</a>
                <?php else: ?>
                <button type="submit" name="add_store" class="button button-primary" style="width: 100%;">Lưu thông tin</button>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="gpt-table-container">
            <!-- Form tìm kiếm -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; padding: 16px;">
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="gpt-store-employee">
                    <input type="hidden" name="tab" value="store">
                    <div class="form-group" style="margin-bottom:0px;">
                        <input type="text" 
                           name="search" 
                           id="search-store" 
                           placeholder="🔍 Tìm kiếm theo tên cửa hàng..." 
                           value="<?= esc_attr($search) ?>">
                    </div>
                    <button type="submit" class="button button-primary">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                        <a href="?page=gpt-store-employee&tab=store" class="button button-danger">Xóa tìm kiếm</a>
                    <?php endif; ?>
                </form>
                
                <div style="text-align: right;">
                    <?php if (!empty($search)): ?>
                        <span style="color: #666;">Tìm thấy <strong><?= count($stores) ?></strong> cửa hàng</span>
                    <?php else: ?>
                        <span style="color: #666;">Tổng cộng: <strong><?= count($stores) ?></strong> cửa hàng</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="widefat striped" id="stores-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">ID</th>
                        <th>Tên cửa hàng</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th style="width: 60px;">Ảnh</th>
                        <th>Kênh</th>
                        <th>Nhà phân phối</th>
                        <th>Nhân viên</th>
                        <th style="width: 100px;">Ngày tạo</th>
                        <th style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stores as $store): ?>
                    <tr>
                        <td><?= esc_html($store->id) ?></td>
                        <td>
                            <strong>
                                <?php 
                                if (!empty($search)) {
                                    $store_name = $store->store_name;
                                    $highlighted = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="highlight-search">$1</span>', $store_name);
                                    echo $highlighted;
                                } else {
                                    echo esc_html($store->store_name);
                                }
                                ?>
                            </strong>
                        </td>
                        <td><?= esc_html($store->phone_number) ?></td>
                        <td title="<?= esc_attr($store->address) ?>">
                            <?php 
                            $address = $store->address;
                            echo esc_html(mb_strlen($address) > 30 ? mb_substr($address, 0, 30) . '...' : $address);
                            ?>
                        </td>
                        <td>
                            <?php if($store->image_url): ?>
                            <img src="<?= esc_url($store->image_url) ?>" width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                            <span style="color: #999; font-size: 12px;">Không có</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($store->channel_name): ?>
                                <span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                    <?= esc_html($store->channel_name) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($store->distributor_name): ?>
                                <span title="<?= esc_attr($store->distributor_name) ?>">
                                    <?php 
                                    $dist_name = $store->distributor_name;
                                    echo esc_html(mb_strlen($dist_name) > 20 ? mb_substr($dist_name, 0, 20) . '...' : $dist_name);
                                    ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($store->employee_name): ?>
                                <?php 
                                $position_labels = [
                                    'asm' => 'ASM',
                                    'pg' => 'PG', 
                                    'sale' => 'Sale'
                                ];
                                $position = $position_labels[$store->employee_position] ?? $store->employee_position;
                                $position_colors = [
                                    'asm' => '#d32f2f',
                                    'pg' => '#388e3c', 
                                    'sale' => '#1976d2'
                                ];
                                $color = $position_colors[$store->employee_position] ?? '#666';
                                ?>
                                <div style="font-size: 12px;">
                                    <span style="background: <?= $color ?>20; color: <?= $color ?>; padding: 2px 6px; border-radius: 3px; font-weight: 500;">
                                        <?= esc_html($position) ?>
                                    </span>
                                    <br>
                                    <span title="Mã NV: <?= esc_attr($store->employee_code) ?>">
                                        <?= esc_html($store->employee_name) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px;"><?= esc_html(date('d/m/Y', strtotime($store->created_at))) ?></td>
                        <td class="btn-actions">
                            <a href="?page=gpt-store-employee&tab=store&edit_id=<?= $store->id ?>" class="button button-small">Sửa</a>
                            <a href="?page=gpt-store-employee&tab=store&delete_id=<?= $store->id ?>" class="button button-small button-danger"
                                onclick="return confirm('Bạn chắc chắn muốn xoá cửa hàng này?')">Xoá</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($stores)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <?php if (!empty($search)): ?>
                        <p>🔍 Không tìm thấy cửa hàng nào với từ khóa "<strong><?= esc_html($search) ?></strong>"</p>
                        <a href="?page=gpt-store-employee&tab=store" class="button" style="margin-top: 10px;">Xem tất cả</a>
                    <?php else: ?>
                        <p>📭 Chưa có cửa hàng nào.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Custom styles cho bảng */
        #stores-table {
            font-size: 13px;
        }
        
        #stores-table th {
            background: #f5f5f5;
            font-weight: 600;
            padding: 12px 8px;
        }
        
        #stores-table td {
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        /* Highlight từ khóa tìm kiếm */
        <?php if (!empty($search)): ?>
        .highlight-search {
            background-color: #ffeb3b;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
        }
        <?php endif; ?>
        
        .btn-actions {
            white-space: nowrap;
        }
        
        .button-small {
            font-size: 12px;
            padding: 4px 8px;
            height: auto;
            line-height: 1.2;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        
        /* Style cho ô tìm kiếm */
        #search-store:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        /* Responsive */
        @media screen and (max-width: 1400px) {
            .gpt-admin-flex-layout {
                flex-direction: column;
            }
            
            .form-section {
                max-width: 100%;
                margin-bottom: 30px;
            }
        }
        
        @media screen and (max-width: 768px) {
            #search-store {
                width: 200px !important;
            }
        }
    </style>
    
<?php
}

// Thêm Select2 CSS và JS
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
});

add_action('admin_footer', function () {
    ?>
    <script>
        jQuery(document).ready(function($){
            // Khởi tạo Select2
            $('.gpt-select2').select2({
                width: '100%',
                placeholder: function(){
                    return $(this).data('placeholder') || $(this).find('option:first').text();
                }
            });

            // Auto-focus vào ô tìm kiếm nếu có
            <?php if (!empty($search)): ?>
                $('#search-store').focus().select();
            <?php endif; ?>
            
            // Xử lý enter key trong ô tìm kiếm
            $('#search-store').on('keypress', function(e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });

            // Xử lý upload ảnh
            let mediaUploader;

            $('.gpt-select-image').on('click', function(e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Chọn ảnh đại diện',
                    button: {
                        text: 'Chọn ảnh'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#image_url').val(attachment.url);
                    $('.gpt-preview').html('<img src="'+attachment.url+'" style="max-width: 150px; height: auto;">');
                });

                mediaUploader.open();
            });

            // Xử lý thay đổi kênh để load nhà phân phối
            $('#channel_id').on('change', function() {
                let channel_id = $(this).val();
                let distributorSelect = $('#distributor_id');
                
                if (channel_id) {
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'gpt_get_distributors_by_channel',
                            channel_id: channel_id
                        },
                        success: function(response) {
                            // Clear existing options
                            distributorSelect.empty().append('<option value="">-- Chọn nhà phân phối --</option>');
                            
                            if (response.success && response.data.length > 0) {
                                $.each(response.data, function(i, distributor) {
                                    distributorSelect.append(`<option value="${distributor.id}">${distributor.title}</option>`);
                                });
                            }
                            
                            // Refresh Select2
                            distributorSelect.trigger('change.select2');
                        },
                        error: function() {
                            distributorSelect.empty().append('<option value="">-- Lỗi khi tải dữ liệu --</option>');
                            distributorSelect.trigger('change.select2');
                        }
                    });
                } else {
                    distributorSelect.empty().append('<option value="">-- Chọn nhà phân phối --</option>');
                    distributorSelect.trigger('change.select2');
                }
            });

            <?php if (isset($edit_data) && $edit_data->channel_id): ?>
                // Delay một chút để đảm bảo select2 đã init xong
                setTimeout(function() {
                    $('#channel_id').trigger('change');
                }, 100);
            <?php endif; ?>
        });
    </script>
    <?php
});

add_action('wp_ajax_gpt_get_distributors_by_channel', 'gpt_get_distributors_by_channel');

function gpt_get_distributors_by_channel() {
    global $wpdb;
    $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;

    $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;

    if ($channel_id > 0) {
        $distributors = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM $table_distributors WHERE channel_id = %d ORDER BY title ASC",
            $channel_id
        ));

        if (!empty($distributors)) {
            wp_send_json_success($distributors);
        } else {
            wp_send_json_success([]);
        }
    } else {
        wp_send_json_error(['message' => 'Kênh không hợp lệ.']);
    }

    wp_die();
}