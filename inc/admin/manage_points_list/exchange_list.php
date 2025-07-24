<?php

function gpt_render_exchange_list_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_EXCHANGE_CODE_FOR_GIFT;

    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY time DESC");
    ?>
    
    <div class="bg_wrap">
        <h1>Danh sách khách hàng đổi điểm</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>SĐT</th>
                    <th>Sản phẩm</th>
                    <th>Điểm đổi</th>
                    <th>Cửa hàng</th>
                    <th>Người thực hiện</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo $row->phone; ?></td>
                        <td><?php echo $row->product; ?></td>
                        <td><?php echo $row->points; ?></td>
                        <td><?php echo $row->store_name; ?></td>
                        <td><?php echo $row->user_name; ?></td>
                        <td><?php echo $row->status; ?></td>
                        <td><?php echo $row->time; ?></td>
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
