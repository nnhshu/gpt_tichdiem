<?php

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [], null, true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
});


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
            <div class="button-doidiem-qr button" data-point="<?php echo $reward_points; ?>" data-name="<?php echo $product->get_title(); ?>" data-id="<?php echo $product->get_id(); ?>">Đổi bằng <?php echo $reward_points; ?> điểm</div>
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
            input#swal_phone {
                width: 100%;
                margin: 0;
                margin-bottom: 16px;
                background-color: #faf3e8;
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
        </style>
        <!-- Include SweetAlert2 + Select2 CSS/JS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            jQuery(document).ready(function($) {
                $('.button-doidiem-qr').on('click', function(e) {
                    e.preventDefault();

                    let productId = $(this).data('id');
                    let productName = $(this).data('name');
                    let points = $(this).data('point');
                    let clientId = "<?php echo getClientIdFromUrlPage(); ?>";

                    let selectHTML = '<select id="swal_store_select" style="width:100%"><option value="">Chọn chi nhánh</option>';
                    GPT_STORE_LOCATIONS.forEach(store => {
                        selectHTML += `<option value="${store.value}">${store.label}</option>`;
                    });
                    selectHTML += '</select>';

                    Swal.fire({
                        title: `Bạn đang đổi điểm cho: ${productName}`,
                        html: `
                            <input id="swal_phone" class="swal2-input" placeholder="Số điện thoại">
                            ${selectHTML}
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận',
                        cancelButtonText: 'Hủy',
                        didOpen: () => {
                            $('#swal_store_select').select2({
                                dropdownParent: $('.swal2-popup'),
                                width: '100%'
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
                                        Swal.fire('Thành công', data.message, 'success');
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

    $table = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT; // bảng gpt_doi_diem_barcode
    $users_table = BIZGPT_PLUGIN_WP_SAVE_USERS;       // bảng gpt_list_users

    $phone     = sanitize_text_field($_POST['phone']);
    $store     = sanitize_text_field($_POST['storeName']);
    $product   = sanitize_text_field($_POST['product']);
    $productId = intval($_POST['productId']);
    $points    = intval($_POST['points']);
    $client_id = sanitize_text_field($_POST['client_id']);
    $user_name = is_user_logged_in() ? wp_get_current_user()->user_login : 'anonymous';
    $now       = current_time('mysql');

    // 1. Kiểm tra user tồn tại
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

    // 2. Kiểm tra đủ điểm không
    if ($total_points < $points) {
        wp_send_json(json_encode([
            'status' => 404,
            'message' => 'Bạn không đủ điểm để đổi quà.'
        ]));
    }

    // 3. Ghi vào bảng đổi điểm (status: pending)
    $remaining_points = $total_points - $points;

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
        $wpdb->update(
            $users_table,
            ['total_points' => $remaining_points],
            ['phone_number' => $phone]
        );
        wp_send_json(json_encode([
            'status' => 200,
            'message' => 'Đã ghi nhận yêu cầu đổi điểm. Vui lòng chờ xác nhận.'
        ]));
    } else {
        wp_send_json(json_encode([
            'status' => 500,
            'message' => 'Không thể lưu dữ liệu. Vui lòng thử lại.'
        ]));
    }

    wp_die();
}


