<?php get_header(); ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <section class="mb-16 animate-on-scroll">
        <div
            class="card p-8 md:p-12 lg:flex lg:items-center lg:justify-between bg-gradient-to-r from-light-surface to-white dark:from-dark-surface dark:to-dark-bg border-none shadow-md relative overflow-hidden">
            <div class="lg:w-1/2 relative z-10">
                <h1
                    class="text-5xl md:text-7xl font-serif font-bold tracking-tight mb-6 text-dark-bg dark:text-light-bg">
                    Stay curious.</h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-lg">
                    Discover stories, thinking, and expertise from writers on any topic.
                </p>
                <?php
$start_url = is_user_logged_in() ? '#articles' : mc_get_page_url('login');
?>
                <a href="<?php echo esc_url($start_url); ?>" class="btn px-8 py-3 text-lg">Start Reading</a>
            </div>
            <div class="hidden lg:block lg:w-1/3 relative">
                <div
                    class="aspect-square bg-gradient-to-tr from-primary/20 to-secondary/20 rounded-full blur-3xl opacity-50 absolute -right-10 -top-10 pointer-events-none">
                </div>
                <img src="https://images.unsplash.com/photo-1542435503-91dce532b225?auto=format&fit=crop&q=80&w=800"
                    class="rounded-2xl shadow-2xl relative z-10 transform -rotate-3 hover:rotate-0 transition-transform duration-500 object-cover h-80 w-full"
                    alt="Hero Image">
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <div class="lg:col-span-8" id="articles">
            <div
                class="border-b border-light-border dark:border-dark-border mb-8 pb-4 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <h2 class="text-xl font-bold">Recommended for you</h2>
                    <div class="flex gap-4">
                        <a href="<?php echo esc_url(add_query_arg('feed', 'following', home_url('/'))); ?>"
                            class="text-sm font-medium <?php echo isset($_GET['feed']) && $_GET['feed'] === 'following' ? 'text-dark-bg dark:text-light-bg border-b border-current' : 'text-gray-500 hover:text-dark-bg dark:hover:text-light-bg'; ?>">
                            Following
                        </a>
                    </div>
                </div>
                <button
                    class="p-1 rounded bg-light-surface dark:bg-dark-surface hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    title="Add post">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>
            </div>

            <?php if (have_posts()): ?>
            <div class="space-y-12">
                <?php while (have_posts()):
        the_post(); ?>
                <?php get_template_part('templates/components/article-card'); ?>
                <?php
    endwhile; ?>
            </div>

            <div
                class="mt-12 py-8 border-t border-light-border dark:border-dark-border flex justify-between items-center text-sm font-medium">
                <?php
    echo get_previous_posts_link('← Newer Posts');
    echo get_next_posts_link('Older Posts →');
?>
            </div>
            <?php
else: ?>
            <div class="py-20 text-center card bg-light-surface/50 dark:bg-dark-surface/50 border-dashed border-2">
                <?php if (isset($_GET['feed']) && $_GET['feed'] === 'following'): ?>
                <div class="max-w-xs mx-auto">
                    <div class="mb-4 text-gray-400">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m16-10V7a4 4 0 00-8 0v4M5 7h.01M19 7h.01">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Your feed is empty</h3>
                    <p class="text-gray-500 mb-6 text-sm">Follow some authors to see their latest stories here.</p>
                    <a href="<?php echo home_url('/'); ?>" class="btn-outline px-4 py-2 text-sm">Discover authors</a>
                </div>
                <?php
    else: ?>
                <p>No stories found.</p>
                <?php
    endif; ?>
            </div>
            <?php
endif; ?>
        </div>

        <div class="lg:col-span-4 space-y-10">
            <div class="animate-on-scroll">
                <h3 class="font-bold mb-4 text-sm font-sans uppercase tracking-wider text-gray-900 dark:text-gray-100">
                    Discover more of what matters to you</h3>
                <div class="flex flex-wrap gap-2">
                    <?php
$categories = get_categories(['number' => 10, 'hide_empty' => true]);
foreach ($categories as $category): ?>
                    <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                        class="px-3 py-1.5 bg-light-surface dark:bg-dark-surface text-gray-600 dark:text-gray-300 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-light-border dark:border-dark-border">
                        <?php echo esc_html($category->name); ?>
                    </a>
                    <?php
endforeach; ?>
                </div>
            </div>

            <div class="animate-on-scroll">
                <h3 class="font-bold mb-4 text-sm font-sans uppercase tracking-wider text-gray-900 dark:text-gray-100">
                    Recommended authors</h3>
                <div class="space-y-4">
                    <?php
$current_user_id = get_current_user_id();
$authors = get_users([
    'number' => 3,
    'exclude' => $current_user_id ? [$current_user_id] : [],
    'orderby' => 'post_count',
    'order' => 'DESC'
]);

foreach ($authors as $author):
    $is_following = $current_user_id ? mc_is_following($current_user_id, $author->ID) : false;
?>
                    <div class="flex items-center justify-between" x-data="{ 
                            following: <?php echo $is_following ? 'true' : 'false'; ?>,
                            loading: false,
                            toggleFollow() {
                                if (!mediumCloneData.nonce) {
                                    window.location.href = '<?php echo esc_url(mc_get_page_url('login')); ?>';
                                    return;
                                }
                                this.loading = true;
                                this.following = !this.following; // Optimistic UI

                                fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/follow', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-WP-Nonce': mediumCloneData.nonce
                                    },
                                    body: JSON.stringify({ user_id: <?php echo $author->ID; ?> })
                                })
                                .finally(() => { this.loading = false; });
                            }
                        }">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <a href="<?php echo get_author_posts_url($author->ID); ?>"
                                class="flex-none w-10 h-10 block overflow-hidden rounded-full border border-gray-100 dark:border-gray-800">
                                <?php echo get_avatar($author->ID, 40, '', '', [
        'class' => 'object-cover hover:opacity-80 transition-opacity'
    ]); ?>
                            </a>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm">
                                    <a href="<?php echo get_author_posts_url($author->ID); ?>"
                                        class="hover:underline text-gray-900 dark:text-gray-100">
                                        <?php echo esc_html($author->display_name); ?>
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-500 line-clamp-1 w-32">
                                    <?php echo esc_html(get_the_author_meta('description', $author->ID)) ?: 'Writer'; ?>
                                </p>
                            </div>
                        </div>

                        <button @click="toggleFollow" :disabled="loading"
                            class="btn-outline px-3 py-1 text-xs transition-all duration-200 min-w-[80px]"
                            :class="following ? '!bg-gray-900 !text-white dark:!bg-white dark:!text-black border-transparent' : 'hover:border-gray-900 dark:hover:border-white'"
                            x-text="following ? 'Following' : 'Follow'">
                            <?php echo $is_following ? 'Following' : 'Follow'; ?>
                        </button>
                    </div>
                    <?php
endforeach; ?>
                </div>
            </div>

            <div class="sticky top-24 pt-4 border-t border-light-border dark:border-dark-border">
                <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
                    <?php
$footer_links = ['Help', 'Status', 'Writers', 'Blog', 'Careers', 'Privacy', 'Terms'];
foreach ($footer_links as $link): ?>
                    <a href="#" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                        <?php echo $link; ?>
                    </a>
                    <?php
endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>