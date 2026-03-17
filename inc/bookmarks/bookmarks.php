<?php
/**
 * Bookmarks Module
 * Saving and managing bookmarks for users.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('rest_api_init', 'mc_register_bookmarks_routes');

function mc_register_bookmarks_routes() {
    register_rest_route('mediumclone/v1', '/bookmark', array(
        'methods' => 'POST',
        'callback' => 'mc_bookmark_handler',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
}

function mc_bookmark_handler($request) {
    global $wpdb;
    $table_bookmarks = $wpdb->prefix . 'mc_bookmarks';
    
    $user_id = get_current_user_id();
    $post_id = intval($request->get_param('post_id'));

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_bookmarks WHERE user_id = %d AND post_id = %d",
        $user_id, $post_id
    ));

    if ($existing) {
        $wpdb->delete($table_bookmarks, ['id' => $existing->id], ['%d']);
        return rest_ensure_response(['status' => 'removed']);
    } else {
        $wpdb->insert($table_bookmarks, [
            'user_id' => $user_id,
            'post_id' => $post_id
        ], ['%d', '%d']);
        return rest_ensure_response(['status' => 'added']);
    }
}

function mc_is_bookmarked($user_id, $post_id) {
    if (!$user_id) return false;
    global $wpdb;
    $table_bookmarks = $wpdb->prefix . 'mc_bookmarks';
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_bookmarks WHERE user_id = %d AND post_id = %d",
        $user_id, $post_id
    ));
    return $existing ? true : false;
}

/**
 * Get all bookmarks for a specific user
 *
 * @param int $user_id
 * @return array List of post IDs
 */
function mc_get_user_bookmarks($user_id) {
    if (!$user_id) return [];
    
    global $wpdb;
    $table_bookmarks = $wpdb->prefix . 'mc_bookmarks';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id FROM $table_bookmarks WHERE user_id = %d ORDER BY id DESC",
        $user_id
    ));

    $bookmarks = [];
    if (!empty($results)) {
        foreach ($results as $row) {
            $bookmarks[] = (int)$row->post_id;
        }
    }
    
    return $bookmarks;
}
