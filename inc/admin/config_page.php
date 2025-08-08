<?php

add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['custom_prod_id'])) {
        $custom_prod_id = sanitize_text_field($_POST['custom_prod_id']);
        update_post_meta($post_id, 'custom_prod_id', $custom_prod_id);
    }
});

function gpt_render_editor_field() {
    $content = get_option('gpt_error_notice_editor', '');
    wp_editor($content, 'gpt_error_notice_editor', [
        'textarea_name' => 'gpt_error_notice_editor',
        'media_buttons' => false,
        'textarea_rows' => 5
    ]);
}

function gpt_config_page() {
    if (isset($_POST['gpt_branch'])) {
        update_option('gpt_branch', sanitize_text_field($_POST['gpt_branch']));
        echo '<div class="notice notice-success is-dismissible"><p>Lưu cấu hình thành công!</p></div>';
    }

    if (isset($_POST['submit_affiliate_config']) && wp_verify_nonce($_POST['affiliate_config_nonce'], 'save_affiliate_config')) {
                
        if (isset($_POST['affiliate_enabled'])) {
            update_option('affiliate_enabled', 1);
        } else {
            update_option('affiliate_enabled', 0);
        }
        
        if (isset($_POST['affiliate_points_per_referral'])) {
            $points = intval($_POST['affiliate_points_per_referral']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_points_per_referral', $points);
        }

        if (isset($_POST['affiliate_percent_per_referral'])) {
            $points = intval($_POST['affiliate_percent_per_referral']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_percent_per_referral', $points);
        }

        if (isset($_POST['affiliate_percent_per_new_user'])) {
            $points = intval($_POST['affiliate_percent_per_new_user']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_percent_per_new_user', $points);
        }
        
        if (isset($_POST['affiliate_min_points_required'])) {
            $min_points = intval($_POST['affiliate_min_points_required']);
            $min_points = max(0, min(100, $min_points));
            update_option('affiliate_min_points_required', $min_points);
        }
        
        if (isset($_POST['affiliate_notification_message'])) {
            $message = sanitize_textarea_field($_POST['affiliate_notification_message']);
            update_option('affiliate_notification_message', $message);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Thành công!</strong> Đã lưu cấu hình Affiliate.</p>';
            echo '</div>';
        });
        
    }

    $branch = get_option('gpt_branch', '');

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
    $current_session = get_option('gpt_current_box_session', 0);

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

    <div class="wrap">
        <h1>GPT Mã cào tích điểm - Cấu hình chung</h1>
        <div class="gpt_form_wrap">
            <!-- <div class="gpt-tich-diem-form">
                <h2>Cấu hình chi nhánh</h2>
                <p>Điền tên chi nhánh để cấu hình tên chi nhánh trong cơ sở dữ liệu</p>
                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gpt_branch">Tên chi nhánh hiện tại:</label>
                            </th>
                            <td>
                                <input type="text" id="gpt_branch" name="gpt_branch" value="<?php echo esc_attr($branch); ?>" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Lưu cấu hình'); ?>
                </form>
            </div> -->

            <div class="gpt-tich-diem-form">
                <h2>1. Cấu hình tích điểm và affiliate</h2>
                <div class="affiliate-status" style="padding: 10px; border-left: 4px solid #0073aa; background: #fff;">
                    <strong>Trạng thái hiện tại:</strong>
                    <?php if (get_option('affiliate_enabled', 0)): ?>
                        <span style="color: #00a32a; font-weight: bold;">✅ ĐANG HOẠT ĐỘNG</span>
                        <br><small>Người giới thiệu sẽ nhận <strong><?php echo get_option('affiliate_points_per_referral', 10); ?> điểm</strong> mỗi lần có người tích điểm thành công.</small>
                    <?php else: ?>
                        <span style="color: #d63638; font-weight: bold;">❌ TẮT</span>
                        <br><small>Chức năng affiliate hiện đang bị tắt.</small>
                    <?php endif; ?>
                </div>
                <hr>
                <form method="post" action="">
                    <?php wp_nonce_field('save_affiliate_config', 'affiliate_config_nonce'); ?>
                    <h3>Chức năng Affiliate</h3>
                    <div class="form-group">
                        <label for="affiliate_enabled">
                            <input type="checkbox" id="affiliate_enabled" name="affiliate_enabled" value="1" 
                                <?php checked(get_option('affiliate_enabled', 0), 1); ?>>
                            Bật chức năng người giới thiệu
                        </label>
                        <p class="description">Cho phép khách hàng nhận điểm khi giới thiệu người khác tích điểm</p>
                    </div>
                    <div class="form-group">
                        <label for="affiliate_points_per_referral">Điểm thưởng mỗi lần giới thiệu</label>
                        <input type="number" id="affiliate_points_per_referral" name="affiliate_points_per_referral" 
                            value="<?php echo esc_attr(get_option('affiliate_points_per_referral', 10)); ?>" 
                            min="0" max="1000" class="regular-text">
                        <p class="description">Số điểm người giới thiệu nhận được mỗi khi có người tích điểm thành công</p>
                    </div>
                    <div class="form-group">
                        <label for="affiliate_percent_per_referral">% điểm thưởng người giới thiệu nhận được</label>
                        <input type="number" id="affiliate_percent_per_referral" name="affiliate_percent_per_referral" 
                            value="<?php echo esc_attr(get_option('affiliate_percent_per_referral', 10)); ?>" 
                            min="0" max="1000" class="regular-text">
                        <p class="description">% điểm người giới thiệu nhận được khi có người đầu tiên tích điểm thành công</p>
                    </div>
                    <div class="form-group">
                        <label for="affiliate_min_points_required">Điểm tối thiểu để nhận thưởng</label>
                        <input type="number" id="affiliate_min_points_required" name="affiliate_min_points_required" 
                            value="<?php echo esc_attr(get_option('affiliate_min_points_required', 1)); ?>" 
                            min="0" max="100" class="regular-text">
                        <p class="description">Người được giới thiệu phải tích ít nhất bao nhiêu điểm thì người giới thiệu mới nhận thưởng</p>
                    </div>
                    <div class="form-group">
                        <label for="affiliate_percent_per_new_user">% điểm thưởng khách hàng lần đầu tiên tích điểm nhận được</label>
                        <input type="number" id="affiliate_percent_per_new_user" name="affiliate_percent_per_new_user" 
                            value="<?php echo esc_attr(get_option('affiliate_percent_per_new_user', 10)); ?>" 
                            min="0" max="1000" class="regular-text">
                        <p class="description">% điểm khách hàng tích điểm lần đầu tiên thành công</p>
                    </div>
                    <div class="form-group">
                        <label for="affiliate_notification_message">Tin nhắn thông báo</label>
                        <textarea id="affiliate_notification_message" name="affiliate_notification_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('affiliate_notification_message', '🎉 Chúc mừng! Bạn vừa nhận được {points} điểm từ việc giới thiệu {customer_name} tích điểm. Tổng điểm hiện tại: {total_points}')); ?></textarea>
                        <p class="description">Tin nhắn gửi cho người giới thiệu. Sử dụng: <code>{points}</code>, <code>{customer_name}</code>, <code>{total_points}</code></p>
                    </div>
                    <div class="affiliate-config-actions">
                        <div class="submit">
                            <input type="submit" name="submit_affiliate_config" class="button-primary" value="💾 Lưu cấu hình Affiliate">
                        </div>
                    </div>
                    <hr>
                    <div class="affiliate-config-actions">
                        <label for="">Chức năng khác</label>
                        <div class="submit">
                            <input type="button" class="button-primary" value="🔄 Reset về mặc định" onclick="resetAffiliateConfig()">
                            <input type="button" class="button-primary" value="📊 Xem báo cáo" onclick="window.open('<?php echo admin_url('admin.php?page=gpt-affiliate-report'); ?>', '_blank')">
                        </div>
                    </div>
                </form>
                <script>
                    function resetAffiliateConfig() {
                        if (confirm('Bạn có chắc muốn reset về cấu hình mặc định không?')) {
                            document.getElementById('affiliate_enabled').checked = false;
                            document.getElementById('affiliate_points_per_referral').value = '10';
                            document.getElementById('affiliate_min_points_required').value = '1';
                            document.getElementById('affiliate_notification_message').value = '🎉 Chúc mừng! Bạn vừa nhận được {points} điểm từ việc giới thiệu {customer_name} tích điểm. Tổng điểm hiện tại: {total_points}';
                            highlightChangedFields();
                        }
                    }

                    function highlightChangedFields() {
                        const fields = ['affiliate_points_per_referral', 'affiliate_min_points_required', 'affiliate_notification_message'];
                        
                        fields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.style.backgroundColor = '#fff3cd';
                                field.style.border = '2px solid #ffc107';
                                setTimeout(() => {
                                    field.style.backgroundColor = '';
                                    field.style.border = '';
                                }, 3000);
                            }
                        });
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        const pointsInput = document.getElementById('affiliate_points_per_referral');
                        const minPointsInput = document.getElementById('affiliate_min_points_required');
                        const enabledCheckbox = document.getElementById('affiliate_enabled');

                        if (pointsInput) {
                            pointsInput.addEventListener('input', function() {
                                const value = parseInt(this.value);
                                if (value < 0) this.value = 0;
                                if (value > 1000) this.value = 1000;
                                updatePreview();
                            });
                        }
                        
                        if (minPointsInput) {
                            minPointsInput.addEventListener('input', function() {
                                const value = parseInt(this.value);
                                if (value < 0) this.value = 0;
                                if (value > 100) this.value = 100;
                            });
                        }
                        
                        if (enabledCheckbox) {
                            enabledCheckbox.addEventListener('change', function() {
                                updatePreview();
                            });
                        }
                        
                        function updatePreview() {
                            const isEnabled = enabledCheckbox.checked;
                            const points = pointsInput.value || 10;
                            
                            const statusDiv = document.querySelector('.affiliate-status');
                            if (statusDiv) {
                                if (isEnabled) {
                                    statusDiv.innerHTML = `
                                        <strong>Trạng thái hiện tại:</strong>
                                        <span style="color: #00a32a; font-weight: bold;">✅ ĐANG HOẠT ĐỘNG</span>
                                        <br><small>Người giới thiệu sẽ nhận <strong>${points} điểm</strong> mỗi lần có người tích điểm thành công.</small>
                                    `;
                                } else {
                                    statusDiv.innerHTML = `
                                        <strong>Trạng thái hiện tại:</strong>
                                        <span style="color: #d63638; font-weight: bold;">❌ TẮT</span>
                                        <br><small>Chức năng affiliate hiện đang bị tắt.</small>
                                    `;
                                }
                            }
                        }
                    });
                </script>
            </div>
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
            <div class="gpt-tich-diem-form">
                <form id="gpt-create-box-form">
                    <h2>2. Tạo mã barcode cho thùng</h2>
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
                        <input type="text" id="gpt_box_session" value="<?php echo esc_attr(str_pad($current_session, 2, '0', STR_PAD_LEFT)); ?>" class="regular-text" readonly>
                    </div>
                    <div class="btn_wrap_list">
                        <button type="button" id="gpt_box_start_generate" class="button-primary">Bắt đầu tạo barcode</button>
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
            <div class="gpt-tich-diem-form">
                <form id="gpt-create-code-form">
                    <h2>2. Tạo mã cào hàng loạt</h2>
                    <div style="padding: 10px;border-left: 4px solid #0073aa;background: #fff;">
                        <span><strong>Lưu ý:</strong> Mã cào sẽ có dạng như sau: <strong>(Kênh)_(Tỉnh Thành)_(ID Sản Phẩm)_(Điểm Từng Mã)_(Phiên)_(4 Ký Tự Ngẫu Nhiên)</strong></span>
                        <br>
                        <span>Vui lòng chọn đầy đủ thông tin phía dưới để tạo mã chuẩn nhất. Dấu <span style="color:red">*</span> là bắt buộc</span>
                    </div>
                    <hr>
                    <div class="form-group" style="margin-top: 16px; margin-bottom: 16px;">
                        <label for="gpt_diem">Số điểm quy đổi tương ứng của mã: <span style="color:red">*</span></label>
                        <select id="gpt_diem" class="regular-text" required>
                            <option value="1">1 điểm</option>
                            <option value="2">2 điểm</option>
                        </select>
                    </div>
                    <hr>
                    <div class="form-group" style="margin-top:16px;">
                        <label for="gpt_channel">Chọn kênh: </label>
                        <select id="gpt_channel" class="regular-text" >
                            <?php foreach ($channel_rows as $row): ?>
                                <option value="<?php echo esc_attr($row->channel_code); ?>" <?php selected($branch, $row->channel_code); ?>>
                                    <?php echo esc_html($row->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                        <label for="gpt_product_id">Chọn sản phẩm (ID của sản phẩm): </label>
                        <select id="gpt_product_id" class="regular-text"  style="width:100%;">
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($products as $product) {
                                $custom_prod_id = get_post_meta($product->ID, 'custom_prod_id', true);
                                if (!$custom_prod_id) continue;
                                ?>
                                <option value="<?php echo esc_attr($custom_prod_id); ?>">
                                    <?php echo esc_html($product->post_title . ' - (ID:' . $custom_prod_id . ')'); ?>
                                </option>
                            <?php } ?>
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
                        <div id="gpt_progress_bar" style="width:0%; height: 100%; background: #0073aa; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <div id="gpt_result" style="margin-top:15px;"></div>
                <div id="gpt_log" style="margin-top:10px; font-size:14px; font-weight: 700;"></div>
            </div>
            <div class="gpt-tich-diem-form">
                <?php 
                if (function_exists('bizgpt_user_points_guide_display')) {
                    bizgpt_user_points_guide_display();
                } else {
                    echo '<p>Function bizgpt_user_points_guide_display() chưa được định nghĩa.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- <div style="margin-top: 20px;">
        <button id="btn_reset_session" class="button button-secondary">
            🔁 Reset Session về 00
        </button>
    </div> -->

    <script>
        jQuery(document).ready(function($) {
            let total = 0, batchSize = 100, created = 0, isCancelled = false;
            let box_total = 0, box_batchSize = 100, box_created = 0, box_isCancelled = false;

            // Initialize Select2
            $('#gpt_product_id').select2({
                placeholder: 'Chọn sản phẩm',
                allowClear: true,
                width: '100%'
            });

            $('#gpt_start_generate').on('click', function() {
                let point = $('#gpt_diem').val();
                let quantity = $('#gpt_quantity').val();
                let productId = $('#gpt_product_id').val();
                let session = $('#gpt_session').val();
                let channel = $('#gpt_channel').val();

                // Validation
                if (!quantity || quantity <= 0) {
                    alert('Vui lòng nhập số lượng hợp lệ!');
                    $('#gpt_quantity').focus();
                    return;
                }
                if (!channel) {
                    alert('Vui lòng chọn kênh');
                    $('#gpt_channel').focus();
                    return;
                }
                // if (!productId || productId.length !== 2) {
                //     alert('Vui lòng chọn sản phẩm có ID đủ 2 ký tự!');
                //     $('#gpt_product_id').focus();
                //     return;
                // }
                if (!point || (point != '1' && point != '2')) {
                    alert('Vui lòng chọn số điểm hợp lệ!');
                    $('#gpt_diem').focus();
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

                        createBatch(channel, point, productId, newSession);
                        
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

            function createBatch(channel, point, productId, session) {
                if (isCancelled) return;

                let currentBatchSize = Math.min(batchSize, total - created);
                let ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
                $.post(ajaxurl, {
                    action: 'gpt_create_code_batch',
                    channel : channel,
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
                            createBatch(channel, point, province, productId, session);
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

// add_action('wp_ajax_gpt_update_session', function() {
//     $current_session = get_option('gpt_current_session', 0);
//     $new_session = $current_session + 1;
//     update_option('gpt_current_session', $new_session);

//     wp_send_json_success(['new_session' => str_pad($new_session, 2, '0', STR_PAD_LEFT)]);
// });

// add_action('wp_ajax_gpt_reset_session', function () {
//     update_option('gpt_current_session', 0);
//     update_option('gpt_current_box_session', 0);
//     update_option('gpt_used_sessions', []);
//     wp_send_json_success(['message' => 'Session đã được reset về 00.']);
// });

add_action('wp_ajax_gpt_update_session', function() {
    $used_sessions = get_option('gpt_used_sessions', []);
    $current_session = get_option('gpt_current_session', 0);

    if ($current_session < 99) {
        $new_session = $current_session + 1;
        update_option('gpt_current_session', $new_session);
        $session_str = str_pad($new_session, 2, '0', STR_PAD_LEFT);
    } else {
        $letters = range('A', 'Z');
        $numbers = range(1, 9);
        $found = false;

        foreach ($letters as $letter) {
            foreach ($numbers as $num) {
                $candidate = $letter . $num;
                if (!in_array($candidate, $used_sessions)) {
                    $session_str = $candidate;
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) {
            wp_send_json_error(['message' => 'Đã hết session khả dụng.']);
        }
    }

    $used_sessions[] = $session_str;
    update_option('gpt_used_sessions', array_unique($used_sessions));

    wp_send_json_success(['new_session' => $session_str]);
});

add_action('wp_ajax_gpt_create_code_batch', 'gpt_create_code_batch');
add_action('wp_ajax_gpt_create_box_code_batch', 'gpt_create_box_code_batch');


function generate_token_4_chars() {
    $first_digits = '123456789';
    
    $other_digits = '0123456789';
    
    $token = $first_digits[rand(0, strlen($first_digits) - 1)];
    
    for ($i = 1; $i < 4; $i++) {
        $token .= $other_digits[rand(0, strlen($other_digits) - 1)];
    }
    
    return $token;
}

function generate_unique_token() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_BARCODE;
    $column_name = 'token';
    do {
        $token = generate_token_4_chars();
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE $column_name = %s",
            $token
        ));
    } while ($exists > 0);
    return $token;
}

function gpt_create_code_batch() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BARCODE;

    $point = intval($_POST['point']);
    $product_id = sanitize_text_field($_POST['product_id']);
    $session = sanitize_text_field($_POST['session']);
    $batch_size = intval($_POST['batch_size']);
    $branch = get_option('gpt_branch', '');
    // $channel = sanitize_text_field($_POST['channel']);

    $generated_codes = [];
    $count = 0;
    $max_attempts = $batch_size * 10;
    $attempts = 0;
    $allowed_chars = 'ACDEFHJKLMNPQRTUVWXY3479';

    while ($count < $batch_size && $attempts < $max_attempts) {
        $attempts++;

        $random_string = '';
        for ($i = 0; $i < 4; $i++) {
            $random_string .= $allowed_chars[rand(0, strlen($allowed_chars) - 1)];
        }
        if(!empty($product_id)){
            $random_code = "{$product_id}{$session}{$point}{$random_string}";
            $random_code_check = "{$product_id}_{$point}_{$session}_{$random_string}";
        } else{
            $random_code = "{$session}{$point}{$random_string}";
            $random_code_check = "{$point}_{$session}_{$random_string}";
        }
        
        // $qr_url = 'https://bimbosan.superhub.vn/tich-diem-ma-cao/?barcode=' . urlencode($random_code);
        $qr_url = home_url('/tich-diem-ma-cao/?barcode=' . urlencode($random_code));
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=1024x1024&data=' . urlencode($qr_url);
        $barcode_url = 'https://bwipjs-api.metafloor.com/?bcid=code39&text=' . urlencode($random_code) . '&includetext&scale=5';
        // $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($random_code) . '&code=Code39&translate-esc=true';

        $token = generate_unique_token();

        if (in_array(strtolower($random_code), array_map('strtolower', $generated_codes))) continue;

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE barcode = %s", $random_code));
        if ($exists > 0) continue;

        $generated_codes[] = $random_code;

        $inserted = $wpdb->insert($table, [
            'barcode' => $random_code,
            'barcode_check' => $random_code_check,
            'token' => $token,
            'point' => $point,
            'status' => 'pending',
            'province' => "",
            'channel' => "",
            'product_id' => !empty($product_id) ? $product_id : '',
            'session' => $session,
            'qr_code_url' => $qr_code_url,
            'barcode_url' => $barcode_url,
            'created_at' => current_time('mysql'),
        ]);

        if ($inserted === false) {
            wp_send_json(['status' => 'error', 'message' => 'Lỗi ghi dữ liệu vào database.']);
        }

        $count++;
    }

    if ($attempts >= $max_attempts) {
        wp_send_json(['status' => 'error', 'message' => 'Không thể tạo đủ mã trong batch.']);
    }

    wp_send_json(['status' => 'success']);
}

// Barcode của thùng

add_action('wp_ajax_gpt_update_box_session', function() {
    $used_sessions = get_option('gpt_used_box_sessions', []);
    $current_session = get_option('gpt_current_box_session', 0);

    if ($current_session < 99) {
        $new_session = $current_session + 1;
        update_option('gpt_current_box_session', $new_session);
        $session_str = str_pad($new_session, 2, '0', STR_PAD_LEFT);
    } else {
        $letters = range('A', 'Z');
        $numbers = range(1, 9);
        $found = false;

        foreach ($letters as $letter) {
            foreach ($numbers as $num) {
                $candidate = $letter . $num;
                if (!in_array($candidate, $used_sessions)) {
                    $session_str = $candidate;
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) {
            wp_send_json_error(['message' => 'Đã hết session khả dụng.']);
        }
    }

    $used_sessions[] = $session_str;
    update_option('gpt_used_box_sessions', array_unique($used_sessions));

    wp_send_json_success(['new_session' => $session_str]);
});

function gpt_create_box_code_batch() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    $session = sanitize_text_field($_POST['session']);
    $batch_size = intval($_POST['batch_size']);

    $generated_codes = [];
    $count = 0;
    $max_attempts = $batch_size * 10;
    $attempts = 0;
    $allowed_chars = 'ACDEFHJKLMNPQRTUVWXY3479';

    while ($count < $batch_size && $attempts < $max_attempts) {
        $attempts++;

        $random_string = '';
        for ($i = 0; $i < 4; $i++) {
            $random_string .= $allowed_chars[rand(0, strlen($allowed_chars) - 1)];
        }
        $random_code = "{$session}{$random_string}";
        $random_code_check = "{$session}_{$random_string}";
        
        $qr_url = home_url('/tra-cuu/?box_barcode=' . urlencode($random_code));
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=1024x1024&data=' . urlencode($qr_url);
        $barcode_url = 'https://bwipjs-api.metafloor.com/?bcid=code39&text=' . urlencode($random_code) . '&includetext&scale=5';

        if (in_array(strtolower($random_code), array_map('strtolower', $generated_codes))) continue;

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE barcode = %s", $random_code));
        if ($exists > 0) continue;

        $generated_codes[] = $random_code;

        $inserted = $wpdb->insert($table, [
            'barcode' => $random_code,
            'barcode_check' => $random_code_check,
            'status' => 'unused',
            'province' => "",
            'channel' => "",
            'list_barcode' => "",
            'session' => $session,
            'qr_code_url' => $qr_code_url,
            'barcode_url' => $barcode_url,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            
        ]);

        if ($inserted === false) {
            wp_send_json(['status' => 'error', 'message' => 'Lỗi ghi dữ liệu vào database.']);
        }

        $count++;
    }

    if ($attempts >= $max_attempts) {
        wp_send_json(['status' => 'error', 'message' => 'Không thể tạo đủ mã trong batch.']);
    }

    wp_send_json(['status' => 'success']);
}

