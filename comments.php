<?php
if (post_password_required())
    return;
?>

<div id="comments" class="comments-area">

    <?php if (comments_open()): ?>
        <div id="respond" class="mb-10">

            <!-- Cancel reply -->
            <div class="cancel-comment-reply mb-3">
                <?php cancel_comment_reply_link(
                    '<span class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-red-400 transition-colors cursor-pointer">'
                    . '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                    . 'Cancel reply</span>'
                ); ?>
            </div>

            <!-- Comment form wrapper -->
            <div class="relative overflow-hidden" x-data>
                <!-- Loading overlay -->
                <div x-show="loading"
                    class="absolute inset-0 bg-white/60 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-10">
                    <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>
                <?php if (is_user_logged_in()): ?>
                    <?php comment_form([
                        'title_reply' => '',
                        'title_reply_to' => '<span class="text-sm font-medium text-gray-500">Replying to <strong class="text-dark-bg dark:text-light-bg">%s</strong></span>',
                        'label_submit' => 'Respond',
                        'submit_button' => '<div class="flex justify-end mt-3"><input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" /></div>',
                        'class_submit' => 'bg-primary hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2 rounded-full transition-colors cursor-pointer',
                        'comment_field' =>
                            '<div class="border-b border-gray-200 dark:border-gray-700 pb-1 mb-3">'
                            . '<textarea id="comment" name="comment" rows="3"'
                            . ' class="w-full bg-transparent border-none focus:ring-0 text-sm text-dark-bg dark:text-light-bg placeholder-gray-400 leading-relaxed resize-none"'
                            . ' placeholder="Share your thoughts…" required></textarea>'
                            . '</div>',
                        'logged_in_as' => '',
                        'comment_notes_before' => '',
                        'submit_field' => '%1$s %2$s',
                        'id_form' => 'commentform',
                    ]); ?>
                <?php else: ?>
                    <div
                        class="p-8 bg-gray-50 dark:bg-dark-surface/50 rounded-2xl text-center border border-dashed border-light-border dark:border-dark-border mb-8">
                        <div class="mb-4 text-gray-400">
                            <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-dark-bg dark:text-light-bg mb-2">Rejoignez la conversation</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-[240px] mx-auto">
                            Connectez-vous pour partager vos réflexions et répondre.
                        </p>
                        <a href="<?php echo esc_url(mc_get_page_url('login')); ?>"
                            class="inline-flex items-center justify-center px-6 py-2.5 bg-dark-bg dark:bg-light-bg text-primary dark:text-dark-bg text-sm font-bold rounded-full hover:bg-primary dark:hover:bg-primary dark:hover:text-white transition-all duration-200">
                            Se connecter pour répondre
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Comment list -->
    <div class="space-y-0" x-ref="commentListContainer">
        <?php if (have_comments()): ?>
            <div class="comment-list divide-y divide-gray-100 dark:divide-gray-800/60">
                <?php wp_list_comments([
                    'style' => 'div',
                    'short_ping' => true,
                    'avatar_size' => 36,
                    'callback' => 'mc_custom_comment_format',
                    'reverse_top_level' => true,
                ]); ?>
            </div>

            <!-- Pagination -->
            <?php $nav = get_the_comments_navigation(['prev_text' => '← Older', 'next_text' => 'Newer →']);
            if ($nav): ?>
                <div class="flex items-center justify-between pt-8 text-sm text-gray-500">
                    <?php the_comments_navigation(['prev_text' => '← Older', 'next_text' => 'Newer →']); ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<style>
    /* Nested replies */
    .comment-list .children {
        margin-left: 2.75rem;
        padding-left: 1rem;
        border-left: 2px solid;
        border-color: rgb(243 244 246);
        /* gray-100 */
    }

    .dark .comment-list .children {
        border-color: rgb(31 41 55 / 0.5);
        /* gray-800/50 */
    }

    .mc-comment {
        padding: 1rem 0;
    }

    .mc-comment .comment-content p {
        margin: 0;
    }

    /* Cancel reply link */
    #cancel-comment-reply-link {
        display: none;
    }

    #cancel-comment-reply-link:not([style*="display: none"]) {
        display: inline-flex;
    }
</style>