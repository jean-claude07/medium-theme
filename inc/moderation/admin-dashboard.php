<?php
/**
 * Admin Moderation Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'mc_register_moderation_dashboard');

function mc_register_moderation_dashboard() {
    $hook = add_menu_page(
        'Community Moderation',
        'Moderation',
        'manage_options',
        'mc-moderation',
        'mc_render_moderation_dashboard',
        'dashicons-shield',
        30
    );

    add_action("admin_print_scripts-$hook", 'mc_moderation_dashboard_assets');
}

function mc_moderation_dashboard_assets() {
    // Load Tailwind CSS from CDN specifically for this page for a modern look
    wp_enqueue_script('tailwindcss-cdn', 'https://cdn.tailwindcss.com', [], '3.4.1', false);
    
    // Add custom config to prevent conflicts with WP Admin styles
    wp_add_inline_script('tailwindcss-cdn', '
        tailwind.config = {
            corePlugins: { preflight: false },
            important: ".mc-admin-dashboard"
        }
    ');
}

function mc_render_moderation_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $nonce = wp_create_nonce('mc_admin_moderation_nonce');
    
    // Query Reported Posts
    $reported_posts = new WP_Query([
        'post_type' => 'post',
        'post_status' => ['publish', 'pending', 'draft'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_mc_report_count',
                'value' => 0,
                'compare' => '>'
            ]
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => '_mc_report_count',
        'order' => 'DESC'
    ]);

    // Query Restricted Users
    $restricted_users = get_users([
        'meta_key' => 'mc_account_status',
        'meta_value' => 'restricted'
    ]);
    ?>
    <div class="wrap mc-admin-dashboard">
        <h1 class="text-3xl font-bold mb-8 flex items-center gap-3">
            <span class="dashicons dashicons-shield text-4xl text-blue-600 mt-1"></span>
            Community Moderation
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Reported Posts Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 m-0">Reported Posts</h2>
                </div>
                <div class="p-6">
                    <?php if ($reported_posts->have_posts()): ?>
                        <div class="space-y-4">
                            <?php while ($reported_posts->have_posts()): $reported_posts->the_post(); 
                                $count = get_post_meta(get_the_ID(), '_mc_report_count', true);
                                $author_id = get_the_author_meta('ID');
                            ?>
                                <div class="border border-gray-100 rounded-lg p-4 bg-gray-50 flex flex-col gap-3" id="post-row-<?php echo get_the_ID(); ?>">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="<?php echo get_edit_post_link(); ?>" target="_blank" class="font-semibold text-lg text-blue-600 hover:underline">
                                                <?php the_title(); ?>
                                            </a>
                                            <p class="text-sm text-gray-500 mt-1">
                                                Author: <a href="<?php echo get_edit_user_link($author_id); ?>" class="hover:underline"><?php the_author(); ?></a> | 
                                                Status: <span class="uppercase text-xs font-bold px-2 py-0.5 rounded bg-gray-200"><?php echo get_post_status(); ?></span>
                                            </p>
                                        </div>
                                        <div class="flex-none text-center bg-red-100 text-red-700 rounded px-3 py-1 font-bold">
                                            <?php echo intval($count); ?> Reports
                                        </div>
                                    </div>
                                    <div class="flex gap-2 pt-2 border-t border-gray-200">
                                        <a href="<?php the_permalink(); ?>" target="_blank" class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded border border-gray-300 bg-white">View Post</a>
                                        <button onclick="mcModerateAction('ignore', <?php echo get_the_ID(); ?>)" class="text-sm text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded border border-blue-200 bg-white">Ignore</button>
                                        <?php if (get_post_status() !== 'pending'): ?>
                                            <button onclick="mcModerateAction('unpublish', <?php echo get_the_ID(); ?>)" class="text-sm text-orange-600 hover:bg-orange-50 px-3 py-1.5 rounded border border-orange-200 bg-white">Unpublish</button>
                                        <?php endif; ?>
                                        <button onclick="mcModerateAction('ban', <?php echo $author_id; ?>, <?php echo get_the_ID(); ?>)" class="text-sm text-red-600 hover:bg-red-50 px-3 py-1.5 rounded border border-red-200 bg-white ml-auto">Ban Author</button>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No posts are currently reported.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Restricted Users Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 m-0">Restricted Users</h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($restricted_users)): ?>
                        <div class="space-y-4">
                            <?php foreach ($restricted_users as $user): ?>
                                <div class="border border-gray-100 rounded-lg p-4 bg-gray-50 flex justify-between items-center" id="user-row-<?php echo $user->ID; ?>">
                                    <div class="flex items-center gap-3">
                                        <?php echo get_avatar($user->ID, 40, '', '', ['class' => 'rounded-full']); ?>
                                        <div>
                                            <a href="<?php echo get_edit_user_link($user->ID); ?>" class="font-semibold text-blue-600 hover:underline">
                                                <?php echo esc_html($user->display_name); ?>
                                            </a>
                                            <p class="text-sm text-gray-500 m-0"><?php echo esc_html($user->user_email); ?></p>
                                        </div>
                                    </div>
                                    <button onclick="mcModerateAction('unban', <?php echo $user->ID; ?>)" class="text-sm text-green-600 hover:bg-green-50 px-3 py-1.5 rounded border border-green-200 bg-white font-medium">Unban User</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No users are currently restricted.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
    function mcModerateAction(action, id, postId = null) {
        if (!confirm('Are you sure you want to perform this action?')) return;

        let actionHook = '';
        let data = new FormData();
        data.append('security', '<?php echo $nonce; ?>');

        if (action === 'ignore') {
            actionHook = 'mc_admin_ignore_report';
            data.append('post_id', id);
        } else if (action === 'unpublish') {
            actionHook = 'mc_admin_unpublish_post';
            data.append('post_id', id);
        } else if (action === 'ban') {
            actionHook = 'mc_admin_ban_user';
            data.append('user_id', id);
        } else if (action === 'unban') {
            actionHook = 'mc_admin_unban_user';
            data.append('user_id', id);
        }
        
        data.append('action', actionHook);

        fetch(ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                // UI Updates
                if (action === 'ignore' || action === 'unpublish') {
                    const el = document.getElementById('post-row-' + id);
                    if (el && action === 'ignore') el.remove();
                    if (el && action === 'unpublish') window.location.reload(); // Reload to show new status
                } else if (action === 'ban') {
                    const el = document.getElementById('post-row-' + postId);
                    if (el) el.style.opacity = '0.5';
                    window.location.reload(); // Reload to move user to restricted list
                } else if (action === 'unban') {
                    const el = document.getElementById('user-row-' + id);
                    if (el) el.remove();
                }
            } else {
                alert(result.data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('A network error occurred.');
        });
    }
    </script>
    <?php
}
