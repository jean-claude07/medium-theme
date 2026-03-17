<?php get_header(); ?>

<?php while (have_posts()):
    the_post();
    $author_id = get_the_author_meta('ID');
    $word_count = str_word_count(strip_tags(get_the_content()));
    $reading_time = max(1, round($word_count / 200));
    $rs = mc_get_reactions(get_the_ID());
    $is_following = function_exists('mc_is_following') && is_user_logged_in() ? mc_is_following(get_current_user_id(), $author_id) : false;
    ?>

    <!-- Reading Progress Bar -->
    <div id="reading-progress" class="reading-progress-bar" style="width:0%;transition:width 0.1s linear;"></div>

    <article class="relative"
        x-data="{ ...reactions(<?php the_ID(); ?>), ...commentSystem(<?php the_ID(); ?>, <?php echo get_comments_number(); ?>) }">

        <!-- ─ Floating Sidebar is inside the content wrapper below ─ -->

        <!-- ─── Main Content Container ─── -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 lg:py-16 relative">

            <!-- ─── Floating Sidebar (Desktop) — absolute wrapper + sticky inner ─── -->
            <div class="hidden lg:block absolute top-0 bottom-0 right-full pr-6 w-16 pointer-events-none">
                <div class="sticky top-32 flex flex-col items-center gap-4 py-5 px-3 rounded-2xl
                        bg-white/90 dark:bg-dark-surface/95 backdrop-blur-sm
                        border border-light-border dark:border-dark-border shadow-sm
                        pointer-events-auto w-max">

                    <!-- Clap -->
                    <div class="flex flex-col items-center gap-1">
                        <button @click="react('clap')"
                            class="group w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-200 hover:bg-primary/10"
                            :class="hasReacted('clap') ? 'text-primary bg-primary/10' : 'text-gray-400 hover:text-primary'">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                        </button>
                        <span class="text-[11px] font-medium text-gray-400 tabular-nums" x-text="counts.clap">0</span>
                    </div>

                    <!-- Love -->
                    <div class="flex flex-col items-center gap-1">
                        <button @click="react('love')"
                            class="group w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-200 hover:bg-red-50 dark:hover:bg-red-900/20"
                            :class="hasReacted('love') ? 'text-red-500 bg-red-50 dark:bg-red-900/20' : 'text-gray-400 hover:text-red-400'">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <span class="text-[11px] font-medium text-gray-400 tabular-nums" x-text="counts.love">0</span>
                    </div>

                    <!-- Comments -->
                    <div class="flex flex-col items-center gap-1">
                        <button @click="open = true; scrollToForm()"
                            class="group w-10 h-10 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </button>
                        <span class="text-[11px] font-medium text-gray-400 tabular-nums" x-text="count">0</span>
                    </div>

                    <div class="w-5 h-px bg-gray-200 dark:bg-gray-700 rounded"></div>

                    <!-- Bookmark -->
                    <button @click="bookmark()"
                        class="group w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                        :class="isBookmarked ? 'text-primary' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110"
                            :fill="isBookmarked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>
                </div>
            </div>


            <!-- ─── Header ─── -->
            <header class="mb-10">

                <!-- Category / Topic pill -->
                <?php $categories = get_the_category();
                if ($categories): ?>
                    <a href="<?php echo get_category_link($categories[0]->term_id); ?>"
                        class="inline-block text-xs font-semibold uppercase tracking-widest text-primary mb-5">
                        <?php echo $categories[0]->name; ?>
                    </a>
                <?php endif; ?>

                <!-- Title -->
                <h1
                    class="font-serif text-3xl md:text-4xl font-bold leading-tight tracking-tight text-dark-bg dark:text-light-bg mb-5">
                    <?php the_title(); ?>
                </h1>

                <!-- Excerpt / subtitle -->
                <?php if (has_excerpt()): ?>
                    <p class="text-lg text-gray-500 dark:text-gray-400 leading-relaxed mb-7 font-sans font-normal">
                        <?php echo get_the_excerpt(); ?>
                    </p>
                <?php endif; ?>

                <!-- Author meta row -->
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <a href="<?php echo get_author_posts_url($author_id); ?>">
                            <?php echo get_avatar($author_id, 40, '', '', ['class' => 'rounded-full ring-2 ring-primary/20 block']); ?>
                        </a>
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="<?php echo get_author_posts_url($author_id); ?>"
                                    class="text-sm font-semibold text-dark-bg dark:text-light-bg hover:text-primary dark:hover:text-primary transition-colors">
                                    <?php echo get_the_author(); ?>
                                </a>
                                <?php if (!is_user_logged_in() || get_current_user_id() !== (int) $author_id): ?>
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <button @click="toggleFollow" x-data="{
                                        following: <?php echo $is_following ? 'true' : 'false'; ?>,
                                        toggleFollow() {
                                            if (!mediumCloneData.nonce) {
                                                window.location.href = '<?php echo mc_get_page_url('login'); ?>';
                                                return;
                                            }
                                            this.following = !this.following;
                                            fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/follow', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': mediumCloneData.nonce },
                                                body: JSON.stringify({ user_id: <?php echo $author_id; ?> })
                                            });
                                        }
                                    }" x-text="following ? 'Following' : 'Follow'"
                                        class="text-xs text-primary hover:text-emerald-600 font-semibold transition-colors"></button>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-0.5">
                                <span><?php echo get_the_date('M j, Y'); ?></span>
                                <span>·</span>
                                <span><?php echo $reading_time; ?> min read</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: share & bookmark (mobile) -->
                    <div class="flex items-center gap-1 lg:hidden">
                        <button @click="bookmark()"
                            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-primary hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                            :class="isBookmarked ? 'text-primary' : ''">
                            <svg class="w-4 h-4" :fill="isBookmarked ? 'currentColor' : 'none'" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                        </button>
                        <button
                            onclick="navigator.share && navigator.share({title: '<?php echo esc_js(get_the_title()); ?>', url: window.location.href})"
                            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- ─── Featured Media ─── -->
            <?php
            $youtube_url = get_post_meta(get_the_ID(), 'mc_youtube_url', true);
            $social_link = get_post_meta(get_the_ID(), 'mc_social_link', true);
            if ($youtube_url || $social_link || has_post_thumbnail()):
                ?>
                <figure class="mb-10 -mx-4 sm:-mx-6 overflow-hidden rounded-none sm:rounded-2xl">
                    <?php if ($youtube_url):
                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtube_url, $match);
                        $video_id = $match[1] ?? '';
                        if ($video_id): ?>
                            <div class="aspect-video bg-black">
                                <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?php echo $video_id; ?>"
                                    frameborder="0" allowfullscreen></iframe>
                            </div>
                        <?php endif;
                    elseif ($social_link): ?>
                        <div
                            class="p-10 bg-gray-50 dark:bg-dark-surface border border-light-border dark:border-dark-border flex flex-col items-center text-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <a href="<?php echo esc_url($social_link); ?>" target="_blank" rel="noopener" class="btn text-sm">View
                                on External Site ↗</a>
                        </div>
                    <?php elseif (has_post_thumbnail()):
                        the_post_thumbnail('large', ['class' => 'w-full h-auto object-cover max-h-[520px]']);
                        if (get_the_post_thumbnail_caption()): ?>
                            <figcaption class="text-center text-xs text-gray-400 mt-3 px-4">
                                <?php echo get_the_post_thumbnail_caption(); ?>
                            </figcaption>
                        <?php endif;
                    endif; ?>
                </figure>
            <?php endif; ?>

            <!-- ─── Article Body ─── -->
            <div class="prose prose-lg dark:prose-invert max-w-none mb-14
            prose-headings:font-serif prose-headings:font-bold prose-headings:tracking-tight
            prose-a:text-primary prose-a:no-underline hover:prose-a:underline hover:prose-a:text-emerald-700
            prose-blockquote:border-primary prose-blockquote:font-serif prose-blockquote:not-italic
            prose-img:rounded-xl prose-img:shadow-md
            font-serif leading-relaxed">
                <?php the_content(); ?>
            </div>

            <!-- ─── Tags ─── -->
            <?php $tags = get_the_tags();
            if ($tags): ?>
                <div class="flex flex-wrap gap-2 mb-10">
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?php echo get_tag_link($tag->term_id); ?>"
                            class="px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-full hover:bg-primary/10 hover:text-primary transition-colors">
                            <?php echo $tag->name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ─── Bottom Reactions Bar ─── -->
            <div
                class="flex items-center justify-between py-5 border-t border-b border-light-border dark:border-dark-border mb-14">
                <div class="flex items-center gap-1">

                    <!-- Clap -->
                    <button @click="react('clap')"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="hasReacted('clap') ? 'text-primary' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'">
                        <svg class="w-5 h-5" :fill="hasReacted('clap') ? 'currentColor' : 'none'" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                        <span x-text="counts.clap" class="tabular-nums">0</span>
                    </button>

                    <!-- Love -->
                    <button @click="react('love')"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-red-50 dark:hover:bg-red-900/20"
                        :class="hasReacted('love') ? 'text-red-500' : 'text-gray-500 hover:text-red-400'">
                        <svg class="w-5 h-5" :fill="hasReacted('love') ? 'currentColor' : 'none'" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span x-text="counts.love" class="tabular-nums">0</span>
                    </button>

                    <!-- Comments -->
                    <button @click="open = true; scrollToForm()"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span x-text="count" class="tabular-nums">0</span>
                    </button>
                </div>

                <div class="flex items-center gap-1">
                    <!-- Bookmark -->
                    <button @click="bookmark()"
                        class="w-9 h-9 flex items-center justify-center rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="isBookmarked ? 'text-primary' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                        <svg class="w-5 h-5" :fill="isBookmarked ? 'currentColor' : 'none'" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>

                    <!-- Share -->
                    <button x-data="{ 
        copied: false,
        share() {
            const data = { title: '<?php echo esc_js(get_the_title()); ?>', url: window.location.href };
            if (navigator.share) {
                navigator.share(data);
            } else {
                navigator.clipboard.writeText(data.url);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }
        }
    }" @click="share()" :class="copied ? 'text-primary' : 'text-gray-400'"
                        class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">

                        <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>

                        <svg x-show="copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ─── Author Card ─── -->
            <div
                class="flex items-start gap-5 p-6 rounded-2xl border border-light-border dark:border-dark-border bg-gray-50/50 dark:bg-dark-surface/50 mb-12">
                <a href="<?php echo get_author_posts_url($author_id); ?>" class="flex-none">
                    <?php echo get_avatar($author_id, 56, '', '', ['class' => 'rounded-xl block']); ?>
                </a>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Written by</p>
                    <a href="<?php echo get_author_posts_url($author_id); ?>"
                        class="font-bold text-base text-dark-bg dark:text-light-bg hover:text-primary transition-colors">
                        <?php echo get_the_author(); ?>
                    </a>
                    <p class="text-xs text-gray-400 mt-0.5 mb-3">
                        <?php echo mc_get_follower_count($author_id) ?? 0; ?> followers
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-2">
                        <?php echo get_the_author_meta('description') ?: 'A writer on MediumClone.'; ?>
                    </p>
                </div>
                <div class="flex-none">
                    <?php if (!is_user_logged_in()): ?>
                        <a class="btn text-xs px-4 py-2" href="<?php echo mc_get_page_url('login'); ?>">
                            Sign In to Follow
                        </a>
                    <?php elseif (get_current_user_id() === (int) $author_id): ?>
                        <a class="btn text-xs px-4 py-2" href="<?php echo mc_get_page_url('profile-edit'); ?>">
                            Edit Profile
                        </a>
                    <?php else: ?>
                        <button class="btn w-full justify-center" @click="toggleFollow" x-data="{
                following: <?php echo $is_following ? 'true' : 'false'; ?>,
                toggleFollow() {
                    if (!mediumCloneData.nonce) {
                        window.location.href = '<?php echo mc_get_page_url('login'); ?>';
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
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /max-w-2xl -->

        <!-- ─── Comments Slide‑over Panel ─── -->
        <section x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true"
            style="display:none;">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[2px]" x-show="open"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false">
            </div>

            <!-- Panel -->
            <div class="fixed inset-y-0 right-0 w-full max-w-lg xl:max-w-xl" x-show="open"
                x-transition:enter="transform ease-out duration-300" x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0" x-transition:leave="transform ease-in duration-200"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                <div class="h-full flex flex-col bg-white dark:bg-[#111] shadow-2xl">

                    <!-- Panel header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-white/80 dark:bg-[#111]/80 backdrop-blur-md sticky top-0 z-10">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-semibold text-dark-bg dark:text-light-bg">Responses</h2>
                            <span
                                class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full font-medium tabular-nums"
                                x-text="count"></span>
                        </div>
                        <button @click="open = false"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Panel body -->
                    <div x-ref="commentContainer" class="flex-1 overflow-y-auto">
                        <div class="px-6 py-8">
                            <?php
                            if (comments_open() || get_comments_number()):
                                $custom_comments = locate_template('template-parts/comments-medium.php');
                                if ($custom_comments) {
                                    include($custom_comments);
                                } else {
                                    comments_template();
                                }
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </article>

    <script>
        // Reading progress
        window.addEventListener('scroll', () => {
            const el = document.getElementById('reading-progress');
            if (!el) return;
            const doc = document.documentElement;
            const scrolled = doc.scrollTop / (doc.scrollHeight - doc.clientHeight);
            el.style.width = Math.min(100, scrolled * 100) + '%';
        }, { passive: true });

        // Alpine data
        document.addEventListener('alpine:init', () => {
            Alpine.data('reactions', (postId) => ({
                postId,
                counts: {
                    clap: <?php echo intval($rs['clap']); ?>,
                    love: <?php echo intval($rs['love']); ?>
                },
                userReactions: <?php
                $user_id = get_current_user_id();
                global $wpdb;
                if ($user_id) {
                    $user_r = $wpdb->get_col($wpdb->prepare(
                        "SELECT reaction_type FROM {$wpdb->prefix}mc_reactions WHERE post_id = %d AND user_id = %d",
                        get_the_ID(),
                        $user_id
                    ));
                    echo json_encode($user_r);
                } else {
                    echo '[]';
                }
                ?>,
                isBookmarked: <?php echo json_encode(mc_is_bookmarked(get_current_user_id(), get_the_ID())); ?>,

                async react(type) {
                    if (!mediumCloneData?.nonce) { alert('Please sign in to react.'); return; }
                    const wasReacted = this.hasReacted(type);
                    if (wasReacted) { this.counts[type]--; this.userReactions = this.userReactions.filter(r => r !== type); }
                    else { this.counts[type]++; this.userReactions.push(type); }
                    try {
                        const res = await fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/react', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': mediumCloneData.nonce },
                            body: JSON.stringify({ post_id: this.postId, type })
                        });
                        if (!res.ok) throw new Error('Network error');
                    } catch (e) { console.error(e); }
                },

                hasReacted(type) { return this.userReactions.includes(type); },

                async bookmark() {
                    if (!mediumCloneData?.nonce) { alert('Please sign in to bookmark.'); return; }
                    this.isBookmarked = !this.isBookmarked;
                    try {
                        await fetch(mediumCloneData.root_url + '/wp-json/mediumclone/v1/bookmark', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': mediumCloneData.nonce },
                            body: JSON.stringify({ post_id: this.postId })
                        });
                    } catch (e) { console.error(e); }
                }
            }));
        });
    </script>

<?php endwhile; ?>

<?php get_footer(); ?>