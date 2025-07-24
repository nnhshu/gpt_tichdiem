<?php

function gpt_render_store_tab() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_STORE_LIST;
    $table_channel = BIZGPT_PLUGIN_WP_CHANNELS;
    $channels = $wpdb->get_results("SELECT id, title FROM $table_channel ORDER BY title ASC");
    if (isset($_POST['add_store'])) {
        $wpdb->insert($table, [
            'store_name' => sanitize_text_field($_POST['store_name']),
            'address'    => sanitize_text_field($_POST['address']),
            'image_url'  => esc_url_raw($_POST['image_url']),
            'channel_id' => intval($_POST['channel_id']),
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
            'image_url'  => esc_url_raw($_POST['image_url']),
            'channel_id' => intval($_POST['channel_id']),
            'distributor_id' => intval($_POST['distributor_id']),
        ], ['id' => intval($_POST['edit_store_id'])]);

        echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật cửa hàng.</p></div>';
    }

    $stores = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

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
                    <label for="gpt_box_session">Tên cửa hàng:</label>
                    <input type="text" name="store_name" placeholder="Tên cửa hàng" class="regular-text" required
                    value="<?= esc_attr($edit_data->store_name ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="gpt_box_session">Địa chỉ:</label>
                    <input type="text" name="address" placeholder="Địa chỉ" class="regular-text" required
                    value="<?= esc_attr($edit_data->address ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="channel_id">Kênh bán hàng:</label>
                    <select name="channel_id" id="channel_id" required>
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
                    <select name="distributor_id" id="distributor_id" required>
                        <option value="">-- Chọn nhà phân phối --</option>
                        <?php
                        $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;
                        $selected_distributor = isset($edit_data->distributor_id) ? $edit_data->distributor_id : '';
                        $distributors = $wpdb->get_results($wpdb->prepare("SELECT id, title FROM $table_distributors WHERE channel_id = %d ORDER BY title ASC", $edit_data->channel_id));
                        foreach ($distributors as $distributor):
                            $selected = $distributor->id == $selected_distributor ? 'selected' : '';
                            echo '<option value="' . esc_attr($distributor->id) . '" ' . $selected . '>' . esc_html($distributor->title) . '</option>';
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gpt_box_session">Ảnh đại diện:</label>
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
                <button type="submit" name="add_store" class="button button-primary">Thêm cửa hàng</button>
                <?php endif; ?>
            </form>
        </div>
        <div class="gpt-table-container">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
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
                        <td><?= esc_html($store->address) ?></td>
                        <td><img src="<?= esc_url($store->image_url) ?>" width="60" height="60" style="object-fit: cover;"></td>
                        <td><?= esc_html($store->channel_id) ?></td>
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

add_action('admin_footer', function () {
    ?>
    <script>
        jQuery(document).ready(function($){
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

            jQuery(document).ready(function($) {
                $('#channel_id').on('change', function() {
                    let channel_id = $(this).val();
                    if (channel_id) {
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'gpt_get_distributors_by_channel',
                                channel_id: channel_id
                            },
                            success: function(response) {
                                if (response.success) {
                                    let distributors = response.data;
                                    $('#distributor_id').html('<option value="">-- Chọn nhà phân phối --</option>');
                                    $.each(distributors, function(i, distributor) {
                                        $('#distributor_id').append(`<option value="${distributor.id}">${distributor.title}</option>`);
                                    });
                                } else {
                                    $('#distributor_id').html('<option value="">Không có nhà phân phối nào</option>');
                                }
                            }
                        });
                    } else {
                        $('#distributor_id').html('<option value="">-- Chọn nhà phân phối --</option>');
                    }
                });
            });

        });
    </script>
    <?php
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
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
            wp_send_json_error(['message' => 'Không có nhà phân phối cho kênh này.']);
        }
    } else {
        wp_send_json_error(['message' => 'Kênh không hợp lệ.']);
    }

    wp_die();
}
