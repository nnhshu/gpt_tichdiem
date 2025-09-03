<?php

function register_order_check_post_type() {
    register_post_type('order_check', array(
        'labels' => array(
            'name' => 'Xuất đơn định danh',
            'singular_name' => 'Xuất đơn định danh',
            'add_new' => 'Thêm đơn định danh',
            'add_new_item' => 'Thêm đơn định danh',
            'edit_item' => 'Chỉnh sửa đơn định danh',
            'new_item' => 'Thêm đơn định danh',
            'view_item' => 'Xem mã định danh trong đơn định danh',
            'search_items' => 'Tìm đơn định danh truy xuất',
            'not_found' => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác'
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'gpt-manager-tem',
        'supports' => array('title'),
        'has_archive' => true,
        'capability_type' => 'post', // BẮT BUỘC
        'map_meta_cap' => true,
    ));
}
add_action('init', 'register_order_check_post_type');

function gpt_render_ordercheck_tab() {
    $args = array(
        'post_type'      => 'order_check',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);

    echo '<div class="wrap">';
    echo '<h2>📦 Danh sách Order Check</h2>';
    echo '<p><a href="' . admin_url('post-new.php?post_type=order_check') . '" class="button button-primary">+ Thêm Order Check mới</a></p>';

    if ($query->have_posts()) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
                <th>Tiêu đề</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Người tạo</th>
                <th>Người duyệt</th>
                <th>Thao tác</th>
              </tr></thead>';
        echo '<tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            $order_status = get_post_meta(get_the_ID(), 'order_status', true);
            $approved_by = get_post_meta(get_the_ID(), 'approved_by', true);
            
            function get_display_status_text($status) {
                $status_map = [
                    'pending' => 'Chờ duyệt',
                    'completed' => 'Hoàn thành'
                ];
                return isset($status_map[$status]) ? $status_map[$status] : ($status ?: 'Chờ duyệt');
            }
            
            $status_class = '';
            $status_text = get_display_status_text($order_status);
            
            switch ($order_status) {
                case 'completed':
                    $status_class = 'style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-weight: bold;"';
                    break;
                case 'pending':
                default:
                    $status_class = 'style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-weight: bold;"';
                    break;
            }
            
            echo '<tr>';
            echo '<td><strong><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a></strong></td>';
            echo '<td><span ' . $status_class . '>' . esc_html($status_text) . '</span></td>';
            echo '<td>' . get_the_date() . '</td>';
            echo '<td>' . get_the_author() . '</td>';
            echo '<td>' . ($approved_by ? esc_html($approved_by) : '-') . '</td>';
            echo '<td><a href="' . get_edit_post_link(get_the_ID()) . '" class="button small">Sửa</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>📭 Không có Order Check nào.</p>';
    }

    wp_reset_postdata();
    echo '</div>';
}

function render_order_check_fields($post) {
    $order_images = get_post_meta($post->ID, 'order_images', true);
    $order_po = get_post_meta($post->ID, 'order_check_po_id', true);
    $order_date = get_post_meta($post->ID, 'order_date', true);
    $order_export_by = get_post_meta($post->ID, 'order_export_by', true);
    $channel = get_post_meta($post->ID, 'order_check_channel', true);
    $distributor = get_post_meta($post->ID, 'order_check_distributor', true);
    $province = get_post_meta($post->ID, 'order_check_province', true);
    $employee = get_post_meta($post->ID, 'order_check_employee', true);
    $current_user = wp_get_current_user();
    $order_export_by = $current_user->user_login;

    $order_export_by_meta = get_post_meta($post->ID, 'order_export_by', true);
    if (!empty($order_export_by_meta)) {
        $order_export_by = $order_export_by_meta;
    }

    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;
    $table_distributors = BIZGPT_PLUGIN_WP_DISTRIBUTORS;
    $table_employees = BIZGPT_PLUGIN_WP_EMPLOYEES;

    $channel_rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    $all_distributors = $wpdb->get_results("SELECT id, title, channel_id FROM $table_distributors ORDER BY title ASC");
    $all_employees = $wpdb->get_results("SELECT id, code, full_name, position FROM $table_employees ORDER BY full_name ASC");
    
    $provinces = [
        'An Giang' => 'AG', 'Bắc Ninh' => 'BN', 'Cà Mau' => 'CM', 'Cao Bằng' => 'CB',
        'Đắk Lắk' => 'DL', 'Điện Biên' => 'DB', 'Đồng Nai' => 'DG', 'Đồng Tháp' => 'DT',
        'Gia Lai' => 'GL', 'Hà Tĩnh' => 'HT', 'Hưng Yên' => 'HY', 'Khánh Hoà' => 'KH',
        'Lai Châu' => 'LC', 'Lâm Đồng' => 'LD', 'Lạng Sơn' => 'LS', 'Lào Cai' => 'LA',
        'Nghệ An' => 'NA', 'Ninh Bình' => 'NB', 'Phú Thọ' => 'PT', 'Quảng Ngãi' => 'QG',
        'Quảng Ninh' => 'QN', 'Quảng Trị' => 'QT', 'Sơn La' => 'SL', 'Tây Ninh' => 'TN',
        'Thái Nguyên' => 'TG', 'Thanh Hóa' => 'TH', 'TP. Cần Thơ' => 'CT', 'TP. Đà Nẵng' => 'DN',
        'TP. Hà Nội' => 'HN', 'TP. Hải Phòng' => 'HP', 'TP. Hồ Chí Minh' => 'SG', 'TP. Huế' => 'HUE',
        'Tuyên Quang' => 'TQ', 'Vĩnh Long' => 'VL'
    ];

    wp_nonce_field('save_order_check_fields', 'order_check_nonce');

    $order_date = get_post_meta($post->ID, 'order_date', true);
    if (empty($order_date)) {
        $order_date = current_time('mysql');
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
        <label for="order_check_po_id">PO đơn hàng:</label>
        <input type="text" name="order_check_po_id" id="order_check_po_id"
            value="<?php echo esc_attr($order_po); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_check_province">Tỉnh thành:</label>
        <select name="order_check_province" style="width:100%;">
            <?php foreach ($provinces as $value => $label): ?>
                <option value="<?php echo esc_attr($label); ?>" <?php selected($province, $label); ?>>
                    <?php echo esc_html($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="order_check_channel">Kênh:</label>
        <select name="order_check_channel" id="order_check_channel" style="width:100%;">
            <option value="">-- Chọn kênh --</option>
            <?php foreach ($channel_rows as $row): ?>
                <option value="<?php echo esc_attr($row->channel_code); ?>" 
                        data-channel-id="<?php echo esc_attr($row->id); ?>"
                        <?php selected($channel, $row->channel_code); ?>>
                    <?php echo esc_html($row->title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="order_check_distributor">Nhà phân phối:</label>
        <select name="order_check_distributor" id="order_check_distributor" style="width:100%;">
            <option value="">-- Chọn nhà phân phối --</option>
            <?php if ($channel && $distributor): ?>
                <?php 
                $selected_channel = null;
                
                foreach ($channel_rows as $row) {
                    if ($row->channel_code == $channel) {
                        $selected_channel = $row->id;
                        break;
                    }
                }
                
                if ($selected_channel) {
                    foreach ($all_distributors as $dist) {
                        if ($dist->channel_id == $selected_channel) {
                            $selected = ($dist->id == $distributor) ? 'selected' : '';
                            echo '<option value="' . esc_attr($dist->id) . '" ' . $selected . '>' . esc_html($dist->title) . '</option>';
                        }
                    }
                }
                ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="order_check_employee">Nhân viên phụ trách:</label>
        <select name="order_check_employee" id="order_check_employee" style="width:100%;">
            <option value="">-- Chọn nhân viên --</option>
            <?php foreach ($all_employees as $emp): ?>
                <option value="<?php echo esc_attr($emp->id); ?>" <?php selected($employee, $emp->id); ?>>
                    [<?php echo esc_html($emp->position); ?>] <?php echo esc_html($emp->code); ?> - <?php echo esc_html($emp->full_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="order_date">Ngày giờ xuất:</label>
        <input type="datetime-local" name="order_date" id="order_date"
            value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($order_date))); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_export_by">Người xuất kho:</label>
        <input type="text" name="order_export_by" id="order_export_by" value="<?php echo esc_attr($order_export_by); ?>" style="width:100%;" disabled>
    </div>
    <div class="form-group">
        <label for="order_images">Ảnh đơn hàng (có thể chọn nhiều):</label>
        <input type="hidden" name="order_images" id="order_images" value="<?php echo esc_attr($order_images); ?>">
        <button type="button" class="button upload_gallery_button">Chọn ảnh</button>
        <div id="order_images_preview" style="margin-top:10px;">
            <?php
            if (!empty($order_images)) {
                $image_urls = explode(',', $order_images);
                foreach ($image_urls as $img) {
                    echo '<img src="' . esc_url($img) . '" style="max-width:100px;margin:5px;border:1px solid #ddd;">';
                }
            }
            ?>
        </div>
    </p>

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
                    $('#order_images').val(attachment_urls.join(','));
                    $('#order_images_preview').html(preview_html);
                }).open();
            });

            $('#order_check_channel').on('change', function() {
                
                var selectedOption = $(this).find('option:selected');
                var channelId = selectedOption.data('channel-id');
                var distributorSelect = $('#order_check_distributor');

                console.log(channelId);
                
                distributorSelect.prop('disabled', true);
                distributorSelect.html('<option value="">🔄 Đang tải...</option>');
                
                if (channelId) {
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'gpt_get_distributors_by_channel_order',
                            channel_id: channelId,
                            nonce: '<?php echo wp_create_nonce("gpt_distributor_nonce"); ?>'
                        },
                        success: function(response) {
                            distributorSelect.html('<option value="">-- Chọn nhà phân phối --</option>');
                            
                            if (response.success && response.data.length > 0) {
                                $.each(response.data, function(i, distributor) {
                                    distributorSelect.append(
                                        '<option value="' + distributor.id + '">' + 
                                        distributor.title + 
                                        '</option>'
                                    );
                                });
                            } else {
                                distributorSelect.append('<option value="">Không có nhà phân phối</option>');
                            }
                            
                            distributorSelect.prop('disabled', false);
                        },
                        error: function() {
                            distributorSelect.html('<option value="">❌ Lỗi khi tải dữ liệu</option>');
                            distributorSelect.prop('disabled', false);
                        }
                    });
                } else {
                    distributorSelect.html('<option value="">-- Chọn nhà phân phối --</option>');
                    distributorSelect.prop('disabled', false);
                }
            });

            <?php if ($channel): ?>
                $('#order_check_channel').trigger('change');
            <?php endif; ?>
        });
    </script>

    <?php
}

add_action('wp_ajax_gpt_get_distributors_by_channel_order', 'gpt_get_distributors_by_channel_order');
add_action('wp_ajax_nopriv_gpt_get_distributors_by_channel_order', 'gpt_get_distributors_by_channel_order');

function gpt_get_distributors_by_channel_order() {

    if (!wp_verify_nonce($_POST['nonce'], 'gpt_distributor_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
        return;
    }

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
            wp_send_json_success([]);
        }
    } else {
        wp_send_json_error(['message' => 'Channel ID không hợp lệ']);
    }

    wp_die();
}

function update_post_meta_if_changed($post_id, $key, $new_value) {
    $old_value = get_post_meta($post_id, $key, true);
    if ($new_value !== $old_value) {
        update_post_meta($post_id, $key, $new_value);
    }
}

function save_order_check_fields($post_id) {
    if (!isset($_POST['order_check_nonce']) || !wp_verify_nonce($_POST['order_check_nonce'], 'save_order_check_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    global $wpdb;
    $order_table   = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;
    $barcode_table   = BIZGPT_PLUGIN_WP_BARCODE;
    $sellout_table = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS_SELL_OUT;
    $box_manager_table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    $old_status = get_post_meta($post_id, 'order_status', true);
    $new_status = isset($_POST['order_status']) ? sanitize_text_field($_POST['order_status']) : 'pending';
    
    function get_status_text($status) {
        $status_map = [
            'pending' => 'Chờ duyệt',
            'completed' => 'Hoàn thành'
        ];
        return isset($status_map[$status]) ? $status_map[$status] : $status;
    }

    // Lưu single products data
    $single_items = $_POST['order_check_single_products'] ?? [];
    $existing_single_items = get_post_meta($post_id, '_order_check_single_items', true);
    if ($single_items !== $existing_single_items) {
        update_post_meta($post_id, '_order_check_single_items', $single_items);
    }

    // Meta fields
    $current_user = wp_get_current_user();
    $order_export_by = $current_user->user_login;
    update_post_meta_if_changed($post_id, 'order_check_po_id', sanitize_text_field($_POST['order_check_po_id']));
    update_post_meta_if_changed($post_id, 'order_images', sanitize_text_field($_POST['order_images']));
    update_post_meta_if_changed($post_id, 'order_date', sanitize_text_field($_POST['order_date']));
    update_post_meta_if_changed($post_id, 'order_export_by', sanitize_text_field($_POST['order_export_by']) ?  sanitize_text_field($_POST['order_export_by']) : $order_export_by);
    update_post_meta_if_changed($post_id, 'order_check_channel', sanitize_text_field($_POST['order_check_channel']));
    update_post_meta_if_changed($post_id, 'order_check_province', sanitize_text_field($_POST['order_check_province']));
    update_post_meta_if_changed($post_id, 'order_check_distributor', sanitize_text_field($_POST['order_check_distributor']));
    update_post_meta_if_changed($post_id, 'order_check_employee', sanitize_text_field($_POST['order_check_employee']));
    
    // Lấy ra tỉnh & kênh
    $province = sanitize_text_field($_POST['order_check_province']);
    $channel = sanitize_text_field($_POST['order_check_channel']);

    // Lưu bulk products data
    $items = $_POST['order_check_products'] ?? [];
    $existing_items = get_post_meta($post_id, '_order_check_line_items', true);
    if ($items !== $existing_items) {
        update_post_meta($post_id, '_order_check_line_items', $items);
    }
    
    // Xử lý thay đổi status
    if ($new_status !== $old_status) {
        update_post_meta($post_id, 'order_status', $new_status);
        
        $status_logs = get_post_meta($post_id, 'order_status_logs', true);
        if (!is_array($status_logs)) $status_logs = [];
        
        $current_user = wp_get_current_user();
        $user_display_name = $current_user->display_name ?: $current_user->user_login;
        
        $status_logs[] = [
            'status' => get_status_text($new_status), 
            'timestamp' => current_time('mysql'),
            'user' => $user_display_name
        ];
        update_post_meta($post_id, 'order_status_logs', $status_logs);
        
        // Nếu chuyển sang "completed", lưu người duyệt
        if ($new_status === 'completed') {
            update_post_meta($post_id, 'approved_by', $user_display_name);
            update_post_meta($post_id, 'approved_at', current_time('mysql'));
        }
    }

    // Khi status = completed, xử lý cả bulk products và single products
    if ($new_status === 'completed') {
        // Xóa dữ liệu cũ trong order_table
        $wpdb->delete($order_table, ['order_id' => $post_id]);

        $inventory_logs = get_post_meta($post_id, '_inventory_logs', true);
        if (!is_array($inventory_logs)) $inventory_logs = [];
        $timestamp = current_time('mysql');

        // ========== XỬ LÝ BULK PRODUCTS (order_check_products) ==========
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $box_quantity = intval($item['box_quantity']);
            $box_codes = sanitize_textarea_field($item['box_codes']);
            $lot_name = sanitize_text_field($item['lot_name'] ?? '');

            if (!$product_id || $box_quantity <= 0 || empty($box_codes)) continue;

            $product = wc_get_product($product_id);
            $title = $product ? $product->get_name() : '';
            $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);

            // Lấy tất cả mã sản phẩm từ các thùng
            $box_codes_array = array_filter(array_map('trim', explode("\n", $box_codes)));
            $all_product_codes = [];
            
            foreach ($box_codes_array as $box_code) {
                $product_codes = $wpdb->get_col($wpdb->prepare(
                    "SELECT barcode FROM $barcode_table 
                    WHERE box_barcode = %s AND product_id = %s",
                    $box_code, $custom_prod_id
                ));
                
                if (!empty($product_codes)) {
                    $all_product_codes = array_merge($all_product_codes, $product_codes);
                }

                // Cập nhật box_manager_table
                $updated_rows = $wpdb->update(
                    $box_manager_table,
                    [
                        'province' => $province,
                        'channel' => $channel,
                        'order_id' => $post_id,
                        'status' => 'delivery'
                    ],
                    ['barcode' => $box_code],
                    ['%s', '%s', '%d', '%s'],
                    ['%s']
                );

                if ($updated_rows > 0) {
                    $inventory_logs[] = sprintf("[%s] 📦 Cập nhật thùng [%s]: Province=%s, Channel=%s, OrderID=%d, Status=delivery", 
                        $timestamp, $box_code, $province, $channel, $post_id);
                } else {
                    $inventory_logs[] = sprintf("[%s] ⚠️ Không tìm thấy thùng [%s] trong hệ thống", 
                        $timestamp, $box_code);
                }
            }

            $actual_product_count = count($all_product_codes);

            // Trừ tồn kho cho bulk products
            if ($product && $actual_product_count > 0) {
                $stock = $product->get_stock_quantity();
                $new_stock = $stock - $actual_product_count;
                
                if ($new_stock >= 0) {
                    $product->set_stock_quantity($new_stock);
                    $product->save();
                    $inventory_logs[] = sprintf("[%s] ✅ [BULK] Trừ %d mã [%s] (ID: %d) từ %d thùng trong đơn #%d. Kho: %d → %d", 
                        $timestamp, $actual_product_count, $title, $product_id, count($box_codes_array), $post_id, $stock, $new_stock);
                } else {
                    $inventory_logs[] = sprintf("[%s] ❌ [BULK] Không đủ tồn kho cho [%s] (còn %d, cần %d)", 
                        $timestamp, $title, $stock, $actual_product_count);
                }
            }

            // Lưu vào order_table cho bulk products
            if ($actual_product_count > 0) {
                $wpdb->insert($order_table, [
                    'order_id' => $post_id,
                    'title' => $title,
                    'quantity' => $actual_product_count,
                    'barcode' => implode("\n", $all_product_codes),
                    'province' => $province,
                    'channel' => $channel,
                    'type' => 'bulk'
                ]);
            }

            // Cập nhật barcode table với thông tin đơn hàng cho bulk
            foreach ($all_product_codes as $product_code) {
                $wpdb->update(
                    $barcode_table, 
                    [
                        'order_by_product_id' => $post_id,
                        'channel' => $channel,
                        'province' => $province,
                        'lot' => $lot_name,
                    ], 
                    ['barcode' => $product_code]
                );
            }
        }

        // ========== XỬ LÝ SINGLE PRODUCTS (order_check_single_products) ==========
        foreach ($single_items as $single_item) {
            $product_id = intval($single_item['product_id']);
            $quantity = intval($single_item['quantity']);
            $lot_name = sanitize_text_field($single_item['lot_name']);
            $lot_date = sanitize_text_field($single_item['lot_date']);
            $product_codes = sanitize_textarea_field($single_item['product_codes']);

            if (!$product_id || $quantity <= 0 || empty($product_codes)) continue;

            $product = wc_get_product($product_id);
            $title = $product ? $product->get_name() : '';
            $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);

            // Xử lý mã sản phẩm đơn lẻ
            $codes_array = array_filter(array_map('trim', explode("\n", $product_codes)));
            $actual_codes_count = count($codes_array);

            // Trừ tồn kho cho single products
            if ($product && $actual_codes_count > 0) {
                $stock = $product->get_stock_quantity();
                $new_stock = $stock - $actual_codes_count;
                
                if ($new_stock >= 0) {
                    $product->set_stock_quantity($new_stock);
                    $product->save();
                    $inventory_logs[] = sprintf("[%s] ✅ [SINGLE] Trừ %d mã [%s] (ID: %d) từ sản phẩm đơn lẻ trong đơn #%d. Kho: %d → %d", 
                        $timestamp, $actual_codes_count, $title, $product_id, $post_id, $stock, $new_stock);
                } else {
                    $inventory_logs[] = sprintf("[%s] ❌ [SINGLE] Không đủ tồn kho cho [%s] (còn %d, cần %d)", 
                        $timestamp, $title, $stock, $actual_codes_count);
                }
            }

            // Lưu vào order_table cho single products
            if ($actual_codes_count > 0) {
                $wpdb->insert($order_table, [
                    'order_id' => $post_id,
                    'title' => $title,
                    'quantity' => $actual_codes_count,
                    'barcode' => implode("\n", $codes_array),
                    'province' => $province,
                    'channel' => $channel,
                    'type' => 'single',
                    'created_at' => $timestamp
                ]);
            }

            // Cập nhật barcode table với thông tin đơn hàng cho single products
            foreach ($codes_array as $product_code) {
                $wpdb->update(
                    $barcode_table, 
                    [
                        'order_by_product_id' => $post_id,
                        'channel' => $channel,
                        'province' => $province,
                        'lot' => $lot_name,
                        'product_date' => $lot_date,
                    ], 
                    ['barcode' => $product_code]
                );
            }
        }

        // ========== XỬ LÝ SELL-OUT CHO CẢ BULK VÀ SINGLE ==========
        // Xử lý sell-out cho bulk products
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            if (!$product_id) continue;

            $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
            $product = wc_get_product($product_id);
            $title = $product ? $product->get_name() : '';

            $used_codes = $wpdb->get_col($wpdb->prepare(
                "SELECT barcode FROM $barcode_table 
                 WHERE order_by_product_id = %d AND product_id = %s AND status = 'used' AND order_type = 'bulk'",
                $post_id, $custom_prod_id
            ));

            $qty_sell = count($used_codes);
            $barcode_text = implode("\n", $used_codes);

            if ($qty_sell > 0) {
                $wpdb->insert($sellout_table, [
                    'order_id' => $post_id,
                    'title' => $title,
                    'quantity' => $qty_sell,
                    'barcode' => $barcode_text,
                    'province' => $province,
                    'channel' => $channel,
                    'type' => 'bulk'
                ]);

                $inventory_logs[] = sprintf("[%s] ✅ [BULK SELL-OUT] Cập nhật %d mã đã sử dụng [%s] (ID: %d) - #%d",
                    $timestamp, $qty_sell, $title, $product_id, $post_id);
            }
        }

        // Xử lý sell-out cho single products
        foreach ($single_items as $single_item) {
            $product_id = intval($single_item['product_id']);
            if (!$product_id) continue;

            $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
            $product = wc_get_product($product_id);
            $title = $product ? $product->get_name() : '';

            $used_codes = $wpdb->get_col($wpdb->prepare(
                "SELECT barcode FROM $barcode_table 
                 WHERE order_by_product_id = %d AND product_id = %s AND status = 'used' AND order_type = 'single'",
                $post_id, $custom_prod_id
            ));

            $qty_sell = count($used_codes);
            $barcode_text = implode("\n", $used_codes);

            if ($qty_sell > 0) {
                $wpdb->insert($sellout_table, [
                    'order_id' => $post_id,
                    'title' => $title,
                    'quantity' => $qty_sell,
                    'barcode' => $barcode_text,
                    'province' => $province,
                    'channel' => $channel,
                    'type' => 'single'
                ]);

                $inventory_logs[] = sprintf("[%s] ✅ [SINGLE SELL-OUT] Cập nhật %d mã đã sử dụng [%s] (ID: %d) - #%d",
                    $timestamp, $qty_sell, $title, $product_id, $post_id);
            }
        }

        // Log hoàn thành
        update_post_meta($post_id, '_inventory_logs', $inventory_logs);
        $inventory_logs[] = sprintf("[%s] 🎉 [%s] Đơn hàng #%d đã được duyệt và hoàn thành xử lý (Theo thùng hàng: %d items, Sản phẩm đơn lẻ: %d items)", 
            $timestamp, $user_display_name, $post_id, count($items), count($single_items));
        update_post_meta($post_id, '_inventory_logs', $inventory_logs);

    } else {
        // Khi status không phải completed, chỉ log
        $inventory_logs = get_post_meta($post_id, '_inventory_logs', true);
        if (!is_array($inventory_logs)) $inventory_logs = [];
        
        $current_user = wp_get_current_user();
        $user_display_name = $current_user->display_name ?: $current_user->user_login;
        
        $inventory_logs[] = sprintf("[%s] ⏳ [%s] Đơn hàng #%d ở trạng thái '%s' - Chưa cập nhật dữ liệu (Theo thùng hàng: %d items, Sản phẩm đơn lẻ: %d items)", 
            current_time('mysql'), $user_display_name, $post_id, get_status_text($new_status), count($items), count($single_items));
        update_post_meta($post_id, '_inventory_logs', $inventory_logs);
    }
}

add_action('save_post', 'save_order_check_fields');

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    }
});

add_action('add_meta_boxes', function() {
    add_meta_box('order_check_products_box', 'Danh sách xuất đơn sản phẩm theo thùng', 'render_order_check_products_box', 'order_check', 'normal', 'high');
    add_meta_box('order_check_single_products_box', 'Danh sách xuất đơn sản phẩm đơn lẻ', 'render_order_check_single_products_box', 'order_check', 'normal', 'high');
    add_meta_box('render_used_codes_box', 'Danh sách sản phẩm đã rớt kệ', 'render_order_used_codes_box', 'order_check', 'normal', 'high');
    add_meta_box('order_status_box', 'Trạng thái đơn hàng', 'render_order_status_box', 'order_check', 'side');
    add_meta_box('order_logs_box', 'Lịch sử trạng thái đơn', 'render_order_logs_box', 'order_check', 'side');
    add_meta_box('order_check_fields', 'Thông tin đơn hàng', 'render_order_check_fields', 'order_check', 'normal', 'default');
    add_meta_box(
        'order_logs_metabox',
        'Nhật ký nhập hàng cho thùng',
        'display_order_logs_metabox',
        'order_check',
        'normal',
        'default'
    );
});

function display_order_logs_metabox($post) {
    $logs = get_post_meta($post->ID, '_inventory_logs', true);
    if (!empty($logs)) {
        $logs = array_reverse($logs);
        echo '<ul>';
        foreach ($logs as $log) {
            echo '<li>' . esc_html($log) . '</li>';
        }
        echo '</ul>';
    }
}

function render_order_status_box($post) {
    $current_status = get_post_meta($post->ID, 'order_status', true);
    $current_user = wp_get_current_user();
        
    // Định nghĩa các trạng thái
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
        
    // Nếu user không có quyền thay đổi trạng thái
    if (empty($allowed_statuses)) {
        echo '<span>Bạn không có quyền thay đổi trạng thái</span>';
        return;
    }
        
    echo '<select name="order_status">';
        
    foreach ($allowed_statuses as $value => $label) {
        $selected = selected($current_status, $value, false);
        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
        
    if (!array_key_exists($current_status, $allowed_statuses) && !empty($current_status)) {
        $current_label = isset($all_statuses[$current_status]) ? $all_statuses[$current_status] : $current_status;
        echo '<option value="' . esc_attr($current_status) . '" selected disabled>' . esc_html($current_label) . ' (Chỉ đọc)</option>';
    }
        
    echo '</select>';
}

function render_order_logs_box($post) {
    $logs = get_post_meta($post->ID, 'order_status_logs', true);
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

add_filter('manage_order_check_posts_columns', function($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['order_status'] = 'Trạng thái';
            $new_columns['approved_by'] = 'Người duyệt';
        }
    }
    return $new_columns;
});

add_action('manage_order_check_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'order_status':
            $status = get_post_meta($post_id, 'order_status', true);
            
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
    if ($typenow === 'order_check') {
        $selected = isset($_GET['order_status_filter']) ? $_GET['order_status_filter'] : '';
        echo '<select name="order_status_filter">';
        echo '<option value="">Tất cả trạng thái</option>';
        echo '<option value="pending"' . selected($selected, 'pending', false) . '>Chờ duyệt</option>';
        echo '<option value="completed"' . selected($selected, 'completed', false) . '>Hoàn thành</option>';
        echo '</select>';
    }
});

add_filter('parse_query', function($query) {
    global $pagenow, $typenow;
    if ($pagenow === 'edit.php' && $typenow === 'order_check' && isset($_GET['order_status_filter']) && $_GET['order_status_filter'] !== '') {
        $query->set('meta_key', 'order_status');
        $query->set('meta_value', $_GET['order_status_filter']);
    }
});

function render_order_check_products_box($post) {
    $products = get_post_meta($post->ID, '_order_check_line_items', true);
    $all_products = wc_get_products(['limit' => -1]);
    ?>

    <div id="order_summary" class="order-summary-box" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px;">
        <div class="summary-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="summary-item" style="background: white; padding: 12px; border-left: 4px solid #007cba;">
                <div class="summary-label" style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Tổng số thùng</div>
                <div class="summary-value" style="font-size: 24px; font-weight: bold; color: #007cba;" id="total_boxes">0</div>
                <div class="summary-detail" style="font-size: 11px; color: #6c757d;" id="boxes_detail">0 sản phẩm</div>
            </div>
            <div class="summary-item" style="background: white; padding: 12px; border-left: 4px solid #28a745;">
                <div class="summary-label" style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Tổng số sản phẩm</div>
                <div class="summary-value" style="font-size: 24px; font-weight: bold; color: #28a745;" id="total_products">0</div>
                <div class="summary-detail" style="font-size: 11px; color: #6c757d;" id="products_detail">0 mã barcode</div>
            </div>
            <div class="summary-item" style="background: white; padding: 12px; border-left: 4px solid #ffc107;">
                <div class="summary-label" style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Trạng thái</div>
                <div class="summary-value" style="font-size: 16px; font-weight: bold; color: #ffc107;" id="check_status">Chưa kiểm tra</div>
                <div class="summary-detail" style="font-size: 11px; color: #6c757d;" id="status_detail">Cần kiểm tra tồn kho</div>
            </div>
        </div>
    </div>
    <div id="order_check_products_container">
        <table class="widefat" id="order_check_products_table" style="margin-bottom:10px;">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng thùng</th>
                    <th>Lô</th>
                    <th>Mã định danh thùng</th>
                    <th>Mã sản phẩm trong thùng</th>
                    <th>Thông báo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($products)) {
                    foreach ($products as $index => $item) {
                        $product_id = isset($item['product_id']) ? $item['product_id'] : '';
                        $box_quantity = isset($item['box_quantity']) ? $item['box_quantity'] : '';
                        $box_codes = isset($item['box_codes']) ? $item['box_codes'] : '';
                        $lot_name = isset($item['lot_name']) ? $item['lot_name'] : '';
                        echo render_new_product_row($all_products, $product_id, $box_quantity, $box_codes, $lot_name, $index);
                    }
                }
                ?>
            </tbody>
        </table>
        
        <button type="button" class="button" id="add_product_row">+ Thêm sản phẩm</button>
        <!-- <button type="button" class="button button-primary" id="check_quantities" style="margin-left: 10px;">🔍 Check số lượng</button> -->
        <button type="button" id="stock-check-button" class="button button-secondary" style="margin-left: 10px; background: #ff9800; border-color: #ff9800; color: white;">📊 Kiểm tra tất cả</button>
    </div>
    <span style="margin-top: 12px; display: inline-block;">Vui lòng bấm nút "Kiểm tra tất cả" trước khi lưu dữ liệu.</span>

    <script>
        let rowIndex = <?php echo (is_array($products) ? count($products) : 0); ?>;

        function updateOrderSummary() {
            let totalBoxes = 0;
            let totalProducts = 0;
            let totalValidBoxes = 0;
            let totalCheckedProducts = 0;
            let productCount = 0;
            
            const rows = document.querySelectorAll('#order_check_products_table tbody tr');
            
            rows.forEach((row, index) => {
                const productSelect = row.querySelector('select[name*="[product_id]"]');
                const boxQuantityInput = row.querySelector('input[name*="[box_quantity]"]');
                const boxCodesInput = row.querySelector('textarea[name*="[box_codes]"]');
                
                if (!productSelect || !boxQuantityInput || !boxCodesInput) return;
                
                const productId = productSelect.value;
                const boxQuantity = parseInt(boxQuantityInput.value) || 0;
                const boxCodes = boxCodesInput.value.trim();
                
                if (productId) {
                    productCount++;
                    totalBoxes += boxQuantity;
                    
                    // Tính số thùng thực tế từ mã thùng
                    if (boxCodes) {
                        const actualBoxes = boxCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code).length;
                        
                        // Lấy thông tin từ product codes display nếu có
                        const productCodesDiv = document.getElementById(`product_codes_${index}`);
                        if (productCodesDiv) {
                            const totalCodesText = productCodesDiv.textContent || productCodesDiv.innerText;
                            const codesMatch = totalCodesText.match(/Tổng số mã sản phẩm: (\d+)/);
                            const validBoxesMatch = totalCodesText.match(/Thùng hợp lệ: (\d+)\/(\d+)/);
                            
                            if (codesMatch) {
                                totalCheckedProducts += parseInt(codesMatch[1]);
                            }
                            
                            if (validBoxesMatch) {
                                totalValidBoxes += parseInt(validBoxesMatch[1]);
                            }
                        }
                    }
                }
            });
            
            // Cập nhật UI
            document.getElementById('total_boxes').textContent = totalBoxes.toLocaleString();
            document.getElementById('boxes_detail').textContent = `${productCount} sản phẩm`;
            
            document.getElementById('total_products').textContent = totalCheckedProducts.toLocaleString();
            document.getElementById('products_detail').textContent = `${totalCheckedProducts} mã barcode`;
        }
    
        // Hàm cập nhật trạng thái kiểm tra
        function updateCheckStatus(status, detail, color) {
            const statusElement = document.getElementById('check_status');
            const detailElement = document.getElementById('status_detail');
            
            statusElement.textContent = status;
            statusElement.style.color = color;
            detailElement.textContent = detail;
        }
        document.getElementById("add_product_row").addEventListener("click", function() {
            let tableBody = document.querySelector("#order_check_products_table tbody");
            let row = document.createElement("tr");

            row.innerHTML = `<?php echo str_replace(["\n", "'"], ["", "\\'"], render_new_product_row($all_products)); ?>`.replace(/__index__/g, rowIndex);
            tableBody.appendChild(row);
            rowIndex++;
            
            // Khởi tạo event listeners cho row mới
            initRowEventListeners(rowIndex - 1);
            setTimeout(updateOrderSummary, 100);
        });

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-row")) {
                e.target.closest("tr").remove();
                setTimeout(updateOrderSummary, 100);
            }
        });

        document.querySelectorAll(".box-codes-input").forEach(function(input, index) {
            initRowEventListeners(index);
        });

        document.addEventListener('input change', function(e) {
            if (e.target.matches('select[name*="[product_id]"], input[name*="[box_quantity]"], textarea[name*="[box_codes]"], select[name*="[lot_date]"]')) {
                setTimeout(updateOrderSummary, 100);
                updateCheckStatus('Cần kiểm tra', 'Dữ liệu đã thay đổi', '#ffc107');
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const index = e.target.getAttribute('data-index');
                const selectedOption = e.target.options[e.target.selectedIndex];
                const customId = selectedOption.getAttribute('data-custom-id');
                const lotSelect = document.getElementById(`lot_select_${index}`);
                
                if (customId && lotSelect) {
                    loadLotsForProduct(customId, lotSelect, index);
                } else if (lotSelect) {
                    // Reset lot select nếu không có sản phẩm
                    lotSelect.innerHTML = '<option value="">-- Chọn lot --</option>';
                }
                
                // Cập nhật display mã sản phẩm
                updateProductCodesDisplay(index);
            }
        });

        const originalUpdateProductCodesDisplay = updateProductCodesDisplay;
        updateProductCodesDisplay = function(index) {
            originalUpdateProductCodesDisplay(index);
            setTimeout(updateOrderSummary, 500);
        };

        jQuery(document).ready(function($){
            // Listen for stock check events
            $(document).on('stock-check-success', function() {
                updateCheckStatus('Đã kiểm tra', 'Tồn kho hợp lệ', '#28a745');
            });
            
            $(document).on('stock-check-error', function() {
                updateCheckStatus('Có lỗi', 'Cần xem lại dữ liệu', '#dc3545');
            });
            
            $(document).on('stock-check-reset', function() {
                updateCheckStatus('Cần kiểm tra', 'Dữ liệu đã thay đổi', '#ffc107');
            });
        });

        // Hàm khởi tạo khi load trang
        function initOrderSummary() {
            updateOrderSummary();
            updateCheckStatus('Chưa kiểm tra', 'Cần kiểm tra tồn kho', '#ffc107');
        }

        function loadLotsForProduct(customId, lotSelect, index) {
            lotSelect.innerHTML = '<option value="">🔄 Đang tải...</option>';
            lotSelect.disabled = true;
            
            // AJAX call để lấy lots
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_lots_by_product_id',
                    custom_prod_id: customId,
                    nonce: '<?php echo wp_create_nonce("get_lots_nonce"); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                lotSelect.innerHTML = '<option value="">-- Chọn lô --</option>';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(lot => {
                        const option = document.createElement('option');
                        option.value = lot.lot_name;
                        option.textContent = lot.lot_name;
                        lotSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Không có lot nào';
                    lotSelect.appendChild(option);
                }
                
                lotSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading lots:', error);
                lotSelect.innerHTML = '<option value="">❌ Lỗi tải dữ liệu</option>';
                lotSelect.disabled = false;
            });
        }

        function initRowEventListeners(index) {
                const boxCodesInput = document.querySelector(`[name="order_check_products[${index}][box_codes]"]`);
                const productSelect = document.querySelector(`[name="order_check_products[${index}][product_id]"]`);
                
                if (boxCodesInput) {
                    boxCodesInput.addEventListener("input", function() {
                        updateProductCodesDisplay(index);
                    });
                }
                
                if (productSelect) {
                    productSelect.addEventListener("change", function() {
                        updateProductCodesDisplay(index);
                    });
                }
            }

        function updateProductCodesDisplay(index) {
            const boxCodesInput = document.querySelector(`[name="order_check_products[${index}][box_codes]"]`);
            const productSelect = document.querySelector(`[name="order_check_products[${index}][product_id]"]`);
            const productCodesDiv = document.getElementById(`product_codes_${index}`);
            const messageDiv = document.getElementById(`message_${index}`);
            const boxQuantityInput = document.querySelector(`[name="order_check_products[${index}][box_quantity]"]`);

            if (!boxCodesInput || !productSelect || !productCodesDiv || !messageDiv) return;

            const boxCodes = boxCodesInput.value.trim();
            const productId = productSelect.value;
            const boxQuantity = parseInt(boxQuantityInput.value) || 0;
            
            const boxCodesList = boxCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
            const actualBoxCount = boxCodesList.length;
            
            // Kiểm tra trùng lặp mã thùng với các sản phẩm khác
            const duplicateBoxes = checkDuplicateBoxCodes(index, boxCodesList);
            
            // Kiểm tra số lượng thùng ngay lập tức - luôn hiển thị khi có dữ liệu
            let quantityMessage = '';
            if (boxQuantity > 0 || actualBoxCount > 0) {
                if (boxQuantity === actualBoxCount && boxQuantity > 0) {
                    quantityMessage = '<span style="color: green; font-weight: bold;">✅ Số lượng thùng khớp (' + actualBoxCount + ' thùng)</span>';
                } else {
                    quantityMessage = '<span style="color: red; font-weight: bold;">❌ Số lượng thùng không khớp</span><br>' +
                                    '<small>Dự kiến: ' + boxQuantity + ' thùng | Thực tế: ' + actualBoxCount + ' thùng</small>';
                }
            }
            
            // Thêm cảnh báo trùng lặp nếu có
            if (duplicateBoxes.length > 0) {
                quantityMessage += '<br><span style="color: red; font-weight: bold;">⚠️ Mã thùng bị trùng:</span><br>' +
                                '<small style="color: #d63031;">' + duplicateBoxes.join(', ') + '</small>';
            }
            
            // Nếu chưa có sản phẩm hoặc mã thùng, chỉ hiển thị thông báo số lượng
            if (!boxCodes || !productId) {
                if (quantityMessage) {
                    messageDiv.innerHTML = quantityMessage;
                } else {
                    messageDiv.innerHTML = '<em style="color: #666;">💡 Nhập thông tin để kiểm tra</em>';
                }
                
                if (!productId) {
                    productCodesDiv.innerHTML = '<em style="color: #666;">📋 Chọn sản phẩm trước</em>';
                } else if (!boxCodes) {
                    productCodesDiv.innerHTML = '<em style="color: #666;">📋 Nhập mã thùng để xem mã sản phẩm</em>';
                }
                return;
            }
            
            // Hiển thị thông báo đang xử lý
            messageDiv.innerHTML = quantityMessage + '<br><span style="color: #0073aa;">🔄 Đang kiểm tra mã sản phẩm...</span>';

            // AJAX call để lấy mã sản phẩm từ database
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_product_codes_from_boxes',
                    box_codes: boxCodesList,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let html = '';
                        
                        // Hiển thị danh sách mã sản phẩm
                        if (data.product_codes.length > 0) {
                            html += '<div style="max-height: 120px; overflow-y: auto; border: 1px solid #ddd; padding: 5px; background: #f9f9f9; margin-bottom: 5px;">';
                            html += '<div style="font-family: monospace; font-size: 11px; line-height: 1.3;">';
                            html += data.product_codes.join('<br>');
                            html += '</div></div>';
                        } else {
                            html += '<div style="padding: 5px; background: #fff2cc; border: 1px solid #f1c40f; margin-bottom: 5px;">';
                            html += '<em style="color: #856404;">⚠️ Không tìm thấy mã sản phẩm nào</em>';
                            html += '</div>';
                        }
                        
                        // Thông tin tổng quan
                        html += '<div style="background: #e8f4fd; padding: 5px; border-left: 3px solid #0073aa; margin-bottom: 5px;">';
                        html += '<strong>Tổng số mã sản phẩm: ' + data.total_codes + '</strong><br>';
                        html += '<small>Thùng hợp lệ: ' + data.valid_boxes + '/' + data.total_boxes + '</small>';
                        html += '</div>';
                        
                        // Cảnh báo thùng không hợp lệ
                        if (data.invalid_boxes && data.invalid_boxes.length > 0) {
                            html += '<div style="background: #ffeaa7; padding: 5px; border-left: 3px solid #fdcb6e; margin-bottom: 5px;">';
                            html += '<small style="color: #d63031;"><strong>⚠️ Thùng không tìm thấy:</strong> ' + data.invalid_boxes.join(', ') + '</small>';
                            html += '</div>';
                        }
                        
                        productCodesDiv.innerHTML = html;
                        
                        // Cập nhật thông báo cuối cùng
                        let finalMessage = quantityMessage;
                        
                        if (data.total_codes > 0) {
                            finalMessage += '<br><span style="color: green;">✅ Tìm thấy ' + data.total_codes + ' mã sản phẩm</span>';
                        } else {
                            finalMessage += '<br><span style="color: red;">❌ Không tìm thấy mã sản phẩm nào</span>';
                        }
                        
                        messageDiv.innerHTML = finalMessage;
                        
                    } else {
                        productCodesDiv.innerHTML = '<div style="color: red; padding: 5px; background: #ffebee;"><em>❌ Lỗi: ' + response.data + '</em></div>';
                        messageDiv.innerHTML = quantityMessage + '<br><span style="color: red;">❌ Lỗi khi lấy dữ liệu</span>';
                    }
                },
                error: function() {
                    productCodesDiv.innerHTML = '<div style="color: red; padding: 5px; background: #ffebee;"><em>❌ Lỗi kết nối server</em></div>';
                    messageDiv.innerHTML = quantityMessage + '<br><span style="color: red;">❌ Lỗi kết nối</span>';
                }
            });
        }

        function checkDuplicateBoxCodes(currentIndex, currentBoxCodes) {
            const duplicates = [];
            const allRows = document.querySelectorAll('#order_check_products_table tbody tr');
            
            allRows.forEach((row, index) => {
                if (index === currentIndex) return;
                
                const otherBoxCodesInput = row.querySelector('textarea[name*="[box_codes]"]');
                if (!otherBoxCodesInput) return;
                
                const otherBoxCodes = otherBoxCodesInput.value.trim();
                if (!otherBoxCodes) return;
                
                const otherBoxCodesList = otherBoxCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                
                currentBoxCodes.forEach(code => {
                    if (otherBoxCodesList.includes(code)) {
                        duplicates.push(code);
                    }
                });
            });
            
            return [...new Set(duplicates)];
        }

        function checkAllDuplicates() {
            const allRows = document.querySelectorAll('#order_check_products_table tbody tr');
            let hasDuplicates = false;
            
            allRows.forEach((row, index) => {
                const boxCodesInput = row.querySelector('textarea[name*="[box_codes]"]');
                if (!boxCodesInput) return;
                
                const boxCodes = boxCodesInput.value.trim();
                if (!boxCodes) return;
                
                const boxCodesList = boxCodes.split(/[\n,;]+/).map(code => code.trim()).filter(code => code);
                const duplicates = checkDuplicateBoxCodes(index, boxCodesList);
                
                if (duplicates.length > 0) {
                    hasDuplicates = true;
                    boxCodesInput.style.borderColor = 'red';
                    boxCodesInput.style.backgroundColor = '#ffe6e6';
                } else {
                    boxCodesInput.style.borderColor = '';
                    boxCodesInput.style.backgroundColor = '';
                }
                
                updateProductCodesDisplay(index);
            });
            
            return hasDuplicates;
        }

        function checkAllQuantities() {
            const rows = document.querySelectorAll('#order_check_products_table tbody tr');
            let allValid = true;
            
            rows.forEach(function(row, index) {
                updateProductCodesDisplay(index);
            });
            
            if (allValid) {
                alert('Đã kiểm tra xong tất cả số lượng!');
            }
        }
    </script>
    <?php
}

add_action('wp_ajax_get_lots_by_product_id', 'handle_get_lots_by_product_id');
add_action('wp_ajax_nopriv_get_lots_by_product_id', 'handle_get_lots_by_product_id');

function handle_get_lots_by_product_id() {
    if (!wp_verify_nonce($_POST['nonce'], 'get_lots_nonce')) {
        wp_send_json_error('Nonce verification failed');
        return;
    }
    
    global $wpdb;
    $lot_table = BIZGPT_PLUGIN_WP_PRODUCT_LOT;
    
    $custom_prod_id = sanitize_text_field($_POST['custom_prod_id']);
    
    if (empty($custom_prod_id)) {
        wp_send_json_error('Custom product ID không hợp lệ');
        return;
    }
    
    $lots = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT lot_name FROM $lot_table WHERE product_id = %s ORDER BY lot_name DESC",
        $custom_prod_id
    ));
    
    if ($lots) {
        wp_send_json_success($lots);
    } else {
        wp_send_json_success([]);
    }
    
    wp_die();
}

function render_order_used_codes_box($post) {
    $post_id = $post->ID;
    if ($_GET['action'] === 'edit'): ?>
    <?php 
        global $wpdb;

        $table_macao = BIZGPT_PLUGIN_WP_BARCODE;
        $table_order_products = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;

        $items = get_post_meta($post_id, '_order_check_line_items', true);
        if (empty($items)) return;

        echo '<table class="widefat striped" style="margin-top:10px">';
        echo '<thead><tr>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Mã đã sử dụng</th>
            <th>Tỉnh</th>
            <th>Kênh</th>
        </tr></thead>';
        echo '<tbody>';

        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            if (!$product_id) continue;
            $product_name = wc_get_product($product_id)->get_name();
            $order_product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id FROM $table_order_products 
                WHERE order_id = %d AND title = %s 
                LIMIT 1",
                $post_id, $product_name
            ));            

            if (!$order_product_id) continue;

            $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);

            $used_codes = $wpdb->get_col($wpdb->prepare(
                "SELECT barcode FROM $table_macao 
                WHERE order_by_product_id = %d AND product_id = %s AND status = 'used'",
                $order_product_id, $custom_prod_id
            ));
            
            $used_count = count($used_codes);

            echo '<tr>';
            echo '<td>' . esc_html($product_name) . '</td>';
            echo '<td>' . intval($used_count) . '</td>';
            echo '<td><pre style="white-space:pre-wrap;max-height:120px;overflow-y:auto;background:#f9f9f9;padding:8px;border:1px solid #ddd;">' . 
            esc_html(implode("\n", $used_codes)) . '</pre></td>';
            echo '<td>' . esc_html(isset($item['province']) ? $item['province'] : "") . '</td>';
            echo '<td>' . esc_html(isset($item['channel']) ? $item['channel'] : "") . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    ?>
    <?php endif; ?>
    <?php
}

function render_new_product_row($all_products, $product_id = '', $box_quantity = '', $box_codes = '', $lot_name = '', $index = '__index__') {
    ob_start();
    ?>
    <tr>
        <td>
            <select name="order_check_products[<?php echo $index; ?>][product_id]" class="product-select" data-index="<?php echo $index; ?>" style="width: 100%;">
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($all_products as $product): ?>
                    <?php 
                        $stock = $product->get_stock_quantity();
                        $custom_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
                        $label = $product->get_name() . ' (ID: ' . $custom_id . ', Tồn: ' . $stock . ')';
                    ?>
                    <option 
                        value="<?php echo esc_attr($product->get_id()); ?>"
                        data-custom-id="<?php echo esc_attr($custom_id); ?>"
                        <?php selected($product_id, $product->get_id()); ?>
                    >
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>

        <td>
            <input type="number"
                name="order_check_products[<?php echo $index; ?>][box_quantity]"
                value="<?php echo esc_attr($box_quantity); ?>"
                min="1" 
                placeholder="Số thùng"
                style="width: 80px;" 
                title="Nhập số lượng thùng dự kiến" />
        </td>

        <td>
            <select name="order_check_products[<?php echo $index; ?>][lot_name]" 
                    id="lot_select_<?php echo esc_attr($index); ?>" 
                    class="lot-select" 
                    data-index="<?php echo esc_attr($index); ?>" 
                    style="width: 150px;">
                <option value="">-- Chọn lô --</option>
                <?php if ($product_id && $lot_name): ?>
                    <?php
                    global $wpdb;
                    $lot_table = BIZGPT_PLUGIN_WP_PRODUCT_LOT;
                    $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
                    
                    if ($custom_prod_id) {
                        $lots = $wpdb->get_results($wpdb->prepare(
                            "SELECT DISTINCT lot_name FROM $lot_table WHERE product_id = %s ORDER BY lot_name DESC",
                            $custom_prod_id
                        ));
                        
                        foreach ($lots as $lot) {
                            $selected = ($lot->lot_name == $lot_name) ? 'selected' : '';
                            echo '<option value="' . esc_attr($lot->lot_name) . '" ' . $selected . '>' . esc_html($lot->lot_name) . '</option>';
                        }
                    }
                    ?>
                <?php endif; ?>
            </select>
        </td>

        <td>
            <textarea 
                name="order_check_products[<?php echo $index; ?>][box_codes]" 
                class="box-codes-input"
                data-index="<?php echo esc_attr($index); ?>"
                rows="4"
                style="width: 200px;"
                placeholder="Nhập mã thùng, mỗi dòng 1 mã"
                title="Nhập danh sách mã định danh thùng"
            ><?php echo esc_textarea($box_codes); ?></textarea>
        </td>
        <td>
            <div id="product_codes_<?php echo esc_attr($index); ?>" style="max-width: 300px; font-size: 11px;">
                <em style="color: #666;">📋 Chưa có dữ liệu</em>
            </div>
        </td>
        <td>
            <div id="message_<?php echo esc_attr($index); ?>" style="font-size: 12px; max-width: 200px;">
                <em style="color: #666;">💡 Nhập thông tin để kiểm tra</em>
            </div>
        </td>
        <td><button type="button" class="button remove-row" title="Xóa dòng này">✕</button></td>
    </tr>
    <?php
    return ob_get_clean();
}

add_action('admin_footer', 'gpt_barcode_check_quantity_script');
function gpt_barcode_check_quantity_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        function countBarcodes(text) {
            return text
                .split('\n')
                .map(line => line.trim())
                .filter(line => line !== '').length;
        }

        function checkMismatch(index) {
            const $textarea = $('.barcode-textarea[data-index="' + index + '"]');
            const $quantity = $('input[name="order_check_products[' + index + '][quantity]"]');
            const $counter  = $('.barcode-count[data-index="' + index + '"]');
            const actualCount = countBarcodes($textarea.val());
            const expectedCount = parseInt($quantity.val(), 10) || 0;

            $counter.text('Số lượng mã: ' + actualCount);

            if (actualCount !== expectedCount) {
                $counter.css('color', 'red').attr('title', '⚠️ Số lượng mã không khớp với số lượng!');
                $textarea.css('border-color', 'red');
            } else {
                $counter.css('color', '').removeAttr('title');
                $textarea.css('border-color', '');
            }
        }

        $(document).on('input', '.barcode-textarea, .barcode-quantity', function() {
            const index = $(this).data('index') || $(this).closest('tr').find('.barcode-textarea').data('index');
            checkMismatch(index);
        });

        $('.barcode-textarea').each(function() {
            const index = $(this).data('index');
            checkMismatch(index);
        });
    });
    </script>
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


// AJAX handler để lấy mã sản phẩm từ mã thùng
add_action('wp_ajax_get_product_codes_from_boxes', 'handle_get_product_codes_from_boxes');
function handle_get_product_codes_from_boxes() {
    global $wpdb;
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    
    $box_codes = isset($_POST['box_codes']) ? $_POST['box_codes'] : [];
    $product_id = intval($_POST['product_id']);
    
    if (empty($box_codes) || !$product_id) {
        wp_send_json_error('Thiếu thông tin cần thiết');
        return;
    }
    
    // Lấy custom_prod_id từ product_id
    $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
    if (empty($custom_prod_id)) {
        wp_send_json_error('Sản phẩm chưa có mã định danh');
        return;
    }
    
    $all_product_codes = [];
    $valid_boxes = 0;
    $invalid_boxes = [];
    
    foreach ($box_codes as $box_code) {
        if (empty(trim($box_code))) continue;
        
        // Tìm mã sản phẩm trong thùng này
        $product_codes = $wpdb->get_col($wpdb->prepare(
            "SELECT barcode FROM $barcode_table 
             WHERE box_barcode = %s AND product_id = %s 
             ORDER BY id",
            trim($box_code), $custom_prod_id
        ));
        
        if (!empty($product_codes)) {
            $all_product_codes = array_merge($all_product_codes, $product_codes);
            $valid_boxes++;
        } else {
            $invalid_boxes[] = trim($box_code);
        }
    }
    
    wp_send_json_success([
        'product_codes' => $all_product_codes,
        'total_codes' => count($all_product_codes),
        'valid_boxes' => $valid_boxes,
        'total_boxes' => count($box_codes),
        'invalid_boxes' => $invalid_boxes
    ]);
}

add_action('wp_ajax_check_stock_before_update', 'handle_check_stock_before_update');
function handle_check_stock_before_update() {
    global $wpdb;
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    
    if (empty($items)) {
        wp_send_json_error('Không có sản phẩm để kiểm tra');
        return;
    }
    
    $stock_issues = [];
    $product_not_exist_issues = [];
    
    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        $box_codes = isset($item['box_codes']) ? $item['box_codes'] : '';
        
        if (!$product_id || empty($box_codes)) continue;
        
        // Lấy thông tin sản phẩm
        $product = wc_get_product($product_id);
        if (!$product) continue;
        
        $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
        if (empty($custom_prod_id)) continue;
        
        // Kiểm tra sản phẩm có tồn tại trong bảng barcode không
        $product_exists_in_barcode = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $barcode_table WHERE product_id = %s",
            $custom_prod_id
        ));
        
        if ($product_exists_in_barcode == 0) {
            $product_not_exist_issues[] = [
                'product_name' => $product->get_name(),
                'product_id' => $product_id,
                'custom_prod_id' => $custom_prod_id
            ];
            continue; // Bỏ qua sản phẩm này cho việc kiểm tra tồn kho
        }
        
        // Tính số lượng mã sản phẩm thực tế từ các thùng
        $box_codes_array = array_filter(array_map('trim', explode("\n", $box_codes)));
        $total_product_codes = 0;
        $invalid_boxes = [];
        
        foreach ($box_codes_array as $box_code) {
            $product_codes_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $barcode_table 
                 WHERE box_barcode = %s AND product_id = %s",
                $box_code, $custom_prod_id
            ));
            
            if ($product_codes_count > 0) {
                $total_product_codes += intval($product_codes_count);
            } else {
                $invalid_boxes[] = $box_code;
            }
        }
        
        // Lấy tồn kho hiện tại
        $current_stock = $product->get_stock_quantity();
        
        // Kiểm tra tồn kho (chỉ khi có mã sản phẩm hợp lệ)
        if ($total_product_codes > 0 && $current_stock < $total_product_codes) {
            $stock_issues[] = [
                'product_name' => $product->get_name(),
                'product_id' => $product_id,
                'current_stock' => $current_stock,
                'required_quantity' => $total_product_codes,
                'shortage' => $total_product_codes - $current_stock,
                'invalid_boxes' => $invalid_boxes
            ];
        }
    }
    
    // Ưu tiên lỗi sản phẩm không tồn tại trước
    if (!empty($product_not_exist_issues)) {
        wp_send_json_error([
            'type' => 'product_not_exist',
            'message' => 'Một số sản phẩm không có mã barcode trong hệ thống',
            'issues' => $product_not_exist_issues
        ]);
        return;
    }
    
    if (!empty($stock_issues)) {
        wp_send_json_error([
            'type' => 'stock_shortage',
            'message' => 'Không đủ tồn kho cho một số sản phẩm',
            'issues' => $stock_issues
        ]);
        return;
    }
    
    wp_send_json_success('Tất cả sản phẩm hợp lệ và đủ tồn kho');
}

// Thêm script jQuery vào admin footer
add_action('admin_footer', 'add_stock_check_script');
function add_stock_check_script() {
    global $post;
    if (!isset($post) || $post->post_type !== 'order_check') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Biến để theo dõi trạng thái kiểm tra
        let stockCheckPassed = false;
        let isCheckingStock = false;
        
        // Thêm nút kiểm tra tồn kho
        // function addStockCheckButton() {
        //     if ($('#stock-check-button').length === 0) {
        //         // const checkButton = $('<button type="button" id="stock-check-button" class="button button-secondary" style="margin-left: 10px; background: #ff9800; border-color: #ff9800; color: white;">📊 Kiểm tra tồn kho</button>');
        //         // $('#check_quantities').after(checkButton);
                
        //         checkButton.on('click', function() {
        //             checkStockBeforeUpdate();
        //         });
        //     }
        // }
        
        // // Gọi hàm thêm nút
        // addStockCheckButton();
        
        // Override form submit để kiểm tra tồn kho trước
        $('form#post').on('submit', function(e) {
            const orderStatus = $('select[name="order_status"]').val();
            
            // Chỉ kiểm tra khi chuyển sang trạng thái "completed"
            if (orderStatus === 'completed' && !stockCheckPassed && !isCheckingStock) {
                e.preventDefault();
                
                // Hiển thị thông báo và tự động kiểm tra
                showStockWarning();
                checkStockBeforeUpdate(true); // true = auto submit after check
                
                return false;
            }
        });
        
        // Hàm hiển thị cảnh báo
        function showStockWarning() {
            const warningHtml = `
                <div id="stock-warning" class="notice notice-warning" style="padding: 10px; margin: 10px 0; border-left: 4px solid #ffba00;">
                    <p><strong>⚠️ Cảnh báo:</strong> Đang kiểm tra tồn kho trước khi hoàn thành đơn hàng...</p>
                    <div class="progress-bar" style="width: 100%; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden;">
                        <div class="progress-fill" style="width: 0%; height: 100%; background: #ffba00; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            `;
            
            if ($('#stock-warning').length === 0) {
                $('.wrap').prepend(warningHtml);
                
                // Animate progress bar
                setTimeout(() => {
                    $('#stock-warning .progress-fill').css('width', '100%');
                }, 100);
            }
        }
        
        // Hàm xóa cảnh báo
        function removeStockWarning() {
            $('#stock-warning').fadeOut(300, function() {
                $(this).remove();
            });
        }
        
        // Hàm kiểm tra tồn kho
        function checkStockBeforeUpdate(autoSubmit = false) {
            if (isCheckingStock) return;
            
            isCheckingStock = true;
            const $button = $('#stock-check-button');
            const originalText = $button.text();
            
            // Disable button và thay đổi text
            $button.prop('disabled', true).text('🔄 Đang kiểm tra...');
            
            // Thu thập dữ liệu từ form
            const items = [];
            $('#order_check_products_table tbody tr').each(function() {
                const $row = $(this);
                const productId = $row.find('select[name*="[product_id]"]').val();
                const boxCodes = $row.find('textarea[name*="[box_codes]"]').val();
                
                if (productId && boxCodes.trim()) {
                    items.push({
                        product_id: productId,
                        box_codes: boxCodes.trim()
                    });
                }
            });
            
            if (items.length === 0) {
                $button.prop('disabled', false).text(originalText);
                isCheckingStock = false;
                removeStockWarning();
                
                alert('❌ Không có sản phẩm nào để kiểm tra!');
                return;
            }
            
            // AJAX call
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_stock_before_update',
                    items: items
                },
                success: function(response) {
                    $button.prop('disabled', false).text(originalText);
                    isCheckingStock = false;
                    removeStockWarning();
                    
                    if (response.success) {
                        stockCheckPassed = true;
                        showSuccessMessage('✅ Kiểm tra tồn kho thành công! Tất cả sản phẩm đều có đủ tồn kho.');
                        
                        // Tự động submit nếu được yêu cầu
                        if (autoSubmit) {
                            setTimeout(() => {
                                $('form#post').off('submit').submit();
                            }, 1000);
                        }
                        
                    } else {
                        stockCheckPassed = false;
                        showStockError(response.data);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(originalText);
                    isCheckingStock = false;
                    removeStockWarning();
                    
                    alert('❌ Lỗi kết nối! Vui lòng thử lại.');
                }
            });
        }
        
        // Hàm hiển thị thông báo thành công
        function showSuccessMessage(message) {
            const successHtml = `
                <div class="notice notice-success is-dismissible" style="padding: 10px; margin: 10px 0;">
                    <p><strong>${message}</strong></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('.wrap').prepend(successHtml);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                $('.notice-success').fadeOut();
            }, 3000);
        }
        
        // Hàm hiển thị lỗi tồn kho và lỗi sản phẩm
        function showStockError(errorData) {
            let errorMessage = '<div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px 12px;">';
            
            // Kiểm tra loại lỗi
            if (errorData.type === 'product_not_exist') {
                errorMessage += '<h3 style="color: #d32f2f; margin-top: 0;">🚫 ' + errorData.message + '</h3>';
                errorMessage += '<p style="color: #666; margin-bottom: 15px;">Các sản phẩm sau không có mã barcode nào trong hệ thống:</p>';
                
                if (errorData.issues && errorData.issues.length > 0) {
                    errorMessage += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    errorMessage += '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Sản phẩm</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Mã SP</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Trạng thái</th></tr></thead>';
                    errorMessage += '<tbody>';
                    
                    errorData.issues.forEach(function(issue) {
                        errorMessage += '<tr>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd;">' + issue.product_name + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">' + issue.custom_prod_id + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #d32f2f; font-weight: bold;">❌ Không có barcode</td>';
                        errorMessage += '</tr>';
                    });
                    
                    errorMessage += '</tbody></table>';
                    errorMessage += '<div style="margin-top: 15px; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">';
                    errorMessage += '<p style="margin: 0; color: #e65100; font-weight: bold;">💡 Hướng dẫn khắc phục:</p>';
                    errorMessage += '<ul style="margin: 10px 0 0 20px; color: #666;">';
                    errorMessage += '<li>Kiểm tra lại mã sản phẩm có đúng không</li>';
                    errorMessage += '<li>Nhập barcode cho sản phẩm này vào hệ thống</li>';
                    errorMessage += '<li>Hoặc chọn sản phẩm khác có sẵn barcode</li>';
                    errorMessage += '</ul>';
                    errorMessage += '</div>';
                }
                
            } else if (errorData.type === 'stock_shortage') {
                errorMessage += '<h3 style="color: #d32f2f; margin-top: 0;">❌ ' + errorData.message + '</h3>';
                
                if (errorData.issues && errorData.issues.length > 0) {
                    errorMessage += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    errorMessage += '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Sản phẩm</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Tồn kho</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Cần xuất</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Thiếu</th></tr></thead>';
                    errorMessage += '<tbody>';
                    
                    errorData.issues.forEach(function(issue) {
                        errorMessage += '<tr>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd;">' + issue.product_name + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #f44336; font-weight: bold;">' + issue.current_stock + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' + issue.required_quantity + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #d32f2f; font-weight: bold;">-' + issue.shortage + '</td>';
                        errorMessage += '</tr>';
                        
                        // Hiển thị thùng không hợp lệ nếu có
                        if (issue.invalid_boxes && issue.invalid_boxes.length > 0) {
                            errorMessage += '<tr>';
                            errorMessage += '<td colspan="4" style="padding: 5px 8px; border: 1px solid #ddd; background: #fff3e0; font-size: 12px;">';
                            errorMessage += '<span style="color: #f57c00;">⚠️ Thùng không hợp lệ: </span>';
                            errorMessage += '<span style="color: #d84315;">' + issue.invalid_boxes.join(', ') + '</span>';
                            errorMessage += '</td>';
                            errorMessage += '</tr>';
                        }
                    });
                    
                    errorMessage += '</tbody></table>';
                    errorMessage += '<p style="margin-top: 15px; color: #666; font-style: italic;">💡 Vui lòng nhập kho thêm sản phẩm hoặc giảm số lượng xuất.</p>';
                }
            } else {
                // Fallback cho các lỗi khác
                errorMessage += '<h3 style="color: #d32f2f; margin-top: 0;">❌ ' + (errorData.message || 'Có lỗi xảy ra') + '</h3>';
            }
            
            errorMessage += '</div>';
            
            // Xóa error cũ nếu có
            $('.stock-error-message').remove();
            
            // Thêm error message
            $('#order_check_products_box').append('<div class="stock-error-message">' + errorMessage + '</div>');
            
            // Scroll to top để user thấy error
            $('html, body').animate({
                scrollTop: $('.stock-error-message').offset().top - 50
            }, 500);
        }
        
        // Reset stock check khi thay đổi dữ liệu sản phẩm
        $(document).on('input change', 'select[name*="[product_id]"], textarea[name*="[box_codes]"], select[name="order_status"]', function() {
            stockCheckPassed = false;
            $('.stock-error-message').remove();
            
            // Thay đổi màu nút để báo hiệu cần check lại
            $('#stock-check-button').css({
                'background': '#ff5722',
                'border-color': '#ff5722'
            }).text('📊 Cần kiểm tra lại');
        });
        
        // Reset màu nút sau khi check thành công
        $(document).on('click', '#stock-check-button', function() {
            if (stockCheckPassed) {
                $(this).css({
                    'background': '#4caf50',
                    'border-color': '#4caf50'
                }).text('✅ Đã kiểm tra');
            }
        });
    });
    </script>
    
    <style>
        #stock-check-button {
            transition: all 0.3s ease;
        }
        
        #stock-check-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .stock-error-message {
            animation: slideDown 0.3s ease;
        }
        .stock-error-message {
            margin: 0 12px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .progress-bar {
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            transition: width 2s ease-in-out;
        }
    </style>
    <?php
}

add_action('admin_footer', 'add_mandatory_stock_check_validation');
function add_mandatory_stock_check_validation() {
    global $post;
    if (!isset($post) || $post->post_type !== 'order_check') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Biến để theo dõi trạng thái kiểm tra
        let stockCheckPassed = false;
        let isCheckingStock = false;
        let formDataSnapshot = null; // Lưu snapshot dữ liệu form khi check thành công

        let checkButton = $('#stock-check-button');                
        checkButton.on('click', function() {
            checkStockBeforeUpdate();
            checkAllQuantities();
        });
        
        // Hàm tạo snapshot dữ liệu form
        function createFormSnapshot() {
            const snapshot = {
                products: [],
                orderStatus: $('select[name="order_status"]').val()
            };
            
            $('#order_check_products_table tbody tr').each(function() {
                const $row = $(this);
                const productId = $row.find('select[name*="[product_id]"]').val();
                const boxQuantity = $row.find('input[name*="[box_quantity]"]').val();
                const boxCodes = $row.find('textarea[name*="[box_codes]"]').val();
                const lotDate = $row.find('select[name*="[lot_date]"]').val();
                
                if (productId || boxQuantity || boxCodes || lotDate) {
                    snapshot.products.push({
                        productId: productId,
                        boxQuantity: boxQuantity,
                        boxCodes: boxCodes,
                        lotDate: lotDate
                    });
                }
            });
            
            return JSON.stringify(snapshot);
        }
        
        // Hàm so sánh snapshot
        function hasFormDataChanged() {
            const currentSnapshot = createFormSnapshot();
            return formDataSnapshot !== currentSnapshot;
        }
        
        // Override form submit để bắt buộc stock check
        $('form#post').on('submit', function(e) {
            const hasProducts = $('#order_check_products_table tbody tr').length > 0;
            
            // Chỉ validate nếu có sản phẩm trong đơn hàng
            if (hasProducts && (!stockCheckPassed || hasFormDataChanged())) {
                e.preventDefault();
                
                if (isCheckingStock) {
                    showTemporaryMessage('⏳ Đang kiểm tra tồn kho, vui lòng đợi...', 'warning');
                    return false;
                }
                
                // Hiển thị modal yêu cầu stock check
                showStockCheckRequiredModal();
                return false;
            }
            
            // Nếu đã check và không có thay đổi, cho phép submit
            if (stockCheckPassed && !hasFormDataChanged()) {
                showTemporaryMessage('✅ Đang lưu đơn hàng...', 'success');
                return true;
            }
        });
        
        // Modal yêu cầu stock check
        function showStockCheckRequiredModal() {
            // Xóa modal cũ nếu có
            $('#stock-check-modal').remove();
            
            const modalHtml = `
                <div id="stock-check-modal" class="stock-check-modal-overlay">
                    <div class="stock-check-modal">
                        <div class="stock-check-modal-header">
                            <h3>🛡️ Bắt buộc kiểm tra tồn kho</h3>
                        </div>
                        <div class="stock-check-modal-body">
                            <div class="warning-icon">⚠️</div>
                            <div class="warning-content">
                                <p><strong>Bạn cần kiểm tra tồn kho trước khi lưu đơn hàng!</strong></p>
                                <p>Việc kiểm tra tồn kho giúp đảm bảo:</p>
                                <ul>
                                    <li>✅ Đủ số lượng sản phẩm trong kho</li>
                                    <li>✅ Mã thùng và sản phẩm hợp lệ</li>
                                    <li>✅ Không có lỗi dữ liệu</li>
                                </ul>
                                <div class="action-buttons">
                                    <button type="button" id="run-stock-check" class="button button-primary">
                                        📊 Kiểm tra ngay
                                    </button>
                                    <button type="button" id="cancel-save" class="button">
                                        ❌ Hủy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Event handlers cho modal
            $('#run-stock-check').on('click', function() {
                $('#stock-check-modal').remove();
                checkStockBeforeUpdate(true); // true = auto submit after check
            });
            
            $('#cancel-save').on('click', function() {
                $('#stock-check-modal').remove();
            });
            
            // Click outside để đóng modal
            $('.stock-check-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).remove();
                }
            });
        }
        
        // Hàm hiển thị thông báo tạm thời
        function showTemporaryMessage(message, type = 'info') {
            const colorMap = {
                'success': '#4caf50',
                'warning': '#ff9800', 
                'error': '#f44336',
                'info': '#2196f3'
            };
            
            const $message = $(`
                <div class="temporary-message" style="
                    position: fixed;
                    top: 32px;
                    right: 20px;
                    background: ${colorMap[type]};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 4px;
                    z-index: 999999;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    animation: slideInRight 0.3s ease;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append($message);
            
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // Hàm kiểm tra tồn kho
        function checkStockBeforeUpdate(autoSubmit = false) {
            if (isCheckingStock) return;
            
            isCheckingStock = true;
            const $button = $('#stock-check-button');
            const originalText = $button.text();
            
            // Disable button và thay đổi text
            $button.prop('disabled', true).text('🔄 Đang kiểm tra...');
            
            // Thu thập dữ liệu từ form
            const items = [];
            $('#order_check_products_table tbody tr').each(function() {
                const $row = $(this);
                const productId = $row.find('select[name*="[product_id]"]').val();
                const boxCodes = $row.find('textarea[name*="[box_codes]"]').val();
                
                if (productId && boxCodes.trim()) {
                    items.push({
                        product_id: productId,
                        box_codes: boxCodes.trim()
                    });
                }
            });
            
            if (items.length === 0) {
                $button.prop('disabled', false).text(originalText);
                isCheckingStock = false;
                
                showTemporaryMessage('❌ Không có sản phẩm nào để kiểm tra!', 'error');
                return;
            }
            
            // AJAX call
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_stock_before_update',
                    items: items
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    isCheckingStock = false;
                    
                    if (response.success) {
                        stockCheckPassed = true;
                        formDataSnapshot = createFormSnapshot(); // Lưu snapshot khi check thành công
                        
                        // Cập nhật giao diện nút
                        $button.css({
                            'background': '#4caf50',
                            'border-color': '#4caf50'
                        }).text('✅ Đã kiểm tra');
                        
                        showSuccessMessage('✅ Kiểm tra tồn kho thành công! Tất cả sản phẩm đều có đủ tồn kho.');
                        
                        // Tự động submit nếu được yêu cầu
                        if (autoSubmit) {
                            setTimeout(() => {
                                showTemporaryMessage('💾 Đang lưu đơn hàng...', 'success');
                                $('form#post').off('submit').submit();
                            }, 1000);
                        }
                        
                    } else {
                        stockCheckPassed = false;
                        formDataSnapshot = null;
                        $button.text(originalText);
                        showStockError(response.data);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(originalText);
                    isCheckingStock = false;
                    
                    showTemporaryMessage('❌ Lỗi kết nối! Vui lòng thử lại.', 'error');
                }
            });
        }
        
        // Hàm hiển thị thông báo thành công
        function showSuccessMessage(message) {
            const successHtml = `
                <div class="notice notice-success is-dismissible" style="padding: 10px; margin: 10px 0;">
                    <p><strong>${message}</strong></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('.wrap').prepend(successHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $('.notice-success').fadeOut();
            }, 5000);
        }
        
        // Hàm hiển thị lỗi tồn kho
        function showStockError(errorData) {
            let errorMessage = '<div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px 12px;">';
            
            if (errorData.type === 'product_not_exist') {
                errorMessage += '<h3 style="color: #d32f2f; margin-top: 0;">🚫 ' + errorData.message + '</h3>';
                errorMessage += '<p style="color: #666; margin-bottom: 15px;">Các sản phẩm sau không có mã barcode nào trong hệ thống:</p>';
                
                if (errorData.issues && errorData.issues.length > 0) {
                    errorMessage += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    errorMessage += '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Sản phẩm</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Mã SP</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Trạng thái</th></tr></thead>';
                    errorMessage += '<tbody>';
                    
                    errorData.issues.forEach(function(issue) {
                        errorMessage += '<tr>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd;">' + issue.product_name + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">' + issue.custom_prod_id + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #d32f2f; font-weight: bold;">❌ Không có barcode</td>';
                        errorMessage += '</tr>';
                    });
                    
                    errorMessage += '</tbody></table>';
                }
                
            } else if (errorData.type === 'stock_shortage') {
                errorMessage += '<h3 style="color: #d32f2f; margin-top: 0;">❌ ' + errorData.message + '</h3>';
                
                if (errorData.issues && errorData.issues.length > 0) {
                    errorMessage += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    errorMessage += '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Sản phẩm</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Tồn kho</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Cần xuất</th><th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Thiếu</th></tr></thead>';
                    errorMessage += '<tbody>';
                    
                    errorData.issues.forEach(function(issue) {
                        errorMessage += '<tr>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd;">' + issue.product_name + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #f44336; font-weight: bold;">' + issue.current_stock + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' + issue.required_quantity + '</td>';
                        errorMessage += '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #d32f2f; font-weight: bold;">-' + issue.shortage + '</td>';
                        errorMessage += '</tr>';
                    });
                    
                    errorMessage += '</tbody></table>';
                }
            }
            
            errorMessage += '</div>';
            
            // Xóa error cũ nếu có
            $('.stock-error-message').remove();
            
            // Thêm error message
            $('#order_check_products_box').append('<div class="stock-error-message">' + errorMessage + '</div>');
            
            // Scroll to top để user thấy error
            $('html, body').animate({
                scrollTop: $('.stock-error-message').offset().top - 50
            }, 500);
        }
        
        // Reset stock check khi thay đổi dữ liệu
        $(document).on('input change', 'select[name*="[product_id]"], textarea[name*="[box_codes]"], input[name*="[box_quantity]"], select[name*="[lot_date]"], select[name="order_status"]', function() {
            if (stockCheckPassed) {
                stockCheckPassed = false;
                formDataSnapshot = null;
                $('.stock-error-message').remove();
                
                // Reset nút về trạng thái cần check lại
                $('#stock-check-button').css({
                    'background': '#ff5722',
                    'border-color': '#ff5722'
                }).text('📊 Cần kiểm tra lại');
                
                // Hiển thị thông báo nhỏ
                showTemporaryMessage('⚠️ Dữ liệu đã thay đổi, cần kiểm tra lại tồn kho', 'warning');
            }
        });
        
        // Event cho việc thêm/xóa sản phẩm
        $(document).on('click', '#add_product_row, .remove-row', function() {
            setTimeout(() => {
                if (stockCheckPassed) {
                    stockCheckPassed = false;
                    formDataSnapshot = null;
                    
                    $('#stock-check-button').css({
                        'background': '#ff5722',
                        'border-color': '#ff5722'
                    }).text('📊 Cần kiểm tra lại');
                    
                    showTemporaryMessage('⚠️ Danh sách sản phẩm đã thay đổi, cần kiểm tra lại', 'warning');
                }
            }, 100);
        });
        
        // Prevent accidental page leave khi đang check stock
        $(window).on('beforeunload', function(e) {
            if (isCheckingStock) {
                return 'Đang kiểm tra tồn kho, bạn có chắc muốn rời khỏi trang?';
            }
        });
        
        // Thêm indicator visual khi form đã được validate
        function updateFormValidationStatus() {
            const $publishButton = $('#publish');
            const $updateButton = $('#save-post');
            
            if (stockCheckPassed && !hasFormDataChanged()) {
                $publishButton.add($updateButton).addClass('stock-validated');
            } else {
                $publishButton.add($updateButton).removeClass('stock-validated');
            }
        }
        
        // Theo dõi thay đổi form để update validation status
        setInterval(updateFormValidationStatus, 1000);
    });
    </script>
    
    <style>
        /* Modal styles */
        .stock-check-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .stock-check-modal {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease;
        }
        
        .stock-check-modal-header {
            background: #ff9800;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .stock-check-modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .stock-check-modal-body {
            padding: 20px;
            display: flex;
            gap: 15px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .warning-icon {
            font-size: 40px;
            color: #ff9800;
            flex-shrink: 0;
        }
        
        .warning-content {
            flex: 1;
        }
        
        .warning-content p {
            margin: 0 0 10px 0;
        }
        
        .warning-content ul {
            margin: 10px 0 20px 20px;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-buttons .button {
            padding: 8px 16px !important;
            height: auto !important;
             width: 100%;
            margin: 0 !important;
            text-decoration: inherit;
        }

        /* Button validation styles */
        #publish.stock-validated,
        #save-post.stock-validated {
            position: relative;
            border-color: #4caf50 !important;
        }
        
        #publish.stock-validated::after,
        #save-post.stock-validated::after {
            content: "✅";
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Stock check button enhancements */
        #stock-check-button {
            transition: all 0.3s ease;
            position: relative;
        }
        
        #stock-check-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Loading states */
        #stock-check-button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .temporary-message {
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Error message styling enhancements */
        .stock-error-message {
            animation: slideDown 0.3s ease;
            margin: 15px 0;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <?php
}

function render_order_check_single_products_box($post) {
    $single_products = get_post_meta($post->ID, '_order_check_single_items', true);
    $all_products = wc_get_products(['limit' => -1]);
    ?>
    
    <div id="single_products_container">
        <!-- Header với thông tin -->
        <div class="single-products-header" style="background: #e3f2fd; padding: 12px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #1565c0;">🏷️ Nhập sản phẩm đơn lẻ</h4>
                    <p style="margin: 0; font-size: 13px; color: #666;">
                        Nhập từng sản phẩm riêng lẻ với mã định danh cụ thể. Hệ thống sẽ tự động kiểm tra trùng lặp và xem đã có trong thùng chưa.
                    </p>
                </div>
                <div class="single-summary" style="text-align: right; color: #1565c0;">
                    <div style="font-size: 24px; font-weight: bold;" id="single_total_count">0</div>
                    <div style="font-size: 12px;">sản phẩm lẻ</div>
                </div>
            </div>
        </div>

        <!-- Thông báo cảnh báo sản phẩm đã có trong thùng -->
        <div id="bulk_conflict_warning" style="display: none; margin-bottom: 15px;">
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 12px;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ Cảnh báo sản phẩm trùng lặp với thùng</h4>
                <div id="bulk_conflict_details"></div>
                <div style="margin-top: 10px;">
                    <button type="button" class="button button-secondary" id="force_allow_bulk_conflict">
                        ✓ Vẫn cho phép thêm
                    </button>
                    <span style="margin-left: 10px; font-size: 12px; color: #666;">
                        Sản phẩm sẽ được đánh dấu để phân biệt với sản phẩm trong thùng
                    </span>
                </div>
            </div>
        </div>

        <table class="widefat" id="single_products_table" style="margin-bottom:10px;">
            <thead>
                <tr>
                    <th style="width: 250px;">Tên sản phẩm</th>
                    <th style="width: 120px;">Số lượng mã</th>
                    <th>Lô</th>
                    <th>Date</th>
                    <th>Mã sản phẩm</th>
                    <th style="width: 200px;">Trạng thái</th>
                    <th style="width: 50px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($single_products)) {
                    foreach ($single_products as $index => $item) {
                        echo render_single_product_row_enhanced(
                            isset($item['product_id']) ? $item['product_id'] : '',
                            isset($item['quantity']) ? $item['quantity'] : '',
                            isset($item['product_codes']) ? $item['product_codes'] : '',
                            $index,
                            isset($item['lot_name']) ? $item['lot_name'] : '',
                            isset($item['lot_date']) ? $item['lot_date'] : '',
                            isset($item['allow_bulk_conflict']) ? $item['allow_bulk_conflict'] : false
                        );
                    }
                }
                ?>
            </tbody>
        </table>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="button" id="add_single_product_row">+ Thêm sản phẩm lẻ</button>
            <button type="button" class="button button-secondary" id="validate_single_products">✅ Kiểm tra tất cả</button>
        </div>

        <!-- Bảng tổng kết trùng lặp -->
        <div id="duplicate_summary" style="margin-top: 20px; display: none;">
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 12px;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ Cảnh báo trùng lặp</h4>
                <div id="duplicate_details"></div>
            </div>
        </div>
    </div>

    <script>
        let singleRowIndex = <?php echo (is_array($single_products) ? count($single_products) : 0); ?>;
        let allowBulkConflicts = {}; // Track products allowed despite bulk conflicts
        
        // Add single product row
        document.getElementById("add_single_product_row").addEventListener("click", function() {
            let tableBody = document.querySelector("#single_products_table tbody");
            let row = document.createElement("tr");
            
            row.innerHTML = renderSingleProductRowEnhanced('', '', '', '', singleRowIndex, '', false);
            tableBody.appendChild(row);
            singleRowIndex++;
            
            // Khởi tạo event listeners cho row mới
            initSingleProductEventListenersEnhanced(singleRowIndex - 1);
            
            // Cập nhật tổng kết
            setTimeout(updateSingleProductsSummary, 100);
        });

        // Remove single product row
        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-single-row")) {
                const row = e.target.closest("tr");
                const index = row.getAttribute('data-single-index');
                
                // Remove from allowBulkConflicts tracking
                if (index && allowBulkConflicts[index]) {
                    delete allowBulkConflicts[index];
                }
                
                row.remove();
                setTimeout(() => {
                    updateSingleProductsSummary();
                    validateAllSingleProductsEnhanced();
                }, 100);
            }
        });

        // Force allow bulk conflict
        document.getElementById("force_allow_bulk_conflict").addEventListener("click", function() {
            const conflictWarning = document.getElementById('bulk_conflict_warning');
            const conflictProductIds = conflictWarning.getAttribute('data-conflict-products');
            
            if (conflictProductIds) {
                const productIds = conflictProductIds.split(',');
                productIds.forEach(productId => {
                    // Find rows with this product and mark as allowed
                    document.querySelectorAll('#single_products_table tbody tr').forEach(row => {
                        const productSelect = row.querySelector('select[name*="[product_id]"]');
                        const index = row.getAttribute('data-single-index');
                        
                        if (productSelect && productSelect.value == productId) {
                            allowBulkConflicts[index] = true;
                            
                            // Add hidden input to track this
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = `order_check_single_products[${index}][allow_bulk_conflict]`;
                            hiddenInput.value = '1';
                            row.appendChild(hiddenInput);
                            
                            // Re-validate this product
                            validateSingleProductEnhanced(parseInt(index));
                        }
                    });
                });
                
                conflictWarning.style.display = 'none';
            }
        });

        // Validate single products
        document.getElementById("validate_single_products").addEventListener("click", function() {
            validateAllSingleProductsEnhanced();
        });

        // Enhanced render single product row function
        function renderSingleProductRowEnhanced(productId, quantity, lotDate, codes, index, lotName = '', allowBulkConflict = false) {
            const productOptions = <?php 
                $products_js = [];
                foreach ($all_products as $product) {
                    $stock = $product->get_stock_quantity();
                    $custom_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
                    $label = $product->get_name() . ' (ID: ' . $custom_id . ', Tồn: ' . $stock . ')';
                    $products_js[] = [
                        'id' => $product->get_id(),
                        'custom_id' => $custom_id,
                        'label' => $label
                    ];
                }
                echo json_encode($products_js);
            ?>;
            
            let optionsHtml = '<option value="">-- Chọn sản phẩm --</option>';
            productOptions.forEach(product => {
                const selected = productId == product.id ? 'selected' : '';
                optionsHtml += `<option value="${product.id}" data-custom-id="${product.custom_id}" ${selected}>${product.label}</option>`;
            });
            
            const bulkConflictInput = allowBulkConflict ? 
                `<input type="hidden" name="order_check_single_products[${index}][allow_bulk_conflict]" value="1" />` : '';
            
            return `
                <tr data-single-index="${index}">
                    <td>
                        <select name="order_check_single_products[${index}][product_id]" 
                                class="single-product-select" 
                                data-index="${index}" 
                                style="width: 100%;" 
                                required>
                            ${optionsHtml}
                        </select>
                        ${bulkConflictInput}
                    </td>
                    <td>
                        <input type="number" 
                               name="order_check_single_products[${index}][quantity]" 
                               class="single-product-quantity"
                               data-index="${index}"
                               value="${quantity}" 
                               placeholder="Số lượng"
                               min="1" 
                               style="width: 100%;" 
                               required />
                    </td>
                    <td>
                        <select name="order_check_single_products[${index}][lot_name]" 
                                id="single_lot_select_${index}" 
                                class="single-lot-select" 
                                data-index="${index}" 
                                style="width: 100%;"
                                required>
                            <option value="">-- Chọn lô --</option>
                        </select>
                    </td>
                    <td>
                        <input type="date" 
                               name="order_check_single_products[${index}][lot_date]" 
                               id="single_date_input_${index}" 
                               class="single-date-input" 
                               data-index="${index}" 
                               value="${lotDate}"
                               style="width: 100%;"
                               required />
                    </td>
                    <td>
                        <textarea name="order_check_single_products[${index}][product_codes]" 
                                  class="single-product-codes"
                                  data-index="${index}"
                                  rows="4" 
                                  placeholder="Nhập mã sản phẩm, mỗi dòng 1 mã"
                                  style="width: 100%;" 
                                  required>${codes}</textarea>
                    </td>
                    <td>
                        <div id="single_message_${index}" class="single-product-message" style="font-size: 12px;">
                            <em style="color: #666;">💡 Chọn sản phẩm và nhập thông tin</em>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="button remove-single-row" title="Xóa sản phẩm này">✕</button>
                    </td>
                </tr>
            `;
        }

        // Enhanced initialize event listeners for single product
        function initSingleProductEventListenersEnhanced(index) {
            const productSelect = document.querySelector(`[name="order_check_single_products[${index}][product_id]"]`);
            const quantityInput = document.querySelector(`[name="order_check_single_products[${index}][quantity]"]`);
            const codesInput = document.querySelector(`[name="order_check_single_products[${index}][product_codes]"]`);
            const lotSelect = document.querySelector(`[name="order_check_single_products[${index}][lot_name]"]`);
            const dateInput = document.querySelector(`[name="order_check_single_products[${index}][lot_date]"]`);
            
            if (productSelect) {
                productSelect.addEventListener('change', () => {
                    checkProductInBulkConflict(index);
                    loadLotsForSingleProduct(index);
                    validateSingleProductEnhanced(index);
                    updateSingleProductsSummary();
                });
            }
            if (quantityInput) {
                quantityInput.addEventListener('input', () => {
                    validateSingleProductEnhanced(index);
                    updateSingleProductsSummary();
                });
            }
            if (codesInput) {
                codesInput.addEventListener('input', () => {
                    validateSingleProductEnhanced(index);
                    updateSingleProductsSummary();
                });
            }
            if (lotSelect) {
                lotSelect.addEventListener('change', () => {
                    validateSingleProductEnhanced(index);
                });
            }
            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    validateSingleProductEnhanced(index);
                });
            }
        }

        // Check if product already exists in bulk products
        function checkProductInBulkConflict(index) {
            const productSelect = document.querySelector(`[name="order_check_single_products[${index}][product_id]"]`);
            const messageDiv = document.getElementById(`single_message_${index}`);
            
            if (!productSelect || !productSelect.value) {
                return;
            }
            
            const productId = productSelect.value;
            const orderId = <?php echo $post->ID; ?>;
            
            // Show checking message
            if (messageDiv) {
                messageDiv.innerHTML = '<small style="color: #007cba;">🔍 Kiểm tra sản phẩm trong thùng...</small>';
            }
            
            // AJAX call to check bulk products
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'check_product_in_bulk',
                    product_id: productId,
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce("check_product_bulk_nonce"); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.found_in_bulk) {
                    // Product found in bulk - show error unless explicitly allowed
                    if (!allowBulkConflicts[index]) {
                        handleBulkConflict(index, productId, data.data.bulk_info);
                    } else {
                        // Product allowed despite conflict
                        if (messageDiv) {
                            messageDiv.innerHTML = '<small style="color: #28a745;">✅ Sản phẩm được phép (đã có trong thùng)</small>';
                        }
                    }
                } else {
                    // Product not in bulk - OK to proceed
                    if (messageDiv) {
                        messageDiv.innerHTML = '<small style="color: #28a745;">✅ Sản phẩm chưa có trong thùng</small>';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking bulk conflict:', error);
                if (messageDiv) {
                    messageDiv.innerHTML = '<small style="color: #dc3545;">❌ Lỗi kiểm tra thùng</small>';
                }
            });
        }

        // Handle bulk conflict
        function handleBulkConflict(index, productId, bulkInfo) {
            const messageDiv = document.getElementById(`single_message_${index}`);
            const productSelect = document.querySelector(`[name="order_check_single_products[${index}][product_id]"]`);
            const conflictWarning = document.getElementById('bulk_conflict_warning');
            const conflictDetails = document.getElementById('bulk_conflict_details');
            
            // Show error in message
            if (messageDiv) {
                messageDiv.innerHTML = `
                    <small style="color: #dc3545;">
                        ❌ Sản phẩm đã có trong thùng<br>
                        📦 Thùng: ${bulkInfo.quantity} cái (Lô: ${bulkInfo.lot_name || 'N/A'})
                    </small>
                `;
            }
            
            // Style the select as error
            if (productSelect) {
                productSelect.style.borderColor = '#dc3545';
                productSelect.style.backgroundColor = '#fff5f5';
            }
            
            // Show warning banner
            conflictDetails.innerHTML = `
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="flex: 1;">
                        <strong>${bulkInfo.product_name}</strong> đã có trong danh sách thùng:<br>
                        <small style="color: #666;">
                            📦 Số lượng thùng: ${bulkInfo.quantity} | 
                            🏷️ Lô: ${bulkInfo.lot_name || 'N/A'} | 
                            📅 Ngày: ${bulkInfo.lot_date || 'N/A'}
                        </small>
                    </div>
                    <div style="color: #dc3545; font-size: 20px;">⚠️</div>
                </div>
            `;
            conflictWarning.setAttribute('data-conflict-products', productId);
            conflictWarning.style.display = 'block';
        }

        // Enhanced validate single product (includes bulk check)
        function validateSingleProductEnhanced(index) {
            const productSelect = document.querySelector(`[name="order_check_single_products[${index}][product_id]"]`);
            const quantityInput = document.querySelector(`[name="order_check_single_products[${index}][quantity]"]`);
            const codesInput = document.querySelector(`[name="order_check_single_products[${index}][product_codes]"]`);
            const lotSelect = document.querySelector(`[name="order_check_single_products[${index}][lot_name]"]`);
            const dateInput = document.querySelector(`[name="order_check_single_products[${index}][lot_date]"]`);
            const messageDiv = document.getElementById(`single_message_${index}`);
            
            if (!productSelect || !quantityInput || !codesInput || !messageDiv) return;
            
            const productId = productSelect.value;
            const quantity = parseInt(quantityInput.value) || 0;
            const codes = codesInput.value.trim();
            const lotName = lotSelect?.value || '';
            const lotDate = dateInput?.value || '';
            
            let errors = [];
            let warnings = [];
            
            // Basic validation
            if (!productId) {
                errors.push('Chưa chọn sản phẩm');
                productSelect.style.borderColor = '#dc3545';
                productSelect.style.backgroundColor = '';
            } else {
                productSelect.style.borderColor = '';
                productSelect.style.backgroundColor = '';
            }
            
            if (quantity <= 0) {
                errors.push('Số lượng phải > 0');
                quantityInput.style.borderColor = '#dc3545';
            } else {
                quantityInput.style.borderColor = '';
            }
            
            if (!codes) {
                errors.push('Thiếu mã sản phẩm');
                codesInput.style.borderColor = '#dc3545';
            } else {
                codesInput.style.borderColor = '';
            }

            if (!lotName) {
                errors.push('Chưa chọn lô');
                if (lotSelect) lotSelect.style.borderColor = '#dc3545';
            } else {
                if (lotSelect) lotSelect.style.borderColor = '';
            }

            if (!lotDate) {
                errors.push('Chưa chọn ngày');
                if (dateInput) dateInput.style.borderColor = '#dc3545';
            } else {
                if (dateInput) dateInput.style.borderColor = '';
            }
            
            // Advanced validation if basic info is complete
            if (productId && quantity > 0 && codes) {
                const codesList = codes.split(/[\n,;]+/)
                    .map(code => code.trim())
                    .filter(code => code);
                const actualCount = codesList.length;
                
                // 1. Quantity check
                if (actualCount !== quantity) {
                    warnings.push(`Số lượng không khớp: ${quantity} dự kiến, ${actualCount} thực tế`);
                }
                
                // 2. Internal duplicates
                const internalDuplicates = findInternalDuplicates(codesList);
                if (internalDuplicates.length > 0) {
                    const duplicateDetails = analyzeInternalDuplicates(codesList);
                    let duplicateMessage = 'Có mã trùng lặp trong danh sách: ';
                    
                    if (internalDuplicates.length <= 3) {
                        const detailStrings = duplicateDetails.map(item => `${item.code} (${item.count} lần)`);
                        duplicateMessage += detailStrings.join(', ');
                    } else {
                        const firstThree = duplicateDetails.slice(0, 3)
                            .map(item => `${item.code} (${item.count} lần)`);
                        duplicateMessage += firstThree.join(', ') + ` và ${internalDuplicates.length - 3} mã khác`;
                    }
                    
                    errors.push(duplicateMessage);
                }
                
                const uniqueCodes = [...new Set(codesList)];
                
                // 3. Check duplicates with other single products
                const duplicatesWithOtherSingle = checkDuplicateWithOtherSingleProductsImproved(index, uniqueCodes);
                if (duplicatesWithOtherSingle.length > 0) {
                    errors.push(`Trùng với sản phẩm lẻ khác: ${duplicatesWithOtherSingle.slice(0, 3).join(', ')}${duplicatesWithOtherSingle.length > 3 ? '...' : ''}`);
                }
                
                // 4. Check duplicates with bulk products
                const duplicatesWithBulk = checkDuplicateWithBulkProductsImproved(uniqueCodes);
                if (duplicatesWithBulk.length > 0) {
                    errors.push(`Trùng với sản phẩm theo thùng: ${duplicatesWithBulk.slice(0, 3).join(', ')}${duplicatesWithBulk.length > 3 ? '...' : ''}`);
                }
                
                // 5. Database validation
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const customProdId = selectedOption.getAttribute('data-custom-id');
                const productName = selectedOption.text;
                
                if (customProdId) {
                    validateCodesWithDatabase(uniqueCodes, customProdId, productName, index);
                }
            }
            
            // Display validation result (excluding bulk conflict and database validation)
            if (!allowBulkConflicts[index] && productId) {
                // Skip showing other errors if we need to check bulk conflict first
                displayValidationResultEnhanced(messageDiv, codesInput, errors, warnings, productId, quantity, codes, true);
            } else {
                displayValidationResultEnhanced(messageDiv, codesInput, errors, warnings, productId, quantity, codes, false);
            }
        }

        function displayValidationResultEnhanced(messageDiv, codesInput, errors, warnings, productId, quantity, codes, skipBulkMessage) {
            // Don't overwrite bulk conflict messages unless explicitly allowed
            const currentMessage = messageDiv.innerHTML;
            if (skipBulkMessage && (currentMessage.includes('Kiểm tra sản phẩm trong thùng') || currentMessage.includes('đã có trong thùng'))) {
                return;
            }
            
            let message = '';
            let color = '#666';
            
            if (errors.length > 0) {
                message = '❌ ' + errors.join('<br>• ');
                color = '#dc3545';
                codesInput.style.borderColor = '#dc3545';
            } else if (warnings.length > 0) {
                message = '⚠️ ' + warnings.join('<br>• ');
                color = '#ffc107';
                codesInput.style.borderColor = '#ffc107';
            } else if (productId && quantity > 0 && codes) {
                message = '🔄 Đang kiểm tra với database...';
                color = '#007cba';
                codesInput.style.borderColor = '#007cba';
            } else {
                message = '💡 Chọn sản phẩm và nhập thông tin';
                color = '#666';
            }
            
            messageDiv.innerHTML = `<small style="color: ${color};">${message}</small>`;
        }

        // Enhanced validate all single products
        function validateAllSingleProductsEnhanced() {
            const rows = document.querySelectorAll('#single_products_table tbody tr');
            let hasConflicts = false;
            
            rows.forEach((row) => {
                const index = row.getAttribute('data-single-index');
                if (index !== null) {
                    validateSingleProductEnhanced(parseInt(index));
                    
                    const messageDiv = document.getElementById(`single_message_${index}`);
                    if (messageDiv && messageDiv.textContent.includes('đã có trong thùng')) {
                        hasConflicts = true;
                    }
                }
            });
            
            if (hasConflicts) {
                showTemporaryMessage('⚠️ Có sản phẩm đã tồn tại trong thùng. Vui lòng xem xét!', 'warning');
            } else {
                showTemporaryMessage('✅ Tất cả sản phẩm lẻ đã được kiểm tra!', 'success');
            }
        }

        // Copy existing functions from original code
        function findInternalDuplicates(codesList) {
            const duplicates = [];
            const seen = new Set();
            const duplicateSet = new Set();
            
            codesList.forEach(code => {
                if (seen.has(code)) {
                    duplicateSet.add(code);
                } else {
                    seen.add(code);
                }
            });
            
            return Array.from(duplicateSet);
        }

        function analyzeInternalDuplicates(codesList) {
            const codeCount = {};
            const duplicates = [];
            
            codesList.forEach(code => {
                codeCount[code] = (codeCount[code] || 0) + 1;
            });
            
            Object.entries(codeCount).forEach(([code, count]) => {
                if (count > 1) {
                    duplicates.push({
                        code: code,
                        count: count
                    });
                }
            });
            
            return duplicates;
        }

        function checkDuplicateWithOtherSingleProductsImproved(currentIndex, currentCodes) {
            const duplicates = [];
            const allSingleRows = document.querySelectorAll('#single_products_table tbody tr');
            
            allSingleRows.forEach((row, rowPosition) => {
                let rowIndex = row.getAttribute('data-single-index');
                if (!rowIndex || rowIndex === '__index__' || rowIndex === null) {
                    rowIndex = rowPosition;
                } else {
                    rowIndex = parseInt(rowIndex);
                }
                
                if (rowIndex === parseInt(currentIndex)) {
                    return;
                }
                
                const otherCodesInput = row.querySelector('textarea[name*="[product_codes]"]');
                if (!otherCodesInput) return;
                
                const otherCodes = otherCodesInput.value.trim();
                if (!otherCodes) return;
                
                const otherCodesList = otherCodes.split(/[\n,;]+/)
                    .map(code => code.trim())
                    .filter(code => code);
                
                currentCodes.forEach(code => {
                    if (otherCodesList.includes(code)) {
                        duplicates.push(code);
                    }
                });
            });
            
            return [...new Set(duplicates)];
        }

        function validateBarcodesInBoxes(codes, customProdId, productName, index) {
            const messageDiv = document.getElementById(`single_message_${index}`);
            const codesInput = document.querySelector(`[name="order_check_single_products[${index}][product_codes]"]`);
            
            if (messageDiv) {
                const currentMessage = messageDiv.innerHTML;
                if (!currentMessage.includes('Đang kiểm tra barcode trong box')) {
                    messageDiv.innerHTML = currentMessage + '<br><small style="color: #007cba;">🔍 Đang kiểm tra barcode có thuộc box nào...</small>';
                }
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_barcode_in_boxes',
                    codes: codes,
                    product_id: customProdId,
                    product_name: productName,
                    nonce: '<?php echo wp_create_nonce("validate_barcode_boxes_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Barcode in boxes validation response:', response);
                    updateBarcodeInBoxesValidationResult(response, index, codesInput, messageDiv);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    if (messageDiv) {
                        let currentMessage = messageDiv.innerHTML;
                        currentMessage = currentMessage.replace(/🔍 Đang kiểm tra barcode trong box\.\.\./g, '');
                        messageDiv.innerHTML = currentMessage + '<br><small style="color: #dc3545;">❌ Lỗi kết nối database</small>';
                    }
                }
            });
        }

        function checkDuplicateWithBulkProductsImproved(codes) {
            const duplicates = [];
            document.querySelectorAll('[id^="product_codes_"]').forEach(div => {
                if (div.id.includes('single_')) return;
                
                const text = div.textContent || div.innerText;
                const lines = text.split('\n');
                
                lines.forEach(line => {
                    const cleanLine = line.trim();
                    if (cleanLine && 
                        !/[➤✅⚠️❌Tổng Thùng hợp lệ:]/i.test(cleanLine) && 
                        /^[A-Z0-9]+$/i.test(cleanLine) &&
                        cleanLine.length >= 6) {
                        
                        codes.forEach(code => {
                            if (cleanLine === code) {
                                duplicates.push(code);
                            }
                        });
                    }
                });
            });
            
            return [...new Set(duplicates)];
        }

        function loadLotsForSingleProduct(index) {
            const productSelect = document.querySelector(`[name="order_check_single_products[${index}][product_id]"]`);
            const lotSelect = document.querySelector(`[name="order_check_single_products[${index}][lot_name]"]`);
            const dateInput = document.querySelector(`[name="order_check_single_products[${index}][lot_date]"]`);
            
            if (!productSelect || !lotSelect) return;
            
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const customId = selectedOption.getAttribute('data-custom-id');
            
            if (dateInput) {
                dateInput.value = '';
            }
            
            if (!customId) {
                lotSelect.innerHTML = '<option value="">-- Chọn lô --</option>';
                return;
            }
            
            lotSelect.innerHTML = '<option value="">🔄 Đang tải...</option>';
            lotSelect.disabled = true;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_lots_by_product_id',
                    custom_prod_id: customId,
                    nonce: '<?php echo wp_create_nonce("get_lots_nonce"); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                lotSelect.innerHTML = '<option value="">-- Chọn lô --</option>';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(lot => {
                        const option = document.createElement('option');
                        option.value = lot.lot_name;
                        option.textContent = lot.lot_name;
                        lotSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Không có lot nào';
                    lotSelect.appendChild(option);
                }
                
                lotSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading lots:', error);
                lotSelect.innerHTML = '<option value="">❌ Lỗi tải dữ liệu</option>';
                lotSelect.disabled = false;
            });
        }

        function validateCodesWithDatabase(codes, customProdId, productName, index){
            const messageDiv = document.getElementById(`single_message_${index}`);
            const codesInput = document.querySelector(`[name="order_check_single_products[${index}][product_codes]"]`);
            
            if (messageDiv) {
                const currentMessage = messageDiv.innerHTML;
                if (!currentMessage.includes('Đang kiểm tra với database')) {
                    messageDiv.innerHTML = currentMessage + '<br><small style="color: #007cba;">🔍 Kiểm tra mã với database...</small>';
                }
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_single_product_codes',
                    codes: codes,
                    custom_prod_id: customProdId,
                    product_name: productName,
                    nonce: window.validateCodesNonce || ''
                },
                success: function(response) {
                    updateValidationWithDatabaseResult(response, index, codesInput, messageDiv);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    if (messageDiv) {
                        let currentMessage = messageDiv.innerHTML;
                        currentMessage = currentMessage.replace(/🔍 Kiểm tra mã với database\.\.\./g, '');
                        messageDiv.innerHTML = currentMessage + '<br><small style="color: #dc3545;">❌ Lỗi kết nối database</small>';
                    }
                }
            });
        }

        function updateValidationWithDatabaseResult(response, index, codesInput, messageDiv) {
            let additionalErrors = [];
            let additionalWarnings = [];

            if (response.success) {
                const data = response.data;

                const codesInBox = [];
                const validCodes = [];

                if (Array.isArray(data.valid_codes)) {
                    data.valid_codes.forEach(item => {
                        if (item.box_barcode) {
                            codesInBox.push(`${item.code} (box: ${item.box_barcode})`);
                        } else {
                            validCodes.push(item.code);
                        }
                    });
                }

                if (codesInBox.length > 0) {
                    additionalErrors.push(`Mã thuộc thùng đã khóa: ${codesInBox.slice(0, 3).join(', ')}${codesInBox.length > 3 ? ` (+${codesInBox.length - 3} mã khác)` : ''}`);
                }

                if (data.invalid_codes && data.invalid_codes.length > 0) {
                    additionalErrors.push(`Mã không thuộc sản phẩm này: ${data.invalid_codes.slice(0, 3).join(', ')}${data.invalid_codes.length > 3 ? ` (+${data.invalid_codes.length - 3} mã khác)` : ''}`);
                }

                if (data.used_codes && data.used_codes.length > 0) {
                    additionalWarnings.push(`Mã đã được sử dụng: ${data.used_codes.slice(0, 3).join(', ')}${data.used_codes.length > 3 ? ` (+${data.used_codes.length - 3} mã khác)` : ''}`);
                }

                if (data.non_existent_codes && data.non_existent_codes.length > 0) {
                    additionalErrors.push(`Mã không tồn tại: ${data.non_existent_codes.slice(0, 3).join(', ')}${data.non_existent_codes.length > 3 ? ` (+${data.non_existent_codes.length - 3} mã khác)` : ''}`);
                }

                let positiveInfo = '';
                if (validCodes.length > 0) {
                    const preview = validCodes.slice(0, 3).join(', ');
                    const more = validCodes.length > 3 ? ` (+${validCodes.length - 3} mã khác)` : '';
                    positiveInfo = `✅ ${validCodes.length} mã hợp lệ: ${preview}${more}`;
                }

                const currentMessageText = messageDiv.textContent || messageDiv.innerText;
                let existingErrors = [];
                let existingWarnings = [];

                if (currentMessageText.includes('❌')) {
                    const errorMatch = currentMessageText.match(/❌\s*(.+?)(?=⚠️|🔍|$)/s);
                    if (errorMatch) {
                        existingErrors = errorMatch[1].split('•')
                            .map(e => e.trim())
                            .filter(e => e && !e.includes('Kiểm tra mã') && !e.includes('database'));
                    }
                }

                if (currentMessageText.includes('⚠️') && !additionalErrors.length && !existingErrors.length) {
                    const warningMatch = currentMessageText.match(/⚠️\s*(.+?)(?=🔍|$)/s);
                    if (warningMatch) {
                        existingWarnings = warningMatch[1].split('•')
                            .map(w => w.trim())
                            .filter(w => w && !w.includes('Kiểm tra mã') && !w.includes('database'));
                    }
                }

                const allErrors = [...existingErrors, ...additionalErrors];
                const allWarnings = [...existingWarnings, ...additionalWarnings];

                let finalMessage = '';
                let finalColor = '#28a745';
                let borderColor = '#28a745';

                if (allErrors.length > 0) {
                    finalMessage = '❌ ' + allErrors.join('<br>• ');
                    finalColor = '#dc3545';
                    borderColor = '#dc3545';
                } else if (allWarnings.length > 0) {
                    finalMessage = '⚠️ ' + allWarnings.join('<br>• ');
                    if (positiveInfo) finalMessage += '<br>' + positiveInfo;
                    finalColor = '#ffc107';
                    borderColor = '#ffc107';
                } else {
                    finalMessage = positiveInfo || '✅ Tất cả mã hợp lệ';
                    finalColor = '#28a745';
                    borderColor = '#28a745';
                }

                messageDiv.innerHTML = `<small style="color: ${finalColor};">${finalMessage}</small>`;
                codesInput.style.borderColor = borderColor;

            } else {
                let currentMessage = messageDiv.innerHTML;
                currentMessage = currentMessage.replace(/🔍 Kiểm tra mã với database\.\.\./g, '');
                messageDiv.innerHTML = currentMessage + '<br><small style="color: #dc3545;">❌ ' + (response.data || 'Lỗi kiểm tra') + '</small>';
                codesInput.style.borderColor = '#dc3545';
            }
        }

        function updateSingleProductsSummary() {
            let totalProducts = 0;
            let totalCodes = 0;
            
            const rows = document.querySelectorAll('#single_products_table tbody tr');
            rows.forEach((row) => {
                const productSelect = row.querySelector('select[name*="[product_id]"]');
                const codesInput = row.querySelector('textarea[name*="[product_codes]"]');
                
                if (productSelect && productSelect.value) {
                    totalProducts++;
                    
                    if (codesInput && codesInput.value.trim()) {
                        const codes = codesInput.value.trim().split(/[\n,;]+/).filter(code => code.trim());
                        totalCodes += codes.length;
                    }
                }
            });
            
            document.getElementById('single_total_count').textContent = totalCodes.toLocaleString();
            
            if (typeof updateOrderSummary === 'function') {
                updateOrderSummary();
            }
        }

        function showTemporaryMessage(message, type = 'info') {
            const colorMap = {
                'success': '#4caf50',
                'warning': '#ff9800', 
                'error': '#f44336',
                'info': '#2196f3'
            };
            
            document.querySelectorAll('.temporary-message').forEach(msg => msg.remove());
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'temporary-message';
            messageDiv.style.cssText = `
                position: fixed;
                top: 32px;
                right: 20px;
                background: ${colorMap[type]};
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                z-index: 999999;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                animation: slideInRight 0.3s ease;
            `;
            messageDiv.innerHTML = message;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 4000);
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('#single_products_table tbody tr').forEach((row) => {
                const index = row.getAttribute('data-single-index');
                if (index !== null && index !== '__index__') {
                    initSingleProductEventListenersEnhanced(parseInt(index));
                    
                    // Check if this product has allowBulkConflict set
                    const allowInput = row.querySelector('input[name*="[allow_bulk_conflict]"]');
                    if (allowInput && allowInput.value === '1') {
                        allowBulkConflicts[index] = true;
                    }
                    
                    validateSingleProductEnhanced(parseInt(index));
                }
            });
            
            updateSingleProductsSummary();
        });

        // Initialize if DOM already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    updateSingleProductsSummary();
                    validateAllSingleProductsEnhanced();
                }, 500);
            });
        } else {
            setTimeout(() => {
                updateSingleProductsSummary();
                validateAllSingleProductsEnhanced();
            }, 500);
        }

        // Provide nonce for AJAX calls
        if (typeof window.validateCodesNonce === 'undefined') {
            window.validateCodesNonce = '<?php echo wp_create_nonce("validate_codes_nonce"); ?>';
        }
    </script>
    
    <style>
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        
        #single_products_table input,
        #single_products_table textarea,
        #single_products_table select {
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }
        
        #single_products_table input:focus,
        #single_products_table textarea:focus,
        #single_products_table select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .single-product-message {
            max-height: 80px;
            overflow-y: auto;
            line-height: 1.3;
            word-wrap: break-word;
        }
        
        #bulk_conflict_warning {
            animation: fadeInDown 0.3s ease;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Error states */
        .error-state {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .warning-state {
            border-color: #ffc107 !important;
            background-color: #fff9e6 !important;
        }
        
        .success-state {
            border-color: #28a745 !important;
            background-color: #f8fff8 !important;
        }
    </style>
    <?php
}

// 3. Enhanced render single product row function
function render_single_product_row_enhanced($product_id = '', $quantity = '', $product_codes = '', $index = '__index__', $lot_name = '', $lot_date = '', $allow_bulk_conflict = false) {
    global $wpdb;
    $all_products = wc_get_products(['limit' => -1]);
    
    ob_start();
    ?>
    <tr data-single-index="<?php echo $index; ?>">
        <td>
            <select name="order_check_single_products[<?php echo $index; ?>][product_id]" 
                    class="single-product-select" 
                    data-index="<?php echo $index; ?>" 
                    style="width: 100%;" 
                    required>
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($all_products as $product): ?>
                    <?php 
                        $stock = $product->get_stock_quantity();
                        $custom_id = get_post_meta($product->get_id(), 'custom_prod_id', true);
                        $label = $product->get_name() . ' (ID: ' . $custom_id . ', Tồn: ' . $stock . ')';
                    ?>
                    <option value="<?php echo esc_attr($product->get_id()); ?>" 
                            data-custom-id="<?php echo esc_attr($custom_id); ?>"
                            <?php selected($product_id, $product->get_id()); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($allow_bulk_conflict): ?>
                <input type="hidden" name="order_check_single_products[<?php echo $index; ?>][allow_bulk_conflict]" value="1" />
            <?php endif; ?>
        </td>
        <td>
            <input type="number" 
                   name="order_check_single_products[<?php echo $index; ?>][quantity]" 
                   class="single-product-quantity"
                   data-index="<?php echo $index; ?>"
                   value="<?php echo esc_attr($quantity); ?>" 
                   placeholder="Số lượng"
                   min="1" 
                   style="width: 100%;" 
                   required />
        </td>
        <td>
            <select name="order_check_single_products[<?php echo $index; ?>][lot_name]" 
                    id="single_lot_select_<?php echo esc_attr($index); ?>" 
                    class="single-lot-select" 
                    data-index="<?php echo esc_attr($index); ?>" 
                    style="width: 100%;"
                    required>
                <option value="">-- Chọn lô --</option>
                <?php if ($product_id && $lot_name): ?>
                    <?php
                    $lot_table = BIZGPT_PLUGIN_WP_PRODUCT_LOT;
                    $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
                    
                    if ($custom_prod_id) {
                        $lots = $wpdb->get_results($wpdb->prepare(
                            "SELECT DISTINCT lot_name FROM $lot_table WHERE product_id = %s ORDER BY lot_name DESC",
                            $custom_prod_id
                        ));
                        
                        foreach ($lots as $lot) {
                            $selected = ($lot->lot_name == $lot_name) ? 'selected' : '';
                            echo '<option value="' . esc_attr($lot->lot_name) . '" ' . $selected . '>' . esc_html($lot->lot_name) . '</option>';
                        }
                    }
                    ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <input type="date" 
                   name="order_check_single_products[<?php echo $index; ?>][lot_date]" 
                   id="single_date_input_<?php echo esc_attr($index); ?>" 
                   class="single-date-input" 
                   data-index="<?php echo esc_attr($index); ?>" 
                   value="<?php echo esc_attr($lot_date); ?>"
                   style="width: 100%;"
                   required />
        </td>
        <td>
            <textarea name="order_check_single_products[<?php echo $index; ?>][product_codes]" 
                      class="single-product-codes"
                      data-index="<?php echo $index; ?>"
                      rows="4" 
                      placeholder="Nhập mã sản phẩm, mỗi dòng 1 mã"
                      style="width: 100%;" 
                      required><?php echo esc_textarea($product_codes); ?></textarea>
        </td>
        <td>
            <div id="single_message_<?php echo $index; ?>" class="single-product-message" style="font-size: 12px; max-width: 200px;">
                <em style="color: #666;">💡 Nhập đầy đủ thông tin</em>
            </div>
        </td>
        <td>
            <button type="button" class="button remove-single-row" title="Xóa sản phẩm này">✕</button>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

// Cập nhật hàm updateOrderSummary trong file cũ để tính cả single products
add_action('admin_footer', 'add_single_products_integration_script');
function add_single_products_integration_script() {
    global $post;
    if (!isset($post) || $post->post_type !== 'order_check') return;
    ?>
    <script>
    // Integration với order summary chính
    if (typeof updateOrderSummary !== 'undefined') {
        const originalUpdateOrderSummary = updateOrderSummary;
        updateOrderSummary = function() {
            originalUpdateOrderSummary();
            
            // Cập nhật thêm thông tin single products
            let singleProductsCount = 0;
            let singleProductsCodes = 0;
            
            document.querySelectorAll('#single_products_table tbody tr').forEach((row) => {
                const nameInput = row.querySelector('input[name*="[product_name]"]');
                const codesInput = row.querySelector('textarea[name*="[product_codes]"]');
                
                if (nameInput && nameInput.value.trim()) {
                    singleProductsCount++;
                    
                    if (codesInput && codesInput.value.trim()) {
                        const codes = codesInput.value.trim().split(/[\n,;]+/).filter(code => code.trim());
                        singleProductsCodes += codes.length;
                    }
                }
            });
            
            // Cập nhật tổng số sản phẩm trong order summary
            const totalProductsElement = document.getElementById('total_products');
            const productsDetailElement = document.getElementById('products_detail');
            
            if (totalProductsElement && productsDetailElement) {
                const bulkProducts = parseInt(totalProductsElement.textContent.replace(/,/g, '')) || 0;
                const currentBulkProducts = bulkProducts - singleProductsCodes; // Trừ đi single products đã tính trước đó
                const grandTotal = currentBulkProducts + singleProductsCodes;
                
                totalProductsElement.textContent = grandTotal.toLocaleString();
                productsDetailElement.textContent = `${currentBulkProducts} từ thùng + ${singleProductsCodes} lẻ`;
            }
        };
    }
    </script>
    <?php
}

add_action('wp_ajax_check_stock_before_update', 'handle_check_stock_before_update_with_singles');
function handle_check_stock_before_update_with_singles() {
    global $wpdb;
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;
    
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $single_items = isset($_POST['single_items']) ? $_POST['single_items'] : [];
    
    if (empty($items) && empty($single_items)) {
        wp_send_json_error('Không có sản phẩm nào để kiểm tra');
        return;
    }
    
    $stock_issues = [];
    $product_not_exist_issues = [];
    $single_product_issues = [];
    
    // Kiểm tra bulk products (code cũ)
    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        $box_codes = isset($item['box_codes']) ? $item['box_codes'] : '';
        
        if (!$product_id || empty($box_codes)) continue;
        
        $product = wc_get_product($product_id);
        if (!$product) continue;
        
        $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
        if (empty($custom_prod_id)) continue;
        
        // Kiểm tra sản phẩm có tồn tại trong bảng barcode không
        $product_exists_in_barcode = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $barcode_table WHERE product_id = %s",
            $custom_prod_id
        ));
        
        if ($product_exists_in_barcode == 0) {
            $product_not_exist_issues[] = [
                'product_name' => $product->get_name(),
                'product_id' => $product_id,
                'custom_prod_id' => $custom_prod_id
            ];
            continue;
        }
        
        // Tính số lượng mã sản phẩm thực tế từ các thùng
        $box_codes_array = array_filter(array_map('trim', explode("\n", $box_codes)));
        $total_product_codes = 0;
        $invalid_boxes = [];
        
        foreach ($box_codes_array as $box_code) {
            $product_codes_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $barcode_table 
                 WHERE box_barcode = %s AND product_id = %s",
                $box_code, $custom_prod_id
            ));
            
            if ($product_codes_count > 0) {
                $total_product_codes += intval($product_codes_count);
            } else {
                $invalid_boxes[] = $box_code;
            }
        }
        
        $current_stock = $product->get_stock_quantity();
        
        if ($total_product_codes > 0 && $current_stock < $total_product_codes) {
            $stock_issues[] = [
                'product_name' => $product->get_name(),
                'product_id' => $product_id,
                'current_stock' => $current_stock,
                'required_quantity' => $total_product_codes,
                'shortage' => $total_product_codes - $current_stock,
                'invalid_boxes' => $invalid_boxes,
                'type' => 'bulk'
            ];
        }
    }
    
    // Kiểm tra single products
    foreach ($single_items as $single_item) {
        $product_name = sanitize_text_field($single_item['product_name']);
        $quantity = intval($single_item['quantity']);
        $product_codes = sanitize_textarea_field($single_item['product_codes']);
        
        if (empty($product_name) || $quantity <= 0 || empty($product_codes)) continue;
        
        $codes_array = array_filter(array_map('trim', explode("\n", $product_codes)));
        $actual_quantity = count($codes_array);
        
        // Kiểm tra số lượng khớp
        if ($actual_quantity !== $quantity) {
            $single_product_issues[] = [
                'product_name' => $product_name,
                'expected_quantity' => $quantity,
                'actual_quantity' => $actual_quantity,
                'issue_type' => 'quantity_mismatch'
            ];
        }
        
        // Kiểm tra mã trùng lặp nội bộ
        $unique_codes = array_unique($codes_array);
        if (count($unique_codes) !== count($codes_array)) {
            $single_product_issues[] = [
                'product_name' => $product_name,
                'issue_type' => 'internal_duplicate',
                'duplicate_count' => count($codes_array) - count($unique_codes)
            ];
        }
        
        // Kiểm tra mã có tồn tại trong hệ thống không (optional)
        $existing_codes = [];
        foreach ($unique_codes as $code) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $barcode_table WHERE barcode = %s",
                $code
            ));
            if ($exists > 0) {
                $existing_codes[] = $code;
            }
        }
        
        if (!empty($existing_codes)) {
            $single_product_issues[] = [
                'product_name' => $product_name,
                'issue_type' => 'codes_exist_in_system',
                'existing_codes' => $existing_codes
            ];
        }
    }
    
    // Ưu tiên lỗi theo thứ tự
    if (!empty($product_not_exist_issues)) {
        wp_send_json_error([
            'type' => 'product_not_exist',
            'message' => 'Một số sản phẩm không có mã barcode trong hệ thống',
            'issues' => $product_not_exist_issues
        ]);
        return;
    }
    
    if (!empty($single_product_issues)) {
        wp_send_json_error([
            'type' => 'single_product_issues',
            'message' => 'Có vấn đề với sản phẩm đơn lẻ',
            'issues' => $single_product_issues
        ]);
        return;
    }
    
    if (!empty($stock_issues)) {
        wp_send_json_error([
            'type' => 'stock_shortage',
            'message' => 'Không đủ tồn kho cho một số sản phẩm',
            'issues' => $stock_issues
        ]);
        return;
    }
    
    wp_send_json_success('Tất cả sản phẩm hợp lệ và đủ tồn kho');
}

add_action('admin_footer', 'add_improved_single_product_validation_script');
function add_improved_single_product_validation_script() {
    global $post;
    if (!isset($post) || $post->post_type !== 'order_check') return;
    ?>
    <script>
        // Khởi tạo nonce ngay khi load trang
        if (typeof window.validateCodesNonce === 'undefined') {
            window.validateCodesNonce = '<?php echo wp_create_nonce("validate_codes_nonce"); ?>';
        }

        // Đảm bảo rằng khi thêm row mới, data-single-index được set đúng
        jQuery(document).ready(function($){
            $(document).on('click', '#add_single_product_row', function() {
                // Đợi DOM được cập nhật, sau đó set attribute
                setTimeout(function() {
                    $('#single_products_table tbody tr').each(function(actualIndex) {
                        const currentDataIndex = $(this).attr('data-single-index');
                        if (!currentDataIndex || currentDataIndex === '__index__') {
                            $(this).attr('data-single-index', actualIndex);
                            
                            // Cập nhật lại tất cả name attributes và IDs
                            $(this).find('select, input, textarea').each(function() {
                                const name = $(this).attr('name');
                                const id = $(this).attr('id');
                                
                                if (name) {
                                    const newName = name.replace(/\[__index__\]/g, '[' + actualIndex + ']');
                                    $(this).attr('name', newName);
                                }
                                
                                if (id) {
                                    const newId = id.replace(/__index__/g, actualIndex);
                                    $(this).attr('id', newId);
                                }
                                
                                // Cập nhật data-index
                                $(this).attr('data-index', actualIndex);
                            });
                            
                            // Cập nhật ID của message div
                            const messageDiv = $(this).find('.single-product-message');
                            if (messageDiv.length) {
                                messageDiv.attr('id', 'single_message_' + actualIndex);
                            }
                        }
                    });
                    
                    console.log('✅ Row attributes updated successfully');
                }, 100);
            });
        });

        // Fix cho các row hiện có khi load trang
        jQuery(document).ready(function($){
            $('#single_products_table tbody tr').each(function(actualIndex) {
                const currentDataIndex = $(this).attr('data-single-index');
                if (!currentDataIndex || currentDataIndex === '__index__' || currentDataIndex === null) {
                    $(this).attr('data-single-index', actualIndex);
                    
                    // Cập nhật tất cả data-index trong row này
                    $(this).find('select, input, textarea').each(function() {
                        $(this).attr('data-index', actualIndex);
                    });
                    
                    console.log('Fixed row', actualIndex, 'data-single-index');
                }
            });
        });

        console.log('✅ Improved Single Product Validation - PHP Integration Ready!');
    </script>
    <?php
}

add_action('wp_ajax_validate_single_product_codes', 'handle_validate_single_product_codes');
add_action('wp_ajax_nopriv_validate_single_product_codes', 'handle_validate_single_product_codes');

function handle_validate_single_product_codes() {
    if (!wp_verify_nonce($_POST['nonce'], 'validate_codes_nonce')) {
        wp_send_json_error('Nonce verification failed');
        return;
    }

    global $wpdb;
    $barcode_table = BIZGPT_PLUGIN_WP_BARCODE;

    $codes = isset($_POST['codes']) ? $_POST['codes'] : [];
    $custom_prod_id = sanitize_text_field($_POST['custom_prod_id']);
    $product_name = sanitize_text_field($_POST['product_name']);

    if (empty($codes) || empty($custom_prod_id)) {
        wp_send_json_error('Thiếu thông tin cần thiết');
        return;
    }

    $valid_codes = [];
    $invalid_codes = [];
    $used_codes = [];
    $non_existent_codes = [];

    foreach ($codes as $code) {
        $code = trim($code);
        if (empty($code)) continue;

        $barcode_info = $wpdb->get_row($wpdb->prepare(
            "SELECT barcode, product_id, status, box_barcode FROM $barcode_table WHERE barcode = %s",
            $code
        ));

        if (!$barcode_info) {
            $non_existent_codes[] = $code;
            continue;
        }

        if ($barcode_info->product_id !== $custom_prod_id) {
            $invalid_codes[] = $code;
            continue;
        }

        if ($barcode_info->status === 'used') {
            $used_codes[] = $code;
            continue;
        }

        $valid_codes[] = [
            'code' => $code,
            'box_barcode' => $barcode_info->box_barcode ?: null
        ];
    }

    $response_data = [
        'valid_codes' => $valid_codes,
        'invalid_codes' => $invalid_codes,
        'used_codes' => $used_codes,
        'non_existent_codes' => $non_existent_codes,
        'total_checked' => count($codes),
        'summary' => [
            'valid_count' => count($valid_codes),
            'invalid_count' => count($invalid_codes),
            'used_count' => count($used_codes),
            'non_existent_count' => count($non_existent_codes)
        ]
    ];

    error_log('Validate Single Product Codes Result: ' . json_encode([
        'product_name' => $product_name,
        'custom_prod_id' => $custom_prod_id,
        'codes_count' => count($codes),
        'summary' => $response_data['summary']
    ]));

    wp_send_json_success($response_data);
    wp_die();
}

// AJAX handler để cung cấp nonce
add_action('wp_ajax_get_validate_codes_nonce', 'handle_get_validate_codes_nonce');
add_action('wp_ajax_nopriv_get_validate_codes_nonce', 'handle_get_validate_codes_nonce');

function handle_get_validate_codes_nonce() {
    wp_send_json_success(wp_create_nonce('validate_codes_nonce'));
    wp_die();
}

add_action('admin_footer', 'disable_update_button_for_completed_orders');
function disable_update_button_for_completed_orders() {
    global $post, $pagenow;
    
    // Chỉ áp dụng cho trang edit order_check
    if ($pagenow !== 'post.php' || !isset($post) || $post->post_type !== 'order_check') {
        return;
    }
    
    $order_status = get_post_meta($post->ID, 'order_status', true);
    
    // Nếu trạng thái là completed, disable nút update
    if ($order_status === 'completed') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Disable nút Update/Publish
            $('#publish, #save-post').prop('disabled', true);
            
            // Thay đổi text và style của nút
            if ($('#publish').length) {
                $('#publish').val('🔒 Đã hoàn thành - Không thể sửa')
                    .css({
                        'background-color': '#6c757d',
                        'border-color': '#6c757d',
                        'cursor': 'not-allowed',
                        'opacity': '0.7'
                    });
            }
            
            if ($('#save-post').length) {
                $('#save-post').val('🔒 Đã khóa')
                    .css({
                        'background-color': '#6c757d',
                        'border-color': '#6c757d',
                        'cursor': 'not-allowed',
                        'opacity': '0.7'
                    });
            }
            
            // Disable toàn bộ form để ngăn chỉnh sửa
            // $('#post input:not(#post_status), #post textarea, #post select:not(#post_status)').prop('disabled', true);
            
            // Disable các nút thêm/xóa sản phẩm
            $('.button').each(function() {
                if ($(this).text().includes('Thêm') || 
                    $(this).text().includes('Xóa') || 
                    $(this).text().includes('Kiểm tra') ||
                    $(this).hasClass('remove-row') ||
                    $(this).hasClass('remove-single-row') ||
                    $(this).attr('id') === 'add_product_row' ||
                    $(this).attr('id') === 'add_single_product_row' ||
                    $(this).attr('id') === 'check_quantities' ||
                    $(this).attr('id') === 'validate_single_products' ||
                    $(this).attr('id') === 'stock-check-button') {
                    $(this).prop('disabled', true).css({
                        'cursor': 'not-allowed',
                        'opacity': '0.5'
                    });
                }
            });
            
            // Disable upload buttons
            $('.upload_gallery_button').prop('disabled', true).css({
                'cursor': 'not-allowed',
                'opacity': '0.5'
            });
            
            // Thêm thông báo ở đầu trang
            $('.wrap h1').after(`
                <div class="notice notice-info" style="margin: 10px 0; padding: 12px; background: #e3f2fd; border-left: 4px solid #1976d2;">
                    <p style="margin: 0; font-weight: bold; color: #1565c0;">
                        🔒 <strong>Đơn hàng đã hoàn thành</strong> - Tất cả các trường đã được khóa để bảo vệ dữ liệu. 
                        Chỉ có Administrator mới có thể chỉnh sửa đơn hàng đã hoàn thành.
                    </p>
                </div>
            `);
            
            // Thêm overlay để ngăn tương tác với form
            $('#post').css('position', 'relative');
            if ($('#completed-order-overlay').length === 0) {
                $('#post').append(`
                    <div id="completed-order-overlay" style="
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(108, 117, 125, 0.1);
                        z-index: 10;
                        pointer-events: none;
                        border-radius: 6px;
                    "></div>
                `);
            }
            
            // Disable context menu để ngăn copy/paste
            $('#post').on('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            console.log('✅ Order completed - Form locked successfully');
        });
        </script>
        
        <style>
            /* Thêm visual indicators cho trạng thái locked */
            #post.completed-order {
                opacity: 0.9;
            }
            
            #post.completed-order input:disabled,
            #post.completed-order textarea:disabled,
            #post.completed-order select:disabled {
                background-color: #f8f9fa !important;
                color: #6c757d !important;
                cursor: not-allowed !important;
            }
            
            /* Thêm icon lock cho các field bị disable */
            #post.completed-order input:disabled::before,
            #post.completed-order textarea:disabled::before,
            #post.completed-order select:disabled::before {
                content: "🔒 ";
            }
            
            /* Style cho thông báo */
            .completed-order-notice {
                background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
        </style>
        <?php
    }
}
