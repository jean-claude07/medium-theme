<?php
/**
 * Moderation Module
 * Handles post reporting and user restrictions.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX actions for reporting posts
 */
add_action('wp_ajax_mc_report_post', 'mc_handle_report_post');

function mc_handle_report_post() {
    check_ajax_referer('mc_comment_nonce', 'security'); // Re-using existing nonce for simplicity or create a new one

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to report a post.']);
    }

    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();

    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID.']);
    }

    // Check if user is restricted
    if (mc_is_user_restricted($user_id)) {
        wp_send_json_error(['message' => 'Your account is restricted.']);
    }

    // Check if already reported by this user
    $reporters = get_post_meta($post_id, '_mc_reporters', true);
    if (!is_array($reporters)) {
        $reporters = [];
    }

    if (in_array($user_id, $reporters)) {
        wp_send_json_error(['message' => 'You have already reported this post.']);
    }

    // Add reporter and increment count
    $reporters[] = $user_id;
    update_post_meta($post_id, '_mc_reporters', $reporters);

    $count = count($reporters);
    update_post_meta($post_id, '_mc_report_count', $count);

    // Check threshold
    $moderated = false;
    if ($count >= 5) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'pending'
        ]);
        $moderated = true;
        
        // Notify Admin
        $post_title = get_the_title($post_id);
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, "Post Moderated: $post_title", "The post '$post_title' has been set to pending after receiving $count reports.");
    }

    wp_send_json_success([
        'message' => $moderated ? 'Post reported and sent for moderation.' : 'Post reported successfully.',
        'count' => $count,
        'moderated' => $moderated
    ]);
}

/**
 * Check if a user is restricted
 */
function mc_is_user_restricted($user_id) {
    $status = get_user_meta($user_id, 'mc_account_status', true);
    return $status === 'restricted';
}

/**
 * Admin: Add report count column to posts list
 */
add_filter('manage_post_posts_columns', 'mc_add_reports_column');
function mc_add_reports_column($columns) {
    $columns['mc_reports'] = 'Reports';
    return $columns;
}

add_action('manage_post_posts_custom_column', 'mc_fill_reports_column', 10, 2);
function mc_fill_reports_column($column, $post_id) {
    if ($column === 'mc_reports') {
        $count = get_post_meta($post_id, '_mc_report_count', true);
        echo $count ? '<span style="color:red;font-weight:bold;">' . intval($count) . '</span>' : '0';
    }
}

/**
 * Admin: Add restriction toggle to user profile
 */
add_action('show_user_profile', 'mc_add_user_moderation_fields');
add_action('edit_user_profile', 'mc_add_user_moderation_fields');

function mc_add_user_moderation_fields($user) {
    if (!current_user_can('administrator')) return;
    
    $status = get_user_meta($user->ID, 'mc_account_status', true);
    ?>
    <h3>Moderation</h3>
    <table class="form-table">
        <tr>
            <th><label for="mc_account_status">Account Status</label></th>
            <td>
                <select name="mc_account_status" id="mc_account_status">
                    <option value="active" <?php selected($status, 'active'); ?>>Active</option>
                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending Activation</option>
                    <option value="restricted" <?php selected($status, 'restricted'); ?>>Restricted (Banned)</option>
                </select>
                <p class="description">Restricted users cannot log in or post content.</p>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'mc_save_user_moderation_fields');
add_action('edit_user_profile_update', 'mc_save_user_moderation_fields');

function mc_save_user_moderation_fields($user_id) {
    if (!current_user_can('administrator')) return;
    
    if (isset($_POST['mc_account_status'])) {
        update_user_meta($user_id, 'mc_account_status', sanitize_text_field($_POST['mc_account_status']));
    }
}

/**
 * Admin Quick Actions (AJAX)
 */
add_action('wp_ajax_mc_admin_ignore_report', 'mc_handle_admin_ignore_report');
add_action('wp_ajax_mc_admin_unpublish_post', 'mc_handle_admin_unpublish_post');
add_action('wp_ajax_mc_admin_ban_user', 'mc_handle_admin_ban_user');
add_action('wp_ajax_mc_admin_unban_user', 'mc_handle_admin_unban_user');

function mc_check_admin_ajax() {
    check_ajax_referer('mc_admin_moderation_nonce', 'security');
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
}

function mc_handle_admin_ignore_report() {
    mc_check_admin_ajax();
    $post_id = intval($_POST['post_id']);
    
    if ($post_id) {
        delete_post_meta($post_id, '_mc_report_count');
        delete_post_meta($post_id, '_mc_reporters');
        wp_send_json_success(['message' => 'Reports ignored successfully.']);
    }
    wp_send_json_error(['message' => 'Invalid post ID.']);
}

function mc_handle_admin_unpublish_post() {
    mc_check_admin_ajax();
    $post_id = intval($_POST['post_id']);
    
    if ($post_id) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'pending'
        ]);
        wp_send_json_success(['message' => 'Post unpublished.']);
    }
    wp_send_json_error(['message' => 'Invalid post ID.']);
}

function mc_handle_admin_ban_user() {
    mc_check_admin_ajax();
    $user_id = intval($_POST['user_id']);
    
    if ($user_id && $user_id !== get_current_user_id()) { // Prevent banning oneself
        update_user_meta($user_id, 'mc_account_status', 'restricted');
        wp_send_json_success(['message' => 'User restricted.']);
    }
    wp_send_json_error(['message' => 'Invalid user ID.']);
}

function mc_handle_admin_unban_user() {
    mc_check_admin_ajax();
    $user_id = intval($_POST['user_id']);
    
    if ($user_id) {
        update_user_meta($user_id, 'mc_account_status', 'active');
        wp_send_json_success(['message' => 'User restored.']);
    }
    wp_send_json_error(['message' => 'Invalid user ID.']);
}
