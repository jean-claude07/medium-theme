<?php
/**
 * Navbar Component
 */
?>
<nav x-data="themeState" class="sticky top-0 z-40 w-full backdrop-blur flex-none transition-colors duration-500 lg:z-50 lg:border-b lg:border-light-border dark:border-dark-border bg-white/75 dark:bg-dark-bg/80">
    <div class="max-w-7xl mx-auto">
        <div class="py-4 px-4 sm:px-6 lg:px-8 border-b border-light-border dark:border-dark-border lg:border-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center">
                        <span class="text-2xl font-bold font-serif tracking-tighter text-dark-bg dark:text-light-bg">
                            Medium<span class="text-primary">Clone</span>
                        </span>
                    </a>
                    <div class="hidden md:block">
                        <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="relative items-center">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" name="s" value="<?php echo get_search_query(); ?>" class="w-64 py-2 pl-10 pr-4 bg-light-surface dark:bg-dark-surface border-none rounded-full text-sm focus:ring-1 focus:ring-primary text-dark-bg dark:text-light-bg" placeholder="Search...">
                        </form>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-light-surface dark:hover:bg-dark-surface transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-dark-bg">
                        <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        <svg x-show="darkMode" class="w-5 h-5 text-gray-300 hidden" :class="{'hidden': !darkMode}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>

                    <?php if (is_user_logged_in()): ?>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                                <?php echo get_avatar(get_current_user_id(), 32, '', '', ['class' => 'rounded-full border border-gray-200 dark:border-gray-700']); ?>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition.opacity.duration.200ms class="absolute right-0 mt-2 w-48 bg-white dark:bg-dark-surface rounded-xl shadow-lg border border-light-border dark:border-dark-border py-1 z-50 capitalize">
                                <a href="<?php echo esc_url(get_author_posts_url(get_current_user_id())); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-light-surface dark:hover:bg-dark-bg">Profile</a>
                                <a href="<?php echo esc_url(mc_get_page_url('dashboard')); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-light-surface dark:hover:bg-dark-bg">Dashboard</a>
                                <a href="<?php echo esc_url(mc_get_page_url('dashboard')); ?>" class="block px-4 py-2 text-sm text-primary hover:bg-light-surface dark:hover:bg-dark-bg font-medium">Write a story</a>
                                <hr class="my-1 border-light-border dark:border-dark-border">
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-light-surface dark:hover:bg-dark-bg">Sign out</a>
                            </div>
                        </div>
                    <?php
else: ?>
                        <div class="hidden md:flex items-center gap-3">
                            <a href="<?php echo esc_url(mc_get_page_url('login')); ?>" class="text-sm font-medium text-dark-bg dark:text-light-bg hover:text-primary transition-colors">Sign In</a>
                            <a href="<?php echo esc_url(mc_get_page_url('login')); ?>" class="btn">Get Started</a>
                        </div>
                    <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>
