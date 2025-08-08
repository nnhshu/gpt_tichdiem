<?php

add_action('admin_init', 'bizgpt_users_points_guide_settings_init');

function bizgpt_users_points_guide_settings_init() {
    // Register settings với callback function đúng+
    register_setting('user_points_guide_group', 'selected_product_by_ids', 'bizgpt_user_points_process_selected_products_by_id');
    
    // Add settings section
    add_settings_section(
        'bizgpt_user_points_section',
        'Cấu hình sản phẩm đổi quà',
        'bizgpt_user_points_section_callback',
        'user_points_guide_page'
    );
    
    // Add settings field
    add_settings_field(
        'selected_product_by_ids',
        'Chọn sản phẩm',
        'selected_products_callback',
        'user_points_guide_page',
        'bizgpt_user_points_section',
        array(
            'label_for' => 'selected_product_by_ids'
        )
    );
}

function bizgpt_user_points_guide_display() {
    // Xử lý lưu dữ liệu cho sản phẩm đổi quà
    if (isset($_POST['selected_product_by_ids']) && is_array($_POST['selected_product_by_ids'])) {
        $selected_product_by_ids = array_map('intval', $_POST['selected_product_by_ids']);
        
        // Lấy danh sách sản phẩm cũ để reset
        $old_selected_products = get_option('selected_product_by_ids', array());
        
        // Reset _is_featured_product cho các sản phẩm cũ
        foreach ($old_selected_products as $old_product_id) {
            if (!in_array($old_product_id, $selected_product_by_ids)) {
                delete_post_meta($old_product_id, '_is_featured_product');
            }
        }
        
        // Cập nhật _is_featured_product cho các sản phẩm được chọn
        foreach ($selected_product_by_ids as $product_id) {
            update_post_meta($product_id, '_is_featured_product', 'yes');
        }
        
        // Lưu vào option
        update_option('selected_product_by_ids', $selected_product_by_ids);
        
        echo '<div class="notice notice-success is-dismissible"><p>Cấu hình sản phẩm đổi quà đã được lưu thành công!</p></div>';
    }
    
    $selected_product_by_ids = get_option('selected_product_by_ids', array());
    
    // Query để lấy sản phẩm có reward points
    $args_products = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key' => '_reward_points',
                'value'     => '1',
                'compare'   => '>='
            )
        )
    );
    
    $woo_custom_query = new WP_Query($args_products);
    $products = $woo_custom_query->get_posts();
    
    ?>
    <form method="post" action="">
        <style>
            .action {
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }

            .action p.submit {
                margin: 0;
                padding: 0;
            }
            .selected-product-row {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 10px;
            }

            .selected-product-row .product-info .product-name {
                flex: 1;
            }
        </style>
        <div class="form-group">
            <label for="selected_product_by_ids">Danh sách sản phẩm hiển thị đổi quà:</label>
            <select name="selected_product_by_ids[]" id="selected_product_by_ids" multiple="multiple" style="width: 100%; min-width: 400px; height: 200px;">
                <?php foreach ($products as $product) : ?>
                    <option value="<?php echo esc_attr($product->ID); ?>" <?php selected(in_array($product->ID, $selected_product_by_ids), true); ?>>
                        <?php echo esc_html($product->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">Chọn các sản phẩm muốn hiển thị trong phần đổi quà. Giữ Ctrl (hoặc Cmd trên Mac) để chọn nhiều sản phẩm.</p>
        </div>
        <div class="action">
            <?php submit_button('Lưu cấu hình sản phẩm'); ?>
        </div>
    </form>
    
    <?php if (!empty($selected_product_by_ids)) : ?>
        <h3>Sản phẩm đã chọn:</h3>
        <div class="selected-products-list" style="border: 1px solid #ddd; border-radius: 5px; background: #fff;">
            <?php foreach ($selected_product_by_ids as $index => $product_id) : 
                $product = wc_get_product($product_id);
                if ($product) :
                    $product_image = $product->get_image('thumbnail');
                    $product_name = $product->get_name();
                    $product_price = $product->get_price_html();
                    $reward_points = get_post_meta($product_id, '_reward_points', true);
                    $border_bottom = ($index < count($selected_product_by_ids) - 1) ? 'border-bottom: 1px solid #eee;' : '';
            ?>
                <div class="selected-product-row" style="<?php echo $border_bottom; ?>">
                    <div class="product-image" style="width: 60px; height: 60px; overflow: hidden; border-radius: 5px;">
                        <?php echo $product_image; ?>
                    </div>
                    <div class="product-info" style="flex: 1; display: flex; align-items: center; gap: 20px;">
                        <div class="product-name">
                            <strong><?php echo esc_html($product_name); ?></strong>
                            <span style="display: block; font-weight: 500;">ID: <?php echo $product_id; ?></span>
                            <span style="display: block; font-weight: 500;">Giá: <?php echo $product_price; ?></span>
                        </div>
                        <?php if ($reward_points) : ?>
                        <div class="product-points" style="font-size: 13px; color: #d63384;">
                            <span style="display: block; font-weight: 500;">Điểm đổi tương ứng:</span>
                            <span><strong><?php echo $reward_points; ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; ?>
        </div>
        
        <h3>Shortcode để hiển thị:</h3>
        <p><code>[bizgpt_display_selected_products_by_ids_shortcode]</code></p>
        <p class="description">Sao chép shortcode trên và dán vào bất kỳ trang hoặc bài viết nào để hiển thị các sản phẩm đổi quà.</p>
    <?php endif; ?>
    
    <script>
        jQuery(document).ready(function($) {
            $('#selected_product_by_ids').select2({
                placeholder: 'Chọn sản phẩm...',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    <?php
    
    wp_reset_postdata();
}

// 1. Chỉ thêm field reward points ngay sau phần giá (loại bỏ hoàn toàn General tab)
add_action('woocommerce_product_options_pricing', 'add_reward_points_after_price');
function add_reward_points_after_price() {
    global $post;
    
    echo '<div class="options_group pricing show_if_simple show_if_external">';
    
    woocommerce_wp_text_input(
        array(
            'id' => '_reward_points',
            'label' => __('💰 Điểm thưởng tương ứng', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Số điểm thưởng khách hàng nhận được khi mua sản phẩm này.', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min' => '0'
            ),
            'wrapper_class' => 'show_if_simple show_if_external'
        )
    );
    
    // Hiển thị thông tin tỷ lệ quy đổi
    $current_points = get_post_meta($post->ID, '_reward_points', true);
    $product_price = get_post_meta($post->ID, '_regular_price', true);
    
    if ($current_points && $product_price && $current_points > 0) {
        $conversion_rate = round(($current_points / $product_price) * 100, 2);
        echo '<p class="form-field" style="margin-left: 160px; color: #666; font-style: italic;">';
        echo '<small>📊 Tỷ lệ: ' . $conversion_rate . '% giá trị sản phẩm</small>';
        echo '</p>';
    }
    
    // Checkbox hiển thị trên frontend
    woocommerce_wp_checkbox( array(
        'id'            => '_show_reward_points',
        'wrapper_class' => 'show_if_simple show_if_external',
        'label' => __('Hiển thị điểm thưởng trên trang sản phẩm', 'woocommerce'),
        'desc_tip' => 'true',
        'description' => __('Tick để hiển thị số điểm thưởng trên trang chi tiết sản phẩm.', 'woocommerce'),
    ) );
    
    echo '</div>';
}

// 2. Lưu chỉ reward_points (loại bỏ hoàn toàn gpt_reward_points)
add_action('woocommerce_process_product_meta', 'gpt_save_reward_points_field');
function gpt_save_reward_points_field($post_id) {
    // Chỉ lưu reward_points
    $reward_points = isset($_POST['_reward_points']) ? intval($_POST['_reward_points']) : 0;
    update_post_meta($post_id, '_reward_points', $reward_points);
    
    // Lưu checkbox hiển thị
    $show_rewards = isset($_POST['_show_reward_points']) ? 'yes' : 'no';
    update_post_meta($post_id, '_show_reward_points', $show_rewards);
}

// 3. Thêm cột trong admin product list
add_filter('manage_edit-product_columns', 'add_reward_points_column');
function add_reward_points_column($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // Thêm cột reward points sau cột price
        if ($key === 'price') {
            $new_columns['reward_points'] = '💰 Điểm thưởng';
        }
    }
    return $new_columns;
}

// 4. Hiển thị dữ liệu trong cột (chỉ _reward_points)
add_action('manage_product_posts_custom_column', 'show_reward_points_column', 10, 2);
function show_reward_points_column($column, $post_id) {
    if ($column == 'reward_points') {
        $reward_points = get_post_meta($post_id, '_reward_points', true);
        
        if ($reward_points && $reward_points > 0) {
            echo '<strong style="color: #2271b1;">' . esc_html($reward_points) . ' điểm</strong>';
        } else {
            echo '<span style="color: #999;">-</span>';
        }
        
        // Hiển thị status hiển thị frontend
        $show_on_frontend = get_post_meta($post_id, '_show_reward_points', true);
        if ($show_on_frontend === 'yes') {
            echo '<br><small style="color: green;">👁️ Hiển thị</small>';
        }
    }
}

// 5. Quick edit chỉ cho reward points
add_action('quick_edit_custom_box', 'add_reward_points_quick_edit', 10, 2);
function add_reward_points_quick_edit($column_name, $post_type) {
    if ($column_name != 'reward_points' || $post_type != 'product') return;
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title">💰 Điểm thưởng</span>
                <span class="input-text-wrap">
                    <input type="number" name="_reward_points" class="_reward_points" value="" min="0" step="1" />
                </span>
            </label>
            <label class="alignleft">
                <input type="checkbox" name="_show_reward_points" class="_show_reward_points" value="yes" />
                <span class="checkbox-title">Hiển thị trên frontend</span>
            </label>
        </div>
    </fieldset>
    <?php
}

// 6. JavaScript cho quick edit (chỉ reward_points)
add_action('admin_footer-edit.php', 'reward_points_quick_edit_js');
function reward_points_quick_edit_js() {
    global $current_screen;
    if ($current_screen->post_type != 'product') return;
    ?>
    <script>
    jQuery(function($){
        var $inline_editor = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $inline_editor.apply(this, arguments);

            var post_id = 0;
            if (typeof(id) == 'object') {
                post_id = parseInt(this.getId(id));
            }

            if (post_id > 0) {
                // Lấy dữ liệu từ hidden fields
                var reward_points = $('#inline_' + post_id + ' .reward_points').text() || '';
                var show_reward = $('#inline_' + post_id + ' .show_reward_points').text() || '';
                
                // Điền vào quick edit form
                $('input[name="_reward_points"]', '.inline-edit-row').val(reward_points.trim());
                
                if (show_reward.trim() === 'yes') {
                    $('input[name="_show_reward_points"]', '.inline-edit-row').prop('checked', true);
                } else {
                    $('input[name="_show_reward_points"]', '.inline-edit-row').prop('checked', false);
                }
            }
        }
    });
    </script>
    <?php
}

// 7. Hidden fields cho quick edit (chỉ reward_points)
add_action('manage_product_posts_custom_column', 'add_reward_points_inline_data', 11, 2);
function add_reward_points_inline_data($column, $post_id) {
    if ($column == 'reward_points') {
        $reward_points = get_post_meta($post_id, '_reward_points', true);
        $show_reward = get_post_meta($post_id, '_show_reward_points', true);
        
        echo '<div class="hidden" id="inline_' . $post_id . '">';
        echo '<div class="reward_points">' . esc_html($reward_points) . '</div>';
        echo '<div class="show_reward_points">' . esc_html($show_reward) . '</div>';
        echo '</div>';
    }
}

// 8. Lưu dữ liệu từ quick edit (chỉ reward_points)
add_action('save_post', 'save_reward_points_quick_edit');
function save_reward_points_quick_edit($post_id) {
    if (
        !isset($_POST['post_type']) || $_POST['post_type'] != 'product' ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !current_user_can('edit_post', $post_id)
    ) return;

    if (isset($_POST['_reward_points'])) {
        update_post_meta($post_id, '_reward_points', intval($_POST['_reward_points']));
    }
    
    $show_reward = isset($_POST['_show_reward_points']) ? 'yes' : 'no';
    update_post_meta($post_id, '_show_reward_points', $show_reward);
}

// 9. Hiển thị reward points trên frontend
add_action('woocommerce_single_product_summary', 'display_reward_points_on_product', 25);
function display_reward_points_on_product() {
    global $product;
    
    $show_rewards = get_post_meta($product->get_id(), '_show_reward_points', true);
    $reward_points = get_post_meta($product->get_id(), '_reward_points', true);
    
    if ($show_rewards === 'yes' && $reward_points && $reward_points > 0) {
        echo '<div class="reward-points-info" style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; margin: 15px 0; border-radius: 5px;">';
        echo '<h4 style="margin: 0 0 10px 0; color: #2271b1;">🎁 Điểm thưởng</h4>';
        echo '<p style="margin: 0; font-size: 16px; font-weight: bold; color: #d63384;">Nhận ' . esc_html($reward_points) . ' điểm thưởng khi mua sản phẩm này!</p>';
        echo '<small style="color: #666;">Điểm thưởng có thể sử dụng cho lần mua hàng tiếp theo.</small>';
        echo '</div>';
    }
}

// 10. CSS cho admin
add_action('admin_head', 'reward_points_admin_css');
function reward_points_admin_css() {
    global $current_screen;
    if ($current_screen && $current_screen->post_type === 'product') {
        ?>
        <style>
        .column-reward_points {
            width: 120px;
        }
        
        .reward-points-info {
            background: #f0f8ff;
            border-left: 4px solid #2271b1;
            padding: 10px;
            margin: 10px 0;
        }
        
        ._reward_points_field label {
            font-weight: bold;
            color: #2271b1;
        }
        
        .inline-edit-row ._reward_points {
            width: 80px;
        }
        
        /* Style cho pricing section */
        .options_group.pricing ._reward_points_field {
            background: #f9f9f9;
            border-left: 3px solid #2271b1;
            padding-left: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .options_group.pricing ._reward_points_field label {
            color: #2271b1 !important;
            font-weight: bold;
        }
        </style>
        <?php
    }
}

// 11. Bulk edit support (chỉ reward_points)
add_action('woocommerce_product_bulk_edit_end', 'reward_points_bulk_edit');
function reward_points_bulk_edit() {
    ?>
    <label>
        <span class="title">💰 Điểm thưởng</span>
        <span class="input-text-wrap">
            <select name="_reward_points_action" class="select_reward_points_action">
                <option value="">— Không thay đổi —</option>
                <option value="0">Xóa điểm thưởng</option>
                <option value="set">Đặt giá trị:</option>
            </select>
            <input type="number" name="_reward_points_value" class="text reward_points_value" placeholder="Nhập điểm..." style="width: 80px; display: none;" min="0" step="1" />
        </span>
    </label>
    
    <script>
    jQuery(document).ready(function($) {
        $('select.select_reward_points_action').change(function() {
            if ($(this).val() === 'set') {
                $('.reward_points_value').show();
            } else {
                $('.reward_points_value').hide();
            }
        });
    });
    </script>
    <?php
}

// 12. Lưu bulk edit (chỉ reward_points)
add_action('woocommerce_product_bulk_edit_save', 'save_reward_points_bulk_edit');
function save_reward_points_bulk_edit($product) {
    $post_id = $product->get_id();
    
    if (isset($_REQUEST['_reward_points_action']) && $_REQUEST['_reward_points_action'] !== '') {
        if ($_REQUEST['_reward_points_action'] === '0') {
            update_post_meta($post_id, '_reward_points', 0);
        } elseif ($_REQUEST['_reward_points_action'] === 'set' && isset($_REQUEST['_reward_points_value'])) {
            $value = intval($_REQUEST['_reward_points_value']);
            update_post_meta($post_id, '_reward_points', $value);
        }
    }
}