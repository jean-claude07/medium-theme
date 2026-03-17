<?php
/**
 * Follow System Module
 * User following capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'mc_register_follow_routes');

function mc_register_follow_routes()
{
    register_rest_route('mediumclone/v1', '/follow', array(
        'methods' => 'POST',
        'callback' => 'mc_follow_handler',
        'permission_callback' => function () {
        return is_user_logged_in();
    }
    ));
}

function mc_follow_handler($request)
{
    global $wpdb;
    $table_follows = $wpdb->prefix . 'mc_follows';

    $follower_id = get_current_user_id();
    $following_id = intval($request->get_param('user_id'));

    if ($follower_id === $following_id) {
        return new WP_Error('invalid_action', 'You cannot follow yourself', ['status' => 400]);
    }

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_follows WHERE follower_id = %d AND following_id = %d",
        $follower_id, $following_id
    ));

    if ($existing) {
        $wpdb->delete($table_follows, ['id' => $existing->id], ['%d']);
        return rest_ensure_response(['status' => 'unfollowed']);
    }
    else {
        $wpdb->insert($table_follows, [
            'follower_id' => $follower_id,
            'following_id' => $following_id
        ], ['%d', '%d']);

        if (function_exists('mc_award_points')) {
            mc_award_points($following_id, 'gain_follower', 25);
            mc_award_points($follower_id, 'follow_author', 5);
        }

        if (function_exists('mc_add_notification')) {
            mc_add_notification($following_id, $follower_id, 'follow');
        }

        return rest_ensure_response(['status' => 'followed']);
    }
}

function mc_is_following($follower_id, $following_id)
{
    global $wpdb;
    $table_follows = $wpdb->prefix . 'mc_follows';
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_follows WHERE follower_id = %d AND following_id = %d",
        $follower_id, $following_id
    ));
    return $existing ? true : false;
}

function mc_get_follower_count($user_id)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}mc_follows WHERE following_id = %d", $user_id));
}


/**
 * Récupère la liste des utilisateurs qui suivent un utilisateur donné.
 */
function mc_get_followers_list($user_id)
{
    global $wpdb;
    $table_follows = $wpdb->prefix . 'mc_follows';

    // On récupère les IDs des followers
    $follower_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT follower_id FROM $table_follows WHERE following_id = %d",
        $user_id
    ));

    if (empty($follower_ids)) {
        return [];
    }

    // On transforme les IDs en objets utilisateurs WP complets
    return get_users(['include' => $follower_ids]);
}