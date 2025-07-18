<?php
function gpt_register_ranking_post_type() {
    register_post_type('ranking', array(
        'labels' => array(
            'name' => 'Cấu hình BXH',
            'singular_name' => 'Xếp hạng',
            'add_new_item' => 'Thêm xếp hạng mới',
            'edit_item' => 'Chỉnh sửa xếp hạng',
            'new_item' => 'Xếp hạng mới',
            'view_item' => 'Xem xếp hạng',
            'search_items' => 'Tìm kiếm xếp hạng',
            'not_found' => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'gpt-macao',
        'supports' => array('title', 'thumbnail'),
        'menu_position' => 25,
        'capability_type' => 'post',
    ));
}
add_action('init', 'gpt_register_ranking_post_type');

function gpt_add_ranking_meta_box() {
    add_meta_box(
        'gpt_ranking_meta',
        'Thông tin xếp hạng',
        'gpt_render_ranking_meta_box',
        'ranking',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'gpt_add_ranking_meta_box');

function gpt_render_ranking_meta_box($post) {
    $min_points = get_post_meta($post->ID, '_min_points', true);
    $sort_order = get_post_meta($post->ID, '_sort_order', true);
    ?>
    <p>
        <label for="min_points"><strong>Điểm tối thiểu:</strong></label><br>
        <input type="number" name="min_points" id="min_points" value="<?php echo esc_attr($min_points); ?>" style="width: 100px;" />
    </p>
    <p>
        <label for="sort_order"><strong>Thứ tự hiển thị:</strong></label><br>
        <input type="number" name="sort_order" id="sort_order" value="<?php echo esc_attr($sort_order); ?>" style="width: 100px;" />
    </p>
    <?php
}

function gpt_save_ranking_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['min_points'])) {
        update_post_meta($post_id, '_min_points', intval($_POST['min_points']));
    }
    if (isset($_POST['sort_order'])) {
        update_post_meta($post_id, '_sort_order', intval($_POST['sort_order']));
    }
}
add_action('save_post_ranking', 'gpt_save_ranking_meta');

function gpt_get_user_rank($points, $ranks) {
    $result = ['name' => 'Chưa phân hạng', 'icon' => ''];
    foreach ($ranks as $rank) {
        if ($points >= $rank['min_points']) {
            $result = $rank;
        }
    }
    return $result;
}