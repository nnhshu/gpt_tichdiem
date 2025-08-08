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
    add_meta_box('import_check_fields', 'Thông tin thùng', 'render_import_check_fields', 'import_check', 'normal', 'default');
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
    // $order_id = get_post_meta($post->ID, 'order_id', true);
    $import_images = get_post_meta($post->ID, 'import_images', true);
    $macao_ids = get_post_meta($post->ID, 'macao_ids', true);
    $import_date = get_post_meta($post->ID, 'import_date', true);
    $current_user = wp_get_current_user();
    $order_import_by = $current_user->user_login;

    $order_import_by_meta = get_post_meta($post->ID, 'order_import_by', true);
    if (!empty($order_import_by_meta)) {
        $order_import_by = $order_import_by_meta;
    }

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
    <!-- <div class="form-group">
        <label for="order_id">ID Đơn hàng:</label>
        <input type="text" name="order_id" id="order_id" value="<?php echo esc_attr($order_id); ?>" style="width:100%;">
    </div> -->
    <div class="form-group">
        <label for="import_date">Ngày giờ nhập:</label>
        <input type="datetime-local" name="import_date" id="import_date"
            value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($import_date))); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_import_by">Người nhập kho:</label>
        <input type="text" name="order_import_by" id="order_import_by" value="<?php echo esc_attr($order_import_by); ?>" style="width:100%;" disabled>
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
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    $box_table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    $current_user = wp_get_current_user();
    $order_import_by = $current_user->user_login;

    // Cập nhật các meta fields cơ bản (không phụ thuộc vào trạng thái)
    import_update_post_meta_if_changed($post_id, 'import_images', sanitize_text_field($_POST['import_images']));
    import_update_post_meta_if_changed($post_id, 'import_date', sanitize_text_field($_POST['import_date']));
    import_update_post_meta_if_changed($post_id, 'order_import_by', sanitize_text_field($_POST['order_import_by']) ?  sanitize_text_field($_POST['order_import_by']) : $order_import_by);

    // Xử lý trạng thái import trước
    $old_status = get_post_meta($post_id, 'import_status', true);
    $new_status = isset($_POST['import_status']) ? sanitize_text_field($_POST['import_status']) : $old_status;
    
    // Cập nhật trạng thái nếu có thay đổi
    if ($new_status !== $old_status) {
        update_post_meta($post_id, 'import_status', $new_status);
        $status_logs = get_post_meta($post_id, 'import_status_logs', true);
        if (!is_array($status_logs)) $status_logs = [];
        $status_logs[] = [
            'status' => $new_status, 
            'timestamp' => current_time('mysql'),
            'user' => $current_user->display_name
        ];
        update_post_meta($post_id, 'import_status_logs', $status_logs);
    }

    // Luôn lưu thông tin boxes vào post meta (để không mất dữ liệu)
    $boxes = $_POST['import_check_boxes'] ?? [];
    $existing_boxes = get_post_meta($post_id, '_import_check_boxes', true);
    
    if ($boxes !== $existing_boxes) {
        update_post_meta($post_id, '_import_check_boxes', $boxes);
    }

    // Kiểm tra trạng thái: chỉ cập nhật SQL khi trạng thái là "completed"
    if ($new_status !== 'completed') {
        // Nếu không phải trạng thái "completed", chỉ lưu vào post meta và dừng lại
        $logs = get_post_meta($post_id, '_import_logs', true);
        if (!is_array($logs)) $logs = [];
        
        $timestamp = current_time('mysql');
        $logs[] = [
            'status' => sprintf("[%s] ⏳ Trạng thái: %s - Chưa cập nhật vào database", 
                               $timestamp, 
                               $new_status === 'pending' ? 'Chờ duyệt' : ucfirst($new_status)),
            'timestamp' => $timestamp
        ];
        
        update_post_meta($post_id, '_import_logs', $logs);
        return; // Dừng lại, không cập nhật SQL
    }

    // Chỉ thực hiện cập nhật SQL khi trạng thái là "completed"
    if ($boxes !== $existing_boxes || $old_status !== 'completed') {
        $logs = get_post_meta($post_id, '_import_logs', true);
        if (!is_array($logs)) $logs = [];

        $timestamp = current_time('mysql');
        $logs[] = [
            'status' => sprintf("[%s] ✅ Bắt đầu cập nhật database - Trạng thái: Hoàn thành", $timestamp),
            'timestamp' => $timestamp
        ];

        foreach ($boxes as $box) {
            $box_code = sanitize_text_field($box['box_code']);
            $product_quantity = intval($box['product_quantity']);
            $product_codes = sanitize_textarea_field($box['product_codes']);
            $lot_date = sanitize_textarea_field($box['lot_date']);

            if (empty($box_code) || empty($product_codes)) {
                $logs[] = [
                    'status' => sprintf("[%s] ⚠️ Bỏ qua thùng thiếu thông tin: %s", $timestamp, $box_code ?: 'Không có mã thùng'),
                    'timestamp' => $timestamp
                ];
                continue;
            }

            // Tách danh sách mã sản phẩm
            $codes = array_filter(array_map('trim', explode("\n", $product_codes)));

            // Kiểm tra thùng đã tồn tại trong database chưa
            $existing_box = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $box_table WHERE barcode = %s",
                $box_code
            ));

            $list_barcode = implode(',', $codes);

            if ($existing_box) {
                // Cập nhật thùng hiện có
                $update_result = $wpdb->update(
                    $box_table,
                    [
                        'list_barcode' => $list_barcode,
                        'order_id' => $post_id,
                        'status' => 'imported',
                        'updated_at' => $timestamp
                    ],
                    ['barcode' => $box_code]
                );

                if ($update_result !== false) {
                    $logs[] = [
                        'status' => sprintf("[%s] 📦 Cập nhật thùng [%s] với %d mã sản phẩm", $timestamp, $box_code, count($codes)),
                        'timestamp' => $timestamp
                    ];
                } else {
                    $logs[] = [
                        'status' => sprintf("[%s] ❌ Lỗi cập nhật thùng [%s]: %s", $timestamp, $box_code, $wpdb->last_error),
                        'timestamp' => $timestamp
                    ];
                }
            } else {
                // Thêm thùng mới
                $insert_result = $wpdb->insert($box_table, [
                    'barcode' => $box_code,
                    'list_barcode' => $list_barcode,
                    'order_id' => $post_id,
                    'status' => 'imported',
                    'created_at' => $timestamp
                ]);

                if ($insert_result !== false) {
                    $logs[] = [
                        'status' => sprintf("[%s] ✅ Tạo mới thùng [%s] với %d mã sản phẩm", $timestamp, $box_code, count($codes)),
                        'timestamp' => $timestamp
                    ];
                } else {
                    $logs[] = [
                        'status' => sprintf("[%s] ❌ Lỗi tạo thùng [%s]: %s", $timestamp, $box_code, $wpdb->last_error),
                        'timestamp' => $timestamp
                    ];
                }
            }

            // Cập nhật bảng barcode cho từng mã sản phẩm
            $successful_codes = 0;
            $failed_codes = 0;

            foreach ($codes as $code) {
                $code = trim($code);
                if (empty($code)) continue;

                // Kiểm tra mã sản phẩm có tồn tại không
                $existing_barcode = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $barcode_table WHERE barcode = %s",
                    $code
                ));

                if ($existing_barcode) {
                    $update_barcode_result = $wpdb->update(
                        $barcode_table,
                        [
                            'box_barcode' => $box_code,
                            'product_date' => $lot_date,
                        ],
                        ['barcode' => $code]
                    );

                    if ($update_barcode_result !== false) {
                        $successful_codes++;
                    } else {
                        $failed_codes++;
                        $logs[] = [
                            'status' => sprintf("[%s] ❌ Lỗi cập nhật mã %s: %s", $timestamp, $code, $wpdb->last_error),
                            'timestamp' => $timestamp
                        ];
                    }
                } else {
                    $failed_codes++;
                    $logs[] = [
                        'status' => sprintf("[%s] ❌ Không tìm thấy mã %s trong hệ thống", $timestamp, $code),
                        'timestamp' => $timestamp
                    ];
                }
            }

            // Log tổng kết cho thùng này
            if ($successful_codes > 0) {
                $logs[] = [
                    'status' => sprintf("[%s] ✅ Thành công: %d mã được gán vào thùng %s", $timestamp, $successful_codes, $box_code),
                    'timestamp' => $timestamp
                ];
            }

            if ($failed_codes > 0) {
                $logs[] = [
                    'status' => sprintf("[%s] ⚠️ Thất bại: %d mã không thể xử lý cho thùng %s", $timestamp, $failed_codes, $box_code),
                    'timestamp' => $timestamp
                ];
            }
        }

        $logs[] = [
            'status' => sprintf("[%s] 🎉 Hoàn tất cập nhật database cho tất cả thùng hàng", $timestamp),
            'timestamp' => $timestamp
        ];

        update_post_meta($post_id, '_import_logs', $logs);
    }
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
    $current_user = wp_get_current_user();
    $all_statuses = [
        'pending' => 'Chờ duyệt',
        'completed' => 'Hoàn thành'
    ];

    // Kiểm tra quyền của user hiện tại
    $is_admin = current_user_can('administrator');
    $is_editor = current_user_can('editor');
    
    // Xác định các trạng thái được phép chọn
    if ($is_admin) {
        // Admin có full quyền
        $allowed_statuses = $all_statuses;
    } elseif ($is_editor) {
        // Biên tập viên chỉ được chọn "Chờ duyệt"
        $allowed_statuses = ['pending' => 'Chờ duyệt'];
    } else {
        // Các role khác không được thay đổi trạng thái
        $allowed_statuses = [];
    }

    echo '<div style="margin-bottom: 15px;">';
    
    // Hiển thị thông báo về tác động của trạng thái
    if ($current_status === 'pending' || empty($current_status)) {
        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; padding: 10px; margin-bottom: 10px;">';
        echo '<strong>⏳ Trạng thái: Chờ duyệt</strong><br>';
        echo '<small style="color: #856404;">Dữ liệu chỉ được lưu vào post meta, chưa cập nhật vào database chính.</small>';
        echo '</div>';
    } elseif ($current_status === 'completed') {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px; padding: 10px; margin-bottom: 10px;">';
        echo '<strong>✅ Trạng thái: Hoàn thành</strong><br>';
        echo '<small style="color: #155724;">Dữ liệu đã được cập nhật vào database chính.</small>';
        echo '</div>';
    }

    echo '<select name="import_status">';
    
    foreach ($allowed_statuses as $value => $label) {
        $selected = selected($current_status, $value, false);
        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    
    if (!array_key_exists($current_status, $allowed_statuses) && !empty($current_status)) {
        $current_label = isset($all_statuses[$current_status]) ? $all_statuses[$current_status] : $current_status;
        echo '<option value="' . esc_attr($current_status) . '" selected disabled>' . esc_html($current_label) . ' (Chỉ đọc)</option>';
    }
    
    echo '</select>';
    
    // Thêm thông báo cảnh báo cho admin
    if ($is_admin) {
        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 3px; padding: 8px; margin-top: 10px; font-size: 12px;">';
        echo '<strong>📋 Lưu ý:</strong><br>';
        echo '• <strong>Chờ duyệt:</strong> Chỉ lưu vào nháp để quản lý duyệt<br>';
        echo '• <strong>Hoàn thành:</strong> Cập nhật dữ liệu cho thùng';
        echo '</div>';
    }
    
    echo '</div>';
}

function render_import_logs_box($post) {
    $logs = get_post_meta($post->ID, 'import_status_logs', true);
    if (!is_array($logs) || empty($logs)) {
        echo '<p>Chưa có log nào.</p>';
        return;
    }

    echo '<ul style="margin: 0; padding: 0;">';
    foreach ($logs as $log) {
        $status = esc_html($log['status']);
        $timestamp = esc_html($log['timestamp']);
        $user = isset($log['user']) ? esc_html($log['user']) : 'Hệ thống';
        
        $status_color = '';
        switch ($log['status']) {
            case 'Hoàn thành':
                $status_color = 'color: #28a745; font-weight: bold;';
                break;
            case 'Chờ duyệt':
                $status_color = 'color: #ffc107; font-weight: bold;';
                break;
        }
        
        echo '<li style="margin-bottom: 8px; padding: 5px; background: #f8f9fa; border-left: 3px solid #007cba;">';
        echo '<div style="' . $status_color . '">' . $status . '</div>';
        echo '<small style="color: #666;">Bởi: <strong>' . $user . '</strong></small><br>';
        echo '<small style="color: #999;">' . $timestamp . '</small>';
        echo '</li>';
    }
    echo '</ul>';
}

add_filter('manage_import_check_posts_columns', function($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['import_status'] = 'Trạng thái';
            $new_columns['approved_by'] = 'Người duyệt';
        }
    }
    return $new_columns;
});

add_action('manage_import_check_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'import_status':
            $status = get_post_meta($post_id, 'import_status', true);
            
            $status_text = get_status_display_text($status);
            $style = '';
            
            switch ($status) {
                case 'completed':
                    $style = 'background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;';
                    break;
                case 'pending':
                default:
                    $style = 'background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;';
                    break;
            }
            echo '<span style="' . $style . '">' . esc_html($status_text) . '</span>';
            break;
            
        case 'approved_by':
            $approved_by = get_post_meta($post_id, 'approved_by', true);
            echo $approved_by ? esc_html($approved_by) : '-';
            break;
    }
}, 10, 2);

add_action('restrict_manage_posts', function() {
    global $typenow;
    if ($typenow === 'import_check') {
        $selected = isset($_GET['import_status_filter']) ? $_GET['import_status_filter'] : '';
        echo '<select name="import_status_filter">';
        echo '<option value="">Tất cả trạng thái</option>';
        echo '<option value="pending"' . selected($selected, 'pending', false) . '>Chờ duyệt</option>';
        echo '<option value="completed"' . selected($selected, 'completed', false) . '>Hoàn thành</option>';
        echo '</select>';
    }
});

add_filter('parse_query', function($query) {
    global $pagenow, $typenow;
    if ($pagenow === 'edit.php' && $typenow === 'import_check' && isset($_GET['import_status_filter']) && $_GET['import_status_filter'] !== '') {
        $query->set('meta_key', 'import_status');
        $query->set('meta_value', $_GET['import_status_filter']);
    }
});

function display_import_check_products_box($post) {
    $boxes = get_post_meta($post->ID, '_import_check_boxes', true);
    if (!is_array($boxes)) $boxes = [];
    ?>
    <style>
        .widefat input, .widefat textarea {
            width: 100% !important;
        }
        .summary-stats {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-item {
            flex: 1;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .required-field {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        .valid-field {
            border: 2px solid #28a745 !important;
            background-color: #f8fff8 !important;
        }
        .validation-summary {
            margin: 15px 0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .validation-summary.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .validation-summary.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .submit-button-disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            background-color: #ccc !important;
            pointer-events: none !important;
        }
    </style>
    
    <!-- Validation Summary -->
    <div id="validation-summary" class="validation-summary error">
        <h4>⚠️ Cần nhập thêm thông tin để có thể cập nhật</h4>
        <ul id="validation-list">
            <li>❌ Tiêu đề bài viết</li>
            <li>❌ Ngày giờ nhập</li>
            <li>❌ Ít nhất 1 thùng hàng hợp lệ</li>
        </ul>
    </div>
    
    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-number" id="total-boxes">0</div>
            <div class="stat-label">📦 Tổng số thùng</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="total-codes">0</div>
            <div class="stat-label">🏷️ Tổng số mã</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="expected-codes">0</div>
            <div class="stat-label">📊 Số mã dự kiến</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="match-status">-</div>
            <div class="stat-label">✅ Trạng thái khớp</div>
        </div>
    </div>
    
    <div id="import_check_boxes_container">
        <table class="widefat" id="import_check_boxes_table" style="margin-bottom:10px;">
            <thead>
                <tr>
                    <th>Mã định danh thùng</th>
                    <th>Số lượng mã sản phẩm</th>
                    <th>Danh sách mã sản phẩm</th>
                    <th>Date <span style="color: red;">*</span></th>
                    <th>Trạng thái & Thông báo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($boxes)) {
                    foreach ($boxes as $index => $box) {
                        $box_code = isset($box['box_code']) ? $box['box_code'] : '';
                        $product_quantity = isset($box['product_quantity']) ? $box['product_quantity'] : '';
                        $product_codes = isset($box['product_codes']) ? $box['product_codes'] : '';
                        $lot_date = isset($box['lot_date']) ? $box['lot_date'] : '';
                        echo render_import_box_row($box_code, $product_quantity, $product_codes, $lot_date, $index);
                    }
                }
                ?>
            </tbody>
        </table>
        
        <button type="button" class="button" id="add_box_row">+ Thêm thùng hàng</button>
        <button type="button" class="button button-primary" id="check_box_quantities" style="margin-left: 10px;">🔍 Check số lượng</button>
        <button type="button" class="button button-secondary" id="check_box_duplicates" style="margin-left: 10px;">⚠️ Check trùng lặp</button>
        <button type="button" class="button button-info" id="refresh_summary" style="margin-left: 10px;">📊 Cập nhật tổng kết</button>
    </div>

    <script>
        // ============ VALIDATION STATE MANAGEMENT ============
        const REQUIRED_CONDITIONS = {
            TITLE: 'title',
            IMPORT_DATE: 'import_date',
            BOXES: 'boxes',
            ALL_DATES: 'all_dates',
            QUANTITIES: 'quantities',
            NO_DUPLICATES: 'no_duplicates'
        };

        let validationState = {
            [REQUIRED_CONDITIONS.TITLE]: false,
            [REQUIRED_CONDITIONS.IMPORT_DATE]: false,
            [REQUIRED_CONDITIONS.BOXES]: false,
            [REQUIRED_CONDITIONS.ALL_DATES]: false,
            [REQUIRED_CONDITIONS.QUANTITIES]: false,
            [REQUIRED_CONDITIONS.NO_DUPLICATES]: false
        };

        let boxRowIndex = <?php echo (is_array($boxes) ? count($boxes) : 0); ?>;

        // ============ SUBMIT BUTTON CONTROL ============
        function disableAllSubmitButtons() {
            const submitButtons = document.querySelectorAll([
                '#publish',
                '#save-post', 
                'input[name="publish"]',
                'input[name="save"]',
                '.editor-post-publish-button',
                '#publishing-action input[type="submit"]',
                '#publishing-action .button-primary',
                '#submitdiv input[type="submit"]',
                '.button-primary[type="submit"]'
            ].join(','));
            
            submitButtons.forEach(button => {
                if (button) {
                    button.disabled = true;
                    button.classList.add('submit-button-disabled');
                    button.title = 'Vui lòng nhập đầy đủ thông tin bắt buộc để có thể cập nhật';
                }
            });
        }

        function enableAllSubmitButtons() {
            const submitButtons = document.querySelectorAll([
                '#publish',
                '#save-post', 
                'input[name="publish"]',
                'input[name="save"]',
                '.editor-post-publish-button',
                '#publishing-action input[type="submit"]',
                '#publishing-action .button-primary',
                '#submitdiv input[type="submit"]',
                '.button-primary[type="submit"]'
            ].join(','));
            
            submitButtons.forEach(button => {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('submit-button-disabled');
                    button.title = 'Có thể cập nhật';
                }
            });
        }

        // ============ VALIDATION FUNCTIONS ============
        function validateTitle() {
            const titleInput = document.getElementById('title');
            const isValid = titleInput && titleInput.value.trim().length > 0;
            
            validationState[REQUIRED_CONDITIONS.TITLE] = isValid;
            
            if (titleInput) {
                if (isValid) {
                    titleInput.style.borderColor = '#28a745';
                    titleInput.style.backgroundColor = '#f8fff8';
                } else {
                    titleInput.style.borderColor = '#dc3545';
                    titleInput.style.backgroundColor = '#fff5f5';
                }
            }
            
            return isValid;
        }

        function validateImportDate() {
            const importDateInput = document.getElementById('import_date');
            const isValid = importDateInput && importDateInput.value.trim().length > 0;
            
            validationState[REQUIRED_CONDITIONS.IMPORT_DATE] = isValid;
            
            if (importDateInput) {
                if (isValid) {
                    importDateInput.style.borderColor = '#28a745';
                    importDateInput.style.backgroundColor = '#f8fff8';
                } else {
                    importDateInput.style.borderColor = '#dc3545';
                    importDateInput.style.backgroundColor = '#fff5f5';
                }
            }
            
            return isValid;
        }

        function validateDateInput(index) {
            const dateInput = document.querySelector(`[name="import_check_boxes[${index}][lot_date]"]`);
            if (!dateInput) return false;

            const dateValue = dateInput.value.trim();
            
            if (!dateValue) {
                dateInput.classList.add('required-field');
                dateInput.classList.remove('valid-field');
                dateInput.title = 'Date là bắt buộc';
                return false;
            } else {
                dateInput.classList.remove('required-field');
                dateInput.classList.add('valid-field');
                dateInput.title = 'Date hợp lệ';
                return true;
            }
        }

        function validateBoxes() {
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            let hasValidBox = false;
            let allQuantitiesMatch = true;
            let allDatesValid = true;
            
            allRows.forEach((row, index) => {
                const boxCodeInput = row.querySelector('input[name*="[box_code]"]');
                const quantityInput = row.querySelector('input[name*="[product_quantity]"]');
                const productCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                const dateInput = row.querySelector('input[name*="[lot_date]"]');

                if (!boxCodeInput || !quantityInput || !productCodesInput || !dateInput) return;

                const boxCode = boxCodeInput.value.trim();
                const expectedQuantity = parseInt(quantityInput.value) || 0;
                const productCodes = productCodesInput.value.trim();
                const dateValue = dateInput.value.trim();

                // Kiểm tra nếu row có thông tin
                if (boxCode || expectedQuantity > 0 || productCodes) {
                    // Kiểm tra đầy đủ thông tin
                    if (boxCode && expectedQuantity > 0 && productCodes && dateValue) {
                        // Kiểm tra số lượng khớp
                        const productCodesList = productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                        const actualQuantity = productCodesList.length;
                        
                        if (expectedQuantity === actualQuantity) {
                            hasValidBox = true;
                        } else {
                            allQuantitiesMatch = false;
                        }
                    } else {
                        allQuantitiesMatch = false;
                    }
                    
                    // Kiểm tra date
                    if (!dateValue) {
                        allDatesValid = false;
                    }
                }
            });
            
            validationState[REQUIRED_CONDITIONS.BOXES] = hasValidBox;
            validationState[REQUIRED_CONDITIONS.ALL_DATES] = allDatesValid;
            validationState[REQUIRED_CONDITIONS.QUANTITIES] = allQuantitiesMatch;
            
            return { hasValidBox, allQuantitiesMatch, allDatesValid };
        }

        function validateDuplicates() {
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            let hasInternalDuplicates = false;
            let hasExternalDuplicates = false;
            
            allRows.forEach((row, index) => {
                const boxCodeInput = row.querySelector('input[name*="[box_code]"]');
                const productCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                
                if (boxCodeInput) {
                    const boxCode = boxCodeInput.value.trim();
                    const duplicateBoxes = checkDuplicateBoxCodes(index, [boxCode]);
                    if (duplicateBoxes.length > 0) {
                        hasExternalDuplicates = true;
                    }
                }
                
                if (productCodesInput) {
                    const productCodes = productCodesInput.value.trim();
                    const productCodesList = productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                    
                    // Check internal duplicates
                    const internalDuplicates = checkInternalDuplicates(productCodesList);
                    if (internalDuplicates.length > 0) {
                        hasInternalDuplicates = true;
                    }
                    
                    // Check external duplicates
                    const duplicateProducts = checkDuplicateProductCodes(index, productCodesList);
                    if (duplicateProducts.length > 0) {
                        hasExternalDuplicates = true;
                    }
                }
            });
            
            const noDuplicates = !hasInternalDuplicates && !hasExternalDuplicates;
            validationState[REQUIRED_CONDITIONS.NO_DUPLICATES] = noDuplicates;
            
            return noDuplicates;
        }

        function validateAllRequiredFields() {
            // Validate từng field
            validateTitle();
            validateImportDate();
            validateBoxes();
            validateDuplicates();
            
            // Kiểm tra tất cả conditions
            const allValid = Object.values(validationState).every(isValid => isValid === true);
            
            // Cập nhật submit button state
            if (allValid) {
                enableAllSubmitButtons();
            } else {
                disableAllSubmitButtons();
            }
            
            // Cập nhật validation summary
            updateValidationSummary();
            
            return allValid;
        }

        // ============ VALIDATION SUMMARY ============
        function updateValidationSummary() {
            const summaryElement = document.getElementById('validation-summary');
            const listElement = document.getElementById('validation-list');
            
            const invalidConditions = [];
            
            if (!validationState[REQUIRED_CONDITIONS.TITLE]) {
                invalidConditions.push('❌ Tiêu đề bài viết');
            }
            if (!validationState[REQUIRED_CONDITIONS.IMPORT_DATE]) {
                invalidConditions.push('❌ Ngày giờ nhập');
            }
            if (!validationState[REQUIRED_CONDITIONS.BOXES]) {
                invalidConditions.push('❌ Ít nhất 1 thùng hàng hợp lệ');
            }
            if (!validationState[REQUIRED_CONDITIONS.ALL_DATES]) {
                invalidConditions.push('❌ Tất cả Date thùng hàng');
            }
            if (!validationState[REQUIRED_CONDITIONS.QUANTITIES]) {
                invalidConditions.push('❌ Số lượng mã khớp');
            }
            if (!validationState[REQUIRED_CONDITIONS.NO_DUPLICATES]) {
                invalidConditions.push('❌ Không có mã trùng lặp');
            }
            
            if (invalidConditions.length === 0) {
                summaryElement.className = 'validation-summary success';
                summaryElement.innerHTML = `
                    <h4>✅ Sẵn sàng cập nhật</h4>
                    <p>Tất cả thông tin bắt buộc đã được nhập đầy đủ và hợp lệ.</p>
                `;
            } else {
                summaryElement.className = 'validation-summary error';
                summaryElement.innerHTML = `
                    <h4>⚠️ Cần nhập thêm thông tin để có thể cập nhật</h4>
                    <ul>${invalidConditions.map(condition => `<li>${condition}</li>`).join('')}</ul>
                `;
            }
        }

        // ============ SUMMARY STATISTICS ============
        function updateSummaryStats() {
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            let totalBoxes = 0;
            let totalActualCodes = 0;
            let totalExpectedCodes = 0;
            let validBoxes = 0;

            allRows.forEach((row, index) => {
                const boxCodeInput = row.querySelector('input[name*="[box_code]"]');
                const quantityInput = row.querySelector('input[name*="[product_quantity]"]');
                const productCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                const dateInput = row.querySelector('input[name*="[lot_date]"]');

                if (!boxCodeInput && !quantityInput && !productCodesInput) return;

                const boxCode = boxCodeInput ? boxCodeInput.value.trim() : '';
                const expectedQuantity = quantityInput ? parseInt(quantityInput.value) || 0 : 0;
                const productCodes = productCodesInput ? productCodesInput.value.trim() : '';
                const dateValue = dateInput ? dateInput.value.trim() : '';

                // Chỉ đếm những row có ít nhất một thông tin
                if (boxCode || expectedQuantity > 0 || productCodes) {
                    totalBoxes++;
                    totalExpectedCodes += expectedQuantity;

                    // Đếm số mã thực tế
                    if (productCodes) {
                        const productCodesList = productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                        totalActualCodes += productCodesList.length;
                    }

                    // Kiểm tra box có hợp lệ không
                    const hasValidInfo = boxCode && productCodes && dateValue && expectedQuantity > 0;
                    const quantityMatch = expectedQuantity > 0 && productCodes && 
                                        expectedQuantity === productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code).length;
                    
                    if (hasValidInfo && quantityMatch) {
                        validBoxes++;
                    }
                }
            });

            // Cập nhật hiển thị
            document.getElementById('total-boxes').textContent = totalBoxes;
            document.getElementById('total-codes').textContent = totalActualCodes;
            document.getElementById('expected-codes').textContent = totalExpectedCodes;

            // Cập nhật trạng thái khớp
            const matchStatusElement = document.getElementById('match-status');
            if (totalBoxes === 0) {
                matchStatusElement.textContent = '-';
                matchStatusElement.style.color = '#666';
            } else if (validBoxes === totalBoxes && totalActualCodes === totalExpectedCodes) {
                matchStatusElement.textContent = '100%';
                matchStatusElement.style.color = '#28a745';
            } else {
                const percentage = totalBoxes > 0 ? Math.round((validBoxes / totalBoxes) * 100) : 0;
                matchStatusElement.textContent = percentage + '%';
                matchStatusElement.style.color = percentage < 100 ? '#dc3545' : '#28a745';
            }
        }

        // ============ BOX DISPLAY UPDATE ============
        function updateBoxDisplay(index) {
            const productCodesInput = document.querySelector(`[name="import_check_boxes[${index}][product_codes]"]`);
            const boxCodeInput = document.querySelector(`[name="import_check_boxes[${index}][box_code]"]`);
            const quantityInput = document.querySelector(`[name="import_check_boxes[${index}][product_quantity]"]`);
            const dateInput = document.querySelector(`[name="import_check_boxes[${index}][lot_date]"]`);
            const statusDiv = document.getElementById(`box_status_${index}`);
            const messageDiv = document.getElementById(`box_message_${index}`);

            if (!productCodesInput || !boxCodeInput || !quantityInput || !statusDiv || !messageDiv) return;

            const productCodes = productCodesInput.value.trim();
            const boxCode = boxCodeInput.value.trim();
            const expectedQuantity = parseInt(quantityInput.value) || 0;
            const dateValue = dateInput ? dateInput.value.trim() : '';
            
            // Đếm số mã sản phẩm thực tế
            const productCodesList = productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
            const actualQuantity = productCodesList.length;
            
            // Kiểm tra các lỗi
            const internalDuplicates = checkInternalDuplicates(productCodesList);
            const isDateValid = validateDateInput(index);
            const duplicateBoxes = checkDuplicateBoxCodes(index, [boxCode]);
            const duplicateProducts = checkDuplicateProductCodes(index, productCodesList);
            
            // Tạo thông báo
            let quantityMessage = '';
            if (expectedQuantity > 0 || actualQuantity > 0) {
                if (expectedQuantity === actualQuantity && expectedQuantity > 0) {
                    quantityMessage = '<span style="color: green; font-weight: bold;">✅ Số lượng khớp (' + actualQuantity + ' mã)</span>';
                } else {
                    quantityMessage = '<span style="color: red; font-weight: bold;">❌ Số lượng không khớp</span><br>' +
                                    '<small>Dự kiến: ' + expectedQuantity + ' | Thực tế: ' + actualQuantity + '</small>';
                }
            }

            if (!isDateValid) {
                quantityMessage += '<br><span style="color: #e74c3c; font-weight: bold;">⚠️ Date là bắt buộc</span>';
            } else {
                quantityMessage += '<br><span style="color: #28a745; font-weight: bold;">✅ Date hợp lệ</span>';
            }
            
            // Highlight duplicates
            if (internalDuplicates.length > 0) {
                quantityMessage += '<br><span style="color: #e74c3c; font-weight: bold;">⚠️ Mã trùng trong thùng:</span><br>' +
                                '<small style="color: #e74c3c;">' + internalDuplicates.join(', ') + '</small>';
                productCodesInput.style.borderColor = '#e74c3c';
                productCodesInput.style.backgroundColor = '#ffeaa7';
                productCodesInput.style.borderWidth = '2px';
            } else if (duplicateProducts.length === 0) {
                productCodesInput.style.borderColor = '';
                productCodesInput.style.backgroundColor = '';
                productCodesInput.style.borderWidth = '';
            }
            
            if (duplicateBoxes.length > 0) {
                quantityMessage += '<br><span style="color: red; font-weight: bold;">⚠️ Mã thùng bị trùng:</span><br>' +
                                '<small style="color: #d63031;">' + duplicateBoxes.join(', ') + '</small>';
            }
            
            if (duplicateProducts.length > 0) {
                quantityMessage += '<br><span style="color: red; font-weight: bold;">⚠️ Mã sản phẩm bị trùng:</span><br>' +
                                '<small style="color: #d63031;">' + duplicateProducts.join(', ') + '</small>';
                productCodesInput.style.borderColor = 'red';
                productCodesInput.style.backgroundColor = '#ffe6e6';
            }
            
            // Hiển thị trạng thái
            let statusMessage = '';
            if (boxCode && productCodes && dateValue) {
                const hasErrors = internalDuplicates.length > 0 || duplicateBoxes.length > 0 || duplicateProducts.length > 0;
                const quantityMismatch = expectedQuantity > 0 && expectedQuantity !== actualQuantity;
                
                if (hasErrors || quantityMismatch) {
                    statusMessage = '<span style="color: #e74c3c;">❌ Có lỗi cần sửa</span>';
                } else {
                    statusMessage = '<span style="color: green;">✅ Hoàn thành</span>';
                }
            } else {
                let missingFields = [];
                if (!boxCode) missingFields.push('mã thùng');
                if (!productCodes) missingFields.push('mã sản phẩm');
                if (!dateValue) missingFields.push('date');
                
                statusMessage = '<span style="color: orange;">⚠️ Chưa nhập: ' + missingFields.join(', ') + '</span>';
            }
            
            statusDiv.innerHTML = statusMessage;
            messageDiv.innerHTML = quantityMessage || '<em style="color: #666;">Nhập thông tin để kiểm tra</em>';
        }

        // ============ DUPLICATE CHECK FUNCTIONS ============
        function checkInternalDuplicates(productCodesList) {
            const seen = {};
            const duplicates = [];
            
            productCodesList.forEach(code => {
                if (code && code.trim()) {
                    const trimmedCode = code.trim();
                    if (seen[trimmedCode]) {
                        if (!duplicates.includes(trimmedCode)) {
                            duplicates.push(trimmedCode);
                        }
                    } else {
                        seen[trimmedCode] = true;
                    }
                }
            });
            
            return duplicates;
        }

        function checkDuplicateBoxCodes(currentIndex, currentBoxCodes) {
            const duplicates = [];
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            
            allRows.forEach((row, index) => {
                if (index === currentIndex) return;
                
                const otherBoxCodeInput = row.querySelector('input[name*="[box_code]"]');
                if (!otherBoxCodeInput) return;
                
                const otherBoxCode = otherBoxCodeInput.value.trim();
                if (!otherBoxCode) return;
                
                currentBoxCodes.forEach(code => {
                    if (code && otherBoxCode === code) {
                        duplicates.push(code);
                    }
                });
            });
            
            return [...new Set(duplicates)];
        }

        function checkDuplicateProductCodes(currentIndex, currentProductCodes) {
            const duplicates = [];
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            
            allRows.forEach((row, index) => {
                if (index === currentIndex) return;
                
                const otherProductCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                if (!otherProductCodesInput) return;
                
                const otherProductCodes = otherProductCodesInput.value.trim();
                if (!otherProductCodes) return;
                
                const otherProductCodesList = otherProductCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                
                currentProductCodes.forEach(code => {
                    if (otherProductCodesList.includes(code)) {
                        duplicates.push(code);
                    }
                });
            });
            
            return [...new Set(duplicates)];
        }

        function checkAllBoxDuplicates() {
            const allRows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            let hasDuplicates = false;
            
            allRows.forEach((row, index) => {
                const boxCodeInput = row.querySelector('input[name*="[box_code]"]');
                const productCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                
                if (boxCodeInput || productCodesInput) {
                    let hasError = false;
                    
                    if (boxCodeInput) {
                        const boxCode = boxCodeInput.value.trim();
                        const duplicateBoxes = checkDuplicateBoxCodes(index, [boxCode]);
                        if (duplicateBoxes.length > 0) {
                            hasError = true;
                            boxCodeInput.style.borderColor = 'red';
                            boxCodeInput.style.backgroundColor = '#ffe6e6';
                        } else {
                            boxCodeInput.style.borderColor = '';
                            boxCodeInput.style.backgroundColor = '';
                        }
                    }
                    
                    if (productCodesInput) {
                        const productCodes = productCodesInput.value.trim();
                        const productCodesList = productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                        
                        const internalDuplicates = checkInternalDuplicates(productCodesList);
                        const hasInternalDuplicates = internalDuplicates.length > 0;
                        
                        const duplicateProducts = checkDuplicateProductCodes(index, productCodesList);
                        const hasExternalDuplicates = duplicateProducts.length > 0;
                        
                        if (hasInternalDuplicates || hasExternalDuplicates) {
                            hasError = true;
                            productCodesInput.style.borderWidth = '2px';
                            
                            if (hasInternalDuplicates && hasExternalDuplicates) {
                                productCodesInput.style.borderColor = '#8e44ad';
                                productCodesInput.style.backgroundColor = '#f8f0ff';
                            } else if (hasInternalDuplicates) {
                                productCodesInput.style.borderColor = '#e74c3c';
                                productCodesInput.style.backgroundColor = '#ffeaa7';
                            } else {
                                productCodesInput.style.borderColor = 'red';
                                productCodesInput.style.backgroundColor = '#ffe6e6';
                            }
                        } else {
                            productCodesInput.style.borderColor = '';
                            productCodesInput.style.backgroundColor = '';
                            productCodesInput.style.borderWidth = '';
                        }
                    }
                    
                    if (hasError) {
                        hasDuplicates = true;
                    }
                    
                    updateBoxDisplay(index);
                }
            });
            
            return hasDuplicates;
        }

        // ============ EVENT HANDLERS ============
        function initBoxRowEventListeners(index) {
            const productCodesInput = document.querySelector(`[name="import_check_boxes[${index}][product_codes]"]`);
            const boxCodeInput = document.querySelector(`[name="import_check_boxes[${index}][box_code]"]`);
            const quantityInput = document.querySelector(`[name="import_check_boxes[${index}][product_quantity]"]`);
            const dateInput = document.querySelector(`[name="import_check_boxes[${index}][lot_date]"]`);
            
            if (productCodesInput) {
                productCodesInput.addEventListener("input", function() {
                    updateBoxDisplay(index);
                    updateSummaryStats();
                    validateAllRequiredFields();
                });
            }
            
            if (boxCodeInput) {
                boxCodeInput.addEventListener("input", function() {
                    updateBoxDisplay(index);
                    updateSummaryStats();
                    validateAllRequiredFields();
                });
            }
            
            if (quantityInput) {
                quantityInput.addEventListener("input", function() {
                    updateBoxDisplay(index);
                    updateSummaryStats();
                    validateAllRequiredFields();
                });
            }

            if (dateInput) {
                dateInput.addEventListener("change", function() {
                    validateDateInput(index);
                    updateBoxDisplay(index);
                    updateSummaryStats();
                    validateAllRequiredFields();
                });
                // Validate ngay khi load
                validateDateInput(index);
            }
        }

        function checkAllBoxQuantities() {
            const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
            
            rows.forEach(function(row, index) {
                updateBoxDisplay(index);
            });
            
            updateSummaryStats();
            validateAllRequiredFields();
            alert('Đã kiểm tra xong tất cả số lượng!');
        }

        // ============ MAIN EVENT LISTENERS ============
        document.getElementById("add_box_row").addEventListener("click", function() {
            let tableBody = document.querySelector("#import_check_boxes_table tbody");
            let row = document.createElement("tr");

            row.innerHTML = `<?php echo str_replace(["\n", "'"], ["", "\\'"], render_import_box_row()); ?>`.replace(/__index__/g, boxRowIndex);
            tableBody.appendChild(row);
            boxRowIndex++;
            
            initBoxRowEventListeners(boxRowIndex - 1);
            updateSummaryStats();
            validateAllRequiredFields();
        });

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-box-row")) {
                e.target.closest("tr").remove();
                updateSummaryStats();
                validateAllRequiredFields();
            }
        });

        document.getElementById("check_box_quantities").addEventListener("click", function() {
            checkAllBoxQuantities();
        });

        document.getElementById("check_box_duplicates").addEventListener("click", function() {
            const hasDuplicates = checkAllBoxDuplicates();
            if (hasDuplicates) {
                alert('❌ Phát hiện mã thùng hoặc mã sản phẩm bị trùng lặp! Vui lòng kiểm tra các ô được highlight.');
            } else {
                alert('✅ Không có mã nào bị trùng lặp!');
            }
            validateAllRequiredFields();
        });

        document.getElementById("refresh_summary").addEventListener("click", function() {
            updateSummaryStats();
            validateAllRequiredFields();
            alert('📊 Đã cập nhật tổng kết!');
        });

        // ============ FORM VALIDATION SETUP ============
        function initFormValidation() {
            // Validate title
            const titleInput = document.getElementById('title');
            if (titleInput) {
                titleInput.addEventListener('input', validateAllRequiredFields);
                titleInput.addEventListener('blur', validateAllRequiredFields);
            }
            
            // Validate import date
            const importDateInput = document.getElementById('import_date');
            if (importDateInput) {
                importDateInput.addEventListener('change', validateAllRequiredFields);
                importDateInput.addEventListener('blur', validateAllRequiredFields);
            }

            // Initialize existing box rows
            document.querySelectorAll(".product-codes-input").forEach(function(input, index) {
                initBoxRowEventListeners(index);
            });
        }

        // ============ FORM SUBMIT PREVENTION ============
        function preventInvalidSubmit() {
            const form = document.getElementById('post');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const isValid = validateAllRequiredFields();
                    if (!isValid) {
                        e.preventDefault();
                        e.stopPropagation();
                        alert('❌ Vui lòng nhập đầy đủ thông tin bắt buộc trước khi cập nhật!');
                        return false;
                    }
                });
            }

            // Intercept all click events on submit buttons
            document.addEventListener('click', function(e) {
                const isSubmitButton = e.target.matches([
                    '#publish',
                    '#save-post', 
                    'input[name="publish"]',
                    'input[name="save"]',
                    '.editor-post-publish-button',
                    '#publishing-action input[type="submit"]',
                    '#publishing-action .button-primary',
                    '#submitdiv input[type="submit"]',
                    '.button-primary[type="submit"]'
                ].join(','));

                if (isSubmitButton && e.target.disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('❌ Vui lòng nhập đầy đủ thông tin bắt buộc trước khi cập nhật!');
                    return false;
                }
            }, true);
        }

        // ============ INITIALIZATION ============
        function initializeValidation() {
            // Disable all submit buttons immediately
            disableAllSubmitButtons();
            
            // Initialize form validation
            initFormValidation();
            
            // Set up submit prevention
            preventInvalidSubmit();
            
            // Initial validation check
            setTimeout(() => {
                updateSummaryStats();
                validateAllRequiredFields();
            }, 500);
        }

        // ============ DOM READY HANDLERS ============
        document.addEventListener('DOMContentLoaded', function() {
            initializeValidation();
            
            // Observer for dynamically added submit buttons
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        const hasSubmitButton = Array.from(mutation.addedNodes).some(node => {
                            return node.nodeType === 1 && node.matches && (
                                node.matches('#publish') ||
                                node.matches('#save-post') ||
                                node.matches('input[name="publish"]') ||
                                node.matches('input[name="save"]') ||
                                node.matches('.button-primary[type="submit"]')
                            );
                        });
                        
                        if (hasSubmitButton) {
                            setTimeout(() => {
                                disableAllSubmitButtons();
                                validateAllRequiredFields();
                            }, 100);
                        }
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });

        // If DOM is already ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeValidation);
        } else {
            initializeValidation();
        }

        // ============ EXPORT FUNCTIONS ============
        window.postValidation = {
            validate: validateAllRequiredFields,
            getState: () => ({ ...validationState }),
            isValid: () => Object.values(validationState).every(v => v === true)
        };
        
    </script>
    <?php
}

function render_import_box_row($box_code = '', $product_quantity = '', $product_codes = '', $lot_date = '', $index = '__index__') {
    ob_start();
    ?>
    <tr>
        <td>
            <input type="text"
                name="import_check_boxes[<?php echo $index; ?>][box_code]"
                value="<?php echo esc_attr($box_code); ?>"
                placeholder="Nhập mã thùng"
                style="width: 150px;"
                title="Mã định danh thùng (phải là duy nhất)" />
        </td>

        <td>
            <input type="number"
                name="import_check_boxes[<?php echo $index; ?>][product_quantity]"
                value="<?php echo esc_attr($product_quantity); ?>"
                min="1" 
                placeholder="Số mã"
                style="width: 80px;"
                title="Số lượng mã sản phẩm dự kiến" />
        </td>

        <td>
            <textarea 
                name="import_check_boxes[<?php echo $index; ?>][product_codes]" 
                class="product-codes-input"
                data-index="<?php echo esc_attr($index); ?>"
                rows="4"
                style="width: 350px;"
                placeholder="Nhập mã sản phẩm, mỗi dòng 1 mã"
                title="Danh sách mã sản phẩm trong thùng này"
            ><?php echo esc_textarea($product_codes); ?></textarea>
        </td>
        <td>
            <input type="date"
                name="import_check_boxes[<?php echo $index; ?>][lot_date]"
                value="<?php echo esc_attr($lot_date); ?>"
                placeholder="Nhập lô date"
                style="<?php echo empty($lot_date) ? 'border: 2px solid #dc3545; background-color: #fff5f5;' : 'border: 2px solid #28a745; background-color: #f8fff8;'; ?>"
                title="Lô date cho thùng này (bắt buộc)"
                required />
        </td>
        <td>
            <div id="box_status_<?php echo esc_attr($index); ?>" style="font-size: 12px; max-width: 150px;">
                <em style="color: #666;">Nhập thông tin thùng hàng</em>
            </div>
            <div id="box_message_<?php echo esc_attr($index); ?>" style="font-size: 12px; max-width: 200px;">
                <em style="color: #666;">Nhập thông tin để kiểm tra</em>
            </div>
        </td>
        <td><button type="button" class="button remove-box-row" title="Xóa thùng này">✕</button></td>
    </tr>
    <?php
    return ob_get_clean();
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



