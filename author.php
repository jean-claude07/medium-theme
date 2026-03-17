<?php get_header(); ?>

<?php
$author_id = get_query_var('author');
$author = get_userdata($author_id);
$follower_count = mc_get_follower_count($author_id);
$is_following = is_user_logged_in() ? mc_is_following(get_current_user_id(), $author_id) : false;
$badges = function_exists('mc_get_user_badges') ? mc_get_user_badges($author_id) : [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Author Info (Sidebar for desktop, top for mobile) -->
        <div class="lg:col-span-4 lg:col-start-9 lg:order-last space-y-8 animate-on-scroll">
            <div>
                <?php echo get_avatar($author_id, 120, '', '', ['class' => 'rounded-full mb-6']); ?>
                <h1 class="text-2xl font-bold font-serif mb-2">
                    <?php echo $author->display_name; ?>
                </h1>
                <p class="text-gray-500 mb-6">
                    <?php echo $follower_count; ?> Followers
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    <?php echo get_the_author_meta('description', $author_id) ?: 'No bio available yet.'; ?>
                </p>

                <div class="flex gap-4">
                    <?php if (get_current_user_id() === (int)$author_id): ?>
                    <a href="<?php echo mc_get_page_url('profile-edit'); ?>"
                        class="btn-outline w-full justify-center flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                            </path>
                        </svg>
                        Edit Profile
                    </a>
                    <?php
else: ?>
                    <button class="btn w-full justify-center" @click="toggleFollow" x-data="{
                            following: <?php echo $is_following ? 'true' : 'false'; ?>,
                            toggleFollow() {
                                if (!mediumCloneData.nonce) {
                                    alert('Please login to follow.');
                                    return;
                                }
                                this.following = !this.following;
                                fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/follow', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-WP-Nonce': mediumCloneData.nonce
                                    },
                                    body: JSON.stringify({ user_id: <?php echo $author_id; ?> })
                                });
                            }
                        }" x-text="following ? 'Following' : 'Follow'"
                        :class="following ? '!bg-gray-200 !text-gray-800 dark:!bg-gray-700 dark:!text-gray-200' : ''">
                        <?php echo $is_following ? 'Following' : 'Follow'; ?>
                    </button>
                    <button class="btn-outline px-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </button>
                    <?php
endif; ?>

                </div>
            </div>

            <?php if (!empty($badges)): ?>
            <div class="pt-8 border-t border-light-border dark:border-dark-border">
                <h3 class="font-bold mb-4 uppercase text-xs tracking-wider text-gray-500">Badges</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($badges as $badge): ?>
                    <span
                        class="px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full flex items-center gap-1 border border-emerald-200 dark:border-emerald-800">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 5a1 1 0 112 0v4h2a1 1 0 110 2h-3a1 1 0 01-1-1V5z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <?php echo ucwords(str_replace('_', ' ', $badge)); ?>
                    </span>
                    <?php
    endforeach; ?>
                </div>
            </div>
            <?php
endif; ?>
        </div>

        <!-- Latest Posts -->
        <div class="lg:col-span-8 lg:col-start-1 lg:order-first" x-data="{ tab: 'home' }">
            <div
                class="border-b border-light-border dark:border-dark-border mb-8 pb-4 flex items-center gap-8 text-sm font-medium">
                <button @click="tab = 'home'"
                    :class="tab === 'home' ? 'text-dark-bg dark:text-light-bg border-b-2 border-dark-bg dark:border-light-bg' : 'text-gray-500'"
                    class="pb-4 -mb-[18px] transition-all">
                    Home
                </button>
                <button @click="tab = 'about'"
                    :class="tab === 'about' ? 'text-dark-bg dark:text-light-bg border-b-2 border-dark-bg dark:border-light-bg' : 'text-gray-500'"
                    class="pb-4 -mb-[18px] transition-all">
                    About
                </button>
            </div>

            <div x-show="tab === 'home'" x-transition>
                <?php if (have_posts()): ?>
                <div class="space-y-12">
                    <?php while (have_posts()):
        the_post(); ?>
                    <?php get_template_part('templates/components/article-card'); ?>
                    <?php
    endwhile; ?>
                </div>
                <div class="mt-12 py-8 flex justify-between">
                    <?php posts_nav_link(); ?>
                </div>
                <?php
else: ?>
                <div class="py-12 text-center text-gray-500">
                    <p>
                        <?php echo $author->display_name; ?> n'a pas encore publié d'histoires.
                    </p>
                </div>
                <?php
endif; ?>
            </div>

            <div x-show="tab === 'about'" x-transition style="display: none;">
                <div class="space-y-12">
                    <section>
                        <h3 class="text-xl font-bold font-serif mb-4">À propos de
                            <?php echo $author->display_name; ?>
                        </h3>
                        <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                            <?php echo wpautop(get_the_author_meta('description', $author_id)); ?>
                        </div>
                    </section>

                    <section class="pt-8 border-t border-light-border dark:border-dark-border">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-6">Followers (
                            <?php echo $follower_count; ?>)
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <?php
$followers = function_exists('mc_get_followers_list') ? mc_get_followers_list($author_id) : [];
if (!empty($followers)):
    foreach ($followers as $follower): ?>
                            <a href="<?php echo get_author_posts_url($follower->ID); ?>"
                                class="flex items-center gap-3 group">
                                <?php echo get_avatar($follower->ID, 40, '', '', ['class' => 'rounded-full']); ?>
                                <span class="text-sm font-medium group-hover:underline">
                                    <?php echo $follower->display_name; ?>
                                </span>
                            </a>
                            <?php
    endforeach;
else: ?>
                            <p class="text-sm text-gray-400">Aucun abonné pour le moment.</p>
                            <?php
endif; ?>
                        </div>
                    </section>

                    <?php if (get_current_user_id() === (int)$author_id): ?>
                    <section class="pt-8 border-t border-light-border dark:border-dark-border">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-6">Tes Bookmarks</h3>
                        <?php
    $bookmarks = function_exists('mc_get_user_bookmarks') ? mc_get_user_bookmarks($author_id) : [];
    if (!empty($bookmarks)): ?>
                        <div class="space-y-6">
                            <?php foreach ($bookmarks as $post_id):
            $post = get_post($post_id); ?>
                            <div class="flex flex-col">
                                <a href="<?php echo get_permalink($post_id); ?>" class="font-bold hover:underline">
                                    <?php echo get_the_title($post_id); ?>
                                </a>
                                <span class="text-xs text-gray-400">
                                    <?php echo get_the_date('', $post_id); ?>
                                </span>
                            </div>
                            <?php
        endforeach; ?>
                        </div>
                        <?php
    else: ?>
                        <p class="text-sm text-gray-400">Tu n'as pas encore enregistré d'articles.</p>
                        <?php
    endif; ?>
                    </section>
                    <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>