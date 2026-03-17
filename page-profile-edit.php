<?php
/**
 * Template Name: Edit Profile
 */
get_header();

if (!is_user_logged_in()) {
    wp_redirect(mc_get_page_url('login'));
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="profileEditor">

    <div class="animate-on-scroll">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold font-serif mb-2">Edit Profile</h1>
                <p class="text-gray-500">Update your personal information and public profile.</p>
            </div>
            <a href="<?php echo esc_url(mc_get_page_url('dashboard')); ?>" class="btn-outline">Back to Dashboard</a>
        </div>

        <!-- Error/Success Toast -->
        <div x-show="message" style="display:none;" x-transition
            class="mb-8 p-4 rounded-xl flex items-center gap-3 backdrop-blur-sm border shadow-sm"
            :class="isError ? 'bg-red-50/80 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400' : 'bg-emerald-50/80 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400'">
            <svg x-show="!isError" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
            </svg>
            <svg x-show="isError" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"></path>
            </svg>
            <span x-text="message" class="font-medium text-sm"></span>
        </div>

        <div class="card p-8 md:p-10 relative overflow-hidden bg-white/80 dark:bg-dark-surface/80 backdrop-blur-xl">

            <!-- Decoration -->
            <div class="absolute -top-32 -right-32 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none">
            </div>

            <form @submit.prevent="saveProfile" class="space-y-8 relative z-10">

                <!-- Avatar Section -->
                <div class="flex items-start gap-6 pb-8 border-b border-light-border dark:border-dark-border">
                    <div class="relative group cursor-pointer" @click="$refs.fileInput.click()">
                        <img :src="avatarPreview || form.avatar"
                            class="w-24 h-24 rounded-full object-cover ring-4 ring-primary/10 shadow-lg transition-opacity duration-200"
                            :class="showCropper ? 'opacity-40' : ''" alt="Avatar">
                        <div
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <input type="file" x-ref="fileInput" @change="handleAvatarUpload" class="hidden"
                            accept="image/*">
                    </div>
                    <div>
                        <h3 class="text-lg font-bold mb-1">Photo de profil</h3>
                        <p class="text-sm text-gray-500 mb-3">Cliquez sur l'image pour télécharger une nouvelle photo.
                        </p>
                        <button type="button" @click="$refs.fileInput.click()"
                            class="text-sm font-medium text-primary hover:underline">Modifier la photo</button>
                    </div>
                </div>

                <!-- Inline Crop Panel -->
                <div x-show="showCropper" x-cloak x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="rounded-2xl border border-light-border dark:border-dark-border overflow-hidden shadow-lg">

                    <div
                        class="px-4 py-3 flex items-center justify-between bg-gray-50 dark:bg-dark-surface border-b border-light-border dark:border-dark-border">
                        <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Ajuster la photo
                        </div>
                        <button @click="closeCropper" type="button"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200 dark:hover:bg-dark-bg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-900 flex items-center justify-center"
                        style="min-height: 300px; max-height: 420px;">
                        <img x-ref="cropperImg" class="max-w-full block">
                    </div>

                    <div
                        class="px-4 py-3 flex justify-end gap-3 bg-gray-50 dark:bg-dark-surface border-t border-light-border dark:border-dark-border">
                        <button @click="closeCropper" type="button"
                            class="btn-outline text-sm px-5 py-2">Annuler</button>
                        <button @click="applyCrop" type="button" class="btn text-sm px-8 py-2">✓ Confirmer</button>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">First
                            Name</label>
                        <input type="text" x-model="form.first_name"
                            class="block w-full px-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">Last
                            Name</label>
                        <input type="text" x-model="form.last_name"
                            class="block w-full px-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">Display
                            Name
                            (Public)</label>
                        <input type="text" x-model="form.display_name" required
                            class="block w-full px-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center mb-1.5 ml-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                            <span class="text-xs text-gray-400" x-text="form.bio.length + '/160'"></span>
                        </div>
                        <textarea x-model="form.bio" rows="4" maxlength="160"
                            class="block w-full px-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all resize-none placeholder-gray-400"
                            placeholder="Tell the world a little about yourself..."></textarea>
                    </div>
                </div>

                <!-- Social Links -->
                <div>
                    <h3
                        class="text-lg font-bold mb-4 font-serif border-b border-light-border dark:border-dark-border pb-2">
                        Social Links</h3>
                    <div class="space-y-5">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">Twitter
                                URL</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                    </svg>
                                </div>
                                <input type="url" x-model="form.twitter"
                                    class="block w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                                    placeholder="https://twitter.com/username">
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">LinkedIn
                                URL</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                    </svg>
                                </div>
                                <input type="url" x-model="form.linkedin"
                                    class="block w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                                    placeholder="https://linkedin.com/in/username">
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">Personal
                                Website</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                        </path>
                                    </svg>
                                </div>
                                <input type="url" x-model="form.website"
                                    class="block w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                                    placeholder="https://yourwebsite.com">
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 ml-1">Facebook
                                URL</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z" />
                                    </svg>
                                </div>
                                <input type="url" x-model="form.facebook"
                                    class="block w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-dark-bg/50 border border-gray-200 dark:border-gray-700 rounded-xl text-dark-bg dark:text-light-bg focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                                    placeholder="https://facebook.com/username">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit -->
                <div class="pt-6 border-t border-light-border dark:border-dark-border flex justify-end gap-4"
                    style="padding-top: 20px;">
                    <a href="<?php echo esc_url(mc_get_page_url('dashboard')); ?>" class="btn-outline px-6">Cancel</a>
                    <button type="submit" :disabled="loading" class="btn px-8 shadow-lg shadow-primary/20">
                        <svg x-show="loading" style="display:none;" class="animate-spin -ml-1 mr-2 h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Save Changes</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>