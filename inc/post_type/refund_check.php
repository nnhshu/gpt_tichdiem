<?php

function register_refund_check_post_type() {
    register_post_type('refund_check', array(
        'labels' => array(
            'name' => 'Đơn hoàn hàng',
            'singular_name' => 'Đơn hoàn hàng',
            'add_new' => 'Thêm đơn hàng hoàn mới',
            'add_new_item' => 'Thêm mới đơn hàng hoàn',
            'edit_item' => 'Chỉnh sửa đơn hàng hoàn',
            'new_item' => 'Thêm đơn hàng mới',
            'view_item' => 'Xem mã định danh trong đơn hàng',
            'search_items' => 'Tìm đơn hàng hoàn',
            'not_found' => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác'
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => array('title'),
        'has_archive' => true,
    ));
}
add_action('init', 'register_refund_check_post_type');

function gpt_render_refund_check_tab() {
    $args = array(
        'post_type'      => 'refund_check',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);

    echo '<div class="wrap">';
    echo '<h2>📦 Danh sách hoàn hàng</h2>';
    echo '<p><a href="' . admin_url('post-new.php?post_type=refund_check') . '" class="button button-primary">+ Thêm hoàn hàng mới</a></p>';

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
        echo '<p>📭 Không có hoàn hàng nào.</p>';
    }

    wp_reset_postdata();
    echo '</div>';
}


function add_refund_check_metaboxes() {
    add_meta_box('import_check_fields', 'Thông tin hoàn hàng', 'render_refund_check_fields', 'refund_check', 'normal', 'default');
}
add_action('add_meta_boxes', 'add_refund_check_metaboxes');

function render_refund_check_fields($post) {
    $order_id = get_post_meta($post->ID, 'order_id', true);
    $refund_images = get_post_meta($post->ID, 'refund_images', true);
    $macao_ids = get_post_meta($post->ID, 'macao_ids', true);
    $refund_date = get_post_meta($post->ID, 'refund_date', true);
    $order_refund_by = get_post_meta($post->ID, 'order_refund_by', true);

    wp_nonce_field('save_refund_check_fields', 'order_check_nonce');

    $refund_date = get_post_meta($post->ID, 'refund_date', true);
    if (empty($refund_date)) {
        $refund_date = current_time('mysql');
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
        <label for="refund_date">Ngày giờ hoàn:</label>
        <input type="datetime-local" name="refund_date" id="refund_date"
            value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($refund_date))); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_refund_by">Người hoàn kho:</label>
        <input type="text" name="order_refund_by" id="order_refund_by" value="<?php echo esc_attr($order_refund_by); ?>" style="width:100%;">
    </div>
    <div class="form-group">
        <label for="refund_images">Ảnh đơn hàng (có thể chọn nhiều):</label>
        <input type="hidden" name="refund_images" id="refund_images" value="<?php echo esc_attr($refund_images); ?>">
        <button type="button" class="button upload_gallery_button">Chọn ảnh</button>
        <div id="order_images_preview" style="margin-top:10px;">
            <?php
            if (!empty($refund_images)) {
                $image_urls = explode(',', $refund_images);
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
                    $('#refund_images').val(attachment_urls.join(','));
                    $('#order_images_preview').html(preview_html);
                }).open();
            });
        });
    </script>

    <?php
}

function refund_update_post_meta_if_changed($post_id, $key, $new_value) {
    $old_value = get_post_meta($post_id, $key, true);
    if ($new_value !== $old_value) {
        update_post_meta($post_id, $key, $new_value);
    }
}

function save_refund_check_fields($post_id) {
    if (!isset($_POST['order_check_nonce']) || !wp_verify_nonce($_POST['order_check_nonce'], 'save_refund_check_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    global $wpdb;
    $order_table   = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;
    $macao_table   = BIZGPT_PLUGIN_WP_BARCODE;

    refund_update_post_meta_if_changed($post_id, 'order_id', sanitize_text_field($_POST['order_id']));
    refund_update_post_meta_if_changed($post_id, 'refund_images', sanitize_text_field($_POST['refund_images']));
    refund_update_post_meta_if_changed($post_id, 'refund_date', sanitize_text_field($_POST['refund_date']));
    refund_update_post_meta_if_changed($post_id, 'order_refund_by', sanitize_text_field($_POST['order_refund_by']));

    $refund_check_products = sanitize_text_field($_POST['refund_check_products'] ?? '');
    if ($refund_check_products) {
        update_post_meta($post_id, '_refund_check_products', $refund_check_products);
    }

    $codes = explode(' ', $refund_check_products);
    $codes = array_map('trim', $codes);

    $logs = get_post_meta($post_id, '_refund_logs', true);
    if (!is_array($logs)) {
        $logs = [];
    }

    $processed_codes = get_post_meta($post_id, '_processed_codes', true);
    if (!is_array($processed_codes)) {
        $processed_codes = [];
    }

    foreach ($codes as $code) {
        $code = trim($code);

        if (in_array($code, $processed_codes)) {
            $logs[] = [
                'status'   => sprintf("[%s]:Mã đã được xử lý", $code),
                'timestamp'=> current_time('mysql')
            ];
            continue;
        }
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $macao_table WHERE barcode = %s", $code));
        if ($result) {
            $wpdb->update(
                $macao_table,
                [
                    'province'   => '',
                    'channel'    => '',
                    'distributor'=> ''
                ],
                ['barcode' => $code]
            );

            $logs[] = [
                'status'   => sprintf("[%s] ✅ Cập nhật mã %s vào đơn #%d và làm rỗng các cột province, channel, distributor", current_time('mysql'), $code, $post_id),
                'timestamp'=> current_time('mysql')
            ];
            $processed_codes[] = $code;
        } else {
            $logs[] = [
                'status'   => sprintf("[%s] ❌ Không tìm thấy mã %s trong bảng cơ sở dữ liệu", current_time('mysql'), $code),
                'timestamp'=> current_time('mysql')
            ];
        }
    }
    update_post_meta($post_id, '_refund_logs', $logs);
    update_post_meta($post_id, '_processed_codes', $processed_codes);
}
add_action('save_post', 'save_refund_check_fields');

add_action('save_post', 'save_refund_check_fields');

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    }
});

add_action('add_meta_boxes', function() {
    add_meta_box('refund_check_products_box', 'Danh sách mã định danh hoàn kho', 'display_refund_check_products_box', 'refund_check', 'normal', 'high');
    add_meta_box('refund_status_box', 'Trạng thái đơn hàng', 'render_refund_status_box', 'refund_check', 'side');
    add_meta_box('refund_logs_box', 'Lịch sử trạng thái đơn', 'render_refund_logs_box', 'refund_check', 'side');
    add_meta_box(
        'import_logs_metabox',
        'Nhật ký hoàn hàng & thay đổi mã định danh',
        'display_refund_logs_metabox',
        'refund_check',
        'normal'
    );
});

function display_refund_logs_metabox($post) {
    // Lấy logs từ post_meta
    $logs = get_post_meta($post->ID, '_refund_logs', true);

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

function render_refund_status_box($post) {
    $current_status = get_post_meta($post->ID, 'refund_status', true);
    $statuses = ['Hoàn hàng'];

    echo '<select name="refund_status">';
    foreach ($statuses as $status) {
        echo '<option value="' . esc_attr($status) . '" ' . selected($current_status, $status, false) . '>' . esc_html($status) . '</option>';
    }
    echo '</select>';
}

function render_refund_logs_box($post) {
    $logs = get_post_meta($post->ID, 'refund_status_logs', true);
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

function display_refund_check_products_box($post) {
    $refund_check_products = get_post_meta($post->ID, '_refund_check_products', true);

    if (!empty($refund_check_products)) {
        $refund_check_products = implode("\n", preg_split('/[\s,;]+/', $refund_check_products));
    }
    ?>
    <div class="form-group">
        <label for="refund_check_products">Nhập mã định danh. (Mỗi mã định danh là 1 dòng):</label>
        <textarea name="refund_check_products" id="refund_check_products" rows="5" style="width:100%;"><?php echo esc_textarea($refund_check_products); ?></textarea>
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
