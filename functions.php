<?php
/**
 * Medium Clone Theme — functions.php
 *
 * Master file: enqueues assets, registers menus, includes modules,
 * handles theme activation/deactivation and page scaffolding.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MEDIUM_CLONE_VERSION', '1.1.0');
define('MEDIUM_CLONE_DIR', trailingslashit(get_template_directory()));
define('MEDIUM_CLONE_URI', trailingslashit(get_template_directory_uri()));

/* ------------------------------------------------------------------ */
/*  Theme Setup                                                         */
/* ------------------------------------------------------------------ */
function medium_clone_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style']);
    add_theme_support('editor-styles');
    add_editor_style('assets/css/tailwind.css');

    // Register nav menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'medium-clone'),
        'footer' => __('Footer Menu', 'medium-clone'),
    ]);

    // Custom image sizes
    add_image_size('mc-card', 600, 400, true);
    add_image_size('mc-hero', 1200, 630, true);
}
add_action('after_setup_theme', 'medium_clone_setup');

/* ------------------------------------------------------------------ */
/*  Enqueue Scripts & Styles                                            */
/* ------------------------------------------------------------------ */
function medium_clone_scripts()
{
    // Main stylesheet
    wp_enqueue_style('medium-clone-style', get_stylesheet_uri(), [], MEDIUM_CLONE_VERSION);

    // Compiled Tailwind CSS
    $tailwind_path = get_template_directory() . '/assets/css/tailwind.css';
    $tailwind_ver = file_exists($tailwind_path) ? filemtime($tailwind_path) : MEDIUM_CLONE_VERSION;
    wp_enqueue_style('medium-clone-tailwind', MEDIUM_CLONE_URI . 'assets/css/tailwind.css', [], $tailwind_ver);

    // GSAP
    wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', [], '3.12.2', true);
    wp_enqueue_script('gsap-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', ['gsap'], '3.12.2', true);

    // ALPINE JS (On garde le chargement classique ici)
    wp_enqueue_script('alpine-js', 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js', [], '3.13.3', true);

    // Styles Quill (Thème Bubble)
    wp_enqueue_style('quill-bubble', 'https://cdn.quilljs.com/1.3.6/quill.bubble.css', [], '1.3.6');

    // Script Quill
    wp_enqueue_script('quill-js', 'https://cdn.quilljs.com/1.3.6/quill.min.js', [], '1.3.6', true);

    // Chart.js
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true);

    // Cropper.js
    wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', [], '1.6.1');
    wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', [], '1.6.1', true);

    // APP JS (Ton fichier qui contient Alpine.data)
    $app_path = get_template_directory() . '/assets/js/app.js';
    $app_ver = file_exists($app_path) ? filemtime($app_path) : MEDIUM_CLONE_VERSION;
    wp_enqueue_script('medium-clone-app', MEDIUM_CLONE_URI . 'assets/js/app.js', ['gsap', 'gsap-scrolltrigger', 'alpine-js', 'cropper-js'], $app_ver, true);

    // Pass data to JS
    wp_localize_script('medium-clone-app', 'mediumCloneData', [
        'root_url' => get_site_url(),
        'ajax_url' => admin_url('admin-ajax.php'),
        'auth_nonce' => wp_create_nonce('auth_nonce'),
        'comment_nonce' => wp_create_nonce('mc_comment_nonce'),
        'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
        'rest_url' => esc_url_raw(rest_url()),
        'is_logged_in' => is_user_logged_in(),
        'user_id' => get_current_user_id(),
        'login_url' => esc_url(mc_get_page_url('login')),
        'dashboard_url' => esc_url(mc_get_page_url('dashboard')),
        'profile_url' => esc_url(mc_get_page_url('profile-edit')),
        'search_url' => esc_url(home_url('/?s=')),
        'categories' => array_map(function ($cat) {
            return [
                'id' => $cat->term_id,
                'name' => $cat->name
            ];
        }, get_categories(['hide_empty' => false])),
    ]);
}
add_action('wp_enqueue_scripts', 'medium_clone_scripts');

/**
 * AJOUTER CE FILTRE ICI : 
 * Il force l'attribut 'defer' sur Alpine.js pour qu'il attende 
 * que app.js enregistre les composants avant de démarrer.
 */
add_filter('script_loader_tag', function ($tag, $handle) {
    if ('alpine-js' !== $handle) {
        return $tag;
    }
    return str_replace(' src', ' defer src', $tag);
}, 10, 2);

/* ------------------------------------------------------------------ */
/*  Helper: get URL of a theme-created page by option key              */
/* ------------------------------------------------------------------ */
function mc_get_page_url($key)
{
    $id = (int) get_option('mc_page_' . $key);
    if ($id) {
        return get_permalink($id);
    }
    // Fallback: search by slug
    $page = get_page_by_path($key);
    return $page ? get_permalink($page->ID) : home_url('/');
}

/* ------------------------------------------------------------------ */
/*  Theme Activation: create pages & DB tables                         */
/* ------------------------------------------------------------------ */
function medium_clone_activate()
{
    medium_clone_create_custom_tables();
    mc_create_required_pages();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'medium_clone_activate');

/* ------------------------------------------------------------------ */
/*  Create required pages if they don't exist                           */
/* ------------------------------------------------------------------ */
function mc_create_required_pages()
{
    $pages = [
        'login' => [
            'title' => 'Login & Register',
            'template' => 'page-login.php',
            'content' => '',
        ],
        'dashboard' => [
            'title' => 'Dashboard',
            'template' => 'page-dashboard.php',
            'content' => '',
        ],
        'profile-edit' => [
            'title' => 'Edit Profile',
            'template' => 'page-profile-edit.php',
            'content' => '',
        ],
    ];

    foreach ($pages as $slug => $data) {
        $opt_key = 'mc_page_' . $slug;
        $existing = (int) get_option($opt_key);

        // Check if still valid
        if ($existing && get_post($existing) && get_post_status($existing) === 'publish') {
            // Update template if needed
            update_post_meta($existing, '_wp_page_template', $data['template']);
            continue;
        }

        // Also check by slug
        $by_slug = get_page_by_path($slug);
        if ($by_slug) {
            update_post_meta($by_slug->ID, '_wp_page_template', $data['template']);
            update_option($opt_key, $by_slug->ID);
            continue;
        }

        // Create new page
        $page_id = wp_insert_post([
            'post_title' => $data['title'],
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => $data['content'],
            'comment_status' => 'closed',
        ]);

        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', $data['template']);
            update_option($opt_key, $page_id);
        }
    }
}

/* ------------------------------------------------------------------ */
/*  Theme Deactivation: cleanup                                         */
/* ------------------------------------------------------------------ */
function medium_clone_deactivate()
{
    // Remove pages created by this theme
    $slugs = ['login', 'dashboard', 'profile-edit'];
    foreach ($slugs as $slug) {
        $opt_key = 'mc_page_' . $slug;
        $page_id = (int) get_option($opt_key);
        if ($page_id) {
            wp_delete_post($page_id, true);
            delete_option($opt_key);
        }
    }
    flush_rewrite_rules();
}
add_action('switch_theme', 'medium_clone_deactivate');

/* ------------------------------------------------------------------ */
/*  Add custom query vars for search                                    */
/* ------------------------------------------------------------------ */
function mc_custom_query_vars($vars)
{
    $vars[] = 'mc_search';
    return $vars;
}
add_filter('query_vars', 'mc_custom_query_vars');

/* ------------------------------------------------------------------ */
/*  SEO: improve <title> tag                                            */
/* ------------------------------------------------------------------ */
function mc_document_title_parts($parts)
{
    if (is_search()) {
        $parts['title'] = sprintf(__('Search: %s', 'medium-clone'), get_search_query());
    }
    return $parts;
}
add_filter('document_title_parts', 'mc_document_title_parts');

/* ------------------------------------------------------------------ */
/*  Include modules                                                     */
/* ------------------------------------------------------------------ */
require_once MEDIUM_CLONE_DIR . 'inc/db-setup.php';
require_once MEDIUM_CLONE_DIR . 'inc/auth/auth.php';
require_once MEDIUM_CLONE_DIR . 'inc/gamification/gamification.php';
require_once MEDIUM_CLONE_DIR . 'inc/reactions/reactions.php';
require_once MEDIUM_CLONE_DIR . 'inc/follow-system/follow.php';
require_once MEDIUM_CLONE_DIR . 'inc/bookmarks/bookmarks.php';
require_once MEDIUM_CLONE_DIR . 'inc/notifications/notifications.php';
require_once MEDIUM_CLONE_DIR . 'inc/seo/seo.php';
require_once MEDIUM_CLONE_DIR . 'inc/gutenberg/blocks.php';
require_once MEDIUM_CLONE_DIR . 'inc/post/post-handler.php';
require_once MEDIUM_CLONE_DIR . 'inc/profile/profile.php';
require_once MEDIUM_CLONE_DIR . 'inc/emails.php';
require_once get_template_directory() . '/inc/rest-api.php';
require_once MEDIUM_CLONE_DIR . 'inc/pwa/pwa.php';

/**
 * Medium Clone — Auth Logic
 */

add_action('wp_ajax_nopriv_mc_auth_action', 'mc_handle_auth_action');

function mc_handle_auth_action()
{
    // Vérification de sécurité avec le nonce généré par WordPress
    check_ajax_referer('auth_nonce', 'security');

    $tab = sanitize_text_field($_POST['tab']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    if ($tab === 'login') {
        $creds = [
            'user_login' => $email,
            'user_password' => $password,
            'remember' => true
        ];

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error('Email ou mot de passe incorrect.');
        }
        wp_send_json_success(['redirect' => home_url()]);

    } else {
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        $result = mc_process_registration_logic($email, $password, $name);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Si l'activation par e-mail est requise (status === 'pending')
        if (isset($result['status']) && $result['status'] === 'pending') {
            wp_send_json_success([
                'status' => 'pending',
                'message' => $result['message']
            ]);
        }

        // Fallback si jamais on désactive l'activation par mail dans le futur
        wp_set_auth_cookie(get_user_by('email', $email)->ID);
        wp_send_json_success(['redirect' => home_url()]);
    }
}


/* ------------------------------------------------------------------ */
/* Hide Admin Bar for non-admins                                       */
/* ------------------------------------------------------------------ */
add_action('after_setup_theme', 'mc_hide_admin_bar');

function mc_hide_admin_bar()
{
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}

/* ------------------------------------------------------------------ */
/* Restrict WP-Admin access & Redirect Logins                         */
/* ------------------------------------------------------------------ */
add_action('admin_init', 'mc_restrict_admin_with_redirect');

function mc_restrict_admin_with_redirect()
{
    // On autorise AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    // Rediriger les non-admins vers le dashboard ou la page de login
    if (!current_user_can('administrator')) {
        if (is_user_logged_in()) {
            wp_safe_redirect(mc_get_page_url('dashboard'));
        } else {
            wp_safe_redirect(mc_get_page_url('login'));
        }
        exit;
    }
}

/**
 * Redirect wp-login.php to custom theme login page
 */
add_action('init', 'mc_redirect_to_custom_login');
function mc_redirect_to_custom_login() {
    global $pagenow;
    // Check if we are on the login page and it's not a logout action
    if ($pagenow == 'wp-login.php' && $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['action'])) {
        wp_redirect(mc_get_page_url('login'));
        exit;
    }
}

/**
 * Ensure WordPress uses the custom login URL everywhere
 */
add_filter('login_url', function($login_url, $redirect, $force_reauth) {
    return mc_get_page_url('login');
}, 10, 3);

/**
 * Filtre la page d'accueil pour n'afficher que les auteurs suivis 
 * si le paramètre ?feed=following est présent.
 */
add_action('pre_get_posts', 'mc_filter_home_by_following');

function mc_filter_home_by_following($query)
{
    // On ne modifie que la requête principale sur la home en front-end
    if (!is_admin() && $query->is_main_query() && is_home()) {

        if (isset($_GET['feed']) && $_GET['feed'] === 'following') {

            if (!is_user_logged_in()) {
                // Si pas connecté, on ne montre rien ou on force une redirection
                $query->set('post__in', [0]);
                return;
            }

            global $wpdb;
            $current_user_id = get_current_user_id();
            $table_follows = $wpdb->prefix . 'mc_follows';

            // Récupérer les IDs des auteurs suivis
            $following_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT following_id FROM $table_follows WHERE follower_id = %d",
                $current_user_id
            ));

            if (!empty($following_ids)) {
                // On limite la requête à ces auteurs
                $query->set('author__in', $following_ids);
            } else {
                // Si l'utilisateur ne suit personne, on affiche aucun post (ou un message)
                $query->set('post__in', [0]);
            }
        }
    }
}

add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && is_user_logged_in()) {
        $muted_authors = get_user_meta(get_current_user_id(), 'muted_authors', true);

        if (!empty($muted_authors)) {
            $query->set('author__not_in', $muted_authors);
        }
    }
});

/* ------------------------------------------------------------------ */
/* Custom Comment Format Helper                                       */
/* ------------------------------------------------------------------ */
function mc_custom_comment_format($comment, $args, $depth)
{
    $is_reply = $depth > 1;
    ?>
    <div <?php comment_class('mc-comment group ' . ($is_reply ? 'ml-6 md:ml-10 border-l border-gray-100 dark:border-gray-800 pl-4 py-2 mt-2' : 'border-b border-gray-50 dark:border-gray-800/50 py-6 last:border-0')); ?>
        id="comment-<?php comment_ID(); ?>">
        <div class="flex items-start gap-3">

            <!-- Avatar -->
            <div class="flex-none pt-0.5">
                <?php echo get_avatar($comment, $is_reply ? 28 : 36, 'mystery', '', [
                    'class' => 'rounded-full block object-cover ring-1 ring-gray-200 dark:ring-gray-700'
                ]); ?>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <!-- Author row -->
                <div class="flex items-baseline gap-2 mb-1.5">
                    <a href="<?php echo get_author_posts_url($comment->user_id); ?>"
                        class="text-sm font-semibold text-dark-bg dark:text-light-bg hover:text-primary transition-colors leading-none">
                        <?php comment_author(); ?>
                    </a>
                    <span class="text-[11px] text-gray-400 leading-none tabular-nums">
                        <?php echo human_time_diff(get_comment_time('U'), current_time('timestamp')); ?> ago
                    </span>
                </div>

                <!-- Comment text -->
                <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed break-words comment-content">
                    <?php comment_text(); ?>
                </div>

                <!-- Reply link -->
                <div class="mt-2">
                    <?php comment_reply_link(array_merge($args, [
                        'depth' => $depth,
                        'max_depth' => $args['max_depth'],
                        'before' => '<button class="text-[11px] font-medium text-gray-400 hover:text-primary transition-colors flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>',
                        'after' => '</button>'
                    ])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function mc_enqueue_comment_reply_script()
{
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'mc_enqueue_comment_reply_script');

// Inject Alpine submit handler into the comment form
add_filter('comment_form_defaults', function ($defaults) {
    if (!is_admin()) {
        $defaults['attributes'] = [
            'x-on:submit.prevent' => 'submitComment($event)',
        ];
    }
    return $defaults;
});

function mc_ajax_post_comment_handler()
{
    check_ajax_referer('mc_comment_nonce', 'security');

    $comment = wp_handle_comment_submission(wp_unslash($_POST));

    if (is_wp_error($comment)) {
        wp_send_json_error(['message' => $comment->get_error_message()]);
    }

    // Récupérer le nouveau compteur
    $post_id = intval($_POST['comment_post_ID']);
    $new_count = get_comments_number($post_id);

    // Notifications
    if (function_exists('mc_add_notification')) {
        $actor_id = get_current_user_id();
        $parent_id = intval($_POST['comment_parent']);

        if ($parent_id > 0) {
            // Reply: Notify the parent comment author
            $parent_comment = get_comment($parent_id);
            if ($parent_comment) {
                mc_add_notification($parent_comment->user_id, $actor_id, 'reply', $parent_id);
            }
        } else {
            // New Comment: Notify the post author
            $post_author = get_post_field('post_author', $post_id);
            mc_add_notification($post_author, $actor_id, 'comment', $post_id);
        }
    }

    ob_start();
    mc_custom_comment_format($comment, ['avatar_size' => 32], 1);
    $comment_html = ob_get_clean();

    wp_send_json_success([
        'html' => $comment_html,
        'count' => $new_count // On renvoie le chiffre ici
    ]);
}

add_action('wp_ajax_mc_ajax_post_comment', 'mc_ajax_post_comment_handler');
add_action('wp_ajax_nopriv_mc_ajax_post_comment', 'mc_ajax_post_comment_handler');
function mc_ajax_load_comments_handler()
{
    $post_id = intval($_POST['post_id']);
    $page = intval($_POST['page']);

    set_query_var('cpage', $page);

    $comments = get_comments([
        'post_id' => $post_id,
        'status' => 'approve',
        'order' => 'DESC',
    ]);

    ob_start();
    ?>
    <div class="comment-list">
        <?php
        wp_list_comments([
            'style' => 'div',
            'short_ping' => true,
            'avatar_size' => 40,
            'callback' => 'mc_custom_comment_format',
            'page' => $page,
            'per_page' => get_option('comments_per_page'),
        ], $comments);
        ?>
    </div>
    <div class="comment-navigation pt-8">
        <?php
        echo paginate_comments_links([
            'echo' => false,
            'current' => $page,
            'base' => add_query_arg('cpage', '%#%'),
            'total' => get_comment_pages_count($comments, get_option('comments_per_page')),
            'prev_text' => '&larr; Older',
            'next_text' => 'Newer &rarr;',
        ]);
        ?>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_mc_ajax_load_comments', 'mc_ajax_load_comments_handler');
add_action('wp_ajax_nopriv_mc_ajax_load_comments', 'mc_ajax_load_comments_handler');


/**
 * Enregistre le flux personnalisé "following"
 */
function mc_register_following_feed()
{
    add_feed('following', 'mc_render_following_feed');
}
add_action('init', 'mc_register_following_feed');

/**
 * Logique d'affichage du flux
 */
function mc_render_following_feed()
{
    // 1. On retire l'en-tête XML par défaut de WordPress et on force le HTML
    header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

    // 2. On charge le template
    include(get_template_directory() . '/index.php');

    // 3. On arrête l'exécution ici pour éviter que WP n'ajoute du code XML après
    exit;
}
