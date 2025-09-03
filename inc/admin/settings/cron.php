<?php
// Helper function for generating tokens

function create_code_generation_cron_post_type() {
    register_post_type('code_gen_cron', array(
        'labels' => array(
            'name' => 'Code Generation Jobs',
            'singular_name' => 'Code Generation Job',
            'add_new' => 'Add New Job',
            'add_new_item' => 'Add New Generation Job',
            'edit_item' => 'Edit Generation Job'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'tools.php',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-clock',
        'capabilities' => array(
            'create_posts' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
        )
    ));
}
add_action('init', 'create_code_generation_cron_post_type');

// Add Meta Boxes cho Cron Job
function add_code_gen_cron_meta_boxes() {
    add_meta_box(
        'code_gen_settings',
        'Generation Settings',
        'code_gen_settings_callback',
        'code_gen_cron',
        'normal',
        'high'
    );
    
    add_meta_box(
        'code_gen_progress',
        'Progress Status',
        'code_gen_progress_callback',
        'code_gen_cron',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_code_gen_cron_meta_boxes');

function code_gen_settings_callback($post) {
    wp_nonce_field('code_gen_settings_nonce', 'code_gen_settings_nonce_field');
    
    $job_type = get_post_meta($post->ID, 'job_type', true) ?: 'product_code';
    $total_quantity = get_post_meta($post->ID, 'total_quantity', true) ?: 100;
    $batch_size = get_post_meta($post->ID, 'batch_size', true) ?: get_code_gen_default_batch_size();
    $interval = get_post_meta($post->ID, 'interval', true) ?: get_code_gen_default_interval();
    $point = get_post_meta($post->ID, 'point', true) ?: '1';
    $product_id = get_post_meta($post->ID, 'product_id', true);
    $session = get_post_meta($post->ID, 'session', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th><label for="job_type">Job Type</label></th>
            <td>
                <select name="job_type" id="job_type" onchange="toggleJobTypeFields()">
                    <option value="product_code" <?php selected($job_type, 'product_code'); ?>>Product Code Generation</option>
                    <option value="box_code" <?php selected($job_type, 'box_code'); ?>>Box Code Generation</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="total_quantity">Total Quantity</label></th>
            <td><input type="number" name="total_quantity" id="total_quantity" value="<?php echo $total_quantity; ?>" min="1" /></td>
        </tr>
        <tr>
            <th><label for="batch_size">Batch Size</label></th>
            <td><input type="number" name="batch_size" id="batch_size" value="<?php echo $batch_size; ?>" min="1" max="200" /></td>
        </tr>
        <tr>
            <th><label for="interval">Interval (seconds)</label></th>
            <td><input type="number" name="interval" id="interval" value="<?php echo $interval; ?>" min="10" /></td>
        </tr>
        <tr class="product-code-field">
            <th><label for="point">Points</label></th>
            <td>
                <select name="point" id="point">
                    <option value="1" <?php selected($point, '1'); ?>>1 Point</option>
                    <option value="2" <?php selected($point, '2'); ?>>2 Points</option>
                </select>
            </td>
        </tr>
        <tr class="product-code-field">
            <th><label for="product_id">Product ID</label></th>
            <td><input type="text" name="product_id" id="product_id" value="<?php echo $product_id; ?>" maxlength="2" /></td>
        </tr>
        <tr>
            <th><label for="session">Session</label></th>
            <td><input type="text" name="session" id="session" value="<?php echo $session; ?>" /></td>
        </tr>
    </table>
    
    <script>
    function toggleJobTypeFields() {
        var jobType = document.getElementById('job_type').value;
        var productFields = document.querySelectorAll('.product-code-field');
        var body = document.body;
        
        if (jobType === 'box_code') {
            productFields.forEach(function(field) {
                field.style.display = 'none';
            });
            body.classList.add('box-code-field');
            body.classList.remove('product-code-field');
        } else {
            productFields.forEach(function(field) {
                field.style.display = 'table-row';
            });
            body.classList.add('product-code-field');
            body.classList.remove('box-code-field');
        }
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleJobTypeFields();
    });
    </script>
    <?php
}

function code_gen_progress_callback($post) {
    $status = get_post_meta($post->ID, 'cron_status', true) ?: 'pending';
    $created = get_post_meta($post->ID, 'codes_created', true) ?: 0;
    $total = get_post_meta($post->ID, 'total_quantity', true) ?: 0;
    $last_run = get_post_meta($post->ID, 'last_run', true);
    $error_message = get_post_meta($post->ID, 'error_message', true);
    $start_time = get_post_meta($post->ID, 'start_time', true);
    $end_time = get_post_meta($post->ID, 'end_time', true);
    
    $percentage = $total > 0 ? round(($created / $total) * 100, 1) : 0;
    
    ?>
    <div class="misc-pub-section">
        <strong>Status:</strong> 
        <span class="status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
    </div>
    
    <div class="misc-pub-section">
        <strong>Progress:</strong> <?php echo $created; ?>/<?php echo $total; ?> (<?php echo $percentage; ?>%)
        <div style="background:#f1f1f1; height:10px; border-radius:5px; margin-top:5px;">
            <div style="width:<?php echo $percentage; ?>%; height:100%; background:#0073aa; border-radius:5px;"></div>
        </div>
    </div>
    
    <?php if ($last_run): ?>
    <div class="misc-pub-section">
        <strong>Last Run:</strong><br>
        <?php echo date('Y-m-d H:i:s', strtotime($last_run)); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($start_time): ?>
    <div class="misc-pub-section">
        <strong>Started:</strong><br>
        <?php echo date('Y-m-d H:i:s', strtotime($start_time)); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($end_time): ?>
    <div class="misc-pub-section">
        <strong>Completed:</strong><br>
        <?php echo date('Y-m-d H:i:s', strtotime($end_time)); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="misc-pub-section" style="color:red;">
        <strong>Error:</strong><br>
        <?php echo esc_html($error_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($status === 'running'): ?>
    <div class="misc-pub-section">
        <button type="button" class="button button-secondary" onclick="stopCronJob(<?php echo $post->ID; ?>)">
            Stop Job
        </button>
    </div>
    <?php elseif ($status === 'pending' || $status === 'error'): ?>
    <div class="misc-pub-section">
        <button type="button" class="button button-primary" onclick="startCronJob(<?php echo $post->ID; ?>)">
            Start Job
        </button>
    </div>
    <?php elseif ($status === 'completed'): ?>
    <div class="misc-pub-section">
        <button type="button" class="button button-secondary" onclick="restartCronJob(<?php echo $post->ID; ?>)">
            Restart Job
        </button>
    </div>
    <?php endif; ?>
    
    <script>
    function startCronJob(postId) {
        if (confirm('Start this code generation job?')) {
            jQuery.post(ajaxurl, {
                action: 'start_code_gen_cron',
                post_id: postId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    }
    
    function stopCronJob(postId) {
        if (confirm('Stop this code generation job?')) {
            jQuery.post(ajaxurl, {
                action: 'stop_code_gen_cron',
                post_id: postId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    }
    
    function restartCronJob(postId) {
        if (confirm('Restart this code generation job? This will reset progress.')) {
            jQuery.post(ajaxurl, {
                action: 'restart_code_gen_cron',
                post_id: postId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    }
    </script>
    <?php
}

// Save Meta Fields
function save_code_gen_cron_meta($post_id) {
    if (!isset($_POST['code_gen_settings_nonce_field']) || 
        !wp_verify_nonce($_POST['code_gen_settings_nonce_field'], 'code_gen_settings_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['job_type', 'total_quantity', 'batch_size', 'interval', 'point', 'product_id', 'session'];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post_code_gen_cron', 'save_code_gen_cron_meta');

// Check if another cron job is running
function is_another_cron_running($exclude_post_id = 0, $job_type = null) {
    $meta_query = array(
        array(
            'key' => 'cron_status',
            'value' => 'running',
            'compare' => '='
        )
    );
    
    // If job_type is specified, only check for the same type
    if ($job_type) {
        $meta_query[] = array(
            'key' => 'job_type',
            'value' => $job_type,
            'compare' => '='
        );
    }
    
    $running_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'meta_query' => $meta_query,
        'numberposts' => -1,
        'exclude' => array($exclude_post_id)
    ));
    
    return !empty($running_jobs);
}

// AJAX Actions
add_action('wp_ajax_start_code_gen_cron', 'start_code_gen_cron_ajax');
function start_code_gen_cron_ajax() {
    $post_id = intval($_POST['post_id']);
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $job_type = get_post_meta($post_id, 'job_type', true) ?: 'product_code';
    
    // Check if another job of the same type is running
    if (is_another_cron_running($post_id, $job_type)) {
        $type_text = $job_type === 'box_code' ? 'box code generation' : 'product code generation';
        wp_send_json_error("Another {$type_text} job is already running. Please wait for it to complete.");
    }
    
    $interval = get_post_meta($post_id, 'interval', true) ?: 30;
    
    // Update session before starting
    if ($job_type === 'box_code') {
        $current_session = get_option('gpt_current_box_session', 0);
        $new_session = str_pad($current_session + 1, 2, '0', STR_PAD_LEFT);
        update_option('gpt_current_box_session', $current_session + 1);
    } else {
        $current_session = get_option('gpt_current_session', 0);
        $new_session = str_pad($current_session + 1, 2, '0', STR_PAD_LEFT);
        update_option('gpt_current_session', $current_session + 1);
    }
    
    update_post_meta($post_id, 'session', $new_session);
    
    // Schedule cron job
    $hook_name = 'code_gen_cron_' . $post_id;
    
    if (!wp_next_scheduled($hook_name)) {
        wp_schedule_event(time(), 'every_30_seconds', $hook_name, array($post_id));
        
        // Update status
        update_post_meta($post_id, 'cron_status', 'running');
        update_post_meta($post_id, 'start_time', current_time('mysql'));
        update_post_meta($post_id, 'codes_created', 0);
        update_post_meta($post_id, 'error_message', '');
        
        wp_send_json_success('Cron job started successfully');
    } else {
        wp_send_json_error('Cron job already running');
    }
}

add_action('wp_ajax_stop_code_gen_cron', 'stop_code_gen_cron_ajax');
function stop_code_gen_cron_ajax() {
    $post_id = intval($_POST['post_id']);
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $hook_name = 'code_gen_cron_' . $post_id;
    wp_clear_scheduled_hook($hook_name);
    
    update_post_meta($post_id, 'cron_status', 'stopped');
    
    wp_send_json_success('Cron job stopped');
}

add_action('wp_ajax_restart_code_gen_cron', 'restart_code_gen_cron_ajax');
function restart_code_gen_cron_ajax() {
    $post_id = intval($_POST['post_id']);
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    // Stop existing cron
    $hook_name = 'code_gen_cron_' . $post_id;
    wp_clear_scheduled_hook($hook_name);
    
    // Reset progress
    update_post_meta($post_id, 'cron_status', 'pending');
    update_post_meta($post_id, 'codes_created', 0);
    update_post_meta($post_id, 'error_message', '');
    update_post_meta($post_id, 'start_time', '');
    update_post_meta($post_id, 'end_time', '');
    
    wp_send_json_success('Job reset successfully');
}

// Custom Cron Schedules with 5-minute interval
function add_code_gen_cron_schedules($schedules) {
    $schedules['every_30_seconds'] = array(
        'interval' => 30,
        'display' => 'Every 30 Seconds'
    );
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => 'Every Minute'
    );
    $schedules['every_5_minutes'] = array(
        'interval' => 300,
        'display' => 'Every 5 Minutes'
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_code_gen_cron_schedules');

// Register Dynamic Cron Actions
function register_code_gen_cron_actions() {
    $cron_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'numberposts' => -1
    ));
    
    foreach ($cron_jobs as $job) {
        $hook_name = 'code_gen_cron_' . $job->ID;
        if (!has_action($hook_name)) {
            add_action($hook_name, 'process_code_generation_cron');
        }
    }
}
add_action('init', 'register_code_gen_cron_actions');

// Process Cron Job Function with enhanced cleanup
function process_code_generation_cron($post_id) {
    // Double check if job should still be running
    $status = get_post_meta($post_id, 'cron_status', true);
    if ($status !== 'running') {
        wp_clear_scheduled_hook('code_gen_cron_' . $post_id);
        error_log("Code Gen Cron #{$post_id}: Job status is {$status}, stopping cron");
        return;
    }
    
    $total_quantity = get_post_meta($post_id, 'total_quantity', true);
    $batch_size = get_post_meta($post_id, 'batch_size', true) ?: get_code_gen_default_batch_size();
    $codes_created = get_post_meta($post_id, 'codes_created', true) ?: 0;
    $job_type = get_post_meta($post_id, 'job_type', true) ?: 'product_code';
    
    // Check if completed - with extra safety checks
    if ($codes_created >= $total_quantity) {
        // Multiple cleanup attempts
        $hook_name = 'code_gen_cron_' . $post_id;
        
        // Clear all scheduled events for this hook
        wp_clear_scheduled_hook($hook_name);
        wp_unschedule_hook($hook_name);
        
        // Also clear by timestamp (more aggressive)
        $scheduled = wp_next_scheduled($hook_name, array($post_id));
        if ($scheduled) {
            wp_unschedule_event($scheduled, $hook_name, array($post_id));
        }
        
        // Clear any other variations
        wp_clear_scheduled_hook($hook_name, array($post_id));
        
        // Update status and end time
        update_post_meta($post_id, 'cron_status', 'completed');
        update_post_meta($post_id, 'end_time', current_time('mysql'));
        
        error_log("Code Gen Cron #{$post_id} COMPLETED: {$total_quantity} codes created - CRON CLEARED");
        
        // Force cleanup check
        force_cleanup_completed_cron($post_id);
        return;
    }
    
    // Safety check: if job has been running too long, stop it
    $start_time = get_post_meta($post_id, 'start_time', true);
    if ($start_time) {
        $start_timestamp = strtotime($start_time);
        $current_timestamp = current_time('timestamp');
        $running_hours = ($current_timestamp - $start_timestamp) / 3600;
        
        // If running more than 24 hours, force stop
        if ($running_hours > 24) {
            wp_clear_scheduled_hook('code_gen_cron_' . $post_id);
            update_post_meta($post_id, 'cron_status', 'error');
            update_post_meta($post_id, 'error_message', 'Job timeout after 24 hours');
            error_log("Code Gen Cron #{$post_id}: Forced stop after 24 hours");
            return;
        }
    }
    
    // Calculate current batch
    $remaining = $total_quantity - $codes_created;
    $current_batch = min($batch_size, $remaining);
    
    try {
        if ($job_type === 'box_code') {
            $result = create_box_codes_batch($post_id, $current_batch);
        } else {
            $result = create_product_codes_batch($post_id, $current_batch);
        }
        
        if ($result['success']) {
            $new_total = $codes_created + $result['created'];
            update_post_meta($post_id, 'codes_created', $new_total);
            update_post_meta($post_id, 'last_run', current_time('mysql'));
            
            // Log progress
            $progress = round(($new_total / $total_quantity) * 100, 2);
            $job_type_text = $job_type === 'box_code' ? 'box codes' : 'product codes';
            error_log("Code Gen Cron #{$post_id}: Created {$result['created']} {$job_type_text}. Total: {$new_total}/{$total_quantity} ({$progress}%)");
            
            // Check if we just completed
            if ($new_total >= $total_quantity) {
                // Immediate cleanup when reaching target
                $hook_name = 'code_gen_cron_' . $post_id;
                wp_clear_scheduled_hook($hook_name);
                wp_unschedule_hook($hook_name);
                
                update_post_meta($post_id, 'cron_status', 'completed');
                update_post_meta($post_id, 'end_time', current_time('mysql'));
                
                error_log("Code Gen Cron #{$post_id} JUST COMPLETED: {$new_total}/{$total_quantity} codes - IMMEDIATE CLEANUP");
                force_cleanup_completed_cron($post_id);
            }
        } else {
            throw new Exception($result['message']);
        }
        
    } catch (Exception $e) {
        error_log("Code Gen Cron #{$post_id} error: " . $e->getMessage());
        update_post_meta($post_id, 'cron_status', 'error');
        update_post_meta($post_id, 'error_message', $e->getMessage());
        
        // Stop cron on error
        wp_clear_scheduled_hook('code_gen_cron_' . $post_id);
        wp_unschedule_hook($hook_name);
    }
}

// Force cleanup for completed cron
function force_cleanup_completed_cron($post_id) {
    $hook_name = 'code_gen_cron_' . $post_id;
    
    // Get all cron jobs
    $crons = get_option('cron');
    if (is_array($crons)) {
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$hook_name])) {
                unset($crons[$timestamp][$hook_name]);
                // If no other jobs at this timestamp, remove the timestamp
                if (empty($crons[$timestamp])) {
                    unset($crons[$timestamp]);
                }
            }
        }
        update_option('cron', $crons);
    }
    
    error_log("Force cleanup completed for cron job #{$post_id}");
}

// Enhanced cleanup function that runs every 5 minutes
function enhanced_cron_cleanup() {
    // Clean up completed jobs that still have active crons
    $completed_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'cron_status',
                'value' => array('completed', 'error', 'stopped'),
                'compare' => 'IN'
            )
        ),
        'numberposts' => -1
    ));
    
    $cleaned = 0;
    foreach ($completed_jobs as $job) {
        $hook_name = 'code_gen_cron_' . $job->ID;
        if (wp_next_scheduled($hook_name)) {
            wp_clear_scheduled_hook($hook_name);
            wp_unschedule_hook($hook_name);
            force_cleanup_completed_cron($job->ID);
            $cleaned++;
        }
    }
    
    if ($cleaned > 0) {
        error_log("Enhanced cleanup: Cleaned {$cleaned} orphaned cron jobs");
    }
    
    // Also check for jobs that are marked as running but haven't run in over 10 minutes
    $stalled_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'cron_status',
                'value' => 'running',
                'compare' => '='
            ),
            array(
                'key' => 'last_run',
                'value' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                'compare' => '<'
            )
        ),
        'numberposts' => -1
    ));
    
    foreach ($stalled_jobs as $job) {
        $total = get_post_meta($job->ID, 'total_quantity', true);
        $created = get_post_meta($job->ID, 'codes_created', true) ?: 0;
        
        if ($created >= $total) {
            // Job should be completed
            wp_clear_scheduled_hook('code_gen_cron_' . $job->ID);
            update_post_meta($job->ID, 'cron_status', 'completed');
            update_post_meta($job->ID, 'end_time', current_time('mysql'));
            force_cleanup_completed_cron($job->ID);
            error_log("Fixed stalled completed job #{$job->ID}");
        }
    }
}

// Schedule enhanced cleanup every 5 minutes
if (!wp_next_scheduled('enhanced_cron_cleanup')) {
    wp_schedule_event(time(), 'every_5_minutes', 'enhanced_cron_cleanup');
}
add_action('enhanced_cron_cleanup', 'enhanced_cron_cleanup');

// Optimized Create Product Codes Batch Function
function create_product_codes_batch($post_id, $batch_size) {
    $point = get_post_meta($post_id, 'point', true);
    $product_id = get_post_meta($post_id, 'product_id', true);
    $session = get_post_meta($post_id, 'session', true);
    
    if (!$point || !$session) {
        return array('success' => false, 'message' => 'Missing required parameters');
    }
    
    return gpt_create_code_batch_function($point, $product_id, $session, $batch_size);
}

// New function for creating box codes batch
function create_box_codes_batch($post_id, $batch_size) {
    $session = get_post_meta($post_id, 'session', true);
    
    if (!$session) {
        return array('success' => false, 'message' => 'Missing required parameters');
    }
    
    return gpt_create_box_code_batch_function($session, $batch_size);
}

// Optimized code generation function

// Alternative method - more balanced distribution
function generate_balanced_mixed_string($letters, $numbers, $length) {
    if ($length < 2) {
        throw new InvalidArgumentException("Length must be at least 2");
    }
    
    // Calculate how many letters and numbers to include
    $min_letters = max(1, floor($length / 2));
    $min_numbers = max(1, $length - $min_letters);
    
    // Adjust if total exceeds length
    if ($min_letters + $min_numbers > $length) {
        $min_letters = $length - 1;
        $min_numbers = 1;
    }
    
    $random_string = '';
    
    // Add required letters
    for ($i = 0; $i < $min_letters; $i++) {
        $random_string .= $letters[rand(0, strlen($letters) - 1)];
    }
    
    // Add required numbers
    for ($i = 0; $i < $min_numbers; $i++) {
        $random_string .= $numbers[rand(0, strlen($numbers) - 1)];
    }
    
    // Fill remaining positions
    $all_chars = $letters . $numbers;
    for ($i = strlen($random_string); $i < $length; $i++) {
        $random_string .= $all_chars[rand(0, strlen($all_chars) - 1)];
    }
    
    return str_shuffle($random_string);
}

function gpt_create_code_batch_function($point, $product_id, $session, $batch_size) {
    try {
        global $wpdb;
        $table_name = BIZGPT_PLUGIN_WP_BARCODE;
        
        // Separate letters and numbers for guaranteed inclusion
        $letters = 'ACDEFHJKLMNPQRTUVWXY';
        $numbers = '123456789';
        $all_chars = $letters . $numbers;
        
        $batch_data = array();
        
        // Pre-generate all batch data at once
        for ($i = 0; $i < $batch_size; $i++) {
            // Generate random string ensuring both letters and numbers are included
            $random_string = generate_balanced_mixed_string($letters, $numbers, 4);
            
            // Create code format
            if (!empty($product_id)) {
                $random_code = "{$product_id}{$session}{$point}{$random_string}";
                $random_code_check = "{$product_id}_{$point}_{$session}_{$random_string}";
            } else {
                $random_code = "{$session}{$point}{$random_string}";
                $random_code_check = "{$point}_{$session}_{$random_string}";
            }
            
            // Create URLs
            $qr_url = home_url('/tich-diem-ma-cao/?barcode=' . urlencode($random_code));
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=1024x1024&data=' . urlencode($qr_url);
            $barcode_url = 'https://bwipjs-api.metafloor.com/?bcid=code39&text=' . urlencode($random_code) . '&includetext&scale=5';
            
            // Generate unique token
            $token = generate_token_4_chars();
            
            // Add to batch
            $batch_data[] = array(
                'barcode' => $random_code,
                'barcode_check' => $random_code_check,
                'token' => $token,
                'point' => intval($point),
                'status' => 'pending',
                'province' => '',
                'channel' => '',
                'product_id' => !empty($product_id) ? $product_id : '',
                'session' => $session,
                'qr_code_url' => $qr_code_url,
                'barcode_url' => $barcode_url,
                'created_at' => current_time('mysql'),
            );
        }
        
        // Bulk insert for better performance
        $values = array();
        $placeholders = array();
        
        foreach ($batch_data as $data) {
            $placeholders[] = "(%s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s)";
            $values = array_merge($values, array_values($data));
        }
        
        $query = "INSERT INTO {$table_name} 
                  (barcode, barcode_check, token, point, status, province, channel, product_id, session, qr_code_url, barcode_url, created_at) 
                  VALUES " . implode(', ', $placeholders);
        
        $result = $wpdb->query($wpdb->prepare($query, $values));
        
        if ($result === false) {
            error_log("Bulk insert failed: " . $wpdb->last_error);
            return array(
                'success' => false,
                'created' => 0,
                'message' => 'Database error: ' . $wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'created' => $batch_size,
            'message' => "Successfully created {$batch_size} product codes"
        );
        
    } catch (Exception $e) {
        error_log("Error in gpt_create_code_batch_function: " . $e->getMessage());
        return array(
            'success' => false,
            'created' => 0,
            'message' => 'System error: ' . $e->getMessage()
        );
    }
}

// Optimized code generation function

function gpt_create_box_code_batch_function($session, $batch_size) {
    try {
        global $wpdb;
        $table_name = BIZGPT_PLUGIN_WP_BOX_MANAGER;
        
        $letters = 'ACDEFHJKLMNPQRTUVWXY';
        $numbers = '123456789';
        $all_chars = $letters . $numbers;
        
        $batch_data = array();
        
        // Lấy tất cả mã hiện có của session này trong 1 query duy nhất
        $existing_codes = $wpdb->get_col($wpdb->prepare(
            "SELECT barcode FROM {$table_name} WHERE session = %s",
            $session
        ));
        
        // Chuyển thành array để tìm kiếm nhanh hơn
        $existing_codes_hash = array_flip($existing_codes);
        $generated_codes = array(); // Lưu mã đã tạo trong batch hiện tại
        
        $max_attempts = $batch_size * 5; // Giảm số lần thử
        $created = 0;
        
        for ($attempt = 0; $attempt < $max_attempts && $created < $batch_size; $attempt++) {
            // Generate random string
            $random_string = generate_balanced_mixed_string($letters, $numbers, 4);
            
            // Create code format
            $random_code = "{$session}{$random_string}";
            $random_code_check = "{$session}_{$random_string}";
            
            // Kiểm tra trùng lặp (chỉ check trong memory, không query DB)
            if (isset($existing_codes_hash[$random_code]) || isset($generated_codes[$random_code])) {
                continue; // Mã đã tồn tại, thử lại
            }
            
            // Đánh dấu mã đã được tạo
            $generated_codes[$random_code] = true;
            
            // Create URLs
            $qr_url = home_url('/tra-cuu/?box_barcode=' . urlencode($random_code));
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=1024x1024&data=' . urlencode($qr_url);
            $barcode_url = 'https://bwipjs-api.metafloor.com/?bcid=code39&text=' . urlencode($random_code) . '&includetext&scale=5';
            
            // Add to batch
            $batch_data[] = array(
                'barcode' => $random_code,
                'barcode_check' => $random_code_check,
                'status' => 'unused',
                'province' => '',
                'channel' => '',
                'session' => $session,
                'list_barcode' => '',
                'qr_code_url' => $qr_code_url,
                'barcode_url' => $barcode_url,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            );
            
            $created++;
        }
        
        // Kiểm tra xem có tạo được mã nào không
        if (empty($batch_data)) {
            return array(
                'success' => false,
                'created' => 0,
                'message' => 'Could not generate any unique codes. Session may have too many existing codes.'
            );
        }
        
        // Bulk insert
        $values = array();
        $placeholders = array();
        
        foreach ($batch_data as $data) {
            $placeholders[] = "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)";
            $values = array_merge($values, array_values($data));
        }
        
        $query = "INSERT INTO {$table_name}
                   (barcode, barcode_check, status, province, channel, session, list_barcode, qr_code_url, barcode_url, created_at, updated_at)
                   VALUES " . implode(', ', $placeholders);
        
        $result = $wpdb->query($wpdb->prepare($query, $values));
        
        if ($result === false) {
            error_log("Bulk insert failed: " . $wpdb->last_error);
            return array(
                'success' => false,
                'created' => 0,
                'message' => 'Database error: ' . $wpdb->last_error
            );
        }
        
        // Thông báo kết quả
        $success_message = "Successfully created {$created} box codes";
        if ($created < $batch_size) {
            $success_message .= " (requested {$batch_size})";
        }
        
        return array(
            'success' => true,
            'created' => $created,
            'requested' => $batch_size,
            'message' => $success_message
        );
        
    } catch (Exception $e) {
        error_log("Error in gpt_create_box_code_batch_function: " . $e->getMessage());
        return array(
            'success' => false,
            'created' => 0,
            'message' => 'System error: ' . $e->getMessage()
        );
    }
}

// Modify existing AJAX functions to use cron
function modify_existing_ajax_to_use_cron() {
    remove_action('wp_ajax_gpt_create_code_batch', 'gpt_create_code_batch_ajax');
    add_action('wp_ajax_gpt_create_code_batch', 'gpt_create_code_batch_via_cron');
}
add_action('init', 'modify_existing_ajax_to_use_cron');

function gpt_create_code_batch_via_cron() {
    $point = sanitize_text_field($_POST['point']);
    $product_id = sanitize_text_field($_POST['product_id']);
    $session = sanitize_text_field($_POST['session']);
    $batch_size = intval($_POST['batch_size']);
    
    // Check if another product code job is running
    if (is_another_cron_running(0, 'product_code')) {
        wp_send_json(array(
            'status' => 'error',
            'message' => 'Another product code generation job is already running. Please wait for it to complete.'
        ));
    }
    
    // Create cron job instead of direct generation
    $job_id = wp_insert_post(array(
        'post_type' => 'code_gen_cron',
        'post_title' => 'Product Code Generation - ' . current_time('Y-m-d H:i:s'),
        'post_status' => 'publish'
    ));
    
    if ($job_id) {
        // Save job information with default values
        update_post_meta($job_id, 'job_type', 'product_code');
        update_post_meta($job_id, 'total_quantity', $batch_size);
        update_post_meta($job_id, 'batch_size', get_code_gen_default_batch_size());
        update_post_meta($job_id, 'interval', get_code_gen_default_interval());
        update_post_meta($job_id, 'point', $point);
        update_post_meta($job_id, 'product_id', $product_id);
        update_post_meta($job_id, 'session', $session);
        update_post_meta($job_id, 'cron_status', 'pending');
        
        // Auto start job
        $hook_name = 'code_gen_cron_' . $job_id;
        wp_schedule_event(time(), 'every_30_seconds', $hook_name, array($job_id));
        
        update_post_meta($job_id, 'cron_status', 'running');
        update_post_meta($job_id, 'start_time', current_time('mysql'));
        
        wp_send_json(array(
            'status' => 'success',
            'message' => 'Code generation job created and started',
            'job_id' => $job_id
        ));
    } else {
        wp_send_json(array(
            'status' => 'error',
            'message' => 'Failed to create cron job'
        ));
    }
}

// Enhanced JavaScript for frontend
function enhanced_code_generation_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Override existing functions
        window.createBatch = function(point, productId, session) {
            if (isCancelled) return;

            let currentBatchSize = Math.min(batchSize, total - created);
            let ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';
            
            $.post(ajaxurl, {
                action: 'gpt_create_code_batch',
                point: point,
                product_id: productId,
                session: session,
                batch_size: currentBatchSize
            }, function(response) {
                if (response.status === 'success') {
                    $('#gpt_result').html('<div class="notice notice-success inline"><p>Job created #' + response.job_id + '! Check progress at <a href="edit.php?post_type=code_gen_cron">Code Generation Jobs</a></p></div>');
                    
                    $('#gpt_progress_wrap').hide();
                    $('#gpt_cancel_generate').hide();
                    $('#gpt_start_generate').prop('disabled', false);
                    
                    if (confirm('Cron job created! Would you like to view progress?')) {
                        window.location.href = 'edit.php?post_type=code_gen_cron';
                    }
                } else {
                    $('#gpt_result').html('<div class="notice notice-error inline"><p>Error: ' + (response.message || 'Cannot create cron job') + '</p></div>');
                    $('#gpt_cancel_generate').hide();
                    $('#gpt_start_generate').prop('disabled', false);
                }
            }).fail(function() {
                $('#gpt_result').html('<div class="notice notice-error inline"><p>Error: Cannot connect to server.</p></div>');
                $('#gpt_cancel_generate').hide();
                $('#gpt_start_generate').prop('disabled', false);
            });
        };
    });
    </script>
    <?php
}

// Admin Columns
function add_code_gen_cron_admin_columns($columns) {
    $columns['job_type'] = 'Job Type';
    $columns['status'] = 'Status';
    $columns['progress'] = 'Progress';
    $columns['last_run'] = 'Last Run';
    return $columns;
}
add_filter('manage_code_gen_cron_posts_columns', 'add_code_gen_cron_admin_columns');

function fill_code_gen_cron_admin_columns($column, $post_id) {
    switch ($column) {
        case 'job_type':
            $job_type = get_post_meta($post_id, 'job_type', true) ?: 'product_code';
            $type_labels = array(
                'product_code' => 'Product Code',
                'box_code' => 'Box Code'
            );
            $type_label = $type_labels[$job_type] ?? 'Unknown';
            $colors = array(
                'product_code' => '#3498db',
                'box_code' => '#e67e22'
            );
            $color = $colors[$job_type] ?? '#333';
            echo '<span style="color: ' . $color . '; font-weight: bold;">' . $type_label . '</span>';
            break;
            
        case 'status':
            $status = get_post_meta($post_id, 'cron_status', true) ?: 'pending';
            $colors = array(
                'pending' => '#f39c12',
                'running' => '#3498db',
                'completed' => '#27ae60',
                'error' => '#e74c3c',
                'stopped' => '#95a5a6'
            );
            $color = $colors[$status] ?? '#333';
            echo '<span style="color: ' . $color . '; font-weight: bold;">' . ucfirst($status) . '</span>';
            break;
            
        case 'progress':
            $created = get_post_meta($post_id, 'codes_created', true) ?: 0;
            $total = get_post_meta($post_id, 'total_quantity', true) ?: 0;
            $percentage = $total > 0 ? round(($created / $total) * 100, 1) : 0;
            echo "{$created}/{$total} ({$percentage}%)";
            break;
            
        case 'last_run':
            $last_run = get_post_meta($post_id, 'last_run', true);
            echo $last_run ? date('Y-m-d H:i:s', strtotime($last_run)) : 'Never';
            break;
    }
}
add_action('manage_code_gen_cron_posts_custom_column', 'fill_code_gen_cron_admin_columns', 10, 2);

// Dashboard Widget
function add_code_gen_dashboard_widget() {
    wp_add_dashboard_widget(
        'code_gen_status',
        'Code Generation Status',
        'code_gen_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'add_code_gen_dashboard_widget');

function code_gen_dashboard_widget_content() {
    $running_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'cron_status',
                'value' => 'running',
                'compare' => '='
            )
        ),
        'numberposts' => -1
    ));
    
    echo "<h4>Running Jobs: " . count($running_jobs) . "</h4>";
    
    if (empty($running_jobs)) {
        echo "<p>No active code generation jobs.</p>";
        return;
    }
    
    foreach ($running_jobs as $job) {
        $created = get_post_meta($job->ID, 'codes_created', true) ?: 0;
        $total = get_post_meta($job->ID, 'total_quantity', true) ?: 0;
        $percentage = $total > 0 ? round(($created / $total) * 100, 1) : 0;
        $job_type = get_post_meta($job->ID, 'job_type', true) ?: 'product_code';
        $type_label = $job_type === 'box_code' ? 'Box Code' : 'Product Code';
        $type_color = $job_type === 'box_code' ? '#e67e22' : '#3498db';
        
        echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;'>";
        echo "<strong>{$job->post_title}</strong>";
        echo "<span style='color: {$type_color}; font-weight: bold; font-size: 12px;'>{$type_label}</span>";
        echo "</div>";
        echo "Progress: {$percentage}% ({$created}/{$total})<br>";
        echo "<div style='background:#f1f1f1; height:8px; border-radius:4px; margin-top:5px;'>";
        echo "<div style='width:{$percentage}%; height:100%; background:#0073aa; border-radius:4px;'></div>";
        echo "</div>";
        echo "</div>";
    }
}

// Default settings
function get_code_gen_default_batch_size() {
    return 100;
}

function get_code_gen_default_interval() {
    return 30;
}

// Auto cleanup completed jobs after 7 days
function cleanup_old_cron_jobs() {
    $old_jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'cron_status',
                'value' => array('completed', 'error', 'stopped'),
                'compare' => 'IN'
            ),
            array(
                'key' => 'end_time',
                'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'compare' => '<'
            )
        ),
        'numberposts' => -1
    ));
    
    foreach ($old_jobs as $job) {
        // Clear any remaining cron hooks
        wp_clear_scheduled_hook('code_gen_cron_' . $job->ID);
        // Delete the post
        wp_delete_post($job->ID, true);
    }
    
    error_log('Cleaned up ' . count($old_jobs) . ' old cron jobs');
}

// Schedule cleanup daily
if (!wp_next_scheduled('cleanup_old_cron_jobs')) {
    wp_schedule_event(time(), 'daily', 'cleanup_old_cron_jobs');
}
add_action('cleanup_old_cron_jobs', 'cleanup_old_cron_jobs');

// Clear all cron hooks on plugin deactivation
function clear_all_code_gen_crons() {
    $jobs = get_posts(array(
        'post_type' => 'code_gen_cron',
        'post_status' => 'any',
        'numberposts' => -1
    ));
    
    foreach ($jobs as $job) {
        $hook_name = 'code_gen_cron_' . $job->ID;
        wp_clear_scheduled_hook($hook_name);
        wp_unschedule_hook($hook_name);
        force_cleanup_completed_cron($job->ID);
    }
    
    wp_clear_scheduled_hook('cleanup_old_cron_jobs');
    wp_clear_scheduled_hook('enhanced_cron_cleanup');
    
    error_log('Cleared all code generation cron jobs');
}
register_deactivation_hook(__FILE__, 'clear_all_code_gen_crons');

// Add CSS for status colors
function code_gen_admin_styles() {
    ?>
    <style>
    .status-pending { color: #f39c12; font-weight: bold; }
    .status-running { color: #3498db; font-weight: bold; }
    .status-completed { color: #27ae60; font-weight: bold; }
    .status-error { color: #e74c3c; font-weight: bold; }
    .status-stopped { color: #95a5a6; font-weight: bold; }
    
    .code-gen-progress {
        background: #f1f1f1;
        height: 20px;
        border-radius: 10px;
        overflow: hidden;
        margin: 5px 0;
    }
    
    .code-gen-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #0073aa, #005177);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    
    .product-code-field {
        display: table-row;
    }
    
    .box-code-field .product-code-field {
        display: none;
    }
    </style>
    <?php
}
add_action('admin_head', 'code_gen_admin_styles');

?>