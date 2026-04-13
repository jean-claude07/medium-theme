<?php
/**
 * Frontend Post Management Module
 * Enables users to draft, publish and delete posts from the dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'mc_register_post_routes');

function mc_register_post_routes()
{
    register_rest_route('mediumclone/v1', '/posts', array(
        'methods' => 'POST',
        'callback' => 'mc_create_or_update_post',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
}

function mc_create_or_update_post($request)
{
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
    }

    $user_id = get_current_user_id();
    $post_id = intval($request->get_param('post_id'));
    $title = sanitize_text_field($request->get_param('title'));
    $raw_content = $request->get_param('content');
    $status = sanitize_text_field($request->get_param('status'));

    // Custom Fields
    $youtube_url = esc_url_raw($request->get_param('youtube_url'));
    $social_link = esc_url_raw($request->get_param('social_link'));

    if (empty($title)) {
        return new WP_Error('missing_title', 'Post title is required', ['status' => 400]);
    }

    if (!in_array($status, ['draft', 'publish'])) {
        $status = 'draft';
    }

    $allowed_html = wp_kses_allowed_html('post');
    $allowed_html['iframe'] = array(
        'src' => true,
        'width' => true,
        'height' => true,
        'frameborder' => true,
        'allowfullscreen' => true,
        'allow' => true,
        'title' => true,
        'class' => true,
        'loading' => true,
    );
    $allowed_html['div'] = array(
        'class' => true,
        'id' => true,
        'style' => true,
    );
    $allowed_html['a'] = array(
        'href' => true,
        'title' => true,
        'class' => true,
        'target' => true,
        'rel' => true,
    );
    $allowed_html['svg'] = array(
        'class' => true,
        'fill' => true,
        'viewbox' => true,
        'stroke' => true,
        'width' => true,
        'height' => true,
    );
    $allowed_html['path'] = array(
        'd' => true,
        'stroke-linecap' => true,
        'stroke-linejoin' => true,
        'stroke-width' => true,
    );

    $content = wp_kses($raw_content, $allowed_html);

    $post_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => $status,
        'post_author' => $user_id,
        'post_type' => 'post'
    );

    if ($post_id > 0) {
        $existing_post = get_post($post_id);
        if (!$existing_post || $existing_post->post_author != $user_id) {
            return new WP_Error('unauthorized', 'You cannot edit this post', ['status' => 403]);
        }
        $post_data['ID'] = $post_id;
        $result = wp_update_post($post_data, true);
    } else {
        $result = wp_insert_post($post_data, true);

        if (!is_wp_error($result) && $status === 'publish' && function_exists('mc_award_points')) {
            mc_award_points($user_id, 'publish_post', 50);
        }
    }

    if (is_wp_error($result)) {
        return $result;
    }

    // Handle Categories
    $categories = $request->get_param('categories');
    if (!empty($categories) && is_array($categories)) {
        $category_ids = array_map('intval', $categories);
        wp_set_post_categories($result, $category_ids);
    }

    // Handle Metadata
    if (!empty($youtube_url)) {
        update_post_meta($result, 'mc_youtube_url', $youtube_url);
    } else {
        delete_post_meta($result, 'mc_youtube_url');
    }

    if (!empty($social_link)) {
        update_post_meta($result, 'mc_social_link', $social_link);
    } else {
        delete_post_meta($result, 'mc_social_link');
    }

    // Handle Featured Image Upload
    $files = $request->get_file_params();
    if (!empty($files['featured_image']) && $files['featured_image']['error'] === UPLOAD_ERR_OK) {

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($files['featured_image'], $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $attachment = array(
                'guid' => $movefile['url'],
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name($files['featured_image']['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $movefile['file'], $result);
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            set_post_thumbnail($result, $attach_id);
        }
    }

    return rest_ensure_response([
        'status' => 'success',
        'post_id' => $result,
        'url' => get_permalink($result)
    ]);
}