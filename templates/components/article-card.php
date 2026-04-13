<?php
$author_id = get_the_author_meta('ID');
$categories = get_the_category();
$is_bookmarked = mc_is_bookmarked(get_current_user_id(), get_the_ID());
$reactions = mc_get_reactions(get_the_ID());
$comment_count = get_comments_number();
?>

<article class="card p-6 flex flex-col md:flex-row gap-6 group" x-data="{ hidden: false }" x-show="!hidden"
    x-transition>
    <div class="flex-1">
        <div class="flex items-center gap-3 mb-3">
            <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>">
                <?php echo get_avatar($author_id, 24, '', '', ['class' => 'rounded-full border border-gray-200 dark:border-gray-700']); ?>
            </a>
            <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"
                class="text-sm font-medium hover:underline">
                <?php the_author(); ?>
            </a>
            <span class="text-gray-500 text-sm">&middot;</span>
            <span class="text-gray-500 text-sm">
                <?php echo get_the_date('M j'); ?>
            </span>
        </div>

        <a href="<?php the_permalink(); ?>">
            <h3 class="text-xl md:text-2xl font-bold font-serif mb-2 group-hover:underline leading-tight">
                <?php the_title(); ?>
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2 md:line-clamp-3">
                <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
            </p>
        </a>

        <div class="flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center gap-4">
                <?php if (!empty($categories)):
    $display_cats = array_slice($categories, 0, 2);
    foreach ($display_cats as $cat): ?>
                <a href="<?php echo get_category_link($cat->term_id); ?>"
                    class="bg-gray-100 dark:bg-dark-surface px-3 py-1 rounded-full text-xs hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <?php echo esc_html($cat->name); ?>
                </a>
                <?php
    endforeach;
endif; ?>
                </span>

                <div class="flex items-center gap-3">
                    <?php if ($reactions['clap'] > 0): ?>
                    <span class="flex items-center gap-1 text-gray-400" title="Claps">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                        <span class="tabular-nums"><?php echo $reactions['clap']; ?></span>
                    </span>
                    <?php endif; ?>

                    <?php if ($reactions['love'] > 0): ?>
                    <span class="flex items-center gap-1 text-gray-400" title="Loves">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span class="tabular-nums"><?php echo $reactions['love']; ?></span>
                    </span>
                    <?php endif; ?>

                    <?php if ($comment_count > 0): ?>
                    <span class="flex items-center gap-1 text-gray-400" title="Comments">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span class="tabular-nums"><?php echo $comment_count; ?></span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button class="hover:text-primary transition-colors" x-data="{ 
                        active: <?php echo json_encode($is_bookmarked); ?>,
                        async toggle() {
                            if(!mediumCloneData.nonce) return alert('Please sign in');
                            this.active = !this.active;
                            await fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/bookmark', {
                                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': mediumCloneData.nonce },
                                body: JSON.stringify({ post_id: <?php the_ID(); ?> })
                            });
                            this.$dispatch('bookmark-updated'); // Notifie la sidebar
                        }
                    }" @click.prevent="toggle()">
                    <svg class="w-5 h-5" :fill="active ? 'currentColor' : 'none'" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                </button>

                <div class="relative" x-data="{ open: false }">
                    <button @click.prevent="open = !open" @click.outside="open = false"
                        class="hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                            </path>
                        </svg>
                    </button>
                    <div x-show="open" x-transition
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-dark-surface border border-light-border dark:border-dark-border rounded-lg shadow-xl z-30 py-1 text-[13px] text-gray-700 dark:text-gray-300">
                        <button @click="hidden = true"
                            class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">Show less like
                            this</button>
                        <button @click="
                            if(confirm('Masquer tous les articles de cet auteur ?')) {
                                fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/mute-author', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': mediumCloneData.nonce },
                                    body: JSON.stringify({ author_id: <?php echo $author_id; ?> })
                                }).then(() => { 
                                    hidden = true; // Cache l'article immédiatement
                                });
                            }
                            open = false;"
                            class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 text-red-500">
                            Mute author
                        </button>
                        <hr class="my-1 border-light-border dark:border-dark-border">
                        <button @click="navigator.clipboard.writeText('<?php the_permalink(); ?>'); open = false;"
                            class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">Copy
                            link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (has_post_thumbnail()): ?>
    <div class="flex-none order-first md:order-last">
        <a href="<?php the_permalink(); ?>">
            <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title_attribute(); ?>"
                class="w-full md:w-36 lg:w-48 h-48 md:h-32 object-cover rounded-xl shadow-sm">
        </a>
    </div>
    <?php
endif; ?>
</article>