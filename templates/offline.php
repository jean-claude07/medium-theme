<?php
/**
 * Medium Clone — Page Offline PWA
 * Affichée quand l'utilisateur est hors-ligne et que la page demandée n'est pas en cache.
 */

// Désactive le template WordPress standard pour cette page
// et force un rendu standalone
if (!defined('ABSPATH')) {
    exit;
}

$site_name  = get_bloginfo('name');
$theme_uri  = get_template_directory_uri();
$icon_url   = $theme_uri . '/assets/images/icons/icon-192x192.png';
$home_url   = home_url('/');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="antialiased">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Hors ligne', 'medium-clone'); ?> — <?php echo esc_html($site_name); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($theme_uri . '/assets/images/icons/favicon-32x32.png'); ?>">
    <?php wp_head(); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --bg-card: #1e293b;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --border: #334155;
            }
        }
        .dark { --bg: #0f172a; --bg-card: #1e293b; --text: #f1f5f9; --text-muted: #94a3b8; --border: #334155; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            transition: background-color .3s;
        }

        .offline-container {
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeUp .5s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .offline-icon-wrap {
            position: relative;
            display: inline-flex;
            margin-bottom: 2rem;
        }
        .offline-icon-wrap img {
            width: 88px;
            height: 88px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(16,185,129,.25);
            filter: grayscale(60%);
        }
        .offline-badge {
            position: absolute;
            bottom: -6px;
            right: -8px;
            width: 32px;
            height: 32px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            border: 3px solid var(--bg);
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }

        .offline-title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -.03em;
            margin-bottom: .75rem;
            color: var(--text);
        }
        .offline-title span { color: var(--primary); }

        .offline-desc {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .offline-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-size: .95rem;
            font-weight: 600;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s, transform .1s;
            box-shadow: 0 4px 16px rgba(16,185,129,.3);
        }
        .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary svg { width: 18px; height: 18px; }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--bg-card);
            color: var(--text);
            font-size: .9rem;
            font-weight: 500;
            padding: 12px 24px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: border-color .15s, background .15s;
        }
        .btn-secondary:hover { border-color: var(--primary); background: rgba(16,185,129,.06); }

        .offline-cached {
            margin-top: 2.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: left;
        }
        .offline-cached h3 {
            font-size: .85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        #cached-pages-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        #cached-pages-list li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text);
            font-size: .88rem;
            transition: background .15s;
            border: 1px solid transparent;
        }
        #cached-pages-list li a:hover {
            background: rgba(16,185,129,.08);
            border-color: rgba(16,185,129,.2);
        }
        #cached-pages-list li a::before {
            content: "📄";
            font-size: 1rem;
            flex-shrink: 0;
        }

        .offline-footer {
            margin-top: 2rem;
            font-size: .8rem;
            color: var(--text-muted);
        }
        .offline-footer span { color: var(--primary); font-weight: 600; }

        /* Pulsating wifi indicator */
        .no-wifi {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239,68,68,.1);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,.2);
            padding: 5px 14px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
    </style>
</head>
<body>
    <?php
    // Init dark mode
    echo '<script>
        if (localStorage.theme === "dark" || (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
            document.documentElement.classList.add("dark");
        }
    </script>';
    ?>

    <div class="offline-container">

        <div class="offline-icon-wrap">
            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
            <div class="offline-badge">📡</div>
        </div>

        <div class="no-wifi">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M24 8.98C21.16 6.12 17.31 4.36 13.03 4.36S4.9 6.12 2.06 8.98L0 6.92C3.37 3.55 7.97 1.5 13.03 1.5s9.66 2.05 13.03 5.42L24 8.98zM13 15a3 3 0 110 6 3 3 0 010-6zm-3.65-3.65c1.03-.97 2.41-1.57 3.92-1.57s2.89.6 3.92 1.57l1.66-1.66A8.39 8.39 0 0013.27 8c-2.36 0-4.48.94-6.02 2.45l1.66 1.66-.56-.11z"/></svg>
            <?php _e('Vous êtes hors ligne', 'medium-clone'); ?>
        </div>

        <h1 class="offline-title">
            <?php _e('Pas de connexion', 'medium-clone'); ?><br>
            <span><?php _e('pour le moment', 'medium-clone'); ?></span>
        </h1>

        <p class="offline-desc">
            <?php _e('Vérifiez votre connexion Wi-Fi ou données mobiles, puis réessayez. Vos articles lus récemment sont disponibles ci-dessous.', 'medium-clone'); ?>
        </p>

        <div class="offline-actions">
            <button class="btn-primary" onclick="location.reload()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <?php _e('Réessayer', 'medium-clone'); ?>
            </button>
            <a href="<?php echo esc_url($home_url); ?>" class="btn-secondary">
                🏠 <?php _e('Retour à l\'accueil', 'medium-clone'); ?>
            </a>
        </div>

        <!-- Pages disponibles en cache -->
        <div class="offline-cached" id="cached-section" style="display:none;">
            <h3>📚 <?php _e('Pages disponibles hors ligne', 'medium-clone'); ?></h3>
            <ul id="cached-pages-list">
                <li><a href="/"><?php _e('Chargement…', 'medium-clone'); ?></a></li>
            </ul>
        </div>

        <p class="offline-footer">
            <?php printf(__('Propulsé par %s', 'medium-clone'), '<span>' . esc_html($site_name) . '</span>'); ?>
        </p>

    </div>

    <script>
    // Lister les pages en cache depuis le Service Worker
    (async () => {
        if ('caches' in window) {
            try {
                const cacheNames = await caches.keys();
                const pagesCaches = cacheNames.filter(n => n.includes('-pages') || n.includes('-static'));
                const allRequests = [];

                for (const name of pagesCaches) {
                    const cache = await caches.open(name);
                    const reqs  = await cache.keys();
                    reqs.forEach(r => {
                        const url = r.url;
                        if (url.includes(location.origin) && !url.includes('/wp-') && !url.includes('/admin') && !url.includes('.css') && !url.includes('.js') && !url.includes('.png') && !url.includes('/offline')) {
                            allRequests.push(url);
                        }
                    });
                }

                if (allRequests.length > 0) {
                    const list = document.getElementById('cached-pages-list');
                    const section = document.getElementById('cached-section');
                    section.style.display = 'block';
                    list.innerHTML = allRequests.slice(0, 7).map(url => {
                        const label = url === location.origin + '/' ? 'Accueil' : decodeURIComponent(url.replace(location.origin, '').replace(/\//g, ' / ').trim());
                        return `<li><a href="${url}">${label}</a></li>`;
                    }).join('');
                }
            } catch (e) {
                console.log('Cache lookup error:', e);
            }
        }
    })();

    // Auto-retry sur reconnexion
    window.addEventListener('online', () => {
        location.reload();
    });
    </script>

    <?php wp_footer(); ?>
</body>
</html>
