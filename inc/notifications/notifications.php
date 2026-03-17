<?php
/**
 * Notifications Module
 * In-app notifications.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('rest_api_init', 'mc_register_notifications_routes');

function mc_register_notifications_routes() {
    register_rest_route('mediumclone/v1', '/notifications', array(
        'methods' => 'GET',
        'callback' => 'mc_get_notifications_handler',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    register_rest_route('mediumclone/v1', '/notifications/read', array(
        'methods' => 'POST',
        'callback' => 'mc_mark_notifications_read',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
}

function mc_add_notification($user_id, $actor_id, $type, $reference_id = 0) {
    if (!$user_id || $user_id === $actor_id) return;
    
    global $wpdb;
    $table_notifications = $wpdb->prefix . 'mc_notifications';
    
    $wpdb->insert($table_notifications, [
        'user_id' => $user_id,
        'actor_id' => $actor_id,
        'type' => sanitize_text_field($type),
        'reference_id' => intval($reference_id),
        'is_read' => 0
    ], ['%d', '%d', '%s', '%d', '%d']);
}

function mc_get_notifications_handler($request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_notifications = $wpdb->prefix . 'mc_notifications';
    
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
        $user_id
    ));
    
    // Format notifications
    $formatted = [];
    foreach ($notifications as $notif) {
        $actor = get_userdata($notif->actor_id);
        $formatted[] = [
            'id' => $notif->id,
            'actor_name' => $actor ? $actor->display_name : 'Someone',
            'actor_avatar' => get_avatar_url($notif->actor_id),
            'type' => $notif->type,
            'reference_id' => $notif->reference_id,
            'is_read' => (bool)$notif->is_read,
            'created_at' => $notif->created_at
        ];
    }
    
    return rest_ensure_response($formatted);
}

function mc_mark_notifications_read($request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_notifications = $wpdb->prefix . 'mc_notifications';
    
    $wpdb->update($table_notifications, ['is_read' => 1], ['user_id' => $user_id], ['%d'], ['%d']);
    
    return rest_ensure_response(['status' => 'success']);
}
