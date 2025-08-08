<?php

register_activation_hook(__FILE__, 'gpt_plugin_activate');
#add_action('admin_init', 'gpt_plugin_activate');
// update_barcode_table_columns();
function gpt_plugin_activate() {
    gpt_create_or_update_tables();
    gpt_create_ranking_table();
    gpt_create_gpt_product_orders_table();
    gpt_create_gpt_products_sold_table();
    gpt_create_sales_channels_table();
    gpt_create_store_tables();
    gpt_create_employee_tables();
    gpt_create_exchange_table();
    gpt_create_table_box();
    gpt_create_gpt_affiliate_logs_table();
    create_wp_order_refund_table();
    create_wp_product_lot_table();
    // update_barcode_table_columns();
    // update_existing_log_table_for_affiliate();
    update_option('bizgpt_plugin_db_version', BIZGPT_PLUGIN_DB_VERSION);
}

function update_barcode_table_columns() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_BARCODE;
    $columns_to_add = [
        'distributor' => "ALTER TABLE $table_name ADD COLUMN distributor CHAR(4) DEFAULT '' AFTER channel",
        'lot' => "ALTER TABLE $table_name ADD COLUMN lot CHAR(4) DEFAULT '' AFTER distributor",
    ];

    foreach ($columns_to_add as $column => $sql) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
        if (empty($column_exists)) {
            $wpdb->query($sql);
            error_log("✅ Added column $column to $table_name");
        } else {
            error_log("ℹ️ Column $column already exists in $table_name");
        }
    }
}

// function update_existing_log_table_for_affiliate() {
//     global $wpdb;
//     $table_name = BIZGPT_PLUGIN_WP_LOGS;
    
//     $columns_to_add = [
//         'phone_referrer' => "ALTER TABLE $table_name ADD COLUMN phone_referrer VARCHAR(20) NULL AFTER product_name",
//         'referrer_name' => "ALTER TABLE $table_name ADD COLUMN referrer_name VARCHAR(255) NULL AFTER phone_referrer", 
//         'is_affiliate_reward' => "ALTER TABLE $table_name ADD COLUMN is_affiliate_reward TINYINT(1) DEFAULT 0 AFTER referrer_name",
//         'user_status' => "ALTER TABLE $table_name ADD COLUMN user_status VARCHAR(255) NULL AFTER is_affiliate_reward",
//         'note_status' => "ALTER TABLE $table_name ADD COLUMN note_status VARCHAR(255) NULL AFTER user_status",
//         'aff_by_store_id' => "ALTER TABLE $table_name ADD COLUMN aff_by_store_id INT(10) NULL AFTER note_status",
//         'aff_by_employee_code' => "ALTER TABLE $table_name ADD COLUMN aff_by_employee_code INT(10) NULL AFTER aff_by_store_id",
//     ];
    
//     foreach ($columns_to_add as $column => $sql) {
//         $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
//         if (empty($column_exists)) {
//             $wpdb->query($sql);
//             error_log("Added column $column to $table_name");
//         } else {
//             error_log("Column $column already exists in $table_name");
//         }
//     }
// }

function create_wp_product_lot_table() {
    global $wpdb;
    
    $table_name = BIZGPT_PLUGIN_WP_PRODUCT_LOT;
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        lot_name varchar(255) NOT NULL,
        product_id varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_wp_order_refund_table() {
    global $wpdb;

    $table_name = BIZGPT_PLUGIN_WP_REFUND_ORDER;

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        refund_id VARCHAR(255) NOT NULL,
        user VARCHAR(255) NOT NULL,
        list_barcode TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        refund_time DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function gpt_create_gpt_affiliate_logs_table() {
    global $wpdb;

    $table_name = BIZGPT_PLUGIN_WP_AFFILIATE_LOGS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        type VARCHAR(20),
        referrer VARCHAR(255),
        referrer_phone VARCHAR(255),
        referred_phone VARCHAR(255),
        points_rewarded INT,
        note LONGTEXT DEFAULT '',
        source varchar(50),
        store_id VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function gpt_create_distributors_table() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_DISTRIBUTORS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS  $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        title VARCHAR(255),
        channel_id INT(20),
        employee_id INT(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function gpt_create_sales_channels_table() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_CHANNELS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS  $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        title VARCHAR(255),
        channel_code VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function gpt_create_gpt_product_orders_table() {
    global $wpdb;

    $table_name = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        barcode LONGTEXT NOT NULL,
        order_id INT NOT NULL,
        province VARCHAR(255),
        channel VARCHAR(255),
        type VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function gpt_create_gpt_products_sold_table() {
    global $wpdb;

    $table_name = BIZGPT_PLUGIN_WP_ORDER_PRODUCTS_SELL_OUT;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        barcode LONGTEXT NOT NULL,
        order_id INT NOT NULL,
        province VARCHAR(255),
        channel VARCHAR(255),
        type VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function gpt_create_exchange_table() {
    global $wpdb;

    $table_name = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `phone` varchar(255) NOT NULL,
        `store_name` varchar(255) NOT NULL,
        `client_id` varchar(255) DEFAULT NULL,
        `product` varchar(255) NOT NULL,
        `points` int(11) NOT NULL,
        `remaining_points` int(11) NOT NULL,
        `status` enum('pending', 'success') NOT NULL DEFAULT 'pending',
        `user_name` varchar(255) NOT NULL,
        `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (`id`),
        KEY `idx_phone` (`phone`),
        KEY `idx_client_id` (`client_id`),
        KEY `idx_status` (`status`),
        KEY `idx_time` (`time`)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function gpt_create_store_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $store_table = BIZGPT_PLUGIN_WP_STORE_LIST;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql_store = "CREATE TABLE IF NOT EXISTS  $store_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        store_name varchar(255) NOT NULL,
        address text NOT NULL,
        image_url varchar(255) DEFAULT '',
        channel_id mediumint(9) NOT NULL,
        phone_number VARCHAR(20),
        distributor_id INT(20),
        employee_id INT(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_store);
}

function gpt_create_employee_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $employee_table = BIZGPT_PLUGIN_WP_EMPLOYEES;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql_employee = "CREATE TABLE IF NOT EXISTS  $employee_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(50) NOT NULL,
        full_name varchar(255) NOT NULL,
        store_id mediumint(9) NOT NULL,
        channel_id mediumint(9) NOT NULL,
        point int(11) DEFAULT 0,
        image_url varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql_employee);
}

function gpt_create_table_box() {
    global $wpdb;
    $table_name = BIZGPT_PLUGIN_WP_BOX_MANAGER;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        barcode VARCHAR(255) NOT NULL,
        barcode_check VARCHAR(255) DEFAULT '',
        status VARCHAR(50) DEFAULT 'pending',
        province VARCHAR(100) DEFAULT '',
        channel VARCHAR(100) DEFAULT '',
        session VARCHAR(50) DEFAULT '',
        list_barcode LONGTEXT DEFAULT '',
        qr_code_url TEXT,
        barcode_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY barcode (barcode)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}


function gpt_create_or_update_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table1 = BIZGPT_PLUGIN_WP_BARCODE;
    $table2 = BIZGPT_PLUGIN_WP_LOGS;
    $table3 = BIZGPT_PLUGIN_WP_CUSTOMERS;
    $table4 = BIZGPT_PLUGIN_WP_LOCATION_WARNINGS;
    $table5 = BIZGPT_PLUGIN_WP_AFFILIATE_STATS;
    $table6 = BIZGPT_PLUGIN_WP_SAVE_USERS;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table1'") != $table1) {
        $sql1 = "CREATE TABLE IF NOT EXISTS  $table1 (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            barcode VARCHAR(255) NOT NULL,
            barcode_check VARCHAR(255) NOT NULL,
            token CHAR(4) DEFAULT '',
            point INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            product VARCHAR(255),
            province VARCHAR(255),
            channel VARCHAR(255),
            distributor CHAR(4) DEFAULT '',
            lot VARCHAR(255) DEFAULT '',
            product_date VARCHAR(255) DEFAULT NULL,
            product_id VARCHAR(255),
            session INT(11),
            qr_code_url TEXT DEFAULT NULL,
            barcode_url TEXT DEFAULT NULL,
            order_by_product_id TEXT DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql1);
    #}

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table2'") != $table2) {
        $sql2 = "CREATE TABLE IF NOT EXISTS $table2 (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT,
            client_id varchar(255) NULL,
            barcode VARCHAR(255) NOT NULL,
            session_code INT(11),
            barcode_status VARCHAR(50) DEFAULT 'unused',
            customer_name VARCHAR(255),
            phone_number VARCHAR(20),
            point_change INT DEFAULT 0,
            product VARCHAR(255),
            store VARCHAR(255),
            point_location VARCHAR(255),
            address varchar(255),
            province VARCHAR(255),
            ward VARCHAR(255),
            transaction_type VARCHAR(50) DEFAULT 'tich_diem',
            product_name VARCHAR(255) NULL,
            phone_referrer VARCHAR(20) NULL,
            referrer_name VARCHAR(255) NULL,
            is_affiliate_reward TINYINT(1) DEFAULT 0,
            u_status VARCHAR(255) DEFAULT '',
            note_status VARCHAR(255) NULL,
            aff_by_store_id INT(10) NULL,
            aff_by_employee_code INT(10) NULL,
            purchase_channel VARCHAR(255) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql2);
    #}

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table3'") != $table3) {
        $sql3 = "CREATE TABLE IF NOT EXISTS $table3 (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT NULL,
                client_id VARCHAR(255) NULL,
                phone VARCHAR(20) NOT NULL,
                customer_name VARCHAR(255) NULL,
                total_point INT DEFAULT 0,
                points_changed INT DEFAULT 0,
                remaining_points INT DEFAULT 0,
                last_points_collected DATETIME NULL,
                last_point_changed DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY (phone)
            ) $charset_collate;";

        dbDelta($sql3);
   # }

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table4'") != $table4) {
        $sql4 = "CREATE TABLE IF NOT EXISTS $table4 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            barcode varchar(255) NOT NULL,
            customer_name varchar(255) NOT NULL,
            phone_number varchar(20) NOT NULL,
            province_expect varchar(255) NOT NULL,
            province_actual varchar(255) NOT NULL,
            full_address text,
            product varchar(255),
            store varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'pending',
            note text,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql4);
    #}

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table5'") != $table5) {
        $sql5 = "CREATE TABLE IF NOT EXISTS $table5 (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_phone varchar(20) NOT NULL,
            referrer_name varchar(255),
            total_referrals int(11) DEFAULT 0,
            total_points_earned int(11) DEFAULT 0,
            last_referral_date datetime,
            first_referral_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY referrer_phone (referrer_phone),
            INDEX idx_total_points (total_points_earned),
            INDEX idx_total_referrals (total_referrals)
        ) $charset_collate;";

        dbDelta($sql5);
    #}

    #if ($wpdb->get_var("SHOW TABLES LIKE '$table6'") != $table6) {
        $sql6 = "CREATE TABLE IF NOT EXISTS $table6 (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            phone_number varchar(20) NOT NULL,
            full_name varchar(255) DEFAULT '',
            email varchar(100) DEFAULT '',
            address text DEFAULT '',
            province varchar(100) DEFAULT '',
            district varchar(100) DEFAULT '',
            ward varchar(100) DEFAULT '',
            total_points int(11) DEFAULT 0,
            earned_points int(11) DEFAULT 0,
            redeemed_points int(11) DEFAULT 0,
            affiliate_points int(11) DEFAULT 0,
            total_transactions int(11) DEFAULT 0,
            total_referrals int(11) DEFAULT 0,
            first_transaction_date datetime DEFAULT NULL,
            last_transaction_date datetime DEFAULT NULL,
            last_activity_date datetime DEFAULT NULL,
            user_status enum('active', 'inactive', 'blocked') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY phone_number (phone_number),
            INDEX idx_total_points (total_points),
            INDEX idx_earned_points (earned_points),
            INDEX idx_province (province),
            INDEX idx_status (user_status),
            INDEX idx_last_activity (last_activity_date)
        ) $charset_collate;";

        dbDelta($sql6);
    #}
}

function gpt_create_ranking_table() {
    global $wpdb;
    
    $table_name = BIZGPT_PLUGIN_WP_RANKINGS;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE  IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        min_points int(11) NOT NULL DEFAULT 0,
        icon_rank varchar(255) DEFAULT '',
        sort_order int(11) DEFAULT 0,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY min_points (min_points),
        KEY sort_order (sort_order)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $sample_data = array(
        array('name' => 'Đồng', 'slug' => 'dong', 'min_points' => 0, 'icon_rank' => 'dashicons-awards', 'sort_order' => 1),
        array('name' => 'Bạc', 'slug' => 'bac', 'min_points' => 100, 'icon_rank' => 'dashicons-star-filled', 'sort_order' => 2),
        array('name' => 'Vàng', 'slug' => 'vang', 'min_points' => 500, 'icon_rank' => 'dashicons-thumbs-up', 'sort_order' => 3),
        array('name' => 'Bạch Kim', 'slug' => 'bach-kim', 'min_points' => 1000, 'icon_rank' => 'dashicons-diamond', 'sort_order' => 4),
        array('name' => 'Kim Cương', 'slug' => 'kim-cuong', 'min_points' => 2000, 'icon_rank' => 'dashicons-superhero-alt', 'sort_order' => 5),
    );
    
    foreach ($sample_data as $data) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE slug = %s", 
            $data['slug']
        ));
        
        if ($exists == 0) {
            $wpdb->insert($table_name, $data);
        }
    }
}
