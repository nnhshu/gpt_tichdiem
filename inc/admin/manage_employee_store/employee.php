<?php

// Nhân viên
function gpt_render_employee_tab() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EMPLOYEES;

    if (isset($_POST['add_employee'])) {
        $wpdb->insert($table, [
            'code'       => sanitize_text_field($_POST['code']),
            'full_name'  => sanitize_text_field($_POST['full_name']),
            'position'   => sanitize_text_field($_POST['position']),
            'image_url'  => esc_url_raw($_POST['image_url']),
        ]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã thêm nhân viên mới.</p></div>';
    }

    if (isset($_GET['delete_id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete_id'])]);
        echo '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xoá nhân viên.</p></div>';
    }

    if (isset($_POST['edit_employee_id'])) {
        $wpdb->update($table, [
            'code'       => sanitize_text_field($_POST['code']),
            'full_name'  => sanitize_text_field($_POST['full_name']),
            'position'   => sanitize_text_field($_POST['position']),
            'image_url'  => esc_url_raw($_POST['image_url']),
        ], ['id' => intval($_POST['edit_employee_id'])]);

        echo '<div class="notice notice-success is-dismissible"><p>✏️ Đã cập nhật nhân viên.</p></div>';
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
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="code">Mã nhân viên:</label>
                    <input type="text" name="code" id="code" placeholder="Mã nhân viên" class="regular-text" required value="<?= esc_attr($edit_data->code ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Họ và tên:</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Họ tên nhân viên" class="regular-text" required value="<?= esc_attr($edit_data->full_name ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="position">Vị trí:</label>
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
                    <a href="?page=gpt-store-employee&tab=employee" class="button">Huỷ</a>
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
                            <td><?= esc_html($emp->code) ?></td>
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

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
});

add_action('admin_footer', function () {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#position').select2({
            width: '100%',
            placeholder: '-- Chọn vị trí --'
        });
    });
    </script>
    <?php
});