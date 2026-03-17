<?php
/**
 * Auth Module
 * Handles custom registration, login, and endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('rest_api_init', 'mc_register_auth_routes');

function mc_register_auth_routes() {
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

function mc_register_handler($request) {
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

    // Update name
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => $name
    ]);

    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    return rest_ensure_response(['status' => 'success', 'user_id' => $user_id]);
}

function mc_login_handler($request) {
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');

    if (empty($email) || empty($password)) {
        return new WP_Error('missing_fields', 'Please provide email and password', ['status' => 400]);
    }

    $user = wp_authenticate($email, $password);

    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
    }

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);

    return rest_ensure_response(['status' => 'success', 'user_id' => $user->ID]);
}
