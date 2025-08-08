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
                        <button type="button" id="gpt_box_start_generate" class="button-primary">Bắt đầu tạo mã</button>
                        <button type="button" id="gpt_box_cancel_generate" class="button-primary" style="display:none; margin-left:10px;">Hủy tiến trình</button>
                    </div>
                </form>
                <div id="gpt_box_progress_wrap" style="margin-top:20px; display:none;">
                    <div style="background: #f1f1f1; height: 20px; border-radius: 10px; overflow: hidden;">
                        <div id="gpt_box_progress_bar" style="width:0%; height: 100%; background: linear-gradient(90deg,rgba(3, 219, 238, 1) 0%, rgba(69, 165, 246, 1) 50%, rgba(124, 119, 254, 1) 100%); transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <div id="gpt_box_result" style="margin-top:15px;"></div>
                <div id="gpt_box_log" style="margin-top:10px; font-size:14px; font-weight: 700;"></div>
            </div>
            <div class="bg-grey">
                <form id="gpt-create-code-form">
                    <h2>2. Tạo mã định danh hàng loạt cho sản phẩm</h2>
                    <div style="padding: 10px;border-left: 4px solid #0073aa;background: #fff;">
                        <!-- <span><strong>Lưu ý:</strong> Mã cào sẽ có dạng như sau: <strong>(Kênh)_(Tỉnh Thành)_(ID Sản Phẩm)_(Điểm Từng Mã)_(Phiên)_(4 Ký Tự Ngẫu Nhiên)</strong></span> -->
                        <span>Vui lòng chọn đầy đủ thông tin phía dưới để tạo mã chuẩn nhất. Dấu <span style="color:red">*</span> là bắt buộc</span>
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
                    <!-- <div class="form-group" style="margin-top:16px;">
                        <label for="gpt_channel">Chọn kênh: </label>
                        <select id="gpt_channel" class="regular-text" >
                             <option value="">Chọn kênh</option>
                            <?php foreach ($channel_rows as $row): ?>
                                <option value="<?php echo esc_attr($row->channel_code); ?>">
                                    <?php echo esc_html($row->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div> -->
                    <!-- <div class="form-group">
                        <label for="gpt_province">Chọn tỉnh thành: <span style="color:red">*</span></label>
                        <select id="gpt_province" class="regular-text" required>
                            <option value="">-- Chọn tỉnh thành --</option>
                            <?php foreach ($provinces as $province => $short) { ?>
                                <option value="<?php echo esc_attr($short); ?>"><?php echo esc_html($province); ?></option>
                            <?php } ?>
                        </select>
                    </div> -->
                    <div class="form-group">
                        <label for="gpt_product_id">Chọn sản phẩm (ID của sản phẩm): <span style="color:red">*</span></label>
                        <select id="gpt_product_id" class="regular-text"  style="width:100%;" disabled>
                            <option value="">-- Vui lòng chọn số điểm trước --</option>
                            <!-- <?php foreach ($products as $product) {
                                $custom_prod_id = get_post_meta($product->ID, 'custom_prod_id', true);
                                $reward_points = get_post_meta($product->ID, '_reward_points', true);
                                if (!$custom_prod_id) continue;
                                $reward_points = $reward_points ? $reward_points : '0';
                                ?>
                                 <option value="<?php echo esc_attr($custom_prod_id); ?>" 
                                    data-points="<?php echo esc_attr($reward_points); ?>">
                                <?php echo esc_html($product->post_title . ' - (ID:' . $custom_prod_id . ') - ' . $reward_points . ' điểm'); ?>
                            </option>
                            <?php } ?> -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gpt_quantity">Nhập số lượng mã muốn tạo: <span style="color:red">*</span></label>
                        <input type="number" id="gpt_quantity" min="1" value="10" class="regular-text" required>
                    </div>
                    <div class="form-group">
                        <label for="gpt_session">Số phiên hiện tại (Tự động):</label>
                        <input type="text" id="gpt_session" value="<?php echo esc_attr(str_pad($current_session, 2, '0', STR_PAD_LEFT)); ?>" class="regular-text" readonly>
                    </div>
                    <div class="btn_wrap_list">
                        <button type="button" id="gpt_start_generate" class="button-primary">Bắt đầu tạo mã</button>
                        <button type="button" id="gpt_cancel_generate" class="button-primary" style="display:none; margin-left:10px;">Hủy tiến trình</button>
                    </div>
                </form>
                <div id="gpt_progress_wrap" style="margin-top:20px; display:none;">
                    <div style="background: #f1f1f1; height: 20px; border-radius: 10px; overflow: hidden;">
                        <div id="gpt_progress_bar" style="width:0%; height: 100%; background: linear-gradient(90deg, rgb(3, 219, 238) 0%, rgb(69, 165, 246) 50%, rgb(124, 119, 254) 100%); transition: width 0.3s;"></div>
                    </div>
                </div>
                <div id="gpt_result" style="margin-top:15px;"></div>
                <div id="gpt_log" style="margin-top:10px; font-size:14px; font-weight: 700;"></div>
            </div>
        </div>
        <!-- <div style="margin-top: 20px;">
            <button id="btn_reset_session" class="button button-secondary">
                🔁 Reset Session về 00
            </button>
        </div> -->
    </div>
    <script>
        jQuery(document).ready(function($) {
            let total = 0, batchSize = 100, created = 0, isCancelled = false;
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
                    // Enable dropdown sản phẩm
                    productSelect.prop('disabled', false);

                    // Reset và thêm option mặc định
                    productSelect.empty().append('<option value="">-- Chọn sản phẩm --</option>');

                    // Lọc và thêm các sản phẩm có điểm phù hợp
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

                    // Nếu không có sản phẩm nào phù hợp
                    if (!hasProducts) {
                        productSelect.append('<option value="">-- Không có sản phẩm nào có ' + selectedPoints + ' điểm --</option>');
                    }

                    // Reinitialize Select2 với placeholder mới
                    productSelect.select2('destroy').select2({
                        placeholder: 'Chọn sản phẩm (' + selectedPoints + ' điểm)',
                        allowClear: true,
                        width: '100%'
                    });
                } else {
                    // Disable dropdown sản phẩm khi chưa chọn điểm
                    productSelect.prop('disabled', true);
                    productSelect.empty().append('<option value="">-- Vui lòng chọn số điểm trước --</option>');

                    // Reinitialize Select2
                    productSelect.select2('destroy').select2({
                        placeholder: 'Vui lòng chọn số điểm trước',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });

            $('#gpt_start_generate').on('click', function() {
                let point = $('#gpt_diem').val();
                let quantity = $('#gpt_quantity').val();
                let productId = $('#gpt_product_id').val();
                let session = $('#gpt_session').val();
                // let channel = $('#gpt_channel').val();

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
                // if (!channel) {
                //     alert('Vui lòng chọn kênh');
                //     $('#gpt_channel').focus();
                //     return;
                // }
                if (!productId || productId.length !== 2) {
                    alert('Vui lòng chọn sản phẩm!');
                    $('#gpt_product_id').focus();
                    return;
                }
                
                let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
                // Update session and start generation
                $.post(ajaxurl, { 
                    action: 'gpt_update_session' 
                }, function(res) {
                    if (res.success) {
                        let newSession = res.data.new_session;
                        $('#gpt_session').val(newSession);
                        
                        total = parseInt(quantity);
                        created = 0;
                        isCancelled = false;

                        $('#gpt_progress_wrap').show();
                        $('#gpt_progress_bar').css('width', '0%');
                        $('#gpt_result').html('<div class="notice notice-info inline"><p>Đang tạo mã...</p></div>');
                        $('#gpt_log').html('Đã tạo: 0 mã');
                        $('#gpt_cancel_generate').show();
                        $('#gpt_start_generate').prop('disabled', true);

                        createBatch(point, productId, newSession);
                        
                    } else {
                        alert('Lỗi cập nhật phiên. Vui lòng thử lại.');
                    }
                });
            });

            $('#gpt_cancel_generate').on('click', function() {
                isCancelled = true;
                $('#gpt_result').html('<div class="notice notice-warning inline"><p>Tiến trình đã bị hủy.</p></div>');
                $('#gpt_cancel_generate').hide();
                $('#gpt_start_generate').prop('disabled', false);
            });

            function createBatch(point, productId, session) {
                if (isCancelled) return;

                let currentBatchSize = Math.min(batchSize, total - created);
                let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
                $.post(ajaxurl, {
                    action: 'gpt_create_code_batch',
                    point: point,
                    product_id: productId,
                    session: session,
                    batch_size: currentBatchSize
                }, function(response) {
                    if (response.status === 'success') {
                        created += currentBatchSize;

                        let percent = Math.min((created / total) * 100, 100);
                        $('#gpt_progress_bar').css('width', percent + '%');
                        $('#gpt_log').html('Đã tạo: ' + created + '/' + total + ' mã (' + Math.round(percent) + '%)');

                        if (created < total) {
                            createBatch(point, province, productId, session);
                        } else {
                            $('#gpt_result').html('<div class="notice notice-success inline"><p>Tạo mã hoàn tất! Đã tạo ' + created + ' mã thành công.</p></div>');
                            $('#gpt_cancel_generate').hide();
                            $('#gpt_start_generate').prop('disabled', false);
                        }
                    } else {
                        $('#gpt_result').html('<div class="notice notice-error inline"><p>Lỗi: ' + (response.message || 'Không thể tạo mã') + '</p></div>');
                        $('#gpt_cancel_generate').hide();
                        $('#gpt_start_generate').prop('disabled', false);
                    }
                }).fail(function() {
                    $('#gpt_result').html('<div class="notice notice-error inline"><p>Lỗi: Không thể kết nối tới server.</p></div>');
                    $('#gpt_cancel_generate').hide();
                    $('#gpt_start_generate').prop('disabled', false);
                });
            }

            // Create barcode box

            $('#gpt_box_start_generate').on('click', function() {
                let quantity = $('#gpt_box_quantity').val();
                let session = $('#gpt_box_session').val();

                // Validation
                if (!quantity || quantity <= 0) {
                    alert('Vui lòng nhập số lượng hợp lệ!');
                    $('#gpt_box_quantity').focus();
                    return;
                }
                let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
                // Update session and start generation
                $.post(ajaxurl, { 
                    action: 'gpt_update_box_session' 
                }, function(res) {
                    if (res.success) {
                        let newSession = res.data.new_session;
                        $('#gpt_box_session').val(newSession);
                        
                        box_total = parseInt(quantity);
                        created = 0;
                        isCancelled = false;

                        $('#gpt_box_progress_wrap').show();
                        $('#gpt_box_progress_bar').css('width', '0%');
                        $('#gpt_box_result').html('<div class="notice notice-info inline"><p>Đang tạo mã...</p></div>');
                        $('#gpt_box_log').html('Đã tạo: 0 mã');
                        $('#gpt_box_cancel_generate').show();
                        $('#gpt_box_start_generate').prop('disabled', true);

                        createBoxBatch(newSession);
                        
                    } else {
                        alert('Lỗi cập nhật phiên. Vui lòng thử lại.');
                    }
                });
            });

            function createBoxBatch(session) {
                if (isCancelled) return;

                let currentBatchSize = Math.min(box_batchSize, box_total - box_created);
                let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
                $.post(ajaxurl, {
                    action: 'gpt_create_box_code_batch',
                    session: session,
                    batch_size: currentBatchSize
                }, function(response) {
                    if (response.status === 'success') {
                        box_created += currentBatchSize;

                        let percent = Math.min((box_created / box_total) * 100, 100);
                        $('#gpt_box_progress_bar').css('width', percent + '%');
                        $('#gpt_box_log').html('Đã tạo: ' + box_created + '/' + box_total + ' mã (' + Math.round(percent) + '%)');

                        if (box_created < box_total) {
                            createBatch(session);
                        } else {
                            $('#gpt_box_result').html('<div class="notice notice-success inline"><p>Tạo mã hoàn tất! Đã tạo ' + box_created + ' mã thành công.</p></div>');
                            $('#gpt_box_cancel_generate').hide();
                            $('#gpt_box_start_generate').prop('disabled', false);
                        }
                    } else {
                        $('#gpt_box_result').html('<div class="notice notice-error inline"><p>Lỗi: ' + (response.message || 'Không thể tạo mã') + '</p></div>');
                        $('#gpt_box_cancel_generate').hide();
                        $('#gpt_box_start_generate').prop('disabled', false);
                    }
                }).fail(function() {
                    $('#gpt_box_result').html('<div class="notice notice-error inline"><p>Lỗi: Không thể kết nối tới server.</p></div>');
                    $('#gpt_box_cancel_generate').hide();
                    $('#gpt_box_start_generate').prop('disabled', false);
                });
            }
        });
    </script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                        } else {
                            Swal.fire('Lỗi!', 'Không thể reset session.', 'error');
                        }
                    });
                }
            });
        });
    });
    </script> -->
    <?php
}