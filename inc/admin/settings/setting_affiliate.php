<?php

function gpt_affiliate_setting_page() {
    if (isset($_POST['submit_affiliate_config']) && wp_verify_nonce($_POST['affiliate_config_nonce'], 'save_affiliate_config')) {
                
        if (isset($_POST['affiliate_enabled'])) {
            update_option('affiliate_enabled', 1);
        } else {
            update_option('affiliate_enabled', 0);
        }
        
        if (isset($_POST['affiliate_points_per_referral'])) {
            $points = intval($_POST['affiliate_points_per_referral']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_points_per_referral', $points);
        }

        if (isset($_POST['affiliate_percent_per_referral'])) {
            $points = intval($_POST['affiliate_percent_per_referral']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_percent_per_referral', $points);
        }

        if (isset($_POST['affiliate_percent_per_new_user'])) {
            $points = intval($_POST['affiliate_percent_per_new_user']);
            $points = max(0, min(1000, $points));
            update_option('affiliate_percent_per_new_user', $points);
        }
        
        if (isset($_POST['affiliate_min_points_required'])) {
            $min_points = intval($_POST['affiliate_min_points_required']);
            $min_points = max(0, min(100, $min_points));
            update_option('affiliate_min_points_required', $min_points);
        }
        
        if (isset($_POST['affiliate_notification_message'])) {
            $message = sanitize_textarea_field($_POST['affiliate_notification_message']);
            update_option('affiliate_notification_message', $message);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Thành công!</strong> Đã lưu cấu hình Affiliate.</p>';
            echo '</div>';
        });
        
    }
    ?>

    <div class="gpt-tich-diem-form bg-grey">
        <h2>Cấu hình tích điểm và Chương trình Affiliate</h2>
        <div class="affiliate-status" style="padding: 10px; border-left: 4px solid #0073aa; background: #fff;">
            <strong>Trạng thái hiện tại:</strong>
            <?php if (get_option('affiliate_enabled', 0)): ?>
                <span style="color: #00a32a; font-weight: bold;">✅ ĐANG HOẠT ĐỘNG</span>
                <br><small>Người giới thiệu sẽ nhận <strong><?php echo get_option('affiliate_points_per_referral', 10); ?> điểm</strong> mỗi lần có người tích điểm thành công.</small>
            <?php else: ?>
                <span style="color: #d63638; font-weight: bold;">❌ TẮT</span>
                <br><small>Chức năng affiliate hiện đang bị tắt.</small>
            <?php endif; ?>
        </div>
        <hr>
        <form method="post" action="">
            <?php wp_nonce_field('save_affiliate_config', 'affiliate_config_nonce'); ?>
            <div class="form-group">
                <label for="affiliate_enabled">
                    <input type="checkbox" id="affiliate_enabled" name="affiliate_enabled" value="1" 
                        <?php checked(get_option('affiliate_enabled', 0), 1); ?>>
                    Bật chức năng người giới thiệu
                </label>
                <p class="description">Cho phép khách hàng nhận điểm khi giới thiệu người khác tích điểm</p>
            </div>
            <div class="form-group">
                <label for="affiliate_points_per_referral">Điểm thưởng mỗi lần giới thiệu</label>
                <input type="number" id="affiliate_points_per_referral" name="affiliate_points_per_referral" 
                    value="<?php echo esc_attr(get_option('affiliate_points_per_referral', 10)); ?>" 
                    min="0" max="1000" class="regular-text">
                <p class="description">Số điểm người giới thiệu nhận được mỗi khi có người tích điểm thành công</p>
            </div>
            <div class="form-group">
                <label for="affiliate_percent_per_referral">% điểm thưởng người giới thiệu nhận được</label>
                <input type="number" id="affiliate_percent_per_referral" name="affiliate_percent_per_referral" 
                    value="<?php echo esc_attr(get_option('affiliate_percent_per_referral', 10)); ?>" 
                    min="0" max="1000" class="regular-text">
                <p class="description">% điểm người giới thiệu nhận được khi có người đầu tiên tích điểm thành công</p>
            </div>
            <div class="form-group">
                <label for="affiliate_min_points_required">Điểm tối thiểu để nhận thưởng</label>
                <input type="number" id="affiliate_min_points_required" name="affiliate_min_points_required" 
                    value="<?php echo esc_attr(get_option('affiliate_min_points_required', 1)); ?>" 
                    min="0" max="100" class="regular-text">
                <p class="description">Người được giới thiệu phải tích ít nhất bao nhiêu điểm thì người giới thiệu mới nhận thưởng</p>
            </div>
            <div class="form-group">
                <label for="affiliate_percent_per_new_user">% điểm thưởng khách hàng lần đầu tiên tích điểm nhận được</label>
                <input type="number" id="affiliate_percent_per_new_user" name="affiliate_percent_per_new_user" 
                    value="<?php echo esc_attr(get_option('affiliate_percent_per_new_user', 10)); ?>" 
                    min="0" max="1000" class="regular-text">
                <p class="description">% điểm khách hàng tích điểm lần đầu tiên thành công</p>
            </div>
            <div class="form-group">
                <label for="affiliate_notification_message">Tin nhắn thông báo</label>
                <textarea id="affiliate_notification_message" name="affiliate_notification_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('affiliate_notification_message', '🎉 Chúc mừng! Bạn vừa nhận được {points} điểm từ việc giới thiệu {customer_name} tích điểm. Tổng điểm hiện tại: {total_points}')); ?></textarea>
                <p class="description">Tin nhắn gửi cho người giới thiệu. Sử dụng: <code>{points}</code>, <code>{customer_name}</code>, <code>{total_points}</code></p>
            </div>
            <div class="affiliate-config-actions">
                <div class="submit">
                    <input type="submit" name="submit_affiliate_config" class="button-primary" value="💾 Lưu cấu hình Affiliate">
                </div>
            </div>
            <hr>
            <div class="affiliate-config-actions">
                <label for="">Chức năng khác</label>
                <div class="submit">
                    <input type="button" class="button-primary" value="🔄 Reset về mặc định" onclick="resetAffiliateConfig()">
                    <input type="button" class="button-primary" value="📊 Xem báo cáo" onclick="window.open('<?php echo admin_url('admin.php?page=gpt-affiliate-report'); ?>', '_blank')">
                </div>
            </div>
        </form>
        <script>
            function resetAffiliateConfig() {
                if (confirm('Bạn có chắc muốn reset về cấu hình mặc định không?')) {
                    document.getElementById('affiliate_enabled').checked = false;
                    document.getElementById('affiliate_points_per_referral').value = '10';
                    document.getElementById('affiliate_min_points_required').value = '1';
                    document.getElementById('affiliate_notification_message').value = '🎉 Chúc mừng! Bạn vừa nhận được {points} điểm từ việc giới thiệu {customer_name} tích điểm. Tổng điểm hiện tại: {total_points}';
                    highlightChangedFields();
                }
            }

            function highlightChangedFields() {
                const fields = ['affiliate_points_per_referral', 'affiliate_min_points_required', 'affiliate_notification_message'];
                
                fields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.style.backgroundColor = '#fff3cd';
                        field.style.border = '2px solid #ffc107';
                        setTimeout(() => {
                            field.style.backgroundColor = '';
                            field.style.border = '';
                        }, 3000);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const pointsInput = document.getElementById('affiliate_points_per_referral');
                const minPointsInput = document.getElementById('affiliate_min_points_required');
                const enabledCheckbox = document.getElementById('affiliate_enabled');

                if (pointsInput) {
                    pointsInput.addEventListener('input', function() {
                        const value = parseInt(this.value);
                        if (value < 0) this.value = 0;
                        if (value > 1000) this.value = 1000;
                        updatePreview();
                    });
                }
                
                if (minPointsInput) {
                    minPointsInput.addEventListener('input', function() {
                        const value = parseInt(this.value);
                        if (value < 0) this.value = 0;
                        if (value > 100) this.value = 100;
                    });
                }
                
                if (enabledCheckbox) {
                    enabledCheckbox.addEventListener('change', function() {
                        updatePreview();
                    });
                }
                
                function updatePreview() {
                    const isEnabled = enabledCheckbox.checked;
                    const points = pointsInput.value || 10;
                    
                    const statusDiv = document.querySelector('.affiliate-status');
                    if (statusDiv) {
                        if (isEnabled) {
                            statusDiv.innerHTML = `
                                <strong>Trạng thái hiện tại:</strong>
                                <span style="color: #00a32a; font-weight: bold;">✅ ĐANG HOẠT ĐỘNG</span>
                                <br><small>Người giới thiệu sẽ nhận <strong>${points} điểm</strong> mỗi lần có người tích điểm thành công.</small>
                            `;
                        } else {
                            statusDiv.innerHTML = `
                                <strong>Trạng thái hiện tại:</strong>
                                <span style="color: #d63638; font-weight: bold;">❌ TẮT</span>
                                <br><small>Chức năng affiliate hiện đang bị tắt.</small>
                            `;
                        }
                    }
                }
            });
        </script>
    </div>
    <?php
}

function gpt_setting_products_gift_page(){
    ?>
    <div class="gpt-tich-diem-form">
        <?php 
            if (function_exists('bizgpt_user_points_guide_display')) {
                bizgpt_user_points_guide_display();
            } else {
                echo '<p>Function bizgpt_user_points_guide_display() chưa được định nghĩa.</p>';
            }
        ?>
    </div>
    <?php
}