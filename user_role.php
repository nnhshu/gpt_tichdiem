<?php
add_action('init', 'create_custom_roles');
add_action('after_switch_theme', 'create_custom_roles');

function create_custom_roles() {
    // Kiểm tra xem role đã tồn tại chưa
    if (!get_role('nhan_vien_kho')) {
        create_warehouse_staff_role();
    }
    
    if (!get_role('quan_ly_kho')) {
        create_warehouse_manager_role();
    }
}

/**
 * Tạo Role: Nhân viên kho
 * Quyền: Quản lý kho, xem đơn hàng, xuất nhập kho
 */
function create_warehouse_staff_role() {
    add_role(
        'nhan_vien_kho',
        'Nhân viên kho',
        array(
            'read' => true,
            'upload_files' => true,
            'edit_files' => false,
            
            // Quyền quản lý posts (đơn hàng)
            'edit_posts' => true,
            'edit_others_posts' => false,
            'edit_published_posts' => true,
            'publish_posts' => false,
            'delete_posts' => false,
            'delete_others_posts' => false,
            'delete_published_posts' => false,
            'delete_private_posts' => false,
            'edit_private_posts' => false,
            'read_private_posts' => false,
            
            // Quyền quản lý pages
            'edit_pages' => false,
            'edit_others_pages' => false,
            'edit_published_pages' => false,
            'publish_pages' => false,
            'delete_pages' => false,
            
            // Quyền quản lý comments
            'moderate_comments' => false,
            'edit_comment' => false,
            'edit_comments' => false,
            'delete_comments' => false,
            
            // Quyền quản lý themes và plugins
            'switch_themes' => false,
            'edit_themes' => false,
            'activate_plugins' => false,
            'edit_plugins' => false,
            'edit_users' => false,
            'list_users' => false,
            'delete_users' => false,
            'create_users' => false,
            
            // Quyền tùy chỉnh cho warehouse
            'manage_warehouse' => true,
            'view_inventory' => true,
            'edit_inventory' => true,
            'export_inventory' => true,
            'import_inventory' => true,
            'view_orders' => true,
            'edit_order_status' => true,
            'create_shipment' => true,
            'manage_stock' => true,

        )
    );
}

/**
 * Tạo Role: Quản lý kho  
 * Quyền: Quản lý đơn hàng, khách hàng, báo cáo
 */
function create_warehouse_manager_role() {
    if (!get_role('quan_ly_kho')) {
        add_role(
            'quan_ly_kho',
            'Quản lý kho',
            array(
                // Quyền cơ bản
                'read' => true,
                'upload_files' => true,
                'edit_files' => false,
                
                // Quyền quản lý posts (đơn hàng)
                'edit_posts' => true,
                'edit_others_posts' => true,
                'edit_published_posts' => true,
                'publish_posts' => true,
                'delete_posts' => false,
                'delete_others_posts' => false,
                'delete_published_posts' => false,
                'delete_private_posts' => false,
                'edit_private_posts' => true,
                'read_private_posts' => true,
                
                // Quyền quản lý pages (hạn chế)
                'edit_pages' => false,
                'edit_others_pages' => false,
                'edit_published_pages' => false,
                'publish_pages' => false,
                'delete_pages' => false,
                
                // Quyền quản lý comments
                'moderate_comments' => true,
                'edit_comment' => true,
                'edit_comments' => true,
                'delete_comments' => false,
                
                // Không có quyền quản lý themes, plugins, users
                'switch_themes' => false,
                'edit_themes' => false,
                'activate_plugins' => false,
                'edit_plugins' => false,
                'edit_users' => false,
                'list_users' => true,
                'delete_users' => false,
                'create_users' => false,
                
                // Quyền tùy chỉnh cho Quản lý kho
                'manage_warehouse' => true,
                'view_inventory' => true,
                'edit_inventory' => true,
                'export_inventory' => true,
                'import_inventory' => true,
                'manage_stock' => true,
                'approve_orders' => true,
                'create_shipment' => true,
                'edit_order_status' => true,
                'view_orders' => true,
                'edit_orders' => true,
                'manage_channels' => true,
                'manage_distributors' => true,
                'manage_stores' => true,
                'manage_employees' => true,
                'view_reports' => true,
                'export_reports' => true,

                // Quyền WooCommerce
                'manage_woocommerce' => true,
                'view_woocommerce_reports' => true,
                'edit_product' => true,
                'read_product' => true,
                'delete_product' => false,
                'edit_products' => true,
                'edit_others_products' => true,
                'publish_products' => true,
                'read_private_products' => true,
                'edit_private_products' => true,
                'edit_published_products' => true,
                'manage_product_terms' => true,
                'edit_product_terms' => true,
                'delete_product_terms' => false,
                'assign_product_terms' => true,
                'edit_shop_orders' => true,
                'edit_others_shop_orders' => true,
                'edit_published_shop_orders' => true,
                'edit_private_shop_orders' => true,
                'read_private_shop_orders' => true,
                'delete_shop_orders' => false,
                'publish_shop_orders' => true,
                'read_shop_order' => true,
                'edit_shop_order' => true,
                'manage_woocommerce_coupons' => false,
                'edit_shop_coupons' => false,
            )
        );
    }
    
    // Thêm capabilities cho Administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('approve_orders');
        $admin_role->add_cap('manage_channels');
        $admin_role->add_cap('manage_distributors');
        $admin_role->add_cap('manage_stores');
        $admin_role->add_cap('manage_employees');
        $admin_role->add_cap('manage_woocommerce');
        $admin_role->add_cap('view_woocommerce_reports');
    }

}

/**
 * Thêm capabilities tùy chỉnh cho Administrator
 */
function add_custom_caps_to_admin() {
    $admin_role = get_role('administrator');
    
    if ($admin_role) {
        // Warehouse capabilities
        $admin_role->add_cap('manage_warehouse');
        $admin_role->add_cap('view_inventory');
        $admin_role->add_cap('edit_inventory');
        $admin_role->add_cap('export_inventory');
        $admin_role->add_cap('import_inventory');
        $admin_role->add_cap('manage_stock');
        
        // Sales capabilities  
        $admin_role->add_cap('manage_sales');
        $admin_role->add_cap('view_all_orders');
        $admin_role->add_cap('edit_all_orders');
        $admin_role->add_cap('delete_orders');
        $admin_role->add_cap('create_orders');
        $admin_role->add_cap('manage_customers');
        $admin_role->add_cap('view_reports');
        $admin_role->add_cap('export_reports');
        $admin_role->add_cap('manage_promotions');
        $admin_role->add_cap('manage_channels');
        $admin_role->add_cap('manage_distributors');
        $admin_role->add_cap('manage_stores');
        $admin_role->add_cap('manage_employees');
        $admin_role->add_cap('create_shipment');
        $admin_role->add_cap('edit_order_status');
    }
}
add_action('init', 'add_custom_caps_to_admin');

/**
 * Hàm kiểm tra quyền tùy chỉnh
 */
function check_user_warehouse_permission() {
    return current_user_can('manage_warehouse') || current_user_can('manage_options');
}

function check_user_sales_permission() {
    return current_user_can('manage_sales') || current_user_can('manage_options');
}

function check_user_inventory_permission() {
    return current_user_can('view_inventory') || current_user_can('manage_options');
}

/**
 * Hạn chế truy cập Admin Dashboard theo role
 */
function restrict_admin_access() {
    if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax()) {
        $current_user = wp_get_current_user();
        
        // Cho phép nhân viên kho và quản lý kho truy cập admin
        if (in_array('nhan_vien_kho', $current_user->roles) || 
            in_array('quan_ly_kho', $current_user->roles)) {
            return; // Cho phép truy cập
        }
        
        // Redirect các role khác về frontend
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'restrict_admin_access');

/**
 * Tùy chỉnh Admin Menu theo role
 */
function customize_admin_menu_by_role() {
    $current_user = wp_get_current_user();
    
    // Menu cho Nhân viên kho
    if (in_array('nhan_vien_kho', $current_user->roles)) {
        // Ẩn các menu không cần thiết
        remove_menu_page('edit.php?post_type=page');
        remove_menu_page('edit-comments.php');
        remove_menu_page('themes.php');
        remove_menu_page('plugins.php');
        remove_menu_page('users.php');
        remove_menu_page('tools.php');
        remove_menu_page('options-general.php');
        
        // Chỉ giữ lại các menu cần thiết
        // Dashboard, Posts (Orders), Media, Custom menus
    }
    
    // Menu cho Quản lý kho  
    if (in_array('quan_ly_kho', $current_user->roles)) {
        // Ẩn một số menu admin
        remove_menu_page('themes.php');
        remove_menu_page('plugins.php');
        remove_menu_page('tools.php');
        remove_menu_page('options-general.php');
        
        // Giữ lại: Dashboard, Posts, Media, Comments, Users (view only)
    }
}
add_action('admin_menu', 'customize_admin_menu_by_role', 999);

/**
 * Tùy chỉnh Admin Bar
 */
function customize_admin_bar_by_role($wp_admin_bar) {
    $current_user = wp_get_current_user();
    
    if (in_array('nhan_vien_kho', $current_user->roles) || 
        in_array('quan_ly_kho', $current_user->roles)) {
        
        // Xóa một số items không cần thiết
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->remove_node('about');
        $wp_admin_bar->remove_node('wporg');
        $wp_admin_bar->remove_node('documentation');
        $wp_admin_bar->remove_node('support-forums');
        $wp_admin_bar->remove_node('feedback');
        $wp_admin_bar->remove_node('themes');
        $wp_admin_bar->remove_node('widgets');
        $wp_admin_bar->remove_node('menus');
        
        // Thêm shortcuts tùy chỉnh
        if (in_array('nhan_vien_kho', $current_user->roles)) {
            $wp_admin_bar->add_node(array(
                'id'    => 'warehouse_menu',
                'title' => '📦 Kho hàng',
                'href'  => admin_url('admin.php?page=warehouse-management'),
            ));
        }
        
        if (in_array('quan_ly_kho', $current_user->roles)) {
            $wp_admin_bar->add_node(array(
                'id'    => 'sales_menu', 
                'title' => '📊 Bán hàng',
                'href'  => admin_url('admin.php?page=sales-management'),
            ));
        }
    }
}
add_action('admin_bar_menu', 'customize_admin_bar_by_role', 999);

/**
 * Thêm custom columns cho Users table
 */
function add_user_role_column($columns) {
    $columns['user_role'] = 'Vai trò';
    return $columns;
}
add_filter('manage_users_columns', 'add_user_role_column');

function show_user_role_column($value, $column_name, $user_id) {
    if ($column_name == 'user_role') {
        $user = get_userdata($user_id);
        $roles = $user->roles;
        
        $role_names = array();
        foreach ($roles as $role) {
            $role_obj = get_role($role);
            switch ($role) {
                case 'nhan_vien_kho':
                    $role_names[] = '📦 Nhân viên kho';
                    break;
                case 'quan_ly_kho':
                    $role_names[] = '📊 Quản lý kho';
                    break;
                case 'administrator':
                    $role_names[] = '👑 Quản trị viên';
                    break;
                default:
                    $role_names[] = ucfirst($role);
            }
        }
        
        return implode(', ', $role_names);
    }
    return $value;
}
add_action('manage_users_custom_column', 'show_user_role_column', 10, 3);

/**
 * Xóa roles khi deactivate (tùy chọn)
 */
function remove_custom_roles() {
    remove_role('nhan_vien_kho');
    remove_role('quan_ly_kho');
}
// Uncomment dòng dưới nếu muốn xóa roles khi deactivate
// register_deactivation_hook(__FILE__, 'remove_custom_roles');

/**
 * Debug function - Hiển thị capabilities của user hiện tại
 */
function debug_user_capabilities() {
    if (current_user_can('manage_options') && isset($_GET['debug_caps'])) {
        $current_user = wp_get_current_user();
        echo '<pre>';
        echo 'User: ' . $current_user->user_login . "\n";
        echo 'Roles: ' . implode(', ', $current_user->roles) . "\n";
        echo "Capabilities:\n";
        foreach ($current_user->allcaps as $cap => $granted) {
            if ($granted) {
                echo "- $cap\n";
            }
        }
        echo '</pre>';
    }
}
add_action('wp_footer', 'debug_user_capabilities');
add_action('admin_footer', 'debug_user_capabilities');