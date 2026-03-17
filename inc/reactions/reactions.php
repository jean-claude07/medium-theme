<?php
/**
 * Reactions Module
 * Likes, Claps, Insightful, Love.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register REST API endpoints for reactions
add_action('rest_api_init', 'mc_register_reactions_routes');

function mc_register_reactions_routes() {
    register_rest_route('mediumclone/v1', '/react', array(
        'methods' => 'POST',
        'callback' => 'mc_react_handler',
        'permission_callback' => function () {
            // Require login for reacting
            return is_user_logged_in();
        }
    ));
}

function mc_react_handler($request) {
    global $wpdb;
    $table_reactions = $wpdb->prefix . 'mc_reactions';
    
    $user_id = get_current_user_id();
    $post_id = intval($request->get_param('post_id'));
    $comment_id = intval($request->get_param('comment_id')) ?: 0;
    $reaction_type = sanitize_text_field($request->get_param('type'));
    
    $allowed_types = ['like', 'clap', 'insightful', 'love'];
    if (!in_array($reaction_type, $allowed_types)) {
        return new WP_Error('invalid_type', 'Invalid reaction type', ['status' => 400]);
    }

    // Check if exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_reactions WHERE user_id = %d AND post_id = %d AND comment_id = %d AND reaction_type = %s",
        $user_id, $post_id, $comment_id, $reaction_type
    ));

    if ($existing) {
        // Remove reaction (toggle)
        $wpdb->delete($table_reactions, ['id' => $existing->id], ['%d']);
        return rest_ensure_response(['status' => 'removed', 'type' => $reaction_type]);
    } else {
        // Add reaction
        $wpdb->insert($table_reactions, [
            'user_id' => $user_id,
            'post_id' => $post_id,
            'comment_id' => $comment_id,
            'reaction_type' => $reaction_type
        ], ['%d', '%d', '%d', '%s']);
        
        // Award gamification points
        if (function_exists('mc_award_points')) {
            $author_id = $comment_id ? get_comment($comment_id)->user_id : get_post_field('post_author', $post_id);
            mc_award_points($author_id, 'receive_reaction', 5);
        }

        if (function_exists('mc_add_notification')) {
            if ($comment_id) {
                $recipient_id = get_comment($comment_id)->user_id;
                mc_add_notification($recipient_id, $user_id, 'like', $comment_id);
            } else {
                $recipient_id = get_post_field('post_author', $post_id);
                mc_add_notification($recipient_id, $user_id, 'like', $post_id);
            }
        }
        
        return rest_ensure_response(['status' => 'added', 'type' => $reaction_type]);
    }
}

// Function to get reaction counts
function mc_get_reactions($post_id, $comment_id = 0) {
    global $wpdb;
    $table_reactions = $wpdb->prefix . 'mc_reactions';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT reaction_type, COUNT(id) as count FROM $table_reactions WHERE post_id = %d AND comment_id = %d GROUP BY reaction_type",
        $post_id, $comment_id
    ));
    
    $counts = ['like' => 0, 'clap' => 0, 'insightful' => 0, 'love' => 0];
    foreach($results as $row) {
        $counts[$row->reaction_type] = intval($row->count);
    }
    return $counts;
}
