<?php

function gpt_marketing_config_page() {
    ?>
    <div class="wrap">
        <h1>Cấu hình quản trị tiếp thị</h1>
        <div class="notice notice-warning">
            <p>Đây là trang cấu hình quản trị tiếp thị. Nội dung sẽ được phát triển.</p>
        </div>
        <!-- Form cấu hình quản trị tiếp thị -->
        <form method="post" action="options.php">
            <?php
            settings_fields('gpt_marketing_settings');
            do_settings_sections('gpt_marketing_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Tỷ lệ hoa hồng giới thiệu</th>
                    <td>
                        <input type="number" name="gpt_referral_commission" 
                               value="<?php echo esc_attr(get_option('gpt_referral_commission', 10)); ?>" 
                               min="0" max="100" step="0.1" />
                        <p class="description">Tỷ lệ hoa hồng cho người giới thiệu (đơn vị: %)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Số lượng giới thiệu tối đa</th>
                    <td>
                        <input type="number" name="gpt_max_referrals" 
                               value="<?php echo esc_attr(get_option('gpt_max_referrals', 50)); ?>" 
                               min="1" />
                        <p class="description">Số lượng tối đa người có thể giới thiệu</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Kích hoạt hệ thống giới thiệu</th>
                    <td>
                        <input type="checkbox" name="gpt_enable_referral" 
                               value="1" <?php checked(1, get_option('gpt_enable_referral', 1)); ?> />
                        <p class="description">Bật/tắt hệ thống giới thiệu</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}