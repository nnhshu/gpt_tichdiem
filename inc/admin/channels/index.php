<?php 

function gpt_render_sales_channels_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;

    // Xử lý form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize_text_field($_POST['title']);
        $channel_code = sanitize_text_field($_POST['channel_code']);

        if ($id > 0) {
            $wpdb->update($table, ['title' => $title, 'channel_code' => $channel_code], ['id' => $id]);
        } else {
            $wpdb->insert($table, ['title' => $title, 'channel_code' => $channel_code]);
        }

        echo '<div class="updated"><p>Lưu thành công.</p></div>';
    }

    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
        echo '<div class="updated"><p>Đã xoá thành công.</p></div>';
    }

    $edit = null;
    if (isset($_GET['edit'])) {
        $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
    }

    $list = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    echo '<div class="wrap"><h2>Kênh bán hàng</h2>';

    ?>
    <form method="post" style="margin-bottom: 20px;">
        <input type="hidden" name="id" value="<?php echo esc_attr($edit->id ?? 0); ?>">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="title">Tiêu đề</label></th>
                <td><input name="title" type="text" required value="<?php echo esc_attr($edit->title ?? ''); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="channel_code">Mã kênh</label></th>
                <td><input name="channel_code" type="text" required value="<?php echo esc_attr($edit->channel_code ?? ''); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <p><input type="submit" class="button button-primary" value="<?php echo $edit ? 'Cập nhật' : 'Thêm mới'; ?>"></p>
    </form>

    <hr>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tiêu đề</th>
                <th>Mã kênh</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($list): foreach ($list as $item): ?>
                <tr>
                    <td><?php echo $item->id; ?></td>
                    <td><?php echo esc_html($item->title); ?></td>
                    <td><?php echo esc_html($item->channel_code); ?></td>
                    <td><?php echo esc_html($item->created_at); ?></td>
                    <td>
                        <a class="button" href="?page=gpt-config&tab=kenh-ban-hang&edit=<?php echo $item->id; ?>">Sửa</a>
                        <a class="button button-danger" onclick="return confirm('Bạn có chắc chắn xoá?')" href="?page=gpt-config&tab=kenh-ban-hang&delete=<?php echo $item->id; ?>">Xoá</a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5">Không có dữ liệu.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    echo '</div>';
}
