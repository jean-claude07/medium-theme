<?php
/**
 * Auth Module
 * Handles custom registration, login, and endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'mc_register_auth_routes');

function mc_register_auth_routes()
{
    register_rest_route('mediumclone/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'mc_register_handler',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('mediumclone/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'mc_login_handler',
        'permission_callback' => '__return_true'
    ));
}

function mc_register_handler($request)
{
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $name = sanitize_text_field($request->get_param('name'));

    if (empty($email) || empty($password) || empty($name)) {
        return new WP_Error('missing_fields', 'Please fill in all fields', ['status' => 400]);
    }

    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Email already in use', ['status' => 400]);
    }

    $user_id = wp_create_user($email, $password, $email);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    $user = new WP_User($user_id);
    $user->set_role('subscriber');

    update_user_meta($user_id, 'mc_account_status', 'pending');

    // Update name
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => $name
    ]);

    $activation_key = wp_generate_password(20, false);
    update_user_meta($user_id, 'mc_activation_key', $activation_key);
    update_user_meta($user_id, 'mc_account_status', 'pending');

    $activation_link = add_query_arg([
        'action' => 'mc_activate',
        'key' => $activation_key,
        'user' => $user_id
    ], home_url('/login/'));
    $subject = "Confirmez votre inscription sur MediumClone";
    $message = "Bonjour $name,\n\nCliquez sur ce lien pour activer votre compte : \n" . $activation_link;

    wp_mail($email, $subject, $message);

    return rest_ensure_response([
        'status' => 'pending',
        'message' => 'Veuillez vérifier votre boîte e-mail pour activer votre compte.'
    ]);
}

function mc_login_handler($request)
{
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');

    if (empty($email) || empty($password)) {
        return new WP_Error('missing_fields', 'Please provide email and password', ['status' => 400]);
    }

    $user = wp_authenticate($email, $password);

    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
    }

    $status = get_user_meta($user->ID, 'mc_account_status', true);
    if ($status === 'pending') {
        return new WP_Error('not_activated', 'Votre compte n\'est pas encore activé. Vérifiez vos e-mails.', ['status' => 403]);
    }

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);

    return rest_ensure_response(['status' => 'success', 'user_id' => $user->ID]);
}


add_action('init', 'mc_handle_activation_link');

function mc_handle_activation_link()
{
    if (isset($_GET['action']) && $_GET['action'] === 'mc_activate' && isset($_GET['key']) && isset($_GET['user'])) {
        $user_id = intval($_GET['user']);
        $key = sanitize_text_field($_GET['key']);
        $saved_key = get_user_meta($user_id, 'mc_activation_key', true);

        $user = new WP_User($user_id);
        $user->set_role('author');

        if ($key === $saved_key) {
            update_user_meta($user_id, 'mc_account_status', 'active');
            delete_user_meta($user_id, 'mc_activation_key');

            wp_redirect(add_query_arg('activated', '1', home_url('/login/')));
            exit;
        } else {
            wp_die("Lien d'activation invalide ou expiré.");
        }
    }
}

function mc_get_profile_callback($request)
{
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    return [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'display_name' => $user->display_name,
        'bio' => get_user_meta($user_id, 'description', true),
        'twitter' => get_user_meta($user_id, 'twitter', true),
        'linkedin' => get_user_meta($user_id, 'linkedin', true),
        'website' => $user->user_url,
        'avatar' => get_avatar_url($user_id)
    ];
}