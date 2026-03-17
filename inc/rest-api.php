<?php
add_action('rest_api_init', function () {
    // Route pour les bookmarks récents de la sidebar
    register_rest_route('mediumclone/v1', '/saved-short', [
        'methods' => 'GET',
        'callback' => 'mc_get_recent_saved_api',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
        ]);
    });

function mc_get_recent_saved_api()
{
    global $wpdb;
    $user_id = get_current_user_id();
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->prefix}mc_bookmarks WHERE user_id = %d ORDER BY id DESC LIMIT 3",
        $user_id
    ));

    $data = [];
    foreach ($results as $row) {
        $data[] = [
            'title' => get_the_title($row->post_id),
            'link' => get_permalink($row->post_id),
            'date' => get_the_date('M j', $row->post_id)
        ];
    }
    return $data;
}

add_action('rest_api_init', function () {
    register_rest_route('mediumclone/v1', '/mute-author', [
        'methods' => 'POST',
        'callback' => 'mc_handle_mute_author',
        'permission_callback' => function () {
            return is_user_logged_in(); }
        ]);
    });

function mc_handle_mute_author($request)
{
    $author_id = intval($request['author_id']);
    $user_id = get_current_user_id();

    // On récupère la liste actuelle des auteurs masqués (array)
    $muted_authors = get_user_meta($user_id, 'muted_authors', true) ?: [];

    if (!in_array($author_id, $muted_authors)) {
        $muted_authors[] = $author_id;
        update_user_meta($user_id, 'muted_authors', $muted_authors);
    }

    return ['success' => true, 'muted' => $muted_authors];
}