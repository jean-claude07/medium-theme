<?php
/**
 * Profile Module
 * Handles profile editing and custom avatar upload via REST API.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'mc_register_profile_routes');

function mc_register_profile_routes()
{
    register_rest_route('mediumclone/v1', '/profile', [
        'methods' => 'POST',
        'callback' => 'mc_update_profile_handler',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('mediumclone/v1', '/profile', [
        'methods' => 'GET',
        'callback' => 'mc_get_profile_handler',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
}

function mc_update_profile_handler($request)
{
    $user_id = get_current_user_id();

    // 1. Mise à jour des infos de base
    $user_data = [
        'ID' => $user_id,
        'description' => sanitize_textarea_field($request->get_param('bio')),
        'first_name' => sanitize_text_field($request->get_param('first_name')),
        'last_name' => sanitize_text_field($request->get_param('last_name')),
        'display_name' => sanitize_text_field($request->get_param('display_name')),
    ];

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        return new WP_Error('update_failed', $result->get_error_message(), ['status' => 500]);
    }

    // 2. Gestion de l'Avatar (Upload de fichier)
    // S'assurer que $_FILES est peuplé (parfois nécessaire avec Windows/certaines configs REST)
    if (empty($_FILES['avatar_file']) && $request->get_file_params()) {
        $_FILES = $request->get_file_params();
    }

    if (!empty($_FILES['avatar_file'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Sécuriser l'upload : on limite aux images
        $file = $_FILES['avatar_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($file['type'], $allowed_types)) {
            $attachment_id = media_handle_upload('avatar_file', 0); // 0 car non rattaché à un post spécifique

            if (!is_wp_error($attachment_id)) {
                // Supprimer l'ancien avatar média s'il existe pour ne pas encombrer le serveur
                $old_avatar_id = get_user_meta($user_id, 'mc_custom_avatar', true);
                if ($old_avatar_id) {
                    wp_delete_attachment($old_avatar_id, true);
                }

                update_user_meta($user_id, 'mc_custom_avatar', $attachment_id);
            }
        }
    }

    // 3. Réseaux Sociaux
    update_user_meta($user_id, 'mc_twitter', esc_url_raw($request->get_param('twitter')));
    update_user_meta($user_id, 'mc_linkedin', esc_url_raw($request->get_param('linkedin')));
    update_user_meta($user_id, 'mc_website', esc_url_raw($request->get_param('website')));
    update_user_meta($user_id, 'mc_facebook', esc_url_raw($request->get_param('facebook')));

    return rest_ensure_response([
        'status' => 'success',
        'message' => 'Profile updated successfully.',
        'avatar' => get_avatar_url($user_id) // Renvoie la nouvelle URL
    ]);
}

function mc_get_profile_handler($request)
{
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    return rest_ensure_response([
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'bio' => get_the_author_meta('description', $user_id),
        'twitter' => get_user_meta($user_id, 'mc_twitter', true),
        'linkedin' => get_user_meta($user_id, 'mc_linkedin', true),
        'website' => get_user_meta($user_id, 'mc_website', true),
        'facebook' => get_user_meta($user_id, 'mc_facebook', true),
        'avatar' => get_avatar_url($user_id, ['size' => 256]),
    ]);
}

/**
 * Filtre pour forcer WordPress à utiliser l'avatar custom s'il existe
 */
add_filter('get_avatar_url', 'mc_use_custom_avatar_url', 10, 3);
function mc_use_custom_avatar_url($url, $id_or_email, $args)
{
    $user_id = null;

    if (is_numeric($id_or_email)) {
        $user_id = $id_or_email;
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user_id = $id_or_email->user_id;
    } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
        $user_id = $user->ID;
    }

    if ($user_id) {
        $avatar_id = get_user_meta($user_id, 'mc_custom_avatar', true);
        if ($avatar_id) {
            $custom_url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
            if ($custom_url) {
                return $custom_url;
            }
        }
    }
    return $url;
}