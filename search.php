<?php
/**
 * Search Results Template
 */
get_header();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="mb-10 animate-on-scroll">
        <h1 class="text-3xl font-bold font-serif mb-2">
            <?php if ( have_posts() ) : ?>
                Results for <span class="text-primary">"<?php echo esc_html( get_search_query() ); ?>"</span>
            <?php else : ?>
                No results for <span class="text-primary">"<?php echo esc_html( get_search_query() ); ?>"</span>
            <?php endif; ?>
        </h1>
        <p class="text-gray-500 text-sm"><?php echo $wp_query->found_posts; ?> stories found</p>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="space-y-10">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'templates/components/article-card' ); ?>
            <?php endwhile; ?>
        </div>

        <div class="mt-12 py-8 border-t border-light-border dark:border-dark-border">
            <?php
            the_posts_pagination( [
                'prev_text' => '&larr; Newer',
                'next_text' => 'Older &rarr;',
                'class'     => 'flex items-center justify-between',
            ] );
            ?>
        </div>
    <?php else : ?>
        <div class="py-20 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="text-lg font-medium mb-2">No stories found</p>
            <p class="text-sm">Try different keywords, or <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-primary hover:underline">browse all stories</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
