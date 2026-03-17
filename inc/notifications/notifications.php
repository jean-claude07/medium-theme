<?php
/**
 * Notifications Module
 * In-app notifications.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'mc_register_notifications_routes');

function mc_register_notifications_routes()
{
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

function mc_add_notification($user_id, $actor_id, $type, $reference_id = 0)
{
    if (!$user_id || $user_id === $actor_id)
        return;

    global $wpdb;
    $table_notifications = $wpdb->prefix . 'mc_notifications';

    $wpdb->insert($table_notifications, [
        'user_id' => $user_id,
        'actor_id' => $actor_id,
        'type' => sanitize_text_field($type),
        'reference_id' => intval($reference_id),
        'is_read' => 0
    ], ['%d', '%d', '%s', '%d', '%d']);

    // --- LOGIQUE D'ENVOI D'EMAIL ---

    $recipient = get_userdata($user_id);
    $actor = get_userdata($actor_id);

    if ($recipient && $actor) {
        $subject = "";
        $body = "";
        $blogname = get_bloginfo('name');

        switch ($type) {
            case 'clap':
                $post_title = get_the_title($reference_id);
                $subject = "{$actor->display_name} a applaudi votre article";
                $body = "Bonne nouvelle ! {$actor->display_name} a aimé votre article : \"{$post_title}\".";
                break;

            case 'comment':
                $post_title = get_the_title($reference_id);
                $subject = "Nouveau commentaire de {$actor->display_name}";
                $body = "{$actor->display_name} a répondu à votre article \"{$post_title}\".";
                break;

            case 'follow':
                $subject = "{$actor->display_name} vous suit désormais";
                $body = "Félicitations ! {$actor->display_name} s'est abonné à vos publications sur {$blogname}.";
                break;
        }

        if ($subject && $body) {
            add_filter('wp_mail_content_type', function () {
                return 'text/html'; });

            $html_message = "
                <div style='font-family: sans-serif; color: #292929;'>
                    <p>Bonjour {$recipient->display_name},</p>
                    <p>{$body}</p>
                    <p><a href='" . get_permalink($reference_id) . "' style='color: #1a8917; text-decoration: none; font-weight: bold;'>Voir sur {$blogname}</a></p>
                </div>
            ";

            wp_mail($recipient->user_email, "[$blogname] $subject", $html_message);

            remove_filter('wp_mail_content_type', 'text/html');
        }
    }
}
function mc_get_notifications_handler($request)
{
    global $wpdb;
    $user_id = get_current_user_id();
    $table_notifications = $wpdb->prefix . 'mc_notifications';

    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
        $user_id
    ));

    $formatted = [];
    foreach ($notifications as $notif) {
        $actor = get_userdata($notif->actor_id);
        $formatted[] = [
            'id' => $notif->id,
            'actor_name' => $actor ? $actor->display_name : 'Someone',
            'actor_avatar' => get_avatar_url($notif->actor_id),
            'type' => $notif->type,
            'reference_id' => $notif->reference_id,
            'is_read' => (bool) $notif->is_read,
            'created_at' => $notif->created_at
        ];
    }

    return rest_ensure_response($formatted);
}

function mc_mark_notifications_read($request)
{
    global $wpdb;
    $user_id = get_current_user_id();
    $table_notifications = $wpdb->prefix . 'mc_notifications';

    $wpdb->update($table_notifications, ['is_read' => 1], ['user_id' => $user_id], ['%d'], ['%d']);

    return rest_ensure_response(['status' => 'success']);
}
