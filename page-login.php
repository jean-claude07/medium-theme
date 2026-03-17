<?php
/**
 * Template Name: Login / Register
 */
get_header();

if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}
?>

<div
    class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-light-surface dark:bg-dark-bg animate-on-scroll">
    <div class="max-w-md w-full space-y-8 card p-10 relative overflow-hidden" x-data="authModal">
        <!-- Decoration -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-secondary/10 rounded-full blur-3xl pointer-events-none">
        </div>

        <div class="text-center relative z-10">
            <h2 class="text-3xl font-bold font-serif text-dark-bg dark:text-light-bg"
                x-text="tab === 'login' ? 'Welcome back.' : 'Join MediumClone.'"></h2>
            <p class="mt-2 text-sm text-gray-500"
                x-text="tab === 'login' ? 'Sign in to access your customized feed.' : 'Create an account to write stories.'">
            </p>
        </div>

        <form class="mt-8 space-y-6 relative z-10" @submit.prevent="submit()">
            <div x-show="error" style="display:none;"
                class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded text-sm text-center"
                x-text="error"></div>

            <div class="space-y-4">
                <div x-show="tab === 'register'" x-transition>
                    <label for="name" class="sr-only">Full Name</label>
                    <input id="name" name="name" type="text" x-model="form.name" :required="tab === 'register'"
                        class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-700 placeholder-gray-500 text-dark-bg dark:text-light-bg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm bg-white dark:bg-dark-surface"
                        placeholder="Full Name">
                </div>
                <div>
                    <label for="email-address" class="sr-only">Email address</label>
                    <input id="email-address" name="email" type="email" autocomplete="email" required
                        x-model="form.email"
                        class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-700 placeholder-gray-500 text-dark-bg dark:text-light-bg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm bg-white dark:bg-dark-surface"
                        placeholder="Email address">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        x-model="form.password"
                        class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-700 placeholder-gray-500 text-dark-bg dark:text-light-bg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm bg-white dark:bg-dark-surface"
                        placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" :disabled="loading"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-full text-white bg-primary hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all shadow-sm">
                    <span x-show="loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </span>
                    <span x-text="tab === 'login' ? 'Sign in' : 'Sign up'"></span>
                </button>
            </div>

            <div class="mt-4 text-center text-sm text-gray-500">
                <p x-show="tab === 'login'">No account? <button type="button" @click="tab = 'register'; error = ''"
                        class="font-bold text-primary hover:underline">Create one</button></p>
                <p x-show="tab === 'register'">Already have an account? <button type="button"
                        @click="tab = 'login'; error = ''" class="font-bold text-primary hover:underline">Sign
                        in</button></p>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>