<?php

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [], null, true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
});

add_action('wp_ajax_gpt_get_customer_info', 'gpt_get_customer_info_callback');
add_action('wp_ajax_nopriv_gpt_get_customer_info', 'gpt_get_customer_info_callback');

function gpt_get_customer_info_callback() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_SAVE_USERS;
    $logs_table = BIZGPT_PLUGIN_WP_LOGS;
    $phone = sanitize_text_field($_POST['phone']);
    
    if (empty($phone)) {
        wp_send_json(array(
            'status' => 'error',
            'message' => 'Số điện thoại không được để trống'
        ));
        return;
    }
    
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        wp_send_json(array(
            'status' => 'error',  
            'message' => 'Số điện thoại không hợp lệ'
        ));
        return;
    }
    
    try {
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT total_points, full_name, address FROM $table_name WHERE phone_number = %s",
            $phone
        ));
        
        if ($customer) {
            $store_history = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT store, address FROM $logs_table WHERE phone_number = %s AND store IS NOT NULL AND store != '' ORDER BY id DESC",
                $phone
            ));

            $stores = array();
            if ($store_history) {
                foreach ($store_history as $log) {
                    // Thêm store vào mảng
                    if (!empty($log->store)) {
                        $stores[] = array(
                            'value' => $log->store,
                            'label' => $log->store,
                            'type' => 'store'
                        );
                    }
                    
                    // Thêm address vào mảng (nếu khác với store)
                    if (!empty($log->address) && $log->address !== $log->store) {
                        $stores[] = array(
                            'value' => $log->address,
                            'label' => $log->address,
                            'type' => 'address'
                        );
                    }
                }
                
                // Loại bỏ các giá trị trùng lặp dựa trên 'value'
                $unique_stores = array();
                $seen_values = array();
                
                foreach ($stores as $store) {
                    if (!in_array($store['value'], $seen_values)) {
                        $unique_stores[] = $store;
                        $seen_values[] = $store['value'];
                    }
                }
                
                $stores = $unique_stores;
            }
            wp_send_json(array(
                'status' => 'success',
                'data' => array(
                    'total_points' => (int)$customer->total_points,
                    'fullname' => $customer->full_name,
                    'address' => $customer->address,
                    'stores' => $stores
                )
            ));
        } else {
            wp_send_json(array(
                'status' => 'error',
                'message' => 'Không tìm thấy khách hàng với số điện thoại này'
            ));
        }
        
    } catch (Exception $e) {
        wp_send_json(array(
            'status' => 'error',
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ));
    }
    
    wp_die();
}


// Hiển thị danh sách sản phẩm đổi quà
function gpt_display_selected_products_shortcode() {
    // Lấy các ID sản phẩm được chọn từ tùy chọn
    $selected_product_ids = get_option('selected_product_by_ids', array());
    $product_ids_string = implode(',', $selected_product_ids);
    $shortcode_content = do_shortcode("[ux_products style='default' type='row' equalize_box='true' ids='$product_ids_string']");
    return $shortcode_content;

}
add_shortcode('list_of_products_to_redeem_gifts', 'gpt_display_selected_products_shortcode');
add_action( 'woocommerce_after_shop_loop_item', 'gpt_display_featured_product_checkbox', 10 );

function gpt_display_featured_product_checkbox() {
    global $product;
    $reward_points = get_post_meta(get_the_ID(), '_reward_points', true);
    if ( $product->get_meta( '_is_featured_product', true ) === 'yes' ): ?>
        <?php $stock_quantity = $product->get_stock_quantity(); if ($stock_quantity > 0): ?>
            <div class="button-doidiem-qr button btn-gradient" data-point="<?php echo $reward_points; ?>" data-name="<?php echo $product->get_title(); ?>" data-id="<?php echo $product->get_id(); ?>">Đổi bằng <?php echo $reward_points; ?> điểm</div>
        <?php else: ?>
            <div class="out-of-stock-message error-small"><span>❗</span> Rất tiếc, sản phẩm này đã hết hàng.</div>
        <?php endif; ?>
    <?php endif;
}

add_action('wp_footer', 'gpt_output_swal_store_data');
function gpt_output_swal_store_data() {
    if (is_admin()) return;

    global $wpdb;
    $table_name = $wpdb->prefix . 'store_locations';
    $stores = $wpdb->get_results("SELECT * FROM $table_name");
    $store_data = [];

    foreach ($stores as $store) {
        $store_data[] = [
            'value' => $store->store_name,
            'label' => $store->store_name . ' - ' . $store->address . ' (' . $store->phone_number . ')'
        ];
    }

    echo '<script>const GPT_STORE_LOCATIONS = ' . json_encode($store_data) . ';</script>';
}

function gpt_add_popup_redeem_gifts_html_to_footer() {
    $client_id = getClientIdFromUrlPage();
    if (!is_admin()) {
        ?>
        <div id="gpt-lightbox-redeem" class="lightbox-content mfp-hide" style="max-width: 500px">
            <div class="gpt-popup-form">
                <h3 class="popup-title-shop">Đổi điểm</h3>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" id="phone-number-input-shop" placeholder="Nhập số điện thoại" required>
                    <span id="phone-error-message-shop" class="form-error">Số điện thoại không hợp lệ</span>
                </div>
                <div class="form-group">
                    <label>Chi nhánh mua hàng</label>
                    <select id="shop_store_name" required>
                        <option value="">CHỌN CHI NHÁNH</option>
                        <option value="PN1: 55A Tôn Thất Thuyết, P. Bình Khánh, Tp Long Xuyên, An Giang">PN1 - 55A Tôn Thất Thuyết</option>
                        <option value="PN2: 239 Ung Văn Khiêm, P. Mỹ Phước, Tp long Xuyên, An Giang">PN2 - 239 Ung Văn Khiêm</option>
                        <option value="PN3: 52A4 Cao Thắng, P. Bình Khánh, Tp Long Xuyên, An Giang">PN3 - 52A4 Cao Thắng</option>
                    </select>
                </div>
                <input type="hidden" id="product-name-input-shop">
                <input type="hidden" id="product-id-shop">
                <input type="hidden" id="client_id_shop" value="<?php echo esc_attr($client_id); ?>">
                <input type="hidden" id="points-input-shop">

                <div class="form-actions">
                    <button class="button primary" id="submit-phone-number-shop">Xác nhận</button>
                    <button class="button black close-lightbox" type="button">Hủy</button>
                </div>
            </div>
        </div>

        <style>
            .gpt-popup-form {
                padding: 20px;
                font-family: system-ui, sans-serif;
            }

            .gpt-popup-form h3 {
                font-size: 18px;
                margin-bottom: 15px;
                font-weight: 600;
            }

            .gpt-popup-form .form-group {
                margin-bottom: 15px;
            }

            .gpt-popup-form label {
                font-weight: 500;
                display: block;
                margin-bottom: 5px;
            }

            .gpt-popup-form input,
            .gpt-popup-form select {
                width: 100%;
                padding: 10px 12px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 6px;
                background: #f9f9f9;
                transition: 0.2s ease-in-out;
            }

            .gpt-popup-form input:focus,
            .gpt-popup-form select:focus {
                border-color: #3498db;
                background: #fff;
                outline: none;
            }

            .form-error {
                color: red;
                font-size: 13px;
                margin-top: 5px;
                display: none;
            }

            .gpt-popup-form .form-actions {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
            }

            .gpt-popup-form .button {
                padding: 10px 16px;
                border-radius: 6px;
                border: none;
                font-weight: 600;
                cursor: pointer;
            }

            .gpt-popup-form .button.primary {
                background: #3498db;
                color: #fff;
            }

            .gpt-popup-form .button.black {
                background: #000;
                color: #fff;
            }
            input#swal_phone,
            input.form-control {
                width: 100%;
                margin: 0;
                margin-bottom: 16px;
                background-color: #F1F5F7;
                border: 0px;
                box-shadow: none;
                border-radius: 8px;
                color: #000;
            }
            div#swal2-validation-message {
                margin: 0 24px;
                border-radius: 8px;
                margin-top: 16px;
                color: #000;
            }
            .form-group label {
                text-align: left;
            }
        </style>
        <!-- Include SweetAlert2 + Select2 CSS/JS -->
        <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->

        <script>
            jQuery(document).ready(function($) {
                $('.button-doidiem-qr').on('click', function(e) {
                    e.preventDefault();
                    
                    let productId = $(this).data('id');
                    let productName = $(this).data('name');
                    let points = $(this).data('point');
                    let clientId = "<?php echo getClientIdFromUrlPage(); ?>";
                    
                    let selectHTML = '<select id="swal_store_select" style="width:100%"><option value="">Vui lòng chọn địa chỉ nhận quà</option>';
                    GPT_STORE_LOCATIONS.forEach(store => {
                        selectHTML += `<option value="${store.value}">${store.label}</option>`;
                    });
                    selectHTML += '</select>';
                    
                    Swal.fire({
                        title: `Bạn đang đổi điểm cho: ${productName}`,
                        html: `
                            <div class="change_points_container">
                                <div class="form-group">
                                    <label>Số điện thoại bạn dùng tích điểm:</label>
                                    <input type="text" id="swal_phone" class="swal2-input" placeholder="Số điện thoại">
                                </div>
                                <div id="customer_info" style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 5px; display: none;">
                                    <div id="customer_details"></div>
                                </div>
                                <div id="store_select_container" style="display: none;">
                                    <div class="form-group">
                                        <label>Chọn địa chỉ nhận quà:</label>
                                    </div>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận',
                        cancelButtonText: 'Hủy',
                        didOpen: () => {
                            $('#swal_store_select').select2({
                                dropdownParent: $('.swal2-popup'),
                                width: '100%'
                            });
                            
                            $('.swal2-confirm').prop('disabled', true).css({
                                'opacity': '0.5',
                                'cursor': 'not-allowed'
                            });
                            
                            function updateStoreSelect(stores = null, show = false) {                                
                                // Xóa nội dung cũ trước khi render mới
                                $('#store_select_container .form-group').empty();
                                
                                if (!show) {
                                    if (!stores) {
                                        let inputHTML = `
                                            <label for="swal_store_select">Nhập địa chỉ giao hàng:</label>
                                            <input type="text" 
                                                id="swal_store_select" 
                                                class="form-control" 
                                                style="width:100%; margin-top: 10px;" 
                                                placeholder="Nhập địa chỉ của bạn...">
                                        `;
                                        
                                        $('#store_select_container .form-group').append(inputHTML);
                                        $('#store_select_container').show();
                                        
                                        // Thêm event listener cho input
                                        $('#swal_store_select').on('input', function() {
                                            updateButtonState();
                                        });
                                        
                                        return;
                                    } else {
                                        // Ẩn select nếu show = false và có stores
                                        $('#store_select_container').hide();
                                        return;
                                    }
                                }
                                
                                // Kiểm tra nếu không có dữ liệu stores khi show = true
                                if (!stores || stores.length === 0) {
                                    $('#store_select_container').hide();
                                    return;
                                }
                                
                                // Tạo select dropdown khi có dữ liệu và show = true
                                let newSelectHTML = `
                                    <label for="swal_store_select">Chọn từ lịch sử:</label>
                                    <select id="swal_store_select" style="width:100%; margin-top: 10px;">
                                        <option value="">Chọn địa chỉ</option>
                                `;
                                
                                // Hiển thị lịch sử store/address của khách hàng
                                stores.forEach(item => {
                                    const typeLabel = item.type === 'store' ? '[Cửa hàng]' : '[Địa chỉ]';
                                    newSelectHTML += `<option value="${item.value}">${typeLabel} ${item.label}</option>`;
                                });
                                
                                newSelectHTML += '</select>';
                                
                                // Cập nhật HTML, hiển thị container và khởi tạo lại select2
                                $('#store_select_container .form-group').append(newSelectHTML);
                                $('#store_select_container').show();
                                $('#swal_store_select').select2({
                                    dropdownParent: $('.swal2-popup'),
                                    width: '100%'
                                }).on('change', function() {
                                    updateButtonState();
                                });
                            }
                            
                            // Khởi tạo select ban đầu
                            updateStoreSelect(null, false);
                            
                            // Biến để track trạng thái khách hàng
                            let customerFound = false;
                            
                            // Function để cập nhật trạng thái button
                            function updateButtonState() {
                                const phone = $('#swal_phone').val();
                                const store = $('#swal_store_select').val();
                                
                                if (customerFound && /^[0-9]{10}$/.test(phone) && store) {
                                    $('.swal2-confirm').prop('disabled', false).css({
                                        'opacity': '1',
                                        'cursor': 'pointer'
                                    });
                                } else {
                                    $('.swal2-confirm').prop('disabled', true).css({
                                        'opacity': '0.5',
                                        'cursor': 'not-allowed'
                                    });
                                }
                            }
                            
                            // Event listener cho select store
                            $(document).on('change', '#swal_store_select', function() {
                                updateButtonState();
                            });
                            
                            // Thêm event listener cho input số điện thoại
                            let phoneTimeout;
                            $('#swal_phone').on('input', function() {
                                const phone = $(this).val();
                                customerFound = false;
                                updateButtonState();
                                
                                // Clear timeout trước đó
                                clearTimeout(phoneTimeout);
                                
                                // Ẩn thông tin khách hàng khi đang nhập
                                $('#customer_info').hide();
                                $('#customer_details').html('');
                                updateStoreSelect(null, false);
                                
                                // Chỉ tìm kiếm khi số điện thoại có đủ 10 số
                                if (/^[0-9]{10}$/.test(phone)) {
                                    phoneTimeout = setTimeout(() => {
                                        // Hiển thị loading
                                        $('#customer_info').show();
                                        $('#customer_details').html('<div style="text-align: center;"><i class="fa fa-spinner fa-spin"></i> Đang tìm kiếm thông tin khách hàng...</div>');
                                        
                                        // Gọi ajax để lấy thông tin khách hàng
                                        $.ajax({
                                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                            method: 'POST',
                                            data: {
                                                action: 'gpt_get_customer_info',
                                                phone: phone
                                            },
                                            success: function(response) {
                                                try {
                                                    
                                                    if (response.status === 'success') {
                                                        customerFound = true;
                                                        const customer = response.data;
                                                        console.log(customer);
                                                        $('#customer_details').html(`
                                                            <div style="text-align: left;">
                                                                <strong>Thông tin khách hàng:</strong>
                                                                <div style="margin-top: 10px;">
                                                                    <p>Họ tên: ${customer.fullname || 'Chưa cập nhật'}</p>
                                                                    <p>Địa chỉ: ${customer.address || 'Chưa cập nhật'}</p> 
                                                                    <p style="margin-bottom: 0px;">Điểm hiện có của bạn: <span style="color: #e74c3c; font-weight: bold;">${customer.total_points || 0} điểm</span></p> 
                                                                </div>
                                                            </div>
                                                        `);
                                                        if (customer.stores && customer.stores.length > 0) {
                                                            updateStoreSelect(customer.stores, true);
                                                        } else {
                                                            $('#customer_details').append(`
                                                                <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
                                                                    <i class="fa fa-info-circle"></i> Khách hàng chưa có lịch sử giao dịch
                                                                </div>
                                                            `);
                                                            updateStoreSelect(null, false);
                                                        }
                                                        updateButtonState();
                                                    } else {
                                                        $('#customer_details').html(`
                                                            <div style="text-align: center; color: #e74c3c;">
                                                                <i class="fa fa-exclamation-triangle"></i> ${data.message || 'Không tìm thấy thông tin khách hàng'}
                                                            </div>
                                                        `);
                                                    }
                                                    updateButtonState();
                                                } catch (e) {
                                                    $('#customer_details').html(`
                                                        <div style="text-align: center; color: #e74c3c;">
                                                            <i class="fa fa-exclamation-triangle"></i> Có lỗi xảy ra khi tải thông tin khách hàng
                                                        </div>
                                                    `);
                                                    updateStoreSelect(null, false);
                                                   
                                                }
                                            },
                                            error: function() {
                                                $('#customer_details').html(`
                                                    <div style="text-align: center; color: #e74c3c;">
                                                        <i class="fa fa-exclamation-triangle"></i> Không thể kết nối để lấy thông tin khách hàng
                                                    </div>
                                                `);
                                            }
                                        });
                                    }, 500);
                                }
                            });
                        },
                        preConfirm: () => {
                            const phone = document.getElementById('swal_phone').value;
                            const store = document.getElementById('swal_store_select').value;
                            
                            if (!/^[0-9]{10}$/.test(phone)) {
                                Swal.showValidationMessage('Số điện thoại không hợp lệ');
                                return false;
                            }
                            if (!store) {
                                Swal.showValidationMessage('Vui lòng chọn chi nhánh');
                                return false;
                            }
                            
                            return { phone, store };
                        }
                    }).then(result => {
                        if (result.isConfirmed && result.value) {
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                method: 'POST',
                                data: {
                                    action: 'gpt_save_exchange_gift',
                                    phone: result.value.phone,
                                    storeName: result.value.store,
                                    productId: productId,
                                    product: productName,
                                    points: points,
                                    client_id: clientId
                                },
                                success: function(res) {
                                    const data = JSON.parse(res);
                                    if (data.status === 404) {
                                        Swal.fire('Thất bại', data.message, 'error');
                                    } else {
                                        Swal.fire(`Chúc mừng bạn đã nhận được phần quà ${productName}`, data.message, 'success');
                                    }
                                }
                            });
                        }
                    });
                });
            });
            </script>
        <?php
    }
}

add_action('wp_footer', 'gpt_add_popup_redeem_gifts_html_to_footer');

add_action('wp_ajax_gpt_save_exchange_gift', 'gpt_save_exchange_gift');
add_action('wp_ajax_nopriv_gpt_save_exchange_gift', 'gpt_save_exchange_gift');

function gpt_save_exchange_gift() {
    global $wpdb;

    $table = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT;
    $users_table = BIZGPT_PLUGIN_WP_SAVE_USERS;

    $phone     = sanitize_text_field($_POST['phone']);
    $store     = sanitize_text_field($_POST['storeName']);
    $product   = sanitize_text_field($_POST['product']);
    $productId = intval($_POST['productId']);
    $points    = intval($_POST['points']);
    $client_id = sanitize_text_field($_POST['client_id']);
    $user_name = is_user_logged_in() ? wp_get_current_user()->user_login : 'anonymous';
    $now       = current_time('mysql');

    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $users_table WHERE phone_number = %s LIMIT 1",
        $phone
    ));

    if (!$user) {
        wp_send_json(json_encode([
            'status' => 404,
            'message' => 'Số điện thoại chưa được đăng ký hệ thống.'
        ]));
    }

    $total_points = intval($user->total_points);
    $current_redeemed_points = intval($user->redeemed_points);

    // 2. Kiểm tra đủ điểm không
    if ($total_points < $points) {
        wp_send_json(json_encode([
            'status' => 404,
            'message' => 'Bạn không đủ điểm để đổi quà.'
        ]));
    }

    // 3. Ghi vào bảng đổi điểm (status: pending)
    $remaining_points = $total_points - $points;
    $new_redeemed_points = $current_redeemed_points + $points;

    $insert = $wpdb->insert($table, [
        'phone'            => $phone,
        'store_name'       => $store,
        'client_id'        => $client_id,
        'product'          => $product,
        'points'           => $points,
        'remaining_points' => $remaining_points,
        'status'           => 'pending',
        'user_name'        => $user_name,
        'time'             => $now
    ]);

    if ($insert) {
        $update_result = $wpdb->update(
            $users_table,
            [
                'total_points' => $remaining_points,
                'redeemed_points' => $new_redeemed_points
            ],
            ['phone_number' => $phone]
        );

        if ($update_result !== false) {
            wp_send_json(json_encode([
                'status' => 200,
                'message' => 'Chúc mừng bạn đã đổi điểm thành công. Số điểm còn lại của bạn là: ' . $remaining_points . ' điểm'
            ]));
        } else {
            $wpdb->delete($table, [
                'phone' => $phone,
                'time' => $now
            ]);
            
            wp_send_json(json_encode([
                'status' => 500,
                'message' => 'Không thể cập nhật thông tin người dùng. Vui lòng thử lại.'
            ]));
        }
    } else {
        wp_send_json(json_encode([
            'status' => 500,
            'message' => 'Không thể lưu dữ liệu. Vui lòng thử lại.'
        ]));
    }

    wp_die();
}


