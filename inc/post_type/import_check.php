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

    // Cập nhật các meta fields cơ bản
    import_update_post_meta_if_changed($post_id, 'import_images', sanitize_text_field($_POST['import_images']));
    import_update_post_meta_if_changed($post_id, 'import_date', sanitize_text_field($_POST['import_date']));
    import_update_post_meta_if_changed($post_id, 'order_import_by', sanitize_text_field($_POST['order_import_by']) ?  sanitize_text_field($_POST['order_import_by']) : $order_import_by);

    // Xử lý trạng thái import
    $old_status = get_post_meta($post_id, 'import_status', true);
    $new_status = isset($_POST['import_status']) ? sanitize_text_field($_POST['import_status']) : $old_status;
    
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

    // Luôn lưu thông tin boxes vào post meta
    $boxes = $_POST['import_check_boxes'] ?? [];
    $existing_boxes = get_post_meta($post_id, '_import_check_boxes', true);
    
    if ($boxes !== $existing_boxes) {
        update_post_meta($post_id, '_import_check_boxes', $boxes);
    }

    // Kiểm tra trạng thái: chỉ cập nhật SQL khi trạng thái là "completed"
    if ($new_status !== 'completed') {
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
        return;
    }

    // Validate tất cả mã trước khi lưu vào database
    $validation_errors = [];
    $all_valid = true;

    foreach ($boxes as $box) {
        $box_code = sanitize_text_field($box['box_code']);
        $product_codes = sanitize_textarea_field($box['product_codes']);
        
        if (empty($box_code) || empty($product_codes)) continue;

        // Check box code status
        $existing_box = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $box_table WHERE barcode = %s",
            $box_code
        ));

        if (!$existing_box) {
            $validation_errors[] = "Mã thùng '$box_code' không tồn tại trong database";
            $all_valid = false;
        } elseif ($existing_box->status !== 'unused') {
            $validation_errors[] = "Mã thùng '$box_code' đã được sử dụng (status: {$existing_box->status})";
            $all_valid = false;
        }

        // Check product codes status
        $codes = array_filter(array_map('trim', explode("\n", $product_codes)));
        foreach ($codes as $code) {
            $existing_barcode = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $barcode_table WHERE barcode = %s",
                $code
            ));

            if (!$existing_barcode) {
                $validation_errors[] = "Mã sản phẩm '$code' không tồn tại trong database";
                $all_valid = false;
            } elseif ($existing_barcode->status !== 'unused') {
                $validation_errors[] = "Mã sản phẩm '$code' đã được sử dụng (status: {$existing_barcode->status})";
                $all_valid = false;
            }
        }
    }

    // Nếu có lỗi validation, không cho phép lưu
    if (!$all_valid) {
        $logs = get_post_meta($post_id, '_import_logs', true);
        if (!is_array($logs)) $logs = [];
        
        $timestamp = current_time('mysql');
        foreach ($validation_errors as $error) {
            $logs[] = [
                'status' => sprintf("[%s] ❌ Lỗi validation: %s", $timestamp, $error),
                'timestamp' => $timestamp
            ];
        }
        
        update_post_meta($post_id, '_import_logs', $logs);
        
        // Đặt lại trạng thái về pending
        update_post_meta($post_id, 'import_status', 'pending');
        
        // Thông báo lỗi cho admin
        add_action('admin_notices', function() use ($validation_errors) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Không thể cập nhật vào database do lỗi validation:</strong></p>';
            echo '<ul>';
            foreach ($validation_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        });
        
        return;
    }

    // Nếu validation passed, tiếp tục cập nhật database
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

            if (empty($box_code) || empty($product_codes)) continue;

            $codes = array_filter(array_map('trim', explode("\n", $product_codes)));
            $list_barcode = implode(',', $codes);

            // Update box status to 'imported'
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

            // Update product codes status to 'used'
            $successful_codes = 0;
            $failed_codes = 0;

            foreach ($codes as $code) {
                $code = trim($code);
                if (empty($code)) continue;

                $update_barcode_result = $wpdb->update(
                    $barcode_table,
                    [
                        'box_barcode' => $box_code,
                        'product_date' => $lot_date,
                        'status' => 'unused',
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
            }

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
    
    // Đảo ngược thứ tự logs để hiển thị từ mới đến cũ
    $logs = array_reverse($logs);
    
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
    $is_quan_ly_kho = in_array('quan_ly_kho', $current_user->roles);
        
    // Xác định các trạng thái được phép chọn
    if ($is_admin || $is_quan_ly_kho) {
        // Admin và Quản lý kho có full quyền
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
        
    // Thêm thông báo cảnh báo cho admin và quản lý kho
    if ($is_admin || $is_quan_ly_kho) {
        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 3px; padding: 8px; margin-top: 10px; font-size: 12px;">';
        echo '<strong style="color: #bc0000;">📋 Lưu ý:</strong><br>';
        echo '• <strong>Chờ duyệt:</strong> Chỉ lưu vào nháp để quản lý duyệt<br>';
        echo '• <strong>Hoàn thành:</strong> Cập nhật dữ liệu cho thùng<br>';
        echo '• <strong style="color: #bc0000;">Khi đơn hàng ở trạng thái "Hoàn thành" thì sẽ không được cập nhật dữ liệu mới.</strong>';
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
        .widefat input, .widefat textarea { width: 100% !important; }
        .summary-stats { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-around; text-align: center; }
        .stat-item { flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #0073aa; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .validation-summary { margin: 15px 0; padding: 15px; border-radius: 5px; border-left: 4px solid; }
        .validation-summary.error { border-color: #dc3545; color: #721c24; }
        .validation-summary.success { background: #d4edda; border-color: #28a745; color: #155724; }
        .validation-summary.checking { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .required-field { border: 2px solid #dc3545 !important; background-color: #fff5f5 !important; }
        .valid-field { border: 2px solid #28a745 !important; background-color: #f8fff8 !important; }
        .invalid-code { border: 2px solid #e74c3c !important; background-color: #ffe6e6 !important; }
        .assigned-to-box { border: 2px solid #ff6b35 !important; background-color: #fff4f1 !important; }
        .submit-button-disabled { opacity: 0.5 !important; cursor: not-allowed !important; background-color: #ccc !important; pointer-events: none !important; }
        .checking-progress { margin: 10px 0; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; }
        .check-step { margin: 5px 0; padding: 5px; border-radius: 3px; }
        .check-step.success { background: #d4edda; color: #155724; }
        .check-step.error { background: #f8d7da; color: #721c24; }
        .check-step.warning { background: #fff3cd; color: #856404; }
        .check-step.checking { background: #e3f2fd; color: #1976d2; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .duplicate-row {
            background-color: #ffe6e6 !important;
            border: 2px solid #e74c3c !important;
            border-radius: 5px;
        }
        .duplicate-row td {
            background-color: #ffe6e6 !important;
        }
    </style>
    
    <div class="summary-stats">
        <div class="stat-item"><div class="stat-number" id="total-boxes">0</div><div class="stat-label">📦 Tổng số thùng</div></div>
        <div class="stat-item"><div class="stat-number" id="total-codes">0</div><div class="stat-label">🏷️ Tổng số mã</div></div>
        <div class="stat-item"><div class="stat-number" id="expected-codes">0</div><div class="stat-label">📊 Số mã dự kiến</div></div>
        <div class="stat-item"><div class="stat-number" id="match-status">-</div><div class="stat-label">✅ Trạng thái khớp</div></div>
    </div>

    <div id="validation-summary" class="validation-summary error">
        <h4>⚠️ Cần nhập thêm thông tin để có thể cập nhật</h4>
        <ul id="validation-list"><li>❌ Chưa kiểm tra dữ liệu</li></ul>
    </div>

    <div id="import_check_boxes_container">
        <table class="widefat" id="import_check_boxes_table" style="margin-bottom:10px;">
            <thead>
                <tr>
                    <th>Mã định danh thùng <span style="color: red;">*</span></th>
                    <th>Số lượng mã sản phẩm</th>
                    <th>Danh sách mã sản phẩm <span style="color: red;">*</span></th>
                    <th>Date <span style="color: red;">*</span></th>
                    <th>Trạng thái & Thông báo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($boxes)) {
                    foreach ($boxes as $index => $box) {
                        echo render_import_box_row(
                            $box['box_code'] ?? '',
                            $box['product_quantity'] ?? '',
                            $box['product_codes'] ?? '',
                            $box['lot_date'] ?? '',
                            $index
                        );
                    }
                }
                ?>
            </tbody>
        </table>
        <button type="button" class="button" id="add_box_row">+ Thêm thùng hàng</button>
        <button type="button" class="button button-primary" id="check_all_validations" style="margin-left: 10px;">
            <strong>🔍 Kiểm tra tất cả</strong>
        </button>
        <span id="last-check-time" style="margin-left: 15px; color: #666; font-style: italic; display: inline-block; margin-top: 6px;"></span>
    </div>
    <span style="margin-top: 12px; display: inline-block;">Vui lòng bấm nút "Kiểm tra tất cả" trước khi lưu dữ liệu.</span>

    <script>
        // ============ VALIDATION MANAGER ============
        const ValidationManager = {
            conditions: {
                TITLE: 'title', IMPORT_DATE: 'import_date', BOXES: 'boxes',
                ALL_DATES: 'all_dates', QUANTITIES: 'quantities', NO_DUPLICATES: 'no_duplicates',
                DATABASE_VALID: 'database_valid', PRODUCTS_NOT_ASSIGNED: 'products_not_assigned'
            },
            
            state: {},
            cache: { boxes: {}, products: {}, productAssignments: {} },
            boxRowIndex: <?php echo (is_array($boxes) ? count($boxes) : 0); ?>,
            
            init() {
                Object.keys(this.conditions).forEach(key => this.state[this.conditions[key]] = false);
                this.setupEventListeners();
                this.disableSubmitButtons();
                setTimeout(() => { this.updateStats(); this.updateSummary(); }, 500);
            },

            async checkAll() {
                const summaryDiv = document.getElementById('validation-summary');
                const button = document.getElementById('check_all_validations');
                
                button.disabled = true;
                button.innerHTML = '⏳ Đang kiểm tra...';
                
                // Show checking status
                summaryDiv.className = 'validation-summary checking';
                summaryDiv.innerHTML = `
                    <h4><span class="spinner"></span>Đang kiểm tra dữ liệu...</h4>
                    <div id="check-progress"></div>
                `;
                
                const progressDiv = document.getElementById('check-progress');
                let allValid = true;
                
                try {
                    // Step 1: Basic validations
                    this.addCheckStep(progressDiv, 'checking', '<span class="spinner"></span>Kiểm tra tiêu đề và ngày nhập...');
                    
                    const titleValid = this.validateField('title');
                    const dateValid = this.validateField('import_date');
                    
                    this.updateLastCheckStep(progressDiv, titleValid && dateValid ? 'success' : 'error', 
                        titleValid && dateValid ? '✅ Tiêu đề và ngày nhập hợp lệ' : '❌ Tiêu đề hoặc ngày nhập chưa hợp lệ');
                    
                    if (!titleValid || !dateValid) allValid = false;

                    // Step 2: Box validation
                    this.addCheckStep(progressDiv, 'checking', '<span class="spinner"></span>Kiểm tra thông tin thùng hàng...');
                    
                    const boxValidation = this.validateBoxes();
                    let boxMessage = '';
                    
                    if (!boxValidation.hasValidBox) {
                        boxMessage += '❌ Chưa có thùng hàng hợp lệ. ';
                        allValid = false;
                    }
                    if (!boxValidation.allDatesValid) {
                        boxMessage += '❌ Một số thùng chưa nhập Date. ';
                        allValid = false;
                    }
                    if (!boxValidation.allQuantitiesMatch) {
                        boxMessage += '⚠️ Số lượng mã không khớp ở một số thùng. ';
                        allValid = false;
                    }
                    
                    if (!boxMessage) boxMessage = '✅ Tất cả thùng hàng hợp lệ';
                    
                    this.updateLastCheckStep(progressDiv, allValid ? 'success' : 'error', boxMessage);

                    // Step 3: Duplicates check
                    this.addCheckStep(progressDiv, 'checking', '<span class="spinner"></span>Kiểm tra mã trùng lặp...');
                    
                    const noDuplicates = await this.checkDuplicates();
                    if (noDuplicates) {
                        this.updateLastCheckStep(progressDiv, 'success', '✅ Không có mã trùng lặp');
                    }
                    
                    if (!noDuplicates) allValid = false;

                    // Step 4: Database existence check
                    this.addCheckStep(progressDiv, 'checking', '<span class="spinner"></span>Kiểm tra tồn tại trong cơ sở dữ liệu...');
                    
                    const dbValid = await this.checkDatabase();
                    this.updateLastCheckStep(progressDiv, dbValid ? 'success' : 'error', 
                        dbValid ? '✅ Tất cả mã tồn tại và chưa được sử dụng' : '❌ Có mã không tồn tại hoặc đã được sử dụng');
                    
                    if (!dbValid) allValid = false;

                    // Step 5: Product assignment check
                    this.addCheckStep(progressDiv, 'checking', '<span class="spinner"></span>Kiểm tra mã sản phẩm chưa thuộc thùng nào...');
                    
                    const productsNotAssigned = await this.checkProductAssignment();
                    if (productsNotAssigned) {
                        this.updateLastCheckStep(progressDiv, 'success', '✅ Tất cả mã sản phẩm chưa thuộc thùng nào');
                    }
                    if (!productsNotAssigned) allValid = false;

                    this.state[this.conditions.DATABASE_VALID] = dbValid;
                    this.state[this.conditions.PRODUCTS_NOT_ASSIGNED] = productsNotAssigned;

                    // Final update
                    this.updateStats();
                    allValid ? this.enableSubmitButtons() : this.disableSubmitButtons();

                    // Show final result
                    setTimeout(() => {
                        this.showFinalResult(allValid, progressDiv);
                        document.getElementById('last-check-time').textContent = `Lần kiểm tra cuối: ${new Date().toLocaleTimeString('vi-VN')}`;
                    }, 500);

                } catch (error) {
                    console.error('Validation error:', error);
                    this.addCheckStep(progressDiv, 'error', '❌ Có lỗi xảy ra khi kiểm tra. Vui lòng thử lại.');
                    allValid = false;
                }

                button.disabled = false;
                button.innerHTML = '🔍 <strong>Kiểm tra tất cả</strong>';
                return allValid;
            },

            addCheckStep(container, type, message) {
                const step = document.createElement('div');
                step.className = `check-step ${type}`;
                step.innerHTML = message;
                container.appendChild(step);
                
                // Auto scroll to bottom
                container.scrollTop = container.scrollHeight;
            },

            updateLastCheckStep(container, type, message) {
                const lastStep = container.lastElementChild;
                if (lastStep) {
                    lastStep.className = `check-step ${type}`;
                    lastStep.innerHTML = message;
                }
            },

            showFinalResult(allValid, progressDiv) {
                const summaryDiv = document.getElementById('validation-summary');
                
                if (allValid) {
                    summaryDiv.className = 'validation-summary success';
                    summaryDiv.innerHTML = `
                        <h4>✅ Kiểm tra hoàn tất - Sẵn sàng cập nhật!</h4>
                        <p>Tất cả thông tin bắt buộc đã được nhập đầy đủ và hợp lệ.</p>
                        ${progressDiv.innerHTML}
                    `;
                } else {
                    summaryDiv.className = 'validation-summary error';
                    const invalidConditions = this.getInvalidConditions();
                    summaryDiv.innerHTML = `
                        <h4>❌ Có lỗi cần sửa trước khi cập nhật</h4>
                        <p><strong>Các vấn đề cần khắc phục:</strong></p>
                        <ul>${invalidConditions.map(condition => `<li>${condition}</li>`).join('')}</ul>
                        <div style="margin-top: 15px;">
                            <summary style="cursor: pointer; font-weight: bold;">Chi tiết quá trình kiểm tra</summary>
                            <div style="margin-top: 10px;">${progressDiv.innerHTML}</div>
                        </div>
                    `;
                }
            },

            getInvalidConditions() {
                const conditionLabels = {
                    [this.conditions.TITLE]: '❌ Tiêu đề bài viết',
                    [this.conditions.IMPORT_DATE]: '❌ Ngày giờ nhập',
                    [this.conditions.BOXES]: '❌ Ít nhất 1 thùng hàng hợp lệ',
                    [this.conditions.ALL_DATES]: '❌ Tất cả Date thùng hàng',
                    [this.conditions.QUANTITIES]: '❌ Số lượng mã khớp',
                    [this.conditions.NO_DUPLICATES]: '❌ Không có mã trùng lặp',
                    [this.conditions.DATABASE_VALID]: '❌ Tất cả mã phải tồn tại trong cơ sở dữ liệu với trạng thái "chưa sử dụng"',
                    [this.conditions.PRODUCTS_NOT_ASSIGNED]: '❌ Tất cả mã sản phẩm phải chưa thuộc thùng nào'
                };
                
                const invalidConditions = [];
                Object.entries(this.state).forEach(([key, value]) => {
                    if (!value && conditionLabels[key]) invalidConditions.push(conditionLabels[key]);
                });
                
                return invalidConditions;
            },

            validateField(fieldId) {
                const input = document.getElementById(fieldId);
                const isValid = input && input.value.trim().length > 0;
                this.state[this.conditions[fieldId.toUpperCase()]] = isValid;
                
                if (input) {
                    input.style.borderColor = isValid ? '#28a745' : '#dc3545';
                    input.style.backgroundColor = isValid ? '#f8fff8' : '#fff5f5';
                }
                return isValid;
            },

            validateBoxes() {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                let hasValidBox = false, allQuantitiesMatch = true, allDatesValid = true, hasAnyBox = false;
                
                rows.forEach((row, index) => {
                    const inputs = this.getRowInputs(row);
                    if (!inputs.all) return;

                    const values = this.getRowValues(inputs);
                    
                    // Kiểm tra nếu row có bất kỳ thông tin nào (kể cả không đầy đủ)
                    const hasAnyData = values.boxCode || values.productCodes || values.expectedQuantity > 0 || values.dateValue;
                    
                    if (hasAnyData) {
                        hasAnyBox = true;
                        
                        // Kiểm tra thông tin đầy đủ và hợp lệ
                        const hasCompleteInfo = values.boxCode && values.productCodes && values.dateValue;
                        
                        if (hasCompleteInfo) {
                            const productCodesList = this.parseProductCodes(values.productCodes);
                            
                            if (values.expectedQuantity > 0) {
                                if (values.expectedQuantity === productCodesList.length) {
                                    hasValidBox = true;
                                } else {
                                    allQuantitiesMatch = false;
                                }
                            } else if (productCodesList.length > 0) {
                                hasValidBox = true;
                            }
                        } else {
                            // Có dữ liệu nhưng không đầy đủ
                            if (!values.dateValue) allDatesValid = false;
                            if (!values.boxCode || !values.productCodes) {
                                // Không đủ thông tin cơ bản để coi là valid
                            }
                        }
                    }
                    
                    this.updateBoxDisplay(index);
                });

                // Nếu không có dữ liệu gì hoặc không có box nào valid
                if (!hasAnyBox) {
                    hasValidBox = false;
                }
                
                this.state[this.conditions.BOXES] = hasValidBox;
                this.state[this.conditions.ALL_DATES] = allDatesValid;
                this.state[this.conditions.QUANTITIES] = allQuantitiesMatch;
                
                return { hasValidBox, allQuantitiesMatch, allDatesValid };
            },

            async checkDuplicates() {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                let hasDuplicates = false;
                let duplicateDetails = [];
                let duplicateRowIndices = new Set();
                
                // Clear previous highlighting
                this.clearAllDuplicateHighlighting();
                
                rows.forEach((row, index) => {
                    const inputs = this.getRowInputs(row);
                    if (!inputs.all) return;

                    const values = this.getRowValues(inputs);
                    
                    // Check duplicate box codes
                    if (values.boxCode) {
                        const duplicateBoxes = this.findDuplicateBoxCodes(index, [values.boxCode]);
                        if (duplicateBoxes.length > 0) {
                            hasDuplicates = true;
                            this.markFieldError(inputs.boxCode);
                            
                            // Tìm các dòng khác có cùng mã thùng
                            const conflictRows = this.findRowsWithBoxCode(values.boxCode, index);
                            
                            // Highlight current row và conflict rows
                            duplicateRowIndices.add(index);
                            conflictRows.forEach(rowNum => duplicateRowIndices.add(rowNum - 1));
                            
                            duplicateDetails.push({
                                type: 'box',
                                code: values.boxCode,
                                currentRow: index + 1,
                                conflictRows: conflictRows,
                                message: `Mã thùng "${values.boxCode}" bị trùng`
                            });
                        } else {
                            this.clearFieldError(inputs.boxCode);
                        }
                    }
                    
                    // Check duplicate product codes
                    if (values.productCodes) {
                        const productCodesList = this.parseProductCodes(values.productCodes);
                        
                        // Check internal duplicates (trong cùng 1 dòng)
                        const internalDups = this.findInternalDuplicates(productCodesList);
                        
                        // Check external duplicates (với các dòng khác)
                        const externalDups = this.findDuplicateProductCodes(index, productCodesList);
                        
                        if (internalDups.length > 0) {
                            hasDuplicates = true;
                            this.markFieldError(inputs.productCodes, '#ffeaa7');
                            
                            // Highlight current row
                            duplicateRowIndices.add(index);
                            
                            duplicateDetails.push({
                                type: 'product_internal',
                                codes: internalDups,
                                currentRow: index + 1,
                                message: `Mã sản phẩm trùng lặp trong cùng dòng ${index + 1}: ${internalDups.join(', ')}`
                            });
                        }
                        
                        if (externalDups.length > 0) {
                            hasDuplicates = true;
                            this.markFieldError(inputs.productCodes, '#ffeaa7');
                            
                            // Tìm các dòng khác có cùng mã sản phẩm
                            const productConflicts = this.findRowsWithProductCodes(externalDups, index);
                            
                            // Highlight current row và conflict rows
                            duplicateRowIndices.add(index);
                            Object.values(productConflicts).forEach(conflictRows => {
                                conflictRows.forEach(rowNum => duplicateRowIndices.add(rowNum - 1));
                            });
                            
                            externalDups.forEach(code => {
                                const conflictRows = productConflicts[code] || [];
                                duplicateDetails.push({
                                    type: 'product_external',
                                    code: code,
                                    currentRow: index + 1,
                                    conflictRows: conflictRows,
                                    message: `Mã sản phẩm "${code}" bị trùng`
                                });
                            });
                        }
                        
                        if (internalDups.length === 0 && externalDups.length === 0) {
                            this.clearFieldError(inputs.productCodes);
                        }
                    }
                });
                
                // Highlight tất cả các dòng bị trùng
                this.highlightDuplicateRows(Array.from(duplicateRowIndices));
                
                this.state[this.conditions.NO_DUPLICATES] = !hasDuplicates;
                
                // Hiển thị chi tiết lỗi trùng lặp
                if (hasDuplicates && duplicateDetails.length > 0) {
                    this.showDuplicateDetails(duplicateDetails);
                }
                
                return !hasDuplicates;
            },

            clearAllDuplicateHighlighting() {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                rows.forEach(row => {
                    row.classList.remove('duplicate-row');
                });
            },

            highlightDuplicateRows(rowIndices) {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                
                rowIndices.forEach(index => {
                    if (rows[index]) {
                        rows[index].classList.add('duplicate-row');
                        
                        // Scroll to first duplicate row để user dễ thấy
                        if (index === Math.min(...rowIndices)) {
                            setTimeout(() => {
                                rows[index].scrollIntoView({ 
                                    behavior: 'smooth', 
                                    block: 'center' 
                                });
                            }, 500);
                        }
                    }
                });
            },

            findRowsWithBoxCode(boxCode, excludeIndex) {
                const conflictRows = [];
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                
                rows.forEach((row, index) => {
                    if (index === excludeIndex) return;
                    
                    const input = row.querySelector('input[name*="[box_code]"]');
                    if (input && input.value.trim() === boxCode) {
                        conflictRows.push(index + 1);
                    }
                });
                
                return conflictRows;
            },

            findRowsWithProductCodes(productCodes, excludeIndex) {
                const conflicts = {};
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                
                // Initialize conflicts object
                productCodes.forEach(code => {
                    conflicts[code] = [];
                });
                
                rows.forEach((row, index) => {
                    if (index === excludeIndex) return;
                    
                    const input = row.querySelector('textarea[name*="[product_codes]"]');
                    if (input && input.value.trim()) {
                        const otherCodes = this.parseProductCodes(input.value.trim());
                        
                        productCodes.forEach(code => {
                            if (otherCodes.includes(code)) {
                                conflicts[code].push(index + 1);
                            }
                        });
                    }
                });
                
                return conflicts;
            },

            showDuplicateDetails(duplicateDetails) {
                let detailMessage = '❌ Chi tiết mã trùng lặp:<br><br>';
                
                // Nhóm lỗi theo loại
                const boxDuplicates = duplicateDetails.filter(d => d.type === 'box');
                const productInternalDuplicates = duplicateDetails.filter(d => d.type === 'product_internal');
                const productExternalDuplicates = duplicateDetails.filter(d => d.type === 'product_external');
                
                // Hiển thị lỗi mã thùng trùng
                if (boxDuplicates.length > 0) {
                    detailMessage += '<div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 3px;">';
                    detailMessage += '<strong>🗂️ Mã thùng trùng lặp:</strong><br>';
                    
                    // Group by box code
                    const groupedBoxes = {};
                    boxDuplicates.forEach(dup => {
                        if (!groupedBoxes[dup.code]) {
                            groupedBoxes[dup.code] = [];
                        }
                        groupedBoxes[dup.code].push(dup.currentRow);
                        groupedBoxes[dup.code].push(...dup.conflictRows);
                    });
                    
                    Object.entries(groupedBoxes).forEach(([boxCode, rows]) => {
                        const uniqueRows = [...new Set(rows)].sort((a, b) => a - b);
                        detailMessage += `<small>• Mã thùng "<strong>${boxCode}</strong>" xuất hiện ở dòng: ${uniqueRows.join(', ')}</small><br>`;
                    });
                    
                    detailMessage += '</div>';
                }
                
                // Hiển thị lỗi mã sản phẩm trùng trong cùng dòng
                if (productInternalDuplicates.length > 0) {
                    detailMessage += '<div style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; border-radius: 3px;">';
                    detailMessage += '<strong>🔄 Mã sản phẩm trùng lặp trong cùng dòng:</strong><br>';
                    
                    productInternalDuplicates.forEach(dup => {
                        detailMessage += `<small>• <span style="color: #e74c3c; font-weight: bold;">Dòng ${dup.currentRow}</span>: ${dup.codes.join(', ')}</small><br>`;
                    });
                    
                    detailMessage += '</div>';
                }
                
                // Hiển thị lỗi mã sản phẩm trùng giữa các dòng
                if (productExternalDuplicates.length > 0) {
                    detailMessage += '<div style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; border-radius: 3px;">';
                    detailMessage += '<strong>↔️ Mã sản phẩm trùng lặp giữa các dòng:</strong><br>';
                    
                    // Group by product code
                    const groupedProducts = {};
                    productExternalDuplicates.forEach(dup => {
                        if (!groupedProducts[dup.code]) {
                            groupedProducts[dup.code] = [];
                        }
                        groupedProducts[dup.code].push(dup.currentRow);
                        groupedProducts[dup.code].push(...dup.conflictRows);
                    });
                    
                    Object.entries(groupedProducts).forEach(([productCode, rows]) => {
                        const uniqueRows = [...new Set(rows)].sort((a, b) => a - b);
                        detailMessage += `<small>• Mã sản phẩm "<strong>${productCode}</strong>" xuất hiện ở <span style="color: #e74c3c; font-weight: bold;">dòng: ${uniqueRows.join(', ')}</span></small><br>`;
                    });
                    
                    detailMessage += '</div>';
                }
                detailMessage += '<p style="color: #666; font-style: italic; margin-top: 10px;">💡 Các dòng bị trùng đã được tô đỏ trong bảng để dễ nhận biết.</p>';
                // Update step với thông tin chi tiết
                const progressDiv = document.getElementById('check-progress');
                if (progressDiv && progressDiv.lastElementChild) {
                    progressDiv.lastElementChild.innerHTML = detailMessage;
                }
            },

            async checkDatabase() {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                let allValid = true;
                let invalidDetails = [];
                let hasAnyDataToCheck = false;
                
                for (let i = 0; i < rows.length; i++) {
                    const inputs = this.getRowInputs(rows[i]);
                    if (!inputs.all) continue;

                    const values = this.getRowValues(inputs);
                    
                    // Kiểm tra nếu có bất kỳ dữ liệu nào để validate
                    const hasData = values.boxCode || values.productCodes || values.expectedQuantity > 0 || values.dateValue;
                    
                    if (!hasData) {
                        // Row trống hoàn toàn - clear tất cả styling
                        this.clearAllFieldStyling(inputs);
                        continue;
                    }
                    
                    hasAnyDataToCheck = true;
                    let rowErrors = [];
                    
                    try {
                        // ============ VALIDATE REQUIRED FIELDS ============
                        
                        // 1. Validate Box Code - Required
                        if (!values.boxCode) {
                            allValid = false;
                            inputs.boxCode.classList.add('required-field');
                            inputs.boxCode.classList.remove('valid-field', 'invalid-code');
                            rowErrors.push('thiếu mã thùng');
                        } else {
                            inputs.boxCode.classList.remove('required-field');
                            
                            // Check box code in database
                            const boxStatus = await this.apiCall('check_barcode_status', { type: 'box', codes: [values.boxCode] });
                            if (boxStatus[values.boxCode]) {
                                const status = boxStatus[values.boxCode];
                                this.cache.boxes[values.boxCode] = status;
                                
                                if (!status.exists) {
                                    allValid = false;
                                    inputs.boxCode.classList.add('invalid-code');
                                    inputs.boxCode.classList.remove('valid-field');
                                    rowErrors.push(`mã thùng "${values.boxCode}" không tồn tại trong hệ thống`);
                                } else if (status.status !== 'unused') {
                                    allValid = false;
                                    inputs.boxCode.classList.add('invalid-code');
                                    inputs.boxCode.classList.remove('valid-field');
                                    rowErrors.push(`mã thùng "${values.boxCode}" đã được sử dụng (trạng thái: ${status.status})`);
                                } else {
                                    inputs.boxCode.classList.add('valid-field');
                                    inputs.boxCode.classList.remove('invalid-code');
                                }
                            } else {
                                allValid = false;
                                inputs.boxCode.classList.add('invalid-code');
                                inputs.boxCode.classList.remove('valid-field');
                                rowErrors.push(`không thể kiểm tra mã thùng "${values.boxCode}"`);
                            }
                        }
                        
                        // 2. Validate Product Codes - Required
                        if (!values.productCodes) {
                            allValid = false;
                            inputs.productCodes.classList.add('required-field');
                            inputs.productCodes.classList.remove('valid-field', 'invalid-code', 'assigned-to-box');
                            rowErrors.push('thiếu danh sách mã sản phẩm');
                        } else {
                            inputs.productCodes.classList.remove('required-field');
                            
                            const productCodesList = this.parseProductCodes(values.productCodes);
                            if (productCodesList.length === 0) {
                                allValid = false;
                                inputs.productCodes.classList.add('required-field');
                                inputs.productCodes.classList.remove('valid-field', 'invalid-code', 'assigned-to-box');
                                rowErrors.push('danh sách mã sản phẩm trống hoặc không hợp lệ');
                            } else {
                                // Check product codes in database
                                const productStatus = await this.apiCall('check_barcode_status', { type: 'product', codes: productCodesList });
                                let invalidProducts = [];
                                let missingProducts = [];
                                let usedProducts = [];
                                
                                for (const code of productCodesList) {
                                    if (productStatus[code]) {
                                        const status = productStatus[code];
                                        this.cache.products[code] = status;
                                        
                                        if (!status.exists) {
                                            missingProducts.push(code);
                                        } else if (status.status !== 'unused') {
                                            usedProducts.push(`${code} (${status.status})`);
                                        }
                                    } else {
                                        invalidProducts.push(code);
                                    }
                                }
                                
                                // Tổng hợp lỗi product codes
                                let productErrors = [];
                                if (missingProducts.length > 0) {
                                    productErrors.push(`không tồn tại: ${missingProducts.join(', ')}`);
                                }
                                if (usedProducts.length > 0) {
                                    productErrors.push(`đã được sử dụng: ${usedProducts.join(', ')}`);
                                }
                                if (invalidProducts.length > 0) {
                                    productErrors.push(`không thể kiểm tra: ${invalidProducts.join(', ')}`);
                                }
                                
                                if (productErrors.length > 0) {
                                    allValid = false;
                                    inputs.productCodes.classList.add('invalid-code');
                                    inputs.productCodes.classList.remove('valid-field');
                                    rowErrors.push(`mã sản phẩm ${productErrors.join('; ')}`);
                                } else {
                                    inputs.productCodes.classList.add('valid-field');
                                    inputs.productCodes.classList.remove('invalid-code');
                                }
                            }
                        }
                        
                        // 3. Validate Date - Required  
                        if (!values.dateValue) {
                            allValid = false;
                            inputs.date.classList.add('required-field');
                            inputs.date.classList.remove('valid-field');
                            rowErrors.push('thiếu Date');
                        } else {
                            // Validate date format (optional - có thể thêm regex validation)
                            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                            if (!dateRegex.test(values.dateValue)) {
                                allValid = false;
                                inputs.date.classList.add('required-field');
                                inputs.date.classList.remove('valid-field');
                                rowErrors.push('Date không đúng định dạng (YYYY-MM-DD)');
                            } else {
                                inputs.date.classList.add('valid-field');
                                inputs.date.classList.remove('required-field');
                            }
                        }
                        
                        // 4. Validate Product Quantity vs Actual Quantity
                        if (values.productCodes) {
                            const productCodesList = this.parseProductCodes(values.productCodes);
                            const actualQuantity = productCodesList.length;
                            
                            if (values.expectedQuantity > 0) {
                                if (values.expectedQuantity !== actualQuantity) {
                                    allValid = false;
                                    inputs.quantity.classList.add('required-field');
                                    inputs.quantity.classList.remove('valid-field');
                                    rowErrors.push(`số lượng không khớp (dự kiến: ${values.expectedQuantity}, thực tế: ${actualQuantity})`);
                                } else {
                                    inputs.quantity.classList.add('valid-field');
                                    inputs.quantity.classList.remove('required-field');
                                }
                            } else {
                                // Nếu không nhập số lượng dự kiến, cảnh báo
                                inputs.quantity.classList.add('required-field');
                                inputs.quantity.classList.remove('valid-field');
                                rowErrors.push(`chưa nhập số lượng dự kiến (hiện có ${actualQuantity} mã)`);
                                allValid = false;
                            }
                        }
                        
                        // ============ VALIDATE BUSINESS RULES ============
                        
                        // 5. Validate complete row data
                        if (values.boxCode && values.productCodes && values.dateValue && values.expectedQuantity > 0) {
                            const productCodesList = this.parseProductCodes(values.productCodes);
                            
                            // Check if box code and product codes are consistent
                            if (productCodesList.length === 0) {
                                allValid = false;
                                rowErrors.push('mã sản phẩm không hợp lệ sau khi parse');
                            }
                            
                            // Additional business rule checks can be added here
                            // Example: Check if box capacity matches product count
                            // Example: Check if date is within valid range
                        }
                        
                        // Lưu lỗi của dòng này nếu có
                        if (rowErrors.length > 0) {
                            invalidDetails.push({
                                row: i + 1,
                                errors: rowErrors,
                                data: {
                                    boxCode: values.boxCode || '(trống)',
                                    productCount: values.productCodes ? this.parseProductCodes(values.productCodes).length : 0,
                                    expectedQuantity: values.expectedQuantity,
                                    date: values.dateValue || '(trống)'
                                }
                            });
                        }
                        
                    } catch (error) {
                        console.error(`Database check error at row ${i + 1}:`, error);
                        allValid = false;
                        
                        // Clear all styling on error
                        this.clearAllFieldStyling(inputs);
                        
                        invalidDetails.push({
                            row: i + 1,
                            errors: [`lỗi kết nối database: ${error.message || 'Unknown error'}`],
                            data: { error: true }
                        });
                    }
                    
                    // Update display for this row
                    this.updateBoxDisplay(i);
                }
                
                // ============ FINAL VALIDATION ============
                
                // Nếu không có dữ liệu nào để check
                if (!hasAnyDataToCheck) {
                    allValid = false;
                    invalidDetails.push({
                        row: 'Tổng quan',
                        errors: ['Không có dữ liệu nào để kiểm tra - cần ít nhất 1 thùng hàng'],
                        data: { global: true }
                    });
                }
                
                // ============ DISPLAY DETAILED ERRORS ============
                
                if (invalidDetails.length > 0) {
                    let detailMessage = '❌ Chi tiết lỗi kiểm tra cơ sở dữ liệu:<br><br>';
                    
                    invalidDetails.forEach(detail => {
                        if (detail.data.global) {
                            detailMessage += `<div style="margin-bottom: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 3px;">`;
                            detailMessage += `<strong>${detail.row}:</strong><br>`;
                        } else if (detail.data.error) {
                            detailMessage += `<div style="margin-bottom: 10px; padding: 8px; background: #f8d7da; border-left: 3px solid #dc3545; border-radius: 3px;">`;
                            detailMessage += `<strong>Dòng ${detail.row}:</strong> Lỗi hệ thống<br>`;
                        } else {
                            detailMessage += `<div style="margin-bottom: 10px; padding: 8px; background: #f8d7da; border-left: 3px solid #dc3545; border-radius: 3px;">`;
                            detailMessage += `<strong>Dòng ${detail.row}:</strong> ${detail.data.boxCode} | ${detail.data.productCount} mã | SL: ${detail.data.expectedQuantity} | ${detail.data.date}<br>`;
                        }
                        
                        detail.errors.forEach(error => {
                            detailMessage += `<small style="color: #721c24;">• ${error}</small><br>`;
                        });
                        
                        detailMessage += `</div>`;
                    });
                    
                    // Update step với thông tin chi tiết
                    const progressDiv = document.getElementById('check-progress');
                    if (progressDiv && progressDiv.lastElementChild) {
                        progressDiv.lastElementChild.innerHTML = detailMessage;
                    }
                }
                
                return allValid;
            },

            clearAllFieldStyling(inputs) {
                const fields = [inputs.boxCode, inputs.productCodes, inputs.date, inputs.quantity];
                const classes = ['required-field', 'valid-field', 'invalid-code', 'assigned-to-box'];
                
                fields.forEach(field => {
                    if (field) {
                        classes.forEach(cls => field.classList.remove(cls));
                    }
                });
            },

            async checkProductAssignment() {
                const allProductCodes = this.getAllProductCodes();
                if (allProductCodes.length === 0) return true;
                
                try {
                    const assignmentStatus = await this.apiCall('check_product_box_assignment', { product_codes: allProductCodes });
                    let allNotAssigned = true;
                    let assignedProductsDetails = []; // Thêm array để lưu chi tiết
                    
                    document.querySelectorAll('#import_check_boxes_table tbody tr').forEach((row, index) => {
                        const inputs = this.getRowInputs(row);
                        if (!inputs.productCodes) return;
                        
                        const productCodesList = this.parseProductCodes(inputs.productCodes.value.trim());
                        let hasAssigned = false;
                        
                        for (const code of productCodesList) {
                            if (assignmentStatus[code] && assignmentStatus[code].assigned) {
                                hasAssigned = true;
                                allNotAssigned = false;
                                this.cache.productAssignments[code] = assignmentStatus[code];
                                // Lưu chi tiết mã đã bị phân bổ
                                assignedProductsDetails.push({
                                    code: code,
                                    assignedToBox: assignmentStatus[code].box_barcode,
                                    rowIndex: index + 1
                                });
                            }
                        }
                        
                        if (hasAssigned) inputs.productCodes.classList.add('assigned-to-box');
                        else inputs.productCodes.classList.remove('assigned-to-box');
                        
                        this.updateBoxDisplay(index);
                    });
                    
                    // Cập nhật step với thông tin chi tiết
                    if (assignedProductsDetails.length > 0) {
                        // Nhóm theo thùng để hiển thị gọn hơn
                        const groupedByBox = {};
                        assignedProductsDetails.forEach(item => {
                            if (!groupedByBox[item.assignedToBox]) {
                                groupedByBox[item.assignedToBox] = [];
                            }
                            groupedByBox[item.assignedToBox].push(`${item.code} (dòng ${item.rowIndex})`);
                        });
                        
                        let detailMessage = '❌ Có mã sản phẩm đã được phân bổ vào thùng khác:<br>';
                        Object.entries(groupedByBox).forEach(([boxCode, codes]) => {
                            detailMessage += `<small style="color: #ff6b35;">• Thùng <strong>${boxCode}</strong>: ${codes.join(', ')}</small><br>`;
                        });
                        
                        // Update step với thông tin chi tiết
                        const progressDiv = document.getElementById('check-progress');
                        if (progressDiv && progressDiv.lastElementChild) {
                            progressDiv.lastElementChild.innerHTML = detailMessage;
                        }
                    }
                    
                    return allNotAssigned;
                } catch (error) {
                    console.error('Assignment check error:', error);
                    return false;
                }
            },

            // ============ HELPER METHODS ============
            getRowInputs(row) {
                const boxCode = row.querySelector('input[name*="[box_code]"]');
                const quantity = row.querySelector('input[name*="[product_quantity]"]');
                const productCodes = row.querySelector('textarea[name*="[product_codes]"]');
                const date = row.querySelector('input[name*="[lot_date]"]');
                return { boxCode, quantity, productCodes, date, all: boxCode && quantity && productCodes && date };
            },

            getRowValues(inputs) {
                return {
                    boxCode: inputs.boxCode ? inputs.boxCode.value.trim() : '',
                    expectedQuantity: inputs.quantity ? parseInt(inputs.quantity.value) || 0 : 0,
                    productCodes: inputs.productCodes ? inputs.productCodes.value.trim() : '',
                    dateValue: inputs.date ? inputs.date.value.trim() : ''
                };
            },

            parseProductCodes(productCodes) {
                return productCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
            },

            getAllProductCodes() {
                const allCodes = [];
                document.querySelectorAll('#import_check_boxes_table tbody tr').forEach(row => {
                    const input = row.querySelector('textarea[name*="[product_codes]"]');
                    if (input && input.value.trim()) {
                        allCodes.push(...this.parseProductCodes(input.value.trim()));
                    }
                });
                return [...new Set(allCodes)];
            },

            findInternalDuplicates(codes) {
                const seen = {}, duplicates = [];
                codes.forEach(code => {
                    if (seen[code]) { if (!duplicates.includes(code)) duplicates.push(code); }
                    else seen[code] = true;
                });
                return duplicates;
            },

            findDuplicateBoxCodes(currentIndex, currentCodes) {
                const duplicates = [];
                document.querySelectorAll('#import_check_boxes_table tbody tr').forEach((row, index) => {
                    if (index === currentIndex) return;
                    const input = row.querySelector('input[name*="[box_code]"]');
                    if (input) {
                        const otherCode = input.value.trim();
                        currentCodes.forEach(code => { if (code && otherCode === code) duplicates.push(code); });
                    }
                });
                return [...new Set(duplicates)];
            },

            findDuplicateProductCodes(currentIndex, currentCodes) {
                const duplicates = [];
                document.querySelectorAll('#import_check_boxes_table tbody tr').forEach((row, index) => {
                    if (index === currentIndex) return;
                    const input = row.querySelector('textarea[name*="[product_codes]"]');
                    if (input && input.value.trim()) {
                        const otherCodes = this.parseProductCodes(input.value.trim());
                        currentCodes.forEach(code => { if (otherCodes.includes(code)) duplicates.push(code); });
                    }
                });
                return [...new Set(duplicates)];
            },

            markFieldError(field, bgColor = '#ffe6e6') {
                field.style.borderColor = '#e74c3c';
                field.style.backgroundColor = bgColor;
            },

            clearFieldError(field) {
                field.style.borderColor = '';
                field.style.backgroundColor = '';
            },

            updateBoxDisplay(index) {
                const inputs = this.getRowInputs(document.querySelectorAll('#import_check_boxes_table tbody tr')[index]);
                if (!inputs.all) return;

                const values = this.getRowValues(inputs);
                const productCodesList = this.parseProductCodes(values.productCodes);
                const actualQuantity = productCodesList.length;
                
                const statusDiv = document.getElementById(`box_status_${index}`);
                const messageDiv = document.getElementById(`box_message_${index}`);
                if (!statusDiv || !messageDiv) return;

                let messages = [];

                // Quantity check
                if (values.expectedQuantity > 0 || actualQuantity > 0) {
                    if (values.expectedQuantity === actualQuantity && values.expectedQuantity > 0) {
                        messages.push('<span style="color: green;">✅ Số lượng khớp (' + actualQuantity + ' mã)</span>');
                    } else {
                        messages.push('<span style="color: red;">❌ Số lượng không khớp</span><br><small>Dự kiến: ' + values.expectedQuantity + ' | Thực tế: ' + actualQuantity + '</small>');
                    }
                }

                // Date check
                messages.push(values.dateValue ? 
                    '<span style="color: #28a745;">✅ Date hợp lệ</span>' : 
                    '<span style="color: #e74c3c;">⚠️ Date là bắt buộc</span>');

                // Database status
                if (values.boxCode && this.cache.boxes[values.boxCode]) {
                    const status = this.cache.boxes[values.boxCode];
                    if (!status.exists) messages.push('<span style="color: #e74c3c;">❌ Mã thùng không tồn tại</span>');
                    else if (status.status !== 'unused') messages.push('<span style="color: #e74c3c;">❌ Mã thùng đã được sử dụng</span>');
                    else messages.push('<span style="color: #28a745;">✅ Mã thùng hợp lệ</span>');
                }

                // Assignment check
                if (values.productCodes) {
                    const assignedProducts = productCodesList.filter(code => 
                        this.cache.productAssignments[code] && this.cache.productAssignments[code].assigned
                    );
                    
                    if (assignedProducts.length > 0) {
                        messages.push('<span style="color: #ff6b35;">🚫 Có mã đã thuộc thùng khác:</span>');
                        assignedProducts.forEach(code => {
                            const info = this.cache.productAssignments[code];
                            messages.push(`<small style="color: #ff6b35;">• ${code} (thuộc thùng: ${info.box_barcode})</small>`);
                        });
                    } else if (productCodesList.length > 0) {
                        const allChecked = productCodesList.every(code => this.cache.productAssignments.hasOwnProperty(code));
                        if (allChecked) messages.push('<span style="color: #28a745;">✅ Tất cả mã chưa thuộc thùng nào</span>');
                    }
                }

                // Status
                let status;
                if (values.boxCode && values.productCodes && values.dateValue) {
                    status = values.expectedQuantity === actualQuantity ? 
                        '<span style="color: green;">✅ Hoàn thành</span>' : 
                        '<span style="color: #e74c3c;">❌ Có lỗi cần sửa</span>';
                } else {
                    const missing = [];
                    if (!values.boxCode) missing.push('mã thùng');
                    if (!values.productCodes) missing.push('mã sản phẩm');
                    if (!values.dateValue) missing.push('date');
                    status = '<span style="color: orange;">⚠️ Chưa nhập: ' + missing.join(', ') + '</span>';
                }

                statusDiv.innerHTML = status;
                messageDiv.innerHTML = messages.join('<br>') || '<em style="color: #666;">Nhập thông tin để kiểm tra</em>';
            },

            updateStats() {
                const rows = document.querySelectorAll('#import_check_boxes_table tbody tr');
                let totalBoxes = 0, totalActualCodes = 0, totalExpectedCodes = 0, validBoxes = 0;

                rows.forEach(row => {
                    const inputs = this.getRowInputs(row);
                    if (!inputs.all) return;

                    const values = this.getRowValues(inputs);
                    if (values.boxCode || values.expectedQuantity > 0 || values.productCodes) {
                        totalBoxes++;
                        totalExpectedCodes += values.expectedQuantity;

                        if (values.productCodes) {
                            totalActualCodes += this.parseProductCodes(values.productCodes).length;
                        }

                        const hasValidInfo = values.boxCode && values.productCodes && values.dateValue && values.expectedQuantity > 0;
                        const quantityMatch = values.expectedQuantity > 0 && values.productCodes && 
                                            values.expectedQuantity === this.parseProductCodes(values.productCodes).length;
                        
                        if (hasValidInfo && quantityMatch) validBoxes++;
                    }
                });

                document.getElementById('total-boxes').textContent = totalBoxes;
                document.getElementById('total-codes').textContent = totalActualCodes;
                document.getElementById('expected-codes').textContent = totalExpectedCodes;

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
            },

            updateSummary() {
                const summaryElement = document.getElementById('validation-summary');
                const invalidConditions = this.getInvalidConditions();
                
                if (invalidConditions.length === 0) {
                    summaryElement.className = 'validation-summary success';
                    summaryElement.innerHTML = '<h4>✅ Sẵn sàng cập nhật</h4><p>Tất cả thông tin bắt buộc đã được nhập đầy đủ và hợp lệ.</p>';
                } else {
                    summaryElement.className = 'validation-summary error';
                    summaryElement.innerHTML = `<h4>⚠️ Cần nhập thêm thông tin để có thể cập nhật</h4><ul>${invalidConditions.map(condition => `<li>${condition}</li>`).join('')}</ul>`;
                }
            },

            // ============ SUBMIT BUTTON CONTROL ============
            disableSubmitButtons() {
                this.toggleSubmitButtons(true);
            },

            enableSubmitButtons() {
                this.toggleSubmitButtons(false);
            },

            toggleSubmitButtons(disable) {
                const selectors = ['#publish', '#save-post', 'input[name="publish"]', 'input[name="save"]', 
                                '.editor-post-publish-button', '#publishing-action input[type="submit"]',
                                '#publishing-action .button-primary', '#submitdiv input[type="submit"]', '.button-primary[type="submit"]'];
                
                document.querySelectorAll(selectors.join(',')).forEach(button => {
                    button.disabled = disable;
                    button.classList.toggle('submit-button-disabled', disable);
                    button.title = disable ? 'Vui lòng kiểm tra và sửa lỗi trước khi cập nhật' : 'Có thể cập nhật';
                });
            },

            // ============ EVENT LISTENERS ============
            setupEventListeners() {
                // Main check button
                document.getElementById('check_all_validations').addEventListener('click', async (e) => {
                    await this.checkAll();
                });

                // Add box row
                document.getElementById('add_box_row').addEventListener('click', () => {
                    const tableBody = document.querySelector('#import_check_boxes_table tbody');
                    const row = document.createElement('tr');
                    row.innerHTML = `<?php echo str_replace(["\n", "'"], ["", "\\'"], render_import_box_row()); ?>`.replace(/__index__/g, this.boxRowIndex);
                    tableBody.appendChild(row);
                    this.initBoxRowEventListeners(this.boxRowIndex);
                    this.boxRowIndex++;
                    this.updateStats();
                });

                // Remove box row
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('remove-box-row')) {
                        e.target.closest('tr').remove();
                        this.updateStats();
                    }
                });

                // Form validation
                ['title', 'import_date'].forEach(fieldId => {
                    const input = document.getElementById(fieldId);
                    if (input) {
                        input.addEventListener(fieldId === 'title' ? 'input' : 'change', () => {
                            this.validateField(fieldId);
                            this.updateSummary();
                        });
                    }
                });

                // Form submit prevention
                this.setupSubmitPrevention();

                // Initialize existing rows
                document.querySelectorAll('#import_check_boxes_table tbody tr').forEach((row, index) => {
                    this.initBoxRowEventListeners(index);
                });

                // Submit button observer
                this.setupSubmitButtonObserver();
            },

            initBoxRowEventListeners(index) {
                const selectors = [
                    { name: 'product_codes', event: 'input', clearCache: (val, oldVal) => {
                        // Clear cache cho tất cả codes cũ
                        if (oldVal) {
                            const oldCodes = this.parseProductCodes(oldVal);
                            oldCodes.forEach(code => {
                                delete this.cache.products[code];
                                delete this.cache.productAssignments[code];
                            });
                        }
                        // Clear cache cho codes mới nếu có
                        if (val) {
                            const newCodes = this.parseProductCodes(val);
                            newCodes.forEach(code => {
                                delete this.cache.products[code];
                                delete this.cache.productAssignments[code];
                            });
                        }
                    }},
                    { name: 'box_code', event: 'input', clearCache: (val, oldVal) => {
                        if (oldVal) delete this.cache.boxes[oldVal.trim()];
                        if (val) delete this.cache.boxes[val.trim()];
                    }},
                    { name: 'product_quantity', event: 'input' },
                    { name: 'lot_date', event: 'change' }
                ];

                selectors.forEach(selector => {
                    const input = document.querySelector(`[name="import_check_boxes[${index}][${selector.name}]"]`);
                    if (input) {
                        let oldValue = input.value; // Lưu giá trị cũ
                        
                        input.addEventListener(selector.event, () => {
                            if (selector.clearCache) {
                                selector.clearCache(input.value, oldValue);
                            }
                            oldValue = input.value; // Cập nhật giá trị cũ
                            
                            this.updateBoxDisplay(index);
                            this.updateStats();
                            
                            // Reset validation state khi có thay đổi
                            this.resetValidationState();
                        });
                    }
                });
            },

            resetValidationState() {
                // Reset validation state về false khi có thay đổi dữ liệu
                Object.keys(this.conditions).forEach(key => {
                    this.state[this.conditions[key]] = false;
                });
                
                // Clear duplicate highlighting
                this.clearAllDuplicateHighlighting();
                
                // Disable submit buttons
                this.disableSubmitButtons();
                
                // Update summary để hiển thị cần kiểm tra lại
                const summaryElement = document.getElementById('validation-summary');
                summaryElement.className = 'validation-summary error';
                summaryElement.innerHTML = `
                    <h4>⚠️ Dữ liệu đã thay đổi - Cần kiểm tra lại</h4>
                    <ul><li>❌ Vui lòng bấm "Kiểm tra tất cả" để validate dữ liệu mới</li></ul>
                `;
            },

            setupSubmitPrevention() {
                const form = document.getElementById('post');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        const allValid = Object.values(this.state).every(v => v === true);
                        if (!allValid) {
                            e.preventDefault();
                            e.stopPropagation();
                            alert('❌ Vui lòng click "Kiểm tra tất cả" và sửa các lỗi trước khi cập nhật!');
                            return false;
                        }
                    });
                }

                document.addEventListener('click', (e) => {
                    const isSubmitButton = e.target.matches(['#publish', '#save-post', 'input[name="publish"]', 
                        'input[name="save"]', '.editor-post-publish-button', '#publishing-action input[type="submit"]',
                        '#publishing-action .button-primary', '#submitdiv input[type="submit"]', '.button-primary[type="submit"]'].join(','));

                    if (isSubmitButton && e.target.disabled) {
                        e.preventDefault();
                        e.stopPropagation();
                        alert('❌ Vui lòng click "Kiểm tra tất cả" và sửa các lỗi trước khi cập nhật!');
                        return false;
                    }
                }, true);
            },

            setupSubmitButtonObserver() {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach(mutation => {
                        if (mutation.addedNodes.length > 0) {
                            const hasSubmitButton = Array.from(mutation.addedNodes).some(node => {
                                return node.nodeType === 1 && node.matches && (
                                    node.matches('#publish') || node.matches('#save-post') ||
                                    node.matches('input[name="publish"]') || node.matches('input[name="save"]') ||
                                    node.matches('.button-primary[type="submit"]')
                                );
                            });
                            if (hasSubmitButton) setTimeout(() => this.disableSubmitButtons(), 100);
                        }
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            },

            // ============ API HELPER ============
            async apiCall(action, data) {
                return new Promise((resolve, reject) => {
                    jQuery.ajax({
                        url: ajax_url,
                        type: 'POST',
                        data: { action, ...data, nonce: check_barcode_nonce },
                        success: (response) => response.success ? resolve(response.data) : reject('API call failed'),
                        error: () => reject('Network error')
                    });
                });
            }
        };

        // ============ INITIALIZATION ============
        document.addEventListener('DOMContentLoaded', () => ValidationManager.init());
        if (document.readyState !== 'loading') ValidationManager.init();
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
                title="Mã định danh thùng (phải tồn tại trong DB với status=unused)" />
            <span class="db-status-indicator" id="box_db_status_<?php echo $index; ?>"></span>
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
                placeholder="Nhập mã sản phẩm (phải tồn tại trong DB với status=unused)"
                title="Danh sách mã sản phẩm trong thùng này"
            ><?php echo esc_textarea($product_codes); ?></textarea>
            <span class="db-status-indicator" id="products_db_status_<?php echo $index; ?>"></span>
        </td>
        
        <td>
            <input type="date"
                name="import_check_boxes[<?php echo $index; ?>][lot_date]"
                value="<?php echo esc_attr($lot_date); ?>"
                placeholder="Nhập lô date"
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

add_action('wp_ajax_check_barcode_status', 'ajax_check_barcode_status');
add_action('wp_ajax_nopriv_check_barcode_status', 'ajax_check_barcode_status');

function ajax_check_barcode_status() {
    global $wpdb;
    
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'check_barcode_nonce')) {
        wp_die('Security check failed');
    }
    
    $type = sanitize_text_field($_POST['type']);
    $codes = $_POST['codes'];
    $response = [];
    
    if ($type === 'box') {
        $box_table = BIZGPT_PLUGIN_WP_BOX_MANAGER;
        
        foreach ($codes as $code) {
            $code = sanitize_text_field($code);
            if (empty($code)) continue;
            
            $box = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $box_table WHERE barcode = %s",
                $code
            ));
            
            if (!$box) {
                $response[$code] = [
                    'exists' => false,
                    'status' => null,
                    'message' => 'Mã thùng không tồn tại trong hệ thống'
                ];
            } else {
                $response[$code] = [
                    'exists' => true,
                    'status' => $box->status,
                    'message' => $box->status === 'unused' ? 'OK' : 'Mã thùng đã được sử dụng'
                ];
            }
        }
    } else if ($type === 'product') {
        $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
        
        foreach ($codes as $code) {
            $code = sanitize_text_field($code);
            if (empty($code)) continue;
            
            $barcode = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $barcode_table WHERE barcode = %s",
                $code
            ));
            
            if (!$barcode) {
                $response[$code] = [
                    'exists' => false,
                    'status' => null,
                    'message' => 'Mã sản phẩm không tồn tại trong hệ thống'
                ];
            } else {
                $response[$code] = [
                    'exists' => true,
                    'status' => $barcode->status,
                    'message' => $barcode->status === 'unused' ? 'OK' : 'Mã sản phẩm đã được sử dụng'
                ];
            }
        }
    }
    
    wp_send_json_success($response);
}

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'import_check') {
        ?>
        <script>
            var check_barcode_nonce = '<?php echo wp_create_nonce('check_barcode_nonce'); ?>';
            var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script>
        <?php
    }
});

add_action('wp_ajax_check_product_box_assignment', 'check_product_box_assignment_callback');
add_action('wp_ajax_nopriv_check_product_box_assignment', 'check_product_box_assignment_callback');

function check_product_box_assignment_callback() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'check_barcode_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $product_codes = isset($_POST['product_codes']) ? $_POST['product_codes'] : array();
    
    if (empty($product_codes) || !is_array($product_codes)) {
        wp_send_json_error('No product codes provided');
        return;
    }
    
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_BARCODE;
    
    $results = array();
    
    try {
        $sanitized_codes = array();
        foreach ($product_codes as $code) {
            $sanitized_code = sanitize_text_field(trim($code));
            if (!empty($sanitized_code)) {
                $sanitized_codes[] = $sanitized_code;
            }
        }
        
        if (empty($sanitized_codes)) {
            wp_send_json_error('No valid product codes provided');
            return;
        }
        
        // Create placeholders for prepared statement
        $placeholders = implode(',', array_fill(0, count($sanitized_codes), '%s'));
        
        // Query để kiểm tra các mã sản phẩm và box_barcode của chúng
        $query = $wpdb->prepare(
            "SELECT barcode, box_barcode, status 
             FROM {$table_name} 
             WHERE barcode IN ($placeholders)",
            $sanitized_codes
        );
        
        $db_results = $wpdb->get_results($query, ARRAY_A);
        
        // Initialize results for all requested codes
        foreach ($sanitized_codes as $code) {
            $results[$code] = array(
                'exists' => false,
                'assigned' => false,
                'box_barcode' => null,
                'status' => null
            );
        }
        
        // Process database results
        if ($db_results) {
            foreach ($db_results as $row) {
                $barcode = $row['barcode'];
                $box_barcode = $row['box_barcode'];
                $status = $row['status'];
                
                $results[$barcode] = array(
                    'exists' => true,
                    'assigned' => !empty($box_barcode),
                    'box_barcode' => $box_barcode,
                    'status' => $status
                );
            }
        }
        
        wp_send_json_success($results);
        
    } catch (Exception $e) {
        error_log('Error in check_product_box_assignment_callback: ' . $e->getMessage());
        wp_send_json_error('Database error occurred');
    }
}



