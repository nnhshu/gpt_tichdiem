<?php

function gpt_customer_list_page() {
    global $wpdb;
    $table = BIZGPT_PLUGIN_WP_LOGS;
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 100");
    ?>
    <style>
        :root{
            --primary-color-admin: #164eaf;
        }
        .list_code_wrap{
            margin-top: 30px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .list_code_wrap h1 {
            margin-top: 0;
            font-size: 20px;
            color: var(--primary-color-admin);
        }

        .ux-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid var(--primary-color-admin);
            border-bottom: 1px solid var(--primary-color-admin);
            padding: 16px 0px;
        }

        .ux-row .col {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ux-row select,
        .ux-row input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .ux-row button {
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: var(--primary-color-admin) !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_export_excel{
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: #22c55e !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_delete_selected{
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            background-color: #ef4444 !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            width: max-content !important;
            border: 0px !important;
        }

        #gpt_macao_table th,
        #gpt_macao_table td {
            padding: 12px 15px;
            text-align: left;
        }

        #gpt_macao_table tr:hover {
            background: #f9f9f9;
        }

        .gpt-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #fff;
        }

        .gpt-badge-success {
            background-color: #22c55e;
        }

        .gpt-badge-danger {
            background-color: #ef4444;
        }

        .tablenav-pages {
            margin-top: 20px;
        }

        .updates-table td input, 
        .widefat tfoot td input, 
        .widefat th input, 
        .widefat thead td input {
            margin: 0 !important;
        }

    </style>
    <div class="list_code_wrap">
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
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->customer_name; ?></td>
                        <td><?php echo $row->phone_number; ?></td>
                        <td><?php echo $row->barcode; ?></td>
                        <td><?php echo $row->store; ?></td>
                        <td><?php echo $row->point_location; ?></td>
                        <td><?php echo $row->product; ?></td>
                        <td><?php echo $row->point_change; ?></td>
                        <td><?php echo date("Y-m-d h:i:sa", strtotime($row->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
