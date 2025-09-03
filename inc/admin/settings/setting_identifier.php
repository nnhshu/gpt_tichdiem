<?php

function gpt_setting_identifier_page() {

    $provinces = [
        'An Giang' => 'AG',
        'Bắc Ninh' => 'BN',
        'Cà Mau' => 'CM',
        'Cao Bằng' => 'CB',
        'Đắk Lắk' => 'DL',
        'Điện Biên' => 'DB',
        'Đồng Nai' => 'DG',
        'Đồng Tháp' => 'DT',
        'Gia Lai' => 'GL',
        'Hà Tĩnh' => 'HT',
        'Hưng Yên' => 'HY',
        'Khánh Hoà' => 'KH',
        'Lai Châu' => 'LC',
        'Lâm Đồng' => 'LD',
        'Lạng Sơn' => 'LS',
        'Lào Cai' => 'LA',
        'Nghệ An' => 'NA',
        'Ninh Bình' => 'NB',
        'Phú Thọ' => 'PT',
        'Quảng Ngãi' => 'QG',
        'Quảng Ninh' => 'QN',
        'Quảng Trị' => 'QT',
        'Sơn La' => 'SL',
        'Tây Ninh' => 'TN',
        'Thái Nguyên' => 'TG',
        'Thanh Hóa' => 'TH',
        'TP. Cần Thơ' => 'CT',
        'TP. Đà Nẵng' => 'DN',
        'TP. Hà Nội' => 'HN',
        'TP. Hải Phòng' => 'HP',
        'TP. Hồ Chí Minh' => 'SG',
        'TP. Huế' => 'HUE',
        'Tuyên Quang' => 'TQ',
        'Vĩnh Long' => 'VL'
    ];

    $current_session = get_option('gpt_current_session', 0);
    //box barcode session
    $current_box_session = get_option('gpt_current_box_session', 0);

    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    // Enqueue Select2
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), null, true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), null, 'all');

    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_CHANNELS;
    $channel_rows = $wpdb->get_results("SELECT channel_code, title FROM $table ORDER BY id DESC");

    ?>
    <div class="tab-content">
        <h1>Tạo mã định danh: Thùng & Sản phẩm</h1>
        <hr>
        <div class="gpt_form_wrap">
            <style>
                .select2-container .select2-selection--single {
                    height: 50px !important;
                }

                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    line-height: 50px!important;
                }

                .select2-container--default .select2-selection--single .select2-selection__arrow {
                    transform: translateY(-50%) !important;
                    top: 50% !important;
                }
                
                .cron-info {
                    background: #e7f3ff;
                    border-left: 4px solid #0073aa;
                    padding: 10px;
                    margin: 10px 0;
                }
                
                .job-link {
                    display: inline-block;
                    margin-top: 10px;
                    padding: 5px 10px;
                    background: #0073aa;
                    color: white;
                    text-decoration: none;
                    border-radius: 3px;
                }
                
                .job-link:hover {
                    background: #005a87;
                    color: white;
                }
            </style>
            <div class="bg-grey">
                <form id="gpt-create-box-form">
                    <h2>1. Tạo mã định danh cho thùng</h2>
                    <div style="padding: 10px;border-left: 4px solid #0073aa;background: #fff;">
                        <span>Vui lòng chọn đầy đủ thông tin phía dưới để tạo mã chuẩn nhất. Dấu <span style="color:red">*</span> là bắt buộc</span>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label for="gpt_box_quantity">Nhập số lượng mã muốn tạo: <span style="color:red">*</span></label>
                        <input type="number" id="gpt_box_quantity" min="1" value="10" class="regular-text" required>
                    </div>
                    <div class="form-group">
                        <label for="gpt_box_session">Số phiên hiện tại (Tự động):</label>
                        <input type="text" id="gpt_box_session" value="<?php echo esc_attr(str_pad($current_box_session, 2, '0', STR_PAD_LEFT)); ?>" class="regular-text" readonly>
                    </div>
                    <hr>
                    <div class="btn_wrap_list">
                        <button type="button" id="gpt_box_start_generate" class="button-primary">🚀 Tạo Cron Job</button>
                        <a href="<?php echo admin_url('edit.php?post_type=code_gen_cron'); ?>" class="button button-secondary" target="_blank">📊 Xem Jobs</a>
                    </div>
                </form>
                <div id="gpt_box_result" style="margin-top:15px;"></div>
                <div class="cron-info" style="margin-top:15px;">
                    <span><strong>Lưu ý:</strong> Hệ thống cron sẽ tự động xử lý tạo mã theo batch để đảm bảo hiệu suất server tốt nhất.</span>
                </div>
            </div>
            
            <div class="bg-grey">
                <form id="gpt-create-code-form">
                    <h2>2. Tạo mã định danh hàng loạt cho sản phẩm</h2>
                    <div class="cron-info">
                        <span><strong>Lưu ý:</strong> Hệ thống cron sẽ tự động xử lý tạo mã theo batch để đảm bảo hiệu suất server tốt nhất.</span>
                    </div>
                    <hr>
                    <div class="form-group" style="margin-top: 16px; margin-bottom: 16px;">
                        <label for="gpt_diem">Số điểm quy đổi tương ứng của mã: <span style="color:red">*</span></label>
                        <select id="gpt_diem" class="regular-text" required>
                            <option value="">-- Chọn số điểm tương ứng --</option>
                            <option value="1">1 điểm</option>
                            <option value="2">2 điểm</option>
                        </select>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label for="gpt_product_id">Chọn sản phẩm (ID của sản phẩm): <span style="color:red">*</span></label>
                        <select id="gpt_product_id" class="regular-text"  style="width:100%;" disabled>
                            <option value="">-- Vui lòng chọn số điểm trước --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gpt_quantity">Nhập số lượng mã muốn tạo: <span style="color:red">*</span></label>
                        <input type="number" id="gpt_quantity" min="1" value="10" class="regular-text" required>
                        <p class="description">Số lượng lớn sẽ được chia thành nhiều batch nhỏ</p>
                    </div>
                    <div class="form-group">
                        <label for="gpt_session">Số phiên hiện tại (Tự động):</label>
                        <input type="text" id="gpt_session" value="<?php echo esc_attr(str_pad($current_session, 2, '0', STR_PAD_LEFT)); ?>" class="regular-text" readonly>
                    </div>
                    <!-- <div class="form-group">
                        <label for="gpt_batch_size">Batch Size (mã/lần):</label>
                        <select id="gpt_batch_size" class="regular-text">
                            <option value="25">25 mã/lần (Chậm, An toàn)</option>
                            <option value="50" selected>50 mã/lần (Cân bằng)</option>
                            <option value="100">100 mã/lần (Nhanh, Có thể lag)</option>
                        </select>
                    </div> -->
                    <!-- <div class="form-group">
                        <label for="gpt_interval">Khoảng cách giữa các batch (giây):</label>
                        <select id="gpt_interval" class="regular-text">
                            <option value="15">15 giây (Nhanh)</option>
                            <option value="30" selected>30 giây (Cân bằng)</option>
                            <option value="60">60 giây (Chậm, An toàn)</option>
                        </select>
                    </div> -->
                    <div class="btn_wrap_list">
                        <button type="button" id="gpt_start_generate" class="button-primary">🚀 Tạo Cron Job</button>
                        <a href="<?php echo admin_url('edit.php?post_type=code_gen_cron'); ?>" class="button button-secondary" target="_blank">📊 Xem Jobs</a>
                    </div>
                </form>
                <div id="gpt_result" style="margin-top:15px;"></div>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button id="btn_reset_session" class="button button-secondary">
                🔁 Reset Session về 00
            </button>
            <a href="<?php echo admin_url('edit.php?post_type=code_gen_cron'); ?>" class="button button-primary" style="margin-left: 10px;">
                📊 Quản lý Cron Jobs
            </a>
            <a href="<?php echo admin_url('options-general.php?page=code-gen-settings'); ?>" class="button button-secondary" style="margin-left: 10px;">
                ⚙️ Cài đặt thông báo
            </a>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
            let box_total = 0, box_batchSize = 100, box_created = 0, box_isCancelled = false;

            if (typeof $('#gpt_product_id').select2 === 'function') {
                $('#gpt_product_id').select2({
                    placeholder: 'Chọn sản phẩm',
                    allowClear: true,
                    width: '100%'
                });
            }

            $('#gpt_diem').on('change', function() {
                var selectedPoints = $(this).val();
                var productSelect = $('#gpt_product_id');

                if (selectedPoints) {
                    productSelect.prop('disabled', false);
                    productSelect.empty().append('<option value="">-- Chọn sản phẩm --</option>');

                    var hasProducts = false;
                    <?php foreach ($products as $product) {
                        $custom_prod_id = get_post_meta($product->ID, 'custom_prod_id', true);
                        $reward_points = get_post_meta($product->ID, '_reward_points', true);
                        if (!$custom_prod_id) continue;
                        $reward_points = $reward_points ? $reward_points : '0';
                        ?>
                        if (selectedPoints == '<?php echo esc_js($reward_points); ?>') {
                            productSelect.append('<option value="<?php echo esc_attr($custom_prod_id); ?>" data-points="<?php echo esc_attr($reward_points); ?>"><?php echo esc_js($product->post_title . ' - (ID:' . $custom_prod_id . ') - ' . $reward_points . ' điểm'); ?></option>');
                            hasProducts = true;
                        }
                    <?php } ?>

                    if (!hasProducts) {
                        productSelect.append('<option value="">-- Không có sản phẩm nào có ' + selectedPoints + ' điểm --</option>');
                    }

                    productSelect.select2('destroy').select2({
                        placeholder: 'Chọn sản phẩm (' + selectedPoints + ' điểm)',
                        allowClear: true,
                        width: '100%'
                    });
                } else {
                    productSelect.prop('disabled', true);
                    productSelect.empty().append('<option value="">-- Vui lòng chọn số điểm trước --</option>');

                    productSelect.select2('destroy').select2({
                        placeholder: 'Vui lòng chọn số điểm trước',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });

            // Product Code Generation via Cron
            $('#gpt_start_generate').on('click', function() {
                let point = $('#gpt_diem').val();
                let quantity = $('#gpt_quantity').val();
                let productId = $('#gpt_product_id').val();
                let batchSize = 100;
                let interval = 30;

                // Validation
                if (!point || (point != '1' && point != '2')) {
                    alert('Vui lòng chọn số điểm hợp lệ!');
                    $('#gpt_diem').focus();
                    return;
                }
                if (!quantity || quantity <= 0) {
                    alert('Vui lòng nhập số lượng hợp lệ!');
                    $('#gpt_quantity').focus();
                    return;
                }
                if (!productId || productId.length !== 2) {
                    alert('Vui lòng chọn sản phẩm!');
                    $('#gpt_product_id').focus();
                    return;
                }

                // Disable button và hiển thị loading
                $(this).prop('disabled', true).text('🔄 Đang tạo cron job...');
                
                // Update session trước
                $.post(ajaxurl, { 
                    action: 'gpt_update_session' 
                }, function(res) {
                    if (res.success) {
                        let newSession = res.data.new_session;
                        $('#gpt_session').val(newSession);
                        
                        // Tạo cron job
                        $.post(ajaxurl, {
                            action: 'create_product_code_cron_job',
                            point: point,
                            product_id: productId,
                            session: newSession,
                            total_quantity: quantity,
                            batch_size: batchSize,
                            interval: interval
                        }, function(response) {
                            if (response.success) {
                                let jobId = response.data.job_id;
                                let jobUrl = response.data.job_url;
                                
                                $('#gpt_result').html(`
                                    <div class="notice notice-success inline">
                                        <p><strong>✅ Đã tạo cron job thành công!</strong></p>
                                        <p>Job ID: #${jobId}</p>
                                        <p>Số lượng: ${quantity} mã</p>
                                        <p>Batch size: ${batchSize} mã/lần</p>
                                        <p>Interval: ${interval} giây</p>
                                        <a href="${jobUrl}" class="job-link" target="_blank">📊 Xem tiến độ job</a>
                                        <a href="<?php echo admin_url('edit.php?post_type=code_gen_cron'); ?>" class="job-link" target="_blank" style="margin-left: 10px;">📋 Tất cả jobs</a>
                                    </div>
                                `);
                                
                                // Thông báo qua notification nếu có
                                if (response.data.auto_started) {
                                    setTimeout(() => {
                                        $('#gpt_result').append('<div class="notice notice-info inline"><p>🚀 Cron job đã tự động bắt đầu chạy!</p></div>');
                                    }, 1000);
                                }
                                
                            } else {
                                $('#gpt_result').html('<div class="notice notice-error inline"><p>❌ Lỗi: ' + (response.data || 'Không thể tạo cron job') + '</p></div>');
                            }
                        }).fail(function() {
                            $('#gpt_result').html('<div class="notice notice-error inline"><p>❌ Lỗi: Không thể kết nối tới server.</p></div>');
                        }).always(function() {
                            $('#gpt_start_generate').prop('disabled', false).text('🚀 Tạo Cron Job');
                        });
                        
                    } else {
                        alert('Lỗi cập nhật phiên. Vui lòng thử lại.');
                        $('#gpt_start_generate').prop('disabled', false).text('🚀 Tạo Cron Job');
                    }
                });
            });

            // Box Code Generation via Cron
            $('#gpt_box_start_generate').on('click', function() {
                let quantity = $('#gpt_box_quantity').val();
                let batchSize = 100;
                let interval = 30;

                // Validation
                if (!quantity || quantity <= 0) {
                    alert('Vui lòng nhập số lượng hợp lệ!');
                    $('#gpt_box_quantity').focus();
                    return;
                }

                // Disable button và hiển thị loading
                $(this).prop('disabled', true).text('🔄 Đang tạo cron job...');
                
                // Update session and start generation
                $.post(ajaxurl, { 
                    action: 'gpt_update_box_session' 
                }, function(res) {
                    if (res.success) {
                        let newSession = res.data.new_session;
                        $('#gpt_box_session').val(newSession);
                        
                        // Tạo cron job cho box codes
                        $.post(ajaxurl, {
                            action: 'create_box_code_cron_job',
                            session: newSession,
                            total_quantity: quantity,
                            batch_size: batchSize,
                            interval: interval
                        }, function(response) {
                            if (response.success) {
                                let jobId = response.data.job_id;
                                let jobUrl = response.data.job_url;
                                
                                $('#gpt_box_result').html(`
                                    <div class="notice notice-success inline">
                                        <p><strong>✅ Đã tạo cron job thành công!</strong></p>
                                        <p>Job ID: #${jobId}</p>
                                        <p>Số lượng: ${quantity} mã</p>
                                        <p>Batch size: ${batchSize} mã/lần</p>
                                        <p>Interval: ${interval} giây</p>
                                        <a href="${jobUrl}" class="job-link" target="_blank">📊 Xem tiến độ job</a>
                                        <a href="<?php echo admin_url('edit.php?post_type=code_gen_cron'); ?>" class="job-link" target="_blank" style="margin-left: 10px;">📋 Tất cả jobs</a>
                                    </div>
                                `);
                                
                                // Thông báo qua notification nếu có
                                if (response.data.auto_started) {
                                    setTimeout(() => {
                                        $('#gpt_box_result').append('<div class="notice notice-info inline"><p>🚀 Cron job đã tự động bắt đầu chạy!</p></div>');
                                    }, 1000);
                                }
                                
                            } else {
                                $('#gpt_box_result').html('<div class="notice notice-error inline"><p>❌ Lỗi: ' + (response.data || 'Không thể tạo cron job') + '</p></div>');
                            }
                        }).fail(function() {
                            $('#gpt_box_result').html('<div class="notice notice-error inline"><p>❌ Lỗi: Không thể kết nối tới server.</p></div>');
                        }).always(function() {
                            $('#gpt_box_start_generate').prop('disabled', false).text('🚀 Tạo Cron Job');
                        });
                        
                    } else {
                        alert('Lỗi cập nhật phiên. Vui lòng thử lại.');
                        $('#gpt_box_start_generate').prop('disabled', false).text('🚀 Tạo Cron Job');
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    jQuery(document).ready(function ($) {
        $('#btn_reset_session').on('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Bạn chắc chắn muốn reset?',
                text: "Hành động này sẽ đưa session về 00 và xóa toàn bộ session đã dùng.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(ajaxurl, {
                        action: 'gpt_reset_session'
                    }, function (res) {
                        if (res.success) {
                            Swal.fire('Đã reset!', res.data.message, 'success');
                            // Reload để cập nhật session numbers
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire('Lỗi!', 'Không thể reset session.', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX Actions để tạo cron jobs
add_action('wp_ajax_create_product_code_cron_job', 'create_product_code_cron_job_ajax');
function create_product_code_cron_job_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $point = sanitize_text_field($_POST['point']);
    $product_id = sanitize_text_field($_POST['product_id']);
    $session = sanitize_text_field($_POST['session']);
    $total_quantity = intval($_POST['total_quantity']);
    $batch_size = intval($_POST['batch_size']);
    $interval = intval($_POST['interval']);
    
    // Validation
    if (!in_array($point, ['1', '2']) || !$product_id || !$session || $total_quantity <= 0) {
        wp_send_json_error('Invalid parameters');
    }
    
    // Tạo cron job
    $job_id = wp_insert_post(array(
        'post_type' => 'code_gen_cron',
        'post_title' => "Product Code Generation - {$total_quantity} codes - " . current_time('Y-m-d H:i:s'),
        'post_status' => 'publish'
    ));
    
    if ($job_id) {
        // Lưu metadata
        update_post_meta($job_id, 'job_type', 'product_code');
        update_post_meta($job_id, 'total_quantity', $total_quantity);
        update_post_meta($job_id, 'batch_size', $batch_size);
        update_post_meta($job_id, 'interval', $interval);
        update_post_meta($job_id, 'point', $point);
        update_post_meta($job_id, 'product_id', $product_id);
        update_post_meta($job_id, 'session', $session);
        update_post_meta($job_id, 'cron_status', 'pending');
        update_post_meta($job_id, 'codes_created', 0);
        
        // Auto start job
        $hook_name = 'code_gen_cron_' . $job_id;
        $schedule = $interval <= 30 ? 'every_30_seconds' : 'every_minute';
        
        wp_schedule_event(time(), $schedule, $hook_name, array($job_id));
        
        update_post_meta($job_id, 'cron_status', 'running');
        update_post_meta($job_id, 'start_time', current_time('mysql'));
        
        wp_send_json_success(array(
            'job_id' => $job_id,
            'job_url' => get_edit_post_link($job_id),
            'auto_started' => true,
            'message' => 'Cron job created and started successfully'
        ));
    } else {
        wp_send_json_error('Failed to create cron job');
    }
}

add_action('wp_ajax_create_box_code_cron_job', 'create_box_code_cron_job_ajax');
function create_box_code_cron_job_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $session = sanitize_text_field($_POST['session']);
    $total_quantity = intval($_POST['total_quantity']);
    $batch_size = intval($_POST['batch_size']);
    $interval = intval($_POST['interval']) ?: 30;
    
    if (!$session || $total_quantity <= 0) {
        wp_send_json_error('Invalid parameters');
    }
    
    // Tạo cron job cho box codes
    $job_id = wp_insert_post(array(
        'post_type' => 'code_gen_cron',
        'post_title' => "Box Code Generation - {$total_quantity} codes - " . current_time('Y-m-d H:i:s'),
        'post_status' => 'publish'
    ));
    
    if ($job_id) {
        update_post_meta($job_id, 'job_type', 'box_code');
        update_post_meta($job_id, 'total_quantity', $total_quantity);
        update_post_meta($job_id, 'batch_size', $batch_size);
        update_post_meta($job_id, 'interval', $interval);
        update_post_meta($job_id, 'session', $session);
        update_post_meta($job_id, 'cron_status', 'pending');
        update_post_meta($job_id, 'codes_created', 0);
        
        // Auto start
        $hook_name = 'code_gen_cron_' . $job_id;
        wp_schedule_event(time(), 'every_30_seconds', $hook_name, array($job_id));
        
        update_post_meta($job_id, 'cron_status', 'running');
        update_post_meta($job_id, 'start_time', current_time('mysql'));
        
        wp_send_json_success(array(
            'job_id' => $job_id,
            'job_url' => get_edit_post_link($job_id),
            'auto_started' => true,
            'message' => 'Box code cron job created and started successfully'
        ));
    } else {
        wp_send_json_error('Failed to create cron job');
    }
}

add_action('wp_ajax_gpt_reset_session', function () {
    update_option('gpt_current_session', 0);
    update_option('gpt_current_box_session', 0);
    update_option('gpt_used_sessions', []);
    wp_send_json_success(['message' => 'Session đã được reset về 00.']);
});