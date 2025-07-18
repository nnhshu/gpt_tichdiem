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
            <form method="post" enctype="multipart/form-data">
                <input type="text" name="store_name" placeholder="Tên cửa hàng" class="regular-text" required
                    value="<?= esc_attr($edit_data->store_name ?? '') ?>">
                <input type="text" name="address" placeholder="Địa chỉ" class="regular-text" required
                    value="<?= esc_attr($edit_data->address ?? '') ?>">
                <div class="gpt-media-uploader">
                    <input type="text" name="image_url" id="image_url" class="regular-text" placeholder="Chưa chọn ảnh" readonly value="<?= esc_attr($edit_data->image_url ?? '') ?>">
                    <button type="button" class="button gpt-select-image">Chọn ảnh</button>
                    <div class="gpt-preview" style="margin-top:10px;">
                        <?php if (!empty($edit_data->image_url)): ?>
                            <img src="<?= esc_url($edit_data->image_url) ?>" style="max-width: 150px; height: auto;">
                        <?php endif; ?>
                    </div>
                </div>
                <select name="channel_id" required>
                    <option value="">-- Chọn kênh bán hàng --</option>
                    <?php foreach ($channels as $channel): ?>
                        <option value="<?= esc_attr($channel->id) ?>"
                            <?= isset($edit_data) && $edit_data->channel_id == $channel->id ? 'selected' : '' ?>>
                            <?= esc_html($channel->title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

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
        });
    </script>
    <?php
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
});