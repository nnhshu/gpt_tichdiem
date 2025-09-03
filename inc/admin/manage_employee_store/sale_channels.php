<?php
function gpt_render_sales_channels_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;

    // Kiểm tra quyền của user hiện tại
    $current_user = wp_get_current_user();
    $can_manage = current_user_can('manage_options') || in_array('quan_ly_kho', $current_user->roles);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
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

    if (isset($_GET['delete']) && $can_manage) {
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
        echo '<div class="updated"><p>Đã xoá thành công.</p></div>';
    }

    $edit = null;
    if (isset($_GET['edit'])) {
        $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
    }

    $list = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    ?>
    <div class="gpt-admin-flex-layout">
        <?php if ($can_manage): ?>
        <div class="form-section">
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($edit->id ?? 0); ?>">
                <div class="form-group">
                    <label for="gpt_box_session">Tên kênh:</label>
                    <input name="title" type="text" required value="<?php echo esc_attr($edit->title ?? ''); ?>" class="regular-text" />
                </div>
                <div class="form-group">
                    <label for="gpt_box_session">Mã kênh:</label>
                    <input name="channel_code" type="text" required value="<?php echo esc_attr($edit->channel_code ?? ''); ?>" class="regular-text" />
                </div>
                <input type="submit" class="button button-primary" value="<?php echo $edit ? 'Cập nhật' : 'Lưu thông tin'; ?>" style="width: 100%;">
            </form>
        </div>
        <?php else: ?>
        <div class="form-section">
            <div class="notice notice-warning">
                <p>Bạn không có quyền thêm/sửa dữ liệu.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="gpt-table-container">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Mã kênh</th>
                        <th>Ngày tạo</th>
                        <?php if ($can_manage): ?>
                        <th>Hành động</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list): foreach ($list as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo esc_html($item->channel_code); ?></td>
                            <td><?php echo esc_html($item->created_at); ?></td>
                            <?php if ($can_manage): ?>
                            <td>
                                <div class="btn-actions">
                                    <a class="button button-edit" href="?page=gpt-store-employee&tab=channels&edit=<?php echo $item->id; ?>">Sửa</a>
                                    <a class="button button-danger" onclick="return confirm('Bạn có chắc chắn xoá?')" href="?page=gpt-store-employee&tab=channels&delete=<?php echo $item->id; ?>">Xoá</a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="<?php echo $can_manage ? '5' : '4'; ?>">Không có dữ liệu.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    echo '</div>';
}