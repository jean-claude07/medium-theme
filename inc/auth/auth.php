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

    $result = mc_process_registration_logic($email, $password, $name);

    if (is_wp_error($result)) {
        return $result;
    }

    return rest_ensure_response($result);
}

/**
 * Core registration logic shared between REST and AJAX
 */
function mc_process_registration_logic($email, $password, $name)
{
    // 1. Vérification des champs vides
    if (empty($email) || empty($password) || empty($name)) {
        return new WP_Error('missing_fields', 'Veuillez remplir tous les champs', ['status' => 400]);
    }

    // 2. Bannir les e-mails jetables
    $disposable_domains = [
        'yopmail.com', 'yopmail.fr', 'yopmail.net', 'temp-mail.org', 'tempmail.com',
        'tempmail.net', 'tempmailaddress.com', 'guerrillamail.com', 'guerrillamail.net',
        'guerrillamail.org', 'sharklasers.com', 'grr.la', 'mailinator.com',
        'mailinator.net', 'mailinator.org', 'trashmail.com', 'trashmail.net',
        'trashmail.org', '10minutemail.com', '10minutemail.net', '10minutemail.org',
        'minuteinbox.com', 'fakeinbox.com', 'throwawaymail.com', 'getnada.com',
        'nada.ltd', 'dispostable.com', 'maildrop.cc', 'mailnesia.com',
        'tempinbox.com', 'moakt.com', 'emailondeck.com', 'mintemail.com',
        'spamgourmet.com', 'spambog.com', 'spamavert.com', 'tempail.com',
        'tempemail.co', 'tempemail.com', 'tempmailo.com', 'emailfake.com',
        'fake-mail.net', 'easytrashmail.com', 'jetable.org', 'jetable.com',
        'jetable.fr', 'mytrashmail.com', 'trashmail.de', 'wegwerfmail.de',
        'wegwerfmail.net', 'wegwerfmail.org', 'mail-temporaire.fr', 'mailcatch.com',
        'mailnull.com', 'bccto.me', 'chacuo.net', 'disposablemail.com',
        'dropmail.me', 'dropmail.org', 'mailpoof.com', 'temp-mail.io',
        'tmpmail.org', 'tmpmail.net', 'tmpmail.com', 'mail.tm', 'guerrillamailblock.com'
    ];
    
    $email_domain = substr(strrchr($email, "@"), 1);
    
    if (in_array(strtolower($email_domain), $disposable_domains)) {
        return new WP_Error('disposable_email', 'Les e-mails jetables ne sont pas autorisés.', ['status' => 400]);
    }

    // 3. Vérifier si l'email existe déjà
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Cet e-mail est déjà utilisé', ['status' => 400]);
    }

    // 4. Vérifier si le NOM (display_name) est unique
    $user_query = get_users([
        'search'         => $name,
        'search_columns' => ['display_name', 'user_nicename'],
        'number'         => 1
    ]);

    if (!empty($user_query)) {
        return new WP_Error('name_exists', 'Ce nom est déjà pris, veuillez en choisir un autre.', ['status' => 400]);
    }

    // 5. Création de l'utilisateur
    // Note : On utilise le nom comme 'user_login' pour garantir l'unicité au niveau de la DB
    $user_id = wp_create_user($name, $password, $email);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    $user = new WP_User($user_id);
    $user->set_role('subscriber');

    // Mise à jour des infos et du statut
    wp_update_user([
        'ID'           => $user_id,
        'display_name' => $name,
        'first_name'   => $name
    ]);

    $activation_key = wp_generate_password(20, false);
    update_user_meta($user_id, 'mc_activation_key', $activation_key);
    update_user_meta($user_id, 'mc_account_status', 'pending');

    // Envoi de l'e-mail
    $activation_link = add_query_arg([
        'action' => 'mc_activate',
        'key'    => $activation_key,
        'user'   => $user_id
    ], home_url('/login/'));
    
    $subject = "Confirmez votre inscription sur MediumClone";
    
    mc_send_template_email($email, $subject, 'activation', [
        'name' => $name,
        'activation_link' => $activation_link
    ]);

    return [
        'status' => 'pending',
        'message' => 'Veuillez vérifier votre boîte e-mail pour activer votre compte.'
    ];
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