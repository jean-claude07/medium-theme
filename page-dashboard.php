<?php
/**
 * Template Name: User Dashboard
 */
get_header();

if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$user = wp_get_current_user();
$total_points = get_user_meta($user->ID, 'mc_total_points', true) ?: 0;
$chart_data = mc_get_author_analytics_data($user->ID);
$status = get_user_meta($user->ID, 'mc_account_status', true);
if ($status === 'pending') {
    wp_redirect(add_query_arg('activation_required', '1', home_url('/login/')));
    exit;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="{ tab: 'stats' }">
    <div class="flex flex-col md:flex-row gap-8">

        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 flex-none space-y-1">
            <h2 class="text-xl font-bold font-serif mb-6 px-3">Dashboard</h2>
            <button @click="tab = 'stats'"
                :class="{'bg-gray-100 dark:bg-gray-800 text-dark-bg dark:text-light-bg': tab === 'stats', 'text-gray-500 hover:text-dark-bg hover:bg-gray-50 dark:hover:bg-gray-800/50': tab !== 'stats'}"
                class="w-full text-left px-3 py-2 rounded-lg font-medium transition flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                </svg>
                Stats & Insights
            </button>
            <button @click="tab = 'posts'"
                :class="{'bg-gray-100 dark:bg-gray-800 text-dark-bg dark:text-light-bg': tab === 'posts', 'text-gray-500 hover:text-dark-bg hover:bg-gray-50 dark:hover:bg-gray-800/50': tab !== 'posts'}"
                class="w-full text-left px-3 py-2 rounded-lg font-medium transition flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                    </path>
                </svg>
                Stories
            </button>
            <button @click="tab = 'followers'"
                :class="{'bg-gray-100 dark:bg-gray-800 text-dark-bg dark:text-light-bg': tab === 'followers', 'text-gray-500 hover:text-dark-bg hover:bg-gray-50 dark:hover:bg-gray-800/50': tab !== 'followers'}"
                class="w-full text-left px-3 py-2 rounded-lg font-medium transition flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                Audience
            </button>
            <button @click="tab = 'notifications'"
                :class="{'bg-gray-100 dark:bg-gray-800 text-dark-bg dark:text-light-bg': tab === 'notifications', 'text-gray-500 hover:text-dark-bg hover:bg-gray-50 dark:hover:bg-gray-800/50': tab !== 'notifications'}"
                class="w-full text-left px-3 py-2 rounded-lg font-medium transition flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
                Notifications
            </button>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-grow">
            <!-- Stats Tab -->
            <div x-show="tab === 'stats'" x-transition.opacity.duration.300ms>
                <h3 class="text-3xl font-bold font-serif mb-8">Audience Stats</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="card p-6">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Followers</p>
                        <p class="text-4xl font-bold mt-2">
                            <?php echo mc_get_follower_count($user->ID); ?>
                        </p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Views</p>
                        <p class="text-4xl font-bold mt-2">
                            <?php echo number_format(mc_get_total_author_views($user->ID)); ?>
                        </p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Points</p>
                        <div class="flex items-center gap-2 mt-2">
                            <svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <p class="text-4xl font-bold">
                                <?php echo $total_points; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="card p-8 min-h-[300px] flex flex-col items-center justify-center bg-gray-50 dark:bg-dark-bg/50">
                    <h4 class="text-lg font-bold font-serif mb-6">Your Badges</h4>
                    <div class="flex flex-wrap justify-center gap-6">
                        <?php
                        $user_badges = mc_get_user_badges($user->ID);
                        if (!empty($user_badges)):
                            foreach ($user_badges as $badge_key):
                                $badge_labels = [
                                    'new_writer' => '🐣 New Writer',
                                    'pro_contributor' => '✍️ Pro Contributor',
                                    'popular_author' => '🔥 Popular Author',
                                    'community_star' => '⭐ Community Star'
                                ];
                                $label = isset($badge_labels[$badge_key]) ? $badge_labels[$badge_key] : $badge_key;
                                ?>
                                <div class="flex flex-col items-center gap-2 group">
                                    <div class="w-16 h-16 rounded-full bg-white dark:bg-dark-surface border-2 border-primary/20 flex items-center justify-center text-3xl shadow-sm group-hover:scale-110 transition-transform cursor-default"
                                        title="<?php echo esc_attr($label); ?>">
                                        <?php echo mb_substr($label, 0, 2); ?>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-gray-500 uppercase tracking-tighter"><?php echo str_replace(mb_substr($label, 0, 2), '', $label); ?></span>
                                </div>
                                <?php
                            endforeach;
                        else:
                            ?>
                            <p class="text-gray-400 italic">No badges earned yet. Keep writing!</p>
                        <?php endif; ?>
                    </div>
                </div>
                <br />
                <div class="card p-8 mb-6 mt-6">
                    <h4 class="text-lg font-bold font-serif mb-6">Engagement Trends (Last 7 Days)</h4>
                    <div class="relative h-[300px]">
                        <canvas id="engagementChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Posts Tab -->
            <div x-show="tab === 'posts'" style="display: none;" x-transition.opacity.duration.300ms
                x-data="postEditor">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-3xl font-bold font-serif" x-text="isEditing ? 'Write Story' : 'Your Stories'"></h3>
                    <button @click="toggleEdit()" class="btn"
                        x-text="isEditing ? 'Cancel Editor' : 'Write a story'"></button>
                </div>

                <!-- Editor Space -->
                <div x-show="isEditing"
                    class="card p-0 overflow-hidden mb-8 border-none shadow-2xl bg-white dark:bg-dark-surface"
                    style="display: none;">

                    <div
                        class="flex items-center justify-between px-8 py-4 border-b border-light-border dark:border-dark-border bg-gray-50/50 dark:bg-dark-bg/20">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Mode Édition</span>
                        </div>
                        <button @click="toggleEdit" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="savePost()" class="p-8">
                        <div x-show="message" x-transition
                            class="mb-6 p-4 rounded-xl flex items-center gap-3 text-sm font-medium"
                            :class="isError ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100'"
                            x-text="message"></div>

                        <input type="text" x-model="form.title" placeholder="Titre de votre histoire..."
                            class="w-full text-4xl md:text-5xl font-serif font-bold border-none focus:ring-0 bg-transparent mb-6 outline-none placeholder-gray-200 dark:placeholder-gray-700 dark:text-light-bg"
                            required>

                        <div
                            class="sticky top-4 z-20 flex items-center gap-1 mb-6 p-1.5 bg-white/80 dark:bg-dark-surface/90 backdrop-blur-md rounded-2xl border border-light-border dark:border-dark-border shadow-sm w-max">
                            <span class="text-[10px] font-black text-gray-400 uppercase px-3">Insérer</span>
                            <div class="h-4 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

                            <button type="button" @click="insertEmbedPrompt('youtube')"
                                class="p-2.5 hover:bg-primary/10 hover:text-primary rounded-xl text-gray-500 dark:text-gray-400 transition-all group"
                                title="Ajouter une vidéo YouTube">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>

                            <button type="button" @click="insertEmbedPrompt('social')"
                                class="p-2.5 hover:bg-primary/10 hover:text-primary rounded-xl text-gray-500 dark:text-gray-400 transition-all group"
                                title="Ajouter un post social">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </button>
                        </div>

                        <div class="mb-10 group">
                            <div x-ref="editor"
                                class="w-full min-h-[400px] text-xl font-serif dark:text-light-bg leading-relaxed prose prose-lg dark:prose-invert max-w-none focus:outline-none">
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-light-border dark:border-dark-border">
                            <h4
                                class="text-sm font-bold text-dark-bg dark:text-light-bg uppercase tracking-widest mb-6">
                                Paramètres de publication</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="col-span-full lg:col-span-1">
                                    <label
                                        class="block text-xs font-bold text-gray-400 uppercase mb-3 text-center md:text-left">Image
                                        de couverture</label>

                                    <div class="relative group cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed group-hover:border-primary transition-colors"
                                        :class="form.featured_image_preview ? 'border-primary' : 'border-gray-200 dark:border-gray-700'">

                                        <input type="file" @change="handleFileUpload($event)" accept="image/*"
                                            class="absolute inset-0 w-full h-full opacity-0 z-20 cursor-pointer">

                                        <div x-show="!form.featured_image_preview"
                                            class="p-8 text-center bg-gray-50/50 dark:bg-dark-bg/20">
                                            <svg class="w-10 h-10 mx-auto text-gray-300 group-hover:text-primary mb-3 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">Ajouter
                                                une image de couverture</p>
                                            <p class="text-xs text-gray-400 mt-1">Haute résolution recommandée (PNG,
                                                JPG, WebP)</p>
                                        </div>

                                        <div x-show="form.featured_image_preview"
                                            class="relative aspect-[16/9] bg-gray-900" style="display:none;">
                                            <img :src="form.featured_image_preview"
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                alt="Prévisualisation">

                                            <div
                                                class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                <div
                                                    class="flex items-center gap-2 text-white bg-black/50 px-4 py-2 rounded-full backdrop-blur-sm text-xs font-bold">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 15.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                    Changer l'image
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-full lg:col-span-1">
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-3">Catégories (2
                                        max)</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="category in availableCategories" :key="category.id">
                                            <button type="button" @click="toggleCategory(category.id)"
                                                :class="form.categories.includes(category.id) 
                                    ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' 
                                    : 'bg-white dark:bg-dark-surface text-gray-500 border-gray-200 dark:border-gray-700 hover:border-primary'"
                                                class="px-4 py-2 text-xs font-semibold rounded-full border transition-all flex items-center gap-2">
                                                <span x-text="category.name"></span>
                                                <svg x-show="form.categories.includes(category.id)" class="w-3 h-3"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between mt-12 pt-6 border-t border-light-border dark:border-dark-border">
                            <button type="button" @click="toggleEdit"
                                class="text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">
                                Annuler
                            </button>
                            <div class="flex gap-4">
                                <button type="button" @click="savePost('draft')"
                                    class="px-6 py-5 rounded-full text-sm font-bold text-gray-500 hover:text-dark-bg transition-all"
                                    :disabled="loading">Brouillon</button>
                                <button type="button" @click="savePost('publish')"
                                    class="px-8 py-5 bg-primary hover:bg-emerald-600 text-white rounded-full text-sm font-bold shadow-lg shadow-primary/20 transition-all disabled:opacity-50"
                                    :disabled="loading">
                                    <span x-show="!loading">Publier l'article</span>
                                    <span x-show="loading" class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        Envoi...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Existing Stories -->
                <div x-show="!isEditing" class="space-y-6">
                    <?php
                    $user_posts = new WP_Query([
                        'author' => $user->ID,
                        'post_type' => 'post',
                        'post_status' => ['publish', 'draft'],
                        'posts_per_page' => 10
                    ]);
                    if ($user_posts->have_posts()):
                        while ($user_posts->have_posts()):
                            $user_posts->the_post();
                            ?>
                            <div class="card p-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4 group">
                                <div>
                                    <h4 class="text-lg font-bold font-serif mb-1 group-hover:underline"><a
                                            href="<?php the_permalink(); ?>">
                                            <?php the_title(); ?>
                                        </a></h4>
                                    <div class="flex items-center gap-3 text-sm text-gray-500">
                                        <span
                                            class="capitalize <?php echo get_post_status() === 'draft' ? 'text-yellow-600 dark:text-yellow-400' : 'text-primary'; ?>">
                                            <?php echo get_post_status(); ?>
                                        </span>
                                        <span>&middot;</span>
                                        <span>
                                            <?php echo get_the_date(); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editPost({
                                    id: <?php the_ID(); ?>,
                                    title: '<?php echo esc_js(get_the_title()); ?>',
                                    content: '<?php echo esc_js(get_the_content()); ?>',
                                    youtube: '<?php echo esc_js(get_post_meta(get_the_ID(), 'mc_youtube_url', true)); ?>',
                                    social: '<?php echo esc_js(get_post_meta(get_the_ID(), 'mc_social_link', true)); ?>',
                                    categories: <?php echo json_encode(wp_get_post_categories(get_the_ID())); ?>
                                })" class="btn-outline px-3 py-1.5 text-xs">
                                        Modifier
                                    </button>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    else: ?>
                        <div
                            class="py-12 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl">
                            <p>Vous n'avez pas encore écrit d'histoires.</p>
                        </div>
                        <?php
                    endif; ?>
                </div>
            </div>

            <!-- Followers Tab -->
            <div x-show="tab === 'followers'" style="display: none;" x-transition.opacity.duration.300ms>
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-3xl font-bold font-serif">Audience</h3>
                    <div class="text-sm text-gray-500 font-medium">
                        <?php echo mc_get_follower_count($user->ID); ?> Followers
                    </div>
                </div>

                <?php
                $followers = mc_get_followers_list($user->ID);
                if (!empty($followers)): ?>
                    <div class="space-y-4">
                        <?php foreach ($followers as $follower): ?>
                            <div
                                class="card p-4 flex items-center justify-between group hover:border-primary transition-all duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <?php echo get_avatar($follower->ID, 48, '', '', ['class' => 'rounded-full border border-gray-100']); ?>
                                    </div>
                                    <div>
                                        <h4
                                            class="font-bold text-lg text-dark-bg dark:text-light-bg group-hover:text-primary transition-colors">
                                            <?php echo esc_html($follower->display_name); ?>
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            @
                                            <?php echo esc_html($follower->user_nicename); ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?php echo get_author_posts_url($follower->ID); ?>"
                                    class="btn-outline px-4 py-2 text-xs">
                                    View Profile
                                </a>
                            </div>
                            <?php
                        endforeach; ?>
                    </div>
                    <?php
                else: ?>
                    <div
                        class="py-16 text-center bg-gray-50 dark:bg-dark-bg/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No audience yet</p>
                        <p class="text-sm text-gray-500 mt-1">Publish more stories to start building your audience.</p>
                    </div>
                    <?php
                endif; ?>
            </div>

            <!-- Notifications Tab -->
            <div x-show="tab === 'notifications'" style="display: none;" x-transition.opacity.duration.300ms
                x-data="notificationsHandler">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-3xl font-bold font-serif">Notifications</h3>
                    <button x-show="notifications.some(n => !n.is_read)" @click="markAllAsRead()"
                        class="text-sm font-bold text-primary hover:underline">
                        Tout marquer comme lu
                    </button>
                </div>

                <div x-show="loading" class="flex justify-center py-12">
                    <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div x-show="!loading && notifications.length > 0" class="space-y-4">
                    <template x-for="notif in notifications" :key="notif.id">
                        <div class="card p-4 transition-all duration-300 border-l-4"
                            :class="notif.is_read ? 'border-transparent opacity-70' : 'border-primary bg-primary/5'">
                            <div class="flex items-center gap-4">
                                <img :src="notif.actor_avatar" class="w-8 h-8 rounded-full border border-gray-100"
                                    alt="Avatar">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm">
                                        <span class="font-bold" x-text="notif.actor_name"></span>
                                        <span class="text-gray-600 dark:text-gray-400"
                                            x-text="formatType(notif.type)"></span>
                                    </p>
                                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest"
                                        x-text="formatTime(notif.created_at)"></p>
                                </div>
                                <div x-show="!notif.is_read" class="w-2 h-2 bg-primary rounded-full"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="!loading && notifications.length === 0"
                    class="py-16 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">Aucune notification.</p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('engagementChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (typeof Chart === 'undefined') return;

        const chartData = <?php echo json_encode($chart_data); ?>;
        const isDark = document.documentElement.classList.contains('dark');

        const primaryColor = '#10b981';
        const secondaryColor = isDark ? '#6366f1' : '#4f46e5';
        const textColor = isDark ? '#9ca3af' : '#6b7280';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, isDark ? 'rgba(16, 185, 129, 0.2)' : 'rgba(16, 185, 129, 0.1)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Vues',
                        data: chartData.views,
                        borderColor: isDark ? '#4b5563' : '#d1d5db',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Engagement',
                        data: chartData.engagement,
                        borderColor: primaryColor,
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: isDark ? '#1f2937' : '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: isDark ? '#e5e7eb' : '#374151',
                            usePointStyle: true,
                            boxWidth: 6,
                            font: { size: 12, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1f2937' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#1f2937',
                        bodyColor: isDark ? '#9ca3af' : '#4b5563',
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function (context) {
                                return ` ${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: textColor,
                            precision: 0,
                            padding: 10
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                }
            }
        });
    });
</script>

<?php get_footer(); ?>