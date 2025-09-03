<?php

function register_refund_check_post_type() {
    register_post_type('refund_check', array(
        'labels' => array(
            'name' => 'Đơn hoàn hàng',
            'singular_name' => 'Đơn hoàn hàng',
            'add_new' => 'Thêm đơn hàng hoàn',
            'add_new_item' => 'Thêm đơn hàng hoàn',
            'edit_item' => 'Chỉnh sửa đơn hàng hoàn',
            'new_item' => 'Thêm đơn hàng hoàn ',
            'view_item' => 'Xem mã định danh trong đơn hàng',
            'search_items' => 'Tìm đơn hàng hoàn',
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
    $refund_message = get_post_meta($post->ID, 'refund_message', true);
    

    $current_user = wp_get_current_user();
    $order_refund_by = $current_user->user_login;

    $order_refund_by_meta = get_post_meta($post->ID, 'order_refund_by', true);
    if (!empty($order_refund_by_meta)) {
        $order_refund_by = $order_refund_by_meta;
    }

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
        <label for="refund_message">Lí do hoàn hàng:</label>
        <input type="text" name="refund_message" id="refund_message"
            value="<?php echo esc_attr($refund_message); ?>"
            style="width:100%;">
    </div>
    <div class="form-group">
        <label for="order_refund_by">Người hoàn kho:</label>
        <input type="text" name="order_refund_by" id="order_refund_by" value="<?php echo esc_attr($order_refund_by); ?>" style="width:100%;" disabled>
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

function check_barcode_status_and_product($barcode) {
    global $wpdb;
    $macao_table = BIZGPT_PLUGIN_WP_BARCODE;
    
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $macao_table WHERE barcode = %s", $barcode));
    
    if (!$result) {
        return [
            'exists' => false,
            'status' => 'not_found',
            'message' => "❌ Không tìm thấy mã $barcode trong cơ sở dữ liệu"
        ];
    }
    
    // Chỉ chấp nhận mã có trạng thái "unused"
    if (isset($result->status) && $result->status !== 'unused') {
        return [
            'exists' => true,
            'status' => $result->status,
            'result' => $result,
            'message' => "❌ Mã $barcode có trạng thái 'Đã sử dụng' - Chỉ chấp nhận mã trạng thái 'Chưa sử dụng'"
        ];
    }
    
    $product_info = '';
    if (isset($result->product_id) && !empty($result->product_id)) {
        $products = get_posts([
            'post_type' => 'product',
            'numberposts' => 1,
            'meta_key' => 'custom_prod_id',
            'meta_value' => $result->product_id,
            'post_status' => 'any'
        ]);
        
        if (!empty($products)) {
            $product = $products[0];
            $product_info = $product->post_title;
        }
    }
    
    return [
        'exists' => true,
        'status' => 'unused',
        'result' => $result,
        'product_info' => $product_info,
        'message' => "✅ Mã $barcode thuộc sản phẩm: " . ($product_info ?: 'Chưa xác định')
    ];
}

// Hàm cộng tồn kho khi hoàn hàng
function increase_product_inventory_by_custom_id($product_custom_id, $quantity = 1) {
    if (empty($product_custom_id)) {
        return [
            'success' => false,
            'message' => "Custom ID rỗng, không thể cộng tồn kho"
        ];
    }

    // Tìm sản phẩm dựa vào custom_prod_id
    $products = get_posts([
        'post_type' => 'product',
        'numberposts' => 1,
        'meta_key' => 'custom_prod_id',
        'meta_value' => $product_custom_id,
        'post_status' => ['publish', 'draft', 'private']
    ]);
    
    error_log("DEBUG: Tìm thấy " . count($products) . " sản phẩm");
    
    if (empty($products)) {
        // Thử tìm với raw SQL nếu không tìm thấy chính xác
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = 'custom_prod_id' 
             AND pm.meta_value = %s 
             AND p.post_type = 'product'",
            $product_custom_id
        ));
        
        if (!$product_id) {
            return [
                'success' => false,
                'message' => "Không tìm thấy sản phẩm có mã: $product_custom_id"
            ];
        }
        
        $product = get_post($product_id);
    } else {
        $product = $products[0];
        $product_id = $product->ID;
    }
    
    $product_title = $product->post_title;
    
    $stock_fields = ['_stock', 'stock', '_stock_quantity', 'inventory'];
    $current_stock = 0;
    $stock_field_used = '';
    
    foreach ($stock_fields as $field) {
        $stock_value = get_post_meta($product_id, $field, true);
        if ($stock_value !== '' && $stock_value !== false) {
            $current_stock = intval($stock_value);
            $stock_field_used = $field;
            break;
        }
    }
        
    if (empty($stock_field_used)) {
        $stock_field_used = '_stock';
        $current_stock = 0;
    }
    
    $new_stock = $current_stock + $quantity;
    $update_result = update_post_meta($product_id, $stock_field_used, $new_stock);
    
    // Kiểm tra sau khi update
    $verify_stock = get_post_meta($product_id, $stock_field_used, true);
    
    return [
        'success' => true,
        'product_id' => $product_id,
        'product_title' => $product_title,
        'old_stock' => $current_stock,
        'new_stock' => $new_stock,
        'stock_field' => $stock_field_used,
        'verify_stock' => $verify_stock,
        'message' => "Đã cộng tồn kho sản phẩm '$product_title' từ $current_stock lên $new_stock (hoàn hàng)"
    ];
}

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

function display_refund_check_products_box($post) {
    $refund_check_products = get_post_meta($post->ID, '_refund_check_products', true);
    $validation_passed = get_post_meta($post->ID, '_validation_passed', true);

    if (!empty($refund_check_products)) {
        $refund_check_products = implode("\n", preg_split('/[\s,;]+/', $refund_check_products));
    }
    ?>
    <style>
        .refund-validation-success {
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 5px 0;
        }
        .refund-validation-error {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 12px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 5px 0;
        }
        .refund-validation-warning {
            background: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            margin: 5px 0;
        }
        #refund_validation_results {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
    
    <div class="form-group">
        <label for="refund_check_products">Nhập mã định danh sản phẩm. <b style="color: red;">Lưu ý: Mỗi mã định danh là 1 dòng. Chỉ chấp nhận mã có trạng thái 'unused'</b></label>
        <textarea name="refund_check_products" id="refund_check_products" rows="5" style="width:100%;"><?php echo esc_textarea($refund_check_products); ?></textarea>
        
        <div style="margin-top: 10px;">
            <button type="button" class="button button-primary" id="validate_refund_codes">🔍 Kiểm tra dữ liệu</button>
            <button type="button" class="button button-secondary" id="clear_refund_codes">🗑️ Xóa tất cả</button>
            <?php if ($validation_passed === 'passed'): ?>
                <span style="color: green; margin-left: 10px;">✅ Đã kiểm tra hợp lệ</span>
            <?php endif; ?>
        </div>
        
        <div id="refund_validation_results" style="margin-top: 15px;"></div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            let isValidationPassed = <?php echo $validation_passed === 'passed' ? 'true' : 'false'; ?>;
            
            // Hàm kiểm tra mã trùng lặp
            function checkDuplicateCodes(codesString) {
                if (!codesString.trim()) return { hasDuplicates: false, duplicates: [] };
                
                var codes = codesString.split(/[\r\n\s,;]+/)
                    .map(function(code) { return code.trim(); })
                    .filter(function(code) { return code !== ''; });
                
                var duplicates = [];
                var seen = {};
                
                codes.forEach(function(code) {
                    if (seen[code]) {
                        if (duplicates.indexOf(code) === -1) {
                            duplicates.push(code);
                        }
                    } else {
                        seen[code] = true;
                    }
                });
                
                return {
                    hasDuplicates: duplicates.length > 0,
                    duplicates: duplicates,
                    uniqueCodes: codes.filter(function(code, index) {
                        return codes.indexOf(code) === index;
                    }),
                    totalCodes: codes.length,
                    uniqueCount: Object.keys(seen).length
                };
            }
            
            // Kiểm tra dữ liệu
            $('#validate_refund_codes').click(function() {
                var codes = $('#refund_check_products').val().trim();
                if (!codes) {
                    $('#refund_validation_results').html('<div class="refund-validation-error">⚠️ Vui lòng nhập mã định danh trước khi kiểm tra.</div>');
                    return;
                }
                
                $(this).prop('disabled', true).text('🔄 Đang kiểm tra...');
                
                // Kiểm tra trùng lặp trước
                var duplicateCheck = checkDuplicateCodes(codes);
                var html = '';
                
                if (duplicateCheck.hasDuplicates) {
                    html += '<div class="refund-validation-error">';
                    html += '❌ <strong>Phát hiện mã trùng lặp:</strong><br>';
                    html += 'Các mã bị trùng: <code>' + duplicateCheck.duplicates.join(', ') + '</code><br>';
                    html += 'Tổng số mã: ' + duplicateCheck.totalCodes + ' | Mã duy nhất: ' + duplicateCheck.uniqueCount;
                    html += '</div>';
                    
                    $('#refund_validation_results').html(html);
                    isValidationPassed = false;
                    $(this).prop('disabled', false).text('🔍 Kiểm tra dữ liệu');
                    updatePublishButton();
                    return;
                }
                
                // Nếu không có trùng lặp, tiếp tục validate qua AJAX
                html += '<div class="refund-validation-success">✅ Không có mã trùng lặp (' + duplicateCheck.uniqueCount + ' mã duy nhất)</div>';
                
                // AJAX call để validate codes
                $.post(ajaxurl, {
                    action: 'validate_refund_codes',
                    codes: codes,
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('validate_refund_codes'); ?>'
                }, function(response) {
                    $('#validate_refund_codes').prop('disabled', false).text('🔍 Kiểm tra dữ liệu');
                    
                    if (response.success) {
                        var hasErrors = false;
                        var validCodes = 0;
                        
                        $.each(response.data.results, function(code, result) {
                            if (!result.exists) {
                                html += '<div class="refund-validation-error">❌ ' + result.message + '</div>';
                                hasErrors = true;
                            } else if (result.status !== 'unused') {
                                html += '<div class="refund-validation-error">❌ ' + result.message + '</div>';
                                hasErrors = true;
                            } else {
                                html += '<div class="refund-validation-success">✅ ' + result.message + '</div>';
                                validCodes++;
                            }
                        });
                        
                        if (!hasErrors && validCodes > 0) {
                            html += '<div class="refund-validation-success"><strong>🎉 Tất cả ' + validCodes + ' mã định danh đều hợp lệ và có trạng thái "Chưa sử dụng"!</strong></div>';
                            isValidationPassed = true;
                            
                            // Lưu trạng thái validation
                            $.post(ajaxurl, {
                                action: 'save_validation_status',
                                post_id: <?php echo $post->ID; ?>,
                                status: 'passed',
                                nonce: '<?php echo wp_create_nonce('save_validation_status'); ?>'
                            });
                        } else {
                            html += '<div class="refund-validation-error"><strong>❌ Có ' + (duplicateCheck.uniqueCount - validCodes) + ' mã không hợp lệ. Vui lòng sửa lại!</strong></div>';
                            isValidationPassed = false;
                        }
                        
                        updatePublishButton();
                        $('#refund_validation_results').html(html);
                    } else {
                        $('#refund_validation_results').html('<div class="refund-validation-error">❌ Lỗi khi kiểm tra: ' + (response.data || 'Unknown error') + '</div>');
                        isValidationPassed = false;
                        updatePublishButton();
                    }
                }).fail(function() {
                    $('#validate_refund_codes').prop('disabled', false).text('🔍 Kiểm tra dữ liệu');
                    $('#refund_validation_results').html('<div class="refund-validation-error">❌ Lỗi kết nối. Vui lòng thử lại!</div>');
                    isValidationPassed = false;
                    updatePublishButton();
                });
            });
            
            // Real-time duplicate check khi nhập
            $('#refund_check_products').on('input', function() {
                var codes = $(this).val();
                var duplicateCheck = checkDuplicateCodes(codes);
                
                isValidationPassed = false;
                
                // Reset validation status
                $.post(ajaxurl, {
                    action: 'save_validation_status',
                    post_id: <?php echo $post->ID; ?>,
                    status: '',
                    nonce: '<?php echo wp_create_nonce('save_validation_status'); ?>'
                });
                
                if (codes.trim() === '') {
                    $('#refund_validation_results').html('');
                } else if (duplicateCheck.hasDuplicates) {
                    var html = '<div class="refund-validation-error">';
                    html += '❌ <strong>Phát hiện mã trùng lặp:</strong> ';
                    html += '<code>' + duplicateCheck.duplicates.join(', ') + '</code><br>';
                    html += '<small>Tổng: ' + duplicateCheck.totalCodes + ' mã | Duy nhất: ' + duplicateCheck.uniqueCount + ' mã</small>';
                    html += '</div>';
                    $('#refund_validation_results').html(html);
                } else if (duplicateCheck.uniqueCount > 0) {
                    $('#refund_validation_results').html('<div class="refund-validation-warning">⚠️ ' + duplicateCheck.uniqueCount + ' mã duy nhất - Vui lòng bấm "Kiểm tra dữ liệu" để xác thực trạng thái mã.</div>');
                } else {
                    $('#refund_validation_results').html('<div class="refund-validation-warning">Vui lòng bấm "Kiểm tra dữ liệu" sau khi nhập xong.</div>');
                }
                
                updatePublishButton();
            });
            
            // Clear codes
            $('#clear_refund_codes').click(function() {
                if (confirm('Bạn có chắc muốn xóa tất cả mã định danh?')) {
                    $('#refund_check_products').val('');
                    $('#refund_validation_results').html('');
                    isValidationPassed = false;
                    
                    // Reset validation status
                    $.post(ajaxurl, {
                        action: 'save_validation_status',
                        post_id: <?php echo $post->ID; ?>,
                        status: '',
                        nonce: '<?php echo wp_create_nonce('save_validation_status'); ?>'
                    });
                    
                    updatePublishButton();
                }
            });
            
            // Update publish button state
            function updatePublishButton() {
                var publishButton = $('#publish, #save-post, input[name="publish"], input[name="save"]');
                var codes = $('#refund_check_products').val().trim();
                var duplicateCheck = checkDuplicateCodes(codes);
                
                if (codes && (!isValidationPassed || duplicateCheck.hasDuplicates)) {
                    publishButton.prop('disabled', true);
                    if (duplicateCheck.hasDuplicates) {
                        publishButton.val('❌ Có mã trùng lặp');
                    } else {
                        publishButton.val('⚠️ Cần kiểm tra dữ liệu');
                    }
                    publishButton.css({
                        'background-color': '#dc3545',
                        'border-color': '#dc3545',
                        'color': 'white'
                    });
                } else {
                    publishButton.prop('disabled', false);
                    publishButton.val(publishButton.data('original-value') || 'Cập nhật');
                    publishButton.css({
                        'background-color': '',
                        'border-color': '',
                        'color': ''
                    });
                }
            }
            
            // Store original button values
            $('#publish, #save-post, input[name="publish"], input[name="save"]').each(function() {
                $(this).data('original-value', $(this).val());
            });
            
            // Prevent form submission if validation not passed
            $('form#post').on('submit', function(e) {
                var codes = $('#refund_check_products').val().trim();
                var duplicateCheck = checkDuplicateCodes(codes);
                
                if (codes && (!isValidationPassed || duplicateCheck.hasDuplicates)) {
                    e.preventDefault();
                    if (duplicateCheck.hasDuplicates) {
                        alert('❌ Có mã trùng lặp: ' + duplicateCheck.duplicates.join(', ') + '\nVui lòng xóa các mã trùng lặp trước khi cập nhật!');
                    } else {
                        alert('❌ Vui lòng bấm "Kiểm tra dữ liệu" và đảm bảo tất cả mã có trạng thái "unused" trước khi lưu!');
                    }
                    return false;
                }
            });
            
            // Initial check
            updatePublishButton();
        });
    </script>
    <?php
}

function validate_refund_barcodes($codes_string) {
    $codes = preg_split('/[\r\n\s,;]+/', $codes_string);
    $codes = array_map('trim', $codes);
    $codes = array_filter($codes);
    
    $validation_results = [];
    $has_errors = false;
    
    foreach ($codes as $code) {
        $check = check_barcode_status_and_product($code);
        $validation_results[$code] = $check;
        
        // Chỉ chấp nhận mã có trạng thái 'unused'
        if (!$check['exists'] || $check['status'] !== 'unused') {
            $has_errors = true;
        }
    }
    
    return [
        'is_valid' => !$has_errors,
        'results' => $validation_results,
        'codes' => $codes
    ];
}

add_action('wp_ajax_validate_refund_codes', 'ajax_validate_refund_codes');
function ajax_validate_refund_codes() {
    check_ajax_referer('validate_refund_codes', 'nonce');
    
    $codes_string = sanitize_textarea_field($_POST['codes']);
    $validation = validate_refund_barcodes($codes_string);
    
    wp_send_json_success($validation);
}

add_action('wp_ajax_save_validation_status', 'ajax_save_validation_status');
function ajax_save_validation_status() {
    check_ajax_referer('save_validation_status', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $status = sanitize_text_field($_POST['status']);
    
    if ($post_id && current_user_can('edit_post', $post_id)) {
        update_post_meta($post_id, '_validation_passed', $status);
        wp_send_json_success();
    } else {
        wp_send_json_error('Không có quyền chỉnh sửa');
    }
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

add_action('admin_footer-post.php', 'disable_refund_update_after_processed');
add_action('admin_footer-post-new.php', 'disable_refund_update_after_processed');

function disable_refund_update_after_processed() {
    global $post;
    
    // Chỉ áp dụng cho post type 'refund_check'
    if (!$post || $post->post_type !== 'refund_check') {
        return;
    }
    
    // Kiểm tra xem đơn hoàn hàng đã được xử lý chưa
    $processed_codes = get_post_meta($post->ID, '_processed_codes', true);
    $is_processed = !empty($processed_codes) && is_array($processed_codes) && count($processed_codes) > 0;
    
    if ($is_processed) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Disable tất cả các button cập nhật/publish
            $('#publish, #save-post, input[name="publish"], input[name="save"]').prop('disabled', true)
                .css({
                    'background-color': '#ccc',
                    'border-color': '#999',
                    'color': '#666',
                    'cursor': 'not-allowed'
                })
                .val('✅ Đã xử lý - Không thể cập nhật');
            
            // Disable tất cả các input fields
            $('#order_id, #refund_date, #refund_message, #refund_check_products').prop('readonly', true)
                .css({
                    'background-color': '#f5f5f5',
                    'cursor': 'not-allowed'
                });
            
            // Disable nút chọn ảnh
            $('.upload_gallery_button').prop('disabled', true)
                .css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed'
                });
            
            // Disable select box trạng thái
            $('select[name="refund_status"]').prop('disabled', true)
                .css({
                    'background-color': '#f5f5f5',
                    'cursor': 'not-allowed'
                });
            
            // Disable các nút kiểm tra và xóa
            $('#validate_refund_codes, #clear_refund_codes').prop('disabled', true)
                .css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed'
                });
            
            // Hiển thị thông báo
            var notice = '<div class="notice notice-warning is-dismissible" style="margin-top: 20px;">' +
                        '<p><strong>⚠️ Lưu ý:</strong> Đơn hoàn hàng này đã được xử lý và không thể chỉnh sửa. ' +
                        'Đã hoàn ' + <?php echo json_encode(count($processed_codes)); ?> + ' mã định danh.</p>' +
                        '</div>';
            $('.wrap h1').after(notice);
            
            // Prevent form submission
            $('form#post').on('submit', function(e) {
                e.preventDefault();
                alert('❌ Đơn hoàn hàng đã được xử lý và không thể cập nhật!');
                return false;
            });
        });
        </script>
        <?php
    }
}

// Hook để ngăn chặn việc save post nếu đã xử lý (phòng trường hợp bypass JS)
add_action('pre_post_update', 'prevent_refund_update_if_processed', 10, 2);

function prevent_refund_update_if_processed($post_id, $data) {
    // Chỉ áp dụng cho post type 'refund_check'
    if (get_post_type($post_id) !== 'refund_check') {
        return;
    }
    
    // Kiểm tra xem đơn đã được xử lý chưa
    $processed_codes = get_post_meta($post_id, '_processed_codes', true);
    
    if (!empty($processed_codes) && is_array($processed_codes) && count($processed_codes) > 0) {
        // Kiểm tra nếu đang cố gắng update content hoặc meta
        if (isset($_POST['refund_check_products']) || isset($_POST['order_id'])) {
            wp_die(
                '❌ Đơn hoàn hàng này đã được xử lý và không thể cập nhật!<br>' .
                '<a href="' . get_edit_post_link($post_id) . '">← Quay lại</a>',
                'Không thể cập nhật',
                array('response' => 403)
            );
        }
    }
}

// Thêm cột trạng thái xử lý vào danh sách
add_filter('manage_refund_check_posts_columns', 'add_refund_processed_column');
function add_refund_processed_column($columns) {
    $columns['processed_status'] = 'Trạng thái xử lý';
    return $columns;
}

add_action('manage_refund_check_posts_custom_column', 'display_refund_processed_column', 10, 2);
function display_refund_processed_column($column, $post_id) {
    if ($column === 'processed_status') {
        $processed_codes = get_post_meta($post_id, '_processed_codes', true);
        
        if (!empty($processed_codes) && is_array($processed_codes)) {
            $count = count($processed_codes);
            echo '<span style="color: green; font-weight: bold;">✅ Đã xử lý (' . $count . ' mã)</span>';
        } else {
            echo '<span style="color: orange;">⏳ Chưa xử lý</span>';
        }
    }
}

// CSS cho danh sách trong admin
add_action('admin_head', 'refund_check_admin_styles');
function refund_check_admin_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'refund_check') {
        ?>
        <style>
            .column-processed_status { width: 120px; }
            .row-actions .trash a { color: #a00; }
            tr.processed td { background-color: #f9f9f9; }
        </style>
        <?php
    }
}

// Thêm class cho row đã xử lý
add_filter('post_class', 'add_processed_class_to_refund_rows', 10, 3);
function add_processed_class_to_refund_rows($classes, $class, $post_id) {
    if (get_post_type($post_id) === 'refund_check') {
        $processed_codes = get_post_meta($post_id, '_processed_codes', true);
        if (!empty($processed_codes) && is_array($processed_codes)) {
            $classes[] = 'processed';
        }
    }
    return $classes;
}

// Thêm thông báo vào metabox hiển thị mã định danh
add_action('add_meta_boxes', 'add_refund_processed_notice_box', 5);
function add_refund_processed_notice_box() {
    global $post;
    if ($post && $post->post_type === 'refund_check') {
        $processed_codes = get_post_meta($post->ID, '_processed_codes', true);
        if (!empty($processed_codes) && is_array($processed_codes)) {
            add_meta_box(
                'refund_processed_notice',
                '🔒 Trạng thái đơn hoàn hàng',
                'render_refund_processed_notice',
                'refund_check',
                'normal',
                'high'
            );
        }
    }
}

function render_refund_processed_notice($post) {
    $processed_codes = get_post_meta($post->ID, '_processed_codes', true);
    $refund_logs = get_post_meta($post->ID, '_refund_logs', true);
    
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #856404;">⚠️ Đơn hoàn hàng đã được xử lý</h3>
        <p><strong>Trạng thái:</strong> ✅ Đã hoàn <?php echo count($processed_codes); ?> mã định danh</p>
        <p><strong>Lưu ý:</strong> Đơn hoàn hàng này đã được xử lý và không thể chỉnh sửa để đảm bảo tính toàn vẹn dữ liệu.</p>
        
        <?php if (!empty($processed_codes)): ?>
            <details style="margin-top: 10px;">
                <summary style="cursor: pointer; font-weight: bold;">📋 Danh sách mã đã xử lý (<?php echo count($processed_codes); ?> mã)</summary>
                <div style="background: white; padding: 10px; margin-top: 5px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;">
                    <?php echo implode(', ', array_map('esc_html', $processed_codes)); ?>
                </div>
            </details>
        <?php endif; ?>
    </div>
    <?php
}
define('REFUND_STATUS_PENDING', 'cho_duyet');
define('REFUND_STATUS_COMPLETED', 'hoan_thanh');
define('ROLE_WAREHOUSE_STAFF', 'nhan_vien_kho');
define('ROLE_WAREHOUSE_MANAGER', 'quan_ly_kho');

/**
 * Get current user's warehouse role
 */
function get_user_warehouse_role() {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (in_array(ROLE_WAREHOUSE_MANAGER, $user_roles)) {
        return ROLE_WAREHOUSE_MANAGER;
    }
    
    if (in_array(ROLE_WAREHOUSE_STAFF, $user_roles)) {
        return ROLE_WAREHOUSE_STAFF;
    }
    
    return false;
}

/**
 * Get available statuses based on user role and current status
 */
function get_available_refund_statuses($current_status = '') {
    $user_role = get_user_warehouse_role();
    $statuses = [];
    
    switch ($user_role) {
        case ROLE_WAREHOUSE_STAFF:
            $statuses[REFUND_STATUS_PENDING] = 'Chờ Duyệt';
            break;
            
        case ROLE_WAREHOUSE_MANAGER:
            $statuses[REFUND_STATUS_PENDING] = 'Chờ Duyệt';
            $statuses[REFUND_STATUS_COMPLETED] = 'Hoàn thành';
            break;
            
        default:
            // Admin or other roles can see all statuses
            $statuses[REFUND_STATUS_PENDING] = 'Chờ Duyệt';
            $statuses[REFUND_STATUS_COMPLETED] = 'Hoàn thành';
            break;
    }
    
    return $statuses;
}

/**
 * Check if user can change status
 */
function can_user_change_refund_status($from_status, $to_status) {
    $user_role = get_user_warehouse_role();
    
    // Admin can always change
    if (current_user_can('manage_options')) {
        return true;
    }
    
    switch ($user_role) {
        case ROLE_WAREHOUSE_STAFF:
            // Staff can only set to pending
            return $to_status === REFUND_STATUS_PENDING;
            
        case ROLE_WAREHOUSE_MANAGER:
            // Manager can change from pending to completed or back to pending
            return true;
            
        default:
            return false;
    }
}

/**
 * Render optimized status box
 */
function render_refund_status_box($post) {
    $current_status = get_post_meta($post->ID, 'refund_status', true);
    $available_statuses = get_available_refund_statuses($current_status);
    $user_role = get_user_warehouse_role();
    
    // Set default status for new posts
    if (empty($current_status)) {
        $current_status = REFUND_STATUS_PENDING;
        update_post_meta($post->ID, 'refund_status', $current_status);
    }
    
    wp_nonce_field('refund_status_nonce', 'refund_status_nonce');
    
    echo '<div class="refund-status-container">';
    echo '<select name="refund_status" id="refund_status">';
    
    foreach ($available_statuses as $status_key => $status_label) {
        $selected = selected($current_status, $status_key, false);
        $disabled = !can_user_change_refund_status($current_status, $status_key) ? 'disabled' : '';
        echo "<option value='{$status_key}' {$selected} {$disabled}>{$status_label}</option>";
    }
    
    echo '</select>';
    
    // Show role info
    if ($user_role) {
        $role_labels = [
            ROLE_WAREHOUSE_STAFF => 'Nhân viên kho',
            ROLE_WAREHOUSE_MANAGER => 'Quản lý kho'
        ];
        echo '<p><small>Vai trò: ' . $role_labels[$user_role] . '</small></p>';
    } elseif (current_user_can('manage_options')) {
        echo '<p><small>Vai trò: Admin (Toàn quyền)</small></p>';
    }
    
    echo '</div>';
    
    // Add JavaScript for status change handling
    ?>
    <script>
    jQuery(document).ready(function($) {
        var originalStatus = '<?php echo $current_status; ?>';
        var userRole = '<?php echo $user_role ?: 'admin'; ?>';
        var isAdmin = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;
        
        $('#refund_status').change(function() {
            var newStatus = $(this).val();
            
            if (newStatus === '<?php echo REFUND_STATUS_COMPLETED; ?>' && (userRole === '<?php echo ROLE_WAREHOUSE_MANAGER; ?>' || isAdmin)) {
                if (!confirm('Bạn có chắc chắn muốn hoàn thành đơn hoàn hàng này?\nSau khi hoàn thành, dữ liệu sẽ được xử lý và không thể chỉnh sửa.')) {
                    $(this).val(originalStatus);
                    return false;
                }
            }
        });
    });
    </script>
    <?php
}

/**
 * Log status changes
 */
function log_refund_status_change($post_id, $old_status, $new_status) {
    $logs = get_post_meta($post_id, 'refund_status_logs', true);
    if (!is_array($logs)) {
        $logs = [];
    }
    
    $current_user = wp_get_current_user();
    $status_labels = [
        REFUND_STATUS_PENDING => 'Chờ Duyệt',
        REFUND_STATUS_COMPLETED => 'Hoàn thành'
    ];
    
    $log_entry = [
        'old_status' => $status_labels[$old_status] ?? $old_status,
        'new_status' => $status_labels[$new_status] ?? $new_status,
        'user' => $current_user->display_name,
        'user_role' => get_user_warehouse_role(),
        'timestamp' => current_time('mysql'),
        'message' => sprintf(
            'Chuyển từ "%s" sang "%s" bởi %s (%s)',
            $status_labels[$old_status] ?? $old_status,
            $status_labels[$new_status] ?? $new_status,
            $current_user->display_name,
            get_user_warehouse_role()
        )
    ];
    
    $logs[] = $log_entry;
    update_post_meta($post_id, 'refund_status_logs', $logs);
}

/**
 * Enhanced status logs display
 */
function render_refund_logs_box($post) {
    $logs = get_post_meta($post->ID, 'refund_status_logs', true);
    
    if (!is_array($logs) || empty($logs)) {
        echo '<p>Chưa có thay đổi trạng thái nào.</p>';
        return;
    }
    
    echo '<div class="refund-logs-container">';
    echo '<ul class="refund-status-timeline">';
    
    foreach (array_reverse($logs) as $log) {
        $timestamp = date('d/m/Y H:i', strtotime($log['timestamp']));
        echo '<li class="log-entry">';
        echo '<strong>' . esc_html($log['message']) . '</strong><br>';
        echo '<small>' . $timestamp . '</small>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    
    // Add CSS for timeline
    ?>
    <style>
    .refund-status-timeline {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .log-entry {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .log-entry:last-child {
        border-bottom: none;
    }
    .refund-status-container select {
        width: 100%;
        padding: 5px;
    }
    </style>
    <?php
}

/**
 * Enhanced save function with role-based processing
 */
function save_refund_check_fields($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'refund_check') return;

    // Save other fields first
    save_other_refund_fields($post_id);

    // Handle status change if nonce is present
    if (isset($_POST['refund_status_nonce']) && wp_verify_nonce($_POST['refund_status_nonce'], 'refund_status_nonce')) {
        // Get old status for comparison
        $old_status = get_post_meta($post_id, 'refund_status', true);
        $new_status = sanitize_text_field($_POST['refund_status'] ?? REFUND_STATUS_PENDING);
        
        // Set default status if empty
        if (empty($old_status)) {
            $old_status = REFUND_STATUS_PENDING;
            update_post_meta($post_id, 'refund_status', REFUND_STATUS_PENDING);
        }
        
        // Check if user can make this status change
        if (!can_user_change_refund_status($old_status, $new_status)) {
            return; // Silently fail instead of wp_die to avoid breaking the flow
        }
        
        // Update status if changed
        if ($old_status !== $new_status) {
            update_post_meta($post_id, 'refund_status', $new_status);
            log_refund_status_change($post_id, $old_status, $new_status);
            
            // Process data only when manager OR admin sets status to completed
            if ($new_status === REFUND_STATUS_COMPLETED && 
                (get_user_warehouse_role() === ROLE_WAREHOUSE_MANAGER || current_user_can('manage_options'))) {
                process_refund_completion($post_id);
            }
        }
    }
}

/**
 * Process refund completion (moved from main save function)
 */
function process_refund_completion($post_id) {
    global $wpdb;
    
    // Check if already processed to avoid duplicate processing
    $processed_codes = get_post_meta($post_id, '_processed_codes', true);
    if (!empty($processed_codes)) {
        return; // Already processed
    }
    
    $macao_table = BIZGPT_PLUGIN_WP_BARCODE;
    $refund_check_products = get_post_meta($post_id, '_refund_check_products', true);
    
    if (empty($refund_check_products)) {
        return;
    }
    
    // Validate codes before processing
    $validation_status = get_post_meta($post_id, '_validation_passed', true);
    if ($validation_status !== 'passed') {
        wp_die('Mã định danh chưa được kiểm tra hoặc không hợp lệ. Vui lòng kiểm tra lại!');
        return;
    }
    
    $codes = preg_split('/[\r\n\s,;]+/', $refund_check_products);
    $codes = array_map('trim', $codes);
    $codes = array_filter($codes);
    
    $logs = get_post_meta($post_id, '_refund_logs', true);
    if (!is_array($logs)) {
        $logs = [];
    }
    
    $processed_codes = [];
    
    foreach ($codes as $code) {
        if (empty($code)) continue;
        
        // Process each code
        $barcode_check = check_barcode_status_and_product($code);
        
        if (!$barcode_check['exists'] || $barcode_check['status'] !== 'unused') {
            $logs[] = [
                'status' => sprintf("[%s] ❌ %s", current_time('mysql'), $barcode_check['message']),
                'timestamp' => current_time('mysql')
            ];
            continue;
        }
        
        $result = $barcode_check['result'];
        
        // Update barcode status
        $wpdb->update(
            $macao_table,
            [
                'province' => '',
                'channel' => '',
                'distributor' => '',
                'status' => 'unused',
                'box_barcode' => ''
            ],
            ['barcode' => $code]
        );
        
        // Update inventory
        if (!empty($result->product_id)) {
            $inventory_result = increase_product_inventory_by_custom_id($result->product_id);
            if ($inventory_result['success']) {
                $logs[] = [
                    'status' => sprintf("[%s] ✅ %s", current_time('mysql'), $inventory_result['message']),
                    'timestamp' => current_time('mysql')
                ];
            } else {
                $logs[] = [
                    'status' => sprintf("[%s] ⚠️ %s", current_time('mysql'), $inventory_result['message']),
                    'timestamp' => current_time('mysql')
                ];
            }
        }
        
        $logs[] = [
            'status' => sprintf("[%s] ✅ Hoàn hàng mã %s - cộng tồn kho", current_time('mysql'), $code),
            'timestamp' => current_time('mysql')
        ];
        
        $processed_codes[] = $code;
    }
    
    // Save processing results
    update_post_meta($post_id, '_refund_logs', $logs);
    update_post_meta($post_id, '_processed_codes', $processed_codes);
    
    // Log completion
    log_refund_status_change($post_id, REFUND_STATUS_PENDING, REFUND_STATUS_COMPLETED);
}

/**
 * Save other refund fields (separated for clarity)
 */
function save_other_refund_fields($post_id) {
    // Chỉ xử lý nếu có dữ liệu POST
    if (empty($_POST)) return;
    
    $current_user = wp_get_current_user();
    $order_refund_by = $current_user->user_login;
    
    // Update other meta fields
    $fields_to_update = [
        'order_id' => sanitize_text_field($_POST['order_id'] ?? ''),
        'refund_images' => sanitize_text_field($_POST['refund_images'] ?? ''),
        'refund_date' => sanitize_text_field($_POST['refund_date'] ?? ''),
        'refund_message' => sanitize_text_field($_POST['refund_message'] ?? ''),
        'order_refund_by' => sanitize_text_field($_POST['order_refund_by'] ?? $order_refund_by)
    ];
    
    foreach ($fields_to_update as $key => $value) {
        if (isset($_POST[$key])) {
            refund_update_post_meta_if_changed($post_id, $key, $value);
        }
    }
    
    // Handle refund_check_products separately
    if (isset($_POST['refund_check_products'])) {
        $refund_check_products = sanitize_text_field($_POST['refund_check_products']);
        update_post_meta($post_id, '_refund_check_products', $refund_check_products);
    }
}

/**
 * Add status column to admin list
 */
add_filter('manage_refund_check_posts_columns', 'add_refund_status_columns');
function add_refund_status_columns($columns) {
    $columns['refund_status'] = 'Trạng thái';
    $columns['processed_status'] = 'Xử lý';
    return $columns;
}

add_action('manage_refund_check_posts_custom_column', 'display_refund_status_columns', 10, 2);
function display_refund_status_columns($column, $post_id) {
    switch ($column) {
        case 'refund_status':
            $status = get_post_meta($post_id, 'refund_status', true);
            $status_labels = [
                REFUND_STATUS_PENDING => '<span style="color: orange; font-weight: bold;">⏳ Chờ Duyệt</span>',
                REFUND_STATUS_COMPLETED => '<span style="color: green; font-weight: bold;">✅ Hoàn thành</span>'
            ];
            echo $status_labels[$status] ?? '<span style="color: gray;">❓ Không xác định</span>';
            break;
            
        case 'processed_status':
            $processed_codes = get_post_meta($post_id, '_processed_codes', true);
            if (!empty($processed_codes) && is_array($processed_codes)) {
                $count = count($processed_codes);
                echo '<span style="color: green; font-weight: bold;">✅ Đã xử lý (' . $count . ' mã)</span>';
            } else {
                echo '<span style="color: orange;">⏳ Chưa xử lý</span>';
            }
            break;
    }
}

// Update the original hook to use new function - MUST be after original hook removal
add_action('init', function() {
    // Remove any existing hooks to avoid conflicts
    remove_action('save_post', 'save_refund_check_fields');
    // Add our enhanced save function with higher priority
    add_action('save_post', 'save_refund_check_fields', 10, 2);
});

// Prevent editing completed refunds
add_action('admin_head-post.php', 'prevent_completed_refund_editing');
add_action('admin_head-post-new.php', 'prevent_completed_refund_editing');
function prevent_completed_refund_editing() {
    global $post;
    
    if (!$post || $post->post_type !== 'refund_check') {
        return;
    }
    
    $status = get_post_meta($post->ID, 'refund_status', true);
    $user_role = get_user_warehouse_role();
    
    // Only warehouse staff cannot edit completed refunds
    // Admin and warehouse manager can always edit
    if ($status === REFUND_STATUS_COMPLETED && 
        $user_role === ROLE_WAREHOUSE_STAFF && 
        !current_user_can('manage_options')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('form#post input, form#post textarea, form#post select').not('[name="refund_status"]').prop('disabled', true);
            $('#publish, #save-post').prop('disabled', true).val('🔒 Đã hoàn thành - Chỉ xem');
            $('.notice-warning').remove();
            $('.wrap h1').after('<div class="notice notice-info"><p><strong>ℹ️ Thông báo:</strong> Đơn hoàn hàng đã hoàn thành. Nhân viên kho chỉ có thể xem thông tin.</p></div>');
        });
        </script>
        <?php
    }
}

/**
 * Debug function to check if logs are being saved
 */
function debug_refund_logs($post_id) {
    if (!WP_DEBUG) return;
    
    $logs = get_post_meta($post_id, 'refund_status_logs', true);
    error_log('Refund Status Logs for Post ID ' . $post_id . ': ' . print_r($logs, true));
}

/**
 * Hook to register metaboxes - ensure this replaces existing ones
 */
add_action('add_meta_boxes', 'register_refund_status_metaboxes', 20);
function register_refund_status_metaboxes() {
    // Remove existing metabox if exists
    remove_meta_box('refund_status_box', 'refund_check', 'side');
    remove_meta_box('refund_logs_box', 'refund_check', 'side');
    
    // Add our enhanced metaboxes
    add_meta_box(
        'refund_status_box_enhanced', 
        'Trạng thái đơn hàng', 
        'render_refund_status_box', 
        'refund_check', 
        'side', 
        'high'
    );
    
    add_meta_box(
        'refund_logs_box_enhanced', 
        'Lịch sử trạng thái đơn', 
        'render_refund_logs_box', 
        'refund_check', 
        'side', 
        'default'
    );
}

/**
 * Add admin notice for debugging
 */
add_action('admin_notices', 'refund_debug_notices');
function refund_debug_notices() {
    global $post;
    
    if (!$post || $post->post_type !== 'refund_check' || !WP_DEBUG) {
        return;
    }
    
    $user_role = get_user_warehouse_role();
    $status = get_post_meta($post->ID, 'refund_status', true);
    
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Debug Info:</strong> Current Role: ' . ($user_role ?: 'None') . ' | Status: ' . ($status ?: 'Empty') . '</p>';
    echo '</div>';
}