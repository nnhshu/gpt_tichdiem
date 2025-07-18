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