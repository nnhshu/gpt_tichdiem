<?php

function gpt_render_store_tab() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_channel = BIZGPT_PLUGIN_WP_CHANNELS;
    $table_employees = BIZGPT_PLUGIN_WP_EMPLOYEES;

    $channels = $wpdb->get_results("SELECT id, title FROM $table_channel ORDER BY title ASC");
    $employees = $wpdb->get_results("SELECT * FROM $table_employees");

    $stores = $wpdb->get_results("SELECT * FROM $table");

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
                    <label for="store_name">Tên cửa hàng:</label>
                    <input type="text" name="store_name" id="store_name" placeholder="Tên cửa hàng" class="regular-text" required
                    value="<?= esc_attr($edit_data->store_name ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Số điện thoại:</label>
                    <input type="text" name="phone_number" id="phone_number" placeholder="Số điện thoại" class="regular-text" required
                    value="<?= esc_attr($edit_data->phone_number ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Địa chỉ:</label>
                    <input type="text" name="address" id="address" placeholder="Địa chỉ" class="regular-text" required
                    value="<?= esc_attr($edit_data->address ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="channel_id">Kênh bán hàng:</label>
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
                                $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;
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
                    <label for="employee_id">Nhân viên:</label>
                    <select name="employee_id" id="employee_id" class="gpt-select2">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php
                             $selected_employee = isset($edit_data->employee_id) ? $edit_data->employee_id : '';
                            foreach ($employees as $employee):
                                $selected = $employee->id == $selected_employee ? 'selected' : '';
                                echo '<option value="' . esc_attr($employee->id) . '" ' . $selected . '>'.'['. esc_html($employee->position). '] - '. esc_html($employee->full_name) . '</option>';
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
                <a href="?page=gpt-store-employee&tab=store" class="button">Huỷ</a>
                <?php else: ?>
                <button type="submit" name="add_store" class="button button-primary" style="width: 100%;">Lưu thông tin</button>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="gpt-table-container">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Ảnh</th>
                        <th>Kênh</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stores as $store): ?>
                    <tr>
                        <td><?= esc_html($store->id) ?></td>
                        <td><?= esc_html($store->store_name) ?></td>
                         <td><?= esc_html($store->phone_number) ?></td>
                        <td><?= esc_html($store->address) ?></td>
                        <td>
                            <?php if($store->image_url): ?>
                            <img src="<?= esc_url($store->image_url) ?>" width="60" height="60" style="object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td><?= esc_html(gpt_get_channel_name($store->channel_id)) ?></td>
                        <td><?= esc_html($store->created_at) ?></td>
                        <td class="btn-actions">
                            <a href="?page=gpt-store-employee&tab=store&edit_id=<?= $store->id ?>" class="button">Sửa</a>
                            <a href="?page=gpt-store-employee&tab=store&delete_id=<?= $store->id ?>" class="button button-danger"
                                onclick="return confirm('Bạn chắc chắn muốn xoá cửa hàng này?')">Xoá</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
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
                $('#channel_id').trigger('change');
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