<?php

function register_order_check_post_type() {
    register_post_type('order_check', array(
        'labels' => array(
            'name' => 'Order Checks',
            'singular_name' => 'Order Check',
            'add_new' => 'Thêm Order Check',
            'add_new_item' => 'Thêm mới Order Check',
            'edit_item' => 'Chỉnh sửa Order Check',
            'new_item' => 'Order Check mới',
            'view_item' => 'Xem Order Check',
            'search_items' => 'Tìm Order Check',
            'not_found' => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác'
        ),
        'public' => true,
        'menu_icon' => 'dashicons-clipboard',
        'show_in_menu' => 'gpt-macao',
        'supports' => array('title'),
        'has_archive' => true,
    ));
}
add_action('init', 'register_order_check_post_type');

function add_order_check_metaboxes() {
    add_meta_box('order_check_fields', 'Thông tin Order Check', 'render_order_check_fields', 'order_check', 'normal', 'default');
}
add_action('add_meta_boxes', 'add_order_check_metaboxes');

function render_order_check_fields($post) {
    $order_id = get_post_meta($post->ID, 'order_id', true);
    $order_images = get_post_meta($post->ID, 'order_images', true);
    $macao_ids = get_post_meta($post->ID, 'macao_ids', true);
    $order_batch = get_post_meta($post->ID, 'order_batch', true);
    $order_date = get_post_meta($post->ID, 'order_date', true);
    $order_export_by = get_post_meta($post->ID, 'order_export_by', true);

    wp_nonce_field('save_order_check_fields', 'order_check_nonce');

    $order_date = get_post_meta($post->ID, 'order_date', true);
    if (empty($order_date)) {
        $order_date = current_time('mysql');
    }
    ?>
    <p>
        <label for="order_id">ID Đơn hàng:</label><br>
        <input type="text" name="order_id" id="order_id" value="<?php echo esc_attr($order_id); ?>" style="width:100%;">
    </p>
    <p>
        <label for="order_batch">Lô date:</label><br>
        <input type="text" name="order_batch" id="order_batch" value="<?php echo esc_attr($order_batch); ?>" style="width:100%;">
    </p>
    <p>
        <label for="order_date">Ngày giờ xuất:</label><br>
        <input type="datetime-local" name="order_date" id="order_date"
            value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($order_date))); ?>"
            style="width:100%;">
    </p>
    <p>
        <label for="order_export_by">Người xuất kho:</label><br>
        <input type="text" name="order_export_by" id="order_export_by" value="<?php echo esc_attr($order_export_by); ?>" style="width:100%;">
    </p>
    <p>
        <label for="order_images">Ảnh đơn hàng (có thể chọn nhiều):</label><br>
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
                    $('#order_images').val(attachment_urls.join(','));
                    $('#order_images_preview').html(preview_html);
                }).open();
            });
        });
    </script>

    <?php
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
    $macao_table   = BIZGPT_PLUGIN_WP_BARCODE;
    $sellout_table = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS_SELL_OUT;

    // Meta fields
    update_post_meta_if_changed($post_id, 'order_id', sanitize_text_field($_POST['order_id']));
    update_post_meta_if_changed($post_id, 'order_images', sanitize_text_field($_POST['order_images']));
    update_post_meta_if_changed($post_id, 'order_batch', sanitize_text_field($_POST['order_batch']));
    update_post_meta_if_changed($post_id, 'order_date', sanitize_text_field($_POST['order_date']));
    update_post_meta_if_changed($post_id, 'order_export_by', sanitize_text_field($_POST['order_export_by']));

    // Trạng thái đơn + log
    if (isset($_POST['order_status'])) {
        $new_status = sanitize_text_field($_POST['order_status']);
        $old_status = get_post_meta($post_id, 'order_status', true);
        if ($new_status !== $old_status) {
            update_post_meta($post_id, 'order_status', $new_status);
            $logs = get_post_meta($post_id, 'order_status_logs', true);
            if (!is_array($logs)) $logs = [];
            $logs[] = ['status' => $new_status, 'timestamp' => current_time('mysql')];
            update_post_meta($post_id, 'order_status_logs', $logs);
        }
    }

    // Xử lý sản phẩm
    $items = $_POST['order_check_products'] ?? [];
    $existing_items = get_post_meta($post_id, '_order_check_line_items', true);

    if ($items !== $existing_items) {
        update_post_meta($post_id, '_order_check_line_items', $items);
        $wpdb->delete($order_table, ['order_id' => $post_id]);

        $inventory_logs = get_post_meta($post_id, '_inventory_logs', true);
        if (!is_array($inventory_logs)) $inventory_logs = [];

        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $barcode = sanitize_textarea_field($item['barcode']);
            $province = sanitize_text_field($item['province']);
            $channel = sanitize_text_field($item['channel']);
            if (!$product_id || $qty <= 0) continue;

            $product = wc_get_product($product_id);
            $title = $product ? $product->get_name() : '';
            $stock = $product ? $product->get_stock_quantity() : 0;
            $new_stock = $stock - $qty;
            $timestamp = current_time('mysql');

            if ($product) {
                if ($new_stock >= 0) {
                    $product->set_stock_quantity($new_stock);
                    $product->save();
                    $inventory_logs[] = sprintf("[%s] ✅ Trừ %d [%s] (ID: %d) từ đơn #%d. Kho: %d → %d", $timestamp, $qty, $title, $product_id, $post_id, $stock, $new_stock);
                } else {
                    $inventory_logs[] = sprintf("[%s] ❌ Không đủ tồn kho cho [%s] (còn %d, cần %d)", $timestamp, $title, $stock, $qty);
                }
            }

            $wpdb->insert($order_table, [
                'order_id' => $post_id,
                'title'       => $title,
                'quantity'    => $qty,
                'barcode'      => $barcode,
                'province'    => $province,
                'channel'     => $channel
            ]);

            $macaos = preg_split('/[\n,;]+/', $barcode, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($macaos as $m) {
                $m = trim($m);
                if ($m) {
                    $wpdb->update($macao_table, ['order_by_product_id' => $post_id], ['barcode' => $m]);
                }
            }
        }

        update_post_meta($post_id, '_inventory_logs', $inventory_logs);
    }

    // Cập nhật bảng sell-out
    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        if (!$product_id) continue;

        $custom_prod_id = get_post_meta($product_id, 'custom_prod_id', true);
        $product = wc_get_product($product_id);
        $title = $product ? $product->get_name() : '';
        $province = sanitize_text_field($item['province']);
        $channel  = sanitize_text_field($item['channel']);

        $used_codes = $wpdb->get_col($wpdb->prepare(
            "SELECT barcode FROM $macao_table WHERE order_by_product_id = %d AND product_id = %s AND status = 'used'",
            $post_id, $custom_prod_id
        ));

        $qty_sell = count($used_codes);
        $barcode_text = implode("\n", $used_codes);

        if ($qty_sell > 0) {
            $existing_qty = $wpdb->get_var($wpdb->prepare(
                "SELECT quantity FROM $sellout_table WHERE order_id = %d AND title = %s AND province = %s AND channel = %s",
                $post_id, $title, $province, $channel
            ));

            if ($existing_qty != $qty_sell) {
                $wpdb->delete($sellout_table, [
                    'order_id' => $post_id,
                    'title'       => $title,
                    'province'    => $province,
                    'channel'     => $channel
                ]);

                $wpdb->insert($sellout_table, [
                    'order_id' => $post_id,
                    'title'       => $title,
                    'quantity'    => $qty_sell,
                    'barcode'      => $barcode_text,
                    'province'    => $province,
                    'channel'     => $channel
                ]);

                $log = sprintf("[%s] ✅ Cập nhật %d mã đã sử dụng [%s] (ID: %d) - #%d",
                    current_time('mysql'), $qty_sell, $title, $product_id, $post_id);
                $inventory_logs[] = $log;
            }
        }
    }

    if (isset($_POST['macao_ids']) && is_array($_POST['macao_ids'])) {
        $ids = array_map('intval', $_POST['macao_ids']);
        update_post_meta_if_changed($post_id, 'macao_ids', implode(',', $ids));
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
    add_meta_box('order_check_products_box', 'Danh sách sản phẩm', 'render_order_check_products_box', 'order_check', 'normal', 'high');
    add_meta_box('render_used_codes_box', 'Danh sách sản phẩm rớt kệ', 'render_order_used_codes_box', 'order_check', 'normal', 'high');
    add_meta_box('order_status_box', 'Trạng thái đơn hàng', 'render_order_status_box', 'order_check', 'side');
    add_meta_box('order_logs_box', 'Lịch sử trạng thái đơn', 'render_order_logs_box', 'order_check', 'side');

});

function render_order_status_box($post) {
    $current_status = get_post_meta($post->ID, 'order_status', true);
    $statuses = ['Mới', 'Xử lý', 'Đóng gói', 'Giao', 'Hoàn hàng'];

    echo '<select name="order_status">';
    foreach ($statuses as $status) {
        echo '<option value="' . esc_attr($status) . '" ' . selected($current_status, $status, false) . '>' . esc_html($status) . '</option>';
    }
    echo '</select>';
}

function render_order_logs_box($post) {
    $logs = get_post_meta($post->ID, 'order_status_logs', true);
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

function render_order_check_products_box($post) {
    $products = get_post_meta($post->ID, '_order_check_line_items', true);
    $all_products = wc_get_products(['limit' => -1]);

    echo '<table class="widefat" id="order_check_products_table" style="margin-bottom:10px;">';
    echo '<thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Mã cào</th><th>Tỉnh thành</th><th>Kênh</th></tr></thead><tbody>';

    if (!empty($products)) {
        foreach ($products as $index => $item) {
            $product_id = isset($item['product_id']) ? $item['product_id'] : '';
            $quantity   = isset($item['quantity']) ? $item['quantity'] : '';
            $barcode     = isset($item['barcode']) ? $item['barcode'] : '';
            $province   = isset($item['province']) ? $item['province'] : '';
            $channel    = isset($item['channel']) ? $item['channel'] : '';
            echo render_product_row($all_products, $product_id, $quantity, $barcode, $province, $channel, $index);
        }
    }

    echo '</tbody></table>';
    echo '<button type="button" class="button" id="add_product_row">+ Thêm sản phẩm</button>';

    echo '<script>
    let rowIndex = ' . (is_array($products) ? count($products) : 0) . ';

    document.getElementById("add_product_row").addEventListener("click", function() {
        let tableBody = document.querySelector("#order_check_products_table tbody");
        let row = document.createElement("tr");

        row.innerHTML = `' . str_replace(["\n", "'"], ["", "\\'"], render_product_row($all_products)) . '`.replace(/__index__/g, rowIndex);
        tableBody.appendChild(row);
        rowIndex++;
    });

    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-row")) {
            e.target.closest("tr").remove();
        }
    });
    </script>';
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
            echo '<td>' . esc_html($item['province']) . '</td>';
            echo '<td>' . esc_html($item['channel']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    ?>
    <?php endif; ?>
    <?php
}


function render_product_row($all_products, $product_id = '', $quantity = '', $barcode = '', $province = '', $channel = '', $index = '__index__') {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;
    $channel_rows = $wpdb->get_results("SELECT channel_code, title FROM $table ORDER BY id DESC");
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

    // $channels = [
    //     '' => 'Chọn kênh',
    //     'GT' => 'General Trade',
    //     'MT' => 'Modern Trade',
    //     'OL' => 'Online',
    // ];
    ob_start();
    ?>
    <tr>
        <td>
            <select name="order_check_products[<?php echo $index; ?>][product_id]" class="product-select" data-index="<?php echo $index; ?>">
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($all_products as $product): ?>
                    <?php 
                        $stock = $product->get_stock_quantity();
                        $label = $product->get_name() . ' (Tồn: ' . $stock . ')';
                    ?>
                    <option value="<?php echo esc_attr($product->get_id()); ?>" <?php selected($product_id, $product->get_id()); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>

        <td>
            <input type="number"
                class="barcode-quantity"
                data-index="<?php echo esc_attr($index); ?>"
                name="order_check_products[<?php echo $index; ?>][quantity]"
                value="<?php echo esc_attr($quantity); ?>"
                min="1" />
        </td>

        <td>
            <textarea 
                name="order_check_products[<?php echo $index; ?>][barcode]" 
                class="barcode-textarea"
                data-index="<?php echo esc_attr($index); ?>"
                rows="3"
                style="width: 100%;"
                placeholder="Nhập hoặc scan mã cào, mỗi dòng 1 mã"
            ><?php echo esc_textarea($barcode); ?></textarea>
            <small class="barcode-count" data-index="<?php echo esc_attr($index); ?>">Số lượng mã: 0</small>
        </td>
        <td>
            <select name="order_check_products[<?php echo $index; ?>][province]">
                <?php foreach ($provinces as $value => $label): ?>
                    <option value="<?php echo esc_attr($label); ?>" <?php selected($province, $label); ?>>
                        <?php echo esc_html($value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="order_check_products[<?php echo $index; ?>][channel]">
                <option value="">-- Chọn kênh --</option>
                <?php foreach ($channel_rows as $row): ?>
                    <option value="<?php echo esc_attr($row->channel_code); ?>" <?php selected($channel, $row->channel_code); ?>>
                        <?php echo esc_html($row->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><button type="button" class="button remove-row">X</button></td>
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



