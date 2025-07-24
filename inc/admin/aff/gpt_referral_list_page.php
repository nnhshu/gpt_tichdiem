<?php

function gpt_referral_list_page() {
    global $wpdb;
    $table_logs = BIZGPT_PLUGIN_WP_LOGS;

    $where = "WHERE (phone_referrer IS NOT NULL OR referrer_name IS NOT NULL)";
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = sanitize_text_field($_GET['start_date']);
        $end_date = sanitize_text_field($_GET['end_date']);
        $where .= " AND DATE(time) BETWEEN '$start_date' AND '$end_date'";
    } elseif (!empty($_GET['date'])) {
        $date = sanitize_text_field($_GET['date']);
        $where .= " AND DATE(time) = '$date'";
    }
    
    if (!empty($_GET['phone_number'])) {
        $phone_number = esc_sql($_GET['phone_number']);
        $where .= " AND phone_referrer LIKE '%$phone_number%'";
    }

    $results = $wpdb->get_results("
        SELECT id, customer_name, phone_number, referrer_name, phone_referrer, created_at, aff_by_store_id
        FROM $table_logs
        $where
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    ?>
    <div class="bg_wrap">
        <h1>Người được giới thiệu</h1>
        <hr>
        <div class="ux-row">
            <form method="get" class="row form-row" style="align-items: flex-end; width: 100%;">
                <input type="hidden" name="page" value="gpt-referred-person" />
                <div class="col large-2">
                    <label for="start_date">Từ ngày:</label>
                    <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>" />
                </div>
                <div class="col large-2">
                    <label for="end_date">Đến ngày:</label>
                    <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>" />
                </div>
                <div class="col large-2">
                    <label for="phone_number">Số điện thoại người giới thiệu:</label>
                    <input type="text" name="phone_number" value="<?php echo esc_attr($_GET['phone_number'] ?? ''); ?>" />
                </div>
                <div class="col large-1">
                    <div class="d-flex gap-1">
                        <input type="submit" class="button button-primary" value="Lọc" />
                        <a href="admin.php?page=gpt-referred-person" class="button button-danger">Reset Bộ Lọc</a>
                    </div>
                </div>
            </form>
        </div>
        <hr>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>[Người giới thiệu] - [Số điện thoại]</th>
                    <th>Chi nhánh</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($results as $row) :
                    $store = getStorebyId($row->aff_by_store_id);
                    $store_name = $store ? esc_html($store) : 'N/A';
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo esc_html($row->customer_name); ?></td>
                        <td><?php echo esc_html($row->phone_number); ?></td>
                        <?php if($row->referrer_name || $row->phone_referrer): ?>
                        <td><?php echo esc_html($row->referrer_name . ' - [' . $row->phone_referrer . ']'); ?></td>
                        <?php else: ?>
                        <td></td>
                        <?php endif; ?>
                        <td><?php echo $store_name; ?></td>
                        <td><?php echo esc_html($row->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
    <?php
}

function getStorebyId($store_id) {
    global $wpdb;

    $store_id = intval($store_id);

    $table_name = BIZGPT_PLUGIN_WP_STORE_LIST;

    $store = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $table_name WHERE id = %d
    ", $store_id));

    if ($store) {
        return $store->store_name;
    } else {
        return null;
    }
}



