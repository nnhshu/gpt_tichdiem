<?php


add_shortcode('gpt_user_ranking_dashboard', 'gpt_user_ranking_dashboard_render');

function gpt_user_ranking_dashboard_render() {

    $plugin_url = plugin_dir_url(__FILE__);
    $client_id = getClientIdFromUrlPage();
    $client_query = $client_id ? '&client_id=' . $client_id : '';

    $rank_icons = [
        0 => $plugin_url . './image/one.png',
        1 => $plugin_url . './image/two.png',
        2 => $plugin_url . './image/three.png'
    ];
    $filter = isset($_GET['gpt_range']) ? $_GET['gpt_range'] : 'week';
    $date_condition = '';

    if ($filter === 'week') {
        $date_condition = "AND last_activity_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter === 'month') {
        $date_condition = "AND last_activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    global $wpdb;
    $user_table = BIZGPT_PLUGIN_WP_SAVE_USERS;

    $ranking_posts = get_posts(array(
        'post_type' => 'ranking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_key' => '_min_points',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    ));
    $rankings = [];
    foreach ($ranking_posts as $post) {
        $rankings[] = [
            'name' => $post->post_title,
            'min_points' => intval(get_post_meta($post->ID, '_min_points', true)),
            'icon' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
        ];
    }

    $users = $wpdb->get_results("
        SELECT id, full_name, phone_number, total_points
        FROM $user_table
        WHERE user_status = 'active' $date_condition
        ORDER BY total_points DESC
        LIMIT 10
    ");

    ob_start();
    echo '<div class="gpt-card-wrap">';
    echo '<div class="gpt-filter-bar">';
    echo '<a href="?gpt_range=week' . $client_query . '" class="gpt-filter-btn ' . ($filter === 'week' ? 'active' : '') . '">Tuần này</a>';
    echo '<a href="?gpt_range=month' . $client_query . '" class="gpt-filter-btn ' . ($filter === 'month' ? 'active' : '') . '">Tháng này</a>';
    echo '<a href="?gpt_range=all' . $client_query . '" class="gpt-filter-btn ' . ($filter === 'all' ? 'active' : '') . '">Tất cả</a>';
    echo '</div>';

    echo '<div class="gpt-ranking-wrapper">';
    if (empty($users)) {
        echo '<p>Không có dữ liệu người dùng trong khoảng thời gian này.</p>';
    } else {
        echo '<div class="gpt-ranking-top3">';
        foreach ($users as $index => $user) {
            if ($index >= 3) break;
            $rank = gpt_get_user_rank($user->total_points, $rankings);
            $rank_label = ($index == 0) ? '1st' : (($index == 1) ? '2nd' : '3rd');
            $extra_class = ($index == 0) ? 'first-place' : '';
            $icon_class = ($index == 0) ? 'rank-icon glow-effect' : 'rank-icon';
            $icon_url = isset($rank_icons[$index]) ? $rank_icons[$index] : '';
            echo '<div class="gpt-card ' . $extra_class . '">';
            echo '<div class="gpt-avatar-circle">';
            if ($icon_url) echo '<img src="' . esc_url($icon_url) . '" class="' . esc_attr($icon_class) . '" />';
            // echo '<span class="rank-badge">' . $rank_label . '</span>';
            echo '</div>';
            echo '<div class="gpt-name">' . esc_html($user->full_name) . '</div>';
            echo '<div class="gpt-phone">' . esc_html($user->phone_number) . '</div>';
            echo '<div class="gpt-points">' . intval($user->total_points) . ' điểm</div>';
            echo '<div class="gpt-rank-name">' . esc_html($rank['name']) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="gpt-ranking-list">';
        foreach ($users as $index => $user) {
            if ($index < 3) continue;
            $rank = gpt_get_user_rank($user->total_points, $rankings);
            echo '<div class="gpt-row">';
            echo '<div class="gpt-rank">#' . ($index + 1) . '</div>';
            echo '<div class="gpt-info">';
            echo '<div class="gpt-name">' . esc_html($user->full_name) . '</div>';
            echo '<div class="gpt-phone">' . esc_html($user->phone_number) . '</div>';
            echo '<div class="gpt-rank-small">';
            if ($rank['icon']) echo '<img src="' . esc_url($rank['icon']) . '" class="rank-icon-small" /> ';
            echo esc_html($rank['name']) . '</div>';
            echo '</div>';
            echo '<div class="gpt-points-row">' . intval($user->total_points) . ' điểm</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    return ob_get_clean();
}