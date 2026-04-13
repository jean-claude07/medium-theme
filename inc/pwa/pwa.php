<?php
/**
 * Medium Clone — PWA Module
 * 
 * Gère : manifest, meta tags, service worker registration,
 * endpoint push subscriptions, et page admin de configuration.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Constantes & options PWA ────────────────────────────────────────────────

define('MC_PWA_VERSION', '2.0.0');

function mc_pwa_get_option($key, $default = '')
{
    $options = get_option('mc_pwa_settings', []);
    return isset($options[$key]) ? $options[$key] : $default;
}

// ─── Manifest dynamique ─────────────────────────────────────────────────────
add_action('init', 'mc_pwa_register_manifest_endpoint');

function mc_pwa_register_manifest_endpoint()
{
    add_rewrite_rule('^manifest\.json$', 'index.php?mc_pwa_manifest=1', 'top');
    add_rewrite_tag('%mc_pwa_manifest%', '([^&]+)');
}

add_action('template_redirect', 'mc_pwa_serve_manifest');

function mc_pwa_serve_manifest()
{
    if (!get_query_var('mc_pwa_manifest')) {
        return;
    }

    $site_url   = trailingslashit(home_url());
    $theme_uri  = trailingslashit(get_template_directory_uri());
    $icons_path = $theme_uri . 'assets/images/icons/';

    $app_name       = mc_pwa_get_option('app_name',       get_bloginfo('name'));
    $app_short_name = mc_pwa_get_option('short_name',     'MediumClone');
    $app_desc       = mc_pwa_get_option('description',    get_bloginfo('description'));
    $theme_color    = mc_pwa_get_option('theme_color',    '#10b981');
    $bg_color       = mc_pwa_get_option('bg_color',       '#10b981');
    $start_url      = mc_pwa_get_option('start_url',      $site_url);
    $display        = mc_pwa_get_option('display',        'standalone');

    $dashboard_url  = esc_url(mc_get_page_url('dashboard'));
    $login_url      = esc_url(mc_get_page_url('login'));

    $manifest = [
        'name'                     => $app_name,
        'short_name'               => $app_short_name,
        'description'              => $app_desc,
        'start_url'                => $start_url,
        'scope'                    => $site_url,
        'display'                  => $display,
        'orientation'              => 'portrait-primary',
        'background_color'         => $bg_color,
        'theme_color'              => $theme_color,
        'lang'                     => get_locale(),
        'dir'                      => 'ltr',
        'categories'               => ['news', 'social', 'education'],
        'prefer_related_applications' => false,
        'icons' => [
            ['src' => $icons_path . 'icon-72x72.png',   'sizes' => '72x72',   'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-96x96.png',   'sizes' => '96x96',   'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-128x128.png', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-144x144.png', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-152x152.png', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-384x384.png', 'sizes' => '384x384', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icons_path . 'maskable_icon.png','sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            [
                'name'        => __('Accueil', 'medium-clone'),
                'short_name'  => 'Home',
                'description' => __('Lire les derniers articles', 'medium-clone'),
                'url'         => $site_url,
                'icons'       => [['src' => $icons_path . 'icon-96x96.png', 'sizes' => '96x96']],
            ],
            [
                'name'        => __('Dashboard', 'medium-clone'),
                'short_name'  => 'Dashboard',
                'description' => __('Mon tableau de bord auteur', 'medium-clone'),
                'url'         => $dashboard_url,
                'icons'       => [['src' => $icons_path . 'icon-96x96.png', 'sizes' => '96x96']],
            ],
            [
                'name'        => __('Écrire', 'medium-clone'),
                'short_name'  => 'Écrire',
                'description' => __('Créer un nouvel article', 'medium-clone'),
                'url'         => admin_url('post-new.php'),
                'icons'       => [['src' => $icons_path . 'icon-96x96.png', 'sizes' => '96x96']],
            ],
        ],
        'screenshots' => [
            [
                'src'         => $icons_path . 'screenshot-wide.png',
                'sizes'       => '1280x720',
                'type'        => 'image/png',
                'form_factor' => 'wide',
                'label'       => $app_name . ' — Desktop',
            ],
            [
                'src'         => $icons_path . 'screenshot-mobile.png',
                'sizes'       => '390x844',
                'type'        => 'image/png',
                'form_factor' => 'narrow',
                'label'       => $app_name . ' — Mobile',
            ],
        ],
    ];

    header('Content-Type: application/manifest+json; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// ─── Meta tags PWA dans <head> ───────────────────────────────────────────────
add_action('wp_head', 'mc_pwa_inject_head_tags', 2);

function mc_pwa_inject_head_tags()
{
    $theme_uri      = trailingslashit(get_template_directory_uri());
    $icons_path     = $theme_uri . 'assets/images/icons/';
    $site_url       = trailingslashit(home_url());
    $app_name       = mc_pwa_get_option('app_name',    get_bloginfo('name'));
    $theme_color    = mc_pwa_get_option('theme_color', '#10b981');
    $sw_enabled     = mc_pwa_get_option('sw_enabled',  '1');
    ?>
<!-- ═══ PWA Meta Tags ═══ -->
<link rel="manifest" href="<?php echo esc_url($site_url . 'manifest.json'); ?>">

<!-- Theme Color (dynamique dark/light via JS) -->
<meta name="theme-color" id="mc-theme-color" content="<?php echo esc_attr($theme_color); ?>">
<meta name="msapplication-TileColor" content="<?php echo esc_attr($theme_color); ?>">
<meta name="msapplication-TileImage" content="<?php echo esc_url($icons_path . 'icon-144x144.png'); ?>">

<!-- Apple PWA -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($app_name); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url($icons_path . 'apple-touch-icon.png'); ?>">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo esc_url($icons_path . 'icon-152x152.png'); ?>">
<link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc_url($icons_path . 'icon-192x192.png'); ?>">

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($icons_path . 'favicon-32x32.png'); ?>">
<link rel="shortcut icon" href="<?php echo esc_url($icons_path . 'favicon.ico'); ?>">

<!-- Capabilities -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="<?php echo esc_attr($app_name); ?>">
<!-- ═══ /PWA Meta Tags ═══ -->
    <?php
}

// ─── Enqueue Service Worker registration JS ───────────────────────────────────
add_action('wp_enqueue_scripts', 'mc_pwa_enqueue_sw_script');

function mc_pwa_enqueue_sw_script()
{
    $sw_enabled = mc_pwa_get_option('sw_enabled', '1');
    if ($sw_enabled !== '1') return;

    // On passe les données PWA au JS
    $pwa_data = [
        'sw_url'          => get_template_directory_uri() . '/assets/js/sw.js',
        'sw_scope'        => trailingslashit(home_url()),
        'vapid_public_key' => mc_pwa_get_option('vapid_public_key', ''),
        'push_enabled'    => mc_pwa_get_option('push_enabled', '0') === '1',
        'push_subscribe_url' => rest_url('mc/v1/push-subscribe'),
        'pwa_nonce'       => wp_create_nonce('mc_pwa_nonce'),
        'offline_url'     => trailingslashit(home_url()) . 'offline',
        'app_name'        => mc_pwa_get_option('app_name', get_bloginfo('name')),
        'theme_color'     => mc_pwa_get_option('theme_color', '#10b981'),
        'dark_theme_color' => mc_pwa_get_option('dark_theme_color', '#0f172a'),
    ];

    wp_localize_script('medium-clone-app', 'mcPWA', $pwa_data);
}

// ─── REST API : Push Subscribe endpoint ──────────────────────────────────────
add_action('rest_api_init', 'mc_pwa_register_push_endpoints');

function mc_pwa_register_push_endpoints()
{
    register_rest_route('mc/v1', '/push-subscribe', [
        'methods'             => 'POST',
        'callback'            => 'mc_pwa_handle_push_subscribe',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('mc/v1', '/push-unsubscribe', [
        'methods'             => 'POST',
        'callback'            => 'mc_pwa_handle_push_unsubscribe',
        'permission_callback' => '__return_true',
    ]);
}

function mc_pwa_handle_push_subscribe($request)
{
    $subscription = $request->get_json_params();

    if (empty($subscription['endpoint'])) {
        return new WP_Error('invalid_subscription', 'Endpoint manquant.', ['status' => 400]);
    }

    $user_id  = get_current_user_id();
    $endpoint = sanitize_text_field($subscription['endpoint']);
    $auth     = sanitize_text_field($subscription['keys']['auth']    ?? '');
    $p256dh   = sanitize_text_field($subscription['keys']['p256dh']  ?? '');

    global $wpdb;
    $table = $wpdb->prefix . 'mc_push_subscriptions';

    // Créer la table si nécessaire
    mc_pwa_maybe_create_push_table();

    $wpdb->replace($table, [
        'user_id'  => $user_id ?: 0,
        'endpoint' => $endpoint,
        'auth'     => $auth,
        'p256dh'   => $p256dh,
        'created_at' => current_time('mysql'),
    ], ['%d', '%s', '%s', '%s', '%s']);

    return rest_ensure_response(['success' => true, 'message' => 'Abonnement enregistré.']);
}

function mc_pwa_handle_push_unsubscribe($request)
{
    $data     = $request->get_json_params();
    $endpoint = sanitize_text_field($data['endpoint'] ?? '');

    if (!$endpoint) {
        return new WP_Error('invalid_request', 'Endpoint manquant.', ['status' => 400]);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mc_push_subscriptions';
    $wpdb->delete($table, ['endpoint' => $endpoint], ['%s']);

    return rest_ensure_response(['success' => true]);
}

function mc_pwa_maybe_create_push_table()
{
    global $wpdb;
    $table   = $wpdb->prefix . 'mc_push_subscriptions';
    $charset = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        $sql = "CREATE TABLE {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL DEFAULT 0,
            endpoint text NOT NULL,
            auth varchar(255) NOT NULL DEFAULT '',
            p256dh varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY endpoint (endpoint(191))
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

// ─── Include Admin Page ───────────────────────────────────────────────────────
require_once MEDIUM_CLONE_DIR . 'inc/pwa/pwa-admin.php';

// ─── Créer la page offline lors de l'activation ───────────────────────────────
function mc_pwa_create_offline_page()
{
    $existing = get_page_by_path('offline');
    if (!$existing) {
        wp_insert_post([
            'post_title'   => 'Hors ligne',
            'post_name'    => 'offline',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'comment_status' => 'closed',
            'page_template' => 'templates/offline.php',
        ]);
    }
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mc_pwa_create_offline_page');
add_action('init', 'mc_pwa_create_offline_page_on_init');

function mc_pwa_create_offline_page_on_init()
{
    // N'exécuter qu'une seule fois
    if (!get_option('mc_pwa_offline_page_created')) {
        mc_pwa_create_offline_page();
        update_option('mc_pwa_offline_page_created', '1');
    }
}
