<?php

function register_import_check_post_type() {
    register_post_type('import_check', array(
        'labels' => array(
            'name' => 'Định danh thùng hàng',
            'singular_name' => 'Danh sách thùng hàng',
            'add_new' => 'Thêm thùng hàng',
            'add_new_item' => 'Thêm thùng hàng',
            'edit_item' => 'Chỉnh sửa thùng hàng',
            'new_item' => 'Thêm thùng hàng',
            'view_item' => 'Xem mã định danh trong thùng hàng',
            'search_items' => 'Tìm thùng hàng',
            'not_found' => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác'
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'gpt-manager-tem',
        'supports' => array('title'),
        'has_archive' => true,
    ));
}
add_action('init', 'register_import_check_post_type');

function gpt_render_import_check_tab() {
    $args = array(
        'post_type'      => 'import_check',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);

    echo '<div class="wrap">';
    echo '<h2>📦 Danh sách nhập thùng</h2>';
    echo '<p><a href="' . admin_url('post-new.php?post_type=import_check') . '" class="button button-primary">+ Thêm nhập hàng mới</a></p>';

    if ($query->have_posts()) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
                <th>Tiêu đề</th>
                <th>Ngày tạo</th>
                <th>Người tạo</th>
                <th>Thao tác</th>
              </tr></thead>';
        echo '<tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<tr>';
            echo '<td><strong><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a></strong></td>';
            echo '<td>' . get_the_date() . '</td>';
            echo '<td>' . get_the_author() . '</td>';
            echo '<td><a href="' . get_edit_post_link(get_the_ID()) . '" class="button small">Sửa</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>📭 Không có nhập hàng nào.</p>';
    }

    wp_reset_postdata();
    echo '</div>';
}


function add_import_check_metaboxes() {
    add_meta_box('import_check_fields', 'Thông tin nhập hàng', 'render_import_check_fields', 'import_check', 'normal', 'default');
    add_meta_box('import_check_products_box', 'Danh sách sản phẩm nhập vào thùng', 'display_import_check_products_box', 'import_check', 'normal', 'high');
    add_meta_box('import_status_box', 'Trạng thái đơn hàng', 'render_import_status_box', 'import_check', 'side');
    add_meta_box('import_logs_box', 'Lịch sử trạng thái đơn', 'render_import_logs_box', 'import_check', 'side');
    add_meta_box(
        'import_logs_metabox',
        'Nhật ký nhập hàng cho thùng',
        'display_import_logs_metabox',
        'import_check',
        'normal'
    );
}
add_action('add_meta_boxes', 'add_import_check_metaboxes');

function render_import_check_fields($post) {
    $order_id = get_post_meta($post->ID, 'order_id', true);
    $import_images = get_post_meta($post->ID, 'import_images', true);
    $macao_ids = get_post_meta($post->ID, 'macao_ids', true);
    $import_date = get_post_meta($post->ID, 'import_date', true);
    $order_import_by = get_post_meta($post->ID, 'order_import_by', true);

    wp_nonce_field('save_import_check_fields', 'order_check_nonce');

    $import_date = get_post_meta($post->ID, 'import_date', true);
    if (empty($import_date)) {
        $import_date = current_time('mysql');
    }
    ?>
    <style>
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            margin-bottom: 8px;
            display: block;
        }
    </style>
    <div class="form-group">
        <label for="order_id">ID Đơn hàng:</label>
        <input type="text" name="order_id" id="order_id" value="<?php echo esc_attr($order_id); ?>" style="width:100%;">
    </div>
    <div class="form-group">
        <label for="import_date">Ngày giờ nhập:</label>
        <input type="datetime-local" name="import_date" id="import_date"
            value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($import_date))); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_import_by">Người nhập kho:</label>
        <input type="text" name="order_import_by" id="order_import_by" value="<?php echo esc_attr($order_import_by); ?>" style="width:100%;">
    </div>
    <div class="form-group">
        <label for="import_images">Ảnh đơn hàng (có thể chọn nhiều):</label>
        <input type="hidden" name="import_images" id="import_images" value="<?php echo esc_attr($import_images); ?>">
        <button type="button" class="button upload_gallery_button">Chọn ảnh</button>
        <div id="order_images_preview" style="margin-top:10px;">
            <?php
            if (!empty($import_images)) {
                $image_urls = explode(',', $import_images);
                foreach ($image_urls as $img) {
                    echo '<img src="' . esc_url($img) . '" style="max-width:100px;margin:5px;border:1px solid #ddd;">';
                }
            }
            ?>
        </div>
    </div>

    <?php
        $logs = get_post_meta($post->ID, '_inventory_logs', true);
        if (!empty($logs)) {
            echo '<div style="background:#f9f9f9;padding:10px;margin-top:20px;border:1px solid #ddd;">';
            echo '<h4>Lịch sử cập nhật tồn kho:</h4><ul>';
            foreach ($logs as $log) {
                echo '<li>' . esc_html($log) . '</li>';
            }
            echo '</ul></div>';
        }
    ?>

    <script>
        jQuery(document).ready(function($){
            $('.upload_gallery_button').click(function(e){
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'Chọn ảnh đơn hàng',
                    button: {
                        text: 'Chọn ảnh'
                    },
                    multiple: true
                }).on('select', function() {
                    var attachment_urls = [];
                    var preview_html = '';
                    custom_uploader.state().get('selection').each(function(file){
                        var url = file.toJSON().url;
                        attachment_urls.push(url);
                        preview_html += '<img src="' + url + '" style="max-width:100px;margin:5px;border:1px solid #ddd;">';
                    });
                    $('#import_images').val(attachment_urls.join(','));
                    $('#order_images_preview').html(preview_html);
                }).open();
            });
        });
    </script>

    <?php
}

function import_update_post_meta_if_changed($post_id, $key, $new_value) {
    $old_value = get_post_meta($post_id, $key, true);
    if ($new_value !== $old_value) {
        update_post_meta($post_id, $key, $new_value);
    }
}

function save_import_check_fields($post_id) {
    if (!isset($_POST['order_check_nonce']) || !wp_verify_nonce($_POST['order_check_nonce'], 'save_import_check_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    global $wpdb;
    $order_table   = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;
    $macao_table   = BIZGPT_PLUGIN_WP_BARCODE;
    $box_table   = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    import_update_post_meta_if_changed($post_id, 'order_id', sanitize_text_field($_POST['order_id']));
    import_update_post_meta_if_changed($post_id, 'import_images', sanitize_text_field($_POST['import_images']));
    import_update_post_meta_if_changed($post_id, 'import_date', sanitize_text_field($_POST['import_date']));
    import_update_post_meta_if_changed($post_id, 'order_import_by', sanitize_text_field($_POST['order_import_by']));
    import_update_post_meta_if_changed($post_id, 'import_check_box_manager', sanitize_text_field($_POST['import_check_box_manager']));
    
    $import_check_products = sanitize_text_field($_POST['import_check_products'] ?? '');
    if ($import_check_products) {
        update_post_meta($post_id, '_import_check_products', $import_check_products);
    }

    $codes = explode(' ', $import_check_products);
    $codes = array_map('trim', $codes);

    $logs = get_post_meta($post_id, '_import_logs', true);
    if (!is_array($logs)) {
        $logs = [];
    }

    $processed_codes = get_post_meta($post_id, '_processed_codes', true);
    if (!is_array($processed_codes)) {
        $processed_codes = [];
    }

    $list_barcode = implode(',', $codes);
    $wpdb->update(
        $box_table,
        [
            'list_barcode' => $list_barcode,
            'order_id' => $post_id
        ],
        ['barcode' => sanitize_text_field($_POST['import_check_box_manager'])]
    );

    foreach ($codes as $code) {
        $code = trim($code);

        if (in_array($code, $processed_codes)) {
            $logs[] = [
                'status' => sprintf("[%s]: Mã đã được xử lý", $code),
                'timestamp' => current_time('mysql')
            ];
            continue;
        }

        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $macao_table WHERE barcode = %s", $code));
        if ($result) {
            $wpdb->update(
                $macao_table,
                [
                    'box_barcode' => sanitize_text_field($_POST['import_check_box_manager'])
                ],
                ['barcode' => $code]
            );
            $logs[] = [
                'status' => sprintf("[%s] ✅ Mã %s đã được xử lý và lưu vào box #%d", current_time('mysql'), $code, $post_id),
                'timestamp' => current_time('mysql')
            ];

            $processed_codes[] = $code;
        } else {
            $logs[] = [
                'status' => sprintf("[%s] ❌ Không tìm thấy mã %s trong bảng barcode", current_time('mysql'), $code),
                'timestamp' => current_time('mysql')
            ];
        }
    }

    update_post_meta($post_id, '_import_logs', $logs);
    update_post_meta($post_id, '_processed_codes', $processed_codes);
}
add_action('save_post', 'save_import_check_fields');

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    }
});

function display_import_logs_metabox($post) {
    // Lấy logs từ post_meta
    $logs = get_post_meta($post->ID, '_import_logs', true);

    // Nếu không có logs, hiển thị thông báo
    if (empty($logs)) {
        echo '<p>No logs available.</p>';
        return;
    }

    // Hiển thị logs
    echo '<ul>';
    foreach ($logs as $log) {
        // Kiểm tra sự tồn tại của các khóa 'timestamp' và 'status'
        $timestamp = isset($log['timestamp']) ? esc_html($log['timestamp']) : 'N/A';
        $status = isset($log['status']) ? esc_html($log['status']) : 'Unknown';

        echo '<li>' . $timestamp . ' - ' . $status . '</li>';
    }
    echo '</ul>';
}

function render_import_status_box($post) {
    $current_status = get_post_meta($post->ID, 'import_status', true);
    $statuses = ['Mới', 'Xử lý', 'Đóng gói', 'Giao', 'Hoàn hàng'];

    echo '<select name="import_status">';
    foreach ($statuses as $status) {
        echo '<option value="' . esc_attr($status) . '" ' . selected($current_status, $status, false) . '>' . esc_html($status) . '</option>';
    }
    echo '</select>';
}

function render_import_logs_box($post) {
    $logs = get_post_meta($post->ID, 'import_status_logs', true);
    if (!is_array($logs) || empty($logs)) {
        echo '<p>Chưa có log nào.</p>';
        return;
    }

    echo '<ul>';
    foreach ($logs as $log) {
        echo '<li>' . esc_html($log['status']) . ' - <em>' . esc_html($log['timestamp']) . '</em></li>';
    }
    echo '</ul>';
}

function display_import_check_products_box($post) {
    $import_check_products = get_post_meta($post->ID, '_import_check_products', true);
    $import_check_box_manager = get_post_meta($post->ID, 'import_check_box_manager', true);

    if (!empty($import_check_products)) {
        $import_check_products = implode("\n", preg_split('/[\s,;]+/', $import_check_products));
    }
    ?>
    <div class="form-group">
        <label for="import_check_box_manager">Nhập mã định danh của thùng</label>
        <input name="import_check_box_manager" type="text" id="import_check_box_manager" style="width:100%;" value="<?php echo esc_attr($import_check_box_manager); ?>" />
    </div>
    <div class="form-group">
        <label for="import_check_products">Nhập mã định danh sản phẩm. (Mỗi mã định danh là 1 dòng):</label>
        <textarea name="import_check_products" id="import_check_products" rows="5" style="width:100%;"><?php echo esc_textarea($import_check_products); ?></textarea>
    </div>
   <?php
}


// Product
add_action('save_post_product', function($post_id) {
    if (get_post_type($post_id) !== 'product') return;

    $existing_id = get_post_meta($post_id, 'custom_prod_id', true);
    if (empty($existing_id)) {
        $assigned_ids = get_posts([
            'post_type' => 'product',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => 'custom_prod_id',
            'meta_compare' => 'EXISTS',
        ]);

        $used_ids = array_map(function($id) {
            return get_post_meta($id, 'custom_prod_id', true);
        }, $assigned_ids);

        for ($i = 1; $i <= 99; $i++) {
            $formatted = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!in_array($formatted, $used_ids)) {
                update_post_meta($post_id, 'custom_prod_id', $formatted);
                break;
            }
        }
    }
});

add_filter('manage_edit-product_columns', function($columns) {
    $columns['custom_prod_id'] = 'Mã SP';
    return $columns;
});

add_action('manage_product_posts_custom_column', function($column, $post_id) {
    if ($column === 'custom_prod_id') {
        echo esc_html(get_post_meta($post_id, 'custom_prod_id', true));
    }
}, 10, 2);



