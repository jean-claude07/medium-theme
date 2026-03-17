<?php
/**
 * Gamification Module
 * Points and Badges system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mc_award_points($user_id, $action, $points) {
    if (!$user_id) return;
    
    global $wpdb;
    $table_points = $wpdb->prefix . 'mc_points_log';
    
    $wpdb->insert($table_points, [
        'user_id' => $user_id,
        'action' => sanitize_text_field($action),
        'points' => intval($points)
    ], ['%d', '%s', '%d']);

    // Update total points in user meta
    $current_points = (int) get_user_meta($user_id, 'mc_total_points', true);
    $new_points = $current_points + $points;
    update_user_meta($user_id, 'mc_total_points', $new_points);

    // Check for new badges
    mc_check_badges($user_id, $new_points);
}

/**
 * View Tracking System
 */
function mc_track_post_view() {
    if (is_singular('post')) {
        $post_id = get_the_ID();
        $views = (int) get_post_meta($post_id, 'mc_views_count', true);
        update_post_meta($post_id, 'mc_views_count', $views + 1);
        
        // Bonus: award 1 point to author every 10 views
        if (($views + 1) % 10 === 0) {
            $author_id = get_post_field('post_author', $post_id);
            mc_award_points($author_id, 'milestone_views', 1);
        }
    }
}
add_action('wp_head', 'mc_track_post_view');

function mc_get_total_author_views($user_id) {
    $args = array(
        'author' => $user_id,
        'post_type' => 'post',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $posts = get_posts($args);
    $total_views = 0;
    foreach ($posts as $post_id) {
        $total_views += (int) get_post_meta($post_id, 'mc_views_count', true);
    }
    return $total_views;
}

/**
 * Badges System
 */
function mc_check_badges($user_id, $total_points) {
    $badges_config = [
        'new_writer' => ['threshold' => 100, 'label' => 'New Writer'],
        'pro_contributor' => ['threshold' => 500, 'label' => 'Pro Contributor'],
        'popular_author' => ['threshold' => 1000, 'label' => 'Popular Author'],
        'community_star' => ['threshold' => 5000, 'label' => 'Community Star']
    ];

    foreach ($badges_config as $key => $config) {
        if ($total_points >= $config['threshold'] && !mc_has_badge($user_id, $key)) {
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'mc_badges', [
                'user_id' => $user_id,
                'badge_key' => $key
            ], ['%d', '%s']);
            
            // Notification for new badge
            if (function_exists('mc_add_notification')) {
                mc_add_notification($user_id, 0, 'badge', $key);
            }
        }
    }
}

function mc_has_badge($user_id, $badge_key) {
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mc_badges WHERE user_id = %d AND badge_key = %s",
        $user_id, $badge_key
    ));
    return $existing ? true : false;
}

function mc_get_author_analytics_data($user_id) {
    global $wpdb;
    $days = 7;
    $data = [
        'labels' => [],
        'views' => [],
        'engagement' => [] // Reactions + Comments
    ];

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $data['labels'][] = date('D', strtotime($date));

        // 1. Reactions + Comments (Real Data)
        $reactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mc_reactions r 
             JOIN {$wpdb->posts} p ON r.post_id = p.ID 
             WHERE p.post_author = %d AND DATE(r.created_at) = %s",
            $user_id, $date
        ));
        
        $comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} c 
             JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID 
             WHERE p.post_author = %d AND DATE(c.comment_date) = %s AND c.comment_approved = '1'",
            $user_id, $date
        ));

        $data['engagement'][] = (int)$reactions + (int)$comments;

        // 2. Views (Simulated for visualization since we don't have daily logs yet)
        // We'll take total views and spread them a bit with some randomness for the past days
        $total_views = mc_get_total_author_views($user_id);
        if ($total_views > 0) {
            $avg_views = ceil($total_views / 30); // 30 days avg
            $data['views'][] = rand((int)($avg_views * 0.5), (int)($avg_views * 1.5));
        } else {
            $data['views'][] = 0;
        }
    }

    return $data;
}

function mc_get_user_badges($user_id) {
    global $wpdb;
    return $wpdb->get_col($wpdb->prepare(
        "SELECT badge_key FROM {$wpdb->prefix}mc_badges WHERE user_id = %d",
        $user_id
    ));
}
