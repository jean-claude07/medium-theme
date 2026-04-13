<?php
/**
 * Medium Clone — PWA Admin Dashboard
 *
 * Page de configuration PWA dans l'administration WordPress.
 * Permet de gérer : nom de l'app, couleurs, VAPID keys, service worker,
 * notifications push, et voir les statistiques d'abonnement.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Enregistrement de la page admin ─────────────────────────────────────────
add_action('admin_menu', 'mc_pwa_admin_menu');

function mc_pwa_admin_menu()
{
    add_menu_page(
        __('PWA Settings', 'medium-clone'),
        __('PWA', 'medium-clone'),
        'manage_options',
        'mc-pwa-settings',
        'mc_pwa_admin_page',
        'dashicons-smartphone',
        59
    );

    add_submenu_page(
        'mc-pwa-settings',
        __('Configuration', 'medium-clone'),
        __('Configuration', 'medium-clone'),
        'manage_options',
        'mc-pwa-settings',
        'mc_pwa_admin_page'
    );

    add_submenu_page(
        'mc-pwa-settings',
        __('Notifications Push', 'medium-clone'),
        __('Notifications Push', 'medium-clone'),
        'manage_options',
        'mc-pwa-push',
        'mc_pwa_push_admin_page'
    );

    add_submenu_page(
        'mc-pwa-settings',
        __('Diagnostic PWA', 'medium-clone'),
        __('Diagnostic', 'medium-clone'),
        'manage_options',
        'mc-pwa-diagnostic',
        'mc_pwa_diagnostic_page'
    );
}

// ─── Enregistrement settings ──────────────────────────────────────────────────
add_action('admin_init', 'mc_pwa_register_settings');

function mc_pwa_register_settings()
{
    register_setting('mc_pwa_settings_group', 'mc_pwa_settings', [
        'sanitize_callback' => 'mc_pwa_sanitize_settings',
    ]);
}

function mc_pwa_sanitize_settings($input)
{
    $clean = [];
    $clean['app_name']        = sanitize_text_field($input['app_name']        ?? '');
    $clean['short_name']      = sanitize_text_field($input['short_name']      ?? '');
    $clean['description']     = sanitize_textarea_field($input['description'] ?? '');
    $clean['theme_color']     = sanitize_hex_color($input['theme_color']      ?? '#10b981');
    $clean['dark_theme_color'] = sanitize_hex_color($input['dark_theme_color'] ?? '#0f172a');
    $clean['bg_color']        = sanitize_hex_color($input['bg_color']         ?? '#10b981');
    $clean['start_url']       = esc_url_raw($input['start_url']               ?? home_url('/'));
    $clean['display']         = in_array($input['display'] ?? '', ['standalone', 'fullscreen', 'minimal-ui', 'browser'])
                                  ? $input['display'] : 'standalone';
    $clean['sw_enabled']      = isset($input['sw_enabled'])  ? '1' : '0';
    $clean['push_enabled']    = isset($input['push_enabled']) ? '1' : '0';
    $clean['vapid_public_key']  = sanitize_text_field($input['vapid_public_key']  ?? '');
    $clean['vapid_private_key'] = sanitize_text_field($input['vapid_private_key'] ?? '');

    // Invalider le manifest.json statique si les settings changent
    flush_rewrite_rules();

    return $clean;
}

// ─── Assets admin ─────────────────────────────────────────────────────────────
add_action('admin_enqueue_scripts', 'mc_pwa_admin_assets');

function mc_pwa_admin_assets($hook)
{
    if (!in_array($hook, ['toplevel_page_mc-pwa-settings', 'pwa_page_mc-pwa-push', 'pwa_page_mc-pwa-diagnostic'])) {
        return;
    }
    // On injecte du CSS inline pour la page admin
    wp_add_inline_style('wp-admin', mc_pwa_admin_css());
}

function mc_pwa_admin_css()
{
    return '
    /* ─── PWA Admin Styles ─── */
    .mc-pwa-wrap { max-width: 1100px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .mc-pwa-header { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); border-radius: 12px; padding: 32px 36px; margin-bottom: 28px; color: #fff; display: flex; align-items: center; gap: 20px; }
    .mc-pwa-header h1 { color: #fff !important; font-size: 1.8rem; margin: 0; padding: 0; }
    .mc-pwa-header p { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: .95rem; }
    .mc-pwa-header .mc-pwa-icon { width: 72px; height: 72px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.25); border: 3px solid rgba(255,255,255,.3); }
    .mc-pwa-badge { display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3); color: #fff; font-size: .75rem; padding: 4px 12px; border-radius: 999px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .mc-pwa-badge.active { background: rgba(255,255,255,.3); }

    .mc-pwa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .mc-pwa-grid { grid-template-columns: 1fr; } }

    .mc-pwa-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .mc-pwa-card-full { grid-column: 1 / -1; }
    .mc-pwa-card h2 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
    .mc-pwa-card h2 .dashicons { color: #10b981; font-size: 1.2rem; }
    .mc-pwa-card p.mc-desc { color: #6b7280; font-size: .85rem; margin: 0 0 18px; }

    .mc-pwa-field { margin-bottom: 16px; }
    .mc-pwa-field label { display: block; font-weight: 600; font-size: .85rem; color: #374151; margin-bottom: 6px; }
    .mc-pwa-field small { display: block; color: #9ca3af; font-size: .78rem; margin-top: 4px; }
    .mc-pwa-field input[type="text"],
    .mc-pwa-field input[type="url"],
    .mc-pwa-field input[type="password"],
    .mc-pwa-field select,
    .mc-pwa-field textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; font-size: .9rem; color: #111827; box-shadow: 0 1px 2px rgba(0,0,0,.05); transition: border-color .15s; box-sizing: border-box; }
    .mc-pwa-field input[type="text"]:focus,
    .mc-pwa-field input[type="password"]:focus,
    .mc-pwa-field select:focus,
    .mc-pwa-field textarea:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
    .mc-pwa-field input[type="color"] { width: 48px; height: 38px; padding: 2px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; }
    .mc-pwa-color-row { display: flex; align-items: center; gap: 10px; }
    .mc-pwa-color-row input[type="text"] { flex: 1; }

    .mc-pwa-toggle-wrap { display: flex; align-items: center; gap: 12px; }
    .mc-pwa-toggle { position: relative; display: inline-block; width: 48px; height: 26px; }
    .mc-pwa-toggle input { opacity: 0; width: 0; height: 0; }
    .mc-pwa-slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 26px; transition: .2s; cursor: pointer; }
    .mc-pwa-slider:before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .mc-pwa-toggle input:checked + .mc-pwa-slider { background: #10b981; }
    .mc-pwa-toggle input:checked + .mc-pwa-slider:before { transform: translateX(22px); }
    .mc-pwa-toggle-label { font-size: .9rem; font-weight: 600; color: #374151; }

    .mc-pwa-save-btn { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; padding: 11px 28px; border-radius: 8px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: opacity .15s, transform .1s; box-shadow: 0 2px 8px rgba(16,185,129,.3); }
    .mc-pwa-save-btn:hover { opacity: .92; transform: translateY(-1px); }
    .mc-pwa-save-btn:active { transform: translateY(0); }

    .mc-pwa-stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .mc-pwa-stat { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 18px; text-align: center; }
    .mc-pwa-stat .mc-stat-val { font-size: 2rem; font-weight: 800; color: #10b981; display: block; }
    .mc-pwa-stat .mc-stat-label { font-size: .8rem; color: #4b5563; margin-top: 4px; display: block; }

    .mc-pwa-checker { list-style: none; padding: 0; margin: 0; }
    .mc-pwa-checker li { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: .9rem; }
    .mc-pwa-checker li:last-child { border-bottom: none; }
    .mc-status-ok   { color: #10b981; font-weight: 700; }
    .mc-status-warn  { color: #f59e0b; font-weight: 700; }
    .mc-status-error { color: #ef4444; font-weight: 700; }

    .mc-pwa-code { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; font-family: monospace; font-size: .82rem; color: #374151; white-space: pre-wrap; word-break: break-all; }

    .mc-pwa-icon-preview { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin: 12px 0; }
    .mc-pwa-icon-preview img { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.12); border: 1px solid #e5e7eb; }

    .mc-pwa-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px; }
    .mc-tab { padding: 10px 20px; cursor: pointer; font-weight: 600; font-size: .9rem; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color .15s, border-color .15s; }
    .mc-tab.active { color: #10b981; border-bottom-color: #10b981; }

    .mc-pwa-notice { display: flex; align-items: flex-start; gap: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 14px; font-size: .85rem; color: #92400e; margin-bottom: 16px; }
    .mc-pwa-notice.info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
    .mc-pwa-notice.success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    ';
}

// ─── PAGE PRINCIPALE : Configuration ─────────────────────────────────────────
function mc_pwa_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Accès refusé.'));
    }

    $opts      = get_option('mc_pwa_settings', []);
    $theme_uri = trailingslashit(get_template_directory_uri());
    $icon_url  = $theme_uri . 'assets/images/icons/icon-192x192.png';
    $sw_enabled   = ($opts['sw_enabled']   ?? '1') === '1';
    $push_enabled = ($opts['push_enabled'] ?? '0') === '1';
    ?>
    <div class="wrap mc-pwa-wrap">

        <div class="mc-pwa-header">
            <img class="mc-pwa-icon" src="<?php echo esc_url($icon_url); ?>" alt="PWA Icon">
            <div>
                <div style="margin-bottom:8px;">
                    <span class="mc-pwa-badge active">⚡ PWA</span>
                    <span class="mc-pwa-badge" style="margin-left:6px;"><?php echo MC_PWA_VERSION; ?></span>
                </div>
                <h1><?php _e('Progressive Web App', 'medium-clone'); ?></h1>
                <p><?php _e('Configurez votre thème Medium Clone comme une PWA complète et optimisée.', 'medium-clone'); ?></p>
            </div>
        </div>

        <?php settings_errors('mc_pwa_settings_group'); ?>

        <form method="post" action="options.php">
            <?php settings_fields('mc_pwa_settings_group'); ?>

            <div class="mc-pwa-grid">

                <!-- ── Identité de l'app ── -->
                <div class="mc-pwa-card">
                    <h2><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Identité de l\'application', 'medium-clone'); ?></h2>
                    <p class="mc-desc"><?php _e('Ces informations apparaissent lors de l\'installation sur l\'appareil.', 'medium-clone'); ?></p>

                    <div class="mc-pwa-field">
                        <label><?php _e('Nom de l\'application', 'medium-clone'); ?></label>
                        <input type="text" name="mc_pwa_settings[app_name]"
                               value="<?php echo esc_attr($opts['app_name'] ?? get_bloginfo('name')); ?>"
                               placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <small><?php _e('Nom complet affiché à l\'installation et dans le splash screen.', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Nom court', 'medium-clone'); ?></label>
                        <input type="text" name="mc_pwa_settings[short_name]"
                               value="<?php echo esc_attr($opts['short_name'] ?? 'MediumClone'); ?>"
                               placeholder="MediumClone" maxlength="12">
                        <small><?php _e('Max 12 caractères. Affiché sous l\'icône sur l\'écran d\'accueil.', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Description', 'medium-clone'); ?></label>
                        <textarea name="mc_pwa_settings[description]" rows="3"
                                  placeholder="<?php echo esc_attr(get_bloginfo('description')); ?>"><?php echo esc_textarea($opts['description'] ?? get_bloginfo('description')); ?></textarea>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('URL de démarrage (Start URL)', 'medium-clone'); ?></label>
                        <input type="url" name="mc_pwa_settings[start_url]"
                               value="<?php echo esc_attr($opts['start_url'] ?? home_url('/')); ?>">
                        <small><?php _e('Page qui s\'ouvre au lancement de l\'app. Utilisez "/" pour la page d\'accueil.', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Mode d\'affichage', 'medium-clone'); ?></label>
                        <select name="mc_pwa_settings[display]">
                            <?php
                            $modes = ['standalone' => 'Standalone (recommandé)', 'fullscreen' => 'Plein écran', 'minimal-ui' => 'Minimal UI', 'browser' => 'Navigateur'];
                            foreach ($modes as $val => $label) {
                                printf('<option value="%s"%s>%s</option>', esc_attr($val), selected($opts['display'] ?? 'standalone', $val, false), esc_html($label));
                            }
                            ?>
                        </select>
                        <small><?php _e('Standalone masque l\'interface du navigateur pour une expérience native.', 'medium-clone'); ?></small>
                    </div>
                </div>

                <!-- ── Couleurs & Thème ── -->
                <div class="mc-pwa-card">
                    <h2><span class="dashicons dashicons-art"></span> <?php _e('Couleurs & Thème', 'medium-clone'); ?></h2>
                    <p class="mc-desc"><?php _e('Couleurs de la barre de statut et du splash screen.', 'medium-clone'); ?></p>

                    <div class="mc-pwa-field">
                        <label><?php _e('Couleur principale (mode clair)', 'medium-clone'); ?></label>
                        <div class="mc-pwa-color-row">
                            <input type="color" id="tc-color" value="<?php echo esc_attr($opts['theme_color'] ?? '#10b981'); ?>"
                                   oninput="document.querySelector('[name=\'mc_pwa_settings[theme_color]\']').value=this.value">
                            <input type="text" name="mc_pwa_settings[theme_color]"
                                   value="<?php echo esc_attr($opts['theme_color'] ?? '#10b981'); ?>"
                                   placeholder="#10b981" maxlength="7"
                                   oninput="document.getElementById('tc-color').value=this.value">
                        </div>
                        <small><?php _e('Couleur de la barre de statut mobile en mode clair.', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Couleur principale (mode sombre)', 'medium-clone'); ?></label>
                        <div class="mc-pwa-color-row">
                            <input type="color" id="dtc-color" value="<?php echo esc_attr($opts['dark_theme_color'] ?? '#0f172a'); ?>"
                                   oninput="document.querySelector('[name=\'mc_pwa_settings[dark_theme_color]\']').value=this.value">
                            <input type="text" name="mc_pwa_settings[dark_theme_color]"
                                   value="<?php echo esc_attr($opts['dark_theme_color'] ?? '#0f172a'); ?>"
                                   placeholder="#0f172a" maxlength="7"
                                   oninput="document.getElementById('dtc-color').value=this.value">
                        </div>
                        <small><?php _e('Couleur en mode sombre (appliquée automatiquement par le JS du thème).', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Couleur de fond (background_color)', 'medium-clone'); ?></label>
                        <div class="mc-pwa-color-row">
                            <input type="color" id="bg-color" value="<?php echo esc_attr($opts['bg_color'] ?? '#10b981'); ?>"
                                   oninput="document.querySelector('[name=\'mc_pwa_settings[bg_color]\']').value=this.value">
                            <input type="text" name="mc_pwa_settings[bg_color]"
                                   value="<?php echo esc_attr($opts['bg_color'] ?? '#10b981'); ?>"
                                   placeholder="#10b981" maxlength="7"
                                   oninput="document.getElementById('bg-color').value=this.value">
                        </div>
                        <small><?php _e('Couleur du fond du splash screen lors du chargement.', 'medium-clone'); ?></small>
                    </div>

                    <hr style="margin: 20px 0; border-color: #f3f4f6;">

                    <p style="font-size:.85rem; color:#6b7280; margin-bottom: 12px;"><strong>Aperçu des icônes générées :</strong></p>
                    <div class="mc-pwa-icon-preview">
                        <?php
                        $icons_dir = get_template_directory_uri() . '/assets/images/icons/';
                        $sizes     = [72, 96, 128, 192];
                        foreach ($sizes as $s) {
                            echo '<img src="' . esc_url($icons_dir . "icon-{$s}x{$s}.png") . '" width="' . ($s > 96 ? 48 : $s) . '" height="' . ($s > 96 ? 48 : $s) . '" alt="' . $s . 'px">';
                        }
                        ?>
                    </div>
                </div>

                <!-- ── Service Worker ── -->
                <div class="mc-pwa-card">
                    <h2><span class="dashicons dashicons-update"></span> <?php _e('Service Worker & Cache', 'medium-clone'); ?></h2>
                    <p class="mc-desc"><?php _e('Active le mode hors-ligne et le cache avancé.', 'medium-clone'); ?></p>

                    <div class="mc-pwa-field">
                        <div class="mc-pwa-toggle-wrap">
                            <label class="mc-pwa-toggle">
                                <input type="checkbox" name="mc_pwa_settings[sw_enabled]" value="1" <?php checked($sw_enabled); ?>>
                                <span class="mc-pwa-slider"></span>
                            </label>
                            <span class="mc-pwa-toggle-label"><?php _e('Activer le Service Worker', 'medium-clone'); ?></span>
                        </div>
                        <small style="margin-top: 8px; display: block;"><?php _e('Active le cache offline, les notifications push et le background sync.', 'medium-clone'); ?></small>
                    </div>

                    <hr style="margin: 16px 0; border-color: #f3f4f6;">
                    <p style="font-size: .82rem; color: #6b7280; margin: 0 0 8px;"><strong><?php _e('URL du Service Worker :', 'medium-clone'); ?></strong></p>
                    <div class="mc-pwa-code"><?php echo esc_html(get_template_directory_uri() . '/assets/js/sw.js'); ?></div>
                    <p style="font-size: .82rem; color: #6b7280; margin: 12px 0 8px;"><strong><?php _e('Stratégies de cache actives :', 'medium-clone'); ?></strong></p>
                    <ul style="font-size:.82rem; color:#4b5563; margin: 0; padding-left: 18px;">
                        <li>📦 <strong>Cache First</strong> — CSS, JS, Fonts, Images (30 jours)</li>
                        <li>🌐 <strong>Network First</strong> — Pages HTML (fallback: offline)</li>
                        <li>⚡ <strong>Stale-While-Revalidate</strong> — API REST WordPress</li>
                    </ul>
                </div>

                <!-- ── Notifications Push ── -->
                <div class="mc-pwa-card">
                    <h2><span class="dashicons dashicons-bell"></span> <?php _e('Notifications Push', 'medium-clone'); ?></h2>
                    <p class="mc-desc"><?php _e('Envoyez des notifications natives aux abonnés.', 'medium-clone'); ?></p>

                    <div class="mc-pwa-notice">
                        <span>⚠️</span>
                        <div><?php _e('Les clés VAPID sont nécessaires pour les notifications push. Générez-les sur <a href="https://vapidkeys.com" target="_blank">vapidkeys.com</a> ou via la CLI <code>web-push generate-vapid-keys</code>.', 'medium-clone'); ?></div>
                    </div>

                    <div class="mc-pwa-field">
                        <div class="mc-pwa-toggle-wrap">
                            <label class="mc-pwa-toggle">
                                <input type="checkbox" name="mc_pwa_settings[push_enabled]" value="1" <?php checked($push_enabled); ?>>
                                <span class="mc-pwa-slider"></span>
                            </label>
                            <span class="mc-pwa-toggle-label"><?php _e('Activer les notifications push', 'medium-clone'); ?></span>
                        </div>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Clé publique VAPID', 'medium-clone'); ?></label>
                        <input type="text" name="mc_pwa_settings[vapid_public_key]"
                               value="<?php echo esc_attr($opts['vapid_public_key'] ?? ''); ?>"
                               placeholder="BEl62iUYgUivf2X5ML_tMaS2OJP8...">
                        <small><?php _e('Clé publique Base64 URL-safe. Visible côté client.', 'medium-clone'); ?></small>
                    </div>

                    <div class="mc-pwa-field">
                        <label><?php _e('Clé privée VAPID', 'medium-clone'); ?></label>
                        <input type="password" name="mc_pwa_settings[vapid_private_key]"
                               value="<?php echo esc_attr($opts['vapid_private_key'] ?? ''); ?>"
                               placeholder="•••••••••••••••••••••••••••••">
                        <small><?php _e('⚠ Gardez cette clé secrète. Elle ne doit jamais être exposée côté client.', 'medium-clone'); ?></small>
                    </div>

                    <?php
                    global $wpdb;
                    $push_table = $wpdb->prefix . 'mc_push_subscriptions';
                    $sub_count  = 0;
                    if ($wpdb->get_var("SHOW TABLES LIKE '{$push_table}'") == $push_table) {
                        $sub_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$push_table}");
                    }
                    ?>
                    <div class="mc-pwa-stat-grid" style="margin-top: 16px;">
                        <div class="mc-pwa-stat">
                            <span class="mc-stat-val"><?php echo $sub_count; ?></span>
                            <span class="mc-stat-label"><?php _e('Abonnés push', 'medium-clone'); ?></span>
                        </div>
                        <div class="mc-pwa-stat">
                            <span class="mc-stat-val"><?php echo $push_enabled ? '✅' : '❌'; ?></span>
                            <span class="mc-stat-label"><?php _e('Push activé', 'medium-clone'); ?></span>
                        </div>
                        <div class="mc-pwa-stat">
                            <span class="mc-stat-val"><?php echo !empty($opts['vapid_public_key']) ? '🔑' : '⚠️'; ?></span>
                            <span class="mc-stat-label"><?php _e('Clé VAPID', 'medium-clone'); ?></span>
                        </div>
                    </div>
                </div>

            </div><!-- /.mc-pwa-grid -->

            <div style="margin-top: 24px; display: flex; align-items: center; gap: 16px;">
                <button type="submit" class="mc-pwa-save-btn">
                    💾 <?php _e('Enregistrer la configuration PWA', 'medium-clone'); ?>
                </button>
                <a href="<?php echo esc_url(home_url('/manifest.json')); ?>" target="_blank"
                   style="font-size:.85rem; color:#10b981; text-decoration:none;">
                    📄 <?php _e('Voir manifest.json →', 'medium-clone'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=mc-pwa-diagnostic')); ?>"
                   style="font-size:.85rem; color:#6366f1; text-decoration:none;">
                    🔬 <?php _e('Diagnostic PWA →', 'medium-clone'); ?>
                </a>
            </div>

        </form>
    </div>
    <?php
}

// ─── PAGE : Notifications Push ────────────────────────────────────────────────
function mc_pwa_push_admin_page()
{
    if (!current_user_can('manage_options')) wp_die(__('Accès refusé.'));

    global $wpdb;
    $push_table = $wpdb->prefix . 'mc_push_subscriptions';
    mc_pwa_maybe_create_push_table();

    $subs = $wpdb->get_results("SELECT * FROM {$push_table} ORDER BY created_at DESC LIMIT 50");

    // Handle test notification send
    if (isset($_POST['mc_send_test_push']) && check_admin_referer('mc_push_test')) {
        echo '<div class="notice notice-info"><p>📤 ' . esc_html__('Test push envoyé (nécessite la librairie PHP web-push côté serveur).', 'medium-clone') . '</p></div>';
    }
    ?>
    <div class="wrap mc-pwa-wrap">
        <div class="mc-pwa-header">
            <div style="font-size:3rem; line-height:1;">🔔</div>
            <div>
                <h1><?php _e('Notifications Push', 'medium-clone'); ?></h1>
                <p><?php _e('Gérez les abonnements push et envoyez des notifications à vos utilisateurs.', 'medium-clone'); ?></p>
            </div>
        </div>

        <div class="mc-pwa-grid">
            <div class="mc-pwa-card">
                <h2><span class="dashicons dashicons-groups"></span> <?php _e('Abonnés actifs', 'medium-clone'); ?></h2>
                <p class="mc-desc"><?php printf(__('%d utilisateur(s) abonné(s) aux notifications push.', 'medium-clone'), count($subs)); ?></p>

                <?php if ($subs): ?>
                <table style="width:100%; border-collapse:collapse; font-size:.85rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #e5e7eb;">
                            <th style="text-align:left; padding:8px 4px; color:#6b7280;"><?php _e('User ID', 'medium-clone'); ?></th>
                            <th style="text-align:left; padding:8px 4px; color:#6b7280;"><?php _e('Endpoint', 'medium-clone'); ?></th>
                            <th style="text-align:left; padding:8px 4px; color:#6b7280;"><?php _e('Date', 'medium-clone'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($subs as $sub): ?>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:8px 4px;"><?php echo $sub->user_id ?: '(anonyme)'; ?></td>
                            <td style="padding:8px 4px; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                title="<?php echo esc_attr($sub->endpoint); ?>">
                                <?php echo esc_html(substr($sub->endpoint, 0, 55)) . '…'; ?>
                            </td>
                            <td style="padding:8px 4px; color:#9ca3af;"><?php echo esc_html(human_time_diff(strtotime($sub->created_at))); ?> ago</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="mc-pwa-notice info">
                    <span>ℹ️</span>
                    <div><?php _e('Aucun abonné pour l\'instant. Les utilisateurs doivent activer les notifications depuis leur navigateur.', 'medium-clone'); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mc-pwa-card">
                <h2><span class="dashicons dashicons-megaphone"></span> <?php _e('Envoyer une notification', 'medium-clone'); ?></h2>
                <p class="mc-desc"><?php _e('Envoyez une notification push à tous vos abonnés.', 'medium-clone'); ?></p>

                <div class="mc-pwa-notice">
                    <span>⚠️</span>
                    <div><?php _e('Nécessite la librairie <code>minishlink/web-push</code> via Composer et les clés VAPID configurées.', 'medium-clone'); ?></div>
                </div>

                <form method="post">
                    <?php wp_nonce_field('mc_push_test'); ?>
                    <div class="mc-pwa-field">
                        <label><?php _e('Titre', 'medium-clone'); ?></label>
                        <input type="text" name="push_title" value="<?php echo esc_attr(get_bloginfo('name')); ?>" placeholder="Titre de la notification">
                    </div>
                    <div class="mc-pwa-field">
                        <label><?php _e('Message', 'medium-clone'); ?></label>
                        <textarea name="push_body" rows="3" placeholder="Contenu de la notification..."><?php _e('Nouveau contenu disponible !', 'medium-clone'); ?></textarea>
                    </div>
                    <div class="mc-pwa-field">
                        <label><?php _e('URL de destination', 'medium-clone'); ?></label>
                        <input type="url" name="push_url" value="<?php echo esc_attr(home_url('/')); ?>">
                    </div>
                    <button type="submit" name="mc_send_test_push" class="mc-pwa-save-btn">
                        📤 <?php _e('Envoyer à tous les abonnés', 'medium-clone'); ?>
                    </button>
                </form>

                <hr style="margin:20px 0; border-color:#f3f4f6;">
                <p style="font-size:.82rem; color:#6b7280;"><strong><?php _e('Intégration PHP recommandée :', 'medium-clone'); ?></strong></p>
                <div class="mc-pwa-code">composer require minishlink/web-push</div>
                <p style="font-size:.78rem; color:#9ca3af; margin-top:8px;"><?php _e('Voir la documentation : <a href="https://github.com/web-push-libs/web-push-php" target="_blank">web-push-php</a>', 'medium-clone'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

// ─── PAGE : Diagnostic ────────────────────────────────────────────────────────
function mc_pwa_diagnostic_page()
{
    if (!current_user_can('manage_options')) wp_die(__('Accès refusé.'));

    $opts       = get_option('mc_pwa_settings', []);
    $theme_dir  = get_template_directory();
    $theme_uri  = get_template_directory_uri();
    $site_url   = home_url('/');
    $is_https   = is_ssl();

    $checks = [
        [
            'label'  => 'HTTPS activé',
            'status' => $is_https,
            'hint'   => 'Le Service Worker et les Push nécessitent HTTPS. Sur localhost, HTTP est autorisé.',
        ],
        [
            'label'  => 'manifest.json accessible',
            'status' => file_exists($theme_dir . '/manifest.json'),
            'hint'   => $theme_uri . '/manifest.json',
        ],
        [
            'label'  => 'Service Worker (sw.js)',
            'status' => file_exists($theme_dir . '/assets/js/sw.js'),
            'hint'   => $theme_uri . '/assets/js/sw.js',
        ],
        [
            'label'  => 'Icône 192x192',
            'status' => file_exists($theme_dir . '/assets/images/icons/icon-192x192.png'),
            'hint'   => 'Requise pour l\'install prompt Chrome/Android.',
        ],
        [
            'label'  => 'Icône 512x512',
            'status' => file_exists($theme_dir . '/assets/images/icons/icon-512x512.png'),
            'hint'   => 'Requise pour le splash screen.',
        ],
        [
            'label'  => 'Maskable icon',
            'status' => file_exists($theme_dir . '/assets/images/icons/maskable_icon.png'),
            'hint'   => 'Optionnel mais recommandé pour Android adaptive icons.',
        ],
        [
            'label'  => 'apple-touch-icon',
            'status' => file_exists($theme_dir . '/assets/images/icons/apple-touch-icon.png'),
            'hint'   => 'Requis pour l\'installation sur iPhone/iPad.',
        ],
        [
            'label'  => 'SW activé dans les settings',
            'status' => ($opts['sw_enabled'] ?? '1') === '1',
            'hint'   => 'Activez dans PWA → Configuration.',
        ],
        [
            'label'  => 'Nom de l\'app configuré',
            'status' => !empty($opts['app_name']),
            'hint'   => 'Configurez PWA → Configuration → Nom de l\'application.',
        ],
        [
            'label'  => 'Clé VAPID publique présente',
            'status' => !empty($opts['vapid_public_key']),
            'hint'   => 'Optionne si les push notifications ne sont pas utilisées.',
        ],
        [
            'label'  => 'Page offline créée',
            'status' => (bool) get_page_by_path('offline'),
            'hint'   => 'La page /offline doit exister pour le fallback offline.',
        ],
        [
            'label'  => 'Table push subscriptions',
            'status' => (bool) ($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '{$GLOBALS['wpdb']->prefix}mc_push_subscriptions'")),
            'hint'   => 'Créée automatiquement lors du premier abonnement push.',
        ],
    ];

    $passed = count(array_filter($checks, fn($c) => $c['status']));
    $total  = count($checks);
    $score  = round(($passed / $total) * 100);
    ?>
    <div class="wrap mc-pwa-wrap">
        <div class="mc-pwa-header">
            <div style="font-size:3rem; line-height:1;">🔬</div>
            <div>
                <h1><?php _e('Diagnostic PWA', 'medium-clone'); ?></h1>
                <p><?php printf(__('Score : %d/%d vérifications passées (%d%%)', 'medium-clone'), $passed, $total, $score); ?></p>
            </div>
        </div>

        <div class="mc-pwa-card mc-pwa-card-full" style="margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
                <div style="font-size:2.5rem; font-weight:900; color:<?php echo $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444'); ?>;">
                    <?php echo $score; ?>%
                </div>
                <div>
                    <strong style="font-size:1rem;"><?php _e('Score PWA', 'medium-clone'); ?></strong><br>
                    <span style="font-size:.85rem; color:#6b7280;"><?php echo $passed; ?>/<?php echo $total; ?> <?php _e('vérifications OK', 'medium-clone'); ?></span>
                </div>
                <div style="flex:1; background:#f3f4f6; border-radius:999px; height:10px; overflow:hidden;">
                    <div style="background:<?php echo $score >= 80 ? 'linear-gradient(90deg,#10b981,#059669)' : ($score >= 60 ? '#f59e0b' : '#ef4444'); ?>; width:<?php echo $score; ?>%; height:100%; border-radius:999px; transition:width .5s;"></div>
                </div>
            </div>

            <ul class="mc-pwa-checker">
                <?php foreach ($checks as $check): ?>
                <li>
                    <?php if ($check['status']): ?>
                        <span class="mc-status-ok">✅</span>
                    <?php else: ?>
                        <span class="mc-status-error">❌</span>
                    <?php endif; ?>
                    <span style="font-weight:600;"><?php echo esc_html($check['label']); ?></span>
                    <span style="color:#9ca3af; font-size:.8rem; margin-left:auto;"><?php echo esc_html($check['hint']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="mc-pwa-grid">
            <div class="mc-pwa-card">
                <h2><span class="dashicons dashicons-admin-links"></span> <?php _e('Liens utiles', 'medium-clone'); ?></h2>
                <ul style="font-size:.88rem; line-height:2; padding-left:0; list-style:none;">
                    <li>📄 <a href="<?php echo esc_url($site_url . 'manifest.json'); ?>" target="_blank">manifest.json</a></li>
                    <li>⚙️ <a href="<?php echo esc_url($theme_uri . '/assets/js/sw.js'); ?>" target="_blank">sw.js</a></li>
                    <li>🔗 <a href="<?php echo esc_url(admin_url('admin.php?page=mc-pwa-settings')); ?>">Retour à la configuration</a></li>
                    <li>🔔 <a href="<?php echo esc_url(admin_url('admin.php?page=mc-pwa-push')); ?>">Notifications Push</a></li>
                    <li>📊 <a href="https://web.dev/measure/" target="_blank">Mesurer sur web.dev →</a></li>
                    <li>🔦 <a href="https://pagespeed.web.dev/" target="_blank">PageSpeed Insights →</a></li>
                </ul>
            </div>

            <div class="mc-pwa-card">
                <h2><span class="dashicons dashicons-info"></span> <?php _e('Prochaines étapes', 'medium-clone'); ?></h2>
                <ul style="font-size:.88rem; line-height:1.9; padding-left:0; list-style:none;">
                    <?php if (!$is_https): ?>
                    <li>🔒 <?php _e('Activez HTTPS sur votre serveur (Let\'s Encrypt recommandé).', 'medium-clone'); ?></li>
                    <?php endif; ?>
                    <?php if (empty($opts['vapid_public_key'])): ?>
                    <li>🔑 <?php printf(__('Générez des clés VAPID sur <a href="%s" target="_blank">vapidkeys.com</a>.', 'medium-clone'), 'https://vapidkeys.com'); ?></li>
                    <?php endif; ?>
                    <li>📸 <?php _e('Ajoutez des screenshots (1280×720 et 390×844) pour enrichir l\'install dialog.', 'medium-clone'); ?></li>
                    <li>📦 <?php _e('Installez <code>minishlink/web-push</code> via Composer pour les envois push PHP.', 'medium-clone'); ?></li>
                    <li>📊 <?php _e('Lancez un audit Lighthouse dans Chrome DevTools pour valider le score PWA.', 'medium-clone'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}
