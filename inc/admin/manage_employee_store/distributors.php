<?php

// Nhà phân phối
function gpt_render_distributors_tab() {
    global $wpdb;
    $table_channel = BIZGPT_PLUGIN_WP_CHANNELS;
    $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;

    $channels = $wpdb->get_results("SELECT id, title FROM $table_channel ORDER BY title ASC");

    if (isset($_POST['add_distributor'])) {
        $channel_id = intval($_POST['channel_id']);

        $wpdb->insert($table_distributors, [
            'title'  => sanitize_text_field($_POST['full_name']),
            'channel_id' => $channel_id,
        ]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã thêm nhà phân phối mới.</p></div>';
    }

    if (isset($_GET['delete_id'])) {
        $wpdb->delete($table_distributors, ['id' => intval($_GET['delete_id'])]);
        echo '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xoá nhà phân phối.</p></div>';
    }

    if (isset($_POST['edit_distributor_id'])) {
        $channel_id = intval($_POST['channel_id']);

        $wpdb->update($table_distributors, [
            'title'  => sanitize_text_field($_POST['full_name']),
            'channel_id' => $channel_id,
        ], ['id' => intval($_POST['edit_distributor_id'])]);

        echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật nhà phân phối.</p></div>';
    }

    $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 1;
    $offset   = ($paged - 1) * $per_page;
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_distributors");

    $distributors = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_distributors ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));

    $total_pages = ceil($total_items / $per_page);

    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $edit_data = $wpdb->get_row("SELECT * FROM $table_distributors WHERE id = " . intval($_GET['edit_id']));
    }

    ?>
    <div class="gpt-admin-flex-layout">
        <div class="form-section">
            <h3><?= $edit_data ? 'Chỉnh sửa nhà phân phối' : 'Thêm nhà phân phối mới' ?></h3>
            <hr>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="gpt_box_session">Tên nhà phân phối:</label>
                    <input type="text" name="full_name" placeholder="Tên nhà phân phối" class="regular-text" required value="<?= esc_attr($edit_data->title ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="gpt_box_session">Chọn kênh:</label>
                    <select name="channel_id" id="store_id_select" class="gpt-select2" required>
                        <option value="">-- Chọn kênh --</option>
                        <?php foreach ($channels as $channel): ?>
                            <option value="<?= esc_attr($channel->id) ?>" <?= isset($edit_data) && $edit_data->channel_id == $channel->id ? 'selected' : '' ?>>
                                <?= esc_html($channel->title) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($edit_data): ?>
                    <input type="hidden" name="edit_distributor_id" value="<?= $edit_data->id ?>">
                    <button type="submit" class="button button-primary">Lưu thay đổi</button>
                    <a href="?page=gpt-store-employee&tab=distributor" class="button">Huỷ</a>
                <?php else: ?>
                    <button type="submit" name="add_distributor" class="button button-primary" style="width: 100%;">Thêm nhà phân phối</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="gpt-table-container">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Kênh</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distributors as $dis): ?>
                        <tr>
                            <td><?= esc_html($dis->id) ?></td>
                            <td><?= esc_html($dis->title) ?></td>
                            <td><?= esc_html(gpt_get_channel_name($dis->channel_id)) ?></td>
                            <td><?= esc_html($dis->created_at) ?></td>
                            <td>
                                <a href="?page=gpt-store-employee&tab=distributor&edit_id=<?= $dis->id ?>" class="button">Sửa</a>
                                <a href="?page=gpt-store-employee&tab=distributor&delete_id=<?= $dis->id ?>" class="button button-danger" onclick="return confirm('Bạn chắc chắn muốn xoá nhân viên này?')">Xoá</a>
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

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
});

add_action('admin_footer', function () {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#store_id_select').select2({
            width: '100%',
            placeholder: "-- Chọn cửa hàng --"
        });
    });
    </script>
    <?php
});