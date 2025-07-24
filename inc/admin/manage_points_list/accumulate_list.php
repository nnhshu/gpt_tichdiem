<?php

function gpt_customer_list_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_LOGS;
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 100");
    ?>
    
    <div class="bg_wrap">
        <h1>Danh sách khách hàng tích điểm</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Số điện thoại</th>
                    <th>Mã cào</th>
                    <th>Chi nhánh</th>
                    <th>Vị trí khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Số điểm</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->customer_name; ?></td>
                        <td><?php echo $row->phone_number; ?></td>
                        <td><code><?php echo $row->barcode; ?></code></td>
                        <td><?php echo $row->store; ?></td>
                        <td><?php echo $row->point_location; ?></td>
                        <td><?php echo $row->product; ?></td>
                        <td><?php echo $row->point_change; ?></td>
                        <td><?php echo date("Y-m-d h:i:sa", strtotime($row->created_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align:center; font-style: italic;">Không có dữ liệu phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
